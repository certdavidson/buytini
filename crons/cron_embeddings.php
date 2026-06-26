<?php
/**
 * Advanced Search Pro — AI embedding cron (vector/hybrid)
 *
 * Generates vector embeddings for products that don't have one for the current
 * model, then drains the embedding queue into the Manticore KNN index. New /
 * edited products only get a TEXT index entry from the incremental cron; this
 * script is what gives them a VECTOR so semantic (hybrid) search sees them.
 *
 * It calls a PAID AI API, so unlike the incremental cron it runs on a slow
 * cadence (recommended: nightly, off-peak) and is gated on vector_enabled.
 * Spend is bounded by ai_budget_daily_limit (tokens) and ai_budget_monthly ($).
 *
 * Usage (CLI):
 *   /usr/bin/php8.3 /path/to/crons/cron_embeddings.php [batch]
 *
 * @package   OcKit\AdvancedSearchPro
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

// Some stores' config.php derive HTTP_SERVER from $_SERVER['HTTP_HOST']; in CLI
// it is absent. Pin a value so config.php doesn't warn — the cron reads the
// store_id=0 settings regardless of host.
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once '../config.php';
if (!defined('VERSION')) {
    define('VERSION', '3.0.3.7');
}
require_once DIR_SYSTEM . 'startup.php';

if (!defined('STDIN')) {
    exit('CLI only' . PHP_EOL);
}

if (!@date_default_timezone_set('Europe/Kyiv')) {
    date_default_timezone_set('Europe/Kiev');
}

$log = function ($msg) {
    echo '[' . date('Y-m-d H:i:s') . '] ASP embed cron: ' . $msg . PHP_EOL;
};

// ── Minimal bootstrap ──────────────────────────────────────────────────────────

$registry = new Registry();

$config = new Config();
$registry->set('config', $config);
$config->load('default');
$config->load('catalog');
$config->set('application', 'catalog');

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? DB_PORT : 3306);
$registry->set('db', $db);

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
if (!$config->get('module_oc_kit_advanced_search_pro_vector_enabled')) {
    $log('vector disabled, nothing to embed.');
    exit(0);
}

// Single-flight: a long embed run must never overlap itself.
$lockName = 'asp_cron_embedding';
$gotLock = (int)($db->query("SELECT GET_LOCK('" . $db->escape($lockName) . "', 0) AS l")->row['l'] ?? 0);
if (!$gotLock) {
    $log('another embed run is in progress, skipping.');
    exit(0);
}

// ── Queue missing products, then drain the embedding queue ─────────────────────

$batch = isset($argv[1]) ? max(1, min(1000, (int)$argv[1])) : 100;
$started = microtime(true);

$pending = function () use ($db) {
    return (int)($db->query("SELECT COUNT(*) AS c FROM `" . DB_PREFIX . "asp_embedding_queue` WHERE status = 'pending'")->row['c'] ?? 0);
};

try {
    require_once DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php';
    $asp = new \OcKit\AdvancedSearchPro\AdvancedSearchPro($registry);

    require_once DIR_APPLICATION . 'model/extension/module/oc_kit_advanced_search_pro.php';
    $model = new ModelExtensionModuleOcKitAdvancedSearchPro($registry);

    $queued = (int)$asp->queueMissingProductsForEmbedding();

    $total = 0;
    while (true) {
        $n = (int)$asp->processEmbeddingQueue($batch);
        $total += $n;
        // n === 0 means the queue is drained, or every remaining item failed
        // (budget exhausted / API error) — either way there is nothing more to do.
        if ($n === 0) {
            break;
        }
    }

    $duration = round(microtime(true) - $started, 2);
    $left = $pending();
    $msg = 'Embedded ' . $total . ' (queued missing: ' . $queued . ', pending left: ' . $left . ') in ' . $duration . 's';
    $model->logCron('embedding', 'ok', $msg);
    $log($msg);
} catch (\Throwable $e) {
    if (isset($model) && is_object($model)) {
        $model->logCron('embedding', 'error', $e->getMessage());
    }
    $log('ERROR: ' . $e->getMessage());
    $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
    exit(1);
}

$db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
exit(0);
