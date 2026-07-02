<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocks extends Controller
{
    private $error = [];

    // ─── Main settings page ────────────────────────────────────────────────────

    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        new \OcKit\ContentBlocks\ContentBlocks($this->registry); // constructor enforces license

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->load->model('setting/setting');

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/content-blocks/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/content-blocks/assets/css/coloris.min.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/coloris.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/sortable.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/admin.js');

        $token = $this->session->data['user_token'];

        $data = $this->buildLangStrings();

        $data['save_url']       = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/setting_save', 'user_token=' . $token, true));
        $data['presets_url']    = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/presets', 'user_token=' . $token, true));
        $data['stickers_url']   = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/stickers', 'user_token=' . $token, true));
        $data['migrate_url']    = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/migrate', 'user_token=' . $token, true));
        $data['demo_url']       = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/demo_page', 'user_token=' . $token, true));
        $data['submissions_url'] = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/submissions', 'user_token=' . $token, true));
        $data['global_url']     = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/global', 'user_token=' . $token, true));
        $data['extensions_url'] = html_entity_decode($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true));

        // Current settings
        $settings = $this->model_setting_setting->getSetting('module_oc_kit_content_blocks');

        $data['status']        = $settings['module_oc_kit_content_blocks_status'] ?? 0;
        $data['wysiwyg']       = $settings['module_oc_kit_content_blocks_wysiwyg_editor'] ?? 'jodit';
        $data['openai_key']    = $settings['module_oc_kit_content_blocks_openai_key'] ?? '';
        $data['blog_type']     = $settings['module_oc_kit_content_blocks_blog_type'] ?? 'default';
        $data['license_key']   = $settings['module_oc_kit_content_blocks_license_key'] ?? '';
        $data['license_info']  = \OcKit\ContentBlocks\ContentBlocks::getLicenseStatus($this->registry);
        $data['license_activate_url'] = html_entity_decode(
            $this->url->link('extension/module/oc_kit_content_blocks/activateLicense', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['module_version'] = \OcKit\ContentBlocks\Libs\StoreContext::VERSION;
        $data['types_config']  = $settings['module_oc_kit_content_blocks_types'] ?? [];
        $data['upload_dir']    = $settings['module_oc_kit_content_blocks_upload_dir'] ?? 'image/catalog/content-blocks';
        $data['enable_cache']  = $settings['module_oc_kit_content_blocks_enable_cache'] ?? 0;
        $data['custom_css']    = $settings['module_oc_kit_content_blocks_custom_css'] ?? '';
        $data['custom_js']     = $settings['module_oc_kit_content_blocks_custom_js'] ?? '';

        // Form-element defaults (per-field accept + max size). These apply to
        // every form rendered on the storefront unless a future field-level
        // override is added back. Defaults provide safe, working values out of
        // the box so admins don't have to remember MIME lists.
        $data['form_max_size']     = (int)($settings['module_oc_kit_content_blocks_form_max_size']     ?? 5120);
        $data['form_accept_file']  = (string)($settings['module_oc_kit_content_blocks_form_accept_file']  ?? '.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.rar,.7z');
        $data['form_accept_image'] = (string)($settings['module_oc_kit_content_blocks_form_accept_image'] ?? 'image/*');

        // Block types list for "Types" tab — add 'type' key so Twig can access type.type
        $typeDefs = $this->model_extension_module_oc_kit_content_blocks->getTypes();
        $blockTypes = [];
        foreach ($typeDefs as $typeKey => $typeDef) {
            $blockTypes[] = ['type' => $typeKey] + $typeDef;
        }
        $data['block_types']   = $blockTypes;

        // Presets for "Presets" tab — flat + grouped
        $presetsFlat = $this->model_extension_module_oc_kit_content_blocks->getPresets();
        $data['presets'] = $presetsFlat;
        $grouped = [];
        foreach ($presetsFlat as $p) {
            $g = $p['group'] ?? '';
            $grouped[$g][] = $p;
        }
        $data['presets_grouped'] = $grouped;

        // Stickers
        $data['stickers']      = $this->getStickers();

        // Languages for sticker multilingual
        $this->load->model('localisation/language');
        $data['languages']     = $this->model_localisation_language->getLanguages();

        // i18n for JS
        $data['i18n_js']       = $this->buildJsI18n();

        $data['header']        = $this->load->controller('common/header');
        $data['column_left']   = $this->load->controller('common/column_left');
        $data['footer']        = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/ockit/content_blocks/setting', $data)
        );
    }

    // ─── License ──────────────────────────────────────────────────────────────

    public function license(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/admin.js');

        $token = $this->session->data['user_token'];

        $licenseInfo = \OcKit\ContentBlocks\ContentBlocks::getLicenseStatus($this->registry);

        // Already active — bounce to settings
        if (!empty($licenseInfo['valid']) && ($licenseInfo['status'] ?? '') === 'active') {
            $this->response->redirect(
                $this->url->link('extension/module/oc_kit_content_blocks', 'user_token=' . $token, true)
            );
            return;
        }

        $data = $this->buildLangStrings();
        $data['heading_title']    = $this->language->get('heading_title');
        $data['license_info']     = $licenseInfo;
        $data['license_key']      = (string)($this->config->get('module_oc_kit_content_blocks_license_key') ?? '');
        $data['module_version']   = \OcKit\ContentBlocks\Libs\StoreContext::VERSION;
        $data['extensions_url']   = html_entity_decode($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true));
        $data['settings_url']     = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks', 'user_token=' . $token, true));
        $data['license_url']      = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/license', 'user_token=' . $token, true));
        $data['action_activate']  = html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks/activateLicense', 'user_token=' . $token, true));

        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_extensions'), 'href' => $data['extensions_url']],
            ['text' => $this->language->get('heading_title'),   'href' => $data['settings_url']],
            ['text' => $this->language->get('text_license') ?? 'License', 'href' => $data['license_url']],
        ];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/ockit/content_blocks/license', $data)
        );
    }

    public function activateLicense(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        $this->load->language('extension/module/oc_kit_content_blocks');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('error_permission')]);
            return;
        }

        $key = trim((string)($this->request->post['license_key'] ?? ''));

        if ($key === '') {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('text_license_invalid')]);
            return;
        }

        $result = \OcKit\ContentBlocks\ContentBlocks::activateLicenseKey($this->registry, $key);

        if (!isset($result['success'])) {
            $this->jsonResponse(['success' => false, 'message' => $this->language->get('text_license_api_error')]);
            return;
        }

        $token = $this->session->data['user_token'];

        $this->jsonResponse([
            'success'      => $result['success'],
            'message'      => $result['success']
                ? $this->language->get('text_license_active')
                : $this->language->get('text_license_invalid'),
            'info'         => $result['info'] ?? [],
            'redirect_url' => $result['success']
                ? html_entity_decode($this->url->link('extension/module/oc_kit_content_blocks', 'user_token=' . $token, true))
                : '',
        ]);
    }

    private function jsonResponse(array $data): void
    {
        \OcKit\ContentBlocks\ContentBlocks::json($this->response, $data);
    }

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->load->model('user/user_group');

        $this->model_extension_module_oc_kit_content_blocks->install();

        $groupId = $this->user->getGroupId();

        $routes = [
            'extension/module/oc_kit_content_blocks',
            'extension/module/oc_kit_content_blocks/setting_save',
            'extension/module/oc_kit_content_blocks/save',
            'extension/module/oc_kit_content_blocks/block',
            'extension/module/oc_kit_content_blocks/element',
            'extension/module/oc_kit_content_blocks/duplicate',
            'extension/module/oc_kit_content_blocks/translate',
            'extension/module/oc_kit_content_blocks/templates',
            'extension/module/oc_kit_content_blocks/video',
            'extension/module/oc_kit_content_blocks/autocomplete',
            'extension/module/oc_kit_content_blocks/upload',
            'extension/module/oc_kit_content_blocks/global',
        ];

        foreach ($routes as $route) {
            $this->model_user_user_group->addPermission($groupId, 'access', $route);
            $this->model_user_user_group->addPermission($groupId, 'modify', $route);
        }
    }

    public function uninstall(): void
    {
        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->load->model('user/user_group');

        $this->model_extension_module_oc_kit_content_blocks->uninstall();

        $this->model_user_user_group->removePermission(
            $this->user->getGroupId(), 'access', 'extension/module/oc_kit_content_blocks'
        );
        $this->model_user_user_group->removePermission(
            $this->user->getGroupId(), 'modify', 'extension/module/oc_kit_content_blocks'
        );
    }

    // ─── Stickers AJAX ───────────────────────────────────────────────────────

    public function stickers(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        new \OcKit\ContentBlocks\ContentBlocks($this->registry);

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('localisation/language');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $json    = [];
        $method  = $this->request->server['REQUEST_METHOD'];
        $post    = $this->request->post;
        $p       = DB_PREFIX;
        $db      = $this->registry->get('db');

        if ($method === 'POST') {
            $action = $post['action'] ?? '';

            if ($action === 'save') {
                $stickerId = (int)($post['sticker_id'] ?? 0);
                $sortOrder = (int)($post['sort_order'] ?? 0);
                $status    = (int)($post['status'] ?? 1);
                $color     = $db->escape($post['color'] ?? '');
                $bgColor   = $db->escape($post['bg_color'] ?? '');
                $position  = $db->escape($post['position'] ?? 'top-left');

                if ($stickerId > 0) {
                    $db->query("UPDATE `{$p}kit_cb_stickers`
                        SET `sort_order`='{$sortOrder}', `status`='{$status}',
                            `color`='{$color}', `bg_color`='{$bgColor}', `position`='{$position}'
                        WHERE `sticker_id`='{$stickerId}'");
                } else {
                    $db->query("INSERT INTO `{$p}kit_cb_stickers`
                        (`sort_order`,`status`,`color`,`bg_color`,`position`) VALUES
                        ('{$sortOrder}','{$status}','{$color}','{$bgColor}','{$position}')");
                    $stickerId = (int)$db->getLastId();
                }

                // Save descriptions
                if (!empty($post['text']) && is_array($post['text'])) {
                    foreach ($post['text'] as $langId => $text) {
                        $langId = (int)$langId;
                        $text   = $db->escape($text);
                        $db->query("INSERT INTO `{$p}kit_cb_sticker_description`
                            (`sticker_id`,`language_id`,`text`) VALUES ('{$stickerId}','{$langId}','{$text}')
                            ON DUPLICATE KEY UPDATE `text`='{$text}'");
                    }
                }

                $json['success'] = true;
                $json['sticker_id'] = $stickerId;

            } elseif ($action === 'delete') {
                $stickerId = (int)($post['sticker_id'] ?? 0);
                $db->query("DELETE FROM `{$p}kit_cb_stickers` WHERE `sticker_id`='{$stickerId}'");
                $json['success'] = true;
            }
        } else {
            // GET: return stickers list
            $json['stickers'] = $this->getStickers();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // ─── Presets AJAX ────────────────────────────────────────────────────────

    public function presets(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        new \OcKit\ContentBlocks\ContentBlocks($this->registry);

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $json   = [];
        $post   = $this->request->post;
        $action = $post['action'] ?? 'list';

        switch ($action) {
            case 'save':
                $presetId  = (int)($post['preset_id'] ?? 0);
                $name      = trim($post['name'] ?? '');
                $classes   = trim($post['classes'] ?? '');
                $group     = trim($post['group'] ?? '');
                $sortOrder = (int)($post['sort_order'] ?? 0);

                if ($name === '') {
                    $json['error'] = $this->language->get('error_preset_name_empty');
                } else {
                    $newId = $this->model_extension_module_oc_kit_content_blocks->savePreset(
                        $presetId, $name, $classes, $sortOrder, $group
                    );
                    $json['success']   = true;
                    $json['preset_id'] = $newId;
                }
                break;

            case 'delete':
                $this->model_extension_module_oc_kit_content_blocks->deletePreset((int)($post['preset_id'] ?? 0));
                $json['success'] = true;
                break;

            case 'reset':
                $this->model_extension_module_oc_kit_content_blocks->resetPresets();
                $json['success'] = true;
                $json['presets'] = $this->model_extension_module_oc_kit_content_blocks->getPresets();
                break;

            default:
                $json['presets'] = $this->model_extension_module_oc_kit_content_blocks->getPresets();
                break;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // ─── Migrate AJAX ────────────────────────────────────────────────────────

    public function migrate(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        new \OcKit\ContentBlocks\ContentBlocks($this->registry);

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $result = $this->model_extension_module_oc_kit_content_blocks->migrateFromSimpleBlocks();

        $json = [
            'migrated' => $result['migrated'],
            'errors'   => $result['errors'],
            'success'  => $this->language->get('text_migration_done') . $result['migrated'],
        ];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function getStickers(): array
    {
        $p   = DB_PREFIX;
        $db  = $this->registry->get('db');

        $rows = $db->query(
            "SELECT s.*, GROUP_CONCAT(CONCAT(sd.language_id, ':', sd.text) SEPARATOR '||') AS descriptions
             FROM `{$p}kit_cb_stickers` s
             LEFT JOIN `{$p}kit_cb_sticker_description` sd ON sd.sticker_id = s.sticker_id
             GROUP BY s.sticker_id
             ORDER BY s.sort_order ASC
             LIMIT 500"
        )->rows;

        $stickers = [];
        foreach ($rows as $row) {
            $texts = [];
            if (!empty($row['descriptions'])) {
                foreach (explode('||', $row['descriptions']) as $pair) {
                    [$langId, $text] = explode(':', $pair, 2);
                    $texts[(int)$langId] = $text;
                }
            }
            $stickers[] = [
                'sticker_id' => (int)$row['sticker_id'],
                'sort_order' => (int)$row['sort_order'],
                'status'     => (int)$row['status'],
                'color'      => $row['color'] !== '' ? $row['color'] : '#fff',
                'bg_color'   => $row['bg_color'] !== '' ? $row['bg_color'] : '#e74c3c',
                'position'   => $row['position'] ?? 'top-left',
                'text'       => $texts,
            ];
        }
        return $stickers;
    }

    private function buildLangStrings(): array
    {
        $keys = [
            'heading_title', 'heading_title_simple',
            'text_extensions', 'text_modules',
            'tab_general', 'tab_types', 'tab_stickers', 'tab_presets',
            'tab_integrations', 'tab_migration', 'tab_faq', 'tab_license',
            'entry_status', 'entry_wysiwyg', 'entry_openai_key', 'entry_openai_key_help',
            'entry_license_key', 'wysiwyg_jodit', 'wysiwyg_summernote', 'wysiwyg_ckeditor',
            'entry_type_status', 'entry_image_width', 'entry_image_height',
            'entry_author_img_w', 'entry_author_img_h',
            'device_mobile', 'device_tablet', 'device_desktop',
            'text_no_type_params',
            'param_limit',
            'param_collapse_in', 'param_vertical', 'param_autoplay', 'param_pagination',
            'param_source_categories', 'param_source_products',
            'param_arrows', 'param_loop', 'param_per_view', 'param_carousel', 'param_random',
            'param_show_price', 'param_show_button', 'param_show_rating', 'param_show_description',
            'param_show_attributes', 'param_show_options', 'param_features_disadvantages',
            'param_description_length',
            'param_popup_enable', 'param_popup_img_w', 'param_popup_img_h',
            'param_img_override', 'param_name_override', 'param_description_override',
            'param_attributes_count', 'param_options_count',
            'entry_upload_dir', 'entry_upload_dir_help', 'entry_enable_cache',
            'entry_custom_css', 'entry_custom_js',
            'entry_custom_css_help', 'entry_custom_js_help',
            'entry_form_max_size', 'entry_form_max_size_help',
            'entry_form_accept_file', 'entry_form_accept_file_help',
            'entry_form_accept_image', 'entry_form_accept_image_help',
            'text_stickers', 'column_sticker_text', 'column_sticker_color',
            'column_sticker_bg', 'column_sticker_border', 'column_sticker_radius',
            'column_sticker_status', 'column_sticker_pos', 'button_add_sticker',
            'entry_pos_top_left', 'entry_pos_top_right', 'entry_pos_bottom_left', 'entry_pos_bottom_right',
            'text_presets_help', 'column_preset_group', 'column_preset_name', 'column_preset_classes',
            'button_add_preset', 'button_reset_presets', 'text_reset_presets_confirm',
            'entry_blog_type', 'blog_type_default', 'blog_type_octemplates',
            'text_migration_desc', 'text_migration_warning', 'button_migrate',
            'text_demo_page_title', 'text_demo_page_desc',
            'button_demo_create', 'button_demo_delete',
            'text_demo_created', 'text_demo_deleted', 'text_demo_exists', 'text_demo_not_found',
            'button_save', 'button_cancel', 'button_activate',
            'text_success', 'error_permission',
            // License tab strings
            'text_license', 'text_license_buy', 'text_license_version', 'text_license_domain',
            'text_license_status_active', 'text_license_status_trial',
            'text_license_status_expired', 'text_license_status_invalid',
            'text_license_status_grace', 'text_license_status_not_validated',
        ];

        // Block type names
        foreach (['grid','video','accordion','faq','reviews','products_carousel',
                  'images_carousel','product','categories','blog_article'] as $type) {
            $keys[] = 'type_' . $type;
        }

        return \OcKit\ContentBlocks\ContentBlocks::buildI18n($this->language, $keys);
    }

    private function buildJsI18n(): array
    {
        return [
            'text_success'                => $this->language->get('text_success'),
            'error_permission'            => $this->language->get('error_permission'),
            'button_add_sticker'          => $this->language->get('button_add_sticker'),
            'button_add_preset'           => $this->language->get('button_add_preset'),
            'button_delete'               => $this->language->get('button_delete'),
            'text_migration_done'         => $this->language->get('text_migration_done'),
            'text_migrating'              => $this->language->get('text_migrating'),
            'text_reset_presets_confirm'  => $this->language->get('text_reset_presets_confirm'),
        ];
    }
}
