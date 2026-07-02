<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

/**
 * ProductGroupService — collapses variant groups in a result list so search
 * mirrors the catalog, where colour/size variants of one item show as a single
 * card. Integrates with the "sppro_product_group" grouping module: products that
 * share a variant group are folded onto the first (highest-ranked) one that
 * matched the query; ungrouped products pass through untouched, original order
 * (relevance) preserved.
 *
 * Degrades gracefully — if the grouping module's tables are absent (a store
 * without it, e.g. the dev sandbox), the input list returns unchanged. Safe to
 * ship enabled in the distribution and gate per store via the
 * autocomplete_group_collapse setting.
 */
class ProductGroupService {
    /** @var \DB */
    private $db;

    /** @var bool|null memoised "are the grouping tables present?" check */
    private $available = null;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Fold variant groups onto one representative each.
     *
     * @param int[] $ids ranked product ids (best match first)
     * @return int[] same order, at most one product per variant group
     */
    public function collapse(array $ids, int $collapseAttrId = 0, int $languageId = 0): array {
        if (!$ids || !$this->isAvailable()) {
            return $ids;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Single bulk lookup: which of these products belong to a variant group.
        $rows = $this->db->query(
            "SELECT product_id, product_group_id
             FROM `" . DB_PREFIX . "sppro_group_product_group_products`
             WHERE product_id IN (" . implode(',', $ids) . ")"
        )->rows;

        if (!$rows) {
            return $ids; // none of the matches are grouped
        }

        $groupOf = [];
        foreach ($rows as $r) {
            $groupOf[(int)$r['product_id']] = (int)$r['product_group_id'];
        }

        // Collapse key. Plain mode (attr = 0) → just the group id: the whole
        // variant group folds onto one card. Attribute mode → group id + the value
        // of the CHOSEN attribute, so products split into a card per value of that
        // attribute (e.g. one card per colour) while every other difference — sizes,
        // and uneven/missing attributes — is ignored. Products without a value for
        // the attribute get an empty value and fold in with the rest of the group.
        $splitVal = [];
        if ($collapseAttrId > 0) {
            $grouped = array_keys($groupOf);
            if ($grouped) {
                $langSql = $languageId > 0 ? " AND language_id = " . (int)$languageId : "";
                $aRows = $this->db->query(
                    "SELECT product_id, `text`
                     FROM `" . DB_PREFIX . "product_attribute`
                     WHERE product_id IN (" . implode(',', array_map('intval', $grouped)) . ")
                       AND attribute_id = " . (int)$collapseAttrId . $langSql . "
                     ORDER BY product_id"
                )->rows;
                foreach ($aRows as $ar) {
                    $pid = (int)$ar['product_id'];
                    if (!isset($splitVal[$pid])) {
                        $splitVal[$pid] = trim((string)$ar['text']);
                    }
                }
            }
        }

        $seen = [];
        $out  = [];
        foreach ($ids as $pid) {
            $gid = isset($groupOf[$pid]) ? $groupOf[$pid] : 0;
            if ($gid === 0) {
                $out[] = $pid;                 // ungrouped — always keep
                continue;
            }
            $key = (string)$gid;
            if ($collapseAttrId > 0) {
                $key .= '#' . (isset($splitVal[$pid]) ? $splitVal[$pid] : '');
            }
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $pid;                 // first (best-ranked) of this variant key
            }
            // else: another variant with the same key → drop
        }

        return $out;
    }

    /** Memoised: the grouping module is installed when its pivot table exists. */
    private function isAvailable(): bool {
        if ($this->available === null) {
            try {
                $q = $this->db->query(
                    "SHOW TABLES LIKE '" . DB_PREFIX . "sppro_group_product_group_products'"
                );
                $this->available = !empty($q->num_rows);
            } catch (\Throwable $e) {
                $this->available = false;
            }
        }

        return $this->available;
    }
}
