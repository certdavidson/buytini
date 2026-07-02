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
 * Rewrites OC internal URLs to SEO-friendly URLs.
 *
 * Implements the rewrite() counterpart of seo_url.php:
 *   - Uses CacheWarmer for O(1) query→keyword lookups
 *   - Supports configurable category URL depth (0=flat, 1=one level, 2=two, -1=full)
 *   - Adds language prefix via LanguagePrefixConfig
 *   - Appends trailing slash or page postfix per config
 *
 * URL depth config key: module_oc_kit_seo_core_url_depth
 */
class UrlGenerator
{
    /** @var CacheWarmer */
    private $cache;
    /** @var LanguagePrefixConfig */
    private $langConfig;
    private $config;
    private $db;
    /** @var CustomRoutesConfig|null */
    private $routesConfig;
    public function __construct(
        CacheWarmer $cache,
        LanguagePrefixConfig $langConfig,
        $config,
        $db = null,
        ?CustomRoutesConfig $routesConfig = null
    ) {
        $this->cache = $cache;
        $this->langConfig = $langConfig;
        $this->config = $config;
        $this->db = $db;
        $this->routesConfig = $routesConfig;
    }

    /**
     * Rewrite an OC internal link (`https://host/index.php?route=...`) to a
     * SEO URL. Used by the OC url->link() rewrite chain.
     */
    public function rewrite(string $link, int $storeId, int $languageId): ?string
    {
        $urlInfo = parse_url(str_replace('&amp;', '&', $link));
        if (empty($urlInfo['query'])) return null;

        parse_str($urlInfo['query'], $data);
        $route = (string)($data['route'] ?? '');
        unset($data['route']);

        $scheme = $urlInfo['scheme'] ?? 'https';
        $host   = $urlInfo['host']   ?? '';
        $port   = isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '';
        $base   = $scheme . '://' . $host . $port;

        $seoUrl = $this->buildSeoUrl($route, $data, $storeId, $languageId);
        return $seoUrl !== null ? $base . $seoUrl : null;
    }

