<?php
/**
 * Content Blocks Pro — AJAX: duplicate a block.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksDuplicate extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        $json    = [];
        $blockId = (int)($this->request->post['block_id'] ?? 0);

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif ($blockId <= 0) {
            $json['error'] = $this->language->get('error_block_not_found');
        } else {
            try {
                $newBlockId = $this->model_extension_module_oc_kit_content_blocks->duplicateBlock($blockId);
                $newBlock   = $this->model_extension_module_oc_kit_content_blocks->getBlock($newBlockId);
                $types      = $this->model_extension_module_oc_kit_content_blocks->getTypes();
                $presets    = $this->model_extension_module_oc_kit_content_blocks->getPresets();

                $this->load->model('localisation/language');
                $languages  = $this->model_localisation_language->getLanguages();
                $languageId = (int)$this->config->get('config_language_id');

                $typeDef  = $types[$newBlock->block_type] ?? [];
                $typeName = $this->language->get('type_' . $newBlock->block_type);

                $json['success']      = $this->language->get('text_block_duplicated');
                $json['new_block_id'] = $newBlockId;
                $json['html']         = $this->load->view('extension/module/ockit/content_blocks/block', [
                    'block'       => $newBlock,
                    'block_type'  => $newBlock->block_type,
                    'type_def'    => $typeDef,
                    'type_name'   => $typeName,
                    'sort_order'  => $newBlock->sort_order,
                    'languages'   => array_values($languages),
                    'language_id' => $languageId,
                    'presets'     => $presets,
                    'i18n'        => $this->buildI18n(),
                ]);

            } catch (\Throwable $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function buildI18n(): array
    {
        $keys = [
            'entry_block_name', 'entry_block_theme', 'button_add_row', 'button_add_col',
            'button_add_element', 'button_duplicate', 'button_translate',
            'button_save_as_template', 'button_settings', 'button_delete', 'button_collapse',
            'button_copy_shortcode', 'text_shortcode_copied', 'entry_block_status',
            'col_width_auto', 'device_mobile', 'device_tablet', 'device_desktop', 'text_row',
            'el_text', 'el_image', 'el_html', 'el_video',
            'param_playerjs_enable', 'param_playerjs_poster',
            'param_autoplay', 'param_vertical', 'param_video_autoplay', 'param_source_categories', 'param_source_products', 'param_video_local', 'param_video_poster', 'param_video_thumb_auto', 'entry_video_url',
            'placeholder_search_product', 'placeholder_search_article', 'placeholder_search_category',
            'placeholder_accordion_title', 'placeholder_faq_question', 'placeholder_faq_answer',
            'placeholder_review_author', 'placeholder_review_text',
            'text_pick_product', 'text_pick_article', 'text_pick_category',
            'button_pick_product', 'button_pick_article', 'button_pick_category',
            'item_product', 'item_reviews', 'item_faq', 'item_carousel_product',
            'item_carousel_image', 'item_categories', 'item_blog_article',
            // Block-level param labels (rendered inside .cb-block-params per type schema)
            'param_responsive_order', 'param_limit', 'param_collapse_in',
            'param_pagination', 'param_arrows', 'param_loop', 'param_per_view',
            'param_carousel', 'param_random',
            'param_show_price', 'param_show_button', 'param_show_rating',
            'param_show_attributes', 'param_attributes_count',
            'param_show_options', 'param_options_count',
            'param_show_description', 'param_features_disadvantages', 'param_description_length',
            'param_img_override', 'param_name_override', 'param_description_override',
            'param_popup_enable', 'param_popup_img_w', 'param_popup_img_h',
        ];
        return \OcKit\ContentBlocks\ContentBlocks::buildI18n($this->language, $keys);
    }
}
