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

use OcKit\SeoCore\Dto\RedirectDto;

/**
 * CRUD + runtime resolution for 301/302 redirect rules.
 *
 * Table: oc_kit_seo_redirects
 *   redirect_id INT PK AUTO_INCREMENT
 *   store_id    INT NOT NULL DEFAULT 0
 *   from_url    VARCHAR(2048) NOT NULL
 *   to_url      VARCHAR(2048) NOT NULL
 *   code        SMALLINT NOT NULL DEFAULT 301
 *   hits        INT NOT NULL DEFAULT 0
 *   created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
 *   INDEX (store_id, from_url(255))
 *
 * Runtime: called from startup controller before routing.
 */
class RedirectManager
{
    private ?array $redirectMap = null; // from_url → RedirectDto

    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Check if the given path matches a redirect rule and issue it.
     * Returns true if a redirect was issued.
     */
    public function resolve(string $path, int $storeId, $response): bool
    {
        $this->ensureMap($storeId);

        $normalised = $this->normalisePath($path);

        if (!isset($this->redirectMap[$normalised])) return false;

        $dto = $this->redirectMap[$normalised];

        // Skip expired redirects (auto-cleanup window).
        if (isset($dto->expiresAt) && $dto->expiresAt !== '' && $dto->expiresAt !== null
            && strtotime($dto->expiresAt) < time()) {
            return false;
        }

        $this->incrementHits($dto->redirectId);

        if ($dto->code === 410) {
            http_response_code(410);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><title>410 Gone</title><h1>410 Gone</h1>';
            exit;
        }

        $response->redirect($dto->toUrl, $dto->code);
        return true;
    }

    // ─── Admin CRUD ───────────────────────────────────────────────────────────

    public function getList(int $storeId, int $page = 1, int $limit = 50, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        $where  = "WHERE `store_id` = " . $storeId;

        if ($search !== '') {
            $s = $this->db->escape($search);
            $where .= " AND (`from_url` LIKE '%" . $s . "%' OR `to_url` LIKE '%" . $s . "%')";
        }

        $total = (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_redirects` " . $where)->row['cnt'];

        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_redirects` " . $where .
            " ORDER BY `redirect_id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        )->rows;

        $items = array_map([$this, 'rowToDto'], $rows);

        return ['items' => $items, 'total' => $total];
    }

    public function getById(int $id): ?RedirectDto
    {
        $result = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE `redirect_id` = " . $id . " LIMIT 1"
        );
        return $result->num_rows ? $this->rowToDto($result->row) : null;
    }

