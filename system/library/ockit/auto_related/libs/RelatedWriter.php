<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

/**
 * Writes related product IDs to oc_product_related and logs to oc_auto_related_log.
 */
class RelatedWriter
{
    private \DB $db;

    public function __construct(\DB $db)
    {
        $this->db = $db;
    }

    /**
     * Replace related products for $productId with $relatedIds.
     * Removes old entries first, then inserts new ones (bidirectional).
     */
    public function write(int $productId, array $relatedIds): void
    {
        // Remove existing related for this product (one direction)
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "product_related` WHERE `product_id` = " . $productId
        );

        if (empty($relatedIds)) {
            return;
        }

        // Deduplicate, remove self-reference
        $relatedIds = array_values(array_unique(array_filter($relatedIds, fn($id) => (int)$id !== $productId)));

        foreach ($relatedIds as $relatedId) {
            $relatedId = (int)$relatedId;
            $this->db->query(
                "INSERT IGNORE INTO `" . DB_PREFIX . "product_related`
                 (`product_id`, `related_id`) VALUES (" . $productId . ", " . $relatedId . ")"
            );
        }
    }

    /**
     * Check if product already has related products in oc_product_related.
     */
    public function hasExisting(int $productId): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product_related`
             WHERE `product_id` = " . $productId
        );
        return (int)($result->row['cnt'] ?? 0) > 0;
    }

    /**
     * Log (upsert) generation result to oc_auto_related_log.
     */
    public function logGeneration(int $productId, int $count, string $source): void
    {
        $source = $this->db->escape($source);
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "auto_related_log`
             (`product_id`, `generated_at`, `source`, `count`)
             VALUES (" . $productId . ", NOW(), '" . $source . "', " . $count . ")
             ON DUPLICATE KEY UPDATE
               `generated_at` = NOW(),
               `source`       = '" . $source . "',
               `count`        = " . $count
        );
    }

    /**
     * Check TTL: returns true if the product needs (re)generation.
     */
    public function needsGeneration(int $productId, int $ttlHours, bool $cacheEnabled): bool
    {
        if (!$cacheEnabled) {
            return true;
        }

        $result = $this->db->query(
            "SELECT `generated_at` FROM `" . DB_PREFIX . "auto_related_log`
             WHERE `product_id` = " . $productId
        );

        if (empty($result->row)) {
            return true;
        }

        $generatedAt = strtotime($result->row['generated_at']);
        return (time() - $generatedAt) > ($ttlHours * 3600);
    }
}
