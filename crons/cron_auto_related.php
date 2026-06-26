<?php
/**
 * Auto Related Products — Cron Script
 *
 * Usage:
 *   php cron_auto_related.php [--limit=200] [--force] [--category_id=N] [--manufacturer_id=N]
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// ── CLI-only guard ────────────────────────────────────────────────────────────
// Refuse to run from a web SAPI. This script processes the entire catalogue
// queue and could be DoS'd by anyone able to reach /crons/ over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────

define('VERSION', '3.0.3.7');

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

chdir(dirname(__DIR__));

require_once __DIR__ . '/../config.php';
require_once DIR_SYSTEM . 'startup.php';

$registry = new Registry();

// DB
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

// Config
$config = new Config();
$config->load('default');
$config->load('catalog');

// Load store settings from DB
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0");
foreach ($query->rows as $row) {
    if (!$row['serialized']) {
        $config->set($row['key'], $row['value']);
    } else {
        $config->set($row['key'], json_decode($row['value'], true));
    }
}

$registry->set('config', $config);

// Cache (minimal stub)
$cache = new Cache('file');
$registry->set('cache', $cache);

// ── Parse CLI args ────────────────────────────────────────────────────────────

$opts = getopt('', ['limit:', 'force', 'category_id:', 'manufacturer_id:']);

$limit = max(1, (int)($opts['limit'] ?? 200));
$force = isset($opts['force']);

$categoryIds = array_filter(array_map('intval', explode(',', $opts['category_id'] ?? '')));
$mfIds       = array_filter(array_map('intval', explode(',', $opts['manufacturer_id'] ?? '')));

// ── Run ───────────────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
$lib = \OcKit\AutoRelated\AutoRelated::getInstance($registry);

if (!$lib->isEnabled()) {
    echo "[AutoRelated] Module is disabled. Exiting.\n";
    exit(0);
}

// Advisory lock so two overlapping cron runs don't fight over the same queue.
// Timeout 0 = fail immediately if another run holds it.
$lockName = 'ok_ar_cron_' . md5(DB_DATABASE);
$lockRow  = $db->query("SELECT GET_LOCK('" . $db->escape($lockName) . "', 0) AS got")->row;
if (empty($lockRow['got'])) {
    echo "[AutoRelated] Another run is in progress. Exiting.\n";
    exit(0);
}
register_shutdown_function(function () use ($db, $lockName) {
    $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
});

$cfg = $lib->getConfig();
$ttl = (int)($cfg['cache_ttl'] ?? 72);

$filters = [];
if ($categoryIds) { $filters['categories']    = $categoryIds; }
if ($mfIds)       { $filters['manufacturers'] = $mfIds; }

$offset    = 0;
$processed = 0;
$skipped   = 0;
$errors    = 0;

echo "[AutoRelated] Starting. limit={$limit} force=" . ($force ? 'yes' : 'no') . "\n";

$ids = $lib->getPendingIds($force, $ttl, $limit, $offset, $filters);

if (empty($ids)) {
    echo "[AutoRelated] Nothing to process.\n";
    exit(0);
}

foreach ($ids as $productId) {
    $result = $lib->generateOne((int)$productId, 'cron');

    if ($result->error !== null) {
        $errors++;
        echo "[AutoRelated] ERROR product_id={$productId}: {$result->error}\n";
    } elseif ($result->skipped) {
        $skipped++;
    } else {
        $processed++;
    }
}

echo "[AutoRelated] Done. processed={$processed} skipped={$skipped} errors={$errors}\n";
exit(0);