    /**
     * Build a SEO URL path (with leading "/", with language prefix and any
     * leftover query) for the given route + params, in the target language.
     * Caller decides whether to prepend scheme://host.
     *
     * @param  array<string,mixed> $params
     */
    public function buildSeoUrl(string $route, array $params, int $storeId, int $languageId): ?string
    {
        $url       = null;
        $isCat     = false;
        $isProduct = false;

        // Admin-configured skip routes — never rewrite these, keep OC's
        // native `index.php?route=...` link untouched. Exception: a route that
        // also has a matching custom entity-route mapping still gets rewritten
        // (explicit entity mapping wins over a broader skip pattern).
        if ($this->routesConfig
            && $this->routesConfig->shouldSkip($route)
            && $this->matchEntityRoute($route, $params) === null) {
            return null;
        }

        // Home — gated by `home_redirect_index` (single toggle that both
        // generates clean home URLs and 301-redirects manual /index.php?route=common/home).
        // When ON (default): emit "/" or "/<prefix>/"; when OFF: leave OC default.
        if ($route === 'common/home') {
            if ((string)$this->config->get('module_oc_kit_seo_core_home_redirect_index') === '0') {
                return null;
            }
            $prefix = $this->langConfig->getPrefixById($languageId);
            $base   = $prefix !== '' ? '/' . $prefix . '/' : '/';
            $queryStr = $params ? '?' . str_replace('&', '&amp;', ltrim(http_build_query($params), '&')) : '';
            return $base . $queryStr;
        }

        if ($route === 'product/product' && isset($params['product_id'])) {
            $url = $this->resolveProductUrl($params, $storeId, $languageId);
            if ($url !== null) unset($params['product_id']);
            $isProduct = true;

        } elseif ($route === 'product/category' && isset($params['path'])) {
            $url = $this->resolveCategoryUrl((string)$params['path'], $storeId, $languageId);
            $isCat = true;
            unset($params['path']);

        } elseif (($route === 'product/manufacturer/info' || $route === 'product/product') && isset($params['manufacturer_id'])) {
            $kw = $this->cache->keywordByQuery('manufacturer_id=' . (int)$params['manufacturer_id'], $storeId, $languageId);
            if ($kw !== null) {
                $url = '/' . $kw;
                unset($params['manufacturer_id']);
            }

        } elseif ($route === 'information/information' && isset($params['information_id'])) {
            $kw = $this->cache->keywordByQuery('information_id=' . (int)$params['information_id'], $storeId, $languageId);
            if ($kw !== null) {
                $url = '/' . $kw;
                unset($params['information_id']);
            }
        } elseif (($entityKey = $this->matchEntityRoute($route, $params)) !== null) {
            // Admin-configured custom entity route (e.g. vendor_id → vendor/vendor/view).
            // The keyword is stored in oc_seo_url under query "{key}={value}".
            $kw = $this->cache->keywordByQuery(
                $entityKey . '=' . (int)$params[$entityKey], $storeId, $languageId
            );
            if ($kw !== null) {
                $url = '/' . $kw;
                unset($params[$entityKey]);
            }
        } else {
            $kw = $this->cache->keywordByQuery($route, $storeId, $languageId);
            if ($kw !== null) $url = '/' . $kw;
        }

        if ($url === null) return null;

        // Language prefix
        $prefix = $this->langConfig->getPrefixById($languageId);
        if ($prefix !== '') {
            $url = '/' . $prefix . $url;
        }

        // Leftover query string
        $queryStr = $params ? '?' . str_replace('&', '&amp;', ltrim(http_build_query($params), '&')) : '';

        // Trailing slash / postfix
        $postfix = (string)$this->config->get('config_page_postfix');
        if ($postfix && !$queryStr) {
            $url .= $postfix;
        } elseif ($queryStr || $this->shouldAppendTrailingSlash($isCat, $isProduct)) {
            $url .= '/';
        }

        return $this->normalizeUrl($url) . $queryStr;
    }

    /**
     * Trailing-slash policy: off / categories / products / all.
     * Legacy boolean values: ''|'0' = off, '1' = all.
     */
    private function shouldAppendTrailingSlash(bool $isCategory, bool $isProduct = false): bool
    {
        $mode = (string)$this->config->get('module_oc_kit_seo_core_trailing_slash');
        if ($mode === '' || $mode === '0' || $mode === 'off') return false;
        if ($mode === 'categories') return $isCategory;
        if ($mode === 'products')   return $isProduct;
        return true; // 'all' or legacy '1'
    }

    /**
     * If $route is registered as a custom entity route AND the matching query
     * param is present in $params, return that param key (e.g. 'vendor_id').
     * Otherwise null.
     */
    private function matchEntityRoute(string $route, array $params): ?string
    {
        if (!$this->routesConfig) return null;
        foreach ($this->routesConfig->getEntityRoutes() as $key => $mappedRoute) {
            if ($mappedRoute === $route && isset($params[$key]) && $params[$key] !== '') {
                return (string)$key;
            }
        }
        return null;
    }

