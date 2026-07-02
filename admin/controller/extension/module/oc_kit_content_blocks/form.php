<?php
/**
 * Content Blocks Pro — AJAX: returns the block editor form HTML.
 * Called from OCMOD hooks in product/category/information/manufacturer edit pages.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksForm extends Controller
{
    /**
     * Entry point when called from OCMOD (passed as $args array).
     */
    public function index($args = []): string
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        // OCMOD-injected sub-controller — never redirect (host page would break).
        // Render an empty string when license is invalid; the host page just hides the tab.
        if (!\OcKit\ContentBlocks\ContentBlocks::isLicensed($this->registry)) {
            return '';
        }

        // Silent guard — host page handles its own auth; we just hide the tab when the
        // current user has no module access (e.g. viewer-only roles).
        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            return '';
        }

        try {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->load->model('setting/setting');

        // Resolve route and pageId from $args or GET
        if (is_array($args) && !empty($args['route'])) {
            $pageRoute = $args['route'];
            $pageId    = (int)($args['page_id'] ?? 0);
        } else {
            $pageRoute = (string)($this->request->get['route'] ?? '');
            $pageId    = (int)($this->request->get['page_id'] ?? 0);
        }

        // Connect assets
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/content-blocks/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/content-blocks/assets/css/coloris.min.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/sortable.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/coloris.min.js');
        $this->document->addScript('view/javascript/ockit/content-blocks/assets/js/admin.js');

        $token = $this->session->data['user_token'];

        // Load settings (getSetting uses json_decode for serialized fields)
        $settings    = $this->model_setting_setting->getSetting('module_oc_kit_content_blocks');
        $typesConfig = $settings['module_oc_kit_content_blocks_types'] ?? [];
        $wysiwyg     = $settings['module_oc_kit_content_blocks_wysiwyg_editor'] ?? 'jodit';

        // Load existing blocks
        $blocks    = $this->model_extension_module_oc_kit_content_blocks->getBlocks($pageRoute, $pageId);
        $this->enrichAutocompleteRefs($blocks);
        $types     = $this->model_extension_module_oc_kit_content_blocks->getTypes(is_array($typesConfig) && $typesConfig ? $typesConfig : null);
        $presetsFlat = $this->model_extension_module_oc_kit_content_blocks->getPresets();
        $presetsGrouped = [];
        foreach ($presetsFlat as $p) {
            $g = $p['group'] ?? '';
            $presetsGrouped[$g][] = $p;
        }
        $templates = $this->model_extension_module_oc_kit_content_blocks->getTemplates();

        $this->load->model('localisation/language');
        $languages  = array_values($this->model_localisation_language->getLanguages());
        $languageId = (int)$this->config->get('config_language_id');

        $data = [
            'page_route'  => $pageRoute,
            'page_id'     => $pageId,
            'user_token'  => $token,
            'blocks'      => $blocks,
            'types'       => $types,
            'presets'          => $presetsFlat,
            'presets_grouped'  => $presetsGrouped,
            'templates'   => $templates,
            'languages'   => $languages,
            'language_id' => $languageId,
            'wysiwyg'     => $wysiwyg,
            // Storefront base URL for <img src> previews (hidden input keeps the
            // bare relative path that OC filemanager natively returns).
            'catalog_url' => HTTPS_CATALOG ? HTTPS_CATALOG : HTTP_CATALOG,
            // Placeholder thumbnail (100×100) for empty image-pickers.
            'placeholder' => $this->loadPlaceholder(),

            // URLs
            'save_url'       => $this->url->link('extension/module/oc_kit_content_blocks/save', 'user_token=' . $token, true),
            'block_url'      => $this->url->link('extension/module/oc_kit_content_blocks/block',     'user_token=' . $token, true),
            'element_url'    => $this->url->link('extension/module/oc_kit_content_blocks/element',   'user_token=' . $token, true),
            'duplicate_url'  => $this->url->link('extension/module/oc_kit_content_blocks/duplicate', 'user_token=' . $token, true),
            'translate_url'  => $this->url->link('extension/module/oc_kit_content_blocks/translate', 'user_token=' . $token, true),
            'templates_url'  => $this->url->link('extension/module/oc_kit_content_blocks/templates', 'user_token=' . $token, true),
            'video_url'      => $this->url->link('extension/module/oc_kit_content_blocks/video',     'user_token=' . $token, true),
            'autocomplete_url' => $this->url->link('extension/module/oc_kit_content_blocks/autocomplete', 'user_token=' . $token, true),

            // Admin "edit entity" links — {ID} is replaced in Twig per element
            'entity_edit_urls' => [
                'product'  => $this->url->link('catalog/product/edit',  'user_token=' . $token . '&product_id={ID}',  true),
                'category' => $this->url->link('catalog/category/edit', 'user_token=' . $token . '&category_id={ID}', true),
                'article'  => $this->url->link('blog/article/edit',     'user_token=' . $token . '&article_id={ID}',  true),
            ],
            'upload_url'       => $this->url->link('extension/module/oc_kit_content_blocks/upload',      'user_token=' . $token, true),

            // i18n strings for JS
            'i18n' => $this->buildI18n(),
        ];

        return $this->load->view('extension/module/ockit/content_blocks/form', $data);
        } catch (\Throwable $e) {
            $this->log->write('Content Blocks form error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return '';
        }
    }

    /**
     * Backfills product/category/article name+image into element params for
     * already-saved blocks where only the *_id was stored (old saves had no
     * cb-ac-img/name fields). Without this the autocomplete-selected widget
     * shows blank thumb & name on form load even though the entity is set.
     * Images are resized to 80x80 to match the .cb-autocomplete-thumb tile.
     *
     * Works with BlockDto objects (admin's getBlocks returns DTOs); mutates
     * each ElementDto->params in place — Twig reads them as `el.params.*`.
     */
    private function enrichAutocompleteRefs(array $blocks): void
    {
        $allElements = []; // flat list of ElementDto refs
        foreach ($blocks as $block) {
            foreach (($block->elements ?? []) as $el) {
                $allElements[] = $el;
            }
            foreach (($block->rows ?? []) as $row) {
                foreach (($row->cols ?? []) as $col) {
                    foreach (($col->elements ?? []) as $el) {
                        $allElements[] = $el;
                    }
                }
            }
        }

        $productIds = $categoryIds = $articleIds = [];
        foreach ($allElements as $el) {
            $p = is_array($el->params ?? null) ? $el->params : [];
            $pid = (int)($p['product_id']  ?? 0);
            $cid = (int)($p['category_id'] ?? 0);
            $aid = (int)($p['article_id']  ?? 0);
            if ($pid && (empty($p['product_name'])  || empty($p['product_img'])))  $productIds[$pid]   = $pid;
            if ($cid && (empty($p['category_name']) || empty($p['category_img']))) $categoryIds[$cid] = $cid;
            if ($aid && (empty($p['article_name'])  || empty($p['article_img'])))  $articleIds[$aid]  = $aid;
        }
        if (!$productIds && !$categoryIds && !$articleIds) return;

        $this->load->model('tool/image');
        $langId = (int)$this->config->get('config_language_id');
        $px     = DB_PREFIX;
        $prods  = $cats = $arts = [];

        $thumb = function ($img) {
            return $img
                ? $this->model_tool_image->resize($img, 80, 80)
                : $this->model_tool_image->resize('placeholder.png', 80, 80);
        };

        if ($productIds) {
            $rows = $this->db->query("SELECT p.product_id, pd.name, p.image FROM `{$px}product` p
                LEFT JOIN `{$px}product_description` pd ON pd.product_id = p.product_id AND pd.language_id = '" . $langId . "'
                WHERE p.product_id IN (" . implode(',', $productIds) . ")")->rows;
            foreach ($rows as $r) {
                $prods[(int)$r['product_id']] = ['name' => $r['name'], 'image' => $thumb($r['image'])];
            }
        }
        if ($categoryIds) {
            $rows = $this->db->query("SELECT c.category_id, cd.name, c.image FROM `{$px}category` c
                LEFT JOIN `{$px}category_description` cd ON cd.category_id = c.category_id AND cd.language_id = '" . $langId . "'
                WHERE c.category_id IN (" . implode(',', $categoryIds) . ")")->rows;
            foreach ($rows as $r) {
                $cats[(int)$r['category_id']] = ['name' => $r['name'], 'image' => $thumb($r['image'])];
            }
        }
        if ($articleIds) {
            // Default OC `oc_information` has no `image` column — fall back to
            // placeholder. octemplates blog (oc_oct_blogarticle.image) is handled
            // by the autocomplete service when blog_type=octemplates.
            $blogType = (string)$this->config->get('module_oc_kit_content_blocks_blog_type') ?: 'default';
            if ($blogType === 'octemplates' && $this->db->query("SHOW TABLES LIKE '{$px}oct_blogarticle'")->num_rows) {
                $rows = $this->db->query("SELECT a.blogarticle_id AS id, a.title AS name, a.image
                    FROM `{$px}oct_blogarticle` a
                    WHERE a.blogarticle_id IN (" . implode(',', $articleIds) . ")")->rows;
                foreach ($rows as $r) {
                    $arts[(int)$r['id']] = ['name' => $r['name'], 'image' => $thumb($r['image'])];
                }
            } else {
                $rows = $this->db->query("SELECT i.information_id AS id, id.title AS name FROM `{$px}information` i
                    LEFT JOIN `{$px}information_description` id ON id.information_id = i.information_id AND id.language_id = '" . $langId . "'
                    WHERE i.information_id IN (" . implode(',', $articleIds) . ")")->rows;
                foreach ($rows as $r) {
                    $arts[(int)$r['id']] = ['name' => $r['name'], 'image' => $thumb('')];
                }
            }
        }

        // Mutate ElementDto->params in place (objects are references in PHP).
        foreach ($allElements as $el) {
            $p = is_array($el->params ?? null) ? $el->params : [];
            $pid = (int)($p['product_id']  ?? 0);
            $cid = (int)($p['category_id'] ?? 0);
            $aid = (int)($p['article_id']  ?? 0);

            if ($pid && isset($prods[$pid])) {
                if (empty($p['product_name'])) $p['product_name'] = $prods[$pid]['name'];
                if (empty($p['product_img']))  $p['product_img']  = $prods[$pid]['image'];
            }
            if ($cid && isset($cats[$cid])) {
                if (empty($p['category_name'])) $p['category_name'] = $cats[$cid]['name'];
                if (empty($p['category_img']))  $p['category_img']  = $cats[$cid]['image'];
            }
            if ($aid && isset($arts[$aid])) {
                if (empty($p['article_name'])) $p['article_name'] = $arts[$aid]['name'];
                if (empty($p['article_img']))  $p['article_img']  = $arts[$aid]['image'];
            }
            $el->params = $p;
        }
    }

    private function loadPlaceholder(): string
    {
        $this->load->model('tool/image');
        return $this->model_tool_image->resize('placeholder.png', 100, 100);
    }

    private function buildI18n(): array
    {
        $keys = [
            'text_content_blocks', 'button_add_block', 'button_add_from_template',
            'button_save_blocks', 'button_add_row', 'button_add_col', 'button_add_element',
            'button_duplicate', 'button_translate', 'button_save_as_template',
            'button_settings', 'button_delete', 'button_collapse',
            'button_copy_shortcode', 'text_shortcode_copied', 'entry_block_status',
            'entry_block_name', 'entry_block_theme', 'entry_col_width', 'col_width_auto',
            'text_no_blocks', 'text_row',
            'modal_title_block', 'modal_title_row', 'modal_title_col', 'modal_title_element',
            'tab_style', 'tab_class', 'tab_display', 'button_apply', 'button_cancel',
            'entry_bg_color', 'entry_text_color', 'entry_font_size', 'entry_font_weight',
            'entry_text_align', 'entry_padding', 'entry_margin', 'entry_border_radius',
            'entry_border', 'entry_custom_class', 'entry_preset', 'entry_no_preset',
            'entry_element_tag',
            'text_templates', 'text_no_templates', 'entry_template_name',
            'button_save_template', 'button_load_template', 'button_delete_template',
            'filter_all_types', 'text_template_saved',
            'text_select_language', 'text_translating', 'text_translated',
            'error_translation_failed', 'error_no_openai_key',
            'text_blocks_saved', 'text_block_deleted', 'text_block_duplicated',
            'error_save_failed', 'error_generic',
            'text_demo_delete_confirm', 'text_demo_warn_save_first',
            'device_mobile', 'device_tablet', 'device_desktop',
            'param_device_display', 'param_responsive_order',
            // Block type param labels (used in block.twig on initial page render)
            'text_no_type_params',
            'param_limit', 'param_collapse_in',
            'param_autoplay', 'param_pagination', 'param_arrows', 'param_loop', 'param_per_view',
            'param_carousel', 'param_random',
            'param_show_price', 'param_show_button', 'param_show_rating', 'param_show_description',
            'param_show_attributes', 'param_attributes_count',
            'param_show_options', 'param_options_count',
            'param_features_disadvantages', 'param_description_length',
            'param_img_override', 'param_name_override', 'param_description_override',
            'param_popup_enable', 'param_popup_img_w', 'param_popup_img_h',
            'param_vertical',
            // Block type names
            'type_grid', 'type_video', 'type_accordion', 'type_faq', 'type_reviews',
            'type_products_carousel', 'type_images_carousel', 'type_product',
            'type_categories', 'type_blog_article',
            // Element type names
            'el_text', 'el_image', 'el_html', 'el_video', 'el_divider', 'el_form',
            // Form builder (form element)
            'form_builder_title', 'form_builder_tab_general', 'form_builder_tab_fields',
            'form_builder_add_field', 'form_builder_no_fields', 'form_builder_configure',
            'form_builder_field_count', 'form_builder_no_recipient',
            'param_recipient_email', 'param_form_subject', 'param_success_message',
            'param_redirect_url', 'param_submit_label', 'param_max_file_size', 'param_captcha_enabled',
            'entry_field_type', 'entry_field_name', 'entry_field_label',
            'entry_field_placeholder', 'entry_field_required', 'entry_field_options', 'entry_field_accept',
            'field_text', 'field_email', 'field_tel', 'field_number', 'field_textarea',
            'field_select', 'field_checkbox', 'field_radio', 'field_file', 'field_image',
            // Image picker
            'button_pick_image', 'button_upload_image', 'button_clear_image',
            // Theme selector
            'text_select_theme',
            // Video element params
            'param_playerjs_enable', 'param_playerjs_poster',
            'param_autoplay', 'param_vertical', 'param_video_autoplay', 'param_source_categories', 'param_source_products', 'param_video_local', 'param_video_poster', 'param_video_thumb_auto', 'entry_video_url',
            // Element/item placeholders and labels
            'placeholder_search_product', 'placeholder_search_article', 'placeholder_search_category',
            'placeholder_accordion_title', 'placeholder_faq_question', 'placeholder_faq_answer',
            'placeholder_review_author', 'placeholder_review_text',
            'text_pick_product', 'text_pick_article', 'text_pick_category',
            'button_pick_product', 'button_pick_article', 'button_pick_category',
            'item_product', 'item_reviews', 'item_faq', 'item_carousel_product',
            'item_carousel_image', 'item_categories', 'item_blog_article',
            // Sticker positions
            'entry_pos_top_left', 'entry_pos_top_right', 'entry_pos_bottom_left', 'entry_pos_bottom_right',
        ];

        return \OcKit\ContentBlocks\ContentBlocks::buildI18n($this->language, $keys);
    }
}
