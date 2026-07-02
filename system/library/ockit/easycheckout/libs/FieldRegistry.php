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
 * Реєстр типів полів. Метадані використовуються адмін-формою для:
 *  - відображення select-у "Тип поля";
 *  - визначення які параметри показати в модалці (mask, options, intl, etc);
 *  - валідації при збереженні.
 *
 * Стадія 2.1: базові типи. Розширені (tel_intl, autocomplete_np, consent,
 * computed_hidden, country_zone_cascade) додамо в 2.2.
 */
final class FieldRegistry
{
    /**
     * Метадані типів полів.
     *
     * @var array<string, array{
     *   label_key: string,
     *   has_mask: bool,
     *   has_options: bool,
     *   has_default: bool,
     *   has_placeholder: bool,
     *   has_tooltip: bool,
     *   default_belongs_to: string,
     *   stage: int
     * }>
     */
    private const TYPES = [
        // ─── Базові типи (Stage 2.1) ─────────────────────────────────────────
        'text' => [
            'label_key' => 'field_type_text',
            'has_mask' => true,  'has_options' => false, 'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'textarea' => [
            'label_key' => 'field_type_textarea',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'select' => [
            'label_key' => 'field_type_select',
            'has_mask' => false, 'has_options' => true,  'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'radio' => [
            'label_key' => 'field_type_radio',
            'has_mask' => false, 'has_options' => true,  'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'checkbox' => [
            'label_key' => 'field_type_checkbox',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'date' => [
            'label_key' => 'field_type_date',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'hidden' => [
            'label_key' => 'field_type_hidden',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => false,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],
        'html' => [
            'label_key' => 'field_type_html',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => false, 'has_tooltip' => false,
            'default_belongs_to' => 'order', 'stage' => 21,
        ],

        // ─── Розширені типи (Stage 2.2 — резерв) ─────────────────────────────
        'segmented' => [
            'label_key' => 'field_type_segmented',
            'has_mask' => false, 'has_options' => true,  'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
        'consent' => [
            'label_key' => 'field_type_consent',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
        'tel_intl' => [
            'label_key' => 'field_type_tel_intl',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'customer', 'stage' => 22,
        ],
        'autocomplete_np' => [
            'label_key' => 'field_type_autocomplete_np',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'autocomplete_ukrposhta' => [
            'label_key' => 'field_type_autocomplete_ukrposhta',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'country' => [
            'label_key' => 'field_type_country',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'zone' => [
            'label_key' => 'field_type_zone',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'city' => [
            'label_key' => 'field_type_city',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'time' => [
            'label_key' => 'field_type_time',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => true, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
        'computed_hidden' => [
            'label_key' => 'field_type_computed_hidden',
            'has_mask' => false, 'has_options' => false, 'has_default' => true,
            'has_placeholder' => false, 'has_tooltip' => false,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
        'group' => [
            'label_key' => 'field_type_group',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => false, 'has_tooltip' => false,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
        'address_select' => [
            'label_key' => 'field_type_address_select',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'address', 'stage' => 22,
        ],
        'file' => [
            'label_key' => 'field_type_file',
            'has_mask' => false, 'has_options' => false, 'has_default' => false,
            'has_placeholder' => false, 'has_tooltip' => true,
            'default_belongs_to' => 'order', 'stage' => 22,
        ],
    ];

    /**
     * Належність поля.
     */
    public const BELONGS_TO = ['order', 'customer', 'address'];

    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function getMeta(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    /**
     * @param int $maxStage Повертати лише типи зі стадії <= цієї.
     * @return array<string, array> Ключ — code типу.
     */
    public static function listTypes(int $maxStage = 99): array
    {
        $result = [];
        foreach (self::TYPES as $code => $meta) {
            if ($meta['stage'] <= $maxStage) {
                $result[$code] = $meta;
            }
        }
        return $result;
    }

    public static function isValidBelongsTo(string $value): bool
    {
        return in_array($value, self::BELONGS_TO, true);
    }
}
