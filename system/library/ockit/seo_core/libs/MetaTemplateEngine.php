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

use OcKit\SeoCore\Dto\MetaData;
use OcKit\SeoCore\SeoCore;

/**
 * Renders title/description/H1 for an entity using a three-level priority:
 *   1. Manual override (oc_kit_seo_meta_override)
 *   2. Template from config with {placeholder} substitution
 *   3. Native OC meta_title / meta_description field
 *
 * Template variables:
 *   {name}        entity name
 *   {store_name}  store name
 *   {category}    primary category name (products only)
 *   {brand}       manufacturer name (products only)
 *   {price}       formatted price (products only)
 *   {sku}         model/sku (products only)
 *   {description} first 160 chars of description (stripped)
 *   {year}        current year
 *   {page}        page number
 *   {count}       product count in category
 */
class MetaTemplateEngine
{
    /** @var MetaRepository */
    private $repo;
    private $db;
    private $config;
    private ?AbTestEngine $abEngine = null;
    public function __construct(MetaRepository $repo, $db, $config) {
        $this->repo = $repo;
        $this->db = $db;
        $this->config = $config;
    }

    public function setAbTestEngine(AbTestEngine $engine): void {
        $this->abEngine = $engine;
    }

    /**
     * Render full MetaData for an entity.
     */
    public function render(string $type, int $entityId, int $languageId, int $storeId = 0, int $page = 1): MetaData
    {
        $override = $this->repo->getOverride($type, $entityId, $languageId, $storeId);

        // Level 1: full override
        if ($override && !empty($override['title'])) {
            return new MetaData(
                (string)$override['title'],
                (string)($override['description'] ?? ''),
                (string)($override['h1'] ?? ''),
                (string)($override['robots'] ?? ''),
                (string)($override['canonical'] ?? ''),
                (string)($override['og_title'] ?? ''),
                (string)($override['og_description'] ?? ''),
                (string)($override['og_image'] ?? '')
            );
        }

        $vars = $this->getEntityVars($type, $entityId, $languageId);
        // Make pagination available in templates. {page} expands to the current
        // page number; with {{#if page}}…{{/if}} blocks rendering only when on
        // a paginated view (page > 1). Page 1 stays "empty" so users can write
        // `{name}{{#if page}} — page {page}{{/if}}` without leakage on page 1.
        $vars['page'] = $page > 1 ? (string)$page : '';
        $h1Supported = SeoCore::supportsNativeH1($this->db);

        // Level 2: template from config
        $langCode = $this->getLangCode($languageId);
        $titleTpl = (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_title_tpl_{$langCode}");
        $descTpl  = (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_desc_tpl_{$langCode}");
        $h1Tpl    = $h1Supported ? (string)$this->config->get("module_oc_kit_seo_core_meta_{$type}_h1_tpl_{$langCode}") : '';

        // Template mode:
        //   'override' (default) → template always takes precedence over native OC fields
        //   'fallback'           → template only used when the native OC field is empty
        $mode = (string)($this->config->get('module_oc_kit_seo_core_meta_tpl_mode') ?: 'override');

        $nativeTitle = (string)($vars['native_title'] ?? '');
        $nativeDesc  = (string)($vars['native_desc']  ?? '');
        $nativeH1    = $h1Supported ? (string)($vars['native_h1'] ?? '') : '';
        $nativeName  = (string)($vars['name']         ?? '');

        if ($mode === 'fallback') {
            $title = $nativeTitle !== '' ? $nativeTitle : ($titleTpl ? $this->fill($titleTpl, $vars) : '');
            $desc  = $nativeDesc  !== '' ? $nativeDesc  : ($descTpl  ? $this->fill($descTpl,  $vars) : '');
            $h1    = $h1Supported ? ($nativeH1 !== '' ? $nativeH1 : ($h1Tpl ? $this->fill($h1Tpl, $vars) : $nativeName)) : '';
        } else {
            $title = $titleTpl ? $this->fill($titleTpl, $vars) : $nativeTitle;
            $desc  = $descTpl  ? $this->fill($descTpl,  $vars) : $nativeDesc;
            $h1    = $h1Supported ? ($h1Tpl ? $this->fill($h1Tpl, $vars) : ($nativeH1 !== '' ? $nativeH1 : $nativeName)) : '';
        }

        // Apply partial overrides (only h1 or description overridden)
        if ($override) {
            if (!empty($override['description'])) $desc = $override['description'];
            if ($h1Supported && !empty($override['h1'])) $h1 = $override['h1'];
        }

        // A/B test title rotation — overrides everything if active for this entity
        if ($this->abEngine !== null && $this->abTestEnabled() && $page <= 1) {
            $abTitle = $this->abEngine->pickTitle($type, $entityId, $languageId);
            if ($abTitle !== null && $abTitle !== '') {
                $title = $abTitle;
            }
        }

        return new MetaData(
            $this->truncateTitle($title),
            $this->truncateDescription($desc),
            $h1,
            (string)($override['robots']         ?? ''),
            (string)($override['canonical']       ?? ''),
            (string)($override['og_title']        ?? ''),
            (string)($override['og_description']  ?? ''),
            (string)($override['og_image']        ?? '')
        );
    }

    /**
     * Collect template variables for a given entity.
     * @return array<string,string>
     */
    public function getEntityVars(string $type, int $id, int $languageId): array
    {
        $vars = [
            'store_name'   => (string)$this->config->get('config_name'),
            'year'         => (string)date('Y'),
        ];

        // ocStore-style native meta_h1 column may or may not exist.
        $h1Supported = SeoCore::supportsNativeH1($this->db);
        $h1Select    = $h1Supported ? ', pd.meta_h1' : '';
        $h1SelectCat = $h1Supported ? ', cd.meta_h1' : '';
        $h1SelectInf = $h1Supported ? ', meta_h1' : '';

        switch ($type) {
            case 'product':
                $row = $this->db->query(
                    "SELECT pd.name, pd.meta_title, pd.meta_description{$h1Select}, pd.description,
                            p.model, p.price, p.tax_class_id
                     FROM `" . DB_PREFIX . "product` p
                     LEFT JOIN `" . DB_PREFIX . "product_description` pd
                       ON pd.product_id = p.product_id AND pd.language_id = " . $languageId . "
                     WHERE p.product_id = " . $id . " LIMIT 1"
                )->row;

                if (!$row) break;
                $vars['name']         = (string)$row['name'];
                $vars['sku']          = (string)$row['model'];
                $vars['price']        = $this->formatPrice((float)$row['price']);
                $vars['description']  = mb_substr(strip_tags($row['description'] ?? ''), 0, 160, 'UTF-8');
                $vars['native_title'] = (string)$row['meta_title'];
                $vars['native_desc']  = (string)$row['meta_description'];
                if ($h1Supported) $vars['native_h1'] = (string)($row['meta_h1'] ?? '');

                // Category and brand
                $cat = $this->db->query(
                    "SELECT cd.name FROM `" . DB_PREFIX . "product_to_category` pc
                     JOIN `" . DB_PREFIX . "category_description` cd
                       ON cd.category_id = pc.category_id AND cd.language_id = " . $languageId . "
                     WHERE pc.product_id = " . $id . " ORDER BY pc.main_category DESC LIMIT 1"
                )->row;
                $vars['category'] = (string)($cat['name'] ?? '');

                $brand = $this->db->query(
                    "SELECT m.name FROM `" . DB_PREFIX . "product` p
                     JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = p.manufacturer_id
                     WHERE p.product_id = " . $id . " LIMIT 1"
                )->row;
                $vars['brand']        = (string)($brand['name'] ?? '');
                $vars['manufacturer'] = $vars['brand']; // alias — preferred public name
                break;

            case 'category':
                $row = $this->db->query(
                    "SELECT cd.name, cd.meta_title, cd.meta_description{$h1SelectCat}
                     FROM `" . DB_PREFIX . "category_description` cd
                     WHERE cd.category_id = " . $id . " AND cd.language_id = " . $languageId . " LIMIT 1"
                )->row;
                if (!$row) break;
                $vars['name']         = (string)$row['name'];
                $vars['native_title'] = (string)$row['meta_title'];
                $vars['native_desc']  = (string)$row['meta_description'];
                if ($h1Supported) $vars['native_h1'] = (string)($row['meta_h1'] ?? '');

                $count = (int)$this->db->query(
                    "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product_to_category` WHERE `category_id` = " . $id
                )->row['cnt'];
                $vars['count'] = (string)$count;
                break;

            case 'manufacturer':
                $row = $this->db->query(
                    "SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $id . " LIMIT 1"
                )->row;
                $vars['name']         = (string)($row['name'] ?? '');
                $vars['native_title'] = '';
                $vars['native_desc']  = '';
                break;

            case 'information':
                $row = $this->db->query(
                    "SELECT `title`, `meta_title`, `meta_description`{$h1SelectInf}, `description`
                     FROM `" . DB_PREFIX . "information_description`
                     WHERE `information_id` = " . $id . " AND `language_id` = " . $languageId . " LIMIT 1"
                )->row;
                if (!$row) break;
                $vars['name']         = (string)$row['title'];
                $vars['native_title'] = (string)$row['meta_title'];
                $vars['native_desc']  = (string)$row['meta_description'];
                $vars['description']  = mb_substr(strip_tags($row['description'] ?? ''), 0, 160, 'UTF-8');
                if ($h1Supported) $vars['native_h1'] = (string)($row['meta_h1'] ?? '');
                break;

            case 'article':
                // Blog post (oc_article + oc_article_description). The article
                // module ships with most ocStore builds; if it isn't installed
                // the SELECT short-circuits to empty $row and falls back to
                // store-default meta.
                $row = $this->db->query(
                    "SELECT ad.`name`, ad.`meta_title`, ad.`meta_description`, ad.`description`,
                            ad.`meta_keyword`, ad.`meta_h1`
                     FROM `" . DB_PREFIX . "article_description` ad
                     WHERE ad.`article_id` = " . $id . " AND ad.`language_id` = " . $languageId . " LIMIT 1"
                )->row;
                if (!$row) break;
                $vars['name']         = (string)$row['name'];
                $vars['native_title'] = (string)$row['meta_title'];
                $vars['native_desc']  = (string)$row['meta_description'];
                $vars['native_keywords'] = (string)($row['meta_keyword'] ?? '');
                $vars['description']  = mb_substr(strip_tags($row['description'] ?? ''), 0, 160, 'UTF-8');
                if ($h1Supported || !empty($row['meta_h1'])) {
                    $vars['native_h1'] = (string)($row['meta_h1'] ?? '');
                }
                break;

            case 'blog_category':
                $row = $this->db->query(
                    "SELECT `name`, `description`
                     FROM `" . DB_PREFIX . "blog_category_description`
                     WHERE `blog_category_id` = " . $id . " AND `language_id` = " . $languageId . " LIMIT 1"
                )->row;
                if (!$row) break;
                $vars['name']         = (string)$row['name'];
                $vars['native_title'] = '';
                $vars['native_desc']  = '';
                $vars['description']  = mb_substr(strip_tags($row['description'] ?? ''), 0, 160, 'UTF-8');

                $count = (int)$this->db->query(
                    "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "article_to_blog_category`
                     WHERE `blog_category_id` = " . $id
                )->row['cnt'];
                $vars['count'] = (string)$count;
                break;
        }

        return $vars;
    }

    /** Truncate title to max 60 chars, not mid-word. */
    public function truncateTitle(string $title): string
    {
        return $this->truncate($title, 60);
    }

    /** Truncate description to max 160 chars, not mid-word. */
    public function truncateDescription(string $desc): string
    {
        return $this->truncate($desc, 160);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function abTestEnabled(): bool
    {
        return (bool)(int)$this->config->get('module_oc_kit_seo_core_ab_test_enabled');
    }

    /**
     * Render template with {var} interpolation and {{#if var}}…{{/if}} blocks.
     *
     * Syntax mirrors Custom Schema rules (Handlebars-lite):
     *   - `{var}`          — replaced with value or '' if missing/empty.
     *   - `{{#if page}}Page {page}{{/if}}` — emits inner block only if `page`
     *                       resolves to a truthy non-empty value.
     *   - Blocks support trivial nesting via lazy regex; whitespace inside is
     *     preserved.
     */
    private function fill(string $template, array $vars): string
    {
        // 1. Resolve {{#if var}}…{{/if}} blocks first (so inner {var} expand correctly).
        $template = preg_replace_callback(
            '/\{\{#if\s+(\w+)\s*\}\}(.*?)\{\{\/if\}\}/s',
            function (array $m) use ($vars): string {
                $key = $m[1];
                $val = $vars[$key] ?? '';
                $truthy = !($val === '' || $val === null || $val === '0' || $val === 0 || $val === false);
                return $truthy ? $m[2] : '';
            },
            $template
        );
        // 2. Plain {var} substitution.
        return preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($vars): string {
            return (string)($vars[$m[1]] ?? '');
        }, $template);
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $max) return $text;

        $truncated = mb_substr($text, 0, $max, 'UTF-8');
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        return $lastSpace !== false ? mb_substr($truncated, 0, $lastSpace, 'UTF-8') : $truncated;
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
                "SELECT `code` FROM `" . DB_PREFIX . "language` WHERE `language_id` = " . $languageId . " LIMIT 1"
            )->row;
            $code = $row['code'] ?? 'uk';
            // Take only the base code part before '-'
            $map[$languageId] = explode('-', strtolower($code))[0];
        }
        return $map[$languageId];
    }
}
