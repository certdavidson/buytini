<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * Реєстр телефонних масок per country (ISO-2). Використовується для:
 *   - Авто-маски на frontend tel-input коли admin вибрав country у polaty
 *   - Phone normalization у server-side (UA, PL, etc) — конвертація 0XXX → 380XXX
 *
 * Schema: { iso2 → { mask: string, prefix: string, regex: string } }
 *   - mask: IMask pattern (`+38 (000) 000-00-00`)
 *   - prefix: digits-only country code для normalization (`38`, `48`)
 *   - regex: повна-форма перевірка (`^380\d{9}$`)
 *
 * Нові країни додаються або сюди (default-список), або через filter-event:
 *   `okec/phone_masks/extend` для shop-specific overrides без правок core.
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

final class PhoneMasks
{
    /** Default-реєстр (ISO-2 → mask config). Розширюється через extend(). */
    private static array $extras = [];

    public static function defaults(): array
    {
        return [
            'UA' => ['prefix' => '38',  'mask' => '+38 (000) 000-00-00',     'regex' => '^380\d{9}$'],
            'PL' => ['prefix' => '48',  'mask' => '+48 000 000 000',         'regex' => '^48\d{9}$'],
            'CZ' => ['prefix' => '420', 'mask' => '+420 000 000 000',        'regex' => '^420\d{9}$'],
            'SK' => ['prefix' => '421', 'mask' => '+421 000 000 000',        'regex' => '^421\d{9}$'],
            'DE' => ['prefix' => '49',  'mask' => '+49 0000 000-0000',       'regex' => '^49\d{9,11}$'],
            'GB' => ['prefix' => '44',  'mask' => '+44 0000 000000',         'regex' => '^44\d{10}$'],
            'US' => ['prefix' => '1',   'mask' => '+1 (000) 000-0000',       'regex' => '^1\d{10}$'],
            'CA' => ['prefix' => '1',   'mask' => '+1 (000) 000-0000',       'regex' => '^1\d{10}$'],
            'IT' => ['prefix' => '39',  'mask' => '+39 000 000 0000',        'regex' => '^39\d{9,10}$'],
            'ES' => ['prefix' => '34',  'mask' => '+34 000 000 000',         'regex' => '^34\d{9}$'],
            'FR' => ['prefix' => '33',  'mask' => '+33 0 00 00 00 00',       'regex' => '^33\d{9}$'],
            'RU' => ['prefix' => '7',   'mask' => '+7 (000) 000-00-00',      'regex' => '^7\d{10}$'],
            'BY' => ['prefix' => '375', 'mask' => '+375 (00) 000-00-00',     'regex' => '^375\d{9}$'],
            'KZ' => ['prefix' => '7',   'mask' => '+7 (000) 000-00-00',      'regex' => '^7\d{10}$'],
            'GE' => ['prefix' => '995', 'mask' => '+995 000 000 000',        'regex' => '^995\d{9}$'],
            'MD' => ['prefix' => '373', 'mask' => '+373 0000 0000',          'regex' => '^373\d{8}$'],
            'TR' => ['prefix' => '90',  'mask' => '+90 (000) 000 00 00',     'regex' => '^90\d{10}$'],
            'IL' => ['prefix' => '972', 'mask' => '+972 00 000 0000',        'regex' => '^972\d{9}$'],
        ];
    }

    /** Глобальний registry: defaults + extras (shop-specific overrides). */
    public static function all(): array
    {
        return array_merge(self::defaults(), self::$extras);
    }

    /** Додає / перевизначає мacku для країни. Викликається ззовні (наприклад з extension/module/* install hook). */
    public static function extend(string $iso2, array $config): void
    {
        $iso2 = strtoupper($iso2);
        if (empty($config['prefix']) || empty($config['mask'])) return;
        self::$extras[$iso2] = [
            'prefix' => (string)$config['prefix'],
            'mask'   => (string)$config['mask'],
            'regex'  => (string)($config['regex'] ?? ''),
        ];
    }

    public static function forCountry(string $iso2): ?array
    {
        $iso2 = strtoupper($iso2);
        $all  = self::all();
        return $all[$iso2] ?? null;
    }

    /**
     * Нормалізує phone-string у E.164-like (digits з international префіксом).
     * Якщо countryIso2 дано — застосовує ось такі правила:
     *   - leading `+` прибирається
     *   - якщо вже починається з prefix → лишається
     *   - якщо leading `0` і національна довжина → додає prefix
     *   - інакше — як є (digits-only)
     */
    public static function normalize(string $raw, string $countryIso2 = ''): string
    {
        $digits = preg_replace('~[^\d+]~', '', $raw);
        $digits = ltrim((string)$digits, '+');

        $cfg = $countryIso2 !== '' ? self::forCountry($countryIso2) : null;
        if (!$cfg) {
            // Backward-compat: UA-special як було у legacy normalizePhone
            if (preg_match('~^0\d{9}$~', $digits)) return '38' . $digits;
            return (string)$digits;
        }

        $prefix = (string)$cfg['prefix'];
        if ($prefix !== '' && strpos($digits, $prefix) === 0) return $digits; // вже з prefix

        // 0XXXXXXXXX → prefixXXXXXXXXX (типово для UA-формату)
        if (preg_match('~^0\d+$~', $digits)) {
            return $prefix . substr($digits, 1);
        }
        return $digits;
    }
}
