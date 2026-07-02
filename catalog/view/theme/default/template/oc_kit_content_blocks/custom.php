<?php
/**
 * Content Blocks Pro — Custom hook (theme-overridable).
 *
 * Called once per block render, AFTER built-in enrichBlockData().
 * Mutate $data freely — its keys end up as Twig variables in the theme.
 *
 * Available scope:
 *   $blockType  string   — 'grid' | 'reviews' | 'products_carousel' | ...
 *   $theme      string   — block theme key (e.g. 'default', 'steps', 'numbered')
 *   $block      array    — raw block row + nested rows/cols/elements
 *   $data       array    — Twig payload, mutate this
 *   $languageId int
 *   $this       Controller — full registry access (load->model, db, config, etc.)
 *
 * Theme overrides: copy this file to
 *   catalog/view/theme/{your_theme}/template/oc_kit_content_blocks/custom.php
 *
 * Per-type files (optional, take priority over this one):
 *   .../oc_kit_content_blocks/custom/{blockType}.php
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

switch ($blockType) {

    // Example — uncomment & adapt:
    //
    // case 'reviews':
    //     foreach ($data['items'] ?? [] as &$item) {
    //         $ed = $item['data'] ?? [];
    //         $item['pros'] = !empty($ed['pros']) ? explode(PHP_EOL, $ed['pros']) : [];
    //         $item['cons'] = !empty($ed['cons']) ? explode(PHP_EOL, $ed['cons']) : [];
    //     }
    //     unset($item);
    //     break;

}
