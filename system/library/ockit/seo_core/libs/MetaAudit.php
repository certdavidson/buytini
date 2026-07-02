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
 * DB-based and optional crawl-based SEO audit.
 *
 * DB audit runs instantly without HTTP requests.
 * Crawl audit is optional, gated behind a config toggle,
 * and warns the user about server load before enabling.
 *
 * Results stored in oc_kit_seo_audit_results (overwritten per run).
 */
class MetaAudit
{
    private $db;
    private $config;
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Run DB-based audit for all entity types.
     * Returns grouped results by severity.
     */
    public function runDbAudit(int $languageId, int $storeId = 0): array
    {
        $this->ensureSchema();

        $issues = [];

        $issues = array_merge($issues, $this->auditProducts($languageId, $storeId));
        $issues = array_merge($issues, $this->auditCategories($languageId, $storeId));
        $issues = array_merge($issues, $this->auditManufacturers($languageId, $storeId));
        $issues = array_merge($issues, $this->auditInformation($languageId, $storeId));
        $issues = array_merge($issues, $this->auditOrphanKeywords($storeId));
        $issues = array_merge($issues, $this->auditDuplicateTitles($languageId, $storeId));
        $issues = array_merge($issues, $this->auditDuplicateDescriptions($languageId, $storeId));
        $issues = array_merge($issues, $this->auditSeoUrls($languageId, $storeId));

        $this->persistResults($issues, $languageId, $storeId);

        return $issues;
    }

