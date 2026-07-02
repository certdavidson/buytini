<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 7.4 implementation — explicit properties, no constructor promotion.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID < 80000.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

use OcKit\AutoRelated\Dto\ProductSignals;
use OcKit\AutoRelated\Dto\ScoringWeights;
use OcKit\AutoRelated\Dto\GenerationResult;

/**
 * Loads product data from DB and coordinates scoring + writing for one or many products.
 */
class GeneratorQueue
{
    private \DB             $db;
    private ScoringWeights  $weights;
    private SimilarityScorer $scorer;
    private RelatedWriter    $writer;
    private array            $config;  // module config array

    public function __construct(\DB $db, ScoringWeights $weights, SimilarityScorer $scorer, RelatedWriter $writer, array $config)
    {
        $this->db      = $db;
        $this->weights = $weights;
        $this->scorer  = $scorer;
        $this->writer  = $writer;
        $this->config  = $config;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Generate related products for a single product_id.
     */
    public function generateOne(int $productId, string $source = 'manual'): GenerationResult
    {
        $overwrite     = (bool)($this->config['overwrite']       ?? false);
        $cacheEnabled  = (bool)($this->config['cache']           ?? true);
        $ttlHours      = (int)($this->config['cache_ttl']        ?? 72);
        $limit         = (int)($this->config['related_limit']    ?? 8);
        $candidateMax  = (int)($this->config['candidate_limit']  ?? 1000);

        // Skip if has existing and overwrite=false
        if (!$overwrite && $this->writer->hasExisting($productId)) {
            return new GenerationResult($productId, [], true);
        }

        // Skip if TTL not expired AND related products are actually present
        if (!$this->writer->needsGeneration($productId, $ttlHours, $cacheEnabled)
            && $this->writer->hasExisting($productId)
        ) {
            return new GenerationResult($productId, [], true);
        }

        try {
            $source_signals = $this->loadSignals($productId);
            if ($source_signals === null) {
                return new GenerationResult($productId, [], false, 'product_not_found');
            }

            $candidates = $this->loadCandidates($productId, $candidateMax);

            if (empty($candidates)) {
                $this->writer->write($productId, []);
                $this->writer->logGeneration($productId, 0, $source);
                return new GenerationResult($productId, [], false);
            }

            // Pre-compute coorders map if weight > 0
            $coordersMap = [];
            if ($this->weights->coorders > 0) {
                $coordersMap = $this->loadCoordersMap($productId, array_column($candidates, 'product_id'));
            }

            // Score candidates
            $scored               = [];
            $candidateManufacturers = [];
            foreach ($candidates as $row) {
                $candId      = (int)$row['product_id'];
                $candSignals = $this->buildSignalsFromRow($row);
                $score       = $this->scorer->score($source_signals, $candSignals);

                if (isset($coordersMap[$candId])) {
                    $score = $this->scorer->addCoordersSignal($score, $coordersMap[$candId]);
                }

                $scored[$candId]                = $score;
                $candidateManufacturers[$candId] = (int)$row['manufacturer_id'];
            }

            arsort($scored);

            // Brand priority: push same-manufacturer products to front (within limit)
            if ($this->weights->brandPriority && $source_signals->manufacturerId > 0) {
                $srcMfId    = $source_signals->manufacturerId;
                $sameBrand  = [];
                $otherBrand = [];
                foreach ($scored as $pid => $score) {
                    if (($candidateManufacturers[$pid] ?? 0) === $srcMfId) {
                        $sameBrand[$pid] = $score;
                    } else {
                        $otherBrand[$pid] = $score;
                    }
                }
                $scored = $sameBrand + $otherBrand;
            }

            $topIds = array_keys(array_slice($scored, 0, $limit, true));

            // result_sort: reorder final list after score-based selection
            $topIds = $this->applyResultSort($topIds, $candidates, $this->config['result_sort'] ?? 'score');

            $this->writer->write($productId, $topIds);
            $this->writer->logGeneration($productId, count($topIds), $source);

            return new GenerationResult($productId, $topIds, false);

        } catch (\Throwable $e) {
            return new GenerationResult($productId, [], false, $e->getMessage());
        }
    }

    /**
     * Generate a batch for the admin AJAX generate action.
     * Returns ['processed' => N, 'total' => M, 'done' => bool].
     */
    public function generateBatch(array $filters, int $batchSize, int $offset): array
    {
        $total    = $this->countProductsForGeneration($filters);
        $products = $this->getProductsForGeneration($filters, $batchSize, $offset);

        $overwrite = (bool)($filters['overwrite'] ?? false);
        $origOverwrite = $this->config['overwrite'] ?? false;
        $this->config['overwrite'] = $overwrite;

        $processed = 0;
        foreach ($products as $row) {
            $result = $this->generateOne((int)$row['product_id'], 'manual');
            if (!$result->skipped) {
                $processed++;
            }
        }

        $this->config['overwrite'] = $origOverwrite;

        return [
            'processed' => $processed,
            'total'     => $total,
            'done'      => ($offset + count($products)) >= $total,
        ];
    }

    /**
     * Dry-run preview: score candidates for a product without writing to DB.
     * Returns ['source' => [...], 'results' => [['product_id', 'name', 'price', 'score'], ...]].
     */
    public function previewOne(int $productId): array
    {
        $limit        = (int)($this->config['related_limit']   ?? 8);
        $candidateMax = (int)($this->config['candidate_limit'] ?? 1000);

        $source_signals = $this->loadSignals($productId);
        if ($source_signals === null) {
            return ['error' => 'product_not_found'];
        }

        $candidates = $this->loadCandidates($productId, $candidateMax);

        if (empty($candidates)) {
            return [
                'source'  => ['product_id' => $productId, 'name' => $source_signals->name, 'price' => $source_signals->price],
                'results' => [],
            ];
        }

        $coordersMap = [];
        if ($this->weights->coorders > 0) {
            $coordersMap = $this->loadCoordersMap($productId, array_column($candidates, 'product_id'));
        }

        $scored               = [];
        $candidateManufacturers = [];
        $candidateMeta        = []; // product_id => [name, price]
        foreach ($candidates as $row) {
            $candId      = (int)$row['product_id'];
            $candSignals = $this->buildSignalsFromRow($row);
            $score       = $this->scorer->score($source_signals, $candSignals);

            if (isset($coordersMap[$candId])) {
                $score = $this->scorer->addCoordersSignal($score, $coordersMap[$candId]);
            }

            $scored[$candId]                = $score;
            $candidateManufacturers[$candId] = (int)$row['manufacturer_id'];
            $candidateMeta[$candId]          = [
                'name'  => (string)($row['name'] ?? ''),
                'price' => (float)($row['price'] ?? 0.0),
            ];
        }

        arsort($scored);

        if ($this->weights->brandPriority && $source_signals->manufacturerId > 0) {
            $srcMfId    = $source_signals->manufacturerId;
            $sameBrand  = [];
            $otherBrand = [];
            foreach ($scored as $pid => $score) {
                if (($candidateManufacturers[$pid] ?? 0) === $srcMfId) {
                    $sameBrand[$pid] = $score;
                } else {
                    $otherBrand[$pid] = $score;
                }
            }
            $scored = $sameBrand + $otherBrand;
        }

        $results = [];
        $count   = 0;
        foreach ($scored as $pid => $score) {
            if ($count >= $limit) {
                break;
            }
            $meta      = $candidateMeta[$pid] ?? ['name' => '', 'price' => 0.0];
            $results[] = [
                'product_id' => $pid,
                'name'       => $meta['name'],
                'price'      => $meta['price'],
                'score'      => round($score, 4),
            ];
            $count++;
        }

        return [
            'source' => [
                'product_id' => $productId,
                'name'       => $source_signals->name,
                'price'      => $source_signals->price,
            ],
            'results' => $results,
        ];
    }

    /**
     * Count products pending generation (for cron batching).
     */
    public function countPending(bool $force, int $ttlHours): int
    {
        if ($force) {
            return (int)($this->db->query($this->buildProductBaseQuery([], 'COUNT(*)'))->row['cnt'] ?? 0);
        }

        $result = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM (" . $this->buildPendingSubquery($ttlHours) . ") sub"
        );
        return (int)($result->row['cnt'] ?? 0);
    }

