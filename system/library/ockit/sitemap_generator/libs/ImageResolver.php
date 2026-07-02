<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Dto\ImageEntry;

/**
 * Resolves product images to public URLs for the image sitemap extension.
 * Supports original paths and resized cache paths (without triggering resize).
 */
class ImageResolver
{
    private string    $baseUrl;
    private string    $imageDir;    // absolute path to image root (DIR_IMAGE)
    private string    $imageType;   // 'original' | 'resized'
    private int       $width;
    private int       $height;
    private bool      $includeAdditional;
    private int       $maxImages;   // 0 = no limit
    /** Callable(string $srcAbs, string $dstAbs, int $w, int $h): void — triggers resize */
    private $resizeFn;
    /** Runtime stats: created/cached/original/missing */
    private array $stats = ['created' => 0, 'cached' => 0, 'original' => 0, 'missing' => 0];

    public function __construct(string $baseUrl, string $imageDir, array $cfg, ?callable $resizeFn = null)
    {
        $this->baseUrl           = rtrim($baseUrl, '/');
        $this->imageDir          = rtrim($imageDir, '/') . '/';
        $this->imageType         = $cfg['image_type']         ?? 'original';
        $this->width             = (int)($cfg['image_width']  ?? 800);
        $this->height            = (int)($cfg['image_height'] ?? 800);
        $this->includeAdditional = !empty($cfg['include_additional_images']);
        $this->maxImages         = (int)($cfg['max_images_per_product'] ?? 10);
        $this->resizeFn          = $resizeFn;
    }

    /**
     * Returns ImageEntry[] for a product given its main image path and additional rows.
     *
     * @param string  $mainImage      relative path like "catalog/my-product.jpg"
     * @param array[] $additionalRows rows from oc_product_image (each has 'image' key)
     * @param int     $entityId       product_id — used to organise sitemap cache under sitemap/{entityId}/
     * @return ImageEntry[]
     */
    public function resolve(string $mainImage, array $additionalRows = [], int $entityId = 0): array
    {
        $images  = [];
        $sources = [];

        if ($mainImage !== '') {
            $sources[] = $mainImage;
        }

        if ($this->includeAdditional) {
            foreach ($additionalRows as $row) {
                if (!empty($row['image'])) {
                    $sources[] = $row['image'];
                }
            }
        }

        foreach ($sources as $path) {
            if ($this->maxImages > 0 && count($images) >= $this->maxImages) break;

            $url = $this->resolveOne($path, $entityId);
            if ($url !== null) {
                $images[] = new ImageEntry($url);
            }
        }

        return $images;
    }

    /** Resets runtime stats (call before a batch run). */
    public function resetStats(): void
    {
        $this->stats = ['created' => 0, 'cached' => 0, 'original' => 0, 'missing' => 0];
    }

    /** Returns runtime stats accumulated since last resetStats(). */
    public function getStats(): array
    {
        return $this->stats;
    }

    private function resolveOne(string $path, int $entityId = 0): ?string
    {
        if ($path === '' || $path === 'no_image.png') return null;

        if ($this->imageType === 'resized') {
            return $this->resolveResized($path, $entityId);
        }
        return $this->resolveOriginal($path);
    }

    private function resolveOriginal(string $path): ?string
    {
        // Check physical existence
        if (!file_exists($this->imageDir . $path)) return null;
        return $this->baseUrl . '/image/' . ltrim($path, '/');
    }

    private function resolveResized(string $path, int $entityId = 0): ?string
    {
        $info      = pathinfo($path);
        $extension = strtolower($info['extension'] ?? 'jpg');
        $filename  = $info['filename'] ?? '';

        $cacheName = $filename . '-' . $this->width . 'x' . $this->height . '.' . $extension;

        // Sitemap-specific cache: image/cache/sitemap/{entityId}/{filename}-WxH.ext
        // Falls back to image/cache/sitemap/{subdir}/{filename}-WxH.ext when no entityId
        if ($entityId > 0) {
            $cachePath = 'cache/sitemap/' . $entityId . '/' . $cacheName;
        } else {
            $dirname   = ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '');
            $cachePath = 'cache/sitemap/' . $dirname . $cacheName;
        }

        $fullCachePath = $this->imageDir . $cachePath;

        if (!file_exists($fullCachePath)) {
            if ($this->resizeFn !== null) {
                $srcPath = $this->imageDir . $path;
                if (file_exists($srcPath)) {
                    $cacheDir = dirname($fullCachePath);
                    if (!is_dir($cacheDir)) {
                        mkdir($cacheDir, 0755, true);
                    }
                    ($this->resizeFn)($srcPath, $fullCachePath, $this->width, $this->height);
                    if (file_exists($fullCachePath)) {
                        $this->stats['created']++;
                        return $this->baseUrl . '/image/' . $cachePath;
                    }
                }
            }
            // Fall back to original
            $orig = $this->resolveOriginal($path);
            if ($orig !== null) {
                $this->stats['original']++;
            } else {
                $this->stats['missing']++;
            }
            return $orig;
        }

        $this->stats['cached']++;
        return $this->baseUrl . '/image/' . $cachePath;
    }
}
