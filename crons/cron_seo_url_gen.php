<?php
/**
 * SEO URL Generator — Cron Script
 *
 * Generates SEO URLs and meta data for all enabled content types.
 * Run via crontab: 0 3 * * * php /path/to/crons/cron_seo_url_gen.php
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// ── Bootstrap OpenCart ────────────────────────────────────────────────────────

$root = dirname(__DIR__) . '/';

define('APPLICATION', 'Catalog');
define('VERSION', '3.0.3.7');
define('DIR_APPLICATION', $root . 'catalog/');
define('DIR_SYSTEM',      $root . 'system/');
define('DIR_IMAGE',       $root . 'image/');
define('DIR_STORAGE',     realpath($root . '../storage_buytini') . '/');
define('DIR_LANGUAGE',    DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE',    DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG',      DIR_SYSTEM . 'config/');
define('DIR_CACHE',       DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD',    DIR_STORAGE . 'download/');
define('DIR_LOGS',        DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION',     DIR_STORAGE . 'session/');
define('DIR_UPLOAD',      DIR_STORAGE . 'upload/');

require_once DIR_SYSTEM . 'startup.php';

start('catalog');

// ── Config prefix ─────────────────────────────────────────────────────────────

$configPrefix = 'module_oc_kit_seo_url_gen_';
$status       = $registry->get('config')->get($configPrefix . 'status');

if (!$status) {
    echo "[SEO URL Gen] Module is disabled. Exiting.\n";
    exit(0);
}

// ── Parse CLI arguments ───────────────────────────────────────────────────────
// Supported: --types=product,category  --fields=seo_url,meta_title  --batch=100

$cliTypes  = null; // null = use config defaults
$cliFields = null;
$cliBatch  = null;

foreach ($argv as $arg) {
    if (preg_match('/^--types=(.+)$/', $arg, $m))  { $cliTypes  = array_filter(explode(',', $m[1])); }
    if (preg_match('/^--fields=(.+)$/', $arg, $m)) { $cliFields = array_filter(explode(',', $m[1])); }
    if (preg_match('/^--batch=(\d+)$/', $arg, $m)) { $cliBatch  = (int)$m[1]; }
}

// ── Load library ──────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/seo_url_gen/SeoUrlGen.php';
$lib = new \OcKit\SeoUrlGen\SeoUrlGen($registry);
$lib->install(); // ensure table exists

// ── Read settings ─────────────────────────────────────────────────────────────

$config    = $registry->get('config');
$allTypes  = array_keys($lib->getTypes());
$allFields = ['seo_url', 'meta_title', 'meta_description', 'meta_keyword', 'meta_h1'];

// Determine active types: CLI arg → cron_type_{key} config → all types
if ($cliTypes !== null) {
    $activeTypes = array_values(array_intersect($cliTypes, $allTypes));
} else {
    $activeTypes = [];
    foreach ($allTypes as $typeKey) {
        $val = $config->get($configPrefix . 'cron_type_' . $typeKey);
        if ($val === null || (int)$val === 1) {
            $activeTypes[] = $typeKey;
        }
    }
}

// Determine active fields: CLI arg → cron_field_{key} config → all fields
if ($cliFields !== null) {
    $activeFields = array_values(array_intersect($cliFields, $allFields));
} else {
    $activeFields = [];
    foreach ($allFields as $fkey) {
        $val = $config->get($configPrefix . 'cron_field_' . $fkey);
        if ($val === null || (int)$val === 1) {
            $activeFields[] = $fkey;
        }
    }
    if (empty($activeFields)) {
        $activeFields = $allFields;
    }
}

$overwrite = (bool)(int)$config->get($configPrefix . 'overwrite');
$batchSize = $cliBatch ?? (int)($config->get($configPrefix . 'cron_batch') ?: $config->get($configPrefix . 'batch_size') ?: 100);
$masksAll  = $config->get($configPrefix . 'masks') ?: [];

// ── Languages ────────────────────────────────────────────────────────────────

$db        = $registry->get('db');
$languages = $db->query("SELECT language_id, code FROM `" . DB_PREFIX . "language` WHERE status = '1'")->rows;
$langIds   = array_map(fn($l) => (int)$l['language_id'], $languages);

// ── Run generation ────────────────────────────────────────────────────────────

$totalGenerated = 0;
$totalSkipped   = 0;
$totalErrors    = 0;

foreach ($activeTypes as $type) {
    $total  = $lib->getTotal($type);
    $offset = 0;
    echo "[SEO URL Gen] Processing type={$type}, total={$total}\n";

    // Build per-language masks for this type
    $typeMasks = [];
    foreach ($langIds as $lid) {
        $typeMasks[$lid] = $masksAll[$type][$lid] ?? [];
    }

    while ($offset < $total) {
        $result = $lib->generateBatch(
            $type, $offset, $batchSize,
            $langIds, $typeMasks,
            $overwrite, $activeFields
        );

        $totalGenerated += $result['generated'];
        $totalSkipped   += $result['skipped'];
        $totalErrors    += $result['errors'];
        $offset          = $result['next_offset'];

        if ($result['done']) break;
    }
}

echo "[SEO URL Gen] Done. Generated={$totalGenerated}, Skipped={$totalSkipped}, Errors={$totalErrors}\n";
