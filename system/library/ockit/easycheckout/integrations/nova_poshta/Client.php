<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Integrations\NovaPoshta
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Integrations\NovaPoshta;

/**
 * HTTP-клієнт Нової Пошти (API v2.0, JSON).
 * Endpoint: https://api.novaposhta.ua/v2.0/json/
 */
final class Client
{
    private const ENDPOINT = 'https://api.novaposhta.ua/v2.0/json/';
    private const TIMEOUT  = 25;

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Виклик методу API. Повертає масив `data` з відповіді.
     * @throws \RuntimeException на помилку HTTP/API.
     */
    public function call(string $modelName, string $calledMethod, array $methodProperties = []): array
    {
        $payload = json_encode([
            'apiKey'           => $this->apiKey,
            'modelName'        => $modelName,
            'calledMethod'     => $calledMethod,
            'methodProperties' => (object)$methodProperties,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('NP cURL error: ' . $err);
        }
        $resp = json_decode((string)$body, true);
        if (!is_array($resp)) {
            throw new \RuntimeException('NP invalid JSON response');
        }
        if (empty($resp['success'])) {
            $msg = is_array($resp['errors'] ?? null) ? implode('; ', $resp['errors']) : 'Unknown error';
            throw new \RuntimeException('NP API error: ' . $msg);
        }
        return is_array($resp['data'] ?? null) ? $resp['data'] : [];
    }

    /** Перевірка з'єднання — найдешевший виклик API. */
    public function ping(): bool
    {
        $data = $this->call('Common', 'getTimeIntervals');
        return !empty($data);
    }

    /** Усі населені пункти України (~30k). Повертає сирі рядки. */
    public function getCities(int $page = 1, int $limit = 500): array
    {
        return $this->call('Address', 'getCities', ['Page' => $page, 'Limit' => $limit]);
    }

    /**
     * Відділення/поштомати. Якщо $cityRef порожній — всі (~50k).
     * TypeOfWarehouseRef: можна фільтрувати за типом, але ефективніше тягнути все
     * і фільтрувати локально під налаштування `warehouse_types`.
     */
    public function getWarehouses(string $cityRef = '', int $page = 1, int $limit = 500): array
    {
        $props = ['Page' => $page, 'Limit' => $limit];
        if ($cityRef !== '') $props['CityRef'] = $cityRef;
        return $this->call('Address', 'getWarehouses', $props);
    }
}
