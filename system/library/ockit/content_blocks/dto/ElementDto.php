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
 * Immutable value object representing a content element.
 * Properties use snake_case to match DB columns and Twig template access.
 *
 * Element types inside grid columns: text, image, html, video
 * Item types in flat blocks: faq_item, reviews_item, product_item,
 *   carousel_product, carousel_image, categories_item, blog_article_item
 *
 * data: type-specific payload (decoded JSON)
 *   text    → {content: {lang_id: 'string'}, tag: 'p'}
 *   image   → {src: 'path', link: 'url', alt: 'string', title: 'string'}
 *   html    → {code: 'string'}
 *   video   → {url: 'string', poster: 'path', autoplay: 0}
 *   faq_item → {question: {lang_id: 'string'}, answer: {lang_id: 'string'}}
 *   etc.
 *
 * params: display settings (device_display, tag, etc.)
 */
final class ElementDto
{
    public int    $element_id;
    public int    $block_id;
    public int    $col_id;        // 0 = directly in block (flat structure)
    public string $element_type;  // DB column: type
    public array  $data;
    public array  $params;
    public string $custom_class;
    public array  $custom_css;
    public int    $preset_id;
    public int    $sort_order;

    public function __construct(array $row)
    {
        $this->element_id   = (int)($row['element_id'] ?? 0);
        $this->block_id     = (int)($row['block_id'] ?? 0);
        $this->col_id       = (int)($row['col_id'] ?? 0);
        $this->element_type = (string)($row['type'] ?? '');
        $this->data         = JsonField::decode($row['data'] ?? null, 'ElementDto.data');
        $this->params       = JsonField::decode($row['params'] ?? null, 'ElementDto.params');
        $this->custom_class = (string)($row['custom_class'] ?? '');
        $this->custom_css   = JsonField::decode($row['custom_css'] ?? null, 'ElementDto.custom_css');
        $this->preset_id    = (int)($row['preset_id'] ?? 0);
        $this->sort_order   = (int)($row['sort_order'] ?? 0);
    }

    /**
     * Get multilingual content field for a specific language.
     */
    public function getContent(int $languageId, string $field = 'content'): string
    {
        return (string)($this->data[$field][$languageId] ?? $this->data[$field][0] ?? '');
    }
}