    /**
     * Get next batch of product IDs pending generation (for cron).
     */
    public function getPendingIds(bool $force, int $ttlHours, int $limit, int $offset, array $filters = []): array
    {
        if ($force) {
            $rows = $this->getProductsForGeneration($filters, $limit, $offset);
        } else {
            $sql = $this->buildPendingSubquery($ttlHours, $limit, $offset);
            $rows = $this->db->query($sql)->rows;
        }
        return array_column($rows, 'product_id');
    }

    // ── Signal loading ────────────────────────────────────────────────────────

    private function loadSignals(int $productId): ?ProductSignals
    {
        $langId = (int)($this->config['language_id'] ?? 1);

        $result = $this->db->query(
            "SELECT p.product_id, p.manufacturer_id, p.price, pd.name
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON (pd.product_id = p.product_id AND pd.language_id = " . $langId . ")
             WHERE p.product_id = " . $productId
        );

        if (empty($result->row)) {
            return null;
        }

        return $this->buildSignalsFromRow(
            $result->row,
            $this->loadCategoryIds($productId),
            $this->loadFields($productId),
            $this->loadAttributeValues($productId)
        );
    }

    private function buildSignalsFromRow(array $row, ?array $categoryIds = null, ?array $fields = null, ?array $attrValues = null): ProductSignals
    {
        return ProductSignals::fromRow(
            $row,
            $categoryIds ?? (array)($row['_category_ids'] ?? []),
            $fields      ?? (array)($row['_fields']       ?? []),
            $attrValues  ?? (array)($row['_attr_values']  ?? [])
        );
    }

