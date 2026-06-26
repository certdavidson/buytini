<?php
/**
 * Sitemap Generator — Cron Script
 *
 * Usage (CLI):
 *   php /var/www/www-root/data/www/buytini.com/crons/cron_sitemap_generator.php
 *
 * Generate a specific map only:
 *   php crons/cron_sitemap_generator.php --map_id=3
 *
 * Dry-run (count URLs without writing files):
 *   php crons/cron_sitemap_generator.php --dry-run
 *
 * Recommended crontab (daily at 3:00 AM):
 *   0 3 * * * php /var/www/www-root/data/www/buytini.com/crons/cron_sitemap_generator.php >> /var/www/www-root/data/www/storage_buytini/logs/cron_sitemap_generator.log 2>&1
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Block web access — this script must only run from CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

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

$langCode = $config->get('config_language') ?: 'uk-ua';
$language = new Language($langCode);
$language->load($langCode);
$registry->set('language', $language);

$document = new Document();
$registry->set('document', $document);

$url = new Url($config->get('config_url'), $config->get('config_url'));
$registry->set('url', $url);

$config->set('application', 'catalog');

// ── Check module status ───────────────────────────────────────────────────────

if (!$config->get('module_oc_kit_sitemap_generator_status')) {
    echo '[' . date('Y-m-d H:i:s') . '] Sitemap Generator: module disabled, skipping.' . PHP_EOL;
    exit(0);
}

if (!$config->get('module_oc_kit_sitemap_generator_auto_generation')) {
    echo '[' . date('Y-m-d H:i:s') . '] Sitemap Generator: auto-generation disabled, skipping.' . PHP_EOL;
    exit(0);
}

// ── Parse CLI arguments ───────────────────────────────────────────────────────

$mapId  = null;
$dryRun = false;

foreach ($argv as $arg) {
    if (preg_match('/^--map_id=(\d+)$/', $arg, $m)) {
        $mapId = (int)$m[1];
    }
    if ($arg === '--dry-run') {
        $dryRun = true;
    }
}

// ── Run generator ─────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';

use OcKit\SitemapGenerator\SitemapGenerator;

$sg      = new SitemapGenerator($registry);
$started = microtime(true);

echo '[' . date('Y-m-d H:i:s') . '] Sitemap Generator cron started'
    . ($mapId  ? ' (map_id=' . $mapId . ')' : '')
    . ($dryRun ? ' [dry-run]'               : '')
    . '.' . PHP_EOL;

try {
    $result  = $sg->generate($mapId, $dryRun, 'cron');
    $elapsed = round(microtime(true) - $started, 2);

    echo '[' . date('Y-m-d H:i:s') . '] Done in ' . $elapsed . 's'
        . ' — URLs: ' . ($result['urls_total'] ?? 0)
        . ', files: ' . ($result['files_total'] ?? 0)
        . PHP_EOL;

    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $err) {
            echo '[' . date('Y-m-d H:i:s') . '] WARN: ' . $err . PHP_EOL;
        }
    }

} catch (\Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
