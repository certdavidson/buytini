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
 * Builds og:* and twitter:* meta tags for the current page.
 *
 * og:title / og:description fall back to MetaTemplateEngine templates
 * if dedicated og templates are empty.
 *
 * Config keys:
 *   module_oc_kit_seo_core_og_status              TINYINT(1)
 *   module_oc_kit_seo_core_og_site_name           string
 *   module_oc_kit_seo_core_og_image_fallback      string (URL)
 *   module_oc_kit_seo_core_twitter_card           summary|summary_large_image
 *   module_oc_kit_seo_core_twitter_site           @handle
 *   module_oc_kit_seo_core_og_title_tpl_{lang}    string
 *   module_oc_kit_seo_core_og_desc_tpl_{lang}     string
 */
class OpenGraphRenderer
{
    /** @var MetaTemplateEngine */
    private $meta;
    private $config;
    private $db;
    private $document;
    public function __construct(MetaTemplateEngine $meta, $config, $db, $document) {
        $this->meta = $meta;
        $this->config = $config;
        $this->db = $db;
        $this->document = $document;
    }

    /**
     * Build og/twitter tags for the current page.
     * @return array<string,string>   property => content
     */
    public function render(string $route, array $params, int $languageId): array
    {
        if (!$this->config->get('module_oc_kit_seo_core_og_status')) return [];

        $tags      = [];
        $siteName  = (string)($this->config->get('module_oc_kit_seo_core_og_site_name') ?: $this->config->get('config_name'));
        $langCode  = $this->getLangCode($languageId);
        $canonical = $this->currentUrl();

        switch ($route) {
            case 'product/product':
                $tags = $this->buildProduct($params, $languageId, $langCode, $siteName, $canonical);
                break;

            case 'product/category':
                $tags = $this->buildCategory($params, $languageId, $langCode, $siteName, $canonical);
                break;

            case 'common/home':
                $tags = $this->buildHome($siteName, $langCode, $canonical);
                break;

            default:
                $tags = $this->buildGeneric($route, $params, $languageId, $langCode, $siteName, $canonical);
                break;
        }

        // Twitter Card
        $twitterCard = (string)$this->config->get('module_oc_kit_seo_core_twitter_card');
        if ($twitterCard) {
            $tags['twitter:card']        = $twitterCard;
            $tags['twitter:title']       = $tags['og:title']       ?? '';
            $tags['twitter:description'] = $tags['og:description'] ?? '';
            if (!empty($tags['og:image'])) {
                $tags['twitter:image'] = $tags['og:image'];
            }
            $twitterSite = (string)$this->config->get('module_oc_kit_seo_core_twitter_site');
            if ($twitterSite) {
                $tags['twitter:site'] = $twitterSite;
            }
        }

        return array_filter($tags);
    }

    /**
     * Inject tags into document head as <meta property="..."> and <meta name="...">
     */
    public function inject(array $tags): void
    {
        foreach ($tags as $property => $content) {
            if (!$content) continue;
            $attr = strpos($property, 'twitter:') === 0 ? 'name' : 'property';
            DocumentExtra::addMeta([$attr => $property, 'content' => (string)$content]);
        }
    }

    // ─── Private builders ─────────────────────────────────────────────────────

