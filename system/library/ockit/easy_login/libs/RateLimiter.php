<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

use OcKit\EasyLogin\Libs\LoginLogger;

/**
 * Sliding-window rate limiter backed by oc_kit_easy_login_log.
 * Counts FAILED attempts (status in failed/rate_limited) in the last hour
 * by IP and by email/phone — legitimate successful logins do not consume
 * the budget, so an active user behind a corporate NAT cannot lock
 * themselves out by signing in a few times.
 */
class RateLimiter
{
    // Statuses that count as abuse signals — successful authentications and
    // bookkeeping events (linked/registered) are excluded.
    private const FAILURE_STATUSES = "'failed','rate_limited'";

    private $db;
    private $config;

    public function __construct($db, $config)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    public function isLimitedByIp(string $ip = ''): bool
    {
        // If caller didn't pass an IP, resolve it through the same helper used by
        // LoginLogger so log writes and rate-limit reads agree on the same identity.
        if ($ip === '') {
            $ip = (string)(LoginLogger::clientIp($this->config) ?? '');
        }
        if ($ip === '') return false;
        $limit = (int)($this->config->get('module_oc_kit_easy_login_rate_limit_per_ip_per_hour') ?: 30);
        if ($limit <= 0) return false;

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easy_login_log`
              WHERE `ip` = '" . $this->db->escape($ip) . "'
                AND `status` IN (" . self::FAILURE_STATUSES . ")
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        )->row;
        return (int)($row['cnt'] ?? 0) >= $limit;
    }

    public function isLimitedByEmail(string $email): bool
    {
        if ($email === '') return false;
        $limit = (int)($this->config->get('module_oc_kit_easy_login_rate_limit_per_email_per_hour') ?: 5);
        if ($limit <= 0) return false;

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easy_login_log`
              WHERE `email` = '" . $this->db->escape($email) . "'
                AND `status` IN (" . self::FAILURE_STATUSES . ")
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        )->row;
        return (int)($row['cnt'] ?? 0) >= $limit;
    }
}
