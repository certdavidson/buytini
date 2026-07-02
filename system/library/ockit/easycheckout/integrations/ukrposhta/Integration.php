<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Integrations\Ukrposhta
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Integrations\Ukrposhta;

use OcKit\EasyCheckout\Integrations\AbstractIntegration;

require_once __DIR__ . '/Client.php';

/**
 * Інтеграція Укрпошти. Власні таблиці:
 *   kit_easycheckout_ukrposhta_regions
 *   kit_easycheckout_ukrposhta_districts
 *   kit_easycheckout_ukrposhta_cities
 *   kit_easycheckout_ukrposhta_postoffices
 */
final class Integration extends AbstractIntegration
{
    public function getCode(): string        { return 'ukrposhta'; }
    public function getName(): string        { return $this->t('name'); }
    public function getDescription(): string { return $this->t('description'); }
    public function getCategory(): string    { return 'shipping'; }
    public function getIcon(): string        { return 'mail'; }
    public function getCountryIso2(): string { return 'UA'; }

    public function getSettingsSchema(): array
    {
        $api   = ['key' => 'api',   'label' => 'API доступ', 'icon' => 'key'];
        $cache = ['key' => 'cache', 'label' => 'Кеш',        'icon' => 'database'];
        return [
            ['key' => 'bearer_token', 'type' => 'text', 'section' => $api,
             'label' => $this->t('settings.bearer_token'),
             'help'  => $this->t('settings.bearer_token.help'), 'default' => ''],
            ['key' => 'cache_ttl_hours', 'type' => 'number', 'section' => $cache,
             'label' => $this->t('settings.cache_ttl_hours'),
             'help'  => $this->t('settings.cache_ttl_hours.help'),
             'default' => 168],
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            ['code' => 'up_region',     'label' => $this->t('field.up_region'),     'group' => 'shipping'],
            ['code' => 'up_district',   'label' => $this->t('field.up_district'),   'group' => 'shipping', 'depends_on' => 'up_region'],
            ['code' => 'up_city',       'label' => $this->t('field.up_city'),       'group' => 'shipping', 'depends_on' => 'up_district'],
            ['code' => 'up_postoffice', 'label' => $this->t('field.up_postoffice'), 'group' => 'shipping', 'depends_on' => 'up_city'],
        ];
    }

    public function getOwnedTables(): array
    {
        return [
            'kit_easycheckout_ukrposhta_regions',
            'kit_easycheckout_ukrposhta_districts',
            'kit_easycheckout_ukrposhta_cities',
            'kit_easycheckout_ukrposhta_postoffices',
        ];
    }

