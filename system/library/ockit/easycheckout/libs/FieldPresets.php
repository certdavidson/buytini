<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * Системні presets — набір готових полів для типових сценаріїв (Nova Poshta,
 * UkrPoshta, B2B-information). Admin може у Fields-секції клацнути "Apply preset"
 * і ці поля створяться у реєстрі (oc_kit_easycheckout_fields + descriptions).
 *
 * Format кожного preset:
 *   { code: string, label: {lang_code: string}, fields: [ {field-data, descriptions} ] }
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

final class FieldPresets
{
    /**
     * @return array<int, array{
     *   code: string,
     *   label: array<string,string>,
     *   description: array<string,string>,
     *   fields: array<int, array{code:string,type:string,belongs_to:string,params:array,descriptions:array<string,array{name:string,placeholder:string,tooltip:string}>}>
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'code'  => 'nova_poshta',
                'label' => [
                    'uk-ua' => 'Нова Пошта (UA)',
                    'ru-ru' => 'Новая Почта (UA)',
                    'en-gb' => 'Nova Poshta (UA)',
                ],
                'description' => [
                    'uk-ua' => 'Місто + відділення для доставки Новою Поштою. 2 поля з autocomplete.',
                    'ru-ru' => 'Город + отделение для доставки Новой Почтой. 2 поля с autocomplete.',
                    'en-gb' => 'City + warehouse for Nova Poshta delivery. 2 fields with autocomplete.',
                ],
                'fields' => [
                    [
                        'code' => 'np_city', 'type' => 'autocomplete_np', 'belongs_to' => 'address',
                        'params' => ['autocomplete_np' => ['scope' => 'city']],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'Місто', 'placeholder' => 'Почніть вводити місто...', 'tooltip' => ''],
                            'ru-ru' => ['name' => 'Город', 'placeholder' => 'Начните вводить город...', 'tooltip' => ''],
                            'en-gb' => ['name' => 'City', 'placeholder' => 'Start typing city...', 'tooltip' => ''],
                        ],
                    ],
                    [
                        'code' => 'np_warehouse', 'type' => 'autocomplete_np', 'belongs_to' => 'address',
                        'params' => ['autocomplete_np' => ['scope' => 'warehouse']],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'Відділення', 'placeholder' => 'Виберіть відділення', 'tooltip' => ''],
                            'ru-ru' => ['name' => 'Отделение', 'placeholder' => 'Выберите отделение', 'tooltip' => ''],
                            'en-gb' => ['name' => 'Warehouse', 'placeholder' => 'Select warehouse', 'tooltip' => ''],
                        ],
                    ],
                ],
            ],
            [
                'code'  => 'b2b_company',
                'label' => [
                    'uk-ua' => 'B2B: компанія + ЄДРПОУ',
                    'ru-ru' => 'B2B: компания + ЕГРПОУ',
                    'en-gb' => 'B2B: Company + Tax ID',
                ],
                'description' => [
                    'uk-ua' => 'Назва компанії та податковий номер для B2B-замовлень.',
                    'ru-ru' => 'Название компании и налоговый номер для B2B-заказов.',
                    'en-gb' => 'Company name and tax ID for B2B orders.',
                ],
                'fields' => [
                    [
                        'code' => 'b2b_company_name', 'type' => 'text', 'belongs_to' => 'order',
                        'params' => [],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'Назва компанії', 'placeholder' => 'ТОВ "..."', 'tooltip' => ''],
                            'ru-ru' => ['name' => 'Название компании', 'placeholder' => 'ООО "..."', 'tooltip' => ''],
                            'en-gb' => ['name' => 'Company name', 'placeholder' => '"..." LLC', 'tooltip' => ''],
                        ],
                    ],
                    [
                        'code' => 'b2b_tax_id', 'type' => 'text', 'belongs_to' => 'order',
                        'params' => [],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'ЄДРПОУ / ІПН', 'placeholder' => '00000000', 'tooltip' => ''],
                            'ru-ru' => ['name' => 'ЕГРПОУ / ИНН', 'placeholder' => '00000000', 'tooltip' => ''],
                            'en-gb' => ['name' => 'Tax ID', 'placeholder' => '00000000', 'tooltip' => ''],
                        ],
                    ],
                ],
            ],
            [
                'code'  => 'delivery_date',
                'label' => [
                    'uk-ua' => 'Дата доставки',
                    'ru-ru' => 'Дата доставки',
                    'en-gb' => 'Delivery date',
                ],
                'description' => [
                    'uk-ua' => 'Дата + часовий слот доставки. Налаштоване виключення вихідних та min/max днів вперед.',
                    'ru-ru' => 'Дата + временной слот доставки. Настроено исключение выходных и min/max дней вперёд.',
                    'en-gb' => 'Delivery date + time slot. Pre-configured weekend exclusion and min/max days ahead.',
                ],
                'fields' => [
                    [
                        'code' => 'delivery_date', 'type' => 'date', 'belongs_to' => 'order',
                        'params' => [
                            'date' => [
                                'disable_past'    => true,
                                'min_days_ahead'  => 1,
                                'max_days_ahead'  => 14,
                                'weekends'        => [0, 6],
                            ],
                        ],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'Дата доставки', 'placeholder' => '', 'tooltip' => 'Найближча: завтра. До 2 тижнів вперед.'],
                            'ru-ru' => ['name' => 'Дата доставки', 'placeholder' => '', 'tooltip' => 'Ближайшая: завтра. До 2 недель вперёд.'],
                            'en-gb' => ['name' => 'Delivery date', 'placeholder' => '', 'tooltip' => 'Earliest: tomorrow. Up to 2 weeks ahead.'],
                        ],
                    ],
                    [
                        'code' => 'delivery_slot', 'type' => 'select', 'belongs_to' => 'order',
                        'params' => [
                            'options' => [
                                ['value' => '09-12', 'labels' => ['uk-ua' => '09:00–12:00', 'ru-ru' => '09:00–12:00', 'en-gb' => '09:00–12:00']],
                                ['value' => '12-15', 'labels' => ['uk-ua' => '12:00–15:00', 'ru-ru' => '12:00–15:00', 'en-gb' => '12:00–15:00']],
                                ['value' => '15-18', 'labels' => ['uk-ua' => '15:00–18:00', 'ru-ru' => '15:00–18:00', 'en-gb' => '15:00–18:00']],
                                ['value' => '18-21', 'labels' => ['uk-ua' => '18:00–21:00', 'ru-ru' => '18:00–21:00', 'en-gb' => '18:00–21:00']],
                            ],
                        ],
                        'descriptions' => [
                            'uk-ua' => ['name' => 'Часовий слот', 'placeholder' => 'Оберіть слот', 'tooltip' => ''],
                            'ru-ru' => ['name' => 'Временной слот', 'placeholder' => 'Выберите слот', 'tooltip' => ''],
                            'en-gb' => ['name' => 'Time slot', 'placeholder' => 'Select slot', 'tooltip' => ''],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function findByCode(string $code): ?array
    {
        foreach (self::all() as $p) {
            if ($p['code'] === $code) return $p;
        }
        return null;
    }
}
