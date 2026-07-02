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

class ControllerExtensionModuleOcKitSeoCore extends Controller
{
    private const ROUTE   = 'extension/module/oc_kit_seo_core';
    private const VERSION = '1.0.0';

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $db = $this->db;

        // Redirects. Includes last_hit_at.
        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_redirects` (
                `redirect_id` INT(11) NOT NULL AUTO_INCREMENT,
                `store_id`    INT(11) NOT NULL DEFAULT 0,
                `from_url`    VARCHAR(2048) NOT NULL,
                `to_url`      VARCHAR(2048) NOT NULL,
                `code`        SMALLINT(3) NOT NULL DEFAULT 301,
                `hits`        INT(11) NOT NULL DEFAULT 0,
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_hit_at` DATETIME NULL DEFAULT NULL,
                `expires_at`  DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`redirect_id`),
                KEY `idx_store_from` (`store_id`, `from_url`(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_meta_override` (
                `meta_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id`       INT(11) NOT NULL DEFAULT 0,
                `language_id`    INT(11) NOT NULL DEFAULT 0,
                `entity_type`    VARCHAR(32) NOT NULL,
                `entity_id`      INT(11) NOT NULL,
                `title`          VARCHAR(255) NULL DEFAULT NULL,
                `description`    VARCHAR(512) NULL DEFAULT NULL,
                `h1`             VARCHAR(255) NULL DEFAULT NULL,
                `robots`         VARCHAR(64) NULL DEFAULT NULL,
                `canonical`      VARCHAR(512) NULL DEFAULT NULL,
                `og_title`       VARCHAR(255) NULL DEFAULT NULL,
                `og_description` VARCHAR(512) NULL DEFAULT NULL,
                `og_image`       VARCHAR(512) NULL DEFAULT NULL,
                `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`meta_id`),
                UNIQUE KEY `uq_entity` (`store_id`, `language_id`, `entity_type`, `entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_schema_rules` (
                `rule_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id`      INT(11) NOT NULL DEFAULT 0,
                `route_pattern` VARCHAR(255) NOT NULL,
                `template`      TEXT NOT NULL,
                `priority`      INT(11) NOT NULL DEFAULT 0,
                `status`        TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`rule_id`),
                KEY `idx_store_route` (`store_id`, `route_pattern`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_audit_results` (
                `result_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id`    INT(11) NOT NULL DEFAULT 0,
                `language_id` INT(11) NOT NULL DEFAULT 0,
                `entity_type` VARCHAR(32) NOT NULL,
                `entity_id`   INT(11) NOT NULL DEFAULT 0,
                `entity_name` VARCHAR(512) NOT NULL DEFAULT '',
                `issue_type`  VARCHAR(64) NOT NULL,
                `severity`    VARCHAR(16) NOT NULL DEFAULT 'info',
                `detail`      TEXT NOT NULL,
                `status`      VARCHAR(16) NOT NULL DEFAULT 'new',
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`result_id`),
                KEY `idx_store_lang` (`store_id`, `language_id`),
                KEY `idx_severity`   (`severity`),
                KEY `idx_status`     (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_header_rules` (
                `rule_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id`      INT(11) NOT NULL DEFAULT 0,
                `url_pattern`   VARCHAR(512) NOT NULL DEFAULT '',
                `route_pattern` VARCHAR(255) NOT NULL DEFAULT '',
                `header_name`   VARCHAR(64) NOT NULL DEFAULT 'X-Robots-Tag',
                `header_value`  VARCHAR(512) NOT NULL DEFAULT '',
                `robots_value`  VARCHAR(128) NOT NULL DEFAULT '',
                `apply_header`  TINYINT(1) NOT NULL DEFAULT 1,
                `apply_meta`    TINYINT(1) NOT NULL DEFAULT 1,
                `status`        TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order`    INT(11) NOT NULL DEFAULT 0,
                `comment`       VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY (`rule_id`),
                KEY `idx_store_status` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_url_history` (
                `history_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `seo_url_id`  INT(11) NOT NULL DEFAULT 0,
                `store_id`    INT(11) NOT NULL DEFAULT 0,
                `language_id` INT(11) NOT NULL DEFAULT 0,
                `query`       VARCHAR(255) NOT NULL DEFAULT '',
                `old_keyword` VARCHAR(255) NOT NULL DEFAULT '',
                `new_keyword` VARCHAR(255) NOT NULL DEFAULT '',
                `changed_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`history_id`),
                KEY `idx_query` (`query`(191)),
                KEY `idx_seo_url` (`seo_url_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_broken_links` (
                `link_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `url`         VARCHAR(2048) NOT NULL,
                `entity_type` VARCHAR(32) NOT NULL,
                `entity_id`   INT(11) NOT NULL,
                `entity_name` VARCHAR(512) NOT NULL DEFAULT '',
                `status_code` SMALLINT(4) NOT NULL DEFAULT 0,
                `error`       VARCHAR(255) NOT NULL DEFAULT '',
                `checked_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`link_id`),
                KEY `idx_status` (`status_code`),
                KEY `idx_url`    (`url`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_ab_tests` (
                `test_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity_type`     VARCHAR(32) NOT NULL,
                `entity_id`       INT(11) NOT NULL,
                `language_id`     INT(11) NOT NULL,
                `variant_a_title` VARCHAR(255) NOT NULL,
                `variant_b_title` VARCHAR(255) NOT NULL,
                `hits_a`          INT UNSIGNED NOT NULL DEFAULT 0,
                `hits_b`          INT UNSIGNED NOT NULL DEFAULT 0,
                `status`          ENUM('active','ended') NOT NULL DEFAULT 'active',
                `started_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ended_at`        DATETIME NULL,
                PRIMARY KEY (`test_id`),
                KEY `idx_entity` (`entity_type`,`entity_id`,`language_id`,`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_seo_absurl_log` (
                `log_id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity_type`      VARCHAR(32) NOT NULL,
                `entity_ids`       TEXT NOT NULL,
                `field`            VARCHAR(64) NOT NULL DEFAULT '',
                `old_url`          VARCHAR(512) NOT NULL,
                `new_url`          VARCHAR(512) NOT NULL,
                `rows_updated`     INT(11) NOT NULL DEFAULT 0,
                `replaced_by_uid`  INT(11) NULL DEFAULT NULL,
                `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`log_id`),
                KEY `idx_entity_type` (`entity_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function uninstall(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_redirects`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_meta_override`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_schema_rules`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_audit_results`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_header_rules`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_absurl_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_url_history`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_broken_links`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_seo_ab_tests`");
    }

    // ─── License page (no guardAdmin — avoids redirect loop) ─────────────────

    public function license(): void
    {
        $this->load->language(self::ROUTE);
        $this->document->setTitle($this->language->get('heading_title'));

        $licenseInfo = $this->getLicenseInfo();

        if (!empty($licenseInfo['valid'])) {
            $this->response->redirect($this->url->link(self::ROUTE, 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/seo-core/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/seo-core/assets/js/admin.js');

        $data = $this->buildBreadcrumbs();
        $data['license_info']      = $licenseInfo;
        $data['license_key']       = (string)$this->config->get('module_oc_kit_seo_core_license_key');
        $data['extensions_url']    = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['activate_url']      = $this->url->link(self::ROUTE . '/activateLicense', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token']        = $this->session->data['user_token'];

        $data['text_extension']          = $this->language->get('text_extension');
        $data['tab_license']             = $this->language->get('tab_license');
        $data['entry_license_key']       = $this->language->get('entry_license_key');
        $data['button_activate']         = $this->language->get('button_activate');
        $data['text_license_version']    = $this->language->get('text_license_version');
        $data['text_license_buy']        = $this->language->get('text_license_buy');
        $data['text_license_trial']      = $this->language->get('text_license_trial');
        $data['text_license_expired']    = $this->language->get('text_license_expired');
        $data['text_license_invalid']    = $this->language->get('text_license_invalid');
        $data['text_license_api_error']  = $this->language->get('text_license_api_error');
        $data['text_license_not_validated'] = $this->language->get('text_license_not_validated');

        $data['lang_js']           = json_encode([
            'js_activating'          => $this->language->get('text_license_activating'),
            'button_activate'        => $this->language->get('button_activate'),
            'text_license_activated' => $this->language->get('text_license_activated'),
            'text_license_error'     => $this->language->get('error_license_invalid_key'),
            'mask_hint_prefix'       => $this->language->get('text_mask_hint_prefix'),
        ]);

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']  = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/seo_core/license', $data));
    }

    public function activateLicense(): void
    {
        $this->requireLib();
        $this->load->language(self::ROUTE);
        $key = trim((string)($this->request->post['key'] ?? ''));

        $result = \OcKit\SeoCore\SeoCore::activateLicenseKey($this->registry, $key);

        $json = ['success' => $result['success']];

        if ($result['success']) {
            $json['message']      = $this->language->get('text_license_activated');
            $json['redirect_url'] = html_entity_decode($this->url->link(self::ROUTE, 'user_token=' . $this->session->data['user_token'], true));
        } else {
            $json['message'] = $this->language->get('error_license_' . ($result['error_code'] ?: 'invalid_key'));
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // ─── Main settings page ───────────────────────────────────────────────────

    public function index(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $this->load->language(self::ROUTE);

        // Detect ocStore-style native meta_h1 column; UI is gated by this flag
        $supportsH1 = \OcKit\SeoCore\SeoCore::supportsNativeH1($this->db);

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/seo-core/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/seo-core/assets/js/admin.js');

        $this->load->model('localisation/language');
        $ut = 'user_token=' . $this->session->data['user_token'];

        $data = $this->buildBreadcrumbs();
        $data['user_token'] = $this->session->data['user_token'];
        $data['languages']  = $this->model_localisation_language->getLanguages();

        // Multi-store: load list (default store + custom). UI hides selector if only one.
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        $data['stores'] = array_merge(
            [['store_id' => 0, 'name' => $this->config->get('config_name') ?: 'Default']],
            array_map(static function ($s) {
                return ['store_id' => (int)$s['store_id'], 'name' => (string)$s['name']];
            }, $stores)
        );

        // Settings
        $data['save_url']            = html_entity_decode($this->url->link(self::ROUTE . '/save',           $ut, true));
        $data['mask_regenerate_url'] = html_entity_decode($this->url->link(self::ROUTE . '/maskRegenerate', $ut, true));

        $configKeys = [
            'module_oc_kit_seo_core_status',
            'module_oc_kit_seo_core_url_depth',
            'module_oc_kit_seo_core_product_include_category',
            'module_oc_kit_seo_core_trailing_slash',
            'module_oc_kit_seo_core_lang_prefixes',
            'module_oc_kit_seo_core_custom_routes',
            'module_oc_kit_seo_core_pagination_mode',
            'module_oc_kit_seo_core_noindex_all_pagination',
            'module_oc_kit_seo_core_noindex_from_page',
            'module_oc_kit_seo_core_noindex_delivery',
            'module_oc_kit_seo_core_mask_product',
            'module_oc_kit_seo_core_mask_category',
            'module_oc_kit_seo_core_mask_manufacturer',
            'module_oc_kit_seo_core_mask_information',
            'module_oc_kit_seo_core_auto_generate_url',
            'module_oc_kit_seo_core_meta_tpl_mode',
            'module_oc_kit_seo_core_hreflang_enabled',
            'module_oc_kit_seo_core_hreflang_format',
            'module_oc_kit_seo_core_home_redirect_index',
            'module_oc_kit_seo_core_allow_duplicate_keywords',
            'module_oc_kit_seo_core_webhook_url',
            'module_oc_kit_seo_core_webhook_secret',
            'module_oc_kit_seo_core_resource_hints',
            'module_oc_kit_seo_core_schema_providers',
            'module_oc_kit_seo_core_og_enabled',
            'module_oc_kit_seo_core_og_twitter_card',
            'module_oc_kit_seo_core_og_twitter_handle',
            'module_oc_kit_seo_core_og_image_fallback',
            'module_oc_kit_seo_core_schema_product',
            'module_oc_kit_seo_core_schema_breadcrumb',
            'module_oc_kit_seo_core_schema_organization',
            'module_oc_kit_seo_core_schema_website',
            'module_oc_kit_seo_core_schema_article',
            'module_oc_kit_seo_core_schema_min_reviews',
            'module_oc_kit_seo_core_schema_org_name',
            'module_oc_kit_seo_core_schema_org_logo',
            'module_oc_kit_seo_core_schema_org_phone',
            'module_oc_kit_seo_core_schema_org_email',
            'module_oc_kit_seo_core_schema_org_type',
            'module_oc_kit_seo_core_schema_org_street',
            'module_oc_kit_seo_core_schema_org_locality',
            'module_oc_kit_seo_core_schema_org_region',
            'module_oc_kit_seo_core_schema_org_postal_code',
            'module_oc_kit_seo_core_schema_org_country',
            'module_oc_kit_seo_core_schema_org_geo_lat',
            'module_oc_kit_seo_core_schema_org_geo_lon',
            'module_oc_kit_seo_core_schema_org_price_range',
            'module_oc_kit_seo_core_schema_org_vat_id',
            'module_oc_kit_seo_core_schema_org_founding_date',
            'module_oc_kit_seo_core_schema_org_opening_hours',
            'module_oc_kit_seo_core_schema_org_same_as',
            'module_oc_kit_seo_core_schema_org_founders',
            'module_oc_kit_seo_core_schema_org_contact_languages',
            'module_oc_kit_seo_core_ab_test_enabled',
            'module_oc_kit_seo_core_strip_query_params',
            'module_oc_kit_seo_core_gsc_client_id',
            'module_oc_kit_seo_core_gsc_client_secret',
            'module_oc_kit_seo_core_gsc_site_property',
        ];
        $legacyJsonKeys = [
            'module_oc_kit_seo_core_lang_prefixes',
            'module_oc_kit_seo_core_custom_routes',
        ];
        // Default values for settings that need a non-empty fallback when never saved.
        $configDefaults = [
            'module_oc_kit_seo_core_noindex_from_page' => 2,
            'module_oc_kit_seo_core_noindex_delivery'  => 'meta',
        ];
        foreach ($configKeys as $key) {
            $val = $this->config->get($key);
            // Legacy rows may contain HTML-escaped JSON (&quot; instead of ") — decode it
            if (in_array($key, $legacyJsonKeys, true) && is_string($val) && strpos($val, '&quot;') !== false) {
                $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
            }
            if (($val === null || $val === '') && isset($configDefaults[$key])) {
                $val = $configDefaults[$key];
            }
            $data[$key] = $val;
        }

        // Meta template fields per type × language
        $metaTypes = ['product', 'category', 'manufacturer', 'information', 'article', 'blog_category'];
        $data['meta_tpls'] = [];
        foreach ($metaTypes as $type) {
            foreach ($data['languages'] as $lang) {
                $lcode = explode('-', strtolower($lang['code']))[0];
                $data['meta_tpls'][$type][$lcode] = [
                    'lang_name' => $lang['name'],
                    'title_tpl' => (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_title_tpl_{$lcode}"),
                    'desc_tpl'  => (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_desc_tpl_{$lcode}"),
                    'h1_tpl'    => $supportsH1 ? (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_h1_tpl_{$lcode}") : '',
                ];
            }
        }
        $data['meta_type_list'] = [
            ['key' => 'product',       'vars_hint' => $this->language->get('text_meta_vars_product')],
            ['key' => 'category',      'vars_hint' => $this->language->get('text_meta_vars_category')],
            ['key' => 'manufacturer',  'vars_hint' => $this->language->get('text_meta_vars_manufacturer')],
            ['key' => 'information',   'vars_hint' => $this->language->get('text_meta_vars_information')],
            ['key' => 'article',       'vars_hint' => $this->language->get('text_meta_vars_article')],
            ['key' => 'blog_category', 'vars_hint' => $this->language->get('text_meta_vars_blog_category')],
        ];

        // Product image alt/title masks — per language pair
        $data['image_masks'] = [];
        foreach ($data['languages'] as $lang) {
            $lcode = explode('-', strtolower($lang['code']))[0];
            $data['image_masks']['alt_'   . $lcode] = (string)$this->config->get('module_oc_kit_seo_core_image_alt_tpl_'   . $lcode);
            $data['image_masks']['title_' . $lcode] = (string)$this->config->get('module_oc_kit_seo_core_image_title_tpl_' . $lcode);
        }

        // Dashboard
        $data['dashboard_stats_url'] = html_entity_decode($this->url->link(self::ROUTE . '/dashboardStats', $ut, true));
        $data['flatten_chains_url']  = html_entity_decode($this->url->link(self::ROUTE . '/flattenChains',  $ut, true));

        // SEO URLs
        $data['urls_list_url']  = html_entity_decode($this->url->link(self::ROUTE . '/urlsList',  $ut, true));
        $data['url_save_url']             = html_entity_decode($this->url->link(self::ROUTE . '/urlSave',            $ut, true));
        $data['url_delete_url']           = html_entity_decode($this->url->link(self::ROUTE . '/urlDelete',          $ut, true));
        $data['url_history_list_url']     = html_entity_decode($this->url->link(self::ROUTE . '/urlHistoryList',     $ut, true));
        $data['url_history_rollback_url'] = html_entity_decode($this->url->link(self::ROUTE . '/urlHistoryRollback', $ut, true));
        $data['url_block_robots_url']     = html_entity_decode($this->url->link(self::ROUTE . '/urlBlockInRobots',  $ut, true));
        $data['url_history_widget_url']   = html_entity_decode($this->url->link(self::ROUTE . '/urlHistoryWidget',  $ut, true));
        $data['url_bulk_replace_url']     = html_entity_decode($this->url->link(self::ROUTE . '/urlBulkReplace',    $ut, true));

        // Redirects
        $data['redirects_list_url']  = html_entity_decode($this->url->link(self::ROUTE . '/redirectsList',  $ut, true));
        $data['redirect_save_url']   = html_entity_decode($this->url->link(self::ROUTE . '/redirectSave',   $ut, true));
        $data['redirect_delete_url'] = html_entity_decode($this->url->link(self::ROUTE . '/redirectDelete', $ut, true));
        $data['redirect_import_url'] = html_entity_decode($this->url->link(self::ROUTE . '/redirectImport', $ut, true));

        // Meta
        $data['meta_list_url']       = html_entity_decode($this->url->link(self::ROUTE . '/metaList',       $ut, true));
        $data['meta_save_url']       = html_entity_decode($this->url->link(self::ROUTE . '/metaSave',       $ut, true));
        $data['meta_delete_url']     = html_entity_decode($this->url->link(self::ROUTE . '/metaDelete',     $ut, true));
        $data['bulk_fill_url']       = html_entity_decode($this->url->link(self::ROUTE . '/bulkFillMeta',   $ut, true));
        $data['bulk_candidates_url'] = html_entity_decode($this->url->link(self::ROUTE . '/bulkCandidates', $ut, true));
        $data['ajax_categories']     = html_entity_decode($this->url->link(self::ROUTE . '/ajaxCategories', $ut, true));
        $data['ajax_entities_url']   = html_entity_decode($this->url->link(self::ROUTE . '/ajaxEntities',   $ut, true));
        $data['default_language_id'] = (int)$this->config->get('config_language_id');
        $data['meta_tpl_mode']       = (string)($this->config->get('module_oc_kit_seo_core_meta_tpl_mode') ?: 'override');

        // Audit
        $data['audit_run_url']     = html_entity_decode($this->url->link(self::ROUTE . '/auditRun',     $ut, true));
        $data['audit_results_url'] = html_entity_decode($this->url->link(self::ROUTE . '/auditResults', $ut, true));
        $data['audit_delete_url']  = html_entity_decode($this->url->link(self::ROUTE . '/auditDelete',  $ut, true));
        $data['audit_status_url']  = html_entity_decode($this->url->link(self::ROUTE . '/auditStatus',  $ut, true));
        $data['audit_crawl_url']        = html_entity_decode($this->url->link(self::ROUTE . '/auditRunCrawl',     $ut, true));
        $data['audit_export_url']       = html_entity_decode($this->url->link(self::ROUTE . '/auditExportCsv',   $ut, true));
        $data['image_alt_bulk_fill_url']= html_entity_decode($this->url->link(self::ROUTE . '/imageAltBulkFill', $ut, true));
        $data['broken_links_scan_url']  = html_entity_decode($this->url->link(self::ROUTE . '/brokenLinksScan',  $ut, true));
        $data['broken_links_list_url']  = html_entity_decode($this->url->link(self::ROUTE . '/brokenLinksList',  $ut, true));

        // Route-level meta
        $data['route_meta_list_url']    = html_entity_decode($this->url->link(self::ROUTE . '/routeMetaList',    $ut, true));
        $data['route_meta_save_url']    = html_entity_decode($this->url->link(self::ROUTE . '/routeMetaSave',    $ut, true));
        $data['route_meta_delete_url']  = html_entity_decode($this->url->link(self::ROUTE . '/routeMetaDelete',  $ut, true));

        // A/B title tests
        $data['ab_test_list_url']   = html_entity_decode($this->url->link(self::ROUTE . '/abTestList',   $ut, true));
        $data['ab_test_save_url']   = html_entity_decode($this->url->link(self::ROUTE . '/abTestSave',   $ut, true));
        $data['ab_test_end_url']    = html_entity_decode($this->url->link(self::ROUTE . '/abTestEnd',    $ut, true));
        $data['ab_test_delete_url'] = html_entity_decode($this->url->link(self::ROUTE . '/abTestDelete', $ut, true));

        // Google Search Console
        $data['gsc_status_url']         = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleStatus',         $ut, true));
        $data['gsc_connect_url']        = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleConnect',        $ut, true));
        $data['gsc_disconnect_url']     = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleDisconnect',     $ut, true));
        $data['gsc_stats_url']          = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleStats',          $ut, true));
        $data['gsc_inspect_url']        = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleInspect',        $ut, true));
        $data['gsc_sitemap_list_url']   = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleSitemapList',    $ut, true));
        $data['gsc_sitemap_submit_url'] = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleSitemapSubmit',  $ut, true));
        $data['gsc_sitemap_delete_url'] = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleSitemapDelete',  $ut, true));
        $data['gsc_submit_url_url']     = html_entity_decode($this->url->link(self::ROUTE . '/searchConsoleSubmitUrl',      $ut, true));

        // Cache
        $data['warm_cache_url']    = html_entity_decode($this->url->link(self::ROUTE . '/warmCache',  $ut, true));
        $data['clear_cache_url']   = html_entity_decode($this->url->link(self::ROUTE . '/clearCache', $ut, true));
        $data['cache_stats_url']   = html_entity_decode($this->url->link(self::ROUTE . '/cacheStats', $ut, true));

        // Redirects utilities
        $data['redirects_export_url']       = html_entity_decode($this->url->link(self::ROUTE . '/redirectsExportCsv',    $ut, true));
        $data['redirects_delete_stale_url'] = html_entity_decode($this->url->link(self::ROUTE . '/redirectsDeleteStale',  $ut, true));

        // Robots validate
        $data['robots_validate_url'] = html_entity_decode($this->url->link(self::ROUTE . '/robotsValidate', $ut, true));

        $data['entity_edit_urls'] = [
            'product'      => html_entity_decode($this->url->link('catalog/product/edit',      $ut . '&product_id=__ID__',      true)),
            'category'     => html_entity_decode($this->url->link('catalog/category/edit',     $ut . '&category_id=__ID__',     true)),
            'manufacturer' => html_entity_decode($this->url->link('catalog/manufacturer/edit', $ut . '&manufacturer_id=__ID__', true)),
            'information'  => html_entity_decode($this->url->link('catalog/information/edit',  $ut . '&information_id=__ID__',  true)),
        ];

        // Headers
        $data['headers_list_url']  = html_entity_decode($this->url->link(self::ROUTE . '/headersList',  $ut, true));
        $data['header_save_url']   = html_entity_decode($this->url->link(self::ROUTE . '/headerSave',   $ut, true));
        $data['header_delete_url'] = html_entity_decode($this->url->link(self::ROUTE . '/headerDelete', $ut, true));
        $data['header_test_url']   = html_entity_decode($this->url->link(self::ROUTE . '/headerTest',   $ut, true));

        // Robots
        $robotsEditor = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);
        $data['robots_content']     = $robotsEditor->read();
        $data['robots_path']        = '/robots.txt';
        $data['robots_backups']     = $robotsEditor->getBackups();
        $data['robots_save_url']    = html_entity_decode($this->url->link(self::ROUTE . '/robotsSave',    $ut, true));
        $data['robots_restore_url'] = html_entity_decode($this->url->link(self::ROUTE . '/robotsRestore', $ut, true));
        $data['robots_diff_url']    = html_entity_decode($this->url->link(self::ROUTE . '/robotsDiff',    $ut, true));

        // Sitemap
        $sitemap = new \OcKit\SeoCore\Libs\SitemapIntegration($this->registry);
        $data['sitemap_status']       = $sitemap->getStatus();
        $data['sitemap_status_url']   = html_entity_decode($this->url->link(self::ROUTE . '/sitemapStatus',   $ut, true));
        $data['sitemap_generate_url'] = html_entity_decode($this->url->link(self::ROUTE . '/sitemapGenerate', $ut, true));
        $data['sitemap_ping_url']     = html_entity_decode($this->url->link(self::ROUTE . '/sitemapPing',     $ut, true));
        $data['sitemap_settings_url'] = html_entity_decode($this->url->link('extension/module/oc_kit_sitemap_generator', $ut, true));

        // AbsURL
        $data['absurl_scan_url']    = html_entity_decode($this->url->link(self::ROUTE . '/absurlScan',    $ut, true));
        $data['absurl_replace_url'] = html_entity_decode($this->url->link(self::ROUTE . '/absurlReplace', $ut, true));
        $data['absurl_log_url']     = html_entity_decode($this->url->link(self::ROUTE . '/absurlLog',     $ut, true));

        // Canonical
        $data['canonical_settings'] = [
            'canonical_pagination'   => (string)($this->config->get('module_oc_kit_seo_core_canonical_pagination')   ?: 'first'),
            'canonical_filters'      => (string)($this->config->get('module_oc_kit_seo_core_canonical_filters')      ?: 'base'),
            'canonical_cross_domain' => (string)($this->config->get('module_oc_kit_seo_core_canonical_cross_domain') ?: ''),
        ];
        $data['canonical_settings_url']  = html_entity_decode($this->url->link(self::ROUTE . '/saveCanonicalSettings',   $ut, true));
        $data['canonical_list_url']      = html_entity_decode($this->url->link(self::ROUTE . '/canonicalOverridesList',  $ut, true));
        $data['canonical_save_url']      = html_entity_decode($this->url->link(self::ROUTE . '/canonicalOverrideSave',   $ut, true));
        $data['canonical_delete_url']    = html_entity_decode($this->url->link(self::ROUTE . '/canonicalOverrideDelete', $ut, true));
        $data['canonical_test_url']      = html_entity_decode($this->url->link(self::ROUTE . '/canonicalTest',           $ut, true));

        // Hreflang
        $data['hreflang_settings'] = [
            'enabled' => (int)(bool)$this->config->get('module_oc_kit_seo_core_hreflang_enabled'),
            'format'  => (string)($this->config->get('module_oc_kit_seo_core_hreflang_format') ?: 'iso'),
        ];
        $data['hreflang_save_url'] = html_entity_decode($this->url->link(self::ROUTE . '/saveHreflangSettings', $ut, true));

        // Home page meta (per language)
        $data['home_meta'] = [];
        foreach ($data['languages'] as $lang) {
            $lcode = explode('-', strtolower($lang['code']))[0];
            foreach (['title', 'desc', 'keywords'] as $f) {
                $data['home_meta'][$f . '_' . $lcode] = (string)$this->config->get("module_oc_kit_seo_core_home_{$f}_{$lcode}");
            }
        }

        // Open Graph
        $data['og_settings'] = [
            'enabled'        => (int)(bool)$this->config->get('module_oc_kit_seo_core_og_enabled'),
            'twitter_card'   => (int)(bool)$this->config->get('module_oc_kit_seo_core_og_twitter_card'),
            'twitter_handle' => (string)$this->config->get('module_oc_kit_seo_core_og_twitter_handle'),
            'image_fallback' => (string)$this->config->get('module_oc_kit_seo_core_og_image_fallback'),
        ];
        $data['og_templates'] = [];
        foreach (['product','category','manufacturer','information'] as $type) {
            foreach ($data['languages'] as $lang) {
                $lcode = explode('-', strtolower($lang['code']))[0];
                $data['og_templates'][$type][$lcode] = [
                    'lang_name' => $lang['name'],
                    'title_tpl' => (string)$this->config->get("module_oc_kit_seo_core_og_title_tpl_{$type}_{$lcode}"),
                    'desc_tpl'  => (string)$this->config->get("module_oc_kit_seo_core_og_desc_tpl_{$type}_{$lcode}"),
                ];
            }
        }
        $data['og_save_url'] = html_entity_decode($this->url->link(self::ROUTE . '/saveOpenGraphSettings', $ut, true));

        // Schema.org
        $data['schema_settings'] = [
            'product'      => (int)(bool)$this->config->get('module_oc_kit_seo_core_schema_product'),
            'breadcrumb'   => (int)(bool)$this->config->get('module_oc_kit_seo_core_schema_breadcrumb'),
            'organization' => (int)(bool)$this->config->get('module_oc_kit_seo_core_schema_organization'),
            'website'      => (int)(bool)$this->config->get('module_oc_kit_seo_core_schema_website'),
            'article'      => (int)(bool)$this->config->get('module_oc_kit_seo_core_schema_article'),
            'min_reviews'  => (int)($this->config->get('module_oc_kit_seo_core_schema_min_reviews') ?: 1),
            'org_name'     => (string)$this->config->get('module_oc_kit_seo_core_schema_org_name'),
            'org_logo'     => (string)$this->config->get('module_oc_kit_seo_core_schema_org_logo'),
            'org_phone'    => (string)$this->config->get('module_oc_kit_seo_core_schema_org_phone'),
            'org_email'    => (string)$this->config->get('module_oc_kit_seo_core_schema_org_email'),
            'org_type'         => (string)($this->config->get('module_oc_kit_seo_core_schema_org_type') ?: 'Organization'),
            'org_street'       => (string)$this->config->get('module_oc_kit_seo_core_schema_org_street'),
            'org_locality'     => (string)$this->config->get('module_oc_kit_seo_core_schema_org_locality'),
            'org_region'       => (string)$this->config->get('module_oc_kit_seo_core_schema_org_region'),
            'org_postal_code'  => (string)$this->config->get('module_oc_kit_seo_core_schema_org_postal_code'),
            'org_country'      => (string)$this->config->get('module_oc_kit_seo_core_schema_org_country'),
            'org_geo_lat'      => (string)$this->config->get('module_oc_kit_seo_core_schema_org_geo_lat'),
            'org_geo_lon'      => (string)$this->config->get('module_oc_kit_seo_core_schema_org_geo_lon'),
            'org_price_range'  => (string)$this->config->get('module_oc_kit_seo_core_schema_org_price_range'),
            'org_vat_id'       => (string)$this->config->get('module_oc_kit_seo_core_schema_org_vat_id'),
            'org_founding_date'=> (string)$this->config->get('module_oc_kit_seo_core_schema_org_founding_date'),
            'org_opening_hours'=> (string)$this->config->get('module_oc_kit_seo_core_schema_org_opening_hours'),
            'org_same_as'      => (string)$this->config->get('module_oc_kit_seo_core_schema_org_same_as'),
            'org_founders'     => (string)$this->config->get('module_oc_kit_seo_core_schema_org_founders'),
            'org_contact_languages' => (string)$this->config->get('module_oc_kit_seo_core_schema_org_contact_languages'),
        ];
        $data['schema_settings_url']  = html_entity_decode($this->url->link(self::ROUTE . '/saveSchemaSettings',    $ut, true));
        $data['schema_list_url']      = html_entity_decode($this->url->link(self::ROUTE . '/schemaRulesList',       $ut, true));
        $data['schema_save_url']      = html_entity_decode($this->url->link(self::ROUTE . '/schemaRuleSave',        $ut, true));
        $data['schema_delete_url']    = html_entity_decode($this->url->link(self::ROUTE . '/schemaRuleDelete',      $ut, true));
        $data['schema_validate_url']  = html_entity_decode($this->url->link(self::ROUTE . '/schemaTemplateValidate',$ut, true));

        $data['license_info']  = $this->getLicenseInfo();
        $data['license_key']   = (string)$this->config->get('module_oc_kit_seo_core_license_key');
        $data['activate_url']  = html_entity_decode($this->url->link(self::ROUTE . '/activateLicense', $ut, true));

        $data['supports_h1'] = $supportsH1;

        // Auto-expose every loaded language string to the view, so templates
        // can use {{ key }} directly without per-key $data assignments. Keeps
        // |default(...) fallbacks unnecessary as long as the key exists in
        // the lang file. Explicit $data[key] above still wins (isset guard).
        foreach ($this->language->all() as $lk => $lv) {
            if (!isset($data[$lk])) $data[$lk] = $lv;
        }

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/seo_core/settings', $data));
    }

    public function save(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $storeId = (int)($this->request->post['store_id'] ?? 0);

        $fields = [
            'module_oc_kit_seo_core_status',
            'module_oc_kit_seo_core_url_depth',
            'module_oc_kit_seo_core_product_include_category',
            'module_oc_kit_seo_core_trailing_slash',
            'module_oc_kit_seo_core_lang_prefixes',
            'module_oc_kit_seo_core_custom_routes',
            'module_oc_kit_seo_core_pagination_mode',
            'module_oc_kit_seo_core_noindex_all_pagination',
            'module_oc_kit_seo_core_noindex_from_page',
            'module_oc_kit_seo_core_noindex_delivery',
            'module_oc_kit_seo_core_mask_product',
            'module_oc_kit_seo_core_mask_category',
            'module_oc_kit_seo_core_mask_manufacturer',
            'module_oc_kit_seo_core_mask_information',
            'module_oc_kit_seo_core_auto_generate_url',
            'module_oc_kit_seo_core_meta_tpl_mode',
            'module_oc_kit_seo_core_hreflang_enabled',
            'module_oc_kit_seo_core_hreflang_format',
            'module_oc_kit_seo_core_home_redirect_index',
            'module_oc_kit_seo_core_allow_duplicate_keywords',
            'module_oc_kit_seo_core_webhook_url',
            'module_oc_kit_seo_core_webhook_secret',
            'module_oc_kit_seo_core_resource_hints',
            'module_oc_kit_seo_core_schema_providers',
            'module_oc_kit_seo_core_og_enabled',
            'module_oc_kit_seo_core_og_twitter_card',
            'module_oc_kit_seo_core_og_twitter_handle',
            'module_oc_kit_seo_core_og_image_fallback',
            'module_oc_kit_seo_core_schema_product',
            'module_oc_kit_seo_core_schema_breadcrumb',
            'module_oc_kit_seo_core_schema_organization',
            'module_oc_kit_seo_core_schema_website',
            'module_oc_kit_seo_core_schema_article',
            'module_oc_kit_seo_core_schema_min_reviews',
            'module_oc_kit_seo_core_schema_org_name',
            'module_oc_kit_seo_core_schema_org_logo',
            'module_oc_kit_seo_core_schema_org_phone',
            'module_oc_kit_seo_core_schema_org_email',
            'module_oc_kit_seo_core_schema_org_type',
            'module_oc_kit_seo_core_schema_org_street',
            'module_oc_kit_seo_core_schema_org_locality',
            'module_oc_kit_seo_core_schema_org_region',
            'module_oc_kit_seo_core_schema_org_postal_code',
            'module_oc_kit_seo_core_schema_org_country',
            'module_oc_kit_seo_core_schema_org_geo_lat',
            'module_oc_kit_seo_core_schema_org_geo_lon',
            'module_oc_kit_seo_core_schema_org_price_range',
            'module_oc_kit_seo_core_schema_org_vat_id',
            'module_oc_kit_seo_core_schema_org_founding_date',
            'module_oc_kit_seo_core_schema_org_opening_hours',
            'module_oc_kit_seo_core_schema_org_same_as',
            'module_oc_kit_seo_core_schema_org_founders',
            'module_oc_kit_seo_core_schema_org_contact_languages',
            'module_oc_kit_seo_core_ab_test_enabled',
            'module_oc_kit_seo_core_strip_query_params',
            'module_oc_kit_seo_core_gsc_client_id',
            'module_oc_kit_seo_core_gsc_client_secret',
            'module_oc_kit_seo_core_gsc_site_property',
        ];

        // JSON fields — OC's Request::clean() HTML-encodes POST values, so decode first
        $jsonFields = [
            'module_oc_kit_seo_core_lang_prefixes',
            'module_oc_kit_seo_core_custom_routes',
            'module_oc_kit_seo_core_resource_hints',
            'module_oc_kit_seo_core_schema_providers',
        ];

        // Boolean toggles — browsers don't POST unchecked checkboxes, so we
        // must write '0' explicitly. Otherwise an unchecked toggle saves as ''
        // and the UI/runtime keep treating it as enabled (≠ '0').
        $boolToggles = [
            'module_oc_kit_seo_core_status',
            'module_oc_kit_seo_core_product_include_category',
            'module_oc_kit_seo_core_noindex_all_pagination',
            'module_oc_kit_seo_core_noindex_delivery',
            'module_oc_kit_seo_core_hreflang_enabled',
            'module_oc_kit_seo_core_home_redirect_index',
            'module_oc_kit_seo_core_allow_duplicate_keywords',
            'module_oc_kit_seo_core_og_enabled',
            'module_oc_kit_seo_core_schema_product',
            'module_oc_kit_seo_core_schema_breadcrumb',
            'module_oc_kit_seo_core_schema_organization',
            'module_oc_kit_seo_core_schema_website',
            'module_oc_kit_seo_core_schema_article',
            'module_oc_kit_seo_core_ab_test_enabled',
        ];

        $settings = [];
        foreach ($fields as $key) {
            $val = $this->request->post[$key] ?? null;
            if ($val === null) {
                $val = in_array($key, $boolToggles, true) ? '0' : '';
            }
            if (in_array($key, $jsonFields, true) && is_string($val) && $val !== '') {
                $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
            }
            $settings[$key] = $val;
        }

        // Dynamic template keys (Meta + Open Graph + Home page: per type/lang).
        // H1-template keys are accepted only when the store has native meta_h1.
        $supportsH1 = \OcKit\SeoCore\SeoCore::supportsNativeH1($this->db);
        $metaTplPattern = $supportsH1
            ? '/^module_oc_kit_seo_core_meta_(product|category|manufacturer|information|article|blog_category)_(title|desc|h1)_tpl_[a-z]{2,5}$/'
            : '/^module_oc_kit_seo_core_meta_(product|category|manufacturer|information|article|blog_category)_(title|desc)_tpl_[a-z]{2,5}$/';

        foreach ($this->request->post as $key => $val) {
            if (preg_match($metaTplPattern, $key)
             || preg_match('/^module_oc_kit_seo_core_og_(title|desc)_tpl_(product|category|manufacturer|information)_[a-z]{2,5}$/', $key)
             || preg_match('/^module_oc_kit_seo_core_home_(title|desc|keywords)_[a-z]{2,5}$/', $key)
             || preg_match('/^module_oc_kit_seo_core_image_(alt|title)_tpl_[a-z]{2,5}$/', $key)) {
                $settings[$key] = (string)$val;
            }
        }

        // Preserve settings that are NOT part of the settings form (license, etc.)
        $preserve = [
            'module_oc_kit_seo_core_license_key',
            'module_oc_kit_seo_core_license_data',
            'module_oc_kit_seo_core_gsc_refresh_token',
            'module_oc_kit_seo_core_gsc_access_token',
            'module_oc_kit_seo_core_gsc_token_expires',
        ];
        foreach ($preserve as $pk) {
            $val = $this->config->get($pk);
            if ($val !== null && $val !== '') {
                $settings[$pk] = $val;
            }
        }

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_oc_kit_seo_core', $settings, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'  => true,
            'message'  => $this->language->get('text_success'),
        ]));
    }

    // ─── URLs tab ─────────────────────────────────────────────────────────────

    public function urlsList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        // ids_only=1 — return list of entity IDs of given type for bulk generation.
        // (Distinct from listing oc_seo_url rows — this enumerates source entities.)
        if (!empty($this->request->get['ids_only'])) {
            $this->urlsListIdsForType();
            return;
        }

        $storeId    = (int)($this->request->get['store_id']    ?? 0);
        $languageId = (int)($this->request->get['language_id'] ?? 0);
        $page       = max(1, (int)($this->request->get['page'] ?? 1));
        $limit      = (int)($this->request->get['limit']       ?? 50);
        $keyword    = trim((string)($this->request->get['keyword'] ?? ''));
        $query      = trim((string)($this->request->get['query']   ?? ''));
        $offset     = ($page - 1) * $limit;

        $where = "WHERE u.`store_id` = " . $storeId;
        if ($languageId) $where .= " AND u.`language_id` = " . $languageId;
        if ($keyword !== '') {
            $where .= " AND u.`keyword` LIKE '%" . $this->db->escape($keyword) . "%'";
        }
        if ($query !== '') {
            $where .= " AND u.`query` LIKE '%" . $this->db->escape($query) . "%'";
        }

        $total = (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url` u " . $where)->row['cnt'];
        $rows  = $this->db->query(
            "SELECT u.`seo_url_id`, u.`keyword`, u.`query`, u.`store_id`, u.`language_id`,
                    COALESCE(h.cnt, 0) AS history_count,
                    h.last_old, h.last_new, h.last_changed
             FROM `" . DB_PREFIX . "seo_url` u
             LEFT JOIN (
                SELECT `seo_url_id`, COUNT(*) AS cnt,
                       SUBSTRING_INDEX(GROUP_CONCAT(`old_keyword` ORDER BY `history_id` DESC), ',', 1) AS last_old,
                       SUBSTRING_INDEX(GROUP_CONCAT(`new_keyword` ORDER BY `history_id` DESC), ',', 1) AS last_new,
                       MAX(`changed_at`) AS last_changed
                FROM `" . DB_PREFIX . "kit_seo_url_history`
                GROUP BY `seo_url_id`
             ) h ON h.seo_url_id = u.seo_url_id
             " . $where . "
             ORDER BY u.`seo_url_id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        )->rows;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $rows, 'total' => $total]));
    }

    /**
     * Return the full list of entity IDs of a given type for bulk URL generation.
     * Used by `urlsList?ids_only=1&entity_type=...&language_id=...`.
     */
    private function urlsListIdsForType(): void
    {
        $type       = (string)($this->request->get['entity_type'] ?? '');
        $languageId = (int)($this->request->get['language_id'] ?? 0);

        $sql = '';
        switch ($type) {
            case 'product':
                $sql = "SELECT `product_id` AS id FROM `" . DB_PREFIX . "product` WHERE `status` = 1 ORDER BY `product_id`";
                break;
            case 'category':
                $sql = "SELECT `category_id` AS id FROM `" . DB_PREFIX . "category` WHERE `status` = 1 ORDER BY `category_id`";
                break;
            case 'manufacturer':
                $sql = "SELECT `manufacturer_id` AS id FROM `" . DB_PREFIX . "manufacturer` ORDER BY `manufacturer_id`";
                break;
            case 'information':
                $sql = "SELECT `information_id` AS id FROM `" . DB_PREFIX . "information` WHERE `status` = 1 ORDER BY `information_id`";
                break;
        }

        $ids = [];
        if ($sql !== '') {
            foreach ($this->db->query($sql)->rows as $r) {
                $ids[] = (int)$r['id'];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['entity_ids' => $ids, 'count' => count($ids)]));
    }

    public function urlSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $id         = (int)($this->request->post['seo_url_id'] ?? 0);
        $keyword    = trim((string)($this->request->post['keyword']     ?? ''));
        $query      = trim((string)($this->request->post['query']       ?? ''));
        $languageId = (int)($this->request->post['language_id']         ?? 0);
        $storeId    = (int)($this->request->post['store_id']            ?? 0);

        if (!$keyword || !$query) {
            $this->jsonError('keyword and query are required');
            return;
        }

        $keyword = $this->db->escape($keyword);
        $query   = $this->db->escape($query);

        if ($id > 0) {
            // Capture history of keyword changes (Phase 2: URL history + rollback)
            $oldRow = $this->db->query(
                "SELECT `keyword`, `query` FROM `" . DB_PREFIX . "seo_url`
                 WHERE `seo_url_id` = " . $id . " LIMIT 1"
            )->row;
            $oldKw  = (string)($oldRow['keyword'] ?? '');
            $newKw  = trim((string)($this->request->post['keyword'] ?? ''));
            if ($oldKw !== '' && $oldKw !== $newKw) {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "kit_seo_url_history`
                     (`seo_url_id`,`store_id`,`language_id`,`query`,`old_keyword`,`new_keyword`)
                     VALUES (" . $id . ", " . $storeId . ", " . $languageId . ",
                             '" . $this->db->escape((string)$oldRow['query']) . "',
                             '" . $this->db->escape($oldKw) . "',
                             '" . $keyword . "')"
                );

                // Auto-capture 301 so the old slug keeps working
                $this->getRedirectManager()->autoCapture('/' . $oldKw, '/' . $newKw, $storeId);

                // Fire webhook (CDN purge / Slack / etc) — fire-and-forget
                (new \OcKit\SeoCore\Libs\WebhookDispatcher($this->config))
                    ->dispatch(\OcKit\SeoCore\Libs\WebhookDispatcher::EV_URL_CHANGED, [
                        'store_id'    => $storeId,
                        'language_id' => $languageId,
                        'query'       => (string)$oldRow['query'],
                        'old_keyword' => $oldKw,
                        'new_keyword' => $newKw,
                    ]);
            }

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "seo_url`
                 SET `keyword` = '" . $keyword . "', `query` = '" . $query . "',
                     `language_id` = " . $languageId . ", `store_id` = " . $storeId . "
                 WHERE `seo_url_id` = " . $id
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "seo_url` (`store_id`, `language_id`, `query`, `keyword`)
                 VALUES (" . $storeId . ", " . $languageId . ", '" . $query . "', '" . $keyword . "')"
            );
            $id = (int)$this->db->getLastId();
        }

        // Invalidate CacheWarmer
        $cw = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $cw->invalidate();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'seo_url_id' => $id]));
    }

    /**
     * Phase 2: list URL keyword change history (newest-first).
     * GET ?seo_url_id=N (optional) | ?query=X (optional) | ?limit=50
     */
    public function urlHistoryList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $seoUrlId = (int)($this->request->get['seo_url_id'] ?? 0);
        $query    = (string)($this->request->get['query'] ?? '');
        $limit    = max(1, min(200, (int)($this->request->get['limit'] ?? 50)));

        $where = '1=1';
        if ($seoUrlId > 0) $where .= " AND `seo_url_id` = " . $seoUrlId;
        if ($query !== '') $where .= " AND `query` = '" . $this->db->escape($query) . "'";

        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_url_history`
             WHERE {$where}
             ORDER BY `history_id` DESC
             LIMIT " . $limit
        )->rows;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $rows]));
    }

    /**
     * Phase 2: revert to a previous keyword from history.
     * POST history_id=N
     */
    public function urlHistoryRollback(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $historyId = (int)($this->request->post['history_id'] ?? 0);
        if (!$historyId) { $this->jsonError('history_id required'); return; }

        $h = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_url_history`
             WHERE `history_id` = " . $historyId . " LIMIT 1"
        )->row;
        if (!$h) { $this->jsonError('history entry not found'); return; }

        // Re-insert via urlSave-style — captures another history entry for the rollback itself
        $cur = $this->db->query(
            "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
             WHERE `seo_url_id` = " . (int)$h['seo_url_id'] . " LIMIT 1"
        )->row;
        $curKw = (string)($cur['keyword'] ?? '');

        if ($curKw !== '' && $curKw !== $h['old_keyword']) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_seo_url_history`
                 (`seo_url_id`,`store_id`,`language_id`,`query`,`old_keyword`,`new_keyword`)
                 VALUES (" . (int)$h['seo_url_id'] . ", " . (int)$h['store_id'] . ", " . (int)$h['language_id'] . ",
                         '" . $this->db->escape($h['query']) . "',
                         '" . $this->db->escape($curKw) . "',
                         '" . $this->db->escape($h['old_keyword']) . "')"
            );
        }

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "seo_url`
             SET `keyword` = '" . $this->db->escape($h['old_keyword']) . "'
             WHERE `seo_url_id` = " . (int)$h['seo_url_id']
        );

        // Invalidate cache
        $cw = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $cw->invalidate();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'restored_keyword' => $h['old_keyword'],
        ]));
    }

    public function urlDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $id = (int)($this->request->post['seo_url_id'] ?? 0);
        if ($id > 0) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `seo_url_id` = " . $id);
            $cw = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
            $cw->invalidate();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboardStats(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->get['store_id'] ?? 0);

        $seoUrlCount  = (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = " . $storeId)->row['cnt'];
        $redirectCount= (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE `store_id` = " . $storeId)->row['cnt'];
        $hitsTotal    = (int)$this->db->query("SELECT COALESCE(SUM(`hits`),0) AS cnt FROM `" . DB_PREFIX . "kit_seo_redirects` WHERE `store_id` = " . $storeId)->row['cnt'];

        $topRows = $this->db->query(
            "SELECT `redirect_id`, `from_url`, `to_url`, `code`, `hits`
             FROM `" . DB_PREFIX . "kit_seo_redirects`
             WHERE `store_id` = " . $storeId . " AND `hits` > 0
             ORDER BY `hits` DESC LIMIT 10"
        )->rows;

        $mgr      = $this->getRedirectManager();
        $detector = $this->getChainDetector($mgr);
        $allData  = $mgr->getList($storeId, 1, 100000);
        $map      = [];
        foreach ($allData['items'] as $dto) {
            $map[$dto->fromUrl] = $dto->toUrl;
        }
        $chains = $detector->detectChains($map);

        // Audit summary (last run, any language/store)
        $auditCounts = $this->db->query(
            "SELECT `severity`, COUNT(*) AS cnt
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = " . $storeId . "
             GROUP BY `severity`"
        )->rows;
        $auditSummary = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($auditCounts as $ac) {
            $auditSummary[$ac['severity']] = (int)$ac['cnt'];
        }

        // Pull ~60 worst rows — JS groups them by entity and keeps top 10 groups.
        $auditTop = $this->db->query(
            "SELECT `entity_type`, `entity_id`, `entity_name`, `issue_type`, `severity`, `detail`
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = " . $storeId . "
             ORDER BY FIELD(`severity`,'error','warning','info'), `result_id` ASC
             LIMIT 60"
        )->rows;

        $auditLastRun = $this->db->query(
            "SELECT MAX(`created_at`) AS dt FROM `" . DB_PREFIX . "kit_seo_audit_results` WHERE `store_id` = " . $storeId
        )->row['dt'] ?? null;

        // Aggregate SEO score (0–100) — null if no audit ran yet
        $audit = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        $seoScore = $audit->getOverallScore((int)$this->config->get('config_language_id'), $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'seo_urls'       => $seoUrlCount,
            'redirects'      => $redirectCount,
            'redirect_hits'  => $hitsTotal,
            'chains'         => count($chains),
            'chain_details'  => $chains,
            'top_redirects'  => $topRows,
            'audit_errors'   => $auditSummary['error'],
            'audit_warnings' => $auditSummary['warning'],
            'audit_top'      => $auditTop,
            'audit_last_run' => $auditLastRun,
            'seo_score'      => $seoScore,
        ]));
    }

    public function flattenChains(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId  = (int)($this->request->post['store_id'] ?? 0);
        $mgr      = $this->getRedirectManager();
        $detector = $this->getChainDetector($mgr);
        $allData  = $mgr->getList($storeId, 1, 100000);
        $map      = [];
        foreach ($allData['items'] as $dto) {
            $map[$dto->fromUrl] = $dto->toUrl;
        }

        $flattened = $detector->flattenChains($map);
        $updated   = 0;

        foreach ($flattened as $from => $to) {
            if ($map[$from] !== $to) {
                $existing = $allData['items'];
                foreach ($existing as $dto) {
                    if ($dto->fromUrl === $from) {
                        $mgr->save($storeId, $from, $to, $dto->code, $dto->redirectId);
                        $updated++;
                        break;
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'updated' => $updated]));
    }

    // ─── Redirects tab ────────────────────────────────────────────────────────

    public function redirectsList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $page    = max(1, (int)($this->request->get['page']   ?? 1));
        $limit   = (int)($this->request->get['limit']  ?? 50);
        $search  = (string)($this->request->get['search'] ?? '');

        $mgr    = $this->getRedirectManager();
        $result = $mgr->getList($storeId, $page, $limit, $search);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'items' => array_map(function ($dto) {
                return [
                    'redirect_id' => $dto->redirectId,
                    'from_url'    => $dto->fromUrl,
                    'to_url'      => $dto->toUrl,
                    'code'        => $dto->code,
                    'hits'        => $dto->hits,
                    'created_at'  => $dto->createdAt,
                    'expires_at'  => $dto->expiresAt,
                    'last_hit_at' => $dto->lastHitAt,
                ];
            }, $result['items']),
            'total' => $result['total'],
        ]));
    }

    public function redirectSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $from    = trim((string)($this->request->post['from_url'] ?? ''));
        $to      = trim((string)($this->request->post['to_url']   ?? ''));
        $code    = (int)($this->request->post['code']     ?? 301);
        $id      = (int)($this->request->post['redirect_id'] ?? 0);
        $expires = trim((string)($this->request->post['expires_at'] ?? ''));
        // Validate datetime string (Y-m-d H:i:s) or empty.
        if ($expires !== '' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $expires)) {
            $expires = '';
        }

        if (!in_array($code, [301, 302, 303, 307, 308, 410], true)) $code = 301;

        if (!$from || ($code !== 410 && !$to)) {
            $this->jsonError($this->language->get('error_redirect_fields'));
            return;
        }

        $mgr      = $this->getRedirectManager();
        $detector = $this->getChainDetector($mgr);

        $existing = [];
        $all = $mgr->getList($storeId, 1, 100000);
        foreach ($all['items'] as $dto) {
            $existing[$dto->fromUrl] = $dto->toUrl;
        }

        if ($detector->wouldCreateLoop($from, $to, $existing)) {
            $this->jsonError($this->language->get('error_redirect_loop'));
            return;
        }

        $newId = $mgr->save($storeId, $from, $to, $code, $id, $expires !== '' ? $expires : null);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'redirect_id' => $newId]));
    }

    public function redirectDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $id = (int)($this->request->post['redirect_id'] ?? 0);
        if ($id > 0) {
            $this->getRedirectManager()->delete($id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function redirectImport(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $content = (string)($this->request->post['csv']   ?? '');

        if (!$content) {
            $this->jsonError($this->language->get('error_import_empty'));
            return;
        }

        $mgr      = $this->getRedirectManager();
        $detector = $this->getChainDetector($mgr);
        $importer = new \OcKit\SeoCore\Libs\RedirectImporter($mgr, $detector);
        $result   = $importer->importCsv($content, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true] + $result));
    }

    // ─── Meta overrides tab ───────────────────────────────────────────────────

    public function metaList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $filters = [
            'store_id'    => (int)($this->request->get['store_id']    ?? 0),
            'language_id' => (int)($this->request->get['language_id'] ?? 0),
            'entity_type' => (string)($this->request->get['entity_type'] ?? ''),
            'search'      => (string)($this->request->get['search']   ?? ''),
        ];
        $page  = max(1, (int)($this->request->get['page']  ?? 1));
        $limit = (int)($this->request->get['limit'] ?? 50);

        $repo  = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
        $items = $repo->getAll($filters, $limit, ($page - 1) * $limit);
        $total = $repo->getTotalCount($filters);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $items, 'total' => $total]));
    }

    public function metaSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $repo = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
        $repo->saveOverride($this->request->post);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function metaDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $id = (int)($this->request->post['meta_id'] ?? 0);
        if ($id > 0) {
            (new \OcKit\SeoCore\Libs\MetaRepository($this->db))->deleteOverride($id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function ajaxCategories(): void
    {
        $this->load->model('catalog/category');
        $q = trim($this->request->get['q'] ?? '');
        $results = $this->model_catalog_category->getCategories(['filter_name' => $q, 'start' => 0, 'limit' => 20]);
        $out = [];
        foreach ($results as $r) {
            $name = html_entity_decode(html_entity_decode($r['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $out[] = ['id' => $r['category_id'], 'text' => $name];
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($out));
    }

    /**
     * Generic entity autocomplete for meta/canonical override forms.
     * ?type=product|category|manufacturer|information&q=foo
     */
    public function ajaxEntities(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $type = (string)($this->request->get['type'] ?? '');
        $q    = trim((string)($this->request->get['q'] ?? ''));
        $lang = (int)($this->request->get['language_id'] ?? $this->config->get('config_language_id'));

        $out = [];
        switch ($type) {
            case 'product':
                $this->load->model('catalog/product');
                $rows = $this->model_catalog_product->getProducts([
                    'filter_name' => $q, 'start' => 0, 'limit' => 20
                ]);
                foreach ($rows as $r) {
                    $out[] = ['id' => (int)$r['product_id'], 'text' => $r['name'] . ' (#' . $r['product_id'] . ')'];
                }
                break;

            case 'category':
                $this->load->model('catalog/category');
                $rows = $this->model_catalog_category->getCategories([
                    'filter_name' => $q, 'start' => 0, 'limit' => 20
                ]);
                foreach ($rows as $r) {
                    $name = html_entity_decode($r['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $out[] = ['id' => (int)$r['category_id'], 'text' => $name . ' (#' . $r['category_id'] . ')'];
                }
                break;

            case 'manufacturer':
                $this->load->model('catalog/manufacturer');
                $rows = $this->model_catalog_manufacturer->getManufacturers([
                    'filter_name' => $q, 'start' => 0, 'limit' => 20
                ]);
                foreach ($rows as $r) {
                    $out[] = ['id' => (int)$r['manufacturer_id'], 'text' => $r['name'] . ' (#' . $r['manufacturer_id'] . ')'];
                }
                break;

            case 'information':
                $qEsc = $this->db->escape($q);
                $rows = $this->db->query(
                    "SELECT i.`information_id`, id.`title`
                     FROM `" . DB_PREFIX . "information` i
                     LEFT JOIN `" . DB_PREFIX . "information_description` id
                       ON id.`information_id` = i.`information_id` AND id.`language_id` = {$lang}
                     WHERE id.`title` LIKE '%{$qEsc}%' AND i.`status` = 1
                     ORDER BY id.`title` LIMIT 20"
                )->rows;
                foreach ($rows as $r) {
                    $out[] = ['id' => (int)$r['information_id'], 'text' => $r['title'] . ' (#' . $r['information_id'] . ')'];
                }
                break;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($out));
    }

    public function bulkCandidates(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $type       = (string)($this->request->get['entity_type'] ?? 'product');
        $langId     = (int)($this->request->get['language_id']    ?? $this->config->get('config_language_id'));
        $mode       = (string)($this->request->get['mode']        ?? 'empty');
        $storeId    = (int)($this->request->get['store_id']       ?? 0);
        $categoryId = (int)($this->request->get['category_id']    ?? 0);

        $repo = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
        $items = $repo->getBulkCandidates($type, $langId, $mode, $storeId, $categoryId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $items, 'total' => count($items)]));
    }

    public function bulkFillMeta(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $entityIds = (array)($this->request->post['entity_ids'] ?? []);
        $type      = (string)($this->request->post['entity_type'] ?? 'product');
        $langId    = (int)($this->request->post['language_id']    ?? $this->config->get('config_language_id'));
        $mode      = (string)($this->request->post['mode']        ?? 'empty');
        $storeId   = (int)($this->request->post['store_id']       ?? 0);

        $repo    = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
        $engine  = new \OcKit\SeoCore\Libs\MetaTemplateEngine($repo, $this->db, $this->config);

        $filled  = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($entityIds as $entityId) {
            $entityId = (int)$entityId;
            if (!$entityId) continue;

            if ($mode === 'empty') {
                $existing = $repo->getOverride($type, $entityId, $langId, $storeId);
                if ($existing && !empty($existing['title'])) { $skipped++; continue; }
            }

            try {
                $meta = $engine->render($type, $entityId, $langId, $storeId);
                if (!$meta->title) { $errors[] = ['entity_id' => $entityId, 'message' => 'Empty title']; continue; }

                $row = [
                    'entity_type' => $type,
                    'entity_id'   => $entityId,
                    'language_id' => $langId,
                    'store_id'    => $storeId,
                    'title'       => $meta->title,
                    'description' => $meta->description,
                ];
                if (\OcKit\SeoCore\SeoCore::supportsNativeH1($this->db)) {
                    $row['h1'] = $meta->h1;
                }
                $repo->saveOverride($row);
                $filled++;
            } catch (\Throwable $e) {
                $errors[] = ['entity_id' => $entityId, 'message' => $e->getMessage()];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'filled' => $filled, 'skipped' => $skipped, 'errors' => $errors]));
    }

    // ─── Audit tab ────────────────────────────────────────────────────────────

    public function auditRun(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $langId  = (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id'));
        $storeId = (int)($this->request->post['store_id']    ?? 0);

        $audit  = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        $issues = $audit->runDbAudit($langId, $storeId);

        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($issues as $i) {
            $sev = $i['severity'] ?? 'info';
            if (isset($counts[$sev])) $counts[$sev]++;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'total'   => count($issues),
            'counts'  => $counts,
        ]));
    }

    public function auditResults(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $langId     = (int)($this->request->get['language_id'] ?? $this->config->get('config_language_id'));
        $storeId    = (int)($this->request->get['store_id']    ?? 0);
        $severity   = (string)($this->request->get['severity'] ?? '');
        $entityType = (string)($this->request->get['entity_type'] ?? '');
        $page       = (int)($this->request->get['page']     ?? 1);
        $perPage    = (int)($this->request->get['per_page'] ?? 50);

        $audit   = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        $data    = $audit->getGroupedResults($langId, $storeId, $severity, $entityType, $page, $perPage);
        $summary = $audit->getSummary($langId, $storeId);
        $lastRun = $audit->getLastRunDate($storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'groups'       => $data['groups'],
            'total_groups' => $data['total_groups'],
            'total_issues' => $data['total_issues'],
            'page'         => $data['page'],
            'per_page'     => $data['per_page'],
            'summary'      => $summary,
            'last_run'     => $lastRun,
        ]));
    }

    public function auditDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $ids = $this->request->post['ids'] ?? '';
        if (!$ids) { $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode(['success' => false])); return; }

        $safeIds = implode(',', array_map('intval', explode(',', $ids)));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_audit_results` WHERE `result_id` IN ({$safeIds})");

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'deleted' => $this->db->countAffected()]));
    }

    public function auditStatus(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $idsStr = (string)($this->request->post['ids']    ?? '');
        $status = (string)($this->request->post['status'] ?? '');
        $ids    = array_filter(array_map('intval', explode(',', $idsStr)));

        $audit   = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        $updated = $audit->updateStatus($ids, $status);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => $updated > 0, 'updated' => $updated]));
    }

    // ─── Absolute URLs tab ────────────────────────────────────────────────────

    public function absurlScan(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $domain  = trim((string)($this->request->post['domain']      ?? ''));
        $langId  = (int)($this->request->post['language_id']         ?? 0);

        if (!$domain) { $this->jsonError('Domain is required'); return; }

        $fixer   = new \OcKit\SeoCore\Libs\AbsoluteUrlFixer($this->db, $this->config);
        $results = $fixer->scan($domain, $langId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'items' => $results, 'total' => count($results)]));
    }

    public function absurlReplace(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $oldDomain  = trim((string)($this->request->post['old_domain']   ?? ''));
        $newDomain  = trim((string)($this->request->post['new_domain']   ?? ''));
        $entityType = (string)($this->request->post['entity_type']       ?? 'product');
        $entityIds  = (array)($this->request->post['entity_ids']         ?? []);
        $httpsOnly  = !empty($this->request->post['https_only']);

        if (!$oldDomain) { $this->jsonError('old_domain is required'); return; }

        $fixer   = new \OcKit\SeoCore\Libs\AbsoluteUrlFixer($this->db, $this->config);

        if ($httpsOnly) {
            // Domain comes from config (config_ssl/config_url) — security:
            // user-supplied $oldDomain is ignored for the HTTP→HTTPS path
            // to prevent arbitrary domain rewrites (TZ §10 + §23).
            $updated = $fixer->replaceHttpToHttps($entityType, $entityIds);
        } else {
            if (!$newDomain) { $this->jsonError('new_domain is required'); return; }
            $updated = $fixer->replace($oldDomain, $newDomain, $entityType, $entityIds);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'updated' => $updated]));
    }

    public function absurlLog(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $page   = max(1, (int)($this->request->get['page']  ?? 1));
        $limit  = (int)($this->request->get['limit'] ?? 50);
        $offset = ($page - 1) * $limit;

        $fixer = new \OcKit\SeoCore\Libs\AbsoluteUrlFixer($this->db);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'items' => $fixer->getLog($limit, $offset),
            'total' => $fixer->getLogTotal(),
        ]));
    }

    // ─── Header rules tab ─────────────────────────────────────────────────────

    public function headersList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $engine  = new \OcKit\SeoCore\Libs\HeaderRuleEngine($this->db, $this->config);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $engine->getAll($storeId)]));
    }

    public function headerSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $engine = new \OcKit\SeoCore\Libs\HeaderRuleEngine($this->db, $this->config);
        $ruleId = $engine->save($this->request->post);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'rule_id' => $ruleId]));
    }

    public function headerDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $id = (int)($this->request->post['rule_id'] ?? 0);
        if ($id > 0) {
            (new \OcKit\SeoCore\Libs\HeaderRuleEngine($this->db, $this->config))->delete($id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function headerTest(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $uri     = (string)($this->request->post['uri']      ?? '');
        $storeId = (int)($this->request->post['store_id']    ?? 0);
        $engine  = new \OcKit\SeoCore\Libs\HeaderRuleEngine($this->db, $this->config);
        $match   = $engine->test($uri, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'match' => $match]));
    }

    // ─── Robots.txt tab ───────────────────────────────────────────────────────

    public function robotsSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $content = (string)($this->request->post['content'] ?? '');
        $editor  = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);
        $errors  = $editor->validate($content);

        if ($errors) {
            $this->jsonError(implode('; ', $errors));
            return;
        }

        $saved = $editor->save($content);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'  => $saved,
            'backups'  => $editor->getBackups(),
        ]));
    }

    public function robotsDiff(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $backup = (string)($this->request->post['backup_path'] ?? $this->request->get['backup_path'] ?? '');
        $editor = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);

        $webRoot    = dirname($editor->getPath());
        $realBackup = realpath($backup);
        $realRoot   = realpath($webRoot);

        if (!$realBackup || !$realRoot || strncmp($realBackup, $realRoot, strlen($realRoot)) !== 0) {
            $this->jsonError('Invalid backup path');
            return;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'  => true,
            'current'  => $editor->read(),
            'backup'   => (string)file_get_contents($realBackup),
            'filename' => basename($realBackup),
        ]));
    }

    public function robotsRestore(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $backup  = (string)($this->request->post['backup_path'] ?? '');
        $editor  = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);

        // Security: ensure path is within web root
        $webRoot = dirname($editor->getPath());
        $realBackup = realpath($backup);
        $realRoot   = realpath($webRoot);

        if (!$realBackup || !$realRoot || strncmp($realBackup, $realRoot, strlen($realRoot)) !== 0) {
            $this->jsonError('Invalid backup path');
            return;
        }

        $restored = $editor->restore($realBackup);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => $restored,
            'content' => $restored ? $editor->read() : '',
            'backups' => $editor->getBackups(),
        ]));
    }

    // ─── Sitemap tab ──────────────────────────────────────────────────────────

    public function sitemapStatus(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $sitemap = new \OcKit\SeoCore\Libs\SitemapIntegration($this->registry);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'status' => $sitemap->getStatus()]));
    }

    public function sitemapGenerate(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $sitemap   = new \OcKit\SeoCore\Libs\SitemapIntegration($this->registry);
        $triggered = $sitemap->triggerGeneration();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => $triggered]));
    }

    public function sitemapPing(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $sitemapUrl = trim((string)($this->request->post['sitemap_url'] ?? ''));
        if (!$sitemapUrl || !filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
            $this->jsonError('Invalid sitemap URL');
            return;
        }
        $engine = (string)($this->request->post['engine'] ?? 'google');

        // Both legacy /ping endpoints were retired (Google 2023, Bing 2022).
        // Replace them with modern equivalents.
        if ($engine === 'google') {
            // Modern path: GSC API submitSitemap. Requires OAuth.
            try {
                $client = $this->gsc();
                if (!$client->isConnected()) {
                    $this->respondJson([
                        'success' => false,
                        'message' => $this->language->get('text_sm_ping_google_need_oauth')
                            ?: 'Connect Google in the Google tab first (OAuth). The legacy ping endpoint was retired in 2023.',
                    ]);
                    return;
                }
                $client->submitSitemap($sitemapUrl);
                $this->respondJson([
                    'success' => true,
                    'message' => $this->language->get('text_sm_ping_google_ok')
                        ?: 'Sitemap submitted to Google Search Console.',
                ]);
            } catch (\Throwable $e) {
                $this->jsonError($e->getMessage());
            }
            return;
        }

        if ($engine === 'bing') {
            // Bing retired ping in 2022; recommend IndexNow.
            $this->respondJson([
                'success'    => false,
                'deprecated' => true,
                'message'    => $this->language->get('text_sm_ping_bing_deprecated')
                    ?: 'Bing /ping endpoint was retired in 2022. Use Bing Webmaster Tools or the IndexNow protocol.',
            ]);
            return;
        }

        $this->jsonError('Unknown engine');
    }

    // ─── URL Mask regeneration ────────────────────────────────────────────────

    public function maskRegenerate(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $entityType = (string)($this->request->post['entity_type'] ?? 'product');
        $languageId = (int)($this->request->post['language_id']    ?? $this->config->get('config_language_id'));
        $storeId    = (int)($this->request->post['store_id']       ?? 0);
        $mode       = (string)($this->request->post['mode']        ?? 'empty');
        $entityIds  = (array)($this->request->post['entity_ids']   ?? []);

        $maskEngine = new \OcKit\SeoCore\Libs\UrlMaskEngine($this->config);
        $cw         = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $regen      = new \OcKit\SeoCore\Libs\MaskRegenerator($this->db, $maskEngine, $cw);

        $result = $regen->regenerate($entityType, $languageId, $storeId, $mode, $entityIds);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true] + $result));
    }

    // ─── Canonical settings ───────────────────────────────────────────────────

    public function saveCanonicalSettings(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId  = (int)($this->request->post['store_id'] ?? 0);
        $settings = [
            'module_oc_kit_seo_core_canonical_pagination'   => $this->request->post['canonical_pagination']   ?? 'first',
            'module_oc_kit_seo_core_canonical_filters'      => $this->request->post['canonical_filters']      ?? 'base',
            'module_oc_kit_seo_core_canonical_cross_domain' => $this->request->post['canonical_cross_domain'] ?? '',
        ];

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_oc_kit_seo_core', $settings, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function canonicalOverridesList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId    = (int)($this->request->get['store_id']    ?? 0);
        $languageId = (int)($this->request->get['language_id'] ?? 0);
        $entityType = (string)($this->request->get['entity_type'] ?? '');
        $page       = max(1, (int)($this->request->get['page']  ?? 1));
        $limit      = max(10, min(200, (int)($this->request->get['limit'] ?? 50)));
        $offset     = ($page - 1) * $limit;

        $where = "WHERE `canonical` IS NOT NULL AND `canonical` != '' AND `store_id` = " . $storeId;
        if ($languageId)   $where .= " AND `language_id` = " . $languageId;
        if ($entityType)   $where .= " AND `entity_type` = '" . $this->db->escape($entityType) . "'";

        $total = (int)$this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_seo_meta_override` {$where}"
        )->row['cnt'];

        $rows = $this->db->query(
            "SELECT `meta_id`, `store_id`, `language_id`, `entity_type`, `entity_id`, `canonical`
             FROM `" . DB_PREFIX . "kit_seo_meta_override` {$where}
             ORDER BY `meta_id` DESC LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $audit = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        foreach ($rows as &$r) {
            $names = $this->fetchEntityNamesFor($r['entity_type'], (int)$r['language_id']);
            $r['entity_name'] = $names[(int)$r['entity_id']] ?? '';
        }
        unset($r);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'items' => $rows,
            'total' => $total,
            'page'  => $page,
            'per_page' => $limit,
        ]));
    }

    public function canonicalOverrideSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId    = (int)($this->request->post['store_id']    ?? 0);
        $languageId = (int)($this->request->post['language_id'] ?? 0);
        $entityType = (string)($this->request->post['entity_type'] ?? '');
        $entityId   = (int)($this->request->post['entity_id']    ?? 0);
        $canonical  = trim((string)($this->request->post['canonical'] ?? ''));

        if (!$entityType || !$entityId || !$canonical) {
            $this->jsonError('Fill entity type, ID and canonical URL');
            return;
        }

        $repo = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
        $repo->saveOverride([
            'store_id'    => $storeId,
            'language_id' => $languageId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'canonical'   => $canonical,
        ]);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function canonicalOverrideDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $metaId = (int)($this->request->post['meta_id'] ?? 0);
        if (!$metaId) { $this->jsonError('meta_id required'); return; }

        // Only clear the canonical column (row may hold other meta override values)
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_seo_meta_override`
             SET `canonical` = NULL
             WHERE `meta_id` = {$metaId}"
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function saveSchemaSettings(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $settings = [
            'module_oc_kit_seo_core_schema_product'      => !empty($this->request->post['schema_product'])      ? '1' : '0',
            'module_oc_kit_seo_core_schema_breadcrumb'   => !empty($this->request->post['schema_breadcrumb'])   ? '1' : '0',
            'module_oc_kit_seo_core_schema_organization' => !empty($this->request->post['schema_organization']) ? '1' : '0',
            'module_oc_kit_seo_core_schema_website'      => !empty($this->request->post['schema_website'])      ? '1' : '0',
            'module_oc_kit_seo_core_schema_article'      => !empty($this->request->post['schema_article'])      ? '1' : '0',
            'module_oc_kit_seo_core_schema_min_reviews'  => max(0, (int)($this->request->post['schema_min_reviews'] ?? 1)),
            'module_oc_kit_seo_core_schema_org_name'     => (string)($this->request->post['schema_org_name']     ?? ''),
            'module_oc_kit_seo_core_schema_org_logo'     => (string)($this->request->post['schema_org_logo']     ?? ''),
            'module_oc_kit_seo_core_schema_org_phone'    => (string)($this->request->post['schema_org_phone']    ?? ''),
            'module_oc_kit_seo_core_schema_org_email'    => (string)($this->request->post['schema_org_email']    ?? ''),
        ];
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_oc_kit_seo_core', $settings, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function schemaRulesList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_schema_rules`
             WHERE `store_id` = {$storeId}
             ORDER BY `priority` DESC, `rule_id` DESC"
        )->rows;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['items' => $rows]));
    }

    public function schemaRuleSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $ruleId       = (int)($this->request->post['rule_id'] ?? 0);
        $storeId      = (int)($this->request->post['store_id'] ?? 0);
        $routePattern = trim((string)($this->request->post['route_pattern'] ?? ''));
        $templateRaw  = (string)($this->request->post['template'] ?? '');
        $template     = html_entity_decode($templateRaw, ENT_QUOTES, 'UTF-8');
        $priority     = (int)($this->request->post['priority'] ?? 0);
        $status       = !empty($this->request->post['status']) ? 1 : 0;

        if (!$routePattern || !$template) { $this->jsonError('Route and template required'); return; }

        $validation = $this->validateSchemaTemplatePayload($template);
        if (!empty($validation['errors'])) {
            $this->jsonError(implode('; ', $validation['errors']));
            return;
        }

        $fields = "`store_id` = {$storeId}, " .
                  "`route_pattern` = '" . $this->db->escape($routePattern) . "', " .
                  "`template` = '" . $this->db->escape($template) . "', " .
                  "`priority` = {$priority}, " .
                  "`status` = {$status}";

        if ($ruleId) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_seo_schema_rules` SET {$fields} WHERE `rule_id` = {$ruleId}");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_seo_schema_rules` SET {$fields}");
            $ruleId = (int)$this->db->getLastId();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'rule_id' => $ruleId]));
    }

    public function schemaRuleDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $ruleId = (int)($this->request->post['rule_id'] ?? 0);
        if (!$ruleId) { $this->jsonError('rule_id required'); return; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_schema_rules` WHERE `rule_id` = {$ruleId}");
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function schemaTemplateValidate(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $template = html_entity_decode((string)($this->request->post['template'] ?? ''), ENT_QUOTES, 'UTF-8');
        $res = $this->validateSchemaTemplatePayload($template);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => empty($res['errors']),
            'errors'  => $res['errors'],
        ]));
    }

    /**
     * Minimal Schema template validator:
     *  - Balanced {{#each}}/{{/each}} and {{#if}}/{{/if}} pairs.
     *  - If the template has no template tags, it must be strict JSON.
     */
    private function validateSchemaTemplatePayload(string $template): array
    {
        $errors = [];

        $eachOpen = preg_match_all('/\{\{\s*#each\b/', $template);
        $eachClose = preg_match_all('/\{\{\s*\/each\s*\}\}/', $template);
        if ($eachOpen !== $eachClose) $errors[] = "Unbalanced {{#each}}: {$eachOpen} opens / {$eachClose} closes";

        $ifOpen  = preg_match_all('/\{\{\s*#if\b/', $template);
        $ifClose = preg_match_all('/\{\{\s*\/if\s*\}\}/', $template);
        if ($ifOpen !== $ifClose) $errors[] = "Unbalanced {{#if}}: {$ifOpen} opens / {$ifClose} closes";

        // If no dynamic tokens — should be valid JSON as-is.
        if (strpos($template, '{{') === false) {
            json_decode($template);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON: ' . json_last_error_msg();
            }
        }

        return ['errors' => $errors];
    }

    public function saveOpenGraphSettings(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->post['store_id'] ?? 0);

        $settings = [
            'module_oc_kit_seo_core_og_enabled'        => !empty($this->request->post['og_enabled'])        ? '1' : '0',
            'module_oc_kit_seo_core_og_twitter_card'   => !empty($this->request->post['og_twitter_card'])   ? '1' : '0',
            'module_oc_kit_seo_core_og_twitter_handle' => (string)($this->request->post['og_twitter_handle'] ?? ''),
            'module_oc_kit_seo_core_og_image_fallback' => (string)($this->request->post['og_image_fallback'] ?? ''),
        ];

        // Per-type, per-language templates
        foreach ($this->request->post as $key => $val) {
            if (preg_match('/^og_(title|desc)_tpl_(product|category|manufacturer|information)_[a-z]{2,5}$/', $key)) {
                $settings['module_oc_kit_seo_core_' . $key] = (string)$val;
            }
        }

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_oc_kit_seo_core', $settings, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function saveHreflangSettings(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId  = (int)($this->request->post['store_id'] ?? 0);
        $enabled  = !empty($this->request->post['hreflang_enabled']) ? '1' : '0';
        $format   = (string)($this->request->post['hreflang_format'] ?? 'iso');
        if (!in_array($format, ['iso', 'bcp47'], true)) $format = 'iso';

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_oc_kit_seo_core', [
            'module_oc_kit_seo_core_hreflang_enabled' => $enabled,
            'module_oc_kit_seo_core_hreflang_format'  => $format,
        ], $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function canonicalTest(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $route      = (string)($this->request->get['route'] ?? '');
        $paramsRaw  = (string)($this->request->get['params'] ?? '');
        $languageId = (int)($this->request->get['language_id'] ?? $this->config->get('config_language_id'));
        $storeId    = (int)($this->request->get['store_id']    ?? 0);

        $params = [];
        parse_str(ltrim($paramsRaw, '?&'), $params);

        $scf_cache = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $scf_lang  = new \OcKit\SeoCore\Libs\LanguagePrefixConfig($this->config);
        $mgr    = new \OcKit\SeoCore\Libs\CanonicalManager(
            new \OcKit\SeoCore\Libs\MetaRepository($this->db),
            new \OcKit\SeoCore\Libs\UrlGenerator($scf_cache, $scf_lang, $this->config, $this->db),
            $this->config, $this->request, $this->url, $this->response, $this->document
        );

        $source = 'auto';
        // Peek at override
        $override = null;
        $entityType = '';
        $entityId   = 0;
        switch ($route) {
            case 'product/product':           $entityType = 'product';      $entityId = (int)($params['product_id']      ?? 0); break;
            case 'product/category':          $entityType = 'category';     $entityId = (int)(explode('_', (string)($params['path'] ?? '0'))[count(explode('_', (string)($params['path'] ?? '0'))) - 1]); break;
            case 'product/manufacturer/info': $entityType = 'manufacturer'; $entityId = (int)($params['manufacturer_id'] ?? 0); break;
            case 'information/information':   $entityType = 'information';  $entityId = (int)($params['information_id']  ?? 0); break;
        }
        if ($entityType && $entityId) {
            $repo = new \OcKit\SeoCore\Libs\MetaRepository($this->db);
            $ov   = $repo->getOverride($entityType, $entityId, $languageId, $storeId);
            if ($ov && !empty($ov['canonical'])) $source = 'manual_override';
        }
        if ($source === 'auto' && $route === 'product/category' && (int)($params['page'] ?? 0) > 1) {
            $source = 'pagination';
        }
        if ($source === 'auto') {
            foreach ($params as $k => $_) { if (strpos($k, 'filter_') === 0) { $source = 'filters'; break; } }
        }

        $canonical = $mgr->resolve($route, $params, $languageId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'   => true,
            'canonical' => $canonical,
            'source'    => $source,
        ]));
    }

    /**
     * Map entity_id → name for a given type+language.
     */
    private function fetchEntityNamesFor(string $type, int $languageId): array
    {
        $out = [];
        switch ($type) {
            case 'product':
            case 'category':
            case 'manufacturer':
                $table = $type === 'manufacturer' ? 'manufacturer' : $type . '_description';
                $idCol = $type . '_id';
                if ($type === 'manufacturer') {
                    $rows = $this->db->query("SELECT `manufacturer_id` AS id, `name` FROM `" . DB_PREFIX . "manufacturer`")->rows;
                } else {
                    $rows = $this->db->query("SELECT `{$idCol}` AS id, `name` FROM `" . DB_PREFIX . "{$table}` WHERE `language_id` = {$languageId}")->rows;
                }
                foreach ($rows as $r) $out[(int)$r['id']] = (string)$r['name'];
                break;
            case 'information':
                $rows = $this->db->query("SELECT `information_id` AS id, `title` AS name FROM `" . DB_PREFIX . "information_description` WHERE `language_id` = {$languageId}")->rows;
                foreach ($rows as $r) $out[(int)$r['id']] = (string)$r['name'];
                break;
        }
        return $out;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function requireLib(): void
    {
        $base = DIR_SYSTEM . 'library/ockit/seo_core/';
        require_once $base . 'Autoloader.php';
        \OcKit\SeoCore\Autoloader::register($base);
    }

    private function getLicenseInfo(): array
    {
        $this->requireLib();
        return \OcKit\SeoCore\SeoCore::getLicenseStatus($this->registry);
    }

    private function getRedirectManager(): \OcKit\SeoCore\Libs\RedirectManager
    {
        return new \OcKit\SeoCore\Libs\RedirectManager($this->db);
    }

    private function getChainDetector(\OcKit\SeoCore\Libs\RedirectManager $mgr): \OcKit\SeoCore\Libs\RedirectChainDetector
    {
        return new \OcKit\SeoCore\Libs\RedirectChainDetector($mgr);
    }

    private function buildBreadcrumbs(): array
    {
        $this->load->language(self::ROUTE);
        $ut = 'user_token=' . $this->session->data['user_token'];

        $lang_js = json_encode([
            'saving'                 => $this->language->get('js_saving'),
            'saved'                  => $this->language->get('js_saved'),
            'error'                  => $this->language->get('js_error_save'),
            'regen_done'             => $this->language->get('js_regen_done'),
            'regen_inserted'         => $this->language->get('js_regen_inserted'),
            'regen_updated'          => $this->language->get('js_regen_updated'),
            'regen_skipped'          => $this->language->get('js_regen_skipped'),
            'confirm_regen_all'      => $this->language->get('js_regen_confirm_all'),
            'confirm_delete'         => $this->language->get('js_confirm_delete_redirect'),
            'import_success'         => $this->language->get('js_import_success'),
            'import_skipped'         => $this->language->get('js_import_skipped'),
            'error_fields'           => $this->language->get('js_error_redirect_fields'),
            'confirm_delete_meta'    => $this->language->get('js_confirm_delete_meta'),
            'bulk_complete'          => $this->language->get('js_bulk_complete'),
            'bulk_filled'            => $this->language->get('js_bulk_filled'),
            'bulk_skipped'           => $this->language->get('js_bulk_skipped'),
            'audit_running'          => $this->language->get('js_audit_running'),
            'audit_done'             => $this->language->get('js_audit_done'),
            'audit_errors'           => $this->language->get('js_audit_errors'),
            'audit_warnings'         => $this->language->get('js_audit_warnings'),
            'audit_info'             => $this->language->get('js_audit_info'),
            'confirm_restore_robots' => $this->language->get('js_confirm_restore_robots'),
            'robots_saved'           => $this->language->get('js_robots_saved'),
            'sm_generate_ok'         => $this->language->get('js_sm_generate_ok'),
            'sm_generate_fail'       => $this->language->get('js_sm_generate_fail'),
            'sm_ping_ok'             => $this->language->get('js_sm_ping_ok'),
            'sm_ping_fail'           => $this->language->get('js_sm_ping_fail'),
            'absurl_scan_found'      => $this->language->get('js_absurl_scan_found'),
            'absurl_replaced'        => $this->language->get('js_absurl_replaced'),
            'confirm_replace_absurl' => $this->language->get('js_confirm_replace_absurl'),
            'confirm_flatten'        => $this->language->get('js_confirm_flatten'),
            'flatten_done'           => $this->language->get('js_flatten_done'),
            'activating'             => $this->language->get('text_license_activating'),
            'error_empty_key'        => $this->language->get('error_license_invalid_key'),
            'activated'              => $this->language->get('text_license_activated'),
            'meta_vars_product'      => $this->language->get('text_meta_vars_product'),
            'meta_vars_category'     => $this->language->get('text_meta_vars_category'),
            'meta_vars_manufacturer' => $this->language->get('text_meta_vars_manufacturer'),
            'meta_vars_information'  => $this->language->get('text_meta_vars_information'),
            'type_product'           => $this->language->get('text_type_product'),
            'type_category'          => $this->language->get('text_type_category'),
            'type_manufacturer'      => $this->language->get('text_type_manufacturer'),
            'type_information'       => $this->language->get('text_type_information'),
            'text_audit_empty'       => $this->language->get('text_audit_empty'),
            'text_selected'          => $this->language->get('text_selected'),
            'button_delete'          => $this->language->get('button_delete'),
            'code_301_use'           => $this->language->get('text_code_301_use'),
            'code_302_use'           => $this->language->get('text_code_302_use'),
            'code_303_use'           => $this->language->get('text_code_303_use'),
            'code_307_use'           => $this->language->get('text_code_307_use'),
            'code_308_use'           => $this->language->get('text_code_308_use'),
            'code_410_use'           => $this->language->get('text_code_410_use'),
            'status_new'             => $this->language->get('status_new'),
            'status_in_progress'     => $this->language->get('status_in_progress'),
            'status_fixed'           => $this->language->get('status_fixed'),
            'status_ignored'         => $this->language->get('status_ignored'),
            'button_edit'            => $this->language->get('button_edit'),
            'button_close'           => $this->language->get('button_close'),
            'text_no_diff'           => $this->language->get('text_no_diff'),
            'issue_labels'           => $this->buildIssueLabels(),
            'text_no_headers_rules'  => $this->language->get('text_no_headers_rules'),
            'text_loading'           => $this->language->get('text_loading'),
            'js_error'               => $this->language->get('js_error'),
            'js_url_not_set'         => $this->language->get('js_url_not_set'),
            'js_running'             => $this->language->get('js_running'),
            'js_preview_label'       => $this->language->get('js_preview_label'),
            'js_done_label'          => $this->language->get('js_done_label'),
            'js_products_label'      => $this->language->get('js_products_label'),
            'js_alts_added'          => $this->language->get('js_alts_added'),
            'js_confirm_img_alt'     => $this->language->get('js_confirm_img_alt'),
            'js_broken_none'         => $this->language->get('js_broken_none'),
            'js_loading'             => $this->language->get('js_loading'),
            'js_records_label'       => $this->language->get('js_records_label'),
            'js_running_preview'     => $this->language->get('js_running_preview'),
            'js_running_apply'       => $this->language->get('js_running_apply'),
            'js_matched_preview'     => $this->language->get('js_matched_preview'),
            'js_matched_apply'       => $this->language->get('js_matched_apply'),
            'js_confirm_bulk_apply'  => $this->language->get('js_confirm_bulk_apply'),
            'cache_warm'             => $this->language->get('cache_warm'),
            'cache_cold'             => $this->language->get('cache_cold'),
            'cache_entries'          => $this->language->get('cache_entries'),
            'cache_kb'               => $this->language->get('cache_kb'),
            'cache_warmed'           => $this->language->get('cache_warmed'),
            'text_no_data'           => $this->language->get('text_no_data'),
            'text_success'           => $this->language->get('text_success'),
            'text_confirm_delete'    => $this->language->get('text_confirm_delete'),
            'text_gsc_connected'     => $this->language->get('text_gsc_connected'),
            'text_gsc_disconnected'  => $this->language->get('text_gsc_disconnected'),
            'text_gsc_confirm_disconnect' => $this->language->get('text_gsc_confirm_disconnect'),
            'text_gsc_submitted'     => $this->language->get('text_gsc_submitted'),
        ]);

        $extensions_url = $this->url->link('marketplace/extension', $ut . '&type=module', true);

        return [
            'breadcrumbs' => [
                [
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link('common/dashboard', $ut, true),
                ],
                [
                    'text' => $this->language->get('text_extension'),
                    'href' => $extensions_url,
                ],
                [
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link(self::ROUTE, $ut, true),
                ],
            ],
            'heading_title'              => $this->language->get('heading_title'),
            'cancel_url'                 => $extensions_url,
            'lang_js'                    => $lang_js,
            'tab_settings'               => $this->language->get('tab_settings'),
            'tab_meta'                   => $this->language->get('tab_meta'),
            'tab_redirects'              => $this->language->get('tab_redirects'),
            'tab_urls'                   => $this->language->get('tab_urls'),
            'tab_headers'                => $this->language->get('tab_headers'),
            'tab_audit'                  => $this->language->get('tab_audit'),
            'tab_robots'                 => $this->language->get('tab_robots'),
            'tab_sitemap'                => $this->language->get('tab_sitemap'),
            'tab_absurl'                 => $this->language->get('tab_absurl'),
            'tab_dashboard'              => $this->language->get('tab_dashboard'),
            'tab_faq'                    => $this->language->get('tab_faq'),
            'tab_google'                 => $this->language->get('tab_google'),
            'button_preview'             => $this->language->get('button_preview'),
            'button_apply'               => $this->language->get('button_apply'),
            'text_schema_providers_about'       => $this->language->get('text_schema_providers_about'),
            'text_schema_providers_howto_title' => $this->language->get('text_schema_providers_howto_title'),
            'text_schema_providers_step_1'      => $this->language->get('text_schema_providers_step_1'),
            'text_schema_providers_step_2'      => $this->language->get('text_schema_providers_step_2'),
            'text_schema_providers_hint'        => $this->language->get('text_schema_providers_hint'),

            // Google Search Console UI
            'text_section_gsc'           => $this->language->get('text_section_gsc'),
            'text_section_gsc_stats'     => $this->language->get('text_section_gsc_stats'),
            'text_section_gsc_sitemaps'  => $this->language->get('text_section_gsc_sitemaps'),
            'text_gsc_about'             => $this->language->get('text_gsc_about'),
            'text_gsc_howto_title'       => $this->language->get('text_gsc_howto_title'),
            'text_gsc_howto_1'           => $this->language->get('text_gsc_howto_1'),
            'text_gsc_howto_2'           => $this->language->get('text_gsc_howto_2'),
            'text_gsc_howto_3'           => $this->language->get('text_gsc_howto_3'),
            'text_gsc_howto_4'           => $this->language->get('text_gsc_howto_4'),
            'text_gsc_howto_5'           => $this->language->get('text_gsc_howto_5'),
            'text_gsc_howto_6'           => $this->language->get('text_gsc_howto_6'),
            'text_gsc_redirect_hint'     => $this->language->get('text_gsc_redirect_hint'),
            'text_gsc_site_property_hint'=> $this->language->get('text_gsc_site_property_hint'),
            'text_gsc_connect_hint'      => $this->language->get('text_gsc_connect_hint'),
            'text_gsc_not_loaded'        => $this->language->get('text_gsc_not_loaded'),
            'label_gsc_redirect'         => $this->language->get('label_gsc_redirect'),
            'label_gsc_site_property'    => $this->language->get('label_gsc_site_property'),
            'label_gsc_status'           => $this->language->get('label_gsc_status'),
            'button_gsc_connect'         => $this->language->get('button_gsc_connect'),
            'button_gsc_disconnect'      => $this->language->get('button_gsc_disconnect'),
            'button_load'                => $this->language->get('button_load'),
            'button_submit'              => $this->language->get('button_submit'),
            'gsc_col_key'                => $this->language->get('gsc_col_key'),
            'gsc_col_clicks'             => $this->language->get('gsc_col_clicks'),
            'gsc_col_impressions'        => $this->language->get('gsc_col_impressions'),
            'gsc_col_ctr'                => $this->language->get('gsc_col_ctr'),
            'gsc_col_position'           => $this->language->get('gsc_col_position'),
            'gsc_col_path'               => $this->language->get('gsc_col_path'),
            'gsc_col_last_submitted'     => $this->language->get('gsc_col_last_submitted'),
            'gsc_col_errors'             => $this->language->get('gsc_col_errors'),
            'gsc_col_warnings'           => $this->language->get('gsc_col_warnings'),
            'gsc_dim_query'              => $this->language->get('gsc_dim_query'),
            'gsc_dim_page'               => $this->language->get('gsc_dim_page'),
            'gsc_dim_country'            => $this->language->get('gsc_dim_country'),
            'gsc_dim_device'             => $this->language->get('gsc_dim_device'),
            'button_save'                => $this->language->get('button_save'),
            'button_cancel'              => $this->language->get('button_cancel'),
            'button_delete'              => $this->language->get('button_delete'),
            'button_add'                 => $this->language->get('button_add'),
            'text_loading'               => $this->language->get('text_loading'),
            'text_license_status_active' => $this->language->get('text_license_status_active'),
            'text_license_status_trial'  => $this->language->get('text_license_status_trial'),
            'text_license_status_grace'  => $this->language->get('text_license_status_grace'),
            'text_license_status_expired'=> $this->language->get('text_license_status_expired'),
            'text_license_status_invalid'=> $this->language->get('text_license_status_invalid'),
            'tab_license'                => $this->language->get('tab_license'),
            'entry_license_key'          => $this->language->get('entry_license_key'),
            'button_activate'            => $this->language->get('button_activate'),
            'text_license_buy'           => $this->language->get('text_license_buy'),

            // Settings labels
            'label_status'                  => $this->language->get('label_status'),
            'label_url_depth'               => $this->language->get('label_url_depth'),
            'label_trailing_slash'          => $this->language->get('label_trailing_slash'),
            'label_lang_prefixes'           => $this->language->get('label_lang_prefixes'),
            'label_custom_routes'           => $this->language->get('label_custom_routes'),
            'label_pagination_mode'         => $this->language->get('label_pagination_mode'),
            'label_noindex_all_pagination'  => $this->language->get('label_noindex_all_pagination'),
            'label_mask_product'            => $this->language->get('label_mask_product'),
            'label_mask_category'           => $this->language->get('label_mask_category'),
            'label_mask_manufacturer'       => $this->language->get('label_mask_manufacturer'),
            'label_mask_information'        => $this->language->get('label_mask_information'),
            'label_auto_generate_url'       => $this->language->get('label_auto_generate_url'),
            'help_auto_generate_url'        => $this->language->get('help_auto_generate_url'),

            // Settings sections
            'text_section_general'          => $this->language->get('text_section_general'),
            'text_section_url'              => $this->language->get('text_section_url'),
            'text_section_url_masks'        => $this->language->get('text_section_url_masks'),
            'text_section_pagination'       => $this->language->get('text_section_pagination'),
            'text_section_lang_prefixes'    => $this->language->get('text_section_lang_prefixes'),
            'text_section_custom_routes'    => $this->language->get('text_section_custom_routes'),
            'text_lang_prefix_hint'         => $this->language->get('text_lang_prefix_hint'),
            'label_lang_default'            => $this->language->get('label_lang_default'),
            'text_skip_routes'              => $this->language->get('text_skip_routes'),
            'text_entity_routes'            => $this->language->get('text_entity_routes'),
            'text_depth_hint'               => $this->language->get('text_depth_hint'),
            'text_trailing_slash_hint'      => $this->language->get('text_trailing_slash_hint'),
            'text_mask_hint'                => $this->language->get('text_mask_hint'),

            // URL depth / pagination options
            'text_depth_flat'               => $this->language->get('text_depth_flat'),
            'text_depth_1'                  => $this->language->get('text_depth_1'),
            'text_depth_2'                  => $this->language->get('text_depth_2'),
            'text_depth_full'               => $this->language->get('text_depth_full'),
            'text_pagination_off'           => $this->language->get('text_pagination_off'),
            'text_pagination_404'           => $this->language->get('text_pagination_404'),
            'text_pagination_robots'        => $this->language->get('text_pagination_robots'),

            // Regen
            'label_regen_type'              => $this->language->get('label_regen_type'),
            'label_regen_lang'              => $this->language->get('label_regen_lang'),
            'label_regen_mode'              => $this->language->get('label_regen_mode'),
            'text_regen_empty'              => $this->language->get('text_regen_empty'),
            'text_regen_all'                => $this->language->get('text_regen_all'),
            'button_regen'                  => $this->language->get('button_regen'),
            'text_regen_note'               => $this->language->get('text_regen_note'),

            // Type/mode/all options
            'text_type_product'             => $this->language->get('text_type_product'),
            'text_type_category'            => $this->language->get('text_type_category'),
            'text_type_manufacturer'        => $this->language->get('text_type_manufacturer'),
            'text_type_information'         => $this->language->get('text_type_information'),
            'text_mode_empty'               => $this->language->get('text_mode_empty'),
            'text_mode_all'                 => $this->language->get('text_mode_all'),
            'text_all_types'                => $this->language->get('text_all_types'),
            'text_all_langs'                => $this->language->get('text_all_langs'),
            'text_all_levels'               => $this->language->get('text_all_levels'),

            // Columns
            'column_from'                   => $this->language->get('column_from'),
            'column_to'                     => $this->language->get('column_to'),
            'column_code'                   => $this->language->get('column_code'),
            'column_hits'                   => $this->language->get('column_hits'),
            'column_date'                   => $this->language->get('column_date'),
            'column_type'                   => $this->language->get('column_type'),
            'column_severity'               => $this->language->get('column_severity'),
            'column_entity'                 => $this->language->get('column_entity'),
            'column_issue'                  => $this->language->get('column_issue'),
            'column_detail'                 => $this->language->get('column_detail'),
            'column_file'                   => $this->language->get('column_file'),
            'column_size'                   => $this->language->get('column_size'),
            'column_field'                  => $this->language->get('column_field'),
            'column_count'                  => $this->language->get('column_count'),

            // Redirects page
            'button_redirect_add'           => $this->language->get('button_redirect_add'),
            'button_import_csv'             => $this->language->get('button_import_csv'),
            'placeholder_search_redirects'  => $this->language->get('placeholder_search_redirects'),
            'text_redirect_modal_title'     => $this->language->get('text_redirect_modal_title'),
            'text_from_uri'                 => $this->language->get('text_from_uri'),
            'text_to_url'                   => $this->language->get('text_to_url'),

            // Meta page
            'text_bulk_fill'                => $this->language->get('text_bulk_fill'),
            'text_meta_overrides'           => $this->language->get('text_meta_overrides'),
            'text_meta_overrides_hint'      => $this->language->get('text_meta_overrides_hint'),
            'text_meta_modal_title'         => $this->language->get('text_meta_modal_title'),
            'label_search_meta'             => $this->language->get('label_search_meta'),
            'button_bulk_start'             => $this->language->get('button_bulk_start'),
            'label_category'                => $this->language->get('label_category'),
            'text_all_categories'           => $this->language->get('text_all_categories'),
            'text_title_hint'               => $this->language->get('text_title_hint'),
            'text_desc_hint'                => $this->language->get('text_desc_hint'),

            // Meta templates section (Settings tab)
            'text_section_meta_templates'   => $this->language->get('text_section_meta_templates'),
            'label_meta_title_tpl'          => $this->language->get('label_meta_title_tpl'),
            'label_meta_desc_tpl'           => $this->language->get('label_meta_desc_tpl'),
            'label_meta_h1_tpl'             => $this->language->get('label_meta_h1_tpl'),
            'text_meta_tpl_hint'            => $this->language->get('text_meta_tpl_hint'),

            // Audit page
            'text_audit_run'                => $this->language->get('text_audit_run'),
            'text_audit_results'            => $this->language->get('text_audit_results'),
            'text_audit_empty'              => $this->language->get('text_audit_empty'),
            'text_analyzing'                => $this->language->get('text_analyzing'),
            'text_level_error'              => $this->language->get('text_level_error'),
            'text_level_warning'            => $this->language->get('text_level_warning'),
            'text_level_info'               => $this->language->get('text_level_info'),
            'button_audit_run'              => $this->language->get('button_audit_run'),

            // Robots page
            'text_robots_editor'            => $this->language->get('text_robots_editor'),
            'text_robots_backups'           => $this->language->get('text_robots_backups'),
            'text_no_backups'               => $this->language->get('text_no_backups'),
            'button_restore'                => $this->language->get('button_restore'),

            // Sitemap page
            'text_sitemap_status_title'     => $this->language->get('text_sitemap_status_title'),
            'text_sitemap_actions'          => $this->language->get('text_sitemap_actions'),
            'text_jetsitemap_installed'     => $this->language->get('text_jetsitemap_installed'),
            'text_jetsitemap_missing'       => $this->language->get('text_jetsitemap_missing'),
            'text_no_sitemap_file'          => $this->language->get('text_no_sitemap_file'),
            'button_sitemap_generate'       => $this->language->get('button_sitemap_generate'),
            'text_sitemap_open_settings'    => $this->language->get('text_sitemap_open_settings'),

            // AbsURL page
            'text_absurl_about_title'       => $this->language->get('text_absurl_about_title'),
            'text_absurl_about'             => $this->language->get('text_absurl_about'),
            'text_absurl_scan_title'        => $this->language->get('text_absurl_scan_title'),
            'text_absurl_replace_title'     => $this->language->get('text_absurl_replace_title'),
            'text_absurl_log_title'         => $this->language->get('text_absurl_log_title'),
            'label_search_domain'           => $this->language->get('label_search_domain'),
            'label_old_domain'              => $this->language->get('label_old_domain'),
            'label_new_domain'              => $this->language->get('label_new_domain'),
            'label_https_only'              => $this->language->get('label_https_only'),
            'button_scan'                   => $this->language->get('button_scan'),
            'button_replace_selected'       => $this->language->get('button_replace_selected'),

            // Dashboard page
            'text_stat_seo_urls'            => $this->language->get('text_stat_seo_urls'),
            'text_stat_redirects'           => $this->language->get('text_stat_redirects'),
            'text_stat_audit_errors'        => $this->language->get('text_stat_audit_errors'),
            'text_stat_audit_warnings'      => $this->language->get('text_stat_audit_warnings'),
            'text_stat_redirect_hits'       => $this->language->get('text_stat_redirect_hits'),
            'text_stat_chains'              => $this->language->get('text_stat_chains'),
            'text_quick_actions'            => $this->language->get('text_quick_actions'),
            'text_audit_issues_top'         => $this->language->get('text_audit_issues_top'),
            'text_all_audit_results'        => $this->language->get('text_all_audit_results'),
            'text_top_redirects'            => $this->language->get('text_top_redirects'),
            'text_chain_warning'            => $this->language->get('text_chain_warning'),

            // FAQ page
            'text_faq_title'                => $this->language->get('text_faq_title'),

            // Headers page
            'text_headers_test'             => $this->language->get('text_headers_test'),
            'text_headers_rules'            => $this->language->get('text_headers_rules'),
            'button_add_rule'               => $this->language->get('button_add_rule'),
            'label_hdr_uri'                 => $this->language->get('label_hdr_uri'),
            'label_hdr_robots'              => $this->language->get('label_hdr_robots'),
            'label_hdr_sort_order'          => $this->language->get('label_hdr_sort_order'),
            'label_hdr_comment'             => $this->language->get('label_hdr_comment'),
            'label_hdr_status'              => $this->language->get('label_hdr_status'),
            'placeholder_hdr_uri'           => $this->language->get('placeholder_hdr_uri'),

            // Dashboard
            'button_flatten_chains'         => $this->language->get('button_flatten_chains'),

            // Module version
            'module_version'                => self::VERSION,
        ];
    }

    public function auditRunCrawl(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $langId = (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id'));
        $urls   = $this->request->post['urls'] ?? [];
        if (is_string($urls)) $urls = array_filter(array_map('trim', explode("\n", $urls)));

        $audit  = new \OcKit\SeoCore\Libs\MetaAudit($this->db, $this->config);
        $issues = $audit->runCrawlAudit((array)$urls, $langId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'count' => count($issues)]));
    }

    public function auditExportCsv(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $langId  = (int)($this->request->get['language_id'] ?? $this->config->get('config_language_id'));
        $storeId = (int)($this->request->get['store_id']    ?? 0);

        $rows = $this->db->query(
            "SELECT `entity_type`, `entity_id`, `entity_name`, `issue_type`, `severity`, `status`, `detail`
             FROM `" . DB_PREFIX . "kit_seo_audit_results`
             WHERE `store_id` = {$storeId} AND `language_id` = {$langId}
             ORDER BY `severity`, `entity_type`, `entity_id`"
        )->rows;

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="seo_audit_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['entity_type','entity_id','entity_name','issue_type','severity','status','detail']);
        foreach ($rows as $r) fputcsv($out, $r);
        rewind($out);
        $this->response->setOutput(stream_get_contents($out));
        fclose($out);
    }

    /**
     * Phase 2: bulk-fill missing alt attributes in product description <img> tags
     * using the product name.
     */
    /**
     * Append `Disallow: /<keyword>` to robots.txt for a given seo_url row.
     * Useful for quickly hiding orphaned/legacy URLs from search engines.
     */
    /**
     * Render a small read-only widget (HTML, JSON-wrapped) listing keyword
     * change history for a single entity. Embedded into product/category/
     * manufacturer/information edit forms via OCMOD-patch.
     *
     * GET ?entity_type=product&entity_id=N&language_id=L
     */
    public function urlHistoryWidget(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $type       = (string)($this->request->get['entity_type'] ?? '');
        $entityId   = (int)($this->request->get['entity_id'] ?? 0);
        $languageId = (int)($this->request->get['language_id'] ?? 0);

        if (!in_array($type, ['product','category','manufacturer','information'], true) || $entityId <= 0) {
            $this->jsonError('invalid input');
            return;
        }
        $query = $type . '_id=' . $entityId;

        $where = "`query` = '" . $this->db->escape($query) . "'";
        if ($languageId > 0) $where .= " AND `language_id` = " . $languageId;

        $rows = $this->db->query(
            "SELECT `history_id`, `language_id`, `old_keyword`, `new_keyword`, `changed_at`
             FROM `" . DB_PREFIX . "kit_seo_url_history`
             WHERE {$where}
             ORDER BY `history_id` DESC
             LIMIT 50"
        )->rows;

        // Current keyword(s)
        $current = $this->db->query(
            "SELECT `language_id`, `keyword` FROM `" . DB_PREFIX . "seo_url`
             WHERE `query` = '" . $this->db->escape($query) . "'"
        )->rows;

        // Language map (id → name + code) so the widget can show readable labels
        $langs = $this->db->query(
            "SELECT `language_id`, `name`, `code` FROM `" . DB_PREFIX . "language`"
        )->rows;
        $languages = [];
        foreach ($langs as $l) {
            $languages[(int)$l['language_id']] = [
                'name' => (string)$l['name'],
                'code' => (string)$l['code'],
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'entity_type' => $type,
            'entity_id'   => $entityId,
            'current'     => $current,
            'history'     => $rows,
            'languages'   => $languages,
        ]));
    }

    /**
     * Bulk search/replace inside oc_seo_url.keyword. Supports plain string
     * and regex patterns. POST {find, replace, regex:0|1, mode:preview|apply,
     * language_id?, store_id?}. In `preview` mode no DB writes happen — admin
     * gets a sample of up to 100 affected rows.
     */
    public function urlBulkReplace(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $find    = (string)($this->request->post['find'] ?? '');
        $replace = (string)($this->request->post['replace'] ?? '');
        $isRegex = !empty($this->request->post['regex']);
        $mode    = (string)($this->request->post['mode'] ?? 'preview');
        $langId  = (int)($this->request->post['language_id'] ?? 0);
        $storeId = (int)($this->request->post['store_id'] ?? 0);

        if ($find === '') { $this->jsonError('find pattern required'); return; }

        $where = "`store_id` = " . $storeId;
        if ($langId > 0) $where .= " AND `language_id` = " . $langId;
        if ($isRegex) {
            // MySQL REGEXP — admin-supplied; safe because we LIKE-shape it; but escape pattern delimiters
            $where .= " AND `keyword` REGEXP '" . $this->db->escape($find) . "'";
        } else {
            $where .= " AND `keyword` LIKE '%" . $this->db->escape($find) . "%'";
        }

        // Validate regex syntax (PCRE) so we can apply replacement in PHP later
        $pcre = '/' . str_replace('/', '\/', $find) . '/u';
        if ($isRegex && @preg_match($pcre, '') === false) {
            $this->jsonError('invalid regex'); return;
        }

        $rows = $this->db->query(
            "SELECT `seo_url_id`, `keyword`, `query`, `language_id`, `store_id`
             FROM `" . DB_PREFIX . "seo_url` WHERE {$where} LIMIT 5000"
        )->rows;

        $changes = [];
        foreach ($rows as $r) {
            $old = (string)$r['keyword'];
            $new = $isRegex ? preg_replace($pcre, $replace, $old) : str_replace($find, $replace, $old);
            if ($new !== null && $new !== $old) {
                $changes[] = ['id' => (int)$r['seo_url_id'], 'old' => $old, 'new' => $new, 'query' => $r['query'], 'lang' => (int)$r['language_id'], 'store' => (int)$r['store_id']];
            }
        }

        if ($mode === 'apply') {
            $rm = $this->getRedirectManager();
            foreach ($changes as $c) {
                $this->db->query(
                    "UPDATE `" . DB_PREFIX . "seo_url`
                     SET `keyword` = '" . $this->db->escape($c['new']) . "'
                     WHERE `seo_url_id` = " . $c['id']
                );
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "kit_seo_url_history`
                     (`seo_url_id`,`store_id`,`language_id`,`query`,`old_keyword`,`new_keyword`)
                     VALUES (" . $c['id'] . ", " . $c['store'] . ", " . $c['lang'] . ",
                             '" . $this->db->escape($c['query']) . "',
                             '" . $this->db->escape($c['old']) . "',
                             '" . $this->db->escape($c['new']) . "')"
                );
                $rm->autoCapture('/' . $c['old'], '/' . $c['new'], $c['store']);
            }
            (new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache))->invalidate();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'  => true,
            'mode'     => $mode,
            'matched'  => count($changes),
            'sample'   => array_slice($changes, 0, 100),
        ]));
    }

    public function urlBlockInRobots(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $seoUrlId = (int)($this->request->post['seo_url_id'] ?? 0);
        if (!$seoUrlId) { $this->jsonError('seo_url_id required'); return; }

        $row = $this->db->query(
            "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url`
             WHERE `seo_url_id` = " . $seoUrlId . " LIMIT 1"
        )->row;
        if (!$row || $row['keyword'] === '') { $this->jsonError('not found'); return; }

        $editor = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);
        $cur    = $editor->read();
        $line   = 'Disallow: /' . ltrim((string)$row['keyword'], '/');
        if (strpos($cur, $line) !== false) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => true, 'already' => true, 'line' => $line]));
            return;
        }
        $cur = rtrim($cur, "\n") . "\n" . $line . "\n";
        $editor->save($cur);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'line' => $line]));
    }

    public function brokenLinksScan(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        set_time_limit(180);

        $scanner = new \OcKit\SeoCore\Libs\BrokenLinksScanner($this->db, $this->config);
        $report  = $scanner->scan();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'checked' => $report['checked'],
            'broken'  => count($report['broken']),
        ]));
    }

    public function brokenLinksList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $limit   = max(1, min(2000, (int)($this->request->get['limit'] ?? 500)));
        $scanner = new \OcKit\SeoCore\Libs\BrokenLinksScanner($this->db, $this->config);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'items' => $scanner->getResults($limit),
            'total' => $scanner->getCount(),
        ]));
    }

    // ─── Route-level meta tags ───────────────────────────────────────────────

    public function routeMetaList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : -1;
        $repo    = new \OcKit\SeoCore\Libs\RouteMetaRepository($this->db);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'items'   => $repo->listAll($storeId, 500),
        ]));
    }

    public function routeMetaSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId    = (int)($this->request->post['store_id']    ?? 0);
        $route      = trim((string)($this->request->post['route'] ?? ''));
        $languageId = (int)($this->request->post['language_id'] ?? 0);

        if ($route === '' || $languageId <= 0) {
            $this->jsonError('route and language_id required');
            return;
        }

        $repo = new \OcKit\SeoCore\Libs\RouteMetaRepository($this->db);
        $repo->save($storeId, $route, $languageId, [
            'title'       => (string)($this->request->post['title']       ?? ''),
            'description' => (string)($this->request->post['description'] ?? ''),
            'keywords'    => (string)($this->request->post['keywords']    ?? ''),
            'h1'          => (string)($this->request->post['h1']          ?? ''),
        ]);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function routeMetaDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $id = (int)($this->request->post['route_meta_id'] ?? 0);
        if ($id <= 0) { $this->jsonError('route_meta_id required'); return; }

        (new \OcKit\SeoCore\Libs\RouteMetaRepository($this->db))->delete($id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function imageAltBulkFill(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $langId = (int)($this->request->post['language_id'] ?? $this->config->get('config_language_id'));
        $mode   = (string)($this->request->post['mode'] ?? 'preview'); // preview|apply

        $rows = $this->db->query(
            "SELECT `product_id`, `name`, `description`
             FROM `" . DB_PREFIX . "product_description`
             WHERE `language_id` = {$langId} AND `description` LIKE '%<img%'"
        )->rows;

        $touched   = 0;
        $altsAdded = 0;
        foreach ($rows as $r) {
            $name = trim((string)$r['name']);
            $desc = (string)$r['description'];
            if ($name === '' || $desc === '') continue;

            $altEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $newDesc = preg_replace_callback(
                '/<img\b([^>]*)>/i',
                function ($m) use ($altEsc, &$altsAdded) {
                    $tag = $m[1];
                    if (preg_match('/\balt\s*=\s*["\'][^"\']*["\']/i', $tag)) {
                        return $m[0];
                    }
                    $altsAdded++;
                    return '<img alt="' . $altEsc . '"' . $tag . '>';
                },
                $desc
            );

            if ($newDesc !== $desc) {
                $touched++;
                if ($mode === 'apply') {
                    $this->db->query(
                        "UPDATE `" . DB_PREFIX . "product_description`
                         SET `description` = '" . $this->db->escape($newDesc) . "'
                         WHERE `product_id` = " . (int)$r['product_id'] . "
                           AND `language_id` = {$langId}"
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'      => true,
            'mode'         => $mode,
            'products_touched' => $touched,
            'alts_added'   => $altsAdded,
        ]));
    }

    public function warmCache(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $cache   = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $count   = (int)$cache->warm($storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'entries' => $count]));
    }

    public function clearCache(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $cache   = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $cache->clear($storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function cacheStats(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $cache   = new \OcKit\SeoCore\Libs\CacheWarmer($this->db, $this->cache);
        $stats   = $cache->getStats($storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($stats));
    }

    public function redirectsExportCsv(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $storeId = (int)($this->request->get['store_id'] ?? 0);

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="redirects_' . date('Y-m-d') . '.csv"');
        $this->response->setOutput($this->getRedirectManager()->exportCsv($storeId));
    }

    public function redirectsDeleteStale(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $days    = max(1, (int)($this->request->post['days'] ?? 90));

        $deleted = $this->getRedirectManager()->deleteStale($days, $storeId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'deleted' => $deleted]));
    }

    public function robotsValidate(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $content = (string)($this->request->post['content'] ?? '');
        $editor  = new \OcKit\SeoCore\Libs\RobotsEditor($this->config);
        $errors  = $editor->validate($content);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => empty($errors), 'errors' => $errors]));
    }

    // ─── A/B test title ────────────────────────────────────────────────────

    public function abTestList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $engine = new \OcKit\SeoCore\Libs\AbTestEngine($this->db);
        $rows   = $engine->listTests(500);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'tests' => $rows]));
    }

    public function abTestSave(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $type   = (string)($this->request->post['entity_type'] ?? '');
        $id     = (int)($this->request->post['entity_id'] ?? 0);
        $langId = (int)($this->request->post['language_id'] ?? 0);
        $titleA = trim((string)($this->request->post['title_a'] ?? ''));
        $titleB = trim((string)($this->request->post['title_b'] ?? ''));

        $allowed = ['product', 'category', 'manufacturer', 'information'];
        if (!in_array($type, $allowed, true) || $id <= 0 || $langId <= 0 || $titleA === '' || $titleB === '') {
            $this->jsonError($this->language->get('error_invalid_input') ?: 'Invalid input');
            return;
        }
        if ($titleA === $titleB) {
            $this->jsonError('Variants must differ');
            return;
        }

        $engine = new \OcKit\SeoCore\Libs\AbTestEngine($this->db);
        $testId = $engine->create($type, $id, $langId, $titleA, $titleB);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'test_id' => $testId]));
    }

    public function abTestEnd(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $testId = (int)($this->request->post['test_id'] ?? 0);
        if ($testId <= 0) { $this->jsonError('Invalid test_id'); return; }

        (new \OcKit\SeoCore\Libs\AbTestEngine($this->db))->end($testId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    public function abTestDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $testId = (int)($this->request->post['test_id'] ?? 0);
        if ($testId <= 0) { $this->jsonError('Invalid test_id'); return; }

        (new \OcKit\SeoCore\Libs\AbTestEngine($this->db))->delete($testId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    // ─── Google Search Console / Indexing API ────────────────────────────────

    private function gsc(): \OcKit\SeoCore\Libs\SearchConsoleClient
    {
        $this->load->model('setting/setting');
        $storeId = (int)($this->request->get['store_id'] ?? $this->request->post['store_id'] ?? 0);
        return new \OcKit\SeoCore\Libs\SearchConsoleClient(
            $this->config, $this->model_setting_setting, $storeId
        );
    }

    private function gscRedirectUri(): string
    {
        return html_entity_decode($this->url->link(
            self::ROUTE . '/searchConsoleCallback',
            'user_token=' . $this->session->data['user_token'],
            true
        ));
    }

    /** Status — connected, site_property, last error. */
    public function searchConsoleStatus(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        $client = $this->gsc();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'       => true,
            'connected'     => $client->isConnected(),
            'site_property' => $client->getSiteProperty(),
            'redirect_uri'  => $this->gscRedirectUri(),
        ]));
    }

    /** Begin OAuth: redirect admin browser to Google consent. */
    public function searchConsoleConnect(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);

        try {
            $authUrl = $this->gsc()->getAuthUrl(
                $this->gscRedirectUri(),
                'scf-' . bin2hex(random_bytes(8))
            );
            $this->response->redirect($authUrl);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /** OAuth callback — Google redirects here with ?code=… */
    public function searchConsoleCallback(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        $this->load->language(self::ROUTE);

        $code  = (string)($this->request->get['code']  ?? '');
        $error = (string)($this->request->get['error'] ?? '');

        $back = $this->url->link(self::ROUTE, 'user_token=' . $this->session->data['user_token'], true);

        if ($error !== '') {
            $this->session->data['error'] = 'Google OAuth: ' . $error;
            $this->response->redirect($back);
            return;
        }
        if ($code === '') {
            $this->session->data['error'] = 'Google OAuth: missing code';
            $this->response->redirect($back);
            return;
        }
        try {
            $this->gsc()->exchangeCode($code, $this->gscRedirectUri());
            $this->session->data['success'] = $this->language->get('text_success') ?: 'Connected to Google';
        } catch (\Throwable $e) {
            $this->session->data['error'] = $e->getMessage();
        }
        $this->response->redirect($back);
    }

    public function searchConsoleDisconnect(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $this->gsc()->disconnect();
            $this->respondJson(['success' => true]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function searchConsoleStats(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $start = (string)($this->request->get['start_date'] ?? date('Y-m-d', strtotime('-28 days')));
            $end   = (string)($this->request->get['end_date']   ?? date('Y-m-d', strtotime('-1 day')));
            $dim   = (string)($this->request->get['dimension']  ?? 'query');
            $limit = max(1, min(1000, (int)($this->request->get['limit'] ?? 100)));
            $rows  = $this->gsc()->searchAnalytics($start, $end, [$dim], [], $limit);
            $this->respondJson(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function searchConsoleInspect(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $url = (string)($this->request->post['url'] ?? '');
            if ($url === '') { $this->jsonError('url required'); return; }
            $this->respondJson(['success' => true, 'data' => $this->gsc()->inspectUrl($url)]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function searchConsoleSitemapList(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $this->respondJson(['success' => true, 'data' => $this->gsc()->listSitemaps()]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function searchConsoleSitemapSubmit(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $url = (string)($this->request->post['url'] ?? '');
            if ($url === '') { $this->jsonError('url required'); return; }
            $this->respondJson(['success' => true, 'data' => $this->gsc()->submitSitemap($url)]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function searchConsoleSitemapDelete(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $url = (string)($this->request->post['url'] ?? '');
            if ($url === '') { $this->jsonError('url required'); return; }
            $this->respondJson(['success' => true, 'data' => $this->gsc()->deleteSitemap($url)]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    /** Notify Google Indexing API: type=updated|deleted. */
    public function searchConsoleSubmitUrl(): void
    {
        $this->requireLib();
        \OcKit\SeoCore\SeoCore::guardAdmin($this->registry);
        try {
            $url  = (string)($this->request->post['url']  ?? '');
            $type = (string)($this->request->post['type'] ?? 'updated');
            if ($url === '') { $this->jsonError('url required'); return; }
            $client = $this->gsc();
            $resp   = $type === 'deleted' ? $client->notifyDeleted($url) : $client->notifyUpdated($url);
            $this->respondJson(['success' => true, 'data' => $resp]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function respondJson(array $payload): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($payload));
    }

    private function jsonError(string $message): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => false, 'message' => $message]));
    }

    private function buildIssueLabels(): array
    {
        $types = [
            'missing_title','missing_description','missing_seo_url',
            'title_too_short','title_too_long','title_equals_name',
            'description_too_short','description_too_long',
            'duplicate_title','duplicate_description',
            'no_image','no_brand','no_body_description','body_too_short','short_content',
            'images_no_alt','no_category','empty_category','no_price','no_model',
            'orphan_keyword','duplicate_keyword',
            'keyword_too_long','keyword_too_short','uppercase_in_keyword','special_chars_in_keyword',
        ];
        $out = [];
        foreach ($types as $t) {
            $out[$t] = $this->language->get('issue_' . $t);
        }
        return $out;
    }
}
