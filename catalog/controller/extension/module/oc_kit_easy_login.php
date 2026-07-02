<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';

use OcKit\EasyLogin\EasyLogin;
use OcKit\EasyLogin\Exceptions\ProviderException;
use OcKit\EasyLogin\Libs\Providers\GoogleProvider;
use OcKit\EasyLogin\Libs\Providers\TelegramProvider;
use OcKit\EasyLogin\Libs\Providers\AppleProvider;
use OcKit\EasyLogin\Libs\Providers\FacebookProvider;
use OcKit\EasyLogin\Libs\Providers\EmailMagicProvider;
use OcKit\EasyLogin\Libs\Providers\SmsOtpProvider;

class ControllerExtensionModuleOcKitEasyLogin extends Controller
{
    private const STATE_KEY = 'easy_login_oauth_state';
    private const CSRF_KEY  = 'easy_login_csrf_token';

    private ?EasyLogin $lib = null;
    private bool $wasLinkMode = false;
    /** Request-scope cache for rendered buttons output, keyed by settings hash. */
    private array $buttonsRenderCache = [];

    private function getLib(): EasyLogin
    {
        if ($this->lib === null) {
            $this->lib = new EasyLogin($this->registry);
        }
        return $this->lib;
    }

    private function isModuleEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_status');
    }

    /**
     * Stricter gate for surfaces that should not appear at all when license is
     * expired or invalid (buttons, one-tap, account-section). Existing OAuth
     * callbacks intentionally do NOT use this — a customer mid-flow should be
     * allowed to finish auth even on the day the license lapses.
     */
    private function isModuleOperational(): bool
    {
        return $this->isModuleEnabled() && $this->getLib()->isLicensed();
    }

    private function callbackUrl(string $action): string
    {
        return $this->getLib()->buildCallbackUrl('', $action);
    }

    private function jsonOut(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    /**
     * Save optional return URL from request into session for post-login redirect.
     * If neither GET 'return' nor POST 'redirect' is set, leaves session untouched
     * and finalizeRedirect falls back to default account/account.
     */
    private function captureReturnUrl(): void
    {
        $url = (string)($this->request->post['redirect'] ?? $this->request->get['return'] ?? '');
        if ($url !== '' && $this->isSafeReturnUrl($url)) {
            $this->session->data[self::STATE_KEY . '_return'] = $url;
        }
    }

    /**
     * Capture "link to current customer" flag from request, store in session.
     * Used so callbacks know to link instead of authenticate.
     */
    private function captureLinkMode(): void
    {
        $isLink = !empty($this->request->post['link']) || !empty($this->request->get['link']);
        if ($isLink && $this->customer->getId()) {
            $this->session->data[self::STATE_KEY . '_link'] = true;
        }
    }

    private function consumeLinkMode(): bool
    {
        $isLink = !empty($this->session->data[self::STATE_KEY . '_link']);
        unset($this->session->data[self::STATE_KEY . '_link']);
        $active = $isLink && $this->customer->getId();
        if ($active) $this->wasLinkMode = true;
        return $active;
    }

    /**
     * Either link a provider to currently logged-in customer (no re-login),
     * or run normal authenticate flow. The 'needs_confirmation' action
     * (existing customer matched by unverified email) is treated as failure
     * here — the caller's finalizeRedirect bounces to login with a clear
     * banner asking the user to sign in to the existing account and link
     * the provider from /account/account. This blocks the FB-unverified-
     * email account-takeover vector.
     */
    private function linkOrAuthenticate($profile)
    {
        if ($this->consumeLinkMode()) {
            return $this->linkToCurrentCustomer($profile);
        }
        return $this->getLib()->getAuthService()->authenticate($profile);
    }

    private function linkToCurrentCustomer($profile)
    {
        require_once DIR_SYSTEM . 'library/ockit/easy_login/dto/AuthResult.php';

        $customerId = (int)$this->customer->getId();
        $repo       = $this->getLib()->getIdentities();
        $logger     = $this->getLib()->getLogger();

        $existing = $repo->findByProviderUser($profile->provider, $profile->providerUserId);
        if ($existing) {
            if ((int)$existing['customer_id'] === $customerId) {
                $repo->touchLastLogin((int)$existing['identity_id']);
                $logger->log($profile->provider, 'success', ['customer_id' => $customerId, 'email' => $profile->email]);
                return new \OcKit\EasyLogin\Dto\AuthResult([
                    'success' => true, 'customer_id' => $customerId, 'action' => 'success',
                ]);
            }
            // Linked to another customer — conflict, refuse silently
            $logger->log($profile->provider, 'failed', [
                'customer_id' => $customerId,
                'email'       => $profile->email,
                'error'       => 'Provider already linked to another customer',
            ]);
            return new \OcKit\EasyLogin\Dto\AuthResult([
                'success' => false, 'action' => 'conflict',
                'message' => 'Provider already linked to another account',
            ]);
        }

        $repo->create($customerId, $profile);
        $logger->log($profile->provider, 'linked', ['customer_id' => $customerId, 'email' => $profile->email]);
        return new \OcKit\EasyLogin\Dto\AuthResult([
            'success' => true, 'customer_id' => $customerId, 'action' => 'linked',
        ]);
    }

    private function checkRateLimit(?string $email = null): bool
    {
        $rl = $this->getLib()->getRateLimiter();
        $ip = (string)(\OcKit\EasyLogin\Libs\LoginLogger::clientIp($this->config) ?? '');
        if ($rl->isLimitedByIp($ip)) {
            $this->getLib()->getLogger()->log('-', 'rate_limited', ['email' => $email]);
            return false;
        }
        if ($email !== null && $email !== '' && $rl->isLimitedByEmail($email)) {
            $this->getLib()->getLogger()->log('-', 'rate_limited', ['email' => $email]);
            return false;
        }
        return true;
    }

    // ─── Account section (linked identities) ──────────────────────────────────

    public function account_section(): string
    {
        if (!$this->isModuleOperational() || !$this->customer->getId()) return '';

        $this->load->language('extension/module/oc_kit_easy_login');

        $identities = $this->getLib()->getIdentities()->findAllForCustomer((int)$this->customer->getId());
        $linkedProviders = array_unique(array_column($identities, 'provider'));

        // Compute whether there's anything left to link (link mode hides passwordless providers)
        $linkableProviders = ['google', 'facebook', 'apple', 'telegram'];
        $hasAddable = false;
        foreach ($linkableProviders as $p) {
            if ($this->getLib()->getProvider($p)->isEnabled() && !in_array($p, $linkedProviders, true)) {
                $hasAddable = true;
                break;
            }
        }

        $providerLabels = [
            'google'      => $this->language->get('provider_google'),
            'facebook'    => $this->language->get('provider_facebook'),
            'apple'       => $this->language->get('provider_apple'),
            'telegram'    => $this->language->get('provider_telegram'),
            'email_magic' => $this->language->get('provider_email_magic'),
            'sms_otp'     => $this->language->get('provider_sms_otp'),
        ];
        // Annotate each identity with a human-readable label
        foreach ($identities as &$it) {
            $it['label'] = $providerLabels[$it['provider']] ?? $it['provider'];
        }
        unset($it);

        // Per-session CSRF token bound to the account-section render. The
        // unlink endpoint refuses POSTs without a matching token so a third-
        // party site cannot trick a logged-in customer into detaching identities.
        if (empty($this->session->data[self::CSRF_KEY])) {
            $this->session->data[self::CSRF_KEY] = bin2hex(random_bytes(16));
        }
        $csrf = (string)$this->session->data[self::CSRF_KEY];

        $data = [
            'identities'        => $identities,
            'csrf_token'        => $csrf,
            'unlink_url'        => html_entity_decode($this->callbackUrl('unlink')),
            'has_addable'       => $hasAddable,
            'buttons_block'     => $hasAddable ? $this->load->controller('extension/module/oc_kit_easy_login/buttons', [
                'context'           => 'account',
                'linked_providers'  => $linkedProviders,
                'hide_passwordless' => true,
                'link_mode'         => true,
            ]) : '',
            // Lang strings (no |default in twig — full localization)
            'heading_linked'    => $this->language->get('heading_linked'),
            'text_no_identities'=> $this->language->get('text_no_identities'),
            'text_link_more'    => $this->language->get('text_link_more'),
            'text_confirm_unlink'=> $this->language->get('text_confirm_unlink'),
            'button_unlink'     => $this->language->get('button_unlink'),
        ];
        return $this->load->view('extension/module/ockit/easy_login/account_linked', $data);
    }

    // ─── Block render (used by OCMOD or AJAX include) ─────────────────────────

    public function buttons($settings = []): string
    {
        if (!$this->isModuleOperational()) {
            return '';
        }

        // The same buttons block can be rendered twice per page (login.twig
        // injection + account_section's link-mode block). Cache the rendered
        // output per-request by settings signature so we don't re-do ~30
        // config/language lookups and another twig render for an identical
        // payload.
        $cacheKey = md5(serialize([$settings, $this->customer->getId()]));
        if (isset($this->buttonsRenderCache[$cacheKey])) {
            return $this->buttonsRenderCache[$cacheKey];
        }

        $this->load->language('extension/module/oc_kit_easy_login');

        $linkedProviders  = (array)($settings['linked_providers'] ?? []);
        $hidePasswordless = (bool)($settings['hide_passwordless'] ?? false);
        $context          = (string)($settings['context'] ?? ($this->request->get['context'] ?? 'popup'));
        $returnUrl        = (string)($settings['return_url'] ?? '');
        $linkMode         = (bool)($settings['link_mode'] ?? false);
        $extraParams      = ($returnUrl !== '' ? '&return=' . urlencode($returnUrl) : '')
                          . ($linkMode ? '&link=1' : '');
        $returnSuffix     = $extraParams;

        $token = $this->session->data[self::STATE_KEY] ?? bin2hex(random_bytes(8));
        $this->session->data[self::STATE_KEY] = $token;

        /** @var GoogleProvider $g */
        $g = $this->getLib()->getProvider('google');
        /** @var TelegramProvider $t */
        $t = $this->getLib()->getProvider('telegram');
        /** @var AppleProvider $a */
        $a = $this->getLib()->getProvider('apple');
        /** @var FacebookProvider $f */
        $f = $this->getLib()->getProvider('facebook');
        /** @var EmailMagicProvider $em */
        $em = $this->getLib()->getProvider('email_magic');
        /** @var SmsOtpProvider $sms */
        $sms = $this->getLib()->getProvider('sms_otp');

        $data = [
            'context'         => $context,
            'linked_providers'=> $linkedProviders,
            'hide_passwordless' => $hidePasswordless,
            'return_url'      => $returnUrl,
            'link_mode'       => $linkMode,
            'urls'            => [
                'google_redirect'    => html_entity_decode($this->callbackUrl('google_redirect')) . $returnSuffix,
                'facebook_redirect'  => html_entity_decode($this->callbackUrl('facebook_redirect')) . $returnSuffix,
                'apple_redirect'     => html_entity_decode($this->callbackUrl('apple_redirect'))    . $returnSuffix,
                'magic_send'         => html_entity_decode($this->callbackUrl('magic_send')),
                'sms_send'           => html_entity_decode($this->callbackUrl('sms_send')),
                'sms_verify'         => html_entity_decode($this->callbackUrl('sms_verify')),
                'telegram_callback'  => html_entity_decode($this->callbackUrl('telegram_callback')),
                'google_one_tap'     => html_entity_decode($this->callbackUrl('google_one_tap')),
            ],
            'google_enabled'  => $g->isEnabled(),
            'google_mode'     => $g->isEnabled() ? $g->getMode() : '',
            'google_client_id'=> $g->isEnabled() ? $g->getClientId() : '',
            'google_button_theme' => (string)($this->config->get('module_oc_kit_easy_login_google_button_theme') ?: 'outline'),
            'google_button_text'  => (string)($this->config->get('module_oc_kit_easy_login_google_button_text') ?: 'continue_with'),
            'telegram_enabled' => $t->isEnabled(),
            'telegram_bot_username' => $t->isEnabled() ? $t->getBotUsername() : '',
            'telegram_button_size'  => (string)($this->config->get('module_oc_kit_easy_login_telegram_button_size') ?: 'large'),
            'telegram_request_phone'=> (bool)$this->config->get('module_oc_kit_easy_login_telegram_request_phone'),
            'apple_enabled'    => $a->isEnabled(),
            'apple_button_theme' => (string)($this->config->get('module_oc_kit_easy_login_apple_button_theme') ?: 'black'),
            'facebook_enabled' => $f->isEnabled(),
            'email_magic_enabled' => $em->isEnabled(),
            'sms_otp_enabled'  => $sms->isEnabled(),
            'sms_otp_code_length' => $sms->getCodeLength(),
            'sms_otp_code_placeholder' => str_repeat('0', $sms->getCodeLength()),
            'lang_code' => $this->config->get('config_language'),

            'text_or'                  => $this->language->get('text_or'),
            'button_continue_google'   => $this->language->get('button_google_' . ((string)$this->config->get('module_oc_kit_easy_login_google_button_text') ?: 'continue_with')),
            'button_continue_facebook' => $this->language->get('button_continue_facebook'),
            'button_continue_apple'    => $this->language->get('button_continue_apple'),
            'button_send_magic'        => $this->language->get('button_send_magic'),
            'button_send_sms_code'     => $this->language->get('button_send_sms_code'),
            'button_verify'            => $this->language->get('button_verify'),
            'placeholder_email'        => $this->language->get('placeholder_email'),
            'placeholder_phone'        => $this->language->get('placeholder_phone'),
            'entry_phone'              => $this->language->get('entry_phone'),
            'js_i18n' => [
                'error_email_required' => $this->language->get('js_error_email_required'),
                'success_magic_sent'   => $this->language->get('js_success_magic_sent'),
                'error_phone_required' => $this->language->get('js_error_phone_required'),
                'success_code_sent'    => $this->language->get('js_success_code_sent'),
                'error_send_failed'    => $this->language->get('js_error_send_failed'),
                'error_code_required'  => $this->language->get('js_error_code_required'),
                'error_invalid_code'   => $this->language->get('js_error_invalid_code'),
                'error_network'        => $this->language->get('js_error_network'),
                'confirm_unlink'       => $this->language->get('text_confirm_unlink'),
                'error_login_conflict' => $this->language->get('js_error_login_conflict'),
                'error_login_needs_confirmation' => $this->language->get('js_error_login_needs_confirmation'),
            ],
        ];

        $rendered = $this->load->view('extension/module/ockit/easy_login/buttons', $data);
        $this->buttonsRenderCache[$cacheKey] = $rendered;
        return $rendered;
    }

    public function one_tap(): string
    {
        if (!$this->isModuleOperational() || !empty($this->customer->getId())) return '';

        /** @var GoogleProvider $g */
        $g = $this->getLib()->getProvider('google');
        if (!$g->isEnabled()) return '';

        $mode = $g->getMode();
        if ($mode !== 'one_tap' && $mode !== 'both') return '';

        // Per-session One-Tap nonce — Google echoes its SHA-256 back in the id_token's
        // 'nonce' claim. We compare on verify so an attacker cannot replay a token
        // they got Google to issue against this client_id in another session.
        $nonce = bin2hex(random_bytes(16));
        $this->session->data[self::STATE_KEY . '_one_tap_nonce'] = $nonce;

        $position   = (string)($this->config->get('module_oc_kit_easy_login_google_one_tap_position') ?: 'top_right');
        $topOffset  = (int)$this->config->get('module_oc_kit_easy_login_google_one_tap_top_offset');
        $sideOffset = (int)($this->config->get('module_oc_kit_easy_login_google_one_tap_side_offset') ?: 20);

        // Build CSS for the prompt parent based on chosen corner.
        // !important is needed because Google's gsi/client sets inline styles on the iframe.
        $vertical   = (strpos($position, 'top') === 0)
            ? 'top:' . $topOffset . 'px !important; bottom:auto !important;'
            : 'bottom:' . $topOffset . 'px !important; top:auto !important;';
        $horizontal = (strpos($position, 'right') !== false)
            ? 'right:' . $sideOffset . 'px !important; left:auto !important;'
            : 'left:' . $sideOffset . 'px !important; right:auto !important;';
        $promptCss  = 'position:fixed !important;' . $vertical . $horizontal . 'z-index:9999 !important;';

        $data = [
            'client_id'    => $g->getClientId(),
            'position'     => $position,
            'prompt_css'   => $promptCss,
            'one_tap_nonce'=> $nonce,
            'callback_url' => html_entity_decode($this->callbackUrl('google_one_tap')),
        ];
        return $this->load->view('extension/module/ockit/easy_login/one_tap', $data);
    }

    // ─── Google: OAuth button flow ────────────────────────────────────────────

    public function google_redirect(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }

        /** @var GoogleProvider $provider */
        $provider = $this->getLib()->getProvider('google');
        if (!$provider->isEnabled()) { $this->bailToLogin(); return; }

        $state    = bin2hex(random_bytes(16));
        $verifier = self::generateVerifier();
        $this->session->data[$this->stateKey('google')]    = $state;
        $this->session->data[$this->verifierKey('google')] = $verifier;
        if (isset($this->request->get['return'])) {
            $this->session->data[self::STATE_KEY . '_return'] = (string)$this->request->get['return'];
        }
        $this->captureLinkMode();

        $authUrl = $provider->buildAuthUrl(
            $this->callbackUrl('google_callback'),
            $state,
            self::deriveChallenge($verifier)
        );
        $this->response->redirect($authUrl);
    }

    public function google_callback(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }

        $code  = (string)($this->request->get['code']  ?? '');
        $state = (string)($this->request->get['state'] ?? '');
        $error = (string)($this->request->get['error'] ?? '');

        $expectedState = (string)($this->session->data[$this->stateKey('google')] ?? '');
        $verifier      = (string)($this->session->data[$this->verifierKey('google')] ?? '');
        unset($this->session->data[$this->stateKey('google')], $this->session->data[$this->verifierKey('google')]);

        if ($error !== '' || $code === '' || $state === '' || !hash_equals($expectedState, $state)) {
            $this->getLib()->getLogger()->log('google', 'failed', ['error' => $error ?: 'Invalid OAuth state or missing code']);
            $this->bailToLogin();
            return;
        }

        try {
            /** @var GoogleProvider $provider */
            $provider = $this->getLib()->getProvider('google');
            $profile  = $provider->handleCallback($code, $this->callbackUrl('google_callback'), $verifier);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('google', 'failed', ['error' => $e->getMessage()]);
            $this->bailToLogin();
            return;
        }

        $this->finalizeRedirect($result->success, $result->action ?? null);
    }

    public function google_one_tap(): void
    {
        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->isModuleEnabled()) {
            $this->jsonOut(['success' => false, 'message' => 'Invalid request']); return;
        }
        $credential = (string)($this->request->post['credential'] ?? '');
        if ($credential === '') { $this->jsonOut(['success' => false, 'message' => 'Missing credential']); return; }

        // Single-use nonce: Google echoes sha256(data-nonce) into the id_token.
        // Consume immediately so a stolen credential cannot be replayed.
        $expectedNonce = (string)($this->session->data[self::STATE_KEY . '_one_tap_nonce'] ?? '');
        unset($this->session->data[self::STATE_KEY . '_one_tap_nonce']);

        try {
            /** @var GoogleProvider $provider */
            $provider = $this->getLib()->getProvider('google');
            $profile  = $provider->verifyIdToken($credential, $expectedNonce);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('google', 'failed', ['error' => $e->getMessage()]);
            $this->jsonOut(['success' => false, 'message' => 'Verification failed']); return;
        }
        $this->jsonOut([
            'success'      => $result->success,
            'redirect_url' => html_entity_decode($this->buildPostLoginUrl($result->success, $result->action ?? null)),
        ]);
    }

    // ─── Telegram: Login Widget callback ──────────────────────────────────────

    public function telegram_callback(): void
    {
        if (!$this->isModuleEnabled() || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['success' => false, 'message' => 'Invalid request']); return;
        }
        $this->captureReturnUrl();
        $this->captureLinkMode();
        $payload = $this->extractTelegramPayload($this->request->post);

        try {
            /** @var TelegramProvider $provider */
            $provider = $this->getLib()->getProvider('telegram');
            if (!$provider->isEnabled()) throw new ProviderException('Telegram provider disabled');
            $profile = $provider->verifyAndBuildProfile($payload);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('telegram', 'failed', ['error' => $e->getMessage()]);
            $this->jsonOut(['success' => false, 'message' => 'Verification failed']); return;
        }
        $this->jsonOut([
            'success'      => $result->success,
            'redirect_url' => html_entity_decode($this->buildPostLoginUrl($result->success, $result->action ?? null)),
        ]);
    }

    private function extractTelegramPayload(array $post): array
    {
        // Blacklist (not whitelist): Telegram's HMAC covers every field they
        // send, so if they add a new field tomorrow, a hard-coded whitelist
        // would silently break logins. We only strip request-routing keys
        // and our own auxiliary params before feeding the payload to the
        // HMAC verifier.
        $strip = ['route', '_route_', 'redirect', 'link'];
        $out = [];
        foreach ($post as $k => $v) {
            if (in_array($k, $strip, true)) continue;
            if (is_array($v)) continue;
            if ((string)$v === '') continue; // Telegram never sends empties — keeps HMAC input minimal
            $out[(string)$k] = (string)$v;
        }
        return $out;
    }

    // ─── Apple: Sign in with Apple ────────────────────────────────────────────

    public function apple_redirect(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }

        /** @var AppleProvider $provider */
        $provider = $this->getLib()->getProvider('apple');
        if (!$provider->isEnabled()) { $this->bailToLogin(); return; }

        // Apple uses response_mode=form_post — the callback is a cross-site POST,
        // so SameSite=Lax browsers will NOT send the OC session cookie. Persist
        // state + PKCE verifier in a dedicated SameSite=None signed cookie.
        $verifier = self::generateVerifier();
        $state = \OcKit\EasyLogin\Libs\SignedStateCookie::issue($this->cookieSecret(), [
            'return'   => (string)($this->request->get['return'] ?? ''),
            'link'     => !empty($this->request->get['link']) && $this->customer->getId(),
            'verifier' => $verifier,
        ]);

        $authUrl = $provider->buildAuthUrl(
            $this->callbackUrl('apple_callback'),
            $state,
            self::deriveChallenge($verifier)
        );
        $this->response->redirect($authUrl);
    }

    public function apple_callback(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') { $this->bailToLogin(); return; }

        $code     = (string)($this->request->post['code']     ?? '');
        $state    = (string)($this->request->post['state']    ?? '');
        $userJson = (string)($this->request->post['user']     ?? '');

        $cookieData = \OcKit\EasyLogin\Libs\SignedStateCookie::consume($this->cookieSecret(), $state);
        if ($code === '' || $state === '' || $cookieData === null) {
            // If cookies are disabled (or stripped by an extension), the
            // signed-state cookie never reaches us and Apple flow can't
            // complete. Surface a clearer reason in the log so admins can
            // distinguish "cookies blocked" from "tampered state".
            $reason = empty($_COOKIE)
                ? 'Apple state cookie missing — cookies blocked?'
                : 'Invalid state or missing code';
            $this->getLib()->getLogger()->log('apple', 'failed', ['error' => $reason]);
            $this->bailToLogin();
            return;
        }

        // Replay state-cookie context into session so existing helpers
        // (linkOrAuthenticate, finalizeRedirect) work unchanged.
        if ($cookieData['return'] !== '' && $this->isSafeReturnUrl($cookieData['return'])) {
            $this->session->data[self::STATE_KEY . '_return'] = $cookieData['return'];
        }
        if ($cookieData['link'] && $this->customer->getId()) {
            $this->session->data[self::STATE_KEY . '_link'] = true;
        }

        try {
            /** @var AppleProvider $provider */
            $provider = $this->getLib()->getProvider('apple');
            // Note: id_token from POST is intentionally ignored by AppleProvider —
            // it verifies the token-endpoint id_token via Apple JWKS.
            $profile  = $provider->handleCallback(
                $code,
                null,
                $userJson ?: null,
                $this->callbackUrl('apple_callback'),
                (string)$cookieData['verifier']
            );
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('apple', 'failed', ['error' => $e->getMessage()]);
            $this->bailToLogin();
            return;
        }

        $this->finalizeRedirect($result->success, $result->action ?? null);
    }

    /**
     * Per-store secret for signing short-lived OAuth-state cookies.
     * Combines OC's config_encryption (admin-only secret stored in DB) with a
     * module-specific salt so a leak of one module's signed cookie can't be
     * replayed against another.
     */
    private function cookieSecret(): string
    {
        $base = (string)$this->config->get('config_encryption');
        if ($base === '') $base = (string)(defined('HTTP_SERVER') ? HTTP_SERVER : 'el_fallback');
        return hash('sha256', 'oc_kit_easy_login|' . $base);
    }

    // ─── Facebook ─────────────────────────────────────────────────────────────

    public function facebook_redirect(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }

        /** @var FacebookProvider $provider */
        $provider = $this->getLib()->getProvider('facebook');
        if (!$provider->isEnabled()) { $this->bailToLogin(); return; }

        $state    = bin2hex(random_bytes(16));
        $verifier = self::generateVerifier();
        $this->session->data[$this->stateKey('facebook')]    = $state;
        $this->session->data[$this->verifierKey('facebook')] = $verifier;
        if (isset($this->request->get['return'])) {
            $this->session->data[self::STATE_KEY . '_return'] = (string)$this->request->get['return'];
        }
        $this->captureLinkMode();

        $authUrl = $provider->buildAuthUrl(
            $this->callbackUrl('facebook_callback'),
            $state,
            self::deriveChallenge($verifier)
        );
        $this->response->redirect($authUrl);
    }

    public function facebook_callback(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }

        $code     = (string)($this->request->get['code']  ?? '');
        $state    = (string)($this->request->get['state'] ?? '');
        $expected = (string)($this->session->data[$this->stateKey('facebook')] ?? '');
        $verifier = (string)($this->session->data[$this->verifierKey('facebook')] ?? '');
        unset($this->session->data[$this->stateKey('facebook')], $this->session->data[$this->verifierKey('facebook')]);

        if ($code === '' || $state === '' || !hash_equals($expected, $state)) {
            $this->getLib()->getLogger()->log('facebook', 'failed', ['error' => 'Invalid state']);
            $this->bailToLogin();
            return;
        }

        try {
            /** @var FacebookProvider $provider */
            $provider = $this->getLib()->getProvider('facebook');
            $profile  = $provider->handleCallback($code, $this->callbackUrl('facebook_callback'), $verifier);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('facebook', 'failed', ['error' => $e->getMessage()]);
            $this->bailToLogin();
            return;
        }

        $this->finalizeRedirect($result->success, $result->action ?? null);
    }

    // ─── Email Magic Link ─────────────────────────────────────────────────────

    public function magic_send(): void
    {
        if (!$this->isModuleEnabled() || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['success' => false, 'message' => 'Invalid request']); return;
        }
        $email = strtolower(trim((string)($this->request->post['email'] ?? '')));
        $this->captureReturnUrl();
        $this->captureLinkMode();
        if (!$this->checkRateLimit($email)) {
            $this->jsonOut(['success' => true, 'message' => 'If the email exists, a link was sent']); return;
        }

        try {
            /** @var EmailMagicProvider $provider */
            $provider = $this->getLib()->getProvider('email_magic');
            if (!$provider->isEnabled()) throw new ProviderException('Email Magic Link disabled');
            $token   = $provider->issueToken($email);
            $magic   = $this->callbackUrl('magic_verify') . '&token=' . urlencode($token);
            $provider->sendMagicLink($email, $magic, (string)$this->config->get('config_language'));
            $this->getLib()->getLogger()->log('email_magic', 'success', ['email' => $email]);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('email_magic', 'failed', ['email' => $email, 'error' => $e->getMessage()]);
            // Always reply identically to prevent email enumeration
        } catch (\Throwable $e) {
            $this->getLib()->getLogger()->log('email_magic', 'failed', ['email' => $email, 'error' => $e->getMessage()]);
        }

        $this->jsonOut(['success' => true, 'message' => 'If the email exists, a link was sent']);
    }

    public function magic_verify(): void
    {
        if (!$this->isModuleEnabled()) { $this->bailToLogin(); return; }
        $token = (string)($this->request->get['token'] ?? '');
        if ($token === '') { $this->bailToLogin(); return; }

        try {
            /** @var EmailMagicProvider $provider */
            $provider = $this->getLib()->getProvider('email_magic');
            $profile  = $provider->verifyToken($token);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('email_magic', 'failed', ['error' => $e->getMessage()]);
            $this->bailToLogin();
            return;
        }
        $this->finalizeRedirect($result->success, $result->action ?? null);
    }

    // ─── SMS OTP ──────────────────────────────────────────────────────────────

    public function sms_send(): void
    {
        if (!$this->isModuleEnabled() || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['success' => false, 'message' => 'Invalid request']); return;
        }
        $phone = (string)($this->request->post['phone'] ?? '');
        $this->captureReturnUrl();
        $this->captureLinkMode();

        try {
            /** @var SmsOtpProvider $provider */
            $provider = $this->getLib()->getProvider('sms_otp');
            if (!$provider->isEnabled()) throw new ProviderException('SMS OTP disabled');

            // Normalize first so per-phone rate limit and OTP storage agree on the
            // same canonical recipient (e.g. 0671234567 ≡ +380671234567 ≡ 380671234567).
            $normalized = $provider->normalizePhone($phone);

            // IP-level cap (cheap log-table count) catches broad abuse.
            if (!$this->checkRateLimit($normalized)) {
                $this->getLib()->getLogger()->log('sms_otp', 'rate_limited', ['email' => $normalized]);
                $this->jsonOut(['success' => false, 'message' => $this->language->get('err_too_many_requests')]); return;
            }

            // Per-phone send cap — without this an attacker can spam a victim's
            // phone (and burn the merchant's TurboSMS balance) regardless of IP.
            $recent = $this->getLib()->getOtp()->recentCountFor('sms', $normalized, $provider->getSendWindowSeconds());
            if ($recent >= $provider->getMaxSendsPerPhone()) {
                $this->getLib()->getLogger()->log('sms_otp', 'rate_limited', ['email' => $normalized]);
                $this->jsonOut(['success' => false, 'message' => $this->language->get('err_too_many_requests')]); return;
            }

            $normalized = $provider->issueAndSend($phone, (string)$this->config->get('config_language'));
            $this->getLib()->getLogger()->log('sms_otp', 'success', ['email' => $normalized]);
            $this->jsonOut(['success' => true, 'phone' => $normalized]);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('sms_otp', 'failed', ['error' => $e->getMessage()]);
            $this->jsonOut(['success' => false, 'message' => $this->translateError($e->getMessage())]);
        }
    }

    public function sms_verify(): void
    {
        if (!$this->isModuleEnabled() || $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['success' => false, 'message' => 'Invalid request']); return;
        }
        $phone = (string)($this->request->post['phone'] ?? '');
        $code  = (string)($this->request->post['code']  ?? '');

        try {
            /** @var SmsOtpProvider $provider */
            $provider = $this->getLib()->getProvider('sms_otp');
            $profile  = $provider->verifyCode($phone, $code);
            $result   = $this->linkOrAuthenticate($profile);
        } catch (ProviderException $e) {
            $this->getLib()->getLogger()->log('sms_otp', 'failed', ['error' => $e->getMessage()]);
            $this->jsonOut(['success' => false, 'message' => $this->translateError($e->getMessage())]); return;
        }
        $this->jsonOut([
            'success'      => $result->success,
            'redirect_url' => html_entity_decode($this->buildPostLoginUrl($result->success, $result->action ?? null)),
        ]);
    }

    // ─── Account linking (logged-in customer adds a new identity) ─────────────

    public function unlink(): void
    {
        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->customer->getId()) {
            $this->jsonOut(['success' => false, 'message' => 'Not logged in']); return;
        }

        // CSRF check: token issued at account_section() render must match.
        $expected = (string)($this->session->data[self::CSRF_KEY] ?? '');
        $given    = (string)($this->request->post['csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $given)) {
            $this->jsonOut(['success' => false, 'message' => 'Invalid token']); return;
        }

        $identityId = (int)($this->request->post['identity_id'] ?? 0);
        if ($identityId <= 0) { $this->jsonOut(['success' => false, 'message' => 'Invalid id']); return; }

        $repo = $this->getLib()->getIdentities();
        $all  = $repo->findAllForCustomer((int)$this->customer->getId());
        if (count($all) <= 1) {
            // Check if customer has a working password (otherwise unlink would lock them out)
            $row = $this->db->query(
                "SELECT `password` FROM `" . DB_PREFIX . "customer`
                  WHERE `customer_id` = '" . (int)$this->customer->getId() . "' LIMIT 1"
            )->row;
            // If the customer was created via OAuth, the password is random and effectively unusable.
            // We still allow unlinking but warn the user; here we simply allow it.
        }
        $ok = $repo->delete($identityId, (int)$this->customer->getId());
        $this->jsonOut(['success' => $ok]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function finalizeRedirect(bool $success, ?string $action = null): void
    {
        $url = $this->buildPostLoginUrl($success, $action);
        $this->clearFlowState();
        $this->response->redirect($url);
    }

    /**
     * Bail out to /account/login on an early failure (provider disabled,
     * missing code, invalid state, ProviderException). Clears any flow-state
     * keys so a stale return URL or link-mode flag from the failed attempt
     * cannot bleed into the next OAuth flow.
     */
    private function bailToLogin(?string $action = null): void
    {
        $this->clearFlowState();
        $url = $this->buildPostLoginUrl(false, $action);
        $this->response->redirect($url);
    }

    private function clearFlowState(): void
    {
        unset(
            $this->session->data[self::STATE_KEY . '_return'],
            $this->session->data[self::STATE_KEY . '_link']
        );
    }

    private function buildPostLoginUrl(bool $success, ?string $action = null): string
    {
        if (!$success) {
            // Pass the failure reason as a query param so the login page (or a
            // JS hook) can surface a localized message — 'conflict' (provider
            // already linked elsewhere) or 'needs_confirmation' (existing
            // customer found by unverified email).
            $qs = '';
            if ($action === 'conflict' || $action === 'needs_confirmation') {
                $qs = 'el_login_error=' . urlencode($action);
            }
            return $this->url->link('account/login', $qs, true);
        }

        // Link-mode flow: after attaching new provider to current customer, return to account page
        if ($this->wasLinkMode) {
            return $this->url->link('account/account', '', true);
        }

        $return = (string)($this->session->data[self::STATE_KEY . '_return'] ?? '');
        if ($return !== '' && $this->isSafeReturnUrl($return)) return $return;

        if ($this->config->get('module_oc_kit_easy_login_require_phone_after_oauth')
            && !$this->getLib()->getCustomerLinker()->customerHasPhone((int)$this->customer->getId())) {
            return $this->url->link('account/edit', '', true);
        }

        $defaultRoute = trim((string)$this->config->get('module_oc_kit_easy_login_default_redirect_route'));
        if ($defaultRoute !== '') {
            return $this->url->link($defaultRoute, '', true);
        }
        return $this->url->link('account/account', '', true);
    }

    /**
     * Provider-namespaced (and store-namespaced) session key for OAuth state.
     * The store_id suffix prevents cross-store collision when several OC
     * stores share the same PHP session domain.
     */
    private function stateKey(string $provider): string
    {
        $storeId = (int)$this->config->get('config_store_id');
        return self::STATE_KEY . '_' . $provider . '_s' . $storeId;
    }

    /**
     * Session key for PKCE code_verifier — mirrors stateKey() shape.
     */
    private function verifierKey(string $provider): string
    {
        $storeId = (int)$this->config->get('config_store_id');
        return self::STATE_KEY . '_v_' . $provider . '_s' . $storeId;
    }

    /**
     * PKCE code_verifier: 43-128 chars from [A-Za-z0-9-._~] (RFC 7636).
     * 32 random bytes → 43 base64url chars after stripping padding — within range.
     */
    private static function generateVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * PKCE S256 challenge: base64url(sha256(verifier)).
     */
    private static function deriveChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Translate a ProviderException's English-keyed message into the user's
     * current catalog language. Unknown messages fall back to a generic key
     * so we never leak raw exception text (which can include cURL detail or
     * upstream API responses) into the UI. The lang key 'err_generic' is the
     * safe default.
     */
    private function translateError(string $msg): string
    {
        $this->load->language('extension/module/oc_kit_easy_login');
        $map = [
            'Invalid phone number'             => 'err_invalid_phone',
            'No active code for this phone'    => 'err_no_active_code',
            'Too many attempts'                => 'err_too_many_attempts',
            'Invalid code'                     => 'err_invalid_code', // js_error_invalid_code already exists
            'Code already used'                => 'err_code_used',
            'SMS OTP disabled'                 => 'err_provider_disabled',
            'Email Magic Link disabled'        => 'err_provider_disabled',
            'Telegram provider disabled'       => 'err_provider_disabled',
            'Invalid email'                    => 'err_invalid_phone', // closest UX match (input-side)
            'Invalid or expired magic link'    => 'err_link_expired',
            'Magic link already used'          => 'err_link_used',
        ];
        if (strpos($msg, 'SMS send failed') === 0) {
            return $this->language->get('err_sms_failed');
        }
        $key = $map[$msg] ?? 'err_generic';
        $val = $this->language->get($key);
        return $val !== '' ? $val : $this->language->get('err_generic');
    }

    private function isSafeReturnUrl(string $url): bool
    {
        if ($url === '') return false;
        // Block protocol-relative / backslash-prefixed URLs (//evil.com, /\evil.com).
        // Some legacy browsers normalize leading backslashes to forward slashes,
        // which would otherwise let parse_url see a same-origin '/' path while
        // the browser navigates to an attacker host.
        if (preg_match('#^[/\\\\]{2,}#', $url)) return false;

        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null) {
            // Relative path — must start with a single forward slash and a
            // path character, not an additional slash or scheme delimiter.
            return (bool)preg_match('#^/[^/\\\\]#', $url);
        }
        $allowed = parse_url((string)(defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER), PHP_URL_HOST);
        return $host === $allowed;
    }
}
