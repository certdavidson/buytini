<?php
/**
 * Content Blocks Pro — Element Repository: standalone CRUD for elements.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

use OcKit\ContentBlocks\Dto\ElementDto;

class ElementRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getElement(int $elementId): ?ElementDto
    {
        $q = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_cb_elements` WHERE `element_id` = " . (int)$elementId
        );
        if (!$q->num_rows) {
            return null;
        }
        return $this->rowToDto($q->row);
    }

    public function getElementsForBlock(int $blockId, int $colId = -1): array
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "kit_cb_elements` WHERE `block_id` = " . (int)$blockId;
        if ($colId >= 0) {
            $sql .= " AND `col_id` = " . (int)$colId;
        }
        $sql .= " ORDER BY `sort_order` ASC";

        $results = [];
        foreach ($this->db->query($sql)->rows as $row) {
            $results[] = $this->rowToDto($row);
        }
        return $results;
    }

    public function updateElement(int $elementId, array $data): void
    {
        $set = [];
        if (isset($data['data'])) {
            $set[] = "`data` = '" . $this->db->escape(is_array($data['data']) ? json_encode($data['data']) : $data['data']) . "'";
        }
        if (isset($data['params'])) {
            $set[] = "`params` = '" . $this->db->escape(is_array($data['params']) ? json_encode($data['params']) : $data['params']) . "'";
        }
        if (isset($data['custom_class'])) {
            $set[] = "`custom_class` = '" . $this->db->escape($data['custom_class']) . "'";
        }
        if (isset($data['custom_css'])) {
            $set[] = "`custom_css` = '" . $this->db->escape(is_array($data['custom_css']) ? json_encode($data['custom_css']) : $data['custom_css']) . "'";
        }
        if (isset($data['preset_id'])) {
            $set[] = "`preset_id` = " . (int)$data['preset_id'];
        }
        if (isset($data['sort_order'])) {
            $set[] = "`sort_order` = " . (int)$data['sort_order'];
        }
        if (!$set) {
            return;
        }
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "kit_cb_elements` SET " . implode(', ', $set) .
            " WHERE `element_id` = " . (int)$elementId
        );
    }

    public function deleteElement(int $elementId): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_elements` WHERE `element_id` = " . (int)$elementId
        );
    }

    public function deleteElementsForBlock(int $blockId): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "kit_cb_elements` WHERE `block_id` = " . (int)$blockId
        );
    }

    private function rowToDto(array $row): ElementDto
    {
        return new ElementDto($row);
    }
}
