<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

use OcKit\ContentBlocks\ContentBlocks;

class ModelExtensionModuleOcKitContentBlocks extends Model
{
    private ?ContentBlocks $lib = null;

    private function getLib(): ContentBlocks
    {
        if ($this->lib === null) {
            $this->lib = new ContentBlocks($this->registry);
        }
        return $this->lib;
    }

    public function install(): void
    {
        $this->getLib()->install();
    }

    public function uninstall(): void
    {
        $this->getLib()->uninstall();
    }

    public function getBlocks(string $pageRoute, int $pageId): array
    {
        return $this->getLib()->getBlocks($pageRoute, $pageId);
    }

    public function saveBlocks(array $data): array
    {
        return $this->getLib()->saveBlocks($data);
    }

    public function getGlobalBlocks(): array
    {
        return $this->getLib()->getGlobalBlocks();
    }

    public function getBlock(int $blockId): \OcKit\ContentBlocks\Dto\BlockDto
    {
        return $this->getLib()->getBlock($blockId);
    }

    public function duplicateBlock(int $blockId): int
    {
        return $this->getLib()->duplicateBlock($blockId);
    }

    public function deleteBlock(int $blockId): void
    {
        $this->getLib()->deleteBlock($blockId);
    }

    public function removePageBlocks(string $pageRoute, int $pageId): void
    {
        $this->getLib()->removePageBlocks($pageRoute, $pageId);
    }

    public function getTypes(?array $enabledTypes = null): array
    {
        return $this->getLib()->getTypes($enabledTypes);
    }

    public function getElementTypes(): array
    {
        return $this->getLib()->getElementTypes();
    }

    public function getTemplates(string $blockType = ''): array
    {
        return $this->getLib()->getTemplates($blockType);
    }

    public function getTemplate(int $templateId): ?array
    {
        return $this->getLib()->getTemplate($templateId);
    }

    public function saveTemplate(string $name, string $blockType, array $data): int
    {
        return $this->getLib()->saveTemplate($name, $blockType, $data);
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->getLib()->deleteTemplate($templateId);
    }

    public function getPresets(): array
    {
        return $this->getLib()->getPresets();
    }

    public function savePreset(int $presetId, string $name, string $classes, int $sortOrder = 0, string $group = ''): int
    {
        return $this->getLib()->savePreset($presetId, $name, $classes, $sortOrder, $group);
    }

    public function deletePreset(int $presetId): void
    {
        $this->getLib()->deletePreset($presetId);
    }

    public function resetPresets(): void
    {
        $this->getLib()->resetPresets();
    }

    public function searchProducts(string $query, int $limit = 20): array
    {
        return $this->getLib()->searchProducts($query, $limit);
    }

    public function searchCategories(string $query, int $limit = 20): array
    {
        return $this->getLib()->searchCategories($query, $limit);
    }

    public function searchArticles(string $query, string $blogType = 'default', int $limit = 20): array
    {
        return $this->getLib()->searchArticles($query, $blogType, $limit);
    }

    public function translateBlock(array $blockData, string $targetLang, int $targetLangId): array
    {
        return $this->getLib()->translateBlock($blockData, $targetLang, $targetLangId);
    }

    /**
     * Migrate data from Simple Blocks to Content Blocks Pro.
     * Called from migrate AJAX controller.
     */
    public function migrateFromSimpleBlocks(): array
    {
        $result = ['migrated' => 0, 'errors' => []];

        // Check if old tables exist
        $check = $this->db->query(
            "SHOW TABLES LIKE '" . DB_PREFIX . "simple_blocks_blocks'"
        );
        if (!$check->num_rows) {
            $result['errors'][] = 'Simple Blocks tables not found';
            return $result;
        }

        // Migrate blocks
        $oldBlocks = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "simple_blocks_blocks` ORDER BY `blockId` ASC"
        )->rows;

