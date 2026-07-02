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

namespace OcKit\ContentBlocks\Libs;

/**
 * Registry of all block types and element types.
 *
 * Returns pure data structures (no language resolution, no DB).
 * Controllers resolve i18n keys using the language engine.
 *
 * structure values:
 *   'rows'     — rows → cols → elements (grid, video)
 *   'cols'     — 1 implicit row → cols → elements (accordion)
 *   'elements' — flat elements with col_id=0 (faq, reviews, carousels, etc.)
 */
class BlockTypeRegistry
{
    /**
     * Returns all block type definitions.
     * Keys are block type identifiers.
     */
    public function getTypes(): array
    {
        return [
            'grid' => [
                'name_key'         => 'type_grid',
                'icon'             => 'grid',
                'structure'        => 'rows',
                'add_element_type' => 'row',
                'params_schema'    => $this->deviceDisplaySchema() + $this->responsiveOrderSchema(),
            ],

            'video' => [
                'name_key'         => 'type_video',
                'icon'             => 'play-circle',
                'structure'        => 'elements',
                'add_element_type' => 'video',
                'params_schema'    => [
                    'limit'           => ['type' => 'number', 'label_key' => 'param_limit', 'default' => 10],
                ] + $this->deviceDisplaySchema(),
            ],

            'accordion' => [
                'name_key'         => 'type_accordion',
                'icon'             => 'layout-list',
                'structure'        => 'cols',
                'add_element_type' => 'accordion_col',
                'params_schema'    => [
                    'collapse_in' => ['type' => 'toggle', 'label_key' => 'param_collapse_in', 'default' => 0],
                ] + $this->deviceDisplaySchema(),
            ],

            'faq' => [
                'name_key'         => 'type_faq',
                'icon'             => 'help-circle',
                'structure'        => 'elements',
                'add_element_type' => 'faq_item',
                'params_schema'    => [
                    'collapse_in' => ['type' => 'toggle', 'label_key' => 'param_collapse_in', 'default' => 0],
                ] + $this->deviceDisplaySchema(),
            ],

            'reviews' => [
                'name_key'         => 'type_reviews',
                'icon'             => 'star',
                'structure'        => 'elements',
                'add_element_type' => 'reviews_item',
                'default_img_w'    => 80,
                'default_img_h'    => 80,
                'params_schema'    => [
                    'limit' => ['type' => 'number', 'label_key' => 'param_limit', 'default' => 10],
                ] + $this->deviceDisplaySchema(),
            ],

            'products_carousel' => [
                'name_key'         => 'type_products_carousel',
                'icon'             => 'shopping-cart',
                'structure'        => 'elements',
                'add_element_type' => 'carousel_product',
                'default_img_w'    => 300,
                'default_img_h'    => 300,
                'params_schema'    => [
                    'limit' => ['type' => 'number', 'label_key' => 'param_limit', 'default' => 10],
                ] + $this->carouselSchema() + [
                    'show_price'       => ['type' => 'toggle', 'label_key' => 'param_show_price',       'default' => 1],
                    'show_button'      => ['type' => 'toggle', 'label_key' => 'param_show_button',      'default' => 1],
                    'show_rating'      => ['type' => 'toggle', 'label_key' => 'param_show_rating',      'default' => 1],
                    'show_attributes'  => ['type' => 'toggle', 'label_key' => 'param_show_attributes',  'default' => 0],
                    'attributes_count' => ['type' => 'number', 'label_key' => 'param_attributes_count', 'default' => 3],
                    'show_options'     => ['type' => 'toggle', 'label_key' => 'param_show_options',     'default' => 0],
                    'options_count'    => ['type' => 'number', 'label_key' => 'param_options_count',    'default' => 3],
                ] + $this->deviceDisplaySchema(),
            ],

            'images_carousel' => [
                'name_key'         => 'type_images_carousel',
                'icon'             => 'image',
                'structure'        => 'elements',
                'add_element_type' => 'carousel_image',
                'default_img_w'    => 600,
                'default_img_h'    => 400,
                'params_schema'    => [
                    'limit' => ['type' => 'number', 'label_key' => 'param_limit', 'default' => 10],
                ] + $this->carouselSchema() + $this->deviceDisplaySchema(),
            ],

            'product' => [
                'name_key'         => 'type_product',
                'icon'             => 'box',
                'structure'        => 'elements',
                'add_element_type' => 'product_item',
                'default_img_w'    => 300,
                'default_img_h'    => 300,
                'params_schema'    => [
                    'show_description'        => ['type' => 'toggle', 'label_key' => 'param_show_description',        'default' => 1],
                    'show_price'              => ['type' => 'toggle', 'label_key' => 'param_show_price',              'default' => 1],
                    'show_button'             => ['type' => 'toggle', 'label_key' => 'param_show_button',             'default' => 1],
                    'show_rating'             => ['type' => 'toggle', 'label_key' => 'param_show_rating',             'default' => 1],
                    'show_attributes'         => ['type' => 'toggle', 'label_key' => 'param_show_attributes',        'default' => 0],
                    'attributes_count'        => ['type' => 'number', 'label_key' => 'param_attributes_count',       'default' => 3],
                    'show_options'            => ['type' => 'toggle', 'label_key' => 'param_show_options',           'default' => 0],
                    'options_count'           => ['type' => 'number', 'label_key' => 'param_options_count',          'default' => 3],
                    'features_disadvantages'  => ['type' => 'toggle', 'label_key' => 'param_features_disadvantages', 'default' => 0],
                    'description_length'      => ['type' => 'number', 'label_key' => 'param_description_length',     'default' => 150],
                    'img_override'            => ['type' => 'toggle', 'label_key' => 'param_img_override',           'default' => 0],
                    'name_override'           => ['type' => 'toggle', 'label_key' => 'param_name_override',          'default' => 0],
                    'description_override'    => ['type' => 'toggle', 'label_key' => 'param_description_override',   'default' => 0],
                    'popup_enable'            => ['type' => 'toggle', 'label_key' => 'param_popup_enable',           'default' => 0],
                    'popup_img_w'             => ['type' => 'number', 'label_key' => 'param_popup_img_w',            'default' => 800],
                    'popup_img_h'             => ['type' => 'number', 'label_key' => 'param_popup_img_h',            'default' => 800],
                    'additional_images'       => ['type' => 'toggle', 'label_key' => 'param_additional_images',      'default' => 0],
                    'additional_images_count' => ['type' => 'number', 'label_key' => 'param_additional_images_count','default' => 4],
                    'additional_img_w'        => ['type' => 'number', 'label_key' => 'param_additional_img_w',       'default' => 80],
                    'additional_img_h'        => ['type' => 'number', 'label_key' => 'param_additional_img_h',       'default' => 80],
                    'cart_add_fn'             => ['type' => 'text',   'label_key' => 'param_cart_add_fn',            'default' => 'cart.add', 'placeholder' => 'cart.add'],
                ] + $this->deviceDisplaySchema(),
            ],

            'categories' => [
                'name_key'         => 'type_categories',
                'icon'             => 'network',
                'structure'        => 'elements',
                'add_element_type' => 'categories_item',
                'default_img_w'    => 300,
                'default_img_h'    => 200,
                'params_schema'    => [
                    'limit'   => ['type' => 'number', 'label_key' => 'param_limit',   'default' => 10],
                    'carousel' => ['type' => 'toggle', 'label_key' => 'param_carousel', 'default' => 0],
                    'random'   => ['type' => 'toggle', 'label_key' => 'param_random',   'default' => 0],
                ] + $this->carouselSchema() + $this->deviceDisplaySchema(),
            ],

            'blog_article' => [
                'name_key'         => 'type_blog_article',
                'icon'             => 'file-text',
                'structure'        => 'elements',
                'add_element_type' => 'blog_article_item',
                'default_img_w'    => 400,
                'default_img_h'    => 250,
                'params_schema'    => [
                    'limit' => ['type' => 'number', 'label_key' => 'param_limit', 'default' => 10],
                ] + $this->deviceDisplaySchema(),
            ],


            // ── Suggested new types (each requires its own item template + frontend renderer) ──
            //   timeline      — chronological events (date + heading + description); structure 'elements'.
            //   pricing       — pricing tiers (title, price, features list, CTA); structure 'cols'.
            //   team          — team members (avatar, name, role, socials); structure 'elements'.
            //   stats_counter — animated number counters (value, label, icon); structure 'elements'.
            //   cta_banner    — full-width call-to-action (heading, subtext, button, bg image).
            //   feature_grid  — icon + heading + text trios (3- or 4-column); structure 'cols'.
            //   testimonial   — extended review w/ author photo + role; like reviews but card-style.
            //   logo_strip    — partner/brand logos in a row; structure 'elements'.
            //   countdown     — countdown timer to a target datetime (promo banners).
            //   tabs          — switcher (like ok-tabs-pills) with text/html panels; structure 'cols'.
        ];
    }

