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
 * Collects sitemap URLs from the database for all supported content types.
 * All queries use LIMIT/OFFSET for chunked processing — no full-table loads.
 */
class UrlCollector
{
    private $db;
    private string $prefix;
    private string $baseUrl;
    private string $urlPrefix;   // e.g. "" or "en/"
    private int    $languageId;
    private int    $storeId;

    // Blog table existence cache (null = not checked yet)
    private ?bool $hasBlogPost     = null;
    private ?bool $hasBlogCategory = null;

    public function __construct($db, string $prefix, string $baseUrl, string $urlPrefix, int $languageId, int $storeId = 0)
    {
        $this->db         = $db;
        $this->prefix     = $prefix;
        $this->baseUrl    = rtrim($baseUrl, '/');
        $this->urlPrefix  = $urlPrefix !== '' ? rtrim($urlPrefix, '/') . '/' : '';
        $this->languageId = $languageId;
        $this->storeId    = $storeId;
    }

    // ── Home ──────────────────────────────────────────────────────────────────

    /** @return SitemapEntry[] */
    public function getHome(array $cfg): array
    {
        return [new SitemapEntry(
            $this->buildUrl(''),
            null,
            $cfg['changefreq'] ?? 'daily',
            (float)($cfg['priority'] ?? 1.0)
        )];
    }

    // ── Categories ───────────────────────────────────────────────────────────

