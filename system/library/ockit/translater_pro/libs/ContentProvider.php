<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

use OcKit\TranslaterPro\Dto\TranslationItem;

/**
 * Fetches content records that are missing a target-language translation.
 */
class ContentProvider
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns paginated list of items missing a target translation.
     * When $includeTranslated is true, already-translated items are returned too.
     *
     * @return TranslationItem[]
     */
    public function getItems(string $type, string $sourceLang, string $targetLang, int $start, int $limit, bool $includeTranslated = false): array
    {
        $def      = TypeDefinitions::get($type);
        $sourceId = $this->getLangId($sourceLang);
        $targetId = $this->getLangId($targetLang);

        if (!$sourceId || !$targetId) {
            return [];
        }

        // For manufacturer: also fetch name from parent table
        $nameSelect = $def['name_field']
            ? ''
            : ", p.`name` AS `_parent_name`";

        $whereTranslated = $includeTranslated ? '1' : "tgt.`{$def['id_field']}` IS NULL";

        $q = $this->db->query("
            SELECT src.*, p.`{$def['id_field']}` AS `_item_id` {$nameSelect}
            FROM `" . DB_PREFIX . "{$def['parent_table']}` p
            INNER JOIN `" . DB_PREFIX . "{$def['table']}` src
                ON src.`{$def['id_field']}` = p.`{$def['id_field']}`
               AND src.`language_id` = " . $sourceId . "
            LEFT JOIN `" . DB_PREFIX . "{$def['table']}` tgt
                ON tgt.`{$def['id_field']}` = p.`{$def['id_field']}`
               AND tgt.`language_id` = " . $targetId . "
            WHERE {$whereTranslated}
            ORDER BY p.`{$def['id_field']}` ASC
            LIMIT " . (int)$start . ", " . (int)$limit . "
        ");

        $items = [];
        foreach ($q->rows as $row) {
            $itemId = (int)$row['_item_id'];

            // Determine display name
            if ($def['name_field']) {
                $displayName = (string)($row[$def['name_field']] ?? '');
            } else {
                $displayName = (string)($row['_parent_name'] ?? '');
            }

            // Collect non-empty source fields
            $fields = [];
            foreach ($def['fields'] as $field) {
                $value = trim(strip_tags((string)($row[$field] ?? '')));
                if ($value !== '') {
                    $fields[$field] = (string)$row[$field];
                }
            }

            if (!empty($fields)) {
                $items[] = new TranslationItem($itemId, $type, $displayName, $fields);
            }
        }

        return $items;
    }

    /**
     * Returns one item's source fields by item_id (for translate action).
     */
    public function getOne(string $type, int $itemId, string $sourceLang): ?TranslationItem
    {
        $def      = TypeDefinitions::get($type);
        $sourceId = $this->getLangId($sourceLang);

        if (!$sourceId) {
            return null;
        }

        $nameSelect = $def['name_field'] ? '' : ", p.`name` AS `_parent_name`";

        $q = $this->db->query("
            SELECT src.* {$nameSelect}
            FROM `" . DB_PREFIX . "{$def['table']}` src
            LEFT JOIN `" . DB_PREFIX . "{$def['parent_table']}` p
                ON p.`{$def['id_field']}` = src.`{$def['id_field']}`
            WHERE src.`{$def['id_field']}` = " . (int)$itemId . "
              AND src.`language_id` = " . $sourceId . "
            LIMIT 1
        ");

        if (!$q->row) {
            return null;
        }

        $row = $q->row;

        $displayName = $def['name_field']
            ? (string)($row[$def['name_field']] ?? '')
            : (string)($row['_parent_name'] ?? '');

        $fields = [];
        foreach ($def['fields'] as $field) {
            $value = trim(strip_tags((string)($row[$field] ?? '')));
            if ($value !== '') {
                $fields[$field] = (string)$row[$field];
            }
        }

        return new TranslationItem($itemId, $type, $displayName, $fields);
    }

    private function getLangId(string $code): int
    {
        $q = $this->db->query(
            "SELECT `language_id` FROM `" . DB_PREFIX . "language`
             WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1"
        );
        return (int)($q->row['language_id'] ?? 0);
    }
}
