<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

/**
 * Stores translation events (success + errors) in the DB for display in the admin log tab.
 */
class DbLogger
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function logSuccess(
        string $type,
        int    $itemId,
        string $sourceLang,
        string $targetLang,
        string $provider,
        int    $fieldsCount
    ): void {
        $this->insert('success', $type, $itemId, $sourceLang, $targetLang, $provider, $fieldsCount, '');
    }

    public function logError(
        string $type,
        int    $itemId,
        string $sourceLang,
        string $targetLang,
        string $provider,
        string $errorMsg
    ): void {
        $this->insert('error', $type, $itemId, $sourceLang, $targetLang, $provider, 0, $errorMsg);
    }

    private function insert(
        string $status,
        string $type,
        int    $itemId,
        string $sourceLang,
        string $targetLang,
        string $provider,
        int    $fieldsCount,
        string $errorMsg
    ): void {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "translater_pro_log`
             SET `status`       = '" . $this->db->escape($status) . "',
                 `type`         = '" . $this->db->escape($type) . "',
                 `item_id`      = '" . (int)$itemId . "',
                 `source_lang`  = '" . $this->db->escape($sourceLang) . "',
                 `target_lang`  = '" . $this->db->escape($targetLang) . "',
                 `provider`     = '" . $this->db->escape($provider) . "',
                 `fields_count` = '" . (int)$fieldsCount . "',
                 `error_msg`    = '" . $this->db->escape(mb_substr($errorMsg, 0, 1000)) . "',
                 `date_added`   = NOW()"
        );
    }

    public function getLogs(int $start = 0, int $limit = 50, string $status = ''): array
    {
        $where = $status !== '' ? "WHERE `status` = '" . $this->db->escape($status) . "'" : '';
        $q = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "translater_pro_log`
             {$where}
             ORDER BY `date_added` DESC
             LIMIT " . (int)$start . ", " . (int)$limit
        );
        return $q->rows;
    }

    public function countLogs(string $status = ''): int
    {
        $where = $status !== '' ? "WHERE `status` = '" . $this->db->escape($status) . "'" : '';
        $q = $this->db->query(
            "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "translater_pro_log` {$where}"
        );
        return (int)$q->row['total'];
    }

    public function clearLogs(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "translater_pro_log`");
    }

    public function install(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "translater_pro_log` (
                `log_id`       INT(11) NOT NULL AUTO_INCREMENT,
                `status`       VARCHAR(8) NOT NULL DEFAULT 'error',
                `type`         VARCHAR(32) NOT NULL DEFAULT '',
                `item_id`      INT(11) NOT NULL DEFAULT 0,
                `source_lang`  VARCHAR(10) NOT NULL DEFAULT '',
                `target_lang`  VARCHAR(10) NOT NULL DEFAULT '',
                `provider`     VARCHAR(32) NOT NULL DEFAULT '',
                `fields_count` TINYINT(4) NOT NULL DEFAULT 0,
                `error_msg`    TEXT NOT NULL,
                `date_added`   DATETIME NOT NULL,
                PRIMARY KEY (`log_id`),
                KEY `idx_date` (`date_added`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Migration: add new columns to existing installs
        $cols = $this->db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '" . DB_PREFIX . "translater_pro_log'
               AND COLUMN_NAME IN ('status', 'fields_count')"
        );
        $existing = array_column($cols->rows, 'COLUMN_NAME');

        if (!in_array('status', $existing)) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "translater_pro_log`
                 ADD COLUMN `status` VARCHAR(8) NOT NULL DEFAULT 'error' AFTER `log_id`"
            );
        }
        if (!in_array('fields_count', $existing)) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "translater_pro_log`
                 ADD COLUMN `fields_count` TINYINT(4) NOT NULL DEFAULT 0 AFTER `provider`"
            );
        }
    }

    public function uninstall(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "translater_pro_log`");
    }
}