    /**
     * Returns a single block type definition or null if not found.
     */
    public function getType(string $type): ?array
    {
        return $this->getTypes()[$type] ?? null;
    }

    /**
     * Returns true if the given type is a valid block type.
     */
    public function isValidType(string $type): bool
    {
        return isset($this->getTypes()[$type]);
    }

    /**
     * Returns element type definitions for grid columns.
     * These are elements that can be added inside a column.
     */
    public function getElementTypes(): array
    {
        return [
            'text'      => ['name_key' => 'el_text',      'icon' => 'type'],
            'image'     => ['name_key' => 'el_image',     'icon' => 'image'],
            'html'      => ['name_key' => 'el_html',      'icon' => 'code'],
            'video'     => ['name_key' => 'el_video',     'icon' => 'play-circle'],
            'divider'   => ['name_key' => 'el_divider',   'icon' => 'minus'],
            'form'      => ['name_key' => 'el_form',      'icon' => 'mail'],
        ];
    }

    // ─── Private schema helpers ───────────────────────────────────────────────

    private function deviceDisplaySchema(): array
    {
        return [
            'device_display' => [
                'type'      => 'device_checkboxes',
                'label_key' => 'param_device_display',
                'default'   => ['mobile' => 1, 'tablet' => 1, 'desktop' => 1],
            ],
        ];
    }

    private function responsiveOrderSchema(): array
    {
        return [
            'responsive_order' => [
                'type'      => 'device_inputs',
                'label_key' => 'param_responsive_order',
                'default'   => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
            ],
        ];
    }

    private function carouselSchema(): array
    {
        return [
            'autoplay'   => ['type' => 'toggle', 'label_key' => 'param_autoplay',   'default' => 0],
            'pagination' => ['type' => 'toggle', 'label_key' => 'param_pagination', 'default' => 1],
            'arrows'     => ['type' => 'toggle', 'label_key' => 'param_arrows',     'default' => 1],
            'loop'       => ['type' => 'toggle', 'label_key' => 'param_loop',       'default' => 0],
            'per_view'   => [
                'type'      => 'device_inputs',
                'label_key' => 'param_per_view',
                'default'   => ['mobile' => 1, 'tablet' => 2, 'desktop' => 3],
            ],
        ];
    }
}
