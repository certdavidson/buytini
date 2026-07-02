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
 * Generates SEO URL keywords from entity data using configurable templates.
 *
 * Templates use {placeholders} filled from the entity data array.
 * Built-in sanitisation: lowercase, transliteration → slug.
 *
 * Config keys:
 *   module_oc_kit_seo_core_mask_product      e.g. "{name}"
 *   module_oc_kit_seo_core_mask_category     e.g. "{name}"
 *   module_oc_kit_seo_core_mask_manufacturer e.g. "{name}"
 *   module_oc_kit_seo_core_mask_information  e.g. "{name}"
 */
class UrlMaskEngine
{
    private const DEFAULTS = [
        'product'      => '{name}',
        'category'     => '{name}',
        'manufacturer' => '{name}',
        'information'  => '{name}',
    ];

    private $config;
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Generate a keyword for an entity.
     *
     * @param string $type   product|category|manufacturer|information
     * @param array  $data   Associative array with entity fields (name, model, sku, …)
     */
    public function generate(string $type, array $data): string
    {
        $template = (string)($this->config->get('module_oc_kit_seo_core_mask_' . $type) ?: (self::DEFAULTS[$type] ?? '{name}'));

        $keyword = preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($data): string {
            return (string)($data[$m[1]] ?? '');
        }, $template);

        return $this->slugify($keyword);
    }

    /**
     * Convert a raw string to a URL-safe slug.
     * Handles Cyrillic, Latin, diacritics.
     */
    public function slugify(string $text): string
    {
        // Cyrillic transliteration table
        static $tr = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo',
            'є'=>'ie','ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','і'=>'i','ї'=>'yi',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
            'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch',
            'ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu',
            'я'=>'ya',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo',
            'Є'=>'Ie','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'J','І'=>'I','Ї'=>'Yi',
            'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R',
            'С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch',
            'Ш'=>'Sh','Щ'=>'Shch','Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'Yu',
            'Я'=>'Ya',
        ];

        $text = strtr($text, $tr);
        $text = mb_strtolower($text, 'UTF-8');
        // Replace any non-alphanumeric run with a single dash
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    /**
     * Default mask per entity type — used when admin hasn't customised one.
     * @return array<string,string>
     */
    public function getDefaultMasks(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Available placeholders with human-readable descriptions for UI help.
     * @return array<string,string>
     */
    public function getPlaceholders(): array
    {
        return [
            '{name}'         => 'Назва сутності (з опису товару/категорії/тощо)',
            '{name-slug}'    => 'Транслітерований slug назви',
            '{category}'     => 'Назва батьківської категорії (для товарів)',
            '{category-slug}'=> 'Slug батьківської категорії',
            '{brand}'        => 'Назва виробника (для товарів)',
            '{brand-slug}'   => 'Slug виробника',
            '{sku}'          => 'Артикул товару (model)',
            '{id}'           => 'Числовий ID сутності',
        ];
    }

    /**
     * Validate a mask template. Returns ['ok' => bool, 'errors' => string[]].
     */
    public function validateMask(string $mask): array
    {
        $errors = [];

        if ($mask === '') {
            $errors[] = 'Маска не може бути порожньою';
            return ['ok' => false, 'errors' => $errors];
        }

        // All placeholders must be from the known list
        $allowed = array_map(static fn(string $p) => trim($p, '{}'), array_keys($this->getPlaceholders()));
        if (preg_match_all('/\{([\w-]+)\}/', $mask, $m)) {
            foreach ($m[1] as $token) {
                if (!in_array($token, $allowed, true)) {
                    $errors[] = "Невідомий плейсхолдер: {{$token}}";
                }
            }
        }

        // Mask must produce at least one slug-able char besides static text
        if (!preg_match('/\{[\w-]+\}/', $mask)) {
            $errors[] = 'Маска має містити хоча б один плейсхолдер';
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }
}
