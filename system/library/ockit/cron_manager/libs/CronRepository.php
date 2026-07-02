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

use OcKit\CronManager\Dto\CronJobDto;

class CronRepository
{
    private object $db;
    private string $table;

    public function __construct(object $db)
    {
        $this->db    = $db;
        $this->table = DB_PREFIX . 'cron_manager_jobs';
    }

    public function getAll(): array
    {
        $q = $this->db->query("
            SELECT * FROM `{$this->table}`
            ORDER BY `sort_order` ASC, `job_id` ASC
        ");
        return array_map([$this, 'hydrate'], $q->rows);
    }

    public function getEnabled(): array
    {
        $q = $this->db->query("
            SELECT * FROM `{$this->table}`
            WHERE `status` = 1
            ORDER BY `sort_order` ASC, `job_id` ASC
        ");
        return array_map([$this, 'hydrate'], $q->rows);
    }

    public function getById(int $jobId): ?CronJobDto
    {
        $q = $this->db->query("
            SELECT * FROM `{$this->table}`
            WHERE `job_id` = " . (int)$jobId . "
            LIMIT 1
        ");
        if (!$q->row) return null;
        return $this->hydrate($q->row);
    }

    public function save(array $data): int
    {
        $jobId = (int)($data['job_id'] ?? 0);
        $now   = date('Y-m-d H:i:s');

        $name        = $this->db->escape($data['name'] ?? '');
        $description = $this->db->escape($data['description'] ?? '');
        $type        = in_array($data['type'] ?? '', ['php', 'shell', 'url']) ? $data['type'] : 'php';
        $command     = $this->db->escape($data['command'] ?? '');
        $schedule    = $this->db->escape($data['schedule'] ?? '* * * * *');
        $timeout     = max(1, min(86400, (int)($data['timeout'] ?? 60)));
        $status      = ($data['status'] ?? 0) ? 1 : 0;
        $sortOrder   = (int)($data['sort_order'] ?? 0);

        if ($jobId > 0) {
            $this->db->query("
                UPDATE `{$this->table}` SET
                    `name`          = '{$name}',
                    `description`   = '{$description}',
                    `type`          = '{$type}',
                    `command`       = '{$command}',
                    `schedule`      = '{$schedule}',
                    `timeout`       = {$timeout},
                    `status`        = {$status},
                    `sort_order`    = {$sortOrder},
                    `date_modified` = '{$now}'
                WHERE `job_id` = {$jobId}
            ");
            return $jobId;
        }

        $this->db->query("
            INSERT INTO `{$this->table}`
                (`name`, `description`, `type`, `command`, `schedule`, `timeout`, `status`, `sort_order`, `last_status`, `date_added`, `date_modified`)
            VALUES
                ('{$name}', '{$description}', '{$type}', '{$command}', '{$schedule}', {$timeout}, {$status}, {$sortOrder}, 'never', '{$now}', '{$now}')
        ");
        return (int)$this->db->getLastId();
    }

    public function delete(int $jobId): void
    {
        $this->db->query("DELETE FROM `{$this->table}` WHERE `job_id` = " . (int)$jobId);
    }

    public function toggle(int $jobId, bool $status): void
    {
        $v = $status ? 1 : 0;
        $this->db->query("
            UPDATE `{$this->table}` SET `status` = {$v}, `date_modified` = '" . date('Y-m-d H:i:s') . "'
            WHERE `job_id` = " . (int)$jobId
        );
    }

    public function updateLastRun(int $jobId, string $status, int $duration): void
    {
        $s   = $this->db->escape($status);
        $now = date('Y-m-d H:i:s');
        $this->db->query("
            UPDATE `{$this->table}` SET
                `last_run`      = '{$now}',
                `last_status`   = '{$s}',
                `last_duration` = {$duration},
                `date_modified` = '{$now}'
            WHERE `job_id` = " . (int)$jobId
        );
    }

    public function setRunning(int $jobId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->query("
            UPDATE `{$this->table}` SET `last_status` = 'running', `date_modified` = '{$now}'
            WHERE `job_id` = " . (int)$jobId
        );
    }

    private function hydrate(array $row): CronJobDto
    {
        $dto               = new CronJobDto();
        $dto->jobId        = (int)$row['job_id'];
        $dto->name         = (string)$row['name'];
        $dto->description  = (string)$row['description'];
        $dto->type         = (string)$row['type'];
        $dto->command      = (string)$row['command'];
        $dto->schedule     = (string)$row['schedule'];
        $dto->timeout      = (int)$row['timeout'];
        $dto->status       = (bool)(int)$row['status'];
        $dto->lastRun      = $row['last_run'] ?: null;
        $dto->lastStatus   = (string)$row['last_status'];
        $dto->lastDuration = $row['last_duration'] !== null ? (int)$row['last_duration'] : null;
        $dto->sortOrder    = (int)$row['sort_order'];
        $dto->dateAdded    = (string)$row['date_added'];
        $dto->dateModified = (string)$row['date_modified'];
        return $dto;
    }
}
