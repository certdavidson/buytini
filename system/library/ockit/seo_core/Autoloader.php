<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore;

/**
 * PSR-4 style autoloader for the SeoCore module.
 *
 * Maps namespace `OcKit\SeoCore\<SubNs>\<Class>` to file
 * `{baseDir}/<subns>/<Class>.php` (sub-namespace lower-cased to
 * match the on-disk folder convention: Libs/ → libs/, Dto/ → dto/,
 * Exceptions/ → exceptions/).
 */
final class Autoloader
{
    private const PREFIX = 'OcKit\\SeoCore\\';
    private static bool $registered = false;

    public static function register(string $baseDir): void
    {
        if (self::$registered) return;
        self::$registered = true;

        $baseDir = rtrim($baseDir, '/') . '/';

        spl_autoload_register(static function (string $class) use ($baseDir): void {
            if (strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0) return;

            $parts     = explode('\\', substr($class, strlen(self::PREFIX)));
            $className = array_pop($parts);
            $dir       = $parts ? strtolower(implode('/', $parts)) . '/' : '';
            $file      = $baseDir . $dir . $className . '.php';

            if (is_file($file)) require_once $file;
        });
    }
}
