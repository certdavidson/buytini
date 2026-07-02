<?php
/**
 * Advanced Search Pro — Stats Service
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class StatsService {
    private $db;
    private $log;

    public function __construct($registry) {
        $this->db  = $registry->get('db');
        $this->log = $registry->has('log') ? $registry->get('log') : null;
    }

    public function logQuery($query, $results, $latencyMs, $sessionId = '') {
        $queryRaw  = (string)$query;
        $queryEsc  = $this->db->escape($queryRaw);
        $results   = (int)$results;
        $latencyMs = (int)$latencyMs;
        $sid       = $this->db->escape((string)$sessionId);

        // Collapse autocomplete prefix bursts. As the visitor types "радіостанція"
        // letter by letter, every keystroke hits live() and would log its own row
        // (ра, рад, раді, …). When the previous row from THIS session within a short
        // window is a prefix of the new query (forward typing) — or the new query is
        // a prefix of it (backspace) — we update that row in place instead of
        // inserting a new one. One typing burst → one logged intent, not a trail.
        if ($sid !== '') {
            $prev = $this->db->query(
                "SELECT `id`, `query` FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE `session_id` = '" . $sid . "'
                   AND `created_at` >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                 ORDER BY `id` DESC LIMIT 1"
            );
            if ($prev->num_rows) {
                $prevQ = (string)$prev->row['query'];
                if ($this->sameTypingBurst($prevQ, $queryRaw)) {
                    $keep = (mb_strlen($queryRaw) >= mb_strlen($prevQ)) ? $queryEsc : $this->db->escape($prevQ);
                    $this->db->query(
                        "UPDATE `" . DB_PREFIX . "asp_query_log`
                         SET `query` = '" . $keep . "', `results` = '" . $results . "', `latency_ms` = '" . $latencyMs . "', `created_at` = NOW()
                         WHERE `id` = '" . (int)$prev->row['id'] . "'"
                    );
                    $this->logStats($results, $latencyMs);
                    return;
                }
            }
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_query_log` SET `query` = '" . $queryEsc . "', `results` = '" . $results . "', `latency_ms` = '" . $latencyMs . "', `session_id` = '" . $sid . "', `created_at` = NOW()");
        $this->logStats($results, $latencyMs);
    }

    /** Two queries belong to the same typing burst when one is a prefix of the other (or they're equal). */
    private function sameTypingBurst($a, $b) {
        $a = mb_strtolower(trim((string)$a));
        $b = mb_strtolower(trim((string)$b));
        if ($a === '' || $b === '') { return false; }
        if ($a === $b) { return true; }
        return mb_strpos($a, $b) === 0 || mb_strpos($b, $a) === 0;
    }

    /** Daily aggregate (throughput, latency, no-results rate) — counted on every Manticore hit. */
    private function logStats($results, $latencyMs) {
        $date = date('Y-m-d');
        $no_results = (int)$results > 0 ? 0 : 1;
        $latencyMs = (int)$latencyMs;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_stats`
            SET `date` = '" . $this->db->escape($date) . "',
                `queries` = 1,
                `no_results` = '" . $no_results . "',
                `avg_latency_ms` = '" . $latencyMs . "',
                `p95_latency_ms` = '" . $latencyMs . "',
                `cache_hit_percent` = 0,
                `errors` = 0,
                `ai_tokens` = 0,
                `ai_cost` = 0.0000
            ON DUPLICATE KEY UPDATE
                `queries` = `queries` + 1,
                `no_results` = `no_results` + " . $no_results . ",
                `avg_latency_ms` = ROUND((`avg_latency_ms` * (`queries` - 1) + " . $latencyMs . ") / `queries`),
                `p95_latency_ms` = GREATEST(`p95_latency_ms`, " . $latencyMs . ")");
    }

    public function getPopularQueries($limit = 10, $days = 30) {
        $rows = $this->db->query(
            "SELECT `query`, COUNT(*) AS cnt
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE `query` <> ''
               AND `results` > 0
               AND `created_at` >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
             GROUP BY `query`
             ORDER BY cnt DESC
             LIMIT " . (int)$limit
        );
        $out = [];
        foreach ($rows->rows as $row) {
            $out[] = (string)$row['query'];
        }
        return $out;
    }

    public function registerCacheHit($isHit) {
        $date = date('Y-m-d');
        $hit  = $isHit ? 1 : 0;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_stats`
            SET `date` = '" . $this->db->escape($date) . "',
                `queries` = 0,
                `no_results` = 0,
                `avg_latency_ms` = 0,
                `p95_latency_ms` = 0,
                `cache_hit_percent` = '" . ($hit ? 100 : 0) . "',
                `errors` = 0,
                `ai_tokens` = 0,
                `ai_cost` = 0.0000
            ON DUPLICATE KEY UPDATE
                `cache_hit_percent` = ROUND((`cache_hit_percent` * GREATEST(`queries`, 1) + " . ($hit * 100) . ") / (GREATEST(`queries`, 1) + 1))");
    }

    public function logSearchError($message = '') {
        $date = date('Y-m-d');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_stats`
            SET `date` = '" . $this->db->escape($date) . "',
                `queries` = 0,
                `no_results` = 0,
                `avg_latency_ms` = 0,
                `p95_latency_ms` = 0,
                `cache_hit_percent` = 0,
                `errors` = 1,
                `ai_tokens` = 0,
                `ai_cost` = 0.0000
            ON DUPLICATE KEY UPDATE
                `errors` = `errors` + 1");
        if ($this->log && $message !== '') {
            $this->log->write('[AdvancedSearchPro] ' . $message);
        }
    }

    public function logAiUsage($tokens, $cost) {
        $tokens = max(0, (int)$tokens);
        $cost   = max(0.0, (float)$cost);
        $date   = date('Y-m-d');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_stats`
            SET `date` = '" . $this->db->escape($date) . "',
                `queries` = 0,
                `no_results` = 0,
                `avg_latency_ms` = 0,
                `p95_latency_ms` = 0,
                `cache_hit_percent` = 0,
                `errors` = 0,
                `ai_tokens` = '" . $tokens . "',
                `ai_cost` = '" . $cost . "'
            ON DUPLICATE KEY UPDATE
                `ai_tokens` = `ai_tokens` + " . $tokens . ",
                `ai_cost` = `ai_cost` + " . $cost);
    }

    public function purgeOldData($logTtlDays = 90) {
        $logTtlDays  = max(7, (int)$logTtlDays);
        $cronLogDays = 30;
        $queueDays   = 7;

        $deleted = ['query_log' => 0, 'cron_log' => 0, 'index_queue' => 0, 'embedding_queue' => 0];

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_query_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL " . $logTtlDays . " DAY)");
        $deleted['query_log'] = $this->db->countAffected();

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_cron_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL " . $cronLogDays . " DAY)");
        $deleted['cron_log'] = $this->db->countAffected();

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_index_queue` WHERE `status` IN ('done','error') AND `updated_at` < DATE_SUB(NOW(), INTERVAL " . $queueDays . " DAY)");
        $deleted['index_queue'] = $this->db->countAffected();

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_embedding_queue` WHERE `status` = 'done' AND `updated_at` < DATE_SUB(NOW(), INTERVAL " . $queueDays . " DAY)");
        $deleted['embedding_queue'] = $this->db->countAffected();

        return $deleted;
    }
}
