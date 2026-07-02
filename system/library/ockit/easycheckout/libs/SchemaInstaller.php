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
 * Створює та видаляє всі таблиці модуля.
 * Викликається ТІЛЬКИ з ModelExtensionModuleOcKitEasycheckout::install() / uninstall().
 */
final class SchemaInstaller
{
    /** @var \DB */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createAll(): void
    {
        $prefix = DB_PREFIX;

        // ── 1. Settings tree (per store/group/code/key) ──────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_settings` (
            `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
            `store_id` INT(11) NOT NULL DEFAULT 0,
            `group_id` INT(11) NOT NULL DEFAULT 0,
            `code` VARCHAR(64) NOT NULL,
            `key` VARCHAR(128) NOT NULL,
            `value` MEDIUMTEXT NOT NULL,
            `serialized` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`setting_id`),
            KEY `lookup` (`store_id`,`group_id`,`code`,`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 2. Settings groups (alternative configs) ─────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_groups` (
            `group_id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(64) NOT NULL,
            `slug` VARCHAR(64) NOT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `date_added` DATETIME NOT NULL,
            PRIMARY KEY (`group_id`),
            UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 3. Fields registry ────────────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_fields` (
            `field_id` INT(11) NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(64) NOT NULL,
            `type` VARCHAR(32) NOT NULL,
            `belongs_to` ENUM('customer','address','order') NOT NULL DEFAULT 'order',
            `mask_mode` ENUM('manual','api') NOT NULL DEFAULT 'manual',
            `mask_value` VARCHAR(255) NULL,
            `default_mode` ENUM('manual','api') NOT NULL DEFAULT 'manual',
            `default_value` TEXT NULL,
            `save_to_comment` TINYINT(1) NOT NULL DEFAULT 0,
            `validation_rules` MEDIUMTEXT NULL,
            `params` MEDIUMTEXT NULL,
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`field_id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_fields_description` (
            `field_id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `name` VARCHAR(255) NULL,
            `tooltip` TEXT NULL,
            `placeholder` VARCHAR(255) NULL,
            PRIMARY KEY (`field_id`,`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 4. Headings registry ──────────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_headings` (
            `heading_id` INT(11) NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(64) NOT NULL,
            `tag` VARCHAR(16) NOT NULL DEFAULT 'h3',
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`heading_id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_headings_description` (
            `heading_id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `text` VARCHAR(255) NULL,
            PRIMARY KEY (`heading_id`,`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 5. Address formats ────────────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_address_formats` (
            `format_id` INT(11) NOT NULL AUTO_INCREMENT,
            `scope` ENUM('customer_group','shipping') NOT NULL,
            `scope_id` VARCHAR(64) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `template` TEXT NOT NULL,
            PRIMARY KEY (`format_id`),
            KEY `lookup` (`scope`,`scope_id`,`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 6. Order restrictions ─────────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_order_restrictions` (
            `restriction_id` INT(11) NOT NULL AUTO_INCREMENT,
            `group_id` INT(11) NOT NULL DEFAULT 0,
            `customer_group_ids` VARCHAR(255) NULL,
            `min_total` DECIMAL(15,4) NULL,
            `max_total` DECIMAL(15,4) NULL,
            `min_qty` INT(11) NULL,
            `max_qty` INT(11) NULL,
            `min_weight` DECIMAL(15,4) NULL,
            `max_weight` DECIMAL(15,4) NULL,
            `error_text` MEDIUMTEXT NULL,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`restriction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 7. Custom fields storage ──────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_order_fields` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `field_code` VARCHAR(64) NOT NULL,
            `value` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_customer_fields` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `customer_id` INT(11) NOT NULL,
            `field_code` VARCHAR(64) NOT NULL,
            `value` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_address_fields` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `address_id` INT(11) NOT NULL,
            `field_code` VARCHAR(64) NOT NULL,
            `value` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `address_id` (`address_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ── 8. Abandoned carts ────────────────────────────────────────────────
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_abandoned` (
            `abandoned_id` INT(11) NOT NULL AUTO_INCREMENT,
            `store_id` INT(11) NOT NULL DEFAULT 0,
            `group_id` INT(11) NOT NULL DEFAULT 0,
            `customer_id` INT(11) NULL,
            `email` VARCHAR(96) NULL,
            `firstname` VARCHAR(64) NULL,
            `lastname` VARCHAR(64) NULL,
            `telephone` VARCHAR(32) NULL,
            `total` DECIMAL(15,4) NULL,
            `currency_code` VARCHAR(3) NULL,
            `language_id` INT(11) NULL,
            `notified_at` DATETIME NULL,
            `recovered_order_id` INT(11) NULL,
            `recovery_token` VARCHAR(64) NULL,
            `ip` VARCHAR(64) NULL,
            `user_agent` VARCHAR(255) NULL,
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`abandoned_id`),
            KEY `email` (`email`),
            KEY `customer_id` (`customer_id`),
            KEY `recovery_token` (`recovery_token`),
            KEY `date_added` (`date_added`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}kit_easycheckout_abandoned_products` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `abandoned_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `model` VARCHAR(64) NULL,
            `quantity` INT(11) NOT NULL DEFAULT 1,
            `price` DECIMAL(15,4) NULL,
            `option_data` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `abandoned_id` (`abandoned_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->createCustomMethodTables();
    }

    /**
     * Таблиці кастомних методів доставки/оплати (filterit-паритет).
     * Ідемпотентно (IF NOT EXISTS) — викликається з createAll() і migrate().
     */
    public function createCustomMethodTables(): void
    {
        $p = DB_PREFIX;

        // Групи варіантів доставки (оплата — без груп, group_id=0)
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_cm_group` (
            `group_id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(16) NOT NULL DEFAULT 'shipping',
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`group_id`),
            KEY `type_sort` (`type`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Варіанти методів (shipping|payment)
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_cm_method` (
            `method_id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(16) NOT NULL DEFAULT 'shipping',
            `group_id` INT(11) NOT NULL DEFAULT 0,
            `code` VARCHAR(64) NOT NULL,
            `cost_type` VARCHAR(24) NOT NULL DEFAULT 'fixed',
            `cost_value` DECIMAL(15,4) NOT NULL DEFAULT 0,
            `cost_rules` JSON NULL,
            `currency_code` VARCHAR(3) NOT NULL DEFAULT '',
            `tax_class_id` INT(11) NOT NULL DEFAULT 0,
            `order_status_id` INT(11) NOT NULL DEFAULT 0,
            `conditions` JSON NULL,
            `condition_expr` VARCHAR(255) NOT NULL DEFAULT '',
            `placeholder_always` TINYINT(1) NOT NULL DEFAULT 0,
            `placeholder_unavailable` TINYINT(1) NOT NULL DEFAULT 0,
            `params` JSON NULL,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`method_id`),
            UNIQUE KEY `code` (`code`),
            KEY `type_group` (`type`, `group_id`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Мультимовні поля варіанта
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_cm_method_description` (
            `method_id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `name` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT NULL,
            `zero_cost_text` VARCHAR(255) NOT NULL DEFAULT '',
            `payment_form_heading` VARCHAR(255) NOT NULL DEFAULT '',
            `payment_info_form` TEXT NULL,
            `payment_info_mail` TEXT NULL,
            PRIMARY KEY (`method_id`, `language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Рядки підсумку (знижки/збори за обраний метод) — «Облік у замовленні»
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_cm_subtotal` (
            `subtotal_id` INT(11) NOT NULL AUTO_INCREMENT,
            `rules` JSON NULL,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`subtotal_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_cm_subtotal_description` (
            `subtotal_id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `name` VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`subtotal_id`, `language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Створює дефолтну групу налаштувань "Default", якщо її ще нема.
     */
    public function ensureDefaultGroup(): int
    {
        $prefix = DB_PREFIX;
        $row = $this->db->query("SELECT `group_id` FROM `{$prefix}kit_easycheckout_groups` WHERE `is_default`=1 LIMIT 1");
        if ($row->num_rows) {
            return (int)$row->row['group_id'];
        }
        $this->db->query("INSERT INTO `{$prefix}kit_easycheckout_groups`
            SET `name`='Default', `slug`='default', `is_default`=1, `sort_order`=0, `date_added`=NOW()");
        return (int)$this->db->getLastId();
    }

    /**
     * Видалити всі таблиці модуля. Викликається у uninstall().
     * Свідома деструктивна операція — користувач підтверджує через адмінку.
     */
    /**
     * Idempotent migrations — додає колонки/індекси що з'явилися в нових версіях.
     * Викликається на кожну admin-сесію (cheap due to information_schema cache).
     * Список migrations розширюється як нові columns додаються.
     */
    public function migrate(): array
    {
        $prefix = DB_PREFIX;
        $applied = [];

        // Нові таблиці (custom methods) — створюються на наявному інсталі
        $this->createCustomMethodTables();

        $migrations = [
            // [table, column, ddl-snippet, description]
            ['kit_easycheckout_abandoned', 'admin_notes',
                "ADD COLUMN `admin_notes` TEXT NULL AFTER `recovery_token`",
                'admin_notes column for sales-team comments'],
            ['kit_easycheckout_fields', 'sort_order',
                "ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `params`",
                'sort_order for manual field reordering'],
            ['kit_easycheckout_headings', 'sort_order',
                "ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `tag`",
                'sort_order for manual heading reordering'],
            ['kit_easycheckout_abandoned', 'reminder_count',
                "ADD COLUMN `reminder_count` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `notified_at`",
                'reminder_count for multi-cadence reminders'],
        ];

        foreach ($migrations as [$table, $column, $ddl, $desc]) {
            if (!$this->columnExists($prefix . $table, $column)) {
                try {
                    $this->db->query("ALTER TABLE `{$prefix}{$table}` " . $ddl);
                    $applied[] = "{$table}.{$column}: {$desc}";
                } catch (\Throwable $e) {
                    // ignore — partial-apply scenario
                }
            }
        }
        return $applied;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = DATABASE()
              AND `TABLE_NAME`   = '" . $this->db->escape($table) . "'
              AND `COLUMN_NAME`  = '" . $this->db->escape($column) . "'");
        return (int)($row->row['cnt'] ?? 0) > 0;
    }

    public function dropAll(): void
    {
        $prefix = DB_PREFIX;
        $tables = [
            'kit_easycheckout_settings',
            'kit_easycheckout_groups',
            'kit_easycheckout_fields',
            'kit_easycheckout_fields_description',
            'kit_easycheckout_headings',
            'kit_easycheckout_headings_description',
            'kit_easycheckout_address_formats',
            'kit_easycheckout_order_restrictions',
            'kit_easycheckout_order_fields',
            'kit_easycheckout_customer_fields',
            'kit_easycheckout_address_fields',
            'kit_easycheckout_abandoned',
            'kit_easycheckout_abandoned_products',
        ];
        foreach ($tables as $t) {
            $this->db->query("DROP TABLE IF EXISTS `{$prefix}{$t}`");
        }
    }
}
