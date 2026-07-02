<?php
/**
 * Advanced Search Pro — Ukrainian morphology builder (CLI).
 *
 * Generates a catalog-scoped Manticore `wordforms` file (form > lemma) for
 * Ukrainian. There is no official Ukrainian lemmatizer .pak for Manticore, so
 * inflection handling is done with a wordforms file derived from the brown-uk
 * dictionary (https://github.com/brown-uk/dict_uk, CC-BY/GPL).
 *
 * "Catalog-scoped" = only word families that actually occur in this store's
 * product names / descriptions / tags / categories / brands are emitted. That
 * keeps the file small (a few MB) and the Manticore RAM footprint low, instead
 * of the ~120 MB a full dictionary would need.
 *
 * Usage:
 *   php build_uk_wordforms.php [--out=/var/lib/manticore/wordforms/uk.txt]
 *                              [--master=/path/dict_corp_vis.txt]
 *                              [--min-len=3]
 *
 * If --master is omitted the script downloads the brown-uk release once and
 * caches it next to this script. After it finishes, run a full reindex so the
 * Manticore table is recreated with the new wordforms file.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$opts = getopt('', ['out::', 'master::', 'min-len::', 'master-url::']);
$DICT_URL = $opts['master-url'] ?? 'https://github.com/brown-uk/dict_uk/releases/download/v6.8.0/dict_corp_vis.txt.bz2';
$OUT      = $opts['out'] ?? '/var/lib/manticore/wordforms/uk.txt';
$MIN_LEN  = max(2, (int)($opts['min-len'] ?? 3));
$CACHE    = __DIR__ . '/cache';
$MASTER   = $opts['master'] ?? ($CACHE . '/dict_corp_vis.txt');

// ── Locate and bootstrap OpenCart so we can read the product vocabulary ──────
$root = realpath(__DIR__ . '/../../../../../');   // → store root (…/system/library/ockit/advanced_search_pro/tools)
$configPath = $root . '/config.php';
if (!is_file($configPath)) {
    // Fallback: walk up until config.php is found.
    $dir = __DIR__;
    for ($i = 0; $i < 8; $i++) {
        $dir = dirname($dir);
        if (is_file($dir . '/config.php')) { $configPath = $dir . '/config.php'; break; }
    }
}
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found — run from inside an OpenCart install\n");
    exit(1);
}
require($configPath);

$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? (int)DB_PORT : 3306);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'DB connect failed: ' . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

// ── 1. Collect catalog vocabulary ────────────────────────────────────────────
fwrite(STDOUT, "Collecting catalog vocabulary…\n");
$vocab = [];
$collect = function ($text) use (&$vocab, $MIN_LEN) {
    $text = mb_strtolower(strip_tags((string)$text), 'UTF-8');
    if (preg_match_all('/[\p{L}\p{N}]+/u', $text, $m)) {
        foreach ($m[0] as $w) {
            if (mb_strlen($w, 'UTF-8') >= $MIN_LEN) {
                $vocab[$w] = true;
            }
        }
    }
};

$res = $mysqli->query("SELECT name, description, tag FROM `" . DB_PREFIX . "product_description`");
while ($res && ($row = $res->fetch_assoc())) {
    $collect($row['name']); $collect($row['description']); $collect($row['tag']);
}
foreach (['category_description' => 'name', 'manufacturer' => 'name'] as $tbl => $col) {
    $r = $mysqli->query("SELECT `$col` AS v FROM `" . DB_PREFIX . "$tbl`");
    while ($r && ($row = $r->fetch_assoc())) { $collect($row['v']); }
}
fwrite(STDOUT, '  unique tokens: ' . count($vocab) . "\n");
if (!$vocab) {
    fwrite(STDERR, "No vocabulary collected — aborting\n");
    exit(1);
}

// ── 2. Ensure master dictionary is present ───────────────────────────────────
if (!is_file($MASTER)) {
    if (!is_dir($CACHE)) { @mkdir($CACHE, 0755, true); }
    $bz2 = $CACHE . '/dict_corp_vis.txt.bz2';
    fwrite(STDOUT, "Downloading brown-uk dictionary…\n");
    $fp = fopen($bz2, 'w');
    $ch = curl_init($DICT_URL);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 600, CURLOPT_CONNECTTIMEOUT => 20,
    ]);
    $ok = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if (!$ok) {
        fwrite(STDERR, "Download failed: $err\n");
        exit(1);
    }
    // Decompress (.bz2). PREFER the system bunzip2 binary — PHP's ext-bz2
    // (bzread) does NOT handle multi-stream bzip2 files and silently stops at
    // the first stream, leaving a truncated dictionary (only ~30 lemmas instead
    // of thousands). brown-uk releases ship as multi-stream, so we must use
    // bunzip2 if available. Fall back to ext-bz2 only when no binary is found.
    $bzipBin = null;
    foreach (['/usr/bin/bunzip2', '/bin/bunzip2', '/usr/local/bin/bunzip2'] as $candidate) {
        if (is_executable($candidate)) { $bzipBin = $candidate; break; }
    }
    if ($bzipBin === null && function_exists('exec')) {
        @exec('command -v bunzip2 2>/dev/null', $o, $code);
        if ($code === 0 && !empty($o[0])) { $bzipBin = trim($o[0]); }
    }
    if ($bzipBin !== null) {
        exec(escapeshellarg($bzipBin) . ' -kf ' . escapeshellarg($bz2), $o, $code);
        if ($code !== 0) {
            fwrite(STDERR, "bunzip2 failed (exit $code)\n");
            exit(1);
        }
    } elseif (function_exists('bzopen')) {
        fwrite(STDOUT, "WARN: bunzip2 binary not found, falling back to ext-bz2.\n");
        fwrite(STDOUT, "      Multi-stream archives will be truncated — install bzip2 package.\n");
        $in = bzopen($bz2, 'r'); $out = fopen($MASTER, 'w');
        while (!feof($in)) { fwrite($out, bzread($in, 1 << 20)); }
        bzclose($in); fclose($out);
    } else {
        fwrite(STDERR, "Neither bunzip2 binary nor ext-bz2 available\n");
        exit(1);
    }
}
if (!is_file($MASTER)) {
    fwrite(STDERR, "Master dictionary missing after download\n");
    exit(1);
}

// ── 3. Stream brown-uk groups, emit families that touch the catalog ──────────
fwrite(STDOUT, "Building scoped wordforms…\n");
$fh  = fopen($MASTER, 'r');
$tmp = $OUT . '.tmp';
$out = fopen($tmp, 'w');
$KEEP = ['noun', 'adj'];

$curLemma = null; $curKeep = false; $curForms = []; $curFamily = [];
$lemmas = 0; $maps = 0; $seen = [];

$flush = function () use (&$curLemma, &$curKeep, &$curForms, &$curFamily, &$vocab, &$out, &$lemmas, &$maps, &$seen) {
    if ($curLemma === null || !$curKeep) { return; }
    $hit = false;
    foreach ($curFamily as $f) { if (isset($vocab[$f])) { $hit = true; break; } }
    if (!$hit) { return; }
    $l = mb_strtolower($curLemma, 'UTF-8');
    $wrote = false;
    foreach ($curForms as $form) {
        $f = mb_strtolower($form, 'UTF-8');
        if ($f === $l) { continue; }
        $key = $f . '>' . $l;
        if (isset($seen[$key])) { continue; }
        $seen[$key] = true;
        fwrite($out, $f . ' > ' . $l . "\n");
        $maps++; $wrote = true;
    }
    if ($wrote) { $lemmas++; }
};

while (($line = fgets($fh)) !== false) {
    $raw = rtrim($line, "\r\n");
    if (trim($raw) === '') { continue; }
    if (strpos($raw, '#') !== false) { $raw = rtrim(substr($raw, 0, strpos($raw, '#'))); }
    if ($raw === '') { continue; }
    $indented = ($raw[0] === ' ' || $raw[0] === "\t");
    // preg_split with /u returns false on invalid UTF-8 (PHP 8 then crashes
    // with TypeError on count(false); PHP 7.4 only warns). brown-uk is
    // mostly clean but occasional bad bytes sneak in — skip such lines.
    $parts = preg_split('/\s+/u', trim($raw));
    if (!is_array($parts) || count($parts) < 2) { continue; }
    $form = $parts[0]; $tags = $parts[1];
    if (!$indented) {
        $flush();
        $curLemma = $form;
        $curKeep  = false;
        foreach ($KEEP as $k) { if (strpos($tags, $k) !== false) { $curKeep = true; break; } }
        $curForms  = [$form];
        $curFamily = [mb_strtolower($form, 'UTF-8')];
    } elseif ($curLemma !== null) {
        $curForms[]  = $form;
        $curFamily[] = mb_strtolower($form, 'UTF-8');
    }
}
$flush();
fclose($fh);
fclose($out);

if (!rename($tmp, $OUT)) {
    fwrite(STDERR, "Could not move result to $OUT (check permissions)\n");
    exit(1);
}
@chmod($OUT, 0644);

fwrite(STDOUT, "Done. lemmas=$lemmas mappings=$maps → $OUT\n");
fwrite(STDOUT, "Next: run a Full Reindex in the admin so Manticore picks up the new wordforms.\n");
