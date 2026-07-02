<?php
/**
 * Advanced Search Pro — Word Splitter
 *
 * Splits run-on / compound queries into individual words.
 * "Кормдлясобак" → "Корм для собак"
 * "royalcanin" → "royal canin"
 *
 * Algorithm: greedy forward max-match against a vocabulary built from
 * the query log + a static list of common stop-words. Only fires when
 * the input is a single token with no spaces.
 *
 * Default OFF — enable via admin setting enable_word_split = 1.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class WordSplitter {

    private $db;

    // Cached vocabulary set (lowercase words that appear in query log)
    private static $vocab     = null;
    private static $vocabTime = 0;
    private const  VOCAB_TTL  = 600;

    // Common UA/RU/EN function words always in vocabulary
    private static $stopWords = [
        'для', 'від', 'без', 'про', 'при', 'під', 'над', 'між', 'через',
        'після', 'перед', 'та', 'або', 'але', 'що', 'як', 'до', 'на', 'за',
        'із', 'зі', 'від', 'по', 'з', 'в', 'у', 'і', 'й', 'не', 'ні',
        'для', 'это', 'для', 'кот', 'пес', 'кошка', 'собака',
        'for', 'the', 'and', 'with', 'from', 'dry', 'wet',
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Try to split a single-token query into space-separated words.
     *
     * Returns the split string if confident, or null if splitting doesn't
     * produce a meaningful result.
     *
     * @param string $query  Already-normalised query
     * @param int    $minLen Minimum length of each split part
     */
    public function trySplit(string $query, int $minLen = 3): ?string {
        $query = trim($query);

        // Only process single-token inputs (no spaces)
        if ($query === '' || mb_strpos($query, ' ') !== false) {
            return null;
        }

        $len = mb_strlen($query, 'UTF-8');
        if ($len < 6) { // too short to be compound
            return null;
        }

        $vocab = $this->getVocab();
        $parts = $this->maxMatch(mb_strtolower($query, 'UTF-8'), $vocab, $minLen);

        if (!$parts || count($parts) < 2) {
            return null;
        }

        // Reconstruct with original case for first char of each part
        $result = [];
        $offset = 0;
        foreach ($parts as $part) {
            $partLen = mb_strlen($part, 'UTF-8');
            $original = mb_substr($query, $offset, $partLen, 'UTF-8');
            $result[] = $original;
            $offset += $partLen;
        }

        return implode(' ', $result);
    }

    /**
     * Greedy forward max-match segmentation.
     * Tries to consume as many characters as possible at each step by
     * matching from the longest possible prefix down to $minLen.
     *
     * Returns array of matched segments, or empty array on failure.
     */
    private function maxMatch(string $text, array $vocab, int $minLen): array {
        $len   = mb_strlen($text, 'UTF-8');
        $parts = [];
        $pos   = 0;

        while ($pos < $len) {
            $remaining = $len - $pos;
            $matched   = false;

            // Try longest match first; skip chunk that spans the entire remaining
            // input (= the full original text at pos=0), which would prevent a real split.
            for ($end = $remaining; $end >= $minLen; $end--) {
                if ($pos === 0 && $end === $len) {
                    continue; // skip whole-string match — must split into ≥2 parts
                }
                $chunk = mb_substr($text, $pos, $end, 'UTF-8');
                if (isset($vocab[$chunk])) {
                    $parts[] = $chunk;
                    $pos += $end;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                // Cannot segment — abort
                return [];
            }
        }

        return $parts;
    }

    /**
     * Build vocabulary from:
     *   1. Words from MULTI-TOKEN successful queries (single-token queries are
     *      excluded because autocomplete logs every prefix keystroke, polluting
     *      the vocab with fragments like "зарядніс", "зарядніст", etc.)
     *   2. Individual words extracted from product and category names.
     *   3. Static stop-words.
     * Words shorter than 3 chars are excluded.
     */
    private function getVocab(): array {
        $now = time();
        if (self::$vocab !== null && ($now - self::$vocabTime) < self::VOCAB_TTL) {
            return self::$vocab;
        }

        $vocab = [];

        // Static stop-words
        foreach (self::$stopWords as $w) {
            $vocab[$w] = true;
        }

        // Words from MULTI-TOKEN successful queries only
        $res = $this->db->query(
            "SELECT `query`
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE `results` > 0 AND `query` LIKE '% %'
             GROUP BY `query`
             ORDER BY COUNT(*) DESC
             LIMIT 500"
        );

        foreach ($res->rows as $row) {
            $words = preg_split('/\s+/u', mb_strtolower(trim((string)$row['query']), 'UTF-8'));
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w, 'UTF-8') >= 3) {
                    $vocab[$w] = true;
                }
            }
        }

        // Words from product names (reliable real-word vocabulary)
        $langRow = $this->db->query(
            "SELECT value FROM `" . DB_PREFIX . "setting`
             WHERE `key` = 'config_language_id' AND store_id = 0
             LIMIT 1"
        )->row;
        $lang_id = isset($langRow['value']) ? (int)$langRow['value'] : 1;
        if ($lang_id < 1) {
            $lang_id = 1;
        }

        $pRes = $this->db->query(
            "SELECT pd.name
             FROM `" . DB_PREFIX . "product_description` pd
             INNER JOIN `" . DB_PREFIX . "product` p ON pd.product_id = p.product_id
             WHERE pd.language_id = '" . $lang_id . "'
               AND p.status = 1
             LIMIT 3000"
        );
        foreach ($pRes->rows as $row) {
            $words = preg_split('/[\s\-\/,\.]+/u', mb_strtolower(trim((string)$row['name']), 'UTF-8'));
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w, 'UTF-8') >= 3) {
                    $vocab[$w] = true;
                }
            }
        }

        // Words from category names
        $cRes = $this->db->query(
            "SELECT name
             FROM `" . DB_PREFIX . "category_description`
             WHERE language_id = '" . $lang_id . "'"
        );
        foreach ($cRes->rows as $row) {
            $words = preg_split('/[\s\-\/,\.]+/u', mb_strtolower(trim((string)$row['name']), 'UTF-8'));
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w, 'UTF-8') >= 3) {
                    $vocab[$w] = true;
                }
            }
        }

        self::$vocab     = $vocab;
        self::$vocabTime = $now;

        return $vocab;
    }
}
