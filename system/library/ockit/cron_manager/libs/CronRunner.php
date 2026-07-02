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

class CronRunner
{
    // Executes a job synchronously and returns run result
    // Returns: ['success' => bool, 'output' => string, 'duration' => int (ms)]
    public function run(CronJobDto $job): array
    {
        $start = microtime(true);

        try {
            if ($job->type === 'url') {
                $result = $this->runUrl($job->command, $job->timeout);
            } else {
                $cmd    = $job->type === 'php'
                    ? PHP_BINARY . ' ' . escapeshellarg($job->command)
                    : $job->command;
                $result = $this->runProcess($cmd, $job->timeout);
            }
        } catch (\Throwable $e) {
            $result = ['success' => false, 'output' => $e->getMessage()];
        }

        $result['duration'] = (int)round((microtime(true) - $start) * 1000);
        return $result;
    }

    private function runProcess(string $cmd, int $timeout): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return ['success' => false, 'output' => 'Failed to start process'];
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout   = '';
        $stderr   = '';
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $read = [$pipes[1], $pipes[2]];
            $w = $e = null;

            $changed = stream_select($read, $w, $e, 0, 200000);
            if ($changed === false) break;

            foreach ($read as $stream) {
                $chunk = fread($stream, 8192);
                if ($chunk !== false) {
                    if ($stream === $pipes[1]) $stdout .= $chunk;
                    else                      $stderr .= $chunk;
                }
            }

            $status = proc_get_status($proc);
            if (!$status['running']) break;
        }

        // Drain remaining output
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $status   = proc_get_status($proc);
        $exitCode = $status['running'] ? -1 : $status['exitcode'];
        proc_close($proc);

        $output = trim($stdout);
        if ($stderr) $output .= ($output ? "\n" : '') . '[STDERR] ' . trim($stderr);

        return [
            'success' => ($exitCode === 0),
            'output'  => $output ?: "(no output, exit code: {$exitCode})",
        ];
    }

    private function runUrl(string $url, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'output' => 'cURL extension not available'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'OcKit-CronManager/1.0',
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'output' => "cURL error: {$err}"];
        }

        $success = ($code >= 200 && $code < 300);
        $output  = "HTTP {$code}\n" . mb_substr((string)$body, 0, 2000);

        return ['success' => $success, 'output' => $output];
    }
}
