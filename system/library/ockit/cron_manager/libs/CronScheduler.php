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

class CronScheduler
{
    // Returns true if $timestamp matches the cron expression
    public function matches(string $schedule, int $timestamp): bool
    {
        $parts = $this->parse($schedule);
        if ($parts === null) return false;

        [$minSet, $hourSet, $domSet, $monSet, $dowSet] = $parts;

        $tz = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        $dt = new \DateTime('@' . $timestamp);
        $dt->setTimezone($tz);

        return in_array((int)$dt->format('i'), $minSet, true)
            && in_array((int)$dt->format('H'), $hourSet, true)
            && in_array((int)$dt->format('j'), $domSet, true)
            && in_array((int)$dt->format('n'), $monSet, true)
            && in_array((int)$dt->format('w'), $dowSet, true);
    }

    // Returns next matching Unix timestamp (or null if expression is invalid)
    public function nextRun(string $schedule, ?int $from = null): ?int
    {
        $parts = $this->parse($schedule);
        if ($parts === null) return null;

        [$minSet, $hourSet, $domSet, $monSet, $dowSet] = $parts;

        $from  = $from ?? time();
        $start = (int)(floor($from / 60) * 60) + 60; // next full minute
        $limit = $start + 366 * 24 * 3600;

        $tz = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');

        for ($t = $start; $t <= $limit; $t += 60) {
            $dt = new \DateTime('@' . $t);
            $dt->setTimezone($tz);

            if (in_array((int)$dt->format('i'), $minSet, true)
                && in_array((int)$dt->format('H'), $hourSet, true)
                && in_array((int)$dt->format('j'), $domSet, true)
                && in_array((int)$dt->format('n'), $monSet, true)
                && in_array((int)$dt->format('w'), $dowSet, true)) {
                return $t;
            }
        }

        return null;
    }

    // Returns human-readable "next run" string
    public function nextRunLabel(string $schedule): string
    {
        $ts = $this->nextRun($schedule);
        if ($ts === null) return '—';

        $diff = $ts - time();
        if ($diff < 120)    return '< 2 хв';
        if ($diff < 3600)   return 'через ' . round($diff / 60) . ' хв';
        if ($diff < 86400)  return 'через ' . round($diff / 3600) . ' год';

        return date('d.m H:i', $ts);
    }

    // Validates cron expression syntax
    public function isValid(string $schedule): bool
    {
        return $this->parse($schedule) !== null;
    }

    // Parses expression into 5 arrays of allowed values
    private function parse(string $schedule): ?array
    {
        $fields = preg_split('/\s+/', trim($schedule));
        if (count($fields) !== 5) return null;

        try {
            return [
                $this->expand($fields[0], 0, 59),  // minute
                $this->expand($fields[1], 0, 23),  // hour
                $this->expand($fields[2], 1, 31),  // dom
                $this->expand($fields[3], 1, 12),  // month
                $this->expandDow($fields[4]),       // dow (normalized to 0-6)
            ];
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    // Expands one cron field expression into a sorted array of integers
    private function expand(string $expr, int $min, int $max): array
    {
        $result = [];

        foreach (explode(',', $expr) as $part) {
            $part = trim($part);

            if (strpos($part, '/') !== false) {
                [$range, $step] = explode('/', $part, 2);
                $step = (int)$step;
                if ($step < 1) throw new \InvalidArgumentException("Invalid step: $step");

                [$from, $to] = $range === '*'
                    ? [$min, $max]
                    : $this->parseRange($range, $min, $max);

                for ($i = $from; $i <= $to; $i += $step) {
                    $result[] = $i;
                }
            } elseif ($part === '*') {
                for ($i = $min; $i <= $max; $i++) {
                    $result[] = $i;
                }
            } elseif (strpos($part, '-') !== false) {
                [$from, $to] = $this->parseRange($part, $min, $max);
                for ($i = $from; $i <= $to; $i++) {
                    $result[] = $i;
                }
            } else {
                $v = (int)$part;
                if ($v < $min || $v > $max) throw new \InvalidArgumentException("Value $v out of range [$min,$max]");
                $result[] = $v;
            }
        }

        return array_values(array_unique($result));
    }

    private function expandDow(string $expr): array
    {
        $raw = $this->expand($expr, 0, 7);
        // Normalize: 7 → 0 (both mean Sunday)
        return array_values(array_unique(array_map(fn($v) => $v === 7 ? 0 : $v, $raw)));
    }

    private function parseRange(string $part, int $min, int $max): array
    {
        if (strpos($part, '-') === false) {
            $v = (int)$part;
            return [$v, $v];
        }
        [$a, $b] = array_map('intval', explode('-', $part, 2));
        if ($a > $b || $a < $min || $b > $max) {
            throw new \InvalidArgumentException("Invalid range: $part");
        }
        return [$a, $b];
    }
}
