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

namespace OcKit\SeoCore\Dto;

/**
 * Single <link rel="alternate" hreflang="..." href="..."> entry.
 */
final class HreflangEntry
{
    public string $hreflang;   // e.g. "uk" or "uk-UA" (ISO or BCP 47)
    public string $href;
    public bool   $xDefault;

    public function __construct(string $hreflang, string $href, bool $xDefault = false)
    {
        $this->hreflang = $hreflang;
        $this->href     = $href;
        $this->xDefault = $xDefault;
    }

    public function toArray(): array
    {
        return [
            'hreflang' => $this->xDefault ? 'x-default' : $this->hreflang,
            'href'     => $this->href,
        ];
    }
}
