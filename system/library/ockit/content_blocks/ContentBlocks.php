<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks;

// ─── Autoload ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/exceptions/ContentBlocksException.php';
require_once __DIR__ . '/exceptions/BlockNotFoundException.php';
require_once __DIR__ . '/exceptions/TranslationException.php';
require_once __DIR__ . '/dto/JsonField.php';
require_once __DIR__ . '/dto/BlockDto.php';
require_once __DIR__ . '/dto/RowDto.php';
require_once __DIR__ . '/dto/ColDto.php';
require_once __DIR__ . '/dto/ElementDto.php';
require_once __DIR__ . '/libs/BlockRepository.php';
require_once __DIR__ . '/libs/ElementRepository.php';
require_once __DIR__ . '/libs/FormSubmissionRepository.php';
require_once __DIR__ . '/libs/BlockTypeRegistry.php';
require_once __DIR__ . '/libs/ThemeScanner.php';
require_once __DIR__ . '/libs/TemplateRepository.php';
require_once __DIR__ . '/libs/StylePresetRepository.php';
require_once __DIR__ . '/libs/AutocompleteService.php';
require_once __DIR__ . '/libs/TranslationService.php';
require_once __DIR__ . '/libs/StoreContext.php';

use OcKit\ContentBlocks\Libs\BlockRepository;
use OcKit\ContentBlocks\Libs\ElementRepository;
use OcKit\ContentBlocks\Libs\FormSubmissionRepository;
use OcKit\ContentBlocks\Libs\BlockTypeRegistry;
use OcKit\ContentBlocks\Libs\ThemeScanner;
use OcKit\ContentBlocks\Libs\TemplateRepository;
use OcKit\ContentBlocks\Libs\StylePresetRepository;
use OcKit\ContentBlocks\Libs\AutocompleteService;
use OcKit\ContentBlocks\Libs\TranslationService;

/**
 * Main facade for Content Blocks Pro.
 * Single entry point for all OpenCart controllers and models.
 *
 * Usage in controllers:
 *   require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
 *   $cb = new \OcKit\ContentBlocks\ContentBlocks($this->registry);
 *   $blocks = $cb->getBlocks('product/product', $productId);
 */
class ContentBlocks
{
    private $registry;

    private ?BlockRepository    $blockRepo    = null;
    private ?ElementRepository  $elementRepo  = null;
    private ?FormSubmissionRepository $formRepo = null;
    private ?BlockTypeRegistry  $typeRegistry = null;
    private ?ThemeScanner       $themeScanner = null;
    private ?TemplateRepository $templateRepo = null;
    private ?StylePresetRepository $presetRepo = null;
    private ?AutocompleteService   $autocomplete = null;
    private ?TranslationService    $translator   = null;

    private bool $licensed = false;

    public function __construct($registry)
    {
        $this->registry = $registry;

        // Check license once per request — redirect to license page if invalid.
        // Catalog (frontend) is allowed regardless to keep saved blocks rendering.
        $ctx = new \OcKit\ContentBlocks\Libs\StoreContext(
            $registry->get('db'),
            $registry->get('config')
        );
        $this->licensed = $ctx->isActive();

        if ($this->licensed || php_sapi_name() === 'cli') return;

        // License redirect only fires from admin entry points. Frontend
        // routes (product/*, information/*, etc.) and AJAX sub-controllers
        // are excluded here so they short-circuit without redirect side-
        // effects that would corrupt the host page or AJAX response.
        $route = (string)($registry->get('request')->get['route'] ?? '');

        // Explicit admin allowlist — only these top-level pages may redirect.
        $adminEntryRoutes = [
            'extension/module/oc_kit_content_blocks',
            'catalog/product',
            'catalog/category',
            'catalog/manufacturer',
            'catalog/information',
            'design/layout',
        ];
        $isAdminEntry = false;
        foreach ($adminEntryRoutes as $prefix) {
            if (strpos($route, $prefix) === 0) {
                // Sub-routes of our own module that aren't license-page must skip.
                if ($prefix === 'extension/module/oc_kit_content_blocks'
                    && (strpos($route, 'oc_kit_content_blocks/license') !== false
                     || strpos($route, 'oc_kit_content_blocks/activateLicense') !== false)
                ) {
                    return;
                }
                // OCMOD-injected sub-controllers must never redirect — would
                // break host pages or AJAX responses.
                $subRoutes = ['form','save','setting_save','autocomplete','upload','block','element','duplicate','templates','translate','video','render','global','demo_page'];
                foreach ($subRoutes as $sub) {
                    if (strpos($route, 'oc_kit_content_blocks/' . $sub) !== false) {
                        return;
                    }
                }
                $isAdminEntry = true;
                break;
            }
        }
        if (!$isAdminEntry) return;

        $token = $registry->get('session')->data['user_token'] ?? '';
        if ($token === '') return;

        $registry->get('response')->redirect(
            $registry->get('url')->link(
                'extension/module/oc_kit_content_blocks/license',
                'user_token=' . $token, true
            )
        );
        exit;
    }

