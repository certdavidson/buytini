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
 * CRUD for oc_kit_seo_meta_override.
 * Table stores manual title/description/h1/robots/canonical/og overrides per entity.
 */
class MetaRepository
{
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get override for a specific entity, or null if none exists.
     */
    public function getOverride(string $type, int $id, int $languageId, int $storeId): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_meta_override`
             WHERE `entity_type` = '" . $this->db->escape($type) . "'
               AND `entity_id` = " . $id . "
               AND `language_id` = " . $languageId . "
               AND `store_id` = " . $storeId . "
             LIMIT 1"
        );
        return $result->num_rows ? $result->row : null;
    }

    /**
     * INSERT ON DUPLICATE KEY UPDATE.
     * $data keys: entity_type, entity_id, language_id, store_id,
     *             title, description, h1, robots, canonical,
     *             og_title, og_description, og_image
     */
    public function saveOverride(array $data): void
    {
        $fields = ['title', 'description', 'h1', 'robots', 'canonical', 'og_title', 'og_description', 'og_image'];

        $sets = [];
        foreach ($fields as $f) {
            $val = isset($data[$f]) ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $sets[] = "`{$f}` = " . $val;
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_seo_meta_override`
             (`store_id`, `language_id`, `entity_type`, `entity_id`,
              `title`, `description`, `h1`, `robots`, `canonical`,
              `og_title`, `og_description`, `og_image`)
             VALUES (
               " . (int)($data['store_id']    ?? 0) . ",
               " . (int)($data['language_id'] ?? 0) . ",
               '" . $this->db->escape($data['entity_type'] ?? '') . "',
               " . (int)($data['entity_id']   ?? 0) . ",
               " . (isset($data['title'])          ? "'" . $this->db->escape($data['title'])          . "'" : 'NULL') . ",
               " . (isset($data['description'])    ? "'" . $this->db->escape($data['description'])    . "'" : 'NULL') . ",
               " . (isset($data['h1'])             ? "'" . $this->db->escape($data['h1'])             . "'" : 'NULL') . ",
               " . (isset($data['robots'])         ? "'" . $this->db->escape($data['robots'])         . "'" : 'NULL') . ",
               " . (isset($data['canonical'])      ? "'" . $this->db->escape($data['canonical'])      . "'" : 'NULL') . ",
               " . (isset($data['og_title'])       ? "'" . $this->db->escape($data['og_title'])       . "'" : 'NULL') . ",
               " . (isset($data['og_description']) ? "'" . $this->db->escape($data['og_description']) . "'" : 'NULL') . ",
               " . (isset($data['og_image'])       ? "'" . $this->db->escape($data['og_image'])       . "'" : 'NULL') . "
             )
             ON DUPLICATE KEY UPDATE " . implode(', ', $sets)
        );
    }

    public function deleteOverride(int $metaId): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_meta_override` WHERE `meta_id` = " . $metaId);
    }

    /**
     * Return entities that need bulk fill.
     * mode='empty' — no override or title IS NULL.
     * mode='all'   — all entities of this type.
     *
     * @return array [{entity_id, entity_name}]
     */
    public function getBulkCandidates(string $type, int $languageId, string $mode, int $storeId, int $categoryId = 0): array
    {
        switch ($type) {
            case 'product':
                $nameField  = 'pd.name';
                $idField    = 'p.product_id';
                $table      = DB_PREFIX . 'product p';
                $catJoin    = $categoryId
                    ? "INNER JOIN `" . DB_PREFIX . "product_to_category` ptc ON ptc.product_id = p.product_id AND ptc.category_id = " . $categoryId
                    : '';
                $join       = "LEFT JOIN `" . DB_PREFIX . "product_description` pd
                               ON pd.product_id = p.product_id AND pd.language_id = " . $languageId .
                              ($catJoin ? "\n                               {$catJoin}" : '');
                $statusWhere = "p.status = 1";
                break;
            case 'category':
                $nameField  = 'cd.name';
                $idField    = 'c.category_id';
                $table      = DB_PREFIX . 'category c';
                $join       = "LEFT JOIN `" . DB_PREFIX . "category_description` cd
                               ON cd.category_id = c.category_id AND cd.language_id = " . $languageId;
                $statusWhere = "c.status = 1";
                break;
            case 'manufacturer':
                $nameField  = 'm.name';
                $idField    = 'm.manufacturer_id';
                $table      = DB_PREFIX . 'manufacturer m';
                $join       = '';
                $statusWhere = '1=1';
                break;
            case 'information':
                $nameField  = 'id2.title';
                $idField    = 'i.information_id';
                $table      = DB_PREFIX . 'information i';
                $join       = "LEFT JOIN `" . DB_PREFIX . "information_description` id2
                               ON id2.information_id = i.information_id AND id2.language_id = " . $languageId;
                $statusWhere = "i.status = 1";
                break;
            default:
                return [];
        }

        $overrideJoin = '';
        $overrideWhere = '';

        if ($mode === 'empty') {
            $overrideJoin  = "LEFT JOIN `" . DB_PREFIX . "kit_seo_meta_override` mo
                              ON mo.entity_type = '" . $this->db->escape($type) . "'
                                 AND mo.entity_id = {$idField}
                                 AND mo.language_id = " . $languageId . "
                                 AND mo.store_id = " . $storeId;
            $overrideWhere = "AND (mo.meta_id IS NULL OR mo.title IS NULL)";
        }

        $sql = "SELECT {$idField} AS entity_id, {$nameField} AS entity_name
                FROM `{$table}`
                " . ($join ? $join : '') . "
                {$overrideJoin}
                WHERE {$statusWhere}
                {$overrideWhere}
                ORDER BY {$idField}";

        return $this->db->query($sql)->rows;
    }

    /**
     * Paginated list for admin UI.
     */
    public function getAll(array $filters, int $limit, int $offset): array
    {
        $where = $this->buildWhere($filters);
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_meta_override`
             {$where}
             ORDER BY `meta_id` DESC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        )->rows;
    }

    public function getTotalCount(array $filters): int
    {
        $where = $this->buildWhere($filters);
        return (int)$this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_meta_override` {$where}"
        )->row['cnt'];
    }

    private function buildWhere(array $filters): string
    {
        $parts = [];
        if (!empty($filters['store_id']))    $parts[] = "`store_id` = " . (int)$filters['store_id'];
        if (!empty($filters['language_id'])) $parts[] = "`language_id` = " . (int)$filters['language_id'];
        if (!empty($filters['entity_type'])) $parts[] = "`entity_type` = '" . $this->db->escape($filters['entity_type']) . "'";
        if (!empty($filters['search'])) {
            $s = $this->db->escape($filters['search']);
            $parts[] = "(`title` LIKE '%{$s}%' OR `description` LIKE '%{$s}%')";
        }
        return $parts ? 'WHERE ' . implode(' AND ', $parts) : '';
    }
}
