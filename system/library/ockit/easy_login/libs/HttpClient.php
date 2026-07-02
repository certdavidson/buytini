<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

use OcKit\EasyLogin\Exceptions\ProviderException;

class HttpClient
{
    private int $timeout;

    public function __construct(int $timeout = 10)
    {
        $this->timeout = $timeout;
    }

    public function getJson(string $url, array $headers = []): array
    {
        return $this->requestJson('GET', $url, null, $headers);
    }

    public function postFormJson(string $url, array $form, array $headers = []): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return $this->requestJson('POST', $url, http_build_query($form), $headers);
    }

    private function requestJson(string $method, string $url, ?string $body, array $headers): array
    {
        // One retry on transient failure (cURL error / 5xx) — covers DNS jitter,
        // TLS handshake hiccups, provider-side intermittent 5xx. 4xx is fatal
        // (auth misconfig) and skips the retry.
        $attempts = 0;
        $lastErr  = '';
        $lastCode = 0;
        $lastResp = '';

        while ($attempts < 2) {
            $attempts++;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $response = curl_exec($ch);
            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err      = curl_error($ch);
            curl_close($ch);

            $lastErr  = (string)$err;
            $lastCode = (int)$code;
            $lastResp = (string)$response;

            if ($response !== false && $code >= 200 && $code < 300) {
                $decoded = json_decode((string)$response, true);
                if (!is_array($decoded)) {
                    throw new ProviderException('Invalid JSON response');
                }
                return $decoded;
            }

            // 4xx is fatal — don't retry on auth errors or bad inputs.
            if ($response !== false && $code >= 400 && $code < 500) {
                break;
            }

            if ($attempts < 2) {
                usleep(150000); // 150ms backoff before single retry
            }
        }

        if ($lastResp === '' && $lastErr !== '') {
            throw new ProviderException('HTTP request failed: ' . $lastErr);
        }
        throw new ProviderException('HTTP ' . $lastCode . ': ' . substr($lastResp, 0, 500));
    }
}
