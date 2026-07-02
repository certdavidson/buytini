<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * CRUD для кастомних методів доставки/оплати (внутрішні методи EasyCheckout).
 * Не реєструються як OC-розширення — інжектяться в блоки чекауту.
 *
 * Структура: групи (тільки shipping) → методи → multilang descriptions.
 * Окремо — рядки підсумку (subtotal: знижки/збори за обраний метод).
 */
final class CustomMethodsRepository
{
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_PAYMENT  = 'payment';

    public const COST_FIXED      = 'fixed';
    public const COST_WEIGHT     = 'weight';
    public const COST_SUM        = 'sum';
    public const COST_SUM_TOTALS = 'sum_totals';
    public const COST_API        = 'api';

    /** @var \DB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ─── Groups (shipping only) ────────────────────────────────────────────

    /** @return array<int,array> групи типу з кількістю методів */
    public function listGroups(string $type = self::TYPE_SHIPPING): array
    {
        $p = DB_PREFIX;
        $rows = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_group`
            WHERE `type` = '" . $this->db->escape($type) . "'
            ORDER BY `sort_order` ASC, `group_id` ASC")->rows;
        return array_map(static fn($r) => [
            'group_id'   => (int)$r['group_id'],
            'type'       => (string)$r['type'],
            'sort_order' => (int)$r['sort_order'],
            'status'     => (int)$r['status'],
        ], $rows);
    }

    public function addGroup(string $type, int $sortOrder = 0): int
    {
        $p = DB_PREFIX;
        $this->db->query("INSERT INTO `{$p}kit_easycheckout_cm_group`
            SET `type`='" . $this->db->escape($type) . "',
                `sort_order`=" . (int)$sortOrder . ", `status`=1, `date_added`=NOW()");
        return (int)$this->db->getLastId();
    }

    public function deleteGroup(int $groupId): void
    {
        $p = DB_PREFIX;
        // Методи групи від'єднуємо в "без групи" (group_id=0), не видаляємо
        $this->db->query("UPDATE `{$p}kit_easycheckout_cm_method`
            SET `group_id`=0 WHERE `group_id`=" . (int)$groupId);
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_group` WHERE `group_id`=" . (int)$groupId);
    }

    // ─── Methods ───────────────────────────────────────────────────────────

    /** Повний список методів типу з descriptions (для admin UI). */
    public function listMethods(string $type): array
    {
        $p = DB_PREFIX;
        $rows = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_method`
            WHERE `type` = '" . $this->db->escape($type) . "'
            ORDER BY `group_id` ASC, `sort_order` ASC, `method_id` ASC")->rows;
        return array_map([$this, 'hydrateMethod'], $rows);
    }

    public function getMethod(int $methodId): ?array
    {
        $p = DB_PREFIX;
        $row = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_method`
            WHERE `method_id`=" . (int)$methodId . " LIMIT 1");
        return $row->num_rows ? $this->hydrateMethod($row->row) : null;
    }