    /**
     * Collapse repeated `/` in a URL path (avoids `//` when keyword is empty,
     * trailing-slash combines with existing slash, etc.).
     */
    private function normalizeUrl(string $url): string
    {
        return preg_replace('#/{2,}#', '/', $url);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function resolveProductUrl(array $data, int $storeId, int $languageId): ?string
    {
        $productId = (int)$data['product_id'];

        $kw = $this->cache->keywordByQuery('product_id=' . $productId, $storeId, $languageId);
        if ($kw === null) return null;

        // Flat product URL — setting wins over depth
        $includeCat = (bool)$this->config->get('module_oc_kit_seo_core_product_include_category');
        if (!$includeCat) {
            return '/' . $kw;
        }

        // Prepend category path (limited by depth)
        $depth = (int)($this->config->get('module_oc_kit_seo_core_url_depth') ?? 0);
        if ($depth !== 0) {
            $catPath = $this->getCategoryPathForProduct($productId, $storeId, $languageId, $depth);
            if ($catPath !== '') {
                return $catPath . '/' . $kw;
            }
        }

        return '/' . $kw;
    }

    private function resolveCategoryUrl(string $path, int $storeId, int $languageId): ?string
    {
        $categoryIds = explode('_', $path);
        $depth       = (int)($this->config->get('module_oc_kit_seo_core_url_depth') ?? 0);

        if ($depth === 0) {
            // Flat: only leaf
            $lastId = end($categoryIds);
            $kw = $this->cache->keywordByQuery('category_id=' . (int)$lastId, $storeId, $languageId);
            return $kw !== null ? '/' . $kw : null;
        }

        if ($depth === -1) {
            // Full hierarchy from root: expand path to include any missing ancestors,
            // then walk root → leaf and resolve each keyword.
            $expanded = $this->expandToRoot($categoryIds);
            $segments = [];
            foreach ($expanded as $catId) {
                $kw = $this->cache->keywordByQuery('category_id=' . (int)$catId, $storeId, $languageId);
                if ($kw === null) return null;
                $segments[] = $kw;
            }
            return '/' . implode('/', $segments);
        }

        // Depth N means "N parents + leaf" — take last (N+1) ids
        $take     = min($depth + 1, count($categoryIds));
        $selected = array_slice($categoryIds, -$take);
        $segments = [];
        foreach ($selected as $catId) {
            $kw = $this->cache->keywordByQuery('category_id=' . (int)$catId, $storeId, $languageId);
            if ($kw === null) return null;
            $segments[] = $kw;
        }

        return '/' . implode('/', $segments);
    }

    /**
     * Walk up parent_id chain from the FIRST id in the given list to root,
     * prepend missing ancestors, and return the full id chain root → leaf.
     */
    private function expandToRoot(array $ids): array
    {
        if (!$ids || !$this->db) return $ids;

        $head = (int)$ids[0];
        if ($head <= 0) return $ids;

        $prepend = [];
        $cur     = $head;
        $guard   = 0;
        while ($guard++ < 10) {
            $row = $this->db->query(
                "SELECT `parent_id` FROM `" . DB_PREFIX . "category`
                 WHERE `category_id` = {$cur} LIMIT 1"
            )->row;
            if (empty($row) || (int)$row['parent_id'] === 0) break;
            $cur = (int)$row['parent_id'];
            array_unshift($prepend, $cur);
        }

        return array_merge($prepend, array_map('intval', $ids));
    }

    private function getCategoryPathForProduct(int $productId, int $storeId, int $languageId, int $depth): string
    {
        // Get primary category (main_category=1 first, then first)
        $result = $this->db->query(
            "SELECT `category_id` FROM `" . DB_PREFIX . "product_to_category`
             WHERE `product_id` = " . $productId . "
             ORDER BY `main_category` DESC LIMIT 1"
        );
        if (!$result->num_rows) return '';

        $catId    = (int)$result->row['category_id'];
        $fullPath = $this->buildCategoryAncestorIds($catId);
        if (empty($fullPath)) return '';

        // For product URL: depth N = "N ancestor categories" before the slug
        if ($depth === -1) {
            $selected = $fullPath;
        } else {
            $selected = array_slice($fullPath, -$depth);
        }

        $segments = [];
        foreach ($selected as $id) {
            $kw = $this->cache->keywordByQuery('category_id=' . $id, $storeId, $languageId);
            if ($kw === null) return '';
            $segments[] = $kw;
        }

        return '/' . implode('/', $segments);
    }

    /**
     * Walk up the category tree, return ordered ancestor id array (root first).
     */
    private function buildCategoryAncestorIds(int $categoryId): array
    {
        $path     = [];
        $currentId = $categoryId;
        $visited  = [];

        while ($currentId > 0 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $path[]  = $currentId;

            $result = $this->db->query(
                "SELECT `parent_id` FROM `" . DB_PREFIX . "category`
                 WHERE `category_id` = " . $currentId . " LIMIT 1"
            );

            if (!$result->num_rows) break;
            $currentId = (int)$result->row['parent_id'];
        }

        return array_reverse($path);
    }

    // ─── Mass generation (TZ §3.5) ───────────────────────────────────────────

    /**
     * Translate Cyrillic / Latin-with-diacritics text into ASCII per the
     * authoritative tables from TZ §3.5. `auto` picks UA / RU table by which
     * has more matching characters in the input.
     */
    public function transliterate(string $text, string $lang = 'auto'): string
    {
        // TZ §3.5 — Ukrainian table
        static $ua = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie',
            'ж'=>'zh','з'=>'z','и'=>'y','і'=>'i','ї'=>'yi','й'=>'i','к'=>'k','л'=>'l',
            'м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
            'ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'',
            'ю'=>'iu','я'=>'ia',
        ];
        // TZ §3.5 — Russian table
        static $ru = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
            'з'=>'z','и'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
            'ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e',
            'ю'=>'yu','я'=>'ya',
        ];

