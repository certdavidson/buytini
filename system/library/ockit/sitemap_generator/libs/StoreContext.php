<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

class StoreContext
{
    private $db;
    private $config;

    const API_URL     = 'https://api.oc-kit.com/v1/license/validate';
    const MODULE      = 'sitemap_generator';
    const VERSION     = '1.0.0';
    const KEY_LICENSE = 'module_oc_kit_sitemap_generator_license_key';
    const KEY_CACHE   = 'module_oc_kit_sitemap_generator_license_cache';
    const KEY_TRIAL   = 'module_oc_kit_sitemap_generator_trial_start';
    const CACHE_TTL   = 15552000; // 6 months
    const GRACE_TTL   = 604800;   // 7 days
    const TRIAL_TTL   = 0;        // 0 = trial disabled

    // ⚠️ DEV ONLY — set to false before release
    const LOCAL_MODE = true;

    // Derived secrets — unique per module to prevent cross-context attacks
    const SECRET_KEY   = 'f3e1a7d29b0c8645ae12f97c3d5b84e017a6c29f3e5d8b1074c2a6e9f0d3b7a2_k';
    const SECRET_CACHE = 'f3e1a7d29b0c8645ae12f97c3d5b84e017a6c29f3e5d8b1074c2a6e9f0d3b7a2_c';
    const SECRET_TRIAL = 'f3e1a7d29b0c8645ae12f97c3d5b84e017a6c29f3e5d8b1074c2a6e9f0d3b7a2_t';

    public function __construct($db, $config)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    public function isActive(): bool
    {
        $key = trim((string)$this->config->get('module_oc_kit_sitemap_generator_license_key'));

        if (self::LOCAL_MODE) {
            return $key !== '' && $this->validateKeyLocally($key);
        }

        if (!$key) {
            return $this->isTrialActive();
        }

        $cache = $this->getCache();

        if ($cache && (time() - (int)($cache['checked_at'] ?? 0)) < self::CACHE_TTL) {
            if ($cache['valid']) return true;
            return isset($cache['last_success'])
                && (time() - (int)$cache['last_success']) < self::GRACE_TTL;
        }

        $result = $this->callApi($key);

        if ($result !== null) {
            $this->saveCache($result);
            return (bool)$result['valid'];
        }

        if ($cache && isset($cache['last_success'])) {
            return (time() - (int)$cache['last_success']) < self::GRACE_TTL;
        }

        return false;
    }

    public function activate(string $key): array
    {
        $this->saveKey($key);

        if (self::LOCAL_MODE) {
            $valid = $key !== '' && $this->validateKeyLocally($key);
            return ['success' => $valid, 'error_code' => $valid ? '' : 'invalid_key', 'info' => $this->getInfo()];
        }

        $result = $this->callApi($key);

        if ($result === null) {
            return ['success' => false, 'error_code' => 'api_unreachable', 'info' => $this->getInfo()];
        }

        $this->saveCache($result);
        return ['success' => (bool)$result['valid'], 'error_code' => '', 'info' => $this->getInfo()];
    }

    public function getInfo(): array
    {
        $key   = trim((string)$this->config->get('module_oc_kit_sitemap_generator_license_key'));
        $cache = $this->getCache();
        $now   = time();

        if (self::LOCAL_MODE) {
            $valid = $key !== '' && $this->validateKeyLocally($key);
            return [
                'status'  => $valid ? 'active' : ($key !== '' ? 'invalid' : 'not_validated'),
                'valid'   => $valid,
                'domain'  => $this->getDomain(),
                'version' => self::VERSION,
            ];
        }

        if (!$key) {
            $trialStart = $this->getTrialStart();
            $remaining  = self::TRIAL_TTL - ($now - $trialStart);
            $daysLeft   = max(0, (int)ceil($remaining / 86400));
            return [
                'status'          => $daysLeft > 0 ? 'trial' : 'expired',
                'valid'           => $daysLeft > 0,
                'trial_days_left' => $daysLeft,
                'version'         => self::VERSION,
            ];
        }

        if (!$cache) {
            return ['status' => 'not_validated', 'valid' => false, 'version' => self::VERSION];
        }

        $updatesUntil  = $cache['updates_until'] ?? '';
        $latestVersion = $cache['latest_version'] ?? '';

        $info = [
            'valid'            => (bool)$cache['valid'],
            'domain'           => $cache['domain']        ?? '',
            'plan'             => $cache['plan']           ?? '',
            'updates_until'    => $updatesUntil,
            'updates_expired'  => $updatesUntil && strtotime($updatesUntil) < $now,
            'latest_version'   => $latestVersion,
            'update_available' => $latestVersion && version_compare($latestVersion, self::VERSION, '>'),
            'checked_at'       => (int)($cache['checked_at'] ?? 0),
            'next_check'       => (int)($cache['checked_at'] ?? 0) + self::CACHE_TTL,
            'version'          => self::VERSION,
        ];

        if ($cache['valid']) {
            $info['status'] = 'active';
        } else {
            $lastSuccess = (int)($cache['last_success'] ?? 0);
            if ($lastSuccess && ($now - $lastSuccess) < self::GRACE_TTL) {
                $info['status']          = 'grace';
                $info['valid']           = true;
                $info['grace_days_left'] = (int)ceil((self::GRACE_TTL - ($now - $lastSuccess)) / 86400);
            } else {
                $info['status'] = 'invalid';
            }
        }

        return $info;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    const KEY_ALPHABET = 'ACDEFGHJKMNPQRSTUVWXYZ23456789';

    private function validateKeyLocally(string $key): bool
    {
        $expected = self::encodeKey($this->getDomain());
        $given    = strtoupper(str_replace('-', '', $key));
        return strlen($given) === 20 && hash_equals($expected, $given);
    }

    private static function encodeKey(string $domain): string
    {
        $hmac     = hash_hmac('sha256', strtolower(trim($domain)), self::SECRET_KEY, true);
        $alphabet = self::KEY_ALPHABET;
        $base     = strlen($alphabet);
        $result   = '';
        $bits = 0;
        $acc  = 0;
        for ($i = 0; $i < strlen($hmac) && strlen($result) < 20; $i++) {
            $acc   = ($acc << 8) | ord($hmac[$i]);
            $bits += 8;
            while ($bits >= 5 && strlen($result) < 20) {
                $bits  -= 5;
                $result .= $alphabet[(($acc >> $bits) & 0x1F) % $base];
            }
        }
        return $result;
    }

    private function isTrialActive(): bool
    {
        return (time() - $this->getTrialStart()) < self::TRIAL_TTL;
    }

    private function signTrial(int $timestamp): string
    {
        return hash_hmac('sha256', (string)$timestamp . '|' . $this->getDomain(), self::SECRET_TRIAL);
    }

    private function getTrialStart(): int
    {
        $result = $this->db->query(
            "SELECT `value` FROM `" . DB_PREFIX . "setting`
             WHERE `key` = '" . self::KEY_TRIAL . "' AND `store_id` = 0 LIMIT 1"
        );

        if (!empty($result->row)) {
            $stored = json_decode($result->row['value'], true);
            if (is_array($stored) && isset($stored['ts'], $stored['sig'])) {
                $ts = (int)$stored['ts'];
                if (!hash_equals($this->signTrial($ts), $stored['sig'])) return 0;
                return $ts;
            }
            return 0;
        }

        $now     = time();
        $payload = $this->db->escape(json_encode(['ts' => $now, 'sig' => $this->signTrial($now)]));
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "setting`
             (`store_id`, `code`, `key`, `value`, `serialized`)
             VALUES (0, 'module_oc_kit_sitemap_generator', '" . self::KEY_TRIAL . "', '" . $payload . "', 0)"
        );
        return $now;
    }

