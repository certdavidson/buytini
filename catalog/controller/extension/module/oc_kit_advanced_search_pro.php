<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

use OcKit\AdvancedSearchPro\AdvancedSearchPro;

class ControllerExtensionModuleOcKitAdvancedSearchPro extends Controller {
    private $asp;

    public function __construct($registry) {
        parent::__construct($registry);
		require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $this->asp = new AdvancedSearchPro($this->registry);
    }

    public function index() {
        $this->load->language('extension/module/oc_kit_advanced_search_pro');

        $settings = $this->asp->getSettings([
            'status' => 0,
            'autocomplete_enabled' => 1,
            'autocomplete_min_chars' => 2,
            'autocomplete_delay' => 180,
            'autocomplete_show_price' => 1,
            'autocomplete_show_stock' => 1,
            'autocomplete_show_image' => 1,
            'autocomplete_limit' => 8,
            'popular_tags_enabled' => 0,
            'popular_tags_source' => 'manual',
            'popular_tags' => '',
            'popular_tags_limit' => 10,
            'autocomplete_cart_fn' => '',
            'autocomplete_layout' => 'popup',
            'autocomplete_color_preset' => 'indigo',
            'autocomplete_color_custom' => ''
        ]);

        if (!$settings['status'] || !$settings['autocomplete_enabled']) {
            return '';
        }

        // Layout variant — each template self-contains its own engine assets
        // (<link>/<script> right in the twig): on this theme the <head> styles and
        // footer scripts are captured before the widget renders, so addStyle()/
        // addScript() can't reach either bucket. 'inline' setting → classic
        // flat-list; otherwise → find-iq popup. There is no shared/common asset
        // between the two engines, so nothing is injected globally.
        $layout = (($settings['autocomplete_layout'] ?? 'popup') === 'inline') ? 'classic' : 'popup';

        $data['heading_title']      = $this->language->get('heading_title');
        $data['text_placeholder']   = $this->language->get('text_placeholder');
        $data['text_no_results']    = $this->language->get('text_no_results');
        $data['text_view_all']      = $this->language->get('text_view_all');
        $data['text_recent']        = $this->language->get('text_recent');
        $data['text_clear_history'] = $this->language->get('text_clear_history');
        $data['text_loading']       = $this->language->get('text_loading');
        $data['text_in_stock']      = $this->language->get('text_in_stock');
        $data['text_out_of_stock']  = $this->language->get('text_out_of_stock');
        $data['text_category']      = $this->language->get('text_category');
        $data['text_popular']       = $this->language->get('text_popular');
        $data['text_sale']          = $this->language->get('text_sale');
        $data['text_did_you_mean']  = $this->language->get('text_did_you_mean');
        $data['text_brand']         = $this->language->get('text_brand');
        $data['text_voice_search']  = $this->language->get('text_voice_search');
        $data['text_voice_listen']  = $this->language->get('text_voice_listen');
        $data['text_popular_tags']  = $this->language->get('text_popular_tags');
        $data['text_id']            = $this->language->get('text_id');
        $data['text_model']         = $this->language->get('text_model');
        $data['text_popular_queries']  = $this->language->get('text_popular_queries');
        $data['text_popular_products'] = $this->language->get('text_popular_products');
        $data['text_popular_brands']   = $this->language->get('text_popular_brands');
        $data['text_add_to_cart']      = $this->language->get('text_add_to_cart');
        $data['text_all_categories']   = $this->language->get('text_all_categories');
        $data['text_other_category']   = $this->language->get('text_other_category');
        $data['text_viewed']           = $this->language->get('text_viewed');
        $data['text_search_history']   = $this->language->get('text_search_history');
        $data['text_more']             = $this->language->get('text_more');
        $data['text_open_category']    = $this->language->get('text_open_category');
        $data['text_sort_cheap']       = $this->language->get('text_sort_cheap');
        $data['text_sort_expensive']   = $this->language->get('text_sort_expensive');
        $data['text_sort_default']     = $this->language->get('text_sort_default');

        // Popular tags — format: [{names:{uk,ru,en}, urls:{uk,ru,en}}, ...]
        $popularTags = [];
        if (!empty($settings['popular_tags_enabled'])) {
            $limit = max(1, (int)$settings['popular_tags_limit']);
            if ($settings['popular_tags_source'] === 'auto') {
                // Auto: top queries from log, no translations, no URLs
                $queries = $this->asp->getPopularQueries($limit, 30);
                foreach ($queries as $q) {
                    $popularTags[] = ['names' => ['uk' => $q, 'ru' => $q, 'en' => $q], 'urls' => ['uk' => '', 'ru' => '', 'en' => '']];
                }
            } else {
                // Manual — stored as JSON array of {names:{uk,ru,en}, urls:{uk,ru,en}}
                // OC3 Request class applies htmlspecialchars() to all POST data,
                // so " is stored as &quot; — decode before json_decode.
                $raw = html_entity_decode(trim((string)$settings['popular_tags']), ENT_QUOTES, 'UTF-8');
                if ($raw !== '') {
                    $parsed = json_decode($raw, true);
                    if (is_array($parsed)) {
                        $popularTags = array_slice($parsed, 0, $limit);
                    }
                }
            }
        }
        // base64 — only [A-Za-z0-9+/=], immune to HTML minifier and any HTML encoding.
        // Unicode is escaped as \uXXXX (no JSON_UNESCAPED_UNICODE) so atob() works safely.
        $data['popular_tags_b64'] = base64_encode(json_encode($popularTags));

        // Carry the current language into the AJAX endpoint — the autocomplete is
        // called without a SEO language prefix, so live() would otherwise default
        // to the primary language and return UA names/links on the RU/EN store.
        // Raw catalog URL (NOT url->link) so the SEO language module doesn't prepend a
        // /ru/ prefix — index.php?route=<module> under /ru/ is a 404, which killed the
        // live search and the popular-dropdown fetch on non-default languages. The
        // language= param keeps the AJAX responses localised.
        $endpointBase = defined('HTTPS_SERVER') ? HTTPS_SERVER : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        $data['endpoint'] = $endpointBase . 'index.php?route=extension/module/oc_kit_advanced_search/live&language=' . $this->config->get('config_language');
        $data['min_chars'] = (int)$settings['autocomplete_min_chars'];
        $data['delay'] = (int)$settings['autocomplete_delay'];

        // Custom add-to-cart JS function path (e.g. "cart.add"); empty → default.
        $data['cart_fn'] = (string)$settings['autocomplete_cart_fn'];

        // Layout: 'popup' (detached overlay + header stub) or 'inline' (header
        // field is the live search input, results drop in-place).
        $data['autocomplete_layout'] = ($settings['autocomplete_layout'] === 'inline') ? 'inline' : 'popup';
        $data['button_search'] = ''; // classic-variant search button is icon-only

        // Colour scheme — resolve the accent (preset or custom hex) and derive
        // the hover/ink/soft shades so the widget can emit a scoped :root.
        $accent = $this->aspResolveAccent($settings);
        $data['accent']             = $accent;
        $data['accent_hover']       = $this->aspShade($accent, -16);
        $data['accent_ink']         = $this->aspShade($accent, -30);
        $data['accent_soft']        = $this->aspShade($accent, 90);
        $data['accent_soft_border'] = $this->aspShade($accent, 66);

        // Active storefront theme code — lets the header stub inherit each theme's
        // native search-box look via a [data-asp-theme] CSS scope (sanitised for
        // the attribute; empty falls back to the generic stub styling).
        $data['asp_theme'] = preg_replace('/[^a-z0-9_-]/i', '', (string)$this->config->get('config_theme'));

        // Theme-agnostic "recently viewed" hook: emit the current product id from
        // the request (the widget renders on every page, so no per-theme template
        // patching is needed). 0 on non-product pages.
        $data['current_product_id'] = 0;
        $route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
        if ($route === 'product/product' && !empty($this->request->get['product_id'])) {
            $data['current_product_id'] = (int)$this->request->get['product_id'];
        }

        return $this->load->view('extension/module/ockit/advanced_search_pro/layouts/' . $layout, $data);
    }

