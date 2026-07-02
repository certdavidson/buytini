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
 * Admin-configurable route mappings.
 *
 * skip_routes:   fnmatch patterns for routes that bypass SEO URL rewriting entirely.
 *                Example: ["account/*","checkout/*","special_id=*"]
 *
 * entity_routes: maps a query param name to an OC route.
 *                Example: {"special_id": "product/special", "vendor_id": "product/manufacturer/info"}
 *                Used when the URL keyword maps to a custom query key not known to the default seo_url logic.
 *
 * Config key: module_oc_kit_seo_core_custom_routes
 * Format: JSON {"skip_routes":["..."],"entity_routes":{"key":"route"}}
 */
class CustomRoutesConfig
{
    /**
     * Built-in routes that always bypass SEO URL rewriting/validation —
     * never user-configurable, always present (per ТЗ §3.3 b).
     */
    public const BUILT_IN_BREAK_ROUTES = [
        'error/not_found',
        'extension/feed/*',
        'startup/*',
    ];

    private ?array $skipRoutes    = null;
    private ?array $entityRoutes  = null;

    private $config;
    public function __construct($config) {
        $this->config = $config;
    }

    private function load(): void
    {
        if ($this->skipRoutes !== null) return;

        $raw = $this->config->get('module_oc_kit_seo_core_custom_routes');
        $data = $raw ? json_decode($raw, true) : [];
        if (!is_array($data)) $data = [];

        $this->skipRoutes   = (array)($data['skip_routes']   ?? []);
        $this->entityRoutes = (array)($data['entity_routes'] ?? []);
    }

    /**
     * Returns true if the given route string matches any skip pattern.
     * Uses fnmatch so wildcards like "account/*" work.
     */
    public function shouldSkip(string $route): bool
    {
        // Built-in routes always skip — independent of admin config.
        foreach (self::BUILT_IN_BREAK_ROUTES as $pattern) {
            if (fnmatch($pattern, $route)) return true;
        }
        $this->load();
        foreach ($this->skipRoutes as $pattern) {
            if (fnmatch($pattern, $route)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the OC route for a custom entity query key, or null if not configured.
     * Example: getEntityRoute('special_id') → 'product/special'
     */
    public function getEntityRoute(string $queryKey): ?string
    {
        $this->load();
        return isset($this->entityRoutes[$queryKey]) ? (string)$this->entityRoutes[$queryKey] : null;
    }

    /**
     * Returns all entity route mappings (key => route).
     * @return array<string,string>
     */
    public function getEntityRoutes(): array
    {
        $this->load();
        return $this->entityRoutes;
    }

    /**
     * Returns all skip patterns.
     * @return string[]
     */
    public function getSkipRoutes(): array
    {
        $this->load();
        return array_values(array_unique(array_merge(self::BUILT_IN_BREAK_ROUTES, $this->skipRoutes)));
    }
}
