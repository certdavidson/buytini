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

class GoogleProvider extends AbstractAuthProvider
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';
    private const JWKS_URL     = 'https://www.googleapis.com/oauth2/v3/certs';
    private const VALID_ISSUERS= ['accounts.google.com', 'https://accounts.google.com'];
    private const JWKS_CACHE_TTL  = 3600;
    private const JWKS_CACHE_FILE = '_google_jwks.json';

    private HttpClient $http;

    public function __construct($config, HttpClient $http)
    {
        parent::__construct($config);
        $this->http = $http;
    }

    public function name(): string { return 'google'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_google_enabled')
            && $this->config->get('module_oc_kit_easy_login_google_client_id')
            && $this->config->get('module_oc_kit_easy_login_google_client_secret');
    }

    public function getClientId(): string
    {
        return (string)$this->config->get('module_oc_kit_easy_login_google_client_id');
    }

    public function getMode(): string
    {
        return (string)($this->config->get('module_oc_kit_easy_login_google_mode') ?: 'button');
    }

    /**
     * Build the OAuth 2.0 authorization URL (button flow).
     * If $codeChallenge is non-empty, PKCE S256 params are added so the
     * authorization-code interception attack is blocked (RFC 9700).
     */
    public function buildAuthUrl(string $redirectUri, string $state, string $codeChallenge = ''): string
    {
        $params = [
            'client_id'     => $this->getClientId(),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];
        if ($codeChallenge !== '') {
            $params['code_challenge']        = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens, then fetch userinfo.
     * Used for the OAuth button flow.
     */
    public function handleCallback(string $code, string $redirectUri, string $codeVerifier = ''): ProviderProfile
    {
        $form = [
            'code'          => $code,
            'client_id'     => $this->getClientId(),
            'client_secret' => (string)$this->config->get('module_oc_kit_easy_login_google_client_secret'),
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ];
        if ($codeVerifier !== '') {
            $form['code_verifier'] = $codeVerifier;
        }
        $tokenRes = $this->http->postFormJson(self::TOKEN_URL, $form);

        $accessToken = (string)($tokenRes['access_token'] ?? '');
        if ($accessToken === '') {
            throw new ProviderException('Missing access_token in Google response');
        }

        $userInfo = $this->http->getJson(self::USERINFO_URL, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        return $this->buildProfile($userInfo);
    }

    /**
     * Verify a Google One Tap ID token via local JWKS signature verification.
     * Replaces the legacy tokeninfo debug endpoint (which leaks the token in
     * the URL and adds a round-trip per login).
     *
     * @param string $expectedNonce Raw nonce that was passed to data-nonce in
     *   the One-Tap prompt. Google embeds SHA-256(nonce) as the 'nonce' claim,
     *   so we hash and compare. Empty string disables the check (button flow).
     */
    public function verifyIdToken(string $idToken, string $expectedNonce = ''): ProviderProfile
    {
        if ($idToken === '') {
            throw new ProviderException('Empty id_token');
        }

        $jwks    = $this->fetchJwks();
        $payload = JwtUtil::verifyRS256($idToken, $jwks);

        // Validate audience
        $aud = (string)($payload['aud'] ?? '');
        if ($aud !== $this->getClientId()) {
            throw new ProviderException('Invalid audience: ' . $aud);
        }
        // Validate issuer
        $iss = (string)($payload['iss'] ?? '');
        if (!in_array($iss, self::VALID_ISSUERS, true)) {
            throw new ProviderException('Invalid issuer: ' . $iss);
        }
        // Validate expiry
        $exp = (int)($payload['exp'] ?? 0);
        if ($exp > 0 && $exp < time()) {
            throw new ProviderException('Token expired');
        }

        // Bind token to the originating session via the nonce we passed to
        // data-nonce. Two echo modes exist depending on whether GIS is in
        // FedCM mode (Chrome 125+ implicit) or not:
        //   - Non-FedCM: token's nonce claim contains the RAW value verbatim.
        //   - FedCM:     token's nonce claim contains SHA-256(rawNonce).
        // We accept either match so the same code path works regardless of
        // browser-side FedCM rollout state. Both hash_equals are constant-time.
        if ($expectedNonce !== '') {
            $tokenNonce = (string)($payload['nonce'] ?? '');
            if ($tokenNonce === '') {
                throw new ProviderException('Nonce missing from id_token');
            }
            $rawMatch    = hash_equals($expectedNonce, $tokenNonce);
            $hashedMatch = hash_equals(hash('sha256', $expectedNonce), $tokenNonce);
            if (!$rawMatch && !$hashedMatch) {
                throw new ProviderException('Nonce mismatch');
            }
        }

        return $this->buildProfile($payload);
    }

    /**
     * Fetch Google JWKS with a small file-based cache (1h TTL).
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
            throw new ProviderException('Google JWKS empty or malformed');
        }
        $written = @file_put_contents($cacheFile, json_encode($live), LOCK_EX);
        if ($written === false && function_exists('error_log')) {
            error_log('[oc_kit_easy_login] Google JWKS cache not writable: ' . $cacheFile);
        }
        return $live['keys'];
    }

    private function buildProfile(array $info): ProviderProfile
    {
        $sub = (string)($info['sub'] ?? '');
        if ($sub === '') {
            throw new ProviderException('Missing sub in Google profile');
        }
        // Google returns 'email_verified' as boolean for userinfo, but as 'true'/'false' string for tokeninfo
        $emailVerified = $info['email_verified'] ?? false;
        if (is_string($emailVerified)) $emailVerified = ($emailVerified === 'true');

        return new ProviderProfile([
            'provider'         => 'google',
            'provider_user_id' => $sub,
            'email'            => isset($info['email']) ? (string)$info['email'] : null,
            'email_verified'   => (bool)$emailVerified,
            'display_name'     => isset($info['name']) ? (string)$info['name'] : null,
            'first_name'       => isset($info['given_name']) ? (string)$info['given_name'] : null,
            'last_name'        => isset($info['family_name']) ? (string)$info['family_name'] : null,
            'avatar_url'       => isset($info['picture']) ? (string)$info['picture'] : null,
            'raw'              => $info,
        ]);
    }
}
