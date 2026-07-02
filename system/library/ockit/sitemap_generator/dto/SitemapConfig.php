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
 * Value object representing one language map configuration row.
 */
class SitemapConfig
{
    public int    $mapId;
    public int    $languageId;
    public string $languageCode;
    public string $urlPrefix;
    public string $filename;
    public string $hreflangLocale;
    public bool   $isDefault;
    public bool   $status;
    public ?string $lastGeneratedAt;
    public int    $urlsCount;
    public int    $sortOrder;

    public function __construct(
        int    $mapId,
        int    $languageId,
        string $languageCode,
        string $urlPrefix,
        string $filename,
        string $hreflangLocale,
        bool   $isDefault,
        bool   $status,
        ?string $lastGeneratedAt,
        int    $urlsCount,
        int    $sortOrder
    ) {
        $this->mapId           = $mapId;
        $this->languageId      = $languageId;
        $this->languageCode    = $languageCode;
        $this->urlPrefix       = $urlPrefix;
        $this->filename        = $filename;
        $this->hreflangLocale  = $hreflangLocale;
        $this->isDefault       = $isDefault;
        $this->status          = $status;
        $this->lastGeneratedAt = $lastGeneratedAt;
        $this->urlsCount       = $urlsCount;
        $this->sortOrder       = $sortOrder;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int)($row['map_id']           ?? 0),
            (int)($row['language_id']      ?? 0),
            (string)($row['language_code'] ?? ''),
            (string)($row['url_prefix']    ?? ''),
            (string)($row['filename']      ?? 'sitemap'),
            (string)($row['hreflang_locale'] ?? ''),
            (bool)(int)($row['is_default'] ?? 0),
            (bool)(int)($row['status']     ?? 1),
            $row['last_generated_at'] ?: null,
            (int)($row['urls_count']       ?? 0),
            (int)($row['sort_order']       ?? 0)
        );
    }

    /**
     * Returns the base URL path prefix segment (e.g. "" or "en/").
     */
    public function getUrlSegment(): string
    {
        return $this->urlPrefix !== '' ? rtrim($this->urlPrefix, '/') . '/' : '';
    }
}