    private function loadCategoryIds(int $productId): array
    {
        $result = $this->db->query(
            "SELECT DISTINCT c.category_id
             FROM `" . DB_PREFIX . "product_to_category` pc
             JOIN `" . DB_PREFIX . "category_path` cp ON cp.category_id = pc.category_id
             JOIN `" . DB_PREFIX . "category` c ON c.category_id = cp.path_id
             WHERE pc.product_id = " . $productId
        );
        return array_column($result->rows, 'category_id');
    }

    private function loadFields(int $productId): array
    {
        $result = $this->db->query(
            "SELECT sku, mpn, ean, jan, isbn, upc
             FROM `" . DB_PREFIX . "product`
             WHERE product_id = " . $productId
        );
        return $result->row ?: [];
    }

    private function loadAttributeValues(int $productId): array
    {
        $langId = (int)($this->config['language_id'] ?? 1);

        $result = $this->db->query(
            "SELECT attribute_id, text
             FROM `" . DB_PREFIX . "product_attribute`
             WHERE product_id = " . $productId . "
               AND language_id = " . $langId
        );

        $values = [];
        foreach ($result->rows as $row) {
            $values[(int)$row['attribute_id']] = $row['text'];
        }
        return $values;
    }

    // ── Candidate loading ─────────────────────────────────────────────────────

