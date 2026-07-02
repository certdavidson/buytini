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

class CronJobDto
{
    public int     $jobId        = 0;
    public string  $name         = '';
    public string  $description  = '';
    public string  $type         = 'php';   // php | shell | url
    public string  $command      = '';
    public string  $schedule     = '* * * * *';
    public int     $timeout      = 60;
    public bool    $status       = true;
    public ?string $lastRun      = null;
    public string  $lastStatus   = 'never'; // success | error | running | never
    public ?int    $lastDuration = null;
    public int     $sortOrder    = 0;
    public string  $dateAdded    = '';
    public string  $dateModified = '';
}
