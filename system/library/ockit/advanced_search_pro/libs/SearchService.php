<?php
/**
 * Advanced Search Pro — Search Service
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class SearchService {
    private $registry;
    private $db;
    private $config;
    private $log;
    private $transliterator = null;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db       = $registry->get('db');
        $this->config   = $registry->get('config');
        $this->log      = $registry->has('log') ? $registry->get('log') : null;
    }

    public function normalizeQuery($query) {
        $query = (string)$query;
        $query = html_entity_decode($query, ENT_QUOTES, 'UTF-8');
        $query = strip_tags($query);
        $query = preg_replace('/[^\p{L}\p{N}\s\-\_\.\,\+\#\/]/u', ' ', $query);
        $query = trim($query);
        $query = preg_replace('/\s+/u', ' ', $query);
        if (utf8_strlen($query) > 255) {
            $query = utf8_substr($query, 0, 255);
            $query = trim($query);
        }
        return $query;
    }

    /**
     * Extract price/budget constraints from a free-text query.
     *
     * Recognises:
     *   "до 5000"            → price_max = 5000
     *   "від 100" / "от 100" → price_min = 100
     *   "не дорожче 800"     → price_max = 800
     *   "1000-3000"          → range
     *
     * Returns the original query with the price phrase stripped, plus the
     * extracted bounds. Currency words (грн, гривень, uah, ₴, $) are eaten
     * so they don't pollute the cleaned query.
     *
     * @return array{query:string, price_min:?float, price_max:?float}
     */
    public function extractPriceFilters($query) {
        $original = (string)$query;
        $q = ' ' . $original . ' ';
        $min = null;
        $max = null;

        // Currency tails — optional after the number. Includes Cyrillic short forms.
        $cur = '(?:\s*(?:грн|гривень|гривні|uah|usd|eur|rub|руб|рублей|\$|€|₴))?';
        $num = '(\d+(?:[.,]\d+)?)';

        // Upper bound: до / not more than / max
        $reMax = '/\s(?:до|не\s+дорожче|не\s+дороже|не\s+більше|не\s+больше|max|under)\s*[:\s]*' . $num . $cur . '\s/iu';
        if (preg_match($reMax, $q, $m)) {
            $max = (float)str_replace(',', '.', $m[1]);
            $q = preg_replace($reMax, ' ', $q, 1);
        }

        // Lower bound: від / от / from / above
        $reMin = '/\s(?:від|от|from|above|не\s+менше|не\s+меньше|min)\s*[:\s]*' . $num . $cur . '\s/iu';
        if (preg_match($reMin, $q, $m)) {
            $min = (float)str_replace(',', '.', $m[1]);
            $q = preg_replace($reMin, ' ', $q, 1);
        }

        // Range "X-Y" / "X – Y" — only if neither bound set above.
        if ($min === null && $max === null && preg_match('/\s(\d+)\s*[-–—]\s*(\d+)' . $cur . '\s/iu', $q, $m)) {
            $a = (float)$m[1];
            $b = (float)$m[2];
            $min = min($a, $b);
            $max = max($a, $b);
            $q = preg_replace('/\s\d+\s*[-–—]\s*\d+' . $cur . '\s/iu', ' ', $q, 1);
        }

        // Sanity: ignore tiny/huge numbers misread as price (years, sizes etc.).
        if ($max !== null && $max < 5) { $max = null; }
        if ($min !== null && $min < 1) { $min = null; }

        $cleaned = trim(preg_replace('/\s+/u', ' ', $q));
        if ($cleaned === '') {
            // Query was JUST a price — don't strip everything, fall back to original.
            $cleaned = $original;
        }

        return [
            'query'     => $cleaned,
            'price_min' => $min,
            'price_max' => $max,
        ];
    }

    /**
     * QWERTY ↔ ЙЦУКЕН keyboard maps: Latin→Cyrillic first, then the reverse.
     * Shared by the whole-query layout variants and the per-token script repair.
     */
    private static function layoutMaps(): array {
        return [
            ['q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з',
             '[' => 'х', ']' => 'ї', 'a' => 'ф', 's' => 'і', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л',
             'l' => 'д', ';' => 'ж', '\'' => 'є', 'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
             ',' => 'б', '.' => 'ю', '/' => '.'],
            ['й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't', 'н' => 'y', 'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p',
             'х' => '[', 'ї' => ']', 'ф' => 'a', 'і' => 's', 'в' => 'd', 'а' => 'f', 'п' => 'g', 'р' => 'h', 'о' => 'j', 'л' => 'k',
             'д' => 'l', 'ж' => ';', 'є' => '\'', 'я' => 'z', 'ч' => 'x', 'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n', 'ь' => 'm',
             'б' => ',', 'ю' => '.', '.' => '/']
        ];
    }

    public function getLayoutVariants($query, $max = 2) {
        $query = $this->normalizeQuery($query);
        if ($query === '') {
            return [];
        }

        $maps = self::layoutMaps();

        $variants = [];
        foreach ($maps as $map) {
            $variant = strtr(utf8_strtolower($query), $map);
            $variant = $this->normalizeQuery($variant);
            if ($variant !== '' && $variant !== $query && !in_array($variant, $variants, true)) {
                $variants[] = $variant;
            }
            if (count($variants) >= (int)$max) {
                break;
            }
        }

        return $variants;
    }

    /**
     * Per-token script repair: candidate queries for when ONE token is in the
     * wrong script — a Latin term typed with the Cyrillic keyboard layout
     * ("камера lkz fpv", lkz = для), or an English term spelled in Cyrillic
     * ("камера для фпв", фпв = fpv). The whole-query layout / transliteration
     * variants can't fix these: they also convert the tokens that were already
     * correct ("камера fpv" → "камера азм"). Here each token in turn is swapped
     * for its layout and transliteration alternatives while the rest of the
     * query stays verbatim, so the right repair ("камера для fpv") is among the
     * candidates. The caller searches them as recovery terms (AND-matched), so a
     * candidate carrying a nonsense token matches nothing and drops out.
     * Bounded: 1–5-token queries only, at most $max candidates.
     *
     * @return string[]
     */
    public function getScriptRepairVariants($query, $max = 10): array {
        $query  = $this->normalizeQuery($query);
        $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($tokens) || count($tokens) < 1 || count($tokens) > 5) {
            return [];
        }

        $variants = [];
        foreach ($tokens as $i => $tok) {
            foreach ($this->tokenScriptAlternatives($tok) as $alt) {
                $copy     = $tokens;
                $copy[$i] = $alt;
                $cand     = implode(' ', $copy);
                if ($cand !== $query && !isset($variants[$cand])) {
                    $variants[$cand] = true;
                    if (count($variants) >= $max) {
                        return array_keys($variants);
                    }
                }
            }
        }
        return array_keys($variants);
    }

    /**
     * Script alternatives for a single token: keyboard-layout swaps (both
     * directions) and phonetic transliteration (both directions). Digit-only or
     * single-character tokens yield nothing; the token itself is never returned.
     *
     * @return string[]
     */
    private function tokenScriptAlternatives($token): array {
        $lower = utf8_strtolower((string)$token);
        if (utf8_strlen($lower) < 2 || preg_match('/^[\p{N}_\-\.]+$/u', $lower)) {
            return [];
        }

        $alts = [];
        foreach (self::layoutMaps() as $map) {
            $swapped = strtr($lower, $map);
            if ($swapped !== $lower && $swapped !== '') {
                $alts[$swapped] = true;
            }
        }

        if ($this->transliterator === null) {
            $this->transliterator = new Transliterator();
        }
        foreach ([$this->transliterator->cyrillicToLatin($token),
                  $this->transliterator->latinToCyrillic($token)] as $t) {
            $t = utf8_strtolower(trim((string)$t));
            if ($t !== '' && $t !== $lower) {
                $alts[$t] = true;
            }
        }

        unset($alts[$lower]);
        return array_keys($alts);
    }

    public function getCrossLangVariants($query) {
        $query = $this->normalizeQuery($query);
        if ($query === '') {
            return [];
        }

        $rule = $this->_applyStoredQueryRuleSimple($query);
        return !empty($rule['expanded_terms']) ? (array)$rule['expanded_terms'] : [];
    }

    private function _applyStoredQueryRuleSimple($query) {
        $row = $this->db->query(
            "SELECT expanded_json FROM `" . DB_PREFIX . "asp_query_rule`
             WHERE query_normalized = '" . $this->db->escape($query) . "'
             LIMIT 1"
        )->row;

        if (!$row || empty($row['expanded_json'])) {
            return ['expanded_terms' => []];
        }

        $json = json_decode((string)$row['expanded_json'], true);
        return ['expanded_terms' => is_array($json) ? $json : []];
    }

    public function getSynonymTerms($query) {
        $query = $this->normalizeQuery($query);
        if ($query === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $query);
        $words = array_filter(array_map('trim', $words));
        if (!$words) {
            return [];
        }

        $escaped = [];
        foreach ($words as $word) {
            $escaped[] = "'" . $this->db->escape(utf8_strtolower($word)) . "'";
        }

        $group_query = $this->db->query(
            "SELECT DISTINCT group_id FROM `" . DB_PREFIX . "asp_synonym` WHERE LOWER(term) IN (" . implode(',', $escaped) . ")"
        );

        if (!$group_query->rows) {
            return [];
        }

        $group_ids = [];
        foreach ($group_query->rows as $row) {
            $group_ids[] = (int)$row['group_id'];
        }

        $terms_query = $this->db->query(
            "SELECT term FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id IN (" . implode(',', $group_ids) . ")"
        );

        $terms = [];
        foreach ($terms_query->rows as $row) {
            $term = trim($row['term']);
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Build full-query variants by substituting each synonym-mapped word
     * IN PLACE. For "білі кросівки найк" with a {найк, nike, Nike} group this
     * yields "білі кросівки nike" / "білі кросівки Nike" — keeping the rest of
     * the phrase intact, so the engine still matches the descriptive words.
     * Whole-group expansion (getSynonymTerms) is meant for single-word queries.
     *
     * @return string[]
     */
    public function getSynonymWordVariants($query, $maxVariants = 16) {
        $query = $this->normalizeQuery($query);
        $words = array_values(array_filter(array_map('trim', preg_split('/\s+/u', $query))));
        if (count($words) < 2) {
            return [];
        }

        // Whole-phrase synonym first: a multi-word brand often indexes as ONE
        // token ("EcoFlow" → ecoflow), so a spaced query "еко фло" / "eco flow"
        // must map to the joined form. If the FULL query is itself a synonym term,
        // pull its group's other terms — a per-word substitution can't reach a
        // single-token brand from two query words.
        $phraseVariants = $this->getWholePhraseSynonyms($query);

        $lowerWords = array_map(function ($w) { return utf8_strtolower($w); }, $words);
        $escaped = [];
        foreach (array_unique($lowerWords) as $w) {
            $escaped[] = "'" . $this->db->escape($w) . "'";
        }

        $wordGroups = [];
        $groupIds = [];
        $rows = $this->db->query(
            "SELECT LOWER(term) AS t, group_id FROM `" . DB_PREFIX . "asp_synonym` WHERE LOWER(term) IN (" . implode(',', $escaped) . ")"
        );
        foreach ($rows->rows as $row) {
            $wordGroups[$row['t']][] = (int)$row['group_id'];
            $groupIds[(int)$row['group_id']] = true;
        }
        if (!$groupIds) {
            // No per-word synonym, but the whole spaced query may still map to a
            // single-token brand ("еко фло" → ecoflow). Don't discard that.
            return $phraseVariants;
        }

        $groupTerms = [];
        $termRows = $this->db->query(
            "SELECT group_id, term FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id IN (" . implode(',', array_keys($groupIds)) . ") ORDER BY synonym_id ASC"
        );
        foreach ($termRows->rows as $row) {
            $term = trim((string)$row['term']);
            if ($term !== '') {
                $groupTerms[(int)$row['group_id']][] = $term;
            }
        }

        // Per-word replacement options: concise synonyms first (group authoring
        // order — list the canonical/index term first), the original word last.
        $options = [];
        $hasSyn = false;
        foreach ($words as $i => $word) {
            $opts = [];
            $lw = $lowerWords[$i];
            foreach ($wordGroups[$lw] ?? [] as $gid) {
                foreach ($groupTerms[$gid] ?? [] as $t) {
                    // 1-2 word entries only — longer ones are query-expansion
                    // phrases, not interchangeable words.
                    if (utf8_strtolower($t) !== $lw
                        && count(preg_split('/\s+/u', trim($t))) <= 2
                        && !in_array($t, $opts, true)) {
                        $opts[] = $t;
                    }
                }
            }
            if ($opts) {
                $hasSyn = true;
            }
            $opts[] = $word; // original last
            $options[$i] = $opts;
        }
        if (!$hasSyn) {
            return $phraseVariants;
        }

        // Capped cross-product. Synonyms lead every list, so the first combo
        // substitutes every word at once — a full translation of the phrase
        // (e.g. "женские кроссовки найк" → "жіночі кросівки nike"). Single-word
        // swaps follow for partially-foreign queries.
        $combos = [[]];
        foreach ($options as $opts) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($opts as $opt) {
                    $next[] = array_merge($combo, [$opt]);
                }
            }
            $combos = array_slice($next, 0, $maxVariants * 3);
        }

        $variants = [];
        foreach ($combos as $combo) {
            $variant = implode(' ', $combo);
            if ($variant !== $query && !in_array($variant, $variants, true)) {
                $variants[] = $variant;
            }
            if (count($variants) >= $maxVariants) {
                break;
            }
        }

        return array_values(array_unique(array_merge($phraseVariants, $variants)));
    }

    /**
     * Whole-query synonym expansion: when the entire (multi-word) query is itself
     * a synonym term, return the OTHER terms of its group(s). Lets a spaced brand
     * query ("еко фло", "eco flow") resolve to the single-token indexed form
     * ("ecoflow") that per-word substitution can't reach.
     *
     * @return string[]
     */
    private function getWholePhraseSynonyms($query) {
        $q = utf8_strtolower(trim((string)$query));
        if ($q === '') {
            return [];
        }
        $groups = $this->db->query(
            "SELECT DISTINCT group_id FROM `" . DB_PREFIX . "asp_synonym` WHERE LOWER(term) = '" . $this->db->escape($q) . "'"
        );
        if (!$groups->rows) {
            return [];
        }
        $gids = [];
        foreach ($groups->rows as $row) { $gids[] = (int)$row['group_id']; }
        $rows = $this->db->query(
            "SELECT term FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id IN (" . implode(',', $gids) . ")"
        )->rows;
        $out = [];
        foreach ($rows as $row) {
            $t = trim((string)$row['term']);
            if ($t !== '' && utf8_strtolower($t) !== $q) { $out[] = $t; }
        }
        return array_values(array_unique($out));
    }

    /**
     * Return stem variants for the query words using DictionaryService (DB
     * override) first, then the algorithmic Stemmer.
     * Only returns stems that differ from the input words.
     * Operates on native mode only — call site must guard with mode check.
     *
     * @return string[] e.g. ['корм'] for query 'кормами'
     */
    public function getStemVariants(string $query): array {
        $query = $this->normalizeQuery($query);
        if ($query === '') {
            return [];
        }

        if (!class_exists('\\OcKit\\AdvancedSearchPro\\Libs\\Stemmer')) {
            require_once __DIR__ . '/Stemmer.php';
        }
        if (!class_exists('\\OcKit\\AdvancedSearchPro\\Libs\\DictionaryService')) {
            require_once __DIR__ . '/DictionaryService.php';
        }

        $stemmer = new Stemmer();
        $dict    = new DictionaryService($this->db);

        $words   = preg_split('/\s+/u', mb_strtolower($query, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        $stems   = [];

        foreach ($words as $word) {
            // 1) DB dictionary override
            $dictStem = $dict->lookup($word);
            if ($dictStem !== null && $dictStem !== $word) {
                $stems[] = $dictStem;
                continue;
            }
            // 2) Algorithmic stemmer
            $algStem = $stemmer->stem($word);
            if ($algStem !== $word && mb_strlen($algStem, 'UTF-8') >= 3) {
                $stems[] = $algStem;
            }
        }

        // If multi-word query: also build stemmed version of the whole phrase
        if (count($words) > 1) {
            $stemmedPhrase = implode(' ', array_map(function($w) use ($stemmer, $dict) {
                $ds = $dict->lookup($w);
                if ($ds !== null) return $ds;
                $as = $stemmer->stem($w);
                return ($as !== $w) ? $as : $w;
            }, $words));

            if ($stemmedPhrase !== $query) {
                $stems[] = $stemmedPhrase;
            }
        }

        return array_values(array_unique(array_filter($stems)));
    }
}
