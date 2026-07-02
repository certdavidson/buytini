<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/easycheckout/EasyCheckout.php';

use OcKit\EasyCheckout\EasyCheckout;
use OcKit\EasyCheckout\Libs\FieldRegistry;
use OcKit\EasyCheckout\Libs\ConfigStore;
use OcKit\EasyCheckout\Exceptions\ValidationException;

class ControllerExtensionModuleOcKitEasycheckout extends Controller
{
    private array $error = [];

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function install(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->install();
    }

    public function uninstall(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->uninstall();
    }

    // ─── Single page with SPA-style tabs ─────────────────────────────────────

    /**
     * Єдиний адмін-роут: рендерить layout з усіма секціями. Секцію визначає
     * GET-параметр `section` (default: general). Перемикання — на клієнті
     * через Alpine + history.pushState. Бекенд завжди віддає всі секції.
     */
    public function index(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->load->model('localisation/language');
        $this->load->model('setting/setting');

        $this->loadAssets();

        // Schema migrations — idempotent, auto-applied на кожну admin-сесію.
        // Cheap (INFORMATION_SCHEMA per migration); skip silently якщо вже applied.
        (new EasyCheckout($this->registry))->getSchemaInstaller()->migrate();

        // ── Save settings (POST) ─────────────────────────────────────────
        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            // Multilang reminder texts — окремий storage (oc_kit_easycheckout_settings),
            // витягуємо з POST щоб не потрапили у setting-table.
            $reminderSubject = is_array($this->request->post['reminder_subject'] ?? null) ? $this->request->post['reminder_subject'] : [];
            $reminderBody    = is_array($this->request->post['reminder_body']    ?? null) ? $this->request->post['reminder_body']    : [];
            unset($this->request->post['reminder_subject'], $this->request->post['reminder_body']);

            // Preserve license_key/cache through editSetting — read from config (not POST)
            // so that saving any other section never wipes the license.
            if (!isset($this->request->post['module_oc_kit_easycheckout_license_key'])) {
                $this->request->post['module_oc_kit_easycheckout_license_key'] =
                    (string)$this->config->get('module_oc_kit_easycheckout_license_key');
            }
            if (!isset($this->request->post['module_oc_kit_easycheckout_license_cache'])) {
                $cache = $this->config->get('module_oc_kit_easycheckout_license_cache');
                if ($cache !== null && $cache !== '') {
                    $this->request->post['module_oc_kit_easycheckout_license_cache'] = $cache;
                }
            }
            if (!isset($this->request->post['module_oc_kit_easycheckout_trial_start'])) {
                $trial = $this->config->get('module_oc_kit_easycheckout_trial_start');
                if ($trial !== null && $trial !== '') {
                    $this->request->post['module_oc_kit_easycheckout_trial_start'] = $trial;
                }
            }

            $this->model_setting_setting->editSetting('module_oc_kit_easycheckout', $this->request->post);

            // OC postsanitizer додає htmlspecialchars — повертаємо HTML-теги для body
            $reminderBody = array_map(static fn($v) => html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $reminderBody);
            $reminderSubject = array_map(static fn($v) => html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $reminderSubject);

            $this->load->model('extension/module/oc_kit_easycheckout');
            $this->model_extension_module_oc_kit_easycheckout->saveReminderTexts($reminderSubject, $reminderBody);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(
                'extension/module/oc_kit_easycheckout',
                'user_token=' . $this->session->data['user_token']
                    . (isset($this->request->get['section']) ? '&section=' . $this->request->get['section'] : ''),
                true
            ));
            return;
        }

        $token            = $this->session->data['user_token'];
        $languages        = array_values($this->model_localisation_language->getLanguages());
        $sectionInitial   = (string)($this->request->get['section'] ?? 'general');
        $allowedSections  = ['general', 'pages', 'fields', 'headings', 'misc', 'groups', 'abandoned', 'presets', 'license', 'health', 'address_formats', 'restrictions', 'modules', 'js', 'integrations'];
        if (!in_array($sectionInitial, $allowedSections, true)) {
            $sectionInitial = 'general';
        }

        $fieldTypes = $this->buildFieldTypesForJs();
        $fieldsList = $this->buildFieldsListForJs();

        // Initial groups list + active (default) — для group-selector в header
        $ec = new EasyCheckout($this->registry);
        $ec->getGroupsRepository()->ensureDefault();
        $groupsList = $ec->getGroupsRepository()->list();
        $activeGroup = $ec->getGroupsRepository()->getDefault();
        $activeGroupId = $activeGroup ? (int)$activeGroup['group_id'] : 0;

        $data = array_merge(
            $this->buildLangStrings(),
            $this->buildLayoutData(),
            $this->buildGeneralFormData(),
            [
                'section_initial'  => $sectionInitial,
                'license_info'     => \OcKit\EasyCheckout\EasyCheckoutGuard::getInfo($this->registry),
                'license_key'      => (string)($this->config->get('module_oc_kit_easycheckout_license_key') ?? ''),
                'cron_last_run'    => $this->readCronLastRun(),
                'cron_path'        => realpath(DIR_APPLICATION . '../crons/cron_easycheckout_reminder.php') ?: '/path/to/crons/cron_easycheckout_reminder.php',
                'languages'        => $languages,
                'field_types'      => $fieldTypes,
                'field_types_json' => json_encode($fieldTypes,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'field_type_groups'=> $this->buildFieldTypeGroups(),
                'languages_json'   => json_encode($languages,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'belongs_to'       => FieldRegistry::BELONGS_TO,
                'url_list'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldList',       'user_token=' . $token, true)),
                'url_get'          => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldGet',        'user_token=' . $token, true)),
                'url_save'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldSave',       'user_token=' . $token, true)),
                'url_delete'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldDelete',     'user_token=' . $token, true)),
                'url_delete_many'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldDeleteMany', 'user_token=' . $token, true)),
                'url_next_code'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldNextCode',   'user_token=' . $token, true)),
                'url_clone'        => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldClone',      'user_token=' . $token, true)),
                'url_fields_export'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldsExport',    'user_token=' . $token, true)),
                'url_fields_import'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldsImport',    'user_token=' . $token, true)),
                'url_fields_reorder'=>html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldsReorder',   'user_token=' . $token, true)),
                'url_fields_bulk_edit'=>html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldsBulkEdit',  'user_token=' . $token, true)),
                'url_native_list'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/nativeFieldList', 'user_token=' . $token, true)),
                'url_native_save'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/nativeFieldSave', 'user_token=' . $token, true)),
                'url_h_reorder'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingsReorder', 'user_token=' . $token, true)),
                'url_fields_list'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldsListForLayout', 'user_token=' . $token, true)),
                // Groups
                'url_g_list'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/groupList',   'user_token=' . $token, true)),
                'url_g_save'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/groupSave',   'user_token=' . $token, true)),
                'url_g_delete'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/groupDelete', 'user_token=' . $token, true)),
                'url_g_clone'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/groupClone',  'user_token=' . $token, true)),
                'url_self'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout',                 'user_token=' . $token, true)),
                // Headings
                'url_h_list'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingList',       'user_token=' . $token, true)),
                'url_h_get'        => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingGet',        'user_token=' . $token, true)),
                'url_h_save'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingSave',       'user_token=' . $token, true)),
                'url_h_delete'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingDelete',     'user_token=' . $token, true)),
                'url_h_delete_many'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingDeleteMany', 'user_token=' . $token, true)),
                'url_h_next_code'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingNextCode',   'user_token=' . $token, true)),
                'url_h_clone'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingClone',      'user_token=' . $token, true)),
                'url_h_export'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingsExport',     'user_token=' . $token, true)),
                'url_h_import'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/headingsImport',     'user_token=' . $token, true)),
                'url_info_search'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/informationSearch', 'user_token=' . $token, true)),
                // Page layout
                'url_layout_get'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutGet',      'user_token=' . $token, true)),
                'url_layout_save'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutSave',     'user_token=' . $token, true)),
                'url_layout_defaults' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutDefaults', 'user_token=' . $token, true)),
                'url_abandoned_list'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedList',      'user_token=' . $token, true)),
                'url_abandoned_delete'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedDelete',    'user_token=' . $token, true)),
                'url_abandoned_delete_many'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedDeleteMany', 'user_token=' . $token, true)),
                'url_abandoned_products'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedProducts',   'user_token=' . $token, true)),
                'url_abandoned_save_note' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedSaveNote',  'user_token=' . $token, true)),
                'url_abandoned_send_reminder' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/abandonedSendReminder', 'user_token=' . $token, true)),
                'url_orders_export'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/ordersExport',       'user_token=' . $token, true)),
                'url_reminder_test'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/reminderTest',       'user_token=' . $token, true)),
                'url_presets_list'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldPresetsList',   'user_token=' . $token, true)),
                'url_preset_apply'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/fieldPresetApply',   'user_token=' . $token, true)),
                'url_layout_preview'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/previewToken',       'user_token=' . $token, true)),
                'url_settings_export' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/settingsExportAll', 'user_token=' . $token, true)),
                'url_settings_import' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/settingsImportAll', 'user_token=' . $token, true)),
                'url_layout_export'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutExport',  'user_token=' . $token, true)),
                'url_layout_import'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutImport',  'user_token=' . $token, true)),
                'url_layout_copy_from'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/pageLayoutCopyFrom','user_token=' . $token, true)),
                'url_health_check'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/healthCheck',        'user_token=' . $token, true)),
                'url_layout_presets_list' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/layoutPresetsList', 'user_token=' . $token, true)),
                'url_layout_preset_apply' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/layoutPresetApply', 'user_token=' . $token, true)),
                'url_address_formats_list' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/addressFormatsList', 'user_token=' . $token, true)),
                'url_address_format_save'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/addressFormatSave',  'user_token=' . $token, true)),
                'url_address_format_delete'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/addressFormatDelete','user_token=' . $token, true)),
                'url_restrictions_list'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/restrictionsList',   'user_token=' . $token, true)),
                'url_restriction_save'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/restrictionSave',    'user_token=' . $token, true)),
                'url_restriction_delete'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/restrictionDelete',  'user_token=' . $token, true)),
                'url_license_info'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/licenseInfo',       'user_token=' . $token, true)),
                'url_license_activate'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/licenseActivate',   'user_token=' . $token, true)),
                'url_modules_list'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/modulesList',       'user_token=' . $token, true)),
                'url_module_override_save' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/moduleOverrideSave', 'user_token=' . $token, true)),
                'url_cm_data'        => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodsData',    'user_token=' . $token, true)),
                'url_cm_group_add'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodGroupAdd', 'user_token=' . $token, true)),
                'url_cm_group_delete'=> html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodGroupDelete', 'user_token=' . $token, true)),
                'url_cm_add'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodAdd',      'user_token=' . $token, true)),
                'url_cm_get'         => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodGet',      'user_token=' . $token, true)),
                'url_cm_save'        => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodSave',     'user_token=' . $token, true)),
                'url_cm_delete'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodDelete',   'user_token=' . $token, true)),
                'url_cm_toggle'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodToggle',   'user_token=' . $token, true)),
                'url_cm_clone'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customMethodClone',    'user_token=' . $token, true)),
                'url_cm_subtotal_add'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customSubtotalAdd',    'user_token=' . $token, true)),
                'url_cm_subtotal_save'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customSubtotalSave',   'user_token=' . $token, true)),
                'url_cm_subtotal_delete' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/customSubtotalDelete', 'user_token=' . $token, true)),
                'url_integrations_list'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationsList',    'user_token=' . $token, true)),
                'url_integration_get'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationGet',      'user_token=' . $token, true)),
                'url_integration_toggle'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationToggle',   'user_token=' . $token, true)),
                'url_integration_save'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationSettingsSave', 'user_token=' . $token, true)),
                'url_integration_action'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationRunAction',   'user_token=' . $token, true)),
                'url_integration_refresh'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationRefresh',    'user_token=' . $token, true)),
                'url_integration_purge'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationPurge',      'user_token=' . $token, true)),
                'url_integration_icon'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationIcon',     'user_token=' . $token, true)),
                'url_integration_health'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationHealth',   'user_token=' . $token, true)),
                'url_integration_add_to_layout' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationAddToLayout', 'user_token=' . $token, true)),
                'url_integration_license_activate' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationLicenseActivate', 'user_token=' . $token, true)),
                'url_integration_install_fields' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationInstallFields', 'user_token=' . $token, true)),
                'url_marketplace_list'      => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/marketplaceList',      'user_token=' . $token, true)),
                'url_marketplace_install'   => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/marketplaceInstall',   'user_token=' . $token, true)),
                'url_marketplace_uninstall' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/marketplaceUninstall', 'user_token=' . $token, true)),
                'url_marketplace_update'    => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/marketplaceUpdate',    'user_token=' . $token, true)),
                'block_types'      => $this->buildBlockTypesForJs(),
                'fields_list'      => $fieldsList,
                'groups_list'      => $groupsList,
                'active_group_id'  => $activeGroupId,
                'primary_lang_code'=> (string)$this->config->get('config_language'),
                'heading_tags'     => \OcKit\EasyCheckout\Libs\HeadingsRepository::VALID_TAGS,
                'layout_presets'   => \OcKit\EasyCheckout\Libs\LayoutPresets::listAll(),
            ]
        );

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(
            'extension/module/ockit/easycheckout/layout',
            $data
        ));
    }

    public function fieldList(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        $this->load->model('extension/module/oc_kit_easycheckout');

        $filter = [
            'search'     => (string)($this->request->get['search']     ?? ''),
            'type'       => (string)($this->request->get['type']       ?? ''),
            'belongs_to' => (string)($this->request->get['belongs_to'] ?? ''),
            'sort'       => (string)($this->request->get['sort']       ?? 'field_id'),
            'order'      => (string)($this->request->get['order']      ?? 'DESC'),
            'start'      => (int)   ($this->request->get['start']      ?? 0),
            'limit'      => max(1, min(200, (int)($this->request->get['limit'] ?? 50))),
        ];

        $items = $this->model_extension_module_oc_kit_easycheckout->listFields($filter);
        $total = $this->model_extension_module_oc_kit_easycheckout->countFields($filter);

        // Збагачуємо order/layout-usage stats (2 single-queries, не per-row N+1)
        $orderUsage  = $this->model_extension_module_oc_kit_easycheckout->getFieldUsageInOrders();
        $layoutUsage = $this->model_extension_module_oc_kit_easycheckout->getFieldUsageInLayouts();
        foreach ($items as &$item) {
            $item['orders_count']  = (int)($orderUsage[(string)$item['code']]   ?? 0);
            $item['layouts_count'] = (int)($layoutUsage[(int)$item['field_id']] ?? 0);
        }
        unset($item);

        // Post-filter:
        //   used     — є в completed-замовленнях
        //   layouts  — є в layouts, але ще не в замовленнях
        //   unused   — повноцінно мертвий (нема ні там, ні там)
        //   all      — без фільтра
        $usageFilter = (string)($this->request->get['usage'] ?? 'all');
        if (in_array($usageFilter, ['used', 'unused', 'layouts'], true)) {
            $items = array_values(array_filter($items, static function ($i) use ($usageFilter) {
                $o = $i['orders_count']  ?? 0;
                $l = $i['layouts_count'] ?? 0;
                if ($usageFilter === 'used')    return $o > 0;
                if ($usageFilter === 'unused')  return $o === 0 && $l === 0;
                if ($usageFilter === 'layouts') return $o === 0 && $l >  0;
                return true;
            }));
        }

        $this->jsonResponse([
            'success' => true,
            'items'   => $items,
            'total'   => $total,
        ]);
    }

    public function fieldGet(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id = (int)($this->request->get['field_id'] ?? $this->request->post['field_id'] ?? 0);
        $field = $id ? $this->model_extension_module_oc_kit_easycheckout->getField($id) : null;
        $this->jsonResponse(['success' => (bool)$field, 'field' => $field]);
    }

    public function fieldSave(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');

        $payload = $this->request->post;
        $id = (int)($payload['field_id'] ?? 0);
        unset($payload['field_id']);

        // OpenCart автоматично проганяє POST через htmlspecialchars (XSS-захист),
        // через що " у JSON-рядках стає &quot;. Перед json_decode повертаємо як було.
        $decodeJson = function ($value) {
            if (!is_string($value) || $value === '') return [];
            $clean = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $arr = json_decode($clean, true);
            return is_array($arr) ? $arr : [];
        };
        if (isset($payload['descriptions'])) {
            $payload['descriptions'] = $decodeJson($payload['descriptions']);
        }
        if (isset($payload['validation_rules'])) {
            $payload['validation_rules'] = $decodeJson($payload['validation_rules']);
        }
        if (isset($payload['params'])) {
            $payload['params'] = $decodeJson($payload['params']);
        }

        try {
            if ($id > 0) {
                $this->model_extension_module_oc_kit_easycheckout->updateField($id, $payload);
                $newId = $id;
            } else {
                $newId = $this->model_extension_module_oc_kit_easycheckout->addField($payload);
            }
            $this->jsonResponse([
                'success'  => true,
                'field_id' => $newId,
                'field'    => $this->model_extension_module_oc_kit_easycheckout->getField($newId),
                'message'  => $this->language->get('text_field_saved'),
            ]);
        } catch (ValidationException $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $e->getErrors(),
                'message' => $this->language->get('text_field_validation_error'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** Список стандартних (native) OC-полів + поточні оверайди назв/placeholder. */
    public function nativeFieldList(): void
    {
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        // Типові OC-назви по мовах — читаємо catalog checkout/checkout lang-файли.
        $loadLang = static function (string $file): array {
            $_ = [];
            if (is_file($file)) { require $file; }
            return is_array($_) ? $_ : [];
        };
        $defaults = [];
        foreach ($languages as $lang) {
            $defaults[(int)$lang['language_id']] = $loadLang(
                DIR_CATALOG . 'language/' . $lang['code'] . '/checkout/checkout.php'
            );
        }

        $ec = new EasyCheckout($this->registry);
        $overrides = $ec->getFieldsRepository()->getNativeOverrides();

        $items = [];
        foreach (\OcKit\EasyCheckout\Libs\NativeFieldsRegistry::listAll() as $nf) {
            $defaultLabels = [];
            foreach ($languages as $lang) {
                $lid = (int)$lang['language_id'];
                $lbl = (string)($defaults[$lid][$nf['lang_key']] ?? '');
                $defaultLabels[$lid] = $lbl !== '' ? $lbl : $nf['code'];
            }
            $items[] = [
                'field_id'       => (int)$nf['field_id'],
                'code'           => $nf['code'],
                'type'           => $nf['type'],
                'belongs_to'     => $nf['belongs_to'],
                'oc_field'       => $nf['oc_field'],
                'default_labels' => $defaultLabels,
                'descriptions'   => $overrides[(int)$nf['field_id']] ?? (object)[],
            ];
        }
        $this->jsonResponse(['success' => true, 'items' => $items]);
    }

    /** Зберегти оверайди (назва/placeholder/підказка по мовах) для native-поля. */
    public function nativeFieldSave(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $negId = (int)($this->request->post['field_id'] ?? 0);
        if ($negId >= 0 || !\OcKit\EasyCheckout\Libs\NativeFieldsRegistry::findById($negId)) {
            $this->jsonResponse(['success' => false, 'message' => 'invalid field']);
            return;
        }
        // OC проганяє POST через htmlspecialchars — повертаємо назад перед json_decode.
        $raw   = (string)($this->request->post['descriptions'] ?? '');
        $clean = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descriptions = json_decode($clean, true);
        if (!is_array($descriptions)) $descriptions = [];

        (new EasyCheckout($this->registry))->getFieldsRepository()->saveNativeOverride($negId, $descriptions);
        $this->jsonResponse(['success' => true, 'message' => $this->language->get('text_field_saved')]);
    }

    public function fieldDelete(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id    = (int)($this->request->post['field_id'] ?? 0);
        $force = !empty($this->request->post['force']);
        if (!$id) { $this->jsonResponse(['success' => true]); return; }

        $usages = $this->model_extension_module_oc_kit_easycheckout->findFieldUsages($id);
        if ($usages && !$force) {
            $this->jsonResponse([
                'success'   => false,
                'in_use'    => true,
                'usages'    => $usages,
                'usage_count' => count($usages),
            ]);
            return;
        }

        $this->model_extension_module_oc_kit_easycheckout->deleteField($id);
        $this->jsonResponse(['success' => true, 'force_deleted' => $force, 'cleaned_usages' => count($usages)]);
    }

    public function fieldDeleteMany(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $ids   = (array)($this->request->post['field_ids'] ?? []);
        $force = !empty($this->request->post['force']);

        // Check usages first — if any, require force
        $allUsages = [];
        foreach ($ids as $id) {
            $u = $this->model_extension_module_oc_kit_easycheckout->findFieldUsages((int)$id);
            if ($u) $allUsages[(int)$id] = count($u);
        }
        if ($allUsages && !$force) {
            $this->jsonResponse([
                'success' => false,
                'in_use'  => true,
                'usages_by_field' => $allUsages,
            ]);
            return;
        }

        $deleted = $this->model_extension_module_oc_kit_easycheckout->deleteFields($ids);
        $this->jsonResponse(['success' => true, 'deleted' => $deleted]);
    }

    /**
     * Bulk-edit обмежене коло безпечних колонок (belongs_to, save_to_comment)
     * для виділених полів. Code/type/назви — НЕ редагуються (потрібен індивідуальний save).
     */
    public function fieldsBulkEdit(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $ids     = array_map('intval', (array)($this->request->post['field_ids'] ?? []));
        $changes = (array)($this->request->post['changes'] ?? []);
        if (!$ids || !$changes) {
            $this->jsonResponse(['success' => false, 'message' => 'no changes']);
            return;
        }
        $allowedBelongsTo = ['order', 'customer', 'address'];
        $sets = [];
        if (isset($changes['belongs_to']) && in_array($changes['belongs_to'], $allowedBelongsTo, true)) {
            $sets[] = "`belongs_to` = '" . $this->db->escape((string)$changes['belongs_to']) . "'";
        }
        if (isset($changes['save_to_comment'])) {
            $sets[] = "`save_to_comment` = " . ((int)$changes['save_to_comment'] ? 1 : 0);
        }
        if (!$sets) {
            $this->jsonResponse(['success' => false, 'message' => 'invalid changes']);
            return;
        }
        $idList = implode(',', array_map('intval', $ids));
        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_fields`
            SET " . implode(', ', $sets) . ", `date_modified` = NOW()
            WHERE `field_id` IN ({$idList})");

        $this->jsonResponse(['success' => true, 'updated' => count($ids)]);
    }

    public function fieldsReorder(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $order = $this->request->post['order'] ?? [];
        if (!is_array($order)) $order = [];
        $map = [];
        foreach ($order as $entry) {
            if (!is_array($entry)) continue;
            $fid = (int)($entry['field_id'] ?? 0);
            $so  = (int)($entry['sort_order'] ?? 0);
            if ($fid > 0) $map[$fid] = $so;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->updateFieldsSortOrder($map);
        $this->jsonResponse(['success' => true]);
    }

    public function headingsReorder(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $order = $this->request->post['order'] ?? [];
        if (!is_array($order)) $order = [];
        $map = [];
        foreach ($order as $entry) {
            if (!is_array($entry)) continue;
            $hid = (int)($entry['heading_id'] ?? 0);
            $so  = (int)($entry['sort_order'] ?? 0);
            if ($hid > 0) $map[$hid] = $so;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->updateHeadingsSortOrder($map);
        $this->jsonResponse(['success' => true]);
    }

    public function fieldClone(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $sourceId = (int)($this->request->post['field_id'] ?? 0);
        $newId    = $this->model_extension_module_oc_kit_easycheckout->cloneField($sourceId);
        $this->jsonResponse([
            'success'  => $newId > 0,
            'field_id' => $newId,
        ]);
    }

    public function fieldNextCode(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->jsonResponse([
            'success' => true,
            'code'    => $this->model_extension_module_oc_kit_easycheckout->generateFieldCode(),
        ]);
    }

    /**
     * AJAX-варіант buildFieldsListForJs() — для оновлення списку полів у layout-builder
     * без перезавантаження сторінки після створення нового поля в реєстрі.
     */
    public function fieldsListForLayout(): void
    {
        $this->jsonResponse([
            'success' => true,
            'fields'  => $this->buildFieldsListForJs(),
        ]);
    }

    // ─── Groups AJAX ──────────────────────────────────────────────────────────

    public function groupList(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->jsonResponse([
            'success' => true,
            'groups'  => $this->model_extension_module_oc_kit_easycheckout->listGroups(),
        ]);
    }

    public function groupSave(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');

        $payload = $this->request->post;
        $id = (int)($payload['group_id'] ?? 0);
        unset($payload['group_id']);

        try {
            if ($id > 0) {
                $this->model_extension_module_oc_kit_easycheckout->updateGroup($id, $payload);
                $newId = $id;
            } else {
                $newId = $this->model_extension_module_oc_kit_easycheckout->addGroup($payload);
            }
            $this->jsonResponse([
                'success'  => true,
                'group_id' => $newId,
                'group'    => $this->model_extension_module_oc_kit_easycheckout->getGroup($newId),
                'message'  => $this->language->get('text_group_saved'),
            ]);
        } catch (ValidationException $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $e->getErrors(),
                'message' => $this->language->get('text_group_validation_error'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function groupDelete(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id = (int)($this->request->post['group_id'] ?? 0);
        if (!$id) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        try {
            $this->model_extension_module_oc_kit_easycheckout->deleteGroup($id);
            $this->jsonResponse(['success' => true, 'message' => $this->language->get('text_group_deleted')]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $key = reset($errors);
            $msg = $this->language->get('error_group_' . $key);
            $this->jsonResponse([
                'success' => false,
                'message' => $msg && $msg !== ('error_group_' . $key) ? $msg : $e->getMessage(),
            ]);
        }
    }

    public function groupClone(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');

        $sourceId = (int)($this->request->post['source_group_id'] ?? 0);
        $payload  = [
            'name'       => (string)($this->request->post['name'] ?? ''),
            'slug'       => (string)($this->request->post['slug'] ?? ''),
            'sort_order' => (int)   ($this->request->post['sort_order'] ?? 0),
        ];
        try {
            $newId = $this->model_extension_module_oc_kit_easycheckout->cloneGroup($sourceId, $payload);
            $this->jsonResponse([
                'success'  => true,
                'group_id' => $newId,
                'group'    => $this->model_extension_module_oc_kit_easycheckout->getGroup($newId),
                'message'  => $this->language->get('text_group_cloned'),
            ]);
        } catch (ValidationException $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $e->getErrors(),
                'message' => $this->language->get('text_group_validation_error'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Headings AJAX ────────────────────────────────────────────────────────

    public function headingList(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        $this->load->model('extension/module/oc_kit_easycheckout');
        $filter = [
            'search' => (string)($this->request->get['search'] ?? ''),
            'tag'    => (string)($this->request->get['tag'] ?? ''),
            'sort'   => (string)($this->request->get['sort'] ?? 'heading_id'),
            'order'  => (string)($this->request->get['order'] ?? 'DESC'),
            'start'  => (int)   ($this->request->get['start'] ?? 0),
            'limit'  => max(1, min(200, (int)($this->request->get['limit'] ?? 50))),
        ];
        $items = $this->model_extension_module_oc_kit_easycheckout->listHeadings($filter);
        $total = $this->model_extension_module_oc_kit_easycheckout->countHeadings($filter);

        // Enrich layouts_count
        $usage = $this->model_extension_module_oc_kit_easycheckout->getHeadingUsageInLayouts();
        foreach ($items as &$item) {
            $item['layouts_count'] = (int)($usage[(int)$item['heading_id']] ?? 0);
        }
        unset($item);

        $this->jsonResponse(['success' => true, 'items' => $items, 'total' => $total]);
    }

    public function headingGet(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id = (int)($this->request->get['heading_id'] ?? $this->request->post['heading_id'] ?? 0);
        $heading = $id ? $this->model_extension_module_oc_kit_easycheckout->getHeading($id) : null;
        $this->jsonResponse(['success' => (bool)$heading, 'heading' => $heading]);
    }

    public function headingSave(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');

        $payload = $this->request->post;
        $id = (int)($payload['heading_id'] ?? 0);
        unset($payload['heading_id']);

        if (isset($payload['descriptions']) && is_string($payload['descriptions'])) {
            $clean = html_entity_decode($payload['descriptions'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $arr = json_decode($clean, true);
            $payload['descriptions'] = is_array($arr) ? $arr : [];
        }

        try {
            if ($id > 0) {
                $this->model_extension_module_oc_kit_easycheckout->updateHeading($id, $payload);
                $newId = $id;
            } else {
                $newId = $this->model_extension_module_oc_kit_easycheckout->addHeading($payload);
            }
            $this->jsonResponse([
                'success'    => true,
                'heading_id' => $newId,
                'heading'    => $this->model_extension_module_oc_kit_easycheckout->getHeading($newId),
                'message'    => $this->language->get('text_heading_saved'),
            ]);
        } catch (ValidationException $e) {
            $this->jsonResponse([
                'success' => false,
                'errors'  => $e->getErrors(),
                'message' => $this->language->get('text_heading_validation_error'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function headingDelete(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id    = (int)($this->request->post['heading_id'] ?? 0);
        $force = !empty($this->request->post['force']);
        if (!$id) { $this->jsonResponse(['success' => true]); return; }

        // Резолвимо code для пошуку за heading_code (legacy slugs у layout-settings)
        $heading = $this->model_extension_module_oc_kit_easycheckout->getHeading($id);
        $code    = $heading ? (string)$heading['code'] : '';
        $usages  = $this->model_extension_module_oc_kit_easycheckout->findHeadingUsages($id, $code);

        if ($usages && !$force) {
            $this->jsonResponse([
                'success'     => false,
                'in_use'      => true,
                'usage_count' => count($usages),
                'usages'      => $usages,
            ]);
            return;
        }
        $this->model_extension_module_oc_kit_easycheckout->deleteHeading($id);
        $this->jsonResponse(['success' => true]);
    }

    public function headingDeleteMany(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $ids   = (array)($this->request->post['heading_ids'] ?? []);
        $force = !empty($this->request->post['force']);

        // In-use check: aggregate per-id usages
        $allUsages = [];
        foreach ($ids as $id) {
            $heading = $this->model_extension_module_oc_kit_easycheckout->getHeading((int)$id);
            $code    = $heading ? (string)$heading['code'] : '';
            $u       = $this->model_extension_module_oc_kit_easycheckout->findHeadingUsages((int)$id, $code);
            if ($u) $allUsages[(int)$id] = count($u);
        }
        if ($allUsages && !$force) {
            $this->jsonResponse([
                'success' => false,
                'in_use'  => true,
                'usages_by_heading' => $allUsages,
            ]);
            return;
        }

        $deleted = $this->model_extension_module_oc_kit_easycheckout->deleteHeadings($ids);
        $this->jsonResponse(['success' => true, 'deleted' => $deleted]);
    }

    public function headingClone(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $newId = $this->model_extension_module_oc_kit_easycheckout->cloneHeading(
            (int)($this->request->post['heading_id'] ?? 0)
        );
        $this->jsonResponse(['success' => $newId > 0, 'heading_id' => $newId]);
    }

    public function headingNextCode(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->jsonResponse([
            'success' => true,
            'code'    => $this->model_extension_module_oc_kit_easycheckout->generateHeadingCode(),
        ]);
    }

    /**
     * Autocomplete для consent — пошук інформаційних сторінок.
     * GET: ?q=privacy&id=14 (id опційно — для гідрації при редагуванні).
     */
    // ─── Integration setup (seo_url + redirect event) ─────────────────────────

    public function integrationStatus(): void
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->jsonResponse([
            'success' => true,
            'status'  => $this->model_extension_module_oc_kit_easycheckout->integrationStatus(),
        ]);
    }

    public function setupIntegration(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $report = $this->model_extension_module_oc_kit_easycheckout->setupIntegration();
        $this->jsonResponse([
            'success' => true,
            'report'  => $report,
            'status'  => $this->model_extension_module_oc_kit_easycheckout->integrationStatus(),
            'message' => $this->language->get('integration_activated'),
        ]);
    }

    public function removeIntegration(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $report = $this->model_extension_module_oc_kit_easycheckout->removeIntegration();
        $this->jsonResponse([
            'success' => true,
            'report'  => $report,
            'status'  => $this->model_extension_module_oc_kit_easycheckout->integrationStatus(),
            'message' => $this->language->get('integration_deactivated'),
        ]);
    }

    // ─── Page Layout AJAX ─────────────────────────────────────────────────────

    public function pageLayoutGet(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $page    = (string)($this->request->get['page'] ?? 'checkout');
        $groupId = (int)($this->request->get['group_id'] ?? 0);
        $storeId = (int)($this->request->get['store_id'] ?? 0);

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        $ec->setGroup($this->resolveGroupId($groupId));
        $layout   = $ec->getPageLayoutRepository()->get($page);
        $warnings = $this->buildLayoutWarnings($ec, $layout);
        $this->jsonResponse([
            'success'  => true,
            'layout'   => $layout,
            'warnings' => $warnings,
            'group_id' => $ec->getGroupId(),
            'store_id' => $storeId,
        ]);
    }

    /**
     * Збирає список field_id / field_code / heading_id зі сховища і зивaє в lint.
     */
    /** No-image placeholder (admin tool/image) для native filemanager-пікера. */
    private function getImagePlaceholder(): string
    {
        try {
            $this->load->model('tool/image');
            return (string)$this->model_tool_image->resize('no_image.png', 100, 100);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function buildLayoutWarnings(EasyCheckout $ec, array $layout): array
    {
        $fields   = $ec->getFieldsRepository()->list(['limit' => 1000]);
        $headings = $ec->getHeadingsRepository()->list(['limit' => 1000]);
        $fieldIds = []; $fieldCodes = []; $headingIds = [];
        foreach ($fields   as $f) { $fieldIds[]   = (int)$f['field_id']; $fieldCodes[(string)$f['code']] = (int)$f['field_id']; }
        foreach ($headings as $h) { $headingIds[] = (int)$h['heading_id']; }
        $warnings = $ec->getPageLayoutRepository()->lint($layout, $fieldIds, $fieldCodes, $headingIds);
        return $this->localizeLayoutWarnings($warnings);
    }

    /**
     * Локалізує lint-warnings: бібліотека повертає type + loc/extra (мова-агностик),
     * контролер формує людські `message` + `where` поточною мовою.
     */
    private function localizeLayoutWarnings(array $warnings): array
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        $wStep  = $this->language->get('layout_warn_loc_step')  ?: 'крок';
        $wRow   = $this->language->get('layout_warn_loc_row')   ?: 'рядок';
        $wCell  = $this->language->get('layout_warn_loc_cell')  ?: 'комірка';
        $wMulti = $this->language->get('layout_warn_loc_multiple') ?: 'кілька блоків';

        foreach ($warnings as &$w) {
            $loc   = (array)($w['loc'] ?? []);
            $extra = (array)($w['extra'] ?? []);

            // Локалізований `where`
            if (isset($loc['block_type'])) {
                $where = $wStep . ' #' . (int)($loc['step'] ?? 0) . ' → ' . (string)$loc['block_type'];
            } elseif (isset($loc['cell'])) {
                $where = $wStep . ' #' . (int)$loc['step'] . ' → ' . $wRow . ' #' . (int)$loc['row'] . ' → ' . $wCell . ' #' . (int)$loc['cell'];
            } elseif (isset($loc['row'])) {
                $where = $wStep . ' #' . (int)$loc['step'] . ' → ' . $wRow . ' #' . (int)$loc['row'];
            } elseif (isset($loc['step'])) {
                $where = $wStep . ' #' . (int)$loc['step'];
            } else {
                $where = $wMulti;
            }

            // Локалізований `message` (з підстановками)
            $key = 'layout_warn_' . (string)($w['type'] ?? '');
            $msg = $this->language->get($key);
            if (!$msg || $msg === $key) {
                $msg = (string)($w['message'] ?? '');  // fallback на англ. з lint
            } else {
                $msg = strtr($msg, [
                    '%field_id%'    => (string)($extra['field_id'] ?? ''),
                    '%heading_id%'  => (string)($extra['heading_id'] ?? ''),
                    '%source_code%' => (string)($extra['source_code'] ?? ''),
                    '%count%'       => (string)($extra['count'] ?? ''),
                ]);
            }

            $w['message'] = $msg;
            $w['where']   = $where;
        }
        unset($w);

        return $warnings;
    }

    /**
     * Повертає дефолтний layout без збереження — JS оновлює стан
     * у конструкторі, користувач сам натискає Save.
     */
    public function pageLayoutDefaults(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $ec = new EasyCheckout($this->registry);
        $this->jsonResponse([
            'success' => true,
            'layout'  => $ec->getPageLayoutRepository()->normalize($ec->getPageLayoutRepository()->defaultLayout()),
        ]);
    }

    /**
     * Print-friendly order для адміна — окрема сторінка з custom-полями.
     */
    public function orderPrint(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $orderId = (int)($this->request->get['order_id'] ?? 0);
        if (!$orderId) { $this->response->setOutput('No order_id'); return; }

        $this->load->model('sale/order');
        $orderInfo = $this->model_sale_order->getOrder($orderId);
        if (!$orderInfo) { $this->response->setOutput('Order not found'); return; }

        $this->load->model('extension/module/oc_kit_easycheckout');
        $customFields = $this->model_extension_module_oc_kit_easycheckout->getOrderCustomFields($orderId);
        $products     = $this->model_sale_order->getOrderProducts($orderId);
        $totals       = $this->model_sale_order->getOrderTotals($orderId);

        $cValue = (float)($orderInfo['currency_value'] ?? 1);
        $cCode  = (string)$orderInfo['currency_code'];
        foreach ($totals as &$t) {
            $t['text'] = $this->currency->format((float)$t['value'], $cCode, $cValue);
        }
        unset($t);

        $data = [
            'order'         => $orderInfo,
            'products'      => $products,
            'totals'        => $totals,
            'custom_fields' => $customFields,
            'store_name'    => (string)$this->config->get('config_name'),
            'store_url'     => (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG),
        ];

        $this->response->setOutput($this->load->view('extension/module/ockit/easycheckout/order_print', $data));
    }

    /**
     * Save edits зі окремого admin order tab.
     * POST: order_id, fields[{code}]={value}
     */
    public function orderFieldsSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'sale/order')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $orderId = (int)($this->request->post['order_id'] ?? 0);
        $fields  = $this->request->post['fields'] ?? [];
        if (!is_array($fields)) $fields = [];
        // OC POST-sanitizer додає htmlspecialchars — повертаємо чистий текст
        $fields = array_map(static fn($v) => is_string($v)
            ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $v, $fields);

        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->saveOrderCustomFields($orderId, $fields);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Test-reminder: рендерить email-шаблон з sample-data + надсилає на email
     * адміна (або вказаний у POST). Дозволяє перевірити шаблон до того, як
     * cron почне розсилати реальним клієнтам.
     */
    public function reminderTest(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $email = trim((string)($this->request->post['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_email') ?: 'Invalid email']);
            return;
        }

        $this->load->model('extension/module/oc_kit_easycheckout');
        $tpl   = $this->model_extension_module_oc_kit_easycheckout->getReminderTexts();
        $code  = (string)$this->config->get('config_language');
        $subjT = (string)($tpl['subject'][$code] ?? reset($tpl['subject']) ?: '{store_name}: complete your order');
        $bodyT = (string)($tpl['body'][$code]    ?? reset($tpl['body'])    ?: '<p>Hi {firstname}, <a href="{recovery_url}">complete</a></p>');

        $vars = [
            'firstname'    => 'John',
            'lastname'     => 'Doe',
            'email'        => $email,
            'store_name'   => (string)$this->config->get('config_name'),
            'recovery_url' => (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG)
                            . 'index.php?route=checkout/easycheckout&recover=' . str_repeat('a', 32),
            'total'        => '199.99',
            'currency'     => 'UAH',
        ];
        $render = static function (string $tplRaw, array $v): string {
            return preg_replace_callback('~\{([a-z_]+)\}~i',
                static fn($m) => array_key_exists($m[1], $v) ? (string)$v[$m[1]] : $m[0], $tplRaw);
        };

        try {
            $mail = new \Mail($this->config->get('config_mail_engine') ?: 'mail');
            $mail->parameter      = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname  = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username  = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password  = html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port      = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout   = $this->config->get('config_mail_smtp_timeout');
            $mail->setTo($email);
            $mail->setFrom((string)$this->config->get('config_email'));
            $mail->setSender(html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject('[TEST] ' . $render($subjT, $vars));
            $mail->setHtml($render($bodyT, $vars));
            $mail->send();
            $this->jsonResponse(['success' => true, 'message' => sprintf((string)($this->language->get('text_reminder_test_sent') ?: 'Test sent to %s'), $email)]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * CSV-export замовлень з custom-полями. Стрімить файл прямо в response,
     * без буфера у пам'яті — підходить для тисяч замовлень.
     */
    public function ordersExport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_easycheckout')) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            return;
        }
        $from = (string)($this->request->get['date_from']        ?? '');
        $to   = (string)($this->request->get['date_to']          ?? '');
        $st   = (int)   ($this->request->get['order_status_id']  ?? 0);

        // Sanity: тільки YYYY-MM-DD
        if ($from !== '' && !preg_match('~^\d{4}-\d{2}-\d{2}$~', $from)) $from = '';
        if ($to   !== '' && !preg_match('~^\d{4}-\d{2}-\d{2}$~', $to))   $to   = '';

        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->streamOrdersCsv($from, $to, $st);
        // streamOrdersCsv викликає fclose; OC framework додасть свої headers — нам треба завершити exit
        exit;
    }

    /**
     * Видає одноразовий preview-token (5 хв TTL) і повертає catalog URL для iframe.
     * Якщо у POST передано `layout` JSON — токен містить snapshot цього layout-у,
     * щоб catalog рендерив поточний (можливо ще не збережений) state з admin Pages.
     * Зберігається у `oc_kit_easycheckout_settings`:
     *   code='preview_tokens', key={token}, value={expires_at}|{json},
     *   serialized=1 для JSON snapshot, інакше 0.
     */
    public function previewToken(): void
    {
        EasyCheckout::guardAdmin($this->registry);

        $tok = bin2hex(random_bytes(16));
        $expires = time() + 300;

        // Якщо JS передав layout-snapshot — нормалізуємо і зберігаємо JSON
        $rawLayout = (string)($this->request->post['layout'] ?? '');
        $layoutPayload = null;
        if ($rawLayout !== '') {
            $clean = html_entity_decode($rawLayout, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decoded = json_decode($clean, true);
            if (is_array($decoded)) {
                $ec = new EasyCheckout($this->registry);
                $layoutPayload = [
                    'expires' => $expires,
                    'layout'  => $ec->getPageLayoutRepository()->normalize($decoded),
                ];
            }
        }

        if ($layoutPayload !== null) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
                SET `store_id`=0, `group_id`=0,
                    `code`='preview_tokens',
                    `key`='" . $this->db->escape($tok) . "',
                    `value`='" . $this->db->escape(json_encode($layoutPayload, JSON_UNESCAPED_UNICODE)) . "',
                    `serialized`=1");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
                SET `store_id`=0, `group_id`=0,
                    `code`='preview_tokens',
                    `key`='" . $this->db->escape($tok) . "',
                    `value`='" . (int)$expires . "',
                    `serialized`=0");
        }

        // GC: видаляємо expired (для serialized=0 порівнюємо unix-int, для serialized=1 — JSON.expires).
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code`='preview_tokens'
              AND `serialized`=0
              AND CAST(`value` AS UNSIGNED) < " . time());

        $catalogBase = (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG);
        $url = rtrim($catalogBase, '/') . '/index.php?route=checkout/easycheckout&preview=' . rawurlencode($tok);
        $this->jsonResponse(['success' => true, 'url' => $url]);
    }

    // ─── Abandoned-checkouts AJAX ─────────────────────────────────────────────

    public function abandonedList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $days   = (int)($this->request->get['days']   ?? 30);
        $filter = [
            'search'    => (string)($this->request->get['search'] ?? ''),
            'status'    => (string)($this->request->get['status'] ?? 'pending'),
            'min_total' => (float) ($this->request->get['min_total'] ?? 0),
            'max_total' => (float) ($this->request->get['max_total'] ?? 0),
        ];
        $items = $this->model_extension_module_oc_kit_easycheckout->getAbandoned(200, 0, $filter);

        // Форматуємо суму у валюті запису (currency->format — presentation у контролері)
        foreach ($items as &$it) {
            $code = (string)($it['currency_code'] ?? $this->config->get('config_currency'));
            $it['total_display'] = $this->currency->format((float)($it['total'] ?? 0), $code, 1.0);
        }
        unset($it);

        $stats = $this->model_extension_module_oc_kit_easycheckout->getAbandonedStats($days);
        // Форматуємо суми-агрегати у базовій валюті магазину
        $baseCurrency = (string)$this->config->get('config_currency');
        foreach (['lost_amount', 'recovered_amount'] as $k) {
            if (isset($stats[$k])) {
                $stats[$k . '_display'] = $this->currency->format((float)$stats[$k], $baseCurrency, 1.0);
            }
        }

        $this->jsonResponse([
            'success' => true,
            'items'   => $items,
            'total'   => $this->model_extension_module_oc_kit_easycheckout->getAbandonedCount(),
            'stats'   => $stats,
        ]);
    }

    public function abandonedSaveNote(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $id   = (int)($this->request->post['abandoned_id'] ?? 0);
        $note = (string)($this->request->post['note'] ?? '');
        $note = html_entity_decode($note, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->saveAbandonedNote($id, $note);
        $this->jsonResponse(['success' => true]);
    }

    public function abandonedProducts(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $id = (int)($this->request->get['abandoned_id'] ?? 0);
        $this->jsonResponse([
            'success'  => true,
            'products' => $this->model_extension_module_oc_kit_easycheckout->getAbandonedProducts($id),
        ]);
    }

    public function abandonedDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $id = (int)($this->request->post['abandoned_id'] ?? 0);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->deleteAbandoned($id);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Manual reminder send для одного abandoned-запису з admin UI.
     * Викликає той же шаблон і renderer що й cron, але по конкретному id.
     */
    public function abandonedSendReminder(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $id = (int)($this->request->post['abandoned_id'] ?? 0);
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'invalid id']);
            return;
        }

        $row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `abandoned_id`=" . $id . " LIMIT 1")->row;
        if (!$row || !filter_var($row['email'], FILTER_VALIDATE_EMAIL) || empty($row['recovery_token'])) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('abandoned_no_email_or_token') ?: 'No email or recovery token']);
            return;
        }
        if (!empty($row['recovered_order_id'])) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('abandoned_already_recovered') ?: 'Already recovered']);
            return;
        }

        $this->load->model('extension/module/oc_kit_easycheckout');
        $tpl   = $this->model_extension_module_oc_kit_easycheckout->getReminderTexts();
        $code  = (string)$this->config->get('config_language');
        $subjT = (string)($tpl['subject'][$code] ?? reset($tpl['subject']) ?: '{store_name}: complete your order');
        $bodyT = (string)($tpl['body'][$code]    ?? reset($tpl['body'])    ?: '<p>Hi {firstname}, <a href="{recovery_url}">complete</a></p>');

        $base        = rtrim((string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG), '/');
        $recoveryUrl = $base . '/index.php?route=checkout/easycheckout&recover=' . rawurlencode((string)$row['recovery_token']);
        $vars = [
            'firstname'    => htmlspecialchars(trim((string)$row['firstname']) ?: 'customer', ENT_QUOTES, 'UTF-8'),
            'lastname'     => htmlspecialchars((string)$row['lastname'], ENT_QUOTES, 'UTF-8'),
            'email'        => htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8'),
            'store_name'   => htmlspecialchars((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'),
            'recovery_url' => htmlspecialchars($recoveryUrl, ENT_QUOTES, 'UTF-8'),
            'total'        => number_format((float)$row['total'], 2),
            'currency'     => htmlspecialchars((string)$row['currency_code'], ENT_QUOTES, 'UTF-8'),
        ];
        $render = static function (string $t, array $v): string {
            return preg_replace_callback('~\{([a-z_]+)\}~i',
                static fn($m) => array_key_exists($m[1], $v) ? (string)$v[$m[1]] : $m[0], $t);
        };

        try {
            $mail = new \Mail($this->config->get('config_mail_engine') ?: 'mail');
            $mail->parameter      = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname  = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username  = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password  = html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port      = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout   = $this->config->get('config_mail_smtp_timeout');
            $mail->setTo((string)$row['email']);
            $mail->setFrom((string)$this->config->get('config_email'));
            $mail->setSender(html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject($render($subjT, $vars));
            $mail->setHtml($render($bodyT, $vars));
            $mail->send();

            $now = date('Y-m-d H:i:s');
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned`
                SET `notified_at` = '" . $this->db->escape($now) . "',
                    `reminder_count` = `reminder_count` + 1
                WHERE `abandoned_id` = " . $id);

            $this->jsonResponse([
                'success'     => true,
                'message'     => $this->language->get('abandoned_reminder_sent') ?: 'Reminder sent',
                'notified_at' => $now,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function headingsExport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $json = $this->model_extension_module_oc_kit_easycheckout->exportHeadingsJson();
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="okec-headings-' . date('Y-m-d') . '.json"');
        $this->response->setOutput($json);
        exit;
    }

    public function headingsImport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $jsonRaw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_file($_FILES['file']['tmp_name'])) {
            $jsonRaw = (string)file_get_contents($_FILES['file']['tmp_name']);
        }
        if ($jsonRaw === '') {
            $this->jsonResponse(['success' => false, 'message' => 'No file received']);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $result = $this->model_extension_module_oc_kit_easycheckout->importHeadingsJson($jsonRaw);
        $this->jsonResponse(array_merge(['success' => true], $result));
    }

    /** Streams page-layout JSON for current group/store. */
    /** Експорт УСІХ налаштувань модуля (download JSON). Raw header() — бо exit гасить OC-output. */
    public function settingsExportAll(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $data = $this->model_extension_module_oc_kit_easycheckout->exportAllSettings();

        $domain   = parse_url((string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG), PHP_URL_HOST) ?: 'store';
        $filename = 'easycheckout-settings-' . $domain . '-' . date('Y-m-d') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /** Імпорт усіх налаштувань з JSON-файлу. */
    public function settingsImportAll(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $jsonRaw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_file($_FILES['file']['tmp_name'])) {
            $jsonRaw = (string)file_get_contents($_FILES['file']['tmp_name']);
        }
        if ($jsonRaw === '') {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('settings_import_no_file') ?: 'No file received']);
            return;
        }
        $data = json_decode($jsonRaw, true);
        if (!is_array($data)) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('settings_import_invalid') ?: 'Invalid JSON']);
            return;
        }

        $this->load->model('extension/module/oc_kit_easycheckout');
        $res = $this->model_extension_module_oc_kit_easycheckout->importAllSettings($data);
        $this->jsonResponse([
            'success' => (bool)$res['success'],
            'message' => $res['success']
                ? ($this->language->get('settings_import_done') ?: 'Settings imported')
                : ($res['message'] ?: ($this->language->get('settings_import_invalid') ?: 'Import failed')),
        ]);
    }

    public function pageLayoutExport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $page    = (string)($this->request->get['page']     ?? 'checkout');
        $groupId = (int)($this->request->get['group_id']  ?? 0);
        $storeId = (int)($this->request->get['store_id']  ?? 0);

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        $ec->setGroup($this->resolveGroupId($groupId));
        $layout = $ec->getPageLayoutRepository()->get($page);

        $payload = [
            'version'  => 1,
            'exported' => date('c'),
            'page'     => $page,
            'layout'   => $layout,
        ];

        $filename = sprintf('okec-layout-%s-g%d-s%d-%s.json', $page, $groupId, $storeId, date('Y-m-d'));
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->setOutput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        exit;
    }

    public function pageLayoutImport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $page    = (string)($this->request->post['page']     ?? 'checkout');
        $groupId = (int)($this->request->post['group_id']  ?? 0);
        $storeId = (int)($this->request->post['store_id']  ?? 0);

        $jsonRaw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_file($_FILES['file']['tmp_name'])) {
            $jsonRaw = (string)file_get_contents($_FILES['file']['tmp_name']);
        }
        if ($jsonRaw === '') {
            $this->jsonResponse(['success' => false, 'message' => 'No file received']);
            return;
        }

        $payload = json_decode($jsonRaw, true);
        if (!is_array($payload) || !is_array($payload['layout'] ?? null)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid JSON format']);
            return;
        }

        try {
            $ec = new EasyCheckout($this->registry);
            $ec->setStore($storeId);
            $ec->setGroup($this->resolveGroupId($groupId));
            $ec->getPageLayoutRepository()->save($page, $payload['layout']);
            $this->jsonResponse([
                'success' => true,
                'layout'  => $ec->getPageLayoutRepository()->get($page),
                'message' => $this->language->get('layout_saved'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Stream JSON dump fields-registry for download. */
    public function fieldsExport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $json = $this->model_extension_module_oc_kit_easycheckout->exportFieldsJson();
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="okec-fields-' . date('Y-m-d') . '.json"');
        $this->response->setOutput($json);
        exit;
    }

    public function fieldsImport(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $jsonRaw = '';
        if (!empty($_FILES['file']['tmp_name']) && is_file($_FILES['file']['tmp_name'])) {
            $jsonRaw = (string)file_get_contents($_FILES['file']['tmp_name']);
        } elseif (!empty($this->request->post['json'])) {
            $jsonRaw = html_entity_decode((string)$this->request->post['json'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if ($jsonRaw === '') {
            $this->jsonResponse(['success' => false, 'message' => 'No JSON received']);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $result = $this->model_extension_module_oc_kit_easycheckout->importFieldsJson($jsonRaw);
        $this->jsonResponse(array_merge(['success' => true], $result));
    }

    /**
     * Список доступних presets для admin Fields-секції dropdown-у.
     */
    public function fieldPresetsList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/FieldPresets.php';
        $code = (string)$this->config->get('config_language');
        $items = [];
        foreach (\OcKit\EasyCheckout\Libs\FieldPresets::all() as $p) {
            $items[] = [
                'code'        => $p['code'],
                'label'       => (string)($p['label'][$code] ?? reset($p['label'])),
                'description' => (string)($p['description'][$code] ?? reset($p['description'])),
                'fields_count'=> count($p['fields']),
            ];
        }
        $this->jsonResponse(['success' => true, 'items' => $items]);
    }

    /**
     * Створює всі поля з обраного preset-у в реєстрі. Якщо field з таким code
     * вже існує — skip (idempotent).
     */
    public function fieldPresetApply(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/FieldPresets.php';
        $code   = (string)($this->request->post['preset'] ?? '');
        $preset = \OcKit\EasyCheckout\Libs\FieldPresets::findByCode($code);
        if (!$preset) {
            $this->jsonResponse(['success' => false, 'message' => 'Unknown preset']);
            return;
        }

        $this->load->model('localisation/language');
        $langByCode = [];
        foreach ($this->model_localisation_language->getLanguages() as $l) {
            $langByCode[(string)$l['code']] = (int)$l['language_id'];
        }

        $ec      = new EasyCheckout($this->registry);
        $repo    = $ec->getFieldsRepository();
        $created = 0; $skipped = 0;

        foreach ($preset['fields'] as $f) {
            $existing = $repo->list(['search' => $f['code'], 'limit' => 50]);
            $hit = false;
            foreach ($existing as $e) if ((string)$e['code'] === $f['code']) { $hit = true; break; }
            if ($hit) { $skipped++; continue; }

            $descs = [];
            foreach (($f['descriptions'] ?? []) as $langCode => $d) {
                if (!isset($langByCode[$langCode])) continue;
                $descs[$langByCode[$langCode]] = [
                    'name'        => (string)($d['name']        ?? ''),
                    'placeholder' => (string)($d['placeholder'] ?? ''),
                    'tooltip'     => (string)($d['tooltip']     ?? ''),
                ];
            }

            try {
                $repo->create([
                    'code'             => $f['code'],
                    'type'             => $f['type'],
                    'belongs_to'       => $f['belongs_to'],
                    'mask_mode'        => 'manual',
                    'mask_value'       => null,
                    'default_mode'     => 'manual',
                    'default_value'    => null,
                    'save_to_comment'  => 0,
                    'validation_rules' => [],
                    'params'           => $f['params'] ?? [],
                    'descriptions'     => $descs,
                ]);
                $created++;
            } catch (\Throwable $e) {
                // ignore — continue with rest
            }
        }

        $this->jsonResponse([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => sprintf(
                (string)($this->language->get('text_preset_applied') ?: 'Created %d, skipped %d'),
                $created, $skipped
            ),
        ]);
    }

    public function abandonedDeleteMany(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $ids = $this->request->post['ids'] ?? [];
        if (!is_array($ids)) $ids = [];
        $this->load->model('extension/module/oc_kit_easycheckout');
        $count = $this->model_extension_module_oc_kit_easycheckout->deleteAbandonedMany($ids);
        $this->jsonResponse(['success' => true, 'count' => $count]);
    }

    public function pageLayoutSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }

        $page    = (string)($this->request->post['page'] ?? 'checkout');
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $rawJson = (string)($this->request->post['layout'] ?? '');
        $clean   = html_entity_decode($rawJson, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $layout  = json_decode($clean, true);

        if (!is_array($layout)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid layout payload']);
            return;
        }

        try {
            $ec = new EasyCheckout($this->registry);
            $ec->setStore($storeId);
            $ec->setGroup($this->resolveGroupId($groupId));
            $ec->getPageLayoutRepository()->save($page, $layout);
            $this->jsonResponse([
                'success'  => true,
                'layout'   => $ec->getPageLayoutRepository()->get($page),
                'group_id' => $ec->getGroupId(),
                'store_id' => $storeId,
                'message'  => $this->language->get('layout_saved'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Копіює layout з (source_store, source_group) у поточний (store, group).
     * Тіло НЕ зберігає — повертає JSON, а конструктор підхоплює його як unsaved
     * стан, користувач сам тисне Save.
     */
    public function pageLayoutCopyFrom(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }

        $page          = (string)($this->request->post['page'] ?? 'checkout');
        $sourceStoreId = (int)($this->request->post['source_store_id'] ?? 0);
        $sourceGroupId = (int)($this->request->post['source_group_id'] ?? 0);

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($sourceStoreId);
        $ec->setGroup($this->resolveGroupId($sourceGroupId));
        $layout = $ec->getPageLayoutRepository()->get($page);

        $this->jsonResponse([
            'success' => true,
            'layout'  => $layout,
            'message' => $this->language->get('layout_copied') ?: 'Layout copied — review and Save',
        ]);
    }

    // ─── Payment/Shipping modules (ТЗ §13) ─────────────────────────────

    /**
     * Список встановлених/увімкнених payment та shipping extensions з overrides
     * зі стора `modules` (per-store, per-group через ConfigStore).
     */
    public function modulesList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $groupId = (int)($this->request->get['group_id'] ?? 0);

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        $ec->setGroup($this->resolveGroupId($groupId));
        $cs = $ec->getConfigStore();

        $payment  = $this->fetchExtensions('payment',  $cs);
        $shipping = $this->fetchExtensions('shipping', $cs);

        $this->jsonResponse([
            'success'  => true,
            'payment'  => $payment,
            'shipping' => $shipping,
        ]);
    }

    /**
     * Зберігає overrides модуля.
     * POST: type=payment|shipping, code=cod, hide=0|1, sort_order=N, override_title=string
     */
    public function moduleOverrideSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $type    = (string)($this->request->post['type'] ?? '');
        $code    = (string)($this->request->post['code'] ?? '');
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        if (!in_array($type, ['payment', 'shipping'], true) || $code === '') {
            $this->jsonResponse(['success' => false, 'message' => 'invalid args']);
            return;
        }

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        $ec->setGroup($this->resolveGroupId($groupId));
        $cs = $ec->getConfigStore();

        $entry = $cs->get('modules.' . $type, $code, []);
        if (!is_array($entry)) $entry = [];
        $entry['hide']                 = !empty($this->request->post['hide']) ? 1 : 0;
        $entry['sort_order']           = (int)($this->request->post['sort_order'] ?? 0);
        $entry['override_title']       = (string)($this->request->post['override_title'] ?? '');
        $entry['override_description'] = (string)($this->request->post['override_description'] ?? '');
        $entry['override_icon']        = (string)($this->request->post['override_icon'] ?? '');
        $cs->set('modules.' . $type, $code, $entry);

        $this->jsonResponse(['success' => true]);
    }

    // ─── Custom shipping/payment methods (внутрішні методи EasyCheckout) ──

    /** Майстер-дані: групи+методи обох типів + lookup-списки для форми. */
    public function customMethodsData(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $repo = (new EasyCheckout($this->registry))->getCustomMethodsRepository();

        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/order_status');
        $this->load->model('localisation/currency');

        $taxClasses = array_map(static fn($t) => [
            'tax_class_id' => (int)$t['tax_class_id'], 'title' => (string)$t['title'],
        ], $this->model_localisation_tax_class->getTaxClasses());

        $orderStatuses = array_map(static fn($s) => [
            'order_status_id' => (int)$s['order_status_id'], 'name' => (string)$s['name'],
        ], $this->model_localisation_order_status->getOrderStatuses());

        $currencies = [];
        foreach ($this->model_localisation_currency->getCurrencies() as $c) {
            $currencies[] = ['code' => (string)$c['code'], 'title' => (string)$c['title']];
        }

        $this->jsonResponse([
            'success'  => true,
            'shipping' => [
                'groups'  => $repo->listGroups(\OcKit\EasyCheckout\Libs\CustomMethodsRepository::TYPE_SHIPPING),
                'methods' => $repo->listMethods(\OcKit\EasyCheckout\Libs\CustomMethodsRepository::TYPE_SHIPPING),
            ],
            'payment'  => [
                'methods' => $repo->listMethods(\OcKit\EasyCheckout\Libs\CustomMethodsRepository::TYPE_PAYMENT),
            ],
            'subtotals'       => $repo->listSubtotals(),
            'tax_classes'     => $taxClasses,
            'order_statuses'  => $orderStatuses,
            'currencies'      => $currencies,
            'condition_types' => $this->buildConditionTypes(),
        ]);
    }

    /** Локалізований список типів умов для дропдауна (з групами + applies). */
    private function buildConditionTypes(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/ConditionTypes.php';
        $this->load->language('extension/module/oc_kit_easycheckout');
        $groups = \OcKit\EasyCheckout\Libs\ConditionTypes::groups();
        $out = [];
        foreach (\OcKit\EasyCheckout\Libs\ConditionTypes::all() as $c) {
            $out[] = [
                'code'        => $c['code'],
                'label'       => $this->language->get($c['lang_key']) ?: $c['code'],
                'group'       => $c['group'],
                'group_label' => $this->language->get($groups[$c['group']] ?? '') ?: $c['group'],
                'applies'     => $c['applies'],
            ];
        }
        return $out;
    }

    public function customSubtotalAdd(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $id = $this->cmRepo()->addSubtotal();
        $this->jsonResponse(['success' => true, 'subtotal_id' => $id]);
    }

    public function customSubtotalSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $id  = (int)($this->request->post['subtotal_id'] ?? 0);
        $raw = (string)($this->request->post['payload'] ?? '');
        $data = $raw !== '' ? json_decode(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true) : [];
        if (!$id || !is_array($data)) { $this->jsonResponse(['success' => false]); return; }
        $this->cmRepo()->saveSubtotal($id, $data);
        $this->jsonResponse(['success' => true]);
    }

    public function customSubtotalDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $this->cmRepo()->deleteSubtotal((int)($this->request->post['subtotal_id'] ?? 0));
        $this->jsonResponse(['success' => true]);
    }

    private function cmRepo(): \OcKit\EasyCheckout\Libs\CustomMethodsRepository
    {
        return (new EasyCheckout($this->registry))->getCustomMethodsRepository();
    }

    private function cmGuardModify(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return false;
        }
        return true;
    }

    public function customMethodGroupAdd(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $type = (string)($this->request->post['type'] ?? 'shipping');
        $id   = $this->cmRepo()->addGroup($type);
        $this->jsonResponse(['success' => true, 'group_id' => $id]);
    }

    public function customMethodGroupDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $this->cmRepo()->deleteGroup((int)($this->request->post['group_id'] ?? 0));
        $this->jsonResponse(['success' => true]);
    }

    public function customMethodAdd(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $type    = (string)($this->request->post['type'] ?? 'shipping');
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        $id      = $this->cmRepo()->addMethod($type, $groupId);
        $this->jsonResponse(['success' => true, 'method_id' => $id, 'method' => $this->cmRepo()->getMethod($id)]);
    }

    public function customMethodGet(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $m = $this->cmRepo()->getMethod((int)($this->request->get['method_id'] ?? 0));
        if (!$m) { $this->jsonResponse(['success' => false]); return; }
        $this->jsonResponse(['success' => true, 'method' => $m]);
    }

    public function customMethodSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $id = (int)($this->request->post['method_id'] ?? 0);
        if (!$id) { $this->jsonResponse(['success' => false, 'message' => 'no method_id']); return; }

        // POST приходить як JSON-рядок payload (щоб не воювати з htmlspecialchars OC)
        $raw  = (string)($this->request->post['payload'] ?? '');
        $data = $raw !== '' ? json_decode(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true) : [];
        if (!is_array($data)) { $this->jsonResponse(['success' => false, 'message' => 'invalid payload']); return; }

        $this->cmRepo()->saveMethod($id, $data);
        $this->jsonResponse(['success' => true, 'method' => $this->cmRepo()->getMethod($id)]);
    }

    public function customMethodDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $this->cmRepo()->deleteMethod((int)($this->request->post['method_id'] ?? 0));
        $this->jsonResponse(['success' => true]);
    }

    public function customMethodToggle(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $this->cmRepo()->setMethodStatus(
            (int)($this->request->post['method_id'] ?? 0),
            !empty($this->request->post['status'])
        );
        $this->jsonResponse(['success' => true]);
    }

    public function customMethodClone(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->cmGuardModify()) return;
        $newId = $this->cmRepo()->cloneMethod((int)($this->request->post['method_id'] ?? 0));
        $this->jsonResponse(['success' => (bool)$newId, 'method_id' => $newId]);
    }

    /** @return array<int,array{code:string,title:string,enabled:bool,override:array}> */
    private function fetchExtensions(string $type, ConfigStore $cs): array
    {
        $rows = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "extension`
            WHERE `type` = '" . $this->db->escape($type) . "'
            ORDER BY `code` ASC")->rows;
        $out = [];
        foreach ($rows as $r) {
            $code = (string)$r['code'];
            $statusKey = $type . '_' . $code . '_status';
            $sortKey   = $type . '_' . $code . '_sort_order';
            $enabled = (int)$this->config->get($statusKey) === 1;
            $native  = (int)$this->config->get($sortKey);
            $override = $cs->get('modules.' . $type, $code, []);
            if (!is_array($override)) $override = [];
            $out[] = [
                'code'                 => $code,
                'enabled'              => $enabled,
                'native_sort'          => $native,
                'hide'                 => !empty($override['hide']),
                'sort_order'           => isset($override['sort_order']) ? (int)$override['sort_order'] : $native,
                'override_title'       => (string)($override['override_title'] ?? ''),
                'override_description' => (string)($override['override_description'] ?? ''),
                'override_icon'        => (string)($override['override_icon'] ?? ''),
            ];
        }
        return $out;
    }

    // ─── License (ТЗ §21) ──────────────────────────────────────────────

    public function licenseInfo(): void
    {
        $info = \OcKit\EasyCheckout\EasyCheckoutGuard::getInfo($this->registry);
        $this->jsonResponse(['success' => true, 'info' => $info]);
    }

    public function licenseActivate(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('text_license_not_validated') ?: 'permission denied']);
            return;
        }
        $this->load->language('extension/module/oc_kit_easycheckout');
        $key  = trim((string)($this->request->post['license_key'] ?? ''));
        $resp = \OcKit\EasyCheckout\EasyCheckoutGuard::activate($this->registry, $key);
        $token = $this->session->data['user_token'];
        $this->jsonResponse([
            'success'      => (bool)($resp['success'] ?? false),
            'message'      => ($resp['success'] ?? false)
                ? ($this->language->get('text_license_active') ?: 'License is active')
                : ($this->language->get('text_license_invalid') ?: 'Invalid key'),
            'info'         => (array)($resp['info'] ?? []),
            'redirect_url' => ($resp['success'] ?? false)
                ? html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout', 'user_token=' . $token, true))
                : '',
        ]);
    }

    public function license(): void
    {
        $this->load->language('extension/module/oc_kit_easycheckout');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/easycheckout/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');

        $token = $this->session->data['user_token'];
        $info  = \OcKit\EasyCheckout\EasyCheckoutGuard::getInfo($this->registry);

        // Якщо вже активовано — на головну сторінку модуля
        if (!empty($info['valid'])) {
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_easycheckout', 'user_token=' . $token, true)
            );
            return;
        }

        $data = [
            'heading_title'   => $this->language->get('heading_title'),
            'tab_license'     => $this->language->get('tab_license')        ?: 'License',
            'entry_license_key' => $this->language->get('entry_license_key') ?: 'License key',
            'button_activate' => $this->language->get('button_activate')    ?: 'Activate',
            'text_extensions' => $this->language->get('text_extensions')    ?: 'Extensions',
            'text_license_active'        => $this->language->get('text_license_active'),
            'text_license_invalid'       => $this->language->get('text_license_invalid'),
            'text_license_expired'       => $this->language->get('text_license_expired'),
            'text_license_trial'         => $this->language->get('text_license_trial'),
            'text_license_not_validated' => $this->language->get('text_license_not_validated') ?: 'License key not entered',
            'text_license_version'       => $this->language->get('text_license_version') ?: 'Version',
            'text_license_domain'        => $this->language->get('text_license_domain')  ?: 'Domain',
            'text_license_buy'           => $this->language->get('text_license_buy')     ?: 'Buy license',
            'license_info'    => $info,
            'license_key'     => (string)($this->config->get('module_oc_kit_easycheckout_license_key') ?? ''),
            'license_url'     => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/license',     'user_token=' . $token, true)),
            'index_url'       => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout',             'user_token=' . $token, true)),
            'extensions_url'  => html_entity_decode($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true)),
            'action_activate' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/licenseActivate', 'user_token=' . $token, true)),
        ];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ockit/easycheckout/license', $data));
    }

    // ─── Integrations marketplace ─────────────────────────────────────

    private function buildIntegrationsRegistry(int $storeId = 0): \OcKit\EasyCheckout\Libs\IntegrationsRegistry
    {
        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        return new \OcKit\EasyCheckout\Libs\IntegrationsRegistry($ec->getConfigStore());
    }

    public function integrationsList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $this->jsonResponse(['success' => true, 'items' => $reg->listForUi()]);
    }

    public function integrationGet(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $code    = (string)($this->request->get['code'] ?? '');
        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $this->jsonResponse([
            'success'  => true,
            'code'     => $i->getCode(),
            'name'     => $i->getName(),
            'schema'   => $i->getSettingsSchema(),
            'settings' => $i->getSettings(),
            'enabled'  => $i->isEnabled(),
        ]);
    }

    public function integrationToggle(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $enabled = !empty($this->request->post['enabled']);
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $i->setEnabled($enabled);
        $this->jsonResponse(['success' => true, 'enabled' => $i->isEnabled()]);
    }

    public function integrationSettingsSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code     = (string)($this->request->post['code'] ?? '');
        $storeId  = (int)($this->request->post['store_id'] ?? 0);
        $settings = (array)($this->request->post['settings'] ?? []);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $i->saveSettings($settings);
        $this->jsonResponse(['success' => true, 'settings' => $i->getSettings()]);
    }

    public function integrationRunAction(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $code    = (string)($this->request->post['code'] ?? '');
        $action  = (string)($this->request->post['action'] ?? '');
        $payload = (array)($this->request->post['payload'] ?? []);
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $this->jsonResponse($i->runAction($action, $payload));
    }

    public function integrationInstallFields(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }

        $blocks = $i->getDefaultBlocks();
        if (!$blocks) { $this->jsonResponse(['success' => false, 'message' => 'Інтеграція не має preset-блоків']); return; }

        $ec = new EasyCheckout($this->registry); $ec->setStore($storeId);
        $repo = $ec->getFieldsRepository();
        $existing = array_column($repo->listAll(), 'code');
        $created = 0; $skipped = 0;
        foreach ($blocks as $block) {
            foreach ((array)($block['fields'] ?? []) as $f) {
                $fcode = (string)($f['code'] ?? '');
                if ($fcode === '') { $skipped++; continue; }
                if (in_array($fcode, $existing, true)) { $skipped++; continue; }
                try {
                    $repo->add([
                        'code'             => $fcode,
                        'type'             => (string)($f['type'] ?? 'text'),
                        'belongs_to'       => 'shipping',
                        'mask_mode'        => 'none',
                        'default_mode'     => 'none',
                        'save_to_comment'  => 0,
                        'validation_rules' => ['required' => !empty($f['required'])],
                        'params'           => ['integration' => $code],
                        'descriptions'     => [],
                    ]);
                    $existing[] = $fcode;
                    $created++;
                } catch (\Throwable $e) { $skipped++; }
            }
        }
        $this->jsonResponse([
            'success' => true,
            'message' => 'Створено ' . $created . ' полів, пропущено ' . $skipped,
            'created' => $created, 'skipped' => $skipped,
        ]);
    }

    public function marketplaceList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $client = new \OcKit\EasyCheckout\Libs\MarketplaceClient(
            DIR_SYSTEM . 'library/ockit/easycheckout/integrations'
        );
        $reg = $this->buildIntegrationsRegistry(0);
        $installed = array_keys($reg->all());
        $items = [];
        foreach ($client->listAvailable() as $row) {
            $row['installed'] = in_array($row['code'], $installed, true);
            $row['installed_version'] = $row['installed'] ? (string)$reg->installedVersion($row['code']) : '';
            $row['has_update'] = $row['installed']
                && $row['installed_version'] !== ''
                && version_compare((string)$row['version'], $row['installed_version'], '>');
            $items[] = $row;
        }
        $this->jsonResponse(['success' => true, 'items' => $items]);
    }

    public function marketplaceInstall(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code = (string)($this->request->post['code'] ?? '');
        $url  = (string)($this->request->post['url'] ?? '');
        $ec = new EasyCheckout($this->registry); $ec->setStore(0);
        $telemetry = new \OcKit\EasyCheckout\Libs\Telemetry($ec->getConfigStore());
        $client = new \OcKit\EasyCheckout\Libs\MarketplaceClient(
            DIR_SYSTEM . 'library/ockit/easycheckout/integrations',
            $telemetry, true
        );
        $res = $client->download($code, $url);
        if (!empty($res['success'])) {
            // Run installSchema for the freshly-installed integration
            $reg = $this->buildIntegrationsRegistry(0);
            if ($i = $reg->get($code)) {
                try { $i->installSchema($this->db); } catch (\Throwable $e) { /* report later */ }
            }
        }
        $this->jsonResponse($res);
    }

    public function marketplaceUpdate(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code = (string)($this->request->post['code'] ?? '');
        $url  = (string)($this->request->post['url'] ?? '');
        $ec = new EasyCheckout($this->registry); $ec->setStore(0);
        $telemetry = new \OcKit\EasyCheckout\Libs\Telemetry($ec->getConfigStore());
        $client = new \OcKit\EasyCheckout\Libs\MarketplaceClient(
            DIR_SYSTEM . 'library/ockit/easycheckout/integrations',
            $telemetry, true
        );
        $res = $client->update($code, $url);
        if (!empty($res['success'])) {
            $reg = $this->buildIntegrationsRegistry(0);
            if ($i = $reg->get($code)) {
                try { $i->installSchema($this->db); } catch (\Throwable $e) { /* skip */ }
            }
        }
        $this->jsonResponse($res);
    }

    public function marketplaceUninstall(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code = (string)($this->request->post['code'] ?? '');
        // First drop schema (if integration class still loadable)
        $reg = $this->buildIntegrationsRegistry(0);
        if ($i = $reg->get($code)) {
            try { $i->uninstallSchema($this->db); } catch (\Throwable $e) { /* skip */ }
        }
        $client = new \OcKit\EasyCheckout\Libs\MarketplaceClient(
            DIR_SYSTEM . 'library/ockit/easycheckout/integrations'
        );
        $this->jsonResponse($client->uninstall($code));
    }

    public function integrationLicenseActivate(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $key     = (string)($this->request->post['license_key'] ?? '');
        $storeId = (int)($this->request->post['store_id'] ?? 0);

        $ec = new EasyCheckout($this->registry); $ec->setStore($storeId);
        $client = new \OcKit\EasyCheckout\Libs\Telemetry($ec->getConfigStore());
        $domain = parse_url(HTTP_CATALOG ?: HTTPS_CATALOG ?: 'http://localhost', PHP_URL_HOST) ?: 'localhost';
        $res = $client->register($code, $key, $domain);
        $this->jsonResponse($res);
    }

    public function integrationAddToLayout(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $page    = (string)($this->request->post['page'] ?? 'checkout');
        $storeId = (int)($this->request->post['store_id'] ?? 0);

        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $blocks = $i->getDefaultBlocks();
        if (!$blocks) { $this->jsonResponse(['success' => false, 'message' => 'No default blocks']); return; }

        $ec = new EasyCheckout($this->registry); $ec->setStore($storeId);
        $repo = $ec->getFieldsRepository();
        $existing = [];
        foreach ($repo->listAll() as $row) $existing[(string)$row['code']] = (int)$row['field_id'];

        // Step 1: ensure fields exist; collect field_id by code
        $createdFields = 0;
        foreach ($blocks as $b) {
            foreach ((array)($b['fields'] ?? []) as $f) {
                $fcode = (string)($f['code'] ?? '');
                if ($fcode === '' || isset($existing[$fcode])) continue;
                try {
                    $existing[$fcode] = $repo->add([
                        'code' => $fcode, 'type' => (string)($f['type'] ?? 'text'),
                        'belongs_to' => 'shipping', 'mask_mode' => 'none', 'default_mode' => 'none',
                        'save_to_comment' => 0,
                        'validation_rules' => ['required' => !empty($f['required'])],
                        'params' => ['integration' => $code], 'descriptions' => [],
                    ]);
                    $createdFields++;
                } catch (\Throwable $e) { /* skip */ }
            }
        }

        // Step 2: append a new block to the LAST cell of the LAST step
        $layoutRepo = $ec->getPageLayoutRepository();
        $layout = $layoutRepo->get($page);
        if (empty($layout['steps'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Layout has no steps']); return;
        }
        $stepIdx = count($layout['steps']) - 1;
        if (empty($layout['steps'][$stepIdx]['rows'])) {
            $layout['steps'][$stepIdx]['rows'] = [[
                'id' => 'row_' . substr(bin2hex(random_bytes(3)), 0, 6),
                'columns' => ['desktop' => 1, 'tablet' => 1],
                'cells' => [['id' => 'cell_' . substr(bin2hex(random_bytes(3)), 0, 6), 'blocks' => []]],
            ]];
        }
        $rowIdx  = count($layout['steps'][$stepIdx]['rows']) - 1;
        $cellIdx = count($layout['steps'][$stepIdx]['rows'][$rowIdx]['cells']) - 1;

        $blockSpec = $blocks[0];
        $fieldRefs = [];
        foreach ((array)($blockSpec['fields'] ?? []) as $f) {
            $fid = $existing[(string)($f['code'] ?? '')] ?? null;
            if ($fid === null) continue;
            $fieldRefs[] = [
                'field_id' => $fid, 'visibility' => 'always',
                'required' => !empty($f['required']), 'reload_on_change' => false,
            ];
        }
        $layout['steps'][$stepIdx]['rows'][$rowIdx]['cells'][$cellIdx]['blocks'][] = [
            'id'   => $code . '_' . substr(bin2hex(random_bytes(3)), 0, 4),
            'type' => 'text',
            'settings' => [
                'title'  => (string)($blockSpec['name'] ?? ''),
                'fields' => $fieldRefs,
            ],
        ];
        $layoutRepo->save($page, $layout);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Блок «' . ($blockSpec['name'] ?? '') . '» додано в layout. Створено нових полів: ' . $createdFields,
            'created_fields' => $createdFields,
        ]);
    }

    public function integrationHealth(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $code    = (string)($this->request->get['code'] ?? '');
        $storeId = (int)($this->request->get['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'health' => []]); return; }

        $ec = new EasyCheckout($this->registry); $ec->setStore($storeId);
        $cs = $ec->getConfigStore();
        $section = 'integration.' . $code;

        $stamp     = (int)$cs->get($section, 'last_refresh_ts', 0);
        $resultRaw = (string)$cs->get($section, 'last_refresh_result', '');
        $result    = $resultRaw ? json_decode($resultRaw, true) : null;

        $totalRows = 0;
        foreach ($i->getOwnedTables() as $table) {
            try {
                $r = $this->db->query("SELECT COUNT(*) AS `c` FROM `" . DB_PREFIX . str_replace('`', '', (string)$table) . "`");
                $totalRows += (int)($r->row['c'] ?? 0);
            } catch (\Throwable $e) { /* table missing — ignore */ }
        }

        $human = '—';
        if ($stamp > 0) {
            $diff = time() - $stamp;
            $human = $diff < 60 ? ($diff . ' сек назад')
                  : ($diff < 3600 ? (round($diff / 60) . ' хв назад')
                  : ($diff < 86400 ? (round($diff / 3600) . ' год назад')
                  : (round($diff / 86400) . ' дн назад')));
        }

        $this->jsonResponse([
            'success' => true,
            'health'  => [
                'last_refresh_ts'    => $stamp,
                'last_refresh_human' => $human,
                'last_success'       => is_array($result) && !empty($result['success']),
                'last_message'       => is_array($result) ? (string)($result['message'] ?? '') : '',
                'last_stats'         => is_array($result) ? (array)($result['stats'] ?? []) : [],
                'records_total'      => $totalRows,
                'tables'             => $i->getOwnedTables(),
            ],
        ]);
    }

    public function integrationIcon(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $code = (string)($this->request->get['code'] ?? '');
        $reg  = $this->buildIntegrationsRegistry(0);
        $path = $reg->iconPath($code);
        if (!$path) { http_response_code(404); exit; }
        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }

    public function integrationRefresh(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        $i->installSchema($this->db);
        @set_time_limit(0);
        $this->jsonResponse($i->refreshCache($this->db));
    }

    public function integrationPurge(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]); return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $storeId = (int)($this->request->post['store_id'] ?? 0);
        $reg = $this->buildIntegrationsRegistry($storeId);
        $i = $reg->get($code);
        if (!$i) { $this->jsonResponse(['success' => false, 'message' => 'Unknown integration']); return; }
        try {
            $i->purgeData($this->db);
            $this->jsonResponse(['success' => true, 'message' => 'Дані очищено']);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Address formats (ТЗ §14) ──────────────────────────────────────

    public function addressFormatsList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $rows = $this->model_extension_module_oc_kit_easycheckout->getAddressFormats();

        $this->load->model('customer/customer_group');
        $groups = array_map(static function ($g) {
            return ['customer_group_id' => (int)$g['customer_group_id'], 'name' => (string)$g['name']];
        }, $this->model_customer_customer_group->getCustomerGroups());

        // Installed shipping extensions — code + title from native lang/setting
        $shippingRows = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "extension`
            WHERE `type`='shipping' ORDER BY `code` ASC")->rows;
        $shippingMethods = [];
        foreach ($shippingRows as $r) {
            $code  = (string)$r['code'];
            $title = trim((string)$this->config->get('shipping_' . $code . '_title')) ?: $code;
            $shippingMethods[] = ['code' => $code, 'title' => $title];
        }

        $this->jsonResponse([
            'success'          => true,
            'items'            => $rows,
            'customer_groups'  => $groups,
            'shipping_methods' => $shippingMethods,
        ]);
    }

    public function addressFormatSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $formatId    = (int)($this->request->post['format_id'] ?? 0);
        $scope       = (string)($this->request->post['scope'] ?? '');
        $scopeId     = (string)($this->request->post['scope_id'] ?? '');
        $languageId  = (int)($this->request->post['language_id'] ?? 0);
        $template    = html_entity_decode((string)($this->request->post['template'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (!in_array($scope, ['customer_group', 'shipping'], true) || $scopeId === '' || $languageId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid scope']);
            return;
        }
        $this->load->model('extension/module/oc_kit_easycheckout');
        $newId = $this->model_extension_module_oc_kit_easycheckout->saveAddressFormat($formatId, $scope, $scopeId, $languageId, $template);
        $this->jsonResponse(['success' => true, 'format_id' => $newId, 'message' => $this->language->get('js_saved')]);
    }

    public function addressFormatDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $id = (int)($this->request->post['format_id'] ?? 0);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->deleteAddressFormat($id);
        $this->jsonResponse(['success' => true]);
    }

    // ─── Order restrictions (ТЗ §16) ───────────────────────────────────

    public function restrictionsList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $rows = $this->model_extension_module_oc_kit_easycheckout->getOrderRestrictions();
        $this->load->model('customer/customer_group');
        $groups = array_map(static function ($g) {
            return ['customer_group_id' => (int)$g['customer_group_id'], 'name' => (string)$g['name']];
        }, $this->model_customer_customer_group->getCustomerGroups());
        $this->jsonResponse(['success' => true, 'items' => $rows, 'customer_groups' => $groups]);
    }

    public function restrictionSave(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $data = [
            'restriction_id'     => (int)($this->request->post['restriction_id'] ?? 0),
            'group_id'           => (int)($this->request->post['group_id'] ?? 0),
            'customer_group_ids' => trim((string)($this->request->post['customer_group_ids'] ?? '')),
            'min_total'          => $this->request->post['min_total']  !== '' ? (float)$this->request->post['min_total']  : null,
            'max_total'          => $this->request->post['max_total']  !== '' ? (float)$this->request->post['max_total']  : null,
            'min_qty'            => $this->request->post['min_qty']    !== '' ? (int)$this->request->post['min_qty']      : null,
            'max_qty'            => $this->request->post['max_qty']    !== '' ? (int)$this->request->post['max_qty']      : null,
            'min_weight'         => $this->request->post['min_weight'] !== '' ? (float)$this->request->post['min_weight'] : null,
            'max_weight'         => $this->request->post['max_weight'] !== '' ? (float)$this->request->post['max_weight'] : null,
            'error_text'         => html_entity_decode((string)($this->request->post['error_text'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'sort_order'         => (int)($this->request->post['sort_order'] ?? 0),
        ];
        $this->load->model('extension/module/oc_kit_easycheckout');
        $newId = $this->model_extension_module_oc_kit_easycheckout->saveOrderRestriction($data);
        $this->jsonResponse(['success' => true, 'restriction_id' => $newId, 'message' => $this->language->get('js_saved')]);
    }

    public function restrictionDelete(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false]);
            return;
        }
        $id = (int)($this->request->post['restriction_id'] ?? 0);
        $this->load->model('extension/module/oc_kit_easycheckout');
        $this->model_extension_module_oc_kit_easycheckout->deleteOrderRestriction($id);
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Список доступних layout-пресетів (ТЗ §22) для UI.
     */
    public function layoutPresetsList(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->jsonResponse([
            'success' => true,
            'presets' => \OcKit\EasyCheckout\Libs\LayoutPresets::listAll(),
        ]);
    }

    /**
     * Застосовує layout-пресет до поточної (store_id, group_id) — overwrite.
     */
    public function layoutPresetApply(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }
        $code    = (string)($this->request->post['code'] ?? '');
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        $storeId = (int)($this->request->post['store_id'] ?? 0);

        $layout = \OcKit\EasyCheckout\Libs\LayoutPresets::build($code);
        if (!$layout) {
            $this->jsonResponse(['success' => false, 'message' => 'Unknown preset']);
            return;
        }

        $ec = new EasyCheckout($this->registry);
        $ec->setStore($storeId);
        $ec->setGroup($this->resolveGroupId($groupId));
        $ec->getPageLayoutRepository()->save('checkout', $layout);

        $this->jsonResponse([
            'success' => true,
            'message' => $this->language->get('preset_applied') ?: 'Preset applied',
            'layout'  => $ec->getPageLayoutRepository()->get('checkout'),
        ]);
    }

    /**
     * Health-check endpoint — повертає масив перевірок зі статусом ok/warn/fail
     * для UI секції "Health". Кожен check: { key, status, detail? }.
     */
    public function healthCheck(): void
    {
        EasyCheckout::guardAdmin($this->registry);
        $this->load->language('extension/module/oc_kit_easycheckout');
        $checks = [];

        // 1. Module status
        $moduleEnabled = (int)$this->config->get('module_oc_kit_easycheckout_status');
        $checks[] = [
            'key'    => 'module_status',
            'status' => $moduleEnabled ? 'ok' : 'fail',
            'detail' => $moduleEnabled ? '' : 'module_oc_kit_easycheckout_status = 0',
        ];

        // 2. Cron heartbeat — recent within 24h
        $lastRun = $this->readCronLastRun();
        $cronStatus = 'fail';
        $cronDetail = 'never';
        if ($lastRun) {
            $diff = time() - strtotime($lastRun);
            if ($diff < 86400)      { $cronStatus = 'ok';   $cronDetail = $lastRun; }
            elseif ($diff < 604800) { $cronStatus = 'warn'; $cronDetail = $lastRun . ' (>24h ago)'; }
            else                    { $cronStatus = 'fail'; $cronDetail = $lastRun . ' (>7d ago)'; }
        }
        $checks[] = ['key' => 'cron_recent', 'status' => $cronStatus, 'detail' => $cronDetail];

        // 3. Mail engine
        $mailEngine = (string)$this->config->get('config_mail_engine');
        $mailHost   = (string)$this->config->get('config_mail_smtp_hostname');
        $mailOk     = $mailEngine !== '' && ($mailEngine !== 'smtp' || $mailHost !== '');
        $checks[] = [
            'key'    => 'mail_engine',
            'status' => $mailOk ? 'ok' : 'warn',
            'detail' => $mailEngine . ($mailEngine === 'smtp' ? (':' . ($mailHost ?: '?')) : ''),
        ];

        // 4. DB tables
        $expected = [
            'kit_easycheckout_settings', 'kit_easycheckout_fields',
            'kit_easycheckout_fields_description', 'kit_easycheckout_headings',
            'kit_easycheckout_headings_description', 'kit_easycheckout_groups',
            'kit_easycheckout_abandoned',
        ];
        $missing = [];
        foreach ($expected as $t) {
            $r = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $t) . "'");
            if (!$r->num_rows) $missing[] = $t;
        }
        $checks[] = [
            'key'    => 'db_tables',
            'status' => $missing ? 'fail' : 'ok',
            'detail' => $missing ? ('missing: ' . implode(', ', $missing)) : count($expected) . ' OK',
        ];

        // 5. OCMOD: order tab + checkout link replacement
        $ocmodRow = $this->db->query("SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "modification`
            WHERE `code` LIKE 'oc_kit_easycheckout%' AND `status` = 1")->row;
        $ocmodCount = (int)$ocmodRow['c'];
        $checks[] = [
            'key'    => 'ocmod_active',
            'status' => $ocmodCount > 0 ? 'ok' : 'warn',
            'detail' => $ocmodCount . ' active',
        ];

        // 6. Default country
        $countryId = (int)$this->config->get('module_oc_kit_easycheckout_default_country_id');
        $checks[] = [
            'key'    => 'default_country',
            'status' => $countryId > 0 ? 'ok' : 'warn',
            'detail' => $countryId ? 'country_id=' . $countryId : 'not set',
        ];

        // 7. Layout warnings count (broken refs)
        try {
            $ec = new EasyCheckout($this->registry);
            $ec->getGroupsRepository()->ensureDefault();
            $ec->setGroup($ec->getGroupsRepository()->getDefault()['group_id']);
            $layout   = $ec->getPageLayoutRepository()->get('checkout');
            $warnings = $this->buildLayoutWarnings($ec, $layout);
            $critical = array_filter($warnings, fn($w) => in_array($w['type'], ['field_missing', 'heading_missing', 'block_condition_broken', 'field_condition_broken'], true));
            $checks[] = [
                'key'    => 'layout_valid',
                'status' => $critical ? 'fail' : ($warnings ? 'warn' : 'ok'),
                'detail' => count($warnings) . ' warnings, ' . count($critical) . ' critical',
            ];
        } catch (\Throwable $e) {
            $checks[] = ['key' => 'layout_valid', 'status' => 'warn', 'detail' => $e->getMessage()];
        }

        // Додаємо локалізований human-readable опис стану кожної перевірки
        foreach ($checks as &$c) {
            $msgKey = 'health_msg_' . $c['key'] . '_' . $c['status'];
            $msg    = $this->language->get($msgKey);
            if (!$msg || $msg === $msgKey) {
                // fallback: загальний по статусу
                $msg = $this->language->get('health_msg_generic_' . $c['status']);
            }
            $c['message'] = $msg;
        }
        unset($c);

        $this->jsonResponse(['success' => true, 'checks' => $checks]);
    }

    /**
     * Зчитує heartbeat-таймстамп останнього запуску cron-job безпосередньо з oc_setting,
     * без залежності від $this->config — щоб не вимагати прев'юд load() цього key.
     */
    private function readCronLastRun(): string
    {
        $row = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting`
            WHERE `store_id`=0 AND `code`='module_oc_kit_easycheckout'
              AND `key`='module_oc_kit_easycheckout_cron_last_run' LIMIT 1");
        return $row->num_rows ? (string)$row->row['value'] : '';
    }

    /**
     * Розв'язує group_id з POST/GET: якщо 0 — повертає default group's id; якщо
     * group не існує — теж default. Гарантує що default-група існує (створює її
     * якщо нема — захист від broken-state).
     */
    private function resolveGroupId(int $requestedId): int
    {
        $ec = new EasyCheckout($this->registry);
        $ec->getGroupsRepository()->ensureDefault();
        if ($requestedId > 0) {
            $g = $ec->getGroupsRepository()->get($requestedId);
            if ($g) return $requestedId;
        }
        $def = $ec->getGroupsRepository()->getDefault();
        return $def ? $def['group_id'] : 0;
    }

    public function informationSearch(): void
    {
        $this->load->model('catalog/information');
        $this->load->model('localisation/language');

        $query = trim((string)($this->request->get['q'] ?? ''));
        $byId  = (int)($this->request->get['id'] ?? 0);
        $langId = (int)$this->config->get('config_language_id');

        $items = [];

        if ($byId > 0) {
            $info = $this->model_catalog_information->getInformation($byId);
            if ($info) {
                $items[] = ['information_id' => (int)$info['information_id'], 'title' => $info['title']];
            }
        } elseif ($query !== '') {
            // Пряма SQL-агрегація — model_catalog_information не має зручного API для пошуку.
            $rows = $this->db->query("SELECT i.information_id, id.title
                FROM `" . DB_PREFIX . "information` i
                LEFT JOIN `" . DB_PREFIX . "information_description` id
                  ON id.information_id = i.information_id AND id.language_id = " . $langId . "
                WHERE i.status = 1 AND id.title LIKE '%" . $this->db->escape($query) . "%'
                ORDER BY i.sort_order, id.title
                LIMIT 20")->rows;
            foreach ($rows as $r) {
                $items[] = ['information_id' => (int)$r['information_id'], 'title' => (string)$r['title']];
            }
        }

        $this->jsonResponse(['success' => true, 'items' => $items]);
    }

    private function buildFieldTypesForJs(): array
    {
        // Lucide icon mapping per type — для visual cue в admin fields list.
        // Усі іконки доступні в Lucide-set (already loaded).
        $iconMap = [
            'text'             => 'type',
            'textarea'         => 'align-left',
            'email'            => 'mail',
            'tel_intl'         => 'phone',
            'select'           => 'list',
            'radio'            => 'circle-dot',
            'segmented'        => 'layout-grid',
            'checkbox'         => 'check-square',
            'consent'          => 'shield-check',
            'date'             => 'calendar',
            'time'             => 'clock',
            'number'           => 'hash',
            'hidden'           => 'eye-off',
            'computed_hidden'  => 'function-square',
            'html'             => 'code-2',
            'file'             => 'paperclip',
            'autocomplete_np'  => 'truck',
            'autocomplete_up'  => 'package-2',
            'address_select'   => 'map-pin',
            'group'            => 'layers',
        ];
        $types = FieldRegistry::listTypes();
        $result = [];
        foreach ($types as $code => $meta) {
            $result[] = [
                'code'              => $code,
                'label'             => $this->language->get($meta['label_key']) ?: $code,
                'icon'              => (string)($iconMap[$code] ?? 'square'),
                'has_mask'          => $meta['has_mask'],
                'has_options'       => $meta['has_options'],
                'has_default'       => $meta['has_default'],
                'has_placeholder'   => $meta['has_placeholder'],
                'has_tooltip'       => $meta['has_tooltip'],
                'default_belongs_to'=> $meta['default_belongs_to'],
                'stage'             => $meta['stage'],
                'integration'       => null,
            ];
        }
        // Доєднуємо field-types від enabled-інтеграцій
        $reg = $this->buildIntegrationsRegistry(0);
        foreach ($reg->all() as $integration) {
            if (!$integration->isEnabled()) continue;
            foreach ($integration->getProvidedFieldTypes() as $ft) {
                $result[] = [
                    'code'              => (string)($ft['code'] ?? ''),
                    'label'             => (string)($ft['label'] ?? ($ft['code'] ?? '')),
                    'icon'              => $integration->getIcon() ?: 'puzzle',
                    'has_mask'          => false,
                    'has_options'       => false,
                    'has_default'       => false,
                    'has_placeholder'   => true,
                    'has_tooltip'       => true,
                    'default_belongs_to'=> 'shipping',
                    'stage'             => 99,
                    'integration'       => $integration->getCode(),
                    'depends_on'        => (string)($ft['depends_on'] ?? ''),
                ];
            }
        }
        return $result;
    }

    /**
     * Згруповані типи полів (optgroup → list of codes).
     * Використовується в модалці створення/редагування поля.
     */
    /**
     * Список полів з мінімальним shape — для UI добавлення полів у блок.
     * Мерджимо native OC-поля (firstname/email/address_1/тощо) з кастомними з реєстру.
     * Native ідуть першими (з прапорцем `native: true`), потім custom.
     *
     * @return array<int, array{field_id:int, code:string, type:string, belongs_to:string, name:string, native:bool}>
     */
    private function buildFieldsListForJs(): array
    {
        $out = [];

        // ── Native OC fields ──────────────────────────────────────────────
        // sale/order дає більшість entry_* ключів; решта (password/confirm/
        // newsletter/register/agree) — у власному мовному файлі модуля.
        $this->load->language('sale/order');
        $this->load->language('extension/module/oc_kit_easycheckout');
        foreach (\OcKit\EasyCheckout\Libs\NativeFieldsRegistry::listAll() as $nf) {
            $name = $this->language->get($nf['lang_key']);
            if (!$name || $name === $nf['lang_key']) {
                $name = $nf['code'];   // fallback якщо OC не має такого ключа
            }
            $out[] = [
                'field_id'   => $nf['field_id'],
                'code'       => $nf['code'],
                'type'       => $nf['type'],
                'belongs_to' => $nf['belongs_to'],
                'name'       => $name,
                'native'     => true,
            ];
        }

        // ── Custom fields з нашого реєстру ────────────────────────────────
        $this->load->model('extension/module/oc_kit_easycheckout');
        $rows = $this->model_extension_module_oc_kit_easycheckout->listFields(['limit' => 500]);
        $primaryLangId = (int)$this->config->get('config_language_id');

        foreach ($rows as $f) {
            $name = '';
            if (!empty($f['descriptions'][$primaryLangId]['name'])) {
                $name = $f['descriptions'][$primaryLangId]['name'];
            } else {
                foreach (($f['descriptions'] ?? []) as $d) {
                    if (!empty($d['name'])) { $name = $d['name']; break; }
                }
            }
            $out[] = [
                'field_id'   => (int)$f['field_id'],
                'code'       => (string)$f['code'],
                'type'       => (string)$f['type'],
                'belongs_to' => (string)$f['belongs_to'],
                'name'       => $name ?: $f['code'],
                'native'     => false,
            ];
        }

        return $out;
    }

    /** Метадані блоків для UI конструктора. */
    private function buildBlockTypesForJs(): array
    {
        $types = \OcKit\EasyCheckout\Libs\BlockRegistry::listTypes();
        $result = [];
        foreach ($types as $code => $meta) {
            $result[] = [
                'code'           => $code,
                'label'          => $this->language->get($meta['label_key']) ?: $code,
                'icon'           => $meta['icon'],
                'unique'         => (bool)$meta['unique'],
                'has_fieldset'   => (bool)$meta['has_fieldset'],
                'has_visibility' => (bool)$meta['has_visibility'],
                'sort_default'   => (int)$meta['sort_default'],
            ];
        }
        return $result;
    }

    private function buildFieldTypeGroups(): array
    {
        $groups = [
            ['label_key' => 'fields_group_basic',    'codes' => ['text', 'textarea', 'select', 'radio', 'segmented', 'checkbox']],
            ['label_key' => 'fields_group_datetime', 'codes' => ['date', 'time']],
            ['label_key' => 'fields_group_hidden',   'codes' => ['hidden', 'computed_hidden', 'html']],
            ['label_key' => 'fields_group_address',  'codes' => ['country', 'zone', 'city', 'autocomplete_np', 'autocomplete_ukrposhta', 'address_select']],
            ['label_key' => 'fields_group_special',  'codes' => ['tel_intl', 'file', 'consent']],
            ['label_key' => 'fields_group_struct',   'codes' => ['group']],
        ];

        $allTypes = FieldRegistry::listTypes();
        $result = [];
        foreach ($groups as $g) {
            $items = [];
            foreach ($g['codes'] as $code) {
                if (!isset($allTypes[$code])) continue;
                $items[] = [
                    'code'  => $code,
                    'label' => $this->language->get($allTypes[$code]['label_key']) ?: $code,
                ];
            }
            if (!$items) continue;
            $result[] = [
                'label' => $this->language->get($g['label_key']),
                'items' => $items,
            ];
        }
        // Окрема група per-integration з provided field types
        $reg = $this->buildIntegrationsRegistry(0);
        foreach ($reg->all() as $integration) {
            if (!$integration->isEnabled()) continue;
            $ftypes = $integration->getProvidedFieldTypes();
            if (!$ftypes) continue;
            $items = [];
            foreach ($ftypes as $ft) {
                $items[] = ['code' => (string)($ft['code'] ?? ''), 'label' => (string)($ft['label'] ?? '')];
            }
            $result[] = ['label' => $integration->getName(), 'items' => $items];
        }
        return $result;
    }

    private function jsonResponse(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function loadAssets(): void
    {
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/easycheckout/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/sortable.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/assets/js/imask.min.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/fields.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/headings.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/pages.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/groups.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/abandoned.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/sections.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/modules/app.js');
        $this->document->addScript('view/javascript/ockit/easycheckout/assets/js/admin.js');
        // Alpine — вантажимо ОСТАННІМ (defer-style) — він автостартує і шукає x-data при DOMContentLoaded.
        $this->document->addScript('view/javascript/ockit/assets/js/alpine.min.js');
    }

    private function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_easycheckout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return empty($this->error);
    }

    private function buildLayoutData(): array
    {
        $token   = $this->session->data['user_token'];
        $self    = $this->url->link('extension/module/oc_kit_easycheckout', 'user_token=' . $token, true);
        $linkFor = function (string $section) use ($self): string {
            return $self . '&section=' . $section;
        };

        // Усі пункти ведуть на той самий контролер; параметр `?section=` визначає,
        // що покаже Alpine. `available` лишаємо лише як індикатор готовності фічі.
        $sidebar = [
            ['key' => 'general',   'label' => $this->language->get('sidebar_general'),
             'url' => $linkFor('general'),   'icon' => 'settings',         'available' => true],
            ['key' => 'pages',     'label' => $this->language->get('sidebar_pages'),
             'url' => $linkFor('pages'),     'icon' => 'layout-dashboard', 'available' => true],
            ['key' => 'fields',    'label' => $this->language->get('sidebar_fields'),
             'url' => $linkFor('fields'),    'icon' => 'list',             'available' => true],
            ['key' => 'headings',  'label' => $this->language->get('sidebar_headings'),
             'url' => $linkFor('headings'),  'icon' => 'heading',          'available' => true],
            ['key' => 'misc',      'label' => $this->language->get('sidebar_misc'),
             'url' => $linkFor('misc'),      'icon' => 'sliders',          'available' => true],
            ['key' => 'groups',    'label' => $this->language->get('sidebar_groups'),
             'url' => $linkFor('groups'),    'icon' => 'layers',           'available' => true],
            ['key' => 'abandoned', 'label' => $this->language->get('sidebar_abandoned'),
             'url' => $linkFor('abandoned'), 'icon' => 'shopping-cart',    'available' => true],
            ['key' => 'health',    'label' => $this->language->get('sidebar_health') ?: 'Health',
             'url' => $linkFor('health'),    'icon' => 'activity',         'available' => true],
            ['key' => 'js',        'label' => $this->language->get('sidebar_js') ?: 'JavaScript',
             'url' => $linkFor('js'),        'icon' => 'code',             'available' => true],
            ['key' => 'integrations', 'label' => $this->language->get('sidebar_integrations') ?: 'Інтеграції',
             'url' => $linkFor('integrations'), 'icon' => 'puzzle',         'available' => true],
            ['key' => 'address_formats', 'label' => $this->language->get('sidebar_address_formats') ?: 'Address formats',
             'url' => $linkFor('address_formats'), 'icon' => 'map-pin',    'available' => true],
            ['key' => 'restrictions',    'label' => $this->language->get('sidebar_restrictions')    ?: 'Restrictions',
             'url' => $linkFor('restrictions'),    'icon' => 'shield',     'available' => true],
            ['key' => 'modules',         'label' => $this->language->get('sidebar_modules')         ?: 'Payment / Shipping',
             'url' => $linkFor('modules'),         'icon' => 'truck',      'available' => true],
            ['key' => 'presets',   'label' => $this->language->get('sidebar_presets'),
             'url' => $linkFor('presets'),   'icon' => 'package',          'available' => true],
            ['key' => 'license',   'label' => $this->language->get('sidebar_license'),
             'url' => $linkFor('license'),   'icon' => 'key',              'available' => true],
        ];

        $this->load->model('extension/module/oc_kit_easycheckout');
        $stats = $this->model_extension_module_oc_kit_easycheckout->getStats();

        return [
            'heading_title'   => $this->language->get('heading_title'),
            'module_name'     => $this->language->get('module_name'),
            'sidebar_items'   => $sidebar,
            'stats'           => $stats,
            'breadcrumbs'     => [
                ['text' => $this->language->get('text_extension'),
                 'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true)],
                ['text' => $this->language->get('heading_title'), 'href' => '#'],
            ],
            'cancel'          => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'extensions_url'  => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            'lang_js'         => $this->buildJsLang(),
            'success'         => $this->session->data['success'] ?? '',
            'error_warning'   => $this->error['warning'] ?? '',
            'stores'          => $this->loadStoresForSelector(),
        ];
    }

    /**
     * Список stores для admin Pages-селектора. Завжди включає Default (id=0)
     * як першу опцію. Якщо у магазині лише 1 store, селектор не показується.
     */
    private function loadStoresForSelector(): array
    {
        $this->load->model('setting/store');
        $rows = $this->model_setting_store->getStores();
        $out = [['store_id' => 0, 'name' => $this->config->get('config_name') . ' (default)']];
        foreach ($rows as $r) {
            $out[] = ['store_id' => (int)$r['store_id'], 'name' => (string)$r['name']];
        }
        return $out;
    }

    private function buildGeneralFormData(): array
    {
        $c = function (string $key, $default = '') {
            return $this->request->post[$key] ?? $this->config->get($key) ?? $default;
        };
        $token = $this->session->data['user_token'];

        // Список країн для default_country select
        $this->load->model('localisation/country');
        $countries = $this->model_localisation_country->getCountries();

        // Initial integration status
        $this->load->model('extension/module/oc_kit_easycheckout');
        $integrationStatus = $this->model_extension_module_oc_kit_easycheckout->integrationStatus();

        return [
            'action' => $this->url->link('extension/module/oc_kit_easycheckout', 'user_token=' . $token, true),
            'module_oc_kit_easycheckout_status'                  => $c('module_oc_kit_easycheckout_status', 0),
            'module_oc_kit_easycheckout_replace_checkout_links'  => $c('module_oc_kit_easycheckout_replace_checkout_links', 0),
            'module_oc_kit_easycheckout_default_country_id'      => $c('module_oc_kit_easycheckout_default_country_id', $this->config->get('config_country_id')),
            'module_oc_kit_easycheckout_np_api_key'              => $c('module_oc_kit_easycheckout_np_api_key', ''),
            'module_oc_kit_easycheckout_ukrposhta_api_key'       => $c('module_oc_kit_easycheckout_ukrposhta_api_key', ''),
            'module_oc_kit_easycheckout_reminder_enabled'        => $c('module_oc_kit_easycheckout_reminder_enabled', 0),
            'module_oc_kit_easycheckout_reminder_delay_minutes'  => $c('module_oc_kit_easycheckout_reminder_delay_minutes', 60),
            'module_oc_kit_easycheckout_reminder_delays'         => $c('module_oc_kit_easycheckout_reminder_delays', ''),
            'module_oc_kit_easycheckout_reminder_subject'        => $this->loadReminderSubject(),
            'module_oc_kit_easycheckout_reminder_body'           => $this->loadReminderBody(),
            'module_oc_kit_easycheckout_abandoned_retention_days'=> $c('module_oc_kit_easycheckout_abandoned_retention_days', 90),
            'module_oc_kit_easycheckout_reminder_blacklist'      => $c('module_oc_kit_easycheckout_reminder_blacklist', ''),
            // Misc / Inshe
            'module_oc_kit_easycheckout_error_display_mode'      => $c('module_oc_kit_easycheckout_error_display_mode', 'inline_under_field'),
            'module_oc_kit_easycheckout_error_scroll_to_first'   => $c('module_oc_kit_easycheckout_error_scroll_to_first', 1),
            'module_oc_kit_easycheckout_theme_wrapper_selector'  => $c('module_oc_kit_easycheckout_theme_wrapper_selector', '.main-container'),
            'module_oc_kit_easycheckout_theme_remove_breadcrumbs'=> $c('module_oc_kit_easycheckout_theme_remove_breadcrumbs', 0),
            'module_oc_kit_easycheckout_js_before_init'          => $c('module_oc_kit_easycheckout_js_before_init', ''),
            'module_oc_kit_easycheckout_js_after_init'           => $c('module_oc_kit_easycheckout_js_after_init', ''),
            'module_oc_kit_easycheckout_js_before_confirm'       => $c('module_oc_kit_easycheckout_js_before_confirm', ''),
            'countries'                                          => $countries,
            'store_name'                                         => (string)$this->config->get('config_name'),
            'catalog_base_url'                                   => (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG),
            'image_base'                                         => (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG) . 'image/',
            'image_placeholder'                                  => $this->getImagePlaceholder(),
            'route_value'                                        => $c('module_oc_kit_easycheckout_route_keyword', 'easycheckout'),
            'module_oc_kit_easycheckout_route_keyword'           => $c('module_oc_kit_easycheckout_route_keyword', 'easycheckout'),
            'version'                                            => '0.1.0-dev',
            'user_token'                                         => $token,
            // Integration buttons
            'integration_status'   => $integrationStatus,
            'url_integration_setup'  => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/setupIntegration',  'user_token=' . $token, true)),
            'url_integration_remove' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/removeIntegration', 'user_token=' . $token, true)),
            'url_integration_status' => html_entity_decode($this->url->link('extension/module/oc_kit_easycheckout/integrationStatus', 'user_token=' . $token, true)),
        ];
    }

    /**
     * Завантажує (або повертає дефолти) email subject для reminder, multilang.
     */
    private function loadReminderSubject(): array
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $rows = $this->model_extension_module_oc_kit_easycheckout->getReminderTexts() ?: [];
        $subject = is_array($rows['subject'] ?? null) ? $rows['subject'] : [];
        return $subject ?: ['en-gb' => '{store_name}: complete your order'];
    }

    private function loadReminderBody(): array
    {
        $this->load->model('extension/module/oc_kit_easycheckout');
        $rows = $this->model_extension_module_oc_kit_easycheckout->getReminderTexts() ?: [];
        $body = is_array($rows['body'] ?? null) ? $rows['body'] : [];
        return $body ?: ['en-gb' =>
            "<p>Hi {firstname},</p>\n" .
            "<p>You started a checkout at <strong>{store_name}</strong> but didn't finish.</p>\n" .
            "<p><a href=\"{recovery_url}\">Complete your order</a></p>\n" .
            "<p>Cart total: {total} {currency}</p>"
        ];
    }

    private function buildLangStrings(): array
    {
        $keys = [
            'abandoned_heading', 'abandoned_help', 'abandoned_empty', 'abandoned_col_name', 'abandoned_col_customer',
            'abandoned_col_phone', 'abandoned_col_total', 'abandoned_col_products', 'abandoned_col_modified',
            'abandoned_stats_days', 'abandoned_stats_total', 'abandoned_stats_recovered', 'abandoned_stats_lost',
            'abandoned_stats_reminder', 'abandoned_search_ph', 'abandoned_filter_pending', 'abandoned_filter_notified',
            'abandoned_filter_recovered', 'abandoned_filter_all', 'abandoned_filter_min_total', 'abandoned_filter_max_total',
            'button_delete_selected', 'text_selected', 'abandoned_show_products', 'abandoned_col_note',
            'abandoned_note_ph', 'abandoned_view_order', 'abandoned_send_reminder_now', 'abandoned_send_reminder_confirm',
            'abandoned_reminder_sent', 'abandoned_notified_at_tooltip', 'cron_last_run_label', 'cron_never_ran',
            'cron_never_ran_help', 'fields_col_usage', 'fields_col_usage_tooltip', 'fields_col_langs',
            'fields_col_langs_tooltip', 'fields_col_usage_orders_short', 'fields_col_usage_orders_tooltip', 'fields_col_usage_layouts_short',
            'fields_col_usage_layouts_tooltip', 'fields_filter_used', 'fields_filter_unused', 'fields_filter_layouts',
            'text_heading_in_use', 'text_headings_in_use', 'button_clone', 'text_field_cloned',
            'text_heading_cloned', 'button_fields_export_tip', 'button_fields_import_tip', 'text_fields_imported',
            'layout_btn_clone_block', 'layout_block_cloned', 'layout_block_unique_no_clone', 'button_reminder_test',
            'button_reminder_preview', 'entry_reminder_test_email', 'text_reminder_test_sent', 'button_reminder_reset',
            'text_reminder_reset_confirm', 'text_reminder_reset_done', 'button_field_code_regen', 'text_field_name_empty',
            'text_field_name_unsupported', 'block_settings_same_as_shipping_toggle', 'help_same_as_shipping_toggle', 'block_settings_field_condition',
            'block_settings_field_width_full', 'block_settings_field_width_two_thirds', 'block_settings_field_width_half', 'block_settings_field_width_third',
            'fields_native_heading', 'fields_native_help', 'fields_native_modal_title',
            'block_settings_condition_show_if', 'block_settings_block_condition', 'block_settings_block_condition_enable', 'block_settings_block_condition_source_ph',
            'fields_btn_presets', 'text_apply_preset_confirm', 'text_preset_applied', 'option_bulk_import',
            'option_bulk_import_help', 'option_bulk_import_ph', 'option_bulk_import_replace', 'text_field_in_use',
            'text_fields_in_use', 'block_settings_condition_op_not_empty', 'block_settings_condition_op_empty', 'block_settings_condition_op_in',
            'block_settings_condition_value_ph', 'button_export_csv', 'text_copy_recovery_url', 'entry_reminder',
            'help_reminder', 'entry_reminder_enabled', 'entry_reminder_delay', 'help_reminder_delay',
            'entry_reminder_template', 'help_reminder_template', 'entry_reminder_subject', 'entry_reminder_body',
            'entry_abandoned_retention', 'help_abandoned_retention', 'entry_reminder_blacklist', 'help_reminder_blacklist',
            'heading_title', 'module_name', 'text_extension', 'text_module_brand',
            'text_version', 'text_dev_stage', 'text_coming_soon', 'text_success',
            'text_enabled', 'text_disabled', 'text_yes', 'text_no',
            'tab_general', 'entry_status', 'entry_route', 'entry_default_group',
            'entry_replace_checkout_links', 'help_replace_checkout_links', 'help_route', 'entry_integration',
            'help_integration', 'integration_active', 'integration_inactive', 'integration_btn_setup',
            'integration_btn_remove', 'integration_languages', 'integration_event_active', 'integration_event_inactive',
            'integration_activated', 'integration_deactivated', 'button_save', 'button_cancel',
            'button_apply', 'button_add', 'button_close', 'button_bulk_edit',
            'bulk_edit_modal_title', 'bulk_edit_apply_to', 'bulk_edit_apply_to_suffix', 'bulk_edit_no_change', 'bulk_edit_yes', 'fields_filter_usage',
            'bulk_edit_no', 'button_delete', 'button_edit', 'sidebar_assistant',
            'sidebar_general', 'sidebar_pages', 'sidebar_page_checkout', 'sidebar_fields',
            'sidebar_headings', 'sidebar_misc', 'sidebar_groups', 'sidebar_abandoned',
            'sidebar_presets', 'sidebar_license', 'text_license_active', 'text_license_dev',
            'fields_heading', 'fields_help', 'fields_empty', 'fields_filter_search',
            'fields_filter_type', 'fields_filter_belongs_to', 'fields_filter_all', 'fields_btn_add',
            'fields_btn_delete_selected', 'fields_col_id', 'fields_col_code', 'fields_col_type',
            'fields_col_belongs_to', 'fields_col_name', 'fields_col_modified', 'fields_col_actions',
            'fields_modal_title_add', 'fields_modal_title_edit', 'fields_section_text', 'fields_section_params',
            'fields_section_mask', 'fields_section_default', 'fields_section_validation', 'fields_section_options',
            'entry_field_code', 'entry_field_type', 'entry_field_belongs_to', 'entry_field_name',
            'entry_field_tooltip', 'entry_field_placeholder', 'entry_field_mask_mode', 'entry_field_mask_value',
            'entry_field_default_mode', 'entry_field_default_value', 'entry_field_save_to_comment', 'entry_field_options',
            'help_field_code', 'help_field_save_to_comment', 'help_field_mask', 'help_field_default',
            'help_field_options', 'mode_manual', 'mode_api', 'entry_field_api_method',
            'help_field_api_method', 'entry_field_use_mask', 'help_field_use_mask', 'entry_field_use_default',
            'help_field_use_default', 'belongs_to_order', 'belongs_to_customer', 'belongs_to_address',
            'option_label', 'option_value', 'option_add', 'option_remove',
            'text_field_saved', 'text_field_deleted', 'text_field_validation_error', 'text_confirm_delete_field',
            'text_confirm_delete_fields', 'error_field_code_required', 'error_field_code_format', 'error_field_code_duplicate', 'error_field_code_reserved',
            'error_field_type_invalid', 'error_field_name_required', 'field_type_text', 'field_type_textarea',
            'field_type_select', 'field_type_radio', 'field_type_checkbox', 'field_type_date',
            'field_type_hidden', 'field_type_html', 'field_type_segmented', 'field_type_consent',
            'field_type_tel_intl', 'field_type_autocomplete_np', 'field_type_autocomplete_ukrposhta', 'field_type_country_zone_cascade',
            'field_type_computed_hidden', 'field_type_group', 'field_type_address_select', 'field_type_file',
            // Custom methods (Twig labels)
            'modules_heading', 'modules_help', 'modules_payment_heading', 'modules_shipping_heading',
            'modules_col_override_icon', 'modules_col_sort', 'modules_empty',
            'cm_heading', 'cm_help', 'cm_add_variant', 'cm_add_group', 'cm_select_hint',
            'cm_field_name', 'cm_field_description', 'cm_cost_type', 'cm_cost_fixed', 'cm_cost_weight',
            'cm_cost_sum', 'cm_cost_sum_totals', 'cm_cost_api', 'cm_cost_rules_hint', 'cm_cost_api_hint',
            'cm_currency', 'cm_currency_default', 'cm_tax_class', 'cm_tax_none', 'cm_zero_cost_text',
            'cm_order_status', 'cm_payment_form_heading', 'cm_payment_info_form', 'cm_payment_info_hint',
            'cm_payment_info_mail', 'cm_conditions', 'cm_placeholder', 'cm_placeholder_always', 'cm_placeholder_unavailable',
            'cm_subtotals_heading', 'cm_subtotals_help', 'cm_sub_add', 'cm_sub_value', 'cm_sub_value_hint',
            'cm_sub_round', 'text_enabled',
            'block_settings_condition_match', 'block_settings_condition_match_all', 'block_settings_condition_match_any',
            'block_settings_condition_op_eq', 'block_settings_condition_op_neq', 'block_settings_condition_op_not_empty',
            'block_settings_condition_op_empty', 'block_settings_condition_op_in', 'block_settings_condition_add_rule',
            'button_save', 'button_delete', 'button_clone',
        ];
        $data = [];
        foreach ($keys as $k) {
            $data[$k] = $this->language->get($k);
        }
        return $data;
    }

    private function buildJsLang(): string
    {
        $keys = [
            'js_saving', 'js_saved', 'js_error', 'js_network_error',
            'js_confirm', 'text_copied', 'text_copy_recovery_url', 'layout_copy_from_confirm',
            'layout_copied', 'license_status_active', 'license_status_invalid', 'license_activated',
            'license_activate_failed', 'button_activate', 'preset_applied', 'preset_apply_confirm',
            'health_status_ok', 'health_status_warn', 'health_status_fail',
            'modules_heading', 'modules_help', 'modules_payment_heading', 'modules_shipping_heading',
            'cm_confirm_delete_group', 'cm_confirm_delete_method', 'cm_confirm_delete_subtotal', 'cm_cost_value_ph', 'cm_cond_source_ph', 'button_clone', 'modules_col_sort',
            'modules_col_status', 'modules_col_override_title', 'modules_col_override_description', 'modules_col_override_icon', 'modules_col_sort', 'modules_col_hide',
            'modules_empty', 'abandoned_heading', 'abandoned_help', 'abandoned_empty',
            'abandoned_col_name', 'abandoned_col_phone', 'abandoned_col_total', 'abandoned_col_products',
            'abandoned_col_modified', 'abandoned_stats_days', 'abandoned_stats_total', 'abandoned_stats_recovered',
            'abandoned_stats_lost', 'abandoned_stats_reminder', 'abandoned_search_ph', 'abandoned_filter_pending',
            'abandoned_filter_notified', 'abandoned_filter_recovered', 'abandoned_filter_all', 'abandoned_filter_min_total',
            'abandoned_filter_max_total', 'button_delete_selected', 'text_selected', 'text_total', 'abandoned_show_products',
            'abandoned_col_note', 'abandoned_note_ph', 'abandoned_view_order', 'abandoned_send_reminder_now',
            'abandoned_send_reminder_confirm', 'abandoned_reminder_sent', 'abandoned_notified_at_tooltip', 'cron_last_run_label',
            'cron_never_ran', 'cron_never_ran_help', 'fields_col_usage', 'fields_col_usage_tooltip',
            'fields_col_langs', 'fields_col_langs_tooltip', 'fields_col_usage_orders_short', 'fields_col_usage_orders_tooltip',
            'fields_col_usage_layouts_short', 'fields_col_usage_layouts_tooltip', 'fields_filter_used', 'fields_filter_unused',
            'fields_filter_layouts', 'fields_filter_all', 'fields_filter_usage',
            'text_heading_in_use', 'text_headings_in_use', 'button_clone',
            'text_field_cloned', 'text_heading_cloned', 'button_fields_export_tip', 'button_fields_import_tip',
            'text_fields_imported', 'layout_btn_clone_block', 'layout_block_cloned', 'layout_block_unique_no_clone',
            'button_reminder_test', 'button_reminder_preview', 'entry_reminder_test_email', 'text_reminder_test_sent',
            'button_reminder_reset', 'text_reminder_reset_confirm', 'text_reminder_reset_done', 'button_field_code_regen',
            'text_field_name_empty', 'text_field_name_unsupported', 'block_settings_same_as_shipping_toggle', 'help_same_as_shipping_toggle',
            'block_settings_field_condition', 'block_settings_condition_show_if', 'block_settings_block_condition', 'block_settings_block_condition_enable',
            'block_settings_block_condition_source_ph', 'fields_btn_presets', 'text_apply_preset_confirm', 'text_preset_applied',
            'block_settings_condition_match', 'block_settings_condition_match_all', 'block_settings_condition_match_any',
            'block_settings_condition_add_rule', 'block_settings_condition_remove_rule',
            'block_settings_condition_op_eq', 'block_settings_condition_op_neq',
            'option_bulk_import', 'option_bulk_import_help', 'option_bulk_import_ph', 'option_bulk_import_replace',
            'text_field_in_use', 'text_fields_in_use', 'block_settings_condition_op_not_empty', 'block_settings_condition_op_empty',
            'block_settings_condition_op_in', 'block_settings_condition_value_ph', 'button_export_csv', 'button_save',
            'button_close', 'button_edit', 'button_delete', 'button_add',
            'text_coming_soon', 'text_field_saved', 'text_field_deleted', 'text_field_validation_error',
            'text_confirm_delete_field', 'text_confirm_delete_fields', 'fields_modal_title_add', 'fields_modal_title_edit',
            'fields_btn_add', 'fields_btn_delete_selected', 'fields_empty', 'belongs_to_order',
            'belongs_to_customer', 'belongs_to_address', 'error_field_code_required', 'error_field_code_format',
            'error_field_code_duplicate', 'error_field_type_invalid', 'error_field_name_required', 'entry_field_code',
            'entry_field_type', 'entry_field_belongs_to', 'entry_field_name', 'entry_field_tooltip',
            'entry_field_placeholder', 'entry_field_mask_mode', 'entry_field_mask_value', 'entry_field_default_mode',
            'entry_field_default_value', 'entry_field_save_to_comment', 'entry_field_options', 'help_field_code',
            'help_field_save_to_comment', 'help_field_mask', 'help_field_default', 'help_field_options',
            'mode_manual', 'mode_api', 'entry_field_api_method', 'help_field_api_method',
            'entry_field_use_mask', 'help_field_use_mask', 'entry_field_use_default', 'help_field_use_default',
            'fields_section_text', 'fields_section_params', 'fields_section_mask', 'fields_section_default',
            'fields_section_validation', 'fields_section_options', 'fields_filter_search', 'fields_filter_type',
            'fields_filter_belongs_to', 'fields_filter_all', 'fields_heading', 'fields_help',
            'fields_col_id', 'fields_col_code', 'fields_col_type', 'fields_col_belongs_to',
            'fields_col_name', 'fields_col_modified', 'fields_col_actions', 'option_label',
            'option_value', 'option_add', 'option_remove', 'rules_help',
            'rules_empty', 'rules_btn_add', 'rules_error_text', 'rules_remove',
            'rule_type_not_empty', 'rule_type_length', 'rule_type_regex', 'rule_type_api',
            'rule_type_match', 'rule_param_min', 'rule_param_max', 'rule_param_pattern',
            'rule_param_method', 'rule_param_field_code', 'placeholder_rule_pattern', 'placeholder_rule_error',
            'mask_preview_label', 'mask_preview_placeholder', 'fields_section_type_params', 'entry_consent_policy_url',
            'entry_consent_version', 'entry_consent_store_meta', 'help_consent_version', 'entry_tel_default_country',
            'entry_tel_preferred_countries', 'help_tel_preferred', 'entry_np_scope', 'entry_np_api_key',
            'help_np_api_key', 'np_scope_city', 'np_scope_warehouse', 'entry_computed_source',
            'help_computed_source', 'entry_computed_extra', 'computed_source_utm_source', 'computed_source_utm_medium',
            'computed_source_utm_campaign', 'computed_source_utm_content', 'computed_source_utm_term', 'computed_source_referrer',
            'computed_source_cookie', 'computed_source_expression', 'entry_group_columns', 'entry_date_disable_past',
            'entry_date_min_days_ahead', 'entry_date_max_days_ahead', 'help_date_min_days_ahead', 'help_date_max_days_ahead',
            'entry_date_weekends', 'help_date_weekends', 'entry_time_working_hours', 'entry_time_working_from',
            'entry_time_working_to', 'entry_time_slot_minutes', 'help_time_slot_minutes', 'entry_time_min_hours_ahead',
            'help_time_min_hours_ahead', 'entry_time_weekends', 'weekday_0', 'weekday_1',
            'weekday_2', 'weekday_3', 'weekday_4', 'weekday_5',
            'weekday_6', 'entry_consent_information_id', 'help_consent_information_id', 'entry_consent_custom_label',
            'help_consent_custom_label', 'placeholder_information_search', 'settings_section_integrations', 'settings_help_integrations',
            'settings_section_country', 'settings_backup_heading', 'settings_backup_help', 'settings_export_btn',
            'settings_import_btn', 'settings_import_confirm',
            'entry_default_country', 'help_default_country', 'entry_integration_np_api_key', 'help_integration_np_api_key',
            'entry_integration_ukrposhta_api_key', 'help_integration_ukrposhta_api_key', 'layout_heading', 'layout_help',
            'layout_btn_save', 'layout_btn_reset', 'layout_confirm_reset', 'layout_reset_done',
            'layout_btn_preview', 'layout_preview_title', 'layout_btn_export_tip', 'layout_btn_import_tip',
            'layout_btn_import_confirm', 'layout_btn_collapse_all', 'layout_btn_expand_all', 'layout_btn_collapse_all_tip',
            'layout_store_label', 'layout_store_help', 'layout_copy_from_label', 'layout_copy_from_btn',
            'layout_copy_from_help', 'layout_warnings_heading', 'entry_reminder_delays', 'help_reminder_delays',
            'health_heading', 'health_help', 'health_check_module_status', 'health_check_cron_recent',
            'health_check_mail_engine', 'health_check_db_tables', 'health_check_ocmod_active', 'health_check_default_country',
            'health_check_layout_valid', 'health_status_ok', 'health_status_warn', 'health_status_fail',
            'entry_check', 'entry_detail', 'entry_status_label', 'button_refresh', 'button_add_format', 'button_add_restriction', 'presets_empty',
            'sidebar_js', 'js_heading', 'js_help', 'help_js_before_init', 'help_js_after_init', 'help_js_before_confirm',
            'sidebar_integrations', 'integrations_heading', 'integrations_help', 'integrations_empty', 'integrations_marketplace_hint',
            'integration_status_active', 'integration_status_inactive', 'integration_test_connection', 'integration_refresh_warehouses',
            'integration_purge_data', 'integration_purge_confirm', 'integration_refresh_running', 'integration_version',
            'integration_install_fields', 'integration_install_fields_help',
            'marketplace_heading', 'marketplace_help', 'marketplace_install', 'marketplace_uninstall',
            'marketplace_installed', 'marketplace_install_confirm', 'marketplace_uninstall_confirm',
            'button_back', 'integration_section_general_fallback', 'integration_section_health',
            'integration_health_last_refresh', 'integration_health_records', 'integration_health_status',
            'integration_health_ok', 'integration_health_stale',
            'marketplace_search_placeholder', 'marketplace_filter_all_countries',
            'marketplace_filter_all_categories', 'marketplace_update',
            'integration_add_to_layout',
            'button_settings',
            'js_api_heading', 'js_api_help', 'js_api_events_heading', 'js_api_methods_heading', 'js_api_when_heading',
            'js_event_ready', 'js_event_field_change', 'js_event_field_focus', 'js_event_field_blur',
            'js_event_payment_select', 'js_event_shipping_select', 'js_event_before_reload', 'js_event_after_reload',
            'js_event_abandoned_saved', 'js_event_before_confirm', 'js_event_order_confirmed',
            'presets_heading', 'presets_help', 'address_formats_heading', 'address_formats_help',
            'address_formats_col_scope', 'address_formats_col_scope_id', 'address_formats_col_language', 'address_formats_col_template',
            'address_formats_help_scope_id', 'address_formats_help_template', 'address_formats_empty', 'restrictions_heading',
            'address_formats_placeholders_label', 'address_formats_placeholders_insert',
            'restrictions_help', 'restrictions_col_groups', 'restrictions_col_total', 'restrictions_col_qty',
            'restrictions_col_weight', 'restrictions_col_error', 'restrictions_help_groups', 'restrictions_help_error',
            'restrictions_empty', 'restrictions_label_total', 'restrictions_label_qty', 'restrictions_label_weight',
            'restrictions_label_sort', 'restrictions_groups_placeholder', 'address_formats_scope_customer_group', 'address_formats_scope_shipping',
            'address_formats_scope_id_ph_shipping', 'address_formats_scope_id_ph_groups',
            'misc_heading', 'misc_help', 'misc_error_heading',
            'misc_theme_heading', 'misc_js_heading', 'misc_js_help', 'entry_error_display_mode',
            'help_error_display_mode', 'error_mode_inline', 'error_mode_top', 'error_mode_toast',
            'entry_error_scroll_to_first', 'help_error_scroll_to_first', 'entry_theme_wrapper', 'help_theme_wrapper',
            'entry_theme_remove_breadcrumbs', 'help_theme_remove_breadcrumbs', 'entry_js_before_init', 'entry_js_after_init',
            'entry_js_before_confirm', 'license_heading', 'license_help', 'license_label_plan',
            'license_label_domain', 'license_label_updates', 'license_activate_heading', 'license_label_key',
            'license_key_help', 'layout_btn_add_step', 'layout_btn_add_block', 'layout_btn_remove_step',
            'layout_btn_remove_block', 'layout_btn_settings', 'layout_step_title', 'layout_step_placeholder',
            'layout_no_more_blocks', 'layout_saved', 'layout_block_settings_soon', 'block_type_customer',
            'block_type_cart', 'block_type_payment_address', 'block_type_shipping_address', 'block_type_shipping',
            'block_type_payment', 'block_type_comment', 'block_type_agreement', 'block_type_help',
            'block_type_summary', 'block_type_payment_form', 'block_type_buttons', 'block_type_custom_html',
            'block_settings_visibility', 'block_settings_visibility_help', 'block_settings_audience', 'block_settings_hide_for_guests',
            'block_settings_hide_for_logged_in', 'block_settings_viewports', 'block_settings_viewports_help', 'block_settings_text_content',
            'block_settings_html_content', 'block_settings_advanced', 'block_settings_advanced_soon', 'block_settings_options',
            'block_settings_display', 'block_settings_agreement_required', 'block_settings_agreement_required_help', 'block_settings_registration_mode',
            'registration_mode_optional', 'registration_mode_required', 'registration_mode_disabled', 'block_settings_show_login_link',
            'block_settings_show_image', 'block_settings_show_model', 'block_settings_show_quantity_controls', 'block_settings_show_remove_btn',
            'block_settings_show_cart_subtotal', 'block_settings_show_subtotal', 'block_settings_show_taxes', 'block_settings_show_coupon_input',
            'block_settings_show_voucher_input', 'block_settings_show_reward_input', 'block_settings_display_mode', 'block_settings_display_radio',
            'block_settings_display_select', 'block_settings_auto_select_first', 'block_settings_show_description', 'block_settings_submit_text',
            'block_settings_submit_text_help', 'block_settings_show_agreement_inline', 'block_settings_show_agreement_inline_help', 'block_settings_sticky_on_mobile',
            'block_settings_sticky_on_mobile_help', 'block_settings_show_company', 'block_settings_address_fieldset_hint', 'block_settings_payment_form_hint',
            'block_settings_fields', 'block_settings_fields_help', 'block_settings_fields_empty', 'block_settings_field_add',
            'block_settings_field_no_more', 'block_settings_field_remove', 'block_settings_field_up', 'block_settings_field_down',
            'block_settings_field_reorder', 'block_settings_field_required', 'block_settings_field_reload', 'block_settings_field_vis_always',
            'block_settings_field_vis_guests', 'block_settings_field_vis_logged', 'block_settings_field_width', 'groups_heading', 'groups_help',
            'groups_empty', 'groups_btn_add', 'groups_btn_clone', 'groups_btn_clone_create',
            'groups_col_id', 'groups_col_name', 'groups_col_slug', 'groups_col_default',
            'groups_col_sort', 'groups_col_url_example', 'groups_col_actions', 'groups_is_default',
            'groups_modal_title_add', 'groups_modal_title_edit', 'groups_inline_rename_hint', 'groups_drag_hint',
            'groups_modal_title_clone', 'groups_clone_help', 'entry_group_name', 'entry_group_slug',
            'entry_group_is_default', 'entry_group_sort_order', 'help_group_name', 'help_group_slug',
            'help_group_is_default', 'headings_heading', 'headings_help', 'headings_empty',
            'headings_filter_search', 'headings_filter_tag', 'headings_btn_add', 'headings_btn_delete_selected',
            'headings_col_id', 'headings_col_code', 'headings_col_tag', 'headings_col_text',
            'headings_col_modified', 'headings_col_actions', 'headings_modal_title_add', 'headings_modal_title_edit',
            'entry_heading_code', 'entry_heading_tag', 'entry_heading_text', 'heading_tag_none',
            'heading_tag_h1', 'heading_tag_h2', 'heading_tag_h3', 'heading_tag_h4',
            'heading_tag_h5', 'heading_tag_p', 'heading_tag_legend', 'text_heading_saved',
            'text_heading_deleted', 'text_heading_validation_error', 'text_confirm_delete_heading', 'text_confirm_delete_headings',
            'error_heading_text_required', 'layout_btn_add_row', 'layout_btn_remove_row', 'layout_group_selector_label',
            'layout_group_selector_help', 'layout_row_1_col', 'layout_row_2_col', 'layout_row_3_col',
            'layout_row_cols', 'layout_viewport_label', 'layout_stack_hint', 'layout_custom_order_label',
            'layout_reset_order', 'text_group_saved', 'text_group_deleted', 'text_group_cloned',
            'text_confirm_delete_group', 'error_group_cannot_delete_default',
            'bulk_edit_apply_to', 'bulk_edit_apply_to_suffix',
            'entry_reminder_subject', 'entry_reminder_body', 'entry_reminder_template',
        ];
        $map = [];
        foreach ($keys as $k) {
            $map[$k] = $this->language->get($k);
        }
        return json_encode($map, JSON_UNESCAPED_UNICODE);
    }
}
