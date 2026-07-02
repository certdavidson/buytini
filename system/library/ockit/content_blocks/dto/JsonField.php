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
 * Internal helper for DTOs: decodes a JSON column safely, defaulting to []
 * but emitting an error_log entry when malformed JSON is encountered so
 * silent corruption doesn't go unnoticed.
 */
final class JsonField
{
    public static function decode($value, string $context = ''): array
    {
        if (is_array($value)) {
            return $value;
        }
        $str = (string)($value ?? '');
        if ($str === '') {
            return [];
        }
        $decoded = json_decode($str, true);
        if (!is_array($decoded)) {
            error_log('Content Blocks: malformed JSON in ' . ($context ?: 'DTO field')
                . ' — ' . json_last_error_msg());
            return [];
        }
        return $decoded;
    }
}
