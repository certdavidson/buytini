<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Engine;

use OcKit\AdvancedSearchPro\Contracts\SearchEngineInterface;

class NativeSearchEngine implements SearchEngineInterface {
    private $db;
    private $config;

    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }

    public function search($query, $limit, $offset = 0) {
        $query = trim((string)$query);
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        if ($query === '') {
            return ['ids' => [], 'total' => 0];
        }

        $languageId = (int)$this->config->get('config_language_id');
        $storeId = (int)$this->config->get('config_store_id');

        // Use pre-built native index when available (fast, per-field weighted)
        if ($this->hasNativeIndex($languageId)) {
            return $this->searchNativeIndex($query, $languageId, $storeId, $limit, $offset);
        }
        $escapedQuery = $this->db->escape($query);
        $queryIsNumeric = ctype_digit($query);

        $searchFields = $this->normalizeSearchFields($this->config->get('module_oc_kit_advanced_search_pro_search_fields'));

        $baseSql = " FROM " . DB_PREFIX . "product p
            INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            WHERE p.status = '1'
              AND p.date_available <= NOW()
              AND pd.language_id = '" . $languageId . "'
              AND p2s.store_id = '" . $storeId . "'";

        $rankParts = [];
        $whereOr = [];

        if (!empty($searchFields['product_id']['enabled']) && $queryIsNumeric) {
            $whereOr[] = "p.product_id = '" . (int)$query . "'";
            $rankParts[] = "(CASE WHEN p.product_id = '" . (int)$query . "' THEN " . ((int)$searchFields['product_id']['weight'] + 30) . " ELSE 0 END)";
        }

        $likeMap = [
            'title' => 'pd.name',
            'description' => 'pd.description',
            'model' => 'p.model',
            'sku' => 'p.sku',
            'upc' => 'p.upc',
            'ean' => 'p.ean',
            'isbn' => 'p.isbn',
            'manufacturer' => 'm.name',
            'tags' => 'pd.tag'
        ];

        foreach ($likeMap as $fieldKey => $column) {
            if (empty($searchFields[$fieldKey]['enabled'])) {
                continue;
            }
            $weight = (int)$searchFields[$fieldKey]['weight'];
            $whereOr[] = $column . " LIKE '%" . $escapedQuery . "%'";
            $rankParts[] = "(CASE WHEN " . $column . " LIKE '%" . $escapedQuery . "%' THEN " . $weight . " ELSE 0 END)";
        }

        if (!empty($searchFields['categories']['enabled'])) {
            $weight = (int)$searchFields['categories']['weight'];
            $categoriesExists = "EXISTS (
                SELECT 1
                FROM " . DB_PREFIX . "product_to_category p2c
                INNER JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . $languageId . "')
                WHERE p2c.product_id = p.product_id
                  AND cd.name LIKE '%" . $escapedQuery . "%'
            )";
            $whereOr[] = $categoriesExists;
            $rankParts[] = "(CASE WHEN " . $categoriesExists . " THEN " . $weight . " ELSE 0 END)";
        }

        if (!empty($searchFields['attributes']['enabled'])) {
            $weight = (int)$searchFields['attributes']['weight'];
            $attributesExists = "EXISTS (
                SELECT 1
                FROM " . DB_PREFIX . "product_attribute pa
                WHERE pa.product_id = p.product_id
                  AND pa.language_id = '" . $languageId . "'
                  AND pa.text LIKE '%" . $escapedQuery . "%'
            )";
            $whereOr[] = $attributesExists;
            $rankParts[] = "(CASE WHEN " . $attributesExists . " THEN " . $weight . " ELSE 0 END)";
        }

        if (!$whereOr) {
            return ['ids' => [], 'total' => 0];
        }

        $fulltextColumns = [];
        if (!empty($searchFields['title']['enabled'])) {
            $fulltextColumns[] = 'pd.name';
        }
        if (!empty($searchFields['tags']['enabled'])) {
            $fulltextColumns[] = 'pd.tag';
        }
        if (!empty($searchFields['description']['enabled'])) {
            $fulltextColumns[] = 'pd.description';
        }

        $rankExpr = '(' . implode(' + ', $rankParts) . ')';
        $orWhereSql = '(' . implode(' OR ', $whereOr) . ')';

        if ($fulltextColumns && $this->hasFulltextIndex($fulltextColumns)) {
            try {
                $matchExpr = "MATCH(" . implode(', ', $fulltextColumns) . ") AGAINST ('" . $escapedQuery . "' IN BOOLEAN MODE)";
                $fulltextRank = "(" . $matchExpr . " * 100)";
                $fulltextWhere = "(" . $matchExpr . " > 0 OR " . $orWhereSql . ")";

                $totalQuery = $this->db->query("SELECT COUNT(DISTINCT p.product_id) AS total" . $baseSql . " AND " . $fulltextWhere);
                $total = (int)($totalQuery->row['total'] ?? 0);

                if ($total > 0) {
                    $rows = $this->db->query(
                        "SELECT DISTINCT p.product_id, (" . $fulltextRank . " + " . $rankExpr . ") AS relevance" . $baseSql . " AND " . $fulltextWhere .
                        " ORDER BY relevance DESC, p.sort_order ASC, LCASE(pd.name) ASC LIMIT " . $offset . "," . $limit
                    );

                    $ids = [];
                    foreach ($rows->rows as $row) {
                        $ids[] = (int)$row['product_id'];
                    }

                    return [
                        'ids' => $ids,
                        'total' => $total
                    ];
                }
            } catch (\Exception $e) {
                // FULLTEXT is optional; continue with LIKE/EXISTS fallback.
                error_log('[ASP] NativeSearchEngine FULLTEXT failed: ' . $e->getMessage());
            }
        }

        $totalQuery = $this->db->query("SELECT COUNT(DISTINCT p.product_id) AS total" . $baseSql . " AND " . $orWhereSql);
        $rows = $this->db->query(
            "SELECT DISTINCT p.product_id, " . $rankExpr . " AS relevance" . $baseSql . " AND " . $orWhereSql .
            " ORDER BY relevance DESC, p.sort_order ASC, LCASE(pd.name) ASC LIMIT " . $offset . "," . $limit
        );

        $ids = [];
        foreach ($rows->rows as $row) {
            $ids[] = (int)$row['product_id'];
        }

        return [
            'ids' => $ids,
            'total' => (int)($totalQuery->row['total'] ?? 0)
        ];
    }

    private static $nativeIndexCache = [];

    private function hasNativeIndex(int $languageId): bool {
        if (!isset(self::$nativeIndexCache[$languageId])) {
            try {
                $row = $this->db->query(
                    "SELECT 1 FROM `" . DB_PREFIX . "asp_native_index` WHERE language_id = '" . $languageId . "' LIMIT 1"
                )->row;
                self::$nativeIndexCache[$languageId] = !empty($row);
            } catch (\Exception $e) {
                error_log('[ASP] hasNativeIndex check failed: ' . $e->getMessage());
                self::$nativeIndexCache[$languageId] = false;
            }
        }
        return self::$nativeIndexCache[$languageId];
    }

    private function searchNativeIndex(string $query, int $languageId, int $storeId, int $limit, int $offset): array {
        $escapedQuery = $this->db->escape($query);

        $joinSql =
            " FROM `" . DB_PREFIX . "asp_native_index` ani
            INNER JOIN `" . DB_PREFIX . "product` p ON p.product_id = ani.product_id
            INNER JOIN `" . DB_PREFIX . "product_to_store` p2s
                ON p2s.product_id = p.product_id AND p2s.store_id = '" . $storeId . "'
            WHERE p.status = '1'
              AND p.date_available <= NOW()
              AND ani.language_id = '" . $languageId . "'";

        // Phase 1: FULLTEXT — uses ft_content index, fast
        $matchExpr = "MATCH(ani.content) AGAINST ('" . $escapedQuery . "' IN BOOLEAN MODE)";
        $likeExpr  = "ani.content LIKE '%" . $escapedQuery . "%'";

        $ftBaseSql = $joinSql . " AND " . $matchExpr . " > 0";

        $totalQuery = $this->db->query("SELECT COUNT(DISTINCT ani.product_id) AS total" . $ftBaseSql);
        $total = (int)($totalQuery->row['total'] ?? 0);

        if ($total > 0) {
            // LIKE is evaluated only on FULLTEXT-matched rows (small set) — no full scan
            $rows = $this->db->query(
                "SELECT ani.product_id,
                        SUM(ani.weight * (" . $matchExpr . " + IF(" . $likeExpr . ", 1, 0))) AS score" .
                $ftBaseSql .
                " GROUP BY ani.product_id
                ORDER BY score DESC, p.sort_order ASC
                LIMIT " . $offset . "," . $limit
            );

            $ids = [];
            foreach ($rows->rows as $row) {
                $ids[] = (int)$row['product_id'];
            }
            return ['ids' => $ids, 'total' => $total];
        }

        // Phase 2: LIKE fallback — for very short tokens, special chars, abbreviations
        // that MySQL FULLTEXT tokenizer may skip (e.g. < innodb_ft_min_token_size)
        $likeBaseSql = $joinSql . " AND " . $likeExpr;

        $totalQuery = $this->db->query("SELECT COUNT(DISTINCT ani.product_id) AS total" . $likeBaseSql);
        $total = (int)($totalQuery->row['total'] ?? 0);

        if ($total === 0) {
            return ['ids' => [], 'total' => 0];
        }

        $rows = $this->db->query(
            "SELECT ani.product_id, SUM(ani.weight) AS score" .
            $likeBaseSql .
            " GROUP BY ani.product_id
            ORDER BY score DESC, p.sort_order ASC
            LIMIT " . $offset . "," . $limit
        );

        $ids = [];
        foreach ($rows->rows as $row) {
            $ids[] = (int)$row['product_id'];
        }
        return ['ids' => $ids, 'total' => $total];
    }

    private static $fulltextIndexCache = null;

    private function hasFulltextIndex(array $columns) {
        // Strip table alias (e.g. "pd.name" → "name") for index lookup.
        $bare = array_map(function($c) {
            return strpos($c, '.') !== false ? substr($c, strpos($c, '.') + 1) : $c;
        }, $columns);

        if (self::$fulltextIndexCache === null) {
            self::$fulltextIndexCache = [];
            $res = $this->db->query(
                "SELECT GROUP_CONCAT(Column_name ORDER BY Seq_in_index) AS cols
                 FROM information_schema.STATISTICS
                 WHERE Table_schema = DATABASE()
                   AND Table_name = '" . DB_PREFIX . "product_description'
                   AND Index_type = 'FULLTEXT'
                 GROUP BY Index_name"
            );
            foreach ($res->rows as $row) {
                $cols = array_map('trim', explode(',', (string)$row['cols']));
                sort($cols);
                self::$fulltextIndexCache[] = $cols;
            }
        }

        sort($bare);
        foreach (self::$fulltextIndexCache as $indexCols) {
            // MySQL MATCH() must reference exactly the columns covered by a FULLTEXT index.
            if ($bare === $indexCols) {
                return true;
            }
        }
        return false;
    }

    private function normalizeSearchFields($value) {
        $defaults = [
            'product_id' => ['enabled' => 1, 'weight' => 100],
            'title' => ['enabled' => 1, 'weight' => 80],
            'description' => ['enabled' => 1, 'weight' => 40],
            'model' => ['enabled' => 1, 'weight' => 30],
            'sku' => ['enabled' => 1, 'weight' => 30],
            'upc' => ['enabled' => 1, 'weight' => 10],
            'ean' => ['enabled' => 1, 'weight' => 10],
            'isbn' => ['enabled' => 1, 'weight' => 10],
            'manufacturer' => ['enabled' => 1, 'weight' => 20],
            'categories' => ['enabled' => 1, 'weight' => 20],
            'attributes' => ['enabled' => 1, 'weight' => 15],
            'tags' => ['enabled' => 1, 'weight' => 15]
        ];

        if (!is_array($value)) {
            return $defaults;
        }

        foreach ($defaults as $key => &$row) {
            $input = isset($value[$key]) && is_array($value[$key]) ? $value[$key] : [];
            if (isset($input['enabled'])) {
                $row['enabled'] = (int)!empty($input['enabled']);
            }
            if (isset($input['weight'])) {
                $row['weight'] = max(1, min(100, (int)$input['weight']));
            }
        }
        unset($row);

        return $defaults;
    }
}
