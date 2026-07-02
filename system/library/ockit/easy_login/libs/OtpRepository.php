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

class OtpRepository
{
    private $db;
    private $config;

    public function __construct($db, $config = null)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    public function create(string $channel, string $recipient, string $codeHash, int $ttlSeconds): int
    {
        // Pass $this->config so the recorded IP matches the rate-limiter view
        // (CF-Connecting-IP when trust_cf_ip is enabled).
        $ip = (string)(LoginLogger::clientIp($this->config) ?? '');
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_easy_login_otp`
                (`channel`, `recipient`, `code_hash`, `expires_at`, `created_at`, `ip`)
             VALUES
                ('" . $this->db->escape($channel) . "',
                 '" . $this->db->escape($recipient) . "',
                 '" . $this->db->escape($codeHash) . "',
                 DATE_ADD(NOW(), INTERVAL " . (int)$ttlSeconds . " SECOND),
                 NOW(),
                 '" . $this->db->escape($ip) . "')"
        );
        return (int)$this->db->getLastId();
    }

    /**
     * Find latest non-consumed, non-expired OTP for recipient on channel.
     */
    public function findActive(string $channel, string $recipient): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_easy_login_otp`
              WHERE `channel`    = '" . $this->db->escape($channel) . "'
                AND `recipient`  = '" . $this->db->escape($recipient) . "'
                AND `consumed`   = '0'
                AND `expires_at` > NOW()
              ORDER BY `otp_id` DESC LIMIT 1"
        )->row;
        return $row ?: null;
    }

    /**
     * Lookup by token hash only (channel + code_hash). Used by email-magic
     * verify, which receives the raw token from the URL and has no recipient
     * to compare against until the row is found. Safe because the token is a
     * 32-byte cryptographic random whose sha256 is collision-resistant for
     * practical purposes.
     */
    public function findByHash(string $channel, string $codeHash): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_easy_login_otp`
              WHERE `channel`    = '" . $this->db->escape($channel) . "'
                AND `code_hash`  = '" . $this->db->escape($codeHash) . "'
                AND `consumed`   = '0'
                AND `expires_at` > NOW()
              ORDER BY `otp_id` DESC LIMIT 1"
        )->row;
        return $row ?: null;
    }

    public function incrementAttempts(int $otpId): void
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_easy_login_otp`
                SET `attempts` = `attempts` + 1
              WHERE `otp_id` = '" . (int)$otpId . "'"
        );
    }

    /**
     * Atomic consume: only succeeds if the row is still un-consumed. Returns
     * true exactly once per OTP, even under concurrent verify requests, so
     * callers can detect (and reject) replay races.
     */
    public function consume(int $otpId): bool
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_easy_login_otp`
                SET `consumed` = '1'
              WHERE `otp_id` = '" . (int)$otpId . "' AND `consumed` = '0'"
        );
        return $this->db->countAffected() === 1;
    }

    public function recentCountFor(string $channel, string $recipient, int $windowSeconds): int
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easy_login_otp`
              WHERE `channel`    = '" . $this->db->escape($channel) . "'
                AND `recipient`  = '" . $this->db->escape($recipient) . "'
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL " . (int)$windowSeconds . " SECOND)"
        )->row;
        return (int)($row['cnt'] ?? 0);
    }

    public function deleteExpired(): int
    {
        // Two queries instead of `OR` so each one can use the existing
        // idx_expires index. The OR-form forced a full table scan once the
        // table got large.
        $deleted = 0;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easy_login_otp` WHERE `expires_at` < NOW()");
        $deleted += (int)$this->db->countAffected();
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easy_login_otp` WHERE `consumed` = '1'");
        $deleted += (int)$this->db->countAffected();
        return $deleted;
    }
}
