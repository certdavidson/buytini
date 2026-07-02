<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

/**
 * Google OAuth2 + Search Console API + Indexing API client.
 *
 * OAuth flow:
 *   1. `getAuthUrl($redirectUri)` — admin clicks "Connect Google", browser
 *      navigates to Google consent screen.
 *   2. Google redirects back to `$redirectUri` with `?code=…`.
 *   3. `exchangeCode($code, $redirectUri)` — swap the code for an access
 *      token + refresh token; refresh token is persisted in config.
 *   4. Subsequent API calls use `getAccessToken()`, which transparently
 *      refreshes when the cached access token expires.
 *
 * APIs:
 *   - Search Analytics: queries, clicks, impressions, CTR, position
 *   - Sitemaps:        list / submit / delete
 *   - URL Inspection:  index status, mobile usability, rich-result eligibility
 *   - Indexing API:    notify URL_UPDATED / URL_DELETED (originally JobPosting,
 *                      widely used for general URLs in practice)
 *
 * Required Google API scopes (request all three so any feature works):
 *   https://www.googleapis.com/auth/webmasters
 *   https://www.googleapis.com/auth/webmasters.readonly
 *   https://www.googleapis.com/auth/indexing
 */
class SearchConsoleClient
{
    private const CFG_CLIENT_ID     = 'module_oc_kit_seo_core_gsc_client_id';
    private const CFG_CLIENT_SECRET = 'module_oc_kit_seo_core_gsc_client_secret';
    private const CFG_REFRESH_TOKEN = 'module_oc_kit_seo_core_gsc_refresh_token';
    private const CFG_ACCESS_TOKEN  = 'module_oc_kit_seo_core_gsc_access_token';
    private const CFG_TOKEN_EXPIRES = 'module_oc_kit_seo_core_gsc_token_expires';
    private const CFG_SITE_PROPERTY = 'module_oc_kit_seo_core_gsc_site_property';

    private const SCOPES = [
        'https://www.googleapis.com/auth/webmasters',
        'https://www.googleapis.com/auth/webmasters.readonly',
        'https://www.googleapis.com/auth/indexing',
    ];

    private const OAUTH_AUTH_URL    = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const OAUTH_TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    private const OAUTH_REVOKE_URL  = 'https://oauth2.googleapis.com/revoke';
    private const SC_API_BASE       = 'https://searchconsole.googleapis.com/webmasters/v3';
    private const SC_INSPECT_API    = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
    private const INDEXING_API      = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private $config;
    /** @var \Setting\Setting|object */
    private $settingModel;
    private int $storeId;

    public function __construct($config, $settingModel, int $storeId = 0)
    {
        $this->config       = $config;
        $this->settingModel = $settingModel;
        $this->storeId      = $storeId;
    }

