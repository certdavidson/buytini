<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

/**
 * Internal-link health scanner: finds <a href> targets in entity descriptions
 * (products, categories, information, articles, manufacturers) and HEAD-pings
 * each unique URL to check it is reachable.
 *
 * Background-friendly: results are cached per scan run in
 * `kit_seo_broken_links` (auto-created on first run). Re-running overwrites.
 *
 * Security: only outbound HEAD requests, max 5s timeout per URL,
 * cap of `module_oc_kit_seo_core_broken_links_limit` URLs per run
 * (default 200) to avoid runaway scans.
 *
 * Usage:
 *   $scanner = new BrokenLinksScanner($db, $config);
 *   $report  = $scanner->scan();   // ['checked' => N, 'broken' => [...]]
 */
class BrokenLinksScanner
{
    private const TABLE = 'kit_seo_broken_links';

    private $db;
    private $config;

    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Ensure the result table exists. Idempotent — safe to call from scan().
     */
    public function ensureSchema(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                `link_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `url`         VARCHAR(2048) NOT NULL,
                `entity_type` VARCHAR(32) NOT NULL,
                `entity_id`   INT(11) NOT NULL,
                `entity_name` VARCHAR(512) NOT NULL DEFAULT '',
                `status_code` SMALLINT(4) NOT NULL DEFAULT 0,
                `error`       VARCHAR(255) NOT NULL DEFAULT '',
                `checked_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`link_id`),
                KEY `idx_status` (`status_code`),
                KEY `idx_url`    (`url`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Scan all entity descriptions for <a href> targets, check each unique URL.
     */
    public function scan(): array
    {
        $this->ensureSchema();
        // Wipe previous run
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . self::TABLE . "`");

        $limit = max(1, (int)($this->config->get('module_oc_kit_seo_core_broken_links_limit') ?: 200));

        $occurrences = $this->collectOccurrences($limit);
        $byUrl = [];
        foreach ($occurrences as $row) {
            $byUrl[$row['url']][] = $row;
        }

        $broken = [];
        $checked = 0;
        foreach ($byUrl as $url => $rows) {
            if ($checked >= $limit) break;
            $checked++;
            [$code, $error] = $this->headProbe($url);
            if ($code >= 200 && $code < 400) continue;
            foreach ($rows as $r) {
                $this->insertResult($url, $r, $code, $error);
                $broken[] = ['url' => $url, 'code' => $code, 'error' => $error] + $r;
            }
        }

        return ['checked' => $checked, 'broken' => $broken];
    }

    /**
     * Read the latest scan report from DB.
     */
    public function getResults(int $limit = 500): array
    {
        $this->ensureSchema();
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "` ORDER BY `link_id` DESC LIMIT " . (int)$limit
        )->rows;
        return $rows;
    }

    public function getCount(): int
    {
        $this->ensureSchema();
        $r = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . self::TABLE . "`")->row;
        return (int)($r['cnt'] ?? 0);
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    /**
     * Pull <a href> URLs from product/category/information/article/manufacturer
     * description fields. Returns [{url, entity_type, entity_id, entity_name}].
     */
    private function collectOccurrences(int $limit): array
    {
        // For each entity type — define how to fetch (id, name, description) tuples.
        // Manufacturer is special: oc_manufacturer_description has no `name` column
        // (name lives in oc_manufacturer), so we JOIN.
        $cap = (int)($limit * 4); // over-fetch — many rows lack actual <a>
        $likeAhref = "LIKE '%<a %href=%'";
        $sources = [
            'product' => [
                'sql' => "SELECT `product_id` AS id, `name` AS name, `description`
                          FROM `" . DB_PREFIX . "product_description`
                          WHERE `description` {$likeAhref} LIMIT {$cap}",
                'table_check' => 'product_description',
            ],
            'category' => [
                'sql' => "SELECT `category_id` AS id, `name` AS name, `description`
                          FROM `" . DB_PREFIX . "category_description`
                          WHERE `description` {$likeAhref} LIMIT {$cap}",
                'table_check' => 'category_description',
            ],
            'information' => [
                'sql' => "SELECT `information_id` AS id, `title` AS name, `description`
                          FROM `" . DB_PREFIX . "information_description`
                          WHERE `description` {$likeAhref} LIMIT {$cap}",
                'table_check' => 'information_description',
            ],
            'manufacturer' => [
                // Name comes from parent table — JOIN
                'sql' => "SELECT m.`manufacturer_id` AS id, m.`name` AS name, md.`description`
                          FROM `" . DB_PREFIX . "manufacturer_description` md
                          JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = md.manufacturer_id
                          WHERE md.`description` {$likeAhref} LIMIT {$cap}",
                'table_check' => 'manufacturer_description',
            ],
        ];

        $found = [];
        foreach ($sources as $type => $cfg) {
            $tbl = DB_PREFIX . $cfg['table_check'];
            if (!$this->db->query("SHOW TABLES LIKE '" . $tbl . "'")->num_rows) continue;

            $rows = $this->db->query($cfg['sql'])->rows;
            foreach ($rows as $r) {
                $urls = $this->extractHrefs((string)$r['description']);
                foreach ($urls as $u) {
                    $found[] = [
                        'url'         => $u,
                        'entity_type' => $type,
                        'entity_id'   => (int)$r['id'],
                        'entity_name' => mb_substr((string)$r['name'], 0, 250),
                    ];
                }
            }
        }
        return $found;
    }

    private function extractHrefs(string $html): array
    {
        if ($html === '') return [];
        if (!preg_match_all('#<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']#i', $html, $m)) return [];
        $out = [];
        foreach ($m[1] as $u) {
            $u = trim($u);
            if ($u === '' || $u[0] === '#') continue;
            if (preg_match('#^(?:javascript|mailto|tel):#i', $u)) continue;
            $out[] = $u;
        }
        return array_values(array_unique($out));
    }

    /**
     * HEAD-probe a URL with short timeout. Returns [status_code, error_string].
     * Relative URLs are resolved against config_url.
     */
    private function headProbe(string $url): array
    {
        if (!preg_match('#^https?://#i', $url)) {
            $base = (string)$this->config->get('config_ssl') ?: (string)$this->config->get('config_url');
            $url  = rtrim($base, '/') . '/' . ltrim($url, '/');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'OcKit SEO Core / Broken-Links',
        ]);
        curl_exec($ch);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 405 Method Not Allowed → retry with GET
        if ($code === 405) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            curl_exec($ch);
            $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
        }
        return [$code, $error];
    }

    private function insertResult(string $url, array $occ, int $code, string $error): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . self::TABLE . "`
             (`url`,`entity_type`,`entity_id`,`entity_name`,`status_code`,`error`)
             VALUES (
                '" . $this->db->escape($url) . "',
                '" . $this->db->escape($occ['entity_type']) . "',
                " . (int)$occ['entity_id'] . ",
                '" . $this->db->escape($occ['entity_name']) . "',
                " . (int)$code . ",
                '" . $this->db->escape($error) . "')"
        );
    }
}
