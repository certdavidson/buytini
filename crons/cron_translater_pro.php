<?php
/**
 * Translater Pro — Cron Script
 *
 * Без параметрів — читає налаштування з БД (вкладка "Налаштування > Крон").
 * З параметрами — можна перевизначити будь-який параметр для конкретного крону.
 *
 * Параметри CLI:
 *   --source=en-gb          Мова оригіналу
 *   --target=uk-ua          Цільова мова (одна)
 *   --types=product,category  Типи через кому (product|category|manufacturer|article|blog_category)
 *   --batch=20              Записів за запуск на тип
 *
 * Приклади crontab:
 *   # Зі стандартних налаштувань адмін-панелі:
 *   0 2 * * * php /var/www/www-root/data/www/buytini.com/crons/cron_translater_pro.php
 *
 *   # З явними параметрами (по одному на мовну пару):
 *   0 2 * * * php /var/www/www-root/data/www/buytini.com/crons/cron_translater_pro.php --source=en-gb --target=uk-ua --types=product,category --batch=30
 *   0 3 * * * php /var/www/www-root/data/www/buytini.com/crons/cron_translater_pro.php --source=en-gb --target=ru-ru --types=product,category --batch=30
 *
 * @package   OcKit\TranslaterPro
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . '../startup.php';

// ── Ініціалізація реєстру ─────────────────────────────────────────────────────

$registry = new Registry();

// DB
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

// Config
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

// ── CLI-параметри (перевизначають налаштування з БД) ─────────────────────────

$cliOpts = getopt('', ['source:', 'target:', 'types:', 'batch:']);

// ── Перевірка увімкненості ────────────────────────────────────────────────────

$prefix = 'module_oc_kit_translater_pro_';

if (!$config->get($prefix . 'status')) {
    echo date('[Y-m-d H:i:s]') . " Translater Pro: module disabled — skip.\n";
    exit(0);
}

// Якщо є CLI-параметри — крон_auto не перевіряємо (запуск явний)
$hasCliParams = isset($cliOpts['source']) || isset($cliOpts['target']);

if (!$hasCliParams && !$config->get($prefix . 'cron_auto')) {
    echo date('[Y-m-d H:i:s]') . " Translater Pro: cron_auto disabled — skip.\n";
    exit(0);
}

// ── Параметри (CLI > налаштування БД) ────────────────────────────────────────

$sourceLang = isset($cliOpts['source'])
    ? trim((string)$cliOpts['source'])
    : (string)$config->get($prefix . 'cron_source_lang');

// --target задає ОДНУ цільову мову; без нього беремо масив з налаштувань
if (isset($cliOpts['target'])) {
    $targetLangs = [trim((string)$cliOpts['target'])];
} else {
    $targetLangs = (array)($config->get($prefix . 'cron_target_langs') ?: []);
}

if (isset($cliOpts['types'])) {
    $types = array_filter(array_map('trim', explode(',', (string)$cliOpts['types'])));
} else {
    $types = (array)($config->get($prefix . 'cron_types') ?: []);
}

$batchSize = isset($cliOpts['batch'])
    ? max(1, (int)$cliOpts['batch'])
    : max(1, (int)($config->get($prefix . 'cron_batch') ?: 20));

if (!$sourceLang || empty($targetLangs) || empty($types)) {
    echo date('[Y-m-d H:i:s]') . " Translater Pro: cron config incomplete — skip.\n";
    exit(0);
}

// ── Запуск перекладу ──────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/translater_pro/TranslaterPro.php';

$lib = new \OcKit\TranslaterPro\TranslaterPro($registry);

$totalDone   = 0;
$totalFailed = 0;

foreach ($targetLangs as $targetLang) {
    if ($targetLang === $sourceLang) continue;

    foreach ($types as $type) {
        echo date('[Y-m-d H:i:s]') . " Translating {$type}: {$sourceLang} → {$targetLang} (batch={$batchSize})…\n";

        try {
            $result       = $lib->translateBatch($type, $sourceLang, $targetLang, $batchSize);
            $totalDone   += $result['done'];
            $totalFailed += $result['failed'];

            echo date('[Y-m-d H:i:s]') . "   done={$result['done']} failed={$result['failed']}\n";
        } catch (\Throwable $e) {
            $totalFailed++;
            echo date('[Y-m-d H:i:s]') . "   ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo date('[Y-m-d H:i:s]') . " Translater Pro cron complete. Total done={$totalDone} failed={$totalFailed}\n";
exit(0);
