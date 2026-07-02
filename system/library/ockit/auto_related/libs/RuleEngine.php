<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

/**
 * Evaluates active rules against a product and returns matching blocks.
 * Supports full constructor-based conditions (attribute, dynamic_attribute,
 * name_contains, price_range, brand_priority, bestseller sort, etc.)
 *
 * Each block: ['rule_id' => int, 'title' => string, 'product_ids' => int[]]
 */
class RuleEngine
{
    /** @var \DB */
    private $db;

    /** @var RuleRepository */
    private $repo;

    /** @var array */
    private $config;

    public function __construct(\DB $db, RuleRepository $repo, array $config)
    {
        $this->db     = $db;
        $this->repo   = $repo;
        $this->config = $config;
    }

    /**
     * Extract integer IDs from a conditions `ids` array that may contain
     * either plain integers (legacy) or {id, label} objects (current).
     */
    private static function extractIds(array $items): array
    {
        $out = array();
        foreach ($items as $item) {
            $id = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
            if ($id > 0) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /**
     * Returns all matching rule-blocks for the given product.
     */
    public function getBlocks(int $productId): array
    {
        $rules = $this->repo->getActiveRules();
        if (empty($rules)) {
            return array();
        }

        $langId = (int)($this->config['language_id'] ?? 1);

        $srcRow = $this->db->query(
            "SELECT p.manufacturer_id, p.price
             FROM `" . DB_PREFIX . "product` p
             WHERE p.product_id = " . (int)$productId
        );
        if (empty($srcRow->row)) {
            return array();
        }

        $srcMfId    = (int)$srcRow->row['manufacturer_id'];
        $srcPrice   = (float)$srcRow->row['price'];
        $srcCatIds  = $this->getCategoryIds($productId);

        $blocks = array();
        foreach ($rules as $rule) {
            if (!$this->matchesSource($rule['source_conditions'], $productId, $srcCatIds, $srcMfId, $langId)) {
                continue;
            }

            $productIds = $this->fetchTargetProducts(
                $rule['target_conditions'],
                $productId,
                $srcCatIds,
                $srcMfId,
                $srcPrice,
                $langId,
                max(1, min(50, (int)($rule['result_limit'] ?? 8))),
                (string)($rule['result_sort'] ?? 'random')
            );

            if (empty($productIds)) {
                continue;
            }

            $titleMap = is_array($rule['block_title']) ? $rule['block_title'] : array();
            $title    = (string)($titleMap[$langId] ?? reset($titleMap) ?: '');

            $blocks[] = array(
                'rule_id'     => (int)$rule['rule_id'],
                'title'       => $title,
                'product_ids' => $productIds,
            );
        }

        return $blocks;
    }

    // ── Source matching ───────────────────────────────────────────────────────

    /**
     * All source conditions must match (AND logic).
     * Empty conditions = show on all products.
     */
    private function matchesSource(array $conditions, int $productId, array $srcCatIds, int $srcMfId, int $langId): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $cond) {
            $type = (string)($cond['type'] ?? '');

            switch ($type) {
                case 'category':
                    $ids = self::extractIds((array)($cond['ids'] ?? array()));
                    if (!empty($ids) && empty(array_intersect($srcCatIds, $ids))) {
                        return false;
                    }
                    break;

                case 'manufacturer':
                    $ids = self::extractIds((array)($cond['ids'] ?? array()));
                    if (!empty($ids) && !in_array($srcMfId, $ids, true)) {
                        return false;
                    }
                    break;

                case 'attribute':
                    $attrId = (int)($cond['attribute_id'] ?? 0);
                    $value  = (string)($cond['value'] ?? '');
                    if ($attrId > 0) {
                        $srcVal = $this->getProductAttributeValue($productId, $attrId, $langId);
                        if ($srcVal === null || mb_strtolower(trim($srcVal)) !== mb_strtolower(trim($value))) {
                            return false;
                        }
                    }
                    break;

                case 'name_contains':
                    $text = (string)($cond['text'] ?? '');
                    if ($text !== '' && !$this->productNameContains($productId, $text, $langId)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    // ── Target product fetching ───────────────────────────────────────────────

    private function fetchTargetProducts(
        array $conditions,
        int   $productId,
        array $srcCatIds,
        int   $srcMfId,
        float $srcPrice,
        int   $langId,
        int   $limit,
        string $sort
    ): array {
        $joins   = array();
        $where   = array(
            "p.product_id != " . (int)$productId,
            "p.status = 1",
        );
        // Multi-store isolation: products are scoped to the configured store
        // via oc_product_to_store. config_store_id defaults to 0 (default store).
        $storeId = (int)($this->config['store_id'] ?? 0);
        $joins[] = "INNER JOIN `" . DB_PREFIX . "product_to_store` p2s"
            . " ON p2s.product_id = p.product_id AND p2s.store_id = " . $storeId;

        $brandPriority = false;
        $joinIdx = 0;
        // Hard cap on synthetic JOIN count to prevent pathological rules from
        // blowing up the planner / hitting MySQL's 61-table limit.
        $maxJoins = 10;

        // Global blacklists (always applied)
        $blProducts   = array_map('intval', (array)($this->config['blacklist_products']   ?? array()));
        $blCategories = array_map('intval', (array)($this->config['blacklist_categories'] ?? array()));

        if (!empty($blProducts)) {
            $where[] = "p.product_id NOT IN (" . implode(',', $blProducts) . ")";
        }
        if (!empty($blCategories)) {
            $where[] = "p.product_id NOT IN ("
                . "SELECT product_id FROM `" . DB_PREFIX . "product_to_category`"
                . " WHERE category_id IN (" . implode(',', $blCategories) . "))";
        }

        // Global exclude_oos
        if (!empty($this->config['exclude_oos'])) {
            $where[] = "p.quantity > 0";
        }

        foreach ($conditions as $cond) {
            if ($joinIdx >= $maxJoins) {
                break; // refuse further JOIN-producing conditions
            }
            $type = (string)($cond['type'] ?? '');
            $idx  = ++$joinIdx;

            switch ($type) {
                case 'same_category':
                    if (!empty($srcCatIds)) {
                        $catStr  = implode(',', array_map('intval', $srcCatIds));
                        $joins[] = "INNER JOIN `" . DB_PREFIX . "product_to_category` ptc{$idx}"
                            . " ON ptc{$idx}.product_id = p.product_id"
                            . " AND ptc{$idx}.category_id IN ({$catStr})";
                    }
                    break;

                case 'same_manufacturer':
                    if ($srcMfId > 0) {
                        $where[] = "p.manufacturer_id = " . (int)$srcMfId;
                    }
                    break;

                case 'category':
                    $ids = self::extractIds((array)($cond['ids'] ?? array()));
                    if (!empty($ids)) {
                        $catStr  = implode(',', $ids);
                        $joins[] = "INNER JOIN `" . DB_PREFIX . "product_to_category` ptc{$idx}"
                            . " ON ptc{$idx}.product_id = p.product_id"
                            . " AND ptc{$idx}.category_id IN ({$catStr})";
                    }
                    break;

                case 'manufacturer':
                    $ids = self::extractIds((array)($cond['ids'] ?? array()));
                    if (!empty($ids)) {
                        $where[] = "p.manufacturer_id IN (" . implode(',', $ids) . ")";
                    }
                    break;

                case 'attribute':
                    $attrId = (int)($cond['attribute_id'] ?? 0);
                    $value  = $this->db->escape(trim((string)($cond['value'] ?? '')));
                    if ($attrId > 0 && $value !== '') {
                        $joins[] = "INNER JOIN `" . DB_PREFIX . "product_attribute` pa{$idx}"
                            . " ON pa{$idx}.product_id = p.product_id"
                            . " AND pa{$idx}.attribute_id = {$attrId}"
                            . " AND pa{$idx}.language_id = {$langId}"
                            . " AND pa{$idx}.text = '{$value}'";
                    }
                    break;

                case 'dynamic_attribute':
                    $attrId = (int)($cond['attribute_id'] ?? 0);
                    if ($attrId > 0) {
                        $srcVal = $this->getProductAttributeValue($productId, $attrId, $langId);
                        if ($srcVal !== null && $srcVal !== '') {
                            $escaped = $this->db->escape($srcVal);
                            $joins[] = "INNER JOIN `" . DB_PREFIX . "product_attribute` pa{$idx}"
                                . " ON pa{$idx}.product_id = p.product_id"
                                . " AND pa{$idx}.attribute_id = {$attrId}"
                                . " AND pa{$idx}.language_id = {$langId}"
                                . " AND pa{$idx}.text = '{$escaped}'";
                        }
                    }
                    break;

                case 'name_contains':
                    $text = trim((string)($cond['text'] ?? ''));
                    if ($text !== '') {
                        $escaped = $this->db->escape($text);
                        $joins[] = "INNER JOIN `" . DB_PREFIX . "product_description` pdn{$idx}"
                            . " ON pdn{$idx}.product_id = p.product_id"
                            . " AND pdn{$idx}.language_id = {$langId}"
                            . " AND pdn{$idx}.name LIKE '%{$escaped}%'";
                    }
                    break;

                case 'price_range':
                    $pct = max(1, min(500, (int)($cond['pct'] ?? 20)));
                    if ($srcPrice > 0) {
                        $lo      = (float)number_format($srcPrice * (1 - $pct / 100), 4, '.', '');
                        $hi      = (float)number_format($srcPrice * (1 + $pct / 100), 4, '.', '');
                        $where[] = "p.price BETWEEN " . $lo . " AND " . $hi;
                    }
                    break;

                case 'only_special':
                    $joins[] = "INNER JOIN `" . DB_PREFIX . "product_special` ps{$idx}"
                        . " ON ps{$idx}.product_id = p.product_id AND ps{$idx}.price > 0"
                        . " AND (ps{$idx}.date_start = '0000-00-00' OR ps{$idx}.date_start <= NOW())"
                        . " AND (ps{$idx}.date_end   = '0000-00-00' OR ps{$idx}.date_end   >= NOW())";
                    break;

                case 'exclude_oos':
                    $where[] = "p.quantity > 0";
                    break;

                case 'brand_priority':
                    $brandPriority = true;
                    break;
            }
        }

        $orderBy = $this->buildOrderBy($sort, $langId, $brandPriority ? $srcMfId : 0);

        $sql = "SELECT DISTINCT p.product_id
                FROM `" . DB_PREFIX . "product` p
                " . implode(' ', $joins) . "
                WHERE " . implode(' AND ', $where) . "
                " . $orderBy . "
                LIMIT " . $limit;

        $result = $this->db->query($sql);
        return array_map('intval', array_column($result->rows, 'product_id'));
    }

    private function buildOrderBy(string $sort, int $langId, int $brandFirstMfId = 0): string
    {
        $brandFirstMfId = (int)$brandFirstMfId;
        $langId         = (int)$langId;
        $bp = $brandFirstMfId > 0 ? "(p.manufacturer_id = {$brandFirstMfId}) DESC, " : '';

        switch ($sort) {
            case 'price_asc':
                return "ORDER BY {$bp}p.price ASC";
            case 'price_desc':
                return "ORDER BY {$bp}p.price DESC";
            case 'new':
                return "ORDER BY {$bp}p.date_added DESC";
            case 'name':
                return "ORDER BY {$bp}("
                    . "SELECT pd.name FROM `" . DB_PREFIX . "product_description` pd"
                    . " WHERE pd.product_id = p.product_id AND pd.language_id = {$langId} LIMIT 1) ASC";
            case 'bestseller':
                return "ORDER BY {$bp}("
                    . "SELECT COALESCE(SUM(op.quantity),0)"
                    . " FROM `" . DB_PREFIX . "order_product` op"
                    . " INNER JOIN `" . DB_PREFIX . "order` o ON o.order_id = op.order_id AND o.order_status_id > 0"
                    . " WHERE op.product_id = p.product_id) DESC";
            case 'random':
            default:
                return "ORDER BY {$bp}RAND()";
        }
    }

    // ── DB helpers ────────────────────────────────────────────────────────────

    private function getProductAttributeValue(int $productId, int $attributeId, int $langId): ?string
    {
        $productId   = (int)$productId;
        $attributeId = (int)$attributeId;
        $langId      = (int)$langId;
        $r = $this->db->query(
            "SELECT `text` FROM `" . DB_PREFIX . "product_attribute`"
            . " WHERE product_id = {$productId} AND attribute_id = {$attributeId} AND language_id = {$langId}"
            . " LIMIT 1"
        );
        return empty($r->row) ? null : (string)$r->row['text'];
    }

    private function productNameContains(int $productId, string $text, int $langId): bool
    {
        $productId = (int)$productId;
        $langId    = (int)$langId;
        $escaped   = $this->db->escape($text);
        $r         = $this->db->query(
            "SELECT 1 FROM `" . DB_PREFIX . "product_description`"
            . " WHERE product_id = {$productId} AND language_id = {$langId} AND name LIKE '%{$escaped}%'"
            . " LIMIT 1"
        );
        return !empty($r->row);
    }

    private function getCategoryIds(int $productId): array
    {
        $result = $this->db->query(
            "SELECT DISTINCT cp.path_id AS category_id
             FROM `" . DB_PREFIX . "product_to_category` pc
             JOIN `" . DB_PREFIX . "category_path` cp ON cp.category_id = pc.category_id
             WHERE pc.product_id = " . (int)$productId
        );
        return array_map('intval', array_column($result->rows, 'category_id'));
    }
}
