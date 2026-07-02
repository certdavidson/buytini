<?php
/**
 * Content Blocks Pro — Admin: Global Blocks editor page.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksGlobal extends Controller
{
    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        new \OcKit\ContentBlocks\ContentBlocks($this->registry);

        $this->load->language('extension/module/oc_kit_content_blocks');
        $this->document->setTitle($this->language->get('heading_title') . ' — Global Blocks');

        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            $this->response->redirect(
                $this->url->link('common/login', '', true)
            );
            return;
        }

        $token = $this->session->data['user_token'];

        // Get editor HTML via the form sub-controller
        $cbFormHtml = '';
        $moduleStatus = (bool)$this->config->get('module_oc_kit_content_blocks_status');
        if ($moduleStatus) {
            try {
                $cbFormHtml = $this->load->controller(
                    'extension/module/oc_kit_content_blocks/form',
                    ['route' => '_global', 'page_id' => 0]
                );
            } catch (\Throwable $e) {
                $cbFormHtml = '';
            }
        }

        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'cb_form_html'  => $cbFormHtml,
            'module_status' => $moduleStatus,
            'text_module_disabled' => $this->language->get('text_module_disabled'),
            'text_settings'        => $this->language->get('text_settings'),
            'settings_url'  => $this->url->link('extension/module/oc_kit_content_blocks', 'user_token=' . $token, true),
            'breadcrumbs'   => [
                ['text' => $this->language->get('text_extensions'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true)],
                ['text' => $this->language->get('heading_title'),   'href' => $this->url->link('extension/module/oc_kit_content_blocks', 'user_token=' . $token, true)],
                ['text' => 'Global Blocks', 'href' => $this->url->link('extension/module/oc_kit_content_blocks/global', 'user_token=' . $token, true)],
            ],
            'header'      => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer'      => $this->load->controller('common/footer'),
        ];

        $this->response->setOutput(
            $this->load->view('extension/module/ockit/content_blocks/global', $data)
        );
    }
}
