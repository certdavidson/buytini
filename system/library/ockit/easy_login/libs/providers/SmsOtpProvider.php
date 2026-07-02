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
use OcKit\EasyLogin\Libs\OtpRepository;
use OcKit\TurboSms\TurboSms;
use OcKit\TurboSms\Exceptions\TurboSmsException;

require_once DIR_SYSTEM . 'library/ockit/turbosms/exceptions/TurboSmsException.php';
require_once DIR_SYSTEM . 'library/ockit/turbosms/exceptions/TurboSmsNetworkException.php';
require_once DIR_SYSTEM . 'library/ockit/turbosms/TurboSmsResponse.php';
require_once DIR_SYSTEM . 'library/ockit/turbosms/TurboSms.php';

class SmsOtpProvider extends AbstractAuthProvider
{
    private OtpRepository $otp;

    public function __construct($config, OtpRepository $otp)
    {
        parent::__construct($config);
        $this->otp = $otp;
    }

    public function name(): string { return 'sms_otp'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_sms_otp_enabled')
            && $this->config->get('module_oc_kit_easy_login_sms_otp_token');
    }

    public function getCodeLength(): int
    {
        $len = (int)($this->config->get('module_oc_kit_easy_login_sms_otp_code_length') ?: 6);
        return max(4, min(8, $len));
    }

    public function getTtlSeconds(): int
    {
        return (int)($this->config->get('module_oc_kit_easy_login_sms_otp_ttl_minutes') ?: 5) * 60;
    }

    public function getMaxAttempts(): int
    {
        return (int)($this->config->get('module_oc_kit_easy_login_sms_otp_max_attempts') ?: 3);
    }

    /**
     * Normalize a phone number using TurboSMS rules. Public so callers can
     * key rate-limit / repository lookups by the same canonical value the
     * provider will eventually persist.
     */
    public function normalizePhone(string $phone): string
    {
        $normalized = $this->buildClient()->normalizePhone($phone);
        if ($normalized === '' || strlen($normalized) < 10) {
            throw new ProviderException('Invalid phone number');
        }
        return $normalized;
    }

    /**
     * Returns the per-recipient send window (in seconds) used by the rate limiter.
     */
    public function getSendWindowSeconds(): int
    {
        return 3600;
    }

    /**
     * Maximum SMS sends per phone within getSendWindowSeconds.
     */
    public function getMaxSendsPerPhone(): int
    {
        return (int)($this->config->get('module_oc_kit_easy_login_sms_otp_max_sends_per_phone_per_hour') ?: 5);
    }

    /**
     * Issue a fresh OTP and send via TurboSMS.
     * Returns the normalized phone (used as recipient identity).
     */
    public function issueAndSend(string $phone, string $langCode): string
    {
        $client = $this->buildClient();
        $normalized = $client->normalizePhone($phone);
        if ($normalized === '' || strlen($normalized) < 10) {
            throw new ProviderException('Invalid phone number');
        }

        $code = $this->generateCode();
        $hash = hash('sha256', $code);
        $this->otp->create('sms', $normalized, $hash, $this->getTtlSeconds());

        $template = (string)($this->config->get('module_oc_kit_easy_login_sms_otp_message_' . $langCode)
                          ?: $this->config->get('module_oc_kit_easy_login_sms_otp_message_en-gb')
                          ?: 'Your sign-in code: {code}');
        $text = strtr($template, ['{code}' => $code]);

        $sender = (string)$this->config->get('module_oc_kit_easy_login_sms_otp_sender');
        try {
            $client->sendSms($normalized, $text, $sender ?: null);
        } catch (TurboSmsException $e) {
            throw new ProviderException('SMS send failed: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new ProviderException('SMS send failed: ' . $e->getMessage());
        }

        return $normalized;
    }

    /**
     * Verify code, consume OTP, and return ProviderProfile.
     */
    public function verifyCode(string $phone, string $code): ProviderProfile
    {
        $client = $this->buildClient();
        $normalized = $client->normalizePhone($phone);

        $row = $this->otp->findActive('sms', $normalized);
        if (!$row) {
            throw new ProviderException('No active code for this phone');
        }

        if ((int)$row['attempts'] >= $this->getMaxAttempts()) {
            $this->otp->consume((int)$row['otp_id']);
            throw new ProviderException('Too many attempts');
        }

        if (!hash_equals((string)$row['code_hash'], hash('sha256', $code))) {
            $this->otp->incrementAttempts((int)$row['otp_id']);
            throw new ProviderException('Invalid code');
        }

        // Atomic consume — if another concurrent request beat us to it, refuse.
        if (!$this->otp->consume((int)$row['otp_id'])) {
            throw new ProviderException('Code already used');
        }

        return new ProviderProfile([
            'provider'         => 'sms_otp',
            'provider_user_id' => $normalized,
            'email'            => null,
            'email_verified'   => false,
            'display_name'     => null,
            'first_name'       => null,
            'last_name'        => null,
            'avatar_url'       => null,
            'raw'              => ['phone' => $normalized],
        ]);
    }

    private function buildClient(): TurboSms
    {
        $token  = (string)$this->config->get('module_oc_kit_easy_login_sms_otp_token');
        $sender = (string)$this->config->get('module_oc_kit_easy_login_sms_otp_sender');
        return new TurboSms($token, $sender);
    }

    private function generateCode(): string
    {
        $len  = $this->getCodeLength();
        $max  = (int)str_repeat('9', $len);
        $code = (string)random_int(0, $max);
        return str_pad($code, $len, '0', STR_PAD_LEFT);
    }
}
