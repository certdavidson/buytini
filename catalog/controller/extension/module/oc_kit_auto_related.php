<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitAutoRelated extends Controller
{
    /**
     * Returns rule-based product blocks for a product (JSON).
     * Called via AJAX from product page.
     * Response: [{'rule_id': int, 'title': string, 'product_ids': int[]}, ...]
     */
    public function blocks(): void
    {
        $productId = (int)($this->request->get['product_id'] ?? 0);

        if (!$productId || !$this->config->get('module_oc_kit_auto_related_status')) {
            $this->jsonResponse([]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        $lib    = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
        $blocks = $lib->getBlocks($productId);

        $this->jsonResponse($blocks);
    }

    /**
     * Called via AJAX from product page (async mode).
     * Ensures related products are generated and logged.
     */
    public function ensure(): void
    {
        $productId = (int)($this->request->get['product_id'] ?? 0);

        if (!$productId) {
            $this->jsonResponse(['generated' => false, 'error' => 'invalid_product_id']);
            return;
        }

        if (!$this->config->get('module_oc_kit_auto_related_status')) {
            $this->jsonResponse(['generated' => false]);
            return;
        }

        require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
        $lib    = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
        $result = $lib->ensureRelated($productId);

        // Clear model-level cache so the next page load shows fresh related products
        if ($result->isOk() && !$result->skipped) {
            $langId = (int)$this->config->get('config_language_id');
            $this->cache->delete('related_products_ids_' . $productId);
            $this->cache->delete('related_products_data_' . $productId . '_lang_' . $langId);
        }

        $this->jsonResponse([
            'generated' => $result->isOk() && !$result->skipped,
            'count'     => $result->count(),
        ]);
    }

    private function jsonResponse(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
}
