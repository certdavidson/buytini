<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 *
 * @deprecated Legacy adapter — kept for compatibility with v0.x add-ons.
 *             New code uses Telemetry. Will be removed in v2.0.
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * Legacy license adapter. У внутрішніх інтеграціях цей клас більше не
 * використовується — лишається лише для сторонніх плагінів, що могли його
 * викликати. Усі методи делеговані до Telemetry або повертають безпечні
 * заглушки.
 */
final class LicenseClient
{
    /** @deprecated лишилось з v0.4 — більше не читається. */
    private const LEGACY_PUB_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxHjFBs+gW6yOA7E4oP9N
mXiAFQ8gEGtjZqvMpV1U+LrR5cz0XTm9b8R+Xm6ABz8Xeg5DkcG3p2Pz9wF1Z6lj
m4qUHPRAY+Z3v9rExF1Wj+MQ8KsZAPhsoGqK1fBmM6V4+KkBxPJYeP3F/qXl3yV4
PxqaLfQ4u7T1F9uAPm0QEEBfRgWZsP7lXLk9RmVMFy8KQkQ4F8mLaAOZkYG7AOPs
2qCKJP5OpVQy3FrJa8Wu6N3lZAEoJfRjVQ9b3Q4YnB6EZ5N1eUZX2CfQyP1lDC9N
TrOxsK+B0VxVH4XmEJQpZ4XKtL6ZBGr7fAuVE1QrGnRqGbVFv+LTsK4dMbqTgQfP
xQIDAQAB
-----END PUBLIC KEY-----
EOT;

    private const ENDPOINT = 'https://oc-kit.com/api/license/activate';

    private ConfigStore $store;
    private ?Telemetry $delegate = null;

    public function __construct(ConfigStore $store)
    {
        $this->store = $store;
    }

    public function activate(string $integration, string $key, string $domain): array
    {
        return $this->getDelegate()->register($integration, $key, $domain);
    }

    public function isLicensed(string $integration): bool
    {
        return $this->getDelegate()->isActive($integration);
    }

    public function getToken(string $integration): ?string
    {
        return $this->getDelegate()->getSession($integration);
    }

    /**
     * Перевірка підпису ZIP. Лишилась для сумісності — нова логіка у
     * MarketplaceClient через Telemetry.
     */
    public function verifyZipSignature(string $zipPath, string $sigPath): bool
    {
        if (!is_file($zipPath) || !is_file($sigPath)) return false;
        $data = (string)file_get_contents($zipPath);
        $sig  = (string)file_get_contents($sigPath);
        if ($data === '' || $sig === '') return false;
        $key = openssl_pkey_get_public(self::LEGACY_PUB_KEY);
        if ($key === false) return false;
        return openssl_verify($data, $sig, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    public function installId(): string
    {
        return $this->getDelegate()->instanceId();
    }

    private function getDelegate(): Telemetry
    {
        if ($this->delegate === null) $this->delegate = new Telemetry($this->store);
        return $this->delegate;
    }
}
