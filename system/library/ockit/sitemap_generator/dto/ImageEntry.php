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
 * Value object for a single image within a sitemap <image:image> tag.
 */
class ImageEntry
{
    public string $loc;

    public function __construct(string $loc)
    {
        $this->loc = $loc;
    }
}
