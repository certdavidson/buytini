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
 * Appends pagination suffixes to title/description/h1 when page > 1.
 *
 * Config keys (per language code, e.g. _uk):
 *   module_oc_kit_seo_core_pagination_title_suffix_uk     " — сторінка {page}"
 *   module_oc_kit_seo_core_pagination_desc_suffix_uk      " Сторінка {page}."
 *   module_oc_kit_seo_core_pagination_h1_suffix_uk        " — сторінка {page}"
 */
class PaginationMetaEngine
{
    private $config;
    private $db;
    public function __construct($config, $db) {
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Apply pagination suffix to title/description/h1 if page > 1.
     */
    public function applyPaginationSuffix(MetaData $meta, int $page, int $languageId): MetaData
    {
        if ($page <= 1) return $meta;

        $langCode = $this->getLangCode($languageId);
        $vars     = ['{page}' => (string)$page];

        $titleSuffix = (string)$this->config->get("module_oc_kit_seo_core_pagination_title_suffix_{$langCode}");
        $descSuffix  = (string)$this->config->get("module_oc_kit_seo_core_pagination_desc_suffix_{$langCode}");

        $title = $meta->title . ($titleSuffix ? strtr($titleSuffix, $vars) : '');
        $desc  = $meta->description . ($descSuffix ? strtr($descSuffix, $vars) : '');

        $result = $meta->withTitle($title)->withDescription($desc);

        if (SeoCore::supportsNativeH1($this->db)) {
            $h1Suffix = (string)$this->config->get("module_oc_kit_seo_core_pagination_h1_suffix_{$langCode}");
            $h1       = $meta->h1 . ($h1Suffix ? strtr($h1Suffix, $vars) : '');
            $result   = $result->withH1($h1);
        }

        return $result;
    }

    private function getLangCode(int $languageId): string
    {
        static $map = [];
        if (!isset($map[$languageId])) {
            $row = $this->db->query(
                "SELECT `code` FROM `" . DB_PREFIX . "language` WHERE `language_id` = " . $languageId . " LIMIT 1"
            )->row;
            $code = $row['code'] ?? 'uk';
            $map[$languageId] = explode('-', strtolower($code))[0];
        }
        return $map[$languageId];
    }
}
