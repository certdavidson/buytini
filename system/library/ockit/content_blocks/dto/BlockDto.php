<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Dto;

/**
 * Immutable value object representing a single content block.
 * Properties use snake_case to match DB columns and Twig template access.
 */
final class BlockDto
{
    public int    $block_id;
    public string $page_route;
    public int    $page_id;
    public string $block_type;   // DB column: type
    public string $block_name;   // DB column: name
    public string $theme;
    public int    $status;
    public int    $sort_order;
    public string $custom_class;
    public array  $custom_css;
    public array  $params;
    public bool   $is_global;

    /** @var RowDto[] — for structure='rows' (grid, video) and structure='cols' (accordion) */
    public array $rows = [];

    /** @var ElementDto[] — for structure='elements' (faq, reviews, carousel, etc.) */
    public array $elements = [];

    public function __construct(array $data)
    {
        $this->block_id    = (int)($data['block_id'] ?? 0);
        $this->page_route  = (string)($data['page_route'] ?? '');
        $this->page_id     = (int)($data['page_id'] ?? 0);
        $this->block_type  = (string)($data['type'] ?? '');
        $this->block_name  = (string)($data['name'] ?? '');
        $this->theme       = (string)($data['theme'] ?? 'default');
        $this->status      = (int)($data['status'] ?? 1);
        $this->sort_order  = (int)($data['sort_order'] ?? 0);
        $this->custom_class = (string)($data['custom_class'] ?? '');
        $this->custom_css  = JsonField::decode($data['custom_css'] ?? null, 'BlockDto.custom_css');
        $this->params      = JsonField::decode($data['params'] ?? null, 'BlockDto.params');
        $this->is_global   = (bool)($data['is_global'] ?? false);
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