    public function installSchema(\DB $db): void
    {
        $p = DB_PREFIX;
        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_ukrposhta_regions` (
            `region_id` INT(11) NOT NULL,
            `name_uk` VARCHAR(128) NOT NULL DEFAULT '',
            `name_ru` VARCHAR(128) NOT NULL DEFAULT '',
            `koatuu` VARCHAR(16) NOT NULL DEFAULT '',
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`region_id`),
            KEY `name_uk` (`name_uk`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_ukrposhta_districts` (
            `district_id` INT(11) NOT NULL,
            `region_id` INT(11) NOT NULL,
            `name_uk` VARCHAR(128) NOT NULL DEFAULT '',
            `name_ru` VARCHAR(128) NOT NULL DEFAULT '',
            `koatuu` VARCHAR(16) NOT NULL DEFAULT '',
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`district_id`),
            KEY `region_id` (`region_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_ukrposhta_cities` (
            `city_id` INT(11) NOT NULL,
            `district_id` INT(11) NOT NULL,
            `region_id` INT(11) NOT NULL,
            `name_uk` VARCHAR(128) NOT NULL DEFAULT '',
            `name_ru` VARCHAR(128) NOT NULL DEFAULT '',
            `type` VARCHAR(16) NOT NULL DEFAULT '',
            `koatuu` VARCHAR(16) NOT NULL DEFAULT '',
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`city_id`),
            KEY `district_id` (`district_id`),
            KEY `name_uk` (`name_uk`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `{$p}kit_easycheckout_ukrposhta_postoffices` (
            `postoffice_id` INT(11) NOT NULL,
            `city_id` INT(11) NOT NULL,
            `postcode` VARCHAR(16) NOT NULL DEFAULT '',
            `name_uk` VARCHAR(255) NOT NULL DEFAULT '',
            `name_ru` VARCHAR(255) NOT NULL DEFAULT '',
            `address` VARCHAR(255) NOT NULL DEFAULT '',
            `lat` DECIMAL(10,7) NULL,
            `lng` DECIMAL(10,7) NULL,
            `data` JSON NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`postoffice_id`),
            KEY `city_id` (`city_id`),
            KEY `postcode` (`postcode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function runAction(string $action, array $payload = []): array
    {
        $token = (string)($this->getSettings()['bearer_token'] ?? '');
        switch ($action) {
            case 'test_connection':
                if ($token === '') return ['success' => false, 'message' => 'Bearer-токен не задано'];
                try {
                    return (new Client($token))->ping()
                        ? ['success' => true,  'message' => "З'єднання з API Укрпошти — OK"]
                        : ['success' => false, 'message' => 'Порожня відповідь API'];
                } catch (\Throwable $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
        }
        return parent::runAction($action, $payload);
    }

    public function refreshCache(\DB $db): array
    {
        $token = (string)($this->getSettings()['bearer_token'] ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'Bearer-токен не задано', 'stats' => []];
        }
        $client = new Client($token);
        $stats  = ['regions' => 0, 'districts' => 0, 'cities' => 0, 'postoffices' => 0];
        try {
            $regions = $client->getRegions();
            foreach ($regions as $r) {
                $rid = (int)($r['REGION_ID'] ?? 0);
                if ($rid <= 0) continue;
                $this->upsertRegion($db, $rid, $r);
                $stats['regions']++;

                foreach ((array)$client->getDistricts($rid) as $d) {
                    $did = (int)($d['DISTRICT_ID'] ?? 0);
                    if ($did <= 0) continue;
                    $this->upsertDistrict($db, $did, $rid, $d);
                    $stats['districts']++;

                    foreach ((array)$client->getCities($did) as $c) {
                        $cid = (int)($c['CITY_ID'] ?? 0);
                        if ($cid <= 0) continue;
                        $this->upsertCity($db, $cid, $did, $rid, $c);
                        $stats['cities']++;

                        foreach ((array)$client->getPostOffices($cid) as $po) {
                            $pid = (int)($po['POSTOFFICE_ID'] ?? 0);
                            if ($pid <= 0) continue;
                            $this->upsertPostOffice($db, $pid, $cid, $po);
                            $stats['postoffices']++;
                        }
                    }
                }
            }
            return ['success' => true, 'message' => 'Кеш Укрпошти оновлено', 'stats' => $stats];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'stats' => $stats];
        }
    }

    public function searchLocations(string $type, string $query, array $context, \DB $db): array
    {
        $p     = DB_PREFIX;
        $q     = mb_strtolower(trim($query));
        $esc   = $db->escape($q);
        $lang  = (!empty($_SESSION['language']) && strpos((string)$_SESSION['language'], 'ru') === 0) ? 'ru' : 'uk';
        $col   = 'name_' . $lang;
        $limit = min(100, max(10, (int)($context['limit'] ?? 50)));
        $page  = max(1, (int)($context['page'] ?? 1));
        $offset= ($page - 1) * $limit;
        $LIM   = " LIMIT " . $offset . ", " . $limit;

        switch ($type) {
            case 'region':
                $rows = $db->query("SELECT `region_id`, `{$col}` AS `name`
                    FROM `{$p}kit_easycheckout_ukrposhta_regions`
                    WHERE LOWER(`{$col}`) LIKE '%" . $esc . "%' OR LOWER(`name_uk`) LIKE '%" . $esc . "%'
                    ORDER BY `name`" . $LIM . "")->rows;
                return array_map(static fn($r) => ['id' => (string)$r['region_id'], 'label' => $r['name']], $rows);
            case 'district':
                $rid = (int)($context['region_id'] ?? $context['parent'] ?? 0);
                if (!$rid) return [];
                $rows = $db->query("SELECT `district_id`, `{$col}` AS `name`
                    FROM `{$p}kit_easycheckout_ukrposhta_districts`
                    WHERE `region_id`=" . $rid . "
                      AND (LOWER(`{$col}`) LIKE '%" . $esc . "%' OR LOWER(`name_uk`) LIKE '%" . $esc . "%')
                    ORDER BY `name`" . $LIM . "")->rows;
                return array_map(static fn($r) => ['id' => (string)$r['district_id'], 'label' => $r['name']], $rows);
            case 'city':
                $did = (int)($context['district_id'] ?? $context['parent'] ?? 0);
                if (!$did) return [];
                $rows = $db->query("SELECT `city_id`, `{$col}` AS `name`, `type`
                    FROM `{$p}kit_easycheckout_ukrposhta_cities`
                    WHERE `district_id`=" . $did . "
                      AND (LOWER(`{$col}`) LIKE '%" . $esc . "%' OR LOWER(`name_uk`) LIKE '%" . $esc . "%')
                    ORDER BY `name`" . $LIM . "")->rows;
                return array_map(static fn($r) => ['id' => (string)$r['city_id'], 'label' => trim($r['type'] . ' ' . $r['name'])], $rows);
            case 'postoffice':
                $cid = (int)($context['city_id'] ?? $context['parent'] ?? 0);
                if (!$cid) return [];
                $rows = $db->query("SELECT `postoffice_id`, `postcode`, `{$col}` AS `name`, `address`
                    FROM `{$p}kit_easycheckout_ukrposhta_postoffices`
                    WHERE `city_id`=" . $cid . "
                      AND (LOWER(`{$col}`) LIKE '%" . $esc . "%' OR `postcode` LIKE '%" . $esc . "%')
                    ORDER BY `postcode`" . $LIM)->rows;
                return array_map(static fn($r) => [
                    'id' => (string)$r['postoffice_id'],
                    'label' => $r['postcode'] . ' — ' . $r['name'],
                    'meta' => ['address' => $r['address']],
                ], $rows);
        }
        return [];
    }

    private function upsertRegion(\DB $db, int $id, array $r): void
    {
        $p = DB_PREFIX;
        $db->query("INSERT INTO `{$p}kit_easycheckout_ukrposhta_regions`
            (`region_id`, `name_uk`, `name_ru`, `koatuu`, `data`, `updated_at`) VALUES
            (" . $id . ",
             '" . $db->escape((string)($r['REGION_UA'] ?? '')) . "',
             '" . $db->escape((string)($r['REGION_RU'] ?? '')) . "',
             '" . $db->escape((string)($r['REGION_KOATUU'] ?? '')) . "',
             '" . $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE)) . "',
             NOW())
            ON DUPLICATE KEY UPDATE
                `name_uk`=VALUES(`name_uk`), `name_ru`=VALUES(`name_ru`),
                `koatuu`=VALUES(`koatuu`), `data`=VALUES(`data`), `updated_at`=NOW()");
    }

    private function upsertDistrict(\DB $db, int $id, int $regionId, array $r): void
    {
        $p = DB_PREFIX;
        $db->query("INSERT INTO `{$p}kit_easycheckout_ukrposhta_districts`
            (`district_id`, `region_id`, `name_uk`, `name_ru`, `koatuu`, `data`, `updated_at`) VALUES
            (" . $id . ", " . $regionId . ",
             '" . $db->escape((string)($r['DISTRICT_UA'] ?? '')) . "',
             '" . $db->escape((string)($r['DISTRICT_RU'] ?? '')) . "',
             '" . $db->escape((string)($r['DISTRICT_KOATUU'] ?? '')) . "',
             '" . $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE)) . "',
             NOW())
            ON DUPLICATE KEY UPDATE
                `region_id`=VALUES(`region_id`), `name_uk`=VALUES(`name_uk`),
                `name_ru`=VALUES(`name_ru`), `koatuu`=VALUES(`koatuu`),
                `data`=VALUES(`data`), `updated_at`=NOW()");
    }

    private function upsertCity(\DB $db, int $id, int $districtId, int $regionId, array $r): void
    {
        $p = DB_PREFIX;
        $db->query("INSERT INTO `{$p}kit_easycheckout_ukrposhta_cities`
            (`city_id`, `district_id`, `region_id`, `name_uk`, `name_ru`, `type`, `koatuu`, `data`, `updated_at`) VALUES
            (" . $id . ", " . $districtId . ", " . $regionId . ",
             '" . $db->escape((string)($r['CITY_UA'] ?? '')) . "',
             '" . $db->escape((string)($r['CITY_RU'] ?? '')) . "',
             '" . $db->escape((string)($r['CITYTYPE_UA'] ?? '')) . "',
             '" . $db->escape((string)($r['CITY_KOATUU'] ?? '')) . "',
             '" . $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE)) . "',
             NOW())
            ON DUPLICATE KEY UPDATE
                `district_id`=VALUES(`district_id`), `region_id`=VALUES(`region_id`),
                `name_uk`=VALUES(`name_uk`), `name_ru`=VALUES(`name_ru`),
                `type`=VALUES(`type`), `koatuu`=VALUES(`koatuu`),
                `data`=VALUES(`data`), `updated_at`=NOW()");
    }

    private function upsertPostOffice(\DB $db, int $id, int $cityId, array $r): void
    {
        $p = DB_PREFIX;
        $lat = isset($r['LATITUDE'])  && $r['LATITUDE']  !== '' ? (float)$r['LATITUDE']  : null;
        $lng = isset($r['LONGITUDE']) && $r['LONGITUDE'] !== '' ? (float)$r['LONGITUDE'] : null;
        $db->query("INSERT INTO `{$p}kit_easycheckout_ukrposhta_postoffices`
            (`postoffice_id`, `city_id`, `postcode`, `name_uk`, `name_ru`, `address`, `lat`, `lng`, `data`, `updated_at`) VALUES
            (" . $id . ", " . $cityId . ",
             '" . $db->escape((string)($r['POSTCODE'] ?? '')) . "',
             '" . $db->escape((string)($r['POSTOFFICE_UA'] ?? '')) . "',
             '" . $db->escape((string)($r['POSTOFFICE_RU'] ?? '')) . "',
             '" . $db->escape((string)($r['ADDRESS'] ?? '')) . "',
             " . ($lat === null ? 'NULL' : (float)$lat) . ",
             " . ($lng === null ? 'NULL' : (float)$lng) . ",
             '" . $db->escape((string)json_encode($r, JSON_UNESCAPED_UNICODE)) . "',
             NOW())
            ON DUPLICATE KEY UPDATE
                `city_id`=VALUES(`city_id`), `postcode`=VALUES(`postcode`),
                `name_uk`=VALUES(`name_uk`), `name_ru`=VALUES(`name_ru`),
                `address`=VALUES(`address`), `lat`=VALUES(`lat`), `lng`=VALUES(`lng`),
                `data`=VALUES(`data`), `updated_at`=NOW()");
    }
}
