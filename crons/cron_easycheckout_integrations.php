<?php
/**
 * EasyCheckout — integrations cache refresh cron
 *
 * Обходить enabled-інтеграції категорії shipping і рефрешить локальний кеш,
 * якщо з останнього оновлення минуло більше ніж cache_ttl_hours.
 *
 * Приклад: 0 3 * * * php /var/www/www-root/data/www/buytini.com/crons/cron_easycheckout_integrations.php > /dev/null 2>&1
 *
 * © 2026 oc-kit.com | https://oc-kit.com
 */

chdir(__DIR__ . '/..');
require_once 'config.php';
if (!defined('VERSION')) define('VERSION', '3.0.3.7');
if (!defined('STDIN')) exit('CLI only');

require_once DIR_SYSTEM . 'startup.php';

$registry = new \Registry();
$registry->set('config', new \Config());
$config = $registry->get('config');
$config->load('default');
$config->load('catalog');

$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

require_once DIR_SYSTEM . 'library/ockit/easycheckout/EasyCheckout.php';

$ec  = new \OcKit\EasyCheckout\EasyCheckout($registry);
$ec->setStore(0);
$reg = new \OcKit\EasyCheckout\Libs\IntegrationsRegistry($ec->getConfigStore());

$now    = time();
$report = [];

foreach ($reg->all() as $integration) {
    $code = $integration->getCode();
    if (!$integration->isEnabled())            { $report[$code] = 'skipped (disabled)';   continue; }
    if ($integration->getCategory() !== 'shipping') { $report[$code] = 'skipped (not shipping)'; continue; }

    $settings = $integration->getSettings();
    $ttlHours = max(1, (int)($settings['cache_ttl_hours'] ?? 24));

    $stamp = (int)$ec->getConfigStore()->get('integration.' . $code, 'last_refresh_ts', 0);
    if ($stamp > 0 && ($now - $stamp) < $ttlHours * 3600) {
        $report[$code] = 'fresh (' . round(($now - $stamp) / 3600, 1) . 'h ago, ttl=' . $ttlHours . 'h)';
        continue;
    }

    $integration->installSchema($db);
    $result = $integration->refreshCache($db);
    $ec->getConfigStore()->set('integration.' . $code, 'last_refresh_ts', $now);
    $ec->getConfigStore()->set('integration.' . $code, 'last_refresh_result',
        json_encode($result, JSON_UNESCAPED_UNICODE));

    $report[$code] = ($result['success'] ? 'OK ' : 'FAIL ') . ($result['message'] ?? '');
}

echo '[' . date('Y-m-d H:i:s') . '] EasyCheckout integrations cron:' . PHP_EOL;
foreach ($report as $code => $line) echo '  ' . $code . ' — ' . $line . PHP_EOL;