    // ─── Blocks ──────────────────────────────────────────────────────────────

    /**
     * Returns all active blocks for a page.
     *
     * @return \OcKit\ContentBlocks\Dto\BlockDto[]
     */
    public function getBlocks(string $pageRoute, int $pageId): array
    {
        return $this->getBlockRepo()->getBlocks($pageRoute, $pageId);
    }

    /**
     * Returns a single block by ID.
     */
    /** @return \OcKit\ContentBlocks\Dto\BlockDto[] */
    public function getGlobalBlocks(): array
    {
        return $this->getBlockRepo()->getGlobalBlocks();
    }

    public function getBlock(int $blockId): \OcKit\ContentBlocks\Dto\BlockDto
    {
        return $this->getBlockRepo()->getBlock($blockId);
    }

    /**
     * Saves all blocks for a page from raw POST data.
     *
     * @param array $data {page_route, page_id, blocks: [...]}
     * @return int[] Ordered list of saved block IDs
     */
    public function saveBlocks(array $data): array
    {
        $pageRoute = (string)($data['page_route'] ?? '');
        $pageId    = (int)($data['page_id'] ?? 0);
        $blocks    = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        // Capture pre-save IDs so we also invalidate caches for blocks the
        // admin removed in this request (their IDs disappear from $ids).
        $preIds = $this->getBlockRepo()->getBlockIdsForPage($pageRoute, $pageId);
        $ids = $this->getBlockRepo()->saveBlocks($pageRoute, $pageId, $blocks);
        $this->bumpRenderCacheVersion(array_unique(array_merge($preIds, $ids)));
        return $ids;
    }

    /**
     * Duplicates a block. Returns new block_id.
     */
    public function duplicateBlock(int $blockId): int
    {
        $newId = $this->getBlockRepo()->duplicateBlock($blockId);
        $this->bumpRenderCacheVersion([$blockId, $newId]);
        return $newId;
    }

    /**
     * Deletes a single block.
     */
    public function deleteBlock(int $blockId): void
    {
        $this->getBlockRepo()->deleteBlock($blockId);
        $this->bumpRenderCacheVersion([$blockId]);
    }

    /**
     * Deletes all blocks for a page (call from entity delete hooks).
     */
    public function removePageBlocks(string $pageRoute, int $pageId): void
    {
        $preIds = $this->getBlockRepo()->getBlockIdsForPage($pageRoute, $pageId);
        $this->getBlockRepo()->deleteBlocksByPage($pageRoute, $pageId);
        $this->bumpRenderCacheVersion($preIds);
    }

    /**
     * Bumps render-cache versions so the next frontend render rebuilds HTML.
     * When $blockIds is null we bump the GLOBAL version (rare — schema/types
     * change); otherwise we bump per-block versions, leaving caches for other
     * blocks intact. The catalog controller picks max(global, block) into
     * its cache key, so either path invalidates the affected entries.
     */
    public function bumpRenderCacheVersion(?array $blockIds = null): void
    {
        $cache = $this->registry->get('cache');
        if (!$cache) return;

        $now = time();
        if ($blockIds === null) {
            $cache->set('cb_render_version', $now);
            return;
        }
        foreach ($blockIds as $bid) {
            $bid = (int)$bid;
            if ($bid > 0) {
                $cache->set('cb_render_version_block_' . $bid, $now);
            }
        }
    }

