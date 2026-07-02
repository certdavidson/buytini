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
 * Sends HTTP POST notifications to a configured external endpoint when
 * SEO-relevant changes happen — typical use case is CDN cache purge or
 * Slack/email notifications.
 *
 * Config keys:
 *   module_oc_kit_seo_core_webhook_url      Target URL (https only enforced for security)
 *   module_oc_kit_seo_core_webhook_secret   Optional shared secret (sent as X-SCF-Signature: HMAC-SHA256)
 *   module_oc_kit_seo_core_webhook_events   JSON array of event names to subscribe to
 *
 * Supported events: url_changed, redirect_added, audit_run, mass_regenerated.
 *
 * Best-effort fire-and-forget: a failed POST is logged via error_log but
 * does NOT block the calling controller.
 */
class WebhookDispatcher
{
    public const EV_URL_CHANGED      = 'url_changed';
    public const EV_REDIRECT_ADDED   = 'redirect_added';
    public const EV_AUDIT_RUN        = 'audit_run';
    public const EV_MASS_REGENERATED = 'mass_regenerated';

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Dispatch an event with payload. Returns true on HTTP 2xx, false otherwise.
     */
    public function dispatch(string $event, array $payload): bool
    {
        $url = trim((string)$this->config->get('module_oc_kit_seo_core_webhook_url'));
        if ($url === '' || !preg_match('#^https?://#i', $url)) return false;

        // Subscribed events
        $events = (array)json_decode((string)$this->config->get('module_oc_kit_seo_core_webhook_events'), true);
        if ($events && !in_array($event, $events, true)) return false;

        $body = json_encode([
            'event'     => $event,
            'timestamp' => date('c'),
            'payload'   => $payload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $headers = ['Content-Type: application/json'];

        $secret = (string)$this->config->get('module_oc_kit_seo_core_webhook_secret');
        if ($secret !== '') {
            $headers[] = 'X-SCF-Signature: sha256=' . hash_hmac('sha256', $body, $secret);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            error_log('[SCF webhook] failed (' . $code . ') ' . $event . ': ' . $err);
            return false;
        }
        return true;
    }
}