    // ─── OAuth ───────────────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return (string)$this->config->get(self::CFG_REFRESH_TOKEN) !== '';
    }

    public function getSiteProperty(): string
    {
        return (string)$this->config->get(self::CFG_SITE_PROPERTY);
    }

    /**
     * Build the consent URL. Caller redirects browser there.
     */
    public function getAuthUrl(string $redirectUri, string $state = ''): string
    {
        $clientId = (string)$this->config->get(self::CFG_CLIENT_ID);
        if ($clientId === '') {
            throw new \RuntimeException('Google OAuth client_id is not configured');
        }
        $params = [
            'client_id'              => $clientId,
            'redirect_uri'           => $redirectUri,
            'response_type'          => 'code',
            'scope'                  => implode(' ', self::SCOPES),
            'access_type'            => 'offline',
            'prompt'                 => 'consent',           // force refresh_token issuance
            'include_granted_scopes' => 'true',
            'state'                  => $state,
        ];
        return self::OAUTH_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange an authorization code for tokens, persist refresh + access.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $resp = $this->httpPostForm(self::OAUTH_TOKEN_URL, [
            'code'          => $code,
            'client_id'     => (string)$this->config->get(self::CFG_CLIENT_ID),
            'client_secret' => (string)$this->config->get(self::CFG_CLIENT_SECRET),
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);
        if (empty($resp['access_token'])) {
            throw new \RuntimeException('Token exchange failed: ' . json_encode($resp));
        }
        $this->persistTokens(
            (string)($resp['refresh_token'] ?? ''),
            (string)$resp['access_token'],
            (int)($resp['expires_in'] ?? 3600)
        );
        return $resp;
    }

    public function disconnect(): void
    {
        $rt = (string)$this->config->get(self::CFG_REFRESH_TOKEN);
        if ($rt !== '') {
            // Best-effort revoke at Google's side; ignore failures.
            $this->httpPostForm(self::OAUTH_REVOKE_URL, ['token' => $rt]);
        }
        $this->saveSettings([
            self::CFG_REFRESH_TOKEN => '',
            self::CFG_ACCESS_TOKEN  => '',
            self::CFG_TOKEN_EXPIRES => '',
        ]);
    }

    /**
     * Get a valid access token, refreshing transparently if expired.
     */
    public function getAccessToken(): string
    {
        $token   = (string)$this->config->get(self::CFG_ACCESS_TOKEN);
        $expires = (int)$this->config->get(self::CFG_TOKEN_EXPIRES);

        if ($token !== '' && $expires > time() + 60) {
            return $token;
        }
        return $this->refreshAccessToken();
    }

    private function refreshAccessToken(): string
    {
        $rt = (string)$this->config->get(self::CFG_REFRESH_TOKEN);
        if ($rt === '') {
            throw new \RuntimeException('Not connected to Google — no refresh token');
        }
        $resp = $this->httpPostForm(self::OAUTH_TOKEN_URL, [
            'client_id'     => (string)$this->config->get(self::CFG_CLIENT_ID),
            'client_secret' => (string)$this->config->get(self::CFG_CLIENT_SECRET),
            'refresh_token' => $rt,
            'grant_type'    => 'refresh_token',
        ]);
        if (empty($resp['access_token'])) {
            throw new \RuntimeException('Refresh failed: ' . json_encode($resp));
        }
        $this->persistTokens('', (string)$resp['access_token'], (int)($resp['expires_in'] ?? 3600));
        return (string)$resp['access_token'];
    }

    // ─── Search Analytics ────────────────────────────────────────────────────

    /**
     * Query GSC search analytics.
     *
     * @param  string[]  $dimensions  one of: query|page|country|device|searchAppearance|date
     * @param  array     $filters     optional: [['dimension'=>'page','operator'=>'contains','expression'=>'/foo']]
     */
    public function searchAnalytics(
        string $startDate,
        string $endDate,
        array $dimensions = ['query'],
        array $filters = [],
        int $rowLimit = 100
    ): array {
        $site = $this->getSiteProperty();
        if ($site === '') throw new \RuntimeException('site_property is not configured');

        $body = [
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'dimensions'=> $dimensions,
            'rowLimit'  => max(1, min(25000, $rowLimit)),
        ];
        if ($filters) {
            $body['dimensionFilterGroups'] = [['filters' => $filters]];
        }

        $url = self::SC_API_BASE . '/sites/' . rawurlencode($site) . '/searchAnalytics/query';
        return $this->apiPostJson($url, $body);
    }

    // ─── Sitemaps ────────────────────────────────────────────────────────────

    public function listSitemaps(): array
    {
        $site = $this->getSiteProperty();
        $url  = self::SC_API_BASE . '/sites/' . rawurlencode($site) . '/sitemaps';
        return $this->apiGet($url);
    }

    public function submitSitemap(string $sitemapUrl): array
    {
        $site = $this->getSiteProperty();
        $url  = self::SC_API_BASE . '/sites/' . rawurlencode($site) . '/sitemaps/' . rawurlencode($sitemapUrl);
        return $this->apiPutEmpty($url);
    }

    public function deleteSitemap(string $sitemapUrl): array
    {
        $site = $this->getSiteProperty();
        $url  = self::SC_API_BASE . '/sites/' . rawurlencode($site) . '/sitemaps/' . rawurlencode($sitemapUrl);
        return $this->apiDelete($url);
    }

    // ─── URL Inspection ──────────────────────────────────────────────────────

    public function inspectUrl(string $inspectionUrl, string $languageCode = 'en-US'): array
    {
        $site = $this->getSiteProperty();
        $body = [
            'inspectionUrl' => $inspectionUrl,
            'siteUrl'       => $site,
            'languageCode'  => $languageCode,
        ];
        return $this->apiPostJson(self::SC_INSPECT_API, $body);
    }

    // ─── Indexing API ────────────────────────────────────────────────────────

    public function notifyUpdated(string $url): array
    {
        return $this->apiPostJson(self::INDEXING_API, ['url' => $url, 'type' => 'URL_UPDATED']);
    }

    public function notifyDeleted(string $url): array
    {
        return $this->apiPostJson(self::INDEXING_API, ['url' => $url, 'type' => 'URL_DELETED']);
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function persistTokens(string $refresh, string $access, int $expiresIn): void
    {
        $patch = [
            self::CFG_ACCESS_TOKEN  => $access,
            self::CFG_TOKEN_EXPIRES => time() + $expiresIn,
        ];
        if ($refresh !== '') {
            $patch[self::CFG_REFRESH_TOKEN] = $refresh;
        }
        $this->saveSettings($patch);
    }

    private function saveSettings(array $patch): void
    {
        // Pull current settings, merge patch, write back. Avoids clobbering
        // unrelated settings written through other code paths.
        $current = $this->settingModel->getSetting('module_oc_kit_seo_core', $this->storeId);
        if (!is_array($current)) $current = [];
        foreach ($patch as $k => $v) {
            $current[$k] = $v;
            // Reflect immediately for in-process reads
            $this->config->set($k, $v);
        }
        $this->settingModel->editSetting('module_oc_kit_seo_core', $current, $this->storeId);
    }

    private function apiGet(string $url): array
    {
        return $this->apiCall('GET', $url, null);
    }

    private function apiDelete(string $url): array
    {
        return $this->apiCall('DELETE', $url, null);
    }

    private function apiPutEmpty(string $url): array
    {
        return $this->apiCall('PUT', $url, '');
    }

    private function apiPostJson(string $url, array $body): array
    {
        return $this->apiCall('POST', $url, json_encode($body), ['Content-Type: application/json']);
    }

    private function apiCall(string $method, string $url, ?string $body, array $extraHeaders = []): array
    {
        $token = $this->getAccessToken();
        $headers = array_merge(['Authorization: Bearer ' . $token, 'Accept: application/json'], $extraHeaders);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Network error: ' . $err);
        }
        $decoded = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($decoded)) $decoded = [];
        if ($code >= 400) {
            $msg = $decoded['error']['message'] ?? ('HTTP ' . $code);
            throw new \RuntimeException('Google API: ' . $msg, $code);
        }
        return $decoded;
    }

    private function httpPostForm(string $url, array $form): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($form),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Network error: ' . $err);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid Google response: ' . substr($raw, 0, 200));
        }
        if ($code >= 400) {
            $msg = $decoded['error_description'] ?? $decoded['error'] ?? ('HTTP ' . $code);
            throw new \RuntimeException('Google OAuth: ' . (is_string($msg) ? $msg : json_encode($msg)), $code);
        }
        return $decoded;
    }
}
