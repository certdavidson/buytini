<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

/**
 * Defines translatable content types and their DB structure.
 */
class TypeDefinitions
{
    /**
     * @return array<string, array{
     *     table: string,
     *     id_field: string,
     *     parent_table: string,
     *     fields: string[],
     *     name_field: string|null  — field used as display name (null = use parent_table.name)
     * }>
     */
    public static function all(): array
    {
        return [
            'product' => [
                'table'        => 'product_description',
                'id_field'     => 'product_id',
                'parent_table' => 'product',
                'fields'       => ['name', 'description', 'description_short', 'tag', 'meta_title', 'meta_description', 'meta_keyword'],
                'name_field'   => 'name',
            ],
            'category' => [
                'table'        => 'category_description',
                'id_field'     => 'category_id',
                'parent_table' => 'category',
                'fields'       => ['name', 'description', 'meta_title', 'meta_description', 'meta_keyword'],
                'name_field'   => 'name',
            ],
            'manufacturer' => [
                'table'        => 'manufacturer_description',
                'id_field'     => 'manufacturer_id',
                'parent_table' => 'manufacturer',
                // No name in manufacturer_description; name lives in manufacturer.name
                'fields'       => ['description', 'description3', 'description_short', 'meta_title', 'meta_description', 'meta_keyword'],
                'name_field'   => null,
            ],
            'article' => [
                'table'        => 'article_description',
                'id_field'     => 'article_id',
                'parent_table' => 'article',
                'fields'       => ['name', 'description', 'meta_title', 'meta_description', 'meta_keyword'],
                'name_field'   => 'name',
            ],
            'blog_category' => [
                'table'        => 'blog_category_description',
                'id_field'     => 'blog_category_id',
                'parent_table' => 'blog_category',
                'fields'       => ['name', 'description', 'meta_title', 'meta_description', 'meta_keyword'],
                'name_field'   => 'name',
            ],
        ];
    }

    public static function get(string $type): array
    {
        $all = self::all();
        if (!isset($all[$type])) {
            throw new \InvalidArgumentException("Unknown type: {$type}");
        }
        return $all[$type];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
