<?php

use OcKit\AdvancedSearchPro\AdvancedSearchPro;

class ControllerExtensionModuleOcKitAdvancedSearch extends Controller {
    private $asp;

    public function __construct($registry) {
        parent::__construct($registry);
		require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $this->asp = new AdvancedSearchPro($this->registry);
    }

    public function live() {
        $this->response->addHeader('Content-Type: application/json');
        // Autocomplete is per-keystroke and personalised — never let a browser or
        // CDN (Cloudflare) cache the JSON, or stale results stick to a query.
        $this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->response->addHeader('Pragma: no-cache');

        // The autocomplete is an AJAX call without a SEO language prefix, so OC
        // defaults to the primary language. Honour the language the widget passed
        // so product names and links match the store the visitor is browsing.
        $reqLang = isset($this->request->get['language']) ? (string)$this->request->get['language'] : '';
        if ($reqLang !== '' && preg_match('/^[a-z]{2}-[a-z]{2}$/', $reqLang)) {
            $langRow = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($reqLang) . "' AND status = '1'")->row;
            if ($langRow) {
                $this->config->set('config_language_id', (int)$langRow['language_id']);
                $this->config->set('config_language', $reqLang);
            }
        }

        // Per-session rate limiting (better than per-IP for CDN / shared hosting).
        $rateWindow = 10; // seconds
        $rateMax    = 30; // requests per window
        $rateData   = $this->session->data['asp_live_rate'] ?? ['start' => 0, 'count' => 0];
        if ((time() - (int)$rateData['start']) > $rateWindow) {
            $rateData = ['start' => time(), 'count' => 0];
        }
        $rateData['count']++;
        $this->session->data['asp_live_rate'] = $rateData;

        if ($rateData['count'] > $rateMax) {
            $this->response->setOutput(json_encode([
                'status' => 'rate_limited',
                'items' => []
            ]));
            return;
        }

        $settings = $this->asp->getSettings([
            'status' => 0,
            'mode' => 'native',
            'autocomplete_enabled' => 1,
            'autocomplete_mode' => 'products',
            'layout_correction' => 1,
            'autocomplete_min_chars' => 2,
            'autocomplete_delay' => 180,
            'autocomplete_show_price' => 1,
            'autocomplete_show_stock' => 1,
            'autocomplete_show_image' => 1,
            'autocomplete_limit' => 8,
            'autocomplete_group_collapse' => 0,
            'group_collapse_attribute' => 0,
            'cache_enabled' => 1,
            'cache_ttl' => 300,
            'cache_type' => 'file',
            'rank_stock_first' => 1,
            'rank_boost_new' => 0,
            'rank_boost_popular' => 0,
            'rank_boost_category' => 0,
            'search_fields' => null,
            'fuzzy' => 1,
            'fuzzy_distance' => 2,
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-4o-mini',
            'ai_expand_query' => 0,
            'ai_rewrite_query' => 0,
            'ai_intent_detection' => 0,
            'ai_budget_monthly' => 50,
            'ai_budget_daily_limit' => 1000,
            'ai_auto_block' => 1,
            'autocomplete_show_facets' => 0,
            'autocomplete_show_categories' => 1,
            'autocomplete_category_limit' => 2,
            'autocomplete_show_brands' => 1,
            'autocomplete_brand_limit' => 2,
            'cross_lang_enabled'      => 1,
            'enable_transliteration'  => 1,
            'enable_typo_correction'  => 1,
            'enable_word_split'       => 0,
            'enable_mmr'              => 0,
            'mmr_lambda'              => 0.5,
            'vector_enabled'          => 0,
            'vector_ratio'            => 30,
            'vector_min_score'        => 0.35,
            'ai_embedding_model'      => 'text-embedding-3-large',
            'autocomplete_semantic'   => 0,
            'oos_mode'                => 'show',
        ]);

        if (!$settings['status'] || !$settings['autocomplete_enabled']) {
            $this->response->setOutput(json_encode([
                'status' => 'disabled',
                'items' => []
            ]));
            return;
        }

        // License degradation — search must keep working when the license is
        // invalid, but Pro features (AI rewrite, vector blend, Manticore mode)
        // fall back to plain native MySQL search so the merchant is never
        // left with a broken store.
        if (!AdvancedSearchPro::isLicensedCatalog($this->registry)) {
            $settings['mode']                  = 'native';
            $settings['ai_expand_query']       = 0;
            $settings['ai_rewrite_query']      = 0;
            $settings['ai_intent_detection']   = 0;
            $settings['vector_enabled']        = 0;
            $settings['autocomplete_semantic'] = 0;
        }

        $raw = '';
        if (isset($this->request->get['q'])) {
            $raw = $this->request->get['q'];
        } elseif (isset($this->request->get['query'])) {
            $raw = $this->request->get['query'];
        } elseif (isset($this->request->get['search'])) {
            $raw = $this->request->get['search'];
        }

        $query    = $this->asp->normalizeQuery($raw);

        // Price/budget extraction — mirror the full search page so autocomplete
        // and results stay consistent ("подарунок хлопцю до 5000" → query
        // "подарунок хлопцю" + ceiling 5000, applied to the suggested products).
        $asp_price_min = null;
        $asp_price_max = null;
        $priceFilter = $this->asp->extractPriceFilters($query);
        if (!empty($priceFilter['query']) && $priceFilter['query'] !== $query) {
            $query = $priceFilter['query'];
        }
        $asp_price_min = $priceFilter['price_min'] ?? null;
        $asp_price_max = $priceFilter['price_max'] ?? null;

        $rawQuery = $query; // preserve before AI rewrite for "did you mean" lookup
        $min_chars = (int)$settings['autocomplete_min_chars'];
        $cache_ttl = max(10, (int)$settings['cache_ttl']);
        $cache_enabled = !empty($settings['cache_enabled']);
        $search_cache = $this->getSearchCacheAdapter($settings['cache_type'], $cache_ttl);

        if ($query === '' || utf8_strlen($query) < $min_chars) {
            $this->response->setOutput(json_encode([
                'status' => 'too_short',
                'items' => [],
                'min_chars' => $min_chars
            ]));
            return;
        }

        $aiMeta = [
            'query' => $query,
            'expanded_terms' => [],
            'intent' => '',
            'applied' => false
        ];
        if (!empty($settings['ai_expand_query']) || !empty($settings['ai_rewrite_query']) || !empty($settings['ai_intent_detection'])) {
            try {
                $aiMeta = $this->asp->applyStoredQueryRule($query, $settings);
                if (!empty($aiMeta['query'])) {
                    $query = $aiMeta['query'];
                }
            } catch (\Throwable $e) {
                $this->asp->logSearchError('Stored AI query rule apply failed: ' . $e->getMessage());
            }
        }

        $cache_key = 'asp.live.result.' . md5(json_encode([
            'query' => $query,
            'autocomplete_mode' => (string)$settings['autocomplete_mode'],
            'mode' => $settings['mode'],
            'limit' => (int)$settings['autocomplete_limit'],
            'currency' => $this->session->data['currency'] ?? '',
            'language_id' => (int)$this->config->get('config_language_id'),
            'store_id' => (int)$this->config->get('config_store_id'),
            'group_collapse' => !empty($settings['autocomplete_group_collapse'])
        ]));

        if ($cache_enabled) {
            $cached = $search_cache->get($cache_key);
            if (is_array($cached) && isset($cached['cached_at']) && isset($cached['payload'])) {
                if ((time() - (int)$cached['cached_at']) <= $cache_ttl) {
                    $payload = $cached['payload'];
                    $payload['cached'] = true;
                    $this->asp->registerCacheHit(true);
                    $this->response->setOutput(json_encode($payload));
                    return;
                }
            }
        }
        $this->asp->registerCacheHit(false);

        $start_time = microtime(true);
        $limit = (int)$settings['autocomplete_limit'];
        // Variant-group collapse (mirror the catalog): over-fetch so that after
        // folding colour/size variants we still have ~$limit distinct items.
        $collapse = !empty($settings['autocomplete_group_collapse']);
        $fetchLimit = $collapse ? max($limit * 3, 60) : $limit;

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $filter_data = [
            'filter_name' => $query,
            'filter_tag' => $query,
            'filter_description' => 0,
            'start' => 0,
            'limit' => $limit
        ];

        $items = [];
        $seen = [];
        $typoCorrectionSuggestion = null; // from TypoCorrector (merged into DYM below)