    public function save(int $storeId, string $fromUrl, string $toUrl, int $code = 301, int $id = 0, ?string $expiresAt = null): int
    {
        $fromEsc = $this->db->escape($fromUrl);
        $toEsc   = $this->db->escape($toUrl);
        $expSql  = ($expiresAt !== null && $expiresAt !== '')
            ? "'" . $this->db->escape($expiresAt) . "'"
            : 'NULL';

        if ($id > 0) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "kit_seo_redirects`
                 SET `store_id` = " . $storeId . ", `from_url` = '" . $fromEsc . "',
                     `to_url` = '" . $toEsc . "', `code` = " . (int)$code . ",
                     `expires_at` = " . $expSql . "
                 WHERE `redirect_id` = " . $id
            );
            $this->redirectMap = null;
            return $id;
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_seo_redirects`
             (`store_id`, `from_url`, `to_url`, `code`, `hits`, `expires_at`)
             VALUES (" . $storeId . ", '" . $fromEsc . "', '" . $toEsc . "', " . (int)$code . ", 0, " . $expSql . ")"
        );
        $this->redirectMap = null;
        return (int)$this->db->getLastId();
    }

    /**
     * Auto-delete expired redirects.
     */
    public function deleteExpired(int $storeId = 0): int
    {
        $where = "`expires_at` IS NOT NULL AND `expires_at` < NOW()";
        if ($storeId > 0) $where = "`store_id` = " . (int)$storeId . " AND " . $where;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE " . $where);
        $this->redirectMap = null;
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE `redirect_id` = " . $id);
        $this->redirectMap = null;
    }

    public function deleteAll(int $storeId): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE `store_id` = " . $storeId);
        $this->redirectMap = null;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function ensureMap(int $storeId): void
    {
        if ($this->redirectMap !== null) return;

        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_redirects`
             WHERE `store_id` = " . $storeId
        )->rows;

        $this->redirectMap = [];
        foreach ($rows as $row) {
            $dto = $this->rowToDto($row);
            $this->redirectMap[$this->normalisePath($dto->fromUrl)] = $dto;
        }
    }

    private function normalisePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        return rtrim($path, '/');
    }

    private function incrementHits(int $id): void
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_seo_redirects`
             SET `hits` = `hits` + 1, `last_hit_at` = NOW()
             WHERE `redirect_id` = " . $id
        );
    }

    /**
     * Auto-capture: when a SEO URL keyword changes, record an old → new
     * redirect so the old URL keeps working with a 301.
     */
    public function autoCapture(string $oldUrl, string $newUrl, int $storeId): void
    {
        $oldUrl = trim($oldUrl);
        $newUrl = trim($newUrl);
        if ($oldUrl === '' || $newUrl === '' || $oldUrl === $newUrl) return;

        $oldEsc = $this->db->escape($this->normalisePath($oldUrl));
        $exists = $this->db->query(
            "SELECT `redirect_id` FROM `" . DB_PREFIX . "kit_seo_redirects`
             WHERE `store_id` = " . (int)$storeId . "
               AND `from_url` = '" . $oldEsc . "' LIMIT 1"
        );
        if ($exists->num_rows) return;

        $this->save($storeId, $oldUrl, $newUrl, 301, 0);
    }

    /**
     * Delete redirects with zero hits older than $days days. Returns the
     * number of rows removed. Useful for periodic clean-up of dead rules.
     */
    public function deleteStale(int $days, int $storeId = 0): int
    {
        $days  = max(1, $days);
        $where = "`hits` = 0 AND `created_at` < (NOW() - INTERVAL " . $days . " DAY)";
        if ($storeId > 0) {
            $where = "`store_id` = " . $storeId . " AND " . $where;
        }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE " . $where);
        $this->redirectMap = null;
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    /**
     * Export all redirects for a store as CSV (UTF-8, BOM, semicolon-sep).
     */
    public function exportCsv(int $storeId): string
    {
        $rows = $this->db->query(
            "SELECT `from_url`, `to_url`, `code`, `hits`, `created_at`, `last_hit_at`
             FROM `" . DB_PREFIX . "kit_seo_redirects`
             WHERE `store_id` = " . (int)$storeId . "
             ORDER BY `redirect_id` DESC"
        )->rows;

        $out = "\xEF\xBB\xBFfrom_url;to_url;code;hits;created_at;last_hit_at\n";
        foreach ($rows as $r) {
            $out .= '"' . str_replace('"', '""', $r['from_url']) . '";'
                  . '"' . str_replace('"', '""', $r['to_url'])   . '";'
                  . (int)$r['code']  . ';'
                  . (int)$r['hits']  . ';'
                  . $r['created_at'] . ';'
                  . ($r['last_hit_at'] ?? '') . "\n";
        }
        return $out;
    }

    private function rowToDto(array $row): RedirectDto
    {
        return new RedirectDto(
            (int)$row['redirect_id'],
            (string)$row['from_url'],
            (string)$row['to_url'],
            (int)$row['code'],
            (int)$row['store_id'],
            (int)$row['hits'],
            (string)$row['created_at'],
            isset($row['expires_at']) ? ($row['expires_at'] ?: null) : null,
            isset($row['last_hit_at']) ? ($row['last_hit_at'] ?: null) : null
        );
    }
}
