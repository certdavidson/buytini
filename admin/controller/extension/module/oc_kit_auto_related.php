<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitAutoRelated extends Controller
{
    private const PREFIX = 'module_oc_kit_auto_related_';

    // ── License page ──────────────────────────────────────────────────────────

    public function license(): void
    {
        $this->load->language('extension/module/oc_kit_auto_related');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/auto-related/assets/js/admin.js');

        $licenseInfo = $this->getLicenseInfo();

        // If license is now active redirect to settings
        if (!empty($licenseInfo['valid'])) {
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_auto_related', 'user_token=' . $this->session->data['user_token'], true)
            );
            return;
        }

        $token = $this->session->data['user_token'];

        $data = array_merge($this->buildLangStrings(), [
            'license_info'    => $licenseInfo,
            'license_key'     => (string)($this->config->get(self::PREFIX . 'license_key') ?? ''),
            'license_url'     => $this->url->link('extension/module/oc_kit_auto_related/license',         'user_token=' . $token, true),
            'action_activate' => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/activateLicense', 'user_token=' . $token, true)),
            'extensions_url'  => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'lang_js'         => json_encode([
                'text_license_active'  => $this->language->get('text_license_active'),
                'text_license_invalid' => $this->language->get('text_license_invalid'),
            ]),
        ]);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/auto_related/license', $data));
    }

    // ── Main settings page ────────────────────────────────────────────────────

    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        $this->load->language('extension/module/oc_kit_auto_related');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/oc_kit_auto_related');
        $this->load->model('setting/setting');

        $this->model_extension_module_oc_kit_auto_related->install();

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/auto-related/assets/js/admin.js');

        $token = $this->session->data['user_token'];

        $this->load->model('localisation/language');
        $data = array_merge($this->buildLangStrings(), $this->buildFormData());
        $data['languages']   = $this->model_localisation_language->getLanguages();
        $data['lang_js']     = $this->buildJsLang();
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/auto_related/settings', $data));
    }

    // ── AJAX: save settings ───────────────────────────────────────────────────

    public function saveSettings(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_auto_related')) {
            $this->jsonResponse(['error' => $this->language->get('error_permission')]);
            return;
        }

        $this->load->language('extension/module/oc_kit_auto_related');
        $this->load->model('setting/setting');

        $post = $this->request->post;

        $settings = [
            self::PREFIX . 'status'           => (int)!empty($post[self::PREFIX . 'status']),
            self::PREFIX . 'related_limit'    => max(1, (int)($post[self::PREFIX . 'related_limit']    ?? 8)),
            self::PREFIX . 'overwrite'        => (int)!empty($post[self::PREFIX . 'overwrite']),
            self::PREFIX . 'on_visit'         => (int)!empty($post[self::PREFIX . 'on_visit']),
            self::PREFIX . 'visit_mode'       => in_array($post[self::PREFIX . 'visit_mode'] ?? '', ['async','sync']) ? $post[self::PREFIX . 'visit_mode'] : 'async',
            self::PREFIX . 'exclude_oos'      => (int)!empty($post[self::PREFIX . 'exclude_oos']),
            self::PREFIX . 'exclude_disabled' => (int)!empty($post[self::PREFIX . 'exclude_disabled']),
            self::PREFIX . 'cache'            => (int)!empty($post[self::PREFIX . 'cache']),
            self::PREFIX . 'cache_ttl'        => max(1, (int)($post[self::PREFIX . 'cache_ttl']        ?? 72)),
            self::PREFIX . 'candidate_limit'  => max(100, (int)($post[self::PREFIX . 'candidate_limit'] ?? 400)),

            // Weights
            self::PREFIX . 'weight_category'     => max(0, min(100, (int)($post[self::PREFIX . 'weight_category']     ?? 30))),
            self::PREFIX . 'weight_name'         => max(0, min(100, (int)($post[self::PREFIX . 'weight_name']         ?? 20))),
            self::PREFIX . 'weight_neighbor_id'  => max(0, min(100, (int)($post[self::PREFIX . 'weight_neighbor_id']  ?? 5))),
            self::PREFIX . 'weight_fields'       => max(0, min(100, (int)($post[self::PREFIX . 'weight_fields']       ?? 25))),
            self::PREFIX . 'weight_manufacturer' => max(0, min(100, (int)($post[self::PREFIX . 'weight_manufacturer'] ?? 20))),
            self::PREFIX . 'weight_attributes'   => max(0, min(100, (int)($post[self::PREFIX . 'weight_attributes']   ?? 30))),
            self::PREFIX . 'weight_coorders'     => max(0, min(100, (int)($post[self::PREFIX . 'weight_coorders']     ?? 40))),

            // Signal config
            self::PREFIX . 'neighbor_range'      => max(1, (int)($post[self::PREFIX . 'neighbor_range']     ?? 50)),
            self::PREFIX . 'field_list'          => array_intersect((array)($post[self::PREFIX . 'field_list'] ?? []), ['sku','mpn','ean','jan','isbn','upc']),
            self::PREFIX . 'field_separator'     => substr((string)($post[self::PREFIX . 'field_separator'] ?? ','), 0, 5),
            self::PREFIX . 'attribute_ids'       => array_map('intval', (array)($post[self::PREFIX . 'attribute_ids'] ?? [])),
            self::PREFIX . 'attribute_min_match' => max(1, (int)($post[self::PREFIX . 'attribute_min_match'] ?? 1)),
            self::PREFIX . 'coorders_days'       => max(1, (int)($post[self::PREFIX . 'coorders_days']       ?? 365)),
            self::PREFIX . 'coorders_min'        => max(1, (int)($post[self::PREFIX . 'coorders_min']        ?? 2)),
            self::PREFIX . 'coorders_statuses'   => array_map('intval', (array)($post[self::PREFIX . 'coorders_statuses'] ?? [])),

            // Price range signal
            self::PREFIX . 'weight_price_range'  => max(0, min(100, (int)($post[self::PREFIX . 'weight_price_range'] ?? 0))),
            self::PREFIX . 'price_range_pct'     => max(1, min(200, (int)($post[self::PREFIX . 'price_range_pct']   ?? 20))),

            // Result sort & only_special
            self::PREFIX . 'result_sort'         => in_array($post[self::PREFIX . 'result_sort'] ?? '', ['score','random','price_asc','price_desc','new','name'])
                ? $post[self::PREFIX . 'result_sort'] : 'score',
            self::PREFIX . 'only_special'        => (int)!empty($post[self::PREFIX . 'only_special']),

            // Brand priority & blacklist
            self::PREFIX . 'brand_priority'      => (int)!empty($post[self::PREFIX . 'brand_priority']),
            self::PREFIX . 'blacklist_products'  => array_map('intval', array_filter((array)($post[self::PREFIX . 'blacklist_products'] ?? []))),
            self::PREFIX . 'blacklist_categories'=> array_map('intval', array_filter((array)($post[self::PREFIX . 'blacklist_categories'] ?? []))),
        ];

        $this->model_setting_setting->editSetting('module_oc_kit_auto_related', $settings);

        $this->jsonResponse(['success' => $this->language->get('text_success')]);
    }

    // ── AJAX: preview ─────────────────────────────────────────────────────────

    public function preview(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_auto_related')) {
            $this->load->language('extension/module/oc_kit_auto_related');
            $this->jsonResponse(['error' => $this->language->get('error_permission')]);
            return;
        }

        $productId = (int)($this->request->get['product_id'] ?? 0);
        if (!$productId) {
            $this->jsonResponse(['error' => 'invalid_product_id']);
            return;
        }

        $lib    = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
        $result = $lib->previewRelated($productId);

        $this->jsonResponse($result);
    }

    // ── AJAX: Rule CRUD ──────────────────────────────────────────────────────

    public function listRules(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);
        $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
        $this->jsonResponse(['rules' => $lib->getRules()]);
    }

    public function saveRule(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_auto_related')) {
            $this->jsonResponse(['error' => $this->language->get('error_permission')]);
            return;
        }

        $this->load->language('extension/module/oc_kit_auto_related');
        $post = $this->request->post;

        // Decode JSON conditions sent from JS. OC runs htmlspecialchars() on
        // all POST values, so JSON quotes arrive encoded as &quot; — must be
        // decoded before json_decode. Field-level whitelisting is performed
        // downstream in RuleRepository::validateConditions().
        $srcRaw = $post['source_conditions'] ?? '[]';
        $tgtRaw = $post['target_conditions'] ?? '[]';
        $srcConds = is_array($srcRaw) ? $srcRaw : (json_decode(html_entity_decode((string)$srcRaw, ENT_QUOTES, 'UTF-8'), true, 8) ?: []);
        $tgtConds = is_array($tgtRaw) ? $tgtRaw : (json_decode(html_entity_decode((string)$tgtRaw, ENT_QUOTES, 'UTF-8'), true, 8) ?: []);
        if (!is_array($srcConds)) { $srcConds = []; }
        if (!is_array($tgtConds)) { $tgtConds = []; }
        // Cap array size to avoid runaway processing
        $srcConds = array_slice($srcConds, 0, 32);
        $tgtConds = array_slice($tgtConds, 0, 32);

        $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
        try {
            $ruleId = $lib->saveRule([
                'rule_id'           => (int)($post['rule_id'] ?? 0),
                'name'              => (string)($post['name'] ?? ''),
                'status'            => (int)!empty($post['status']),
                'sort_order'        => (int)($post['sort_order'] ?? 0),
                'block_title'       => is_array($post['block_title'] ?? null) ? $post['block_title'] : [],
                'result_limit'      => max(1, min(50, (int)($post['result_limit'] ?? 8))),
                'result_sort'       => $post['result_sort'] ?? 'random',
                'source_conditions' => $srcConds,
                'target_conditions' => $tgtConds,
            ]);
        } catch (\OcKit\AutoRelated\Exceptions\AutoRelatedException $e) {
            $this->jsonResponse(['error' => $e->getMessage()]);
            return;
        }

        $this->jsonResponse([
            'success' => $this->language->get('text_rule_saved'),
            'rule_id' => $ruleId,
        ]);
    }

    public function deleteRule(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_auto_related')) {
            $this->jsonResponse(['error' => $this->language->get('error_permission')]);
            return;
        }

        $this->load->language('extension/module/oc_kit_auto_related');
        $ruleId = (int)($this->request->post['rule_id'] ?? 0);

        if ($ruleId > 0) {
            $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
            $lib->deleteRule($ruleId);
        }

        $this->jsonResponse(['success' => $this->language->get('text_rule_deleted')]);
    }

    // ── AJAX: product autocomplete ────────────────────────────────────────────

    public function autocompleteProduct(): void
    {
        $this->load->model('catalog/product');
        $term    = $this->db->escape($this->request->get['term'] ?? '');
        $langId  = (int)$this->config->get('config_language_id');

        $result = $this->db->query(
            "SELECT p.product_id, pd.name
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON (pd.product_id = p.product_id AND pd.language_id = " . $langId . ")
             WHERE pd.name LIKE '%" . $term . "%'
             ORDER BY pd.name ASC
             LIMIT 20"
        );

        $out = [];
        foreach ($result->rows as $r) {
            $out[] = ['id' => (int)$r['product_id'], 'label' => '[' . $r['product_id'] . '] ' . $r['name']];
        }
        $this->jsonResponse($out);
    }

    // ── AJAX: batch generate ──────────────────────────────────────────────────

    public function generate(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        \OcKit\AutoRelated\AutoRelated::guardAdmin($this->registry);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_auto_related')) {
            $this->jsonResponse(['error' => $this->language->get('error_permission')]);
            return;
        }

        $this->load->language('extension/module/oc_kit_auto_related');

        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);

        $post       = $this->request->post;
        $batchSize  = max(1, min(100, (int)($post['batch_size'] ?? 20)));
        $offset     = max(0, (int)($post['offset'] ?? 0));
        $filters    = [
            'id_from'       => $post['id_from']       ?? '',
            'id_to'         => $post['id_to']         ?? '',
            'categories'    => (array)($post['categories']    ?? []),
            'manufacturers' => (array)($post['manufacturers'] ?? []),
            'overwrite'     => !empty($post['overwrite']),
        ];

        $result = $lib->generateBatch($filters, $batchSize, $offset);

        $this->jsonResponse($result);
    }

    // ── AJAX: stats ───────────────────────────────────────────────────────────

    public function stats(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);

        $this->jsonResponse([
            'summary'      => $lib->getStats(),
            'recent'       => $lib->getRecentLog(10),
            'distribution' => $lib->getSourceDistribution(),
            'trend'        => $lib->getDailyTrend(30),
        ]);
    }

    // ── AJAX: autocomplete helpers ────────────────────────────────────────────

    public function autocompleteCategory(): void
    {
        $this->load->model('catalog/category');
        $term    = $this->db->escape($this->request->get['term'] ?? '');
        $results = $this->model_catalog_category->getCategories(['filter_name' => $term, 'start' => 0, 'limit' => 20]);
        $out = [];
        foreach ($results as $r) {
            $out[] = ['id' => $r['category_id'], 'label' => html_entity_decode(strip_tags($r['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8')];
        }
        $this->jsonResponse($out);
    }

    public function autocompleteManufacturer(): void
    {
        $this->load->model('catalog/manufacturer');
        $term    = $this->db->escape($this->request->get['term'] ?? '');
        $results = $this->model_catalog_manufacturer->getManufacturers(['filter_name' => $term, 'start' => 0, 'limit' => 20]);
        $out = [];
        foreach ($results as $r) {
            $out[] = ['id' => $r['manufacturer_id'], 'label' => $r['name']];
        }
        $this->jsonResponse($out);
    }

    public function autocompleteAttribute(): void
    {
        $this->load->model('catalog/attribute');
        $term    = $this->db->escape($this->request->get['term'] ?? '');
        $results = $this->model_catalog_attribute->getAttributes(['filter_name' => $term, 'start' => 0, 'limit' => 30]);
        $out = [];
        foreach ($results as $r) {
            $out[] = ['id' => $r['attribute_id'], 'label' => html_entity_decode(strip_tags($r['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8')];
        }
        $this->jsonResponse($out);
    }

    // ── License helpers ───────────────────────────────────────────────────────

    public function activateLicense(): void
    {
        $this->load->language('extension/module/oc_kit_auto_related');

        $key = trim((string)($this->request->post['license_key'] ?? ''));

        if (!$key) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('text_license_not_validated')]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        $result = \OcKit\AutoRelated\AutoRelated::activateLicenseKey($this->registry, $key);

        $this->jsonResponse([
            'success'      => $result['success'],
            'message'      => $result['success']
                ? $this->language->get('text_license_active')
                : $this->language->get('text_license_invalid'),
            'info'         => $result['info'],
            'redirect_url' => $result['success']
                ? html_entity_decode($this->url->link('extension/module/oc_kit_auto_related', 'user_token=' . $this->session->data['user_token'], true))
                : '',
        ]);
    }

    private function getLicenseInfo(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        return \OcKit\AutoRelated\AutoRelated::getLicenseStatus($this->registry);
    }

    // ── Install / Uninstall ───────────────────────────────────────────────────

    public function install(): void
    {
        $this->load->model('extension/module/oc_kit_auto_related');
        $this->model_extension_module_oc_kit_auto_related->install();
    }

    public function uninstall(): void
    {
        $this->load->model('extension/module/oc_kit_auto_related');
        $this->model_extension_module_oc_kit_auto_related->uninstall();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildFormData(): array
    {
        $this->load->model('localisation/order_status');
        $this->load->model('localisation/language');

        $c = fn(string $key, $default = '') =>
            $this->config->get(self::PREFIX . $key) ?? $default;

        $token = $this->session->data['user_token'];

        return [
            'heading_title' => $this->language->get('heading_title'),

            // General
            self::PREFIX . 'status'           => $c('status', 0),
            self::PREFIX . 'related_limit'    => $c('related_limit', 8),
            self::PREFIX . 'overwrite'        => $c('overwrite', 0),
            self::PREFIX . 'on_visit'         => $c('on_visit', 0),
            self::PREFIX . 'visit_mode'       => $c('visit_mode', 'async'),
            self::PREFIX . 'exclude_oos'      => $c('exclude_oos', 1),
            self::PREFIX . 'exclude_disabled' => $c('exclude_disabled', 1),
            self::PREFIX . 'cache'            => $c('cache', 1),
            self::PREFIX . 'cache_ttl'        => $c('cache_ttl', 72),
            self::PREFIX . 'candidate_limit'  => $c('candidate_limit', 400),

            // Weights
            self::PREFIX . 'weight_category'     => $c('weight_category', 30),
            self::PREFIX . 'weight_name'         => $c('weight_name', 20),
            self::PREFIX . 'weight_neighbor_id'  => $c('weight_neighbor_id', 5),
            self::PREFIX . 'weight_fields'       => $c('weight_fields', 25),
            self::PREFIX . 'weight_manufacturer' => $c('weight_manufacturer', 20),
            self::PREFIX . 'weight_attributes'   => $c('weight_attributes', 30),
            self::PREFIX . 'weight_coorders'     => $c('weight_coorders', 40),

            // Signal config
            self::PREFIX . 'neighbor_range'      => $c('neighbor_range', 50),
            self::PREFIX . 'field_list'          => (array)($this->config->get(self::PREFIX . 'field_list') ?? ['sku', 'mpn']),
            self::PREFIX . 'field_separator'     => $c('field_separator', ','),
            self::PREFIX . 'attribute_ids'       => (array)($this->config->get(self::PREFIX . 'attribute_ids') ?? []),
            self::PREFIX . 'attribute_min_match' => $c('attribute_min_match', 1),
            self::PREFIX . 'coorders_days'       => $c('coorders_days', 365),
            self::PREFIX . 'coorders_min'        => $c('coorders_min', 2),
            self::PREFIX . 'coorders_statuses'   => (array)($this->config->get(self::PREFIX . 'coorders_statuses') ?? []),

            // Price range
            self::PREFIX . 'weight_price_range'  => $c('weight_price_range', 0),
            self::PREFIX . 'price_range_pct'     => $c('price_range_pct', 20),

            // Result sort & only_special
            self::PREFIX . 'result_sort'         => $c('result_sort', 'score'),
            self::PREFIX . 'only_special'        => $c('only_special', 0),

            // Brand priority & blacklist
            self::PREFIX . 'brand_priority'      => $c('brand_priority', 0),
            self::PREFIX . 'blacklist_products'  => (array)($this->config->get(self::PREFIX . 'blacklist_products')   ?? []),
            self::PREFIX . 'blacklist_categories'=> (array)($this->config->get(self::PREFIX . 'blacklist_categories') ?? []),

            'order_statuses' => $this->model_localisation_order_status->getOrderStatuses(),
            'cron_path'      => realpath(DIR_APPLICATION . '../crons/cron_auto_related.php'),

            // URLs (html_entity_decode: OC url->link() returns &amp; which breaks JS fetch)
            'action_save'                => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/saveSettings',           'user_token=' . $token, true)),
            'action_generate'            => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/generate',               'user_token=' . $token, true)),
            'action_stats'               => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/stats',                  'user_token=' . $token, true)),
            'action_autocomplete_cat'    => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/autocompleteCategory',    'user_token=' . $token, true)),
            'action_autocomplete_mf'     => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/autocompleteManufacturer','user_token=' . $token, true)),
            'action_autocomplete_attr'   => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/autocompleteAttribute',   'user_token=' . $token, true)),
            'action_autocomplete_product'=> html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/autocompleteProduct',     'user_token=' . $token, true)),
            'action_preview'             => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/preview',                 'user_token=' . $token, true)),
            'action_list_rules'          => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/listRules',               'user_token=' . $token, true)),
            'action_save_rule'           => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/saveRule',                'user_token=' . $token, true)),
            'action_delete_rule'         => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/deleteRule',              'user_token=' . $token, true)),
            'cancel_url'                 => $this->url->link('marketplace/extension',                                       'user_token=' . $token . '&type=module', true),
            'license_url'                => $this->url->link('extension/module/oc_kit_auto_related/license',         'user_token=' . $token, true),
            'action_activate'            => html_entity_decode($this->url->link('extension/module/oc_kit_auto_related/activateLicense', 'user_token=' . $token, true)),
            'license_info'               => $this->getLicenseInfo(),
            'license_key'                => (string)($this->config->get(self::PREFIX . 'license_key') ?? ''),

            'breadcrumbs' => [
                ['text' => $this->language->get('text_home'),      'href' => $this->url->link('common/dashboard',       'user_token=' . $token, true)],
                ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension',  'user_token=' . $token . '&type=module', true)],
                ['text' => $this->language->get('heading_title'),  'href' => $this->url->link('extension/module/oc_kit_auto_related', 'user_token=' . $token, true)],
            ],
        ];
    }

    private function buildLangStrings(): array
    {
        $keys = [
            'heading_title', 'text_home', 'text_extension', 'text_success',
            'text_settings', 'button_save', 'button_cancel',
            'tab_general', 'tab_weights', 'tab_performance', 'tab_generate', 'tab_stats', 'tab_rules',

            // General
            'entry_status', 'entry_related_limit', 'entry_overwrite', 'entry_on_visit',
            'entry_visit_mode', 'entry_visit_mode_async', 'entry_visit_mode_sync',
            'entry_exclude_oos', 'entry_exclude_disabled', 'entry_cache', 'entry_cache_ttl',

            // Result sort & only_special
            'entry_result_sort',
            'entry_result_sort_score', 'entry_result_sort_random',
            'entry_result_sort_price_asc', 'entry_result_sort_price_desc',
            'entry_result_sort_new', 'entry_result_sort_name',
            'entry_only_special', 'text_only_special_help',

            // General extras
            'entry_brand_priority', 'text_brand_priority_help',
            'entry_blacklist_products', 'entry_blacklist_categories', 'text_blacklist_help',

            // Presets
            'text_presets', 'text_preset_balanced', 'text_preset_coorders',
            'text_preset_category', 'text_preset_variants', 'text_preset_help',

            // Weights
            'entry_weight_category', 'entry_weight_name', 'entry_weight_neighbor_id',
            'entry_weight_fields', 'entry_weight_manufacturer', 'entry_weight_attributes',
            'entry_weight_coorders', 'entry_weight_price_range',
            'entry_neighbor_enabled', 'entry_neighbor_range', 'entry_field_list',
            'entry_field_separator', 'entry_attribute_ids', 'entry_attribute_min_match',
            'entry_coorders_days', 'entry_coorders_min', 'entry_coorders_statuses',
            'entry_price_range_pct', 'text_price_range_pct_help',
            'text_weights_help', 'text_field_separator_help', 'text_coorders_statuses_help',

            // Performance
            'entry_candidate_limit', 'text_candidate_limit_help',
            'text_cron', 'text_cron_command', 'text_cron_schedule', 'text_cron_all',
            'text_cron_daily_2', 'text_cron_daily_3', 'text_cron_daily_4',
            'text_cron_every_6h', 'text_cron_every_1h',
            'text_cron_param_limit', 'text_cron_param_force', 'text_cron_param_category', 'text_cron_param_mf',

            // Generate + Preview
            'entry_id_from', 'entry_id_to', 'entry_gen_categories', 'entry_gen_manufacturers',
            'entry_gen_overwrite', 'button_generate', 'button_stop',
            'text_processed', 'text_of', 'text_generating', 'text_done',
            'tab_preview', 'text_preview_product', 'button_preview',
            'text_preview_results', 'column_preview_score', 'text_preview_empty', 'text_no_results',

            // Stats
            'text_total_products', 'text_with_related', 'text_coverage', 'text_without_related',
            'text_recent_generated', 'column_product', 'column_generated_at', 'column_source', 'column_count',
            'source_cron', 'source_visit', 'source_manual',

            // Fields
            'field_sku', 'field_mpn', 'field_ean', 'field_jan', 'field_isbn', 'field_upc',

            'error_permission',

            // Rule Builder
            'text_rules_intro', 'button_add_rule', 'button_edit_rule', 'button_delete_rule',
            'button_save_rule', 'button_cancel_rule',
            'column_rule_name', 'column_rule_source', 'column_rule_target',
            'column_rule_sort', 'column_rule_status', 'column_rule_actions',
            'entry_rule_name', 'entry_rule_status', 'entry_rule_sort_order',
            'entry_rule_block_title', 'entry_rule_result_limit', 'entry_rule_result_sort',
            'entry_result_sort_bestseller',
            'text_no_rules', 'confirm_delete_rule', 'text_rule_saved', 'text_rule_deleted',
            // Constructor: source conditions
            'text_source_conditions', 'text_source_conditions_help', 'button_add_source_cond',
            'cond_src_category', 'cond_src_manufacturer', 'cond_src_attribute', 'cond_src_name_contains',
            // Constructor: target conditions
            'text_target_conditions', 'text_target_conditions_help', 'button_add_target_cond',
            'cond_tgt_same_category', 'cond_tgt_same_manufacturer',
            'cond_tgt_category', 'cond_tgt_manufacturer',
            'cond_tgt_attribute', 'cond_tgt_dynamic_attribute',
            'cond_tgt_name_contains', 'cond_tgt_price_range',
            'cond_tgt_only_special', 'cond_tgt_exclude_oos', 'cond_tgt_brand_priority',
            // Condition field labels
            'entry_cond_attribute_id', 'entry_cond_attribute_value',
            'entry_cond_price_pct', 'entry_cond_name_text', 'entry_cond_ids_placeholder',
            'text_cond_same_cat_help', 'text_cond_same_mf_help',
            'text_cond_dyn_attr_help', 'text_cond_brand_priority_help',
            'text_cond_only_special_help', 'text_cond_exclude_oos_help',

            // License
            'tab_license', 'entry_license_key', 'button_activate',
            'text_license_not_validated', 'text_license_invalid', 'text_license_active',
            'text_license_expired', 'text_license_trial', 'text_license_api_error',
            'text_license_grace', 'text_license_version', 'text_license_buy',
        ];

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->language->get($key);
        }
        return $out;
    }

    private function buildJsLang(): array
    {
        $keys = [
            'text_processed', 'text_of', 'text_generating', 'text_done',
            'error_permission',
            'text_no_results', 'text_preview_product', 'text_preview_empty', 'button_preview',
            // Rule builder
            'button_edit_rule', 'button_delete_rule', 'button_save_rule', 'button_cancel_rule',
            'button_add_rule', 'text_no_rules', 'confirm_delete_rule',
            'text_rule_saved', 'text_rule_deleted',
            // Source condition types
            'cond_src_category', 'cond_src_manufacturer', 'cond_src_attribute', 'cond_src_name_contains',
            // Target condition types
            'cond_tgt_same_category', 'cond_tgt_same_manufacturer',
            'cond_tgt_category', 'cond_tgt_manufacturer',
            'cond_tgt_attribute', 'cond_tgt_dynamic_attribute',
            'cond_tgt_name_contains', 'cond_tgt_price_range',
            'cond_tgt_only_special', 'cond_tgt_exclude_oos', 'cond_tgt_brand_priority',
            // Condition helpers
            'text_cond_same_cat_help', 'text_cond_same_mf_help',
            'text_cond_dyn_attr_help', 'text_cond_brand_priority_help',
            'text_cond_only_special_help', 'text_cond_exclude_oos_help',
            'entry_cond_attribute_id', 'entry_cond_attribute_value',
            'entry_cond_price_pct', 'entry_cond_name_text',
            'entry_cond_ids_placeholder',
            // Source/target section labels
            'text_source_conditions', 'text_source_conditions_help',
            'text_target_conditions', 'text_target_conditions_help',
            'button_add_source_cond', 'button_add_target_cond',
            // Condition rule column labels
            'column_rule_sort', 'column_rule_name', 'column_rule_source',
            'column_rule_target', 'column_rule_status', 'column_rule_actions',
        ];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->language->get($key);
        }
        return $out;
    }

    private function jsonResponse(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
}