    /** Resolve the accent colour from the preset name or the custom hex. */
    private function aspResolveAccent($settings) {
        $presets = [
            'indigo' => '#6366f1', 'purple' => '#8b5cf6', 'blue'   => '#3b82f6',
            'green'  => '#10b981', 'teal'   => '#14b8a6', 'orange' => '#f59e0b',
            'red'    => '#ef4444', 'pink'   => '#ec4899', 'slate'  => '#475569',
        ];
        $preset = (string)($settings['autocomplete_color_preset'] ?? 'indigo');
        if ($preset === 'custom') {
            $c = trim((string)($settings['autocomplete_color_custom'] ?? ''));
            if (preg_match('/^#?[0-9a-fA-F]{6}$/', $c)) {
                return '#' . strtolower(ltrim($c, '#'));
            }
            return '#6366f1';
        }
        return $presets[$preset] ?? '#6366f1';
    }

    /** Lighten ($pct > 0, toward white) or darken ($pct < 0, toward black) a hex. */
    private function aspShade($hex, $pct) {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) { return '#6366f1'; }
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        if ($pct >= 0) {
            $r += (255 - $r) * $pct / 100; $g += (255 - $g) * $pct / 100; $b += (255 - $b) * $pct / 100;
        } else {
            $f = (100 + $pct) / 100; $r *= $f; $g *= $f; $b *= $f;
        }
        return sprintf('#%02x%02x%02x',
            max(0, min(255, (int)round($r))), max(0, min(255, (int)round($g))), max(0, min(255, (int)round($b))));
    }

    /**
     * Search results page. Dedicated ASP controller — the quick-search form and
     * the SEO keyword `search` route here instead of patching core product/search.
     *
     * Structure mirrors the vanilla OpenCart search controller (params →
     * breadcrumbs → category tree → results → sorts/limits/pagination → render).
     * The only swap is the product source: instead of the native
     * model_catalog_product->getProducts(), it runs the ASP search engine
     * (Manticore/hybrid/native via CascadeSearchEngine) and post-filters with
     * the library facade. Relevance lives entirely in the library.
     */
    public function results() {
        $this->load->language('product/search');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $route = 'extension/module/oc_kit_advanced_search_pro/results';
        $asp   = $this->asp;

        $asp_settings = $asp->getSettings([
            'mode' => 'native', 'status' => 0,
            'filter_enabled' => 1, 'filter_ajax_mode' => 'own',
            'filter_category' => 1, 'filter_manufacturer' => 1, 'filter_price' => 1,
            'filter_stock' => 1, 'filter_attribute' => 1, 'filter_rating' => 1,
            'layout_correction' => 1, 'cross_lang_enabled' => 1,
            'enable_transliteration' => 1, 'enable_typo_correction' => 1,
            'enable_word_split' => 0, 'enable_morphology' => 0, 'enable_trigram_fallback' => 0,
            'ai_expand_query' => 0, 'ai_rewrite_query' => 0, 'ai_intent_detection' => 0,
            'vector_enabled' => 0, 'vector_ratio' => 30, 'oos_mode' => 'show',
            'rank_stock_first' => 0, 'rank_boost_new' => 0, 'rank_boost_popular' => 0, 'rank_boost_category' => 0,
            // Engine-input keys the autocomplete (live) requests but the results page
            // omitted — getSettings() returns ONLY the requested keys, so a missing key
            // means the engine never sees it. Without 'fuzzy' the results page searched
            // with fuzzy OFF (live had it ON) → far fewer hits for the same query.
            'fuzzy' => 1, 'fuzzy_distance' => 2, 'search_fields' => null,
            'autocomplete_group_collapse' => 0,
            'group_collapse_attribute' => 0,
        ]);
        // License degradation — Pro features off, native search still works.
        if (!\OcKit\AdvancedSearchPro\AdvancedSearchPro::isLicensedCatalog($this->registry)) {
            $asp_settings['mode'] = 'native';
            $asp_settings['vector_enabled'] = 0;
            $asp_settings['ai_expand_query'] = $asp_settings['ai_rewrite_query'] = $asp_settings['ai_intent_detection'] = 0;
        }

        // ── Request params ───────────────────────────────────────────────────
        $search          = isset($this->request->get['search']) ? $this->request->get['search'] : '';
        $tag             = isset($this->request->get['tag']) ? $this->request->get['tag'] : (isset($this->request->get['search']) ? $this->request->get['search'] : '');
        $description     = isset($this->request->get['description']) ? $this->request->get['description'] : '';
        $category_id     = isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0;
        $sub_category    = isset($this->request->get['sub_category']) ? $this->request->get['sub_category'] : '';
        $manufacturer_id = isset($this->request->get['manufacturer_id']) ? (int)$this->request->get['manufacturer_id'] : 0;
        $price_min       = isset($this->request->get['price_min']) ? (float)$this->request->get['price_min'] : null;
        $price_max       = isset($this->request->get['price_max']) ? (float)$this->request->get['price_max'] : null;
        $stock           = isset($this->request->get['stock']) ? (int)$this->request->get['stock'] : null;
        $rating          = isset($this->request->get['rating']) ? (int)$this->request->get['rating'] : null;
        $sort            = isset($this->request->get['sort']) ? $this->request->get['sort'] : ''; // '' = engine relevance
        $order           = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        $page            = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit           = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        if ($limit < 1) { $limit = 20; }

        $attr_filters = [];
        if (isset($this->request->get['attr']) && is_array($this->request->get['attr'])) {
            foreach ($this->request->get['attr'] as $aid => $aval) {
                $aid = (int)$aid; $aval = trim((string)$aval);
                if ($aid > 0 && $aval !== '') { $attr_filters[$aid] = $aval; }
            }
        }

        // ── ASP query preparation (homoglyphs, price extraction, AI rule,
        //    synonym + recovery term variants) ─────────────────────────────────
        $search_expanded   = $search;
        $tag_expanded      = $tag;
        $asp_did_you_mean  = null;
        $asp_synonym_terms = [];
        $asp_recovery_terms = [];

        if ($search !== '') {
            $search = $asp->normalizeHomoglyphs($search);

            $priceEx = $asp->extractPriceFilters($search);
            if ($priceEx['query'] !== '' && $priceEx['query'] !== $search) {
                if ($tag === $search) { $tag = $priceEx['query']; }
                $search = $priceEx['query'];
            }
            // Query-extracted price becomes a filter unless the URL set one.
            if ($price_min === null && !empty($priceEx['price_min'])) { $price_min = (float)$priceEx['price_min']; }
            if ($price_max === null && !empty($priceEx['price_max'])) { $price_max = (float)$priceEx['price_max']; }

            $search_expanded = $search;
            $tag_expanded    = $tag;

            if (!empty($asp_settings['ai_expand_query']) || !empty($asp_settings['ai_rewrite_query']) || !empty($asp_settings['ai_intent_detection'])) {
                try {
                    $aiMeta = $asp->applyStoredQueryRule($search, $asp_settings);
                    if (!empty($aiMeta['query'])) { $search_expanded = $tag_expanded = $aiMeta['query']; }
                } catch (\Throwable $e) {}
            }

            // High-confidence variants (synonyms) — always searched.
            $wordCount = count(array_filter(preg_split('/\s+/u', trim($search))));
            $synExtra  = $wordCount > 1 ? $asp->getSynonymWordVariants($search) : $asp->getSynonymTerms($search);
            if ($synExtra) { $asp_synonym_terms = $synExtra; }

            // Recovery variants (wrong layout/script/typo) — searched only if the
            // primary terms find nothing.
            if (!empty($asp_settings['layout_correction']))   { $asp_recovery_terms = array_merge($asp_recovery_terms, $asp->getLayoutVariants($search, 2)); }
            if (!empty($asp_settings['cross_lang_enabled']))  { $asp_recovery_terms = array_merge($asp_recovery_terms, $asp->getCrossLangVariants($search)); }
            if (!empty($asp_settings['enable_transliteration'])) { $asp_recovery_terms = array_merge($asp_recovery_terms, $asp->getTransliterationVariants($search)); }
            if (!empty($asp_settings['enable_typo_correction'])) {
                $correction = $asp->getTypoCorrection($search);
                if ($correction !== null) { $asp_recovery_terms[] = $correction; $asp_did_you_mean = $correction; }
            }
            if (!empty($asp_settings['enable_word_split'])) {
                $split = $asp->splitCompoundQuery($search);
                if ($split !== null) { $asp_recovery_terms[] = $split; }
            }
            if (!empty($asp_settings['enable_morphology']) && $asp_settings['mode'] === 'native') {
                $asp_recovery_terms = array_merge($asp_recovery_terms, $asp->getStemVariants($search));
            }
            $asp_recovery_terms = array_values(array_unique(array_filter($asp_recovery_terms, 'strlen')));
        }

        // OOS mode: URL ?stock= always wins.
        $oos_mode = (string)($asp_settings['oos_mode'] ?? 'show');
        if ($stock === null && $oos_mode === 'hide') { $stock = 1; }

        // ── Assets ───────────────────────────────────────────────────────────
        // The header search widget loads the variant JS/CSS on every page (incl.
        // this one); here we only add the results-page faceted-filter assets.
        $this->document->addStyle('catalog/view/javascript/asp_ajax_filter.css');
        $this->document->addScript('catalog/view/javascript/asp_ajax_filter.js');

        // ── Title ────────────────────────────────────────────────────────────
        if (isset($this->request->get['search'])) {
            $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->request->get['search']);
        } elseif (isset($this->request->get['tag'])) {
            $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
        } else {
            $this->document->setTitle($this->language->get('heading_title'));
        }

        // ── URL builder — replaces the vanilla controller's five repeated blocks.
        //    Returns the active filter/sort/paging params, minus $exclude keys. ──
        $current = [
            'search' => $search, 'tag' => isset($this->request->get['tag']) ? $tag : null,
            'description' => $description !== '' ? $description : null,
            'category_id' => $category_id ?: null, 'sub_category' => $sub_category !== '' ? $sub_category : null,
            'manufacturer_id' => $manufacturer_id ?: null,
            'price_min' => isset($this->request->get['price_min']) ? $this->request->get['price_min'] : null,
            'price_max' => isset($this->request->get['price_max']) ? $this->request->get['price_max'] : null,
            'stock' => isset($this->request->get['stock']) ? $this->request->get['stock'] : null,
            'rating' => isset($this->request->get['rating']) ? $this->request->get['rating'] : null,
            'sort' => isset($this->request->get['sort']) ? $sort : null,
            'order' => isset($this->request->get['order']) ? $order : null,
            'page' => isset($this->request->get['page']) ? $page : null,
            'limit' => isset($this->request->get['limit']) ? $limit : null,
        ];
        $buildUrl = function (array $exclude = []) use ($current) {
            $parts = [];
            foreach ($current as $key => $value) {
                if ($value === null || $value === '' || in_array($key, $exclude, true)) { continue; }
                if ($key === 'search' || $key === 'tag') {
                    $parts[] = $key . '=' . urlencode(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
                } else {
                    $parts[] = $key . '=' . rawurlencode((string)$value);
                }
            }
            return implode('&', $parts);
        };

        // ── Breadcrumbs ──────────────────────────────────────────────────────
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/home')];
        $data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link($route, $buildUrl())];

        $data['heading_title'] = isset($this->request->get['search'])
            ? $this->language->get('heading_title') . ' - ' . $this->request->get['search']
            : $this->language->get('heading_title');

        $data['asp_did_you_mean']     = $asp_did_you_mean;
        $data['asp_did_you_mean_url']  = $asp_did_you_mean ? $this->url->link($route, 'search=' . urlencode($asp_did_you_mean)) : '';
        $data['asp_filter_ajax_mode']  = (string)($asp_settings['filter_ajax_mode'] ?? 'own');
        $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
        $data['compare'] = $this->url->link('product/compare');

        // ── 3-level category tree (theme search-in-category dropdown) ─────────
        $data['categories'] = [];
        foreach ($this->model_catalog_category->getCategories(0) as $c1) {
            $lvl2 = [];
            foreach ($this->model_catalog_category->getCategories($c1['category_id']) as $c2) {
                $lvl3 = [];
                foreach ($this->model_catalog_category->getCategories($c2['category_id']) as $c3) {
                    $lvl3[] = ['category_id' => $c3['category_id'], 'name' => $c3['name']];
                }
                $lvl2[] = ['category_id' => $c2['category_id'], 'name' => $c2['name'], 'children' => $lvl3];
            }
            $data['categories'][] = ['category_id' => $c1['category_id'], 'name' => $c1['name'], 'children' => $lvl2];
        }

        $data['products']   = [];
        $product_total      = 0;
        $data['pagination'] = '';
        $data['results']    = '';
        $data['facets']     = [];

        if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
            // ── Engine search (one path; CascadeSearchEngine resolves the mode) ─
            $primary = $search_expanded !== '' ? $search_expanded : $tag_expanded;
            $primaryTerms = array_values(array_unique(array_filter(array_merge([$primary], $asp_synonym_terms), 'strlen')));
            $candidates = max(2000, $limit * 20);
            $engine = $asp->getSearchEngine($asp_settings);

            $ids = []; $seen = [];
            $collect = function ($terms) use (&$ids, &$seen, $engine, $candidates) {
                foreach ($terms as $term) {
                    if ($term === '') { continue; }
                    foreach ($engine->search($term, $candidates, 0)['ids'] as $pid) {
                        $pid = (int)$pid;
                        if (!isset($seen[$pid])) { $seen[$pid] = true; $ids[] = $pid; }
                    }
                }
            };
            $collect($primaryTerms);
            if (empty($ids)) { $collect($asp_recovery_terms); }

            // Semantic blend — hybrid only, and only when lexical is short of a page.
            if ($asp_settings['mode'] === 'hybrid' && !empty($asp_settings['vector_enabled']) && count($ids) < $limit) {
                $sem = $asp->semanticSearch($primary, $asp_settings, 200);
                if ($sem) { $ids = $asp->blendVectorResults($ids, $sem, $asp_settings['vector_ratio'] ?? 30); }
            }
            // Exact numeric product id — prepend if it exists.
            if ($search !== '' && ctype_digit(trim($search))) {
                $pid = (int)$search;
                if (!isset($seen[$pid]) && $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE status = 1 AND date_available <= NOW() AND product_id = '" . $pid . "' LIMIT 1")->num_rows) {
                    array_unshift($ids, $pid);
                }
            }
            // Trigram net — last resort when everything above found nothing.
            if (empty($ids) && !empty($asp_settings['enable_trigram_fallback'])) {
                $ids = array_map('intval', $asp->trigramFallback($primary, $candidates)['ids']);
            }

            // Variant-group collapse — fold colour/size variants onto the
            // best-ranked member so the results page mirrors the catalog and the
            // autocomplete (one card per group). Done BEFORE filtering/pagination
            // so the total and the page slice count groups, not variants. Same
            // toggle as the autocomplete; no-op when the grouping module is absent.
            if (!empty($asp_settings['autocomplete_group_collapse'])) {
                $ids = $asp->collapseProductGroups($ids, (int)($asp_settings['group_collapse_attribute'] ?? 0));
            }

            $filtered = $asp->filterProductIds($ids, [
                'category_id' => $category_id, 'sub_category' => !empty($sub_category),
                'manufacturer_id' => $manufacturer_id, 'price_min' => $price_min, 'price_max' => $price_max,
                'stock' => $stock, 'rating' => $rating, 'attr' => $attr_filters,
                'sort' => $sort, 'order' => $order, 'start' => ($page - 1) * $limit, 'limit' => $limit,
                'rank_stock_first' => !empty($asp_settings['rank_stock_first']) || $oos_mode === 'last',
                'rank_boost_new' => !empty($asp_settings['rank_boost_new']),
                'rank_boost_popular' => !empty($asp_settings['rank_boost_popular']),
            ]);
            $product_total = (int)$filtered['total'];

            $product_url = $buildUrl();
            foreach ($filtered['ids'] as $id) {
                $product = $this->model_catalog_product->getProduct($id);
                if (!$product) { continue; }

                // ── DEFAULT: vanilla inline product card ──────────────────────
                $image = $this->model_tool_image->resize(
                    $product['image'] ?: 'placeholder.png',
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
                );
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }
                if (!is_null($product['special']) && (float)$product['special'] >= 0) {
                    $special   = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $tax_price = (float)$product['special'];
                } else {
                    $special   = false;
                    $tax_price = (float)$product['price'];
                }
                $tax    = $this->config->get('config_tax') ? $this->currency->format($tax_price, $this->session->data['currency']) : false;
                $rating_value = $this->config->get('config_review_status') ? (int)$product['rating'] : false;

                // Stock flags the theme product card needs for its add-to-cart button.
                // The oct_showcase / oct_deals search.twig gates the whole cart button on
                // product.can_buy, so without it no button renders. Mirror the native
                // logic: buyable when in stock — or when out-of-stock checkout is allowed;
                // the stock label is shown only when out of stock.
                $qty     = (int)$product['quantity'];
                $can_buy = $qty > 0 || (bool)$this->config->get('config_stock_checkout');
                $stock   = $qty <= 0 ? $product['stock_status'] : false;

                $data['products'][] = [
                    'product_id'  => $product['product_id'],
                    'thumb'       => $image,
                    'name'        => $product['name'],
                    'description' => utf8_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                    'price'       => $price,
                    'special'     => $special,
                    'tax'         => $tax,
                    'minimum'     => $product['minimum'] > 0 ? $product['minimum'] : 1,
                    'rating'      => $rating_value,
                    'href'        => $this->url->link('product/product', 'product_id=' . $product['product_id'] . ($product_url ? '&' . $product_url : '')),
                    'can_buy'     => $can_buy,
                    'stock'       => $stock,
                ];

                // ── ALTERNATIVE: rich theme card via product_formatter ────────
                //    Enable this (and remove the inline block above) if the theme
                //    product widget needs special_percentage / stickers / in_stock
                //    / manufacturer / wishlist_count etc:
                // $data['products'][] = $this->product_formatter->format($product, []);
            }

            // ── Facets ───────────────────────────────────────────────────────
            $filtersOn = !empty($asp_settings['filter_enabled']) && (
                !empty($asp_settings['filter_category']) || !empty($asp_settings['filter_manufacturer']) ||
                !empty($asp_settings['filter_price']) || !empty($asp_settings['filter_stock']) ||
                !empty($asp_settings['filter_attribute']) || !empty($asp_settings['filter_rating'])
            );
            if ($filtersOn) {
                $facets = $asp->getEngineFacets([
                    'search' => $search_expanded, 'tag' => $tag_expanded, 'description' => $description,
                    'category_id' => $category_id, 'sub_category' => !empty($sub_category),
                    'manufacturer_id' => $manufacturer_id, 'price_min' => $price_min, 'price_max' => $price_max,
                    'in_stock' => $stock, 'rating' => $rating, 'attr' => $attr_filters,
                ], ['mode' => $asp_settings['mode']]);

                foreach ($facets['categories'] as &$cat) {
                    $cat['href'] = $this->url->link($route, $buildUrl(['category_id', 'page']) . '&category_id=' . (int)$cat['category_id']);
                }
                unset($cat);
                foreach ($facets['manufacturers'] as &$man) {
                    $man['href'] = $this->url->link($route, $buildUrl(['manufacturer_id', 'page']) . '&manufacturer_id=' . (int)$man['manufacturer_id']);
                }
                unset($man);
                foreach ($facets['attributes'] as &$attr) {
                    foreach ($attr['values'] as &$val) {
                        $val['href'] = $this->url->link($route, $buildUrl(['page']) . '&attr[' . (int)$attr['attribute_id'] . ']=' . urlencode($val['value']));
                    }
                    unset($val);
                }
                unset($attr);

                $data['facets'] = $facets;
                $data['facet_price_action']   = $this->url->link($route, $buildUrl(['price_min', 'price_max', 'page']));
                $data['facet_stock_link_in']  = $this->url->link($route, $buildUrl(['stock', 'page']) . '&stock=1');
                $data['facet_stock_link_out'] = $this->url->link($route, $buildUrl(['stock', 'page']) . '&stock=0');
                $data['facet_stock_link_all'] = $this->url->link($route, $buildUrl(['stock', 'page']));
                $data['facet_rating_links']   = [];
                for ($r = 5; $r >= 1; $r--) {
                    $data['facet_rating_links'][$r] = $this->url->link($route, $buildUrl(['rating', 'page']) . '&rating=' . $r);
                }
            }

            // ── Sort options ─────────────────────────────────────────────────
            $sortBase = $buildUrl(['sort', 'order', 'page']);
            $sortBase = $sortBase ? '&' . $sortBase : '';
            $data['sorts'] = [
                ['text' => $this->language->get('text_default'),   'value' => '',              'href' => $this->url->link($route, ltrim($sortBase, '&'))],
                ['text' => $this->language->get('text_name_asc'),  'value' => 'pd.name-ASC',   'href' => $this->url->link($route, 'sort=pd.name&order=ASC' . $sortBase)],
                ['text' => $this->language->get('text_name_desc'), 'value' => 'pd.name-DESC',  'href' => $this->url->link($route, 'sort=pd.name&order=DESC' . $sortBase)],
                ['text' => $this->language->get('text_price_asc'), 'value' => 'p.price-ASC',   'href' => $this->url->link($route, 'sort=p.price&order=ASC' . $sortBase)],
                ['text' => $this->language->get('text_price_desc'),'value' => 'p.price-DESC',  'href' => $this->url->link($route, 'sort=p.price&order=DESC' . $sortBase)],
            ];
            if ($this->config->get('config_review_status')) {
                $data['sorts'][] = ['text' => $this->language->get('text_rating_desc'), 'value' => 'rating-DESC', 'href' => $this->url->link($route, 'sort=rating&order=DESC' . $sortBase)];
            }
            $data['sorts'][] = ['text' => $this->language->get('text_date_added_desc') ?: 'Newest', 'value' => 'p.date_added-DESC', 'href' => $this->url->link($route, 'sort=p.date_added&order=DESC' . $sortBase)];

            // ── Limit options ────────────────────────────────────────────────
            $limitBase = $buildUrl(['limit', 'page']);
            $limitBase = $limitBase ? '&' . $limitBase : '';
            $data['limits'] = [];
            $limits = array_unique([(int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100]);
            sort($limits);
            foreach ($limits as $value) {
                $data['limits'][] = ['text' => $value, 'value' => $value, 'href' => $this->url->link($route, 'limit=' . $value . $limitBase)];
            }

            // ── Pagination (standard OpenCart Pagination library) ─────────────
            // Our own results template omits the theme's octLoadMore <script>, so
            // ul.pagination stays plain links the theme JS never wires up.
            $pagination = new Pagination();
            $pagination->total = $product_total;
            $pagination->page  = $page;
            $pagination->limit = $limit;
            $pageBase = $buildUrl(['page']);
            $pagination->url = $this->url->link($route, ($pageBase ? $pageBase . '&' : '') . 'page={page}');
            $data['pagination'] = $pagination->render();
            $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            // ── Customer search log ──────────────────────────────────────────
            if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
                $this->load->model('account/search');
                $this->model_account_search->addSearch([
                    'keyword'      => $search,
                    'category_id'  => $category_id,
                    'sub_category' => $sub_category,
                    'description'  => $description,
                    'products'     => $product_total,
                    'customer_id'  => $this->customer->isLogged() ? $this->customer->getId() : 0,
                    'ip'           => isset($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : '',
                ]);
            }
        }

        // ── Canonical + robots ───────────────────────────────────────────────
        if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
            $canon = $buildUrl(['sort', 'order', 'page', 'limit', 'price_min', 'price_max', 'stock', 'rating']);
            if ($canon !== '') { $this->document->addLink($this->url->link($route, $canon), 'canonical'); }
            $this->document->setRobots($product_total > 0 ? 'index,follow' : 'noindex,follow');
        } else {
            $this->document->setRobots('noindex,follow');
        }

        // ── Template vars (theme contract) ───────────────────────────────────
        $data['search'] = $search;
        $data['asp_results_action'] = html_entity_decode($this->url->link($route), ENT_QUOTES, 'UTF-8');
        $data['description'] = $description;
        $data['category_id'] = $category_id;
        $data['sub_category'] = $sub_category;
        $data['manufacturer_id'] = $manufacturer_id;
        $data['price_min'] = $price_min;
        $data['price_max'] = $price_max;
        $data['stock'] = $stock;
        $data['rating'] = $rating;
        $data['attr_filters'] = $attr_filters;
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['limit'] = $limit;

        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('product/oc_kit_advanced_search', $data));
    }
}
