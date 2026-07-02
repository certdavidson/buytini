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

require_once __DIR__ . '/../exceptions/ValidationException.php';

use OcKit\EasyCheckout\Exceptions\ValidationException;

/**
 * CRUD для груп налаштувань (alternative configs).
 *
 * Кожна група — це окремий "проект" розкладки checkout:
 *   - power-merchant може мати default + b2b + wholesale + landing
 *   - на фронт-сторінці `?group=b2b` активує B2B-розкладку
 *   - окремі URL-и/landings → різні воронки в одного магазину
 *
 * Дані: `oc_kit_easycheckout_groups`.
 * Layout групи: `oc_kit_easycheckout_settings` з відповідним group_id.
 */
final class GroupsRepository
{
    /** @var \DB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function list(): array
    {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_groups`
            ORDER BY `is_default` DESC, `sort_order`, `name`")->rows;

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'group_id'   => (int)$r['group_id'],
                'name'       => (string)$r['name'],
                'slug'       => (string)$r['slug'],
                'is_default' => (int)$r['is_default'],
                'sort_order' => (int)$r['sort_order'],
                'date_added' => (string)$r['date_added'],
            ];
        }
        return $out;
    }

    public function get(int $groupId): ?array
    {
        $row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_groups`
            WHERE `group_id` = " . $groupId . " LIMIT 1")->row;
        if (!$row) return null;
        return [
            'group_id'   => (int)$row['group_id'],
            'name'       => (string)$row['name'],
            'slug'       => (string)$row['slug'],
            'is_default' => (int)$row['is_default'],
            'sort_order' => (int)$row['sort_order'],
            'date_added' => (string)$row['date_added'],
        ];
    }

    public function getBySlug(string $slug): ?array
    {
        $row = $this->db->query("SELECT `group_id` FROM `" . DB_PREFIX . "kit_easycheckout_groups`
            WHERE `slug` = '" . $this->db->escape($slug) . "' LIMIT 1");
        return $row->num_rows ? $this->get((int)$row->row['group_id']) : null;
    }

    public function getDefault(): ?array
    {
        $row = $this->db->query("SELECT `group_id` FROM `" . DB_PREFIX . "kit_easycheckout_groups`
            WHERE `is_default` = 1 LIMIT 1");
        return $row->num_rows ? $this->get((int)$row->row['group_id']) : null;
    }

    public function add(array $data): int
    {
        $clean = $this->validateAndNormalize($data, null);

        // Якщо нова — default, скидаємо default з усіх інших.
        if ($clean['is_default']) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_groups` SET `is_default` = 0");
        }

        $now = date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_groups` SET
            `name`       = '" . $this->db->escape($clean['name']) . "',
            `slug`       = '" . $this->db->escape($clean['slug']) . "',
            `is_default` = " . (int)$clean['is_default'] . ",
            `sort_order` = " . (int)$clean['sort_order'] . ",
            `date_added` = '{$now}'");

        return (int)$this->db->getLastId();
    }

    public function update(int $groupId, array $data): void
    {
        $existing = $this->get($groupId);
        if (!$existing) {
            throw new ValidationException(['group_id' => 'not_found']);
        }
        $clean = $this->validateAndNormalize($data, $groupId);

        if ($clean['is_default']) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_groups`
                SET `is_default` = 0 WHERE `group_id` <> " . $groupId);
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_groups` SET
            `name`       = '" . $this->db->escape($clean['name']) . "',
            `slug`       = '" . $this->db->escape($clean['slug']) . "',
            `is_default` = " . (int)$clean['is_default'] . ",
            `sort_order` = " . (int)$clean['sort_order'] . "
            WHERE `group_id` = " . $groupId);
    }

    public function delete(int $groupId): void
    {
        $existing = $this->get($groupId);
        if (!$existing) return;
        if ($existing['is_default']) {
            throw new ValidationException(['group_id' => 'cannot_delete_default']);
        }
        // Видаляємо саму групу
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_groups`
            WHERE `group_id` = " . $groupId);
        // Видаляємо її settings (всі layout записи з цим group_id)
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `group_id` = " . $groupId);
    }

    /**
     * Створює нову групу копіюючи всі settings з src-групи.
     */
    public function clone(int $sourceId, array $newGroupData): int
    {
        $src = $this->get($sourceId);
        if (!$src) {
            throw new ValidationException(['source_group_id' => 'not_found']);
        }
        $newGroupData['is_default'] = 0;   // клон ніколи не default

        $newId = $this->add($newGroupData);

        // Копіюємо settings (layout, та інші per-group записи) з src
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `group_id` = " . $sourceId)->rows;
        foreach ($rows as $r) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings` SET
                `store_id`   = " . (int)$r['store_id'] . ",
                `group_id`   = " . $newId . ",
                `code`       = '" . $this->db->escape($r['code']) . "',
                `key`        = '" . $this->db->escape($r['key'])  . "',
                `value`      = '" . $this->db->escape($r['value']). "',
                `serialized` = " . (int)$r['serialized']);
        }
        return $newId;
    }

    /** Гарантує наявність default-групи — створює якщо ніколи не існувала. */
    public function ensureDefault(): int
    {
        $def = $this->getDefault();
        if ($def) return $def['group_id'];
        return $this->add([
            'name'       => 'Default',
            'slug'       => 'default',
            'is_default' => 1,
            'sort_order' => 0,
        ]);
    }

    private function validateAndNormalize(array $data, ?int $editingId): array
    {
        $errors = [];

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'required';
        } elseif (mb_strlen($name) > 64) {
            $errors['name'] = 'too_long';
        }

        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            $errors['slug'] = 'required';
        } elseif (!preg_match('~^[a-z0-9][a-z0-9_-]{0,63}$~', $slug)) {
            $errors['slug'] = 'invalid_format';
        } else {
            $r = $this->db->query("SELECT `group_id` FROM `" . DB_PREFIX . "kit_easycheckout_groups`
                WHERE `slug` = '" . $this->db->escape($slug) . "' LIMIT 1");
            if ($r->num_rows && (int)$r->row['group_id'] !== ($editingId ?? -1)) {
                $errors['slug'] = 'duplicate';
            }
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return [
            'name'       => $name,
            'slug'       => $slug,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
        ];
    }
}
