<?php
/**
 * Content Blocks Pro — AJAX: render a new element or item form.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksElement extends Controller
{
    /** Element types rendered from elements/ partials */
    private const ELEMENT_TYPES = ['text', 'image', 'html', 'video', 'divider', 'form'];

    /** Item types rendered from items/ partials */
    private const ITEM_TYPES = [
        'faq_item', 'reviews_item', 'product_item', 'carousel_product',
        'carousel_image', 'categories_item', 'blog_article_item', 'accordion_col',
    ];

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

        $elType    = (string)($this->request->post['el_type'] ?? '');
        $blockType = (string)($this->request->post['block_type'] ?? '');
        $sortOrder = (int)($this->request->post['sort_order'] ?? 0);

        $this->load->model('localisation/language');
        $this->load->model('tool/image');

        $languages  = $this->model_localisation_language->getLanguages();
        $languageId = (int)$this->config->get('config_language_id');
        $presets    = $this->model_extension_module_oc_kit_content_blocks->getPresets();
        $placeholder = $this->model_tool_image->resize('placeholder.png', 100, 100);

        $data = [
            'el_type'     => $elType,
            'block_type'  => $blockType,
            'sort_order'  => $sortOrder,
            'languages'   => $languages,
            'language_id' => $languageId,
            'presets'     => $presets,
            'placeholder' => $placeholder,
            'i18n'        => $this->buildI18n(),
        ];

        if (in_array($elType, self::ELEMENT_TYPES, true)) {
            $tpl = 'extension/module/ockit/content_blocks/elements/' . $elType;
        } elseif (in_array($elType, self::ITEM_TYPES, true)) {
            $tpl = 'extension/module/ockit/content_blocks/items/' . $elType;
        } else {
            $json['error'] = 'Unknown element type: ' . htmlspecialchars($elType);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $json['html'] = $this->load->view($tpl, $data);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function buildI18n(): array
    {
        $keys = [
            'entry_element_tag', 'entry_custom_class', 'entry_preset', 'entry_no_preset',
            'button_settings', 'button_delete',
            'el_text', 'el_image', 'el_html', 'el_video', 'el_divider', 'el_form',
            'form_builder_field_count', 'form_builder_configure', 'form_builder_no_recipient',
            'param_playerjs_enable', 'param_playerjs_poster',
            'param_autoplay', 'param_vertical', 'param_video_autoplay', 'param_video_local', 'param_video_poster', 'param_video_thumb_auto', 'entry_video_url',
            'placeholder_search_product', 'placeholder_search_article', 'placeholder_search_category',
            'placeholder_accordion_title', 'placeholder_faq_question', 'placeholder_faq_answer',
            'placeholder_review_author', 'placeholder_review_text',
            'text_pick_product', 'text_pick_article', 'text_pick_category',
            'button_pick_product', 'button_pick_article', 'button_pick_category',
            'item_product', 'item_reviews', 'item_faq', 'item_carousel_product',
            'item_carousel_image', 'item_categories', 'item_blog_article',
        ];
        return \OcKit\ContentBlocks\ContentBlocks::buildI18n($this->language, $keys);
    }
}
