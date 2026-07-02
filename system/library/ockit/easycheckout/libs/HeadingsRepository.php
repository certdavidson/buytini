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
 * CRUD для глобального реєстру заголовків модуля.
 *
 * Заголовок — багатомовний текстовий блок з тегом (h1-h5/legend/none),
 * який вставляється між полями в блоках сторінки checkout.
 */
final class HeadingsRepository
{
    public const VALID_TAGS = ['none', 'h1', 'h2', 'h3', 'h4', 'h5', 'p', 'legend'];

    /** @var \DB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ─── Read ────────────────────────────────────────────────────────────────

    public function list(array $filter = []): array
    {
        $where = ['1=1'];
        if (!empty($filter['tag'])) {
            $where[] = "h.`tag` = '" . $this->db->escape($filter['tag']) . "'";
        }
        if (isset($filter['search']) && $filter['search'] !== '') {
            $term = $this->db->escape($filter['search']);
            $where[] = "(h.`code` LIKE '%{$term}%' OR EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "kit_easycheckout_headings_description` d
                WHERE d.`heading_id` = h.`heading_id` AND d.`text` LIKE '%{$term}%'))";
        }

        $sortable = ['heading_id', 'code', 'tag', 'date_modified', 'sort_order'];
        $explicitSort = in_array($filter['sort'] ?? '', $sortable, true);
        $sort     = $explicitSort ? $filter['sort'] : 'sort_order';
        $order    = (strtoupper($filter['order'] ?? ($explicitSort ? 'DESC' : 'ASC')) === 'ASC') ? 'ASC' : 'DESC';

        $start = max(0, (int)($filter['start'] ?? 0));
        $limit = max(1, (int)($filter['limit'] ?? 50));

        $orderClause = $explicitSort
            ? "h.`{$sort}` {$order}"
            : "h.`sort_order` ASC, h.`heading_id` DESC";

        $sql = "SELECT h.* FROM `" . DB_PREFIX . "kit_easycheckout_headings` h
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderClause}
                LIMIT {$start}, {$limit}";

        $rows = $this->db->query($sql)->rows;
        if (!$rows) return [];

        $ids = array_map(fn($r) => (int)$r['heading_id'], $rows);
        $descriptions = $this->loadDescriptions($ids);

        foreach ($rows as &$r) {
            $r['heading_id']   = (int)$r['heading_id'];
            $r['descriptions'] = $descriptions[$r['heading_id']] ?? [];
            $filled = 0;
            foreach ($r['descriptions'] as $d) {
                if (!empty($d['text']) && trim((string)$d['text']) !== '') $filled++;
            }
            $r['_langs_filled'] = $filled;
        }
        return $rows;
    }

    public function count(array $filter = []): int
    {
        $where = ['1=1'];
        if (!empty($filter['tag'])) {
            $where[] = "h.`tag` = '" . $this->db->escape($filter['tag']) . "'";
        }
        if (isset($filter['search']) && $filter['search'] !== '') {
            $term = $this->db->escape($filter['search']);
            $where[] = "(h.`code` LIKE '%{$term}%' OR EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "kit_easycheckout_headings_description` d
                WHERE d.`heading_id` = h.`heading_id` AND d.`text` LIKE '%{$term}%'))";
        }
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_headings` h
            WHERE " . implode(' AND ', $where))->row;
        return (int)$row['cnt'];
    }

    public function get(int $headingId): ?array
    {
        $row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_headings`
            WHERE `heading_id` = " . $headingId)->row;
        if (!$row) return null;

        $row['heading_id']   = (int)$row['heading_id'];
        $row['descriptions'] = $this->loadDescriptions([$headingId])[$headingId] ?? [];
        return $row;
    }

    public function getByCode(string $code): ?array
    {
        $row = $this->db->query("SELECT `heading_id` FROM `" . DB_PREFIX . "kit_easycheckout_headings`
            WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");
        return $row->num_rows ? $this->get((int)$row->row['heading_id']) : null;
    }

    private function loadDescriptions(array $ids): array
    {
        if (!$ids) return [];
        $idList = implode(',', array_map('intval', $ids));
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_headings_description`
            WHERE `heading_id` IN ({$idList})")->rows;

        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['heading_id']][(int)$r['language_id']] = [
                'text' => (string)$r['text'],
            ];
        }
        return $result;
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    public function add(array $data): int
    {
        $clean = $this->validateAndNormalize($data, null);

        $now = date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_headings` SET
            `code`          = '" . $this->db->escape($clean['code']) . "',
            `tag`           = '" . $this->db->escape($clean['tag'])  . "',
            `date_added`    = '{$now}',
            `date_modified` = '{$now}'");

        $headingId = (int)$this->db->getLastId();
        $this->saveDescriptions($headingId, $clean['descriptions']);
        return $headingId;
    }

    public function update(int $headingId, array $data): void
    {
        $existing = $this->get($headingId);
        if (!$existing) {
            throw new ValidationException(['heading_id' => 'not_found']);
        }
        $clean = $this->validateAndNormalize($data, $headingId);

        $now = date('Y-m-d H:i:s');
        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_headings` SET
            `code`          = '" . $this->db->escape($clean['code']) . "',
            `tag`           = '" . $this->db->escape($clean['tag'])  . "',
            `date_modified` = '{$now}'
            WHERE `heading_id` = " . $headingId);

        $this->saveDescriptions($headingId, $clean['descriptions']);
    }

    public function delete(int $headingId): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_headings` WHERE `heading_id` = " . $headingId);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_headings_description` WHERE `heading_id` = " . $headingId);
    }

    public function deleteMany(array $ids): int
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) return 0;
        $list = implode(',', $ids);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_headings` WHERE `heading_id` IN ({$list})");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_headings_description` WHERE `heading_id` IN ({$list})");
        return count($ids);
    }

    public function generateNextCode(string $prefix = 'heading'): string
    {
        $rows = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "kit_easycheckout_headings`
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

    // ─── Validation ──────────────────────────────────────────────────────────

    private function validateAndNormalize(array $data, ?int $editingId): array
    {
        $errors = [];

        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            $errors['code'] = 'required';
        } elseif (!preg_match('~^[a-z][a-z0-9_]{0,63}$~', $code)) {
            $errors['code'] = 'invalid_format';
        } else {
            $row = $this->db->query("SELECT `heading_id` FROM `" . DB_PREFIX . "kit_easycheckout_headings`
                WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");
            if ($row->num_rows && (int)$row->row['heading_id'] !== ($editingId ?? -1)) {
                $errors['code'] = 'duplicate';
            }
        }

        $tag = (string)($data['tag'] ?? 'h3');
        if (!in_array($tag, self::VALID_TAGS, true)) {
            $errors['tag'] = 'invalid';
        }

        $descriptions = [];
        foreach (($data['descriptions'] ?? []) as $langId => $desc) {
            $langId = (int)$langId;
            if ($langId <= 0) continue;
            $descriptions[$langId] = [
                'text' => trim((string)($desc['text'] ?? '')),
            ];
        }

        $hasText = false;
        foreach ($descriptions as $d) {
            if ($d['text'] !== '') { $hasText = true; break; }
        }
        if (!$hasText && empty($errors)) {
            $errors['text'] = 'required_in_any_language';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return [
            'code'         => $code,
            'tag'          => $tag,
            'descriptions' => $descriptions,
        ];
    }

    private function saveDescriptions(int $headingId, array $descriptions): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_headings_description`
            WHERE `heading_id` = " . $headingId);

        foreach ($descriptions as $languageId => $desc) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_headings_description` SET
                `heading_id`  = " . $headingId . ",
                `language_id` = " . (int)$languageId . ",
                `text`        = '" . $this->db->escape($desc['text']) . "'");
        }
    }
}
