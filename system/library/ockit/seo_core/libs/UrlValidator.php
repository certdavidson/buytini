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
 * Validates the current request URL and issues 301 redirects for:
 *   - canonical URL mismatch (wrong category depth, missing/extra slash)
 *   - page=1 removal
 *   - language prefix mismatch
 *
 * Called from the startup controller after routing is resolved.
 */
class UrlValidator
{
    /** @var UrlGenerator */
    private $generator;
    /** @var LanguagePrefixConfig */
    private $langConfig;
    private $config;
    private $request;
    private $response;
    private $url;
    /** @var CustomRoutesConfig|null */
    private $routesConfig;
    public function __construct(
        UrlGenerator $generator,
        LanguagePrefixConfig $langConfig,
        $config,
        $request,
        $response,
        $url,
        ?CustomRoutesConfig $routesConfig = null
    ) {
        $this->generator = $generator;
        $this->langConfig = $langConfig;
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;
        $this->url = $url;
        $this->routesConfig = $routesConfig;
    }

    /**
     * Validate the current request and redirect if necessary.
     * Returns true if a redirect was issued (caller should return immediately).
     */
    /**
     * Detect AJAX/XHR/asset requests. Per ТЗ §3.3 (г): AJAX should get
     * X-Robots-Tag: noindex but bypass canonical-mismatch redirects so XHR
     * payloads aren't 301-bounced.
     */
    public function detectAjax(): bool
    {
        $req = $this->request;
        if (!empty($req->server['HTTP_X_REQUESTED_WITH'])
            && strcasecmp($req->server['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0) {
            return true;
        }
        $accept = (string)($req->server['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && stripos($accept, 'application/json') !== false) {
            return true;
        }
        $uri = (string)($req->server['REQUEST_URI'] ?? '');
        if (preg_match('/\.(?:css|js|json|map|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot)(?:$|\?)/i', $uri)) {
            return true;
        }
        return false;
    }

    public function validate(array $requestGet, string $requestUri, int $storeId): bool
    {
        // AJAX → noindex header, no canonical redirect (per ТЗ §3.3 г)
        if ($this->detectAjax()) {
            $this->response->addHeader('X-Robots-Tag: noindex');
            return false;
        }

        // Collapse repeated slashes in the request path: //foo///bar → /foo/bar.
        // Run before any route-based logic — `////` won't have a _route_ but
        // is still a duplicate of `/` and should 301 home.
        $path  = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $query = parse_url($requestUri, PHP_URL_QUERY);
        if (preg_match('#//+#', $path)) {
            $clean = preg_replace('#/{2,}#', '/', $path);
            $this->redirect($clean . ($query !== null && $query !== '' ? '?' . $query : ''), 301);
            return true;
        }

        // Remove page=1 — always a redirect target
        if (isset($requestGet['page']) && (int)$requestGet['page'] === 1) {
            $get = $requestGet;
            unset($get['page'], $get['_route_']);
            $route = $get['route'] ?? '';
            unset($get['route']);
            $canonical = $this->url->link($route, http_build_query($get));
            $this->redirect($canonical, 301);
            return true;
        }

        // index.php?route=... requests have no _route_; canonicalize them too
        // so users hitting non-SEO URLs get 301'd to the SEO version.
        $hasIndexPhp = strpos($requestUri, '/index.php') !== false;
        if (!isset($requestGet['_route_']) && !$hasIndexPhp) return false;

        // /index.php?route=common/home is gated by an admin toggle — some
        // shops intentionally use that URL. Default = redirect enabled;
        // user has to explicitly save with the toggle off ('0') to opt out.
        if ($hasIndexPhp
            && ($requestGet['route'] ?? '') === 'common/home'
            && (string)$this->config->get('module_oc_kit_seo_core_home_redirect_index') === '0') {
            return false;
        }

        $canonical = $this->buildCanonicalForRequest($requestGet, $storeId);
        if ($canonical === null) return false;

        $current = $this->normalise($requestUri);
        $target  = $this->normalise($canonical);

        if ($current !== $target) {
            // Preserve non-structural query params (page, ajax, search, sort, etc.)
            $extra = $this->extraQueryParams($requestGet);
            if ($extra !== '') {
                $canonical .= (strpos($canonical, '?') === false ? '?' : '&') . $extra;
            }
            $this->redirect($canonical, 301);
            return true;
        }

        return false;
    }

    /**
     * Build a query string of all $_GET params that aren't route-structural
     * and aren't on the admin-configured "strip" list (UTM, gclid, fbclid…).
     *
     * Structural keys (route, _route_, product_id, path, manufacturer_id,
     * information_id, language_id) are already encoded in the canonical URL.
     *
     * Strip list — config key `module_oc_kit_seo_core_strip_query_params`,
     * comma- or newline-separated. Supports wildcard suffix `*` (e.g. `utm_*`).
     */
    private function extraQueryParams(array $requestGet): string
    {
        $structural = [
            'route', '_route_', 'product_id', 'path',
            'manufacturer_id', 'information_id', 'language_id',
        ];
        // Custom entity-route keys (e.g. vendor_id) are structural too — the
        // canonical SEO URL already encodes them, so they must not be re-appended.
        if ($this->routesConfig) {
            foreach (array_keys($this->routesConfig->getEntityRoutes()) as $key) {
                $structural[] = (string)$key;
            }
        }
        $stripPatterns = $this->getStripPatterns();

        $extra = [];
        foreach ($requestGet as $k => $v) {
            if (in_array($k, $structural, true)) continue;
            if ($this->matchesAny((string)$k, $stripPatterns)) continue;
            $extra[$k] = is_array($v) ? $v : (string)$v;
        }
        return $extra ? http_build_query($extra) : '';
    }

    /** @return string[] */
    private function getStripPatterns(): array
    {
        $raw = (string)$this->config->get('module_oc_kit_seo_core_strip_query_params');
        if ($raw === '') return [];
        $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_map('strtolower', $parts);
    }

    private function matchesAny(string $key, array $patterns): bool
    {
        $key = strtolower($key);
        foreach ($patterns as $p) {
            if (substr($p, -1) === '*') {
                if (strpos($key, substr($p, 0, -1)) === 0) return true;
            } elseif ($key === $p) {
                return true;
            }
        }
        return false;
    }

    private function buildCanonicalForRequest(array $params, int $storeId): ?string
    {
        $route = $params['route'] ?? null;
        if (!$route) return null;

        $langId = (int)($params['language_id'] ?? $this->config->get('config_language_id'));

        $queryStr = $this->buildQueryForRoute($route, $params);
        if ($queryStr === null) return null;

        return $this->url->link($route, $queryStr);
    }

    private function buildQueryForRoute(string $route, array $params): ?string
    {
        $pairs = [];

        switch ($route) {
            case 'product/product':
                if (isset($params['product_id'])) $pairs[] = 'product_id=' . (int)$params['product_id'];
                if (isset($params['manufacturer_id'])) $pairs[] = 'manufacturer_id=' . (int)$params['manufacturer_id'];
                break;

            case 'product/category':
                if (isset($params['path'])) $pairs[] = 'path=' . $params['path'];
                break;

            case 'product/manufacturer/info':
                if (isset($params['manufacturer_id'])) $pairs[] = 'manufacturer_id=' . (int)$params['manufacturer_id'];
                break;

            case 'information/information':
                if (isset($params['information_id'])) $pairs[] = 'information_id=' . (int)$params['information_id'];
                break;

            case 'common/home':
                // Home — empty query, canonical is the language root URL
                break;

            default:
                // Custom / third-party routes (vendor/vendor, vendor/vendor/view,
                // blog/post, …). Include ONLY the params that structurally
                // identify the entity — i.e. registered custom entity-route keys
                // mapping to THIS route. Everything else (page, sort, …) is left
                // for extraQueryParams() in the caller, so nothing is duplicated.
                // Route-override rows (query has no "=") have no key here →
                // empty query string → url->link() resolves via keywordByQuery().
                if ($this->routesConfig) {
                    foreach ($this->routesConfig->getEntityRoutes() as $key => $mappedRoute) {
                        if ($mappedRoute === $route && isset($params[$key]) && $params[$key] !== '') {
                            $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$params[$key]);
                        }
                    }
                }
                break;
        }

        return implode('&', $pairs);
    }

    private function normalise(string $url): string
    {
        // Strip scheme+host, query string, fragment. Preserve trailing slash —
        // it's part of the canonical comparison (trailing-slash policy:
        // off / categories / all). Special-case "/" so home stays "/".
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        return $path;
    }

    private function redirect(string $url, int $code): void
    {
        $this->response->redirect($url, $code);
    }
}
