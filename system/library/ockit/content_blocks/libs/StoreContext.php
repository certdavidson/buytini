<?php
/**
 * Content Blocks — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

class StoreContext
{
    private $db;
    private $config;

    const API_URL   = 'https://api.oc-kit.com/v1/license/validate';
    const MODULE    = 'content_blocks';
    const VERSION   = '1.0.4';
    const KEY_LICENSE = 'module_oc_kit_content_blocks_license_key';
    const KEY_CACHE   = 'module_oc_kit_content_blocks_license_cache';
    const KEY_TRIAL   = 'module_oc_kit_content_blocks_trial_start';
    const CACHE_TTL = 15552000; // 6 months
    const GRACE_TTL = 604800;  // 7 days
    const TRIAL_TTL = 0;       // no trial — license key is always required

    // ⚠️ DEV ONLY — API not ready yet; switch to false before release
    const LOCAL_MODE = true;

    // Derived secrets — different per usage to prevent cross-context attacks
    // ⚠️ Replace with unique secrets before ioncube encoding
    const SECRET_KEY   = 'cb_8e5f1a92d4c7b063e2f8a1c5b7d9e0f3a6c2b8d5e1f4a7c9b3d6e8f0a2c5b7d_k';
    const SECRET_CACHE = 'cb_8e5f1a92d4c7b063e2f8a1c5b7d9e0f3a6c2b8d5e1f4a7c9b3d6e8f0a2c5b7d_c';
    const SECRET_TRIAL = 'cb_8e5f1a92d4c7b063e2f8a1c5b7d9e0f3a6c2b8d5e1f4a7c9b3d6e8f0a2c5b7d_t';

    public function __construct($db, $config)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    /**
     * Returns true if the module is allowed to operate.
     * Uses cached result; only calls the API when the cache is expired.
     */
    public function isActive(): bool
    {
        $key = trim((string)$this->config->get(self::KEY_LICENSE));

        if (self::LOCAL_MODE) {
            return $key !== '' && $this->validateKeyLocally($key);
        }

        if (!$key) {
            return false;
        }

        $cache = $this->getCache();

        // Cache is still fresh
        if ($cache && (time() - (int)($cache['checked_at'] ?? 0)) < self::CACHE_TTL) {
            if ($cache['valid']) return true;
            // Cache says invalid — still allow during grace period
            return isset($cache['last_success'])
                && (time() - (int)$cache['last_success']) < self::GRACE_TTL;
        }

        // Cache expired or missing — validate against the API
        $result = $this->callApi($key);

        if ($result !== null) {
            $this->saveCache($result);
            return (bool)$result['valid'];
        }

        // API unreachable — fall back to grace period
        if ($cache && isset($cache['last_success'])) {
            return (time() - (int)$cache['last_success']) < self::GRACE_TTL;
        }

        return false;
    }

    /**
     * Validates a key against the API immediately and persists the result.
     * Called when the admin clicks "Activate".
     *
     * @return array ['success' => bool, 'info' => array, 'error_code' => string]
     */
    public function activate(string $key): array
    {
        // Persist the key directly (editSettingValue in ocStore only does UPDATE, not INSERT)
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

    /**
     * Returns display data for the license status panel.
     */
    public function getInfo(): array
    {
        $key   = trim((string)$this->config->get(self::KEY_LICENSE));
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

        // No key — license is always required
        if (!$key) {
            return [
                'status'  => 'not_validated',
                'valid'   => false,
                'domain'  => $this->getDomain(),
                'version' => self::VERSION,
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
            // Check grace period
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
        $expected = $this->makeLocalKey($this->getDomain());
        $given    = strtoupper(str_replace('-', '', $key));
        return strlen($given) === 20 && hash_equals($expected, $given);
    }

    private function makeLocalKey(string $domain): string
    {
        return self::encodeKey($domain);
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

    private function callApi(string $key): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

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

        if (!$response || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

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
             WHERE `code` = 'module_oc_kit_content_blocks' AND `key` = '" . self::KEY_LICENSE . "' AND `store_id` = 0"
        );
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
             VALUES (0, 'module_oc_kit_content_blocks', '" . self::KEY_LICENSE . "', '" . $escaped . "', 0)"
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

        if (!hash_equals($this->signCache($data), $sig)) {
            return null; // tampered — treat as missing
        }

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
                 SET `value` = '" . $json . "', `serialized` = 0
                 WHERE `key` = '" . self::KEY_CACHE . "' AND `store_id` = 0"
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "setting`
                 (`store_id`, `code`, `key`, `value`, `serialized`)
                 VALUES (0, 'module_oc_kit_content_blocks', '" . self::KEY_CACHE . "', '" . $json . "', 0)"
            );
        }
    }
}
