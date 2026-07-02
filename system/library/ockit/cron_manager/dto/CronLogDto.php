<?php
/**
 * Cron Manager — OpenCart 3.x Module
 *
 * @package   OcKit\CronManager
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\CronManager\Dto;

class CronLogDto
{
    public int     $logId       = 0;
    public int     $jobId       = 0;
    public string  $startedAt   = '';
    public ?int    $duration    = null;
    public string  $status      = '';        // success | error
    public string  $output      = '';
    public string  $triggeredBy = 'scheduler'; // scheduler | manual
}
