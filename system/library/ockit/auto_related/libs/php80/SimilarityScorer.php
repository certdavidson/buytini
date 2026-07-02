<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * PHP 8.0+ implementation — uses constructor property promotion.
 * Loaded conditionally by AutoRelated.php when PHP_VERSION_ID >= 80000.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

use OcKit\AutoRelated\Dto\ProductSignals;
use OcKit\AutoRelated\Dto\ScoringWeights;

/**
 * Computes a similarity score [0.0 … 1.0] between two products.
 */
class SimilarityScorer
{
    public function __construct(
        private readonly ScoringWeights $weights
    ) {}

    public function score(ProductSignals $a, ProductSignals $b): float
    {
        $w = $this->weights;
        $score = 0.0;

        if ($w->category > 0) {
            $score += $w->category * $this->simCategory($a, $b);
        }
        if ($w->name > 0) {
            $score += $w->name * $this->simName($a, $b);
        }
        if ($w->neighborId > 0) {
            $score += $w->neighborId * $this->simNeighborId($a, $b);
        }
        if ($w->fields > 0) {
            $score += $w->fields * $this->simFields($a, $b);
        }
        if ($w->manufacturer > 0) {
            $score += $w->manufacturer * $this->simManufacturer($a, $b);
        }
        if ($w->attributes > 0) {
            $score += $w->attributes * $this->simAttributes($a, $b);
        }
        if ($w->priceRange > 0) {
            $score += $w->priceRange * $this->simPriceRange($a, $b);
        }
        // coorders signal is pre-computed externally and injected per batch

        return $score;
    }

    /**
     * Inject pre-computed coorders signal into a final score.
     */
    public function addCoordersSignal(float $baseScore, float $coordersSim): float
    {
        return $baseScore + $this->weights->coorders * $coordersSim;
    }

    // ── Signals ───────────────────────────────────────────────────────────────

    private function simCategory(ProductSignals $a, ProductSignals $b): float
    {
        if (empty($a->categoryIds) || empty($b->categoryIds)) {
            return 0.0;
        }
        $shared = count(array_intersect($a->categoryIds, $b->categoryIds));
        $total  = count(array_unique(array_merge($a->categoryIds, $b->categoryIds)));
        return $total > 0 ? $shared / $total : 0.0;
    }

    private function simName(ProductSignals $a, ProductSignals $b): float
    {
        $tokensA = $this->tokenize($a->name);
        $tokensB = $this->tokenize($b->name);
        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }
        $shared = count(array_intersect($tokensA, $tokensB));
        $total  = count(array_unique(array_merge($tokensA, $tokensB)));
        return $total > 0 ? $shared / $total : 0.0;
    }

    private function simNeighborId(ProductSignals $a, ProductSignals $b): float
    {
        $diff = abs($a->productId - $b->productId);
        $range = $this->weights->neighborRange;
        if ($diff === 0 || $diff > $range) {
            return 0.0;
        }
        return 1.0 - ($diff / $range);
    }

    private function simFields(ProductSignals $a, ProductSignals $b): float
    {
        $sep = $this->weights->fieldSeparator;
        $matchCount = 0;
        $checkedFields = 0;

        foreach ($this->weights->fieldList as $field) {
            $valA = trim((string)($a->fields[$field] ?? ''));
            $valB = trim((string)($b->fields[$field] ?? ''));

            if ($valA === '' || $valB === '') {
                continue;
            }

            $checkedFields++;

            // Split by separator if needed
            $partsA = $sep !== '' ? array_map('trim', explode($sep, $valA)) : [$valA];
            $partsB = $sep !== '' ? array_map('trim', explode($sep, $valB)) : [$valB];
            $partsA = array_filter($partsA);
            $partsB = array_filter($partsB);

            if (!empty(array_intersect($partsA, $partsB))) {
                $matchCount++;
            }
        }

        return $checkedFields > 0 ? $matchCount / $checkedFields : 0.0;
    }

    private function simManufacturer(ProductSignals $a, ProductSignals $b): float
    {
        if ($a->manufacturerId === 0 || $b->manufacturerId === 0) {
            return 0.0;
        }
        return $a->manufacturerId === $b->manufacturerId ? 1.0 : 0.0;
    }

    private function simAttributes(ProductSignals $a, ProductSignals $b): float
    {
        $filterIds = $this->weights->attributeIds;
        $minMatch  = $this->weights->attributeMinMatch;

        $attrsA = $a->attributeValues;
        $attrsB = $b->attributeValues;

        if (!empty($filterIds)) {
            $filterIds = array_map('intval', $filterIds);
            $attrsA = array_intersect_key($attrsA, array_flip($filterIds));
            $attrsB = array_intersect_key($attrsB, array_flip($filterIds));
        }

        if (empty($attrsA) || empty($attrsB)) {
            return 0.0;
        }

        $shared = 0;
        foreach ($attrsA as $attrId => $valA) {
            if (isset($attrsB[$attrId]) && mb_strtolower(trim($valA)) === mb_strtolower(trim($attrsB[$attrId]))) {
                $shared++;
            }
        }

        if ($shared < $minMatch) {
            return 0.0;
        }

        $total = count(array_unique(array_merge(array_keys($attrsA), array_keys($attrsB))));
        return $total > 0 ? $shared / $total : 0.0;
    }

    private function simPriceRange(ProductSignals $a, ProductSignals $b): float
    {
        $srcPrice  = $a->price;
        $candPrice = $b->price;

        if ($srcPrice <= 0.0 || $candPrice <= 0.0) {
            return 0.0;
        }

        $maxDiff = $srcPrice * $this->weights->priceRangePct / 100.0;
        $diff    = abs($srcPrice - $candPrice);

        if ($diff >= $maxDiff) {
            return 0.0;
        }

        return 1.0 - ($diff / $maxDiff);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function tokenize(string $text): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        // Remove very short tokens (articles, prepositions)
        return array_values(array_filter($tokens, fn($t) => mb_strlen($t) > 2));
    }
}
