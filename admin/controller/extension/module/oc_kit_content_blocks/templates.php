<?php
/**
 * Content Blocks Pro — AJAX: block templates CRUD.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksTemplates extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->load->model('extension/module/oc_kit_content_blocks');

        $json   = [];
        $method = $this->request->server['REQUEST_METHOD'];
        $post   = $this->request->post;

        $needsModify = $method === 'POST' && in_array(($post['action'] ?? 'list'), ['save', 'delete'], true);
        $perm        = $needsModify ? 'modify' : 'access';
        if (!$this->user->hasPermission($perm, 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if ($method === 'POST') {
            $action = $post['action'] ?? 'list';

            switch ($action) {
                case 'save':
                    $name      = trim($post['name'] ?? '');
                    $blockType = trim($post['block_type'] ?? '');
                    // OC3 HTML-encodes request data — read raw JSON directly from $_POST
                    $data      = isset($_POST['data']) ? json_decode($_POST['data'], true) : [];

                    // Whitelist block_type against the registered types so we
                    // never persist an unknown identifier (would later 404 in
                    // the renderer).
                    $validTypes = array_keys($this->model_extension_module_oc_kit_content_blocks->getTypes());
                    if ($blockType !== '' && !in_array($blockType, $validTypes, true)) {
                        $json['error'] = $this->language->get('error_invalid_type');
                    } elseif ($name === '') {
                        $json['error'] = $this->language->get('entry_template_name') . ' required';
                    } else {
                        $id = $this->model_extension_module_oc_kit_content_blocks->saveTemplate($name, $blockType, $data ?: []);
                        $json['success']     = $this->language->get('text_template_saved');
                        $json['template_id'] = $id;
                    }
                    break;

                case 'delete':
                    $this->model_extension_module_oc_kit_content_blocks->deleteTemplate((int)($post['template_id'] ?? 0));
                    $json['success'] = true;
                    break;

                case 'load':
                    $tpl = $this->model_extension_module_oc_kit_content_blocks->getTemplate((int)($post['template_id'] ?? 0));
                    if ($tpl) {
                        $json['template'] = $tpl;
                    } else {
                        $json['error'] = $this->language->get('error_block_not_found');
                    }
                    break;

                default:
                    $blockType    = $post['block_type'] ?? '';
                    $json['templates'] = $this->model_extension_module_oc_kit_content_blocks->getTemplates($blockType);
                    break;
            }
        } else {
            // GET: return list
            $blockType         = (string)($this->request->get['block_type'] ?? '');
            $json['templates'] = $this->model_extension_module_oc_kit_content_blocks->getTemplates($blockType);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
