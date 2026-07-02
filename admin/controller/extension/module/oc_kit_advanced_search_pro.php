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
    private $error = [];
    private $asp;

    public function __construct($registry) {
        parent::__construct($registry);
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $this->asp = new AdvancedSearchPro($this->registry);
    }

    public function install() {
        $this->asp->install();

        // Persist a stable cron key ONCE (C7). It used to be regenerated for
        // display on every settings page load and never saved, so the cron URL
        // kept changing until the form was saved. The if-guard makes this a
        // no-op after the first run.
        if (!$this->config->get('module_oc_kit_advanced_search_pro_cron_key')) {
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('module_oc_kit_advanced_search_pro');
            $cronKey  = bin2hex(random_bytes(16));
            $settings['module_oc_kit_advanced_search_pro_cron_key'] = $cronKey;
            $this->model_setting_setting->editSetting('module_oc_kit_advanced_search_pro', $settings);
            // Reflect into the live config so the current request sees it.
            $this->config->set('module_oc_kit_advanced_search_pro_cron_key', $cronKey);
        }
    }

    public function uninstall() {
        $this->asp->uninstall();
    }

    public function index() {
        $this->install();

        require_once DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php';

        $lang = $this->load->language('extension/module/oc_kit_advanced_search_pro');
        $this->document->setTitle($this->language->get('heading_title'));
        // Cache-buster from asset mtimes — without it the browser caches admin.js
        // / admin.css indefinitely and never picks up updates.
        $assetBase = DIR_APPLICATION . 'view/javascript/ockit/';
        $assetV = (string) max(
            (int) @filemtime($assetBase . 'assets/css/styles.css'),
            (int) @filemtime($assetBase . 'advanced_search_pro/assets/css/admin.css'),
            (int) @filemtime($assetBase . 'assets/js/ok-common.js'),
            (int) @filemtime($assetBase . 'advanced_search_pro/assets/js/admin.js')
        );

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css?v=' . $assetV);
        $this->document->addStyle('view/javascript/ockit/advanced_search_pro/assets/css/admin.css?v=' . $assetV);
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js?v=' . $assetV);

        $this->load->model('setting/setting');
        $this->load->model('extension/module/oc_kit_advanced_search_pro');

        // ── License activation gate ───────────────────────────────────────────
        // Without a valid licence the admin sees only the activation screen —
        // the settings page is never rendered for an unlicensed store.
        $licOk = ['active', 'trial', 'grace'];
        $activationError = '';

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['asp_activate'])) {
            $key = trim((string)($this->request->post['license_key'] ?? ''));
            $this->saveLicenseKey($key);
            $this->config->set('module_oc_kit_advanced_search_pro_license_key', $key);
            $info = \OcKit\AdvancedSearchPro\AdvancedSearchPro::getLicenseStatus($this->registry);
            if (in_array($info['status'], $licOk, true)) {
                $this->response->redirect($this->url->link('extension/module/oc_kit_advanced_search_pro', 'user_token=' . $this->session->data['user_token'], true));
                return;
            }
            $activationError = ($key === '')
                ? $this->language->get('error_license_empty')
                : $this->language->get('text_license_invalid');
        }

        $licInfo = \OcKit\AdvancedSearchPro\AdvancedSearchPro::getLicenseStatus($this->registry);
        if (!in_array($licInfo['status'], $licOk, true)) {
            $this->response->setOutput($this->renderActivationPage($licInfo, $activationError));
            return;
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $post = $this->request->post;

            $post['module_oc_kit_advanced_search_pro_search_fields'] = $this->sanitizeSearchFields($post['module_oc_kit_advanced_search_pro_search_fields'] ?? []);

            $csvSynonymGroups = $this->parseSynonymsCsvUpload();

            // Detect embedding model change before saving new value.
            $oldEmbedModel = (string)$this->config->get('module_oc_kit_advanced_search_pro_ai_embedding_model');
            $newEmbedModel = (string)($post['module_oc_kit_advanced_search_pro_ai_embedding_model'] ?? '');

            // Per-provider keys: mirror the active provider's value into the
            // legacy single `ai_api_key` setting that the catalog reads.
            $activeProvider = (string)($post['module_oc_kit_advanced_search_pro_ai_provider'] ?? 'openai');
            $providerKeyName = 'module_oc_kit_advanced_search_pro_ai_api_key_' . $activeProvider;
            if (isset($post[$providerKeyName])) {
                $post['module_oc_kit_advanced_search_pro_ai_api_key'] = (string)$post[$providerKeyName];
            }

            $this->model_setting_setting->editSetting('module_oc_kit_advanced_search_pro', $post);

            // If embedding model changed and vector is enabled → queue all products for re-embedding.
            if ($oldEmbedModel !== '' && $newEmbedModel !== '' && $oldEmbedModel !== $newEmbedModel
                && !empty($post['module_oc_kit_advanced_search_pro_vector_enabled'])
            ) {
                // Drop + recreate the Manticore KNN table — different OpenAI models
                // produce different dimensions (3-large=3072, 3-small=1536) and the
                // table schema bakes in KNN_DIMS, so a model swap requires a fresh
                // table or KNN will reject the new vectors.
                try {
                    $vecSettings = $this->asp->getSettings([
                        'host' => '127.0.0.1', 'port' => 9306, 'index' => 'products',
                        'login' => '', 'password' => '',
                        'ai_embedding_model' => $newEmbedModel,
                    ]);
                    $vecSettings['ai_embedding_model'] = $newEmbedModel;
                    $this->asp->ensureVectorIndex($vecSettings, true);
                } catch (\Throwable $e) {
                    if ($this->log) {
                        $this->log->write('[AdvancedSearchPro] ensureVectorIndex on model change: ' . $e->getMessage());
                    }
                }
                $queued = $this->asp->queueAllProductsForEmbedding();
                $this->asp->setMeta('embed_model', $newEmbedModel);
                $this->session->data['success'] = sprintf(
                    $this->language->get('text_reembed_queued'),
                    $oldEmbedModel, $newEmbedModel, $queued
                );
            } else {
                $this->session->data['success'] = $this->language->get('text_success');
            }

            $this->syncSynonymGroupsFromPost($this->request->post, $csvSynonymGroups);

            $attributes_raw = $this->request->post['module_oc_kit_advanced_search_pro_attributes_raw'] ?? '';
            $this->model_extension_module_oc_kit_advanced_search_pro->syncAttributesFromRaw($attributes_raw);
            $this->response->redirect($this->url->link('extension/module/oc_kit_advanced_search_pro', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $lang;

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/oc_kit_advanced_search_pro', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/module/oc_kit_advanced_search_pro', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];
        $data['asp_module_version'] = AdvancedSearchPro::VERSION;
        // OC3 url->link() encodes & as &amp;; decode for use in JavaScript fetch() calls
        $ut = $this->session->data['user_token'];
        $aspLink = function($route) use ($ut) {
            return html_entity_decode($this->url->link($route, 'user_token=' . $ut, true), ENT_QUOTES, 'UTF-8');
        };
        $data['asp_test_url']               = $aspLink('extension/module/oc_kit_advanced_search_pro/testConnection');
        $data['asp_daemon_url']             = $aspLink('extension/module/oc_kit_advanced_search_pro/daemonStatus');
        $data['asp_generate_config_url']    = $aspLink('extension/module/oc_kit_advanced_search_pro/generateConfig');
        $data['asp_daemon_action_url']      = $aspLink('extension/module/oc_kit_advanced_search_pro/daemonAction');
        $data['asp_clear_cron_log_url']     = $aspLink('extension/module/oc_kit_advanced_search_pro/clearCronLog');
        $data['asp_generate_query_rules_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/generateQueryRules');
        $data['asp_generate_synonyms_url']  = $aspLink('extension/module/oc_kit_advanced_search_pro/generateSynonyms');
        $data['asp_accept_synonym_pending_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/acceptSynonymPending');
        $data['asp_reject_synonym_pending_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/rejectSynonymPending');
        $data['asp_import_synonyms_csv_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/importSynonymsCsv');
        $data['asp_load_bundled_synonyms_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/loadBundledSynonyms');
        $data['asp_load_preset_url']           = $aspLink('extension/module/oc_kit_advanced_search_pro/loadPreset');

        // Niche presets — read once for the UI buttons.
        $data['asp_presets'] = [];
        $presetsPath = DIR_SYSTEM . 'library/ockit/advanced_search_pro/data/presets.json';
        if (is_file($presetsPath)) {
            $presetsData = json_decode((string)file_get_contents($presetsPath), true);
            if (is_array($presetsData) && !empty($presetsData['presets'])) {
                foreach ($presetsData['presets'] as $p) {
                    $data['asp_presets'][] = [
                        'code'        => (string)($p['code'] ?? ''),
                        'name'        => (string)($p['name'] ?? ''),
                        'description' => (string)($p['description'] ?? ''),
                        'syn_count'   => count((array)($p['synonyms'] ?? [])),
                    ];
                }
            }
        }
        $data['asp_preview_query_rule_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/previewQueryRule');
        $data['asp_no_results_trend_url']   = $aspLink('extension/module/oc_kit_advanced_search_pro/noResultsTrend');
        $data['asp_top_zero_queries_url']   = $aspLink('extension/module/oc_kit_advanced_search_pro/topZeroQueries');
        $data['asp_generate_rule_for_url']  = $aspLink('extension/module/oc_kit_advanced_search_pro/generateRuleFor');
        $data['asp_export_query_rules_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/exportQueryRules');
        $data['asp_export_stats_url']       = $aspLink('extension/module/oc_kit_advanced_search_pro/exportStats');
        $data['asp_list_query_rules_url']   = $aspLink('extension/module/oc_kit_advanced_search_pro/listQueryRules');
        $data['asp_update_query_rule_url']  = $aspLink('extension/module/oc_kit_advanced_search_pro/updateQueryRule');
        $data['asp_delete_query_rule_url']  = $aspLink('extension/module/oc_kit_advanced_search_pro/deleteQueryRule');
        $data['asp_dict_import_url']        = $aspLink('extension/module/oc_kit_advanced_search_pro/dictionaryImport');
        $data['asp_dict_delete_url']        = $aspLink('extension/module/oc_kit_advanced_search_pro/dictionaryDelete');
        $data['asp_dict_entries_url']       = $aspLink('extension/module/oc_kit_advanced_search_pro/dictionaryEntries');
        $data['asp_dict_counts']            = $this->model_extension_module_oc_kit_advanced_search_pro->getDictionaryCounts();
        $data['wizard_done']      = (bool)$this->asp->getMeta('wizard_done');

        // Ukrainian morphology file presence → shown as a badge in the UI.
        $ukWf = (string)($this->config->get('module_oc_kit_advanced_search_pro_morphology_uk_wordforms') ?: '/var/lib/manticore/wordforms/uk.txt');
        $data['asp_morphology_uk_status'] = (is_file($ukWf) && is_readable($ukWf))
            ? sprintf($this->language->get('text_morphology_uk_ready'), number_format((int)(filesize($ukWf) / 1024)))
            : '';

        $data['wizard_url']       = $aspLink('extension/module/oc_kit_advanced_search_pro/saveWizard');
        $data['wizard_reset_url'] = $aspLink('extension/module/oc_kit_advanced_search_pro/resetWizard');
        $data['wizard_current_mode'] = (string)($this->config->get('module_oc_kit_advanced_search_pro_mode') ?: 'native');
        $data['wizard_current_ai']   = !empty($this->config->get('module_oc_kit_advanced_search_pro_ai_api_key'));
        $data['wizard_current_cron'] = (bool)$this->config->get('module_oc_kit_advanced_search_pro_cron_enabled');
        $data['asp_ai_model_options'] = $this->getAiModelOptions();

        $defaults = [
            'status' => 0,
            'mode' => 'native',
            'debug' => 0,
            'host' => '127.0.0.1',
            'port' => '9306',
            'index' => 'products',
            'login' => '',
            'password' => '',
            'sphinx_host' => '127.0.0.1',
            'sphinx_port' => '9306',
            'sphinx_index' => 'products',
            'sphinx_login' => '',
            'sphinx_password' => '',
            'install_mode' => 'local',
            'cron_enabled' => 0,
            'cron_type' => 'full',
            'cron_interval' => 'daily',
            'cron_key' => '',
            'cron_lock_wait_sec' => 0,
            'queue_max_attempts' => 5,
            'queue_retry_after_sec' => 120,
            'queue_recover_stuck_sec' => 900,
            'min_word_length' => 2,
            'fuzzy' => 1,
            'fuzzy_distance' => 2,
            'layout_correction' => 1,
            'cross_lang_enabled' => 1,
            'stopwords' => '',
            'wordforms' => '',
            'lemmatization' => 0,
            'morphology' => 'lemmatize_ru_all, lemmatize_en_all',
            'morphology_uk_wordforms' => '/var/lib/manticore/wordforms/uk.txt',
            'vector_enabled' => 0,
            'vector_ratio' => 30,
            'vector_min_score' => 0.35,
            'oos_mode' => 'show',
            'rank_stock_first' => 1,
            'rank_boost_new' => 1,
            'rank_boost_popular' => 1,
            'rank_boost_category' => 0,
            'cache_enabled' => 0,
            'cache_ttl' => 300,
            'cache_type' => 'file',
            'autocomplete_enabled' => 1,
            'autocomplete_mode' => 'products',
            'autocomplete_min_chars' => 2,
            'autocomplete_delay' => 180,
            'autocomplete_show_price' => 1,
            'autocomplete_show_stock' => 1,
            'autocomplete_show_image' => 1,
            'autocomplete_show_facets' => 0,
            'autocomplete_group_collapse' => 0,
            'group_collapse_attribute' => 0,
            'autocomplete_show_categories' => 1,
            'autocomplete_category_limit' => 2,
            'autocomplete_show_brands' => 1,
            'autocomplete_brand_limit' => 2,
            'autocomplete_semantic' => 0,
            'autocomplete_limit' => 8,
            'autocomplete_cart_fn' => '',
            'autocomplete_layout' => 'popup',
            'autocomplete_color_preset' => 'indigo',
            'autocomplete_color_custom' => '',
            'license_key' => '',
            'filter_enabled' => 1,
            'filter_ajax_mode' => 'own',
            'filter_category' => 1,
            'filter_manufacturer' => 1,
            'filter_price' => 1,
            'filter_stock' => 1,
            'filter_attribute' => 1,
            'filter_rating' => 1,
            'synonyms_raw' => '',
            'attributes_raw' => '',
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            // Per-provider API keys — only the active provider's value is
            // copied into `ai_api_key` on save (so the catalog reads one key).
            'ai_api_key_openai'   => '',
            'ai_api_key_deepseek' => '',
            'ai_api_key_gemini'   => '',
            'ai_api_key_claude'   => '',
            'ai_model' => 'gpt-4o-mini',
            'ai_embedding_model' => 'text-embedding-3-large',
            'ai_batch_size' => 100,
            'ai_embed_fields' => [
                'name' => 1,
                'description' => 1,
                'attributes' => 0,
                'categories' => 0,
                'manufacturer' => 0,
                'tags' => 0
            ],
            'ai_budget_monthly' => 50,
            'ai_budget_daily_limit' => 1000,
            'ai_auto_block' => 1,
            'ai_expand_query' => 0,
            'ai_rewrite_query' => 0,
            'ai_intent_detection' => 0,
            'log_ttl_days' => 90,
            'popular_tags_enabled' => 0,
            'popular_tags_source' => 'manual',
            'popular_tags' => '',
            'popular_tags_limit' => 10,
            'popular_products_enabled' => 1,
            'popular_products_source' => 'auto',
            'popular_products' => '',
            'popular_products_limit' => 8,
            'popular_brands_enabled' => 1,
            'popular_brands_source' => 'auto',
            'popular_brands' => '',
            'popular_brands_limit' => 10,
            'enable_transliteration' => 1,
            'enable_typo_correction' => 1,
            'enable_word_split' => 0,
            'enable_morphology' => 0,
            'enable_trigram_fallback' => 0,
            'index_trigger' => 'both',
            'enable_mmr' => 0,
            'mmr_lambda' => 0.5,
        ];

        foreach ($defaults as $key => $default) {
            $full_key = 'module_oc_kit_advanced_search_pro_' . $key;
            if (isset($this->request->post[$full_key])) {
                $data[$full_key] = $this->request->post[$full_key];
            } else {
                $config_value = $this->config->get($full_key);
                $data[$full_key] = ($config_value !== null && $config_value !== '') ? $config_value : $default;
            }

        }

        $provider = (string)$data['module_oc_kit_advanced_search_pro_ai_provider'];
        // Migration: when upgrading from the legacy single ai_api_key to
        // per-provider keys, seed the active provider's field with the legacy
        // value so the admin doesn't see an empty input on first open.
        $activeKeyVar = 'module_oc_kit_advanced_search_pro_ai_api_key_' . $provider;
        if (empty($data[$activeKeyVar]) && !empty($data['module_oc_kit_advanced_search_pro_ai_api_key'])) {
            $data[$activeKeyVar] = $data['module_oc_kit_advanced_search_pro_ai_api_key'];
        }
        $modelsForProvider = $data['asp_ai_model_options'][$provider] ?? [];
        if (!in_array((string)$data['module_oc_kit_advanced_search_pro_ai_model'], $modelsForProvider, true)) {
            $data['asp_ai_model_options'][$provider][] = (string)$data['module_oc_kit_advanced_search_pro_ai_model'];
        }
        $data['asp_ai_model_options_json'] = json_encode($data['asp_ai_model_options']);

        // JS i18n + endpoints — built here and emitted as one JSON blob so the
        // template never has to escape language strings inline.
        $L = function ($key) { return $this->language->get($key); };
        $data['asp_i18n_json'] = json_encode([
            'testing'            => $L('text_testing'),
            'requestFailed'      => $L('text_request_failed'),
            'connHealthy'        => $L('text_conn_healthy'),
            'connFailed'         => $L('text_conn_failed'),
            'connNative'         => $L('text_conn_native'),
            'bundledLoading'     => $L('text_bundled_loading'),
            'bundledDone'        => $L('text_bundled_done'),
            'presetLoading'      => $L('text_preset_loading'),
            'presetDone'         => $L('text_preset_done'),
            'generating'         => $L('text_generating'),
            'wizApplying'        => $L('text_wiz_applying'),
            'wizApplied'         => $L('text_wiz_applied'),
            'synonymsGenerating' => $L('text_synonyms_generating'),
            'csvNoFile'          => $L('text_csv_no_file'),
            'csvImported'        => $L('text_csv_imported'),
            'purgeLogRunning'    => $L('text_purge_log_running'),
            'purgeLogDone'       => $L('text_purge_log_done'),
            'reembedQueuing'     => $L('text_reembed_queuing'),
            'reembedQueued'      => $L('text_reembed_queued_short'),
            'reembedConfirm'     => $L('text_reembed_confirm'),
            'reembedRunning'     => $L('text_reembed_running'),
            'reembedDone'        => $L('text_reembed_done'),
            'reembedStopped'     => $L('text_reembed_stopped'),
            'reembedFailed'      => $L('text_reembed_failed'),
            'benchmarkRunning'   => $L('text_benchmark_running'),
            'benchmarkFailed'    => $L('text_benchmark_failed'),
            'qrEmpty'            => $L('text_qr_empty'),
            'qrLoading'          => $L('text_qr_loading'),
            'qrSaved'            => $L('text_qr_saved'),
            'qrDeleted'          => $L('text_qr_deleted'),
            'qrDeleteConfirm'    => $L('text_qr_delete_confirm'),
            'qrEdit'             => $L('button_qr_edit'),
            'qrDelete'           => $L('button_qr_delete'),
            'previewQuerying'    => $L('text_preview_querying'),
            'generatingRule'     => $L('text_generating_rule'),
            'ruleGenerated'      => $L('text_rule_generated'),
            'trendNoData'        => $L('text_trend_no_data'),
            'trendDate'          => $L('text_trend_date'),
            'trendTotal'         => $L('text_trend_total'),
            'trendZero'          => $L('text_trend_zero'),
            'trendPct'           => $L('text_trend_pct'),
            'wizHintNative'      => $L('text_wiz_hint_native'),
            'wizHintManticore'   => $L('text_wiz_hint_manticore'),
            'wizHintSphinx'      => $L('text_wiz_hint_sphinx'),
            'wizHintHybrid'      => $L('text_wiz_hint_hybrid'),
            'dict_import_url'    => $data['asp_dict_import_url'],
            'dict_delete_url'    => $data['asp_dict_delete_url'],
            'dict_entries_url'   => $data['asp_dict_entries_url'],
            'dict_imported'      => $L('dict_imported'),
            'dict_deleted'       => $L('dict_deleted'),
            'dict_no_file'       => $L('dict_no_file'),
            'dict_confirm_delete_lang' => $L('dict_confirm_delete_lang'),
            'dict_confirm_delete_all'  => $L('dict_confirm_delete_all'),
            'dict_col_lang'      => $L('dict_col_lang'),
            'dict_col_count'     => $L('dict_col_count'),
            'dict_empty'         => $L('dict_empty'),
            'dict_lang_all'      => $L('dict_lang_all'),
            'dict_page'          => $L('dict_page'),
            'button_delete'      => $L('button_delete'),
            'synDeleteConfirm'   => $L('text_syn_delete_confirm'),
            'synonymsGenerated'  => $L('text_synonyms_generated'),
            'synonymsProposed'   => $L('text_synonyms_proposed'),
            'synonymAccepted'    => $L('text_synonym_accepted'),
            'synonymRejected'    => $L('text_synonym_rejected'),
            'noPendingSynonyms'  => $L('text_no_pending_synonyms'),
            'selectedNQueries'   => $L('text_selected_n_queries'),
            'bulkGenerated'      => $L('text_bulk_generated'),
            'copied'             => $L('text_copied'),
            'deleted'            => $L('text_qr_deleted'),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);

        // Use the persisted key (install() guarantees one). No more per-load
        // random generation, which produced a phantom cron URL that broke once
        // the form was saved with a different key.
        if (empty($data['module_oc_kit_advanced_search_pro_cron_key'])) {
            $data['module_oc_kit_advanced_search_pro_cron_key'] = (string)$this->config->get('module_oc_kit_advanced_search_pro_cron_key');
        }

        $data['module_oc_kit_advanced_search_pro_search_fields'] = $this->loadSearchFieldsFromConfig($this->request->post);

        // License status for the License tab — go through the standard StoreContext helper
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $aspLicense = \OcKit\AdvancedSearchPro\AdvancedSearchPro::getLicenseStatus($this->registry);
        $data['asp_license_status'] = $aspLicense['status']  ?? 'not_validated';
        $data['asp_license_domain'] = $aspLicense['domain']  ?? '';
        $data['asp_license_expiry'] = $aspLicense['version'] ?? '';
        $data['asp_license_info']   = $aspLicense;

        $catalog_base = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTP_CATALOG') ? HTTP_CATALOG : '');
        $data['module_oc_kit_advanced_search_pro_cron_url'] = $catalog_base ? $catalog_base . 'index.php?route=extension/module/oc_kit_advanced_search/cron&key=' . $data['module_oc_kit_advanced_search_pro_cron_key'] : '';
        $data['asp_cron_url'] = $data['module_oc_kit_advanced_search_pro_cron_url'];
        $data['asp_benchmark_url'] = $catalog_base ? $catalog_base . 'index.php?route=extension/module/oc_kit_advanced_search/benchmark&key=' . $data['module_oc_kit_advanced_search_pro_cron_key'] : '';
        $data['asp_manual_run_url']        = $aspLink('extension/module/oc_kit_advanced_search_pro/manualRun');
        $data['asp_clear_query_log_url']   = $aspLink('extension/module/oc_kit_advanced_search_pro/clearQueryLog');
        $data['asp_attr_search_url']       = $aspLink('extension/module/oc_kit_advanced_search_pro/searchAttributes');
        $data['asp_search_products_url']   = $aspLink('extension/module/oc_kit_advanced_search_pro/searchProducts');
        $data['asp_search_brands_url']     = $aspLink('extension/module/oc_kit_advanced_search_pro/searchBrands');
        $data['asp_delete_synonym_url']    = $aspLink('extension/module/oc_kit_advanced_search_pro/deleteSynonymGroup');

        $summary = $this->model_extension_module_oc_kit_advanced_search_pro->getStatsSummary();
        $index_metrics = $this->model_extension_module_oc_kit_advanced_search_pro->getIndexMetrics();
        $top_queries = $this->model_extension_module_oc_kit_advanced_search_pro->getTopQueries(20);
        $no_result_queries = $this->model_extension_module_oc_kit_advanced_search_pro->getNoResultQueries(20);
        $recent_queries = $this->model_extension_module_oc_kit_advanced_search_pro->getRecentQueries(30);
        $all_no_result_queries = $this->model_extension_module_oc_kit_advanced_search_pro->getAllNoResultQueries(50);
        $cron_log = $this->model_extension_module_oc_kit_advanced_search_pro->getCronLog(10);

        $attrPage = max(1, (int)($this->request->get['asp_attr_page'] ?? 1));
        $attrLimit = 20;
        $attrQuery = trim((string)($this->request->get['asp_attr_q'] ?? ''));
        $attrTotal = $this->model_extension_module_oc_kit_advanced_search_pro->getAttributeCatalogTotal($attrQuery);
        $attrPages = max(1, (int)ceil($attrTotal / $attrLimit));
        if ($attrPage > $attrPages) {
            $attrPage = $attrPages;
        }

        $data['asp_attributes_catalog'] = $this->model_extension_module_oc_kit_advanced_search_pro->getAttributeCatalog($attrQuery, $attrPage, $attrLimit);
        // Full attribute list for the variant-collapse "group by attribute" select.
        $data['group_collapse_attr_list'] = $this->model_extension_module_oc_kit_advanced_search_pro->getAttributeCatalog('', 1, 1000);
        $data['asp_attributes_total'] = $attrTotal;
        $data['asp_attributes_page'] = $attrPage;
        $data['asp_attributes_pages'] = $attrPages;
        $data['asp_attributes_q'] = $attrQuery;
        $data['asp_attr_page_links'] = [];
        for ($p = 1; $p <= $attrPages; $p++) {
            $data['asp_attr_page_links'][] = [
                'num' => $p,
                'active' => ($p === $attrPage),
                'href' => $this->url->link(
                    'extension/module/oc_kit_advanced_search_pro',
                    'user_token=' . $this->session->data['user_token'] . '&asp_tab=attributes&asp_attr_page=' . $p . '&asp_attr_q=' . urlencode($attrQuery),
                    true
                )
            ];
        }

        $data['asp_selected_attributes'] = $this->parseAttributeRowsWithNames((string)$data['module_oc_kit_advanced_search_pro_attributes_raw']);
        $data['asp_synonym_groups'] = $this->model_extension_module_oc_kit_advanced_search_pro->getSynonymGroupsDetailed();
        $data['asp_synonym_pending'] = $this->asp->getPendingSynonyms('pending', 200);

        $budget_monthly = (float)$data['module_oc_kit_advanced_search_pro_ai_budget_monthly'];
        $ai_cost_month = (float)$summary['ai_cost_month'];

        $data['stats'] = [
            'indexed_products' => $index_metrics['indexed_products'],
            'last_indexed' => $index_metrics['last_indexed'],
            'index_size' => $index_metrics['index_size'],
            'queries_today' => $summary['queries_today'],
            'queries_7' => $summary['queries_7'],
            'queries_30' => $summary['queries_30'],
            'top_queries' => $top_queries,
            'no_result_queries' => $no_result_queries,
            'recent_queries' => $recent_queries,
            'all_no_result_queries' => $all_no_result_queries,
            'avg_latency' => $summary['avg_latency'],
            'p95_latency' => $summary['p95_latency'],
            'cache_hit' => $summary['cache_hit'],
            'errors' => $summary['errors'],
            'ai_tokens_today' => $summary['ai_tokens_today'],
            'ai_cost_today' => '$' . number_format($summary['ai_cost_today'], 2),
            'ai_cost_month' => '$' . number_format($summary['ai_cost_month'], 2),
            'ai_budget_left' => '$' . number_format(max(0, $budget_monthly - $ai_cost_month), 2)
        ];

        $data['cron_log'] = $cron_log;

        $data['asp_manticore_status'] = 'unknown';
        $data['asp_daemon'] = [
            'status' => 'unknown',
            'pid' => '-',
            'memory' => '-',
            'cpu' => '-'
        ];
        if (isset($this->request->get['asp_test'])) {
            try {
                $settings = $this->buildConnectionSettings($data['module_oc_kit_advanced_search_pro_mode']);
                $client = $this->asp->getManticoreClient($settings);
                if ($data['module_oc_kit_advanced_search_pro_mode'] === 'sphinx') {
                    $client = $this->asp->getSphinxClient($settings);
                }
                $data['asp_manticore_status'] = $client->test() ? 'running' : 'error';
            } catch (Exception $e) {
                $data['asp_manticore_status'] = 'error';
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['asp_assets_v'] = $assetV;

        // Resolve saved manual popular-products / popular-brands ids → [{id,name}]
        // so the picker renders its chips with names (the setting stores ids only).
        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $ppIds = array_filter(array_map('intval', explode(',', (string)$data['module_oc_kit_advanced_search_pro_popular_products'])));
        $pbIds = array_filter(array_map('intval', explode(',', (string)$data['module_oc_kit_advanced_search_pro_popular_brands'])));
        $data['asp_popular_products_resolved'] = $ppIds ? $this->model_extension_module_oc_kit_advanced_search_pro->getProductNamesByIds($ppIds) : [];
        $data['asp_popular_brands_resolved']   = $pbIds ? $this->model_extension_module_oc_kit_advanced_search_pro->getManufacturerNamesByIds($pbIds) : [];

        $this->response->setOutput($this->load->view('extension/module/ockit/advanced_search_pro/settings', $data));
    }

    /**
     * Persist only the licence key. editSetting() would wipe every other module
     * setting, and ocStore's editSettingValue only UPDATEs (never INSERTs), so
     * we upsert the single row directly.
     */
    private function saveLicenseKey($key) {
        $code = 'module_oc_kit_advanced_search_pro';
        $name = $code . '_license_key';
        $exists = $this->db->query(
            "SELECT setting_id FROM `" . DB_PREFIX . "setting`
             WHERE `key` = '" . $this->db->escape($name) . "' AND store_id = '0' LIMIT 1"
        )->row;
        if ($exists) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape($key) . "'
                 WHERE `key` = '" . $this->db->escape($name) . "' AND store_id = '0'"
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "setting`
                 SET store_id = '0', `code` = '" . $this->db->escape($code) . "',
                     `key` = '" . $this->db->escape($name) . "',
                     `value` = '" . $this->db->escape($key) . "', serialized = '0'"
            );
        }
    }

    /**
     * Render the standalone licence-activation screen (shown instead of the
     * settings when the store has no valid licence).
     */
    private function renderActivationPage(array $licInfo, string $activationError) {
        $data = $this->load->language('extension/module/oc_kit_advanced_search_pro');

        $data['heading_title']    = $this->language->get('heading_title');
        $data['asp_module_version'] = AdvancedSearchPro::VERSION;
        $data['user_token']       = $this->session->data['user_token'];
        $data['action']           = $this->url->link('extension/module/oc_kit_advanced_search_pro', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel']           = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['asp_license_status'] = $licInfo['status'] ?? 'not_validated';
        $data['asp_license_domain'] = $licInfo['domain'] ?? '';
        $data['activation_error']   = $activationError;
        $data['module_oc_kit_advanced_search_pro_license_key'] = (string)$this->config->get('module_oc_kit_advanced_search_pro_license_key');

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)],
            ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)],
            ['text' => $this->language->get('heading_title'),  'href' => $data['action']],
        ];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        return $this->load->view('extension/module/ockit/advanced_search_pro/activate', $data);
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    /**
     * Permission gate for AJAX endpoints (C6). Any logged-in admin could
     * otherwise trigger reindex, delete data, or spend the AI budget without
     * the module's modify permission. Emits a JSON error and returns false when
     * the user is not authorised — call as: if (!$this->requirePermission()) return;
     */
    private function requirePermission() {
        if ($this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            return true;
        }
        $this->load->language('extension/module/oc_kit_advanced_search_pro');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'status' => 'error',
            'error'  => $this->language->get('error_permission'),
        ]));
        return false;
    }

    private function getDefaultSearchFields() {
        $t = function ($key) { return $this->language->get($key); };
        return [
            'product_id'   => ['id' => 'product_id',   'label' => $t('field_product_id'),   'enabled' => 1, 'weight' => 100],
            'title'        => ['id' => 'title',         'label' => $t('field_title'),        'enabled' => 1, 'weight' => 80],
            'description'  => ['id' => 'description',  'label' => $t('field_description'),  'enabled' => 1, 'weight' => 40],
            'model'        => ['id' => 'model',         'label' => $t('field_model'),        'enabled' => 1, 'weight' => 30],
            'sku'          => ['id' => 'sku',           'label' => $t('field_sku'),          'enabled' => 1, 'weight' => 30],
            'upc'          => ['id' => 'upc',           'label' => $t('field_upc'),          'enabled' => 1, 'weight' => 10],
            'ean'          => ['id' => 'ean',           'label' => $t('field_ean'),          'enabled' => 1, 'weight' => 10],
            'isbn'         => ['id' => 'isbn',          'label' => $t('field_isbn'),         'enabled' => 1, 'weight' => 10],
            'manufacturer' => ['id' => 'manufacturer',  'label' => $t('field_manufacturer'), 'enabled' => 1, 'weight' => 20],
            'categories'   => ['id' => 'categories',   'label' => $t('field_categories'),   'enabled' => 1, 'weight' => 20],
            'attributes'   => ['id' => 'attributes',   'label' => $t('field_attributes'),   'enabled' => 1, 'weight' => 15],
            'tags'         => ['id' => 'tags',          'label' => $t('field_tags'),         'enabled' => 1, 'weight' => 15],
        ];
    }

    private function sanitizeSearchFields($input) {
        $defaults = $this->getDefaultSearchFields();
        $output = [];

        foreach ($defaults as $key => $default) {
            $row = isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [];
            $id = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($row['id'] ?? $default['id'])));
            if ($id === '') {
                $id = $default['id'];
            }

            $weight = (int)($row['weight'] ?? $default['weight']);
            $weight = max(1, min(100, $weight));

            $enabled = (int)!empty($row['enabled']);
            $output[$key] = [
                'id' => $id,
                'label' => $default['label'],
                'enabled' => $enabled,
                'weight' => $weight
            ];
        }

        return $output;
    }

    private function loadSearchFieldsFromConfig(array $post = []) {
        if (isset($post['module_oc_kit_advanced_search_pro_search_fields']) && is_array($post['module_oc_kit_advanced_search_pro_search_fields'])) {
            return $this->sanitizeSearchFields($post['module_oc_kit_advanced_search_pro_search_fields']);
        }

        $fromConfig = $this->config->get('module_oc_kit_advanced_search_pro_search_fields');
        if (is_array($fromConfig) && $fromConfig) {
            return $this->sanitizeSearchFields($fromConfig);
        }

        // Backward-compatibility: migrate old `weights` map on first load.
        $legacy = $this->config->get('module_oc_kit_advanced_search_pro_weights');
        $defaults = $this->getDefaultSearchFields();
        if (is_array($legacy) && $legacy) {
            foreach ($defaults as $key => &$row) {
                if (isset($legacy[$key])) {
                    $row['weight'] = max(1, min(100, (int)$legacy[$key]));
                }
            }
            unset($row);
            return $defaults;
        }

        return $defaults;
    }

    private function getAiModelOptions() {
        return [
            'openai'   => ['gpt-4o-mini', 'gpt-4.1-mini', 'gpt-4.1', 'gpt-4o'],
            'claude'   => ['claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest', 'claude-3-opus-latest'],
            'deepseek' => ['deepseek-chat', 'deepseek-reasoner'],
            'gemini'   => ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'],
        ];
    }

    private function parseAttributeRowsWithNames($raw) {
        $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
        $rows = [];
        $ids = [];

        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 4) {
                continue;
            }

            $attributeId = (int)$parts[0];
            if ($attributeId <= 0) {
                continue;
            }

            $type = strtolower((string)$parts[1]);
            if (!in_array($type, ['text', 'numeric', 'bool'], true)) {
                $type = 'text';
            }

            $isFilter = (int)(bool)$parts[2];
            $isSearch = (int)(bool)$parts[3];

            $rows[] = [
                'attribute_id' => $attributeId,
                'type' => $type,
                'is_filter' => $isFilter,
                'is_search' => $isSearch,
                'name' => 'ID ' . $attributeId
            ];
            $ids[] = $attributeId;
        }

        if (!$rows) {
            return [];
        }

        $names = $this->model_extension_module_oc_kit_advanced_search_pro->getAttributeNamesByIds($ids);
        foreach ($rows as &$row) {
            $id = (int)$row['attribute_id'];
            if (isset($names[$id])) {
                $row['name']  = $names[$id]['name'];
                $row['group'] = $names[$id]['group_name'];
            }
        }
        unset($row);

        return $rows;
    }

    private function syncSynonymGroupsFromPost(array $post, array $uploadedGroups) {
        if (!empty($post['asp_synonym_delete']) && is_array($post['asp_synonym_delete'])) {
            foreach ($post['asp_synonym_delete'] as $groupId) {
                $this->model_extension_module_oc_kit_advanced_search_pro->deleteSynonymGroup((int)$groupId);
            }
        }

        // Form rows post as synonym_groups[<key>][name|terms] — exactly what the
        // table markup and the add-row JS emit. A numeric key is an existing group
        // (update); a "new_*" key is a freshly added row (insert). Earlier this read
        // asp_synonym_groups[..][terms_raw] / asp_synonym_new_* — keys the form never
        // sends — so saving silently did nothing.
        if (!empty($post['synonym_groups']) && is_array($post['synonym_groups'])) {
            foreach ($post['synonym_groups'] as $groupKey => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name  = trim((string)($row['name'] ?? ''));
                $terms = $this->extractTermsFromCsvLine(trim((string)($row['terms'] ?? '')));
                if (count($terms) < 2) {
                    continue;
                }
                if (ctype_digit((string)$groupKey) && (int)$groupKey > 0) {
                    $this->model_extension_module_oc_kit_advanced_search_pro->updateSynonymGroup((int)$groupKey, $terms, $name);
                } else {
                    $this->model_extension_module_oc_kit_advanced_search_pro->addSynonymGroup($terms, $name);
                }
            }
        }

        if ($uploadedGroups) {
            foreach ($uploadedGroups as $group) {
                $terms = isset($group['terms']) && is_array($group['terms']) ? $group['terms'] : [];
                $name = trim((string)($group['name'] ?? ''));
                if (count($terms) < 2) {
                    continue;
                }
                $this->model_extension_module_oc_kit_advanced_search_pro->addSynonymGroup($terms, $name);
            }
        }
    }

    private function extractTermsFromCsvLine($line) {
        $line = trim((string)$line);
        if ($line === '') {
            return [];
        }

        $terms = [];
        if (strpos($line, ',') !== false) {
            $parts = str_getcsv($line);
            foreach ($parts as $part) {
                $part = trim((string)$part);
                if ($part !== '') {
                    $terms[] = $part;
                }
            }
        } else {
            foreach (preg_split('/\s+/', $line) as $part) {
                $part = trim((string)$part);
                if ($part !== '') {
                    $terms[] = $part;
                }
            }
        }

        return array_values(array_unique($terms));
    }


    public function testConnection() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $mode = isset($this->request->post['mode']) ? (string)$this->request->post['mode'] : '';
        if ($mode === '') {
            $mode = (string)$this->config->get('module_oc_kit_advanced_search_pro_mode');
        }

        if ($mode === 'native') {
            $this->response->setOutput(json_encode([
                'status' => 'ok',
                'mode' => 'native',
                'message' => 'Native mode does not require daemon connection'
            ]));
            return;
        }

        try {
            $settings = $this->buildConnectionSettings($mode);
            $settings['host'] = isset($this->request->post['host']) ? (string)$this->request->post['host'] : $settings['host'];
            $settings['port'] = isset($this->request->post['port']) ? (string)$this->request->post['port'] : $settings['port'];
            $settings['index'] = isset($this->request->post['index']) ? (string)$this->request->post['index'] : $settings['index'];
            $settings['login'] = isset($this->request->post['login']) ? (string)$this->request->post['login'] : $settings['login'];
            $settings['password'] = isset($this->request->post['password']) ? (string)$this->request->post['password'] : $settings['password'];
            $settings['sphinx_host'] = isset($this->request->post['sphinx_host']) ? (string)$this->request->post['sphinx_host'] : $settings['sphinx_host'];
            $settings['sphinx_port'] = isset($this->request->post['sphinx_port']) ? (string)$this->request->post['sphinx_port'] : $settings['sphinx_port'];
            $settings['sphinx_index'] = isset($this->request->post['sphinx_index']) ? (string)$this->request->post['sphinx_index'] : $settings['sphinx_index'];
            $settings['sphinx_login'] = isset($this->request->post['sphinx_login']) ? (string)$this->request->post['sphinx_login'] : $settings['sphinx_login'];
            $settings['sphinx_password'] = isset($this->request->post['sphinx_password']) ? (string)$this->request->post['sphinx_password'] : $settings['sphinx_password'];
            $client = $this->asp->getManticoreClient($settings);
            if ($mode === 'sphinx') {
                $client = $this->asp->getSphinxClient($settings);
            }
            $ok = $client->test();

            $this->response->setOutput(json_encode([
                'status' => $ok ? 'running' : 'error',
                'mode' => $mode,
                'message' => $ok ? 'Connection is healthy' : 'Connection failed'
            ]));
        } catch (Exception $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] testConnection: ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'mode' => $mode,
                'message' => $this->formatAjaxErrorMessage('Connection test failed', $e)
            ]));
        }
    }

    public function daemonStatus() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $mode = (string)$this->config->get('module_oc_kit_advanced_search_pro_mode');
        if ($mode === 'native') {
            $this->response->setOutput(json_encode([
                'status' => 'not_required',
                'pid' => '-',
                'memory' => '-',
                'cpu' => '-',
                'message' => 'Daemon not required for Native mode'
            ]));
            return;
        }

        try {
            $settings = $this->buildConnectionSettings($mode);
            $client = $this->asp->getManticoreClient($settings);
            if ($mode === 'sphinx') {
                $client = $this->asp->getSphinxClient($settings);
            }
            $running = $client->test();
            $metrics = ['pid' => '-', 'memory' => '-', 'cpu' => '-'];

            if ($running && (string)$this->config->get('module_oc_kit_advanced_search_pro_install_mode') === 'local') {
                $metrics = $this->getLocalDaemonMetrics();
            }

            $this->response->setOutput(json_encode([
                'status' => $running ? 'running' : 'error',
                'pid' => $metrics['pid'],
                'memory' => $metrics['memory'],
                'cpu' => $metrics['cpu'],
                'message' => $running ? 'Daemon is reachable' : 'Daemon is not reachable'
            ]));
        } catch (Exception $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] daemonStatus: ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'pid' => '-',
                'memory' => '-',
                'cpu' => '-',
                'message' => $this->formatAjaxErrorMessage('Daemon status request failed', $e)
            ]));
        }
    }

    public function generateConfig() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $postMode    = isset($this->request->post['mode']) ? strtolower(trim((string)$this->request->post['mode'])) : '';
        $activeMode  = $postMode ?: strtolower((string)$this->config->get('module_oc_kit_advanced_search_pro_mode'));
        $settings    = $this->buildConnectionSettings($activeMode);
        $host        = $this->request->post['host']  ?? $settings['host'];
        $port        = $this->request->post['port']  ?? $settings['port'];
        $index       = $this->request->post['index'] ?? $settings['index'];
        $installMode = (string)$this->config->get('module_oc_kit_advanced_search_pro_install_mode');
        $safeIndex   = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$index);

        // In remote mode the daemon must listen on all interfaces (0.0.0.0) so OpenCart can connect.
        // In local mode listening on 127.0.0.1 is safer.
        $listenHost  = ($installMode === 'remote') ? '0.0.0.0' : '127.0.0.1';
        $listenPort  = (int)$port;

        if ($activeMode === 'sphinx') {
            // Legacy Sphinx: still uses the classic config-driven index definition.
            $confPath = '/etc/sphinx/sphinx.conf';
            $config = "# Sphinx Search configuration\n" .
                "# Place this file at: " . $confPath . " on the search server\n" .
                ($installMode === 'remote'
                    ? "# Remote mode: OpenCart connects to " . (string)$host . ":" . $listenPort . "\n"
                    : "# Local mode: daemon runs on the same server as OpenCart\n"
                ) .
                "\nsearchd {\n" .
                "  listen      = " . $listenHost . ":" . $listenPort . ":mysql41\n" .
                "  log         = /var/log/searchd/searchd.log\n" .
                "  query_log   = /var/log/searchd/query.log\n" .
                "  pid_file    = /var/run/searchd/searchd.pid\n" .
                "  workers     = threads\n" .
                "}\n\n" .
                "index " . $safeIndex . " {\n" .
                "  type     = rt\n" .
                "  path     = /var/data/sphinx/" . $safeIndex . "\n" .
                "  rt_field = name\n" .
                "  rt_field = description\n" .
                "  rt_field = tag\n" .
                "  rt_field = manufacturer\n" .
                "  rt_attr_uint   = quantity\n" .
                "  rt_attr_float  = price\n" .
                "  rt_attr_string = model\n" .
                "  rt_attr_string = sku\n" .
                "}\n";
        } else {
            // Manticore 25+ RT mode: tables are created via SQL CREATE TABLE
            // (the module does it in IndexService::ensureManticoreIndex with the
            // morphology, wordforms and min_infix_len options we use). The .conf
            // therefore only configures the daemon — it MUST NOT declare any
            // `index { ... }` block or it'll conflict with the SQL-created table.
            $confPath = '/etc/manticoresearch/manticore.conf';
            $wfPath   = (string)$this->config->get('module_oc_kit_advanced_search_pro_morphology_uk_wordforms');
            if ($wfPath === '') {
                $wfPath = '/var/lib/manticore/wordforms/uk.txt';
            }
            $config = "# Manticore Search configuration\n" .
                "# Place this file at: " . $confPath . " on the search server, then\n" .
                "#   sudo systemctl restart manticore\n" .
                ($installMode === 'remote'
                    ? "# Remote mode: OpenCart connects from web server to " . (string)$host . ":" . $listenPort . "\n"
                    : "# Local mode: daemon runs on the same server as OpenCart (127.0.0.1)\n"
                ) .
                "# Tables are auto-created via SQL CREATE TABLE at first reindex —\n" .
                "# do NOT add an `index { ... }` block here, it would conflict.\n" .
                "# Ukrainian wordforms must be readable by the manticore user:\n" .
                "#   " . $wfPath . "\n" .
                "\nsearchd {\n" .
                "  listen    = " . $listenHost . ":" . $listenPort . ":mysql41\n" .
                "  listen    = " . $listenHost . ":" . ($listenPort + 2) . ":http\n" .
                "  log       = /var/log/manticore/searchd.log\n" .
                "  query_log = /var/log/manticore/query.log\n" .
                "  pid_file  = /run/manticore/searchd.pid\n" .
                "  data_dir  = /var/lib/manticore\n" .
                "}\n";
        }

        $this->response->setOutput(json_encode([
            'status' => 'ok',
            'config' => $config
        ]));
    }

    public function daemonAction() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $installMode = (string)$this->config->get('module_oc_kit_advanced_search_pro_install_mode');
        if ($installMode !== 'local') {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'Daemon actions are available only in local mode'
            ]));
            return;
        }

        $action = strtolower((string)($this->request->post['action'] ?? ''));
        if (!in_array($action, ['start', 'stop', 'restart'], true)) {
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]));
            return;
        }

        $commands = [
            'start' => [
                'systemctl start manticore',
                'systemctl start searchd',
                'service manticore start',
                'service searchd start',
                'searchd --config /etc/manticoresearch/manticore.conf'
            ],
            'stop' => [
                'systemctl stop manticore',
                'systemctl stop searchd',
                'service manticore stop',
                'service searchd stop',
                'searchd --stopwait'
            ],
            'restart' => [
                'systemctl restart manticore',
                'systemctl restart searchd',
                'service manticore restart',
                'service searchd restart',
                'searchd --stopwait && searchd --config /etc/manticoresearch/manticore.conf'
            ]
        ];

        $output = '';
        $ok = false;

        foreach ($commands[$action] as $command) {
            @exec($command . ' 2>&1', $lines, $code);
            $output = implode("\n", $lines);
            if ($code === 0) {
                $ok = true;
                break;
            }
        }

        $this->response->setOutput(json_encode([
            'status' => $ok ? 'ok' : 'error',
            'message' => $ok ? ('Daemon action "' . $action . '" executed') : ('Failed: ' . $output)
        ]));
    }

    public function clearCronLog() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $this->model_extension_module_oc_kit_advanced_search_pro->clearCronLog();

        $this->response->setOutput(json_encode([
            'status' => 'ok',
            'message' => 'Cron log cleared'
        ]));
    }

    public function generateQueryRules() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $limit = isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 100;
        $days = isset($this->request->post['days']) ? (int)$this->request->post['days'] : 30;
        $minCount = isset($this->request->post['min_count']) ? (int)$this->request->post['min_count'] : 2;

        try {
            $settings = $this->asp->getSettings([
                'ai_provider' => 'openai',
                'ai_api_key' => '',
                'ai_model' => 'gpt-4o-mini',
                'ai_expand_query' => 1,
                'ai_rewrite_query' => 1,
                'ai_intent_detection' => 1,
                'ai_budget_monthly' => 50,
                'ai_budget_daily_limit' => 1000,
                'ai_auto_block' => 1
            ]);

            $result = $this->asp->generateQueryRules($limit, $days, $minCount, $settings);
            $this->response->setOutput(json_encode([
                'status' => 'ok',
                'result' => $result,
                'message' => 'Generated: ' . (int)$result['created'] . ', failed: ' . (int)$result['failed']
            ]));
        } catch (Exception $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] generateQueryRules: ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status' => 'error',
                'message' => $this->formatAjaxErrorMessage('AI rules generation failed', $e)
            ]));
        }
    }

    public function previewQueryRule() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $query = isset($this->request->post['query']) ? trim((string)$this->request->post['query']) : '';
        if ($query === '') {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'Query is required']));
            return;
        }

        try {
            $normalized = $this->asp->normalizeQuery($query);

            $row = $this->db->query(
                "SELECT * FROM `" . DB_PREFIX . "asp_query_rule`
                 WHERE query_normalized = '" . $this->db->escape($normalized) . "'
                 LIMIT 1"
            )->row;

            if (!$row) {
                $this->response->setOutput(json_encode([
                    'status'           => 'ok',
                    'applied'          => false,
                    'query_normalized' => $normalized,
                ]));
                return;
            }

            $expanded = [];
            if (!empty($row['expanded_json'])) {
                $json = json_decode((string)$row['expanded_json'], true);
                if (is_array($json)) {
                    $expanded = $json;
                }
            }

            $this->response->setOutput(json_encode([
                'status'           => 'ok',
                'applied'          => true,
                'query_normalized' => $normalized,
                'rewritten_query'  => (string)$row['rewritten_query'],
                'expanded_terms'   => $expanded,
                'intent'           => (string)$row['intent'],
                'hits'             => (int)$row['hits'],
                'source'           => (string)$row['source'],
                'updated_at'       => (string)$row['updated_at'],
            ]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Preview failed', $e),
            ]));
        }
    }

    public function noResultsTrend() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $days = isset($this->request->post['days']) ? max(7, min(90, (int)$this->request->post['days'])) : 14;

        try {
            $result = $this->db->query(
                "SELECT DATE(created_at) AS day,
                        COUNT(*)         AS total,
                        SUM(results = 0) AS zero_count
                 FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC"
            );

            $data = [];
            foreach ($result->rows as $row) {
                $total = (int)$row['total'];
                $zero  = (int)$row['zero_count'];
                $data[] = [
                    'date'  => (string)$row['day'],
                    'total' => $total,
                    'zero'  => $zero,
                    'pct'   => $total > 0 ? round($zero / $total * 100, 1) : 0.0,
                ];
            }

            $this->response->setOutput(json_encode([
                'status' => 'ok',
                'days'   => $days,
                'data'   => $data,
            ]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Trend failed', $e),
            ]));
        }
    }

    /**
     * Returns top zero-result queries for the given lookback period.
     * Each row includes whether an AI rule already exists.
     */
    public function topZeroQueries() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $days  = isset($this->request->post['days'])  ? max(1, min(365, (int)$this->request->post['days']))  : 30;
        $limit = isset($this->request->post['limit']) ? max(1, min(200, (int)$this->request->post['limit'])) : 50;

        try {
            $rows = $this->db->query(
                "SELECT ql.query,
                        COUNT(*)       AS cnt,
                        MAX(ql.created_at) AS last_seen,
                        (SELECT 1 FROM `" . DB_PREFIX . "asp_query_rule` qr
                         WHERE qr.query_normalized = ql.query LIMIT 1) AS has_rule
                 FROM `" . DB_PREFIX . "asp_query_log` ql
                 WHERE ql.results = 0
                   AND ql.created_at >= DATE_SUB(NOW(), INTERVAL " . $days . " DAY)
                 GROUP BY ql.query
                 ORDER BY cnt DESC
                 LIMIT " . $limit
            );

            $data = [];
            foreach ($rows->rows as $row) {
                $data[] = [
                    'query'     => (string)$row['query'],
                    'count'     => (int)$row['cnt'],
                    'last_seen' => (string)$row['last_seen'],
                    'has_rule'  => !empty($row['has_rule']),
                ];
            }

            $this->response->setOutput(json_encode(['status' => 'ok', 'rows' => $data]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('topZeroQueries failed', $e),
            ]));
        }
    }

    /**
     * Generate (or regenerate) an AI query rule for a single specific query.
     */
    public function generateRuleFor() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $query = trim((string)($this->request->post['query'] ?? ''));
        if ($query === '') {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'query required']));
            return;
        }

        try {
            $settings = $this->asp->getSettings([
                'ai_provider'        => 'openai',
                'ai_api_key'         => '',
                'ai_model'           => 'gpt-4o-mini',
                'ai_expand_query'    => 1,
                'ai_rewrite_query'   => 1,
                'ai_intent_detection'=> 1,
            ]);
            $settings['ai_expand_query']     = 1;
            $settings['ai_rewrite_query']    = 1;
            $settings['ai_intent_detection'] = 1;

            $enhanced = $this->asp->enhanceQueryWithAi($query, $settings);
            $rewrite  = (string)($enhanced['query']          ?? '');
            $expanded = (array)($enhanced['expanded_terms']  ?? []);
            $intent   = (string)($enhanced['intent']         ?? '');

            $aiApplied = !empty($enhanced['applied']);

            // Only persist a rule when the AI actually ran — otherwise we would
            // save an empty "query → query, []" rule that masks the real query.
            if ($aiApplied) {
                $this->asp->saveQueryRule($query, $rewrite, $expanded, $intent, 'ai');
            }
            $this->response->setOutput(json_encode([
                'status'     => 'ok',
                'query'      => $query,
                'rewrite'    => $rewrite,
                'expanded'   => $expanded,
                'intent'     => $intent,
                'ai_applied' => $aiApplied,
                'message'    => $aiApplied
                    ? ''
                    : 'AI did not run (check API key, model, budget, or enable AI features in settings)',
            ]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('generateRuleFor failed', $e),
            ]));
        }
    }

    /**
     * Export all query rules as a CSV file download.
     * Columns: id, query_normalized, rewritten_query, expanded_terms, intent, source, hits, created_at, updated_at
     */
    /** Export search statistics to a multi-sheet .xlsx (summary / daily / top / no-result / recent). */
    public function exportStats() {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        $this->load->language('extension/module/oc_kit_advanced_search_pro');
        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $m = $this->model_extension_module_oc_kit_advanced_search_pro;
        $L = function ($k) { return $this->language->get($k); };

        require_once DIR_SYSTEM . 'library/ockit/advanced_search_pro/libs/XlsxWriter.php';
        $xlsx = new \OcKit\AdvancedSearchPro\Libs\XlsxWriter();

        // 1) Summary
        $s = $m->getStatsSummary();
        $xlsx->addSheet($L('text_xls_sheet_summary'), [$L('text_xls_metric'), $L('text_xls_value')], [
            [$L('text_xls_queries_today'),   (int)$s['queries_today']],
            [$L('text_xls_queries_7'),       (int)$s['queries_7']],
            [$L('text_xls_queries_30'),      (int)$s['queries_30']],
            [$L('text_xls_noresults_today'), (int)$s['no_results_today']],
            [$L('text_xls_avg_latency'),     (string)$s['avg_latency']],
            [$L('text_xls_p95_latency'),     (string)$s['p95_latency']],
            [$L('text_xls_errors'),          (int)$s['errors']],
        ]);

        // 2) Daily aggregate (last 90 days)
        $daily = [];
        foreach ($m->getDailyStats(90) as $r) {
            $daily[] = [$r['date'], (int)$r['queries'], (int)$r['no_results'], (int)$r['avg_latency_ms'], (int)$r['p95_latency_ms'], (int)$r['errors']];
        }
        $xlsx->addSheet($L('text_xls_sheet_daily'), [
            $L('text_xls_date'), $L('text_xls_queries'), $L('text_xls_noresults'),
            $L('text_xls_avg_latency'), $L('text_xls_p95_latency'), $L('text_xls_errors'),
        ], $daily);

        // 3) Top queries
        $top = [];
        foreach ($m->getTopQueries(1000) as $r) {
            $top[] = [$r['query'], (int)$r['count'], (string)$r['last_seen']];
        }
        $xlsx->addSheet($L('text_xls_sheet_top'), [$L('text_xls_query'), $L('text_xls_count'), $L('text_xls_last_seen')], $top);

        // 4) No-result queries (last 365 days, any count)
        $nores = [];
        foreach ($m->getNoResultQueryRows(500, 365, 1) as $r) {
            $nores[] = [$r['query'], (int)$r['total']];
        }
        $xlsx->addSheet($L('text_xls_sheet_noresults'), [$L('text_xls_query'), $L('text_xls_count')], $nores);

        // 5) Recent queries
        $recent = [];
        foreach ($m->getRecentQueries(200) as $r) {
            $recent[] = [$r['query'], (int)$r['results'], (int)$r['hits'], (string)$r['created_at']];
        }
        $xlsx->addSheet($L('text_xls_sheet_recent'), [
            $L('text_xls_query'), $L('text_xls_results'), $L('text_xls_hits'), $L('text_xls_date'),
        ], $recent);

        $binary = $xlsx->build();
        $filename = 'asp_stats_' . date('Ymd_His') . '.xlsx';
        $this->response->addHeader('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('Cache-Control: no-cache, no-store, must-revalidate');
        $this->response->setOutput($binary);
    }

    public function exportQueryRules() {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $rows = $this->db->query(
            "SELECT id, query_normalized, rewritten_query, expanded_json, intent, source, hits, created_at, updated_at
             FROM `" . DB_PREFIX . "asp_query_rule`
             ORDER BY hits DESC, updated_at DESC"
        );

        $filename = 'asp_query_rules_' . date('Ymd_His') . '.csv';

        $this->response->addHeader('Content-Type: text/csv; charset=UTF-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['id', 'query', 'rewrite', 'expanded_terms', 'intent', 'source', 'hits', 'created_at', 'updated_at']);

        foreach ($rows->rows as $row) {
            $expanded = '';
            if (!empty($row['expanded_json'])) {
                $decoded = json_decode($row['expanded_json'], true);
                if (is_array($decoded)) {
                    $expanded = implode('; ', $decoded);
                }
            }
            fputcsv($out, [
                $row['id'],
                $row['query_normalized'],
                $row['rewritten_query'],
                $expanded,
                $row['intent'],
                $row['source'],
                $row['hits'],
                $row['created_at'],
                $row['updated_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    public function importSynonymsCsv() {
        $this->response->addHeader('Content-Type: application/json');
        if (!$this->validate()) {
            $this->response->setOutput(json_encode(['status' => 'error', 'error' => 'Forbidden']));
            return;
        }
        // Reuse existing parseSynonymsCsvUpload() — it reads from files['asp_synonyms_csv']
        // Map the uploaded file key to what the parser expects
        if (!empty($this->request->files['synonyms_csv'])) {
            $this->request->files['asp_synonyms_csv'] = $this->request->files['synonyms_csv'];
        }
        $groups = $this->parseSynonymsCsvUpload();
        if (empty($groups)) {
            $this->response->setOutput(json_encode(['status' => 'error', 'error' => 'No valid groups found in CSV']));
            return;
        }
        $this->syncSynonymGroupsFromPost([], $groups);
        $this->response->setOutput(json_encode(['status' => 'ok', 'imported' => count($groups)]));
    }

    /**
     * Propose AI synonyms for recent zero-result single-word queries.
     *
     * Skips multi-word queries (those belong to query rules). Results land in
     * `asp_synonym_pending` for manual admin review — never written directly
     * to active synonym groups.
     */
    public function generateSynonyms() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $limit = isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 50;
        $days = isset($this->request->post['days']) ? (int)$this->request->post['days'] : 30;
        $minCount = isset($this->request->post['min_count']) ? (int)$this->request->post['min_count'] : 2;

        $limit = max(1, min(500, $limit));
        $days = max(1, min(365, $days));
        $minCount = max(1, min(1000, $minCount));

        try {
            $this->load->model('extension/module/oc_kit_advanced_search_pro');
            $queries = $this->model_extension_module_oc_kit_advanced_search_pro->getNoResultQueryRows($limit, $days, $minCount);

            $settings = $this->asp->getSettings([
                'ai_provider' => 'openai',
                'ai_api_key' => '',
                'ai_model' => 'gpt-4o-mini',
                'ai_budget_monthly' => 50,
                'ai_budget_daily_limit' => 1000,
                'ai_auto_block' => 1,
            ]);

            $proposed = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($queries as $row) {
                $query = $this->asp->normalizeQuery((string)$row['query']);
                if ($query === '') {
                    continue;
                }
                if (preg_match('/\s/u', $query)) {
                    // Multi-word — synonyms only apply to single tokens.
                    $skipped++;
                    continue;
                }

                try {
                    $res = $this->asp->proposeSynonyms($query, $settings);
                    if (($res['status'] ?? '') === 'ok') {
                        $proposed++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                }
            }

            $this->response->setOutput(json_encode([
                'status'    => 'ok',
                'processed' => count($queries),
                'proposed'  => $proposed,
                'skipped'   => $skipped,
                'failed'    => $failed,
            ]));
        } catch (\Throwable $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] generateSynonyms: ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Synonyms generation failed', $e),
            ]));
        }
    }

    /**
     * Accept a pending AI synonym proposal → create real synonym group.
     */
    public function acceptSynonymPending() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');
        try {
            $id = (int)($this->request->post['id'] ?? 0);
            if ($id <= 0) {
                throw new \RuntimeException('Invalid id');
            }

            $pending = $this->asp->getPendingSynonyms('pending', 1000);
            $row = null;
            foreach ($pending as $p) {
                if ($p['id'] === $id) {
                    $row = $p;
                    break;
                }
            }
            if (!$row) {
                throw new \RuntimeException('Proposal not found or already reviewed');
            }

            $terms = array_merge([$row['query']], (array)$row['terms']);
            $terms = array_values(array_unique(array_filter(array_map('trim', $terms))));
            if (count($terms) < 2) {
                throw new \RuntimeException('Not enough terms');
            }

            $this->load->model('extension/module/oc_kit_advanced_search_pro');
            $groupId = $this->model_extension_module_oc_kit_advanced_search_pro->addSynonymGroup(
                $terms, 'AI: ' . $row['query']
            );
            $this->asp->markSynonymPending($id, 'accepted');

            $this->response->setOutput(json_encode([
                'status'   => 'ok',
                'group_id' => (int)$groupId,
            ]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Accept failed', $e),
            ]));
        }
    }

    /**
     * Reject a pending AI synonym proposal — mark as rejected, no group created.
     */
    public function rejectSynonymPending() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');
        try {
            $id = (int)($this->request->post['id'] ?? 0);
            if ($id <= 0) {
                throw new \RuntimeException('Invalid id');
            }
            $this->asp->markSynonymPending($id, 'rejected');
            $this->response->setOutput(json_encode(['status' => 'ok']));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Reject failed', $e),
            ]));
        }
    }

    /**
     * Load a curated, universal pack of brand synonyms shipped with the module.
     * Idempotent — groups whose name already exists are skipped, so re-running
     * is safe and only adds what's new.
     */
    public function loadBundledSynonyms() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        try {
            $path = DIR_SYSTEM . 'library/ockit/advanced_search_pro/data/brand_synonyms.json';
            if (!is_file($path)) {
                $this->response->setOutput(json_encode([
                    'status' => 'error',
                    'message' => 'brand_synonyms.json not found',
                ]));
                return;
            }
            $data = json_decode((string)file_get_contents($path), true);
            if (!is_array($data) || empty($data['groups']) || !is_array($data['groups'])) {
                $this->response->setOutput(json_encode([
                    'status' => 'error',
                    'message' => 'Bundled file is malformed',
                ]));
                return;
            }

            $this->load->model('extension/module/oc_kit_advanced_search_pro');

            // Existing group names (case-insensitive) — used to skip duplicates.
            $existing = [];
            $rows = $this->db->query("SELECT name FROM `" . DB_PREFIX . "asp_synonym_group`")->rows;
            foreach ($rows as $r) {
                $existing[mb_strtolower(trim((string)$r['name']))] = true;
            }

            $created = 0;
            $skipped = 0;
            foreach ($data['groups'] as $g) {
                $name  = trim((string)($g['name'] ?? ''));
                $terms = array_values(array_filter(array_map('trim', (array)($g['terms'] ?? []))));
                if ($name === '' || count($terms) < 2) {
                    continue;
                }
                $key = mb_strtolower($name);
                if (isset($existing[$key])) {
                    $skipped++;
                    continue;
                }
                $gid = $this->model_extension_module_oc_kit_advanced_search_pro->addSynonymGroup($terms, $name);
                if ($gid > 0) {
                    $created++;
                    $existing[$key] = true;
                }
            }

            $this->response->setOutput(json_encode([
                'status'  => 'ok',
                'created' => $created,
                'skipped' => $skipped,
                'total'   => count($data['groups']),
            ]));
        } catch (\Throwable $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] loadBundledSynonyms: ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Bundled load failed', $e),
            ]));
        }
    }

    /**
     * Apply a niche preset (clothes / electronics / food / cosmetics / home_garden / auto_parts / general):
     *   - adds the preset's synonym groups (idempotent, skips dupes by name)
     *   - merges the preset's recommended settings on top of the existing ones
     *
     * Synonyms are additive across presets — switching from one to another keeps
     * what was added. Settings are overwritten.
     */
    public function loadPreset() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $code = isset($this->request->post['code']) ? (string)$this->request->post['code'] : '';
        $code = preg_replace('/[^a-z0-9_]/i', '', $code);
        if ($code === '') {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'code required']));
            return;
        }

        try {
            $path = DIR_SYSTEM . 'library/ockit/advanced_search_pro/data/presets.json';
            if (!is_file($path)) {
                $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'presets.json not found']));
                return;
            }
            $data = json_decode((string)file_get_contents($path), true);
            if (!is_array($data) || empty($data['presets']) || !is_array($data['presets'])) {
                $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'presets file is malformed']));
                return;
            }

            $preset = null;
            foreach ($data['presets'] as $p) {
                if (($p['code'] ?? '') === $code) {
                    $preset = $p;
                    break;
                }
            }
            if (!$preset) {
                $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'Unknown preset: ' . $code]));
                return;
            }

            // 1. Synonyms — additive, skip dupes by group name (case-insensitive).
            $this->load->model('extension/module/oc_kit_advanced_search_pro');
            $existing = [];
            $rows = $this->db->query("SELECT name FROM `" . DB_PREFIX . "asp_synonym_group`")->rows;
            foreach ($rows as $r) {
                $existing[mb_strtolower(trim((string)$r['name']))] = true;
            }
            $synAdded = 0;
            $synSkipped = 0;
            foreach ((array)($preset['synonyms'] ?? []) as $g) {
                $name  = trim((string)($g['name'] ?? ''));
                $terms = array_values(array_filter(array_map('trim', (array)($g['terms'] ?? []))));
                if ($name === '' || count($terms) < 2) {
                    continue;
                }
                $key = mb_strtolower($name);
                if (isset($existing[$key])) {
                    $synSkipped++;
                    continue;
                }
                if ($this->model_extension_module_oc_kit_advanced_search_pro->addSynonymGroup($terms, $name) > 0) {
                    $synAdded++;
                    $existing[$key] = true;
                }
            }

            // 2. Settings — merge on top of current.
            $settingsApplied = [];
            if (!empty($preset['settings']) && is_array($preset['settings'])) {
                $this->load->model('setting/setting');
                $current = $this->model_setting_setting->getSetting('module_oc_kit_advanced_search_pro');
                if (!is_array($current)) {
                    $current = [];
                }
                foreach ($preset['settings'] as $k => $v) {
                    $fullKey = 'module_oc_kit_advanced_search_pro_' . $k;
                    $current[$fullKey] = $v;
                    $settingsApplied[$k] = $v;
                }
                $this->model_setting_setting->editSetting('module_oc_kit_advanced_search_pro', $current);
            }

            $this->response->setOutput(json_encode([
                'status'           => 'ok',
                'code'             => $code,
                'name'             => (string)($preset['name'] ?? $code),
                'synonyms_added'   => $synAdded,
                'synonyms_skipped' => $synSkipped,
                'synonyms_total'   => count((array)($preset['synonyms'] ?? [])),
                'settings_applied' => $settingsApplied,
            ]));
        } catch (\Throwable $e) {
            if ($this->log) {
                $this->log->write('[AdvancedSearchPro] loadPreset(' . $code . '): ' . $e->getMessage());
            }
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('Preset load failed', $e),
            ]));
        }
    }

    private function buildConnectionSettings($mode = '') {
        $defaults = [
            'host' => '127.0.0.1',
            'port' => '9306',
            'index' => 'products',
            'login' => '',
            'password' => '',
            'sphinx_host' => '127.0.0.1',
            'sphinx_port' => '9306',
            'sphinx_index' => 'products',
            'sphinx_login' => '',
            'sphinx_password' => ''
        ];

        $settings = $this->asp->getSettings($defaults);
        $mode = strtolower(trim((string)$mode));

        if (empty($settings['port'])) {
            $settings['port'] = '9306';
        }

        if ($mode === 'sphinx') {
            $settings['host'] = $settings['sphinx_host'] ?: $settings['host'];
            $settings['port'] = $settings['sphinx_port'] ?: $settings['port'];
            $settings['index'] = $settings['sphinx_index'] ?: $settings['index'];
            $settings['login'] = $settings['sphinx_login'] ?: $settings['login'];
            $settings['password'] = $settings['sphinx_password'] ?: $settings['password'];
        }

        return $settings;
    }

    private function getLocalDaemonMetrics() {
        $result = ['pid' => '-', 'memory' => '-', 'cpu' => '-'];

        $pid = trim((string)@shell_exec("pgrep -f 'searchd|manticore|sphinx' | head -n 1"));
        if ($pid === '') {
            return $result;
        }

        $pid = preg_replace('/[^0-9]/', '', $pid);
        if ($pid === '') {
            return $result;
        }

        $stats = trim((string)@shell_exec("ps -p " . $pid . " -o %cpu=,%mem="));
        $result['pid'] = $pid;

        if ($stats !== '') {
            $parts = preg_split('/\s+/', trim($stats));
            if (!empty($parts[0])) {
                $result['cpu'] = trim($parts[0]) . '%';
            }
            if (!empty($parts[1])) {
                $result['memory'] = trim($parts[1]) . '%';
            }
        }

        return $result;
    }

    private function formatAjaxErrorMessage($fallback, \Throwable $e) {
        $fallback = trim((string)$fallback);
        if ($fallback === '') {
            $fallback = 'Request failed';
        }

        if ((int)$this->config->get('module_oc_kit_advanced_search_pro_debug') === 1) {
            return $fallback . ': ' . $e->getMessage();
        }

        return $fallback;
    }


    public function saveWizard() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->language('extension/module/oc_kit_advanced_search_pro');
        $this->load->model('setting/setting');

        try {
            $post = $this->request->post;
            $new  = [];

            // Step 1 — search engine mode
            $mode = in_array($post['wiz_mode'] ?? '', ['native', 'manticore', 'hybrid', 'sphinx'])
                ? $post['wiz_mode'] : 'native';
            $new['module_oc_kit_advanced_search_pro_mode']   = $mode;
            $new['module_oc_kit_advanced_search_pro_status'] = 1;

            // Step 2 — connection (only for non-native modes)
            if ($mode !== 'native') {
                if (!empty($post['wiz_host']))     $new['module_oc_kit_advanced_search_pro_host']     = trim((string)$post['wiz_host']);
                if (!empty($post['wiz_port']))     $new['module_oc_kit_advanced_search_pro_port']     = (int)$post['wiz_port'];
                if (!empty($post['wiz_index']))    $new['module_oc_kit_advanced_search_pro_index']    = trim((string)$post['wiz_index']);
                if (isset($post['wiz_login']))     $new['module_oc_kit_advanced_search_pro_login']    = trim((string)$post['wiz_login']);
                if (isset($post['wiz_password']))  $new['module_oc_kit_advanced_search_pro_password'] = trim((string)$post['wiz_password']);
            }

            // Step 3 — language / morphology
            // Map UI language codes to real Manticore morphology tokens.
            // IndexService::buildMorphologyOptions() whitelists only the
            // lemmatize_* / stem_* tokens — bare 'uk' / 'ru' / 'none' would be
            // silently dropped → morphology effectively disabled. Ukrainian
            // pulls Russian+English lemmatizers (UA is handled at index level
            // via the wordforms file the user generates separately).
            $morphMap = [
                'uk'    => 'lemmatize_ru_all, lemmatize_en_all',
                'ru'    => 'lemmatize_ru_all',
                'en'    => 'lemmatize_en_all',
                'mixed' => 'lemmatize_ru_all, lemmatize_en_all',
            ];
            $wizLang  = (string)($post['wiz_morphology'] ?? 'uk');
            $new['module_oc_kit_advanced_search_pro_morphology'] = $morphMap[$wizLang] ?? 'lemmatize_ru_all, lemmatize_en_all';

            // Step 4 — AI key (optional; only saved when non-empty)
            $wizAiKey = trim((string)($post['wiz_ai_key'] ?? ''));
            if ($wizAiKey !== '') {
                $new['module_oc_kit_advanced_search_pro_ai_api_key']        = $wizAiKey;
                $new['module_oc_kit_advanced_search_pro_ai_provider']       = 'openai';
                $new['module_oc_kit_advanced_search_pro_ai_budget_monthly'] = max(1, (int)($post['wiz_budget'] ?? 10));
            }

            // Step 5 — cron
            if (!empty($post['wiz_cron_enabled'])) {
                $new['module_oc_kit_advanced_search_pro_cron_enabled'] = 1;
                $new['module_oc_kit_advanced_search_pro_cron_type']    = 'auto';
                $existingKey = (string)$this->config->get('module_oc_kit_advanced_search_pro_cron_key');
                if ($existingKey === '') {
                    $new['module_oc_kit_advanced_search_pro_cron_key'] = bin2hex(random_bytes(16));
                }
            }

            // Merge with existing settings (preserve everything not touched by wizard)
            $existing = $this->model_setting_setting->getSetting('module_oc_kit_advanced_search_pro');
            $merged   = array_merge($existing, $new);
            $this->model_setting_setting->editSetting('module_oc_kit_advanced_search_pro', $merged);

            $this->asp->setMeta('wizard_done', '1');

            $this->response->setOutput(json_encode(['status' => 'ok']));
        } catch (\Throwable $e) {
            if ($this->log) $this->log->write('[AdvancedSearchPro] saveWizard: ' . $e->getMessage());
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => $this->formatAjaxErrorMessage('Wizard save failed', $e)]));
        }
    }

    private function parseSynonymsCsvUpload() {
        if (empty($this->request->files['asp_synonyms_csv']) || !is_array($this->request->files['asp_synonyms_csv'])) {
            return [];
        }

        $file = $this->request->files['asp_synonyms_csv'];
        $groups = [];

        $files = [];
        if (is_array($file['tmp_name'])) {
            foreach ($file['tmp_name'] as $idx => $tmpName) {
                $files[] = [
                    'tmp_name' => $tmpName,
                    'error' => $file['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                    'name' => $file['name'][$idx] ?? 'csv'
                ];
            }
        } else {
            $files[] = [
                'tmp_name' => $file['tmp_name'],
                'error' => $file['error'],
                'name' => $file['name'] ?? 'csv'
            ];
        }

        foreach ($files as $oneFile) {
            if (!empty($oneFile['error']) || empty($oneFile['tmp_name']) || !is_uploaded_file($oneFile['tmp_name'])) {
                continue;
            }

            $handle = @fopen($oneFile['tmp_name'], 'rb');
            if (!$handle) {
                continue;
            }

            $lineNo = 0;
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $lineNo++;
                $terms = [];
                foreach ($data as $term) {
                    $term = trim((string)$term);
                    if ($term !== '') {
                        $terms[] = $term;
                    }
                }

                $terms = array_values(array_unique($terms));
                if (count($terms) >= 2) {
                    $filename = (string)($oneFile['name'] ?? 'csv');
                    $groups[] = [
                        'name' => trim($filename) !== '' ? ($filename . ' #' . $lineNo) : ('CSV #' . $lineNo),
                        'terms' => $terms
                    ];
                }
            }

            fclose($handle);
        }

        return $groups;
    }

    /**
     * List query rules with optional search filter and pagination.
     * GET params (passed as POST for AJAX): page, limit, search
     */
    public function listQueryRules() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $page   = max(1, (int)($this->request->post['page']  ?? 1));
        $limit  = max(1, min(200, (int)($this->request->post['limit'] ?? 50)));
        $search = trim((string)($this->request->post['search'] ?? ''));

        $offset = ($page - 1) * $limit;

        try {
            $where = $search !== ''
                ? "WHERE query_normalized LIKE '%" . $this->db->escape($search) . "%'"
                : '';

            $total = (int)$this->db->query(
                "SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_query_rule` " . $where
            )->row['c'];

            $rows = $this->db->query(
                "SELECT id, query_normalized, rewritten_query, expanded_json, intent, source, hits, updated_at
                 FROM `" . DB_PREFIX . "asp_query_rule` " . $where . "
                 ORDER BY hits DESC, updated_at DESC
                 LIMIT " . (int)$limit . " OFFSET " . (int)$offset
            )->rows;

            $items = [];
            foreach ($rows as $row) {
                $expanded = [];
                if (!empty($row['expanded_json'])) {
                    $decoded = json_decode((string)$row['expanded_json'], true);
                    if (is_array($decoded)) {
                        $expanded = $decoded;
                    }
                }
                $items[] = [
                    'id'               => (int)$row['id'],
                    'query'            => (string)$row['query_normalized'],
                    'rewrite'          => (string)$row['rewritten_query'],
                    'expanded'         => $expanded,
                    'intent'           => (string)$row['intent'],
                    'source'           => (string)$row['source'],
                    'hits'             => (int)$row['hits'],
                    'updated_at'       => (string)$row['updated_at'],
                ];
            }

            $this->response->setOutput(json_encode([
                'status' => 'ok',
                'total'  => $total,
                'page'   => $page,
                'limit'  => $limit,
                'pages'  => (int)ceil($total / $limit),
                'items'  => $items,
            ]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('listQueryRules failed', $e),
            ]));
        }
    }

    /**
     * Update a single query rule (expanded terms, rewrite, intent).
     */
    public function updateQueryRule() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $id = (int)($this->request->post['id'] ?? 0);
        if ($id <= 0) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'id required']));
            return;
        }

        try {
            $expandedRaw = trim((string)($this->request->post['expanded'] ?? ''));
            $rewrite     = trim((string)($this->request->post['rewrite']  ?? ''));
            $intent      = trim((string)($this->request->post['intent']   ?? ''));

            // Parse expanded: comma- or newline-separated terms
            $expanded = [];
            foreach (preg_split('/[\n,]+/', $expandedRaw) as $term) {
                $term = trim($term);
                if ($term !== '') {
                    $expanded[] = $term;
                }
            }
            $expanded = array_values(array_unique($expanded));

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "asp_query_rule`
                 SET rewritten_query = '" . $this->db->escape($rewrite) . "',
                     expanded_json   = '" . $this->db->escape(json_encode($expanded)) . "',
                     intent          = '" . $this->db->escape($intent) . "',
                     source          = 'manual',
                     updated_at      = NOW()
                 WHERE id = " . $id
            );

            $this->response->setOutput(json_encode(['status' => 'ok', 'id' => $id]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('updateQueryRule failed', $e),
            ]));
        }
    }

    /**
     * Reset wizard state — hides wizard-done message and shows wizard form again.
     */
    public function resetWizard() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->asp->setMeta('wizard_done', '0');
        $this->response->setOutput(json_encode(['status' => 'ok']));
    }

    /**
     * Manual index run from admin UI — bypasses cron_enabled check.
     * Runs the same indexing tasks as the catalog cron endpoint.
     */
    public function manualRun() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $type   = trim((string)($this->request->post['type']   ?? 'incremental'));
        $limit  = max(1, min(5000, (int)($this->request->post['limit']  ?? 500)));
        $offset = max(0, (int)($this->request->post['offset'] ?? 0));
        $minutes = max(1, min(10080, (int)($this->request->post['minutes'] ?? 180)));

        // Load catalog model directly — it holds all indexing methods
        if (!class_exists('ModelExtensionModuleOcKitAdvancedSearchPro', false)) {
            $catalogModelPath = defined('DIR_CATALOG')
                ? DIR_CATALOG . 'model/extension/module/oc_kit_advanced_search_pro.php'
                : DIR_APPLICATION . '../catalog/model/extension/module/oc_kit_advanced_search_pro.php';
            require_once($catalogModelPath);
        }
        $model = new ModelExtensionModuleOcKitAdvancedSearchPro($this->registry);

        $processed = 0;
        $extra = [];

        $total = 0;
        if ($type === 'full') {
            $totalRow = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product`");
            $total = (int)($totalRow->row['cnt'] ?? 0);
        }

        try {
            if ($type === 'full') {
                $processed = $model->reindexAll($limit, $offset);
                $model->logCron($type, 'ok', 'Reindexed: ' . $processed . ' (offset ' . $offset . ')');
            } elseif ($type === 'incremental') {
                $processed = $model->processQueue($limit);
                $model->logCron($type, 'ok', 'Processed: ' . $processed);
            } elseif ($type === 'sync_modified') {
                $processed = $model->syncModifiedProducts($limit, $minutes);
                $model->logCron($type, 'ok', 'Queued modified products: ' . $processed . ' (minutes ' . $minutes . ')');
            } elseif ($type === 'ai_rules') {
                $result = $model->generateQueryRules($limit, 30, 2, ['rule_refresh_days' => 7]);
                $processed = (int)($result['created'] ?? 0);
                $extra = ['failed' => (int)($result['failed'] ?? 0), 'total' => (int)($result['total'] ?? 0)];
                $model->logCron($type, 'ok', 'AI rules: ' . $processed . '/' . ($extra['total']) . ', failed: ' . $extra['failed']);
            } elseif ($type === 'warm_cache') {
                $result = $this->asp->warmQueryRuleCache(500);
                $processed = (int)($result['cached'] ?? 0);
                $model->logCron($type, 'ok', 'Warm cache: cached ' . $processed);
            } elseif ($type === 'reembed_all') {
                $processed = $this->asp->queueAllProductsForEmbedding();
                $extra = ['pending' => $this->embeddingQueuePending()];
                $model->logCron($type, 'ok', 'Queued for re-embedding: ' . $processed);
            } elseif ($type === 'embed_missing') {
                // Incremental: queue only products without a current-model embedding.
                // Already-embedded products are left untouched, so the button can be
                // re-run cheaply to fill gaps without paying for what is done.
                $processed = $this->asp->queueMissingProductsForEmbedding();
                $extra = ['pending' => $this->embeddingQueuePending()];
                $model->logCron($type, 'ok', 'Queued missing for embedding: ' . $processed);
            } elseif ($type === 'embedding') {
                // Drain a batch of the embedding queue NOW (synchronous, paid AI).
                // The reembed button polls this until pending hits 0, showing live
                // generation progress instead of silently queueing.
                $processed = $this->asp->processEmbeddingQueue($limit);
                $extra = ['pending' => $this->embeddingQueuePending()];
                $model->logCron($type, 'ok', 'Embedded: ' . $processed . ', pending: ' . $extra['pending']);
            } elseif ($type === 'purge_log') {
                $days = max(7, min(3650, (int)($this->request->post['days'] ?? 90)));
                $result = $this->asp->purgeOldData($days);
                $processed = array_sum($result);
                $extra = $result;
                $model->logCron($type, 'ok', 'Purged: ' . $processed . ' (ttl=' . $days . 'd)');
            } else {
                $processed = $model->processQueue($limit);
                $model->logCron($type, 'ok', 'Processed: ' . $processed);
            }
        } catch (\Throwable $e) {
            if ($this->log) $this->log->write('[AdvancedSearchPro] manualRun(' . $type . '): ' . $e->getMessage());
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'type'    => $type,
                'message' => $this->formatAjaxErrorMessage('Run failed', $e),
            ]));
            return;
        }

        $response = ['status' => 'ok', 'type' => $type, 'processed' => $processed, 'total' => $total, 'offset' => $offset];
        if ($extra) {
            $response = array_merge($response, $extra);
        }
        $this->response->setOutput(json_encode($response));
    }

    /** Pending items left in the embedding queue — drives the reembed progress UI. */
    private function embeddingQueuePending(): int {
        $row = $this->db->query(
            "SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_embedding_queue` WHERE status = 'pending'"
        )->row;
        return (int)($row['c'] ?? 0);
    }

    /**
     * Clear query log (statistics).
     */
    public function clearQueryLog() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $this->model_extension_module_oc_kit_advanced_search_pro->clearQueryLog();

        $this->response->setOutput(json_encode(['status' => 'ok', 'message' => 'Query log cleared']));
    }

    public function searchAttributes() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $q = trim((string)($this->request->get['q'] ?? ''));
        $items = $this->model_extension_module_oc_kit_advanced_search_pro->getAttributeCatalog($q, 1, 50);

        $this->response->setOutput(json_encode(['items' => $items]));
    }

    /** Product autocomplete for the popular-products picker. */
    public function searchProducts() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $q = trim((string)($this->request->get['q'] ?? ''));
        $rows = $this->model_extension_module_oc_kit_advanced_search_pro->getProductCatalog($q, 30);
        $items = [];
        foreach ($rows as $r) { $items[] = ['id' => (int)$r['product_id'], 'name' => (string)$r['name']]; }

        $this->response->setOutput(json_encode(['items' => $items]));
    }

    /** Manufacturer/brand autocomplete for the popular-brands picker. */
    public function searchBrands() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('extension/module/oc_kit_advanced_search_pro');
        $q = trim((string)($this->request->get['q'] ?? ''));
        $rows = $this->model_extension_module_oc_kit_advanced_search_pro->getManufacturerCatalog($q, 30);
        $items = [];
        foreach ($rows as $r) { $items[] = ['id' => (int)$r['manufacturer_id'], 'name' => (string)$r['name']]; }

        $this->response->setOutput(json_encode(['items' => $items]));
    }

    /**
     * Delete a single query rule by ID.
     */
    public function deleteSynonymGroup() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $id = (int)($this->request->post['id'] ?? 0);
        if ($id <= 0) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'id required']));
            return;
        }

        try {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym_group` WHERE group_id = " . $id);
            $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id = " . $id);
            $this->response->setOutput(json_encode(['status' => 'ok', 'id' => $id]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('deleteSynonymGroup failed', $e),
            ]));
        }
    }

    public function deleteQueryRule() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json');

        $id = (int)($this->request->post['id'] ?? 0);
        if ($id <= 0) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'id required']));
            return;
        }

        try {
            $this->db->query(
                "DELETE FROM `" . DB_PREFIX . "asp_query_rule` WHERE id = " . $id
            );
            $this->response->setOutput(json_encode(['status' => 'ok', 'id' => $id]));
        } catch (\Throwable $e) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => $this->formatAjaxErrorMessage('deleteQueryRule failed', $e),
            ]));
        }
    }

    // ── Dictionary UI endpoints ──────────────────────────────────────────────

    /**
     * POST: upload CSV or JSON file, import morphology entries.
     * Returns JSON { status, imported, errors }
     */
    public function dictionaryImport() {
        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'Permission denied']));
            return;
        }

        if (!isset($_FILES['dict_file']) || $_FILES['dict_file']['error'] !== UPLOAD_ERR_OK) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'No file uploaded']));
            return;
        }

        $tmpPath  = $_FILES['dict_file']['tmp_name'];
        $origName = strtolower((string)($_FILES['dict_file']['name'] ?? ''));
        $lang     = mb_strtolower(trim((string)($this->request->post['dict_lang'] ?? '')), 'UTF-8');

        $entries = [];
        $errors  = [];

        if (str_ends_with($origName, '.json')) {
            $raw = file_get_contents($tmpPath);
            $arr = json_decode($raw, true);
            if (!is_array($arr)) {
                $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'Invalid JSON']));
                return;
            }
            foreach ($arr as $i => $row) {
                if (is_array($row) && isset($row['word'], $row['stem'])) {
                    $entries[] = [
                        'word'     => $row['word'],
                        'stem'     => $row['stem'],
                        'language' => $row['language'] ?? $row['lang'] ?? $lang,
                    ];
                } else {
                    $errors[] = 'Row ' . $i . ': expected {word, stem}';
                }
            }
        } else {
            // CSV: word,stem[,language]  or  word|stem[|language]
            $fp = fopen($tmpPath, 'r');
            $lineNo = 0;
            while (($line = fgets($fp)) !== false) {
                $lineNo++;
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;

                // Detect delimiter
                $delim = strpos($line, '|') !== false ? '|' : ',';
                $parts = array_map('trim', explode($delim, $line));

                if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                    $errors[] = 'Line ' . $lineNo . ': skipped';
                    continue;
                }
                $entries[] = [
                    'word'     => $parts[0],
                    'stem'     => $parts[1],
                    'language' => $parts[2] ?? $lang,
                ];
            }
            fclose($fp);
        }

        if (empty($entries)) {
            $this->response->setOutput(json_encode([
                'status'  => 'error',
                'message' => 'No valid entries found',
                'errors'  => $errors,
            ]));
            return;
        }

        $dict     = new \OcKit\AdvancedSearchPro\Libs\DictionaryService($this->db);
        $imported = $dict->import($entries);

        $this->response->setOutput(json_encode([
            'status'   => 'ok',
            'imported' => $imported,
            'errors'   => $errors,
            'counts'   => $dict->getCounts(),
        ]));
    }

    /**
     * POST: delete dictionary entries by language (or all if lang='').
     */
    public function dictionaryDelete() {
        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_advanced_search_pro')) {
            $this->response->setOutput(json_encode(['status' => 'error', 'message' => 'Permission denied']));
            return;
        }

        $lang = mb_strtolower(trim((string)($this->request->post['lang'] ?? '')), 'UTF-8');
        $dict = new \OcKit\AdvancedSearchPro\Libs\DictionaryService($this->db);
        $dict->deleteByLanguage($lang);

        $this->response->setOutput(json_encode([
            'status' => 'ok',
            'counts' => $dict->getCounts(),
        ]));
    }

    /**
     * GET: return paginated dictionary entries as JSON.
     */
    public function dictionaryEntries() {
        if (!$this->requirePermission()) { return; }
        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');

        $lang   = mb_strtolower(trim((string)($this->request->get['lang'] ?? '')), 'UTF-8');
        $page   = max(1, (int)($this->request->get['page'] ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $dict    = new \OcKit\AdvancedSearchPro\Libs\DictionaryService($this->db);
        $entries = $dict->getEntries($lang, $limit, $offset);
        $total   = $dict->getTotal($lang);

        $this->response->setOutput(json_encode([
            'status'  => 'ok',
            'entries' => $entries,
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int)ceil($total / $limit),
        ]));
    }
}
