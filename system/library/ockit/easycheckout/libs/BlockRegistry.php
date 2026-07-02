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
 * Реєстр типів блоків сторінки checkout.
 *
 * Кожен тип має метадані для UI конструктора:
 *  - label_key — мовний ключ назви
 *  - icon — назва Lucide-іконки в адмінці
 *  - unique — чи можна додавати лише одну копію блоку на сторінку
 *  - has_fieldset — чи має блок налаштування набору полів (customer, address)
 *  - has_visibility — чи має блок toggle для гостей/авторизованих
 */
final class BlockRegistry
{
    private const TYPES = [
        'customer' => [
            'label_key'      => 'block_type_customer',
            'icon'           => 'user',
            'unique'         => true,
            'has_fieldset'   => true,
            'has_visibility' => true,
            'sort_default'   => 10,
        ],
        'cart' => [
            'label_key'      => 'block_type_cart',
            'icon'           => 'shopping-cart',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 5,
        ],
        'payment_address' => [
            'label_key'      => 'block_type_payment_address',
            'icon'           => 'credit-card',
            'unique'         => true,
            'has_fieldset'   => true,
            'has_visibility' => true,
            'sort_default'   => 20,
        ],
        'shipping_address' => [
            'label_key'      => 'block_type_shipping_address',
            'icon'           => 'map-pin',
            'unique'         => true,
            'has_fieldset'   => true,
            'has_visibility' => true,
            'sort_default'   => 25,
        ],
        'shipping' => [
            'label_key'      => 'block_type_shipping',
            'icon'           => 'truck',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 30,
        ],
        'payment' => [
            'label_key'      => 'block_type_payment',
            'icon'           => 'wallet',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 40,
        ],
        'comment' => [
            'label_key'      => 'block_type_comment',
            'icon'           => 'message-square',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 50,
        ],
        'agreement' => [
            'label_key'      => 'block_type_agreement',
            'icon'           => 'file-check',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 55,
        ],
        'help' => [
            'label_key'      => 'block_type_help',
            'icon'           => 'help-circle',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 60,
        ],
        'summary' => [
            'label_key'      => 'block_type_summary',
            'icon'           => 'receipt',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 70,
        ],
        'payment_form' => [
            'label_key'      => 'block_type_payment_form',
            'icon'           => 'banknote',
            'unique'         => true,
            'has_fieldset'   => false,
            'has_visibility' => false,
            'sort_default'   => 80,
        ],
        'buttons' => [
            'label_key'      => 'block_type_buttons',
            'icon'           => 'send',
            'unique'         => false,    // можна додати кілька (зверху + знизу + sticky-mobile)
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 90,
        ],
        'custom_html' => [
            'label_key'      => 'block_type_custom_html',
            'icon'           => 'code',
            'unique'         => false,    // багато довільного HTML
            'has_fieldset'   => false,
            'has_visibility' => true,
            'sort_default'   => 100,
        ],
    ];

    public static function exists(string $code): bool
    {
        return isset(self::TYPES[$code]);
    }

    public static function getMeta(string $code): ?array
    {
        return self::TYPES[$code] ?? null;
    }

    /** @return array<string, array> */
    public static function listTypes(): array
    {
        return self::TYPES;
    }

    /** Чи можна додавати блок з кодом $code до layout (для unique перевіряємо чи вже є). */
    public static function canAdd(string $code, array $usedCodes): bool
    {
        $meta = self::getMeta($code);
        if (!$meta) return false;
        if (!$meta['unique']) return true;
        return !in_array($code, $usedCodes, true);
    }

    /**
     * Згенерувати унікальний id блока (для випадків коли unique=false).
     * Формат: type__N (customer__1, buttons__1, buttons__2...).
     */
    public static function generateBlockId(string $type, array $existingIds): string
    {
        $i = 1;
        do {
            $id = $type . '__' . $i++;
        } while (in_array($id, $existingIds, true));
        return $id;
    }
}
