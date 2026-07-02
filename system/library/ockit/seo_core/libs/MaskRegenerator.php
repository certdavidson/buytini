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
 * Bulk-regenerates oc_seo_url keywords using UrlMaskEngine templates.
 *
 * For each entity type the generator:
 *   1. Loads all active entities with their description for the given language.
 *   2. Generates a keyword via UrlMaskEngine.
 *   3. Inserts (or updates on duplicate) the seo_url row, optionally skipping
 *      entities that already have an existing keyword (mode = 'empty').
 *
 * Returns a summary: {inserted, updated, skipped, errors:[]}
 */
class MaskRegenerator
{
    private const ENTITY_QUERIES = [
        'product' => [
            'query'    => "SELECT p.product_id AS id, pd.name
                           FROM `{P}product` p
                           LEFT JOIN `{P}product_description` pd
                             ON pd.product_id = p.product_id AND pd.language_id = {L}
                           WHERE p.status = 1",
            'id_field' => 'product_id',
            'query_tpl'=> 'product_id={ID}',
        ],
        'category' => [
            'query'    => "SELECT c.category_id AS id, cd.name
                           FROM `{P}category` c
                           LEFT JOIN `{P}category_description` cd
                             ON cd.category_id = c.category_id AND cd.language_id = {L}
                           WHERE c.status = 1",
            'id_field' => 'category_id',
            'query_tpl'=> 'category_id={ID}',
        ],
        'manufacturer' => [
            'query'    => "SELECT manufacturer_id AS id, name
                           FROM `{P}manufacturer`",
            'id_field' => 'manufacturer_id',
            'query_tpl'=> 'manufacturer_id={ID}',
        ],
        'information' => [
            'query'    => "SELECT i.information_id AS id, id_.title AS name
                           FROM `{P}information` i
                           LEFT JOIN `{P}information_description` id_
                             ON id_.information_id = i.information_id AND id_.language_id = {L}
                           WHERE i.status = 1",
            'id_field' => 'information_id',
            'query_tpl'=> 'information_id={ID}',
        ],
    ];

    private $db;
    /** @var UrlMaskEngine */
    private $maskEngine;
    /** @var CacheWarmer */
    private $cacheWarmer;
    public function __construct($db, UrlMaskEngine $maskEngine, CacheWarmer $cacheWarmer) {
        $this->db = $db;
        $this->maskEngine = $maskEngine;
        $this->cacheWarmer = $cacheWarmer;
    }

