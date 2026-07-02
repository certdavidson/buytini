<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitEasyLogin extends Controller
{
    private array $error = [];

    private const PREFIX = 'module_oc_kit_easy_login_';
    private const ADMIN_CSRF_KEY = 'oc_kit_easy_login_admin_csrf';

    private const TOGGLE_FIELDS = [
        'status',
        'display_in_popup',
        'display_on_login_page',
        'display_on_register_page',
        'display_on_account_page',
        'require_phone_after_oauth',
        'trust_cf_ip',
        'google_enabled',
        'telegram_enabled',
        'telegram_request_phone',
        'apple_enabled',
        'facebook_enabled',
        'email_magic_enabled',
        'sms_otp_enabled',
    ];

    private const NUMERIC_FIELDS = [
        'log_retention_days'                  => 90,
        'rate_limit_per_ip_per_hour'          => 30,
        'rate_limit_per_email_per_hour'       => 5,
        'email_magic_token_ttl_minutes'       => 15,
        'sms_otp_code_length'                 => 6,
        'sms_otp_ttl_minutes'                 => 5,
        'sms_otp_max_attempts'                => 3,
        'google_one_tap_top_offset'           => 0,
        'google_one_tap_side_offset'          => 20,
    ];

    // license_key is intentionally NOT in STRING_FIELDS — the regular settings
    // form must never overwrite it (prevents accidental wipe on settings save).
    // It is read from config and re-persisted explicitly in save() below.
    private const STRING_FIELDS = [
        'default_redirect_route'     => '',
        'google_mode'                => 'button',
        'google_client_id'           => '',
        'google_client_secret'       => '',
        'google_one_tap_position'    => 'top_right',
        'google_button_theme'        => 'outline',
        'google_button_text'         => 'continue_with',
        'telegram_bot_token'         => '',
        'telegram_bot_username'      => '',
        'telegram_button_size'       => 'large',
        'apple_service_id'           => '',
        'apple_team_id'              => '',
        'apple_key_id'               => '',
        'apple_private_key'          => '',
        'apple_button_theme'         => 'black',
        'facebook_app_id'            => '',
        'facebook_app_secret'        => '',
        'facebook_button_size'       => 'large',
        'email_magic_from_name'      => '',
        'sms_otp_token'              => '',
        'sms_otp_sender'             => '',
    ];

    /** Per-language fields (subject/template/sms message) — saved as one config key per lang. */
    private const LANG_FIELDS = [
        'email_magic_subject',
        'email_magic_template',
        'sms_otp_message',
    ];

    private const SUPPORTED_LANGS = ['en-gb', 'ru-ru', 'uk-ua'];

    // ─── License page (no library init — avoids redirect loop) ───────────────

    public function license(): void
    {
        $this->load->language('extension/module/oc_kit_easy_login');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/easy_login/assets/js/admin.js');

        $licenseInfo = $this->getLicenseInfo();

        if (!empty($licenseInfo['valid'])) {
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_easy_login', 'user_token=' . $this->session->data['user_token'], true)
            );
            return;
        }

        $token = $this->session->data['user_token'];
        $data = array_merge($this->buildLangStrings(), [
            'heading_title'   => $this->language->get('heading_title'),
            'license_info'    => $licenseInfo,
            'license_key'     => (string)($this->config->get('module_oc_kit_easy_login_license_key') ?? ''),
            'action_activate' => html_entity_decode($this->url->link('extension/module/oc_kit_easy_login/activateLicense', 'user_token=' . $token, true)),
            'extensions_url'  => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'license_url'     => $this->url->link('extension/module/oc_kit_easy_login/license', 'user_token=' . $token, true),
            'js_lang'         => $this->buildJsLang(),
        ]);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/easy_login/license', $data));
    }

    public function activateLicense(): void
    {
        $this->load->language('extension/module/oc_kit_easy_login');
        $this->load->model('setting/setting');

        $key = trim((string)($this->request->post['license_key'] ?? ''));
        if ($key === '') {
            $this->jsonOut(['success' => false, 'message' => $this->language->get('text_license_invalid')]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        $result = \OcKit\EasyLogin\EasyLogin::activateLicenseKey($this->registry, $key);

        if (($result['error_code'] ?? '') === 'api_unreachable') {
            $this->jsonOut(['success' => false, 'message' => $this->language->get('text_license_api_error')]);
            return;
        }

        $this->jsonOut([
            'success'      => $result['success'],
            'message'      => $result['success']
                ? $this->language->get('text_license_active')
                : $this->language->get('text_license_invalid'),
            'info'         => $result['info'],
            'redirect_url' => $result['success']
                ? html_entity_decode($this->url->link('extension/module/oc_kit_easy_login', 'user_token=' . $this->session->data['user_token'], true))
                : '',
        ]);
    }

    private function getLicenseInfo(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        return \OcKit\EasyLogin\EasyLogin::getLicenseStatus($this->registry);
    }

    // ─── Settings page ────────────────────────────────────────────────────────

    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        \OcKit\EasyLogin\EasyLogin::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_easy_login');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $this->loadAssets();

        $token = $this->session->data['user_token'];

        if (empty($this->session->data[self::ADMIN_CSRF_KEY])) {
            $this->session->data[self::ADMIN_CSRF_KEY] = bin2hex(random_bytes(16));
        }
        $adminCsrf = (string)$this->session->data[self::ADMIN_CSRF_KEY];

        $data = array_merge(
            $this->buildLangStrings(),
            [
                'action_save'             => html_entity_decode($this->url->link('extension/module/oc_kit_easy_login/save', 'user_token=' . $token, true)),
                'action_activate_license' => html_entity_decode($this->url->link('extension/module/oc_kit_easy_login/activateLicense', 'user_token=' . $token, true)),
                'log_url'         => $this->url->link('extension/module/oc_kit_easy_login/log', 'user_token=' . $token, true),
                'cancel'          => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
                'active_tab'      => 'general',
                'settings'        => $this->loadCurrentSettings(),
                'callback_urls'   => $this->buildCallbackUrls(),
                'license_info'    => $this->getLicenseInfo(),
                'js_lang'         => $this->buildJsLang(),
                'admin_csrf'      => $adminCsrf,
                'is_https'        => $this->isCatalogHttps(),
                'catalog_origin'  => $this->catalogOrigin(),
                'cron_path'       => $this->cronPath(),
            ]
        );

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/easy_login/settings', $data));
    }

    // ─── AJAX save ────────────────────────────────────────────────────────────

    public function save(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        \OcKit\EasyLogin\EasyLogin::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_easy_login');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easy_login')) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        // Match clearLog/clearOldLog: body-bound CSRF in addition to user_token.
        $expected = (string)($this->session->data[self::ADMIN_CSRF_KEY] ?? '');
        $given    = (string)($this->request->post['csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $given)) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $this->load->model('setting/setting');

        $post     = $this->request->post;
        $settings = [];

        foreach (self::TOGGLE_FIELDS as $f) {
            $settings[self::PREFIX . $f] = (int)!empty($post[self::PREFIX . $f]);
        }
        foreach (self::NUMERIC_FIELDS as $f => $default) {
            $val = $post[self::PREFIX . $f] ?? $default;
            $settings[self::PREFIX . $f] = (int)$val;
        }
        foreach (self::STRING_FIELDS as $f => $default) {
            $settings[self::PREFIX . $f] = (string)($post[self::PREFIX . $f] ?? $default);
        }
        foreach (self::LANG_FIELDS as $f) {
            foreach (self::SUPPORTED_LANGS as $lang) {
                $key = self::PREFIX . $f . '_' . $lang;
                $settings[$key] = (string)($post[$key] ?? '');
            }
        }

        // Preserve the existing license key — it has a dedicated activation
        // endpoint and must survive any normal settings save.
        $settings[self::PREFIX . 'license_key'] = (string)$this->config->get(self::PREFIX . 'license_key');

        $this->model_setting_setting->editSetting('module_oc_kit_easy_login', $settings);

        $json['success'] = $this->language->get('text_success');
        $this->jsonOut($json);
    }

    // ─── Log page ─────────────────────────────────────────────────────────────

    public function log(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        \OcKit\EasyLogin\EasyLogin::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_easy_login');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/oc_kit_easy_login');

        $this->loadAssets();

        $filter = [
            'provider'  => $this->request->get['filter_provider']  ?? '',
            'status'    => $this->request->get['filter_status']    ?? '',
            'email'     => $this->request->get['filter_email']     ?? '',
            'ip'        => $this->request->get['filter_ip']        ?? '',
            'date_from' => $this->request->get['filter_date_from'] ?? '',
            'date_to'   => $this->request->get['filter_date_to']   ?? '',
        ];

        $page  = max(1, (int)($this->request->get['page'] ?? 1));
        $limit = 25;
        $start = ($page - 1) * $limit;

        $filterSql          = $filter;
        $filterSql['start'] = $start;
        $filterSql['limit'] = $limit;

        $entries = $this->model_extension_module_oc_kit_easy_login->getLogEntries($filterSql);
        $total   = $this->model_extension_module_oc_kit_easy_login->getLogTotal($filter);
        $stats   = $this->model_extension_module_oc_kit_easy_login->getLogStats();

        $token = $this->session->data['user_token'];

        // Per-admin-session CSRF token; rendered into the log page and
        // required by clearLog/clearOldLog. user_token alone is OK against
        // simple CSRF (it's in the URL query) but adding a body-bound token
        // matches the destructive-action pattern used elsewhere in oc-kit.
        if (empty($this->session->data[self::ADMIN_CSRF_KEY])) {
            $this->session->data[self::ADMIN_CSRF_KEY] = bin2hex(random_bytes(16));
        }
        $adminCsrf = (string)$this->session->data[self::ADMIN_CSRF_KEY];

        $data = array_merge(
            $this->buildLangStrings(),
            [
                'admin_csrf' => $adminCsrf,
                'entries'    => $entries,
                'total'      => $total,
                'stats'      => $stats,
                'page'       => $page,
                'limit'      => $limit,
                'pages'      => max(1, (int)ceil($total / $limit)),
                'filter'     => $filter,
                'user_token'   => $token,
                'extensions_url' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
                'settings_url' => $this->url->link('extension/module/oc_kit_easy_login', 'user_token=' . $token, true),
                'log_url'    => $this->url->link('extension/module/oc_kit_easy_login/log', 'user_token=' . $token, true),
                'clear_log_url' => html_entity_decode($this->url->link('extension/module/oc_kit_easy_login/clearLog', 'user_token=' . $token, true)),
                'clear_old_url' => html_entity_decode($this->url->link('extension/module/oc_kit_easy_login/clearOldLog', 'user_token=' . $token, true)),
                'active_tab' => 'log',
                'js_lang'    => $this->buildJsLang(),
                'retention_days' => (int)($this->config->get(self::PREFIX . 'log_retention_days') ?: 90),
            ]
        );

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/easy_login/log', $data));
    }

    public function clearLog(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        \OcKit\EasyLogin\EasyLogin::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_easy_login');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easy_login')
            || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $expected = (string)($this->session->data[self::ADMIN_CSRF_KEY] ?? '');
        $given    = (string)($this->request->post['csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $given)) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $this->load->model('extension/module/oc_kit_easy_login');
        $this->model_extension_module_oc_kit_easy_login->clearLog();

        $json['success'] = $this->language->get('text_log_cleared');
        $this->jsonOut($json);
    }

    public function clearOldLog(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';
        \OcKit\EasyLogin\EasyLogin::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_easy_login');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easy_login')
            || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $expected = (string)($this->session->data[self::ADMIN_CSRF_KEY] ?? '');
        $given    = (string)($this->request->post['csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $given)) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $retention = (int)($this->config->get(self::PREFIX . 'log_retention_days') ?: 90);
        $this->load->model('extension/module/oc_kit_easy_login');
        $deleted = $this->model_extension_module_oc_kit_easy_login->clearOldLog($retention);

        $json['success'] = sprintf($this->language->get('text_old_cleared'), $deleted);
        $this->jsonOut($json);
    }

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->load->model('extension/module/oc_kit_easy_login');
        $this->model_extension_module_oc_kit_easy_login->install();
    }

    public function uninstall(): void
    {
        $this->load->model('extension/module/oc_kit_easy_login');
        $this->model_extension_module_oc_kit_easy_login->uninstall();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function loadAssets(): void
    {
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/easy_login/assets/css/admin.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/easy_login/assets/js/admin.js');
    }

    private function loadCurrentSettings(): array
    {
        $out = [];
        foreach (self::TOGGLE_FIELDS as $f) {
            $out[$f] = (int)$this->config->get(self::PREFIX . $f);
        }
        // set proper defaults for fields that have non-0 defaults
        if (!$this->config->has(self::PREFIX . 'require_phone_after_oauth')) {
            $out['require_phone_after_oauth'] = 1;
        }
        foreach (self::NUMERIC_FIELDS as $f => $default) {
            $cur = $this->config->get(self::PREFIX . $f);
            $out[$f] = ($cur === null || $cur === '') ? $default : (int)$cur;
        }
        foreach (self::STRING_FIELDS as $f => $default) {
            $cur = $this->config->get(self::PREFIX . $f);
            $out[$f] = ($cur === null || $cur === '') ? $default : (string)$cur;
        }
        // license_key is excluded from STRING_FIELDS save path but the twig
        // license panel still needs to display it.
        $out['license_key'] = (string)($this->config->get(self::PREFIX . 'license_key') ?? '');
        foreach (self::LANG_FIELDS as $f) {
            foreach (self::SUPPORTED_LANGS as $lang) {
                $key = $f . '_' . $lang;
                $out[$key] = (string)$this->config->get(self::PREFIX . $key);
            }
        }
        return $out;
    }

    private function buildCallbackUrls(): array
    {
        $base = defined('HTTP_CATALOG') ? rtrim(HTTP_CATALOG, '/') : rtrim($this->config->get('config_url') ?: HTTP_SERVER, '/');
        return [
            'google'   => $base . '/index.php?route=extension/module/oc_kit_easy_login/google_callback',
            'telegram' => $base . '/index.php?route=extension/module/oc_kit_easy_login/telegram_callback',
            'apple'    => $base . '/index.php?route=extension/module/oc_kit_easy_login/apple_callback',
            'facebook' => $base . '/index.php?route=extension/module/oc_kit_easy_login/facebook_callback',
            'magic'    => $base . '/index.php?route=extension/module/oc_kit_easy_login/magic_verify',
        ];
    }

    private function buildLangStrings(): array
    {
        $keys = [
            'heading_title','text_extension','text_success','text_edit','text_module_name','text_module_description',
            'text_enabled','text_disabled','text_yes','text_no','text_log_empty','text_records_total',
            'tab_general','tab_google','tab_telegram','tab_apple','tab_facebook','tab_email_magic','tab_sms_otp','tab_log','tab_faq','tab_license',
            'text_section_status','text_section_display','text_section_policies','text_section_rate_limits','text_section_log_settings',
            'entry_status','entry_display_in_popup','help_display_in_popup',
            'text_section_google_credentials','text_section_google_appearance',
            'entry_google_enabled','entry_google_mode','entry_google_client_id','entry_google_client_secret',
            'entry_google_one_tap_position','entry_google_button_theme','entry_google_button_text',
            'help_google_callback_url','help_google_mode',
            'mode_button','mode_one_tap','mode_both',
            'pos_top_right','pos_top_left','pos_bottom_right','pos_bottom_left',
            'entry_one_tap_top_offset','entry_one_tap_side_offset','help_one_tap_offset',
            'theme_outline','theme_filled_blue','theme_filled_black',
            'btn_text_signin_with','btn_text_signup_with','btn_text_continue_with',
            'text_section_telegram_credentials','text_section_telegram_appearance',
            'entry_telegram_enabled','entry_telegram_bot_token','entry_telegram_bot_username',
            'entry_telegram_button_size','entry_telegram_request_phone',
            'help_telegram_setup','help_telegram_domain','help_telegram_bot_username',
            'help_telegram_request_phone',
            'btn_size_large','btn_size_medium','btn_size_small',
            // Apple
            'text_section_apple_credentials','text_section_apple_appearance',
            'entry_apple_enabled','entry_apple_service_id','entry_apple_team_id','entry_apple_key_id',
            'entry_apple_private_key','entry_apple_button_theme',
            'help_apple_setup','help_apple_private_key',
            'theme_black','theme_white','theme_white_outline',
            // Facebook
            'text_section_facebook_credentials','text_section_facebook_appearance',
            'entry_facebook_enabled','entry_facebook_app_id','entry_facebook_app_secret','entry_facebook_button_size',
            'help_facebook_setup',
            // Email Magic
            'text_email_magic_description',
            'text_section_email_magic_settings','text_section_email_magic_template',
            'entry_email_magic_enabled','entry_email_magic_token_ttl_minutes','entry_email_magic_from_name',
            'entry_email_magic_subject','entry_email_magic_template',
            'help_email_magic_template',
            // SMS OTP
            'text_section_sms_otp_settings','text_section_sms_otp_text',
            'entry_sms_otp_enabled','entry_sms_otp_token','entry_sms_otp_sender',
            'entry_sms_otp_code_length','entry_sms_otp_ttl_minutes','entry_sms_otp_max_attempts',
            'entry_sms_otp_message','help_sms_otp_message',
            // License
            'text_license_title','text_license_subtitle','entry_license_key','button_activate',
            'text_license_status_active','text_license_status_invalid','text_license_status_grace',
            'text_license_status_trial','text_license_status_expired','text_license_status_not_validated',
            'text_license_domain','text_license_version','text_license_get_key',
            'entry_display_on_login_page','entry_display_on_register_page',
            'entry_display_on_account_page','help_display_on_account_page',
            'entry_require_phone_after_oauth','help_require_phone_after_oauth',
            'entry_log_retention_days','help_log_retention_days',
            'entry_default_redirect_route','help_default_redirect_route',
            'entry_rate_limit_per_ip_per_hour','entry_rate_limit_per_email_per_hour',
            'entry_trust_cf_ip','help_trust_cf_ip',
            'column_provider','column_status','column_email','column_customer_id','column_ip','column_user_agent','column_error','column_created_at',
            'entry_filter_provider','entry_filter_status','entry_filter_email','entry_filter_ip','entry_filter_date_from','entry_filter_date_to',
            'button_filter','button_reset_filter','button_clear_log','button_clear_old',
            'status_success','status_failed','status_rate_limited','status_linked','status_registered',
            'text_stats_total','text_stats_success','text_stats_failed','text_stats_rate_limited','text_stats_linked','text_stats_registered',
            'text_faq_intro',
            'button_save','button_cancel','button_back',
            'text_https_required_title','text_https_required_body',
        ];
        $out = [];
        foreach ($keys as $k) $out[$k] = $this->language->get($k);
        return $out;
    }

    private function buildJsLang(): array
    {
        return [
            'saved'                     => $this->language->get('text_success'),
            'error_save'                => $this->language->get('error_permission'),
            'error_network'             => $this->language->get('error_network'),
            'confirm_clear_log'         => $this->language->get('text_confirm_clear_log'),
            'confirm_clear_old'         => $this->language->get('text_confirm_clear_old'),
            'error_license_key_required'=> $this->language->get('js_error_license_key_required'),
            'error_no_activate_url'     => $this->language->get('js_error_no_activate_url'),
        ];
    }

    private function jsonOut(array $json): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * OAuth providers refuse non-HTTPS redirect URIs and Apple's signed-state
     * cookie requires Secure;SameSite=None — so on plain HTTP the module
     * cannot work. Show a banner to make this obvious during setup.
     */
    private function isCatalogHttps(): bool
    {
        $base = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        if ($base === '') return true; // Can't determine — don't show a false warning.
        return stripos($base, 'https://') === 0;
    }

    /**
     * Origin (scheme + host) of the storefront — used in FAQ snippets like
     * Google's "Authorized JavaScript origins". Trailing slash stripped.
     */
    private function catalogOrigin(): string
    {
        $base = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        $parts = parse_url($base);
        if (!is_array($parts) || empty($parts['host'])) return rtrim($base, '/');
        $scheme = $parts['scheme'] ?? 'https';
        return $scheme . '://' . $parts['host'];
    }

    /**
     * Absolute path to crons/cron_easy_login.php on disk — used in FAQ to
     * suggest a crontab entry. DIR_APPLICATION here is admin/, so we walk
     * one level up to the OC root.
     */
    private function cronPath(): string
    {
        $root = realpath(DIR_APPLICATION . '..');
        if ($root === false) {
            $root = rtrim(str_replace('\\', '/', DIR_APPLICATION), '/');
            $root = preg_replace('#/admin/?$#', '', $root);
        }
        return rtrim($root, '/') . '/crons/cron_easy_login.php';
    }
}
