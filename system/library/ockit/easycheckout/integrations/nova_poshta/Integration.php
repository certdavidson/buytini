<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Integrations\NovaPoshta
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Integrations\NovaPoshta;

use OcKit\EasyCheckout\Integrations\AbstractIntegration;

require_once __DIR__ . '/Client.php';

/**
 * Інтеграція Нової Пошти. Власні таблиці:
 *   kit_easycheckout_nova_poshta_cities      (Ref, Description, AreaRef, ...)
 *   kit_easycheckout_nova_poshta_warehouses  (Ref, CityRef, Type, Description, ...)
 */
final class Integration extends AbstractIntegration
{
    public function getCode(): string        { return 'nova_poshta'; }
    public function getName(): string        { return $this->t('name'); }
    public function getDescription(): string { return $this->t('description'); }
    public function getCategory(): string    { return 'shipping'; }
    public function getIcon(): string        { return 'truck'; }
    public function getCountryIso2(): string { return 'UA'; }

    public function getSettingsSchema(): array
    {
        $api  = ['key' => 'api',     'label' => 'API доступ',  'icon' => 'key'];
        $beh  = ['key' => 'behaviour','label' => 'Поведінка',   'icon' => 'sliders'];
        $cache = ['key' => 'cache',  'label' => 'Кеш',          'icon' => 'database'];
        return [
            ['key' => 'api_key', 'type' => 'text', 'section' => $api,
             'label' => $this->t('settings.api_key'),
             'help'  => $this->t('settings.api_key.help'), 'default' => ''],
            ['key' => 'warehouse_types', 'type' => 'multiselect', 'section' => $beh,
             'label' => $this->t('settings.warehouse_types'),
             'help'  => $this->t('settings.warehouse_types.help'),
             'default' => ['branch', 'postomat'],
             'options' => [
                 ['value' => 'branch',   'label' => $this->t('option.branch')],
                 ['value' => 'cargo',    'label' => $this->t('option.cargo')],
                 ['value' => 'postomat', 'label' => $this->t('option.postomat')],
             ]],
            ['key' => 'cache_ttl_hours', 'type' => 'number', 'section' => $cache,
             'label' => $this->t('settings.cache_ttl_hours'),
             'help'  => $this->t('settings.cache_ttl_hours.help'), 'default' => 24],
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            ['code' => 'np_city',      'label' => $this->t('field.np_city'),      'group' => 'shipping'],
            ['code' => 'np_warehouse', 'label' => $this->t('field.np_warehouse'), 'group' => 'shipping', 'depends_on' => 'np_city'],
        ];
    }

    public function getDefaultBlocks(): array
    {
        return [[
            'code'   => 'np_shipping_block',
            'name'   => $this->t('block.np_shipping_block'),
            'fields' => [
                ['type' => 'np_city',      'code' => 'np_city',      'required' => true],
                ['type' => 'np_warehouse', 'code' => 'np_warehouse', 'required' => true],
            ],
        ]];
    }

    public function getOwnedTables(): array
    {
        return [
            'kit_easycheckout_nova_poshta_cities',
            'kit_easycheckout_nova_poshta_warehouses',
        ];
    }

    public function installSchema(\DB $db): void
    {
        $p = DB_PREFIX;
        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_nova_poshta_cities` (
            `ref` CHAR(36) NOT NULL,
            `description_uk` VARCHAR(191) NOT NULL DEFAULT '',
            `description_ru` VARCHAR(191) NOT NULL DEFAULT '',
            `area_ref` CHAR(36) NOT NULL DEFAULT '',
            `area_description` VARCHAR(128) NOT NULL DEFAULT '',
            `search_key` VARCHAR(191) NOT NULL DEFAULT '',
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`),
            KEY `search_key` (`search_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_nova_poshta_warehouses` (
            `ref` CHAR(36) NOT NULL,
            `city_ref` CHAR(36) NOT NULL,
            `type` VARCHAR(16) NOT NULL DEFAULT 'branch',
            `number` VARCHAR(16) NOT NULL DEFAULT '',
            `description_uk` VARCHAR(255) NOT NULL DEFAULT '',
            `description_ru` VARCHAR(255) NOT NULL DEFAULT '',
            `short_address` VARCHAR(255) NOT NULL DEFAULT '',
            `lat` DECIMAL(10,7) NULL,
            `lng` DECIMAL(10,7) NULL,
            `max_weight` DECIMAL(8,2) NULL,
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ref`),
            KEY `city_type` (`city_ref`, `type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function runAction(string $action, array $payload = []): array
    {
        $apiKey = (string)($this->getSettings()['api_key'] ?? '');
        switch ($action) {
            case 'test_connection':
                if ($apiKey === '') return ['success' => false, 'message' => 'API-ключ не задано'];
                try {
                    $ok = (new Client($apiKey))->ping();
                    return $ok
                        ? ['success' => true,  'message' => "З'єднання з API НП — OK"]
                        : ['success' => false, 'message' => 'Порожня відповідь API'];
                } catch (\Throwable $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
        }
        return parent::runAction($action, $payload);
    }

    public function refreshCache(\DB $db): array
    {
        $apiKey = (string)($this->getSettings()['api_key'] ?? '');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'API-ключ не задано', 'stats' => []];
        }
        $client = new Client($apiKey);
        $stats  = ['cities' => 0, 'warehouses' => 0, 'pages' => 0];

        try {
            $stats['cities'] = $this->fetchAndStoreCities($client, $db);
            $stats['warehouses'] = $this->fetchAndStoreWarehouses($client, $db);
            $stats['pages'] = (int)ceil($stats['cities'] / 500) + (int)ceil($stats['warehouses'] / 500);
            return ['success' => true, 'message' => 'Кеш оновлено', 'stats' => $stats];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'stats' => $stats];
        }
    }

