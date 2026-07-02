<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

/**
 * Persists translated field values to the appropriate description table.
 */
class ContentSaver
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Saves translated fields for one item.
     * Uses INSERT ... ON DUPLICATE KEY UPDATE so it works whether the row exists or not.
     *
     * @param string   $type        Content type key (product, category, …)
     * @param int      $itemId      Primary key value
     * @param string   $targetLang  Language code
     * @param array    $fields      field_name => translated_value
     */
    public function save(string $type, int $itemId, string $targetLang, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $def      = TypeDefinitions::get($type);
        $langId   = $this->getLangId($targetLang);

        if (!$langId) {
            return;
        }

        // Only keep fields that belong to this type
        $allowed = array_flip($def['fields']);
        $fields  = array_intersect_key($fields, $allowed);

        if (empty($fields)) {
            return;
        }

        // Check if target row already exists
        $exists = $this->db->query(
            "SELECT `{$def['id_field']}` FROM `" . DB_PREFIX . "{$def['table']}`
             WHERE `{$def['id_field']}` = " . $itemId . "
               AND `language_id` = " . $langId . "
             LIMIT 1"
        )->row;

        if ($exists) {
            $sets = [];
            foreach ($fields as $col => $val) {
                $sets[] = "`{$col}` = '" . $this->db->escape($val) . "'";
            }
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "{$def['table']}`
                 SET " . implode(', ', $sets) . "
                 WHERE `{$def['id_field']}` = " . $itemId . "
                   AND `language_id` = " . $langId
            );
        } else {
            $cols   = ["`{$def['id_field']}`", '`language_id`'];
            $values = [$itemId, $langId];
            foreach ($fields as $col => $val) {
                $cols[]   = "`{$col}`";
                $values[] = "'" . $this->db->escape($val) . "'";
            }
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "{$def['table']}`
                 (" . implode(', ', $cols) . ")
                 VALUES (" . implode(', ', $values) . ")"
            );
        }
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