    /**
     * Builds an {key: localised_string} map for a list of language keys.
     * Used by every admin controller that ships i18n strings to its JS —
     * single source of truth, replaces 5 copies of buildI18n().
     *
     * @param object $language OC language helper ($this->language)
     * @param string[] $keys
     */
    public static function buildI18n($language, array $keys): array
    {
        $i18n = [];
        foreach ($keys as $key) {
            $i18n[$key] = $language->get($key);
        }
        return $i18n;
    }

    /**
     * Writes a JSON payload to an OC response — single canonical helper so
     * every admin controller emits identical headers/encoding. Replaces the
     * trio of $this->respond() / $this->jsonResponse() / inline addHeader+
     * setOutput sprinkled across our controllers.
     */
    public static function json($response, array $payload): void
    {
        $response->addHeader('Content-Type: application/json');
        $response->setOutput(json_encode($payload));
    }

    // ─── Block Types ─────────────────────────────────────────────────────────

    /**
     * Returns all block type definitions (i18n keys, params schema, etc.).
     * Filtered by enabled types from module settings.
     */
    public function getTypes(?array $enabledTypes = null): array
    {
        $types = $this->getTypeRegistry()->getTypes();

        // Merge in themes from FS
        $themes = $this->getThemeScanner()->getAllThemes();
        foreach ($types as $key => &$type) {
            $type['themes'] = $themes[$key] ?? [['name' => 'default', 'preview' => false]];
        }
        unset($type);

        // Filter by enabled settings
        if ($enabledTypes !== null) {
            foreach ($types as $key => $def) {
                if (empty($enabledTypes[$key]['status'])) {
                    unset($types[$key]);
                }
            }
        }

        return $types;
    }

    /**
     * Returns element type definitions (text, image, html, video).
     */
    public function getElementTypes(): array
    {
        return $this->getTypeRegistry()->getElementTypes();
    }

    // ─── Templates ───────────────────────────────────────────────────────────

    public function getTemplates(string $blockType = ''): array
    {
        return $this->getTemplateRepo()->getTemplates($blockType);
    }

    public function getTemplate(int $templateId): ?array
    {
        return $this->getTemplateRepo()->getTemplate($templateId);
    }

    public function saveTemplate(string $name, string $blockType, array $data): int
    {
        return $this->getTemplateRepo()->saveTemplate($name, $blockType, $data);
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->getTemplateRepo()->deleteTemplate($templateId);
    }

    // ─── Style Presets ───────────────────────────────────────────────────────

    public function getPresets(): array
    {
        return $this->getPresetRepo()->getPresets();
    }

    public function savePreset(int $presetId, string $name, string $classes, int $sortOrder = 0, string $group = ''): int
    {
        return $this->getPresetRepo()->savePreset($presetId, $name, $classes, $sortOrder, $group);
    }

    public function deletePreset(int $presetId): void
    {
        $this->getPresetRepo()->deletePreset($presetId);
    }

    public function resetPresets(): void
    {
        $this->getPresetRepo()->resetPresets();
    }

    // ─── Autocomplete ────────────────────────────────────────────────────────

    public function searchProducts(string $query, int $limit = 20): array
    {
        return $this->getAutocomplete()->searchProducts($query, $limit);
    }

    public function searchCategories(string $query, int $limit = 20): array
    {
        return $this->getAutocomplete()->searchCategories($query, $limit);
    }

    public function searchArticles(string $query, string $blogType = 'default', int $limit = 20): array
    {
        return $this->getAutocomplete()->searchArticles($query, $blogType, $limit);
    }

    // ─── Translation ─────────────────────────────────────────────────────────

    /**
     * Translates text content in a block via OpenAI.
     *
     * @throws \OcKit\ContentBlocks\Exceptions\TranslationException
     */
    public function translateBlock(array $blockData, string $targetLang, int $targetLangId, string $sourceLang = '', int $sourceLangId = 0): array
    {
        return $this->getTranslator()->translateBlock($blockData, $targetLang, $targetLangId, $sourceLang, $sourceLangId);
    }