    private function loadCandidates(int $excludeId, int $limit): array
    {
        $excludeOos      = (bool)($this->config['exclude_oos']      ?? true);
        $excludeDisabled = (bool)($this->config['exclude_disabled'] ?? true);
        $onlySpecial     = (bool)($this->config['only_special']     ?? false);
        $langId          = (int)($this->config['language_id']       ?? 1);

        $where = ["p.product_id != " . $excludeId];
        $joins = [];
        if ($excludeOos)      { $where[] = "p.quantity > 0"; }
        if ($excludeDisabled) { $where[] = "p.status = 1"; }

        // only_special: INNER JOIN to filter products with an active special price
        if ($onlySpecial) {
            $joins[] = "INNER JOIN `" . DB_PREFIX . "product_special` ps"
                . " ON ps.product_id = p.product_id AND ps.price > 0"
                . " AND (ps.date_start = '0000-00-00' OR ps.date_start <= NOW())"
                . " AND (ps.date_end = '0000-00-00' OR ps.date_end >= NOW())";
        }

        // Blacklist
        $blProducts   = array_map('intval', (array)($this->config['blacklist_products']   ?? []));
        $blCategories = array_map('intval', (array)($this->config['blacklist_categories'] ?? []));
        if (!empty($blProducts)) {
            $where[] = "p.product_id NOT IN (" . implode(',', $blProducts) . ")";
        }
        if (!empty($blCategories)) {
            $where[] = "p.product_id NOT IN (SELECT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE category_id IN (" . implode(',', $blCategories) . "))";
        }

        $whereStr = implode(' AND ', $where);
        $joinStr  = implode(' ', $joins);

        $result = $this->db->query(
            "SELECT DISTINCT p.product_id, p.manufacturer_id, p.price, pd.name,
                    p.sku, p.mpn, p.ean, p.jan, p.isbn, p.upc, p.date_added
             FROM `" . DB_PREFIX . "product` p
             " . $joinStr . "
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON (pd.product_id = p.product_id AND pd.language_id = " . $langId . ")
             WHERE " . $whereStr . "
             ORDER BY p.product_id DESC
             LIMIT " . $limit
        );

        if (empty($result->rows)) {
            return [];
        }

        $productIds = array_column($result->rows, 'product_id');

        // Load category IDs for all candidates in bulk
        $categoryMap = $this->loadCategoryIdsForMany($productIds);

        // Load attribute values for all candidates in bulk
        $attrMap = $this->loadAttributeValuesForMany($productIds);

        foreach ($result->rows as &$row) {
            $pid = (int)$row['product_id'];
            $row['_category_ids'] = $categoryMap[$pid] ?? [];
            $row['_fields'] = [
                'sku'  => $row['sku']  ?? '',
                'mpn'  => $row['mpn']  ?? '',
                'ean'  => $row['ean']  ?? '',
                'jan'  => $row['jan']  ?? '',
                'isbn' => $row['isbn'] ?? '',
                'upc'  => $row['upc']  ?? '',
            ];
            $row['_attr_values'] = $attrMap[$pid] ?? [];
        }
        unset($row);

        return $result->rows;
    }

    private function loadCategoryIdsForMany(array $productIds): array
    {
        if (empty($productIds)) return [];
        $ids = implode(',', array_map('intval', $productIds));

        $result = $this->db->query(
            "SELECT DISTINCT pc.product_id, cp.path_id AS category_id
             FROM `" . DB_PREFIX . "product_to_category` pc
             JOIN `" . DB_PREFIX . "category_path` cp ON cp.category_id = pc.category_id
             WHERE pc.product_id IN (" . $ids . ")"
        );

        $map = [];
        foreach ($result->rows as $row) {
            $map[(int)$row['product_id']][] = (int)$row['category_id'];
        }
        return $map;
    }

    private function loadAttributeValuesForMany(array $productIds): array
    {
        if (empty($productIds)) return [];
        $langId = (int)($this->config['language_id'] ?? 1);
        $ids    = implode(',', array_map('intval', $productIds));

        $filterIds = $this->weights->attributeIds;
        $attrWhere = '';
        if (!empty($filterIds)) {
            $attrWhere = " AND attribute_id IN (" . implode(',', array_map('intval', $filterIds)) . ")";
        }

        $result = $this->db->query(
            "SELECT product_id, attribute_id, text
             FROM `" . DB_PREFIX . "product_attribute`
             WHERE product_id IN (" . $ids . ")
               AND language_id = " . $langId . $attrWhere
        );

        $map = [];
        foreach ($result->rows as $row) {
            $map[(int)$row['product_id']][(int)$row['attribute_id']] = $row['text'];
        }
        return $map;
    }

