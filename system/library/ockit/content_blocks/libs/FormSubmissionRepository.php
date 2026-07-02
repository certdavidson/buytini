<?php
/**
 * Content Blocks Pro — Form submissions storage.
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

class FormSubmissionRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createTables(): void
    {
        $p = DB_PREFIX;
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_form_submissions` (
                `submission_id` INT(11) NOT NULL AUTO_INCREMENT,
                `block_id`      INT(11) NOT NULL,
                `element_id`    INT(11) NOT NULL DEFAULT 0,
                `page_route`    VARCHAR(64)  NOT NULL DEFAULT '',
                `page_id`       INT(11) NOT NULL DEFAULT 0,
                `ip`            VARCHAR(45)  NOT NULL DEFAULT '',
                `user_agent`    VARCHAR(255) NOT NULL DEFAULT '',
                `status`        TINYINT(1)   NOT NULL DEFAULT 1,
                `date_added`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`submission_id`),
                KEY `idx_block`   (`block_id`),
                KEY `idx_element` (`element_id`),
                KEY `idx_date`    (`date_added`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Idempotent migration for installs that pre-date the element_id column.
        $cols = $this->db->query(
            "SHOW COLUMNS FROM `{$p}kit_cb_form_submissions` LIKE 'element_id'"
        );
        if (!$cols->num_rows) {
            $this->db->query(
                "ALTER TABLE `{$p}kit_cb_form_submissions`
                 ADD COLUMN `element_id` INT(11) NOT NULL DEFAULT 0 AFTER `block_id`,
                 ADD KEY `idx_element` (`element_id`)"
            );
        }
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_form_submission_data` (
                `id`            INT(11) NOT NULL AUTO_INCREMENT,
                `submission_id` INT(11) NOT NULL,
                `field_name`    VARCHAR(64) NOT NULL DEFAULT '',
                `field_value`   MEDIUMTEXT  NOT NULL,
                `file_path`     VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                KEY `idx_submission` (`submission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function dropTables(): void
    {
        $p = DB_PREFIX;
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_form_submission_data`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_form_submissions`");
    }

    /** Creates a submission with associated field data; returns submission_id. */
    public function insert(array $meta, array $fields): int
    {
        $p = DB_PREFIX;
        $this->db->query(
            "INSERT INTO `{$p}kit_cb_form_submissions`
             (`block_id`, `element_id`, `page_route`, `page_id`, `ip`, `user_agent`, `status`, `date_added`)
             VALUES (
                '" . (int)($meta['block_id']   ?? 0) . "',
                '" . (int)($meta['element_id'] ?? 0) . "',
                '" . $this->db->escape((string)($meta['page_route'] ?? '')) . "',
                '" . (int)($meta['page_id']    ?? 0) . "',
                '" . $this->db->escape((string)($meta['ip']         ?? '')) . "',
                '" . $this->db->escape((string)($meta['user_agent'] ?? '')) . "',
                1, NOW()
             )"
        );
        $submissionId = (int)$this->db->getLastId();

        foreach ($fields as $name => $row) {
            $value = is_array($row) ? ($row['value'] ?? '') : (string)$row;
            $file  = is_array($row) ? ($row['file']  ?? '') : '';
            $this->db->query(
                "INSERT INTO `{$p}kit_cb_form_submission_data`
                 (`submission_id`, `field_name`, `field_value`, `file_path`)
                 VALUES (
                    '" . $submissionId . "',
                    '" . $this->db->escape((string)$name) . "',
                    '" . $this->db->escape((string)$value) . "',
                    '" . $this->db->escape((string)$file) . "'
                 )"
            );
        }
        return $submissionId;
    }

    public function getSubmissions(int $blockId = 0, int $start = 0, int $limit = 50): array
    {
        $p   = DB_PREFIX;
        $where = $blockId > 0 ? "WHERE `block_id` = '" . (int)$blockId . "'" : '';
        $rows = $this->db->query(
            "SELECT * FROM `{$p}kit_cb_form_submissions`
             $where ORDER BY `date_added` DESC
             LIMIT " . (int)$start . ", " . (int)$limit
        )->rows;
        foreach ($rows as &$r) {
            $r['fields'] = $this->db->query(
                "SELECT `field_name`, `field_value`, `file_path`
                 FROM `{$p}kit_cb_form_submission_data`
                 WHERE `submission_id` = '" . (int)$r['submission_id'] . "'"
            )->rows;
        }
        return $rows;
    }

    public function countSubmissions(int $blockId = 0): int
    {
        $p   = DB_PREFIX;
        $where = $blockId > 0 ? "WHERE `block_id` = '" . (int)$blockId . "'" : '';
        $q = $this->db->query("SELECT COUNT(*) AS cnt FROM `{$p}kit_cb_form_submissions` $where");
        return (int)($q->row['cnt'] ?? 0);
    }

    public function deleteSubmission(int $submissionId): void
    {
        $p = DB_PREFIX;
        $this->db->query("DELETE FROM `{$p}kit_cb_form_submissions`     WHERE `submission_id` = '" . (int)$submissionId . "'");
        $this->db->query("DELETE FROM `{$p}kit_cb_form_submission_data` WHERE `submission_id` = '" . (int)$submissionId . "'");
    }
}
