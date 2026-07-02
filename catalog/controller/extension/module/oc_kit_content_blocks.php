<?php
/**
 * Content Blocks Pro — Catalog Controller
 * Renders blocks via shortcodes [cb block_id=X] or direct output.
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocks extends Controller
{
    /** @var string Active device: 'mobile'|'tablet'|'desktop' */
    private string $device = 'desktop';

    /** Per-request cache for model_tool_image->resize() — same source+dims hits cache */
    private array $resizeCache = [];

    private function cachedResize(string $path, int $width, int $height): string
    {
        if ($path === '' || $width <= 0 || $height <= 0) {
            return '';
        }
        $key = $path . '|' . $width . 'x' . $height;
        if (!isset($this->resizeCache[$key])) {
            $this->resizeCache[$key] = (string)$this->model_tool_image->resize($path, $width, $height);
        }
        return $this->resizeCache[$key];
    }

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->detectDevice();
    }

    // ─── Public API: render all blocks for a page ────────────────────────────

    /**
     * Render all blocks assigned to a page route + page id.
     * Called from OCMOD hooks in product/category/information/manufacturer templates.
     */
    public function renderPage(string $pageRoute, int $pageId): string
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        $status = $this->config->get('module_oc_kit_content_blocks_status');
        if (!$status) {
            return '';
        }

        $this->load->model('extension/module/oc_kit_content_blocks');
        $blocks = $this->model_extension_module_oc_kit_content_blocks->getBlocks($pageRoute, $pageId);

        if (empty($blocks)) {
            return '';
        }

        $out = '';
        foreach ($blocks as $block) {
            // Admin-disabled blocks must never reach the storefront.
            $blockArr = is_array($block) ? $block : (array)$block;
            if (isset($blockArr['status']) && (int)$blockArr['status'] !== 1) {
                continue;
            }
            $out .= $this->renderBlock($blockArr);
        }
        return $out;
    }

    /**
     * Render a single block by ID (used for shortcode [cb block_id=X]).
     *
     * @param bool $globalOnly  If true, only renders blocks with is_global=1.
     */
    public function renderById(int $blockId, bool $globalOnly = false): string
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        $this->load->model('extension/module/oc_kit_content_blocks');

        try {
            $block = $this->model_extension_module_oc_kit_content_blocks->getBlock($blockId);
        } catch (\OcKit\ContentBlocks\Exceptions\BlockNotFoundException $e) {
            return '';
        }

        if (!$block) {
            return '';
        }

        // Catalog model returns array; admin DTO has same shape via json_decode
        $blockArr = is_array($block) ? $block : (array)$block;

        // Admin-disabled blocks must never render, even when embedded via shortcode.
        if (isset($blockArr['status']) && (int)$blockArr['status'] !== 1) {
            return '';
        }

        if ($globalOnly && empty($blockArr['is_global'])) {
            return '';
        }

        return $this->renderBlock($blockArr);
    }

    // ─── Module shortcode entry point ────────────────────────────────────────

    /**
     * Called as a module instance in OC layout pages.
     */
    public function index(): string
    {
        $setting = $this->config->get('module_oc_kit_content_blocks');
        if (empty($setting['module_oc_kit_content_blocks_status'])) {
            return '';
        }

        // When called as layout module, we need block_id from module setting
        $blockId = (int)($setting['block_id'] ?? 0);
        if (!$blockId) {
            return '';
        }

        return $this->renderById($blockId);
    }

    // ─── Render a single block ───────────────────────────────────────────────

    private function renderBlock(array $block): string
    {
        $blockType = $block['block_type'] ?? '';
        $theme     = $block['theme'] ?? 'default';

        // Theme-level defaults (theme.json sitting next to theme.twig).
        // Block-saved params win — theme defaults only fill missing keys.
        $themeConfig = $this->loadThemeConfig($blockType, $theme);
        $params      = ($block['params'] ?? []) + $themeConfig;

        // Device visibility
        $hideOn = $params['hide_on'] ?? [];
        if (!empty($hideOn[$this->device])) {
            return '';
        }

        $languageId = (int)$this->config->get('config_language_id');

        // Build inline styles from params
        $inlineStyle = $this->buildInlineStyle($params);
        $cssClasses  = $this->buildCssClasses($params);

        // Frontend i18n
        $this->load->language('extension/module/oc_kit_content_blocks');
        $i18nKeys = [
            'button_buy', 'button_buy_now', 'button_out_of_stock', 'button_view_all',
            'button_read_more', 'button_view',
            'text_reviews', 'text_review', 'text_products_count', 'text_in_stock',
            'text_blog', 'text_main_article', 'text_pluses', 'text_minuses', 'text_specs',
            'sticker_top', 'sticker_top_sales', 'sticker_hit', 'sticker_new', 'sticker_sale',
        ];
        $t = [];
        foreach ($i18nKeys as $k) $t[$k] = $this->language->get($k);

        // Prepare data for template
        $data = [
            'block'        => $block,
            'block_id'     => $block['block_id'],
            'block_type'   => $blockType,
            'theme'        => $theme,
            'params'       => $params,
            'theme_config' => $themeConfig,
            'language_id'  => $languageId,
            'inline_style' => $inlineStyle,
            'css_classes'  => $cssClasses,
            'device'       => $this->device,
            't'            => $t,
        ];

        // Per-request asset registry — OC's document doesn't dedup, so on
        // pages with multiple blocks of the same type we'd add the same
        // <link>/<script> 5+ times. Track what's been added and skip dupes.
        static $loadedAssets = [];
        $addAssetOnce = function (string $key, callable $loader) use (&$loadedAssets) {
            if (isset($loadedAssets[$key])) return;
            $loadedAssets[$key] = true;
            $loader();
        };

        $addAssetOnce('cb-base', function () {
            $this->document->addStyle('catalog/view/javascript/ockit/content_blocks/css/cb-base.css');
        });

        // Load theme-specific CSS if present (next to the theme.twig)
        $themeCssRel = 'catalog/view/theme/default/template/oc_kit_content_blocks/'
            . $blockType . '/' . $theme . '/theme.css';
        if (file_exists(DIR_APPLICATION . '../' . $themeCssRel)) {
            $addAssetOnce('theme:' . $blockType . '/' . $theme, function () use ($themeCssRel) {
                $this->document->addStyle($themeCssRel);
            });
        }

        // Swiper for carousel-type blocks
        if (in_array($blockType, ['products_carousel', 'images_carousel'], true) ||
            ($blockType === 'reviews' && $theme === 'slider')) {
            $addAssetOnce('swiper', function () {
                $this->document->addStyle('catalog/view/javascript/ockit/content_blocks/swiper/swiper.min.css');
                $this->document->addScript('catalog/view/javascript/ockit/content_blocks/swiper/swiper.min.js');
            });
        }

        // PlayerJS for video block (used by `playerjs` theme)
        if ($blockType === 'video' && $theme === 'playerjs') {
            $addAssetOnce('playerjs', function () {
                $this->document->addScript('catalog/view/javascript/ockit/content_blocks/playerjs/playerjs.js');
            });
        }

        // Form submission script — needed whenever a form element appears anywhere
        // in the block (form is an element type, not a block type).
        if ($this->blockHasFormElement($block)) {
            $addAssetOnce('cb-form-js', function () {
                $this->document->addScript('catalog/view/javascript/ockit/content_blocks/js/cb-form.js');
            });
        }

        // Load GLightbox if popup is enabled for this block
        if (!empty($params['popup_enable'])) {
            $addAssetOnce('glightbox', function () {
                $this->document->addStyle('catalog/view/javascript/ockit/content-blocks/glightbox/glightbox.min.css');
                $this->document->addScript('catalog/view/javascript/ockit/content-blocks/glightbox/glightbox.min.js');
            });
        }

        // Load FAQ styles
        if ($blockType === 'faq') {
            $addAssetOnce('faq-css', function () {
                $this->document->addStyle('catalog/view/theme/default/assets/css/modules/_cb_faq.min.css');
            });
        }

        // Render cache: per-block versioned so editing one block doesn't churn
        // every other block's cache. Currency/store/device are part of the key
        // so price-formatted output stays correct. We check the cache *after*
        // assets are registered (assets must run every request), and *before*
        // the heavy enrichBlockData + custom hook + Twig render. Cache hit
        // short-circuits all of that. Gated by the admin enable_cache toggle —
        // when disabled, both get and set are skipped so settings/code edits
        // are visible immediately during development.
        $cacheEnabled = (bool)$this->config->get('module_oc_kit_content_blocks_enable_cache');
        $blockId      = (int)($block['block_id'] ?? 0);
        $cacheKey     = '';
        if ($cacheEnabled) {
            $version  = $this->getRenderCacheVersion($blockId);
            $cacheKey = 'cb_render.' . $version
                . '.' . $blockId
                . '.' . $languageId
                . '.' . (int)$this->config->get('config_store_id')
                . '.' . ($this->session->data['currency'] ?? $this->config->get('config_currency'))
                . '.' . $this->device;
            $cachedHtml = $this->cache->get($cacheKey);
            if (is_string($cachedHtml) && $cachedHtml !== '') {
                return $cachedHtml;
            }
        }

        // Enrich data by block type
        $data = array_merge($data, $this->enrichBlockData($block, $languageId));

        // Custom hook — theme-overridable PHP file that may mutate $data.
        // Lookup order:
        //   theme/{config_theme}/template/oc_kit_content_blocks/custom/{blockType}.php
        //   theme/{config_theme}/template/oc_kit_content_blocks/custom.php
        //   theme/default/template/oc_kit_content_blocks/custom/{blockType}.php
        //   theme/default/template/oc_kit_content_blocks/custom.php
        $themeName = $this->config->get('config_theme') ?: 'default';
        // Whitelist theme + blockType to prevent path traversal in hook lookup.
        $safeTheme     = preg_match('/^[a-z0-9_-]+$/i', $themeName) ? $themeName : 'default';
        $safeBlockType = preg_match('/^[a-z0-9_-]+$/i', $blockType) ? $blockType : '';
        $hookCandidates = [];
        if ($safeBlockType !== '') {
            $hookCandidates[] = DIR_TEMPLATE . $safeTheme . '/template/oc_kit_content_blocks/custom/' . $safeBlockType . '.php';
        }
        $hookCandidates[] = DIR_TEMPLATE . $safeTheme . '/template/oc_kit_content_blocks/custom.php';
        if ($safeBlockType !== '') {
            $hookCandidates[] = DIR_TEMPLATE . 'default/template/oc_kit_content_blocks/custom/' . $safeBlockType . '.php';
        }
        $hookCandidates[] = DIR_TEMPLATE . 'default/template/oc_kit_content_blocks/custom.php';
        foreach ($hookCandidates as $hookFile) {
            if (is_file($hookFile)) {
                try {
                    include $hookFile;
                } catch (\Throwable $e) {
                    $this->log->write('Content Blocks custom hook error in '
                        . $hookFile . ': ' . $e->getMessage()
                        . ' @ ' . $e->getFile() . ':' . $e->getLine());
                }
                break;
            }
        }

        // Find theme template
        $tplPath = $this->resolveTemplate($blockType, $theme);
        if (!$tplPath) {
            return '';
        }

        $html = $this->load->view($tplPath, $data);
        if ($cacheEnabled && is_string($html) && $html !== '') {
            $this->cache->set($cacheKey, $html);
        }
        return $html;
    }

    /**
     * Returns max(global render version, per-block version). ContentBlocks
     * bumps either: per-block on save/delete/duplicate (default — leaves other
     * blocks' caches intact), global on rare schema/types changes.
     */
    private function getRenderCacheVersion(int $blockId): int
    {
        $global = (int)$this->cache->get('cb_render_version');
        if ($global <= 0) {
            $global = time();
            $this->cache->set('cb_render_version', $global);
        }
        $perBlock = $blockId > 0 ? (int)$this->cache->get('cb_render_version_block_' . $blockId) : 0;
        return $perBlock > $global ? $perBlock : $global;
    }

    // ─── Template resolution ─────────────────────────────────────────────────

    private function resolveTemplate(string $blockType, string $theme): string
    {
        // Whitelist: only [a-z0-9_-]+ — prevents path traversal via DB-stored values.
        if (!preg_match('/^[a-z0-9_-]+$/i', $blockType) || !preg_match('/^[a-z0-9_-]+$/i', $theme)) {
            return '';
        }

        // Try: catalog/view/theme/{theme}/template/oc_kit_content_blocks/{type}/{theme}/theme.twig
        $themeName = $this->config->get('config_theme') ?: 'default';

        $candidates = [
            'oc_kit_content_blocks/' . $blockType . '/' . $theme . '/theme',
            'oc_kit_content_blocks/' . $blockType . '/default/theme',
        ];

        foreach ($candidates as $candidate) {
            // OC3 load->view checks theme path automatically
            // Use file_exists to check
            $paths = [
                DIR_TEMPLATE . $themeName . '/template/' . $candidate . '.twig',
                DIR_TEMPLATE . 'default/template/' . $candidate . '.twig',
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    // ─── Data enrichment by block type ──────────────────────────────────────

    private function enrichBlockData(array $block, int $languageId): array
    {
        $extra = [];
        $this->load->model('tool/image');

        $blockType = $block['block_type'] ?? '';
        $theme     = $block['theme'] ?? 'default';

        switch ($blockType) {
            case 'grid':
            case 'accordion':
                $extra['rows'] = $this->enrichRows($block['rows'] ?? [], $languageId);
                break;

            case 'faq':
            case 'reviews':
                $extra['items'] = $this->enrichElements($block['elements'] ?? [], $languageId);
                break;

            case 'products_carousel':
            case 'product':
                [$pw, $ph] = $this->getTypeImgSize($blockType, 300, 300, $theme);
                $extra['products'] = $this->enrichProductElements($block['elements'] ?? [], $languageId, $block['params'] ?? [], $pw, $ph);
                break;

            case 'images_carousel':
                [$iw, $ih] = $this->getTypeImgSize('images_carousel', 600, 400, $theme);
                $extra['images'] = $this->enrichElements($block['elements'] ?? [], $languageId, $iw, $ih);
                break;

            case 'categories':
                [$cw, $ch] = $this->getTypeImgSize('categories', 300, 200, $theme);
                $extra['categories'] = $this->enrichCategoryElements($block['elements'] ?? [], $languageId, $cw, $ch);
                break;

            case 'blog_article':
                [$aw, $ah] = $this->getTypeImgSize('blog_article', 400, 250, $theme);
                $extra['articles']   = $this->enrichArticleElements($block['elements'] ?? [], $languageId, $aw, $ah);
                $extra['blog_error'] = $this->blogError;
                break;

            case 'video':
                $extra['videos'] = $this->enrichElements($block['elements'] ?? [], $languageId);
                break;
        }

        return $extra;
    }

    private function enrichRows(array $rows, int $languageId): array
    {
        foreach ($rows as &$row) {
            foreach ($row['cols'] as &$col) {
                $col['elements_data'] = $this->enrichElements($col['elements'] ?? [], $languageId);
                $col['css_classes']   = $this->buildCssClasses($col['params'] ?? []);
                $col['inline_style']  = $this->buildInlineStyle($col['params'] ?? []);
                // Pre-resolved title string for the current language (accordion col titles etc).
                // Twig templates can use {{ col.title }} without |default fallbacks.
                $col['title']         = $this->pickLangValue($col['params']['title'] ?? null, $languageId);
                // Bootstrap col class
                $width = (int)($col['width'] ?? 0);
                $col['bs_class'] = $width > 0 ? 'col-' . $width : 'col';
            }
            unset($col);
            $row['css_classes']  = $this->buildCssClasses($row['params'] ?? []);
            $row['inline_style'] = $this->buildInlineStyle($row['params'] ?? []);
        }
        unset($row);
        return $rows;
    }

    /**
     * Resolves a possibly per-language scalar to a plain string for the current
     * language, with fallback to the first non-empty entry. Lets templates avoid
     * |default(…) chains by always receiving a ready string.
     */
    private function pickLangValue($value, int $languageId): string
    {
        if (is_array($value)) {
            if (isset($value[$languageId])) return (string)$value[$languageId];
            $first = reset($value);
            return $first !== false ? (string)$first : '';
        }
        return $value !== null ? (string)$value : '';
    }

    /**
     * Pick lang-data for an element with sensible fallback chain:
     *   current language → store default language → any other non-empty record.
     * Avoids reset()-based fallback which silently grabs the first array entry
     * (often uk-ua) regardless of the store's default language.
     */
    private function pickLangData(array $perLang, int $languageId): array
    {
        if (!empty($perLang[$languageId]) && is_array($perLang[$languageId])) {
            return $perLang[$languageId];
        }
        $defaultLangId = (int)$this->config->get('config_language_id');
        if ($defaultLangId && $defaultLangId !== $languageId
            && !empty($perLang[$defaultLangId]) && is_array($perLang[$defaultLangId])) {
            return $perLang[$defaultLangId];
        }
        foreach ($perLang as $entry) {
            if (is_array($entry) && $entry !== []) return $entry;
        }
        return [];
    }

    /**
     * True if the block contains at least one element with element_type='form',
     * scanning both rows→cols→elements and flat elements arrays.
     */
    private function blockHasFormElement(array $block): bool
    {
        foreach (($block['rows'] ?? []) as $row) {
            foreach (($row['cols'] ?? []) as $col) {
                foreach (($col['elements'] ?? []) as $el) {
                    if (($el['element_type'] ?? '') === 'form') return true;
                }
            }
        }
        foreach (($block['elements'] ?? []) as $el) {
            if (($el['element_type'] ?? '') === 'form') return true;
        }
        return false;
    }

    /**
     * Form element: pre-resolves multilang per-form strings (subject/submit_label/success_message)
     * and per-field label/placeholder/options into the current language, and injects submit_url.
     */
    private function enrichFormElementParams(array $params, int $languageId): array
    {
        $perLang = is_array($params['lang'] ?? null) ? $params['lang'] : [];
        $ld      = $this->pickLangData($perLang, $languageId);

        $params['subject']         = (string)($ld['subject']         ?? '');
        $params['submit_label']    = (string)($ld['submit_label']    ?? '');
        $params['success_message'] = (string)($ld['success_message'] ?? '');

        // Resolve global accept defaults once per call
        $acceptFile  = (string)$this->config->get('module_oc_kit_content_blocks_form_accept_file');
        $acceptImage = (string)$this->config->get('module_oc_kit_content_blocks_form_accept_image');
        if ($acceptImage === '') $acceptImage = 'image/*';

        $fields = [];
        foreach (($params['fields'] ?? []) as $f) {
            $fLang = is_array($f['lang'] ?? null) ? $f['lang'] : [];
            $fld   = $this->pickLangData($fLang, $languageId);
            $type  = (string)($f['type'] ?? 'text');
            $accept = '';
            if ($type === 'file')  $accept = $acceptFile;
            if ($type === 'image') $accept = $acceptImage;
            $fields[] = [
                'type'        => $type,
                'name'        => (string)($f['name']     ?? ''),
                'required'    => (int)($f['required']    ?? 0),
                'accept'      => $accept,
                'label'       => (string)($fld['label']       ?? ''),
                'placeholder' => (string)($fld['placeholder'] ?? ''),
                'options'     => (string)($fld['options']     ?? ''),
            ];
        }
        $params['fields']        = $fields;
        $params['submit_url']    = $this->url->link('extension/module/oc_kit_content_blocks/form_submit', '', true);
        $params['max_file_size'] = (int)($this->config->get('module_oc_kit_content_blocks_form_max_size') ?: 5120);

        // Drop heavy nested structures the template doesn't need
        unset($params['lang']);
        return $params;
    }

    private function enrichElements(array $elements, int $languageId, int $defaultImgW = 0, int $defaultImgH = 0): array
    {
        $result = [];
        foreach ($elements as $el) {
            $perLang  = is_array($el['data'] ?? null) ? $el['data'] : [];
            $langData = $this->pickLangData($perLang, $languageId);

            // Per-field fallback: if current language has empty image/alt/url/W/H,
            // take it from the first language that has a value.
            if ($el['element_type'] === 'image' || $el['element_type'] === 'carousel_image') {
                foreach (['image', 'alt', 'title', 'url', 'width', 'height'] as $f) {
                    if (!empty($langData[$f])) continue;
                    foreach ($perLang as $other) {
                        if (!empty($other[$f])) { $langData[$f] = $other[$f]; break; }
                    }
                }
            }

            $item = [
                'element_id'   => $el['element_id'],
                'element_type' => $el['element_type'],
                'params'       => $el['params'] ?? [],
                'css_classes'  => $this->buildCssClasses($el['params'] ?? []),
                'inline_style' => $this->buildInlineStyle($el['params'] ?? []),
                'data'         => $langData,
            ];

            // Form element: flatten per-lang strings + per-field labels into the current language
            // so the template can render without lang lookups. Also inject submit_url.
            if ($el['element_type'] === 'form') {
                $item['params'] = $this->enrichFormElementParams($item['params'], $languageId);
            }

            if (!empty($langData['image'])) {
                // Per-element override → otherwise block-type fallback (settings).
                $w = (int)($langData['width']  ?? 0) ?: $defaultImgW;
                $h = (int)($langData['height'] ?? 0) ?: $defaultImgH;
                $item['image_resized'] = $this->cachedResize($langData['image'], $w, $h);
            }

            // Video: pre-resolved src + poster so templates need no |default chains.
            if ($el['element_type'] === 'video' || $el['element_type'] === 'carousel_video') {
                $item['video_src']    = (string)($langData['local']  ?? $langData['url']   ?? '');
                $item['video_poster'] = (string)($langData['poster'] ?? $langData['thumb'] ?? '');
            }

            // Review author: pre-resolved uppercase initial (fallback '?').
            $author = (string)($el['params']['author'] ?? '');
            $item['author_initial'] = $author !== '' ? mb_strtoupper(mb_substr($author, 0, 1)) : '?';

            $result[] = $item;
        }
        return $result;
    }

    private function enrichProductElements(array $elements, int $languageId, array $blockParams = [], int $imgW = 300, int $imgH = 300): array
    {
        $popupEnable = !empty($blockParams['popup_enable']);
        $popupW      = (int)($blockParams['popup_img_w'] ?? 800);
        $popupH      = (int)($blockParams['popup_img_h'] ?? 800);

        // Block-level "override" toggles — let element data overwrite product fields.
        $imgOverride  = !empty($blockParams['img_override']);
        $nameOverride = !empty($blockParams['name_override']);
        $descOverride = !empty($blockParams['description_override']);
        $featDis      = !empty($blockParams['features_disadvantages']);
        $showAttrs    = !empty($blockParams['show_attributes']);
        $attrsCount   = (int)($blockParams['attributes_count'] ?? 0);
        $addImgs      = !empty($blockParams['additional_images']);
        $addImgsCount = (int)($blockParams['additional_images_count'] ?? 4);
        $addImgW      = (int)($blockParams['additional_img_w'] ?? 80) ?: 80;
        $addImgH      = (int)($blockParams['additional_img_h'] ?? 80) ?: 80;
        $cartAddFn    = trim((string)($blockParams['cart_add_fn'] ?? ''));

        if ($showAttrs || $addImgs) {
            $this->load->model('catalog/product');
        }

        // Collect IDs in display order, then batch-fetch in one query.
        $ids = [];
        foreach ($elements as $el) {
            $pid = (int)($el['params']['product_id'] ?? 0);
            if ($pid) $ids[] = $pid;
        }
        $byId = $this->model_extension_module_oc_kit_content_blocks->getProductsByIds($ids, $languageId, $imgW, $imgH);
        if (!$byId) return [];

        if ($popupEnable) {
            $this->load->model('tool/image');
        }

        $result = [];
        foreach ($elements as $el) {
            $params = $el['params'] ?? [];
            $prodId = (int)($params['product_id'] ?? 0);
            if (!$prodId || !isset($byId[$prodId])) continue;

            $product = $byId[$prodId];

            // Per-language override data: pick by current/default/first non-empty.
            $perLang  = is_array($el['data'] ?? null) ? $el['data'] : [];
            $langData = $this->pickLangData($perLang, $languageId);

            // Image override (params.override_image is a path relative to image/).
            if ($imgOverride && !empty($params['override_image'])) {
                $ovr = (string)$params['override_image'];
                $product['image']     = $this->cachedResize($ovr, $imgW, $imgH);
                $product['image_raw'] = $ovr;
            }
            // Name / description per-language overrides.
            if ($nameOverride && !empty($langData['override_name'])) {
                $product['name'] = (string)$langData['override_name'];
            }
            if ($descOverride && !empty($langData['override_description'])) {
                $product['description'] = (string)$langData['override_description'];
            }
            // Pros/cons — multiline → trimmed arrays for the twig {% for %} loops.
            if ($featDis) {
                $product['pluses']  = $this->splitLines($langData['override_pros']  ?? '');
                $product['minuses'] = $this->splitLines($langData['override_cons']  ?? '');
            }
            // Rating override (numeric).
            if (!empty($params['override_rating'])) {
                $product['rating'] = (float)$params['override_rating'];
            }

            // Additional images (thumb gallery under the main photo).
            // {thumb, popup} pairs, capped at additional_images_count.
            // Main product image is prepended so the user can always return to it.
            if ($addImgs) {
                $rows = $this->model_catalog_product->getProductImages($prodId);
                if ($addImgsCount > 0) {
                    $rows = array_slice($rows, 0, $addImgsCount);
                }
                $imgs = [];
                if (!empty($product['image_raw'])) {
                    $mainPopup = ($popupEnable && !empty($product['image_raw']))
                        ? $this->cachedResize($product['image_raw'], $popupW, $popupH)
                        : ($product['image'] ?? '');
                    $imgs[] = [
                        'thumb' => $this->cachedResize($product['image_raw'], $addImgW, $addImgH),
                        'popup' => $mainPopup,
                    ];
                }
                foreach ($rows as $r) {
                    if (empty($r['image'])) continue;
                    $imgs[] = [
                        'thumb' => $this->cachedResize($r['image'], $addImgW, $addImgH),
                        'popup' => $this->cachedResize($r['image'], $popupW, $popupH),
                    ];
                }
                $product['images'] = $imgs;
            }

            // Cart-add onclick: try the configured JS function, fall back to
            // navigation when it's missing (cart === undefined, etc.).
            // Empty cart_add_fn → no onclick (plain link to product).
            $minimum = (int)($product['minimum'] ?? 1) ?: 1;
            $hrefEsc = htmlspecialchars((string)($product['href'] ?? ''), ENT_QUOTES);
            $product['cart_add_js'] = $cartAddFn !== ''
                ? 'try{' . $cartAddFn . '(' . $prodId . ',' . $minimum . ')}catch(e){window.location=\'' . $hrefEsc . '\'}return false;'
                : '';

            // Attribute groups (when block requests them) — trim per group to
            // attributes_count to keep the spec table compact.
            if ($showAttrs) {
                $groups = $this->model_catalog_product->getProductAttributes($prodId);
                if ($attrsCount > 0) {
                    foreach ($groups as &$g) {
                        if (isset($g['attribute']) && is_array($g['attribute'])) {
                            $g['attribute'] = array_slice($g['attribute'], 0, $attrsCount);
                        }
                    }
                    unset($g);
                }
                $product['attribute_groups'] = $groups;
            }

            $product['popup_image'] = ($popupEnable && !empty($product['image_raw']))
                ? $this->cachedResize($product['image_raw'], $popupW, $popupH)
                : '';

            $result[] = array_merge($product, [
                'element_id'   => $el['element_id'],
                'css_classes'  => $this->buildCssClasses($params),
                'inline_style' => $this->buildInlineStyle($params),
            ]);
        }
        return $result;
    }

    /** Splits a multi-line override string into trimmed non-empty lines. */
    private function splitLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        return array_values(array_filter(array_map('trim', $lines), 'strlen'));
    }

    private function enrichCategoryElements(array $elements, int $languageId, int $imgW = 300, int $imgH = 200): array
    {
        $ids = [];
        foreach ($elements as $el) {
            $cid = (int)($el['params']['category_id'] ?? 0);
            if ($cid) $ids[] = $cid;
        }
        $byId = $this->model_extension_module_oc_kit_content_blocks->getCategoriesByIds($ids, $languageId, $imgW, $imgH);
        if (!$byId) return [];

        $result = [];
        foreach ($elements as $el) {
            $params = $el['params'] ?? [];
            $catId  = (int)($params['category_id'] ?? 0);
            if (!$catId || !isset($byId[$catId])) continue;

            $result[] = array_merge($byId[$catId], [
                'element_id'   => $el['element_id'],
                'css_classes'  => $this->buildCssClasses($params),
                'inline_style' => $this->buildInlineStyle($params),
            ]);
        }
        return $result;
    }

    private function enrichArticleElements(array $elements, int $languageId, int $imgW = 400, int $imgH = 250): array
    {
        $ids = [];
        foreach ($elements as $el) {
            $aid = (int)($el['params']['article_id'] ?? 0);
            if ($aid) $ids[] = $aid;
        }
        $byId = $this->model_extension_module_oc_kit_content_blocks->getArticlesByIds($ids, $languageId, $imgW, $imgH);

        // Bubble up blog-source errors so the theme can surface a comment
        if (isset($byId['__error'])) {
            $this->blogError = (string)$byId['__error'];
            return [];
        }
        if (!$byId) return [];

        $result = [];
        foreach ($elements as $el) {
            $params    = $el['params'] ?? [];
            $articleId = (int)($params['article_id'] ?? 0);
            if (!$articleId || !isset($byId[$articleId])) continue;

            $result[] = array_merge($byId[$articleId], [
                'element_id'   => $el['element_id'],
                'css_classes'  => $this->buildCssClasses($params),
                'inline_style' => $this->buildInlineStyle($params),
            ]);
        }
        return $result;
    }
    private ?string $blogError = null;

    /**
     * Resolves per-type image resize dimensions from the module settings
     * (`module_oc_kit_content_blocks_types[{type}][img_w|img_h]`), falling back
     * to the supplied defaults when an admin hasn't customised the type.
     *
     * @return array{0:int,1:int} [width, height]
     */
    private function getTypeImgSize(string $type, int $fallbackW, int $fallbackH, string $theme = ''): array
    {
        // Priority: admin per-type override > theme.json > caller fallback.
        $types = $this->config->get('module_oc_kit_content_blocks_types') ?: [];
        $cfg   = is_array($types[$type] ?? null) ? $types[$type] : [];
        $w     = (int)($cfg['img_w'] ?? 0);
        $h     = (int)($cfg['img_h'] ?? 0);

        if (($w === 0 || $h === 0) && $theme !== '') {
            $tc = $this->loadThemeConfig($type, $theme);
            if ($w === 0) $w = (int)($tc['img_w'] ?? $tc['image_width']  ?? 0);
            if ($h === 0) $h = (int)($tc['img_h'] ?? $tc['image_height'] ?? 0);
        }
        return [$w > 0 ? $w : $fallbackW, $h > 0 ? $h : $fallbackH];
    }

    /**
     * Loads `theme.json` next to `theme.twig` (one per-type/per-theme) and
     * returns its decoded contents. Provides per-theme defaults for params
     * such as `img_w`, `img_h`, `per_view`, etc. Cached per request.
     *
     * Discovery path:
     *   catalog/view/theme/{theme}/template/oc_kit_content_blocks/{type}/{themeName}/theme.json
     *
     * Returns `[]` when the file is missing or contains invalid JSON.
     */
    private function loadThemeConfig(string $type, string $themeName): array
    {
        static $cache = [];
        $key = $type . '|' . $themeName;
        if (array_key_exists($key, $cache)) return $cache[$key];

        $catalogTheme = $this->config->get('config_theme') ?: 'default';
        $candidates = [
            DIR_TEMPLATE . $catalogTheme . '/template/oc_kit_content_blocks/' . $type . '/' . $themeName . '/theme.json',
            DIR_TEMPLATE . 'default/template/oc_kit_content_blocks/'      . $type . '/' . $themeName . '/theme.json',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $decoded = json_decode((string)file_get_contents($path), true);
                return $cache[$key] = is_array($decoded) ? $decoded : [];
            }
        }
        return $cache[$key] = [];
    }

    // ─── CSS helpers ─────────────────────────────────────────────────────────

    private function buildInlineStyle(array $params): string
    {
        $styles = [];

        // Scalar properties: param key => CSS property + optional px suffix
        $scalarMap = [
            'bg_color'      => ['background-color', false],
            'text_color'    => ['color',             false],
            'font_size'     => ['font-size',         true],
            'font_weight'   => ['font-weight',       false],
            'text_align'    => ['text-align',        false],
            'border_radius' => ['border-radius',     true],
            'border'        => ['border',            false],
        ];

        foreach ($scalarMap as $key => [$prop, $px]) {
            $val = $params[$key] ?? '';
            if ((string)$val !== '') {
                $styles[] = $prop . ':' . ($px ? (int)$val . 'px' : $val);
            }
        }

        // Box-model shorthand properties: param key => CSS property
        foreach (['padding', 'margin'] as $prop) {
            $box = $params[$prop] ?? [];
            $t   = (int)($box['top']    ?? 0);
            $r   = (int)($box['right']  ?? 0);
            $b   = (int)($box['bottom'] ?? 0);
            $l   = (int)($box['left']   ?? 0);
            if ($t || $r || $b || $l) {
                $styles[] = "{$prop}:{$t}px {$r}px {$b}px {$l}px";
            }
        }

        return implode(';', $styles);
    }

    private function buildCssClasses(array $params): string
    {
        $classes = [];

        if (!empty($params['custom_class'])) {
            $classes[] = htmlspecialchars($params['custom_class'], ENT_QUOTES, 'UTF-8');
        }

        if (!empty($params['preset'])) {
            $classes[] = htmlspecialchars($params['preset'], ENT_QUOTES, 'UTF-8');
        }

        $hideOn = $params['hide_on'] ?? [];
        if (!empty($hideOn['mobile']))  $classes[] = 'd-none d-md-block';
        if (!empty($hideOn['tablet']))  $classes[] = 'd-md-none d-lg-block';
        if (!empty($hideOn['desktop'])) $classes[] = 'd-lg-none';

        return implode(' ', $classes);
    }

    // ─── Device detection ────────────────────────────────────────────────────

    private function detectDevice(): void
    {
        $autoload = DIR_SYSTEM . 'library/ockit/content_blocks/vendor/vendor/autoload.php';

        if (is_file($autoload)) {
            require_once $autoload;
        }

        if (class_exists('\Detection\MobileDetect')) {
            try {
                $detect = new \Detection\MobileDetect();
                if ($detect->isTablet()) {
                    $this->device = 'tablet';
                } elseif ($detect->isMobile()) {
                    $this->device = 'mobile';
                } else {
                    $this->device = 'desktop';
                }
                return;
            } catch (\Throwable $e) { /* fall through */ }
        }

        // Fallback: lightweight UA sniffing
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/iPad|Tablet/i', $ua)) {
            $this->device = 'tablet';
        } elseif (preg_match('/Mobile|Android|iPhone|iPod/i', $ua)) {
            $this->device = 'mobile';
        } else {
            $this->device = 'desktop';
        }
    }
}
