<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Dto\SitemapConfig;

/**
 * Creates UrlCollector instances per language map.
 * Injected into CronRunner to avoid circular dependencies.
 */
class UrlCollectorFactory
{
    private $db;
    private string $prefix;
    private string $baseUrl;
    private int    $storeId;
    private ?ImageResolver $imageResolver;

    public function __construct($db, string $prefix, string $baseUrl, int $storeId = 0, ?ImageResolver $imageResolver = null)
    {
        $this->db            = $db;
        $this->prefix        = $prefix;
        $this->baseUrl       = $baseUrl;
        $this->storeId       = $storeId;
        $this->imageResolver = $imageResolver;
    }

    public function make(SitemapConfig $map): UrlCollector
    {
        return new UrlCollector(
            $this->db,
            $this->prefix,
            $this->baseUrl,
            $map->urlPrefix,
            $map->languageId,
            $this->storeId
        );
    }

    public function getImageResolver(): ?ImageResolver
    {
        return $this->imageResolver;
    }
}
