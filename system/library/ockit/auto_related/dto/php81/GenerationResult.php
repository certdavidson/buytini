<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 8.1+ implementation — uses readonly constructor promotion.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID >= 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Immutable value object: result of generating related products for one product.
 */
final class GenerationResult
{
    public function __construct(
        public readonly int    $productId,
        public readonly array  $relatedIds,   // product_ids written to oc_product_related
        public readonly bool   $skipped,      // true = had existing related and overwrite=false
        public readonly ?string $error = null
    ) {}

    public function count(): int
    {
        return count($this->relatedIds);
    }

    public function isOk(): bool
    {
        return $this->error === null;
    }
}
