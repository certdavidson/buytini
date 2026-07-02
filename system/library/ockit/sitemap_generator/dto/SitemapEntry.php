<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Dto;

/**
 * Value object representing one <url> record in a sitemap XML.
 */
class SitemapEntry
{
    public string  $loc;
    public ?string $lastmod;
    public ?string $changefreq;
    public ?float  $priority;
    /** @var ImageEntry[] */
    public array $images;
    /** @var array<string, string>  hreflang => href */
    public array $hreflangLinks;
    /**
     * OC query string for this entity (e.g. 'product_id=123').
     * Used by HreflangBuilder for per-language slug lookup.
     * Null for static pages (home, special, contact).
     */
    public ?string $query = null;

    public function __construct(
        string  $loc,
        ?string $lastmod       = null,
        ?string $changefreq    = null,
        ?float  $priority      = null,
        array   $images        = [],
        array   $hreflangLinks = []
    ) {
        $this->loc           = $loc;
        $this->lastmod       = $lastmod;
        $this->changefreq    = $changefreq;
        $this->priority      = $priority;
        $this->images        = $images;
        $this->hreflangLinks = $hreflangLinks;
    }
}
