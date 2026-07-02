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
 * Generates per-image `alt` and `title` for product gallery entries from a
 * configurable mask. Called once per product page render. Outputs into the
 * shared `$data` array so the theme template can pick the values up.
 *
 * Mask syntax (same as MetaTemplateEngine):
 *   - `{name}`         — product name
 *   - `{category}`     — primary category name
 *   - `{price}`        — formatted price
 *   - `{special}`      — special price (empty when none)
 *   - `{sort_order}`   — 1-based image index
 *   - `{sku}`          — SKU
 *   - `{model}`        — model
 *   - `{manufacturer}` / `{brand}` — manufacturer name
 *   - `{store_name}`, `{year}`
 *   - `{{#if special}}-${special}{{/if}}` — conditional block
 *
 * Config keys: `module_oc_kit_seo_core_image_alt_tpl_{lcode}`,
 *              `module_oc_kit_seo_core_image_title_tpl_{lcode}`.
 *
 * The applier writes:
 *   $data['scf_image_alt_main'],   $data['scf_image_title_main']    — main image (sort_order=1)
 *   $data['scf_image_alts'][i],    $data['scf_image_titles'][i]     — additional gallery images
 *   $data['images'][i]['alt']/['title']                              — direct override
 *
 * Theme integration: replace `alt="<?= $heading_title ?>"` with
 * `alt="<?= $image['alt'] ?? $heading_title ?>"` (or use Twig equivalent).
 */
class ProductImageMetaApplier
{
    private $db;
    private $config;

    public function __construct($db, $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Apply alt/title masks to a product's gallery in $data.
     * Safe to call even when masks are empty (no-op).
     */
    public function apply(array &$data, int $productId, int $languageId, int $storeId = 0): void
    {
        $lcode = $this->getLangCode($languageId);
        $altTpl   = (string)$this->config->get('module_oc_kit_seo_core_image_alt_tpl_'   . $lcode);
        $titleTpl = (string)$this->config->get('module_oc_kit_seo_core_image_title_tpl_' . $lcode);

        if ($altTpl === '' && $titleTpl === '') return; // nothing to do

        $vars = $this->getProductVars($productId, $languageId);
        if (!$vars) return;

        // Main image (popup/thumb, sort_order = 1)
        $mainVars = $vars + ['sort_order' => '1'];
        if ($altTpl   !== '') $data['scf_image_alt_main']   = $this->render($altTpl,   $mainVars);
        if ($titleTpl !== '') $data['scf_image_title_main'] = $this->render($titleTpl, $mainVars);

        // Gallery (additional images). OC stores them in $data['images'] as
        // [popup, thumb] tuples — we inject alt/title alongside.
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $i => &$img) {
                if (!is_array($img)) continue;
                $imgVars = $vars + ['sort_order' => (string)($i + 2)]; // main is 1, gallery starts at 2
                if ($altTpl   !== '') $img['alt']   = $this->render($altTpl,   $imgVars);
                if ($titleTpl !== '') $img['title'] = $this->render($titleTpl, $imgVars);
            }
            unset($img);
        }
    }

    /**
     * Fetch all template variables for a product in one shot.
     */
    private function getProductVars(int $productId, int $languageId): array
    {
        $row = $this->db->query(
            "SELECT pd.`name`, p.`model`, p.`sku`, p.`price`, p.`manufacturer_id`,
                    (SELECT `price` FROM `" . DB_PREFIX . "product_special`
                     WHERE `product_id` = p.`product_id` AND `customer_group_id` = 1
                       AND ((`date_start` = '0000-00-00' OR `date_start` < NOW())
                       AND  (`date_end`   = '0000-00-00' OR `date_end`   > NOW()))
                     ORDER BY `priority` ASC, `price` ASC LIMIT 1) AS special
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
                ON pd.product_id = p.product_id AND pd.language_id = " . (int)$languageId . "
             WHERE p.product_id = " . (int)$productId . " LIMIT 1"
        )->row;
        if (!$row) return [];

        $vars = [
            'name'         => (string)$row['name'],
            'sku'          => (string)$row['sku'],
            'model'        => (string)$row['model'],
            'price'        => $this->formatPrice((float)$row['price']),
            'special'      => $row['special'] !== null ? $this->formatPrice((float)$row['special']) : '',
            'store_name'   => (string)$this->config->get('config_name'),
            'year'         => date('Y'),
        ];

        // Primary category name (main_category = 1)
        $cat = $this->db->query(
            "SELECT cd.name FROM `" . DB_PREFIX . "product_to_category` pc
             JOIN `" . DB_PREFIX . "category_description` cd
                ON cd.category_id = pc.category_id AND cd.language_id = " . (int)$languageId . "
             WHERE pc.product_id = " . (int)$productId . "
             ORDER BY pc.main_category DESC LIMIT 1"
        )->row;
        $vars['category'] = (string)($cat['name'] ?? '');

        // Manufacturer name (alias: brand, manufacturer)
        if ((int)$row['manufacturer_id'] > 0) {
            $brand = $this->db->query(
                "SELECT `name` FROM `" . DB_PREFIX . "manufacturer`
                 WHERE manufacturer_id = " . (int)$row['manufacturer_id'] . " LIMIT 1"
            )->row;
            $brandName = (string)($brand['name'] ?? '');
            $vars['brand']        = $brandName;
            $vars['manufacturer'] = $brandName;
        } else {
            $vars['brand'] = $vars['manufacturer'] = '';
        }

        return $vars;
    }

    /**
     * Render a mask with {var} substitution and {{#if var}}…{{/if}} blocks.
     */
    private function render(string $template, array $vars): string
    {
        // 1) Conditional blocks
        $template = preg_replace_callback(
            '/\{\{#if\s+(\w+)\s*\}\}(.*?)\{\{\/if\}\}/s',
            function (array $m) use ($vars): string {
                $v = $vars[$m[1]] ?? '';
                $truthy = !($v === '' || $v === null || $v === '0' || $v === 0 || $v === false);
                return $truthy ? $m[2] : '';
            },
            $template
        );
        // 2) Plain {var}
        return preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($vars): string {
            return (string)($vars[$m[1]] ?? '');
        }, $template);
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', ' ');
    }

    private function getLangCode(int $languageId): string
    {
        static $map = [];
        if (!isset($map[$languageId])) {
            $row = $this->db->query(
                "SELECT `code` FROM `" . DB_PREFIX . "language`
                 WHERE language_id = " . (int)$languageId . " LIMIT 1"
            )->row;
            $code = $row['code'] ?? 'uk';
            $map[$languageId] = explode('-', strtolower($code))[0];
        }
        return $map[$languageId];
    }
}
