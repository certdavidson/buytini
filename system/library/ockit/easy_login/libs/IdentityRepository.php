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

class IdentityRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByProviderUser(string $provider, string $providerUserId): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_easy_login_identity`
             WHERE `provider` = '" . $this->db->escape($provider) . "'
               AND `provider_user_id` = '" . $this->db->escape($providerUserId) . "'
             LIMIT 1"
        )->row;
        return $row ?: null;
    }

    public function findAllForCustomer(int $customerId): array
    {
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_easy_login_identity`
             WHERE `customer_id` = '" . (int)$customerId . "'
             ORDER BY `created_at` DESC"
        )->rows ?: [];
    }

    public function create(int $customerId, ProviderProfile $profile): int
    {
        // meta holds a narrow whitelist of non-PII context (locale, issuer)
        // for debugging — the full provider payload is intentionally NOT
        // stored to minimise GDPR data-retention footprint.
        $metaWhitelist = [];
        foreach (['iss', 'locale', 'hd', 'sub'] as $k) {
            if (isset($profile->raw[$k]) && is_scalar($profile->raw[$k])) {
                $metaWhitelist[$k] = (string)$profile->raw[$k];
            }
        }
        $meta = $metaWhitelist ? json_encode($metaWhitelist, JSON_UNESCAPED_UNICODE) : null;

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_easy_login_identity`
                (`customer_id`, `provider`, `provider_user_id`, `email`, `email_verified`,
                 `display_name`, `avatar_url`, `meta`, `created_at`, `last_login_at`)
             VALUES
                ('" . (int)$customerId . "',
                 '" . $this->db->escape($profile->provider) . "',
                 '" . $this->db->escape($profile->providerUserId) . "',
                 " . ($profile->email !== null ? "'" . $this->db->escape($profile->email) . "'" : "NULL") . ",
                 '" . (int)$profile->emailVerified . "',
                 " . ($profile->displayName !== null ? "'" . $this->db->escape($profile->displayName) . "'" : "NULL") . ",
                 " . ($profile->avatarUrl !== null ? "'" . $this->db->escape($profile->avatarUrl) . "'" : "NULL") . ",
                 " . ($meta !== null ? "'" . $this->db->escape($meta) . "'" : "NULL") . ",
                 NOW(), NOW())"
        );
        return (int)$this->db->getLastId();
    }

    public function touchLastLogin(int $identityId): void
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_easy_login_identity`
                SET `last_login_at` = NOW()
              WHERE `identity_id` = '" . (int)$identityId . "'"
        );
    }

    public function delete(int $identityId, int $customerId): bool
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_easy_login_identity`
              WHERE `identity_id` = '" . (int)$identityId . "'
                AND `customer_id` = '" . (int)$customerId . "'"
        );
        return $this->db->countAffected() > 0;
    }
}
