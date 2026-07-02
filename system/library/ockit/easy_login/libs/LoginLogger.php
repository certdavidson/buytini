<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

class LoginLogger
{
    private $db;
    private $config;

    public function __construct($db, $config = null)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    public function log(string $provider, string $status, array $extra = []): void
    {
        $customerId = isset($extra['customer_id']) ? (int)$extra['customer_id'] : null;
        $email      = isset($extra['email']) ? (string)$extra['email'] : null;
        $error      = isset($extra['error']) ? (string)$extra['error'] : null;
        $ip         = self::clientIp($this->config);
        $userAgent  = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_easy_login_log`
                (`provider`, `customer_id`, `email`, `ip`, `user_agent`, `status`, `error`, `created_at`)
             VALUES
                ('" . $this->db->escape($provider) . "',
                 " . ($customerId !== null ? "'" . (int)$customerId . "'" : "NULL") . ",
                 " . ($email !== null ? "'" . $this->db->escape($email) . "'" : "NULL") . ",
                 " . ($ip !== null ? "'" . $this->db->escape($ip) . "'" : "NULL") . ",
                 " . ($userAgent !== null ? "'" . $this->db->escape($userAgent) . "'" : "NULL") . ",
                 '" . $this->db->escape($status) . "',
                 " . ($error !== null ? "'" . $this->db->escape($error) . "'" : "NULL") . ",
                 NOW())"
        );
    }

    public function getEntries(array $filter = []): array
    {
        $sql   = "SELECT * FROM `" . DB_PREFIX . "kit_easy_login_log` WHERE 1=1";
        $sql  .= $this->buildWhere($filter);
        $sql  .= " ORDER BY `created_at` DESC, `log_id` DESC";

        $start = isset($filter['start']) ? (int)$filter['start'] : 0;
        $limit = isset($filter['limit']) ? (int)$filter['limit'] : 25;
        $sql  .= " LIMIT " . $start . ", " . $limit;

        return $this->db->query($sql)->rows ?: [];
    }

    public function getTotal(array $filter = []): int
    {
        $sql  = "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easy_login_log` WHERE 1=1";
        $sql .= $this->buildWhere($filter);
        return (int)($this->db->query($sql)->row['cnt'] ?? 0);
    }

    public function getStats(): array
    {
        $row = $this->db->query(
            "SELECT
                SUM(`status` = 'success')    AS success,
                SUM(`status` = 'failed')     AS failed,
                SUM(`status` = 'rate_limited') AS rate_limited,
                SUM(`status` = 'linked')     AS linked,
                SUM(`status` = 'registered') AS registered,
                COUNT(*)                     AS total
             FROM `" . DB_PREFIX . "kit_easy_login_log`"
        )->row ?: [];

        return [
            'success'      => (int)($row['success'] ?? 0),
            'failed'       => (int)($row['failed'] ?? 0),
            'rate_limited' => (int)($row['rate_limited'] ?? 0),
            'linked'       => (int)($row['linked'] ?? 0),
            'registered'   => (int)($row['registered'] ?? 0),
            'total'        => (int)($row['total'] ?? 0),
        ];
    }

    public function clearAll(): int
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "kit_easy_login_log`");
        return (int)$this->db->countAffected();
    }

    public function clearOld(int $retentionDays): int
    {
        if ($retentionDays <= 0) return 0;
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_easy_login_log`
             WHERE `created_at` < DATE_SUB(NOW(), INTERVAL " . (int)$retentionDays . " DAY)"
        );
        return (int)$this->db->countAffected();
    }

    private function buildWhere(array $filter): string
    {
        $sql = '';
        if (!empty($filter['provider'])) {
            $sql .= " AND `provider` = '" . $this->db->escape((string)$filter['provider']) . "'";
        }
        if (!empty($filter['status'])) {
            $sql .= " AND `status` = '" . $this->db->escape((string)$filter['status']) . "'";
        }
        if (!empty($filter['email'])) {
            $sql .= " AND `email` LIKE '%" . $this->db->escape((string)$filter['email']) . "%'";
        }
        if (!empty($filter['ip'])) {
            $sql .= " AND `ip` = '" . $this->db->escape((string)$filter['ip']) . "'";
        }
        if (!empty($filter['date_from'])) {
            $sql .= " AND `created_at` >= '" . $this->db->escape((string)$filter['date_from']) . " 00:00:00'";
        }
        if (!empty($filter['date_to'])) {
            $sql .= " AND `created_at` <= '" . $this->db->escape((string)$filter['date_to']) . " 23:59:59'";
        }
        return $sql;
    }

    /**
     * Single source of truth for client IP — used by both LoginLogger and RateLimiter
     * so log writes and rate-limit reads agree on the same identity.
     *
     * Defaults to REMOTE_ADDR. Forwarded headers are spoofable and only trusted when
     * 'module_oc_kit_easy_login_trust_cf_ip' is enabled in admin and the request
     * actually arrived over Cloudflare (presence of CF-Connecting-IP).
     */
    public static function clientIp($config = null): ?string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? null;

        // Allow Cloudflare's edge-supplied client IP only when admin opted in.
        // We deliberately do NOT honour X-Real-IP / X-Forwarded-For here because
        // any caller can forge them when the site is reachable without CF.
        $trustCf = $config ? (bool)$config->get('module_oc_kit_easy_login_trust_cf_ip') : false;
        if ($trustCf && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cf = (string)$_SERVER['HTTP_CF_CONNECTING_IP'];
            if (filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
        }

        if ($remote && filter_var($remote, FILTER_VALIDATE_IP)) {
            return (string)$remote;
        }
        return null;
    }
}