    private function fetchAndStoreCities(Client $client, \DB $db): int
    {
        $p = DB_PREFIX;
        $page = 1; $limit = 500; $total = 0;
        do {
            $rows = $client->getCities($page, $limit);
            foreach ($rows as $r) {
                $ref     = (string)($r['Ref'] ?? '');
                if ($ref === '') continue;
                $descUk  = (string)($r['Description'] ?? '');
                $descRu  = (string)($r['DescriptionRu'] ?? $descUk);
                $areaRef = (string)($r['Area'] ?? '');
                $area    = (string)($r['AreaDescription'] ?? '');
                $search  = mb_strtolower($descUk . ' ' . $descRu);
                $json    = $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE));

                $db->query("INSERT INTO `{$p}kit_easycheckout_nova_poshta_cities`
                    (`ref`, `description_uk`, `description_ru`, `area_ref`, `area_description`, `search_key`, `data`, `updated_at`)
                    VALUES ('" . $db->escape($ref) . "',
                            '" . $db->escape($descUk) . "',
                            '" . $db->escape($descRu) . "',
                            '" . $db->escape($areaRef) . "',
                            '" . $db->escape($area) . "',
                            '" . $db->escape($search) . "',
                            '" . $json . "',
                            NOW())
                    ON DUPLICATE KEY UPDATE
                        `description_uk`=VALUES(`description_uk`),
                        `description_ru`=VALUES(`description_ru`),
                        `area_ref`=VALUES(`area_ref`),
                        `area_description`=VALUES(`area_description`),
                        `search_key`=VALUES(`search_key`),
                        `data`=VALUES(`data`),
                        `updated_at`=NOW()");
                $total++;
            }
            $page++;
        } while (count($rows) === $limit);
        return $total;
    }

    private function fetchAndStoreWarehouses(Client $client, \DB $db): int
    {
        $p = DB_PREFIX;
        $page = 1; $limit = 500; $total = 0;
        do {
            $rows = $client->getWarehouses('', $page, $limit);
            foreach ($rows as $r) {
                $ref = (string)($r['Ref'] ?? '');
                if ($ref === '') continue;
                $cityRef = (string)($r['CityRef'] ?? '');
                $type    = $this->mapWarehouseType((string)($r['CategoryOfWarehouse'] ?? ''));
                $number  = (string)($r['Number'] ?? '');
                $descUk  = (string)($r['Description'] ?? '');
                $descRu  = (string)($r['DescriptionRu'] ?? $descUk);
                $addr    = (string)($r['ShortAddress'] ?? '');
                $lat     = $r['Latitude']  !== '' ? (float)$r['Latitude']  : null;
                $lng     = $r['Longitude'] !== '' ? (float)$r['Longitude'] : null;
                $mw      = isset($r['TotalMaxWeightAllowed']) ? (float)$r['TotalMaxWeightAllowed'] : null;
                $json    = $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE));

                $db->query("INSERT INTO `{$p}kit_easycheckout_nova_poshta_warehouses`
                    (`ref`, `city_ref`, `type`, `number`, `description_uk`, `description_ru`, `short_address`, `lat`, `lng`, `max_weight`, `data`, `updated_at`)
                    VALUES ('" . $db->escape($ref) . "',
                            '" . $db->escape($cityRef) . "',
                            '" . $db->escape($type) . "',
                            '" . $db->escape($number) . "',
                            '" . $db->escape($descUk) . "',
                            '" . $db->escape($descRu) . "',
                            '" . $db->escape($addr) . "',
                            " . ($lat === null ? 'NULL' : (float)$lat) . ",
                            " . ($lng === null ? 'NULL' : (float)$lng) . ",
                            " . ($mw  === null ? 'NULL' : (float)$mw) . ",
                            '" . $json . "',
                            NOW())
                    ON DUPLICATE KEY UPDATE
                        `city_ref`=VALUES(`city_ref`),
                        `type`=VALUES(`type`),
                        `number`=VALUES(`number`),
                        `description_uk`=VALUES(`description_uk`),
                        `description_ru`=VALUES(`description_ru`),
                        `short_address`=VALUES(`short_address`),
                        `lat`=VALUES(`lat`),
                        `lng`=VALUES(`lng`),
                        `max_weight`=VALUES(`max_weight`),
                        `data`=VALUES(`data`),
                        `updated_at`=NOW()");
                $total++;
            }
            $page++;
        } while (count($rows) === $limit);
        return $total;
    }

    public function searchLocations(string $type, string $query, array $context, \DB $db): array
    {
        $p     = DB_PREFIX;
        $q     = mb_strtolower(trim($query));
        $esc   = $db->escape($q);
        $lang  = $this->detectLangSuffix();
        $limit = min(100, max(10, (int)($context['limit'] ?? 50)));
        $page  = max(1, (int)($context['page'] ?? 1));
        $offset= ($page - 1) * $limit;

        if ($type === 'city') {
            $rows = $db->query("SELECT `ref`, `description_uk`, `description_ru`, `area_description`
                FROM `{$p}kit_easycheckout_nova_poshta_cities`
                WHERE `search_key` LIKE '%" . $esc . "%'
                ORDER BY CHAR_LENGTH(`description_uk`) ASC
                LIMIT " . $offset . ", " . $limit)->rows;
            $out = [];
            foreach ($rows as $r) {
                $name = $lang === 'ru' ? ($r['description_ru'] ?: $r['description_uk']) : $r['description_uk'];
                $out[] = ['id' => $r['ref'], 'label' => $name . ' (' . $r['area_description'] . ')'];
            }
            return $out;
        }

        if ($type === 'warehouse') {
            $cityRef = (string)($context['city_ref'] ?? $context['parent'] ?? '');
            if ($cityRef === '') return [];
            $allowed = (array)($this->getSettings()['warehouse_types'] ?? ['branch', 'postomat']);
            if (!$allowed) $allowed = ['branch'];
            $allowedSql = "'" . implode("','", array_map([$db, 'escape'], $allowed)) . "'";

            $where = "`city_ref`='" . $db->escape($cityRef) . "' AND `type` IN (" . $allowedSql . ")";
            if ($q !== '') {
                $where .= " AND (LOWER(`description_uk`) LIKE '%" . $esc . "%'
                              OR LOWER(`description_ru`) LIKE '%" . $esc . "%'
                              OR `number` LIKE '%" . $esc . "%')";
            }
            $rows = $db->query("SELECT `ref`, `number`, `type`, `description_uk`, `description_ru`, `short_address`
                FROM `{$p}kit_easycheckout_nova_poshta_warehouses`
                WHERE " . $where . "
                ORDER BY CAST(`number` AS UNSIGNED) ASC
                LIMIT " . $offset . ", " . $limit)->rows;
            $out = [];
            foreach ($rows as $r) {
                $name = $lang === 'ru' ? ($r['description_ru'] ?: $r['description_uk']) : $r['description_uk'];
                $out[] = ['id' => $r['ref'], 'label' => '№' . $r['number'] . ': ' . $name,
                          'meta' => ['type' => $r['type'], 'address' => $r['short_address']]];
            }
            return $out;
        }
        return [];
    }

    private function detectLangSuffix(): string
    {
        if (!empty($_SESSION['language']) && strpos((string)$_SESSION['language'], 'ru') === 0) return 'ru';
        return 'uk';
    }

    private function mapWarehouseType(string $category): string
    {
        $c = mb_strtolower($category);
        if (strpos($c, 'postomat') !== false || strpos($c, 'поштомат') !== false) return 'postomat';
        if (strpos($c, 'cargo')    !== false || strpos($c, 'вантаж')    !== false) return 'cargo';
        return 'branch';
    }
}
