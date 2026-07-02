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

class FacebookProvider extends AbstractAuthProvider
{
    private const AUTH_URL    = 'https://www.facebook.com/v18.0/dialog/oauth';
    private const TOKEN_URL   = 'https://graph.facebook.com/v18.0/oauth/access_token';
    private const PROFILE_URL = 'https://graph.facebook.com/v18.0/me';

    private HttpClient $http;

    public function __construct($config, HttpClient $http)
    {
        parent::__construct($config);
        $this->http = $http;
    }

    public function name(): string { return 'facebook'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_facebook_enabled')
            && $this->config->get('module_oc_kit_easy_login_facebook_app_id')
            && $this->config->get('module_oc_kit_easy_login_facebook_app_secret');
    }

    public function buildAuthUrl(string $redirectUri, string $state, string $codeChallenge = ''): string
    {
        $params = [
            'client_id'     => (string)$this->config->get('module_oc_kit_easy_login_facebook_app_id'),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'email,public_profile',
            'state'         => $state,
        ];
        if ($codeChallenge !== '') {
            $params['code_challenge']        = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $redirectUri, string $codeVerifier = ''): ProviderProfile
    {
        $tokenParams = [
            'client_id'     => (string)$this->config->get('module_oc_kit_easy_login_facebook_app_id'),
            'client_secret' => (string)$this->config->get('module_oc_kit_easy_login_facebook_app_secret'),
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ];
        if ($codeVerifier !== '') {
            $tokenParams['code_verifier'] = $codeVerifier;
        }
        $tokenRes = $this->http->getJson(self::TOKEN_URL . '?' . http_build_query($tokenParams));

        $accessToken = (string)($tokenRes['access_token'] ?? '');
        if ($accessToken === '') {
            throw new ProviderException('Missing access_token in Facebook response');
        }

        $profile = $this->http->getJson(self::PROFILE_URL . '?' . http_build_query([
            'fields'       => 'id,name,first_name,last_name,email,picture.type(large)',
            'access_token' => $accessToken,
        ]));

        $id = (string)($profile['id'] ?? '');
        if ($id === '') {
            throw new ProviderException('Missing id in Facebook profile');
        }

        $picture = null;
        if (isset($profile['picture']['data']['url'])) {
            $picture = (string)$profile['picture']['data']['url'];
        }

        return new ProviderProfile([
            'provider'         => 'facebook',
            'provider_user_id' => $id,
            // Facebook Graph API does not expose a per-user "email_verified" claim.
            // Treat FB email as UNVERIFIED — AuthService will route through the
            // 'needs_confirmation' branch when an existing customer matches by email.
            // This blocks account-takeover via FB accounts registered with a victim's email.
            'email'            => isset($profile['email']) ? (string)$profile['email'] : null,
            'email_verified'   => false,
            'display_name'     => isset($profile['name']) ? (string)$profile['name'] : null,
            'first_name'       => isset($profile['first_name']) ? (string)$profile['first_name'] : null,
            'last_name'        => isset($profile['last_name']) ? (string)$profile['last_name'] : null,
            'avatar_url'       => $picture,
            'raw'              => $profile,
        ]);
    }
}
