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
 * CRUD for saved block templates.
 * A template is a named snapshot of a block's structure + content.
 * Users save a block as a template and later insert it on any page.
 */
class TemplateRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Returns all templates, optionally filtered by block type.
     */
    public function getTemplates(string $blockType = ''): array
    {
        $sql = "SELECT `template_id`, `name`, `block_type`, `date_added`
                FROM `" . DB_PREFIX . "kit_cb_templates`";

        if ($blockType !== '') {
            $sql .= " WHERE `block_type` = '" . $this->db->escape($blockType) . "'";
        }

        $sql .= " ORDER BY `date_added` DESC";

        return $this->db->query($sql)->rows;
    }

    /**
     * Returns a single template with full data JSON.
     */
    public function getTemplate(int $templateId): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_templates`
             WHERE `template_id` = '" . (int)$templateId . "' LIMIT 1"
        )->row;

        if (!$row) {
            return null;
        }

        $row['data'] = json_decode($row['data'], true) ?: [];
        return $row;
    }

    /**
     * Saves a new template from a block's data snapshot.
     *
     * @param string $name       Display name
     * @param string $blockType  Block type identifier
     * @param array  $data       Full block structure (JS-serialized, already parsed)
     * @return int New template_id
     */
    public function saveTemplate(string $name, string $blockType, array $data): int
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "kit_cb_templates`
             (`name`, `block_type`, `data`, `date_added`)
             VALUES (
                '" . $this->db->escape($name) . "',
                '" . $this->db->escape($blockType) . "',
                '" . $this->db->escape(json_encode($data, JSON_UNESCAPED_UNICODE)) . "',
                NOW()
             )"
        );
        return (int)$this->db->getLastId();
    }

    /**
     * Deletes a template by ID.
     */
    public function deleteTemplate(int $templateId): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_templates`
             WHERE `template_id` = '" . (int)$templateId . "'"
        );
    }
}