    /**
     * Regenerate SEO URLs for a given entity type, language and store.
     *
     * @param string   $entityType  product|category|manufacturer|information
     * @param int      $languageId
     * @param int      $storeId
     * @param string   $mode        'empty' (skip if keyword already exists) | 'all' (overwrite)
     * @param int[]    $entityIds   Empty = all entities; otherwise only these IDs
     */
    public function regenerate(
        string $entityType,
        int    $languageId,
        int    $storeId = 0,
        string $mode    = 'empty',
        array  $entityIds = []
    ): array {
        if (!isset(self::ENTITY_QUERIES[$entityType])) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [['Cannot handle type: ' . $entityType]]];
        }

        $def = self::ENTITY_QUERIES[$entityType];
        $sql = str_replace(['{P}', '{L}'], [DB_PREFIX, $languageId], $def['query']);

        if ($entityIds) {
            $ids = implode(',', array_map('intval', $entityIds));
            $sql .= " AND id IN ({$ids})";
        }

        $rows = $this->db->query($sql)->rows;

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $row) {
            $id      = (int)$row['id'];
            $name    = (string)($row['name'] ?? '');
            $qStr    = str_replace('{ID}', $id, $def['query_tpl']);

            if (!$name) { $skipped++; continue; }

            $keyword = $this->maskEngine->generate($entityType, ['name' => $name]);
            if (!$keyword) { $skipped++; continue; }

            // Ensure uniqueness: append -N if slug already taken
            $keyword = $this->ensureUnique($keyword, $qStr, $storeId, $languageId);

            // Check if row exists
            $existing = $this->db->query(
                "SELECT `seo_url_id`, `keyword` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `query` = '" . $this->db->escape($qStr) . "'
                   AND `store_id` = {$storeId} AND `language_id` = {$languageId}
                 LIMIT 1"
            )->row;

            if ($existing) {
                if ($mode === 'empty') { $skipped++; continue; }
                if ($existing['keyword'] === $keyword) { $skipped++; continue; }

                $this->db->query(
                    "UPDATE `" . DB_PREFIX . "seo_url`
                     SET `keyword` = '" . $this->db->escape($keyword) . "'
                     WHERE `seo_url_id` = " . (int)$existing['seo_url_id']
                );
                $updated++;
            } else {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "seo_url`
                     (`store_id`, `language_id`, `query`, `keyword`)
                     VALUES ({$storeId}, {$languageId}, '" . $this->db->escape($qStr) . "', '" . $this->db->escape($keyword) . "')"
                );
                $inserted++;
            }
        }

        $this->cacheWarmer->invalidate();

        return compact('inserted', 'updated', 'skipped', 'errors');
    }

    /**
     * Generate (and insert) a single entity's SEO URL keyword.
     *
     * Used by the on-the-fly auto-generation hook (when admin enables
     * module_oc_kit_seo_core_auto_generate_url): a visitor lands on an
     * entity page that has no seo_url row yet → we insert one and the next
     * URL generation will pick it up.
     *
     * @return bool  true when a new row was inserted, false otherwise
     *               (already exists, no name, or unsupported type)
     */
    public function regenerateOne(string $entityType, int $entityId, int $languageId, int $storeId = 0): bool
    {
        if (!isset(self::ENTITY_QUERIES[$entityType]) || $entityId <= 0) {
            return false;
        }

        $def  = self::ENTITY_QUERIES[$entityType];
        $qStr = str_replace('{ID}', $entityId, $def['query_tpl']);

        // Already have a row? Skip.
        $existing = $this->db->query(
            "SELECT `seo_url_id` FROM `" . DB_PREFIX . "seo_url`
             WHERE `query` = '" . $this->db->escape($qStr) . "'
               AND `store_id` = " . (int)$storeId . " AND `language_id` = " . (int)$languageId . "
             LIMIT 1"
        );
        if ($existing->num_rows) return false;

        // Pull entity name for the keyword
        $sql = str_replace(['{P}', '{L}'], [DB_PREFIX, (int)$languageId], $def['query']) . " AND id = " . (int)$entityId;
        $row = $this->db->query($sql)->row;
        $name = (string)($row['name'] ?? '');
        if ($name === '') return false;

        $keyword = $this->maskEngine->generate($entityType, ['name' => $name]);
        if ($keyword === '') return false;

        $keyword = $this->ensureUnique($keyword, $qStr, (int)$storeId, (int)$languageId);

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "seo_url`
             (`store_id`, `language_id`, `query`, `keyword`)
             VALUES (" . (int)$storeId . ", " . (int)$languageId . ",
                     '" . $this->db->escape($qStr) . "', '" . $this->db->escape($keyword) . "')"
        );

        $this->cacheWarmer->invalidate();
        return true;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function ensureUnique(string $keyword, string $query, int $storeId, int $languageId): string
    {
        $base    = $keyword;
        $attempt = $keyword;
        $n       = 1;

        while (true) {
            $conflict = $this->db->query(
                "SELECT `seo_url_id` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `keyword` = '" . $this->db->escape($attempt) . "'
                   AND `store_id` = {$storeId} AND `language_id` = {$languageId}
                   AND `query` != '" . $this->db->escape($query) . "'
                 LIMIT 1"
            )->row;

            if (!$conflict) break;

            $n++;
            $attempt = $base . '-' . $n;
        }

        return $attempt;
    }
}
