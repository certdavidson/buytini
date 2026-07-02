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
 * Immutable value object representing a row inside a grid/video/accordion block.
 * Properties use snake_case to match DB columns and Twig template access.
 */
final class RowDto
{
    public int   $row_id;
    public int   $block_id;
    public array $custom_css;
    public array $params;
    public int   $sort_order;

    /** @var ColDto[] */
    public array $cols = [];

    public function __construct(array $data)
    {
        $this->row_id     = (int)($data['row_id'] ?? 0);
        $this->block_id   = (int)($data['block_id'] ?? 0);
        $this->custom_css = JsonField::decode($data['custom_css'] ?? null, 'RowDto.custom_css');
        $this->params     = JsonField::decode($data['params'] ?? null, 'RowDto.params');
        $this->sort_order = (int)($data['sort_order'] ?? 0);
    }
}
