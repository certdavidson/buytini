<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * Реєстр типів умов показу кастомних методів (підмножина filterit, що
 * реально обчислюється зі стану чекауту + кошика, без важких агрегацій).
 *
 * Кожен тип: code, lang_key, group, applies ('both'|'shipping'|'payment').
 * applies='shipping' → доступний лише в shipping-методі (напр. payment_variant);
 * applies='payment'  → лише в payment-методі (напр. shipping_variant).
 */
final class ConditionTypes
{
    /** @return array<int,array{code:string,lang_key:string,group:string,applies:string}> */
    public static function all(): array
    {
        return [
            // Покупець
            ['code' => 'logged_in',       'lang_key' => 'cm_cond_logged_in',       'group' => 'customer', 'applies' => 'both'],
            ['code' => 'customer_group',  'lang_key' => 'cm_cond_customer_group',  'group' => 'customer', 'applies' => 'both'],
            ['code' => 'has_orders',      'lang_key' => 'cm_cond_has_orders',      'group' => 'customer', 'applies' => 'both'],
            // Сума / кількість / вага
            ['code' => 'total',             'lang_key' => 'cm_cond_total',             'group' => 'cart', 'applies' => 'both'],
            ['code' => 'total_no_shipping', 'lang_key' => 'cm_cond_total_no_shipping', 'group' => 'cart', 'applies' => 'both'],
            ['code' => 'total_quantity',    'lang_key' => 'cm_cond_total_quantity',    'group' => 'cart', 'applies' => 'both'],
            ['code' => 'total_weight',      'lang_key' => 'cm_cond_total_weight',      'group' => 'cart', 'applies' => 'both'],
            ['code' => 'max_weight_single', 'lang_key' => 'cm_cond_max_weight_single', 'group' => 'cart', 'applies' => 'both'],
            ['code' => 'coupon_used',       'lang_key' => 'cm_cond_coupon_used',       'group' => 'cart', 'applies' => 'both'],
            ['code' => 'reward_used',       'lang_key' => 'cm_cond_reward_used',       'group' => 'cart', 'applies' => 'both'],
            ['code' => 'voucher_used',      'lang_key' => 'cm_cond_voucher_used',      'group' => 'cart', 'applies' => 'both'],
            ['code' => 'products_no_shipping','lang_key' => 'cm_cond_products_no_shipping','group' => 'cart', 'applies' => 'both'],
            // Адреса
            ['code' => 'country_id',  'lang_key' => 'cm_cond_country',  'group' => 'address', 'applies' => 'both'],
            ['code' => 'zone_id',     'lang_key' => 'cm_cond_zone',     'group' => 'address', 'applies' => 'both'],
            ['code' => 'city',        'lang_key' => 'cm_cond_city',     'group' => 'address', 'applies' => 'both'],
            ['code' => 'postcode',    'lang_key' => 'cm_cond_postcode', 'group' => 'address', 'applies' => 'both'],
            // Контекст
            ['code' => 'language',    'lang_key' => 'cm_cond_language', 'group' => 'context', 'applies' => 'both'],
            ['code' => 'currency',    'lang_key' => 'cm_cond_currency', 'group' => 'context', 'applies' => 'both'],
            ['code' => 'store_id',    'lang_key' => 'cm_cond_store',    'group' => 'context', 'applies' => 'both'],
            ['code' => 'ip',          'lang_key' => 'cm_cond_ip',       'group' => 'context', 'applies' => 'both'],
            ['code' => 'day_of_week', 'lang_key' => 'cm_cond_day',      'group' => 'context', 'applies' => 'both'],
            ['code' => 'time',        'lang_key' => 'cm_cond_time',     'group' => 'context', 'applies' => 'both'],
            ['code' => 'date',        'lang_key' => 'cm_cond_date',     'group' => 'context', 'applies' => 'both'],
            // Крос-тип методів
            ['code' => 'payment_method',  'lang_key' => 'cm_cond_payment_variant',  'group' => 'methods', 'applies' => 'shipping'],
            ['code' => 'shipping_method', 'lang_key' => 'cm_cond_shipping_variant', 'group' => 'methods', 'applies' => 'payment'],
        ];
    }

    /** Групи для optgroup. */
    public static function groups(): array
    {
        return [
            'customer' => 'cm_cond_group_customer',
            'cart'     => 'cm_cond_group_cart',
            'address'  => 'cm_cond_group_address',
            'context'  => 'cm_cond_group_context',
            'methods'  => 'cm_cond_group_methods',
        ];
    }
}
