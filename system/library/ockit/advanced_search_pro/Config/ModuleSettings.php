<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Config;

use OcKit\AdvancedSearchPro\SearchMode;

class ModuleSettings {
    private $config;
    private $keyPrefix;

    public function __construct($config, $keyPrefix = 'module_oc_kit_advanced_search_pro_') {
        $this->config = $config;
        $this->keyPrefix = $keyPrefix;
    }

    public function get(array $defaults) {
        $result = [];

        foreach ($defaults as $key => $default) {
            $fullKey = $this->keyPrefix . $key;
            $value = $this->config->get($fullKey);
            $result[$key] = ($value !== null && $value !== '') ? $value : $default;
        }

        $result['mode'] = SearchMode::normalize($result['mode'] ?? SearchMode::NATIVE);

        return $result;
    }
}
