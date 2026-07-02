<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Factory;

use OcKit\AdvancedSearchPro\SearchMode;
use OcKit\AdvancedSearchPro\Engine\NativeSearchEngine;
use OcKit\AdvancedSearchPro\Engine\ManticoreSearchEngine;
use OcKit\AdvancedSearchPro\Engine\SphinxSearchEngine;
use OcKit\AdvancedSearchPro\Engine\CascadeSearchEngine;

class SearchEngineFactory {
    private $db;
    private $config;
    private $manticoreClient;
    private $sphinxClient;

    public function __construct($db, $config, $manticoreClient, $sphinxClient = null) {
        $this->db = $db;
        $this->config = $config;
        $this->manticoreClient = $manticoreClient;
        $this->sphinxClient = $sphinxClient ?: $manticoreClient;
    }

    public function create($mode, array $settings = []) {
        $mode = SearchMode::normalize($mode);

        $native    = new NativeSearchEngine($this->db, $this->config);
        $manticore = new ManticoreSearchEngine(
            $this->manticoreClient,
            $settings['index'] ?? 'products',
            $settings
        );
        $sphinx = new SphinxSearchEngine(
            $this->sphinxClient,
            $settings['sphinx_index'] ?? ($settings['index'] ?? 'products'),
            $settings
        );

        if ($mode === SearchMode::MANTICORE) {
            // Same degraded-mode safety net as hybrid: native takes over only
            // when the daemon is down or the index is not built.
            return new CascadeSearchEngine(
                $manticore,
                $native,
                $this->manticoreClient,
                $settings['index'] ?? 'products'
            );
        }

        if ($mode === SearchMode::SPHINX) {
            return $sphinx;
        }

        if ($mode === SearchMode::HYBRID) {
            // Manticore is authoritative; native is degraded-mode only (daemon
            // down / index not built). Vector blending happens above this layer
            // in AdvancedSearchPro::semanticSearch + blendVectorResults.
            return new CascadeSearchEngine(
                $manticore,
                $native,
                $this->manticoreClient,
                $settings['index'] ?? 'products'
            );
        }

        return $native;
    }
}
