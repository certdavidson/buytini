<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

/**
 * Tiny signed cookie used for OAuth state when the OC session cookie cannot be
 * relied on — specifically Apple's response_mode=form_post, which is a cross-site
 * POST that browsers (SameSite=Lax by default) drop the session cookie on.
 *
 * The cookie carries: random state, optional return URL, link-mode flag, and an
 * HMAC over those values so a client cannot forge it. Single-use: callers MUST
 * delete the cookie immediately after consuming it.
 */
class SignedStateCookie
{
    private const COOKIE_NAME = 'el_state';
    private const TTL_SECONDS = 600; // 10 minutes — covers slow Apple flow
    private const SECRET_PREFIX = 'el_state|';

    /**
     * Issue a fresh state value, set the signed cookie, and return the state
     * (so callers can pass it as the OAuth `state` parameter).
     */
    public static function issue(string $secret, array $extra = []): string
    {
        $state = bin2hex(random_bytes(16));
        $exp   = time() + self::TTL_SECONDS;
        $payload = [
            's'   => $state,
            'e'   => $exp,
            'r'   => (string)($extra['return'] ?? ''),
            'l'   => !empty($extra['link']) ? 1 : 0,
            'v'   => (string)($extra['verifier'] ?? ''),
        ];
        $payload['h'] = self::sign($payload, $secret);

        $value = self::base64UrlEncode(json_encode($payload));

        // SameSite=None;Secure is required so the cookie is sent on Apple's
        // cross-site form_post callback. HttpOnly keeps it away from JS.
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            $params = [
                'expires'  => $exp,
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'None',
            ];
            // PHP 7.3+: setcookie with options array
            if (!setcookie(self::COOKIE_NAME, $value, $params) && function_exists('error_log')) {
                error_log('[oc_kit_easy_login] SignedStateCookie::issue setcookie returned false');
            }
            $_COOKIE[self::COOKIE_NAME] = $value;
        } elseif (headers_sent() && function_exists('error_log')) {
            error_log('[oc_kit_easy_login] SignedStateCookie::issue: headers already sent — cookie not set');
        }

        return $state;
    }

    /**
     * Verify the cookie matches the supplied state and return the cookie payload
     * (return URL, link flag, PKCE verifier) on success. Returns null on any failure.
     * Always clears the cookie when called — single-use semantics.
     */
    public static function consume(string $secret, string $expectedState): ?array
    {
        $raw = (string)($_COOKIE[self::COOKIE_NAME] ?? '');
        self::clear();
        if ($raw === '' || $expectedState === '') return null;

        $json = self::base64UrlDecode($raw);
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['s'], $data['e'], $data['h'])) return null;

        if ((int)$data['e'] < time()) return null;
        $signedPayload = [
            's' => $data['s'],
            'e' => $data['e'],
            'r' => $data['r'] ?? '',
            'l' => $data['l'] ?? 0,
            'v' => $data['v'] ?? '',
        ];
        if (!hash_equals(self::sign($signedPayload, $secret), (string)$data['h'])) return null;
        if (!hash_equals((string)$data['s'], $expectedState)) return null;

        return [
            'return'   => (string)($data['r'] ?? ''),
            'link'     => !empty($data['l']),
            'verifier' => (string)($data['v'] ?? ''),
        ];
    }

    public static function clear(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) return;
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => 1,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    private static function sign(array $payload, string $secret): string
    {
        ksort($payload);
        return hash_hmac('sha256', self::SECRET_PREFIX . json_encode($payload), $secret);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return (string)base64_decode(strtr($data, '-_', '+/'));
    }
}