    public function countCategories(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) AS total FROM `{$this->prefix}category` WHERE status = '1'"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getCategories(array $cfg, int $offset, int $limit): array
    {
        $rows = $this->db->query(
            "SELECT c.category_id, c.date_modified
             FROM `{$this->prefix}category` c
             WHERE c.status = '1'
             ORDER BY c.category_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'category_id=' . $row['category_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                $this->formatDate($row['date_modified'], $cfg['lastmod'] ?? 'auto'),
                $cfg['changefreq'] ?? 'weekly',
                (float)($cfg['priority'] ?? 0.8)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    // ── Products ─────────────────────────────────────────────────────────────

    public function countProducts(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) AS total FROM `{$this->prefix}product`
             WHERE status = '1'"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getProducts(array $cfg, int $offset, int $limit): array
    {
        $rows = $this->db->query(
            "SELECT p.product_id, p.date_modified
             FROM `{$this->prefix}product` p
             WHERE p.status = '1'
             ORDER BY p.product_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'product_id=' . $row['product_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                $this->formatDate($row['date_modified'], $cfg['lastmod'] ?? 'auto'),
                $cfg['changefreq'] ?? 'weekly',
                (float)($cfg['priority'] ?? 0.8)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    /**
     * Returns product rows with raw image paths for ImageResolver.
     * Includes product_id and image field.
     */
    public function getProductsWithImages(array $cfg, int $offset, int $limit): array
    {
        return $this->db->query(
            "SELECT p.product_id, p.image, p.date_modified
             FROM `{$this->prefix}product` p
             WHERE p.status = '1'
             ORDER BY p.product_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;
    }

    public function getProductAdditionalImages(int $productId): array
    {
        return $this->db->query(
            "SELECT image FROM `{$this->prefix}product_image`
             WHERE product_id = " . (int)$productId . "
             ORDER BY sort_order"
        )->rows;
    }

    public function getProductSeoUrl(int $productId): ?string
    {
        return $this->getSeoUrl('product_id=' . $productId);
    }

    // ── Manufacturers ────────────────────────────────────────────────────────

    public function countManufacturers(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(DISTINCT m.manufacturer_id) AS total
             FROM `{$this->prefix}manufacturer` m
             INNER JOIN `{$this->prefix}product` p ON p.manufacturer_id = m.manufacturer_id
             WHERE p.status = '1'"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getManufacturers(array $cfg, int $offset, int $limit): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT m.manufacturer_id
             FROM `{$this->prefix}manufacturer` m
             INNER JOIN `{$this->prefix}product` p ON p.manufacturer_id = m.manufacturer_id
             WHERE p.status = '1'
             ORDER BY m.manufacturer_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'manufacturer_id=' . $row['manufacturer_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                null,
                $cfg['changefreq'] ?? 'monthly',
                (float)($cfg['priority'] ?? 0.6)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    // ── Information pages ────────────────────────────────────────────────────

    public function countInformation(bool $includeBottom = false): int
    {
        $bottomSql = $includeBottom ? '' : "AND bottom = '0'";
        return (int)$this->db->query(
            "SELECT COUNT(*) AS total FROM `{$this->prefix}information`
             WHERE status = '1' {$bottomSql}"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getInformation(array $cfg, int $offset, int $limit): array
    {
        $includeBottom = !empty($cfg['include_bottom']);
        $bottomSql     = $includeBottom ? '' : "AND i.bottom = '0'";

        $rows = $this->db->query(
            "SELECT i.information_id
             FROM `{$this->prefix}information` i
             WHERE i.status = '1' {$bottomSql}
             ORDER BY i.information_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'information_id=' . $row['information_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                null,
                $cfg['changefreq'] ?? 'monthly',
                (float)($cfg['priority'] ?? 0.5)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    // ── Blog posts ───────────────────────────────────────────────────────────

    public function hasBlogPostTable(): bool
    {
        if ($this->hasBlogPost === null) {
            $result = $this->db->query(
                "SHOW TABLES LIKE '" . $this->prefix . "blog_post'"
            );
            $this->hasBlogPost = !empty($result->rows);
        }
        return $this->hasBlogPost;
    }

    public function countBlogPosts(): int
    {
        if (!$this->hasBlogPostTable()) return 0;
        return (int)$this->db->query(
            "SELECT COUNT(*) AS total FROM `{$this->prefix}blog_post` WHERE status = '1'"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getBlogPosts(array $cfg, int $offset, int $limit): array
    {
        if (!$this->hasBlogPostTable()) return [];

        $rows = $this->db->query(
            "SELECT post_id, date_modified
             FROM `{$this->prefix}blog_post`
             WHERE status = '1'
             ORDER BY post_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'blog_post_id=' . $row['post_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                $this->formatDate($row['date_modified'] ?? null, $cfg['lastmod'] ?? 'auto'),
                $cfg['changefreq'] ?? 'monthly',
                (float)($cfg['priority'] ?? 0.5)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    // ── Blog categories ──────────────────────────────────────────────────────

    public function hasBlogCategoryTable(): bool
    {
        if ($this->hasBlogCategory === null) {
            $result = $this->db->query(
                "SHOW TABLES LIKE '" . $this->prefix . "blog_category'"
            );
            $this->hasBlogCategory = !empty($result->rows);
        }
        return $this->hasBlogCategory;
    }

    public function countBlogCategories(): int
    {
        if (!$this->hasBlogCategoryTable()) return 0;
        return (int)$this->db->query(
            "SELECT COUNT(*) AS total FROM `{$this->prefix}blog_category` WHERE status = '1'"
        )->row['total'];
    }

    /** @return SitemapEntry[] */
    public function getBlogCategories(array $cfg, int $offset, int $limit): array
    {
        if (!$this->hasBlogCategoryTable()) return [];

        $rows = $this->db->query(
            "SELECT category_id
             FROM `{$this->prefix}blog_category`
             WHERE status = '1'
             ORDER BY category_id
             LIMIT {$limit} OFFSET {$offset}"
        )->rows;

        $entries = [];
        foreach ($rows as $row) {
            $ocQuery = 'blog_category_id=' . $row['category_id'];
            $slug    = $this->getSeoUrl($ocQuery);
            if ($slug === null) continue;

            $e = new SitemapEntry(
                $this->buildUrl($slug),
                null,
                $cfg['changefreq'] ?? 'monthly',
                (float)($cfg['priority'] ?? 0.5)
            );
            $e->query = $ocQuery;
            $entries[] = $e;
        }
        return $entries;
    }

    // ── Static pages ─────────────────────────────────────────────────────────

    /** @return SitemapEntry[] */
    public function getSpecialPage(array $cfg): array
    {
        $slug = $this->getSeoUrl('route=product/special') ?? 'index.php?route=product/special';
        return [new SitemapEntry(
            $this->buildUrl($slug),
            null,
            $cfg['changefreq'] ?? 'daily',
            (float)($cfg['priority'] ?? 0.7)
        )];
    }

    /** @return SitemapEntry[] */
    public function getContactPage(array $cfg): array
    {
        $slug = $this->getSeoUrl('route=information/contact') ?? 'index.php?route=information/contact';
        return [new SitemapEntry(
            $this->buildUrl($slug),
            null,
            $cfg['changefreq'] ?? 'yearly',
            (float)($cfg['priority'] ?? 0.3)
        )];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns [query => keyword] map for all SEO URLs in this language.
     * Used by HreflangBuilder to resolve per-language slugs for hreflang links.
     * Example: ['product_id=123' => 'nike-air-max', 'category_id=5' => 'shoes']
     */
    public function getAllSeoUrlMap(): array
    {
        $result = $this->db->query(
            "SELECT query, keyword FROM `{$this->prefix}seo_url`
             WHERE store_id   = " . (int)$this->storeId . "
               AND language_id = " . (int)$this->languageId . "
               AND keyword != ''"
        );

        $map = [];
        foreach ($result->rows as $row) {
            $map[$row['query']] = $row['keyword'];
        }

        // Also include store_id=0 fallback entries (do not override store-specific)
        if ($this->storeId !== 0) {
            $fallback = $this->db->query(
                "SELECT query, keyword FROM `{$this->prefix}seo_url`
                 WHERE store_id   = 0
                   AND language_id = " . (int)$this->languageId . "
                   AND keyword != ''"
            );
            foreach ($fallback->rows as $row) {
                if (!isset($map[$row['query']])) {
                    $map[$row['query']] = $row['keyword'];
                }
            }
        }

        return $map;
    }

    /**
     * Looks up SEO URL from oc_seo_url table.
     * Returns the keyword/slug or null if not found.
     */
    public function getSeoUrl(string $query): ?string
    {
        $result = $this->db->query(
            "SELECT keyword FROM `{$this->prefix}seo_url`
             WHERE query      = '" . $this->db->escape($query) . "'
               AND store_id   = " . (int)$this->storeId . "
               AND language_id = " . (int)$this->languageId . "
             LIMIT 1"
        );

        if (!empty($result->row['keyword'])) {
            return (string)$result->row['keyword'];
        }

        // Fallback: try store_id=0 for multi-store setups
        if ($this->storeId !== 0) {
            $result = $this->db->query(
                "SELECT keyword FROM `{$this->prefix}seo_url`
                 WHERE query       = '" . $this->db->escape($query) . "'
                   AND store_id    = 0
                   AND language_id = " . (int)$this->languageId . "
                 LIMIT 1"
            );
            if (!empty($result->row['keyword'])) {
                return (string)$result->row['keyword'];
            }
        }

        return null;
    }

    private function buildUrl(string $slug): string
    {
        if ($slug === '') {
            return $this->baseUrl . '/' . $this->urlPrefix;
        }
        return $this->baseUrl . '/' . $this->urlPrefix . ltrim($slug, '/');
    }

    private function formatDate(?string $date, string $mode): ?string
    {
        if ($mode === 'none' || $mode === '' || $date === null) return null;
        // mode = 'auto': use DB date; mode = any date string: use that
        if ($mode === 'auto') {
            return substr($date, 0, 10); // YYYY-MM-DD
        }
        // fixed date provided
        return substr($mode, 0, 10);
    }
}
