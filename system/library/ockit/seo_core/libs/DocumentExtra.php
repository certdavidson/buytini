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

namespace OcKit\SeoCore\Libs;

/**
 * Centralized accumulator for extra <head> tags that OpenCart 3.x Document
 * class cannot produce on its own: arbitrary <meta>, hreflang-specific <link>,
 * <script type="application/ld+json">.
 *
 * OCMOD-patch on catalog/view/theme/*_/template/common/header.twig reads
 * $scf_head_extra passed by the common/header.php patch and echoes it before
 * the closing </head>.
 */
class DocumentExtra
{
    /** @var array<int, array{name:string, content:string, property?:string}> */
    private static array $metas = [];

    /** @var array<int, array{href:string, rel:string, hreflang?:string}> */
    private static array $links = [];

    /** @var array<int, string> Raw JSON-LD blocks. */
    private static array $jsonLd = [];

    public static function addMeta(array $attrs): void
    {
        if (empty($attrs)) return;
        self::$metas[] = $attrs;
    }

    public static function addLink(array $attrs): void
    {
        if (empty($attrs['href']) || empty($attrs['rel'])) return;
        self::$links[] = $attrs;
    }

    public static function addJsonLd(string $jsonLd): void
    {
        $jsonLd = trim($jsonLd);
        if ($jsonLd === '') return;
        self::$jsonLd[] = $jsonLd;
    }

    public static function reset(): void
    {
        self::$metas  = [];
        self::$links  = [];
        self::$jsonLd = [];
    }

    /**
     * Render all accumulated tags as an HTML string suitable for inclusion
     * in <head>. Safe for use inside Twig via |raw.
     */
    public static function render(): string
    {
        $out = '';

        foreach (self::$links as $l) {
            $out .= '<link';
            foreach ($l as $k => $v) {
                $out .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') .
                        '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
            }
            $out .= ' />' . "\n";
        }

        foreach (self::$metas as $m) {
            $out .= '<meta';
            foreach ($m as $k => $v) {
                $out .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') .
                        '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
            }
            $out .= ' />' . "\n";
        }

        foreach (self::$jsonLd as $json) {
            // JSON-LD content is expected to be escaped by the template engine
            // at build time. Only strip </script> as a safety net.
            $json = str_replace('</script', '<\\/script', $json);
            $out .= '<script type="application/ld+json">' . $json . '</script>' . "\n";
        }

        return $out;
    }
}
