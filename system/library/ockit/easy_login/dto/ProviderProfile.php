<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Dto;

/**
 * Immutable value object — fields are populated once in the constructor and
 * must not be mutated thereafter. PHP 7.4 typed properties do not enforce
 * `readonly` (PHP 8.1+), so this is by convention.
 */
class ProviderProfile
{
    /** @readonly */ public string $provider;
    /** @readonly */ public string $providerUserId;
    /** @readonly */ public ?string $email;
    /** @readonly */ public bool $emailVerified;
    /** @readonly */ public ?string $displayName;
    /** @readonly */ public ?string $firstName;
    /** @readonly */ public ?string $lastName;
    /** @readonly */ public ?string $avatarUrl;
    /** @readonly */ public array $raw;

    public function __construct(array $data)
    {
        $this->provider       = (string)($data['provider'] ?? '');
        $this->providerUserId = (string)($data['provider_user_id'] ?? '');
        $this->email          = isset($data['email']) ? (string)$data['email'] : null;
        $this->emailVerified  = (bool)($data['email_verified'] ?? false);
        $this->displayName    = isset($data['display_name']) ? (string)$data['display_name'] : null;
        $this->firstName      = isset($data['first_name']) ? (string)$data['first_name'] : null;
        $this->lastName       = isset($data['last_name']) ? (string)$data['last_name'] : null;
        $this->avatarUrl      = isset($data['avatar_url']) ? (string)$data['avatar_url'] : null;
        $this->raw            = (array)($data['raw'] ?? []);
    }
}
