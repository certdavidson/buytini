<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ModelExtensionModuleOcKitAutoRelated extends Model
{
    public function install(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "auto_related_log` (
                `log_id`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
                `product_id`   INT UNSIGNED     NOT NULL,
                `generated_at` DATETIME         NOT NULL,
                `source`       ENUM('cron','visit','manual') NOT NULL DEFAULT 'manual',
                `count`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`log_id`),
                UNIQUE KEY `product_id` (`product_id`),
                KEY `generated_at` (`generated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Migrate old rule table schema (source_scope/target_scope) to new constructor schema
        $check = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . DB_PREFIX . "auto_related_rule'
             AND COLUMN_NAME = 'source_scope'"
        );
        if (!empty($check->row['cnt'])) {
            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "auto_related_rule`");
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "auto_related_rule` (
                `rule_id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`               VARCHAR(255) NOT NULL DEFAULT '',
                `status`             TINYINT(1)   NOT NULL DEFAULT 1,
                `sort_order`         INT          NOT NULL DEFAULT 0,
                `block_title`        VARCHAR(255) NOT NULL DEFAULT '',
                `result_limit`       TINYINT UNSIGNED NOT NULL DEFAULT 8,
                `result_sort`        ENUM('random','price_asc','price_desc','new','name','bestseller') NOT NULL DEFAULT 'random',
                `source_conditions`  TEXT         NOT NULL,
                `target_conditions`  TEXT         NOT NULL,
                `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`rule_id`),
                KEY `status_sort` (`status`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->ensureCoordersIndex();
        $this->dropLegacyFulltextIndex();
    }

    /**
     * One-time cleanup: the FULLTEXT index was previously offered as an opt-in
     * but never actually used in any query. Drop it on (re)install so leftover
     * installs don't carry dead weight.
     */
    private function dropLegacyFulltextIndex(): void
    {
        $exists = $this->db->query(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = '" . DB_PREFIX . "product_description'
               AND INDEX_NAME   = 'ok_auto_related_name'
             LIMIT 1"
        );
        if (!empty($exists->row)) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "product_description`
                 DROP INDEX `ok_auto_related_name`"
            );
        }
    }

    /**
     * Ensure (product_id, order_id) composite index exists on oc_order_product.
     * Required by the coorders signal — otherwise the bestseller/coorders
     * subquery falls back to a full scan with O(n^2) behaviour on hot products.
     */
    private function ensureCoordersIndex(): void
    {
        $exists = $this->db->query(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = '" . DB_PREFIX . "order_product'
               AND INDEX_NAME   = 'ok_ar_product_order'
             LIMIT 1"
        );
        if (empty($exists->row)) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "order_product`
                 ADD INDEX `ok_ar_product_order` (`product_id`, `order_id`)"
            );
        }
    }

    public function uninstall(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "auto_related_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "auto_related_rule`");
    }
}
