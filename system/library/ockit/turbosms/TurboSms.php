<?php
/**
 * TurboSMS PHP Library
 *
 * @package   OcKit\TurboSms
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Комерційна ліцензія — див. LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\TurboSms;

use OcKit\TurboSms\Exceptions\TurboSmsException;
use OcKit\TurboSms\Exceptions\TurboSmsNetworkException;

/**
 * Клієнт TurboSMS HTTP API v1.
 *
 * Документація API: https://turbosms.ua/api.html
 *
 * Підтримує:
 *  - відправка SMS (одному або кільком отримувачам)
 *  - відправка Viber-повідомлення (з fallback на SMS)
 *  - перевірка балансу акаунта
 *  - отримання статусу доставки
 *
 * Приклад:
 *   $sms = new TurboSms('YOUR_TOKEN', 'Sender');
 *   $responses = $sms->sendSms('380671234567', 'Ваш відгук: https://...');
 *   $balance   = $sms->getBalance();
 */
class TurboSms
{
    const API_BASE = 'https://api.turbosms.ua';

    /** @var string  Bearer-токен з особистого кабінету TurboSMS */
    private $token;

    /** @var string  Ім'я відправника за замовчуванням (SMS) */
    private $smsSender;

    /** @var string  Ім'я відправника за замовчуванням (Viber) */
    private $viberSender;

    /** @var int  Таймаут cURL у секундах */
    private $timeout;

    /**
     * @param string $token        Bearer-токен (API key)
     * @param string $smsSender    Ім'я відправника для SMS (до 11 лат. символів або 16 цифр)
     * @param string $viberSender  Ім'я відправника для Viber (зареєстроване в TurboSMS)
     * @param int    $timeout      Таймаут HTTP-запиту у секундах
     */
    public function __construct(
        string $token,
        string $smsSender   = '',
        string $viberSender = '',
        int    $timeout     = 15
    ) {
        $this->token       = $token;
        $this->smsSender   = $smsSender;
        $this->viberSender = $viberSender;
        $this->timeout     = $timeout;
    }

    // ─── Відправка ────────────────────────────────────────────────────────────

    /**
     * Відправляє SMS одному або кільком отримувачам.
     *
     * @param string|string[] $recipients  Номер(и) телефону (підтримує формати: 380..., 80..., 0...)
     * @param string          $text        Текст повідомлення
     * @param string|null     $sender      Перевизначення відправника
     * @return TurboSmsResponse[]          Масив відповідей — по одному на кожен номер
     *
     * @throws TurboSmsException        Якщо API повернуло помилку
     * @throws TurboSmsNetworkException Якщо мережева помилка або не вдалося підключитись
     */
    public function sendSms($recipients, string $text, ?string $sender = null): array
    {
        $recipients = $this->normalizeRecipients($recipients);

        $payload = [
            'recipients' => $recipients,
            'sms'        => [
                'sender' => $sender ?? $this->smsSender,
                'text'   => $text,
            ],
        ];

        return $this->doSend($payload);
    }

    /**
     * Відправляє Viber-повідомлення одному або кільком отримувачам.
     *
     * Якщо Viber недоступний у отримувача — TurboSMS може зробити fallback на SMS,
     * якщо $smsFallbackText заповнений.
     *
     * @param string|string[] $recipients
     * @param string          $text             Текст Viber-повідомлення
     * @param string          $imageUrl         URL картинки (необов'язково)
     * @param string          $buttonText       Текст кнопки (необов'язково)
     * @param string          $buttonUrl        URL кнопки (необов'язково)
     * @param string|null     $smsFallbackText  Текст SMS при недоступності Viber; null — без fallback
     * @param string|null     $sender           Перевизначення відправника Viber
     * @return TurboSmsResponse[]
     *
     * @throws TurboSmsException
     * @throws TurboSmsNetworkException
     */
    public function sendViber(
        $recipients,
        string  $text,
        string  $imageUrl        = '',
        string  $buttonText      = '',
        string  $buttonUrl       = '',
        ?string $smsFallbackText = null,
        ?string $sender          = null
    ): array {
        $recipients = $this->normalizeRecipients($recipients);

        $viber = [
            'sender' => $sender ?? $this->viberSender,
            'text'   => $text,
        ];
        if ($imageUrl)   { $viber['image']       = $imageUrl;   }
        if ($buttonText) { $viber['caption']      = $buttonText; }
        if ($buttonUrl)  { $viber['action']       = $buttonUrl;  }

        $payload = [
            'recipients' => $recipients,
            'viber'      => $viber,
        ];

        // Fallback на SMS
        if ($smsFallbackText !== null) {
            $payload['sms'] = [
                'sender' => $this->smsSender,
                'text'   => $smsFallbackText,
            ];
        }

        return $this->doSend($payload);
    }

