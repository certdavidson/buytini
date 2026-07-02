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

namespace OcKit\SeoCore;

use OcKit\SeoCore\Libs\StoreContext;
use OcKit\SeoCore\Libs\CacheWarmer;
use OcKit\SeoCore\Libs\LanguagePrefixConfig;
use OcKit\SeoCore\Libs\CustomRoutesConfig;
use OcKit\SeoCore\Libs\UrlRouter;
use OcKit\SeoCore\Libs\UrlGenerator;
use OcKit\SeoCore\Libs\UrlValidator;
use OcKit\SeoCore\Libs\MetaRepository;
use OcKit\SeoCore\Libs\MetaTemplateEngine;
use OcKit\SeoCore\Libs\PaginationMetaEngine;
use OcKit\SeoCore\Libs\CanonicalManager;
use OcKit\SeoCore\Libs\HreflangBuilder;
use OcKit\SeoCore\Libs\OpenGraphRenderer;
use OcKit\SeoCore\Libs\SchemaBuilder;
use OcKit\SeoCore\Libs\SchemaCustomEditor;
use OcKit\SeoCore\Libs\SchemaTemplateEngine;
use OcKit\SeoCore\Libs\RedirectManager;
use OcKit\SeoCore\Libs\PaginationGuard;
use OcKit\SeoCore\Libs\AbTestEngine;
use OcKit\SeoCore\Libs\HeaderRuleEngine;
use OcKit\SeoCore\Libs\DocumentExtra;
use OcKit\SeoCore\Libs\RouteMetaRepository;

/**
 * Main facade. All public entry points used by OC controllers go through here.
 *
 * Catalog usage:
 *   $seoCore = new \OcKit\SeoCore\SeoCore($registry);
 *   $seoCore->prepareRoute($parts);
 *   $seoCore->validate();
 *   $seoCore->injectHeadTags($route, $params, $languageId);  // in common/header
 *
 * Admin usage:
 *   SeoCore::guardAdmin($registry);
 *   SeoCore::getLicenseStatus($registry);
 *   SeoCore::activateLicenseKey($registry, $key);
 */
class SeoCore
{
    private $registry;
    private bool $licensed;

    private ?StoreContext         $storeContext   = null;
    private ?CacheWarmer          $cacheWarmer    = null;
    private ?LanguagePrefixConfig $langConfig     = null;
    private ?CustomRoutesConfig   $routesConfig   = null;
    private ?UrlRouter            $urlRouter      = null;
    private ?UrlGenerator         $urlGenerator   = null;
    private ?UrlValidator         $urlValidator   = null;
    private ?MetaRepository       $metaRepo       = null;
    private ?MetaTemplateEngine   $metaEngine     = null;
    private ?PaginationMetaEngine $paginMeta      = null;
    private ?CanonicalManager     $canonical      = null;
    private ?HreflangBuilder      $hreflang       = null;
    private ?OpenGraphRenderer    $og             = null;
    private ?SchemaBuilder        $schema         = null;
    private ?RedirectManager      $redirectManager = null;
    private ?PaginationGuard      $paginGuard     = null;
    private ?AbTestEngine         $abEngine       = null;
    private ?RouteMetaRepository  $routeMetaRepo  = null;

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->licensed = $this->getStoreContext()->isActive();

