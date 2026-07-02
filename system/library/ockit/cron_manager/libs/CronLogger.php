<?php
/**
 * Cron Manager — OpenCart 3.x Module
 *
 * @package   OcKit\CronManager
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\CronManager\Libs;

use OcKit\CronManager\Dto\CronLogDto;

class CronLogger
{
    private const MAX_PER_JOB = 100;

    private object $db;
    private string $table;

    public function __construct(object $db)
    {
        $this->db    = $db;
        $this->table = DB_PREFIX . 'cron_manager_log';
    }

    public function log(int $jobId, string $status, string $output, int $duration, string $triggeredBy = 'scheduler'): void
    {
        $s           = $this->db->escape($status);
        $out         = $this->db->escape(mb_substr($output, 0, 65535));
        $trig        = $this->db->escape($triggeredBy);
        $now         = date('Y-m-d H:i:s');

        $this->db->query("
            INSERT INTO `{$this->table}` (`job_id`, `started_at`, `duration`, `status`, `output`, `triggered_by`)
            VALUES (" . (int)$jobId . ", '{$now}', {$duration}, '{$s}', '{$out}', '{$trig}')
        ");

        $this->cleanup($jobId);
    }

    public function getByJob(int $jobId, int $limit = 50): array
    {
        $q = $this->db->query("
            SELECT * FROM `{$this->table}`
            WHERE `job_id` = " . (int)$jobId . "
            ORDER BY `log_id` DESC
            LIMIT " . (int)$limit
        );
        return array_map([$this, 'hydrate'], $q->rows);
    }

    public function clearByJob(int $jobId): void
    {
        $this->db->query("DELETE FROM `{$this->table}` WHERE `job_id` = " . (int)$jobId);
    }

    // Auto-remove old entries, keeping the most recent MAX_PER_JOB
    private function cleanup(int $jobId): void
    {
        $q = $this->db->query("
            SELECT `log_id` FROM `{$this->table}`
            WHERE `job_id` = " . (int)$jobId . "
            ORDER BY `log_id` DESC
            LIMIT 1 OFFSET " . (self::MAX_PER_JOB - 1)
        );

        if (!empty($q->row['log_id'])) {
            $cutoff = (int)$q->row['log_id'];
            $this->db->query("
                DELETE FROM `{$this->table}`
                WHERE `job_id` = " . (int)$jobId . " AND `log_id` < {$cutoff}
            ");
        }
    }

    private function hydrate(array $row): CronLogDto
    {
        $dto              = new CronLogDto();
        $dto->logId       = (int)$row['log_id'];
        $dto->jobId       = (int)$row['job_id'];
        $dto->startedAt   = (string)$row['started_at'];
        $dto->duration    = $row['duration'] !== null ? (int)$row['duration'] : null;
        $dto->status      = (string)$row['status'];
        $dto->output      = (string)$row['output'];
        $dto->triggeredBy = (string)$row['triggered_by'];
        return $dto;
    }
}
