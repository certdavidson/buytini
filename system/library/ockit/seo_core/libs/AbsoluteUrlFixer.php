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
 * Scans and replaces absolute URLs in product/category descriptions.
 *
 * Helps migrate domains or enforce HTTPS by replacing hardcoded URLs
 * in DB text fields. All changes are logged to oc_kit_seo_absurl_log.
 */
class AbsoluteUrlFixer
{
    private const SCANNED_FIELDS = [
        'product'     => ['table' => 'product_description',  'id' => 'product_id',     'fields' => ['description']],
        'category'    => ['table' => 'category_description', 'id' => 'category_id',    'fields' => ['description']],
        'information' => ['table' => 'information_description', 'id' => 'information_id', 'fields' => ['description']],
    ];

    private $db;
    private $config;
    public function __construct($db, $config = null) {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Scan for occurrences of $domain in all text fields.
     * Returns grouped results: [{entity_type, entity_id, field, count}]
     */
    public function scan(string $domain, int $languageId = 0): array
    {
        $escaped = $this->db->escape($domain);
        $results = [];

        foreach (self::SCANNED_FIELDS as $type => $def) {
            $table   = DB_PREFIX . $def['table'];
            $idField = $def['id'];
            foreach ($def['fields'] as $field) {
                $langWhere = $languageId ? " AND `language_id` = {$languageId}" : '';
                $rows = $this->db->query(
                    "SELECT `{$idField}` AS entity_id,
                            (LENGTH(`{$field}`) - LENGTH(REPLACE(`{$field}`, '{$escaped}', '')))
                            / LENGTH('{$escaped}') AS cnt
                     FROM `{$table}`
                     WHERE `{$field}` LIKE '%{$escaped}%'{$langWhere}"
                )->rows;

                foreach ($rows as $r) {
                    $results[] = [
                        'entity_type' => $type,
                        'entity_id'   => (int)$r['entity_id'],
                        'field'       => $field,
                        'count'       => (int)$r['cnt'],
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Replace $oldDomain with $newDomain in specific entities (or all if $entityIds=[]).
     * Returns number of DB rows updated.
     */
    public function replace(string $oldDomain, string $newDomain, string $entityType, array $entityIds = []): int
    {
        if (!isset(self::SCANNED_FIELDS[$entityType])) return 0;

        $def     = self::SCANNED_FIELDS[$entityType];
        $table   = DB_PREFIX . $def['table'];
        $idField = $def['id'];
        $updated = 0;

        $idWhere = $entityIds
            ? ' AND `' . $idField . '` IN (' . implode(',', array_map('intval', $entityIds)) . ')'
            : '';

        $oldEsc = $this->db->escape($oldDomain);
        $newEsc = $this->db->escape($newDomain);

        foreach ($def['fields'] as $field) {
            $this->db->query(
                "UPDATE `{$table}`
                 SET `{$field}` = REPLACE(`{$field}`, '{$oldEsc}', '{$newEsc}')
                 WHERE `{$field}` LIKE '%{$oldEsc}%'{$idWhere}"
            );
            $updated += $this->db->countAffected();
        }

        $this->log($entityType, $entityIds, $oldDomain, $newDomain, $updated);

        return $updated;
    }

    /**
     * Shorthand: replace http:// with https:// for a given domain.
     */
    /**
     * Replace http://your-domain → https://your-domain in entity descriptions.
     * Domain is derived from config_url / config_ssl — never user-supplied
     * (security: prevents arbitrary domain rewrites). Per ТЗ §10 + §23.
     */
    public function replaceHttpToHttps(string $entityType, array $entityIds = []): int
    {
        $domain = $this->ownDomain();
        if ($domain === '') return 0;
        $host = parse_url($domain, PHP_URL_HOST) ?: $domain;
        return $this->replace('http://' . $host, 'https://' . $host, $entityType, $entityIds);
    }

    /**
     * Resolve own domain from config_ssl, falling back to config_url.
     * Returns empty string when neither is configured.
     */
    private function ownDomain(): string
    {
        if (!$this->config) return '';
        $ssl = (string)$this->config->get('config_ssl');
        if ($ssl !== '') return rtrim($ssl, '/');
        $url = (string)$this->config->get('config_url');
        return rtrim($url, '/');
    }

    /**
     * Retrieve log entries.
     */
    public function getLog(int $limit = 100, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_absurl_log`
             ORDER BY `created_at` DESC
             LIMIT {$offset}, {$limit}"
        )->rows;
    }

    public function getLogTotal(): int
    {
        return (int)($this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_absurl_log`"
        )->row['cnt'] ?? 0);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function log(string $entityType, array $entityIds, string $oldUrl, string $newUrl, int $rowsUpdated): void
    {
        $entityIdsJson = $this->db->escape(json_encode($entityIds));
        $oldEsc        = $this->db->escape($oldUrl);
        $newEsc        = $this->db->escape($newUrl);
        $typeEsc       = $this->db->escape($entityType);

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_seo_absurl_log`
             (`entity_type`, `entity_ids`, `old_url`, `new_url`, `rows_updated`, `created_at`)
             VALUES ('{$typeEsc}', '{$entityIdsJson}', '{$oldEsc}', '{$newEsc}', {$rowsUpdated}, NOW())"
        );
    }
}