        if (!$this->licensed && php_sapi_name() !== 'cli') {
            $token = $registry->get('session')->data['user_token'] ?? '';
            if ($token !== '') {
                $registry->get('response')->redirect(
                    $registry->get('url')->link(
                        'extension/module/oc_kit_seo_core/license',
                        'user_token=' . $token, true
                    )
                );
                exit;
            }
        }
    }

    // ─── Catalog: URL routing ─────────────────────────────────────────────────

    /**
     * Decode SEO URL path segments into $_GET params.
     * @param  string[] $parts
     * @return string[]
     */
    public function prepareRoute(array $parts): array
    {
        if (!$this->licensed) return $parts;

        $storeId    = (int)$this->registry->get('config')->get('config_store_id');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        $result = $this->getUrlRouter()->decode($parts, $requestUri, $storeId);

        if ($result['not_found']) {
            // Check redirect rules first; if one matches, RedirectManager
            // issues the redirect and exits.
            $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
            $this->getRedirectManager()->resolve($path, $storeId, $this->registry->get('response'));

            // No redirect rule and no matching SEO URL anywhere — explicit 404.
            // We set the route here because SCF is the SEO authority; without
            // this OC's seo_url falls through to action_default (common/home).
            $this->registry->get('request')->get['route'] = 'error/not_found';
            return [];
        }

        $request = $this->registry->get('request');
        foreach ($result['params'] as $key => $value) {
            $request->get[$key] = $value;
        }

        if (isset($result['params']['path'])) {
            if (!$this->getUrlRouter()->validateCategoryPath($result['params'], $storeId)) {
                $request->get['route'] = 'error/not_found';
                return [];
            }
        }

        if (!isset($request->get['route'])) {
            $route = $this->getUrlRouter()->inferRoute($result['params']);
            if ($route) $request->get['route'] = $route;
        }

        if ($result['language_id']) {
            $request->get['language_id'] = $result['language_id'];
        }

        return [];
    }

    /**
     * Validate canonical URL and issue 301 redirects where needed.
     */
    public function validate(): void
    {
        if (!$this->licensed) return;

        $storeId    = (int)$this->registry->get('config')->get('config_store_id');
        $request    = $this->registry->get('request');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        $this->getUrlValidator()->validate($request->get, $requestUri, $storeId);

        // Note: empty-page 404/noindex needs $total from the catalog controller
        // (product/category, blog/category, product/search). It's applied via
        // OCMOD patches on those controllers — not here in startup/seo_url.
        // At this point we only know $page, so we skip the guard call.
    }

    /**
     * Rewrite internal OC link to SEO URL.
     * Returns null to signal "keep original link".
     */
    public function rewrite(string $link): ?string
    {
        if (!$this->licensed) return null;

        $config     = $this->registry->get('config');
        $storeId    = (int)$config->get('config_store_id');
        $languageId = (int)$config->get('config_language_id');

        return $this->getUrlGenerator()->rewrite($link, $storeId, $languageId);
    }

    // ─── Catalog: head tag injection ──────────────────────────────────────────

    /**
     * Inject all SEO head tags for the current page.
     * Called from catalog/controller/common/header.php.
     *
     * Sequence:
     *   1. CanonicalManager::resolve() → inject()
     *   2. HreflangBuilder::build()    → inject()
     *   3. OpenGraphRenderer::render() → inject()
     *   4. SchemaBuilder::injectForRoute()
     *   5. MetaTemplateEngine::render() → apply to document
     *   6. PaginationMetaEngine::applyPaginationSuffix() (page > 1)
     */
    public function injectHeadTags(string $route, array $params, int $languageId): void
    {
        if (!$this->licensed) return;

        $config = $this->registry->get('config');
        $page   = (int)($this->registry->get('request')->get['page'] ?? 1);

        // 0. Resource hints — preload + dns-prefetch (configured globally)
        $this->injectResourceHints($config);

        // 1. Canonical — always emitted (no toggle, it's a baseline SEO signal)
        $canonical = $this->getCanonical()->resolve($route, $params, $languageId);
        $this->getCanonical()->inject($canonical);

        // 2. Hreflang — gated by hreflang_enabled
        if ((int)$config->get('module_oc_kit_seo_core_hreflang_enabled')) {
            $hreflangLinks = $this->getHreflang()->build($route, $params);
            $this->getHreflang()->inject($hreflangLinks);
        }

        // 3. Open Graph — gated by og_enabled
        if ((int)$config->get('module_oc_kit_seo_core_og_enabled')) {
            $ogTags = $this->getOg()->render($route, $params, $languageId);
            $this->getOg()->inject($ogTags);
        }

        // 4. Schema.org — at least one type toggle must be on (per-type gates inside)
        $this->getSchema()->injectForRoute($route, $params, $languageId);

        // 5. Custom Schema rules (always evaluated — store-id and route filter inside)
        $this->getSchemaCustom()->renderForRouteAndInject($route, $params, $languageId);

        // 5 + 6. Meta title/description
        $document = $this->registry->get('document');

        if ($route === 'common/home') {
            $this->applyHomeMeta($document, $languageId);
        } else {
            [$entityType, $entityId] = $this->entityFromParams($route, $params);
            $appliedTitle = $appliedDesc = $appliedKw = false;

            if ($entityType && $entityId) {
                // Auto-generate seo_url for this entity if toggle is on and it's missing.
                $this->autoGenerateUrlIfMissing($entityType, $entityId, $languageId);

                $meta = $this->getMetaEngine()->render($entityType, $entityId, $languageId, (int)$this->registry->get('config')->get('config_store_id'), $page);

                if ($page > 1) {
                    $meta = $this->getPaginMeta()->applyPaginationSuffix($meta, $page, $languageId);
                }

                if ($meta->title)       { $document->setTitle($meta->title);       $appliedTitle = true; }
                if ($meta->description) { $document->setDescription($meta->description); $appliedDesc = true; }
            }

            // Route-level meta — applies to pages without an entity-meta path
            // (manufacturer list, blog index, third-party modules) and as a
            // per-field fallback when the entity path didn't set a value.
            $storeId = (int)$this->registry->get('config')->get('config_store_id');
            $routeMeta = $this->getRouteMetaRepo()->getForRoute($route, $storeId, $languageId);
            if ($routeMeta) {
                if (!$appliedTitle && $routeMeta['title'] !== '') {
                    $document->setTitle($this->expandRouteVars($routeMeta['title'], $page));
                }
                if (!$appliedDesc && $routeMeta['description'] !== '') {
                    $document->setDescription($this->expandRouteVars($routeMeta['description'], $page));
                }
                if (!$appliedKw && $routeMeta['keywords'] !== '') {
                    $document->setKeywords($this->expandRouteVars($routeMeta['keywords'], $page));
                }
            }
        }
    }

    /**
     * Minimal template expansion for route-meta strings: {page}, {store_name},
     * {year}, and `{{#if page}}…{{/if}}` blocks. Mirrors MetaTemplateEngine syntax.
     */
    private function expandRouteVars(string $template, int $page): string
    {
        $vars = [
            'page'       => $page > 1 ? (string)$page : '',
            'store_name' => (string)$this->registry->get('config')->get('config_name'),
            'year'       => date('Y'),
        ];
        $template = preg_replace_callback(
            '/\{\{#if\s+(\w+)\s*\}\}(.*?)\{\{\/if\}\}/s',
            function (array $m) use ($vars): string {
                $v = $vars[$m[1]] ?? '';
                $truthy = !($v === '' || $v === null || $v === '0' || $v === false);
                return $truthy ? $m[2] : '';
            },
            $template
        );
        return preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($vars): string {
            return (string)($vars[$m[1]] ?? '');
        }, $template);
    }

    /**
     * Apply admin-configured meta tags to the home page (`common/home`).
     */
    private function applyHomeMeta($document, int $languageId): void
    {
        $code = '';
        foreach ($this->getLangConfig()->all() as $lp) {
            if ($lp->languageId === $languageId) { $code = $lp->code; break; }
        }
        $lcode = explode('-', strtolower($code))[0] ?: 'uk';

        $config = $this->registry->get('config');
        $title    = (string)$config->get("module_oc_kit_seo_core_home_title_{$lcode}");
        $desc     = (string)$config->get("module_oc_kit_seo_core_home_desc_{$lcode}");
        $keywords = (string)$config->get("module_oc_kit_seo_core_home_keywords_{$lcode}");

        if ($title    !== '') $document->setTitle($title);
        if ($desc     !== '') $document->setDescription($desc);
        if ($keywords !== '') $document->setKeywords($keywords);
    }

    /**
     * Auto-create a `seo_url` row for the entity if the admin enabled
     * `module_oc_kit_seo_core_auto_generate_url` and no row exists yet.
     *
     * Runs once per visit. The next URL generation for this entity will
     * pick up the new slug automatically.
     */
    private function autoGenerateUrlIfMissing(string $entityType, int $entityId, int $languageId): void
    {
        $config = $this->registry->get('config');
        if (!(int)$config->get('module_oc_kit_seo_core_auto_generate_url')) return;

        try {
            $db          = $this->registry->get('db');
            $storeId     = (int)$config->get('config_store_id');
            $maskEngine  = new \OcKit\SeoCore\Libs\UrlMaskEngine($config);
            $cacheWarmer = new \OcKit\SeoCore\Libs\CacheWarmer($db, $this->registry->get('cache'));
            $regen       = new \OcKit\SeoCore\Libs\MaskRegenerator($db, $maskEngine, $cacheWarmer);

            $regen->regenerateOne($entityType, $entityId, $languageId, $storeId);
        } catch (\Throwable $e) {
            error_log('[oc_kit_seo_core] autoGenerateUrlIfMissing: ' . $e->getMessage());
        }
    }

    // ─── Admin: licensing ─────────────────────────────────────────────────────

    public static function guardAdmin($registry): void
    {
        new self($registry);
        // Constructor handles redirect; reaching here means licensed
    }

    /**
     * Single entry point for the common/header.php OCMOD patch.
     *
     * Handles: autoloader registration, status check, head-tag injection,
     * per-URL header rules, and returns the accumulated extras for the theme.
     *
     * Returns '' when the module is disabled or fails — the catalog page
     * never breaks because of SEO Core.
     */
    public static function renderForHeader($registry): string
    {
        $config = $registry->get('config');
        if (!$config->get('module_oc_kit_seo_core_status')) {
            return '';
        }

        // Make sure the autoloader is registered (defensive — header.php may run
        // before any other SCF code path that would have registered it).
        if (!class_exists(Autoloader::class, false)) {
            require_once __DIR__ . '/Autoloader.php';
        }
        Autoloader::register(__DIR__);

        try {
            $request = $registry->get('request');
            $route   = (string)($request->get['route'] ?? 'common/home');
            $langId  = (int)$config->get('config_language_id');

            $seoCore = new self($registry);
            $seoCore->injectHeadTags($route, $request->get, $langId);

            // Per-URL header rules (robots / CSP / X-Frame / etc.)
            $engine = new HeaderRuleEngine($registry->get('db'), $config);
            $engine->checkAndApply(
                $_SERVER['REQUEST_URI'] ?? '/',
                $route,
                (int)$config->get('config_store_id'),
                $registry->get('document')
            );

            return DocumentExtra::render();
        } catch (\Throwable $e) {
            error_log('[oc_kit_seo_core] renderForHeader: ' . $e->getMessage());
            return '';
        }
    }

    public static function getLicenseStatus($registry): array
    {
        return (new StoreContext($registry->get('db'), $registry->get('config')))->getInfo();
    }

    public static function activateLicenseKey($registry, string $key): array
    {
        return (new StoreContext($registry->get('db'), $registry->get('config')))->activate($key);
    }

    /**
     * Detect whether the running store has the ocStore-style `meta_h1` column
     * in `*_description` tables. Vanilla OpenCart 3.x does NOT have it; ocStore
     * and many derivatives do.
     *
     * Result is cached per request. Pass any one of: db, registry, or controller
     * with `->db` property.
     */
    public static function supportsNativeH1($source): bool
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        // Normalise to a DB-like object with ->query()
        if (is_object($source) && method_exists($source, 'get') && !method_exists($source, 'query')) {
            $db = $source->get('db');
        } elseif (is_object($source) && isset($source->db)) {
            $db = $source->db;
        } else {
            $db = $source;
        }

        if (!is_object($db) || !method_exists($db, 'query')) {
            return $cache = false;
        }

        try {
            $r = $db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'meta_h1'");
            $cache = $r->num_rows > 0;
        } catch (\Throwable $e) {
            $cache = false;
        }
        return $cache;
    }

    // ─── Public lib accessors (for admin controller direct access) ────────────

    /**
     * Build a SEO URL for the given route + params in the specified language.
     * Pure function: no config mutation, no URL-string parsing — feeds the
     * structured params straight into UrlGenerator::buildSeoUrl().
     *
     * @param  array<string,mixed> $params
     */
    public function urlFor(string $route, array $params, int $languageId): string
    {
        $config  = $this->registry->get('config');
        $storeId = (int)$config->get('config_store_id');

        $request = $this->registry->get('request');
        $scheme  = !empty($request->server['HTTPS']) && $request->server['HTTPS'] !== 'off' ? 'https' : 'http';
        $host    = $request->server['HTTP_HOST'] ?? '';

        // Home — root URL for default language, /{prefix}/ for the others.
        // UrlGenerator::buildSeoUrl returns null for common/home (no SEO row),
        // and its fallback `/index.php?route=common/home` is wrong for the
        // language switcher (kills the SEO URL on the home page).
        if ($route === 'common/home') {
            $prefix = $this->getLangConfig()->getPrefixById($languageId);
            $path   = $prefix !== '' ? '/' . $prefix . '/' : '/';
            return $scheme . '://' . $host . $path;
        }

        $path = $this->getUrlGenerator()->buildSeoUrl($route, $params, $storeId, $languageId);
        if ($path === null) {
            // Fallback: build a non-SEO link in the target language.
            $qs = $params ? '&' . http_build_query($params) : '';
            return $scheme . '://' . $host . '/index.php?route=' . $route . $qs;
        }
        return $scheme . '://' . $host . $path;
    }

    public function meta(): MetaRepository         { return $this->getMetaRepo(); }
    public function canonical(): CanonicalManager  { return $this->getCanonical(); }
    public function hreflang(): HreflangBuilder    { return $this->getHreflang(); }
    public function og(): OpenGraphRenderer        { return $this->getOg(); }
    public function schema(): SchemaBuilder        { return $this->getSchema(); }
    public function url(): UrlGenerator            { return $this->getUrlGenerator(); }

    // ─── Lazy getters ─────────────────────────────────────────────────────────

    private function getStoreContext(): StoreContext
    {
        if ($this->storeContext === null) {
            $this->storeContext = new StoreContext($this->registry->get('db'), $this->registry->get('config'));
        }
        return $this->storeContext;
    }

    private function getCacheWarmer(): CacheWarmer
    {
        if ($this->cacheWarmer === null) {
            $this->cacheWarmer = new CacheWarmer($this->registry->get('db'), $this->registry->get('cache'));
        }
        return $this->cacheWarmer;
    }

    private function getLangConfig(): LanguagePrefixConfig
    {
        if ($this->langConfig === null) {
            $this->langConfig = new LanguagePrefixConfig($this->registry->get('config'));
        }
        return $this->langConfig;
    }

    private function getRoutesConfig(): CustomRoutesConfig
    {
        if ($this->routesConfig === null) {
            $this->routesConfig = new CustomRoutesConfig($this->registry->get('config'));
        }
        return $this->routesConfig;
    }

    private function getUrlRouter(): UrlRouter
    {
        if ($this->urlRouter === null) {
            $this->urlRouter = new UrlRouter(
                $this->getCacheWarmer(), $this->getLangConfig(),
                $this->getRoutesConfig(), $this->registry->get('db'), $this->registry->get('config')
            );
        }
        return $this->urlRouter;
    }

    private function getUrlGenerator(): UrlGenerator
    {
        if ($this->urlGenerator === null) {
            $this->urlGenerator = new UrlGenerator(
                $this->getCacheWarmer(), $this->getLangConfig(),
                $this->registry->get('config'), $this->registry->get('db'),
                $this->getRoutesConfig()
            );
        }
        return $this->urlGenerator;
    }

    private function getUrlValidator(): UrlValidator
    {
        if ($this->urlValidator === null) {
            $this->urlValidator = new UrlValidator(
                $this->getUrlGenerator(), $this->getLangConfig(),
                $this->registry->get('config'), $this->registry->get('request'),
                $this->registry->get('response'), $this->registry->get('url'),
                $this->getRoutesConfig()
            );
        }
        return $this->urlValidator;
    }

    private function getMetaRepo(): MetaRepository
    {
        if ($this->metaRepo === null) {
            $this->metaRepo = new MetaRepository($this->registry->get('db'));
        }
        return $this->metaRepo;
    }

    private function getMetaEngine(): MetaTemplateEngine
    {
        if ($this->metaEngine === null) {
            $this->metaEngine = new MetaTemplateEngine(
                $this->getMetaRepo(), $this->registry->get('db'), $this->registry->get('config')
            );
            if ((int)$this->registry->get('config')->get('module_oc_kit_seo_core_ab_test_enabled')) {
                $this->metaEngine->setAbTestEngine($this->getAbEngine());
            }
        }
        return $this->metaEngine;
    }

    public function abTest(): AbTestEngine { return $this->getAbEngine(); }

    public function routeMeta(): RouteMetaRepository { return $this->getRouteMetaRepo(); }

    private function getRouteMetaRepo(): RouteMetaRepository
    {
        if ($this->routeMetaRepo === null) {
            $this->routeMetaRepo = new RouteMetaRepository($this->registry->get('db'));
        }
        return $this->routeMetaRepo;
    }

    private function getAbEngine(): AbTestEngine
    {
        if ($this->abEngine === null) {
            $this->abEngine = new AbTestEngine($this->registry->get('db'));
        }
        return $this->abEngine;
    }

    private function getPaginMeta(): PaginationMetaEngine
    {
        if ($this->paginMeta === null) {
            $this->paginMeta = new PaginationMetaEngine($this->registry->get('config'), $this->registry->get('db'));
        }
        return $this->paginMeta;
    }

    private function getCanonical(): CanonicalManager
    {
        if ($this->canonical === null) {
            $this->canonical = new CanonicalManager(
                $this->getMetaRepo(), $this->getUrlGenerator(),
                $this->registry->get('config'), $this->registry->get('request'),
                $this->registry->get('url'), $this->registry->get('response'),
                $this->registry->get('document')
            );
        }
        return $this->canonical;
    }

    private function getHreflang(): HreflangBuilder
    {
        if ($this->hreflang === null) {
            $this->hreflang = new HreflangBuilder(
                $this->getCacheWarmer(), $this->getLangConfig(),
                $this->registry->get('config'), $this->registry->get('db'),
                $this->registry->get('url'), $this->registry->get('document')
            );
        }
        return $this->hreflang;
    }

    private function getOg(): OpenGraphRenderer
    {
        if ($this->og === null) {
            $this->og = new OpenGraphRenderer(
                $this->getMetaEngine(), $this->registry->get('config'),
                $this->registry->get('db'), $this->registry->get('document')
            );
        }
        return $this->og;
    }

    private function getSchema(): SchemaBuilder
    {
        if ($this->schema === null) {
            $this->schema = new SchemaBuilder(
                $this->registry->get('config'), $this->registry->get('db'),
                $this->registry->get('document'), $this->registry->get('url')
            );
        }
        return $this->schema;
    }

    /** @var SchemaCustomEditor|null */
    private $schemaCustom = null;

    private function getSchemaCustom(): SchemaCustomEditor
    {
        if ($this->schemaCustom === null) {
            $this->schemaCustom = new SchemaCustomEditor(
                new SchemaTemplateEngine(),
                $this->registry->get('db'), $this->registry->get('config'),
                $this->registry->get('document')
            );
        }
        return $this->schemaCustom;
    }

    private function getRedirectManager(): RedirectManager
    {
        if ($this->redirectManager === null) {
            $this->redirectManager = new RedirectManager($this->registry->get('db'));
        }
        return $this->redirectManager;
    }

    private function getPaginGuard(): PaginationGuard
    {
        if ($this->paginGuard === null) {
            $this->paginGuard = new PaginationGuard(
                $this->registry->get('config'), $this->registry->get('response')
            );
        }
        return $this->paginGuard;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Phase 2: Resource hints (preload + dns-prefetch + preconnect).
     * Configured globally as JSON in module_oc_kit_seo_core_resource_hints:
     *   [{"rel":"preload","href":"/...","as":"font","type":"font/woff2","crossorigin":"anonymous"},
     *    {"rel":"dns-prefetch","href":"//fonts.googleapis.com"},
     *    {"rel":"preconnect","href":"https://cdn.example.com","crossorigin":""}]
     */
    private function injectResourceHints($config): void
    {
        $raw = (string)$config->get('module_oc_kit_seo_core_resource_hints');
        if ($raw === '') return;
        $raw = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
        $list = json_decode($raw, true);
        if (!is_array($list)) return;

        foreach ($list as $hint) {
            if (!is_array($hint) || empty($hint['href']) || empty($hint['rel'])) continue;
            DocumentExtra::addLink($hint);
        }
    }

    private function entityFromParams(string $route, array $params): array
    {
        switch ($route) {
            case 'product/product':           return ['product',      (int)($params['product_id']      ?? 0)];
            case 'product/category':
                $ids = explode('_', $params['path'] ?? '0');
                return ['category', (int)end($ids)];
            case 'product/manufacturer/info': return ['manufacturer', (int)($params['manufacturer_id'] ?? 0)];
            case 'information/information':   return ['information',  (int)($params['information_id']   ?? 0)];
            case 'blog/article':              return ['article',      (int)($params['article_id']      ?? 0)];
            case 'blog/category':             return ['blog_category',(int)($params['blog_category_id']?? 0)];
        }
        return ['', 0];
    }
}
