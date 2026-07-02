<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 8.1+ implementation — uses readonly properties.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID >= 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Immutable value object: normalized scoring weights + extra signal config.
 */
final class ScoringWeights
{
    public readonly float $category;
    public readonly float $name;
    public readonly float $neighborId;
    public readonly float $fields;
    public readonly float $manufacturer;
    public readonly float $attributes;
    public readonly float $coorders;
    public readonly float $priceRange;       // normalized weight for price-range signal

    // Extra config per signal
    public readonly int   $neighborRange;      // ±N product_id range
    public readonly array $fieldList;          // ['sku','mpn',...]
    public readonly string $fieldSeparator;    // ',' or ';'
    public readonly array $attributeIds;       // attribute_ids to compare
    public readonly int   $attributeMinMatch;  // min shared attributes
    public readonly int   $coordersDays;       // last N days of orders
    public readonly int   $coordersMin;        // min shared orders
    public readonly array $coordersStatuses;   // order_status_ids (empty = all)
    public readonly int   $priceRangePct;      // max price deviation %, e.g. 20 = ±20%
    public readonly bool  $brandPriority;      // push same-manufacturer products to front

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

        $norm = fn(int $v): float => $total > 0 ? round($v / $total, 6) : 0.0;

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
