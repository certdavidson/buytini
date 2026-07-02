<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Integrations\Ukrposhta
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Integrations\Ukrposhta;

/**
 * HTTP-клієнт Укрпошти (Public address-classifier API).
 * Endpoint: https://www.ukrposhta.ua/address-classifier-ws/get_*
 * Авторизація: Bearer-токен з договору.
 */
final class Client
{
    private const ENDPOINT = 'https://www.ukrposhta.ua/address-classifier-ws';
    private const TIMEOUT  = 25;

    private string $bearer;

    public function __construct(string $bearer)
    {
        $this->bearer = $bearer;
    }

    public function get(string $path, array $query = []): array
    {
        $url = self::ENDPOINT . $path;
        if ($query) $url .= '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->bearer,
                'Accept: application/json',
            ],
        ]);
        $body  = curl_exec($ch);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($errno) throw new \RuntimeException('Ukrposhta cURL error: ' . $err);
        if ($code >= 400) throw new \RuntimeException('Ukrposhta HTTP ' . $code . ': ' . substr((string)$body, 0, 200));

        $resp = json_decode((string)$body, true);
        if (!is_array($resp)) throw new \RuntimeException('Ukrposhta invalid JSON');

        // Public API повертає {"Entries": {"Entry": [...]}, ...} — нормалізуємо
        if (isset($resp['Entries']['Entry']) && is_array($resp['Entries']['Entry'])) {
            return $resp['Entries']['Entry'];
        }
        return $resp;
    }

    public function ping(): bool
    {
        $rows = $this->get('/get_regions_by_region_ua', ['region_name' => 'Київ']);
        return is_array($rows);
    }

    public function getRegions(): array        { return $this->get('/get_regions_by_region_ua'); }
    public function getDistricts(int $regionId): array { return $this->get('/get_districts_by_region_id_and_district_ua', ['region_id' => $regionId]); }
    public function getCities(int $districtId): array  { return $this->get('/get_city_by_district_id_and_city_ua',     ['district_id' => $districtId]); }
    public function getPostOffices(int $cityId): array { return $this->get('/get_postoffices_by_city_id',              ['city_id' => $cityId]); }
}