    private function callApi(string $key): ?array
    {
        if (!function_exists('curl_init')) return null;

        $payload = json_encode([
            'key'     => $key,
            'domain'  => $this->getDomain(),
            'module'  => self::MODULE,
            'version' => self::VERSION,
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode < 200 || $httpCode >= 300) return null;
        $data = json_decode($response, true);
        return (is_array($data) && isset($data['valid'])) ? $data : null;
    }

    private function getDomain(): string
    {
        if (defined('HTTP_SERVER')) {
            return strtolower(parse_url(HTTP_SERVER, PHP_URL_HOST) ?: '');
        }
        $url = $this->config->get('config_url') ?: $this->config->get('config_ssl');
        return $url ? strtolower(parse_url($url, PHP_URL_HOST) ?: '') : '';
    }

    private function saveKey(string $key): void
    {
        $escaped = $this->db->escape($key);
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "setting`
             WHERE `code` = 'module_oc_kit_sitemap_generator' AND `key` = '" . self::KEY_LICENSE . "' AND `store_id` = 0"
        );
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
             VALUES (0, 'module_oc_kit_sitemap_generator', '" . self::KEY_LICENSE . "', '" . $escaped . "', 0)"
        );
    }

    private function signCache(array $data): string
    {
        return hash_hmac('sha256', json_encode($data), self::SECRET_CACHE);
    }

    private function getCache(): ?array
    {
        $result = $this->db->query(
            "SELECT `value` FROM `" . DB_PREFIX . "setting`
             WHERE `key` = '" . self::KEY_CACHE . "' AND `store_id` = 0 LIMIT 1"
        );
        if (empty($result->row)) return null;
        $stored = json_decode($result->row['value'], true);
        if (!is_array($stored)) return null;
        $sig  = $stored['_sig'] ?? '';
        $data = $stored;
        unset($data['_sig']);
        if (!hash_equals($this->signCache($data), $sig)) return null;
        return $data;
    }

    private function saveCache(array $apiResponse): void
    {
        $prev = $this->getCache();
        $now  = time();
        $cache = [
            'valid'          => (bool)($apiResponse['valid']          ?? false),
            'domain'         => (string)($apiResponse['domain']        ?? ''),
            'plan'           => (string)($apiResponse['plan']          ?? ''),
            'updates_until'  => (string)($apiResponse['updates_until'] ?? ''),
            'latest_version' => (string)($apiResponse['latest_version'] ?? ''),
            'checked_at'     => $now,
            'last_success'   => ($apiResponse['valid'] ?? false)
                                 ? $now
                                 : (int)($prev['last_success'] ?? 0),
        ];
        $cache['_sig'] = $this->signCache($cache);
        $json = $this->db->escape(json_encode($cache));

        $existing = $this->db->query(
            "SELECT `setting_id` FROM `" . DB_PREFIX . "setting`
             WHERE `key` = '" . self::KEY_CACHE . "' AND `store_id` = 0 LIMIT 1"
        );

        if (!empty($existing->row)) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "setting`
                 SET `value` = '{$json}', `serialized` = 0
                 WHERE `key` = '" . self::KEY_CACHE . "' AND `store_id` = 0"
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
                 VALUES (0, 'module_oc_kit_sitemap_generator', '" . self::KEY_CACHE . "', '{$json}', 0)"
            );
        }
    }
}
