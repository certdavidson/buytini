<?php
/**
 * Advanced Search Pro — Incremental index cron
 *
 * Drains the index queue (oc_asp_index_queue) into Manticore: every product
 * save / edit / delete / stock-change enqueues a row via the OCMOD hooks, and
 * this script upserts/deletes those documents so the search index stays fresh
 * without a full reindex. A full reindex is only needed when the index SCHEMA
 * changes (e.g. new morphology / new fields).
 *
 * This script intentionally drains ONLY the text-index queue. It does NOT touch
 * the embedding queue (vector/hybrid) because that calls a paid AI API — vectors
 * for new products must be generated on explicit demand, never silently by cron.
 *
 * Usage (CLI):
 *   /opt/php74/bin/php /path/to/crons/cron_advanced_search_pro.php [batch]
 *
 * Recommended schedule: every 5 minutes. Wire it via the CronManager admin job
 * ("ASP Incremental Index", type php) or a crontab minute-step-5 entry that
 * runs this file and appends stdout to
 * storage_buytini/logs/cron_advanced_search_pro.log.
 *
 * @package   OcKit\AdvancedSearchPro
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
if (!defined('VERSION')) {
    define('VERSION', '3.0.3.7');
}
require_once DIR_SYSTEM . 'startup.php';

if (!defined('STDIN')) {
    exit('CLI only' . PHP_EOL);
}

// Pin a valid timezone: some CLI php.ini carry a tz name this PHP build's tzdata
// doesn't know (e.g. 'Europe/Kyiv' on older tzdata), which makes every date()
// emit a warning. Try the modern name, fall back to the legacy alias.
if (!@date_default_timezone_set('Europe/Kyiv')) {
    date_default_timezone_set('Europe/Kiev');
}

$log = function ($msg) {
    echo '[' . date('Y-m-d H:i:s') . '] ASP cron: ' . $msg . PHP_EOL;
};

// ── Minimal bootstrap (indexing only — no session/url/language needed) ─────────

$registry = new Registry();

$config = new Config();
$registry->set('config', $config);
$config->load('default');
$config->load('catalog');
$config->set('application', 'catalog');

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? DB_PORT : 3306);
$registry->set('db', $db);

// Merge store (store_id=0) settings into config — the front controller does this
// on every request, so a CLI bootstrap must replicate it for the facade's
// getSettings() (and the status/cron-enabled guards) to resolve module_* keys.
foreach ($db->query("SELECT `key`, `value`, `serialized` FROM `" . DB_PREFIX . "setting` WHERE store_id = '0'")->rows as $s) {
    $config->set($s['key'], $s['serialized'] ? json_decode($s['value'], true) : $s['value']);
}

$registry->set('load', new Loader($registry));
$registry->set('cache', new Cache('file'));
$registry->set('log', new Log('error.log'));

// ── Guards ────────────────────────────────────────────────────────────────────

if (!$config->get('module_oc_kit_advanced_search_pro_status')) {
    $log('module disabled, skipping.');
    exit(0);
}
if (!$config->get('module_oc_kit_advanced_search_pro_cron_enabled')) {
    $log('cron disabled in settings, skipping.');
    exit(0);
}

// Single-flight: skip if another incremental run is still holding the lock.
$lockName = 'asp_cron_incremental';
$gotLock = (int)($db->query("SELECT GET_LOCK('" . $db->escape($lockName) . "', 0) AS l")->row['l'] ?? 0);
if (!$gotLock) {
    $log('another incremental run is in progress, skipping.');
    exit(0);
}

// ── Drain the index queue ─────────────────────────────────────────────────────

$batch = isset($argv[1]) ? max(1, min(5000, (int)$argv[1])) : 1000;
$started = microtime(true);

try {
    require_once DIR_APPLICATION . 'model/extension/module/oc_kit_advanced_search_pro.php';
    $model = new ModelExtensionModuleOcKitAdvancedSearchPro($registry);

    $processed = (int)$model->processQueue($batch);
    $duration = round(microtime(true) - $started, 2);

    if ($processed > 0) {
        $model->logCron('incremental', 'ok', 'Drained ' . $processed . ' queue item(s) in ' . $duration . 's');
        $log('drained ' . $processed . ' item(s) in ' . $duration . 's.');
    } else {
        $log('queue empty, nothing to do (' . $duration . 's).');
    }
} catch (\Throwable $e) {
    if (isset($model) && is_object($model)) {
        $model->logCron('incremental', 'error', $e->getMessage());
    }
    $log('ERROR: ' . $e->getMessage());
    $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
    exit(1);
}

$db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
exit(0);
