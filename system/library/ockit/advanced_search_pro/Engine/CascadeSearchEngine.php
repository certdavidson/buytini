<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Engine;

use OcKit\AdvancedSearchPro\Contracts\SearchEngineInterface;
use OcKit\AdvancedSearchPro\ManticoreClient;

/**
 * CascadeSearchEngine — Manticore is the authoritative engine; native MySQL is
 * a DEGRADED-MODE replacement, NOT a relevance retry.
 *
 * Design (agreed 2026-06-02): when Manticore is healthy (daemon up + index
 * populated) it owns the result, even when that result is empty — a healthy
 * Manticore returning nothing means no product matches, and native MySQL
 * searching the SAME text would only repeat that nothing. Native runs ONLY when
 * Manticore is unavailable (daemon down) or its index is not built yet, so the
 * storefront still serves search instead of an empty page.
 *
 * The recall cascade (AND → quorum → QSUGGEST correction → fuzzy) lives inside
 * ManticoreSearchEngine; this class only chooses authoritative-vs-degraded.
 */
class CascadeSearchEngine implements SearchEngineInterface {
    private $manticore;
    private $native;
    private $client;
    private $index;

    public function __construct(
        SearchEngineInterface $manticore,
        SearchEngineInterface $native,
        ManticoreClient $client = null,
        $index = 'products'
    ) {
        $this->manticore = $manticore;
        $this->native    = $native;
        $this->client    = $client;
        $this->index     = (string)$index;
    }

    public function search($query, $limit, $offset = 0, $relax = true) {
        if ($this->client !== null && $this->client->isReady($this->index)) {
            // Manticore authoritative — its result stands, empty or not.
            try {
                return $this->manticore->search($query, $limit, $offset, $relax);
            } catch (\Throwable $e) {
                // Daemon passed the health check then died mid-request → degrade
                // for this query. A silent fallback to inferior native search is
                // an operational event the operator must see, so record it
                // (same error_log convention as NativeSearchEngine).
                error_log('[ASP] Manticore failed mid-request, degrading to native search: ' . $e->getMessage());
                return $this->native->search($query, $limit, $offset);
            }
        }

        // Degraded mode: daemon unreachable or index not built yet.
        return $this->native->search($query, $limit, $offset);
    }

    /** Expose the last typo correction from the Manticore engine, if any. */
    public function getLastCorrection() {
        return method_exists($this->manticore, 'getLastCorrection')
            ? $this->manticore->getLastCorrection()
            : null;
    }
}
