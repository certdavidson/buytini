<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs\Providers;

use OcKit\EasyLogin\Dto\ProviderProfile;
use OcKit\EasyLogin\Exceptions\ProviderException;

class TelegramProvider extends AbstractAuthProvider
{
    private const MAX_AUTH_AGE_SECONDS = 86400; // 24h per Telegram recommendation

    public function name(): string { return 'telegram'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_telegram_enabled')
            && $this->config->get('module_oc_kit_easy_login_telegram_bot_token')
            && $this->config->get('module_oc_kit_easy_login_telegram_bot_username');
    }

    public function getBotUsername(): string
    {
        return (string)$this->config->get('module_oc_kit_easy_login_telegram_bot_username');
    }

    /**
     * Verify Telegram Login Widget payload via HMAC-SHA256.
     *
     * @param array $payload  Raw fields from the widget callback (id, first_name, username, photo_url, auth_date, hash, ...)
     */
    public function verifyAndBuildProfile(array $payload): ProviderProfile
    {
        $hash = (string)($payload['hash'] ?? '');
        if ($hash === '') {
            throw new ProviderException('Missing Telegram hash');
        }

        $authDate = (int)($payload['auth_date'] ?? 0);
        if ($authDate <= 0 || (time() - $authDate) > self::MAX_AUTH_AGE_SECONDS) {
            throw new ProviderException('Telegram auth_date expired or invalid');
        }

        $id = (string)($payload['id'] ?? '');
        if ($id === '' || !ctype_digit($id)) {
            throw new ProviderException('Invalid Telegram user id');
        }

        $botToken = (string)$this->config->get('module_oc_kit_easy_login_telegram_bot_token');
        if ($botToken === '') {
            throw new ProviderException('Bot token not configured');
        }

        // Build data_check_string per Telegram spec: sorted key=value pairs joined by \n, excluding 'hash'
        $check = $payload;
        unset($check['hash']);
        ksort($check);
        $pairs = [];
        foreach ($check as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        $dataCheckString = implode("\n", $pairs);

        $secretKey   = hash('sha256', $botToken, true);
        $expectedHex = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($expectedHex, $hash)) {
            throw new ProviderException('Telegram hash mismatch');
        }

        $firstName = isset($payload['first_name']) ? (string)$payload['first_name'] : null;
        $lastName  = isset($payload['last_name'])  ? (string)$payload['last_name']  : null;
        $username  = isset($payload['username'])   ? (string)$payload['username']   : null;
        $photo     = isset($payload['photo_url'])  ? (string)$payload['photo_url']  : null;

        $displayName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        if ($displayName === '' && $username !== null) {
            $displayName = '@' . $username;
        }

        return new ProviderProfile([
            'provider'         => 'telegram',
            'provider_user_id' => $id,
            'email'            => null,         // Telegram never returns email
            'email_verified'   => false,
            'display_name'     => $displayName ?: null,
            'first_name'       => $firstName,
            'last_name'        => $lastName,
            'avatar_url'       => $photo,
            'raw'              => $payload,
        ]);
    }
}
