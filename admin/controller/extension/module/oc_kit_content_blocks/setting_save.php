<?php
/**
 * Content Blocks — AJAX: save module settings
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksSettingSave extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('setting/setting');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $post = $this->request->post;

            // License key is rendered in the form with its full setting key
            // (matches PS pattern). The form round-trips it through POST so editSetting
            // preserves the current value across regular saves.
            $this->model_setting_setting->editSetting('module_oc_kit_content_blocks', [
                'module_oc_kit_content_blocks_status'         => (int)($post['status'] ?? 0),
                'module_oc_kit_content_blocks_wysiwyg_editor' => $post['wysiwyg_editor'] ?? 'jodit',
                'module_oc_kit_content_blocks_openai_key'     => trim($post['openai_key'] ?? ''),
                'module_oc_kit_content_blocks_blog_type'      => $post['blog_type'] ?? 'default',
                'module_oc_kit_content_blocks_types'          => $post['types'] ?? [],
                'module_oc_kit_content_blocks_image_sizes'    => $post['image_sizes'] ?? [],
                'module_oc_kit_content_blocks_upload_dir'     => trim($post['upload_dir'] ?? 'image/catalog/content-blocks'),
                'module_oc_kit_content_blocks_enable_cache'   => (int)($post['enable_cache'] ?? 0),
                'module_oc_kit_content_blocks_custom_css'     => $post['custom_css'] ?? '',
                'module_oc_kit_content_blocks_custom_js'      => $post['custom_js'] ?? '',
                'module_oc_kit_content_blocks_form_max_size'     => (int)($post['form_max_size']     ?? 5120),
                'module_oc_kit_content_blocks_form_accept_file'  => trim((string)($post['form_accept_file']  ?? '')),
                'module_oc_kit_content_blocks_form_accept_image' => trim((string)($post['form_accept_image'] ?? 'image/*')),
                'module_oc_kit_content_blocks_license_key'    => (string)($this->config->get('module_oc_kit_content_blocks_license_key') ?? ''),
            ]);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