        if ($lang === 'auto') {
            $lower = mb_strtolower($text, 'UTF-8');
            // Distinctive UA-only chars
            $uaScore = preg_match_all('/[іїєґ]/u', $lower);
            $ruScore = preg_match_all('/[ыэъё]/u', $lower);
            $lang = ($uaScore >= $ruScore) ? 'uk' : 'ru';
        }
        $table = $lang === 'ru' ? $ru : $ua;

        // Apply uppercase variants too
        $tr = $table;
        foreach ($table as $k => $v) {
            $tr[mb_strtoupper($k, 'UTF-8')] = $v === '' ? '' : ucfirst($v);
        }
        return strtr($text, $tr);
    }

    /**
     * Convert any text to a URL-safe slug: transliterate, lowercase, collapse
     * non-alphanumeric runs to a single dash.
     */
    public function slug(string $text, string $lang = 'auto'): string
    {
        $text = $this->transliterate($text, $lang);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim((string)$text, '-');
    }

    /**
     * Make $slug unique within (store_id, language_id) by appending -2, -3…
     * if a different entity already owns the same keyword. Returns the
     * resulting unique slug.
     */
    public function ensureUnique(
        string $slug,
        string $entityType,
        int $entityId,
        int $storeId = 0,
        int $languageId = 0
    ): string {
        if ($slug === '' || $this->db === null) return $slug;

        $query = $this->entityQuery($entityType, $entityId);
        $candidate = $slug;
        $i = 1;
        while ($this->keywordTakenByOther($candidate, $query, $storeId, $languageId)) {
            $i++;
            $candidate = $slug . '-' . $i;
            if ($i > 99) break;
        }
        return $candidate;
    }

    private function keywordTakenByOther(string $kw, string $ownQuery, int $storeId, int $languageId): bool
    {
        $kwEsc = $this->db->escape($kw);
        $sql = "SELECT `query` FROM `" . DB_PREFIX . "seo_url`
                WHERE `keyword` = '" . $kwEsc . "'
                  AND `store_id` = " . (int)$storeId . "
                  AND `language_id` = " . (int)$languageId . "
                LIMIT 1";
        $r = $this->db->query($sql);
        if (!$r->num_rows) return false;
        return (string)$r->row['query'] !== $ownQuery;
    }

    private function entityQuery(string $type, int $id): string
    {
        switch ($type) {
            case 'product':       return 'product_id='       . (int)$id;
            case 'category':      return 'category_id='      . (int)$id;
            case 'manufacturer':  return 'manufacturer_id='  . (int)$id;
            case 'information':   return 'information_id='   . (int)$id;
            default:              return $type . '_id=' . (int)$id;
        }
    }

    /**
     * Generate (or refresh) a SEO URL row for one entity. Reads the entity's
     * name from its description table for $languageId, runs it through the
     * configured mask + ensureUnique, and INSERTs / UPDATEs `oc_seo_url`.
     *
     * Returns the resulting keyword (or '' if nothing could be generated).
     */
    public function generateForEntity(
        string $entityType,
        int $entityId,
        int $languageId,
        int $storeId = 0,
        ?UrlMaskEngine $masks = null
    ): string {
        if ($this->db === null) return '';

        $name = $this->fetchEntityName($entityType, $entityId, $languageId);
        if ($name === '') return '';

        $masks = $masks ?: new UrlMaskEngine($this->config);
        $rawKeyword = $masks->generate($entityType, ['name' => $name, 'id' => $entityId]);
        if ($rawKeyword === '') return '';

        $keyword = $this->ensureUnique($rawKeyword, $entityType, $entityId, $storeId, $languageId);
        $query   = $this->entityQuery($entityType, $entityId);

        // Track previous keyword to feed RedirectManager::autoCapture later
        $prev = $this->db->query(
            "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
             WHERE `query` = '" . $this->db->escape($query) . "'
               AND `store_id` = " . (int)$storeId . "
               AND `language_id` = " . (int)$languageId . " LIMIT 1"
        );
        $oldKw = $prev->num_rows ? (string)$prev->row['keyword'] : '';

        if ($oldKw !== '') {
            if ($oldKw === $keyword) return $keyword;
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "seo_url`
                 SET `keyword` = '" . $this->db->escape($keyword) . "'
                 WHERE `query` = '" . $this->db->escape($query) . "'
                   AND `store_id` = " . (int)$storeId . "
                   AND `language_id` = " . (int)$languageId
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "seo_url`
                 (`store_id`, `language_id`, `query`, `keyword`)
                 VALUES (" . (int)$storeId . ", " . (int)$languageId . ",
                         '" . $this->db->escape($query) . "',
                         '" . $this->db->escape($keyword) . "')"
            );
        }
        return $keyword;
    }

    /**
     * Bulk-generate SEO URLs for many entities of the same type.
     * Returns ['ok' => N, 'skipped' => M, 'old_keywords' => [...]]
     */
    public function generateBulk(
        string $entityType,
        array $entityIds,
        int $languageId,
        int $storeId = 0,
        ?UrlMaskEngine $masks = null
    ): array {
        $masks = $masks ?: new UrlMaskEngine($this->config);
        $ok = 0; $skipped = 0; $changes = [];
        foreach ($entityIds as $id) {
            $kw = $this->generateForEntity($entityType, (int)$id, $languageId, $storeId, $masks);
            if ($kw === '') { $skipped++; continue; }
            $ok++;
        }
        return ['ok' => $ok, 'skipped' => $skipped];
    }

    private function fetchEntityName(string $type, int $id, int $languageId): string
    {
        if ($this->db === null) return '';
        switch ($type) {
            case 'product':
                $r = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "product_description`
                    WHERE `product_id` = " . (int)$id . " AND `language_id` = " . (int)$languageId . " LIMIT 1");
                break;
            case 'category':
                $r = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "category_description`
                    WHERE `category_id` = " . (int)$id . " AND `language_id` = " . (int)$languageId . " LIMIT 1");
                break;
            case 'manufacturer':
                // OC stores manufacturer name in oc_manufacturer (not language-keyed)
                $r = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer`
                    WHERE `manufacturer_id` = " . (int)$id . " LIMIT 1");
                break;
            case 'information':
                $r = $this->db->query("SELECT `title` AS name FROM `" . DB_PREFIX . "information_description`
                    WHERE `information_id` = " . (int)$id . " AND `language_id` = " . (int)$languageId . " LIMIT 1");
                break;
            default:
                return '';
        }
        return $r->num_rows ? (string)$r->row['name'] : '';
    }
}