    // ─── Баланс ───────────────────────────────────────────────────────────────

    /**
     * Повертає поточний баланс акаунту TurboSMS.
     *
     * @return float  Баланс у гривнях
     *
     * @throws TurboSmsException
     * @throws TurboSmsNetworkException
     */
    public function getBalance(): float
    {
        $data = $this->request('GET', '/user/balance.json', []);
        return (float)($data['response_result'] ?? 0.0);
    }

    // ─── Статус доставки ──────────────────────────────────────────────────────

    /**
     * Отримує статус доставки за message_id.
     *
     * @param string|string[] $messageIds  Один або кілька ID повідомлень
     * @return array<string, string>       message_id => status
     *
     * @throws TurboSmsException
     * @throws TurboSmsNetworkException
     */
    public function getStatus($messageIds): array
    {
        if (is_string($messageIds)) {
            $messageIds = [$messageIds];
        }

        $data    = $this->request('POST', '/message/get-status.json', ['message_id' => $messageIds]);
        $results = $data['response_result'] ?? [];

        $statuses = [];
        foreach ($results as $row) {
            if (isset($row['message_id'], $row['status'])) {
                $statuses[(string)$row['message_id']] = (string)$row['status'];
            }
        }

        return $statuses;
    }

    // ─── Утиліти ──────────────────────────────────────────────────────────────

    /**
     * Нормалізує номер телефону до формату 380XXXXXXXXX.
     * Підтримує: +380..., 380..., 80..., 0...
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 10 && $phone[0] === '0') {
            // 0XXXXXXXXX → 380XXXXXXXXX
            return '38' . $phone;
        }

        if (strlen($phone) === 11 && $phone[0] === '8') {
            // 80XXXXXXXXX → 380XXXXXXXXX
            return '3' . $phone;
        }

        return $phone;
    }

    // ─── Внутрішні ───────────────────────────────────────────────────────────

    /**
     * @param string|string[] $recipients
     * @return string[]
     */
    private function normalizeRecipients($recipients): array
    {
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        return array_values(array_map([$this, 'normalizePhone'], $recipients));
    }

    /**
     * Відправляє повідомлення і повертає масив TurboSmsResponse.
     *
     * @return TurboSmsResponse[]
     */
    private function doSend(array $payload): array
    {
        $data    = $this->request('POST', '/message/send.json', $payload);
        $results = $data['response_result'] ?? [];

        $responses = [];
        foreach ($results as $row) {
            $responses[] = new TurboSmsResponse($row);
        }

        return $responses;
    }

    /**
     * Виконує HTTP-запит до TurboSMS API.
     *
     * @throws TurboSmsNetworkException  При мережевих помилках (cURL)
     * @throws TurboSmsException         При помилці API (response_code != 0)
     */
    private function request(string $method, string $path, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new TurboSmsNetworkException('cURL extension is not available');
        }

        $url     = self::API_BASE . $path;
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $result === false) {
            throw new TurboSmsNetworkException(
                'TurboSMS connection error: ' . $error,
                $errno
            );
        }

        $data = json_decode((string)$result, true);

        if (!is_array($data)) {
            throw new TurboSmsException(
                'TurboSMS invalid response (HTTP ' . $code . '): ' . mb_substr($result, 0, 200)
            );
        }

        $responseCode   = $data['response_code']   ?? -1;
        $responseStatus = (string)($data['response_status'] ?? '');

        // TurboSMS treats SUCCESS_* statuses as success even when response_code is non-zero
        $isSuccess = ($responseCode === 0) || (strpos($responseStatus, 'SUCCESS_') === 0);

        if (!$isSuccess) {
            throw new TurboSmsException(
                'TurboSMS API error: ' . ($responseStatus ?: 'Unknown'),
                (int)$responseCode
            );
        }

        return $data;
    }
}
