<?php
/**
 * Content Blocks Pro — AJAX: AI translate block content.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksTranslate extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('setting/setting');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $post         = $this->request->post;
        // OC3 HTML-encodes request data — read raw JSON fields directly from $_POST
        $blockData    = isset($_POST['block_data']) ? json_decode($_POST['block_data'], true) : [];
        $sourceLang   = (string)($post['source_lang'] ?? '');
        $sourceLangId = (int)($post['source_lang_id'] ?? 0);
        $targetLang   = (string)($post['target_lang'] ?? '');
        $targetLangId = (int)($post['target_lang_id'] ?? 0);

        if (!is_array($blockData) || empty($blockData)) {
            $json['error'] = 'Invalid block data';
        } elseif (empty($targetLang)) {
            $json['error'] = 'Target language is required';
        } else {
            $apiKey = (string)$this->model_setting_setting->getSettingValue('module_oc_kit_content_blocks_openai_key');

            if (empty($apiKey)) {
                $json['error'] = $this->language->get('error_no_openai_key');
            } else {
                try {
                    require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
                    $cb = new \OcKit\ContentBlocks\ContentBlocks($this->registry);

                    $translated = $cb->translateBlock($blockData, $targetLang, $targetLangId, $sourceLang, $sourceLangId);

                    $json['success']   = $this->language->get('text_translated') . $targetLang;
                    $json['block_data'] = $translated;

                } catch (\OcKit\ContentBlocks\Exceptions\TranslationException $e) {
                    $json['error'] = $this->language->get('error_translation_failed') . ': ' . $e->getMessage();
                } catch (\Throwable $e) {
                    $json['error'] = $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
