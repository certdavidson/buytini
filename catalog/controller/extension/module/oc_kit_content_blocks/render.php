<?php
/**
 * Content Blocks Pro — Catalog: render a single block by ID.
 * Entry point for [cb block_id=X] shortcodes in descriptions.
 *
 * Renders any block — shortcodes are placed by admins, so embedding any
 * existing block is intentional (matches Simple Blocks behaviour).
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksRender extends Controller
{
    public function index($args = []): string
    {
        $blockId = (int)($args['block_id'] ?? $this->request->get['block_id'] ?? 0);

        if (!$blockId || !$this->config->get('module_oc_kit_content_blocks_status')) {
            return '';
        }

        require_once DIR_APPLICATION . 'controller/extension/module/oc_kit_content_blocks.php';

        try {
            $ctrl = new ControllerExtensionModuleOcKitContentBlocks($this->registry);
            return $ctrl->renderById($blockId, false);
        } catch (\Throwable $e) {
            $this->log->write('Content Blocks render error block_id=' . $blockId . ': ' . $e->getMessage());
            return '';
        }
    }
}
