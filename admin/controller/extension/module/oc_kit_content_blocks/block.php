<?php
/**
 * Content Blocks Pro — AJAX: render an empty block shell for a new block type.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksBlock extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $blockType = (string)($this->request->post['block_type'] ?? '');
        $sortOrder = (int)($this->request->post['sort_order'] ?? 0);

        $types = $this->model_extension_module_oc_kit_content_blocks->getTypes();

        if (!isset($types[$blockType])) {
            $json['error'] = $this->language->get('error_invalid_type');
        } else {
            $presets = $this->model_extension_module_oc_kit_content_blocks->getPresets();

            $data = [
                'block_type'  => $blockType,
                'type_def'    => $types[$blockType],
                'sort_order'  => $sortOrder,
                'presets'     => $presets,
                'languages'   => $this->getLanguages(),
                'language_id' => (int)$this->config->get('config_language_id'),
                'type_name'   => $this->language->get('type_' . $blockType),
                'i18n'        => $this->buildI18n(),
            ];

            $json['html'] = $this->load->view('extension/module/ockit/content_blocks/block', $data);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getLanguages(): array
    {
        $this->load->model('localisation/language');
        return $this->model_localisation_language->getLanguages();
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
            'param_vertical', 'param_video_autoplay', 'param_source_categories', 'param_source_products', 'param_video_local', 'param_video_poster', 'param_video_thumb_auto', 'entry_video_url',
            'placeholder_search_product', 'placeholder_search_article', 'placeholder_search_category',
            'placeholder_accordion_title', 'placeholder_faq_question', 'placeholder_faq_answer',
            'placeholder_review_author', 'placeholder_review_text',
            'text_pick_product', 'text_pick_article', 'text_pick_category',
            'button_pick_product', 'button_pick_article', 'button_pick_category',
            'item_product', 'item_reviews', 'item_faq', 'item_carousel_product',
            'item_carousel_image', 'item_categories', 'item_blog_article',
            // Responsive order
            'param_responsive_order',
            // Type param labels (for per-block overrides)
            'param_limit',
            'param_collapse_in',
            'param_autoplay', 'param_pagination', 'param_arrows', 'param_loop', 'param_per_view',
            'param_show_price', 'param_show_button', 'param_show_rating',
            'param_show_attributes', 'param_attributes_count',
            'param_show_options', 'param_options_count',
            'param_show_description', 'param_features_disadvantages', 'param_description_length',
            'param_img_override', 'param_name_override', 'param_description_override',
            'param_popup_enable', 'param_popup_img_w', 'param_popup_img_h',
            'param_carousel', 'param_random',
        ];
        return \OcKit\ContentBlocks\ContentBlocks::buildI18n($this->language, $keys);
    }
}
