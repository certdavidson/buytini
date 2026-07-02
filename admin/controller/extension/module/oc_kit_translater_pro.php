<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @package   OcKit\TranslaterPro
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitTranslaterPro extends Controller
{
    private $error = [];
    private ?\OcKit\TranslaterPro\TranslaterPro $lib = null;

    private function getLib(): \OcKit\TranslaterPro\TranslaterPro
    {
        if ($this->lib === null) {
            require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
            $this->lib = new \OcKit\TranslaterPro\TranslaterPro($this->registry);
            // Ensure DB table exists — called once per request when lib is first created
            $this->lib->install();
        }
        return $this->lib;
    }

    // ─── License page ─────────────────────────────────────────────────────────

    public function license(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');

        $licenseInfo = $this->getLicenseInfo();

        if (!empty($licenseInfo['valid'])) {
            $this->response->redirect($this->url->link('extension/module/oc_kit_translater_pro', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $data['license_info']    = $licenseInfo;
        $data['license_key']     = $this->config->get('module_oc_kit_translater_pro_license_key') ?: '';
        $data['action_activate'] = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/activateLicense', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['license_url']     = $this->url->link('extension/module/oc_kit_translater_pro/license', 'user_token=' . $this->session->data['user_token'], true);
        $data['extensions_url']  = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $langKeys = [
            'heading_title', 'text_extension', 'tab_license',
            'entry_license_key', 'button_activate',
            'text_license_trial', 'text_license_expired', 'text_license_invalid',
            'text_license_api_error', 'text_license_not_validated', 'text_license_version',
            'text_license_buy', 'js_license_activating', 'text_license_activated',
            'text_license_error',
        ];
        $langJs = [];
        foreach ($langKeys as $k) {
            $langJs[$k] = $this->language->get($k);
        }
        $data['lang_js'] = json_encode($langJs, JSON_UNESCAPED_UNICODE);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/translater_pro/license', $data));
    }

    public function activateLicense(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $key = trim((string)($this->request->post['key'] ?? ''));

        if ($key === '') {
            $json['error'] = $this->language->get('text_license_not_validated');
            $this->jsonOut($json);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        $result = \OcKit\TranslaterPro\TranslaterPro::activateLicenseKey($this->registry, $key);

        if ($result['success']) {
            $json['success']      = true;
            $json['message']      = $this->language->get('text_license_activated');
            $json['redirect_url'] = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        } else {
            $json['success'] = false;
            $json['error']   = $this->language->get('text_license_error');
        }

        $this->jsonOut($json);
    }

    private function getLicenseInfo(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        return \OcKit\TranslaterPro\TranslaterPro::getLicenseStatus($this->registry);
    }

    // ─── Main page (settings) ─────────────────────────────────────────────────

    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $this->load->model('localisation/language');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/translater_pro/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/oc_kit_translater_pro', 'user_token=' . $this->session->data['user_token'], true),
            ],
        ];

        $data['action']      = $this->url->link('extension/module/oc_kit_translater_pro/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel']      = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token']  = $this->session->data['user_token'];

        // AJAX endpoints — html_entity_decode converts OC's &amp; back to & for JS use
        $data['url_stats']       = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/stats', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['url_items']       = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/loadItems', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['url_translate']   = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/translate', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['url_logs']        = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/getLogs', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['url_clear_logs']  = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/clearLogs', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');

        // Languages for selects
        $data['languages'] = $this->model_localisation_language->getLanguages();

        // Pre-select dashboard language pair from store config
        $storeLang = (string)$this->config->get('config_language'); // e.g. 'en-gb'
        $data['dash_source_lang'] = $storeLang;
        $data['dash_target_lang'] = '';
        foreach ($data['languages'] as $lang) {
            if ($lang['code'] !== $storeLang) {
                $data['dash_target_lang'] = $lang['code'];
                break;
            }
        }

        // Current config values
        $prefix = 'module_oc_kit_translater_pro_';
        $fields = [
            'status', 'api_provider',
            'openai_key', 'openai_model',
            'deepseek_key', 'deepseek_model',
            'gemini_key', 'gemini_model',
            'prompt',
            'cron_auto', 'cron_source_lang', 'cron_target_langs', 'cron_types', 'cron_batch',
        ];
        foreach ($fields as $f) {
            $key        = $prefix . $f;
            $data[$key] = $this->config->get($key);
        }

        // Ensure library is loaded — needed for DEFAULT_PROMPT constant access below
        $this->getLib();

        // Defaults
        if (!$data[$prefix . 'openai_model'])   $data[$prefix . 'openai_model']   = 'gpt-4o-mini';
        if (!$data[$prefix . 'deepseek_model']) $data[$prefix . 'deepseek_model'] = 'deepseek-chat';
        // Pre-fill prompt with the default so user can see and edit the full prompt text.
        // Placeholders {source} and {target} are substituted at translation time.
        if (!$data[$prefix . 'prompt']) {
            $data[$prefix . 'prompt'] = \OcKit\TranslaterPro\Libs\OpenAiClient::DEFAULT_PROMPT;
        }
        if (!$data[$prefix . 'gemini_model'])   $data[$prefix . 'gemini_model']   = 'gemini-2.0-flash';
        if (!$data[$prefix . 'cron_batch'])     $data[$prefix . 'cron_batch']     = 20;
        if (!$data[$prefix . 'api_provider'])   $data[$prefix . 'api_provider']   = 'openai';

        // Types list for cron checkboxes
        $data['content_types'] = [
            'product'      => $this->language->get('text_type_product'),
            'category'     => $this->language->get('text_type_category'),
            'manufacturer' => $this->language->get('text_type_manufacturer'),
            'article'      => $this->language->get('text_type_article'),
            'blog_category'=> $this->language->get('text_type_blog_category'),
        ];

        $data['error_warning'] = $this->error['warning'] ?? '';

        // Pass language strings needed directly in Twig
        foreach (['help_cron_command', 'help_prompt', 'button_log_all', 'button_log_errors', 'text_per_page'] as $key) {
            $data[$key] = $this->language->get($key);
        }

        // Admin edit URL base per content type (JS appends the item ID)
        $token = $this->session->data['user_token'];
        $data['admin_edit_urls'] = [
            'product'       => html_entity_decode($this->url->link('catalog/product/edit',          'user_token=' . $token, true), ENT_QUOTES, 'UTF-8') . '&product_id=',
            'category'      => html_entity_decode($this->url->link('catalog/category/edit',         'user_token=' . $token, true), ENT_QUOTES, 'UTF-8') . '&category_id=',
            'manufacturer'  => html_entity_decode($this->url->link('catalog/manufacturer/edit',     'user_token=' . $token, true), ENT_QUOTES, 'UTF-8') . '&manufacturer_id=',
            'article'       => html_entity_decode($this->url->link('blog/article/edit',             'user_token=' . $token, true), ENT_QUOTES, 'UTF-8') . '&article_id=',
            'blog_category' => html_entity_decode($this->url->link('blog/category/edit',            'user_token=' . $token, true), ENT_QUOTES, 'UTF-8') . '&blog_category_id=',
        ];

        // Frontend catalog base URL and per-type route patterns
        $catalogBase = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTP_CATALOG') ? HTTP_CATALOG : '');
        $catalogBase = rtrim($catalogBase, '/') . '/';
        $data['catalog_url_patterns'] = [
            'product'       => $catalogBase . 'index.php?route=product/product&product_id=',
            'category'      => $catalogBase . 'index.php?route=product/category&path=',
            'manufacturer'  => $catalogBase . 'index.php?route=product/manufacturer/info&manufacturer_id=',
            'article'       => $catalogBase . 'index.php?route=blog/article&article_id=',
            'blog_category' => $catalogBase . 'index.php?route=blog/category&blog_category_id=',
        ];

        // Cron paths — derived from server constants, no hardcoding
        $data['cron_php_path'] = realpath(DIR_SYSTEM . '../crons/cron_translater_pro.php');
        $data['cron_log_path'] = DIR_STORAGE . 'logs/cron_translater_pro.log';

        // Default prompt for "reset to default" button in JS
        $data['default_prompt'] = \OcKit\TranslaterPro\Libs\OpenAiClient::DEFAULT_PROMPT;

        // License data for License tab
        $data['license_info']    = $this->getLicenseInfo();
        $data['license_key']     = $this->config->get('module_oc_kit_translater_pro_license_key') ?: '';
        $data['action_activate'] = html_entity_decode($this->url->link('extension/module/oc_kit_translater_pro/activateLicense', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');

        // i18n for JS
        $data['i18n'] = $this->buildI18n();

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/translater_pro/index', $data));
    }

    // ─── Save settings ────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->error['permission'] ?? $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $this->load->model('setting/setting');

        $post   = $this->request->post;
        $prefix = 'module_oc_kit_translater_pro_';

        $settings = [];
        $textFields = [
            'status', 'api_provider',
            'openai_key', 'openai_model',
            'deepseek_key', 'deepseek_model',
            'gemini_key', 'gemini_model',
            'prompt',
            'cron_auto', 'cron_source_lang', 'cron_batch',
        ];
        foreach ($textFields as $f) {
            $settings[$prefix . $f] = $post[$prefix . $f] ?? '';
        }

        // Array fields
        $settings[$prefix . 'cron_target_langs'] = $post[$prefix . 'cron_target_langs'] ?? [];
        $settings[$prefix . 'cron_types']        = $post[$prefix . 'cron_types'] ?? [];

        // Preserve license state — it lives under the same setting code but is not part of
        // this form. OC's editSetting() deletes all rows for the code before re-inserting,
        // so without this the license key (and cache/trial) would be wiped on every save.
        foreach (['license_key', 'license_cache', 'trial_start'] as $keep) {
            $current = $this->config->get($prefix . $keep);
            if ($current !== null && $current !== '') {
                $settings[$prefix . $keep] = $current;
            }
        }

        $this->model_setting_setting->editSetting('module_oc_kit_translater_pro', $settings);

        $json['success'] = $this->language->get('text_success');
        $this->jsonOut($json);
    }

    // ─── AJAX: stats ──────────────────────────────────────────────────────────

    public function stats(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if (!$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $sourceLang = (string)($this->request->get['source_lang'] ?? '');
        $targetLang = (string)($this->request->get['target_lang'] ?? '');

        if (!$sourceLang || !$targetLang) {
            $json['error'] = 'Missing language parameters.';
            $this->jsonOut($json);
            return;
        }

        try {
            $json['stats'] = $this->getLib()->getStats($sourceLang, $targetLang);
        } catch (\Throwable $e) {
            $json['error'] = $e->getMessage();
        }

        $this->jsonOut($json);
    }

    // ─── AJAX: load items for translation table ───────────────────────────────

    public function loadItems(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if (!$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $type       = (string)($this->request->get['type'] ?? 'product');
        $sourceLang = (string)($this->request->get['source_lang'] ?? '');
        $targetLang = (string)($this->request->get['target_lang'] ?? '');
        $page       = max(1, (int)($this->request->get['page'] ?? 1));
        $overwrite  = !empty($this->request->get['overwrite']) && $this->request->get['overwrite'] !== '0';
        $limit      = (int)($this->request->get['limit'] ?? 30);
        if (!in_array($limit, [10, 30, 50, 100], true)) {
            $limit = 30;
        }
        $start      = ($page - 1) * $limit;

        if (!$sourceLang || !$targetLang) {
            $json['error'] = 'Missing language parameters.';
            $this->jsonOut($json);
            return;
        }

        try {
            $lib          = $this->getLib();
            $json['items'] = $lib->getItems($type, $sourceLang, $targetLang, $start, $limit, $overwrite);
            $json['total'] = $lib->countItems($type, $sourceLang, $targetLang, $overwrite);
            $json['page']  = $page;
            $json['pages'] = $json['total'] > 0 ? (int)ceil($json['total'] / $limit) : 1;
        } catch (\Throwable $e) {
            $json['error'] = $e->getMessage();
        }

        $this->jsonOut($json);
    }

    // ─── AJAX: translate one item ─────────────────────────────────────────────

    public function translate(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $type       = (string)($input['type']        ?? '');
        $itemId     = (int)($input['item_id']        ?? 0);
        $sourceLang = (string)($input['source_lang'] ?? '');
        $targetLang = (string)($input['target_lang'] ?? '');

        if (!$type || !$itemId || !$sourceLang || !$targetLang) {
            $json['error'] = 'Missing required parameters.';
            $this->jsonOut($json);
            return;
        }

        try {
            $result = $this->getLib()->translateOne($type, $itemId, $sourceLang, $targetLang);
            $json   = array_merge($json, $result);
            $json['item_id'] = $itemId;
        } catch (\Throwable $e) {
            $json['success'] = false;
            $json['error']   = $e->getMessage();
            $json['item_id'] = $itemId;
        }

        $this->jsonOut($json);
    }

    // ─── AJAX: load error logs ────────────────────────────────────────────────

    public function getLogs(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if (!$this->validate()) {
            $json['error'] = 'Permission denied.';
            $this->jsonOut($json);
            return;
        }

        $page   = max(1, (int)($this->request->get['page'] ?? 1));
        $status = in_array($this->request->get['status'] ?? '', ['success', 'error', ''], true)
            ? ($this->request->get['status'] ?? '')
            : '';
        $limit  = 50;
        $start  = ($page - 1) * $limit;

        try {
            $lib           = $this->getLib();
            $json['logs']  = $lib->getLogs($start, $limit, $status);
            $json['total'] = $lib->countLogs($status);
            $json['page']  = $page;
            $json['pages'] = $json['total'] > 0 ? (int)ceil($json['total'] / $limit) : 1;
        } catch (\Throwable $e) {
            $json['error'] = $e->getMessage();
        }

        $this->jsonOut($json);
    }

    // ─── AJAX: clear error logs ───────────────────────────────────────────────

    public function clearLogs(): void
    {
        $this->load->language('extension/module/oc_kit_translater_pro');
        require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';
        \OcKit\TranslaterPro\TranslaterPro::guardAdmin($this->registry);
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        try {
            $this->getLib()->clearLogs();
            $json['success'] = true;
        } catch (\Throwable $e) {
            $json['error'] = $e->getMessage();
        }

        $this->jsonOut($json);
    }

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->getLib()->install();
    }

    public function uninstall(): void
    {
        // Keep logs on uninstall by default — user can clear manually
        // $this->getLib()->uninstall();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_translater_pro')) {
            $this->error['permission'] = $this->language->get('error_permission');
        }
        return empty($this->error);
    }

    private function jsonOut(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function buildI18n(): array
    {
        $keys = [
            'text_loading', 'text_no_results', 'text_translating', 'text_done',
            'text_error', 'text_select_items', 'text_confirm_clear_logs',
            'text_all_translated', 'text_progress',
            'text_total', 'text_page', 'text_of',
            'text_no_logs',
            'text_type_product', 'text_type_category', 'text_type_manufacturer',
            'text_type_article', 'text_type_blog_category',
            'button_translate_selected', 'button_translate_all', 'button_clear_logs',
            'button_prev_page', 'button_next_page', 'button_load',
            'button_log_all', 'button_log_errors',
            'text_success', 'column_id', 'column_name', 'column_fields', 'column_preview',
            'column_status', 'column_type', 'column_item', 'column_source',
            'column_target', 'column_provider', 'column_error', 'column_date',
        ];
        $i18n = [];
        foreach ($keys as $k) {
            $i18n[$k] = $this->language->get($k);
        }
        return $i18n;
    }
}
