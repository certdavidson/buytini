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

class SphinxClient {
    protected $host;
    protected $port;
    protected $index;
    protected $user;
    protected $pass;
    protected $timeout;

    public function __construct($options = []) {
        $this->host = $options['host'] ?? '127.0.0.1';
        $this->port = $options['port'] ?? 9306;
        $this->index = $options['index'] ?? 'products';
        $this->user = $options['user'] ?? '';
        $this->pass = $options['pass'] ?? '';
        $this->timeout = $options['timeout'] ?? 2;
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

    public function query($sql) {
        $mysqli = $this->connect();
        $result = $mysqli->query($sql);
        if ($result === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception($error);
        }

        $rows = [];
        if ($result instanceof mysqli_result) {
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
        $result = $mysqli->query($sql);
        if ($result === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception($error);
        }

        $rows = [];
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }

        $meta = [];
        $meta_result = $mysqli->query('SHOW META');
        if ($meta_result instanceof mysqli_result) {
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
            throw new \Exception('Sphinx connection failed: mysqli_init error');
        }

        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, (int)$this->timeout);
        $connected = @$mysqli->real_connect($this->host, $this->user, $this->pass, '', (int)$this->port);
        if (!$connected || $mysqli->connect_errno) {
            throw new \Exception('Sphinx connection failed: ' . $mysqli->connect_error);
        }

        return $mysqli;
    }
}
