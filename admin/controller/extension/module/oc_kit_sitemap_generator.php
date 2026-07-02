<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitSitemapGenerator extends Controller
{
    private $error = [];
    private ?\OcKit\SitemapGenerator\SitemapGenerator $lib = null;

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->load->model('extension/module/oc_kit_sitemap_generator');
        $this->model_extension_module_oc_kit_sitemap_generator->install();
    }

    public function uninstall(): void
    {
        $this->load->model('extension/module/oc_kit_sitemap_generator');
        $this->model_extension_module_oc_kit_sitemap_generator->uninstall();
    }

    // ─── License page ─────────────────────────────────────────────────────────

    public function license(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/sitemap_generator/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/sitemap_generator/assets/js/admin.js');

        $licenseInfo = $this->getLicenseInfo();

        if (!empty($licenseInfo['valid'])) {
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_sitemap_generator', 'user_token=' . $this->session->data['user_token'], true)
            );
            return;
        }

        $token = $this->session->data['user_token'];
        $data  = array_merge($this->buildLangStrings(), [
            'heading_title'   => $this->language->get('heading_title'),
            'license_info'    => $licenseInfo,
            'license_key'     => (string)($this->config->get('module_oc_kit_sitemap_generator_license_key') ?? ''),
            'action_activate' => $this->jsUrl('extension/module/oc_kit_sitemap_generator/activateLicense', 'user_token=' . $token),
            'extensions_url'  => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'license_url'     => $this->url->link('extension/module/oc_kit_sitemap_generator/license', 'user_token=' . $token, true),
            'lang_js'         => $this->buildJsLang(),
        ]);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/sitemap_generator/license', $data));
    }

    // ─── Activate license (AJAX) ──────────────────────────────────────────────

    public function activateLicense(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => false, 'error' => $this->language->get('error_permission')]));
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        $key    = trim((string)($this->request->post['license_key'] ?? ''));
        $result = \OcKit\SitemapGenerator\SitemapGenerator::activateLicenseKey($this->registry, $key);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }

    // ─── Main settings page ────────────────────────────────────────────────────

    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        \OcKit\SitemapGenerator\SitemapGenerator::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/oc_kit_sitemap_generator');
        $this->load->model('setting/setting');

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/sitemap_generator/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/sitemap_generator/assets/js/admin.js');

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $postData = $this->request->post;

            // Preserve settings managed outside this form (license & cron key)
            // editSetting() replaces ALL keys for the code, so we must carry them over.
            $preserve = [
                'module_oc_kit_sitemap_generator_license_key',
                'module_oc_kit_sitemap_generator_license_cache',
                'module_oc_kit_sitemap_generator_trial_start',
                'module_oc_kit_sitemap_generator_cron_key',
            ];
            foreach ($preserve as $k) {
                if (empty($postData[$k])) {
                    $v = $this->config->get($k);
                    if ($v !== null && $v !== '') {
                        $postData[$k] = $v;
                    }
                }
            }

            $this->model_setting_setting->editSetting('module_oc_kit_sitemap_generator', $postData);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_sitemap_generator', 'user_token=' . $this->session->data['user_token'], true)
            );
        }

        $token = $this->session->data['user_token'];

        $outputDirStatus = $this->model_extension_module_oc_kit_sitemap_generator->getOutputDirStatus();
        $cronKey         = $this->model_extension_module_oc_kit_sitemap_generator->getCronKey();
        $storeUrl        = $this->config->get('config_url') ?: (defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER);
        $cronUrl         = $this->url->link(
            'extension/module/oc_kit_sitemap_generator/cronRun',
            'cron_key=' . $cronKey,
            true
        );

        $data = array_merge($this->buildLangStrings(), $this->buildFormData(), [
            'heading_title'    => $this->language->get('heading_title'),
            'action'           => $this->url->link('extension/module/oc_kit_sitemap_generator', 'user_token=' . $token, true),
            'cancel'           => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'extensions_url'   => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),

            // Ajax endpoints (jsUrl strips &amp; → & for use in JS fetch)
            'ajax_save'             => $this->jsUrl('extension/module/oc_kit_sitemap_generator/saveSettings',    'user_token=' . $token),
            'ajax_generate'         => $this->jsUrl('extension/module/oc_kit_sitemap_generator/generate',         'user_token=' . $token),
            'ajax_generate_resizes' => $this->jsUrl('extension/module/oc_kit_sitemap_generator/generateResizes', 'user_token=' . $token),
            'ajax_save_map'    => $this->jsUrl('extension/module/oc_kit_sitemap_generator/saveMap',        'user_token=' . $token),
            'ajax_delete_map'  => $this->jsUrl('extension/module/oc_kit_sitemap_generator/deleteMap',      'user_token=' . $token),
            'ajax_delete_files'=> $this->jsUrl('extension/module/oc_kit_sitemap_generator/deleteFiles',    'user_token=' . $token),
            'ajax_clear_logs'  => $this->jsUrl('extension/module/oc_kit_sitemap_generator/clearLogs',      'user_token=' . $token),
            'ajax_logs'        => $this->jsUrl('extension/module/oc_kit_sitemap_generator/logsData',       'user_token=' . $token),
            'ajax_regen_key'   => $this->jsUrl('extension/module/oc_kit_sitemap_generator/regenerateKey',  'user_token=' . $token),
            'ajax_activate'    => $this->jsUrl('extension/module/oc_kit_sitemap_generator/activateLicense','user_token=' . $token),

            'maps'             => $this->model_extension_module_oc_kit_sitemap_generator->getMaps(),
            'languages'        => $this->model_extension_module_oc_kit_sitemap_generator->getLanguages(),
            'has_blog'         => $this->model_extension_module_oc_kit_sitemap_generator->hasBlogModule(),
            'generated_files'  => $this->model_extension_module_oc_kit_sitemap_generator->listGeneratedFiles(),
            'robots_hint'      => $this->model_extension_module_oc_kit_sitemap_generator->getRobotsHint(),
            'output_dir'       => $outputDirStatus['dir'],
            'output_dir_abs'   => $outputDirStatus['abs'],
            'output_dir_writable' => $outputDirStatus['writable'],
            'cron_key'         => $cronKey,
            'cron_url'         => $cronUrl,
            'crontab_cmd'      => '/usr/bin/php ' . realpath(DIR_APPLICATION . '../crons/cron_sitemap_generator.php'),
            'store_url'        => rtrim($storeUrl, '/'),
            'license_info'     => $this->getLicenseInfo(),
            'module_oc_kit_sitemap_generator_license_key' => (string)($this->config->get('module_oc_kit_sitemap_generator_license_key') ?? ''),
            'lang_js'          => $this->buildJsLang(),

            'success'          => $this->session->data['success'] ?? '',
            'error_warning'    => !empty($this->error['warning']) ? $this->error['warning'] : '',
        ]);
        unset($this->session->data['success']);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/sitemap_generator/form', $data));
    }

    // ─── Save Settings (AJAX) ─────────────────────────────────────────────────

    public function saveSettings(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('setting/setting');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        if (!$this->validate()) {
            $this->jsonResponse(['success' => false, 'error' => $this->error['warning'] ?? $this->language->get('error_permission')]);
            return;
        }

        $postData = $this->request->post;

        $preserve = [
            'module_oc_kit_sitemap_generator_license_key',
            'module_oc_kit_sitemap_generator_license_cache',
            'module_oc_kit_sitemap_generator_trial_start',
            'module_oc_kit_sitemap_generator_cron_key',
        ];
        foreach ($preserve as $k) {
            if (empty($postData[$k])) {
                $v = $this->config->get($k);
                if ($v !== null && $v !== '') {
                    $postData[$k] = $v;
                }
            }
        }

        $this->model_setting_setting->editSetting('module_oc_kit_sitemap_generator', $postData);
        $this->jsonResponse(['success' => true, 'message' => $this->language->get('text_success')]);
    }

    // ─── Generate (AJAX) ──────────────────────────────────────────────────────

    public function generate(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $mapId  = isset($this->request->post['map_id']) && $this->request->post['map_id'] !== ''
                ? (int)$this->request->post['map_id']
                : null;
        $dryRun = !empty($this->request->post['dry_run']);

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';

        try {
            $result = $this->model_extension_module_oc_kit_sitemap_generator->generate($mapId, $dryRun, 'manual');
            $this->jsonResponse(['success' => empty($result['errors']), 'result' => $result]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─── Generate resizes (AJAX) ──────────────────────────────────────────────

    public function generateResizes(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        @set_time_limit(0);

        try {
            $result = $this->model_extension_module_oc_kit_sitemap_generator->generateResizes();
            $this->jsonResponse(['success' => !isset($result['error']), 'result' => $result]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─── HTTP Cron endpoint (with private key) ────────────────────────────────

    public function cronRun(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        $providedKey = (string)($this->request->get['cron_key'] ?? '');
        $storedKey   = $this->model_extension_module_oc_kit_sitemap_generator->getCronKey();

        if (!$storedKey || !hash_equals($storedKey, $providedKey)) {
            http_response_code(403);
            $this->response->setOutput($this->language->get('error_invalid_cron_key'));
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';

        $mapId = isset($this->request->get['map_id']) ? (int)$this->request->get['map_id'] : null;

        try {
            $result = $this->model_extension_module_oc_kit_sitemap_generator->generate($mapId, false, 'http');
            $output = "OK\n";
            $output .= "URLs: " . $result['urls_total'] . "\n";
            $output .= "Files: " . $result['files_total'] . "\n";
            if (!empty($result['errors'])) {
                $output .= "Errors: " . implode('; ', $result['errors']) . "\n";
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            $output = "ERROR: " . $e->getMessage() . "\n";
        }

        $this->response->addHeader('Content-Type: text/plain; charset=UTF-8');
        $this->response->setOutput($output);
    }

    // ─── Language Map CRUD (AJAX) ─────────────────────────────────────────────

    public function saveMap(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $data = $this->request->post;

        if (empty($data['filename'])) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_filename_required')]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';

        $mapId = $this->model_extension_module_oc_kit_sitemap_generator->saveMap($data);
        $map   = $this->model_extension_module_oc_kit_sitemap_generator->getMap($mapId);

        $this->jsonResponse(['success' => true, 'map_id' => $mapId, 'map' => $map]);
    }

    public function deleteMap(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $mapId = (int)($this->request->post['map_id'] ?? 0);
        if (!$mapId) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid map_id']);
            return;
        }

        $this->model_extension_module_oc_kit_sitemap_generator->deleteMap($mapId);
        $this->jsonResponse(['success' => true]);
    }

    // ─── Delete Files (AJAX) ──────────────────────────────────────────────────

    public function deleteFiles(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $this->model_extension_module_oc_kit_sitemap_generator->deleteGeneratedFiles();
        $this->jsonResponse(['success' => true]);
    }

    // ─── Logs data (AJAX) ─────────────────────────────────────────────────────

    public function logsData(): void
    {
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        $filter = [
            'map_id'    => $this->request->get['map_id']    ?? null,
            'status'    => $this->request->get['status']    ?? null,
            'date_from' => $this->request->get['date_from'] ?? null,
            'date_to'   => $this->request->get['date_to']   ?? null,
            'page'      => (int)($this->request->get['page']  ?? 1),
            'limit'     => (int)($this->request->get['limit'] ?? 50),
        ];

        $result = $this->model_extension_module_oc_kit_sitemap_generator->getLogs($filter);
        $this->jsonResponse($result);
    }

    // ─── Clear Logs (AJAX) ────────────────────────────────────────────────────

    public function clearLogs(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $mapId = isset($this->request->post['map_id']) && $this->request->post['map_id'] !== ''
               ? (int)$this->request->post['map_id']
               : null;

        $this->model_extension_module_oc_kit_sitemap_generator->clearLogs($mapId);
        $this->jsonResponse(['success' => true]);
    }

    // ─── Regenerate cron key (AJAX) ───────────────────────────────────────────

    public function regenerateKey(): void
    {
        $this->load->language('extension/module/oc_kit_sitemap_generator');
        $this->load->model('extension/module/oc_kit_sitemap_generator');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }

        $key    = $this->model_extension_module_oc_kit_sitemap_generator->regenerateCronKey();
        $cronUrl = $this->url->link(
            'extension/module/oc_kit_sitemap_generator/cronRun',
            'cron_key=' . $key,
            true
        );

        $this->jsonResponse(['success' => true, 'cron_key' => $key, 'cron_url' => $cronUrl]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function getLib(): \OcKit\SitemapGenerator\SitemapGenerator
    {
        if ($this->lib === null) {
            require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
            $this->lib = new \OcKit\SitemapGenerator\SitemapGenerator($this->registry);
        }
        return $this->lib;
    }

    private function getLicenseInfo(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        return \OcKit\SitemapGenerator\SitemapGenerator::getLicenseStatus($this->registry);
    }

    private function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_sitemap_generator')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return empty($this->error);
    }

    private function buildFormData(): array
    {
        $defaults = [
            'module_oc_kit_sitemap_generator_status'           => '0',
            'module_oc_kit_sitemap_generator_generation_mode'  => 'static',
            'module_oc_kit_sitemap_generator_output_directory' => '',
            'module_oc_kit_sitemap_generator_index_filename'   => 'sitemap',
            'module_oc_kit_sitemap_generator_urls_per_file'    => '10000',
            'module_oc_kit_sitemap_generator_grouping_mode'   => 'combined',
            'module_oc_kit_sitemap_generator_enable_gzip'      => '0',
            'module_oc_kit_sitemap_generator_enable_xsl'       => '0',
            'module_oc_kit_sitemap_generator_dynamic_cache_ttl'=> '3600',
            'module_oc_kit_sitemap_generator_include_images'   => '0',
            'module_oc_kit_sitemap_generator_image_type'       => 'original',
            'module_oc_kit_sitemap_generator_image_width'      => '800',
            'module_oc_kit_sitemap_generator_image_height'     => '800',
            'module_oc_kit_sitemap_generator_include_additional_images' => '0',
            'module_oc_kit_sitemap_generator_max_images_per_product'    => '10',
            'module_oc_kit_sitemap_generator_hreflang_enable'           => '0',
            'module_oc_kit_sitemap_generator_hreflang_xdefault_map_id'  => '0',
            'module_oc_kit_sitemap_generator_hreflang_missing_behavior' => 'use_default',
            'module_oc_kit_sitemap_generator_auto_generation'  => '0',
            'module_oc_kit_sitemap_generator_cron_schedule'    => 'daily',
            'module_oc_kit_sitemap_generator_custom_cron'      => '0 3 * * *',
        ];

        $data = [];
        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $data = $this->request->post;
        } else {
            foreach ($defaults as $key => $default) {
                $val = $this->config->get($key);
                $data[$key] = $val !== null ? $val : $default;
            }
            // Type configs
            $types = ['home', 'category', 'product', 'manufacturer', 'information',
                      'blog_post', 'blog_category', 'special', 'contact'];
            foreach ($types as $type) {
                $prefix = 'module_oc_kit_sitemap_generator_type_' . $type;
                $data[$prefix . '_enabled']    = $this->config->get($prefix . '_enabled')    ?? '0';
                $data[$prefix . '_changefreq'] = $this->config->get($prefix . '_changefreq') ?? 'weekly';
                $data[$prefix . '_priority']   = $this->config->get($prefix . '_priority')   ?? '0.5';
                $data[$prefix . '_lastmod']    = $this->config->get($prefix . '_lastmod')    ?? 'auto';
                $data[$prefix . '_max_urls']   = $this->config->get($prefix . '_max_urls')   ?? '0';
                $data[$prefix . '_sort_order'] = $this->config->get($prefix . '_sort_order') ?? '0';
            }
        }
        return $data;
    }

    private function buildLangStrings(): array
    {
        $keys = [
            'heading_title', 'text_edit', 'text_success',
            'tab_general', 'tab_language_maps', 'tab_content_types', 'tab_images',
            'tab_hreflang', 'tab_cron', 'tab_logs', 'tab_license',
            'button_save', 'button_cancel', 'button_generate', 'button_generate_map', 'button_generate_resizes',
            'button_add_map', 'button_edit', 'button_delete', 'button_copy',
            'button_clear_logs', 'button_delete_files', 'button_regenerate_key', 'button_activate',
            'text_generate_mode_static', 'text_generate_mode_dynamic',
            'text_writable', 'text_not_writable', 'text_robots_hint',
            'text_generated_files', 'text_no_files', 'text_not_generated',
            'text_maps_empty', 'text_add_map', 'text_edit_map',
            'text_type_home', 'text_type_category', 'text_type_product',
            'text_type_manufacturer', 'text_type_information',
            'text_type_blog_post', 'text_type_blog_category',
            'text_type_special', 'text_type_contact', 'text_not_installed',
            'text_lastmod_auto', 'text_lastmod_none', 'text_lastmod_fixed',
            'text_image_original', 'text_image_resized',
            'text_missing_skip', 'text_missing_use_default', 'text_xdefault_auto',
            'text_schedule_hourly', 'text_schedule_every6h', 'text_schedule_daily',
            'text_schedule_weekly', 'text_schedule_custom',
            'text_crontab_command', 'text_http_cron_url', 'text_http_cron_hint',
            'text_log_manual', 'text_log_cron', 'text_log_http',
            'text_log_success', 'text_log_error', 'text_log_running', 'text_logs_empty',
            'text_license_status_active', 'text_license_status_invalid',
            'text_license_status_not_validated', 'text_license_status_grace',
            'text_license_domain', 'text_license_version', 'text_license_enter_key', 'text_license_buy',
            'column_language', 'column_url_prefix', 'column_filename', 'column_hreflang',
            'column_xdefault', 'column_status', 'column_urls', 'column_last_generated',
            'column_actions', 'column_date', 'column_map', 'column_triggered_by',
            'column_files', 'column_duration', 'column_error', 'column_size', 'column_modified',
            'entry_status', 'entry_generation_mode', 'entry_output_directory',
            'entry_index_filename', 'entry_urls_per_file', 'entry_grouping_mode', 'entry_enable_gzip', 'entry_enable_xsl',
            'entry_dynamic_cache_ttl', 'entry_language', 'entry_url_prefix',
            'entry_filename', 'entry_hreflang_locale', 'entry_is_default', 'entry_map_status',
            'entry_changefreq', 'entry_priority', 'entry_lastmod', 'entry_max_urls',
            'entry_include_bottom', 'entry_include_images', 'entry_image_source',
            'entry_image_width', 'entry_image_height', 'entry_include_additional',
            'entry_max_images', 'entry_enable_hreflang', 'entry_xdefault_map',
            'entry_missing_translation', 'entry_auto_generation', 'entry_schedule',
            'entry_custom_cron', 'entry_cron_key', 'entry_license_key',
            'help_output_directory', 'help_output_directory_placeholder', 'help_nginx_subdir', 'help_cron_key',
            'help_index_filename', 'help_urls_per_file', 'help_grouping_mode', 'help_enable_xsl',
            'text_grouping_combined', 'text_grouping_by_type',
            'help_url_prefix', 'help_filename', 'help_dynamic_cache_ttl',
            'tab_faq',
            'faq_q_static_vs_dynamic', 'faq_a_static_vs_dynamic',
            'faq_q_cron_setup',        'faq_a_cron_setup',
            'faq_q_output_dir',        'faq_a_output_dir',
            'faq_q_hreflang',          'faq_a_hreflang',
            'faq_q_xdefault',          'faq_a_xdefault',
            'faq_q_url_limit',         'faq_a_url_limit',
            'faq_q_robots',            'faq_a_robots',
            'faq_q_gsc',               'faq_a_gsc',
            'faq_q_server_config',     'faq_a_server_config',
            'error_permission', 'error_generation_failed',
            'text_confirm_delete_files', 'text_confirm_delete_map', 'text_confirm_clear_logs',
            'text_last_generated', 'text_copied',
        ];

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->language->get($key);
        }
        return $data;
    }

    private function buildJsLang(): array
    {
        return [
            'generating'              => $this->language->get('text_generating'),
            'generate_success'        => $this->language->get('text_generate_success'),
            'generate_dry_run'        => $this->language->get('text_generate_dry_run'),
            'generate_error'          => $this->language->get('error_generation_failed'),
            'copied'                  => $this->language->get('text_copied'),
            'no_files'                => $this->language->get('text_no_files'),
            'add_map'                 => $this->language->get('text_add_map'),
            'edit_map'                => $this->language->get('text_edit_map'),
            'success'                 => $this->language->get('text_success'),
            'confirm_delete_files'    => $this->language->get('text_confirm_delete_files'),
            'confirm_delete_map'      => $this->language->get('text_confirm_delete_map'),
            'confirm_clear_logs'      => $this->language->get('text_confirm_clear_logs'),
            'confirm_regen_key'       => $this->language->get('text_confirm_regen_key'),
            'not_generated'           => $this->language->get('text_not_generated'),
            'error_language_required' => $this->language->get('error_language_required'),
            'error_filename_required' => $this->language->get('error_filename_required'),
            'log_manual'              => $this->language->get('text_log_manual'),
            'log_cron'                => $this->language->get('text_log_cron'),
            'log_http'                => $this->language->get('text_log_http'),
            'log_success'             => $this->language->get('text_log_success'),
            'log_error'               => $this->language->get('text_log_error'),
            'log_running'             => $this->language->get('text_log_running'),
            'logs_empty'              => $this->language->get('text_logs_empty'),
            'col_date'                => $this->language->get('column_date'),
            'col_map'                 => $this->language->get('column_map'),
            'col_triggered_by'        => $this->language->get('column_triggered_by'),
            'col_urls'                => $this->language->get('column_urls'),
            'col_files'               => $this->language->get('column_files'),
            'col_duration'            => $this->language->get('column_duration'),
            'col_status'              => $this->language->get('column_status'),
            'col_error'               => $this->language->get('column_error'),
            'col_language'            => $this->language->get('column_language'),
            'col_url_prefix'          => $this->language->get('column_url_prefix'),
            'col_filename'            => $this->language->get('column_filename'),
            'col_hreflang'            => $this->language->get('column_hreflang'),
            'col_xdefault'            => $this->language->get('column_xdefault'),
            'col_last_generated'      => $this->language->get('column_last_generated'),
            'col_actions'             => $this->language->get('column_actions'),
            'btn_generate_map'        => $this->language->get('button_generate_map'),
            'resizes_running'         => $this->language->get('text_resizes_running'),
            'resizes_done'            => $this->language->get('text_resizes_done'),
            'resizes_error'           => $this->language->get('text_resizes_error'),
        ];
    }

    private function jsonResponse(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    /**
     * Generate a URL safe for use in JavaScript contexts.
     * OC's url->link() produces &amp; separators intended for HTML output;
     * here we decode them back to plain & for use in JS strings / fetch().
     */
    private function jsUrl(string $route, string $args = ''): string
    {
        return str_replace('&amp;', '&', $this->url->link($route, $args, true));
    }
}
