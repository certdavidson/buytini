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
 * Generates and injects the canonical URL for the current page.
 *
 * Resolution order:
 *   a) Manual override in oc_kit_seo_meta_override.canonical
 *   b) Pagination rule (first page / self)
 *   c) Filter params rule (base / self / noindex)
 *   d) Depth correction for category URLs
 *   e) Cross-domain replacement
 *
 * Config keys:
 *   module_oc_kit_seo_core_canonical_pagination  (first|self)
 *   module_oc_kit_seo_core_canonical_filters     (base|self|noindex)
 *   module_oc_kit_seo_core_canonical_cross_domain
 */
class CanonicalManager
{
    /** @var MetaRepository */
    private $repo;
    /** @var UrlGenerator */
    private $generator;
    private $config;
    private $request;
    private $url;
    private $response;
    private $document;
    public function __construct(
        MetaRepository $repo,
        UrlGenerator $generator,
        $config,
        $request,
        $url,
        $response,
        $document
    ) {
        $this->repo = $repo;
        $this->generator = $generator;
        $this->config = $config;
        $this->request = $request;
        $this->url = $url;
        $this->response = $response;
        $this->document = $document;
    }

    /**
     * Determine the canonical URL for the current request.
     */
    public function resolve(string $route, array $params, int $languageId): string
    {
        $storeId = (int)$this->config->get('config_store_id');

        // a) Manual override
        [$entityType, $entityId] = $this->entityFromParams($route, $params);
        if ($entityType && $entityId) {
            $override = $this->repo->getOverride($entityType, $entityId, $languageId, $storeId);
            if ($override && !empty($override['canonical'])) {
                return $this->applyDomain($override['canonical']);
            }
        }

        $page = (int)($params['page'] ?? $this->request->get['page'] ?? 1);

        // b) Pagination
        if ($route === 'product/category' && $page > 1) {
            $mode = $this->config->get('module_oc_kit_seo_core_canonical_pagination') ?: 'first';
            if ($mode === 'first') {
                return $this->applyDomain($this->buildBaseUrl($route, $params, $languageId));
            }
        }

        // c) Filter params
        $hasFilters = $this->hasFilterParams();
        if ($hasFilters) {
            $mode = $this->config->get('module_oc_kit_seo_core_canonical_filters') ?: 'base';
            if ($mode === 'noindex') {
                $this->response->addHeader('X-Robots-Tag: noindex, follow');
                return $this->applyDomain($this->currentUrl());
            }
            if ($mode === 'base') {
                return $this->applyDomain($this->buildBaseUrl($route, $params, $languageId));
            }
        }

        // d) Current URL as canonical
        $canonical = $this->buildBaseUrl($route, $params, $languageId);
        return $this->applyDomain($canonical);
    }

    /**
     * Inject canonical into document head.
     */
    public function inject(string $canonical): void
    {
        if ($canonical) {
            $this->document->addLink('canonical', $canonical);
        }
    }

    /**
     * Save manual canonical override.
     */
    public function setManual(string $entityType, int $entityId, int $languageId, int $storeId, string $url): void
    {
        $this->repo->saveOverride([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'language_id' => $languageId,
            'store_id'    => $storeId,
            'canonical'   => $url,
        ]);
    }

    public function getSettings(int $storeId): array
    {
        return [
            'canonical_pagination'  => $this->config->get('module_oc_kit_seo_core_canonical_pagination')  ?: 'first',
            'canonical_filters'     => $this->config->get('module_oc_kit_seo_core_canonical_filters')     ?: 'base',
            'canonical_cross_domain'=> $this->config->get('module_oc_kit_seo_core_canonical_cross_domain') ?: '',
        ];
    }

    public function saveSettings(array $settings, int $storeId): void
    {
        $this->config->set('module_oc_kit_seo_core_canonical_pagination',   $settings['canonical_pagination']   ?? 'first');
        $this->config->set('module_oc_kit_seo_core_canonical_filters',      $settings['canonical_filters']      ?? 'base');
        $this->config->set('module_oc_kit_seo_core_canonical_cross_domain', $settings['canonical_cross_domain'] ?? '');
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function entityFromParams(string $route, array $params): array
    {
        switch ($route) {
            case 'product/product':      return ['product',      (int)($params['product_id']      ?? 0)];
            case 'product/category':     return ['category',     (int)(explode('_', $params['path'] ?? '0')[count(explode('_', $params['path'] ?? '0')) - 1])];
            case 'product/manufacturer/info': return ['manufacturer', (int)($params['manufacturer_id'] ?? 0)];
            case 'information/information':   return ['information',  (int)($params['information_id']   ?? 0)];
        }
        return ['', 0];
    }

    private function buildBaseUrl(string $route, array $params, int $languageId): string
    {
        $get = $params;
        unset($get['page'], $get['route'], $get['language_id']);

        $link = $this->url->link($route, http_build_query($get));
        $rewritten = $this->generator->rewrite($link, (int)$this->config->get('config_store_id'), $languageId);
        return $rewritten ?? $link;
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    private function hasFilterParams(): bool
    {
        foreach ($this->request->get as $key => $_) {
            if (strpos($key, 'filter_') === 0) return true;
        }
        return false;
    }

    private function applyDomain(string $url): string
    {
        $crossDomain = (string)$this->config->get('module_oc_kit_seo_core_canonical_cross_domain');
        if (!$crossDomain || !$url) return $url;

        $parsed = parse_url($url);
        if (!$parsed) return $url;

        $target = parse_url(rtrim($crossDomain, '/'));
        if (!$target) return $url;

        return ($target['scheme'] ?? 'https') . '://' .
               ($target['host'] ?? ($parsed['host'] ?? '')) .
               ($parsed['path'] ?? '') .
               (isset($parsed['query']) ? '?' . $parsed['query'] : '');
    }
}
