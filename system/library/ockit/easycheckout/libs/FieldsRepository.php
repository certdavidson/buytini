<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

require_once __DIR__ . '/FieldRegistry.php';
require_once __DIR__ . '/../exceptions/ValidationException.php';

use OcKit\EasyCheckout\Exceptions\ValidationException;

/**
 * CRUD для глобального реєстру полів модуля.
 *
 * Поле описується записом в `kit_easycheckout_fields` + по одному запису
 * на мову в `kit_easycheckout_fields_description` (name/tooltip/placeholder).
 *
 * Бізнес-логіка та валідація — тут. Адмін-модель просто проксі.
 */
final class FieldsRepository
{
    /** @var \DB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ─── Read ────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array> Список полів з усіма descriptions.
     */
    public function list(array $filter = []): array
    {
        $where = ['1=1'];
        if (!empty($filter['type'])) {
            $where[] = "f.`type` = '" . $this->db->escape($filter['type']) . "'";
        }
        if (!empty($filter['belongs_to'])) {
            $where[] = "f.`belongs_to` = '" . $this->db->escape($filter['belongs_to']) . "'";
        }
        if (isset($filter['search']) && $filter['search'] !== '') {
            $term = $this->db->escape($filter['search']);
            $where[] = "(f.`code` LIKE '%{$term}%' OR EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "kit_easycheckout_fields_description` d
                WHERE d.`field_id` = f.`field_id` AND d.`name` LIKE '%{$term}%'))";
        }

        $sortable = ['field_id', 'code', 'type', 'belongs_to', 'date_modified', 'sort_order'];
        $explicitSort = in_array($filter['sort'] ?? '', $sortable, true);
        $sort     = $explicitSort ? $filter['sort'] : 'sort_order';
        $order    = (strtoupper($filter['order'] ?? ($explicitSort ? 'DESC' : 'ASC')) === 'ASC') ? 'ASC' : 'DESC';

        $start = max(0, (int)($filter['start'] ?? 0));
        $limit = max(1, (int)($filter['limit'] ?? 50));

        $orderClause = $explicitSort
            ? "f.`{$sort}` {$order}"
            : "f.`sort_order` ASC, f.`field_id` DESC";

        $sql = "SELECT f.* FROM `" . DB_PREFIX . "kit_easycheckout_fields` f
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderClause}
                LIMIT {$start}, {$limit}";

        $rows = $this->db->query($sql)->rows;
        if (!$rows) return [];

        $ids = array_map(fn($r) => (int)$r['field_id'], $rows);
        $descriptions = $this->loadDescriptions($ids);

        foreach ($rows as &$r) {
            $r['field_id']         = (int)$r['field_id'];
            $r['save_to_comment']  = (int)$r['save_to_comment'];
            $r['validation_rules'] = $r['validation_rules'] ? json_decode($r['validation_rules'], true) : [];
            $r['params']           = $r['params'] ? json_decode($r['params'], true) : [];
            $r['descriptions']     = $descriptions[$r['field_id']] ?? [];
            $filled = 0;
            foreach ($r['descriptions'] as $d) {
                if (!empty($d['name']) && trim((string)$d['name']) !== '') $filled++;
            }
            $r['_langs_filled'] = $filled;
        }
        return $rows;
    }

    public function count(array $filter = []): int
    {
        $where = ['1=1'];
        if (!empty($filter['type'])) {
            $where[] = "f.`type` = '" . $this->db->escape($filter['type']) . "'";
        }
        if (!empty($filter['belongs_to'])) {
            $where[] = "f.`belongs_to` = '" . $this->db->escape($filter['belongs_to']) . "'";
        }
        if (isset($filter['search']) && $filter['search'] !== '') {
            $term = $this->db->escape($filter['search']);
            $where[] = "(f.`code` LIKE '%{$term}%' OR EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "kit_easycheckout_fields_description` d
                WHERE d.`field_id` = f.`field_id` AND d.`name` LIKE '%{$term}%'))";
        }
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_fields` f
            WHERE " . implode(' AND ', $where))->row;
        return (int)$row['cnt'];
    }

    public function get(int $fieldId): ?array
    {
        $row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_fields`
            WHERE `field_id` = " . $fieldId)->row;
        if (!$row) return null;

        $row['field_id']         = (int)$row['field_id'];
        $row['save_to_comment']  = (int)$row['save_to_comment'];
        $row['validation_rules'] = $row['validation_rules'] ? json_decode($row['validation_rules'], true) : [];
        $row['params']           = $row['params'] ? json_decode($row['params'], true) : [];
        $row['descriptions']     = $this->loadDescriptions([$fieldId])[$fieldId] ?? [];
        return $row;
    }

    public function getByCode(string $code): ?array
    {
        $row = $this->db->query("SELECT `field_id` FROM `" . DB_PREFIX . "kit_easycheckout_fields`
            WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");
        return $row->num_rows ? $this->get((int)$row->row['field_id']) : null;
    }

    /**
     * @param int[] $ids
     * @return array<int, array<int, array>> field_id => language_id => row
     */
    private function loadDescriptions(array $ids): array
    {
        if (!$ids) return [];
        $idList = implode(',', array_map('intval', $ids));
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_fields_description`
            WHERE `field_id` IN ({$idList})")->rows;

        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['field_id']][(int)$r['language_id']] = [
                'name'        => (string)$r['name'],
                'tooltip'     => (string)$r['tooltip'],
                'placeholder' => (string)$r['placeholder'],
            ];
        }
        return $result;
    }

    // ─── Native field overrides ────────────────────────────────────────────────
    // Зберігаються в `fields_description` з ВІДʼЄМНИМ field_id (= field_id native-поля
    // з NativeFieldsRegistry). Окрема таблиця не потрібна — native-поля в `fields`
    // не існують, тож колізій немає.

    /** @return array<int, array<int, array{name:string,tooltip:string,placeholder:string}>> */
    public function getNativeOverrides(): array
    {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_fields_description`
            WHERE `field_id` < 0")->rows;
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['field_id']][(int)$r['language_id']] = [
                'name'        => (string)$r['name'],
                'tooltip'     => (string)$r['tooltip'],
                'placeholder' => (string)$r['placeholder'],
            ];
        }
        return $out;
    }

    /** Зберегти оверайди (name/placeholder/tooltip по мовах) для native-поля. */
    public function saveNativeOverride(int $negId, array $descriptions): void
    {
        if ($negId >= 0) return;
        $norm = [];
        foreach ($descriptions as $langId => $d) {
            if (!is_array($d)) continue;
            $norm[(int)$langId] = [
                'name'        => (string)($d['name'] ?? ''),
                'tooltip'     => (string)($d['tooltip'] ?? ''),
                'placeholder' => (string)($d['placeholder'] ?? ''),
            ];
        }
        $this->saveDescriptions($negId, $norm);
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    /**
     * @return int field_id новоствореного поля.
     */
    public function add(array $data): int
    {
        $clean = $this->validateAndNormalize($data, null);

        $now = date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_fields` SET
            `code`             = '" . $this->db->escape($clean['code']) . "',
            `type`             = '" . $this->db->escape($clean['type']) . "',
            `belongs_to`       = '" . $this->db->escape($clean['belongs_to']) . "',
            `mask_mode`        = '" . $this->db->escape($clean['mask_mode']) . "',
            `mask_value`       = " . $this->nullableEscape($clean['mask_value']) . ",
            `default_mode`     = '" . $this->db->escape($clean['default_mode']) . "',
            `default_value`    = " . $this->nullableEscape($clean['default_value']) . ",
            `save_to_comment`  = " . (int)$clean['save_to_comment'] . ",
            `validation_rules` = '" . $this->db->escape(json_encode($clean['validation_rules'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
            `params`           = '" . $this->db->escape(json_encode($clean['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
            `date_added`       = '{$now}',
            `date_modified`    = '{$now}'");

        $fieldId = (int)$this->db->getLastId();
        $this->saveDescriptions($fieldId, $clean['descriptions']);
        return $fieldId;
    }

    public function update(int $fieldId, array $data): void
    {
        $existing = $this->get($fieldId);
        if (!$existing) {
            throw new ValidationException(['field_id' => 'not_found']);
        }
        $clean = $this->validateAndNormalize($data, $fieldId);

        $now = date('Y-m-d H:i:s');
        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_fields` SET
            `code`             = '" . $this->db->escape($clean['code']) . "',
            `type`             = '" . $this->db->escape($clean['type']) . "',
            `belongs_to`       = '" . $this->db->escape($clean['belongs_to']) . "',
            `mask_mode`        = '" . $this->db->escape($clean['mask_mode']) . "',
            `mask_value`       = " . $this->nullableEscape($clean['mask_value']) . ",
            `default_mode`     = '" . $this->db->escape($clean['default_mode']) . "',
            `default_value`    = " . $this->nullableEscape($clean['default_value']) . ",
            `save_to_comment`  = " . (int)$clean['save_to_comment'] . ",
            `validation_rules` = '" . $this->db->escape(json_encode($clean['validation_rules'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
            `params`           = '" . $this->db->escape(json_encode($clean['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
            `date_modified`    = '{$now}'
            WHERE `field_id` = " . $fieldId);

        $this->saveDescriptions($fieldId, $clean['descriptions']);
    }

    public function delete(int $fieldId): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_fields` WHERE `field_id` = " . $fieldId);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_fields_description` WHERE `field_id` = " . $fieldId);
    }

    public function deleteMany(array $ids): int
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) return 0;
        $list = implode(',', $ids);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_fields` WHERE `field_id` IN ({$list})");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_fields_description` WHERE `field_id` IN ({$list})");
        return count($ids);
    }

    /**
     * Згенерувати наступний унікальний `code` (fieldN), де N — мінімальне вільне число.
     */
    public function generateNextCode(string $prefix = 'field'): string
    {
        $rows = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "kit_easycheckout_fields`
            WHERE `code` LIKE '" . $this->db->escape($prefix) . "%'")->rows;
        $taken = [];
        foreach ($rows as $r) {
            if (preg_match('~^' . preg_quote($prefix, '~') . '(\d+)$~', $r['code'], $m)) {
                $taken[(int)$m[1]] = true;
            }
        }
        $i = 1;
        while (isset($taken[$i])) $i++;
        return $prefix . $i;
    }

    // ─── Validation / normalization ───────────────────────────────────────────

    private function validateAndNormalize(array $data, ?int $editingId): array
    {
        $errors = [];

        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            $errors['code'] = 'required';
        } elseif (!preg_match('~^[a-z][a-z0-9_]{0,63}$~', $code)) {
            $errors['code'] = 'invalid_format';
        } elseif (in_array($code, NativeFieldsRegistry::reservedCodes(), true)) {
            // Колізія з native-полем (email/city/address_1/...) — той самий POST-ключ
            $errors['code'] = 'reserved';
        } else {
            $row = $this->db->query("SELECT `field_id` FROM `" . DB_PREFIX . "kit_easycheckout_fields`
                WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");
            if ($row->num_rows && (int)$row->row['field_id'] !== ($editingId ?? -1)) {
                $errors['code'] = 'duplicate';
            }
        }

        $type = (string)($data['type'] ?? '');
        if (!FieldRegistry::exists($type)) {
            $errors['type'] = 'invalid';
        }

        $belongsTo = (string)($data['belongs_to'] ?? 'order');
        if (!FieldRegistry::isValidBelongsTo($belongsTo)) {
            $errors['belongs_to'] = 'invalid';
        }

        $maskMode = (string)($data['mask_mode'] ?? 'manual');
        if (!in_array($maskMode, ['manual', 'api'], true)) {
            $errors['mask_mode'] = 'invalid';
        }
        $defaultMode = (string)($data['default_mode'] ?? 'manual');
        if (!in_array($defaultMode, ['manual', 'api'], true)) {
            $errors['default_mode'] = 'invalid';
        }

        // Descriptions: масив language_id => {name, tooltip, placeholder}.
        $descriptions = [];
        foreach (($data['descriptions'] ?? []) as $langId => $desc) {
            $langId = (int)$langId;
            if ($langId <= 0) continue;
            $descriptions[$langId] = [
                'name'        => trim((string)($desc['name']        ?? '')),
                'tooltip'     => trim((string)($desc['tooltip']     ?? '')),
                'placeholder' => trim((string)($desc['placeholder'] ?? '')),
            ];
        }

        // Принаймні одна мова повинна мати назву.
        $hasName = false;
        foreach ($descriptions as $d) {
            if ($d['name'] !== '') { $hasName = true; break; }
        }
        if (!$hasName && empty($errors)) {
            $errors['name'] = 'required_in_any_language';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        // Validation rules — приймаємо як уже структурований масив.
        $rules = is_array($data['validation_rules'] ?? null) ? $data['validation_rules'] : [];
        $params = is_array($data['params'] ?? null) ? $data['params'] : [];

        return [
            'code'             => $code,
            'type'             => $type,
            'belongs_to'       => $belongsTo,
            'mask_mode'        => $maskMode,
            'mask_value'       => $this->trimOrNull($data['mask_value'] ?? null),
            'default_mode'     => $defaultMode,
            'default_value'    => $this->trimOrNull($data['default_value'] ?? null),
            'save_to_comment'  => !empty($data['save_to_comment']) ? 1 : 0,
            'validation_rules' => $rules,
            'params'           => $params,
            'descriptions'     => $descriptions,
        ];
    }

    private function saveDescriptions(int $fieldId, array $descriptions): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_fields_description`
            WHERE `field_id` = " . $fieldId);

        foreach ($descriptions as $languageId => $desc) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_fields_description` SET
                `field_id`    = " . $fieldId . ",
                `language_id` = " . (int)$languageId . ",
                `name`        = '" . $this->db->escape($desc['name']) . "',
                `tooltip`     = '" . $this->db->escape($desc['tooltip']) . "',
                `placeholder` = '" . $this->db->escape($desc['placeholder']) . "'");
        }
    }

    private function trimOrNull($v): ?string
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        return $v === '' ? null : $v;
    }

    private function nullableEscape(?string $v): string
    {
        return $v === null ? 'NULL' : "'" . $this->db->escape($v) . "'";
    }
}
