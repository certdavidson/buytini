<?php
/**
 * Advanced Search Pro — Trigram Fallback Matcher
 *
 * Third-level cascade: fires only when the primary search returned 0 results.
 * Splits the query into character trigrams and searches product names using
 * multiple LIKE patterns — no schema changes required.
 *
 * Strategy:
 *   1. Compute trigrams of the query.
 *   2. Run SQL: SELECT product_id FROM product_description
 *               WHERE name LIKE '%tri1%' OR name LIKE '%tri2%' ...
 *   3. Score results by how many trigrams matched → order by score DESC.
 *   4. Return top $limit product IDs.
 *
 * Native mode only. Only fires when all previous cascade levels gave 0 results.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class TrigramMatcher {

    private $db;
    private $config;

    // Don't bother for very short queries — too many false positives
    private const MIN_QUERY_LEN = 4;

    // Minimum trigram similarity ratio (matched_trigrams / total_trigrams)
    private const MIN_SCORE = 0.4;

    // Max number of LIKE clauses in the SQL to avoid huge queries
    private const MAX_TRIGRAMS_IN_SQL = 12;

    public function __construct($db, $config) {
        $this->db     = $db;
        $this->config = $config;
    }

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Find products whose names are trigram-similar to $query.
     *
     * @param  string $query   Normalised query (no result from primary search)
     * @param  int    $limit   Max product IDs to return
     * @return array{ids: int[], total: int}
     */
    public function match(string $query, int $limit = 100): array {
        $query = mb_strtolower(trim($query), 'UTF-8');

        if (mb_strlen($query, 'UTF-8') < self::MIN_QUERY_LEN) {
            return ['ids' => [], 'total' => 0];
        }

        $trigrams = $this->buildTrigrams($query);
        if (count($trigrams) < 2) {
            return ['ids' => [], 'total' => 0];
        }

        $langId  = (int)$this->config->get('config_language_id');
        $storeId = (int)$this->config->get('config_store_id');
        $total   = count($trigrams);

        // Use only the most distinctive trigrams (up to MAX_TRIGRAMS_IN_SQL)
        $sqlTrigrams = array_slice($trigrams, 0, self::MAX_TRIGRAMS_IN_SQL);

        // Build CASE WHEN scoring + WHERE OR condition
        $scoreExpr = [];
        $whereOr   = [];
        foreach ($sqlTrigrams as $tri) {
            $esc       = $this->db->escape($tri);
            $whereOr[] = "pd.name LIKE '%" . $esc . "%'";
            $scoreExpr[] = "(CASE WHEN pd.name LIKE '%" . $esc . "%' THEN 1 ELSE 0 END)";
        }

        $scoreSQL = '(' . implode(' + ', $scoreExpr) . ')';
        $minScore = max(1, (int)ceil(count($sqlTrigrams) * self::MIN_SCORE));

        $sql = "SELECT p.product_id, " . $scoreSQL . " AS tri_score
                FROM `" . DB_PREFIX . "product` p
                INNER JOIN `" . DB_PREFIX . "product_description` pd
                    ON (p.product_id = pd.product_id AND pd.language_id = '" . $langId . "')
                INNER JOIN `" . DB_PREFIX . "product_to_store` p2s
                    ON (p.product_id = p2s.product_id AND p2s.store_id = '" . $storeId . "')
                WHERE p.status = '1'
                  AND p.date_available <= NOW()
                  AND (" . implode(' OR ', $whereOr) . ")
                HAVING tri_score >= " . $minScore . "
                ORDER BY tri_score DESC
                LIMIT " . max(1, (int)$limit);

        $rows = $this->db->query($sql);

        $ids = [];
        foreach ($rows->rows as $row) {
            $ids[] = (int)$row['product_id'];
        }

        return ['ids' => $ids, 'total' => count($ids)];
    }

    // ── Trigram builder ────────────────────────────────────────────────────

    /**
     * Build character trigrams from a string.
     * Pads with spaces: " ab" "abc" "bc " for "abc"
     * Returns unique trigrams, longest first.
     */
    public function buildTrigrams(string $text): array {
        $text = ' ' . $text . ' ';
        $len  = mb_strlen($text, 'UTF-8');
        $tris = [];

        for ($i = 0; $i <= $len - 3; $i++) {
            $tri = mb_substr($text, $i, 3, 'UTF-8');
            // Skip trigrams that are all spaces or contain only 1 real char
            if (trim($tri) !== '') {
                $tris[$tri] = true;
            }
        }

        return array_keys($tris);
    }
}
