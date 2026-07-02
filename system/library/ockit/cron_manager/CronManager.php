<?php
/**
 * Cron Manager — OpenCart 3.x Module
 *
 * @package   OcKit\CronManager
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\CronManager;

use OcKit\CronManager\Dto\CronJobDto;
use OcKit\CronManager\Dto\CronLogDto;
use OcKit\CronManager\Libs\CronRepository;
use OcKit\CronManager\Libs\CronLogger;
use OcKit\CronManager\Libs\CronRunner;
use OcKit\CronManager\Libs\CronScheduler;

require_once __DIR__ . '/exceptions/CronException.php';
require_once __DIR__ . '/dto/CronJobDto.php';
require_once __DIR__ . '/dto/CronLogDto.php';
require_once __DIR__ . '/libs/CronScheduler.php';
require_once __DIR__ . '/libs/CronRepository.php';
require_once __DIR__ . '/libs/CronLogger.php';
require_once __DIR__ . '/libs/CronRunner.php';

class CronManager
{
    private object         $registry;
    private ?CronRepository $repo      = null;
    private ?CronLogger    $logger    = null;
    private ?CronRunner    $runner    = null;
    private ?CronScheduler $scheduler = null;

    public function __construct(object $registry)
    {
        $this->registry = $registry;
    }

    // ─── DB setup ─────────────────────────────────────────────────────────────

    public function install(): void
    {
        $db = $this->registry->get('db');
        $db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cron_manager_jobs` (
                `job_id`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`          VARCHAR(255) NOT NULL,
                `description`   TEXT,
                `type`          ENUM('php','shell','url') NOT NULL DEFAULT 'php',
                `command`       TEXT NOT NULL,
                `schedule`      VARCHAR(100) NOT NULL DEFAULT '* * * * *',
                `timeout`       INT(11) NOT NULL DEFAULT 60,
                `status`        TINYINT(1) NOT NULL DEFAULT 1,
                `last_run`      DATETIME DEFAULT NULL,
                `last_status`   ENUM('success','error','running','never') NOT NULL DEFAULT 'never',
                `last_duration` INT(11) DEFAULT NULL,
                `sort_order`    INT(11) NOT NULL DEFAULT 0,
                `date_added`    DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`job_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cron_manager_log` (
                `log_id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `job_id`       INT(11) UNSIGNED NOT NULL,
                `started_at`   DATETIME NOT NULL,
                `duration`     INT(11) DEFAULT NULL,
                `status`       ENUM('success','error') NOT NULL,
                `output`       TEXT,
                `triggered_by` ENUM('scheduler','manual') NOT NULL DEFAULT 'scheduler',
                PRIMARY KEY (`log_id`),
                KEY `idx_job_id`    (`job_id`),
                KEY `idx_started_at`(`started_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
        ");
    }

    public function uninstall(): void
    {
        $db = $this->registry->get('db');
        $db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cron_manager_jobs`");
        $db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cron_manager_log`");
    }

    // ─── Jobs ─────────────────────────────────────────────────────────────────

    public function getJobs(): array
    {
        $scheduler = $this->getScheduler();
        $jobs      = [];

        foreach ($this->getRepo()->getAll() as $dto) {
            $jobs[] = $this->jobToArray($dto, $scheduler);
        }

        return $jobs;
    }

    public function getJob(int $jobId): ?array
    {
        $dto = $this->getRepo()->getById($jobId);
        if (!$dto) return null;
        return $this->jobToArray($dto, $this->getScheduler());
    }

    public function saveJob(array $data): int
    {
        return $this->getRepo()->save($data);
    }

    public function deleteJob(int $jobId): void
    {
        $this->getRepo()->delete($jobId);
        $this->getLogger()->clearByJob($jobId);
    }

    public function toggleJob(int $jobId, bool $status): void
    {
        $this->getRepo()->toggle($jobId, $status);
    }

    // ─── Execution ────────────────────────────────────────────────────────────

    public function runJob(int $jobId): array
    {
        $dto = $this->getRepo()->getById($jobId);
        if (!$dto) {
            return ['success' => false, 'output' => 'Job not found', 'duration' => 0];
        }

        $this->getRepo()->setRunning($jobId);

        $result = $this->getRunner()->run($dto);

        $status = $result['success'] ? 'success' : 'error';
        $this->getRepo()->updateLastRun($jobId, $status, $result['duration']);
        $this->getLogger()->log($jobId, $status, $result['output'], $result['duration'], 'manual');

        return $result;
    }

    // Called by cron_manager.php bootstrap — runs all due jobs
    public function runDueJobs(): void
    {
        $now       = time();
        $scheduler = $this->getScheduler();

        foreach ($this->getRepo()->getEnabled() as $dto) {
            if ($dto->lastStatus === 'running') {
                // Check stale lock (job stuck > timeout + 30s)
                if ($dto->lastRun !== null) {
                    $lastRunTs = strtotime($dto->lastRun);
                    if (($now - $lastRunTs) < ($dto->timeout + 30)) {
                        continue; // still legitimately running
                    }
                }
            }

            if (!$scheduler->matches($dto->schedule, $now)) continue;

            $this->getRepo()->setRunning($dto->jobId);
            $result = $this->getRunner()->run($dto);
            $status = $result['success'] ? 'success' : 'error';
            $this->getRepo()->updateLastRun($dto->jobId, $status, $result['duration']);
            $this->getLogger()->log($dto->jobId, $status, $result['output'], $result['duration'], 'scheduler');
        }
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    public function clearLogs(int $jobId): void
    {
        $this->getLogger()->clearByJob($jobId);
    }

    public function getLogs(int $jobId, int $limit = 50): array
    {
        return array_map(
            fn(CronLogDto $l) => [
                'log_id'       => $l->logId,
                'job_id'       => $l->jobId,
                'started_at'   => $l->startedAt,
                'duration'     => $l->duration,
                'status'       => $l->status,
                'output'       => $l->output,
                'triggered_by' => $l->triggeredBy,
            ],
            $this->getLogger()->getByJob($jobId, $limit)
        );
    }

    // ─── Schedule preview ─────────────────────────────────────────────────────

    public function previewSchedule(string $schedule): array
    {
        $scheduler = $this->getScheduler();
        if (!$scheduler->isValid($schedule)) {
            return ['valid' => false, 'label' => ''];
        }
        return [
            'valid' => true,
            'label' => $scheduler->nextRunLabel($schedule),
            'next_ts' => $scheduler->nextRun($schedule),
        ];
    }

    // ─── Discovery ────────────────────────────────────────────────────────────

    public function scanCrons(string $cronsDir): array
    {
        $existing = [];
        foreach ($this->getRepo()->getAll() as $dto) {
            $existing[] = realpath($dto->command) ?: $dto->command;
        }

        $found = [];
        foreach (glob(rtrim($cronsDir, '/') . '/*.php') as $file) {
            $real = realpath($file);
            if (!in_array($real, $existing, true)) {
                $found[] = [
                    'file'    => $real ?: $file,
                    'name'    => basename($file, '.php'),
                    'type'    => 'php',
                    'command' => $real ?: $file,
                ];
            }
        }

        return $found;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function jobToArray(CronJobDto $dto, CronScheduler $scheduler): array
    {
        $nextTs = $scheduler->nextRun($dto->schedule);
        return [
            'job_id'         => $dto->jobId,
            'name'           => $dto->name,
            'description'    => $dto->description,
            'type'           => $dto->type,
            'command'        => $dto->command,
            'schedule'       => $dto->schedule,
            'timeout'        => $dto->timeout,
            'status'         => (int)$dto->status,
            'last_run'       => $dto->lastRun,
            'last_status'    => $dto->lastStatus,
            'last_duration'  => $dto->lastDuration,
            'sort_order'     => $dto->sortOrder,
            'next_run_ts'    => $nextTs,
            'next_run_label' => $dto->status ? $scheduler->nextRunLabel($dto->schedule) : '—',
            'date_added'     => $dto->dateAdded,
            'date_modified'  => $dto->dateModified,
        ];
    }

    // ─── Lazy loaders ─────────────────────────────────────────────────────────

    private function getRepo(): CronRepository
    {
        if ($this->repo === null) {
            $this->repo = new CronRepository($this->registry->get('db'));
        }
        return $this->repo;
    }

    private function getLogger(): CronLogger
    {
        if ($this->logger === null) {
            $this->logger = new CronLogger($this->registry->get('db'));
        }
        return $this->logger;
    }

    private function getRunner(): CronRunner
    {
        if ($this->runner === null) {
            $this->runner = new CronRunner();
        }
        return $this->runner;
    }

    private function getScheduler(): CronScheduler
    {
        if ($this->scheduler === null) {
            $this->scheduler = new CronScheduler();
        }
        return $this->scheduler;
    }
}
