<?php
/**
 * EasyCheckout — smoke-test крон для інтеграцій НП/Укрпошти.
 *
 * Запуск:
 *   php crons/cron_easycheckout_smoke.php nova_poshta=YOUR_NP_KEY
 *   php crons/cron_easycheckout_smoke.php ukrposhta=YOUR_BEARER
 *   php crons/cron_easycheckout_smoke.php nova_poshta=KEY ukrposhta=TOKEN
 *
 * Виконує: ping → 1 сторінку cities → 1 сторінку warehouses (для НП) /
 * regions → 1 districts → 1 cities → 1 postoffices (для Укрпошти),
 * без запису в БД. Друкує лише розмір вибірки + перші 2 елементи на діагностику.
 *
 * © 2026 oc-kit.com | https://oc-kit.com
 */

if (php_sapi_name() !== 'cli') exit('CLI only');

chdir(__DIR__ . '/..');
require_once 'config.php';
if (!defined('VERSION')) define('VERSION', '3.0.3.7');

$keys = [];
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '=') === false) continue;
    [$k, $v] = explode('=', $arg, 2);
    $keys[trim($k)] = trim($v);
}
if (!$keys) { echo "Usage: php cron_easycheckout_smoke.php nova_poshta=KEY ukrposhta=TOKEN\n"; exit(1); }

$base = DIR_SYSTEM . 'library/ockit/easycheckout/integrations';
require_once $base . '/IntegrationInterface.php';
require_once $base . '/AbstractIntegration.php';

if (isset($keys['nova_poshta'])) {
    require_once $base . '/nova_poshta/Client.php';
    $c = new \OcKit\EasyCheckout\Integrations\NovaPoshta\Client($keys['nova_poshta']);
    echo "[NP] ping... "; flush();
    try {
        $ok = $c->ping();
        echo $ok ? "OK\n" : "FAIL (empty)\n";
    } catch (\Throwable $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

    echo "[NP] getCities(page=1, limit=10)... "; flush();
    try {
        $rows = $c->getCities(1, 10);
        echo count($rows) . " rows\n";
        for ($i = 0; $i < min(2, count($rows)); $i++) {
            $r = $rows[$i];
            echo "  - {$r['Description']} / Ref={$r['Ref']} / Area={$r['AreaDescription']}\n";
        }
    } catch (\Throwable $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

    echo "[NP] getWarehouses('', page=1, limit=10)... "; flush();
    try {
        $rows = $c->getWarehouses('', 1, 10);
        echo count($rows) . " rows\n";
        for ($i = 0; $i < min(2, count($rows)); $i++) {
            $r = $rows[$i];
            echo "  - #{$r['Number']} / {$r['Description']} / Type='{$r['CategoryOfWarehouse']}'\n";
        }
    } catch (\Throwable $e) { echo "ERROR: " . $e->getMessage() . "\n"; }
}

if (isset($keys['ukrposhta'])) {
    require_once $base . '/ukrposhta/Client.php';
    $c = new \OcKit\EasyCheckout\Integrations\Ukrposhta\Client($keys['ukrposhta']);
    echo "\n[UP] getRegions()... "; flush();
    try {
        $rows = $c->getRegions();
        echo count($rows) . " rows\n";
        for ($i = 0; $i < min(2, count($rows)); $i++) {
            $r = $rows[$i];
            echo "  - keys: " . implode(',', array_keys($r)) . "\n";
            echo "    " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
        }
        $rid = (int)($rows[0]['REGION_ID'] ?? 0);
        if ($rid) {
            echo "[UP] getDistricts({$rid})... "; flush();
            $d = $c->getDistricts($rid);
            echo count($d) . " rows\n";
            $did = (int)($d[0]['DISTRICT_ID'] ?? 0);
            if ($did) {
                echo "[UP] getCities({$did})... "; flush();
                $cs = $c->getCities($did);
                echo count($cs) . " rows\n";
                $cid = (int)($cs[0]['CITY_ID'] ?? 0);
                if ($cid) {
                    echo "[UP] getPostOffices({$cid})... "; flush();
                    $po = $c->getPostOffices($cid);
                    echo count($po) . " rows\n";
                }
            }
        }
    } catch (\Throwable $e) { echo "ERROR: " . $e->getMessage() . "\n"; }
}

echo "\nDone.\n";
