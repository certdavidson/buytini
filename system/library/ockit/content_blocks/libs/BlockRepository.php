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

namespace OcKit\ContentBlocks\Libs;

use OcKit\ContentBlocks\Dto\BlockDto;
use OcKit\ContentBlocks\Dto\RowDto;
use OcKit\ContentBlocks\Dto\ColDto;
use OcKit\ContentBlocks\Dto\ElementDto;
use OcKit\ContentBlocks\Exceptions\BlockNotFoundException;

/**
 * Handles all DB operations for content blocks.
 *
 * Tables managed:
 *   oc_kit_cb_blocks    — block metadata
 *   oc_kit_cb_rows      — rows (for grid/video/accordion)
 *   oc_kit_cb_cols      — columns inside rows
 *   oc_kit_cb_elements  — content elements
 */
class BlockRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Returns all blocks for a page, ordered by sort_order.
     *
     * @return BlockDto[]
     */
    public function getBlocks(string $pageRoute, int $pageId): array
    {
        // No status filter here — admin editor needs to see disabled blocks too.
        // Catalog rendering applies status=1 in the controller layer.
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `page_route` = '" . $this->db->escape($pageRoute) . "'
               AND `page_id` = '" . (int)$pageId . "'
             ORDER BY `sort_order` ASC"
        )->rows;

        $blocks = [];
        foreach ($rows as $row) {
            $blocks[] = $this->hydrateBlock($row);
        }
        return $blocks;
    }

    /**
     * Returns all global blocks (is_global=1), ordered by sort_order.
     *
     * @return BlockDto[]
     */
    public function getGlobalBlocks(): array
    {
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `is_global` = 1
             ORDER BY `sort_order` ASC"
        )->rows;

        $blocks = [];
        foreach ($rows as $row) {
            $blocks[] = $this->hydrateBlock($row);
        }
        return $blocks;
    }

    /**
     * Returns a single block by ID.
     *
     * @throws BlockNotFoundException
     */
    public function getBlock(int $blockId): BlockDto
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `block_id` = '" . (int)$blockId . "' LIMIT 1"
        )->row;

        if (!$row) {
            throw new BlockNotFoundException("Block #{$blockId} not found");
        }

        return $this->hydrateBlock($row);
    }

    // ─── Save ────────────────────────────────────────────────────────────────

    /**
     * Saves all blocks for a page (full replace strategy):
     * - Upserts each block
     * - Deletes sub-items then re-inserts
     * - Deletes blocks removed by user
     *
     * @param array $blocksData Raw block data from JS
     * @return int[] Ordered list of saved block IDs (new or existing)
     */
    public function saveBlocks(string $pageRoute, int $pageId, array $blocksData): array
    {
        $isGlobalPage = ($pageRoute === '_global');
        $savedIds     = [];

        // Wrap in a transaction so partial failures don't leave the page
        // half-saved (e.g. blocks updated but sub-items missing). The list of
        // existing block IDs is read INSIDE the transaction with FOR UPDATE so
        // two admins editing the same page in parallel can't race-delete each
        // other's newly-added blocks.
        $this->db->query('START TRANSACTION');
        try {
            $existingIds = $this->getBlockIdsByPage($pageRoute, $pageId, true);
            foreach ($blocksData as $blockData) {
                if ($isGlobalPage) {
                    $blockData['is_global'] = 1;
                }
                $blockId = (int)($blockData['block_id'] ?? 0);

                if ($blockId > 0 && isset($existingIds[$blockId])) {
                    $this->updateBlock($blockId, $blockData);
                    if ($isGlobalPage) {
                        $this->db->query(
                            "UPDATE `" . DB_PREFIX . "kit_cb_blocks`
                             SET `is_global` = 1
                             WHERE `block_id` = '" . (int)$blockId . "'"
                        );
                    }
                    unset($existingIds[$blockId]);
                } else {
                    $blockId = $this->insertBlock($pageRoute, $pageId, $blockData);
                }

                $savedIds[] = $blockId;

                // Replace all sub-items (delete + reinsert)
                $this->deleteSubItems($blockId);
                $this->insertSubItems($blockId, $blockData);
            }

            // Delete blocks removed by the user
            foreach (array_keys($existingIds) as $removedId) {
                $this->deleteBlock((int)$removedId);
            }

            $this->db->query('COMMIT');
        } catch (\Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }

        return $savedIds;
    }

    /**
     * Duplicates a block (deep copy of all rows/cols/elements).
     * The duplicate is appended after the original (sort_order + 1).
     *
     * @return int New block_id
     */
    public function duplicateBlock(int $blockId): int
    {
        $original = $this->getBlock($blockId);

        // Insert new block record
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_cb_blocks`
             (`page_route`, `page_id`, `type`, `name`, `theme`, `status`,
              `sort_order`, `custom_class`, `custom_css`, `params`, `is_global`, `date_added`)
             SELECT `page_route`, `page_id`, `type`,
                    CONCAT(`name`, ' (copy)'),
                    `theme`, `status`,
                    `sort_order` + 1,
                    `custom_class`, `custom_css`, `params`, `is_global`,
                    NOW()
             FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `block_id` = '" . (int)$blockId . "'"
        );
        $newBlockId = (int)$this->db->getLastId();
        if ($newBlockId <= 0) {
            throw new \RuntimeException('Failed to duplicate block — source block not found or insert failed');
        }

        // Copy rows
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_rows`
             WHERE `block_id` = '" . (int)$blockId . "'
             ORDER BY `sort_order` ASC"
        )->rows;

        foreach ($rows as $row) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_cb_rows`
                 (`block_id`, `custom_css`, `params`, `sort_order`)
                 VALUES ('" . (int)$newBlockId . "',
                         '" . $this->db->escape($row['custom_css']) . "',
                         '" . $this->db->escape($row['params']) . "',
                         '" . (int)$row['sort_order'] . "')"
            );
            $newRowId = (int)$this->db->getLastId();

            // Copy cols
            $cols = $this->db->query(
                "SELECT * FROM `" . DB_PREFIX . "kit_cb_cols`
                 WHERE `row_id` = '" . (int)$row['row_id'] . "'
                 ORDER BY `sort_order` ASC"
            )->rows;

            foreach ($cols as $col) {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "kit_cb_cols`
                     (`row_id`, `width`, `custom_css`, `params`, `sort_order`)
                     VALUES ('" . (int)$newRowId . "',
                             '" . (int)$col['width'] . "',
                             '" . $this->db->escape($col['custom_css']) . "',
                             '" . $this->db->escape($col['params']) . "',
                             '" . (int)$col['sort_order'] . "')"
                );
                $newColId = (int)$this->db->getLastId();

                // Copy elements for this col
                $this->copyElements($blockId, $newBlockId, (int)$col['col_id'], $newColId);
            }
        }

        // Copy flat elements (col_id = 0)
        $this->copyElements($blockId, $newBlockId, 0, 0);

        return $newBlockId;
    }

    /**
     * Deletes a block and all its sub-items (cascade handles rows/cols).
     */
    public function deleteBlock(int $blockId): void
    {
        // Delete elements first (FK is on block_id, not cascaded from rows)
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_elements`
             WHERE `block_id` = '" . (int)$blockId . "'"
        );
        // Delete rows (cascades to cols via FK)
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_rows`
             WHERE `block_id` = '" . (int)$blockId . "'"
        );
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `block_id` = '" . (int)$blockId . "'"
        );
    }

    /**
     * Deletes all blocks for a page (used when deleting a product/category).
     */
    public function deleteBlocksByPage(string $pageRoute, int $pageId): void
    {
        $ids = $this->getBlockIdsByPage($pageRoute, $pageId);
        foreach (array_keys($ids) as $blockId) {
            $this->deleteBlock((int)$blockId);
        }
    }

    // ─── DB Tables ───────────────────────────────────────────────────────────

    public function createTables(): void
    {
        $p = DB_PREFIX;

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_blocks` (
                `block_id`     INT(10)       NOT NULL AUTO_INCREMENT,
                `page_route`   VARCHAR(100)  NOT NULL DEFAULT '',
                `page_id`      INT(10)       NOT NULL DEFAULT 0,
                `type`         VARCHAR(50)   NOT NULL DEFAULT '',
                `name`         VARCHAR(255)  NOT NULL DEFAULT '',
                `theme`        VARCHAR(100)  NOT NULL DEFAULT 'default',
                `status`       TINYINT(1)    NOT NULL DEFAULT 1,
                `sort_order`   INT(10)       NOT NULL DEFAULT 0,
                `custom_class` VARCHAR(255)  NOT NULL DEFAULT '',
                `custom_css`   TEXT          NOT NULL,
                `params`       TEXT          NOT NULL,
                `is_global`    TINYINT(1)    NOT NULL DEFAULT 0,
                `date_added`   DATETIME      NOT NULL,
                PRIMARY KEY (`block_id`),
                KEY `idx_page`     (`page_route`, `page_id`, `sort_order`),
                KEY `idx_global`   (`is_global`, `sort_order`),
                KEY `idx_type`     (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migration: backfill the new indices on existing installations.
        // INFORMATION_SCHEMA check is cheaper than a failed CREATE INDEX.
        foreach (['idx_global', 'idx_type'] as $idx) {
            $exists = (int)($this->db->query(
                "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = '{$p}kit_cb_blocks'
                   AND INDEX_NAME   = '" . $this->db->escape($idx) . "'"
            )->row['cnt'] ?? 0);
            if ($exists === 0) {
                $cols = $idx === 'idx_global' ? '`is_global`, `sort_order`' : '`type`';
                $this->db->query("ALTER TABLE `{$p}kit_cb_blocks` ADD INDEX `{$idx}` ({$cols})");
            }
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_rows` (
                `row_id`     INT(11) NOT NULL AUTO_INCREMENT,
                `block_id`   INT(11) NOT NULL,
                `custom_css` TEXT    NOT NULL,
                `params`     TEXT    NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`row_id`),
                KEY `idx_block` (`block_id`),
                CONSTRAINT `{$p}kit_cb_rows_fk`
                    FOREIGN KEY (`block_id`)
                    REFERENCES `{$p}kit_cb_blocks` (`block_id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_cols` (
                `col_id`     INT(11)     NOT NULL AUTO_INCREMENT,
                `row_id`     INT(11)     NOT NULL,
                `width`      TINYINT(2)  NOT NULL DEFAULT 0,
                `custom_css` TEXT        NOT NULL,
                `params`     TEXT        NOT NULL,
                `sort_order` INT(11)     NOT NULL DEFAULT 0,
                PRIMARY KEY (`col_id`),
                KEY `idx_row` (`row_id`),
                CONSTRAINT `{$p}kit_cb_cols_fk`
                    FOREIGN KEY (`row_id`)
                    REFERENCES `{$p}kit_cb_rows` (`row_id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_elements` (
                `element_id`   INT(11)      NOT NULL AUTO_INCREMENT,
                `block_id`     INT(11)      NOT NULL,
                `col_id`       INT(11)      NOT NULL DEFAULT 0,
                `type`         VARCHAR(50)  NOT NULL DEFAULT '',
                `data`         MEDIUMTEXT   NOT NULL,
                `params`       TEXT         NOT NULL,
                `custom_class` VARCHAR(255) NOT NULL DEFAULT '',
                `custom_css`   TEXT         NOT NULL,
                `preset_id`    INT(11)      NOT NULL DEFAULT 0,
                `sort_order`   INT(11)      NOT NULL DEFAULT 0,
                PRIMARY KEY (`element_id`),
                KEY `idx_block_col` (`block_id`, `col_id`),
                CONSTRAINT `{$p}kit_cb_elements_fk`
                    FOREIGN KEY (`block_id`)
                    REFERENCES `{$p}kit_cb_blocks` (`block_id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_templates` (
                `template_id` INT(11)      NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(255) NOT NULL DEFAULT '',
                `block_type`  VARCHAR(50)  NOT NULL DEFAULT '',
                `data`        MEDIUMTEXT   NOT NULL,
                `date_added`  DATETIME     NOT NULL,
                PRIMARY KEY (`template_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_stickers` (
                `sticker_id` INT(11)     NOT NULL AUTO_INCREMENT,
                `sort_order` INT(11)     NOT NULL DEFAULT 0,
                `status`     TINYINT(1)  NOT NULL DEFAULT 1,
                `color`      VARCHAR(20) NOT NULL DEFAULT '',
                `bg_color`   VARCHAR(20) NOT NULL DEFAULT '',
                `position`   VARCHAR(20) NOT NULL DEFAULT 'top-left',
                PRIMARY KEY (`sticker_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migration: add position column for existing installations
        $colCheck = $this->db->query("
            SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = '{$p}kit_cb_stickers'
              AND COLUMN_NAME  = 'position'
        ")->row;
        if ((int)($colCheck['cnt'] ?? 0) === 0) {
            $this->db->query("
                ALTER TABLE `{$p}kit_cb_stickers`
                ADD COLUMN `position` VARCHAR(20) NOT NULL DEFAULT 'top-left'
            ");
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_sticker_description` (
                `sticker_id`  INT(11)      NOT NULL,
                `language_id` INT(11)      NOT NULL,
                `text`        VARCHAR(100) NOT NULL DEFAULT '',
                PRIMARY KEY (`sticker_id`, `language_id`),
                CONSTRAINT `{$p}kit_cb_sticker_desc_fk`
                    FOREIGN KEY (`sticker_id`)
                    REFERENCES `{$p}kit_cb_stickers` (`sticker_id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}kit_cb_style_presets` (
                `preset_id`  INT(11)      NOT NULL AUTO_INCREMENT,
                `group`      VARCHAR(100) NOT NULL DEFAULT '',
                `name`       VARCHAR(100) NOT NULL DEFAULT '',
                `classes`    VARCHAR(500) NOT NULL DEFAULT '',
                `sort_order` INT(11)      NOT NULL DEFAULT 0,
                PRIMARY KEY (`preset_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migration: add group column for existing installations
        $grpCheck = $this->db->query("
            SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = '{$p}kit_cb_style_presets'
              AND COLUMN_NAME  = 'group'
        ")->row;
        if (empty($grpCheck['cnt'])) {
            $this->db->query("
                ALTER TABLE `{$p}kit_cb_style_presets`
                ADD COLUMN `group` VARCHAR(100) NOT NULL DEFAULT '' AFTER `preset_id`
            ");
        }
    }

    public function dropTables(): void
    {
        $p = DB_PREFIX;
        // Drop in reverse FK dependency order
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_sticker_description`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_stickers`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_style_presets`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_templates`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_elements`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_cols`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_rows`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}kit_cb_blocks`");
    }

    // ─── Private: hydration ──────────────────────────────────────────────────

    private function hydrateBlock(array $row): BlockDto
    {
        $dto     = new BlockDto($row);
        $blockId = (int)$row['block_id'];

        // Single fetch for all elements of this block — grouped by col_id below.
        $elementsByCol = $this->fetchElementsGroupedByCol($blockId);

        $rowRows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_rows`
             WHERE `block_id` = '" . $blockId . "'
             ORDER BY `sort_order` ASC"
        )->rows;

        if (!$rowRows) {
            // Flat structure — all elements live under col_id = 0.
            $dto->elements = $elementsByCol[0] ?? [];
            return $dto;
        }

        // Single fetch for all cols across all rows of this block.
        $colsByRow = $this->fetchColsGroupedByRow($blockId);

        $rows = [];
        foreach ($rowRows as $rowRow) {
            $rowDto = new RowDto($rowRow);
            $rowId  = (int)$rowRow['row_id'];
            $cols   = [];
            foreach ($colsByRow[$rowId] ?? [] as $colRow) {
                $colDto           = new ColDto($colRow);
                $colDto->elements = $elementsByCol[(int)$colRow['col_id']] ?? [];
                $cols[]           = $colDto;
            }
            $rowDto->cols = $cols;
            $rows[]       = $rowDto;
        }
        $dto->rows = $rows;
        return $dto;
    }

    /** @return array<int, array> col_id → ordered raw col rows */
    private function fetchColsGroupedByRow(int $blockId): array
    {
        $cols = $this->db->query(
            "SELECT c.* FROM `" . DB_PREFIX . "kit_cb_cols` c
             INNER JOIN `" . DB_PREFIX . "kit_cb_rows` r ON r.row_id = c.row_id
             WHERE r.block_id = '" . $blockId . "'
             ORDER BY c.sort_order ASC"
        )->rows;
        $grouped = [];
        foreach ($cols as $col) {
            $grouped[(int)$col['row_id']][] = $col;
        }
        return $grouped;
    }

    /** @return array<int, ElementDto[]> col_id → ElementDto[] (sort_order preserved) */
    private function fetchElementsGroupedByCol(int $blockId): array
    {
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_elements`
             WHERE `block_id` = '" . $blockId . "'
             ORDER BY `sort_order` ASC"
        )->rows;
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['col_id']][] = new ElementDto($row);
        }
        return $grouped;
    }

    // ─── Private: write ──────────────────────────────────────────────────────

    private function insertBlock(string $pageRoute, int $pageId, array $data): int
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_cb_blocks`
             (`page_route`, `page_id`, `type`, `name`, `theme`, `status`,
              `sort_order`, `custom_class`, `custom_css`, `params`, `is_global`, `date_added`)
             VALUES (
                '" . $this->db->escape($pageRoute) . "',
                '" . (int)$pageId . "',
                '" . $this->db->escape($data['block_type'] ?? '') . "',
                '" . $this->db->escape($data['block_name'] ?? '') . "',
                '" . $this->db->escape($data['theme'] ?? 'default') . "',
                '" . (int)($data['status'] ?? 1) . "',
                '" . (int)($data['sort_order'] ?? 0) . "',
                '" . $this->db->escape($data['custom_class'] ?? '') . "',
                '" . $this->db->escape($this->encodeJson($data['custom_css'] ?? [])) . "',
                '" . $this->db->escape($this->encodeJson($data['params'] ?? [])) . "',
                '" . (int)($data['is_global'] ?? 0) . "',
                NOW()
             )"
        );
        $id = (int)$this->db->getLastId();
        if ($id <= 0) {
            throw new \RuntimeException('Failed to insert block — getLastId() returned 0');
        }
        return $id;
    }

    private function updateBlock(int $blockId, array $data): void
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_cb_blocks` SET
                `name`         = '" . $this->db->escape($data['block_name'] ?? '') . "',
                `theme`        = '" . $this->db->escape($data['theme'] ?? 'default') . "',
                `status`       = '" . (int)($data['status'] ?? 1) . "',
                `sort_order`   = '" . (int)($data['sort_order'] ?? 0) . "',
                `custom_class` = '" . $this->db->escape($data['custom_class'] ?? '') . "',
                `custom_css`   = '" . $this->db->escape($this->encodeJson($data['custom_css'] ?? [])) . "',
                `params`       = '" . $this->db->escape($this->encodeJson($data['params'] ?? [])) . "'
             WHERE `block_id` = '" . (int)$blockId . "'"
        );
    }

    private function deleteSubItems(int $blockId): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_elements` WHERE `block_id` = '" . (int)$blockId . "'"
        );
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_rows` WHERE `block_id` = '" . (int)$blockId . "'"
        );
        // cols are cascade-deleted when rows are deleted
    }

    private function insertSubItems(int $blockId, array $data): void
    {
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $sortRow => $row) {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "kit_cb_rows`
                     (`block_id`, `custom_css`, `params`, `sort_order`)
                     VALUES (
                        '" . (int)$blockId . "',
                        '" . $this->db->escape($this->encodeJson($row['custom_css'] ?? [])) . "',
                        '" . $this->db->escape($this->encodeJson($row['params'] ?? [])) . "',
                        '" . (int)($row['sort_order'] ?? $sortRow) . "'
                     )"
                );
                $rowId = (int)$this->db->getLastId();

                if (!empty($row['cols'])) {
                    foreach ($row['cols'] as $sortCol => $col) {
                        $this->db->query(
                            "INSERT INTO `" . DB_PREFIX . "kit_cb_cols`
                             (`row_id`, `width`, `custom_css`, `params`, `sort_order`)
                             VALUES (
                                '" . (int)$rowId . "',
                                '" . (int)($col['width'] ?? 0) . "',
                                '" . $this->db->escape($this->encodeJson($col['custom_css'] ?? [])) . "',
                                '" . $this->db->escape($this->encodeJson($col['params'] ?? [])) . "',
                                '" . (int)($col['sort_order'] ?? $sortCol) . "'
                             )"
                        );
                        $colId = (int)$this->db->getLastId();

                        if (!empty($col['elements'])) {
                            foreach ($col['elements'] as $sortEl => $element) {
                                $this->insertElement($blockId, $colId, $element, (int)($element['sort_order'] ?? $sortEl));
                            }
                        }
                    }
                }
            }
        } elseif (!empty($data['elements'])) {
            // Flat elements (col_id = 0): faq, reviews, carousels, etc.
            foreach ($data['elements'] as $sortEl => $element) {
                $this->insertElement($blockId, 0, $element, (int)($element['sort_order'] ?? $sortEl));
            }
        }
    }

    private function insertElement(int $blockId, int $colId, array $el, int $sortOrder): void
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_cb_elements`
             (`block_id`, `col_id`, `type`, `data`, `params`, `custom_class`, `custom_css`, `preset_id`, `sort_order`)
             VALUES (
                '" . (int)$blockId . "',
                '" . (int)$colId . "',
                '" . $this->db->escape($el['element_type'] ?? '') . "',
                '" . $this->db->escape($this->encodeJson($el['data'] ?? [])) . "',
                '" . $this->db->escape($this->encodeJson($el['params'] ?? [])) . "',
                '" . $this->db->escape($el['custom_class'] ?? '') . "',
                '" . $this->db->escape($this->encodeJson($el['custom_css'] ?? [])) . "',
                '" . (int)($el['preset_id'] ?? 0) . "',
                '" . (int)$sortOrder . "'
             )"
        );
    }

    private function copyElements(int $fromBlockId, int $toBlockId, int $fromColId, int $toColId): void
    {
        $elements = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_elements`
             WHERE `block_id` = '" . (int)$fromBlockId . "'
               AND `col_id` = '" . (int)$fromColId . "'
             ORDER BY `sort_order` ASC"
        )->rows;

        foreach ($elements as $el) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_cb_elements`
                 (`block_id`, `col_id`, `type`, `data`, `params`, `custom_class`, `custom_css`, `preset_id`, `sort_order`)
                 VALUES (
                    '" . (int)$toBlockId . "',
                    '" . (int)$toColId . "',
                    '" . $this->db->escape($el['type']) . "',
                    '" . $this->db->escape($el['data']) . "',
                    '" . $this->db->escape($el['params']) . "',
                    '" . $this->db->escape($el['custom_class']) . "',
                    '" . $this->db->escape($el['custom_css']) . "',
                    '" . (int)$el['preset_id'] . "',
                    '" . (int)$el['sort_order'] . "'
                 )"
            );
        }
    }

    // ─── Private: helpers ────────────────────────────────────────────────────

    /**
     * Returns existing block_ids for a page as [block_id => block_id].
     */
    /**
     * Public flat-list variant of getBlockIdsByPage — used by ContentBlocks
     * to invalidate per-block render caches before/after a save.
     *
     * @return int[]
     */
    public function getBlockIdsForPage(string $pageRoute, int $pageId): array
    {
        return array_values($this->getBlockIdsByPage($pageRoute, $pageId));
    }

    private function getBlockIdsByPage(string $pageRoute, int $pageId, bool $forUpdate = false): array
    {
        $rows = $this->db->query(
            "SELECT `block_id` FROM `" . DB_PREFIX . "kit_cb_blocks`
             WHERE `page_route` = '" . $this->db->escape($pageRoute) . "'
               AND `page_id` = '" . (int)$pageId . "'"
            . ($forUpdate ? ' FOR UPDATE' : '')
        )->rows;

        $ids = [];
        foreach ($rows as $row) {
            $ids[(int)$row['block_id']] = (int)$row['block_id'];
        }
        return $ids;
    }

    private function encodeJson($value): string
    {
        if (is_string($value)) {
            return $value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
