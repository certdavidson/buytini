<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * Каталог стартових layout-пресетів (ТЗ §22).
 * Завантажує усі `*.json` з директорії `presets/` поряд з `libs/`.
 * Структура файлу: { "code", "name", "description", "layout": {...} }.
 *
 * Native-field IDs використовуються від'ємні (наприклад -1 = email).
 */
final class LayoutPresets
{
    /** @return array<int,array{code:string,name:string,description:string}> */
    public static function listAll(): array
    {
        $out = [];
        foreach (self::loadAll() as $p) {
            $out[] = [
                'code'        => (string)$p['code'],
                'name'        => (string)($p['name'] ?? $p['code']),
                'description' => (string)($p['description'] ?? ''),
            ];
        }
        return $out;
    }

    /** Повертає layout-структуру для конкретного коду. Null якщо не знайдено. */
    public static function build(string $code): ?array
    {
        foreach (self::loadAll() as $p) {
            if ((string)$p['code'] === $code) {
                $layout = $p['layout'] ?? null;
                return is_array($layout) ? $layout : null;
            }
        }
        return null;
    }

    /** @return array<int,array{code:string,name?:string,description?:string,layout:array}> */
    private static function loadAll(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $dir = dirname(__DIR__) . '/presets';
        $cache = [];
        if (!is_dir($dir)) return $cache;

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw  = (string)file_get_contents($file);
            $data = json_decode($raw, true);
            if (!is_array($data) || empty($data['code']) || !isset($data['layout'])) continue;
            $cache[] = $data;
        }
        return $cache;
    }
}
