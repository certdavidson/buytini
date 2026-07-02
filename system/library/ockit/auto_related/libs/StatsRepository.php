<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated\Libs;

/**
 * Queries for dashboard statistics.
 */
class StatsRepository
{
    /** Cross-request cache TTL in seconds. Stats are loaded on every admin
     *  Stats-tab open — repeated COUNT(*) over `oc_product_related` is the
     *  expensive bit on large catalogues. */
    private const CACHE_TTL = 300;

    private \DB $db;
    /** @var \Cache|null */
    private $cache;
    /** @var array<string, array{at:int, data:mixed}> */
    private array $local = [];

    public function __construct(\DB $db, $cache = null)
    {
        $this->db    = $db;
        $this->cache = $cache;
    }

    private function cached(string $key, callable $loader)
    {
        if (isset($this->local[$key])) {
            return $this->local[$key]['data'];
        }
        if ($this->cache !== null) {
            $stored = $this->cache->get('ok_ar_stats.' . $key);
            if (is_array($stored) && isset($stored['at'], $stored['data'])
                && (time() - (int)$stored['at']) < self::CACHE_TTL) {
                $this->local[$key] = $stored;
                return $stored['data'];
            }
        }
        $data = $loader();
        $entry = ['at' => time(), 'data' => $data];
        $this->local[$key] = $entry;
        if ($this->cache !== null) {
            $this->cache->set('ok_ar_stats.' . $key, $entry);
        }
        return $data;
    }

    public function getSummary(): array
    {
        return $this->cached('summary', function () {
            $total = (int)($this->db->query(
                "SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product`"
            )->row['cnt'] ?? 0);

            $withRelated = (int)($this->db->query(
                "SELECT COUNT(DISTINCT product_id) AS cnt FROM `" . DB_PREFIX . "product_related`"
            )->row['cnt'] ?? 0);

            $coverage = $total > 0 ? round($withRelated / $total * 100, 1) : 0.0;

            return [
                'total'        => $total,
                'with_related' => $withRelated,
                'coverage'     => $coverage,
                'without'      => $total - $withRelated,
            ];
        });
    }

    public function getRecentLog(int $limit = 10): array
    {
        return $this->cached('recent.' . (int)$limit, function () use ($limit) {
            $langRow = $this->db->query("SELECT value FROM `" . DB_PREFIX . "setting` WHERE `key` = 'config_language_id'")->row;
            $langId  = (int)($langRow['value'] ?? 1);

            $result = $this->db->query(
                "SELECT arl.log_id, arl.product_id, arl.generated_at, arl.source, arl.count,
                        pd.name AS product_name
                 FROM `" . DB_PREFIX . "auto_related_log` arl
                 LEFT JOIN `" . DB_PREFIX . "product_description` pd
                   ON (pd.product_id = arl.product_id AND pd.language_id = " . (int)$langId . ")
                 ORDER BY arl.generated_at DESC
                 LIMIT " . (int)$limit
            );
            return $result->rows;
        });
    }

    public function getSourceDistribution(): array
    {
        return $this->cached('distribution', function () {
            $result = $this->db->query(
                "SELECT source, COUNT(*) AS cnt
                 FROM `" . DB_PREFIX . "auto_related_log`
                 GROUP BY source"
            );
            $dist = ['cron' => 0, 'visit' => 0, 'manual' => 0];
            foreach ($result->rows as $row) {
                $dist[$row['source']] = (int)$row['cnt'];
            }
            return $dist;
        });
    }

    public function getDailyTrend(int $days = 30): array
    {
        return $this->cached('trend.' . (int)$days, function () use ($days) {
            $result = $this->db->query(
                "SELECT DATE(generated_at) AS day, COUNT(*) AS cnt
                 FROM `" . DB_PREFIX . "auto_related_log`
                 WHERE generated_at >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
                 GROUP BY DATE(generated_at)
                 ORDER BY day ASC"
            );
            return $result->rows;
        });
    }

}
