<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

/**
 * Writes generation run records to oc_sitemap_generator_log table.
 */
class DbLogger
{
    private $db;
    private string $table;

    public function __construct(\DB $db, string $prefix)
    {
        $this->db    = $db;
        $this->table = $prefix . 'sitemap_generator_log';
    }

    public function install(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `log_id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                `map_id`        INT UNSIGNED    NULL,
                `started_at`    DATETIME        NOT NULL,
                `finished_at`   DATETIME        NULL,
                `status`        ENUM('running','success','error') NOT NULL DEFAULT 'running',
                `urls_count`    INT UNSIGNED    NOT NULL DEFAULT 0,
                `files_count`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `error_message` TEXT            NULL,
                `triggered_by`  ENUM('manual','cron','http') NOT NULL DEFAULT 'manual',
                PRIMARY KEY (`log_id`),
                KEY `map_id` (`map_id`),
                KEY `started_at` (`started_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function uninstall(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `{$this->table}`");
    }

    /**
     * Creates a new run record with status=running and returns its log_id.
     */
    public function startRun(?int $mapId, string $triggeredBy): int
    {
        $mapSql = $mapId !== null ? (int)$mapId : 'NULL';
        $by     = $this->db->escape($triggeredBy);
        $this->db->query(
            "INSERT INTO `{$this->table}`
             SET map_id       = {$mapSql},
                 started_at   = NOW(),
                 status       = 'running',
                 triggered_by = '{$by}'"
        );
        return (int)$this->db->getLastId();
    }

    public function finishRun(int $logId, int $urlsCount, int $filesCount): void
    {
        $this->db->query(
            "UPDATE `{$this->table}`
             SET status       = 'success',
                 finished_at  = NOW(),
                 urls_count   = " . (int)$urlsCount . ",
                 files_count  = " . (int)$filesCount . "
             WHERE log_id = " . (int)$logId
        );
    }

    public function failRun(int $logId, string $errorMessage): void
    {
        $msg = $this->db->escape(mb_substr($errorMessage, 0, 65535));
        $this->db->query(
            "UPDATE `{$this->table}`
             SET status        = 'error',
                 finished_at   = NOW(),
                 error_message = '{$msg}'
             WHERE log_id = " . (int)$logId
        );
    }

    public function getLogs(array $filter = []): array
    {
        $where = [];

        if (!empty($filter['map_id'])) {
            $where[] = "map_id = " . (int)$filter['map_id'];
        }
        if (!empty($filter['status'])) {
            $where[] = "status = '" . $this->db->escape($filter['status']) . "'";
        }
        if (!empty($filter['date_from'])) {
            $where[] = "DATE(started_at) >= '" . $this->db->escape($filter['date_from']) . "'";
        }
        if (!empty($filter['date_to'])) {
            $where[] = "DATE(started_at) <= '" . $this->db->escape($filter['date_to']) . "'";
        }

        $sql    = "SELECT * FROM `{$this->table}`";
        $sqlCnt = "SELECT COUNT(*) AS total FROM `{$this->table}`";

        if ($where) {
            $cond    = " WHERE " . implode(' AND ', $where);
            $sql    .= $cond;
            $sqlCnt .= $cond;
        }

        $sql .= " ORDER BY started_at DESC";

        $page  = max(1, (int)($filter['page']  ?? 1));
        $limit = max(1, (int)($filter['limit'] ?? 50));
        $sql  .= " LIMIT {$limit} OFFSET " . (($page - 1) * $limit);

        $rows  = $this->db->query($sql)->rows;
        $total = (int)$this->db->query($sqlCnt)->row['total'];

        return ['rows' => $rows, 'total' => $total];
    }

    public function clearLogs(?int $mapId = null): void
    {
        if ($mapId !== null) {
            $this->db->query("DELETE FROM `{$this->table}` WHERE map_id = " . (int)$mapId);
        } else {
            $this->db->query("TRUNCATE TABLE `{$this->table}`");
        }
    }
}
