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
 * Generates hreflang link tags for multilingual pages.
 *
 * Logic:
 *   - For the current entity query, finds SEO keywords in all active languages.
 *   - Builds URL for each language using LanguagePrefixConfig prefix.
 *   - The language with empty prefix is also x-default.
 *   - Languages with no keyword are excluded.
 *
 * Config key:
 *   module_oc_kit_seo_core_hreflang_format  (iso639|bcp47)
 */
class HreflangBuilder
{
    /** @var CacheWarmer */
    private $cache;
    /** @var LanguagePrefixConfig */
    private $langConfig;
    private $config;
    private $db;
    private $url;
    private $document;
    public function __construct(CacheWarmer $cache, LanguagePrefixConfig $langConfig, $config, $db, $url, $document) {
        $this->cache = $cache;
        $this->langConfig = $langConfig;
        $this->config = $config;
        $this->db = $db;
        $this->url = $url;
        $this->document = $document;
    }

    /**
     * Build hreflang data for the current page.
     * @return array [{hreflang, href}]
     */
    public function build(string $route, array $params): array
    {
        $storeId = (int)$this->config->get('config_store_id');
        $query   = $this->paramsToQuery($route, $params);
        if (!$query) return [];

        $langs  = $this->getActiveLanguages();
        $format = (string)($this->config->get('module_oc_kit_seo_core_hreflang_format') ?: 'iso639');
        $links  = [];
        $xDefault = null;

        foreach ($langs as $lang) {
            $langId = (int)$lang['language_id'];
            $keyword = $this->cache->keywordByQuery($query, $storeId, $langId);
            if ($keyword === null) continue;

            $prefix = $this->langConfig->getPrefixById($langId);
            $slug   = ($prefix !== '' ? '/' . $prefix : '') . '/' . $keyword;

            $baseUrl = defined('HTTPS_SERVER') ? HTTPS_SERVER : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
            $href    = rtrim($baseUrl, '/') . $slug;
            if ($this->config->get('module_oc_kit_seo_core_trailing_slash')) {
                $href .= '/';
            }

            $hreflang = $format === 'bcp47' ? $this->toBcp47($lang['code']) : $this->toIso639($lang['code']);

            $links[] = ['hreflang' => $hreflang, 'href' => $href];

            // x-default = language with empty prefix
            if ($prefix === '' && $xDefault === null) {
                $xDefault = $href;
            }
        }

        if ($xDefault) {
            $links[] = ['hreflang' => 'x-default', 'href' => $xDefault];
        }

        return $links;
    }

    /**
     * Inject hreflang <link> tags into document head.
     * @param array $links [{hreflang, href}]
     */
    public function inject(array $links): void
    {
        foreach ($links as $link) {
            // OC 3.x Document::addLink($href, $rel) has no 3rd arg for hreflang —
            // emit through DocumentExtra so the theme OCMOD patch renders them.
            DocumentExtra::addLink([
                'rel'      => 'alternate',
                'href'     => (string)$link['href'],
                'hreflang' => (string)$link['hreflang'],
            ]);
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function paramsToQuery(string $route, array $params): ?string
    {
        switch ($route) {
            case 'product/product':
                return isset($params['product_id']) ? 'product_id=' . (int)$params['product_id'] : null;
            case 'product/category':
                if (!isset($params['path'])) return null;
                $ids = explode('_', $params['path']);
                return 'category_id=' . end($ids);
            case 'product/manufacturer/info':
                return isset($params['manufacturer_id']) ? 'manufacturer_id=' . (int)$params['manufacturer_id'] : null;
            case 'information/information':
                return isset($params['information_id']) ? 'information_id=' . (int)$params['information_id'] : null;
        }
        return null;
    }

    private function getActiveLanguages(): array
    {
        return $this->db->query(
            "SELECT `language_id`, `code`, `name` FROM `" . DB_PREFIX . "language` WHERE `status` = 1 ORDER BY `sort_order`"
        )->rows;
    }

    private function toIso639(string $code): string
    {
        return strtolower(explode('-', $code)[0]);
    }

    private function toBcp47(string $code): string
    {
        $parts = explode('-', $code, 2);
        if (count($parts) === 2) {
            return strtolower($parts[0]) . '-' . strtoupper($parts[1]);
        }
        return strtolower($code);
    }
}