        foreach ($oldBlocks as $old) {
            try {
                // Normalize Simple Blocks page route to OC admin route (CB lookup format).
                // SB historically stored shorthand like "information", "product", "category",
                // "manufacturer". CB queries by the full admin route ("catalog/information").
                $normalizedRoute = $this->normalizeSimpleBlocksRoute((string)($old['pageRoute'] ?? ''));

                // Map old camelCase columns to new snake_case
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "kit_cb_blocks`
                     (`block_id`, `page_route`, `page_id`, `type`, `name`, `theme`,
                      `status`, `sort_order`, `custom_class`, `custom_css`, `params`, `is_global`, `date_added`)
                     VALUES (
                        '" . (int)$old['blockId'] . "',
                        '" . $this->db->escape($normalizedRoute) . "',
                        '" . (int)$old['pageId'] . "',
                        '" . $this->db->escape($old['type']) . "',
                        '" . $this->db->escape($old['name']) . "',
                        '" . $this->db->escape($old['theme'] ?? 'default') . "',
                        '" . (int)$old['status'] . "',
                        '" . (int)$old['sortOrder'] . "',
                        '" . $this->db->escape($old['customClass'] ?? '') . "',
                        '" . $this->db->escape($old['customCss'] ?? '') . "',
                        '" . $this->db->escape($old['params'] ?? '') . "',
                        '0',
                        NOW()
                     )
                     ON DUPLICATE KEY UPDATE `block_id` = `block_id`"
                );
                $result['migrated']++;
            } catch (\Exception $e) {
                $result['errors'][] = 'Block #' . $old['blockId'] . ': ' . $e->getMessage();
            }
        }

        // Migrate rows + cols. SB stores customCss as JSON {class, backgroundImage,
        // backgroundColor}; CB renders custom_class from `params.custom_class` and
        // ignores custom_css for rows/cols. Lift the SB JSON keys into params.
        $this->migrateRowsOrCols(DB_PREFIX . 'simple_blocks_rows', DB_PREFIX . 'kit_cb_rows', 'rowId', 'row_id', false);
        $this->migrateRowsOrCols(DB_PREFIX . 'simple_blocks_cols', DB_PREFIX . 'kit_cb_cols', 'colId', 'col_id', true);

        // Migrate elements — map SB element types to CB equivalents inline
        // (SB had `textarea` and used block-type names like `images_carousel`,
        // `reviews` for child elements; CB has different element type names).
        $this->db->query(
            "INSERT IGNORE INTO `" . DB_PREFIX . "kit_cb_elements`
             (`element_id`, `block_id`, `col_id`, `type`, `data`, `params`, `custom_class`, `custom_css`, `preset_id`, `sort_order`)
             SELECT `elementId`, `blockId`, `colId`,
                    CASE `type`
                      WHEN 'textarea'        THEN 'text'
                      WHEN 'images_carousel' THEN 'carousel_image'
                      WHEN 'reviews'         THEN 'reviews_item'
                      WHEN 'faq'             THEN 'faq_item'
                      WHEN 'accordion'       THEN 'accordion_col'
                      WHEN 'product'         THEN 'product_item'
                      WHEN 'products_carousel' THEN 'carousel_product'
                      WHEN 'categories'      THEN 'categories_item'
                      WHEN 'blog_article'    THEN 'blog_article_item'
                      ELSE `type`
                    END,
                    `data`, `params`, '', `customCss`, 0, `sortOrder`
             FROM `" . DB_PREFIX . "simple_blocks_elements`"
        );

        // Backfill: rewrite any element types that came from earlier migrations
        // running the old (un-mapped) INSERT — idempotent UPDATE per source value.
        $elementTypeMap = [
            'textarea'         => 'text',
            'images_carousel'  => 'carousel_image',
            'reviews'          => 'reviews_item',
            'faq'              => 'faq_item',
            'accordion'        => 'accordion_col',
            'product'          => 'product_item',
            'products_carousel' => 'carousel_product',
            'categories'       => 'categories_item',
            'blog_article'     => 'blog_article_item',
        ];
        foreach ($elementTypeMap as $from => $to) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "kit_cb_elements`
                 SET `type` = '" . $this->db->escape($to) . "'
                 WHERE `type` = '" . $this->db->escape($from) . "'"
            );
        }

        // Backfill: rewrite any rows that still hold SB shorthand or SB frontend routes
        // to the OC admin route. Idempotent — only matches the exact source values.
        $rewriteMap = [
            'information'             => 'catalog/information',
            'product'                 => 'catalog/product',
            'category'                => 'catalog/category',
            'manufacturer'            => 'catalog/manufacturer',
            'information/information' => 'catalog/information',
            'product/product'         => 'catalog/product',
            'product/category'        => 'catalog/category',
            'product/manufacturer'    => 'catalog/manufacturer',
        ];
        foreach ($rewriteMap as $from => $to) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "kit_cb_blocks`
                 SET `page_route` = '" . $this->db->escape($to) . "'
                 WHERE `page_route` = '" . $this->db->escape($from) . "'"
            );
        }

        // Migrate stickers from JSON setting
        $this->migrateStickers();

        return $result;
    }

    /**
     * Map Simple Blocks pageRoute (frontend or shorthand) to the OC admin route
     * used by Content Blocks (`catalog/...`). Unknown values pass through unchanged.
     */
    /**
     * Pull rows/cols from SB and insert into CB, lifting SB customCss JSON
     * ({class, backgroundImage, backgroundColor}) into CB params.
     */
    private function migrateRowsOrCols(string $sbTable, string $cbTable, string $sbPk, string $cbPk, bool $isCol): void
    {
        $rows = $this->db->query("SELECT * FROM `{$sbTable}`")->rows;
        foreach ($rows as $r) {
            $params = json_decode((string)($r['params'] ?? ''), true);
            if (!is_array($params)) $params = [];

            $sbCss = json_decode((string)($r['customCss'] ?? ''), true);
            if (is_array($sbCss)) {
                if (!empty($sbCss['class']))           $params['custom_class'] = (string)$sbCss['class'];
                if (!empty($sbCss['backgroundImage'])) $params['bg_image']     = (string)$sbCss['backgroundImage'];
                if (!empty($sbCss['backgroundColor'])) $params['bg_color']     = (string)$sbCss['backgroundColor'];
            }

            $paramsJson = $this->db->escape(json_encode($params, JSON_UNESCAPED_UNICODE));
            $sortOrder  = (int)($r['sortOrder'] ?? 0);
            $sbId       = (int)$r[$sbPk];

            if ($isCol) {
                $this->db->query(
                    "INSERT IGNORE INTO `{$cbTable}` (`{$cbPk}`, `row_id`, `width`, `custom_css`, `params`, `sort_order`)
                     VALUES (
                        " . $sbId . ",
                        " . (int)($r['rowId'] ?? 0) . ",
                        0,
                        '',
                        '" . $paramsJson . "',
                        " . $sortOrder . "
                     )"
                );
            } else {
                $this->db->query(
                    "INSERT IGNORE INTO `{$cbTable}` (`{$cbPk}`, `block_id`, `custom_css`, `params`, `sort_order`)
                     VALUES (
                        " . $sbId . ",
                        " . (int)($r['blockId'] ?? 0) . ",
                        '',
                        '" . $paramsJson . "',
                        " . $sortOrder . "
                     )"
                );
            }
        }
    }

    private function normalizeSimpleBlocksRoute(string $route): string
    {
        $route = trim($route);
        if ($route === '') return $route;

        $map = [
            // shorthand
            'information'           => 'catalog/information',
            'product'               => 'catalog/product',
            'category'              => 'catalog/category',
            'manufacturer'          => 'catalog/manufacturer',
            // frontend SB routes
            'information/information' => 'catalog/information',
            'product/product'         => 'catalog/product',
            'product/category'        => 'catalog/category',
            'product/manufacturer'    => 'catalog/manufacturer',
        ];

        if (isset($map[$route])) {
            return $map[$route];
        }

        // Already in canonical catalog/* form? Pass through silently.
        // Anything else means the SB install had a non-standard route — log
        // it so the migration result is auditable rather than silently broken.
        if (strpos($route, 'catalog/') !== 0) {
            $this->log->write('Content Blocks migration: unknown Simple Blocks route "' . $route . '" — passed through as-is');
        }
        return $route;
    }

    private function migrateStickers(): void
    {
        $stickersJson = $this->db->query(
            "SELECT `value` FROM `" . DB_PREFIX . "setting`
             WHERE `key` = 'simple_blocks_stickers' LIMIT 1"
        )->row['value'] ?? '';

        if (empty($stickersJson)) {
            return;
        }

        $stickers = json_decode($stickersJson, true);
        if (!is_array($stickers)) {
            return;
        }

        $sort = 0;
        foreach ($stickers as $sticker) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_cb_stickers`
                 (`sort_order`, `status`, `color`, `bg_color`)
                 VALUES ('" . (int)$sort . "', '1', '', '')"
            );
            $stickerId = (int)$this->db->getLastId();

            if (!empty($sticker['text']) && is_array($sticker['text'])) {
                foreach ($sticker['text'] as $langId => $text) {
                    $this->db->query(
                        "INSERT IGNORE INTO `" . DB_PREFIX . "kit_cb_sticker_description`
                         (`sticker_id`, `language_id`, `text`)
                         VALUES ('" . (int)$stickerId . "', '" . (int)$langId . "', '" . $this->db->escape($text) . "')"
                    );
                }
            }
            $sort++;
        }
    }
}
