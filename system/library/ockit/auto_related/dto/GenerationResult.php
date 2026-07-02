<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 7.4 implementation — explicit properties, no constructor promotion.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID < 80100.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Dto;

/**
 * Value object: result of generating related products for one product.
 */
final class GenerationResult
{
    public int    $productId;
    public array  $relatedIds;   // product_ids written to oc_product_related
    public bool   $skipped;      // true = had existing related and overwrite=false
    public ?string $error;

    public function __construct(int $productId, array $relatedIds, bool $skipped, ?string $error = null)
    {
        $this->productId  = $productId;
        $this->relatedIds = $relatedIds;
        $this->skipped    = $skipped;
        $this->error      = $error;
    }

    public function count(): int
    {
        return count($this->relatedIds);
    }

    public function isOk(): bool
    {
        return $this->error === null;
    }
}
