<?php
/**
 * Multicurrency Products — Cron Script
 *
 * Recomputes all materialized prices (products, specials, discounts) at the
 * current oc_currency rate. Run after currency rates are updated.
 *
 * Crontab (hourly):
 *   15 * * * * php /var/www/www-root/data/www/buytini.com/crons/cron_multicurrency.php >> /var/log/cron_multicurrency.log 2>&1
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . '../startup.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

$registry = new Registry();

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT ?? 3306);
$registry->set('db', $db);

$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

$q = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = 0");
foreach ($q->rows as $row) {
    $config->set($row['key'], $row['serialized'] ? json_decode($row['value'], true) : $row['value']);
}

// Currency service (convert() does not need language)
require_once DIR_SYSTEM . 'library/cart/currency.php';
$registry->set('currency', new \Cart\Currency($registry));

// ── Check module enabled ────────────────────────────────────────────────────────

if (!$config->get('module_oc_kit_multicurrency_status')) {
    echo date('[Y-m-d H:i:s]') . " Multicurrency: module disabled — skip.\n";
    exit(0);
}

// ── Recompute ─────────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/multicurrency/Multicurrency.php';
$lib = new \OcKit\Multicurrency\Multicurrency($registry);

$count = $lib->compiler()->recompute();

// Flush product cache so the catalog picks up new prices
(new Cache('file'))->delete('product');

echo date('[Y-m-d H:i:s]') . " Multicurrency: recomputed {$count} position(s).\n";
exit(0);
