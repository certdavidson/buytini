<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro;

class SearchMode {
    const NATIVE = 'native';
    const MANTICORE = 'manticore';
    const SPHINX = 'sphinx';
    const HYBRID = 'hybrid';

    public static function normalize($mode) {
        $mode = strtolower(trim((string)$mode));
        if (!in_array($mode, [self::NATIVE, self::MANTICORE, self::SPHINX, self::HYBRID], true)) {
            return self::NATIVE;
        }

        return $mode;
    }
}
