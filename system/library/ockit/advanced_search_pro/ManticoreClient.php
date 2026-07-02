<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro;

class ManticoreClient {
    protected $host;
    protected $port;
    protected $httpPort;
    protected $index;
    protected $user;
    protected $pass;
    protected $timeout;

    public function __construct($options = []) {
        $this->host     = $options['host']      ?? '127.0.0.1';
        $this->port     = $options['port']      ?? 9306;
        // HTTP port for JSON API (/search, /sql). Default Manticore mapping is
        // SQL 9306 → HTTP 9308. Buddy plugins (fuzzy, autocomplete) only fire
        // reliably on this endpoint.
        $this->httpPort = $options['http_port'] ?? 9308;
        $this->index    = $options['index']     ?? 'products';
        $this->user     = $options['user']      ?? '';
        $this->pass     = $options['pass']      ?? '';
        $this->timeout  = $options['timeout']   ?? 2;
    }

    public function getIndex() {
        return $this->index;
    }

    /**
     * POST a JSON payload to Manticore's HTTP /search endpoint. Returns the
     * decoded response array, or null on transport error.
     *
     * Used for fuzzy search — Manticore Buddy's fuzzy plugin only rewrites
     * requests reliably on the HTTP/JSON path; the SQL path silently returns
     * zero rows in v25.0.0.
     */
    public function searchHttp(array $payload) {
        $url = 'http://' . $this->host . ':' . $this->httpPort . '/search';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int)$this->timeout,
            CURLOPT_TIMEOUT        => (int)$this->timeout + 3,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new \Exception('Manticore HTTP error: ' . $err);
        }
        $resp = json_decode($raw, true);
        return is_array($resp) ? $resp : null;
    }

    /**
     * Dictionary-based spelling suggestions via Manticore CALL QSUGGEST.
     * Returns [['suggest'=>string,'distance'=>int,'docs'=>int], ...] ordered
     * by Manticore's own relevance (distance asc, docs desc). Empty on error.
     *
     * Used for did-you-mean / typo correction — far more reliable than raw
     * fuzzy OR-expansion, because candidates come from words that actually
     * exist in the index (with their document frequencies).
     */
    public function suggest($word, $index, $limit = 5) {
        $word  = $this->escape((string)$word);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$index);
        if ($word === '' || $index === '') {
            return [];
        }
        $sql = "CALL QSUGGEST('" . $word . "', '" . $index . "', " . max(1, (int)$limit) . " as limit)";
        try {
            $rows = $this->query($sql);
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            if (isset($r['suggest']) && $r['suggest'] !== '') {
                $out[] = [
                    'suggest'  => (string)$r['suggest'],
                    'distance' => (int)($r['distance'] ?? 99),
                    'docs'     => (int)($r['docs'] ?? 0),
                ];
            }
        }
        return $out;
    }

    public function test() {
        try {
            $mysqli = $this->connect();
            $result = $mysqli->query("SHOW STATUS LIKE 'uptime'");
            $mysqli->close();
            return $result ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Health cache keyed by host:port:index — checked once per request.
    private static $readyCache = [];

    /**
     * Is the daemon reachable AND the index populated? Used to decide whether
     * Manticore is authoritative or the search must fall back to native MySQL
     * (degraded mode). An empty/missing index counts as NOT ready, so a fresh
     * store that has not reindexed yet still serves results via native.
     */
    public function isReady($index = null) {
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($index !== null ? $index : $this->index));
        if ($index === '') {
            return false;
        }
        $cacheKey = $this->host . ':' . $this->port . ':' . $index;
        if (array_key_exists($cacheKey, self::$readyCache)) {
            return self::$readyCache[$cacheKey];
        }
        $ok = false;
        try {
            $rows = $this->query("SELECT COUNT(*) AS c FROM " . $index);
            $ok = !empty($rows) && (int)($rows[0]['c'] ?? 0) > 0;
        } catch (\Throwable $e) {
            $ok = false;
        }
        return self::$readyCache[$cacheKey] = $ok;
    }

    public function query($sql) {
        $mysqli = $this->connect();
        // @ — Manticore errors (e.g. unknown index) surface as an mysqli warning
        // *before* query() returns false; that raw warning cannot be caught by
        // try/catch. Suppress it here and rethrow as an Exception below.
        $result = @$mysqli->query($sql);
        if ($result === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception($error);
        }
        $rows = [];
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $mysqli->close();
        return $rows;
    }

    public function queryWithMeta($sql) {
        $mysqli = $this->connect();
        $result = @$mysqli->query($sql);
        if ($result === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception($error);
        }

        $rows = [];
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }

        $meta = [];
        $meta_result = $mysqli->query('SHOW META');
        if ($meta_result instanceof \mysqli_result) {
            while ($row = $meta_result->fetch_assoc()) {
                if (isset($row['Variable_name'])) {
                    $meta[$row['Variable_name']] = $row['Value'] ?? null;
                }
            }
            $meta_result->free();
        }

        $mysqli->close();
        return [
            'rows' => $rows,
            'meta' => $meta
        ];
    }

    public function escape($value) {
        // Pure-PHP escape — avoids opening a new TCP connection per value.
        // Replicates the character substitutions of mysqli::real_escape_string
        // for the MySQL/Manticore protocol (NUL, LF, CR, \, ', ", Ctrl+Z).
        return strtr((string)$value, [
            "\0"   => '\\0',
            "\n"   => '\\n',
            "\r"   => '\\r',
            "\\"   => '\\\\',
            "'"    => "\\'",
            '"'    => '\\"',
            "\x1a" => '\\Z',
        ]);
    }

    protected function connect() {
        $mysqli = mysqli_init();
        if (!$mysqli) {
            throw new \Exception('Manticore connection failed: mysqli_init error');
        }

        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, (int)$this->timeout);
        $connected = @$mysqli->real_connect($this->host, $this->user, $this->pass, '', (int)$this->port);
        if (!$connected || $mysqli->connect_errno) {
            throw new \Exception('Manticore connection failed: ' . $mysqli->connect_error);
        }

        return $mysqli;
    }
}
