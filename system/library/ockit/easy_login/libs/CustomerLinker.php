<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

use OcKit\EasyLogin\Dto\ProviderProfile;

class CustomerLinker
{
    private $db;
    private $config;

    public function __construct($db, $config)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    public function findByEmail(string $email): ?array
    {
        if ($email === '') return null;
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "customer`
              WHERE LOWER(`email`) = LOWER('" . $this->db->escape($email) . "')
              LIMIT 1"
        )->row;
        return $row ?: null;
    }

    /**
     * Creates a new customer record from a provider profile.
     * Random password (the user can later set their own via password recovery).
     *
     * Note on email uniqueness: OC core does not enforce UNIQUE on
     * customer.email and we deliberately do not ALTER the table — that would
     * risk breaking other modules and existing rows with case / whitespace
     * differences. Concurrent-INSERT race is mitigated upstream in
     * AuthService::authenticate via a per-email MySQL named lock (GET_LOCK)
     * with a re-check inside the critical section.
     */
    public function createFromProfile(ProviderProfile $profile): int
    {
        $email = $profile->email ?: $this->buildPlaceholderEmail($profile);

        // Random password — user cannot login via standard form (only via OAuth) until they reset it
        $salt     = substr(md5(uniqid((string)mt_rand(), true)), 0, 9);
        $password = bin2hex(random_bytes(16));
        $passwordHash = sha1($salt . sha1($salt . sha1($password)));

        $firstname = $profile->firstName ?? ($profile->displayName ?: 'Customer');
        $lastname  = $profile->lastName  ?? '';

        $customerGroupId = (int)($this->config->get('config_customer_group_id') ?: 1);
        $languageId      = (int)($this->config->get('config_language_id') ?: 1);
        $storeId         = (int)($this->config->get('config_store_id') ?: 0);

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "customer` SET
                `customer_group_id` = '" . $customerGroupId . "',
                `store_id`          = '" . $storeId . "',
                `language_id`       = '" . $languageId . "',
                `firstname`         = '" . $this->db->escape($firstname) . "',
                `lastname`          = '" . $this->db->escape($lastname) . "',
                `email`             = '" . $this->db->escape($email) . "',
                `telephone`         = '',
                `password`          = '" . $this->db->escape($passwordHash) . "',
                `salt`              = '" . $this->db->escape($salt) . "',
                `newsletter`        = '0',
                `status`            = '1',
                `safe`              = '0',
                `date_added`        = NOW(),
                `ip`                = '" . $this->db->escape($this->detectIp()) . "',
                `code`              = ''"
        );
        return (int)$this->db->getLastId();
    }

    public function customerHasPhone(int $customerId): bool
    {
        $row = $this->db->query(
            "SELECT `telephone` FROM `" . DB_PREFIX . "customer`
              WHERE `customer_id` = '" . (int)$customerId . "' LIMIT 1"
        )->row;
        return !empty($row['telephone']);
    }

    private function buildPlaceholderEmail(ProviderProfile $profile): string
    {
        $storeDomain = $this->getStoreDomain();
        $id          = preg_replace('/[^a-z0-9]/', '', strtolower($profile->providerUserId));

        switch ($profile->provider) {
            case 'sms_otp':
                return $id . '@phone.' . $storeDomain;
            case 'telegram':
                return $id . '@telegram.' . $storeDomain;
            default:
                return preg_replace('/[^a-z0-9]/', '', strtolower($profile->provider)) . '.' . $id . '@' . $storeDomain;
        }
    }

    private function getStoreDomain(): string
    {
        if (defined('HTTP_SERVER')) {
            $host = parse_url(HTTP_SERVER, PHP_URL_HOST);
            if ($host) return strtolower($host);
        }
        $url = $this->config->get('config_url') ?: $this->config->get('config_ssl');
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host) return strtolower($host);
        }
        return 'local';
    }

    private function detectIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