    private function buildProduct(array $params, int $langId, string $langCode, string $siteName, string $canonical): array
    {
        $productId = (int)($params['product_id'] ?? 0);
        if (!$productId) return [];

        $row = $this->db->query(
            "SELECT pd.name, pd.description, p.price, p.model, p.quantity,
                    m.name AS brand_name
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON pd.product_id = p.product_id AND pd.language_id = " . $langId . "
             LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = p.manufacturer_id
             WHERE p.product_id = " . $productId . " LIMIT 1"
        )->row;

        if (!$row) return [];

        $metaData = $this->meta->render('product', $productId, $langId);
        $title    = $this->ogTitle($langCode, ['name' => $row['name'], 'store_name' => $siteName]) ?: $metaData->title;
        $desc     = $this->ogDesc($langCode,  ['name' => $row['name'], 'description' => mb_substr(strip_tags($row['description'] ?? ''), 0, 160, 'UTF-8')]) ?: $metaData->description;
        $image    = $this->getProductImage($productId) ?: (string)$this->config->get('module_oc_kit_seo_core_og_image_fallback');

        return [
            'og:type'                  => 'product',
            'og:title'                 => $title,
            'og:description'           => $desc,
            'og:url'                   => $canonical,
            'og:image'                 => $image,
            'og:site_name'             => $siteName,
            'product:price:amount'     => number_format((float)$row['price'], 2, '.', ''),
            'product:price:currency'   => (string)$this->config->get('config_currency'),
            'product:availability'     => $row['quantity'] > 0 ? 'in stock' : 'out of stock',
        ];
    }

    private function buildCategory(array $params, int $langId, string $langCode, string $siteName, string $canonical): array
    {
        $ids      = explode('_', $params['path'] ?? '0');
        $catId    = (int)end($ids);
        $row      = $this->db->query(
            "SELECT `name`, `image` FROM `" . DB_PREFIX . "category_description` cd
             JOIN `" . DB_PREFIX . "category` c USING(`category_id`)
             WHERE cd.category_id = " . $catId . " AND cd.language_id = " . $langId . " LIMIT 1"
        )->row;

        $metaData = $this->meta->render('category', $catId, $langId);
        $image    = !empty($row['image']) ? (defined('HTTP_CATALOG') ? HTTP_CATALOG : '') . 'image/' . $row['image'] : '';
        $image    = $image ?: (string)$this->config->get('module_oc_kit_seo_core_og_image_fallback');

        return [
            'og:type'        => 'website',
            'og:title'       => $metaData->title,
            'og:description' => $metaData->description,
            'og:url'         => $canonical,
            'og:image'       => $image,
            'og:site_name'   => $siteName,
        ];
    }

    private function buildHome(string $siteName, string $langCode, string $canonical): array
    {
        $locale = str_replace('-', '_', $langCode);
        return [
            'og:type'      => 'website',
            'og:site_name' => $siteName,
            'og:url'       => $canonical,
            'og:locale'    => $locale,
        ];
    }

    private function buildGeneric(string $route, array $params, int $langId, string $langCode, string $siteName, string $canonical): array
    {
        return [
            'og:type'      => 'website',
            'og:site_name' => $siteName,
            'og:url'       => $canonical,
        ];
    }

    private function ogTitle(string $langCode, array $vars): string
    {
        $tpl = (string)$this->config->get("module_oc_kit_seo_core_og_title_tpl_{$langCode}");
        return $tpl ? $this->fill($tpl, $vars) : '';
    }

    private function ogDesc(string $langCode, array $vars): string
    {
        $tpl = (string)$this->config->get("module_oc_kit_seo_core_og_desc_tpl_{$langCode}");
        return $tpl ? $this->fill($tpl, $vars) : '';
    }

    private function fill(string $tpl, array $vars): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn($m) => (string)($vars[$m[1]] ?? ''), $tpl);
    }

    private function getProductImage(int $productId): string
    {
        $row = $this->db->query(
            "SELECT `image` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $productId . " LIMIT 1"
        )->row;
        if (empty($row['image'])) return '';
        $catalog = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        return rtrim($catalog, '/') . '/image/' . $row['image'];
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    private function getLangCode(int $languageId): string
    {
        static $map = [];
        if (!isset($map[$languageId])) {
            $row = $this->db->query(
                "SELECT `code` FROM `" . DB_PREFIX . "language` WHERE `language_id` = " . $languageId . " LIMIT 1"
            )->row;
            $map[$languageId] = explode('-', strtolower($row['code'] ?? 'uk'))[0];
        }
        return $map[$languageId];
    }
}
