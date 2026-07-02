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

class EmailMagicProvider extends AbstractAuthProvider
{
    private OtpRepository $otp;
    private $registry;

    public function __construct($config, OtpRepository $otp, $registry)
    {
        parent::__construct($config);
        $this->otp      = $otp;
        $this->registry = $registry;
    }

    public function name(): string { return 'email_magic'; }

    protected function checkEnabled(): bool
    {
        return (bool)$this->config->get('module_oc_kit_easy_login_email_magic_enabled');
    }

    public function getTtlSeconds(): int
    {
        return (int)($this->config->get('module_oc_kit_easy_login_email_magic_token_ttl_minutes') ?: 15) * 60;
    }

    /**
     * Generate a one-time token, store hash, and send a login link to email.
     * Returns the raw token (caller is responsible for embedding into URL).
     */
    public function issueToken(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ProviderException('Invalid email');
        }

        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $this->otp->create('email', $email, $hash, $this->getTtlSeconds());
        return $token;
    }

    /**
     * Send the magic link email. Uses OpenCart's Mail class.
     */
    public function sendMagicLink(string $email, string $magicUrl, string $langCode): void
    {
        $config  = $this->config;
        $subject = (string)($config->get('module_oc_kit_easy_login_email_magic_subject_' . $langCode)
                          ?: $config->get('module_oc_kit_easy_login_email_magic_subject_en-gb')
                          ?: 'Your sign-in link');
        $template = (string)($config->get('module_oc_kit_easy_login_email_magic_template_' . $langCode)
                          ?: $config->get('module_oc_kit_easy_login_email_magic_template_en-gb')
                          ?: '<p>Click to sign in: <a href="{magic_url}">{magic_url}</a></p><p>This link expires in {ttl_minutes} minutes.</p>');

        $body = strtr($template, [
            '{magic_url}'    => $magicUrl,
            '{ttl_minutes}'  => (string)((int)($config->get('module_oc_kit_easy_login_email_magic_token_ttl_minutes') ?: 15)),
            '{store_name}'   => (string)$config->get('config_name'),
        ]);

        $mail = new \Mail($config->get('config_mail_engine'));
        $mail->parameter = $config->get('config_mail_parameter');
        $mail->smtp_hostname = $config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode((string)$config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $config->get('config_mail_smtp_timeout');

        // CRLF strip — OC Mail does not sanitize subject/sender headers, so
        // any admin-saved value containing \r\n could inject extra headers
        // (Bcc:, etc.). Hardening against misconfigured admin input.
        $sender  = self::stripHeaderBreaks(html_entity_decode((string)($config->get('module_oc_kit_easy_login_email_magic_from_name') ?: $config->get('config_name')), ENT_QUOTES, 'UTF-8'));
        $subject = self::stripHeaderBreaks(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));

        $mail->setTo($email);
        $mail->setFrom($config->get('config_email'));
        $mail->setSender($sender);
        $mail->setSubject($subject);
        $mail->setHtml($body);
        $mail->send();
    }

    private static function stripHeaderBreaks(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', $value));
    }

    /**
     * Verify token and return ProviderProfile (consumes OTP on success).
     */
    public function verifyToken(string $token): ProviderProfile
    {
        $hash = hash('sha256', $token);
        $row  = $this->otp->findByHash('email', $hash);
        if (!$row) {
            throw new ProviderException('Invalid or expired magic link');
        }
        // Atomic single-use: protects against double-click / parallel-tab
        // replay of the same magic-link URL.
        if (!$this->otp->consume((int)$row['otp_id'])) {
            throw new ProviderException('Magic link already used');
        }

        $email = (string)$row['recipient'];
        return new ProviderProfile([
            'provider'         => 'email_magic',
            'provider_user_id' => $email,
            'email'            => $email,
            'email_verified'   => true, // user clicked link in their inbox — proves email control
            'display_name'     => null,
            'first_name'       => null,
            'last_name'        => null,
            'avatar_url'       => null,
            'raw'              => [],
        ]);
    }
}