    /**
     * Lazily add new columns/indexes if the table pre-dates them.
     */
    public function ensureSchema(): void
    {
        $cols = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "kit_seo_audit_results` LIKE 'status'");
        if (!$cols->num_rows) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "kit_seo_audit_results`
                 ADD COLUMN `status` VARCHAR(16) NOT NULL DEFAULT 'new' AFTER `detail`,
                 ADD KEY `idx_status` (`status`)"
            );
        }
    }

    public function updateStatus(array $ids, string $status): int
    {
        if (!$ids) return 0;
        if (!in_array($status, ['new', 'in_progress', 'fixed', 'ignored'], true)) return 0;

        $this->ensureSchema();

        $safe = implode(',', array_map('intval', $ids));
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_seo_audit_results`
             SET `status` = '" . $this->db->escape($status) . "'
             WHERE `result_id` IN ({$safe})"
        );
        return (int)$this->db->countAffected();
    }

    /**
     * Optional crawl-based audit. Only call if config toggle is enabled.
     * @param string[] $urls
     */
    public function runCrawlAudit(array $urls, int $languageId): array
    {
        $issues    = [];
        $limit     = (int)($this->config->get('module_oc_kit_seo_core_crawl_limit')     ?: 100);
        $pause     = (int)($this->config->get('module_oc_kit_seo_core_crawl_pause_ms')  ?: 500);
        $userAgent = (string)($this->config->get('module_oc_kit_seo_core_crawl_ua')
                     ?: 'OcKit SEO Core Crawler/1.0');

        $checked = 0;
        foreach (array_slice($urls, 0, $limit) as $url) {
            $html = $this->fetchPage($url, $userAgent);
            if (!$html) continue;

            $h1s = $this->parseH1($html);
            if (count($h1s) === 0) {
                $issues[] = $this->issue('crawl', 0, $url, 'missing_h1', 'error', 'No <h1> found on page ' . $url);
            } elseif (count($h1s) > 1) {
                $issues[] = $this->issue('crawl', 0, $url, 'multiple_h1', 'warning', 'Multiple <h1> on page ' . $url);
            }

            $imagesNoAlt = $this->parseImagesWithoutAlt($html);
            if ($imagesNoAlt) {
                $issues[] = $this->issue('crawl', 0, $url, 'images_no_alt', 'warning',
                    count($imagesNoAlt) . ' image(s) without alt on ' . $url);
            }

            $checked++;
            if ($pause > 0) usleep($pause * 1000);
        }

        return $issues;
    }

    public function fetchPage(string $url, string $userAgent = 'OcKit SEO Core Crawler/1.0'): string
    {
        if (!function_exists('curl_init')) return '';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $html = (string)curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    public function parseH1(string $html): array
    {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m);
        return array_map('strip_tags', $m[1] ?? []);
    }

    public function parseH2H3(string $html): array
    {
        preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/si', $html, $m);
        return array_map('strip_tags', $m[1] ?? []);
    }

    public function parseImagesWithoutAlt(string $html): array
    {
        preg_match_all('/<img[^>]+>/si', $html, $m);
        $noAlt = [];
        foreach ($m[0] ?? [] as $tag) {
            if (!preg_match('/\balt\s*=/i', $tag)) {
                $noAlt[] = $tag;
            }
        }
        return $noAlt;
    }

    public function getLastResults(int $languageId, int $storeId = 0, string $severity = ''): array
    {
        $where = "WHERE `store_id` = {$storeId} AND `language_id` = {$languageId}";
        if ($severity) $where .= " AND `severity` = '" . $this->db->escape($severity) . "'";
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_audit_results` {$where} ORDER BY `severity`, `issue_type`"
        )->rows;
    }

    /**
     * Paginated, grouped results for the audit UI.
     * Groups by entity (entity_type + entity_id), page by groups, then loads all
     * issues for the selected group slice.
     *
     * @return array{
     *     groups: array<array{
     *         entity_type:string, entity_id:int, entity_name:string,
     *         severity:string, status:string,
     *         issues: array<array{result_id:int, issue_type:string, severity:string, detail:string, status:string}>,
     *         ids:int[]
     *     }>,
     *     total_groups:int, total_issues:int, page:int, per_page:int
     * }
     */
    public function getGroupedResults(
        int $languageId, int $storeId = 0,
        string $severity = '', string $entityType = '',
        int $page = 1, int $perPage = 50
    ): array {
        $this->ensureSchema();

        $where = "WHERE `store_id` = {$storeId} AND `language_id` = {$languageId}";
        if ($severity)   $where .= " AND `severity` = '" . $this->db->escape($severity) . "'";
        if ($entityType) $where .= " AND `entity_type` = '" . $this->db->escape($entityType) . "'";

        $totalIssues = (int)$this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_audit_results` {$where}"
        )->row['cnt'];

        // Severity order: error > warning > info (numeric for ORDER BY)
        $sevExpr = "CASE `severity` WHEN 'error' THEN 2 WHEN 'warning' THEN 1 ELSE 0 END";
        // Worst status per group wins: new < in_progress < fixed < ignored
        $stExpr  = "CASE `status` WHEN 'new' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'fixed' THEN 2 WHEN 'ignored' THEN 3 ELSE 0 END";

        $totalGroups = (int)$this->db->query(
            "SELECT COUNT(*) AS cnt FROM (
                SELECT `entity_type`, `entity_id`
                FROM `" . DB_PREFIX . "kit_seo_audit_results` {$where}
                GROUP BY `entity_type`, `entity_id`
            ) g"
        )->row['cnt'];

        $perPage = max(1, min(200, $perPage));
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;

        $rows = $this->db->query(
            "SELECT `entity_type`, `entity_id`,
                    MAX(`entity_name`) AS entity_name,
                    MAX({$sevExpr}) AS sev_ord,
                    MIN({$stExpr})  AS st_ord,
                    COUNT(*)        AS issue_count
             FROM `" . DB_PREFIX . "kit_seo_audit_results` {$where}
             GROUP BY `entity_type`, `entity_id`
             ORDER BY sev_ord DESC, `entity_type`, `entity_id`
             LIMIT {$perPage} OFFSET {$offset}"
        )->rows;

        if (!$rows) {
            return [
                'groups' => [], 'total_groups' => $totalGroups,
                'total_issues' => $totalIssues, 'page' => $page, 'per_page' => $perPage,
            ];
        }

        $sevMap = [0 => 'info', 1 => 'warning', 2 => 'error'];
        $stMap  = [0 => 'new', 1 => 'in_progress', 2 => 'fixed', 3 => 'ignored'];

        // Build IN-clause for pulling all issues of the paged groups
        $pairs = [];
        foreach ($rows as $r) {
            $pairs[] = "('" . $this->db->escape($r['entity_type']) . "'," . (int)$r['entity_id'] . ")";
        }
        $pairSql = implode(',', $pairs);

        $issueRows = $this->db->query(
            "SELECT `result_id`, `entity_type`, `entity_id`, `entity_name`,
                    `issue_type`, `severity`, `detail`, `status`
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             {$where}
               AND (`entity_type`, `entity_id`) IN ({$pairSql})
             ORDER BY {$sevExpr} DESC"
        )->rows;

        $groups = [];
        foreach ($rows as $r) {
            $key = $r['entity_type'] . ':' . (int)$r['entity_id'];
            $groups[$key] = [
                'entity_type' => $r['entity_type'],
                'entity_id'   => (int)$r['entity_id'],
                'entity_name' => $r['entity_name'],
                'severity'    => $sevMap[(int)$r['sev_ord']] ?? 'info',
                'status'      => $stMap[(int)$r['st_ord']]   ?? 'new',
                'issues'      => [],
                'ids'         => [],
            ];
        }
        foreach ($issueRows as $ir) {
            $key = $ir['entity_type'] . ':' . (int)$ir['entity_id'];
            if (!isset($groups[$key])) continue;
            $groups[$key]['issues'][] = [
                'result_id'  => (int)$ir['result_id'],
                'issue_type' => $ir['issue_type'],
                'severity'   => $ir['severity'],
                'detail'     => $ir['detail'],
                'status'     => $ir['status'] ?: 'new',
            ];
            $groups[$key]['ids'][] = (int)$ir['result_id'];
        }

        return [
            'groups'       => array_values($groups),
            'total_groups' => $totalGroups,
            'total_issues' => $totalIssues,
            'page'         => $page,
            'per_page'     => $perPage,
        ];
    }

    /**
     * Compute SEO score per entity (0-100) from currently stored audit results.
     * Score = 100 - (errors*30 + warnings*10 + infos*2), clamped.
     *
     * @return array{0:int,1:int,2:int} [score, errors_count, warnings_count]
     */
    public function getEntityScore(string $entityType, int $entityId, int $languageId, int $storeId = 0): array
    {
        $rows = $this->db->query(
            "SELECT `severity`, COUNT(*) AS cnt
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = {$storeId} AND `language_id` = {$languageId}
               AND `entity_type` = '" . $this->db->escape($entityType) . "'
               AND `entity_id` = " . (int)$entityId . "
               AND `status` <> 'fixed' AND `status` <> 'ignored'
             GROUP BY `severity`"
        )->rows;

        $by = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($rows as $r) {
            $by[$r['severity']] = (int)$r['cnt'];
        }

        $score = 100 - ($by['error'] * 30 + $by['warning'] * 10 + $by['info'] * 2);
        $score = max(0, min(100, $score));

        return [$score, $by['error'], $by['warning']];
    }

    public function getSummary(int $languageId, int $storeId = 0): array
    {
        $this->ensureSchema();
        $rows = $this->db->query(
            "SELECT `severity`, COUNT(*) AS cnt
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = {$storeId} AND `language_id` = {$languageId}
             GROUP BY `severity`"
        )->rows;
        $out = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($rows as $r) $out[$r['severity']] = (int)$r['cnt'];
        return $out;
    }

    /**
     * Aggregate 0–100 SEO score for the store/language. Calculation matches
     * `getEntityScore` formula, applied to overall counts:
     *   100 - (errors * 30 + warnings * 10 + info * 2), normalized to entity count.
     * If no audit has been run yet → null (UI renders "—").
     */
    public function getOverallScore(int $languageId, int $storeId = 0): ?int
    {
        $this->ensureSchema();
        if ($this->getLastRunDate($storeId) === null) return null;

        $sum = $this->getSummary($languageId, $storeId);

        // Normalise penalty by total entities (rough proxy: number of products+categories)
        $totalRow = $this->db->query(
            "SELECT (SELECT COUNT(*) FROM `" . DB_PREFIX . "product` WHERE `status` = 1)
                  + (SELECT COUNT(*) FROM `" . DB_PREFIX . "category` WHERE `status` = 1) AS total"
        )->row;
        $total = max(1, (int)($totalRow['total'] ?? 1));

        $penalty = ($sum['error'] * 30 + $sum['warning'] * 10 + $sum['info'] * 2) / $total;
        $score   = (int)round(100 - $penalty);
        return max(0, min(100, $score));
    }

    public function getLastRunDate(int $storeId = 0): ?string
    {
        $row = $this->db->query(
            "SELECT MAX(`created_at`) AS dt FROM `" . DB_PREFIX . "kit_seo_audit_results` WHERE `store_id` = {$storeId}"
        )->row;
        return $row['dt'] ?: null;
    }

    // ─── Private audit checks ─────────────────────────────────────────────────

    private function auditProducts(int $langId, int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT p.product_id, pd.name, pd.meta_title, pd.meta_description, pd.description,
                    p.manufacturer_id, p.image, p.model, p.sku, p.price, p.weight
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON pd.product_id = p.product_id AND pd.language_id = {$langId}
             WHERE p.status = 1"
        )->rows;

        foreach ($rows as $r) {
            $id    = (int)$r['product_id'];
            $name  = (string)$r['name'];
            $title = (string)$r['meta_title'];
            $desc  = (string)$r['meta_description'];

            if (empty($title)) {
                $issues[] = $this->issue('product', $id, $name, 'missing_title', 'error', 'Відсутній meta_title');
            } else {
                $tlen = mb_strlen($title, 'UTF-8');
                if ($tlen < 30)      $issues[] = $this->issue('product', $id, $name, 'title_too_short', 'warning', "meta_title < 30 символів ({$tlen})");
                elseif ($tlen > 65)  $issues[] = $this->issue('product', $id, $name, 'title_too_long', 'warning',  "meta_title > 65 символів ({$tlen})");
                if ($name !== '' && mb_strtolower($title, 'UTF-8') === mb_strtolower($name, 'UTF-8')) {
                    $issues[] = $this->issue('product', $id, $name, 'title_equals_name', 'info', 'meta_title дублює назву товару — немає SEO-значення');
                }
            }

            if (empty($desc)) {
                $issues[] = $this->issue('product', $id, $name, 'missing_description', 'error', 'Відсутній meta_description');
            } else {
                $dlen = mb_strlen($desc, 'UTF-8');
                if ($dlen < 70)       $issues[] = $this->issue('product', $id, $name, 'description_too_short', 'warning', "meta_description < 70 символів ({$dlen})");
                elseif ($dlen > 165)  $issues[] = $this->issue('product', $id, $name, 'description_too_long', 'warning',  "meta_description > 165 символів ({$dlen})");
            }

            $plain = trim(strip_tags((string)$r['description']));
            if ($plain === '') {
                $issues[] = $this->issue('product', $id, $name, 'no_body_description', 'warning', 'Порожній опис товару');
            } elseif (mb_strlen($plain, 'UTF-8') < 100) {
                $issues[] = $this->issue('product', $id, $name, 'body_too_short', 'info', 'Опис товару < 100 символів');
            }

            if ((string)$r['description'] !== '' && preg_match_all('/<img\b[^>]*>/i', (string)$r['description'], $imgs)) {
                $noAlt = 0;
                foreach ($imgs[0] as $tag) {
                    if (!preg_match('/\balt\s*=\s*["\'][^"\']+["\']/i', $tag)) $noAlt++;
                }
                if ($noAlt > 0) {
                    $issues[] = $this->issue('product', $id, $name, 'images_no_alt', 'warning', "{$noAlt} зобр. в описі без alt");
                }
            }

            if (empty($r['image'])) {
                $issues[] = $this->issue('product', $id, $name, 'no_image', 'warning', 'Товар без головного зображення');
            }

            if ((int)$r['manufacturer_id'] === 0) {
                $issues[] = $this->issue('product', $id, $name, 'no_brand', 'info', 'Товар без виробника');
            }

            $catCount = (int)$this->db->query(
                "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = {$id}"
            )->row['cnt'];
            if ($catCount === 0) {
                $issues[] = $this->issue('product', $id, $name, 'no_category', 'warning', 'Товар не прив’язаний до жодної категорії');
            }

            if ((float)$r['price'] <= 0) {
                $issues[] = $this->issue('product', $id, $name, 'no_price', 'info', 'Ціна товару дорівнює 0');
            }

            if (trim((string)$r['model']) === '') {
                $issues[] = $this->issue('product', $id, $name, 'no_model', 'info', 'Порожнє поле «Модель»');
            }

            // Check keyword exists
            $kw = $this->db->query(
                "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `query` = 'product_id={$id}' AND `store_id` = {$storeId} AND `language_id` = {$langId} LIMIT 1"
            )->row;
            if (!$kw) {
                $issues[] = $this->issue('product', $id, $name, 'missing_seo_url', 'error', 'Відсутній SEO URL (keyword)');
            }
        }

        return $issues;
    }

    private function auditCategories(int $langId, int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT c.category_id, cd.name, cd.meta_title, cd.meta_description, cd.description, c.image
             FROM `" . DB_PREFIX . "category` c
             LEFT JOIN `" . DB_PREFIX . "category_description` cd
               ON cd.category_id = c.category_id AND cd.language_id = {$langId}
             WHERE c.status = 1"
        )->rows;

        foreach ($rows as $r) {
            $id   = (int)$r['category_id'];
            $name = (string)$r['name'];

            if (empty($r['meta_title'])) {
                $issues[] = $this->issue('category', $id, $name, 'missing_title', 'error', 'Відсутній meta_title');
            }
            if (empty($r['meta_description'])) {
                $issues[] = $this->issue('category', $id, $name, 'missing_description', 'error', 'Відсутній meta_description');
            }
            if (empty($r['image'])) {
                $issues[] = $this->issue('category', $id, $name, 'no_image', 'info', 'Категорія без зображення');
            }
            if (trim(strip_tags((string)$r['description'])) === '') {
                $issues[] = $this->issue('category', $id, $name, 'no_body_description', 'info', 'Порожній опис категорії');
            }

            $prodCount = (int)$this->db->query(
                "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product_to_category` p2c
                 JOIN `" . DB_PREFIX . "product` p ON p.product_id = p2c.product_id
                 WHERE p2c.category_id = {$id} AND p.status = 1"
            )->row['cnt'];
            if ($prodCount === 0) {
                $issues[] = $this->issue('category', $id, $name, 'empty_category', 'warning', 'Категорія не містить активних товарів');
            }

            $kw = $this->db->query(
                "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `query` = 'category_id={$id}' AND `store_id` = {$storeId} AND `language_id` = {$langId} LIMIT 1"
            )->row;
            if (!$kw) {
                $issues[] = $this->issue('category', $id, $name, 'missing_seo_url', 'error', 'Відсутній SEO URL');
            }
        }

        return $issues;
    }

    private function auditManufacturers(int $langId, int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer`"
        )->rows;

        foreach ($rows as $r) {
            $id   = (int)$r['manufacturer_id'];
            $name = (string)$r['name'];

            $kw = $this->db->query(
                "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `query` = 'manufacturer_id={$id}' AND `store_id` = {$storeId} AND `language_id` = {$langId} LIMIT 1"
            )->row;
            if (!$kw) {
                $issues[] = $this->issue('manufacturer', $id, $name, 'missing_seo_url', 'warning', 'Відсутній SEO URL для виробника');
            }
        }

        return $issues;
    }

    private function auditOrphanKeywords(int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT `seo_url_id`, `keyword`, `query` FROM `" . DB_PREFIX . "seo_url`
             WHERE `store_id` = {$storeId}"
        )->rows;

        foreach ($rows as $r) {
            if (!preg_match('/^(\w+)_id=(\d+)$/', $r['query'], $m)) continue;

            $entityType = $m[1];
            $entityId   = (int)$m[2];
            $table      = DB_PREFIX . $entityType;

            try {
                $result = @$this->db->query(
                    "SELECT COUNT(*) AS cnt FROM `{$table}` WHERE `{$entityType}_id` = {$entityId} LIMIT 1"
                );
                $exists = $result ? ($result->row['cnt'] ?? 0) : 0;
            } catch (\Throwable $e) {
                continue;
            }

            if (!(int)$exists) {
                $issues[] = $this->issue('seo_url', (int)$r['seo_url_id'], $r['keyword'],
                    'orphan_keyword', 'warning',
                    "Keyword '{$r['keyword']}' → '{$r['query']}' але entity не існує");
            }
        }

        return $issues;
    }

    private function auditDuplicateTitles(int $langId, int $storeId): array
    {
        $issues = [];

        foreach (['product' => 'product_description', 'category' => 'category_description'] as $type => $table) {
            $idField = $type . '_id';
            $rows    = $this->db->query(
                "SELECT `meta_title`, COUNT(*) AS cnt, GROUP_CONCAT({$idField}) AS ids
                 FROM `" . DB_PREFIX . $table . "`
                 WHERE `language_id` = {$langId} AND `meta_title` != ''
                 GROUP BY `meta_title`
                 HAVING cnt > 1"
            )->rows;

            $names = $this->fetchNames($type, $langId);

            foreach ($rows as $r) {
                $ids = array_filter(array_map('intval', explode(',', (string)$r['ids'])));
                foreach ($ids as $id) {
                    $issues[] = $this->issue($type, $id, $names[$id] ?? $r['meta_title'],
                        'duplicate_title', 'warning',
                        "meta_title дублюється у {$r['cnt']} записах: '{$r['meta_title']}'");
                }
            }
        }

        return $issues;
    }

    /** @return array<int,string> entity_id → name */
    private function fetchNames(string $type, int $langId): array
    {
        $table  = $type . '_description';
        $idCol  = $type . '_id';
        $rows   = $this->db->query(
            "SELECT `{$idCol}` AS id, `name` FROM `" . DB_PREFIX . $table . "` WHERE `language_id` = {$langId}"
        )->rows;
        $out = [];
        foreach ($rows as $r) $out[(int)$r['id']] = (string)$r['name'];
        return $out;
    }

    private function issue(string $type, int $id, string $name, string $issueType, string $severity, string $detail): array
    {
        return [
            'entity_type' => $type,
            'entity_id'   => $id,
            'entity_name' => $name,
            'issue_type'  => $issueType,
            'severity'    => $severity,
            'detail'      => $detail,
        ];
    }

    private function persistResults(array $issues, int $langId, int $storeId): void
    {
        // Snapshot existing statuses to preserve across re-runs
        $prev = $this->db->query(
            "SELECT `entity_type`, `entity_id`, `issue_type`, `status`
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = {$storeId} AND `language_id` = {$langId}"
        )->rows;

        $statusMap = [];
        foreach ($prev as $p) {
            $key = $p['entity_type'] . ':' . $p['entity_id'] . ':' . $p['issue_type'];
            $statusMap[$key] = $p['status'] ?: 'new';
        }

        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = {$storeId} AND `language_id` = {$langId}"
        );

        foreach ($issues as $issue) {
            $key    = $issue['entity_type'] . ':' . (int)$issue['entity_id'] . ':' . $issue['issue_type'];
            $status = $statusMap[$key] ?? 'new';

            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_seo_audit_results`
                 (`store_id`, `language_id`, `entity_type`, `entity_id`, `entity_name`, `issue_type`, `severity`, `detail`, `status`)
                 VALUES (
                   {$storeId}, {$langId},
                   '" . $this->db->escape($issue['entity_type']) . "',
                   " . (int)$issue['entity_id'] . ",
                   '" . $this->db->escape($issue['entity_name']) . "',
                   '" . $this->db->escape($issue['issue_type']) . "',
                   '" . $this->db->escape($issue['severity']) . "',
                   '" . $this->db->escape($issue['detail']) . "',
                   '" . $this->db->escape($status) . "'
                 )"
            );
        }
    }

    // ─── Extended audit checks (9.9) ──────────────────────────────────────────

    private function auditInformation(int $langId, int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT i.information_id, id.title, id.meta_title, id.meta_description, id.description
             FROM `" . DB_PREFIX . "information` i
             LEFT JOIN `" . DB_PREFIX . "information_description` id
               ON id.information_id = i.information_id AND id.language_id = {$langId}
             WHERE i.status = 1"
        )->rows;

        foreach ($rows as $r) {
            $id   = (int)$r['information_id'];
            $name = (string)$r['title'];

            if (empty($r['meta_title'])) {
                $issues[] = $this->issue('information', $id, $name, 'missing_title', 'error', 'Відсутній meta_title');
            }
            if (empty($r['meta_description'])) {
                $issues[] = $this->issue('information', $id, $name, 'missing_description', 'error', 'Відсутній meta_description');
            }
            if (mb_strlen(strip_tags((string)$r['description']), 'UTF-8') < 200) {
                $issues[] = $this->issue('information', $id, $name, 'short_content', 'warning', 'Вміст сторінки < 200 символів');
            }

            $kw = $this->db->query(
                "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `query` = 'information_id={$id}' AND `store_id` = {$storeId} AND `language_id` = {$langId} LIMIT 1"
            )->row;
            if (!$kw) {
                $issues[] = $this->issue('information', $id, $name, 'missing_seo_url', 'warning', 'Відсутній SEO URL');
            }
        }

        return $issues;
    }

    private function auditDuplicateDescriptions(int $langId, int $storeId): array
    {
        $issues = [];

        foreach (['product' => 'product_description', 'category' => 'category_description'] as $type => $table) {
            $idField = $type . '_id';
            $rows    = $this->db->query(
                "SELECT `meta_description`, COUNT(*) AS cnt, GROUP_CONCAT({$idField}) AS ids
                 FROM `" . DB_PREFIX . $table . "`
                 WHERE `language_id` = {$langId} AND `meta_description` != ''
                 GROUP BY `meta_description`
                 HAVING cnt > 1"
            )->rows;

            $names = $this->fetchNames($type, $langId);

            foreach ($rows as $r) {
                $ids     = array_filter(array_map('intval', explode(',', (string)$r['ids'])));
                $descSnip = mb_substr((string)$r['meta_description'], 0, 80);
                foreach ($ids as $id) {
                    $issues[] = $this->issue($type, $id, $names[$id] ?? $descSnip,
                        'duplicate_description', 'warning',
                        "meta_description дублюється у {$r['cnt']} записах: '{$descSnip}…'");
                }
            }
        }

        return $issues;
    }

    private function auditSeoUrls(int $langId, int $storeId): array
    {
        $issues = [];

        $rows = $this->db->query(
            "SELECT `seo_url_id`, `keyword`, `query` FROM `" . DB_PREFIX . "seo_url`
             WHERE `store_id` = {$storeId} AND `language_id` = {$langId}"
        )->rows;

        $seen = [];
        foreach ($rows as $r) {
            $id  = (int)$r['seo_url_id'];
            $kw  = (string)$r['keyword'];
            $len = strlen($kw);

            if ($len > 80) {
                $issues[] = $this->issue('seo_url', $id, $kw, 'keyword_too_long', 'warning', "SEO URL > 80 символів ({$len})");
            }
            if ($len > 0 && $len < 3) {
                $issues[] = $this->issue('seo_url', $id, $kw, 'keyword_too_short', 'warning', "SEO URL < 3 символів ({$kw})");
            }
            if ($kw !== '' && preg_match('/[A-Z]/', $kw)) {
                $issues[] = $this->issue('seo_url', $id, $kw, 'uppercase_in_keyword', 'info', "SEO URL містить великі літери: {$kw}");
            }
            if ($kw !== '' && preg_match('/[^a-z0-9\-\/_]/', $kw)) {
                $issues[] = $this->issue('seo_url', $id, $kw, 'special_chars_in_keyword', 'info', "SEO URL містить спецсимволи: {$kw}");
            }

            $key = $kw . '|' . $r['query'];
            if (isset($seen[$kw]) && $seen[$kw] !== $r['query']) {
                $issues[] = $this->issue('seo_url', $id, $kw, 'duplicate_keyword', 'error', "Keyword '{$kw}' використовується для двох різних query");
            }
            $seen[$kw] = $r['query'];
        }

        return $issues;
    }
}
