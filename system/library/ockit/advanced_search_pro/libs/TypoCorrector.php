<?php
/**
 * Advanced Search Pro — Typo Corrector
 *
 * Finds the closest known query from the search log for a misspelled input.
 * Works like a lightweight SymSpell: builds a frequency vocabulary from
 * successful queries (results > 0) and scores candidates by Levenshtein
 * distance + frequency.
 *
 * "Сімпарікп" → "Сімпаріка" (adjacent key typo)
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class TypoCorrector {

    private $db;

    // Cached vocabulary: [query => frequency]
    private static $vocab     = null;
    private static $vocabTime = 0;
    private const  VOCAB_TTL  = 600; // rebuild every 10 min

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Return the most likely correction for a misspelled query, or null if
     * the query looks correct (i.e. it already exists in the vocabulary or
     * no confident correction is found).
     *
     * @param string $query        Raw (normalised) user query
     * @param int    $maxDistance  Max edit distance to accept (1 or 2)
     * @param int    $minQueryLen  Don't correct very short words
     */
    public function correct(string $query, int $maxDistance = 2, int $minQueryLen = 4): ?string {
        $query = trim(mb_strtolower($query, 'UTF-8'));
        if ($query === '' || mb_strlen($query, 'UTF-8') < $minQueryLen) {
            return null;
        }

        $vocab = $this->getVocab();
        if (!$vocab) {
            return null;
        }

        // Already a known query → no correction needed
        if (isset($vocab[$query])) {
            return null;
        }

        $bestCandidate = null;
        $bestScore     = PHP_INT_MAX;
        $bestFreq      = 0;

        foreach ($vocab as $candidate => $freq) {
            $candLen  = mb_strlen($candidate, 'UTF-8');
            $queryLen = mb_strlen($query, 'UTF-8');

            // Quick length pre-filter — avoid expensive levenshtein for far-off lengths
            if (abs($candLen - $queryLen) > $maxDistance) {
                continue;
            }

            // levenshtein() works on bytes; for multibyte strings encode to a
            // single-byte representation using character ordinal mapping.
            $dist = $this->mbLevenshtein($query, $candidate);
            if ($dist === 0) {
                return null; // exact match found, no correction
            }

            if ($dist > $maxDistance) {
                continue;
            }

            // Prefer lower distance; break ties by higher frequency
            if ($dist < $bestScore || ($dist === $bestScore && $freq > $bestFreq)) {
                $bestScore     = $dist;
                $bestCandidate = $candidate;
                $bestFreq      = $freq;
            }
        }

        return $bestCandidate;
    }

    /**
     * Load (or return cached) vocabulary of top successful queries.
     * Returns array [query_lowercase => frequency].
     */
    private function getVocab(): array {
        $now = time();
        if (self::$vocab !== null && ($now - self::$vocabTime) < self::VOCAB_TTL) {
            return self::$vocab;
        }

        $res = $this->db->query(
            "SELECT `query`, COUNT(*) AS freq
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE `results` > 0
               AND `query` != ''
               AND LENGTH(`query`) >= 3
             GROUP BY `query`
             ORDER BY freq DESC
             LIMIT 500"
        );

        $vocab = [];
        foreach ($res->rows as $row) {
            $q = trim(mb_strtolower((string)$row['query'], 'UTF-8'));
            if ($q !== '') {
                $vocab[$q] = (int)$row['freq'];
            }
        }

        self::$vocab     = $vocab;
        self::$vocabTime = $now;

        return $vocab;
    }

    /**
     * Compute Levenshtein distance for multibyte (UTF-8) strings.
     * Splits into arrays of Unicode codepoints and uses DP.
     * Capped at 3 for performance.
     */
    private function mbLevenshtein(string $s1, string $s2, int $cap = 3): int {
        $a = preg_split('//u', $s1, -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', $s2, -1, PREG_SPLIT_NO_EMPTY);
        $la = count($a);
        $lb = count($b);

        if ($la === 0) return min($lb, $cap);
        if ($lb === 0) return min($la, $cap);

        // DP with early-exit row minimum optimisation
        $prev = range(0, $lb);
        for ($i = 1; $i <= $la; $i++) {
            $curr    = [$i];
            $rowMin  = $i;
            for ($j = 1; $j <= $lb; $j++) {
                $cost    = ($a[$i - 1] === $b[$j - 1]) ? 0 : 1;
                $curr[$j] = min($curr[$j - 1] + 1, $prev[$j] + 1, $prev[$j - 1] + $cost);
                $rowMin   = min($rowMin, $curr[$j]);
            }
            if ($rowMin >= $cap) {
                return $cap;
            }
            $prev = $curr;
        }

        return min($prev[$lb], $cap);
    }
}
