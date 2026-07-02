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
 * Generates standard Schema.org JSON-LD blocks.
 *
 * Config keys (all TINYINT toggles):
 *   module_oc_kit_seo_core_schema_product       (default 1)
 *   module_oc_kit_seo_core_schema_breadcrumb    (default 1)
 *   module_oc_kit_seo_core_schema_organization  (default 1, homepage only)
 *   module_oc_kit_seo_core_schema_article       (default 1)
 *   module_oc_kit_seo_core_schema_website       (default 1)
 *   module_oc_kit_seo_core_schema_min_reviews   (default 5)
 *   module_oc_kit_seo_core_schema_org_name
 *   module_oc_kit_seo_core_schema_org_logo
 *   module_oc_kit_seo_core_schema_org_phone
 *   module_oc_kit_seo_core_schema_org_email
 */
class SchemaBuilder
{
    private $config;
    private $db;
    private $document;
    private $url;
    public function __construct($config, $db, $document, $url = null) {
        $this->config = $config;
        $this->db = $db;
        $this->document = $document;
        $this->url = $url;
    }

    /**
     * Build and inject all applicable schema blocks for the current route.
     */
    public function injectForRoute(string $route, array $params, int $languageId): void
    {
        switch ($route) {
            case 'product/product':
                if ($this->enabled('product') && isset($params['product_id'])) {
                    $json = $this->buildProduct((int)$params['product_id'], $languageId);
                    if ($json) $this->inject($json);
                }
                // Breadcrumb on product page (Home → Cat → Subcat → Product)
                if ($this->enabled('breadcrumb') && isset($params['product_id'])) {
                    $json = $this->buildBreadcrumbForProduct((int)$params['product_id'], $languageId);
                    if ($json) $this->inject($json);
                }
                break;

            case 'product/category':
                if ($this->enabled('breadcrumb')) {
                    $json = $this->buildBreadcrumbFromCategory($params, $languageId);
                    if ($json) $this->inject($json);
                }
                break;

            case 'product/manufacturer/info':
                if ($this->enabled('organization') && isset($params['manufacturer_id'])) {
                    $json = $this->buildBrand((int)$params['manufacturer_id']);
                    if ($json) $this->inject($json);
                }
                break;

            case 'information/contact':
                if ($this->enabled('organization')) {
                    $this->inject($this->buildContactPage());
                }
                break;

            case 'common/home':
                if ($this->enabled('organization')) {
                    $this->inject($this->buildOrganization());
                }
                if ($this->enabled('website')) {
                    $this->inject($this->buildWebSite());
                }
                break;
        }
    }

    // ─── Public schema builders ───────────────────────────────────────────────

