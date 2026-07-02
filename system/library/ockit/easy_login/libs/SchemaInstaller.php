<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

class SchemaInstaller
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createTables(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_easy_login_identity` (
                `identity_id`       INT(11) NOT NULL AUTO_INCREMENT,
                `customer_id`       INT(11) NOT NULL,
                `provider`          VARCHAR(32) NOT NULL,
                `provider_user_id`  VARCHAR(191) NOT NULL,
                `email`             VARCHAR(191) DEFAULT NULL,
                `email_verified`    TINYINT(1) NOT NULL DEFAULT 0,
                `display_name`      VARCHAR(191) DEFAULT NULL,
                `avatar_url`        VARCHAR(500) DEFAULT NULL,
                `meta`              TEXT DEFAULT NULL,
                `created_at`        DATETIME NOT NULL,
                `last_login_at`     DATETIME DEFAULT NULL,
                PRIMARY KEY (`identity_id`),
                UNIQUE KEY `uniq_provider_user` (`provider`, `provider_user_id`),
                KEY `idx_customer` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_easy_login_log` (
                `log_id`       INT(11) NOT NULL AUTO_INCREMENT,
                `provider`     VARCHAR(32) NOT NULL,
                `customer_id`  INT(11) DEFAULT NULL,
                `email`        VARCHAR(191) DEFAULT NULL,
                `ip`           VARCHAR(45) DEFAULT NULL,
                `user_agent`   VARCHAR(500) DEFAULT NULL,
                `status`       VARCHAR(20) NOT NULL,
                `error`        VARCHAR(500) DEFAULT NULL,
                `created_at`   DATETIME NOT NULL,
                PRIMARY KEY (`log_id`),
                KEY `idx_provider_status` (`provider`, `status`),
                KEY `idx_ip_created` (`ip`, `created_at`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kit_easy_login_otp` (
                `otp_id`       INT(11) NOT NULL AUTO_INCREMENT,
                `channel`      VARCHAR(20) NOT NULL,
                `recipient`    VARCHAR(191) NOT NULL,
                `code_hash`    VARCHAR(255) NOT NULL,
                `attempts`     TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
                `consumed`     TINYINT(1) NOT NULL DEFAULT 0,
                `expires_at`   DATETIME NOT NULL,
                `created_at`   DATETIME NOT NULL,
                `ip`           VARCHAR(45) DEFAULT NULL,
                PRIMARY KEY (`otp_id`),
                KEY `idx_recipient_channel` (`recipient`, `channel`),
                KEY `idx_expires` (`expires_at`),
                KEY `idx_consumed_expires` (`consumed`, `expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        // Best-effort upgrade for existing installs created before
        // idx_consumed_expires was part of the schema. Errors out cleanly when
        // the index already exists; nothing else relies on this succeeding.
        try {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "kit_easy_login_otp`
                 ADD INDEX `idx_consumed_expires` (`consumed`, `expires_at`)"
            );
        } catch (\Throwable $e) {
            // "Duplicate key name" — already in place, expected on re-install.
            // Anything else (lock, permission) is unexpected and worth logging
            // so an admin can spot it during setup without a hard install
            // failure.
            $msg = $e->getMessage();
            if (stripos($msg, 'duplicate') === false && function_exists('error_log')) {
                error_log('[oc_kit_easy_login] SchemaInstaller ALTER idx_consumed_expires failed: ' . $msg);
            }
        }
    }

    public function dropTables(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_easy_login_identity`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_easy_login_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kit_easy_login_otp`");
    }
}
