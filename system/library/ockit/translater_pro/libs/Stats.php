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
 * Counts untranslated records per content type.
 */
class Stats
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns untranslated counts for all types.
     *
     * @return array<string, int>
     */
    public function getAll(string $sourceLang, string $targetLang): array
    {
        $sourceId = $this->getLangId($sourceLang);
        $targetId = $this->getLangId($targetLang);

        if (!$sourceId || !$targetId || $sourceId === $targetId) {
            return array_fill_keys(TypeDefinitions::keys(), 0);
        }

        $result = [];
        foreach (TypeDefinitions::all() as $type => $def) {
            $result[$type] = $this->countUntranslated($def, $sourceId, $targetId);
        }
        return $result;
    }

    public function getOne(string $type, string $sourceLang, string $targetLang, bool $includeTranslated = false): int
    {
        $sourceId = $this->getLangId($sourceLang);
        $targetId = $this->getLangId($targetLang);

        if (!$sourceId || !$targetId || $sourceId === $targetId) {
            return 0;
        }

        return $this->countUntranslated(TypeDefinitions::get($type), $sourceId, $targetId, $includeTranslated);
    }

    private function countUntranslated(array $def, int $sourceId, int $targetId, bool $includeTranslated = false): int
    {
        $whereTranslated = $includeTranslated ? '1' : "tgt.`{$def['id_field']}` IS NULL";

        $q = $this->db->query("
            SELECT COUNT(DISTINCT p.`{$def['id_field']}`) AS total
            FROM `" . DB_PREFIX . "{$def['parent_table']}` p
            INNER JOIN `" . DB_PREFIX . "{$def['table']}` src
                ON src.`{$def['id_field']}` = p.`{$def['id_field']}`
               AND src.`language_id` = " . $sourceId . "
            LEFT JOIN `" . DB_PREFIX . "{$def['table']}` tgt
                ON tgt.`{$def['id_field']}` = p.`{$def['id_field']}`
               AND tgt.`language_id` = " . $targetId . "
            WHERE {$whereTranslated}
        ");
        return (int)$q->row['total'];
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
