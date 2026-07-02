<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

/**
 * CRUD for the oc_auto_related_rule table.
 * Rules use a constructor-based condition model stored as JSON arrays.
 */
class RuleRepository
{
    /** @var \DB */
    private $db;

    /** Allowed source condition types */
    private static $srcTypes = ['category', 'manufacturer', 'attribute', 'name_contains'];

    /** Allowed target condition types */
    private static $tgtTypes = [
        'same_category', 'same_manufacturer',
        'category', 'manufacturer',
        'attribute', 'dynamic_attribute',
        'name_contains', 'price_range',
        'only_special', 'exclude_oos', 'brand_priority',
    ];

    /** Allowed result_sort values */
    private static $sortValues = ['random', 'price_asc', 'price_desc', 'new', 'name', 'bestseller'];

    public function __construct(\DB $db)
    {
        $this->db = $db;
    }

    public function getActiveRules(): array
    {
        $result = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "auto_related_rule`
             WHERE status = 1
             ORDER BY sort_order ASC, rule_id ASC"
        );
        return $this->decodeAll($result->rows);
    }

    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "auto_related_rule`
             ORDER BY sort_order ASC, rule_id ASC"
        );
        return $this->decodeAll($result->rows);
    }

    public function getById(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "auto_related_rule`
             WHERE rule_id = " . $id
        );
        if (empty($result->row)) {
            return null;
        }
        return $this->decode($result->row);
    }

    public function save(array $data): int
    {
        $ruleId     = (int)($data['rule_id'] ?? 0);
        $resultSort = in_array($data['result_sort'] ?? '', self::$sortValues, true)
            ? $data['result_sort'] : 'random';

        $srcConds = $this->validateConditions((array)($data['source_conditions'] ?? []), 'source');
        $tgtConds = $this->validateConditions((array)($data['target_conditions'] ?? []), 'target');

        // Reject empty rules: an unconditional rule effectively pairs every
        // product in the catalogue with every other product, which is never
        // intentional and produces a noise-only block.
        if (empty($srcConds)) {
            require_once __DIR__ . '/../exceptions/AutoRelatedException.php';
            throw new \OcKit\AutoRelated\Exceptions\AutoRelatedException(
                'Rule must have at least one valid source condition.'
            );
        }
        if (empty($tgtConds)) {
            require_once __DIR__ . '/../exceptions/AutoRelatedException.php';
            throw new \OcKit\AutoRelated\Exceptions\AutoRelatedException(
                'Rule must have at least one valid target condition.'
            );
        }

        $fields = array(
            'name'               => "'" . $this->db->escape((string)($data['name'] ?? '')) . "'",
            'status'             => (int)!empty($data['status']),
            'sort_order'         => (int)($data['sort_order'] ?? 0),
            'block_title'        => "'" . $this->db->escape($this->encodeBlockTitle($data['block_title'] ?? [])) . "'",
            'result_limit'       => max(1, min(50, (int)($data['result_limit'] ?? 8))),
            'result_sort'        => "'" . $this->db->escape($resultSort) . "'",
            'source_conditions'  => "'" . $this->db->escape(json_encode($srcConds, JSON_UNESCAPED_UNICODE)) . "'",
            'target_conditions'  => "'" . $this->db->escape(json_encode($tgtConds, JSON_UNESCAPED_UNICODE)) . "'",
        );

        if ($ruleId > 0) {
            $parts = array();
            foreach ($fields as $k => $v) {
                $parts[] = "`" . $k . "` = " . $v;
            }
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "auto_related_rule`
                 SET " . implode(', ', $parts) . "
                 WHERE rule_id = " . $ruleId
            );
            return $ruleId;
        }

        $fields['created_at'] = "NOW()";
        $keys = implode(', ', array_map(function ($k) { return "`" . $k . "`"; }, array_keys($fields)));
        $vals = implode(', ', array_values($fields));
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "auto_related_rule` (" . $keys . ") VALUES (" . $vals . ")"
        );
        return (int)$this->db->getLastId();
    }

    public function delete(int $id): void
    {
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "auto_related_rule` WHERE rule_id = " . $id
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Validates and sanitises a conditions array.
     * Unknown types are skipped. Fields are sanitised per type.
     */
    private function validateConditions(array $conditions, string $side): array
    {
        $allowedTypes = $side === 'source' ? self::$srcTypes : self::$tgtTypes;
        $valid = array();

        foreach ($conditions as $cond) {
            if (!is_array($cond)) {
                continue;
            }
            $type = (string)($cond['type'] ?? '');
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }

            $validated = array('type' => $type);

            switch ($type) {
                case 'category':
                case 'manufacturer':
                    // Accept both [1, 2] (legacy) and [{id, label}, ...] (from UI).
                    // Preserve labels so the edit form can restore tag text without
                    // an extra API call.
                    $rawIds = (array)($cond['ids'] ?? []);
                    $ids    = [];
                    foreach ($rawIds as $item) {
                        if (is_array($item)) {
                            $id    = (int)($item['id'] ?? 0);
                            $label = trim((string)($item['label'] ?? ''));
                        } else {
                            $id    = (int)$item;
                            $label = '';
                        }
                        if ($id > 0) {
                            $ids[] = $label !== '' ? ['id' => $id, 'label' => $label] : ['id' => $id];
                        }
                    }
                    if (empty($ids)) {
                        continue 2;
                    }
                    $validated['ids'] = $ids;
                    break;

                case 'attribute':
                    $attrId = (int)($cond['attribute_id'] ?? 0);
                    $value  = trim((string)($cond['value'] ?? ''));
                    if ($attrId <= 0 || $value === '') {
                        continue 2;
                    }
                    $validated['attribute_id'] = $attrId;
                    $validated['value']        = $value;
                    break;

                case 'dynamic_attribute':
                    $attrId = (int)($cond['attribute_id'] ?? 0);
                    if ($attrId <= 0) {
                        continue 2;
                    }
                    $validated['attribute_id'] = $attrId;
                    break;

                case 'name_contains':
                    $text = trim((string)($cond['text'] ?? ''));
                    if ($text === '') {
                        continue 2;
                    }
                    $validated['text'] = $text;
                    break;

                case 'price_range':
                    $validated['pct'] = max(1, min(500, (int)($cond['pct'] ?? 20)));
                    break;

                // no extra fields needed
                case 'same_category':
                case 'same_manufacturer':
                case 'only_special':
                case 'exclude_oos':
                case 'brand_priority':
                    break;
            }

            $valid[] = $validated;
        }

        return $valid;
    }

    private function decode(array $row): array
    {
        $row['source_conditions'] = json_decode($row['source_conditions'] ?? '[]', true) ?: array();
        $row['target_conditions'] = json_decode($row['target_conditions'] ?? '[]', true) ?: array();
        // block_title: try JSON decode; if not JSON (legacy plain string) wrap it
        $bt = $row['block_title'] ?? '';
        $decoded = json_decode($bt, true);
        $row['block_title'] = is_array($decoded) ? $decoded : (($bt !== '') ? array(1 => $bt) : array());
        return $row;
    }

    private function encodeBlockTitle($value): string
    {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $langId => $title) {
                $langId = (int)$langId;
                $title  = trim((string)$title);
                if ($langId > 0) {
                    $clean[$langId] = $title;
                }
            }
            return json_encode($clean, JSON_UNESCAPED_UNICODE);
        }
        return (string)$value;
    }

    private function decodeAll(array $rows): array
    {
        return array_map(array($this, 'decode'), $rows);
    }
}
