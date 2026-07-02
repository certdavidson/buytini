<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

/**
 * Loads the full oc_seo_url table into PHP memory once per request,
 * keyed by [store_id][language_id] for O(1) lookups.
 *
 * Two indexes:
 *   $keywords[keyword][store_id][language_id] = query
 *   $queries[query][store_id][language_id]    = keyword
 *
 * Uses OC's file cache (cache/seo_core.*.cache) with a configurable TTL
 * (default 24 h) so the DB is only hit once after cache expiry or invalidation.
 */
class CacheWarmer
{
    private array $keywords = [];
    private array $queries  = [];
    private bool  $loaded   = false;

    // Bump the suffix whenever the stored structure changes to invalidate
    // stale caches automatically on next request.
    private const CACHE_KEY = 'seo_core.url_map.v2';

    private $db;
    private $cache;
    public function __construct($db, $cache) {
        $this->db = $db;
        $this->cache = $cache;
    }

    private function load(): void
    {
        if ($this->loaded) return;

        $cached = $this->cache->get(self::CACHE_KEY);
        if (is_array($cached) && isset($cached['keywords'], $cached['queries'])) {
            $this->keywords = $cached['keywords'];
            $this->queries  = $cached['queries'];
            $this->loaded   = true;
            return;
        }

        $result = $this->db->query(
            "SELECT `keyword`, `query`, `store_id`, `language_id`
             FROM `" . DB_PREFIX . "seo_url`
             ORDER BY `store_id`, `language_id`"
        );

        foreach ($result->rows as $row) {
            $kw  = $row['keyword'];
            $q   = $row['query'];
            $sid = (int)$row['store_id'];
            $lid = (int)$row['language_id'];

            // Store all candidates for a keyword — needed to disambiguate when
            // the same slug exists in multiple categories (e.g. "krosivky").
            if (!isset($this->keywords[$kw][$sid][$lid])) {
                $this->keywords[$kw][$sid][$lid] = [];
            }
            $this->keywords[$kw][$sid][$lid][] = $q;

            $this->queries[$q][$sid][$lid] = $kw;
        }

        $this->cache->set(self::CACHE_KEY, [
            'keywords' => $this->keywords,
            'queries'  => $this->queries,
        ]);

        $this->loaded = true;
    }

    /**
     * Resolve keyword → query for a given store/language.
     * Falls back to store_id=0 if not found in current store.
     */
    public function queryByKeyword(string $keyword, int $storeId, int $languageId): ?string
    {
        $candidates = $this->queryByKeywordAll($keyword, $storeId, $languageId);
        return $candidates[0] ?? null;
    }

    /**
     * Return ALL query candidates for a keyword (used to disambiguate
     * colliding slugs across different categories).
     * @return string[]
     */
    public function queryByKeywordAll(string $keyword, int $storeId, int $languageId): array
    {
        $this->load();
        $list = $this->keywords[$keyword][$storeId][$languageId]
            ?? $this->keywords[$keyword][0][$languageId]
            ?? [];
        // Legacy cache entries stored a plain string — normalise to array.
        if (is_string($list)) $list = [$list];
        return $list;
    }

    /**
     * Return all query candidates for a keyword across any language under the
     * given store. Used as a cross-language fallback when the slug exists
     * under a non-current language and we need to identify the underlying
     * entity so the canonical URL can be built and a 301 issued.
     *
     * @return string[]
     */
    public function queryByKeywordAnyLang(string $keyword, int $storeId): array
    {
        $this->load();
        $found = [];
        foreach ([$storeId, 0] as $sid) {
            if (!isset($this->keywords[$keyword][$sid])) continue;
            foreach ($this->keywords[$keyword][$sid] as $list) {
                if (is_string($list)) $list = [$list];
                foreach ($list as $q) {
                    if ($q !== null && $q !== '' && !in_array($q, $found, true)) {
                        $found[] = $q;
                    }
                }
            }
            if ($found) return $found;
        }
        return $found;
    }

    /**
     * Resolve query → keyword for a given store/language.
     * Falls back to store_id=0 if not found in current store.
     */
    public function keywordByQuery(string $query, int $storeId, int $languageId): ?string
    {
        $this->load();
        // Try requested store+language, then store=0+language, then ANY
        // language for the requested store, then any language for store=0.
        // The cross-language fallback lets pages with translations missing
        // for some languages still render at a stable URL — the slug stays
        // in the source language but the prefix follows the user choice.
        $value = $this->queries[$query][$storeId][$languageId]
            ?? $this->queries[$query][0][$languageId]
            ?? null;
        if ($value !== null) return $value;

        foreach ([$storeId, 0] as $sid) {
            if (!isset($this->queries[$query][$sid])) continue;
            foreach ($this->queries[$query][$sid] as $kw) {
                if ($kw !== null && $kw !== '') return $kw;
            }
        }
        return null;
    }

    /**
     * Invalidate the in-memory and file cache (call after any seo_url table change).
     */
    public function invalidate(): void
    {
        $this->keywords = [];
        $this->queries  = [];
        $this->loaded   = false;
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * Populate the in-memory + persistent cache from oc_seo_url.
     * Returns the number of entries cached.
     */
    public function warm(int $storeId = 0): int
    {
        $this->keywords = [];
        $this->queries  = [];
        $this->loaded   = false;
        $this->cache->delete(self::CACHE_KEY);
        $this->load();
        return count($this->queries);
    }

    public function clear(int $storeId = 0): void
    {
        $this->invalidate();
    }

    public function isWarm(int $storeId = 0): bool
    {
        $cached = $this->cache->get(self::CACHE_KEY);
        return is_array($cached) && isset($cached['keywords'], $cached['queries']) && count($cached['queries']) > 0;
    }

    public function getStats(int $storeId = 0): array
    {
        $warm    = $this->isWarm($storeId);
        $entries = 0;
        $sizeKb  = 0;

        if ($warm) {
            $cached  = $this->cache->get(self::CACHE_KEY);
            $entries = is_array($cached['queries'] ?? null) ? count($cached['queries']) : 0;
            $serial  = serialize($cached);
            $sizeKb  = (int)round(strlen($serial) / 1024);
        }

        return [
            'warm'      => $warm,
            'entries'   => $entries,
            'size_kb'   => $sizeKb,
            'warmed_at' => $warm ? date('Y-m-d H:i:s') : null,
        ];
    }
}