    // ── Co-orders ─────────────────────────────────────────────────────────────

    /**
     * Returns [product_id => normalized_coorders_score] for candidates.
     */
    private function loadCoordersMap(int $productId, array $candidateIds): array
    {
        if (empty($candidateIds)) return [];

        $days      = $this->weights->coordersDays;
        $minOrders = $this->weights->coordersMin;
        $statuses  = $this->weights->coordersStatuses;
        $ids       = implode(',', array_map('intval', $candidateIds));

        $statusWhere = '';
        if (!empty($statuses)) {
            $statusWhere = " AND o.order_status_id IN (" . implode(',', $statuses) . ")";
        }

        $result = $this->db->query(
            "SELECT op2.product_id, COUNT(DISTINCT op1.order_id) AS shared
             FROM `" . DB_PREFIX . "order_product` op1
             JOIN `" . DB_PREFIX . "order_product` op2 ON op2.order_id = op1.order_id
             JOIN `" . DB_PREFIX . "order` o ON o.order_id = op1.order_id
             WHERE op1.product_id = " . $productId . "
               AND op2.product_id != " . $productId . "
               AND op2.product_id IN (" . $ids . ")
               AND o.date_added >= DATE_SUB(NOW(), INTERVAL " . $days . " DAY)
               " . $statusWhere . "
             GROUP BY op2.product_id
             HAVING shared >= " . $minOrders
        );

        if (empty($result->rows)) return [];

        $maxShared = max(array_column($result->rows, 'shared'));
        $map = [];
        foreach ($result->rows as $row) {
            $map[(int)$row['product_id']] = $maxShared > 0 ? $row['shared'] / $maxShared : 0.0;
        }
        return $map;
    }

    // ── Product selection ─────────────────────────────────────────────────────

    private function getProductsForGeneration(array $filters, int $limit, int $offset): array
    {
        $excludeOos      = (bool)($this->config['exclude_oos']      ?? true);
        $excludeDisabled = (bool)($this->config['exclude_disabled'] ?? true);

        $where = ['1'];
        if ($excludeOos)      { $where[] = "p.quantity > 0"; }
        if ($excludeDisabled) { $where[] = "p.status = 1"; }

        if (!empty($filters['id_from'])) { $where[] = "p.product_id >= " . (int)$filters['id_from']; }
        if (!empty($filters['id_to']))   { $where[] = "p.product_id <= " . (int)$filters['id_to']; }

        if (!empty($filters['categories'])) {
            $catIds = implode(',', array_map('intval', (array)$filters['categories']));
            $where[] = "p.product_id IN (SELECT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE category_id IN (" . $catIds . "))";
        }
        if (!empty($filters['manufacturers'])) {
            $mfIds = implode(',', array_map('intval', (array)$filters['manufacturers']));
            $where[] = "p.manufacturer_id IN (" . $mfIds . ")";
        }

        $whereStr = implode(' AND ', $where);

        $result = $this->db->query(
            "SELECT p.product_id
             FROM `" . DB_PREFIX . "product` p
             WHERE " . $whereStr . "
             ORDER BY p.product_id ASC
             LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );

        return $result->rows;
    }

