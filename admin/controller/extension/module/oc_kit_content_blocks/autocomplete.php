<?php
/**
 * Content Blocks Pro — AJAX: autocomplete for products, categories, articles.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksAutocomplete extends Controller
{
    public function index(): void
    {
        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([]));
            return;
        }

        $this->load->model('extension/module/oc_kit_content_blocks');
        $this->load->model('tool/image');
        $this->load->model('setting/setting');

        $type  = (string)($this->request->get['type'] ?? 'product');
        $query = (string)($this->request->get['filter_name'] ?? '');
        // Cap limit to keep an unauthenticated-looking attacker from sweeping
        // the whole catalog with limit=999999.
        $limit = max(1, min(50, (int)($this->request->get['limit'] ?? 20)));
        $json  = [];

        switch ($type) {
            case 'product':
                $results = $this->model_extension_module_oc_kit_content_blocks->searchProducts($query, $limit);
                foreach ($results as $r) {
                    $json[] = [
                        'id'    => (int)$r['product_id'],
                        'name'  => strip_tags(html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8')),
                        'model' => $r['model'],
                        'image' => $r['image']
                            ? $this->model_tool_image->resize($r['image'], 100, 100)
                            : $this->model_tool_image->resize('placeholder.png', 100, 100),
                    ];
                }
                break;

            case 'category':
                $results = $this->model_extension_module_oc_kit_content_blocks->searchCategories($query, $limit);
                foreach ($results as $r) {
                    $json[] = [
                        'id'    => (int)$r['category_id'],
                        'name'  => strip_tags(html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8')),
                        'image' => $r['image']
                            ? $this->model_tool_image->resize($r['image'], 100, 100)
                            : $this->model_tool_image->resize('placeholder.png', 100, 100),
                    ];
                }
                break;

            case 'article':
                $blogType = (string)$this->model_setting_setting->getSettingValue('module_oc_kit_content_blocks_blog_type') ?: 'default';
                $results  = $this->model_extension_module_oc_kit_content_blocks->searchArticles($query, $blogType, $limit);
                foreach ($results as $r) {
                    $json[] = [
                        'id'    => (int)$r['article_id'],
                        'name'  => strip_tags(html_entity_decode($r['name'], ENT_QUOTES, 'UTF-8')),
                        'image' => !empty($r['image'])
                            ? $this->model_tool_image->resize($r['image'], 100, 100)
                            : $this->model_tool_image->resize('placeholder.png', 100, 100),
                    ];
                }
                break;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
