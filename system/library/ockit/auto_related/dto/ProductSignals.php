<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 7.4 implementation — explicit properties, no constructor promotion, no named arguments.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID < 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Value object: all scoring-relevant signals for one product.
 */
final class ProductSignals
{
    public int    $productId;
    public int    $manufacturerId;
    public string $name;
    public float  $price;           // product price for price-range signal
    public array  $categoryIds;    // direct + parent category IDs
    public array  $fields;         // ['sku' => 'ABC', 'mpn' => 'XYZ', ...]
    public array  $attributeValues; // [attribute_id => 'value', ...]

    public function __construct(int $productId, int $manufacturerId, string $name, float $price, array $categoryIds, array $fields, array $attributeValues)
    {
        $this->productId       = $productId;
        $this->manufacturerId  = $manufacturerId;
        $this->name            = $name;
        $this->price           = $price;
        $this->categoryIds     = $categoryIds;
        $this->fields          = $fields;
        $this->attributeValues = $attributeValues;
    }

    public static function fromRow(array $row, array $categoryIds, array $fields, array $attributeValues): self
    {
        return new self(
            (int)$row['product_id'],
            (int)($row['manufacturer_id'] ?? 0),
            (string)($row['name'] ?? ''),
            (float)($row['price'] ?? 0.0),
            $categoryIds,
            $fields,
            $attributeValues
        );
    }
}