    private function countProductsForGeneration(array $filters): int
    {
        $excludeOos      = (bool)($this->config['exclude_oos']      ?? true);
        $excludeDisabled = (bool)($this->config['exclude_disabled'] ?? true);

        $where = ['1'];
        if ($excludeOos)      { $where[] = "p.quantity > 0"; }
        if ($excludeDisabled) { $where[] = "p.status = 1"; }

        if (!empty($filters['id_from'])) { $where[] = "p.product_id >= " . (int)$filters['id_from']; }
        if (!empty($filters['id_to']))   { $where[] = "p.product_id <= " . (int)$filters['id_to']; }

        if (!empty($filters['categories'])) {
            $catIds = implode(',', array_map('intval', (array)$filters['categories']));
            $where[] = "p.product_id IN (SELECT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE category_id IN (" . $catIds . "))";
        }
        if (!empty($filters['manufacturers'])) {
            $mfIds = implode(',', array_map('intval', (array)$filters['manufacturers']));
            $where[] = "p.manufacturer_id IN (" . $mfIds . ")";
        }

        $whereStr = implode(' AND ', $where);
        $result   = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product` p WHERE " . $whereStr
        );
        return (int)($result->row['cnt'] ?? 0);
    }

    private function buildPendingSubquery(int $ttlHours, int $limit = 0, int $offset = 0): string
    {
        $excludeOos      = (bool)($this->config['exclude_oos']      ?? true);
        $excludeDisabled = (bool)($this->config['exclude_disabled'] ?? true);

        $where = ['1'];
        if ($excludeOos)      { $where[] = "p.quantity > 0"; }
        if ($excludeDisabled) { $where[] = "p.status = 1"; }
        $whereStr = implode(' AND ', $where);

        $limitStr = $limit > 0 ? " LIMIT " . $limit . " OFFSET " . $offset : '';

        return "SELECT p.product_id
                FROM `" . DB_PREFIX . "product` p
                LEFT JOIN `" . DB_PREFIX . "auto_related_log` arl ON arl.product_id = p.product_id
                WHERE " . $whereStr . "
                  AND (arl.product_id IS NULL OR arl.generated_at < DATE_SUB(NOW(), INTERVAL " . $ttlHours . " HOUR))
                ORDER BY p.product_id ASC" . $limitStr;
    }

    private function buildProductBaseQuery(array $filters, string $select): string
    {
        return "SELECT " . $select . " AS cnt FROM `" . DB_PREFIX . "product` p WHERE 1";
    }

    // ── Result sort ───────────────────────────────────────────────────────────

    /**
     * Reorders the final top-N product IDs according to result_sort config.
     * Score-based selection happens first; this only affects display order.
     *
     * @param int[]   $topIds    Product IDs already chosen by scoring
     * @param array[] $candidates Full candidate rows (keyed by iteration, contain 'product_id','price','name','date_added')
     * @param string  $sort      Config value: score|random|price_asc|price_desc|new|name
     * @return int[]
     */
    private function applyResultSort(array $topIds, array $candidates, string $sort): array
    {
        if ($sort === 'score' || empty($topIds)) {
            return $topIds; // keep score order (already sorted by arsort)
        }

        if ($sort === 'random') {
            shuffle($topIds);
            return $topIds;
        }

        // Build lookup: product_id => row data
        $meta = [];
        foreach ($candidates as $row) {
            $pid = (int)$row['product_id'];
            $meta[$pid] = $row;
        }

        usort($topIds, function ($a, $b) use ($sort, $meta) {
            $ra = $meta[$a] ?? [];
            $rb = $meta[$b] ?? [];

            switch ($sort) {
                case 'price_asc':
                    return (float)($ra['price'] ?? 0) <=> (float)($rb['price'] ?? 0);
                case 'price_desc':
                    return (float)($rb['price'] ?? 0) <=> (float)($ra['price'] ?? 0);
                case 'new':
                    return strcmp((string)($rb['date_added'] ?? ''), (string)($ra['date_added'] ?? ''));
                case 'name':
                    return strcmp((string)($ra['name'] ?? ''), (string)($rb['name'] ?? ''));
                default:
                    return 0;
            }
        });

        return $topIds;
    }
}
