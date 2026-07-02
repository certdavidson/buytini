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

/**
 * CRUD for named CSS class presets.
 * Presets allow admins to define reusable CSS class combinations
 * (e.g., "Section Heading" → "ok-h2 text-primary mb-4") that can be
 * applied to elements, columns, and rows from a dropdown.
 */
class StylePresetRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns all presets ordered by group, sort_order.
     */
    public function getPresets(): array
    {
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_style_presets`
             ORDER BY `group` ASC, `sort_order` ASC, `preset_id` ASC"
        )->rows;
    }

    /**
     * Returns a single preset by ID.
     */
    public function getPreset(int $presetId): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_style_presets`
             WHERE `preset_id` = '" . (int)$presetId . "' LIMIT 1"
        )->row;

        return $row ?: null;
    }

    /**
     * Saves (insert or update) a preset.
     * If preset_id = 0 — insert. If preset_id > 0 — update.
     *
     * @return int preset_id
     */
    public function savePreset(int $presetId, string $name, string $classes, int $sortOrder = 0, string $group = ''): int
    {
        if ($presetId > 0) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "kit_cb_style_presets`
                 SET `group`      = '" . $this->db->escape($group) . "',
                     `name`       = '" . $this->db->escape($name) . "',
                     `classes`    = '" . $this->db->escape($classes) . "',
                     `sort_order` = '" . (int)$sortOrder . "'
                 WHERE `preset_id` = '" . (int)$presetId . "'"
            );
            return $presetId;
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_cb_style_presets`
             (`group`, `name`, `classes`, `sort_order`)
             VALUES (
                '" . $this->db->escape($group) . "',
                '" . $this->db->escape($name) . "',
                '" . $this->db->escape($classes) . "',
                '" . (int)$sortOrder . "'
             )"
        );
        return (int)$this->db->getLastId();
    }

    /**
     * Deletes a preset by ID.
     */
    public function deletePreset(int $presetId): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_style_presets`
             WHERE `preset_id` = '" . (int)$presetId . "'"
        );
    }

    /**
     * Deletes all presets and re-inserts the default initial set.
     */
    public function resetPresets(): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_cb_style_presets`");
        $this->insertDefaults();
    }

    /**
     * Inserts default presets only if the table is currently empty.
     * Call from install().
     */
    public function insertDefaultsIfEmpty(): void
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_cb_style_presets`"
        )->row;

        if (empty($row['cnt'])) {
            $this->insertDefaults();
        }
    }

    /**
     * Returns the built-in default preset set.
     */
    public function getDefaultPresets(): array
    {
        return [
            // Вирівнювання
            ['group' => 'Вирівнювання', 'name' => 'По центру',       'classes' => 'text-center', 'sort_order' => 10],
            ['group' => 'Вирівнювання', 'name' => 'По правому краю', 'classes' => 'text-right',  'sort_order' => 11],
            ['group' => 'Вирівнювання', 'name' => 'По лівому краю',  'classes' => 'text-left',   'sort_order' => 12],
            // Відступи
            ['group' => 'Відступи', 'name' => 'Відступ малий',       'classes' => 'mt-2 mb-2',   'sort_order' => 20],
            ['group' => 'Відступи', 'name' => 'Відступ середній',    'classes' => 'mt-4 mb-4',   'sort_order' => 21],
            ['group' => 'Відступи', 'name' => 'Відступ великий',     'classes' => 'mt-5 mb-5',   'sort_order' => 22],
            // Картки
            ['group' => 'Картки', 'name' => 'Рамка',                 'classes' => 'border rounded p-3',    'sort_order' => 30],
            ['group' => 'Картки', 'name' => 'Тінь',                  'classes' => 'shadow-sm rounded p-3', 'sort_order' => 31],
            // Фон
            ['group' => 'Фон', 'name' => 'Фон сірий',                'classes' => 'bg-light',              'sort_order' => 40],
            ['group' => 'Фон', 'name' => 'Фон темний',               'classes' => 'bg-dark text-white',    'sort_order' => 41],
            // Кнопки
            ['group' => 'Кнопки', 'name' => 'Кнопка основна',        'classes' => 'btn btn-primary',        'sort_order' => 50],
            ['group' => 'Кнопки', 'name' => 'Кнопка вторинна',       'classes' => 'btn btn-default',        'sort_order' => 51],
            ['group' => 'Кнопки', 'name' => 'Кнопка небезпека',      'classes' => 'btn btn-danger',         'sort_order' => 52],
            ['group' => 'Кнопки', 'name' => 'Кнопка мала',           'classes' => 'btn btn-primary btn-sm', 'sort_order' => 53],
            ['group' => 'Кнопки', 'name' => 'Кнопка велика',         'classes' => 'btn btn-primary btn-lg', 'sort_order' => 54],
        ];
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function insertDefaults(): void
    {
        foreach ($this->getDefaultPresets() as $p) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_cb_style_presets`
                 (`group`, `name`, `classes`, `sort_order`) VALUES (
                    '" . $this->db->escape($p['group']) . "',
                    '" . $this->db->escape($p['name']) . "',
                    '" . $this->db->escape($p['classes']) . "',
                    '" . (int)$p['sort_order'] . "'
                 )"
            );
        }
    }
}