    private function hydrateMethod(array $r): array
    {
        $p = DB_PREFIX;
        $mid = (int)$r['method_id'];
        $descRows = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_method_description`
            WHERE `method_id`=" . $mid)->rows;
        $descriptions = [];
        foreach ($descRows as $d) {
            $descriptions[(int)$d['language_id']] = [
                'name'                 => (string)$d['name'],
                'description'          => (string)($d['description'] ?? ''),
                'zero_cost_text'       => (string)$d['zero_cost_text'],
                'payment_form_heading' => (string)$d['payment_form_heading'],
                'payment_info_form'    => (string)($d['payment_info_form'] ?? ''),
                'payment_info_mail'    => (string)($d['payment_info_mail'] ?? ''),
            ];
        }
        return [
            'method_id'               => $mid,
            'type'                    => (string)$r['type'],
            'group_id'                => (int)$r['group_id'],
            'code'                    => (string)$r['code'],
            'cost_type'               => (string)$r['cost_type'],
            'cost_value'              => (float)$r['cost_value'],
            'cost_rules'              => $this->jsonCol($r['cost_rules'] ?? null),
            'currency_code'           => (string)$r['currency_code'],
            'tax_class_id'            => (int)$r['tax_class_id'],
            'order_status_id'         => (int)$r['order_status_id'],
            'conditions'              => $this->jsonCol($r['conditions'] ?? null),
            'condition_expr'          => (string)$r['condition_expr'],
            'placeholder_always'      => (int)$r['placeholder_always'],
            'placeholder_unavailable' => (int)$r['placeholder_unavailable'],
            'params'                  => $this->jsonCol($r['params'] ?? null),
            'sort_order'              => (int)$r['sort_order'],
            'status'                  => (int)$r['status'],
            'descriptions'            => $descriptions,
        ];
    }

    /** Створити порожній метод, повертає method_id. */
    public function addMethod(string $type, int $groupId = 0): int
    {
        $p = DB_PREFIX;
        $this->db->query("INSERT INTO `{$p}kit_easycheckout_cm_method`
            SET `type`='" . $this->db->escape($type) . "',
                `group_id`=" . (int)$groupId . ",
                `code`='', `status`=1, `date_added`=NOW(), `date_modified`=NOW()");
        $mid = (int)$this->db->getLastId();
        // code за замовчуванням — okec_cm_{id}
        $this->db->query("UPDATE `{$p}kit_easycheckout_cm_method`
            SET `code`='okec_cm_" . $mid . "' WHERE `method_id`=" . $mid);
        return $mid;
    }

    public function saveMethod(int $methodId, array $data): void
    {
        $p = DB_PREFIX;
        $set = [
            "`group_id`=" . (int)($data['group_id'] ?? 0),
            "`cost_type`='" . $this->db->escape((string)($data['cost_type'] ?? self::COST_FIXED)) . "'",
            "`cost_value`=" . (float)($data['cost_value'] ?? 0),
            "`cost_rules`=" . $this->jsonEscape($data['cost_rules'] ?? null),
            "`currency_code`='" . $this->db->escape((string)($data['currency_code'] ?? '')) . "'",
            "`tax_class_id`=" . (int)($data['tax_class_id'] ?? 0),
            "`order_status_id`=" . (int)($data['order_status_id'] ?? 0),
            "`conditions`=" . $this->jsonEscape($data['conditions'] ?? null),
            "`condition_expr`='" . $this->db->escape((string)($data['condition_expr'] ?? '')) . "'",
            "`placeholder_always`=" . (!empty($data['placeholder_always']) ? 1 : 0),
            "`placeholder_unavailable`=" . (!empty($data['placeholder_unavailable']) ? 1 : 0),
            "`params`=" . $this->jsonEscape($data['params'] ?? null),
            "`sort_order`=" . (int)($data['sort_order'] ?? 0),
            "`status`=" . (!empty($data['status']) ? 1 : 0),
            "`date_modified`=NOW()",
        ];
        if (isset($data['code']) && $data['code'] !== '') {
            $set[] = "`code`='" . $this->db->escape((string)$data['code']) . "'";
        }
        $this->db->query("UPDATE `{$p}kit_easycheckout_cm_method`
            SET " . implode(', ', $set) . " WHERE `method_id`=" . (int)$methodId);

        $this->saveMethodDescriptions($methodId, (array)($data['descriptions'] ?? []));
    }

    private function saveMethodDescriptions(int $methodId, array $descriptions): void
    {
        $p = DB_PREFIX;
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_method_description`
            WHERE `method_id`=" . (int)$methodId);
        foreach ($descriptions as $langId => $d) {
            $this->db->query("INSERT INTO `{$p}kit_easycheckout_cm_method_description` SET
                `method_id`=" . (int)$methodId . ",
                `language_id`=" . (int)$langId . ",
                `name`='" . $this->db->escape((string)($d['name'] ?? '')) . "',
                `description`='" . $this->db->escape((string)($d['description'] ?? '')) . "',
                `zero_cost_text`='" . $this->db->escape((string)($d['zero_cost_text'] ?? '')) . "',
                `payment_form_heading`='" . $this->db->escape((string)($d['payment_form_heading'] ?? '')) . "',
                `payment_info_form`='" . $this->db->escape((string)($d['payment_info_form'] ?? '')) . "',
                `payment_info_mail`='" . $this->db->escape((string)($d['payment_info_mail'] ?? '')) . "'");
        }
    }

    public function deleteMethod(int $methodId): void
    {
        $p = DB_PREFIX;
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_method` WHERE `method_id`=" . (int)$methodId);
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_method_description` WHERE `method_id`=" . (int)$methodId);
    }

    public function setMethodStatus(int $methodId, bool $status): void
    {
        $p = DB_PREFIX;
        $this->db->query("UPDATE `{$p}kit_easycheckout_cm_method`
            SET `status`=" . ($status ? 1 : 0) . ", `date_modified`=NOW()
            WHERE `method_id`=" . (int)$methodId);
    }

    public function cloneMethod(int $methodId): int
    {
        $src = $this->getMethod($methodId);
        if (!$src) return 0;
        $newId = $this->addMethod($src['type'], $src['group_id']);
        $src['code'] = 'okec_cm_' . $newId;
        $this->saveMethod($newId, $src);
        return $newId;
    }

    // ─── Subtotal rows («Облік у замовленні») ──────────────────────────────

    public function listSubtotals(): array
    {
        $p = DB_PREFIX;
        $rows = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_subtotal`
            ORDER BY `sort_order` ASC, `subtotal_id` ASC")->rows;
        return array_map([$this, 'hydrateSubtotal'], $rows);
    }

    public function getSubtotal(int $id): ?array
    {
        $p = DB_PREFIX;
        $row = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_subtotal`
            WHERE `subtotal_id`=" . (int)$id . " LIMIT 1");
        return $row->num_rows ? $this->hydrateSubtotal($row->row) : null;
    }

    private function hydrateSubtotal(array $r): array
    {
        $p = DB_PREFIX;
        $id = (int)$r['subtotal_id'];
        $descRows = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_cm_subtotal_description`
            WHERE `subtotal_id`=" . $id)->rows;
        $descriptions = [];
        foreach ($descRows as $d) {
            $descriptions[(int)$d['language_id']] = ['name' => (string)$d['name']];
        }
        return [
            'subtotal_id'  => $id,
            'rules'        => $this->jsonCol($r['rules'] ?? null),
            'sort_order'   => (int)$r['sort_order'],
            'status'       => (int)$r['status'],
            'descriptions' => $descriptions,
        ];
    }

    public function addSubtotal(): int
    {
        $p = DB_PREFIX;
        $this->db->query("INSERT INTO `{$p}kit_easycheckout_cm_subtotal`
            SET `status`=1, `sort_order`=0, `date_added`=NOW()");
        return (int)$this->db->getLastId();
    }

    public function saveSubtotal(int $id, array $data): void
    {
        $p = DB_PREFIX;
        $this->db->query("UPDATE `{$p}kit_easycheckout_cm_subtotal` SET
            `rules`=" . $this->jsonEscape($data['rules'] ?? null) . ",
            `sort_order`=" . (int)($data['sort_order'] ?? 0) . ",
            `status`=" . (!empty($data['status']) ? 1 : 0) . "
            WHERE `subtotal_id`=" . (int)$id);
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_subtotal_description`
            WHERE `subtotal_id`=" . (int)$id);
        foreach ((array)($data['descriptions'] ?? []) as $langId => $d) {
            $this->db->query("INSERT INTO `{$p}kit_easycheckout_cm_subtotal_description` SET
                `subtotal_id`=" . (int)$id . ", `language_id`=" . (int)$langId . ",
                `name`='" . $this->db->escape((string)($d['name'] ?? '')) . "'");
        }
    }

    public function deleteSubtotal(int $id): void
    {
        $p = DB_PREFIX;
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_subtotal` WHERE `subtotal_id`=" . (int)$id);
        $this->db->query("DELETE FROM `{$p}kit_easycheckout_cm_subtotal_description` WHERE `subtotal_id`=" . (int)$id);
    }

    // ─── helpers ───────────────────────────────────────────────────────────

    private function jsonCol($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    private function jsonEscape($v): string
    {
        if ($v === null || $v === '' || (is_array($v) && !$v)) return 'NULL';
        $json = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        return "'" . $this->db->escape((string)$json) . "'";
    }
}
