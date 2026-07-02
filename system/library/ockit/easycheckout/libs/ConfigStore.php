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

/**
 * Доступ до конфігурації модуля у таблиці `oc_kit_easycheckout_settings`.
 *
 * Дерево організоване по (store_id, group_id, code, key) — де `code` це секція
 * (наприклад "general", "page_layout", "blocks.customer"), а `key` — конкретний параметр.
 *
 * Fallback-логіка: якщо для (store_id=N, group_id=M) немає запису —
 * читаємо з (store_id=0, group_id=M); потім (store_id=0, group_id=0).
 */
final class ConfigStore
{
    /** @var \DB */
    private $db;

    private int $storeId;
    private int $groupId;

    /** @var array<string,mixed> Кеш у пам'яті в межах запиту */
    private array $memo = [];

    public function __construct($db, int $storeId = 0, int $groupId = 0)
    {
        $this->db      = $db;
        $this->storeId = $storeId;
        $this->groupId = $groupId;
    }

    public function setStore(int $storeId): self
    {
        $this->storeId = $storeId;
        $this->memo    = [];
        return $this;
    }

    public function setGroup(int $groupId): self
    {
        $this->groupId = $groupId;
        $this->memo    = [];
        return $this;
    }

    /**
     * Повертає значення з fallback-ланцюжком.
     */
    public function get(string $code, string $key, $default = null)
    {
        $memoKey = "{$this->storeId}|{$this->groupId}|{$code}|{$key}";
        if (array_key_exists($memoKey, $this->memo)) {
            return $this->memo[$memoKey];
        }

        $candidates = $this->fallbackCandidates();
        foreach ($candidates as [$storeId, $groupId]) {
            $row = $this->db->query("SELECT `value`,`serialized` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
                WHERE `store_id`=" . (int)$storeId . "
                  AND `group_id`=" . (int)$groupId . "
                  AND `code`='" . $this->db->escape($code) . "'
                  AND `key`='"  . $this->db->escape($key)  . "'
                LIMIT 1");
            if ($row->num_rows) {
                $value = $row->row['serialized']
                    ? json_decode($row->row['value'], true)
                    : $row->row['value'];
                return $this->memo[$memoKey] = $value;
            }
        }
        return $this->memo[$memoKey] = $default;
    }

    /**
     * Записує значення для поточних (store_id, group_id).
     */
    public function set(string $code, string $key, $value): void
    {
        $serialized = !is_string($value) ? 1 : 0;
        $stored = $serialized
            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string)$value;

        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `store_id`=" . (int)$this->storeId . "
              AND `group_id`=" . (int)$this->groupId . "
              AND `code`='" . $this->db->escape($code) . "'
              AND `key`='"  . $this->db->escape($key)  . "'");

        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
            SET `store_id`=" . (int)$this->storeId . ",
                `group_id`=" . (int)$this->groupId . ",
                `code`='" . $this->db->escape($code) . "',
                `key`='"  . $this->db->escape($key)  . "',
                `value`='" . $this->db->escape($stored) . "',
                `serialized`=" . (int)$serialized);

        unset($this->memo["{$this->storeId}|{$this->groupId}|{$code}|{$key}"]);
    }

    /**
     * Повертає всі записи у заданій секції.
     * @return array<string,mixed>
     */
    public function getSection(string $code): array
    {
        $result = [];
        foreach ($this->fallbackCandidates() as [$storeId, $groupId]) {
            $rows = $this->db->query("SELECT `key`,`value`,`serialized` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
                WHERE `store_id`=" . (int)$storeId . "
                  AND `group_id`=" . (int)$groupId . "
                  AND `code`='" . $this->db->escape($code) . "'");
            foreach ($rows->rows as $r) {
                if (!array_key_exists($r['key'], $result)) {
                    $result[$r['key']] = $r['serialized']
                        ? json_decode($r['value'], true)
                        : $r['value'];
                }
            }
        }
        return $result;
    }

    /**
     * Клонує всі записи з (store, sourceGroup) у (store, targetGroup).
     */
    public function cloneGroup(int $sourceGroupId, int $targetGroupId): void
    {
        $rows = $this->db->query("SELECT `code`,`key`,`value`,`serialized` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `store_id`=" . (int)$this->storeId . "
              AND `group_id`=" . (int)$sourceGroupId);

        foreach ($rows->rows as $r) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
                SET `store_id`=" . (int)$this->storeId . ",
                    `group_id`=" . (int)$targetGroupId . ",
                    `code`='" . $this->db->escape($r['code']) . "',
                    `key`='"  . $this->db->escape($r['key'])  . "',
                    `value`='" . $this->db->escape($r['value']) . "',
                    `serialized`=" . (int)$r['serialized']);
        }
    }

    /**
     * Послідовність (store, group) для fallback читання.
     */
    private function fallbackCandidates(): array
    {
        $candidates = [[$this->storeId, $this->groupId]];
        if ($this->storeId !== 0) {
            $candidates[] = [0, $this->groupId];
        }
        if ($this->groupId !== 0) {
            $candidates[] = [$this->storeId, 0];
            if ($this->storeId !== 0) {
                $candidates[] = [0, 0];
            }
        }
        return $candidates;
    }
}
