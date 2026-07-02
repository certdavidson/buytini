<?php
/**
 * Advanced Search Pro — Maximal Marginal Relevance (MMR) Service
 *
 * Reranks a list of product IDs to balance relevance with diversity.
 * Prevents the "10 identical Royal Canin" problem by penalising results
 * that are too similar to already-selected ones.
 *
 * MMR score = λ * relevance − (1 − λ) * max_similarity_to_selected
 *
 * λ = 1.0 → pure relevance (same as no MMR)
 * λ = 0.0 → pure diversity
 * λ = 0.5 → balanced (recommended default)
 *
 * Similarity is computed from shared manufacturer + top-level category.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class MmrService {

    private $db;
    private $config;

    public function __construct($db, $config) {
        $this->db     = $db;
        $this->config = $config;
    }

    /**
     * Rerank product IDs using MMR.
     *
     * @param array $ids      Ordered product IDs (most relevant first)
     * @param float $lambda   Relevance weight 0.0–1.0 (default 0.5)
     * @param int   $limit    Max results to return (0 = all)
     * @return array          Reranked product IDs
     */
    public function rerank(array $ids, float $lambda = 0.5, int $limit = 0): array {
        $ids = array_values(array_map('intval', $ids));
        if (count($ids) <= 1) {
            return $limit > 0 ? array_slice($ids, 0, $limit) : $ids;
        }

        $lambda = max(0.0, min(1.0, $lambda));

        // Assign initial relevance score: position-based (first = most relevant)
        $total = count($ids);
        $relevance = [];
        foreach ($ids as $pos => $id) {
            $relevance[$id] = 1.0 - ($pos / $total);
        }

        // Fetch product metadata for similarity computation
        $meta = $this->fetchMeta($ids);

        $selected  = [];
        $remaining = $ids;
        $maxItems  = $limit > 0 ? min($limit, $total) : $total;

        while (!empty($remaining) && count($selected) < $maxItems) {
            $bestId    = null;
            $bestScore = -PHP_FLOAT_MAX;

            foreach ($remaining as $id) {
                $rel = $relevance[$id] ?? 0.0;

                if (empty($selected)) {
                    $score = $rel;
                } else {
                    $maxSim = 0.0;
                    foreach ($selected as $selId) {
                        $sim    = $this->similarity($meta[$id] ?? [], $meta[$selId] ?? []);
                        $maxSim = max($maxSim, $sim);
                    }
                    $score = $lambda * $rel - (1.0 - $lambda) * $maxSim;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestId    = $id;
                }
            }

            if ($bestId === null) {
                break;
            }

            $selected[]  = $bestId;
            $remaining   = array_filter($remaining, static fn($id) => $id !== $bestId);
            $remaining   = array_values($remaining);
        }

        return $selected;
    }

    /**
     * Compute similarity between two products in [0.0, 1.0].
     * Uses manufacturer (weight 0.7) and top-level category (weight 0.3).
     */
    private function similarity(array $a, array $b): float {
        if (!$a || !$b) {
            return 0.0;
        }

        $sim = 0.0;

        if (!empty($a['manufacturer_id']) && $a['manufacturer_id'] === $b['manufacturer_id']) {
            $sim += 0.7;
        }

        if (!empty($a['category_id']) && $a['category_id'] === $b['category_id']) {
            $sim += 0.3;
        }

        return min(1.0, $sim);
    }

    /**
     * Fetch manufacturer_id and primary category_id for a list of product IDs.
     * Returns [product_id => ['manufacturer_id' => int, 'category_id' => int]]
     */
    private function fetchMeta(array $ids): array {
        if (!$ids) {
            return [];
        }

        $inList = implode(',', $ids);
        $storeId = (int)$this->config->get('config_store_id');

        $res = $this->db->query(
            "SELECT p.product_id, p.manufacturer_id,
                    MIN(p2c.category_id) AS category_id
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c
                ON (p2c.product_id = p.product_id)
             WHERE p.product_id IN (" . $inList . ")
             GROUP BY p.product_id, p.manufacturer_id"
        );

        $meta = [];
        foreach ($res->rows as $row) {
            $meta[(int)$row['product_id']] = [
                'manufacturer_id' => (int)$row['manufacturer_id'],
                'category_id'     => (int)$row['category_id'],
            ];
        }

        return $meta;
    }
}
