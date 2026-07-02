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
use OcKit\AdvancedSearchPro\ManticoreClient;

class ManticoreSearchEngine implements SearchEngineInterface {
    private $client;
    private $index;
    private $settings;

    // Mapping: our search_fields keys → Manticore RT index field names
    // Only rt_field columns are fulltext-searchable via MATCH()
    private static $fieldWeightMap = [
        'title'        => 'name',
        'description'  => 'description',
        'tags'         => 'tag',
        'manufacturer' => 'manufacturer',
        'model'        => 'model',
        'sku'          => 'sku',
    ];

    public function __construct($client, $index = 'products', array $settings = []) {
        $this->client   = $client;
        $this->index    = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$index);
        if ($this->index === '') {
            $this->index = 'products';
        }
        $this->settings = $settings;
    }

    // Typo correction (QSUGGEST) bounds.
    const CORRECT_MAX_DISTANCE = 2;     // max edit distance to accept a suggestion
    const CORRECT_MIN_WORD_LEN = 4;     // don't try to correct very short words
    const CORRECT_MIN_SIM      = 0.4;   // min trigram overlap to trust a correction

    // Search-as-you-type prefix pass — minimum length of the trailing token
    // before it is matched as a prefix wildcard ("...ант" → "...ант*"). Set to
    // the index min_infix_len so the wildcard expands to real catalog words.
    const PREFIX_MIN_LEN = 2;

    // Quorum fallback for long, over-specified queries (a pasted product name
    // listing more spec tokens than the catalog title actually holds). Only
    // queries with at least QUORUM_MIN_WORDS content words quorum, requiring
    // QUORUM_RATIO of them (but never fewer than QUORUM_MIN_WORDS-1) — high
    // enough to stay precise on rare tokens, while short queries keep the strict
    // AND / noun relaxation (a low quorum on a short query floods).
    const QUORUM_MIN_WORDS = 5;
    const QUORUM_RATIO     = 0.6;

    // Last QSUGGEST-corrected query (for the controller's "did you mean?" UI).
    private $lastCorrection = null;
    // AND-pass total of the most recent lexicalSearch() call.
    private $lastAndTotal = 0;
    // Catalog adjective set (form => true) for POS-aware relaxation; lazy-loaded.
    private static $adjectives = null;

    public function search($query, $limit, $offset = 0, $relax = true) {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $this->lastCorrection = null;

        // Exact code lookup (C11) — model/sku are string attributes, NOT in the
        // fulltext index, so MATCH() never finds a product by its code. A
        // code-like query ("1115333", "rituals-es-1115333") is matched directly
        // against the attributes and, when found, wins outright. Falls through
        // to normal search when the code is unknown.
        $codeResult = $this->searchExactCode((string)$query, $limit, $offset);
        if ($codeResult !== null) {
            return $codeResult;
        }

        $words = $this->extractWords((string)$query);
        if (!$words) {
            return ['ids' => [], 'total' => 0];
        }

        // Drop function-word stopwords (prepositions/conjunctions) from the
        // matched terms so they don't dilute AND/quorum. Without this, "крем для
        // тіла" quorum (2-of-3) let clothing in via the common "для"+"тіла";
        // dropping "для" leaves {крем, тіла}, both required → only body products.
        // Kept only if the whole query is stopwords (don't blank the search).
        $words = $this->dropStopwords($words);

        // Restrict MATCH to the fulltext fields the admin left enabled (C3).
        // A disabled field must not contribute matches, not merely lose weight.
        $fieldPrefix = $this->buildFieldPrefix();

        $result = $this->lexicalSearch($words, $fieldPrefix, $limit, $offset, $relax);

        // Typo correction fires when the AND pass matched NOTHING — that means a
        // query word is misspelled (no doc contains all words together). We
        // replace words that exist in no document with their closest dictionary
        // word (QSUGGEST) and re-search. The corrected result is PREFERRED even
        // when smaller: "шраб для тіла" (40 generic body items via quorum on
        // "для тіла") → "скраб для тіла" (4 actual scrubs) matches intent far
        // better. Only the distinctive misspelled token is changed. Correction
        // (and the fuzzy net below) run ONLY for the original query ($relax) —
        // synonym/expansion terms are matched strictly, so a rare synonym word
        // that matches nothing ("духи", "одеколон") is never "corrected" into a
        // common homonym that floods the list.
        if ($relax && $this->lastAndTotal === 0) {
            $corrected = $this->correctWords($words, $fieldPrefix);
            if ($corrected !== null && $corrected !== $words) {
                $cResult = $this->lexicalSearch($corrected, $fieldPrefix, $limit, $offset, $relax);
                if (!empty($cResult['ids'])) {
                    $result = $cResult;
                    $this->lastCorrection = implode(' ', $corrected);
                }
            }
        }

        // Last resort — raw fuzzy HTTP, but ONLY when a query word is unknown
        // (a genuine misspelling). Manticore fuzzy match is OR-semantic and
        // distance-2 broad, so on a query whose words are all real catalog words
        // that simply don't co-occur — "парфуми чоловічі" when no men's perfume
        // exists — it would flood the list with the most common word's matches
        // ("чоловічі" → 2390 products). A precise empty result ("nothing found")
        // is correct there; fuzzy is for typos, not for absent combinations.
        if ($relax && empty($result['ids']) && !empty($this->settings['fuzzy'])
            && $this->hasUnknownWord($words, $fieldPrefix)
        ) {
            return $this->searchFuzzyHttp((string)$query, $limit, $offset);
        }

        return $result;
    }

    /**
     * True when at least one content word matches no document in the enabled
     * fields — i.e. a likely misspelling that warrants the fuzzy fallback. Words
     * shorter than the correction threshold are ignored (too noisy to fuzz).
     */
    private function hasUnknownWord(array $words, $fieldPrefix) {
        foreach ($words as $w) {
            $len = function_exists('mb_strlen') ? mb_strlen($w, 'UTF-8') : strlen($w);
            if ($len < self::CORRECT_MIN_WORD_LEN) {
                continue;
            }
            if ((int)$this->runMatch($fieldPrefix . $w, 1, 0)['total'] === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * AND-first lexical search with POS-aware relaxation. The full AND is tried
     * first (most precise). When it finds nothing AND $relax is on, attribute
     * adjectives are dropped and only the product-type NOUNS are re-searched —
     * so "парфуми чоловічі" (no men's perfume) relaxes to "парфуми", never to
     * the common modifier "чоловічі". If even the nouns match nothing, the empty
     * result stands: show nothing, not common-word noise (precision over recall).
     * This replaces the old count-based quorum, which kept the common word and
     * flooded. Records the AND-pass total in $lastAndTotal.
     */
    private function lexicalSearch(array $words, $fieldPrefix, $limit, $offset, $relax = true) {
        $andResult = $this->runMatch($fieldPrefix . $this->joinAnd($words), $limit, $offset);
        $this->lastAndTotal = (int)$andResult['total'];
        if ($andResult['total'] > 0 || !$relax) {
            return $andResult;
        }

        // Prefix pass (search-as-you-type) — the trailing token is usually the
        // word the shopper is still typing ("виносна ант" → "виносна ант*"). The
        // index carries min_infix_len, so a prefix wildcard on the last token
        // resolves it to real words ("антена"). Runs only after the exact AND
        // found nothing, so complete queries keep full morphology; a hit here
        // suppresses typo-correction (lastAndTotal > 0) — a real prefix match
        // beats a "did you mean".
        $prefixed = $this->prefixLastToken($words);
        if ($prefixed !== null) {
            $pResult = $this->runMatch($fieldPrefix . $this->joinAnd($prefixed), $limit, $offset);
            if ($pResult['total'] > 0) {
                $this->lastAndTotal = (int)$pResult['total'];
                return $pResult;
            }
        }

        $nouns = [];
        foreach ($words as $w) {
            if (!$this->isAdjective($w)) { $nouns[] = $w; }
        }
        if (count($nouns) > 0 && count($nouns) < count($words)) {
            $relaxed = $this->runMatch($fieldPrefix . $this->joinAnd($nouns), $limit, $offset);
            if ($relaxed['total'] > 0) {
                return $relaxed;
            }
        }

        // Quorum (last resort, LONG queries only). A long, very specific query
        // ("Motorola R7 VHF FKP BT WiFI GNSS PREMIUM") usually lists more spec
        // tokens than the product's actual title carries, so the full AND matches
        // nothing. Requiring a high fraction of these (already rare, content-word)
        // terms still pins the right product without the common-word flooding a
        // low quorum on a SHORT query would cause — hence the QUORUM_MIN_WORDS
        // gate; short queries fall through to the empty result instead.
        $wordCount = count($words);
        if ($wordCount >= self::QUORUM_MIN_WORDS) {
            $k = max(self::QUORUM_MIN_WORDS - 1, (int)ceil($wordCount * self::QUORUM_RATIO));
            $quorum = $this->runMatch($fieldPrefix . '"' . $this->joinAnd($words) . '"/' . $k, $limit, $offset);
            if ($quorum['total'] > 0) {
                $this->lastAndTotal = (int)$quorum['total']; // a quorum hit beats "did you mean"
                return $quorum;
            }
        }

        return $andResult;
    }

    /**
     * Catalog adjective lookup for POS-aware relaxation. The set is generated
     * offline from the catalog vocabulary with pymorphy2 (top parse = ADJF/ADJS/
     * PRTF) and stored in data/adjectives.txt. Unknown words default to "not an
     * adjective" (kept), so relaxation only ever drops confirmed attributes.
     */
    private function isAdjective($word) {
        if (self::$adjectives === null) {
            self::$adjectives = [];
            $file = __DIR__ . '/../data/adjectives.txt';
            if (is_file($file)) {
                $fh = @fopen($file, 'r');
                if ($fh) {
                    while (($line = fgets($fh)) !== false) {
                        $w = trim($line);
                        if ($w !== '') { self::$adjectives[$w] = true; }
                    }
                    fclose($fh);
                }
            }
        }
        $w = function_exists('mb_strtolower') ? mb_strtolower((string)$word, 'UTF-8') : strtolower((string)$word);
        return isset(self::$adjectives[$w]);
    }

    /**
     * Per-word spelling correction via QSUGGEST. Words that already match a
     * document are kept; words that match nothing are replaced with a dictionary
     * candidate. Candidate choice is CONTEXT-AWARE for multi-word queries: among
     * the QSUGGEST candidates, the one that co-occurs most with the other query
     * words wins (so "шраб для тіла" → "скраб", not the more frequent but
     * unrelated "гра"). Single-word queries fall back to QSUGGEST's own ranking
     * (distance, then doc frequency) — there is no context to disambiguate.
     *
     * @return string[]|null  corrected words, or null if nothing changed
     */
    private function correctWords(array $words, $fieldPrefix) {
        $corrected = $words;
        $changed   = false;

        foreach ($words as $i => $w) {
            $len = function_exists('mb_strlen') ? mb_strlen($w, 'UTF-8') : strlen($w);
            if ($len < self::CORRECT_MIN_WORD_LEN) {
                continue;
            }
            // Known word (matches a doc in the enabled fields) → keep.
            if ($this->runMatch($fieldPrefix . $w, 1, 0)['total'] > 0) {
                continue;
            }
            $pick = $this->pickSuggestion($w);
            if ($pick !== null && $pick !== $w) {
                $corrected[$i] = $pick;
                $changed = true;
            }
        }

        return $changed ? $corrected : null;
    }

    /**
     * Choose the best QSUGGEST candidate for a misspelled word. Candidates are
     * filtered to distance ≤ CORRECT_MAX_DISTANCE and docs ≥ 1, then ranked by
     * character-trigram overlap with the typo (primary) with QSUGGEST's own
     * order (distance, doc frequency) as the stable tiebreak. Trigram overlap is
     * what separates the real correction from a frequent-but-unrelated word at
     * the same edit distance: "шраб" shares the trigram "раб" with "скраб" but
     * nothing with the more common "гра".
     */
    private function pickSuggestion($word) {
        $bestPick = null;
        $bestSim  = -1.0;
        // Limit 10 (not 5): the correct low-frequency word can sit below several
        // common same-distance words (QSUGGEST ranks doc frequency high), e.g.
        // "скраб" (4 docs) trails "гра"/"раз" for the typo "шраб".
        foreach ($this->client->suggest($word, $this->index, 10) as $c) {
            if ($c['distance'] > self::CORRECT_MAX_DISTANCE || $c['docs'] < 1 || $c['suggest'] === $word) {
                continue;
            }
            $sim = $this->trigramSim($word, $c['suggest']);
            if ($sim > $bestSim) { // strict → keeps QSUGGEST order on ties
                $bestSim  = $sim;
                $bestPick = $c['suggest'];
            }
        }
        // Below the confidence floor the candidates are ambiguous (e.g. "тіал"
        // ≈ "тіло" ≈ "стал"); leave the query untouched rather than silently
        // swap in a wrong word — the lexical quorum pass still returns products.
        return $bestSim >= self::CORRECT_MIN_SIM ? $bestPick : null;
    }

    /** Shared-trigram ratio between two words: |T(a) ∩ T(b)| / |T(a)|. */
    private function trigramSim($a, $b) {
        $ta = $this->trigramsOf($a);
        if (!$ta) {
            return 0.0;
        }
        $shared = count(array_intersect($ta, $this->trigramsOf($b)));
        return $shared / count($ta);
    }

    /** Unique character trigrams of a word (space-padded, lowercased). */
    private function trigramsOf($s) {
        $s = ' ' . (function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s)) . ' ';
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $n = is_array($chars) ? count($chars) : 0;
        $out = [];
        for ($i = 0; $i + 2 < $n; $i++) {
            $out[$chars[$i] . $chars[$i + 1] . $chars[$i + 2]] = true;
        }
        return array_keys($out);
    }

    /**
     * The corrected query from the last search(), or null if no correction was
     * applied. Lets the controller render a "did you mean?" hint.
     */
    public function getLastCorrection() {
        return $this->lastCorrection;
    }

    /**
     * Exact SKU/model lookup against the string attributes. Returns a result
     * only when the query looks like a product code (single token containing a
     * digit) AND it matches a stored sku/model; otherwise null so the caller
     * continues with normal fulltext search.
     *
     * Note: string-attribute equality is case-sensitive — it covers the common
     * copy-pasted-code case. Case-insensitive / partial code matching needs
     * model/sku indexed as TEXT (separate reindex step).
     */
    private function searchExactCode($query, $limit, $offset) {
        $q = trim($query);
        // Single token, code charset only, at least one digit.
        if ($q === ''
            || !preg_match('~^[A-Za-z0-9][A-Za-z0-9._/\-]*$~', $q)
            || !preg_match('~\d~', $q)) {
            return null;
        }

        $ids  = [];
        $seen = [];

        // Code lookup in the model/sku TEXT fields FIRST. A shopper typing an
        // article number wants the SKU/model match: "2189" must surface the size
        // variants 2189-42/44/46, "2021" the 2021 SKU. The query is tokenised (a
        // "-" suffix splits into separate tokens), so a partial base code still hits
        // every variant. Runs BEFORE the id lookup so a number that happens to equal
        // an unrelated product_id can't hijack the code search.
        $words = $this->extractWords($q);
        if ($words) {
            $codeRes = $this->runMatch('@(model,sku) ' . $this->joinAnd($words), $limit, $offset);
            foreach ($codeRes['ids'] as $pid) {
                $pid = (int)$pid;
                if (!isset($seen[$pid])) { $seen[$pid] = true; $ids[] = $pid; }
            }
        }

        // Numeric → also resolve as a direct product id (Manticore's document id IS
        // the OpenCart product_id), appended after the code hits, so a bare "34981"
        // still finds product 34981 when it isn't anyone's article code.
        if (ctype_digit($q) && $q[0] !== '0' && strlen($q) <= 18) {
            try {
                $idRes = $this->client->queryWithMeta(
                    "SELECT id FROM " . $this->index . " WHERE id = " . $q . " LIMIT 1"
                );
                foreach ($idRes['rows'] as $row) {
                    $pid = (int)$row['id'];
                    if (!isset($seen[$pid])) { $seen[$pid] = true; $ids[] = $pid; }
                }
            } catch (\Throwable $e) {}
        }

        if (!$ids) {
            return null; // unknown code → fall through to the normal fulltext pass
        }
        return ['ids' => $ids, 'total' => count($ids)];
    }

    /**
     * Run one MATCH expression and return ['ids','total']. Applies field
     * weights and max_matches. total comes from SHOW META total_found (the true
     * match count, not capped by max_matches).
     */
    private function runMatch($matchExpr, $limit, $offset) {
        if ($matchExpr === '') {
            return ['ids' => [], 'total' => 0];
        }

        $optionParts  = [];
        $fieldWeights = $this->buildFieldWeights();
        if ($fieldWeights) {
            $optionParts[] = 'field_weights=(' . implode(',', $fieldWeights) . ')';
        }
        // Title-first ranking: reward the number of distinct query words present
        // in each field, scaled by the field weight, so a product carrying all
        // query terms in its NAME ("камера для FPV") outranks one that only
        // matches them across a term-heavy description (an FPV drone whose write-up
        // repeats "камера"). BM25 is the secondary tie-breaker within the same
        // title-match level. Without this, description term-frequency buries exact
        // name hits.
        $optionParts[] = "ranker=expr('1000 * sum(word_count * user_weight) + bm25')";
        $optionParts[] = 'max_matches=' . max(1, $offset + $limit);

        $sql = "SELECT id, weight() AS weight FROM " . $this->index
             . " WHERE MATCH('" . $matchExpr . "')"
             . " LIMIT " . $offset . "," . $limit
             . " OPTION " . implode(', ', $optionParts);

        try {
            $result = $this->client->queryWithMeta($sql);
        } catch (\Throwable $e) {
            return ['ids' => [], 'total' => 0];
        }

        $ids = [];
        foreach ($result['rows'] as $row) {
            $ids[] = (int)$row['id'];
        }
        $total = isset($result['meta']['total_found']) ? (int)$result['meta']['total_found']
               : (isset($result['meta']['total']) ? (int)$result['meta']['total'] : count($ids));
        return ['ids' => $ids, 'total' => $total];
    }

    private function searchFuzzyHttp($query, $limit, $offset) {
        $dist    = max(1, min(2, (int)($this->settings['fuzzy_distance'] ?? 2)));
        $layouts = isset($this->settings['fuzzy_layouts']) && is_array($this->settings['fuzzy_layouts'])
            ? array_values(array_filter(array_map('strval', $this->settings['fuzzy_layouts'])))
            : ['us', 'ua', 'ru'];

        $payload = [
            'table'   => $this->index,
            'query'   => ['match' => ['*' => (string)$query]],
            'limit'   => $limit,
            'offset'  => $offset,
            '_source' => ['excludes' => ['*']],
            'options' => [
                'fuzzy'       => true,
                'distance'    => $dist,
                'layouts'     => $layouts,
                'max_matches' => max(1, $offset + $limit),
            ],
        ];
        // Field weights — JSON form: {"name":80,"description":40,...}
        $fieldWeights = $this->buildFieldWeightsAssoc();
        if ($fieldWeights) {
            $payload['options']['field_weights'] = (object)$fieldWeights;
        }

        try {
            $resp = $this->client->searchHttp($payload);
        } catch (\Throwable $e) {
            return ['ids' => [], 'total' => 0];
        }
        if (!is_array($resp) || isset($resp['error'])) {
            return ['ids' => [], 'total' => 0];
        }

        $hits  = $resp['hits']['hits']  ?? [];
        $total = (int)($resp['hits']['total'] ?? count($hits));
        $ids   = [];
        foreach ($hits as $h) {
            if (isset($h['_id'])) {
                $ids[] = (int)$h['_id'];
            }
        }
        return ['ids' => $ids, 'total' => $total];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Build field_weights option parts from module search_fields config.
     * Only RT fulltext fields are included (attributes are not MATCH()-searchable).
     *
     * @return string[]  e.g. ['name=80', 'description=40', 'tag=15', 'manufacturer=20']
     */
    /**
     * Split a query into clean fulltext words. Strips every character that is
     * not a letter, digit or underscore — this removes Manticore extended-query
     * operators (| / " @ ~ - etc.) so user input can never break or inject into
     * the MATCH() expression, and leaves alnum tokens that are SQL-string-safe.
     *
     * @return string[]
     */
    private function extractWords($query) {
        $clean = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', (string)$query);
        $words = preg_split('/\s+/u', trim((string)$clean), -1, PREG_SPLIT_NO_EMPTY);
        return is_array($words) ? $words : [];
    }

    // Function words (UA/RU/EN) that never identify a product. Dropped from the
    // matched terms so they don't count toward AND/quorum. Lowercased.
    private static $stopwords = [
        // UA
        'для'=>1,'на'=>1,'та'=>1,'і'=>1,'й'=>1,'в'=>1,'у'=>1,'з'=>1,'із'=>1,'зі'=>1,'зо'=>1,
        'по'=>1,'до'=>1,'від'=>1,'за'=>1,'про'=>1,'при'=>1,'під'=>1,'над'=>1,'без'=>1,'як'=>1,
        // RU
        'и'=>1,'с'=>1,'со'=>1,'от'=>1,'о'=>1,'об'=>1,'под'=>1,'над'=>1,'без'=>1,'из'=>1,'к'=>1,
        // EN — only unambiguous function words. Short ones like "on" (a sneaker
        // brand), "a"/"an"/"at"/"in" are deliberately excluded so they can't
        // strip a meaningful product token.
        'for'=>1,'the'=>1,'and'=>1,'with'=>1,'of'=>1,
    ];

    /**
     * Remove function-word stopwords from the term list. If every word is a
     * stopword (e.g. the query is just "для"), the original list is returned so
     * the search isn't blanked.
     *
     * @return string[]
     */
    private function dropStopwords(array $words) {
        $kept = [];
        foreach ($words as $w) {
            $lw = function_exists('mb_strtolower') ? mb_strtolower($w, 'UTF-8') : strtolower($w);
            if (!isset(self::$stopwords[$lw])) {
                $kept[] = $w;
            }
        }
        return $kept ? $kept : $words;
    }

    /** AND join — every word must occur (Manticore default operator). */
    private function joinAnd(array $words) {
        return implode(' ', $words);
    }

    /**
     * Append a prefix wildcard to the trailing token for search-as-you-type:
     * the last word is usually the one still being typed ("...ант" → "...ант*").
     * Returns null when the tail is too short for the index min_infix_len or is
     * already wildcarded, so the prefix pass is skipped and the cascade falls
     * through to typo-correction / fuzzy unchanged. Earlier tokens stay as-is —
     * the shopper has finished typing them.
     */
    private function prefixLastToken(array $words) {
        if (!$words) {
            return null;
        }
        $i    = count($words) - 1;
        $last = $words[$i];
        $len  = function_exists('mb_strlen') ? mb_strlen($last, 'UTF-8') : strlen($last);
        if ($len < self::PREFIX_MIN_LEN || substr($last, -1) === '*') {
            return null;
        }
        $words[$i] = $last . '*';
        return $words;
    }


    /**
     * Field-restriction prefix built from the admin's enabled search fields.
     * Returns '@(name,tag,...) ' covering only enabled fulltext fields, or ''
     * when all fields are enabled (no restriction needed) or none resolved.
     */
    private function buildFieldPrefix() {
        $searchFields = isset($this->settings['search_fields']) && is_array($this->settings['search_fields'])
            ? $this->settings['search_fields']
            : [];

        $enabled = [];
        foreach (self::$fieldWeightMap as $key => $indexField) {
            // No per-field config → treat as enabled (default-on).
            $on = !$searchFields
                || !isset($searchFields[$key])
                || !array_key_exists('enabled', (array)$searchFields[$key])
                || !empty($searchFields[$key]['enabled']);
            if ($on) {
                $enabled[] = $indexField;
            }
        }

        if (empty($enabled) || count($enabled) === count(self::$fieldWeightMap)) {
            return '';
        }
        return '@(' . implode(',', $enabled) . ') ';
    }

    private function buildFieldWeightsAssoc() {
        $assoc = [];
        foreach ($this->buildFieldWeights() as $kv) {
            [$k, $v] = explode('=', $kv, 2);
            $assoc[$k] = (int)$v;
        }
        return $assoc;
    }

    private function buildFieldWeights() {
        $searchFields = isset($this->settings['search_fields']) && is_array($this->settings['search_fields'])
            ? $this->settings['search_fields']
            : [];

        $defaults = [
            'title'        => 80,
            'description'  => 40,
            'tags'         => 15,
            'manufacturer' => 20,
            'model'        => 60,
            'sku'          => 60,
        ];

        $weights = [];
        foreach (self::$fieldWeightMap as $key => $indexField) {
            // Field disabled via settings → skip (weight 0 means excluded)
            if ($searchFields && isset($searchFields[$key]) && empty($searchFields[$key]['enabled'])) {
                continue;
            }

            if (!empty($searchFields[$key]['weight'])) {
                $w = max(1, min(100, (int)$searchFields[$key]['weight']));
            } else {
                $w = $defaults[$key] ?? 50;
            }

            $weights[] = $indexField . '=' . $w;
        }

        return $weights;
    }
}
