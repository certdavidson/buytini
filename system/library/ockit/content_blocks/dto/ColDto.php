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
 * Immutable value object representing a column inside a row.
 * width: 0 = auto (equal distribution), 1-12 = Bootstrap grid columns.
 * Properties use snake_case to match DB columns and Twig template access.
 */
final class ColDto
{
    public int   $col_id;
    public int   $row_id;
    public int   $width;
    public array $custom_css;
    public array $params;
    public int   $sort_order;

    /** @var ElementDto[] */
    public array $elements = [];

    public function __construct(array $data)
    {
        $this->col_id     = (int)($data['col_id'] ?? 0);
        $this->row_id     = (int)($data['row_id'] ?? 0);
        $this->width      = (int)($data['width'] ?? 0);
        $this->custom_css = is_array($data['custom_css'] ?? null)
            ? $data['custom_css']
            : (json_decode((string)($data['custom_css'] ?? ''), true) ?: []);
        $this->params     = is_array($data['params'] ?? null)
            ? $data['params']
            : (json_decode((string)($data['params'] ?? ''), true) ?: []);
        $this->sort_order = (int)($data['sort_order'] ?? 0);
    }

    /**
     * Bootstrap column class string, e.g. "col-md-6" or "col" for auto.
     */
    public function getColClass(): string
    {
        return $this->width > 0 ? 'col-md-' . $this->width : 'col';
    }
}
