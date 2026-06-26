<?php
/**
 * EasyCheckout — abandoned checkout reminder cron
 *
 * Розсилає клієнтам email з recovery-посиланням якщо вони почали checkout
 * але не завершили протягом N хвилин (config: reminder_delay_minutes).
 *
 * Запускати з cron (приклад): кожні 15 хв — *‍/15 * * * * php /path/to/crons/cron_easycheckout_reminder.php
 *
 * © 2026 oc-kit.com | https://oc-kit.com
 */

// Bootstrap OC — мінімальний (без framework.php, лише config + DB + Mail)
chdir(__DIR__ . '/..');
require_once 'config.php';
if (!defined('VERSION')) define('VERSION', '3.0.3.7');

if (!defined('STDIN')) exit('CLI only');

require_once DIR_SYSTEM . 'startup.php';

$registry = new \Registry();
$registry->set('config', new \Config());
$config = $registry->get('config');
// `default` group зберігає опції модуля у oc_setting (через addEvent у admin)
$config->load('default');
$config->load('catalog');

$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

// Heartbeat: фіксуємо запуск крона навіть коли він "Disabled" — admin побачить
// що cron-job налаштований і працює, просто ремайндери вимкнені.
$db->query("DELETE FROM `" . DB_PREFIX . "setting`
    WHERE `store_id`=0 AND `code`='module_oc_kit_easycheckout'
      AND `key`='module_oc_kit_easycheckout_cron_last_run'");
$db->query("INSERT INTO `" . DB_PREFIX . "setting`
    SET `store_id`=0, `code`='module_oc_kit_easycheckout',
        `key`='module_oc_kit_easycheckout_cron_last_run',
        `value`='" . $db->escape(date('Y-m-d H:i:s')) . "', `serialized`=0");

$enabled = (int)$config->get('module_oc_kit_easycheckout_reminder_enabled');
if (!$enabled) {
    fwrite(STDOUT, "[okec-reminder] Disabled in config — exit\n");
    exit(0);
}

// Multi-cadence: список затримок у хвилинах, через кому. Перша = 1-й reminder, друга = 2-й, etc.
// Backwards-compat: якщо поле порожнє, fallback на старий single-delay setting.
$delaysRaw  = (string)$config->get('module_oc_kit_easycheckout_reminder_delays');
$delaysList = [];
if ($delaysRaw !== '') {
    foreach (preg_split('~[,\s]+~', $delaysRaw) as $d) {
        $d = (int)$d;
        if ($d >= 5) $delaysList[] = $d;
    }
}
if (!$delaysList) {
    $delaysList = [max(5, (int)($config->get('module_oc_kit_easycheckout_reminder_delay_minutes') ?: 60))];
}
$maxStage = count($delaysList);
$batch    = max(1, (int)($config->get('module_oc_kit_easycheckout_reminder_batch') ?: 50));

// Кандидати: ще не recovered, ще не вичерпали всі stages, мають email і токен.
// Філтруємо по часу для кожного rec.: NOW() - last_milestone >= delays[reminder_count]
$rows = $db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
    WHERE `recovered_order_id` IS NULL
      AND `email`              <> ''
      AND `recovery_token`     <> ''
      AND `reminder_count`     < " . (int)$maxStage . "
    ORDER BY `date_modified` ASC
    LIMIT " . (int)$batch)->rows;

// Локальний фільтр: чи готовий рядок до наступного reminder?
$nowTs = time();
$rows  = array_values(array_filter($rows, static function ($r) use ($delaysList, $nowTs) {
    $stage    = (int)$r['reminder_count'];
    $delayMin = (int)($delaysList[$stage] ?? 0);
    if ($delayMin <= 0) return false;
    // Reference point: notified_at якщо вже надсилали, інакше date_modified
    $refTs = !empty($r['notified_at']) ? strtotime((string)$r['notified_at']) : strtotime((string)$r['date_modified']);
    if (!$refTs) return false;
    return ($nowTs - $refTs) >= ($delayMin * 60);
}));

// Email blacklist (по одному pattern на рядок). Підтримує `*@domain.tld` wildcards.
$blacklistRaw = (string)$config->get('module_oc_kit_easycheckout_reminder_blacklist');
$blacklist = [];
foreach (preg_split('~\r?\n~', $blacklistRaw) as $line) {
    $line = trim($line);
    if ($line !== '') $blacklist[] = mb_strtolower($line);
}
$matchesBlacklist = static function (string $email) use ($blacklist): bool {
    $email = mb_strtolower($email);
    foreach ($blacklist as $p) {
        if ($p === $email) return true;
        if (strpos($p, '*@') === 0) {
            $domain = substr($p, 2);
            if ($domain !== '' && substr($email, -strlen('@' . $domain)) === '@' . $domain) return true;
        }
    }
    return false;
};

if (!$rows) {
    fwrite(STDOUT, "[okec-reminder] Nothing to send\n");
    exit(0);
}

require_once DIR_SYSTEM . 'library/mail.php';

$base = (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG);
$base = rtrim($base, '/');
$storeName = (string)$config->get('config_name');
$from      = (string)$config->get('config_email');

// Завантажуємо multilang шаблони (subject/body) з oc_kit_easycheckout_settings
$tplRows = $db->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
    WHERE `code`='reminder' AND `serialized`=1")->rows;
$subjectByLang = [];
$bodyByLang    = [];
foreach ($tplRows as $tr) {
    $dec = json_decode((string)$tr['value'], true);
    if (!is_array($dec)) continue;
    if ($tr['key'] === 'subject') $subjectByLang = $dec;
    elseif ($tr['key'] === 'body') $bodyByLang   = $dec;
}

// Lang lookup: language_id → code
$langRows = $db->query("SELECT `language_id`, `code` FROM `" . DB_PREFIX . "language` WHERE `status`=1")->rows;
$langCodeById = [];
foreach ($langRows as $lr) $langCodeById[(int)$lr['language_id']] = (string)$lr['code'];

$pickTemplate = static function (array $byLang, int $langId) use ($langCodeById, $config): string {
    $code = $langCodeById[$langId] ?? '';
    if ($code !== '' && !empty($byLang[$code])) return (string)$byLang[$code];
    // fallback: primary language code → first available → empty
    $primary = (string)$config->get('config_language');
    if ($primary !== '' && !empty($byLang[$primary])) return (string)$byLang[$primary];
    foreach ($byLang as $v) if (!empty($v)) return (string)$v;
    return '';
};

// Простий placeholder-engine: {firstname}, {lastname}, {email}, {store_name}, {recovery_url}, {total}, {currency}
$render = static function (string $tpl, array $vars): string {
    return preg_replace_callback('~\{([a-z_]+)\}~i', static function ($m) use ($vars) {
        return array_key_exists($m[1], $vars) ? (string)$vars[$m[1]] : $m[0];
    }, $tpl);
};

$sent = 0; $skipped = 0;
foreach ($rows as $r) {
    if ($matchesBlacklist((string)$r['email'])) {
        // Виштовхуємо запис до останнього stage щоб не повертатись до нього взагалі.
        $db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned`
            SET `notified_at` = NOW(), `reminder_count` = " . (int)$maxStage . "
            WHERE `abandoned_id` = " . (int)$r['abandoned_id']);
        $skipped++;
        fwrite(STDOUT, "[okec-reminder] skipped (blacklist) → {$r['email']}\n");
        continue;
    }
    $recoveryUrl = $base . '/index.php?route=checkout/easycheckout&recover=' . rawurlencode((string)$r['recovery_token']);
    $name        = trim((string)$r['firstname']) ?: 'customer';

    $vars = [
        'firstname'    => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'lastname'     => htmlspecialchars((string)$r['lastname'], ENT_QUOTES, 'UTF-8'),
        'email'        => htmlspecialchars((string)$r['email'], ENT_QUOTES, 'UTF-8'),
        'store_name'   => htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8'),
        'recovery_url' => htmlspecialchars($recoveryUrl, ENT_QUOTES, 'UTF-8'),
        'total'        => number_format((float)$r['total'], 2),
        'currency'     => htmlspecialchars((string)$r['currency_code'], ENT_QUOTES, 'UTF-8'),
    ];

    $subjectTpl = $pickTemplate($subjectByLang, (int)$r['language_id']) ?: '{store_name}: complete your order';
    $bodyTpl    = $pickTemplate($bodyByLang, (int)$r['language_id'])    ?:
        "<p>Hi {firstname},</p>\n" .
        "<p>You started a checkout at <strong>{store_name}</strong> but didn't finish.</p>\n" .
        "<p><a href=\"{recovery_url}\">Complete your order</a></p>\n" .
        "<p>Cart total: {total} {currency}</p>";

    $subject = $render($subjectTpl, $vars);
    $html    = $render($bodyTpl,    $vars);

    try {
        $mail = new \Mail($config->get('config_mail_engine') ?: 'mail');
        $mail->parameter      = $config->get('config_mail_parameter');
        $mail->smtp_hostname  = $config->get('config_mail_smtp_hostname');
        $mail->smtp_username  = $config->get('config_mail_smtp_username');
        $mail->smtp_password  = html_entity_decode($config->get('config_mail_smtp_password') ?? '', ENT_QUOTES, 'UTF-8');
        $mail->smtp_port      = $config->get('config_mail_smtp_port');
        $mail->smtp_timeout   = $config->get('config_mail_smtp_timeout');
        $mail->setTo((string)$r['email']);
        $mail->setFrom($from);
        $mail->setSender(html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'));
        $mail->setSubject($subject);
        $mail->setHtml($html);
        $mail->send();

        $db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned`
            SET `notified_at` = NOW(), `reminder_count` = `reminder_count` + 1
            WHERE `abandoned_id` = " . (int)$r['abandoned_id']);
        $sent++;
        $stage = (int)$r['reminder_count'] + 1;
        fwrite(STDOUT, "[okec-reminder] sent stage {$stage}/{$maxStage} → {$r['email']}\n");
    } catch (\Throwable $e) {
        fwrite(STDERR, "[okec-reminder] FAIL → {$r['email']}: " . $e->getMessage() . "\n");
    }
}

fwrite(STDOUT, "[okec-reminder] Done. Sent={$sent}, skipped={$skipped}, total=" . count($rows) . "\n");

// ─── Cleanup: видаляємо старі abandoned записи (recovered АБО notified та старі) ───
$retentionDays = max(7, (int)($config->get('module_oc_kit_easycheckout_abandoned_retention_days') ?: 90));
$cleaned = $db->query("DELETE a, p FROM `" . DB_PREFIX . "kit_easycheckout_abandoned` a
    LEFT JOIN `" . DB_PREFIX . "kit_easycheckout_abandoned_products` p
        ON p.`abandoned_id` = a.`abandoned_id`
    WHERE a.`date_modified` <= DATE_SUB(NOW(), INTERVAL " . (int)$retentionDays . " DAY)
      AND (a.`recovered_order_id` IS NOT NULL OR a.`notified_at` IS NOT NULL)");
$cleanedCount = (int)($db->countAffected() ?? 0);
if ($cleanedCount > 0) {
    fwrite(STDOUT, "[okec-cleanup] Removed {$cleanedCount} stale abandoned rows (>{$retentionDays}d)\n");
}

// Stale preview-tokens (admin Pages preview-iframe). Live ~5 min, чистимо все старіше.
$now = time();
$tokensCleaned = 0;
// Simple flat-tokens (serialized=0) — value це Unix timestamp expiration
$db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
    WHERE `code` = 'preview_tokens' AND `serialized` = 0
      AND CAST(`value` AS UNSIGNED) < " . (int)$now);
$tokensCleaned += (int)($db->countAffected() ?? 0);
// JSON-snapshot tokens (serialized=1) — парсимо expires
$tokenRows = $db->query("SELECT setting_id, value FROM `" . DB_PREFIX . "kit_easycheckout_settings`
    WHERE code = 'preview_tokens' AND serialized = 1")->rows;
$expiredIds = [];
foreach ($tokenRows as $tr) {
    $payload = json_decode((string)$tr['value'], true);
    if (!is_array($payload) || (int)($payload['expires'] ?? 0) < $now) {
        $expiredIds[] = (int)$tr['setting_id'];
    }
}
if ($expiredIds) {
    $db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
        WHERE setting_id IN (" . implode(',', $expiredIds) . ")");
    $tokensCleaned += count($expiredIds);
}
if ($tokensCleaned > 0) {
    fwrite(STDOUT, "[okec-cleanup] Removed {$tokensCleaned} stale preview-tokens\n");
}

exit(0);
