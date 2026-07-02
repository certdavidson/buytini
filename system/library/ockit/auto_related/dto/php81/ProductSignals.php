<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 8.1+ implementation — uses readonly constructor promotion and named arguments.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID >= 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Immutable value object: all scoring-relevant signals for one product.
 */
final class ProductSignals
{
    public function __construct(
        public readonly int    $productId,
        public readonly int    $manufacturerId,
        public readonly string $name,
        public readonly float  $price,           // product price for price-range signal
        public readonly array  $categoryIds,    // direct + parent category IDs
        public readonly array  $fields,         // ['sku' => 'ABC', 'mpn' => 'XYZ', ...]
        public readonly array  $attributeValues // [attribute_id => 'value', ...]
    ) {}

    public static function fromRow(array $row, array $categoryIds, array $fields, array $attributeValues): self
    {
        return new self(
            productId:       (int)$row['product_id'],
            manufacturerId:  (int)($row['manufacturer_id'] ?? 0),
            name:            (string)($row['name'] ?? ''),
            price:           (float)($row['price'] ?? 0.0),
            categoryIds:     $categoryIds,
            fields:          $fields,
            attributeValues: $attributeValues
        );
    }
}
