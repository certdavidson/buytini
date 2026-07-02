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
 * Decodes incoming SEO URL path segments into OC request params.
 *
 * Replaces the seo_url.php decode loop with:
 *   - language prefix stripping (LanguagePrefixConfig)
 *   - in-memory keyword lookup (CacheWarmer)
 *   - category path validation with parent_id chain check
 *   - custom entity route support (CustomRoutesConfig)
 *   - proper 404 vs "pass through" decision
 *
 * Result returned as array of $_GET-style params to merge into $request->get.
 */
class UrlRouter
{
    /** @var CacheWarmer */
    private $cache;
    /** @var LanguagePrefixConfig */
    private $langConfig;
    /** @var CustomRoutesConfig */
    private $routesConfig;
    private $db;
    private $config;
    public function __construct(
        CacheWarmer $cache,
        LanguagePrefixConfig $langConfig,
        CustomRoutesConfig $routesConfig,
        $db,
        $config
    ) {
        $this->cache = $cache;
        $this->langConfig = $langConfig;
        $this->routesConfig = $routesConfig;
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Decode _route_ path into request params.
     *
     * @param  string[] $parts URL segments (from explode('/', _route_))
     * @param  string   $requestUri  Full REQUEST_URI for language detection
     * @param  int      $storeId
     * @return array{params: array<string,mixed>, language_id: int|null, not_found: bool}
     */
    public function decode(array $parts, string $requestUri, int $storeId): array
    {
        // The pre-startup `oc_kit_seo_core_lang` already stripped the
        // language prefix from `_route_` and switched the language. Call
        // stripPrefix here only as a safety net for direct callers (admin
        // tools, sitemap generators) that may pass raw URIs.
        [$parts, $stripLangId] = $this->langConfig->stripPrefix($parts, $requestUri);
        $languageId = $stripLangId ?: (int)$this->config->get('config_language_id');

        $params   = [];
        $notFound = false;

        foreach ($parts as $part) {
            if ($part === '') continue;

            $candidates = $this->cache->queryByKeywordAll($part, $storeId, $languageId);
            if (!$candidates) {
                // Cross-language fallback: keyword may exist under a different
                // language (e.g. /en/{uk-slug}). Pull candidates from any
                // language so we can resolve the entity, then UrlValidator
                // 301s to the current-language canonical URL.
                $candidates = $this->cache->queryByKeywordAnyLang($part, $storeId);
                if (!$candidates) {
                    $notFound = true;
                    break;
                }
            }

            // Disambiguate colliding category keywords by matching parent_id
            // against the last resolved category id in $params['path'].
            $query = $this->pickBestCandidate($candidates, $params);

            $resolved = $this->applyQuery($query, $params);
            if (!$resolved) {
                $notFound = true;
                break;
            }
        }

        // Enrich category path with the full ancestor chain so UrlValidator
        // can detect canonical mismatch (flat /krosivky → /parent/krosivky).
        // This also disambiguates keyword collisions — same slug across
        // different branches resolves to the branch whose ancestors were
        // actually present in the URL, or (if none) to the canonical path
        // and triggers a 301 redirect.
        if (!$notFound && isset($params['path'])) {
            $params['path'] = $this->expandCategoryPath((string)$params['path']);
        }

        return [
            'params'      => $params,
            'language_id' => $languageId,
            'not_found'   => $notFound,
        ];
    }

    /**
     * When a keyword has multiple candidates (same slug in different categories),
     * pick the one whose parent_id matches the last resolved category in path.
     * Non-category queries are returned as-is (first candidate).
     */
    private function pickBestCandidate(array $candidates, array $params): string
    {
        if (count($candidates) === 1) return $candidates[0];

        // Slug collision between different entity types (e.g. a vendor and a
        // manufacturer both named "armani-exchange"). When one candidate's
        // query key is registered in custom entity_routes, the admin has
        // explicitly declared which entity owns such slugs — prefer it.
        foreach ($candidates as $q) {
            $eq = strpos($q, '=');
            if ($eq !== false && $this->routesConfig->getEntityRoute(substr($q, 0, $eq)) !== null) {
                return $q;
            }
        }

        // Only category queries can collide across branches — bail early for others
        $catQueries = [];
        foreach ($candidates as $q) {
            if (strpos($q, 'category_id=') === 0) $catQueries[] = $q;
        }
        if (!$catQueries) return $candidates[0];

        // If we already have a resolved parent in path, prefer a child of it
        if (isset($params['path'])) {
            $ids = explode('_', (string)$params['path']);
            $lastParent = (int)end($ids);
            if ($lastParent > 0) {
                foreach ($catQueries as $q) {
                    $childId = (int)substr($q, strlen('category_id='));
                    $row = $this->db->query(
                        "SELECT `parent_id` FROM `" . DB_PREFIX . "category`
                         WHERE `category_id` = {$childId} LIMIT 1"
                    )->row;
                    if ($row && (int)$row['parent_id'] === $lastParent) {
                        return $q;
                    }
                }
            }
        }

        // Fallback — first candidate (will be expanded via ancestor chain later)
        return $catQueries[0];
    }

    /**
     * Walk up parent_id chain and return a `root_..._leaf` category id string.
     * If the path already has 2+ ids, keep it unchanged (already explicit).
     */
    private function expandCategoryPath(string $path): string
    {
        $ids = array_values(array_filter(explode('_', $path), 'strlen'));
        if (count($ids) >= 2) return $path;
        if (!$ids) return $path;

        $leaf = (int)end($ids);
        if (!$leaf) return $path;

        $chain = [$leaf];
        $guard = 0;
        $current = $leaf;
        while ($guard++ < 10) {
            $row = $this->db->query(
                "SELECT `parent_id` FROM `" . DB_PREFIX . "category`
                 WHERE `category_id` = " . (int)$current . " LIMIT 1"
            )->row;
            if (empty($row) || (int)$row['parent_id'] === 0) break;
            $current = (int)$row['parent_id'];
            array_unshift($chain, $current);
        }
        return implode('_', $chain);
    }

    /**
     * Apply a single query string from oc_seo_url into the params array.
     * Returns false if the query is unrecognised.
     */
    private function applyQuery(string $query, array &$params): bool
    {
        // Full route override (e.g. query = "information/contact")
        if (strpos($query, '=') === false) {
            $params['route'] = $query;
            return true;
        }

        [$key, $value] = explode('=', $query, 2);

        switch ($key) {
            case 'product_id':
                $params['product_id'] = $value;
                return true;

            case 'manufacturer_id':
                $params['manufacturer_id'] = $value;
                return true;

            case 'information_id':
                $params['information_id'] = $value;
                return true;

            case 'category_id':
                // Accumulate path (validated later in validateCategoryPath)
                if (!isset($params['path'])) {
                    $params['path'] = $value;
                } else {
                    $params['path'] .= '_' . $value;
                }
                return true;

            default:
                // Check custom entity routes
                $route = $this->routesConfig->getEntityRoute($key);
                if ($route !== null) {
                    $params[$key]    = $value;
                    $params['route'] = $route;
                    return true;
                }
                // Unknown key — treat as arbitrary GET param, set as route query
                $params[$key] = $value;
                return true;
        }
    }

    /**
     * Validates that the category_id chain in $params['path'] is correct
     * (each category's parent_id matches the previous segment).
     * Returns false if the chain is broken.
     */
    public function validateCategoryPath(array $params, int $storeId): bool
    {
        if (!isset($params['path'])) return true;

        $ids = array_values(array_filter(explode('_', $params['path']), 'strlen'));
        if (count($ids) <= 1) return true;

        // Validate each link in the chain — child.parent_id must equal the
        // previous id. We do NOT require the first id to be a root (parent=0)
        // because at depth=1/2 the URL legitimately starts from mid-tree.
        for ($i = 1, $n = count($ids); $i < $n; $i++) {
            $childId  = (int)$ids[$i];
            $expected = (int)$ids[$i - 1];
            $row = $this->db->query(
                "SELECT `parent_id` FROM `" . DB_PREFIX . "category`
                 WHERE `category_id` = {$childId} LIMIT 1"
            )->row;
            if (!$row) return false;
            if ((int)$row['parent_id'] !== $expected) return false;
        }

        return true;
    }

    /**
     * Infer the OC route from decoded params (matches OC's own logic).
     */
    public function inferRoute(array $params): ?string
    {
        if (isset($params['route'])) return $params['route'];
        if (isset($params['product_id']))     return 'product/product';
        if (isset($params['path']))           return 'product/category';
        if (isset($params['manufacturer_id'])) return 'product/manufacturer/info';
        if (isset($params['information_id'])) return 'information/information';
        return null;
    }
}
