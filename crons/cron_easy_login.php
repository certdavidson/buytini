<?php
/**
 * Easy Login — log retention + OTP cleanup cron.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 *
 * Run daily:  /usr/bin/php <store-root>/crons/cron_easy_login.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$root = __DIR__ . '/..';
require_once $root . '/config.php';
require_once DIR_SYSTEM . 'startup.php';

ob_start();
// Derive a sensible HTTP_HOST from the project config.php so this script
// works on any deployment without hand-editing. HTTP_CATALOG/HTTP_SERVER are
// defined in config.php; fall back to localhost only as last resort.
$cronHost = 'localhost';
if (defined('HTTP_CATALOG')) {
    $cronHost = parse_url(HTTP_CATALOG, PHP_URL_HOST) ?: $cronHost;
} elseif (defined('HTTP_SERVER')) {
    $cronHost = parse_url(HTTP_SERVER, PHP_URL_HOST) ?: $cronHost;
}
$_SERVER['HTTP_HOST']      = $cronHost;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['SERVER_PROTOCOL']= 'HTTP/1.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';

$registry = new Registry();

$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

require_once DIR_SYSTEM . 'library/ockit/easy_login/EasyLogin.php';

$lib       = new \OcKit\EasyLogin\EasyLogin($registry);
$retention = (int)($config->get('module_oc_kit_easy_login_log_retention_days') ?: 90);

$logsDeleted = $lib->getLogger()->clearOld($retention);
$otpsDeleted = $lib->getOtp()->deleteExpired();

echo "[" . date('Y-m-d H:i:s') . "] Easy Login cleanup — logs deleted: {$logsDeleted}, OTPs deleted: {$otpsDeleted}\n";
ob_end_flush();
