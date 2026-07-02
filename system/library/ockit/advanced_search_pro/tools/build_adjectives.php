<?php
/**
 * Advanced Search Pro — build the POS adjective set for query relaxation.
 *
 * Reads the catalog vocabulary (product names + descriptions) and tags each
 * Cyrillic word with pymorphy2 (Python 3.9) — words whose top parse is an
 * adjective/participle are written to data/adjectives.txt. The search engine
 * drops these (attributes) and keeps nouns (the product type) when an exact AND
 * match finds nothing, so "парфуми чоловічі" relaxes to "парфуми" instead of
 * flooding on the common modifier "чоловічі".
 *
 * Runs standalone (no OpenCart bootstrap): reads DB creds from the store's
 * config.php and shells out to a local pymorphy2. Safe to run from cron.
 *
 * Usage:
 *   php build_adjectives.php [--py=/opt/python3.9/bin/python3.9] [--langs=1,2,3] [--out=/abs/path]
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$opts    = getopt('', ['py:', 'langs:', 'out:', 'config:']);
$python  = $opts['py']  ?? '/opt/python3.9/bin/python3.9';
$langs   = isset($opts['langs']) ? array_values(array_filter(array_map('intval', explode(',', $opts['langs'])))) : [];
$outFile = $opts['out'] ?? (dirname(__DIR__) . '/data/adjectives.txt');

// Module layout: system/library/ockit/advanced_search_pro/tools/ → OC root is 5 up.
$config = $opts['config'] ?? (dirname(__DIR__, 5) . '/config.php');
if (!is_file($config)) {
    fwrite(STDERR, "config.php not found at $config (pass --config=)\n");
    exit(1);
}
$cfg = file_get_contents($config);
$def = function ($name, $fallback = '') use ($cfg) {
    return preg_match("/define\\('" . $name . "',\\s*'([^']*)'/", $cfg, $m) ? $m[1] : $fallback;
};
$host   = $def('DB_HOSTNAME', 'localhost');
$user   = $def('DB_USERNAME');
$pass   = $def('DB_PASSWORD');
$dbname = $def('DB_DATABASE');
$prefix = $def('DB_PREFIX', 'oc_');
$port   = (int)($def('DB_PORT', '3306') ?: 3306);

$db = mysqli_init();
if (!@$db->real_connect($host, $user, $pass, $dbname, $port)) {
    fwrite(STDERR, "DB connect failed: " . mysqli_connect_error() . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$where = $langs ? ' WHERE language_id IN (' . implode(',', $langs) . ')' : '';
$res = $db->query("SELECT name, description FROM `" . $prefix . "product_description`" . $where);
if (!$res) {
    fwrite(STDERR, "query failed: " . $db->error . "\n");
    exit(1);
}

$vocab = [];
while ($row = $res->fetch_row()) {
    $text = preg_replace('/<[^>]+>/u', ' ', $row[0] . ' ' . $row[1]);
    if (preg_match_all('/[А-Яа-яІіЇїЄєҐґЁё]{3,30}/u', $text, $m)) {
        foreach ($m[0] as $w) {
            $vocab[mb_strtolower($w, 'UTF-8')] = true;
        }
    }
}
$res->free();
$db->close();

if (!$vocab) {
    fwrite(STDERR, "empty vocabulary\n");
    exit(1);
}

// pymorphy2 tagger: keep a word when its top parse (uk or ru) is an adjective.
$tagger = <<<'PY'
import sys, pymorphy2
mu = pymorphy2.MorphAnalyzer(lang='uk')
mr = pymorphy2.MorphAnalyzer(lang='ru')
adj = set()
for line in sys.stdin:
    w = line.strip()
    if not w:
        continue
    best, bs = None, -1.0
    for m in (mu, mr):
        try:
            p = m.parse(w)
        except Exception:
            continue
        if p:
            sc = getattr(p[0], 'score', 0.0) or 0.0
            if sc > bs:
                bs, best = sc, str(p[0].tag)
    if best and ('ADJF' in best or 'ADJS' in best or 'PRTF' in best):
        adj.add(w)
sys.stdout.write('\n'.join(sorted(adj)))
PY;

$spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open(escapeshellarg($python) . ' -c ' . escapeshellarg($tagger), $spec, $pipes);
if (!is_resource($proc)) {
    fwrite(STDERR, "cannot launch python: $python\n");
    exit(1);
}
fwrite($pipes[0], implode("\n", array_keys($vocab)));
fclose($pipes[0]);
$adjOut = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$err = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$code = proc_close($proc);
if ($code !== 0) {
    fwrite(STDERR, "python failed (exit $code): " . trim($err) . "\n");
    exit(1);
}

$adj = array_values(array_filter(array_map('trim', explode("\n", $adjOut)), 'strlen'));
if (!$adj) {
    fwrite(STDERR, "no adjectives produced\n");
    exit(1);
}

@mkdir(dirname($outFile), 0775, true);
$tmp = $outFile . '.tmp';
file_put_contents($tmp, implode("\n", $adj) . "\n");
rename($tmp, $outFile);
fwrite(STDOUT, sprintf("vocab=%d adjectives=%d -> %s\n", count($vocab), count($adj), $outFile));
