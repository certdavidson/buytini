<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 7.4 implementation — plain public properties (no readonly).
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID < 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Value object: normalized scoring weights + extra signal config.
 */
final class ScoringWeights
{
    public float $category;
    public float $name;
    public float $neighborId;
    public float $fields;
    public float $manufacturer;
    public float $attributes;
    public float $coorders;
    public float $priceRange;       // normalized weight for price-range signal

    // Extra config per signal
    public int   $neighborRange;      // ±N product_id range
    public array $fieldList;          // ['sku','mpn',...]
    public string $fieldSeparator;    // ',' or ';'
    public array $attributeIds;       // attribute_ids to compare
    public int   $attributeMinMatch;  // min shared attributes
    public int   $coordersDays;       // last N days of orders
    public int   $coordersMin;        // min shared orders
    public array $coordersStatuses;   // order_status_ids (empty = all)
    public int   $priceRangePct;      // max price deviation %, e.g. 20 = ±20%
    public bool  $brandPriority;      // push same-manufacturer products to front

    public function __construct(array $raw)
    {
        $neighborEnabled = (bool)(int)($raw['neighbor_enabled'] ?? 1);

        $w = [
            'category'     => (int)($raw['weight_category']     ?? 30),
            'name'         => (int)($raw['weight_name']         ?? 20),
            'neighbor_id'  => $neighborEnabled ? (int)($raw['weight_neighbor_id'] ?? 5) : 0,
            'fields'       => (int)($raw['weight_fields']       ?? 25),
            'manufacturer' => (int)($raw['weight_manufacturer'] ?? 20),
            'attributes'   => (int)($raw['weight_attributes']   ?? 30),
            'coorders'     => (int)($raw['weight_coorders']     ?? 40),
            'price_range'  => (int)($raw['weight_price_range']  ?? 0),
        ];

        $total = array_sum($w);

        $norm = function(int $v) use ($total): float {
            return $total > 0 ? round($v / $total, 6) : 0.0;
        };

        $this->category     = $norm($w['category']);
        $this->name         = $norm($w['name']);
        $this->neighborId   = $norm($w['neighbor_id']);
        $this->fields       = $norm($w['fields']);
        $this->manufacturer = $norm($w['manufacturer']);
        $this->attributes   = $norm($w['attributes']);
        $this->coorders     = $norm($w['coorders']);
        $this->priceRange   = $norm($w['price_range']);

        $this->neighborRange     = max(1, (int)($raw['neighbor_range']     ?? 50));
        $this->fieldList         = (array)($raw['field_list']              ?? ['sku', 'mpn']);
        $this->fieldSeparator    = (string)($raw['field_separator']        ?? ',');
        $this->attributeIds      = (array)($raw['attribute_ids']           ?? []);
        $this->attributeMinMatch = max(1, (int)($raw['attribute_min_match'] ?? 1));
        $this->coordersDays      = max(1, (int)($raw['coorders_days']      ?? 365));
        $this->coordersMin       = max(1, (int)($raw['coorders_min']       ?? 2));
        $this->coordersStatuses  = array_map('intval', (array)($raw['coorders_statuses'] ?? []));
        $this->priceRangePct     = max(1, (int)($raw['price_range_pct']    ?? 20));
        $this->brandPriority     = (bool)($raw['brand_priority']           ?? false);
    }

    public function hasAnyWeight(): bool
    {
        return ($this->category + $this->name + $this->neighborId +
                $this->fields + $this->manufacturer + $this->attributes +
                $this->coorders + $this->priceRange) > 0;
    }
}
