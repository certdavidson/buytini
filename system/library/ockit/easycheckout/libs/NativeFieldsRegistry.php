<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * Реєстр "стандартних" полів OpenCart, які користувач може розміщати в блоках
 * checkout-сторінки (поряд із кастомними полями з нашого реєстру).
 *
 * Ідея: native-поля мають **негативні** field_id (від -100 до -1), кастомні поля
 * з `oc_kit_easycheckout_fields` — позитивні (>=1). Це дозволяє в `block.settings.fields`
 * зберігати уніфікований список без додаткових прапорців.
 *
 * `code` — внутрішня назва (для рендеру і dedup).
 * `oc_field` — ім'я в OC-моделях (оригінальне). Для customer.firstname це 'firstname';
 *              для address.firstname — теж 'firstname', але `code` ми робимо
 *              `address_firstname` щоб уникнути колізії.
 * `lang_key` — ключ у мовному файлі `checkout/checkout` для отримання людської назви.
 */
final class NativeFieldsRegistry
{
    /**
     * @return array<int, array{
     *   field_id: int,
     *   code: string,
     *   type: string,
     *   belongs_to: string,
     *   oc_field: string,
     *   lang_key: string,
     *   native: bool
     * }>
     */
    public static function listAll(): array
    {
        return [
            // ── Customer entity ──────────────────────────────────────────────
            self::row(-1,  'email',         'text',     'customer', 'email',         'entry_email'),
            self::row(-2,  'firstname',     'text',     'customer', 'firstname',     'entry_firstname'),
            self::row(-3,  'lastname',      'text',     'customer', 'lastname',      'entry_lastname'),
            self::row(-4,  'telephone',     'text',     'customer', 'telephone',     'entry_telephone'),
            self::row(-5,  'fax',           'text',     'customer', 'fax',           'entry_fax'),
            self::row(-6,  'company',       'text',     'customer', 'company',       'entry_company'),
            self::row(-7,  'password',      'text',     'customer', 'password',      'entry_password'),
            self::row(-8,  'confirm',       'text',     'customer', 'confirm',       'entry_confirm'),
            self::row(-9,  'newsletter',    'radio',    'customer', 'newsletter',    'entry_newsletter'),
            self::row(-10, 'register',      'radio',    'customer', 'register',      'text_account_register'),
            self::row(-11, 'agree',         'checkbox', 'customer', 'agree',         'text_agree'),

            // ── Address entity (prefix `address_*` де є колізія з customer) ──
            self::row(-21, 'address_firstname', 'text',   'address', 'firstname',   'entry_firstname'),
            self::row(-22, 'address_lastname',  'text',   'address', 'lastname',    'entry_lastname'),
            self::row(-23, 'address_company',   'text',   'address', 'company',     'entry_company'),
            self::row(-24, 'address_1',         'text',   'address', 'address_1',   'entry_address_1'),
            self::row(-25, 'address_2',         'text',   'address', 'address_2',   'entry_address_2'),
            self::row(-26, 'city',              'text',   'address', 'city',        'entry_city'),
            self::row(-27, 'postcode',          'text',   'address', 'postcode',    'entry_postcode'),
            self::row(-28, 'country',           'select', 'address', 'country_id',  'entry_country'),
            self::row(-29, 'zone',              'select', 'address', 'zone_id',     'entry_zone'),

            // Дублюючі полі-обгортки (comment/shipping_method/payment_method) НЕ додаємо —
            // для них є окремі типи блоків (Comment / Shipping / Payment), які роблять те саме.
        ];
    }

    /** Знайти native-поле за field_id (негативним). Повертає null якщо не знайдено. */
    public static function findById(int $fieldId): ?array
    {
        foreach (self::listAll() as $f) {
            if ($f['field_id'] === $fieldId) return $f;
        }
        return null;
    }

    /** Чи є field_id native-полем (тобто < 0). */
    public static function isNativeId(int $fieldId): bool
    {
        return $fieldId < 0;
    }

    /**
     * Зарезервовані коди, які НЕ можна використати для кастомного поля —
     * це native `code` ТА `oc_field` (катало-сайд формує POST-ключ `okec[code]`
     * для кастомних і `okec[oc_field]` для native; збіг = колізія значень
     * у формі, в умовах показу та при збереженні замовлення).
     *
     * @return string[] унікальний список зарезервованих кодів
     */
    public static function reservedCodes(): array
    {
        $reserved = [];
        foreach (self::listAll() as $f) {
            $reserved[$f['code']]     = true;
            $reserved[$f['oc_field']] = true;
        }
        return array_keys($reserved);
    }

    private static function row(int $id, string $code, string $type, string $belongsTo, string $ocField, string $langKey): array
    {
        return [
            'field_id'   => $id,
            'code'       => $code,
            'type'       => $type,
            'belongs_to' => $belongsTo,
            'oc_field'   => $ocField,
            'lang_key'   => $langKey,
            'native'     => true,
        ];
    }
}
