<?php
/**
 * Review Request — Cron Script
 *
 * Usage:
 *   php /path/to/crons/cron_review_request.php
 *
 * Or via HTTP (with cron_token):
 *   curl -s "https://buytini.com/index.php?route=extension/module/review_request/cron&cron_token=YOUR_TOKEN"
 *
 * Recommended crontab (every 15 minutes):
 *   * /15 * * * * php /var/www/www-root/data/www/buytini.com/crons/cron_review_request.php >> /var/www/www-root/data/www/storage_buytini/logs/cron_review_request.log 2>&1
 *
 * @package   OcKit\ReviewRequest
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . '../startup.php';

// ── Registry bootstrap ────────────────────────────────────────────────────────

$registry = new Registry();

$loader = new Loader($registry);
$registry->set('load', $loader);

$config = new Config();
$registry->set('config', $config);

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

$event = new Event($registry);
$registry->set('event', $event);

$request = new Request();
$registry->set('request', $request);

$response = new Response();
$registry->set('response', $response);

$cache = new Cache('file');
$registry->set('cache', $cache);

$session = new Session($config->get('session_engine'), $registry);
$registry->set('session', $session);

// Language is taken from the store configuration
$langCode = $config->get('config_language') ?: 'uk-ua';
$language = new Language($langCode);
$language->load($langCode);
$registry->set('language', $language);

$document = new Document();
$registry->set('document', $document);

$url = new Url($config->get('config_url'), $config->get('config_url'));
$registry->set('url', $url);

$config->set('application', 'catalog');

// ── Module status check ───────────────────────────────────────────────────────

if (!$config->get('module_oc_kit_review_request_status')) {
    echo '[' . date('Y-m-d H:i:s') . '] Review Request: module disabled, skipping.' . PHP_EOL;
    exit(0);
}

// ── Process queue ─────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/review_request/ReviewRequest.php';

use OcKit\ReviewRequest\ReviewRequest;

$rr = new ReviewRequest($registry);

$started = microtime(true);
echo '[' . date('Y-m-d H:i:s') . '] Review Request cron started.' . PHP_EOL;

try {
    $stats = $rr->processBatch(50);
    $elapsed = round(microtime(true) - $started, 2);

    echo '[' . date('Y-m-d H:i:s') . '] Done in ' . $elapsed . 's — '
        . 'sent: ' . $stats['sent'] . ', '
        . 'failed: ' . $stats['failed'] . ', '
        . 'skipped: ' . $stats['skipped'] . PHP_EOL;

} catch (\Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