        // ── Homoglyph normalisation (always on) ───────────────────────────────
        $query = $this->asp->normalizeHomoglyphs($query);

        // Two tiers of query terms:
        //  • primaryTerms  — high confidence (original + AI expansion + synonyms).
        //    Always searched; their hits are genuinely relevant.
        //  • recoveryTerms — wrong layout / script / typo guesses. Searched ONLY
        //    when the primary terms found nothing, because on a query that did
        //    match they just pad suggestions with loosely-related products
        //    (translit "скаб"→"skab" dragging in latin-described clothing).
        $primaryTerms = [$query];
        if (!empty($aiMeta['expanded_terms']) && is_array($aiMeta['expanded_terms'])) {
            $primaryTerms = array_merge($primaryTerms, $aiMeta['expanded_terms']);
        }
        // Synonyms: single-word → whole group ("найк" → nike, Nike); multi-word →
        // in-place substitution so descriptive words survive.
        $queryWordCount = count(array_filter(preg_split('/\s+/u', trim($query))));
        $synExtra = $queryWordCount > 1
            ? $this->asp->getSynonymWordVariants($query)
            : $this->asp->getSynonymTerms($query);
        if ($synExtra) {
            $primaryTerms = array_merge($primaryTerms, $synExtra);
        }
        $primaryTerms = array_values(array_unique(array_filter($primaryTerms, 'strlen')));

        $recoveryTerms = [];
        if (!empty($settings['layout_correction'])) {
            $recoveryTerms = array_merge($recoveryTerms, $this->asp->getLayoutVariants($query, 2));
        }
        if (!empty($settings['cross_lang_enabled'])) {
            $recoveryTerms = array_merge($recoveryTerms, $this->asp->getCrossLangVariants($query));
        }
        if (!empty($settings['enable_transliteration'])) {
            $recoveryTerms = array_merge($recoveryTerms, $this->asp->getTransliterationVariants($query));
        }
        // Typo correction also drives the "Did you mean?" hint.
        if (!empty($settings['enable_typo_correction'])) {
            $correction = $this->asp->getTypoCorrection($query);
            if ($correction !== null) {
                $recoveryTerms[]          = $correction;
                $typoCorrectionSuggestion = $correction;
            }
        }
        if (!empty($settings['enable_word_split'])) {
            $split = $this->asp->splitCompoundQuery($query);
            if ($split !== null) {
                $recoveryTerms[] = $split;
            }
        }
        // Per-token script repair (wrong keyboard layout "lkz"→"для", English
        // term spelled in Cyrillic "фпв"→"fpv") — searched last, only when the
        // primary terms didn't fill the list; the AND filter drops nonsense.
        if (!empty($settings['layout_correction']) || !empty($settings['cross_lang_enabled']) || !empty($settings['enable_transliteration'])) {
            $recoveryTerms = array_merge($recoveryTerms, $this->asp->getScriptRepairVariants($query));
        }
        $recoveryTerms = array_values(array_unique(array_filter($recoveryTerms, function ($t) use ($primaryTerms) {
            return $t !== '' && !in_array($t, $primaryTerms, true);
        })));

        // Full set — used by the native fallback branch below.
        $terms = array_values(array_unique(array_merge($primaryTerms, $recoveryTerms)));

        $show_price = (bool)$settings['autocomplete_show_price'];
        $show_stock = (bool)$settings['autocomplete_show_stock'];
        $show_image = (bool)$settings['autocomplete_show_image'];
        $can_show_price = !$this->config->get('config_customer_price') || $this->customer->isLogged();

        $mode = $settings['mode'];
        $engine_failed = false;

        $autocompleteMode = strtolower(trim((string)$settings['autocomplete_mode']));
        if (!in_array($autocompleteMode, ['products', 'categories', 'popular'], true)) {
            $autocompleteMode = 'products';
        }

        // Helper: fetch matching categories for the query (used in products mode alongside product results)
        $getCategoryMatches = function($searchQuery, $catLimit) {
            $lang  = (int)$this->config->get('config_language_id');
            $store = (int)$this->config->get('config_store_id');
            $rows = $this->db->query(
                "SELECT c.category_id, cd.name
                 FROM `" . DB_PREFIX . "category` c
                 INNER JOIN `" . DB_PREFIX . "category_description` cd
                    ON (cd.category_id = c.category_id AND cd.language_id = '" . $lang . "')
                 INNER JOIN `" . DB_PREFIX . "category_to_store` c2s
                    ON (c2s.category_id = c.category_id AND c2s.store_id = '" . $store . "')
                 WHERE c.status = 1
                   AND cd.name LIKE '%" . $this->db->escape($searchQuery) . "%'
                 GROUP BY c.category_id
                 ORDER BY cd.name ASC
                 LIMIT " . (int)$catLimit
            );
            $catItems = [];
            $seenNames = [];
            foreach ($rows->rows as $row) {
                $name = (string)$row['name'];
                if (isset($seenNames[$name])) {
                    continue;
                }
                $seenNames[$name] = true;
                $catItems[] = [
                    'category_id' => (int)$row['category_id'],
                    'name'        => $name,
                    'price'       => null,
                    'special'     => null,
                    'image'       => '',
                    'type'        => 'category',
                    'href'        => $this->url->link('product/category', 'path=' . (int)$row['category_id'])
                ];
            }
            return $catItems;
        };

