<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * Catalog-side помічник для роботи зі збереженими custom-полями замовлення
 * (`oc_kit_easycheckout_order_fields`). Використовується OCMOD-патчами
 * (account/order_info, etc) щоб не дублювати DB-логіку у XML.
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ModelExtensionEasycheckoutOrder extends Model
{
    /**
     * Повертає кастомні oc-kit поля для конкретного замовлення з людською
     * назвою (поточна мова, fallback — будь-яка непорожня), типом та value.
     * JSON-array значення розпаковуються у CSV-рядок.
     *
     * @return array<int, array{code: string, value: string, name: string, type: string}>
     */
    public function getCustomFields(int $orderId): array
    {
        if (!$orderId) return [];

        $rows = $this->db->query("SELECT v.`field_code`, v.`value`, f.`type`, d.`name`
            FROM `" . DB_PREFIX . "kit_easycheckout_order_fields` v
            LEFT JOIN `" . DB_PREFIX . "kit_easycheckout_fields`             f ON f.`code`     = v.`field_code`
            LEFT JOIN `" . DB_PREFIX . "kit_easycheckout_fields_description` d ON d.`field_id` = f.`field_id`
                                                                              AND d.`language_id` = " . (int)$this->config->get('config_language_id') . "
            WHERE v.`order_id` = " . (int)$orderId . "
            ORDER BY v.`id`")->rows;

        $out = [];
        foreach ($rows as $r) {
            $val = (string)$r['value'];
            // Розпакувати JSON-array значення (multi-select / checkbox group)
            if ($val !== '' && ($val[0] === '[' || $val[0] === '{')) {
                $dec = json_decode($val, true);
                if (is_array($dec)) {
                    $val = implode(', ', array_map('strval', $dec));
                }
            }
            $out[] = [
                'code'  => (string)$r['field_code'],
                'value' => $val,
                'name'  => (string)($r['name'] ?: $r['field_code']),
                'type'  => (string)($r['type'] ?: ''),
            ];
        }
        return $out;
    }
}
