<?php
/**
 * Stock Notify — Cron Script
 *
 * Перевіряє заявки зі статусом "pending", і якщо товар повернувся в наявність —
 * відправляє email + SMS покупцю та змінює статус на "sent".
 *
 * Crontab (кожні 30 хвилин):
 *   30 * * * * php /var/www/www-root/data/www/buytini.com/crons/cron_stock_notify.php >> /var/log/cron_stock_notify.log 2>&1
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . '../startup.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

$registry = new Registry();

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT ?? 3306);
$registry->set('db', $db);

$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

// Load store settings
$q = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = 0");
foreach ($q->rows as $row) {
    $config->set($row['key'], $row['serialized'] ? json_decode($row['value'], true) : $row['value']);
}

$cache = new Cache('file');
$registry->set('cache', $cache);

$registry->set('event', new Event($registry));
$registry->set('request', new Request());

// ── Check module enabled ───────────────────────────────────────────────────────

$cfg = 'oc_kit_stock_notify_';

if (!$config->get($cfg . 'status')) {
    echo date('[Y-m-d H:i:s]') . " Stock Notify: module disabled — skip.\n";
    exit(0);
}

$emailEnabled = (bool)$config->get($cfg . 'email_enabled');
$smsEnabled   = (bool)$config->get($cfg . 'sms_enabled');
$useShortLink = (bool)$config->get($cfg . 'short_link');
$smsToken     = (string)$config->get($cfg . 'sms_token');
$smsSender    = (string)$config->get($cfg . 'sms_sender');
$emailSubject = (string)$config->get($cfg . 'email_subject');
$emailBody    = (string)$config->get($cfg . 'email_body');
$smsText      = (string)$config->get($cfg . 'sms_text');

if (!$emailEnabled && !$smsEnabled) {
    echo date('[Y-m-d H:i:s]') . " Stock Notify: email and SMS both disabled — skip.\n";
    exit(0);
}

// ── Load library ───────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/stock_notify/Notify.php';
$notify = new \OcKit\StockNotify\Notify($registry);
$repo   = $notify->repo();

// ── Process ────────────────────────────────────────────────────────────────────

$requests = $repo->getPendingInStock();

if (!$requests) {
    echo date('[Y-m-d H:i:s]') . " Stock Notify: no pending requests with stock — nothing to do.\n";
    exit(0);
}

echo date('[Y-m-d H:i:s]') . " Stock Notify: processing " . count($requests) . " request(s)...\n";

$sent   = 0;
$failed = 0;

foreach ($requests as $req) {
    $id         = (int)$req['request_id'];
    $productUrl = HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $req['product_id'];

    $tokens = [
        'name'         => $req['name'],
        'product_name' => $req['product_name'] ?? ('Product #' . $req['product_id']),
        'product_url'  => $productUrl,
    ];

    $okEmail = false;
    $okSms   = false;

    if ($emailEnabled && !empty($req['email'])) {
        $okEmail = $notify->mailer()->send($req['email'], $emailSubject, $emailBody, $tokens);
        echo date('[Y-m-d H:i:s]') . "   Email → {$req['email']}: " . ($okEmail ? 'OK' : 'FAIL') . "\n";
    }

    if ($smsEnabled && !empty($req['phone'])) {
        $okSms = $notify->smsSender()->send($req['phone'], $smsText, $tokens, $smsToken, $smsSender, $useShortLink);
        echo date('[Y-m-d H:i:s]') . "   SMS   → {$req['phone']}: " . ($okSms ? 'OK' : 'FAIL') . "\n";
    }

    if ($okEmail || $okSms) {
        $repo->markSent($id, $okEmail, $okSms);
        $sent++;
    } else {
        $repo->updateStatus($id, 'failed');
        $failed++;
    }
}

echo date('[Y-m-d H:i:s]') . " Stock Notify: done. Sent: $sent, Failed: $failed.\n";
