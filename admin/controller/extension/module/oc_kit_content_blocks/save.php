<?php
/**
 * Content Blocks Pro — AJAX: save blocks for a page
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksSave extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                // OC3 applies htmlspecialchars() to $this->request->post but leaves raw
                // $_POST untouched. Use raw $_POST for the JSON payload so quotes/ampersands
                // in user content survive untouched; scalar fields go through request->post.
                $pageRoute = (string)($this->request->post['page_route'] ?? '');
                $pageId    = (int)($this->request->post['page_id'] ?? 0);
                $rawBlocks = isset($_POST['blocks']) ? (string)$_POST['blocks'] : '';
                $blocks    = $rawBlocks !== '' ? json_decode($rawBlocks, true) : [];

                if (!is_array($blocks)) {
                    $blocks = [];
                }

                $blockIds = $this->model_extension_module_oc_kit_content_blocks->saveBlocks([
                    'page_route' => $pageRoute,
                    'page_id'    => $pageId,
                    'blocks'     => $blocks,
                ]);

                $json['success']   = $this->language->get('text_blocks_saved');
                $json['block_ids'] = $blockIds;
            } catch (\Throwable $e) {
                $json['error'] = $this->language->get('error_save_failed') . ': ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