    // ─── Install / Uninstall ─────────────────────────────────────────────────

    public function install(): void
    {
        $this->getBlockRepo()->createTables();
        $this->getFormRepo()->createTables();
        $this->getPresetRepo()->insertDefaultsIfEmpty();
    }

    public function uninstall(): void
    {
        $this->getBlockRepo()->dropTables();
        $this->getFormRepo()->dropTables();
    }

    // ─── License (static — called without instantiation to avoid redirect loop) ─

    public static function getLicenseStatus($registry): array
    {
        $ctx = new \OcKit\ContentBlocks\Libs\StoreContext(
            $registry->get('db'),
            $registry->get('config')
        );
        return $ctx->getInfo();
    }

    public static function activateLicenseKey($registry, string $key): array
    {
        $ctx = new \OcKit\ContentBlocks\Libs\StoreContext(
            $registry->get('db'),
            $registry->get('config')
        );
        return $ctx->activate($key);
    }

    /**
     * Soft check — returns true if licensed. Use in OCMOD-injected sub-controllers
     * (product/category/info edit pages) where redirect would break the host page.
     */
    public static function isLicensed($registry): bool
    {
        $ctx = new \OcKit\ContentBlocks\Libs\StoreContext(
            $registry->get('db'),
            $registry->get('config')
        );
        return $ctx->isActive();
    }

    // ─── Lazy getters ────────────────────────────────────────────────────────

    public function getBlockRepo(): BlockRepository
    {
        if ($this->blockRepo === null) {
            $this->blockRepo = new BlockRepository($this->registry->get('db'));
        }
        return $this->blockRepo;
    }

    public function getFormRepo(): FormSubmissionRepository
    {
        if ($this->formRepo === null) {
            $this->formRepo = new FormSubmissionRepository($this->registry->get('db'));
        }
        return $this->formRepo;
    }

    public function getElementRepo(): ElementRepository
    {
        if ($this->elementRepo === null) {
            $this->elementRepo = new ElementRepository($this->registry->get('db'));
        }
        return $this->elementRepo;
    }

    public function getTypeRegistry(): BlockTypeRegistry
    {
        if ($this->typeRegistry === null) {
            $this->typeRegistry = new BlockTypeRegistry();
        }
        return $this->typeRegistry;
    }

    public function getThemeScanner(): ThemeScanner
    {
        if ($this->themeScanner === null) {
            $config     = $this->registry->get('config');
            $themeName  = $config->get('config_theme') ?: 'default';
            $catalogDir = defined('DIR_CATALOG') ? DIR_CATALOG : '';
            $catalogUrl = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTPS_CATALOG') ? HTTPS_CATALOG : '');
            $this->themeScanner = new ThemeScanner($catalogDir, $catalogUrl, $themeName);
        }
        return $this->themeScanner;
    }

    public function getTemplateRepo(): TemplateRepository
    {
        if ($this->templateRepo === null) {
            $this->templateRepo = new TemplateRepository($this->registry->get('db'));
        }
        return $this->templateRepo;
    }

    public function getPresetRepo(): StylePresetRepository
    {
        if ($this->presetRepo === null) {
            $this->presetRepo = new StylePresetRepository($this->registry->get('db'));
        }
        return $this->presetRepo;
    }

    public function getAutocomplete(): AutocompleteService
    {
        if ($this->autocomplete === null) {
            $config     = $this->registry->get('config');
            $languageId = (int)$config->get('config_language_id');
            $this->autocomplete = new AutocompleteService($this->registry->get('db'), $languageId);
        }
        return $this->autocomplete;
    }

    public function getTranslator(): TranslationService
    {
        if ($this->translator === null) {
            $config  = $this->registry->get('config');
            $apiKey  = (string)$config->get('module_oc_kit_content_blocks_openai_key');
            $this->translator = new TranslationService($apiKey);
        }
        return $this->translator;
    }
}
