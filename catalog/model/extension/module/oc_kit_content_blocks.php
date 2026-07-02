<?php
/**
 * Content Blocks Pro — Catalog Model
 * Thin wrapper around the ContentBlocks library + direct product/category lookups.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ModelExtensionModuleOcKitContentBlocks extends Model
{
    private ?\OcKit\ContentBlocks\ContentBlocks $lib = null;

    private function getLib(): \OcKit\ContentBlocks\ContentBlocks
    {
        if ($this->lib === null) {
            require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
            $this->lib = new \OcKit\ContentBlocks\ContentBlocks($this->registry);
        }
        return $this->lib;
    }

    // ─── Blocks ──────────────────────────────────────────────────────────────

    public function getBlocks(string $pageRoute, int $pageId): array
    {
        $dtos = $this->getLib()->getBlocks($pageRoute, $pageId);
        return $this->dtosToArray($dtos);
    }

    public function getBlock(int $blockId): ?array
    {
        try {
            $dto = $this->getLib()->getBlock($blockId);
            return json_decode(json_encode($dto), true);
        } catch (\OcKit\ContentBlocks\Exceptions\BlockNotFoundException $e) {
            return null;
        }
    }

    public function getGlobalBlocks(): array
    {
        $dtos = $this->getLib()->getGlobalBlocks();
        return $this->dtosToArray($dtos);
    }

    /** Convert an array of DTOs (with nested objects) to plain associative arrays. */
    private function dtosToArray(array $dtos): array
    {
        return json_decode(json_encode($dtos), true) ?: [];
    }

    // ─── Products ────────────────────────────────────────────────────────────

    /**
     * Batch fetch products by IDs. Returns a map keyed by product_id.
     * Single query — replaces N+1 from per-element getProductById calls.
     */
    public function getProductsByIds(array $productIds, int $languageId, int $imgW = 300, int $imgH = 300): array
    {
        $ids = array_filter(array_map('intval', $productIds));
        if (!$ids) return [];

        $p     = DB_PREFIX;
        $idCsv = implode(',', $ids);

        $cgrp = (int)($this->config->get('config_customer_group_id') ?: 1);
        $rows = $this->db->query(
            "SELECT p.product_id, pd.name, pd.description, p.price, p.model, p.minimum,
                    p.image, p.quantity, p.status,
                    r.rating, r.total,
                    sp.price AS special
             FROM `{$p}product` p
             LEFT JOIN `{$p}product_description` pd
                ON pd.product_id = p.product_id AND pd.language_id = '" . (int)$languageId . "'
             LEFT JOIN (
                 SELECT product_id, ROUND(AVG(rating),1) AS rating, COUNT(*) AS total
                 FROM `{$p}review` WHERE status = 1 GROUP BY product_id
             ) r ON r.product_id = p.product_id
             LEFT JOIN (
                 SELECT product_id, MIN(price) AS price
                 FROM `{$p}product_special`
                 WHERE customer_group_id = '" . $cgrp . "'
                   AND (date_start = '0000-00-00' OR date_start < NOW())
                   AND (date_end   = '0000-00-00' OR date_end   > NOW())
                 GROUP BY product_id
             ) sp ON sp.product_id = p.product_id
             WHERE p.product_id IN ({$idCsv}) AND p.status = 1"
        )->rows;

        if (!$rows) return [];

        $this->load->model('tool/image');
        $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
        $taxCfg   = $this->config->get('config_tax');

        $map = [];
        foreach ($rows as $row) {
            $hasSpecial   = $row['special'] !== null && (float)$row['special'] < (float)$row['price'];
            $specialRaw   = $hasSpecial ? (float)$row['special'] : 0.0;
            $specialFmt   = $hasSpecial ? $this->currency->format($this->tax->calculate($specialRaw, 0, $taxCfg), $currency) : '';
            $specialPct   = $hasSpecial && (float)$row['price'] > 0
                ? (int)round((1 - $specialRaw / (float)$row['price']) * 100)
                : 0;

            $map[(int)$row['product_id']] = [
                'product_id'      => (int)$row['product_id'],
                'name'            => $row['name'],
                'description'     => $row['description'],
                'price'           => $this->currency->format(
                    $this->tax->calculate($row['price'], 0, $taxCfg), $currency
                ),
                'price_raw'       => (float)$row['price'],
                'special'         => $specialFmt,
                'special_raw'     => $specialRaw,
                'special_percent' => $specialPct,
                'model'           => $row['model'],
                'minimum'         => (int)($row['minimum'] ?? 1),
                'rating'          => (float)($row['rating'] ?? 0),
                'reviews'         => (int)($row['total'] ?? 0),
                'image'           => $row['image']
                    ? $this->model_tool_image->resize($row['image'], $imgW, $imgH)
                    : $this->model_tool_image->resize('placeholder.png', $imgW, $imgH),
                'image_raw'       => $row['image'] ?: 'placeholder.png',
                'href'            => $this->url->link('product/product', 'product_id=' . $row['product_id']),
            ];
        }
        return $map;
    }

    public function getProductById(int $productId, int $languageId): ?array
    {
        $p   = DB_PREFIX;
        $row = $this->db->query(
            "SELECT p.product_id, pd.name, pd.description, p.price, p.model,
                    p.image, p.quantity, p.status,
                    r.rating, r.total
             FROM `{$p}product` p
             LEFT JOIN `{$p}product_description` pd
                ON pd.product_id = p.product_id AND pd.language_id = '" . (int)$languageId . "'
             LEFT JOIN (
                 SELECT product_id, ROUND(AVG(rating),1) AS rating, COUNT(*) AS total
                 FROM `{$p}review` WHERE status = 1 GROUP BY product_id
             ) r ON r.product_id = p.product_id
             WHERE p.product_id = '" . (int)$productId . "' AND p.status = 1
             LIMIT 1"
        )->row;

        if (!$row) return null;

        $this->load->model('tool/image');

        return [
            'product_id'  => (int)$row['product_id'],
            'name'        => $row['name'],
            'description' => $row['description'],
            'price'       => $this->currency->format(
                $this->tax->calculate($row['price'], 0, $this->config->get('config_tax')),
                $this->session->data['currency'] ?? $this->config->get('config_currency')
            ),
            'price_raw'   => (float)$row['price'],
            'model'       => $row['model'],
            'rating'      => (float)($row['rating'] ?? 0),
            'reviews'     => (int)($row['total'] ?? 0),
            'image'       => $row['image']
                ? $this->model_tool_image->resize($row['image'], 300, 300)
                : $this->model_tool_image->resize('placeholder.png', 300, 300),
            'image_raw'   => $row['image'] ?: 'placeholder.png',
            'href' => $this->url->link('product/product', 'product_id=' . $row['product_id']),
        ];
    }

    // ─── Categories ──────────────────────────────────────────────────────────

    /** Batch fetch categories by IDs. Returns a map keyed by category_id. */
    public function getCategoriesByIds(array $categoryIds, int $languageId, int $imgW = 300, int $imgH = 200): array
    {
        $ids = array_filter(array_map('intval', $categoryIds));
        if (!$ids) return [];

        $p     = DB_PREFIX;
        $idCsv = implode(',', $ids);

        $rows = $this->db->query(
            "SELECT c.category_id, cd.name, cd.description, c.image
             FROM `{$p}category` c
             LEFT JOIN `{$p}category_description` cd
                ON cd.category_id = c.category_id AND cd.language_id = '" . (int)$languageId . "'
             WHERE c.category_id IN ({$idCsv}) AND c.status = 1"
        )->rows;

        if (!$rows) return [];

        $this->load->model('tool/image');
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['category_id']] = [
                'category_id' => (int)$row['category_id'],
                'name'        => $row['name'],
                'description' => $row['description'],
                'image'       => $row['image']
                    ? $this->model_tool_image->resize($row['image'], $imgW, $imgH)
                    : $this->model_tool_image->resize('placeholder.png', $imgW, $imgH),
                'href'        => $this->url->link('product/category', 'path=' . $row['category_id']),
            ];
        }
        return $map;
    }

    public function getCategoryById(int $categoryId, int $languageId): ?array
    {
        $p   = DB_PREFIX;
        $row = $this->db->query(
            "SELECT c.category_id, cd.name, cd.description, c.image
             FROM `{$p}category` c
             LEFT JOIN `{$p}category_description` cd
                ON cd.category_id = c.category_id AND cd.language_id = '" . (int)$languageId . "'
             WHERE c.category_id = '" . (int)$categoryId . "' AND c.status = 1
             LIMIT 1"
        )->row;

        if (!$row) return null;

        $this->load->model('tool/image');

        return [
            'category_id' => (int)$row['category_id'],
            'name'        => $row['name'],
            'description' => $row['description'],
            'image'       => $row['image']
                ? $this->model_tool_image->resize($row['image'], 300, 200)
                : $this->model_tool_image->resize('placeholder.png', 300, 200),
            'href' => $this->url->link('product/category', 'path=' . $row['category_id']),
        ];
    }

    // ─── Blog articles ────────────────────────────────────────────────────────

    /** Batch fetch articles by IDs. Returns a map keyed by article_id. */
    public function getArticlesByIds(array $articleIds, int $languageId, int $imgW = 400, int $imgH = 250): array
    {
        $ids = array_filter(array_map('intval', $articleIds));
        if (!$ids) return [];

        $blogType = (string)$this->config->get('module_oc_kit_content_blocks_blog_type') ?: 'default';
        $p        = DB_PREFIX;
        $idCsv    = implode(',', $ids);
        $this->load->model('tool/image');
        $placeholder = $this->model_tool_image->resize('placeholder.png', $imgW, $imgH);

        // Per-blog-type table mapping: trust the config, don't probe schema.
        // If the configured blog isn't installed, the query throws → return
        // an error map so the theme can print a comment with the message.
        $map = [
            'octemplates' => [
                'table' => 'oct_blogarticle',
                'desc'  => 'oct_blogarticle_description',
                'id'    => 'blogarticle_id',
                'route' => 'oct/blog/article',
            ],
            'default'     => [
                'table' => 'article',
                'desc'  => 'article_description',
                'id'    => 'article_id',
                'route' => 'blog/article',
            ],
        ];
        $cfg = $map[$blogType] ?? $map['default'];

        try {
            $rows = $this->db->query(
                "SELECT a.`{$cfg['id']}` AS article_id, a.image, a.date_added, a.viewed,
                        ad.name, ad.description
                 FROM `{$p}{$cfg['table']}` a
                 LEFT JOIN `{$p}{$cfg['desc']}` ad
                    ON ad.`{$cfg['id']}` = a.`{$cfg['id']}` AND ad.language_id = '" . (int)$languageId . "'
                 WHERE a.`{$cfg['id']}` IN ({$idCsv}) AND a.status = 1"
            )->rows;
        } catch (\Throwable $e) {
            return ['__error' => '[blog/' . $blogType . '] ' . $e->getMessage()];
        }

        return $this->mapArticleRows($rows, $imgW, $imgH, $placeholder, $cfg['route'], $cfg['id']);
    }

    /** Build article result map from raw rows (image-resize + URL build). */
    private function mapArticleRows(array $rows, int $imgW, int $imgH, string $placeholder, string $route, string $idKey): array
    {
        $map = [];
        foreach ($rows as $row) {
            $image = !empty($row['image']) && is_file(DIR_IMAGE . $row['image'])
                ? $this->model_tool_image->resize($row['image'], $imgW, $imgH)
                : $placeholder;
            $aid = (int)$row['article_id'];
            $map[$aid] = [
                'article_id'  => $aid,
                'name'        => $row['name']        ?? '',
                'description' => $row['description'] ?? '',
                'image'       => $image,
                'date_added'  => $row['date_added']  ?? '',
                'viewed'      => (int)($row['viewed'] ?? 0),
                'href'        => $this->url->link($route, $idKey . '=' . $aid),
            ];
        }
        return $map;
    }

    public function getArticleById(int $articleId, int $languageId): ?array
    {
        $map = $this->getArticlesByIds([$articleId], $languageId, 400, 250);
        return $map[$articleId] ?? null;
    }
}