        // Helper: fetch matching manufacturers/brands for the query.
        $getBrandMatches = function($searchQuery, $brandLimit) {
            $store = (int)$this->config->get('config_store_id');
            $rows = $this->db->query(
                "SELECT m.manufacturer_id, m.name
                 FROM `" . DB_PREFIX . "manufacturer` m
                 INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` m2s
                    ON (m2s.manufacturer_id = m.manufacturer_id AND m2s.store_id = '" . $store . "')
                 WHERE m.name LIKE '%" . $this->db->escape($searchQuery) . "%'
                 ORDER BY m.name ASC
                 LIMIT " . (int)$brandLimit
            );
            $brandItems = [];
            foreach ($rows->rows as $row) {
                $brandItems[] = [
                    'manufacturer_id' => (int)$row['manufacturer_id'],
                    'name'   => (string)$row['name'],
                    'price'  => null,
                    'special'=> null,
                    'image'  => '',
                    'type'   => 'brand',
                    'href'   => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . (int)$row['manufacturer_id']),
                ];
            }
            return $brandItems;
        };

        // Helper: wrap matched query substring in a highlight mark (server-side, HTML-safe).
        $hlName = function($name, $rawQuery) {
            if ($rawQuery === '') {
                return '';
            }
            // Decode first: OpenCart often stores names HTML-encoded in DB (e.g. "Jack &amp; Jones").
            // Re-encoding without decoding would produce double-encoding ("&amp;amp;").
            $safe    = htmlspecialchars(html_entity_decode((string)$name, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            $escaped = preg_quote($rawQuery, '/');
            return preg_replace('/(' . $escaped . ')/iu', '<mark class="asp-hl">$1</mark>', $safe);
        };

        if ($autocompleteMode === 'categories') {
            $lang = (int)$this->config->get('config_language_id');
            $store = (int)$this->config->get('config_store_id');
            $rows = $this->db->query(
                "SELECT c.category_id, cd.name
                 FROM `" . DB_PREFIX . "category` c
                 INNER JOIN `" . DB_PREFIX . "category_description` cd
                    ON (cd.category_id = c.category_id AND cd.language_id = '" . $lang . "')
                 INNER JOIN `" . DB_PREFIX . "category_to_store` c2s
                    ON (c2s.category_id = c.category_id AND c2s.store_id = '" . $store . "')
                 WHERE c.status = 1
                   AND cd.name LIKE '%" . $this->db->escape($query) . "%'
                 ORDER BY cd.name ASC
                 LIMIT " . $limit
            );

            foreach ($rows->rows as $row) {
                $items[] = [
                    'category_id' => (int)$row['category_id'],
                    'name' => $row['name'],
                    'price' => null,
                    'special' => null,
                    'image' => '',
                    'type' => 'category',
                    'href' => $this->url->link('product/category', 'path=' . (int)$row['category_id'])
                ];
            }
        } elseif ($autocompleteMode === 'popular') {
            $rows = $this->db->query(
                "SELECT `query`, COUNT(*) AS total
                 FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE `results` > 0
                   AND `query` LIKE '" . $this->db->escape($query) . "%'
                 GROUP BY `query`
                 ORDER BY total DESC
                 LIMIT " . $limit
            );

            foreach ($rows->rows as $row) {
                $q = trim((string)$row['query']);
                if ($q === '') {
                    continue;
                }
                $items[] = [
                    'name' => $q,
                    'price' => null,
                    'special' => null,
                    'image' => '',
                    'type' => 'query',
                    'href' => $this->url->link('extension/module/oc_kit_advanced_search_pro/results', 'search=' . urlencode($q))
                ];
            }
        } elseif (in_array($mode, ['manticore', 'sphinx', 'hybrid'], true)) {
            try {
                $engine = $this->asp->getSearchEngine(array_merge($settings, [
                    'mode' => $mode,
                    // Keep autocomplete latency stable; semantic hybrid is for full result page.
                    'semantic_runtime' => 0
                ]));
                // Search the original query first — its BM25 ranking is canonical.
                // Variants (layout, cross-lang, translit, synonyms) are only mixed in
                // when the original could not fill the result list. Otherwise variant
                // hits with weak per-word matches ("synya" → Sytong scopes) flood the
                // top positions and bury truly relevant products.
                $ids = [];
                $idSeen = [];
                $perTermLimit = max($fetchLimit, 20);
                $mergeIds = function (array $found) use (&$ids, &$idSeen, $perTermLimit) {
                    foreach ($found as $pid) {
                        $pid = (int)$pid;
                        if (!isset($idSeen[$pid])) { $idSeen[$pid] = true; $ids[] = $pid; }
                    }
                    return count($ids) >= $perTermLimit;
                };

                // Pass 1 — primary terms (original first for canonical BM25, then
                // synonyms / AI expansion). POS relaxation (drop attribute
                // adjectives, keep the product-type noun) applies ONLY to the
                // original query ($i === 0); synonym/expansion terms are matched
                // strictly (AND), so a multi-word synonym like "туалетна вода"
                // can't relax to the over-common noun "вода" and flood.
                // Strict AND pass first — original query + synonyms, no relaxation.
                // A configured synonym ("павер банк" → "power bank") must get
                // searched even when the original query would otherwise relax into
                // fuzzy noise and fill the list before the synonym is reached.
                foreach ($primaryTerms as $term) {
                    if ($mergeIds($engine->search($term, $perTermLimit, 0, false)['ids'])) break;
                }
                // Relaxation pass — POS-drop attribute adjectives from the ORIGINAL
                // query only, and only when the strict pass left the list short, so a
                // real synonym / AND hit always outranks a relaxed fuzzy one.
                if (count($ids) < $perTermLimit) {
                    $mergeIds($engine->search($query, $perTermLimit, 0, true)['ids']);
                }

                // Pass 2 — recovery terms (layout / translit / typo) ONLY when the
                // primary terms found nothing, i.e. the whole query was in the
                // wrong script/layout. On a query that matched, these would just
                // pad the list with loosely-related products. Matched strictly.
                if (empty($ids)) {
                    foreach ($recoveryTerms as $term) {
                        if ($mergeIds($engine->search($term, $perTermLimit, 0, false)['ids'])) break;
                    }
                }

                // Semantic layer in autocomplete — cascade: fire only when the
                // fast lexical step left the suggestion list unfilled. Embedding
                // every keystroke (an OpenAI call each) is what made autocomplete
                // sluggish; this keeps typed-prefix matches instant and only pays
                // for AI when plain search came up short.
                // Fire ONLY when the lexical step found (almost) nothing, i.e. a
                // meaning-based query whose keywords don't match ("що взути на
                // пробіжку"). When keywords already matched (e.g. "скаб" → scrubs),
                // blending KNN neighbours of a short/typo embedding just unions in
                // loosely-related products (clothing) and buries the real hits.
                if ($mode === 'hybrid' && !empty($settings['vector_enabled'])
                    && !empty($settings['autocomplete_semantic']) && count($ids) < 2
                ) {
                    $semScores = $this->asp->semanticSearch($query, $settings, 100);
                    if ($semScores) {
                        $ids = $this->asp->blendVectorResults($ids, $semScores, $settings['vector_ratio'] ?? 30);
                    }
                }

                // Apply stock-first / boost ranking if any rank flags are set.
                // filterProductIds also validates status=1 and date_available,
                // so we always run it when we have IDs from an external engine.
                if ($ids) {
                    $aspOosMode = (string)($settings['oos_mode'] ?? 'show');
                    $ranked = $this->asp->filterProductIds($ids, [
                        'limit'               => $fetchLimit,
                        'start'               => 0,
                        'price_min'           => $asp_price_min,
                        'price_max'           => $asp_price_max,
                        'stock'               => $aspOosMode === 'hide' ? 1 : null,
                        'rank_stock_first'    => !empty($settings['rank_stock_first']) || $aspOosMode === 'last',
                        'rank_boost_new'      => !empty($settings['rank_boost_new']),
                        'rank_boost_popular'  => !empty($settings['rank_boost_popular']),
                        'rank_boost_category' => false,
                    ]);
                    $ids = $ranked['ids'];
                }

                // Fold product variant groups (mirror the catalog grouping),
                // keeping the best-ranked match of each group, then trim back to
                // the display limit.
                if ($ids && $collapse) {
                    $ids = array_slice($this->asp->collapseProductGroups($ids, (int)($settings['group_collapse_attribute'] ?? 0)), 0, $limit);
                }

                if ($ids) {
                    foreach ($ids as $id) {
                        $product = $this->model_catalog_product->getProduct($id);
                        if (!$product) {
                            continue;
                        }
                        $product_id = (int)$product['product_id'];
                        if (isset($seen[$product_id])) {
                            continue;
                        }
                        $seen[$product_id] = true;

                        $image = '';
                        if ($show_image) {
                            if (!empty($product['image'])) {
                                $image = $this->model_tool_image->resize($product['image'], 60, 60);
                            } else {
                                $image = $this->model_tool_image->resize('placeholder.png', 60, 60);
                            }
                        }

                        $price = null;
                        $special = null;
                        $discount_pct = 0;
                        if ($show_price && $can_show_price) {
                            $priceRaw = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                            $price    = $this->currency->format($priceRaw, $this->session->data['currency']);
                            if (!empty($product['special'])) {
                                $specialRaw   = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                                $special      = $this->currency->format($specialRaw, $this->session->data['currency']);
                                $discount_pct = $priceRaw > 0 ? (int)round((1 - $specialRaw / $priceRaw) * 100) : 0;
                            }
                        }

                        $item = [
                            'product_id'   => (int)$product['product_id'],
                            'name'         => $product['name'],
                            'name_hl'      => $hlName($product['name'], $rawQuery),
                            'price'        => $price,
                            'special'      => $special,
                            'discount_pct' => $discount_pct,
                            'image'        => $image,
                            'model'        => $product['model'],
                            'href'         => $this->url->link('product/product', 'product_id=' . (int)$product['product_id'])
                        ];

                        if ($show_stock) {
                            $item['in_stock']     = (int)$product['quantity'] > 0;
                            $item['stock_status'] = $product['stock_status'];
                        }

                        $items[] = $item;
                        if (count($items) >= $limit) {
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $engine_failed = true;
                $this->asp->logSearchError('Engine search failed (' . $mode . '): ' . $e->getMessage());
            }
        }

        // Native fallback fires ONLY in degraded mode (native engine selected, or
        // Manticore failed mid-request) — NOT merely because a healthy Manticore
        // returned few/zero hits. An authoritative empty result is correct; a
        // native MySQL LIKE retry on a no-match query floods the list with the
        // most common query word (e.g. "парфуми чоловічі" → every men's product
        // via "чоловічі"). This matches the CascadeSearchEngine design: native is
        // a degraded mode, not a relevance retry.
        if ($autocompleteMode === 'products' && ($mode === 'native' || $engine_failed)) {
            $synonyms = $this->asp->getSynonymTerms($query);
            if ($synonyms) {
                $terms = array_values(array_unique(array_merge($terms, $synonyms)));
            }

            // Use ASP NativeSearchEngine: searches name, categories, attributes, manufacturer, etc.
            // This is consistent with the full search page and finds products by category name.
            $nativeCandidates = max(50, $limit * 6);
            $nativeIds = [];
            $nativeSeen = [];
            // Force the native engine here — this branch is the fallback used when the
            // external engine (Manticore/Sphinx) failed or returned nothing.
            $nativeSettings = array_merge($settings, ['mode' => 'native']);
            foreach ($terms as $term) {
                try {
                    $termResult = $this->asp->getSearchEngine($nativeSettings)->search($term, $nativeCandidates, 0);
                    foreach ($termResult['ids'] as $pid) {
                        $pid = (int)$pid;
                        if (!isset($nativeSeen[$pid])) {
                            $nativeSeen[$pid] = true;
                            $nativeIds[] = $pid;
                        }
                    }
                } catch (\Throwable $e) {}
            }

            if ($nativeIds) {
                $aspOosMode = (string)($settings['oos_mode'] ?? 'show');
                $ranked = $this->asp->filterProductIds($nativeIds, [
                    'limit'               => $limit,
                    'start'               => 0,
                    'price_min'           => $asp_price_min,
                    'price_max'           => $asp_price_max,
                    'stock'               => $aspOosMode === 'hide' ? 1 : null,
                    'rank_stock_first'    => !empty($settings['rank_stock_first']) || $aspOosMode === 'last',
                    'rank_boost_new'      => !empty($settings['rank_boost_new']),
                    'rank_boost_popular'  => !empty($settings['rank_boost_popular']),
                    'rank_boost_category' => false,
                ]);
                $rankedIds = $ranked['ids'];
                if (!empty($settings['enable_mmr']) && count($rankedIds) > 1) {
                    $rankedIds = $this->asp->mmrRerank(
                        $rankedIds,
                        (float)($settings['mmr_lambda'] ?? 0.5),
                        $limit
                    );
                }
                foreach ($rankedIds as $product_id) {
                    $product_id = (int)$product_id;
                    if (isset($seen[$product_id])) continue;
                    $seen[$product_id] = true;
                    $product = $this->model_catalog_product->getProduct($product_id);
                    if (!$product) continue;

                    $image = '';
                    if ($show_image) {
                        if (!empty($product['image'])) {
                            $image = $this->model_tool_image->resize($product['image'], 60, 60);
                        } else {
                            $image = $this->model_tool_image->resize('placeholder.png', 60, 60);
                        }
                    }

                    $price = null;
                    $special = null;
                    $discount_pct = 0;
                    if ($show_price && $can_show_price) {
                        $priceRaw = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                        $price    = $this->currency->format($priceRaw, $this->session->data['currency']);
                        if (!empty($product['special'])) {
                            $specialRaw   = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                            $special      = $this->currency->format($specialRaw, $this->session->data['currency']);
                            $discount_pct = $priceRaw > 0 ? (int)round((1 - $specialRaw / $priceRaw) * 100) : 0;
                        }
                    }

                    $item = [
                        'product_id'   => $product_id,
                        'name'         => $product['name'],
                        'name_hl'      => $hlName($product['name'], $rawQuery),
                        'price'        => $price,
                        'special'      => $special,
                        'discount_pct' => $discount_pct,
                        'image'        => $image,
                        'model'        => $product['model'],
                        'href'         => $this->url->link('product/product', 'product_id=' . $product_id)
                    ];

                    if ($show_stock) {
                        $item['in_stock']     = (int)$product['quantity'] > 0;
                        $item['stock_status'] = $product['stock_status'];
                    }

                    $items[] = $item;
                }
            }

            // Product ID exact match: when query is a pure number search by product_id directly.
            if (count($items) < $limit && ctype_digit($query)) {
                $pidRows = $this->db->query(
                    "SELECT p.product_id FROM `" . DB_PREFIX . "product` p
                     WHERE p.status = 1 AND p.date_available <= NOW()
                       AND p.product_id = '" . (int)$query . "'
                     LIMIT 1"
                );
                foreach ($pidRows->rows as $pidRow) {
                    $product_id = (int)$pidRow['product_id'];
                    if (!isset($seen[$product_id])) {
                        $seen[$product_id] = true;
                        $product = $this->model_catalog_product->getProduct($product_id);
                        if ($product) {
                            $image = '';
                            if ($show_image) {
                                $image = !empty($product['image'])
                                    ? $this->model_tool_image->resize($product['image'], 60, 60)
                                    : $this->model_tool_image->resize('placeholder.png', 60, 60);
                            }
                            $price = $special = null;
                            $discount_pct = 0;
                            if ($show_price && $can_show_price) {
                                $priceRaw   = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                                $price      = $this->currency->format($priceRaw, $this->session->data['currency']);
                                if (!empty($product['special'])) {
                                    $specialRaw   = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                                    $special      = $this->currency->format($specialRaw, $this->session->data['currency']);
                                    $discount_pct = $priceRaw > 0 ? (int)round((1 - $specialRaw / $priceRaw) * 100) : 0;
                                }
                            }
                            $item = [
                                'product_id'   => $product_id,
                                'name'         => $product['name'],
                                'name_hl'      => $hlName($product['name'], $rawQuery),
                                'price'        => $price,
                                'special'      => $special,
                                'discount_pct' => $discount_pct,
                                'image'        => $image,
                                'model'        => $product['model'],
                                'type'         => 'product_id',
                                'href'         => $this->url->link('product/product', 'product_id=' . $product_id),
                            ];
                            if ($show_stock) {
                                $item['in_stock']     = (int)$product['quantity'] > 0;
                                $item['stock_status'] = $product['stock_status'];
                            }
                            array_unshift($items, $item); // prepend — ID match is most relevant
                        }
                    }
                }
            }

            // SKU / model search: find products whose model/article code matches
            // the query or a separator-stripped variant (e.g. "NIK-AF1-07" ↔ "NIKAF107").
            if (count($items) < $limit) {
                $skuNorm = strtoupper(preg_replace('/[\s\-\.\/]/u', '', $query));
                if ($skuNorm !== '') {
                    $skuRows = $this->db->query(
                        "SELECT p.product_id
                         FROM `" . DB_PREFIX . "product` p
                         WHERE p.status = 1
                           AND p.date_available <= NOW()
                           AND (p.model LIKE '%" . $this->db->escape($query) . "%'
                            OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(p.model, '-', ''), '.', ''), '/', ''), ' ', ''))
                               LIKE '%" . $this->db->escape($skuNorm) . "%')
                         ORDER BY p.sort_order ASC
                         LIMIT " . ($limit - count($items))
                    );
                    foreach ($skuRows->rows as $skuRow) {
                        $product_id = (int)$skuRow['product_id'];
                        if (isset($seen[$product_id])) continue;
                        $seen[$product_id] = true;
                        $product = $this->model_catalog_product->getProduct($product_id);
                        if (!$product) continue;

                        $image = '';
                        if ($show_image) {
                            $image = !empty($product['image'])
                                ? $this->model_tool_image->resize($product['image'], 60, 60)
                                : $this->model_tool_image->resize('placeholder.png', 60, 60);
                        }
                        $price = $special = null;
                        $discount_pct = 0;
                        if ($show_price && $can_show_price) {
                            $priceRaw = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                            $price    = $this->currency->format($priceRaw, $this->session->data['currency']);
                            if (!empty($product['special'])) {
                                $specialRaw   = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                                $special      = $this->currency->format($specialRaw, $this->session->data['currency']);
                                $discount_pct = $priceRaw > 0 ? (int)round((1 - $specialRaw / $priceRaw) * 100) : 0;
                            }
                        }
                        $item = [
                            'product_id'   => $product_id,
                            'name'         => $product['name'],
                            'name_hl'      => $hlName($product['name'], $rawQuery),
                            'price'        => $price,
                            'special'      => $special,
                            'discount_pct' => $discount_pct,
                            'image'        => $image,
                            'model'        => $product['model'],
                            'type'         => 'sku',
                            'href'         => $this->url->link('product/product', 'product_id=' . $product_id),
                        ];
                        if ($show_stock) {
                            $item['in_stock']     = (int)$product['quantity'] > 0;
                            $item['stock_status'] = $product['stock_status'];
                        }
                        $items[] = $item;
                    }
                }
            }
        }

        // Prepend matching brands, then categories — search across all term variants
        // (layout-corrected, cross-lang, synonyms) so chips appear for mistyped queries.
        if ($autocompleteMode === 'products' && !empty($settings['autocomplete_show_brands'])) {
            $brandLimit = max(1, min(5, (int)$settings['autocomplete_brand_limit']));
            $brandItems = [];
            $seenBrandIds = [];
            foreach ($terms as $term) {
                foreach ($getBrandMatches($term, $brandLimit) as $bi) {
                    if (!isset($seenBrandIds[$bi['manufacturer_id']])) {
                        $seenBrandIds[$bi['manufacturer_id']] = true;
                        $bi['name_hl'] = $hlName($bi['name'], $rawQuery);
                        $brandItems[] = $bi;
                        if (count($brandItems) >= $brandLimit) break 2;
                    }
                }
            }
            if ($brandItems) {
                $items = array_merge($brandItems, $items);
            }
        }

        if ($autocompleteMode === 'products' && !empty($settings['autocomplete_show_categories'])) {
            $catLimit  = max(1, min(5, (int)$settings['autocomplete_category_limit']));
            $catItems  = [];
            $seenCatIds = [];
            $seenCatNames = [];
            foreach ($terms as $term) {
                foreach ($getCategoryMatches($term, $catLimit) as $ci) {
                    if (!isset($seenCatIds[$ci['category_id']]) && !isset($seenCatNames[$ci['name']])) {
                        $seenCatIds[$ci['category_id']] = true;
                        $seenCatNames[$ci['name']] = true;
                        $ci['name_hl'] = $hlName($ci['name'], $rawQuery);
                        $catItems[] = $ci;
                        if (count($catItems) >= $catLimit) break 2;
                    }
                }
            }
            if ($catItems) {
                $items = array_merge($catItems, $items);
            }
        }

        $latency_ms = (int)round((microtime(true) - $start_time) * 1000);
        $this->asp->logQuery($query, count($items), $latency_ms, $this->session->getId());
        $history = $this->session->data['asp_recent_queries'] ?? [];
        $history = array_values(array_filter(array_map('trim', $history)));
        $history = array_values(array_diff($history, [$query]));
        array_unshift($history, $query);
        $history = array_slice($history, 0, 10);
        $this->session->data['asp_recent_queries'] = $history;

        // "Did you mean?" — typo corrector result takes priority;
        // fallback to prefix-match from query log when 0 results.
        $didYouMean = null;
        if ($typoCorrectionSuggestion !== null) {
            $didYouMean = [
                'query'      => $typoCorrectionSuggestion,
                'search_url' => $this->url->link('extension/module/oc_kit_advanced_search_pro/results', 'search=' . urlencode($typoCorrectionSuggestion)),
            ];
        } elseif (count($items) === 0 && mb_strlen($rawQuery) >= 3) {
            $prefix = mb_substr($rawQuery, 0, max(3, mb_strlen($rawQuery) - 1));
            $dymRow = $this->db->query(
                "SELECT `query` FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE `results` > 0
                   AND `query` LIKE '" . $this->db->escape($prefix) . "%'
                   AND `query` <> '" . $this->db->escape($rawQuery) . "'
                 GROUP BY `query`
                 ORDER BY COUNT(*) DESC
                 LIMIT 1"
            )->row;
            if ($dymRow) {
                $dymQuery = trim((string)$dymRow['query']);
                $didYouMean = [
                    'query'      => $dymQuery,
                    'search_url' => $this->url->link('extension/module/oc_kit_advanced_search_pro/results', 'search=' . urlencode($dymQuery)),
                ];
            }
        }

        // Attach each result's primary category (for the grouped search view).
        // Only product items carry product_id — category/brand suggestion items
        // don't, so collect ids defensively (a missing key would emit a notice
        // that corrupts the JSON body).
        if (!empty($items)) {
            $catPids = [];
            foreach ($items as $it) {
                if (!empty($it['product_id'])) { $catPids[] = (int)$it['product_id']; }
            }
            $catMap = [];
            if (!empty($catPids)) {
                $catLang  = (int)$this->config->get('config_language_id');
                $catStore = (int)$this->config->get('config_store_id');
                $catRows = $this->db->query(
                    "SELECT ptc.product_id, ptc.category_id, cd.name
                     FROM " . DB_PREFIX . "product_to_category ptc
                     JOIN " . DB_PREFIX . "category c ON c.category_id = ptc.category_id AND c.status = '1'
                     JOIN " . DB_PREFIX . "category_to_store c2s ON c2s.category_id = c.category_id AND c2s.store_id = '" . $catStore . "'
                     JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = ptc.category_id AND cd.language_id = '" . $catLang . "'
                     WHERE ptc.product_id IN (" . implode(',', $catPids) . ")
                     ORDER BY ptc.product_id, c.sort_order"
                );
                foreach ($catRows->rows as $cr) {
                    $cpid = (int)$cr['product_id'];
                    if (!isset($catMap[$cpid])) {
                        $catMap[$cpid] = ['id' => (int)$cr['category_id'], 'name' => $cr['name']];
                    }
                }
            }
            foreach ($items as &$catIt) {
                $pid2 = !empty($catIt['product_id']) ? (int)$catIt['product_id'] : 0;
                $catIt['category'] = ($pid2 && isset($catMap[$pid2])) ? $catMap[$pid2] : null;
            }
            unset($catIt);
        }

        // Facet counts — categories + manufacturers from result product IDs.
        $facets = [];
        if (!empty($settings['autocomplete_show_facets']) && count($items) > 0) {
            $productIds = [];
            foreach ($items as $it) {
                if (!empty($it['product_id'])) {
                    $productIds[] = (int)$it['product_id'];
                }
            }
            if ($productIds) {
                $langId  = (int)$this->config->get('config_language_id');
                $idList  = implode(',', $productIds);

                // Top categories
                $catRows = $this->db->query(
                    "SELECT cd.name, p2c.category_id, COUNT(*) AS cnt
                     FROM `" . DB_PREFIX . "product_to_category` p2c
                     INNER JOIN `" . DB_PREFIX . "category_description` cd
                         ON (cd.category_id = p2c.category_id AND cd.language_id = '" . $langId . "')
                     WHERE p2c.product_id IN (" . $idList . ")
                     GROUP BY p2c.category_id
                     ORDER BY cnt DESC
                     LIMIT 4"
                );
                foreach ($catRows->rows as $row) {
                    $facets[] = [
                        'type'  => 'category',
                        'name'  => (string)$row['name'],
                        'count' => (int)$row['cnt'],
                        'href'  => $this->url->link('product/category', 'path=' . (int)$row['category_id']),
                    ];
                }

                // Top manufacturers
                $mfRows = $this->db->query(
                    "SELECT m.name, p.manufacturer_id, COUNT(*) AS cnt
                     FROM `" . DB_PREFIX . "product` p
                     INNER JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = p.manufacturer_id
                     WHERE p.product_id IN (" . $idList . ")
                       AND p.manufacturer_id > 0
                     GROUP BY p.manufacturer_id
                     ORDER BY cnt DESC
                     LIMIT 3"
                );
                foreach ($mfRows->rows as $row) {
                    $facets[] = [
                        'type'  => 'manufacturer',
                        'name'  => (string)$row['name'],
                        'count' => (int)$row['cnt'],
                        'href'  => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . (int)$row['manufacturer_id']),
                    ];
                }
            }
        }

        $payload = [
            'status'        => 'ok',
            'query'         => $query,
            'items'         => $items,
            'count'         => count($items),
            'took_ms'       => $latency_ms,
            'ai_intent'     => $aiMeta['intent'] ?? '',
            'ai_applied'    => !empty($aiMeta['applied']),
            'search_url'    => $this->url->link('extension/module/oc_kit_advanced_search_pro/results', 'search=' . urlencode($query)),
            'recent_queries'=> $history,
            'did_you_mean'  => $didYouMean,
            'facets'        => $facets,
        ];

        if ($cache_enabled) {
            $search_cache->set($cache_key, [
                'cached_at' => time(),
                'payload' => $payload
            ]);
        }

        $this->response->setOutput(json_encode($payload));
    }

    /**
     * Empty-state dashboard for the live-search dropdown: popular queries,
     * most-viewed products and top brands. Loaded by the widget on first focus.
     */
    public function dashboard() {
        $this->response->addHeader('Content-Type: application/json');

        $settings = $this->asp->getSettings([
            'status'                   => 0,
            'autocomplete_enabled'     => 1,
            'autocomplete_show_price'  => 1,
            'autocomplete_show_image'  => 1,
            'popular_tags_enabled'     => 1,
            'popular_tags_source'      => 'manual',
            'popular_tags'             => '',
            'popular_tags_limit'       => 10,
            'popular_products_enabled' => 1,
            'popular_products_source'  => 'auto',
            'popular_products'         => '',
            'popular_products_limit'   => 8,
            'popular_brands_enabled'   => 1,
            'popular_brands_source'    => 'auto',
            'popular_brands'           => '',
            'popular_brands_limit'     => 10,
        ]);

        if (empty($settings['status']) || empty($settings['autocomplete_enabled'])) {
            $this->response->setOutput(json_encode(['status' => 'off']));
            return;
        }

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $currency       = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        $can_show_price = $this->customer->isLogged() || !$this->config->get('config_customer_price');
        $show_price     = !empty($settings['autocomplete_show_price']) && $can_show_price;
        $show_image     = !empty($settings['autocomplete_show_image']);

        // Popular queries / tags — off (disabled) | manual (admin-curated tags
        // with an optional per-language URL) | auto (top queries from the search
        // log). Manual but empty → show nothing. Each item: {text, href}.
        $queries = [];
        $qLimit = max(1, (int)($settings['popular_tags_limit'] ?: 10));
        if (!empty($settings['popular_tags_enabled'])) {
            if (($settings['popular_tags_source'] ?? 'manual') === 'manual') {
                $lang = (string)$this->config->get('config_language');
                $lng  = (strpos($lang, 'ru') === 0) ? 'ru' : ((strpos($lang, 'en') === 0) ? 'en' : 'uk');
                // OC3 Request applies htmlspecialchars to POST — decode before json_decode.
                $rawTags = html_entity_decode(trim((string)($settings['popular_tags'] ?? '')), ENT_QUOTES, 'UTF-8');
                $parsed  = $rawTags !== '' ? json_decode($rawTags, true) : [];
                if (is_array($parsed)) {
                    foreach ($parsed as $t) {
                        $names = (isset($t['names']) && is_array($t['names'])) ? $t['names'] : [];
                        $urls  = (isset($t['urls'])  && is_array($t['urls']))  ? $t['urls']  : [];
                        $text  = trim((string)($names[$lng] ?? $names['uk'] ?? $names['ru'] ?? $names['en'] ?? ''));
                        if ($text === '') { continue; }
                        $href  = trim((string)($urls[$lng] ?? $urls['uk'] ?? ''));
                        if ($href === '#') { $href = ''; } // placeholder URL → treat as "search this tag"
                        $queries[] = ['text' => $text, 'href' => $href];
                        if (count($queries) >= $qLimit) { break; }
                    }
                }
            } else {
                try {
                    $raw = $this->asp->getPopularQueries(40, 60);
                    // Drop junk: queries < 3 chars, and typed partials that are a prefix
                    // of a longer popular query (e.g. "сіт"/"сітк" when "сітка" exists).
                    $raw = array_values(array_filter($raw, function ($q) { return mb_strlen(trim((string)$q)) >= 3; }));
                    foreach ($raw as $q) {
                        $isPrefix = false;
                        foreach ($raw as $other) {
                            if ($other !== $q && mb_strlen($other) > mb_strlen($q) && mb_stripos($other, $q) === 0) { $isPrefix = true; break; }
                        }
                        if (!$isPrefix) { $queries[] = ['text' => $q, 'href' => '']; }
                        if (count($queries) >= $qLimit) { break; }
                    }
                } catch (\Throwable $e) {}
            }
        }

        // Shared card builder (most-viewed + recently-viewed).
        $buildCard = function ($product_id) use ($show_image, $show_price, $currency) {
            $product = $this->model_catalog_product->getProduct($product_id);
            if (!$product) { return null; }

            $image = '';
            if ($show_image) {
                $src   = !empty($product['image']) ? $product['image'] : 'placeholder.png';
                $image = $this->model_tool_image->resize($src, 200, 200);
            }

            $price = null; $special = null; $discount_pct = 0;
            if ($show_price) {
                $priceRaw = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                $price    = $this->currency->format($priceRaw, $currency);
                if (!empty($product['special'])) {
                    $specialRaw   = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                    $special      = $this->currency->format($specialRaw, $currency);
                    $discount_pct = $priceRaw > 0 ? (int)round((1 - $specialRaw / $priceRaw) * 100) : 0;
                }
            }

            return [
                'product_id'   => (int)$product['product_id'],
                'name'         => $product['name'],
                'price'        => $price,
                'special'      => $special,
                'discount_pct' => $discount_pct,
                'image'        => $image,
                'in_stock'     => (int)$product['quantity'] > 0,
                'href'         => html_entity_decode($this->url->link('product/product', 'product_id=' . (int)$product['product_id']), ENT_QUOTES, 'UTF-8'),
            ];
        };

        // Popular products — off | manual (admin-picked, shown in the saved order)
        // | auto (most-viewed). Manual but empty → show nothing.
        $products = [];
        $pLimit = max(1, (int)($settings['popular_products_limit'] ?: 8));
        if (!empty($settings['popular_products_enabled'])) {
            if (($settings['popular_products_source'] ?? 'auto') === 'manual') {
                $pids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($settings['popular_products'] ?? ''))))));
                $pids = array_slice($pids, 0, $pLimit);
                if ($pids) {
                    $inList = implode(',', $pids);
                    $validRows = $this->db->query(
                        "SELECT product_id FROM `" . DB_PREFIX . "product`
                         WHERE product_id IN ($inList) AND status = '1' AND date_available <= NOW()"
                    );
                    $valid = [];
                    foreach ($validRows->rows as $vr) { $valid[(int)$vr['product_id']] = true; }
                    foreach ($pids as $pid) {
                        if (isset($valid[$pid])) { $card = $buildCard($pid); if ($card) { $products[] = $card; } }
                    }
                }
            } else {
                $prodRows = $this->db->query(
                    "SELECT p.product_id FROM `" . DB_PREFIX . "product` p
                     WHERE p.status = '1' AND p.date_available <= NOW()
                     ORDER BY p.viewed DESC, p.date_added DESC
                     LIMIT " . (int)$pLimit
                );
                foreach ($prodRows->rows as $pr) {
                    $card = $buildCard($pr['product_id']);
                    if ($card) { $products[] = $card; }
                }
            }
        }

        // Recently-viewed products — IDs come from client localStorage (?viewed=1,2,3).
        $viewed = [];
        $viewedRaw = isset($this->request->get['viewed']) ? (string)$this->request->get['viewed'] : '';
        if ($viewedRaw !== '') {
            $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', $viewedRaw))))), 0, 12);
            if ($ids) {
                $inList = implode(',', $ids);
                $validRows = $this->db->query(
                    "SELECT product_id FROM `" . DB_PREFIX . "product`
                     WHERE product_id IN ($inList) AND status = '1' AND date_available <= NOW()"
                );
                $validSet = [];
                foreach ($validRows->rows as $vr) { $validSet[(int)$vr['product_id']] = true; }
                foreach ($ids as $vid) {
                    if (isset($validSet[$vid])) {
                        $card = $buildCard($vid);
                        if ($card) { $viewed[] = $card; }
                    }
                }
            }
        }

        // Popular brands — off | manual (admin-picked, shown in the saved order)
        // | auto (top by active-product count). Manual but empty → show nothing.
        $brands = [];
        $bLimit = max(1, (int)($settings['popular_brands_limit'] ?: 10));
        $brandHref = function ($mid) {
            return html_entity_decode($this->url->link('product/manufacturer.info', 'manufacturer_id=' . (int)$mid), ENT_QUOTES, 'UTF-8');
        };
        if (!empty($settings['popular_brands_enabled'])) {
            if (($settings['popular_brands_source'] ?? 'auto') === 'manual') {
                $mids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($settings['popular_brands'] ?? ''))))));
                $mids = array_slice($mids, 0, $bLimit);
                if ($mids) {
                    $inList   = implode(',', $mids);
                    $rows     = $this->db->query("SELECT manufacturer_id, name FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id IN ($inList)");
                    $nameMap  = [];
                    foreach ($rows->rows as $r) { $nameMap[(int)$r['manufacturer_id']] = (string)$r['name']; }
                    foreach ($mids as $mid) {
                        if (isset($nameMap[$mid]) && $nameMap[$mid] !== '') {
                            $brands[] = ['name' => $nameMap[$mid], 'href' => $brandHref($mid)];
                        }
                    }
                }
            } else {
                $brandRows = $this->db->query(
                    "SELECT m.manufacturer_id, m.name, COUNT(p.product_id) AS cnt
                     FROM `" . DB_PREFIX . "manufacturer` m
                     JOIN `" . DB_PREFIX . "product` p
                       ON p.manufacturer_id = m.manufacturer_id AND p.status = '1'
                     GROUP BY m.manufacturer_id, m.name
                     ORDER BY cnt DESC
                     LIMIT " . (int)$bLimit
                );
                foreach ($brandRows->rows as $br) {
                    if ((string)$br['name'] === '') { continue; }
                    $brands[] = ['name' => $br['name'], 'href' => $brandHref((int)$br['manufacturer_id'])];
                }
            }
        }

        $this->response->setOutput(json_encode([
            'status'   => 'ok',
            'queries'  => array_values($queries),
            'products' => $products,
            'brands'   => $brands,
            'viewed'   => $viewed,
        ]));
    }

    public function cron() {
        $this->response->addHeader('Content-Type: application/json');
        $settings = $this->asp->getSettings([
            'cron_enabled' => 0,
            'cron_key' => '',
            'cron_type' => 'full',
            'cron_lock_wait_sec' => 0
        ]);

        $key = $this->request->get['key'] ?? '';
        $storedKey = (string)$settings['cron_key'];
        if (!$settings['cron_enabled'] || $key === '' || $storedKey === '' || !hash_equals($storedKey, (string)$key)) {
            $this->load->model('extension/module/oc_kit_advanced_search_pro');
            $this->model_extension_module_oc_kit_advanced_search_pro->logCron('cron', 'error', 'Invalid key or cron disabled');
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'Invalid key'
            ]));
            return;
        }

        $type = $this->request->get['type'] ?? $settings['cron_type'];
        $limit = isset($this->request->get['limit']) ? max(1, min(5000, (int)$this->request->get['limit'])) : 500;
        $offset = isset($this->request->get['offset']) ? max(0, (int)$this->request->get['offset']) : 0;
        $lockWaitSec = max(0, min(30, (int)$settings['cron_lock_wait_sec']));
        $lockName = 'asp_cron_' . preg_replace('/[^a-z0-9_]/i', '_', (string)$type);

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        if (!$this->acquireDbLock($lockName, $lockWaitSec)) {
            $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'error', 'Skipped: cron lock is already held');
            $this->response->setOutput(json_encode([
                'status' => 'locked',
                'message' => 'Cron run is already in progress for this type',
                'type' => $type
            ]));
            return;
        }

        $processed = 0;
        $extra = [];
        try {
            if ($type === 'full') {
                $processed = $this->model_extension_module_oc_kit_advanced_search_pro->reindexAll($limit, $offset);
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Reindexed: ' . $processed . ' (offset ' . $offset . ')');
            } elseif ($type === 'embedding') {
                $processed = $this->model_extension_module_oc_kit_advanced_search_pro->processEmbeddingQueue($limit);
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Embeddings processed: ' . $processed);
            } elseif ($type === 'sync_modified') {
                $minutes = isset($this->request->get['minutes']) ? max(1, min(10080, (int)$this->request->get['minutes'])) : 180;
                $processed = $this->model_extension_module_oc_kit_advanced_search_pro->syncModifiedProducts($limit, $minutes);
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Queued modified products: ' . $processed . ' (minutes ' . $minutes . ')');
            } elseif ($type === 'ai_rules') {
                $days     = isset($this->request->get['days'])      ? max(1, min(365,  (int)$this->request->get['days']))      : 30;
                $minCount = isset($this->request->get['min_count']) ? max(1, min(1000, (int)$this->request->get['min_count'])) : 2;
                $refreshDays = isset($this->request->get['refresh_days']) ? max(1, min(365, (int)$this->request->get['refresh_days'])) : 7;
                $result   = $this->model_extension_module_oc_kit_advanced_search_pro->generateQueryRules(
                    $limit, $days, $minCount, ['rule_refresh_days' => $refreshDays]
                );
                $processed = (int)($result['created'] ?? 0);
                $extra = ['failed' => (int)($result['failed'] ?? 0), 'total' => (int)($result['total'] ?? 0)];
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'AI rules generated: ' . $processed . '/' . (int)($result['total'] ?? 0) . ', failed: ' . (int)($result['failed'] ?? 0));
            } elseif ($type === 'ai_rules_zero') {
                // Dedicated task: process queries that returned zero results.
                // These are the most valuable for improving search quality.
                $days     = isset($this->request->get['days'])      ? max(1, min(365,  (int)$this->request->get['days']))      : 30;
                $minCount = isset($this->request->get['min_count']) ? max(1, min(1000, (int)$this->request->get['min_count'])) : 1;
                $refreshDays = isset($this->request->get['refresh_days']) ? max(1, min(365, (int)$this->request->get['refresh_days'])) : 7;
                $result   = $this->model_extension_module_oc_kit_advanced_search_pro->generateQueryRules(
                    $limit, $days, $minCount, ['no_results_only' => true, 'rule_refresh_days' => $refreshDays]
                );
                $processed = (int)($result['created'] ?? 0);
                $extra = ['failed' => (int)($result['failed'] ?? 0), 'total' => (int)($result['total'] ?? 0)];
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'AI zero-results rules: ' . $processed . '/' . (int)($result['total'] ?? 0) . ', failed: ' . (int)($result['failed'] ?? 0));
            } elseif ($type === 'warm_cache') {
                // Pre-populate APCu with top query rules to avoid per-request DB lookups.
                $warmLimit = isset($this->request->get['warm_limit']) ? max(1, min(5000, (int)$this->request->get['warm_limit'])) : 500;
                $result    = $this->asp->warmQueryRuleCache($warmLimit);
                $processed = (int)($result['cached'] ?? 0);
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Warm cache: ' . $result['status'] . ', cached: ' . $processed);
            } elseif ($type === 'reembed_all') {
                // Force re-queue all products for embedding (e.g. after model change).
                $processed = $this->asp->queueAllProductsForEmbedding();
                $this->asp->setMeta('embed_model', ''); // reset stored model so next embedding run detects fresh state
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Queued for re-embedding: ' . $processed);
            } elseif ($type === 'purge_log') {
                // Purge old query log, cron log, and completed queue entries.
                $days   = isset($this->request->get['days']) ? max(7, min(3650, (int)$this->request->get['days'])) : max(7, (int)$settings['log_ttl_days']);
                $result = $this->asp->purgeOldData($days);
                $processed = array_sum($result);
                $extra = $result;
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok',
                    'Purged: query_log=' . $result['query_log'] .
                    ' cron_log=' . $result['cron_log'] .
                    ' index_queue=' . $result['index_queue'] .
                    ' embedding_queue=' . $result['embedding_queue'] .
                    ' (ttl=' . $days . 'd)'
                );
            } elseif ($type === 'auto') {
                // Smart mode: detect the most needed task and execute it.
                $chosenType = $this->autoPickCronType();
                $extra['auto_chosen'] = $chosenType;

                switch ($chosenType) {
                    case 'embedding':
                        $processed = $this->model_extension_module_oc_kit_advanced_search_pro->processEmbeddingQueue($limit);
                        break;
                    case 'sync_modified':
                        $processed = $this->model_extension_module_oc_kit_advanced_search_pro->syncModifiedProducts($limit, 30);
                        break;
                    case 'incremental':
                        $processed = $this->model_extension_module_oc_kit_advanced_search_pro->processQueue($limit);
                        break;
                    case 'ai_rules_zero':
                        $result    = $this->model_extension_module_oc_kit_advanced_search_pro->generateQueryRules(
                            min($limit, 50), 7, 1, ['no_results_only' => true, 'rule_refresh_days' => 7]
                        );
                        $processed = (int)($result['created'] ?? 0);
                        $extra['failed'] = (int)($result['failed'] ?? 0);
                        break;
                    case 'purge_log':
                        $purgeResult = $this->asp->purgeOldData((int)$settings['log_ttl_days'] ?: 90);
                        $processed   = array_sum($purgeResult);
                        $extra       = array_merge($extra, $purgeResult);
                        break;
                    default: // warm_cache
                        $warmResult = $this->asp->warmQueryRuleCache(500);
                        $processed  = (int)($warmResult['cached'] ?? 0);
                }

                $this->model_extension_module_oc_kit_advanced_search_pro->logCron(
                    'auto', 'ok',
                    '[auto→' . $chosenType . '] processed: ' . $processed
                );
            } else {
                $processed = $this->model_extension_module_oc_kit_advanced_search_pro->processQueue($limit);
                $this->model_extension_module_oc_kit_advanced_search_pro->logCron($type, 'ok', 'Processed: ' . $processed);
            }
        } finally {
            $this->releaseDbLock($lockName);
        }

        $response = [
            'status' => 'ok',
            'processed' => $processed,
            'type' => $type,
            'offset' => $offset
        ];
        if ($extra) {
            $response = array_merge($response, $extra);
        }

        $this->response->setOutput(json_encode($response));
    }

    public function benchmark() {
        $this->response->addHeader('Content-Type: application/json');

        $settings = $this->asp->getSettings([
            'status' => 0,
            'mode' => 'native',
            'cron_key' => ''
        ]);

        $key = (string)($this->request->get['key'] ?? '');
        $storedKey = (string)$settings['cron_key'];
        if ($key === '' || $storedKey === '' || !hash_equals($storedKey, $key)) {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'Invalid key'
            ]));
            return;
        }

        $ip = '';
        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $this->request->server['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($this->request->server['REMOTE_ADDR'])) {
            $ip = $this->request->server['REMOTE_ADDR'];
        }
        if ($ip === '') {
            $ip = 'unknown';
        }

        $rateKey = 'asp.benchmark.rate.' . md5($ip . ':' . $key);
        $rate = $this->cache->get($rateKey);
        if (!is_array($rate) || !isset($rate['start']) || !isset($rate['count'])) {
            $rate = ['start' => time(), 'count' => 0];
        }
        if ((time() - (int)$rate['start']) > 60) {
            $rate['start'] = time();
            $rate['count'] = 0;
        }
        $rate['count']++;
        $this->cache->set($rateKey, $rate);
        if ($rate['count'] > 6) {
            $this->response->setOutput(json_encode([
                'status' => 'rate_limited',
                'message' => 'Too many benchmark requests'
            ]));
            return;
        }

        $sampleLimit = max(1, min(200, (int)($this->request->get['samples'] ?? 30)));
        $resultLimit = max(1, min(100, (int)($this->request->get['limit'] ?? 10)));
        $mode = strtolower(trim((string)($this->request->get['mode'] ?? 'current')));

        $modes = [];
        if ($mode === 'all') {
            $modes = ['native', 'manticore', 'sphinx', 'hybrid'];
        } elseif ($mode === 'current') {
            $modes = [strtolower((string)$settings['mode'])];
        } else {
            $modes = [$mode];
        }

        $allowedModes = ['native', 'manticore', 'sphinx', 'hybrid'];
        $modes = array_values(array_unique(array_intersect($allowedModes, $modes)));
        if (!$modes) {
            $modes = ['native'];
        }

        $queryRows = $this->db->query(
            "SELECT `query`, COUNT(*) AS total
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE `query` <> ''
             GROUP BY `query`
             ORDER BY total DESC
             LIMIT " . (int)$sampleLimit
        );

        $queries = [];
        foreach ($queryRows->rows as $row) {
            $q = trim((string)$row['query']);
            if ($q !== '') {
                $queries[] = $q;
            }
        }

        if (!$queries) {
            $queries = ['iphone', 'samsung', 'laptop', 'tv', 'shoes'];
        }

        $report = [
            'status' => 'ok',
            'date_utc' => gmdate('Y-m-d H:i:s'),
            'samples' => count($queries),
            'limit' => $resultLimit,
            'modes' => []
        ];

        foreach ($modes as $benchMode) {
            $latencies = [];
            $errors = 0;
            $totalResults = 0;

            foreach ($queries as $query) {
                $started = microtime(true);
                try {
                    $benchSettings = array_merge($settings, ['mode' => $benchMode]);
                    $found = $this->asp->getSearchEngine($benchSettings)->search($query, $resultLimit, 0);
                    $latencies[] = (microtime(true) - $started) * 1000;
                    $totalResults += (int)($found['total'] ?? count($found['ids'] ?? []));
                } catch (Exception $e) {
                    $errors++;
                    $latencies[] = (microtime(true) - $started) * 1000;
                }
            }

            sort($latencies);
            $count = count($latencies);
            $avg = $count ? (array_sum($latencies) / $count) : 0;
            $p95Index = $count ? (int)floor(($count - 1) * 0.95) : 0;
            $p95 = $count ? $latencies[$p95Index] : 0;
            $min = $count ? $latencies[0] : 0;
            $max = $count ? $latencies[$count - 1] : 0;

            $report['modes'][$benchMode] = [
                'queries' => $count,
                'avg_ms' => round($avg, 2),
                'p95_ms' => round($p95, 2),
                'min_ms' => round($min, 2),
                'max_ms' => round($max, 2),
                'errors' => $errors,
                'avg_results' => $count ? round($totalResults / $count, 2) : 0
            ];
        }

        $this->response->setOutput(json_encode($report));
    }

    private function getSearchCacheAdapter($cacheType, $ttl) {
        $ttl = max(10, (int)$ttl);

        // Map alias → canonical OpenCart adapter name (class names are case-insensitive in PHP)
        $map = [
            'file'      => 'file',
            'redis'     => 'redis',
            'memcached' => 'memcached',
            'mem'       => 'memcached',
            'apc'       => 'apc',
        ];

        $key = strtolower(trim((string)$cacheType));

        if (isset($map[$key])) {
            try {
                // Catch both Exception and Error:
                // - Error on PHP 8 from undefined CACHE_HOSTNAME/CACHE_PORT constants (Redis)
                // - Error if PHP extension (redis, memcached) is not installed
                // - Exception from OpenCart Cache constructor itself
                return new Cache($map[$key], $ttl);
            } catch (\Throwable $e) {
                // Silently fall back to OpenCart's default cache adapter
            }
        }

        return $this->cache;
    }

    /**
     * Inspect DB state and return the most needed cron task type.
     *
     * Priority (highest → lowest):
     *   1. embedding   – vector embeddings pending in queue
     *   2. sync_modified – products changed in last 30 min not yet queued for indexing
     *   3. incremental  – products already in index queue waiting to be processed
     *   4. ai_rules_zero – zero-result queries from last 7 days without a fresh AI rule (≥3)
     *   5. warm_cache   – pre-warm APCu (default / nothing more urgent)
     */
    private function autoPickCronType() {
        try {
            // 1. Pending AI embeddings?
            $r = $this->db->query(
                "SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_embedding_queue` WHERE status = 'pending'"
            )->row;
            if ((int)($r['c'] ?? 0) > 0) {
                return 'embedding';
            }

            // 2. Recently modified products not yet in the index queue?
            $r = $this->db->query(
                "SELECT COUNT(*) AS c
                 FROM `" . DB_PREFIX . "product` p
                 WHERE p.date_modified >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                   AND p.status = 1
                   AND NOT EXISTS (
                       SELECT 1 FROM `" . DB_PREFIX . "asp_index_queue` q
                       WHERE q.entity_id = p.product_id
                         AND q.entity_type = 'product'
                   )"
            )->row;
            if ((int)($r['c'] ?? 0) > 0) {
                return 'sync_modified';
            }

            // 3. Pending index queue items?
            $r = $this->db->query(
                "SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_index_queue` WHERE status = 'pending'"
            )->row;
            if ((int)($r['c'] ?? 0) > 0) {
                return 'incremental';
            }

            // 4. Zero-result queries without a fresh AI rule?
            $r = $this->db->query(
                "SELECT COUNT(DISTINCT ql.query) AS c
                 FROM `" . DB_PREFIX . "asp_query_log` ql
                 WHERE ql.results = 0
                   AND ql.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   AND NOT EXISTS (
                       SELECT 1 FROM `" . DB_PREFIX . "asp_query_rule` qr
                       WHERE qr.query_normalized = ql.query
                         AND qr.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   )"
            )->row;
            if ((int)($r['c'] ?? 0) >= 3) {
                return 'ai_rules_zero';
            }

            // 5. Old query log entries (older than 90 days) → purge
            $r = $this->db->query(
                "SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY)
                 LIMIT 1"
            )->row;
            if ((int)($r['c'] ?? 0) > 0) {
                return 'purge_log';
            }
        } catch (\Throwable $e) {
            // Fall back gracefully if any probe query fails.
        }

        return 'warm_cache';
    }

    private function acquireDbLock($name, $waitSec = 0) {
        $name = trim((string)$name);
        if ($name === '') {
            return true;
        }

        try {
            $row = $this->db->query(
                "SELECT GET_LOCK('" . $this->db->escape($name) . "', " . (int)$waitSec . ") AS lock_ok"
            )->row;
            return (int)($row['lock_ok'] ?? 0) === 1;
        } catch (Exception $e) {
            return true;
        }
    }

    private function releaseDbLock($name) {
        $name = trim((string)$name);
        if ($name === '') {
            return;
        }

        try {
            $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($name) . "')");
        } catch (Exception $e) {
            // ignore lock release failures
        }
    }
}
