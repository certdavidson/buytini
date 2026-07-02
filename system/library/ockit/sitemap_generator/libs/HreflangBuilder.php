<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Dto\SitemapConfig;

/**
 * Builds xhtml:link hreflang alternate sets for each URL entry.
 * Uses URL-prefix substitution approach (v1): same slug, different prefix per language.
 */
class HreflangBuilder
{
    /** @var SitemapConfig[] */
    private array $maps;
    private bool  $enabled;
    private int   $xdefaultMapId;
    private string $missingBehavior; // 'skip' | 'use_default'

    /**
     * @param SitemapConfig[] $maps    All active language maps
     * @param array           $cfg     Hreflang settings from module config
     */
    public function __construct(array $maps, array $cfg)
    {
        $this->maps             = $maps;
        $this->enabled          = !empty($cfg['enable_hreflang']);
        $this->xdefaultMapId    = (int)($cfg['xdefault_map_id'] ?? 0);
        $this->missingBehavior  = $cfg['missing_translation_behavior'] ?? 'skip';
    }

    /**
     * Returns assoc array [hreflang => href] for all alternate languages.
     * Returns empty array if hreflang is disabled or only one map exists.
     *
     * Two modes depending on whether $query is provided:
     *
     * 1. Entity mode ($query not null, e.g. 'product_id=123'):
     *    Uses $keywordIndex[mapId][query] to resolve per-language slugs.
     *    If a language has no SEO URL for this entity, the link is omitted.
     *
     * 2. Static-page mode ($query null, e.g. home/special/contact):
     *    Falls back to simple URL-prefix substitution using $slug.
     *
     * @param string      $baseUrl      Store base URL (without trailing slash)
     * @param string      $slug         Current-language slug — fallback for static pages
     * @param string      $currentPrefix URL prefix of the current map
     * @param array       $keywordIndex [mapId => [query => keyword]] pre-loaded for all maps
     * @param string|null $query        OC query string (e.g. 'product_id=123'); null for static pages
     */
    public function buildLinks(string $baseUrl, string $slug, string $currentPrefix, array $keywordIndex = [], ?string $query = null): array
    {
        if (!$this->enabled || count($this->maps) <= 1) {
            return [];
        }

        $links      = [];
        $baseUrl    = rtrim($baseUrl, '/');
        $defaultMap = $this->getDefaultMap();

        foreach ($this->maps as $map) {
            if (!$map->status) continue;

            if ($query !== null && !empty($keywordIndex)) {
                // Entity mode: per-language slug from keyword index
                $mapSlug = $keywordIndex[$map->mapId][$query] ?? null;
                if ($mapSlug === null) {
                    if ($this->missingBehavior === 'skip') {
                        continue;
                    }
                    // use_default: fall back to current language's slug with this map's prefix
                    $mapSlug = ltrim($slug, '/');
                }
                $href = $baseUrl . '/' . $map->getUrlSegment() . $mapSlug;
            } else {
                // Static-page mode: same slug, different prefix
                $href = $baseUrl . '/' . $map->getUrlSegment() . ltrim($slug, '/');
            }

            $href = preg_replace('#([^:])/{2,}#', '$1/', $href);
            $links[$map->hreflangLocale] = $href;
        }

        // x-default
        if ($defaultMap !== null && $defaultMap->status) {
            if ($query !== null && !empty($keywordIndex)) {
                $mapSlug = $keywordIndex[$defaultMap->mapId][$query] ?? null;
                if ($mapSlug === null) {
                    if ($this->missingBehavior !== 'skip') {
                        $mapSlug = ltrim($slug, '/');
                    }
                }
                if ($mapSlug !== null) {
                    $defaultHref = $baseUrl . '/' . $defaultMap->getUrlSegment() . $mapSlug;
                    $defaultHref = preg_replace('#([^:])/{2,}#', '$1/', $defaultHref);
                    $links['x-default'] = $defaultHref;
                }
            } else {
                $defaultHref = $baseUrl . '/' . $defaultMap->getUrlSegment() . ltrim($slug, '/');
                $defaultHref = preg_replace('#([^:])/{2,}#', '$1/', $defaultHref);
                $links['x-default'] = $defaultHref;
            }
        }

        return $links;
    }

    private function getDefaultMap(): ?SitemapConfig
    {
        // Explicit x-default map selection
        if ($this->xdefaultMapId > 0) {
            foreach ($this->maps as $map) {
                if ($map->mapId === $this->xdefaultMapId) return $map;
            }
        }
        // Fall back to the map with is_default flag
        foreach ($this->maps as $map) {
            if ($map->isDefault) return $map;
        }
        // Last resort: first active map
        foreach ($this->maps as $map) {
            if ($map->status) return $map;
        }
        return null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
