<?php
/**
 * Cron Manager — Bootstrap Script
 *
 * Запускається системним crontab кожну хвилину.
 * Перевіряє всі активні задачі та виконує ті, час яких настав.
 *
 * Додати в crontab:
 *   * * * * * php /var/www/www-root/data/www/buytini.com/crons/cron_manager.php
 *
 * @package   OcKit\CronManager
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . 'startup.php';

// PHP 7.4 ships tzdata that may lack 'Europe/Kyiv' (added upstream in 2022).
// Try the configured zone, then its legacy alias, then UTC.
foreach (['Europe/Kyiv', 'Europe/Kiev', 'UTC'] as $tz) {
    if (@date_default_timezone_set($tz)) break;
}

// ── Bootstrap registry ────────────────────────────────────────────────────────

$registry = new Registry();

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

// Load store settings
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0");
foreach ($query->rows as $result) {
    if ($result['serialized']) {
        $config->set($result['key'], json_decode($result['value'], true));
    } else {
        $config->set($result['key'], $result['value']);
    }
}

// ── Run due jobs ──────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/cron_manager/CronManager.php';

$manager = new \OcKit\CronManager\CronManager($registry);
$manager->install(); // ensures tables exist (idempotent)
$manager->runDueJobs();

exit(0);
