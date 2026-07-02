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
use OcKit\EasyLogin\Libs\HttpClient;
use OcKit\EasyLogin\Libs\JwtUtil;

class AppleProvider extends AbstractAuthProvider
{
    private const AUTH_URL  = 'https://appleid.apple.com/auth/authorize';
    private const TOKEN_URL = 'https://appleid.apple.com/auth/token';
    private const JWKS_URL  = 'https://appleid.apple.com/auth/keys';
    private const VALID_ISSUERS = ['https://appleid.apple.com'];
    private const JWKS_CACHE_TTL = 3600;
    private const JWKS_CACHE_FILE = '_apple_jwks.json';

    private HttpClient $http;

    public function __construct($config, HttpClient $http)
    {
        parent::__construct($config);
        $this->http = $http;
    }

    public function name(): string { return 'apple'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_apple_enabled')
            && $this->config->get('module_oc_kit_easy_login_apple_service_id')
            && $this->config->get('module_oc_kit_easy_login_apple_team_id')
            && $this->config->get('module_oc_kit_easy_login_apple_key_id')
            && $this->config->get('module_oc_kit_easy_login_apple_private_key');
    }

    public function buildAuthUrl(string $redirectUri, string $state, string $codeChallenge = ''): string
    {
        $params = [
            'client_id'     => (string)$this->config->get('module_oc_kit_easy_login_apple_service_id'),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope'         => 'name email',
            'state'         => $state,
        ];
        if ($codeChallenge !== '') {
            $params['code_challenge']        = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Apple form_post callback gives us code + id_token directly.
     * We exchange the code at the token endpoint, then verify the id_token's
     * RS256 signature against Apple's JWKS — never trust the form-posted token.
     */
    public function handleCallback(string $code, ?string $idTokenFromForm, ?string $userJson, string $redirectUri, string $codeVerifier = ''): ProviderProfile
    {
        $clientSecret = $this->generateClientSecret();

        $form = [
            'client_id'     => (string)$this->config->get('module_oc_kit_easy_login_apple_service_id'),
            'client_secret' => $clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri,
        ];
        if ($codeVerifier !== '') {
            $form['code_verifier'] = $codeVerifier;
        }
        $tokenRes = $this->http->postFormJson(self::TOKEN_URL, $form);

        // Only trust id_token from the token endpoint (HTTPS-direct from Apple).
        // The form-posted id_token is attacker-controllable and must never be used.
        $idToken = (string)($tokenRes['id_token'] ?? '');
        if ($idToken === '') {
            throw new ProviderException('Missing id_token in Apple response');
        }

        $jwks    = $this->fetchJwks();
        $payload = JwtUtil::verifyRS256($idToken, $jwks);
        $this->validateIdTokenClaims($payload);

        // Apple sends 'user' JSON only on the first login — parse if present
        $first = null; $last = null;
        if ($userJson) {
            $userData = json_decode($userJson, true);
            if (is_array($userData) && isset($userData['name']) && is_array($userData['name'])) {
                $first = isset($userData['name']['firstName']) ? (string)$userData['name']['firstName'] : null;
                $last  = isset($userData['name']['lastName'])  ? (string)$userData['name']['lastName']  : null;
            }
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if (is_string($emailVerified)) $emailVerified = ($emailVerified === 'true');

        return new ProviderProfile([
            'provider'         => 'apple',
            'provider_user_id' => (string)$payload['sub'],
            'email'            => isset($payload['email']) ? (string)$payload['email'] : null,
            'email_verified'   => (bool)$emailVerified,
            'display_name'     => trim(($first ?? '') . ' ' . ($last ?? '')) ?: null,
            'first_name'       => $first,
            'last_name'        => $last,
            'avatar_url'       => null,
            'raw'              => array_merge($payload, ['user' => $userJson]),
        ]);
    }

    private function generateClientSecret(): string
    {
        $now = time();
        $header = [
            'kid' => (string)$this->config->get('module_oc_kit_easy_login_apple_key_id'),
        ];
        $payload = [
            'iss' => (string)$this->config->get('module_oc_kit_easy_login_apple_team_id'),
            'iat' => $now,
            'exp' => $now + 86400 * 30, // 30 days; Apple allows up to ~6 months
            'aud' => 'https://appleid.apple.com',
            'sub' => (string)$this->config->get('module_oc_kit_easy_login_apple_service_id'),
        ];
        $privateKey = (string)$this->config->get('module_oc_kit_easy_login_apple_private_key');
        return JwtUtil::signES256($header, $payload, $privateKey);
    }

    /**
     * Fetch Apple JWKS with a small file-based cache (1h TTL).
     * Falls back to live fetch if cache cannot be written.
     */
    private function fetchJwks(): array
    {
        $cacheDir  = defined('DIR_CACHE') ? DIR_CACHE : sys_get_temp_dir();
        $cacheFile = rtrim($cacheDir, '/') . '/' . self::JWKS_CACHE_FILE;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::JWKS_CACHE_TTL) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded) && !empty($decoded['keys'])) {
                    return $decoded['keys'];
                }
            }
        }

        $live = $this->http->getJson(self::JWKS_URL);
        if (empty($live['keys']) || !is_array($live['keys'])) {
            throw new ProviderException('Apple JWKS empty or malformed');
        }
        $written = @file_put_contents($cacheFile, json_encode($live), LOCK_EX);
        if ($written === false && function_exists('error_log')) {
            // Without a writable cache every Apple sign-in pays a network round
            // trip to JWKS_URL — log once so admins see it during setup.
            error_log('[oc_kit_easy_login] Apple JWKS cache not writable: ' . $cacheFile);
        }
        return $live['keys'];
    }

    private function validateIdTokenClaims(array $payload): void
    {
        $iss = (string)($payload['iss'] ?? '');
        if (!in_array($iss, self::VALID_ISSUERS, true)) {
            throw new ProviderException('Invalid Apple issuer: ' . $iss);
        }
        $aud = (string)($payload['aud'] ?? '');
        $expectedAud = (string)$this->config->get('module_oc_kit_easy_login_apple_service_id');
        if ($aud !== $expectedAud) {
            throw new ProviderException('Invalid Apple audience: ' . $aud);
        }
        $exp = (int)($payload['exp'] ?? 0);
        if ($exp > 0 && $exp < time()) {
            throw new ProviderException('Apple id_token expired');
        }
        if (empty($payload['sub'])) {
            throw new ProviderException('Apple id_token missing sub');
        }
    }
}
