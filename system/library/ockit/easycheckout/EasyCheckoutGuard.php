<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout;

require_once __DIR__ . '/libs/StoreContext.php';

use OcKit\EasyCheckout\Libs\StoreContext;

/**
 * Ліцензійний guard модуля EasyCheckout. Static-методи виключно — щоб
 * викликати без інстансу і уникнути redirect loop у самому license-екрані.
 */
final class EasyCheckoutGuard
{
    public static function getInfo($registry): array
    {
        return (new StoreContext($registry->get('db'), $registry->get('config')))->getInfo();
    }

    public static function isLicensed($registry): bool
    {
        return (new StoreContext($registry->get('db'), $registry->get('config')))->isActive();
    }

    public static function activate($registry, string $key): array
    {
        $result = (new StoreContext($registry->get('db'), $registry->get('config')))->activate($key);
        return [
            'success'    => (bool)($result['success'] ?? false),
            'error_code' => (string)($result['error_code'] ?? ''),
            'info'       => (array)($result['info'] ?? []),
        ];
    }

    /**
     * Якщо модуль не ліцензовано — редірект на /license-екран. Виклик з кожного
     * page/AJAX handler-а admin-контролера. Самі license-endpoints пропускаються.
     */
    public static function guardAdmin($registry): void
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        if ($ctx->isActive()) return;
        if (php_sapi_name() === 'cli') return;

        $route = (string)($registry->get('request')->get['route'] ?? '');
        if (
            $route === 'extension/module/oc_kit_easycheckout/license' ||
            $route === 'extension/module/oc_kit_easycheckout/licenseActivate' ||
            $route === 'extension/module/oc_kit_easycheckout/licenseInfo'
        ) return;

        $token = $registry->get('session')->data['user_token'] ?? '';
        if ($token === '') return;
        $registry->get('response')->redirect(
            $registry->get('url')->link(
                'extension/module/oc_kit_easycheckout/license',
                'user_token=' . $token, true
            )
        );
        exit;
    }
}
