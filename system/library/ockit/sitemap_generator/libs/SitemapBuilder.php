<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Dto\SitemapEntry;

/**
 * Produces XML strings for sitemap files using XMLWriter.
 * Streaming approach: each entry is rendered independently — no full document in memory.
 */
class SitemapBuilder
{
    private bool   $includeImages;
    private bool   $includeHreflang;
    private bool   $enableXsl;
    private string $xslUrl;

    public function __construct(
        bool   $includeImages   = false,
        bool   $includeHreflang = false,
        bool   $enableXsl       = false,
        string $xslUrl          = ''
    ) {
        $this->includeImages   = $includeImages;
        $this->includeHreflang = $includeHreflang;
        $this->enableXsl       = $enableXsl;
        $this->xslUrl          = $xslUrl;
    }

    /**
     * Returns the opening <urlset> tag with appropriate namespaces.
     * Prepends an XSL processing instruction when enabled.
     */
    public function openUrlset(): string
    {
        $ns = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

        if ($this->includeImages) {
            $ns .= "\n        xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\"";
        }
        if ($this->includeHreflang) {
            $ns .= "\n        xmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }

        $xslPi = ($this->enableXsl && $this->xslUrl !== '')
            ? '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($this->xslUrl, ENT_XML1, 'UTF-8') . '"?>' . "\n"
            : '';

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . $xslPi
             . '<urlset ' . $ns . ">\n";
    }

    public function closeUrlset(): string
    {
        return "</urlset>\n";
    }

    /**
     * Renders a single <url> block as a string.
     */
    public function renderEntry(SitemapEntry $entry): string
    {
        $xml = "  <url>\n";
        $xml .= '    <loc>' . $this->esc($entry->loc) . "</loc>\n";

        if ($entry->lastmod !== null) {
            $xml .= '    <lastmod>' . $this->esc($entry->lastmod) . "</lastmod>\n";
        }
        if ($entry->changefreq !== null) {
            $xml .= '    <changefreq>' . $this->esc($entry->changefreq) . "</changefreq>\n";
        }
        if ($entry->priority !== null) {
            $xml .= '    <priority>' . number_format($entry->priority, 1, '.', '') . "</priority>\n";
        }

        if ($this->includeImages && !empty($entry->images)) {
            foreach ($entry->images as $image) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . $this->esc($image->loc) . "</image:loc>\n";
                $xml .= "    </image:image>\n";
            }
        }

        if ($this->includeHreflang && !empty($entry->hreflangLinks)) {
            foreach ($entry->hreflangLinks as $hreflang => $href) {
                $xml .= '    <xhtml:link'
                      . ' rel="alternate"'
                      . ' hreflang="' . $this->esc($hreflang) . '"'
                      . ' href="'     . $this->esc($href)     . '"'
                      . "/>\n";
            }
        }

        $xml .= "  </url>\n";
        return $xml;
    }

    /**
     * Renders a full <sitemapindex> document.
     * Prepends an XSL processing instruction when enabled.
     *
     * @param array $entries  [['loc' => string, 'lastmod' => string|null], ...]
     */
    public function renderSitemapIndex(array $entries): string
    {
        $xslPi = ($this->enableXsl && $this->xslUrl !== '')
            ? '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($this->xslUrl, ENT_XML1, 'UTF-8') . '"?>' . "\n"
            : '';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= $xslPi;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . $this->esc($entry['loc']) . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . $this->esc($entry['lastmod']) . "</lastmod>\n";
            }
            $xml .= "  </sitemap>\n";
        }

        $xml .= "</sitemapindex>\n";
        return $xml;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
