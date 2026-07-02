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
use OcKit\AdvancedSearchPro\SphinxClient;

class SphinxSearchEngine implements SearchEngineInterface {
    private $client;
    private $index;
    private $settings;

    // Sphinx field names that match the declared rt_field / sql_field columns
    private static $fieldWeightMap = [
        'title'        => 'name',
        'description'  => 'description',
        'tags'         => 'tag',
        'manufacturer' => 'manufacturer',
    ];

    public function __construct($client, $index = 'products', array $settings = []) {
        $this->client   = $client;
        $this->index    = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$index);
        if ($this->index === '') {
            $this->index = 'products';
        }
        $this->settings = $settings;
    }

    public function search($query, $limit, $offset = 0) {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $escapedQuery = $this->client->escape((string)$query);

        $optionParts  = [];
        $fieldWeights = $this->buildFieldWeights();
        if ($fieldWeights) {
            $optionParts[] = 'field_weights=(' . implode(',', $fieldWeights) . ')';
        }

        // NOTE: Sphinx does not support fuzzy=1 option in SQL protocol.
        // Fuzzy behaviour in Sphinx must be configured at index level via
        // morphology / min_infix_len / enable_star settings.

        $maxMatches    = max(1, $offset + $limit);
        $optionParts[] = 'max_matches=' . $maxMatches;

        $optionSql = ' OPTION ' . implode(', ', $optionParts);

        $sql = "SELECT id, weight() AS weight FROM " . $this->index
             . " WHERE MATCH('" . $escapedQuery . "')"
             . " LIMIT " . $offset . "," . $limit
             . $optionSql;

        $result = $this->client->queryWithMeta($sql);

        $ids = [];
        foreach ($result['rows'] as $row) {
            $ids[] = (int)$row['id'];
        }

        if (isset($result['meta']['total_found'])) {
            $total = (int)$result['meta']['total_found'];
        } elseif (isset($result['meta']['total'])) {
            $total = (int)$result['meta']['total'];
        } else {
            $total = count($ids);
        }

        return [
            'ids'   => $ids,
            'total' => $total,
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Build field_weights option from search_fields config.
     *
     * @return string[]
     */
    private function buildFieldWeights() {
        $searchFields = isset($this->settings['search_fields']) && is_array($this->settings['search_fields'])
            ? $this->settings['search_fields']
            : [];

        $defaults = [
            'title'        => 80,
            'description'  => 40,
            'tags'         => 15,
            'manufacturer' => 20,
        ];

        $weights = [];
        foreach (self::$fieldWeightMap as $key => $indexField) {
            // Field disabled via settings → skip (weight 0 means excluded)
            if ($searchFields && isset($searchFields[$key]) && empty($searchFields[$key]['enabled'])) {
                continue;
            }

            if (!empty($searchFields[$key]['weight'])) {
                $w = max(1, min(100, (int)$searchFields[$key]['weight']));
            } else {
                $w = $defaults[$key] ?? 50;
            }

            $weights[] = $indexField . '=' . $w;
        }

        return $weights;
    }
}