    public function buildProduct(int $productId, int $languageId): string
    {
        $row = $this->db->query(
            "SELECT p.*, pd.name, pd.description, m.name AS brand_name
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON pd.product_id = p.product_id AND pd.language_id = " . $languageId . "
             LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = p.manufacturer_id
             WHERE p.product_id = " . $productId . " LIMIT 1"
        )->row;

        if (!$row) return '';

        $catalog = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string)$row['name'],
            'description' => mb_substr(strip_tags($row['description'] ?? ''), 0, 500, 'UTF-8'),
            'sku'         => (string)$row['model'],
        ];

        if (!empty($row['image'])) {
            $schema['image'] = [rtrim($catalog, '/') . '/image/' . $row['image']];
        }

        if (!empty($row['brand_name'])) {
            $schema['brand'] = ['@type' => 'Brand', 'name' => $row['brand_name']];
        }

        $schema['offers'] = [
            '@type'         => 'Offer',
            'price'         => number_format((float)$row['price'], 2, '.', ''),
            'priceCurrency' => (string)$this->config->get('config_currency'),
            'availability'  => (int)$row['quantity'] > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url'           => $this->currentUrl(),
        ];

        $minReviews = (int)($this->config->get('module_oc_kit_seo_core_schema_min_reviews') ?: 5);
        $rating     = $this->getProductRating($productId, $minReviews);
        if ($rating) {
            $schema['aggregateRating'] = $rating;
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function buildBreadcrumb(array $breadcrumbs): string
    {
        if (empty($breadcrumbs)) return '';

        $items = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => (string)($crumb['name'] ?? ''),
                'item'     => (string)($crumb['url']  ?? ''),
            ];
        }

        return json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function buildOrganization(): string
    {
        $cfg = function (string $k): string {
            return (string)$this->config->get('module_oc_kit_seo_core_schema_' . $k);
        };

        $type    = $cfg('org_type') ?: 'Organization';   // Organization | LocalBusiness | OnlineStore | Store | Restaurant
        $name    = $cfg('org_name') ?: (string)$this->config->get('config_name');
        $logo    = $cfg('org_logo');
        $phone   = $cfg('org_phone');
        $email   = $cfg('org_email');
        $foundingDate = $cfg('org_founding_date');
        $priceRange   = $cfg('org_price_range');
        $vatId   = $cfg('org_vat_id');

        $url = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $type,
            'name'     => $name,
            'url'      => rtrim($url, '/'),
        ];
        if ($logo)         $schema['logo']        = $logo;
        if ($foundingDate) $schema['foundingDate'] = $foundingDate;
        if ($vatId)        $schema['vatID']       = $vatId;
        // priceRange — defined on LocalBusiness (inherited by all subtypes)
        // and OnlineStore. Skip only purely abstract / non-local Org types.
        $nonLocal = [
            'Organization','OnlineBusiness','Corporation','NGO','Brand',
            'EducationalOrganization','Preschool','School','College','University',
            'Library','Museum','NewsMediaOrganization','GovernmentOrganization',
            'GovernmentOffice','SportsOrganization','SportsTeam','MedicalOrganization',
            'ChildCare',
        ];
        if ($priceRange && !in_array($type, $nonLocal, true)) {
            $schema['priceRange'] = $priceRange;
        }

        // PostalAddress
        $street   = $cfg('org_street');
        $locality = $cfg('org_locality');
        $region   = $cfg('org_region');
        $postal   = $cfg('org_postal_code');
        $country  = $cfg('org_country');
        if ($street || $locality || $postal || $country) {
            $address = ['@type' => 'PostalAddress'];
            if ($street)   $address['streetAddress']   = $street;
            if ($locality) $address['addressLocality'] = $locality;
            if ($region)   $address['addressRegion']   = $region;
            if ($postal)   $address['postalCode']      = $postal;
            if ($country)  $address['addressCountry']  = $country;
            $schema['address'] = $address;
        }

        // GeoCoordinates (LocalBusiness/Restaurant make most use of this)
        $lat = $cfg('org_geo_lat');
        $lon = $cfg('org_geo_lon');
        if ($lat !== '' && $lon !== '') {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$lat,
                'longitude' => (float)$lon,
            ];
        }

        // openingHoursSpecification — admin enters lines like "Mo-Fr 09:00-18:00"
        $hours = $this->parseOpeningHours($cfg('org_opening_hours'));
        if ($hours) $schema['openingHoursSpecification'] = $hours;

        // sameAs — list of profile URLs (one per line)
        $sameAs = $this->splitLines($cfg('org_same_as'));
        if ($sameAs) $schema['sameAs'] = $sameAs;

        // Founders — list of names (one per line)
        $founders = $this->splitLines($cfg('org_founders'));
        if ($founders) {
            $schema['founder'] = array_map(function ($n) {
                return ['@type' => 'Person', 'name' => $n];
            }, $founders);
        }

        // ContactPoint (multilingual support — admin can add language list comma-separated)
        if ($phone || $email) {
            $contact = ['@type' => 'ContactPoint', 'contactType' => 'customer service'];
            if ($phone) $contact['telephone'] = $phone;
            if ($email) $contact['email']     = $email;
            $langsRaw = $cfg('org_contact_languages');
            if ($langsRaw !== '') {
                $langs = preg_split('/[\r\n,]+/', $langsRaw) ?: [];
                $langs = array_values(array_filter(array_map('trim', $langs), 'strlen'));
                if ($langs) $contact['availableLanguage'] = $langs;
            }
            $schema['contactPoint'] = $contact;
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Split a textarea into trimmed non-empty lines. */
    private function splitLines(string $raw): array
    {
        if ($raw === '') return [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        $out = [];
        foreach ($lines as $l) {
            $t = trim($l);
            if ($t !== '') $out[] = $t;
        }
        return $out;
    }

    /**
     * Parse human-readable opening hours into Schema.org OpeningHoursSpecification.
     * Accepts lines like:
     *   Mo-Fr 09:00-18:00
     *   Sa 10:00-16:00
     *   Su closed
     * Returns array of OpeningHoursSpecification objects (one per line).
     */
    private function parseOpeningHours(string $raw): array
    {
        $lines = $this->splitLines($raw);
        $dayMap = [
            'Mo'=>'Monday','Tu'=>'Tuesday','We'=>'Wednesday','Th'=>'Thursday',
            'Fr'=>'Friday','Sa'=>'Saturday','Su'=>'Sunday',
        ];
        $expand = function (string $token) use ($dayMap): array {
            // "Mo-Fr" → all days from Monday to Friday in $dayMap order
            if (strpos($token, '-') === false) {
                return isset($dayMap[$token]) ? [$dayMap[$token]] : [];
            }
            [$a, $b] = array_map('trim', explode('-', $token, 2));
            $keys  = array_keys($dayMap);
            $iA = array_search($a, $keys, true);
            $iB = array_search($b, $keys, true);
            if ($iA === false || $iB === false || $iA > $iB) return [];
            $out = [];
            for ($i = $iA; $i <= $iB; $i++) $out[] = $dayMap[$keys[$i]];
            return $out;
        };

        $out = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 2) ?: [];
            if (count($parts) < 2) continue;
            $days = $expand($parts[0]);
            if (!$days) continue;
            $time = trim($parts[1]);
            if (strcasecmp($time, 'closed') === 0) continue;
            if (!preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $time, $m)) continue;
            $opens  = sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
            $closes = sprintf('%02d:%02d', (int)$m[3], (int)$m[4]);
            $out[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens'     => $opens,
                'closes'    => $closes,
            ];
        }
        return $out;
    }

    public function buildArticle(int $articleId, int $languageId): string
    {
        $row = $this->db->query(
            "SELECT ad.title, ad.description, a.date_added, a.date_modified, a.image
             FROM `" . DB_PREFIX . "article` a
             LEFT JOIN `" . DB_PREFIX . "article_description` ad
               ON ad.article_id = a.article_id AND ad.language_id = " . $languageId . "
             WHERE a.article_id = " . $articleId . " LIMIT 1"
        )->row;

        if (!$row) return '';

        $catalog = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');

        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => (string)$row['title'],
            'datePublished' => (string)$row['date_added'],
            'dateModified'  => (string)$row['date_modified'],
            'author'        => ['@type' => 'Organization', 'name' => (string)$this->config->get('config_name')],
        ];

        if (!empty($row['image'])) {
            $schema['image'] = rtrim($catalog, '/') . '/image/' . $row['image'];
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function buildWebSite(): string
    {
        $url      = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        $siteName = (string)$this->config->get('config_name');

        return json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $siteName,
            'url'             => rtrim($url, '/'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => rtrim($url, '/') . '/index.php?route=product/search&search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Queue JSON-LD for injection. Actual emission happens in catalog/view/theme/*_/template/common/header.twig
     * via an OCMOD patch that outputs DocumentExtra::render() into <head>.
     */
    public function inject(string $jsonLd): void
    {
        if (!$jsonLd) return;
        DocumentExtra::addJsonLd($jsonLd);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function enabled(string $type): bool
    {
        $key = "module_oc_kit_seo_core_schema_{$type}";
        $val = $this->config->get($key);
        return $val === null || (bool)$val; // default enabled
    }

    private function getProductRating(int $productId, int $minReviews): ?array
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS total, AVG(`rating`) AS avg_rating
             FROM `" . DB_PREFIX . "review`
             WHERE `product_id` = " . $productId . " AND `status` = 1"
        )->row;

        $total = (int)($row['total'] ?? 0);
        if ($total < $minReviews) return null;

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format((float)($row['avg_rating'] ?? 0), 1, '.', ''),
            'reviewCount' => $total,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }

    private function buildBreadcrumbFromCategory(array $params, int $languageId): string
    {
        if (!isset($params['path'])) return '';

        $ids         = explode('_', (string)$params['path']);
        $base        = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        $breadcrumbs = [['name' => (string)$this->config->get('config_name'), 'url' => rtrim($base, '/') . '/']];

        // Build incremental path so each breadcrumb URL points to its category page
        $accumulated = [];
        foreach ($ids as $catId) {
            $catId = (int)$catId;
            $accumulated[] = $catId;

            $name = $this->db->query(
                "SELECT `name` FROM `" . DB_PREFIX . "category_description`
                 WHERE `category_id` = {$catId} AND `language_id` = {$languageId} LIMIT 1"
            )->row['name'] ?? '';
            if (!$name) continue;

            $crumbUrl = $this->buildCategoryUrl($accumulated, $languageId, $base);
            $breadcrumbs[] = ['name' => $name, 'url' => $crumbUrl];
        }

        return $this->buildBreadcrumb($breadcrumbs);
    }

    private function buildBreadcrumbForProduct(int $productId, int $languageId): string
    {
        $base = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');

        // Find primary category chain
        $catRow = $this->db->query(
            "SELECT `category_id` FROM `" . DB_PREFIX . "product_to_category`
             WHERE `product_id` = " . (int)$productId . "
             ORDER BY `main_category` DESC LIMIT 1"
        )->row;

        $breadcrumbs = [['name' => (string)$this->config->get('config_name'), 'url' => rtrim($base, '/') . '/']];

        if (!empty($catRow)) {
            $chain = [];
            $cur   = (int)$catRow['category_id'];
            $guard = 0;
            while ($cur > 0 && $guard++ < 10) {
                array_unshift($chain, $cur);
                $row = $this->db->query("SELECT `parent_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` = {$cur} LIMIT 1")->row;
                if (!$row || (int)$row['parent_id'] === 0) break;
                $cur = (int)$row['parent_id'];
            }

            $accumulated = [];
            foreach ($chain as $catId) {
                $accumulated[] = $catId;
                $name = $this->db->query(
                    "SELECT `name` FROM `" . DB_PREFIX . "category_description`
                     WHERE `category_id` = {$catId} AND `language_id` = {$languageId} LIMIT 1"
                )->row['name'] ?? '';
                if ($name) {
                    $breadcrumbs[] = ['name' => $name, 'url' => $this->buildCategoryUrl($accumulated, $languageId, $base)];
                }
            }
        }

        // Final crumb: product itself
        $pname = $this->db->query(
            "SELECT `name` FROM `" . DB_PREFIX . "product_description`
             WHERE `product_id` = " . (int)$productId . " AND `language_id` = {$languageId} LIMIT 1"
        )->row['name'] ?? '';
        if ($pname) {
            $breadcrumbs[] = ['name' => $pname, 'url' => $this->currentUrl()];
        }

        return $this->buildBreadcrumb($breadcrumbs);
    }

    private function buildBrand(int $manufacturerId): string
    {
        $row = $this->db->query(
            "SELECT `name`, `image` FROM `" . DB_PREFIX . "manufacturer`
             WHERE `manufacturer_id` = " . (int)$manufacturerId . " LIMIT 1"
        )->row;
        if (!$row) return '';

        $base = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '');
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Brand',
            'name'     => (string)$row['name'],
            'url'      => $this->currentUrl(),
        ];
        if (!empty($row['image'])) {
            $schema['logo'] = rtrim($base, '/') . '/image/' . $row['image'];
        }
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function buildContactPage(): string
    {
        $name = (string)$this->config->get('config_name');
        $tel  = (string)$this->config->get('config_telephone');
        $mail = (string)$this->config->get('config_email');
        $addr = (string)$this->config->get('config_address');

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'ContactPage',
            'url'      => $this->currentUrl(),
            'name'     => $name . ' — ' . 'Contact',
        ];
        if ($tel || $mail) {
            $cp = ['@type' => 'ContactPoint', 'contactType' => 'customer service'];
            if ($tel)  $cp['telephone'] = $tel;
            if ($mail) $cp['email']     = $mail;
            $schema['contactPoint'] = $cp;
        }
        if ($addr) {
            $schema['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $addr];
        }
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build a clean category URL by walking through OC's url->link rewrite.
     * Returns absolute URL.
     */
    private function buildCategoryUrl(array $catIds, int $languageId, string $base): string
    {
        $path = implode('_', array_map('intval', $catIds));
        if ($this->url) {
            $link = $this->url->link('product/category', 'path=' . $path);
            return html_entity_decode($link, ENT_QUOTES, 'UTF-8');
        }
        return rtrim($base, '/') . '/index.php?route=product/category&path=' . $path;
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
