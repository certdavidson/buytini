<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

use OcKit\AdvancedSearchPro\AdvancedSearchPro;

class ModelExtensionModuleOcKitAdvancedSearchPro extends Model {
    public function getIndexedProductsCount() {
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        $settings = $asp->getSettings([
            'mode' => 'native',
            'index' => 'products',
            'sphinx_index' => 'products'
        ]);

        return (int)$asp->getIndexDocumentsCount($settings);
    }

    public function getIndexMetrics() {
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        $settings = $asp->getSettings([
            'mode' => 'native',
            'index' => 'products',
            'sphinx_index' => 'products'
        ]);

        $indexed = (int)$asp->getIndexDocumentsCount($settings);
        $lastIndexed = (string)$asp->getMeta('last_indexed_at', '-');
        $indexSize = (string)$asp->getMeta('last_index_size', '');

        if ($indexSize === '') {
            $indexSize = $this->estimateIndexSize($settings['mode']);
            $asp->setMeta('last_index_size', $indexSize);
        }

        return [
            'indexed_products' => $indexed,
            'last_indexed' => $lastIndexed,
            'index_size' => $indexSize
        ];
    }

    private function estimateIndexSize($mode) {
        $mode = strtolower((string)$mode);
        $dbName = defined('DB_DATABASE') ? DB_DATABASE : '';
        if ($dbName === '') {
            return '-';
        }

        if ($mode === 'native') {
            $tables = [
                DB_PREFIX . 'product',
                DB_PREFIX . 'product_description',
                DB_PREFIX . 'product_to_category',
                DB_PREFIX . 'product_attribute'
            ];
            $escaped = [];
            foreach ($tables as $table) {
                $escaped[] = "'" . $this->db->escape($table) . "'";
            }

            $row = $this->db->query(
                "SELECT SUM(data_length + index_length) AS bytes
                 FROM information_schema.TABLES
                 WHERE table_schema = '" . $this->db->escape($dbName) . "'
                   AND table_name IN (" . implode(',', $escaped) . ")"
            )->row;

            return $this->formatBytes((int)($row['bytes'] ?? 0));
        }

        return 'external';
    }

    private function formatBytes($bytes) {
        $bytes = (float)$bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int)floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);

        return number_format($value, 2) . ' ' . $units[$power];
    }

    public function getStatsSummary() {
        $today = date('Y-m-d');
        $summary = [
            'queries_today' => 0,
            'queries_7' => 0,
            'queries_30' => 0,
            'no_results_today' => 0,
            'avg_latency' => '-',
            'p95_latency' => '-',
            'cache_hit' => '-',
            'errors' => 0,
            'ai_tokens_today' => 0,
            'ai_cost_today' => 0.0,
            'ai_cost_month' => 0.0
        ];

        $today_row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "asp_stats` WHERE `date` = '" . $this->db->escape($today) . "'")->row;
        if ($today_row) {
            $summary['queries_today'] = (int)$today_row['queries'];
            $summary['no_results_today'] = (int)$today_row['no_results'];
            $summary['avg_latency'] = (int)$today_row['avg_latency_ms'] . ' ms';
            $summary['p95_latency'] = (int)$today_row['p95_latency_ms'] . ' ms';
            $summary['cache_hit'] = (int)$today_row['cache_hit_percent'] . '%';
            $summary['errors'] = (int)$today_row['errors'];
            $summary['ai_tokens_today'] = (int)$today_row['ai_tokens'];
            $summary['ai_cost_today'] = (float)$today_row['ai_cost'];
        }

        $range_7 = $this->db->query("SELECT SUM(queries) AS total, SUM(no_results) AS no_results FROM `" . DB_PREFIX . "asp_stats` WHERE `date` >= DATE_SUB('" . $this->db->escape($today) . "', INTERVAL 6 DAY)");
        $summary['queries_7'] = (int)$range_7->row['total'];
        $summary['no_results_7'] = (int)$range_7->row['no_results'];

        $range_30 = $this->db->query("SELECT SUM(queries) AS total FROM `" . DB_PREFIX . "asp_stats` WHERE `date` >= DATE_SUB('" . $this->db->escape($today) . "', INTERVAL 29 DAY)");
        $summary['queries_30'] = (int)$range_30->row['total'];

        $month_cost = $this->db->query("SELECT SUM(ai_cost) AS total FROM `" . DB_PREFIX . "asp_stats` WHERE `date` >= DATE_SUB('" . $this->db->escape($today) . "', INTERVAL 29 DAY)");
        $summary['ai_cost_month'] = (float)$month_cost->row['total'];

        return $summary;
    }

    public function getTopQueries($limit = 20) {
        $limit = (int)$limit;
        $query = $this->db->query(
            "SELECT `query`, COUNT(*) AS total, MAX(`created_at`) AS last_seen
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE `query` != '' AND `query` IS NOT NULL
             GROUP BY `query` ORDER BY total DESC LIMIT " . $limit
        );
        $items = [];
        foreach ($query->rows as $row) {
            $items[] = [
                'query'     => $row['query'],
                'count'     => (int)$row['total'],
                'last_seen' => $row['last_seen'],
            ];
        }
        return $items;
    }

    public function getNoResultQueries($limit = 20) {
        $limit = (int)$limit;
        $query = $this->db->query(
            "SELECT ql.`query`, COUNT(*) AS total, MAX(ql.`created_at`) AS last_seen,
                    IF(qr.id IS NOT NULL, 1, 0) AS has_rule
             FROM `" . DB_PREFIX . "asp_query_log` ql
             LEFT JOIN `" . DB_PREFIX . "asp_query_rule` qr ON qr.query_normalized = ql.`query`
             WHERE ql.results = 0 AND ql.`query` != '' AND ql.`query` IS NOT NULL
             GROUP BY ql.`query`, qr.id ORDER BY total DESC LIMIT " . $limit
        );
        $items = [];
        foreach ($query->rows as $row) {
            $items[] = [
                'query'     => $row['query'],
                'count'     => (int)$row['total'],
                'last_seen' => $row['last_seen'],
                'has_rule'  => (bool)$row['has_rule'],
            ];
        }
        return $items;
    }

    public function getNoResultQueryRows($limit = 50, $days = 30, $min_count = 2) {
        $limit = max(1, min(500, (int)$limit));
        $days = max(1, min(365, (int)$days));
        $min_count = max(1, min(1000, (int)$min_count));

        $query = $this->db->query(
            "SELECT `query`, COUNT(*) AS total
             FROM `" . DB_PREFIX . "asp_query_log`
             WHERE results = 0
               AND `query` <> ''
               AND `created_at` >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
             GROUP BY `query`
             HAVING total >= " . (int)$min_count . "
             ORDER BY total DESC
             LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    public function getRecentQueries($limit = 20) {
        $limit = max(1, min(200, (int)$limit));
        // Group by query — show the latest occurrence + total hit count for it,
        // so the list is dedup'd instead of listing the same query repeatedly.
        $query = $this->db->query(
            "SELECT q.`query`, q.`results`, q.`created_at`, g.hits
             FROM `" . DB_PREFIX . "asp_query_log` q
             INNER JOIN (
                 SELECT `query`, COUNT(*) AS hits, MAX(id) AS max_id
                 FROM `" . DB_PREFIX . "asp_query_log`
                 WHERE `query` <> '' AND `query` IS NOT NULL
                 GROUP BY `query`
             ) g ON g.max_id = q.id
             ORDER BY q.id DESC
             LIMIT " . $limit
        );
        $items = [];
        foreach ($query->rows as $row) {
            $items[] = [
                'query'      => $row['query'],
                'results'    => (int)$row['results'],
                'hits'       => (int)$row['hits'],
                'created_at' => $row['created_at'],
            ];
        }
        return $items;
    }

    public function getAllNoResultQueries($limit = 50) {
        $limit = max(1, min(500, (int)$limit));
        // Group by query (dedup) + LEFT JOIN asp_query_rule so the UI knows
        // whether a rule already exists for that phrase — without it the
        // "Generate rule" button stays active forever after page reload.
        $query = $this->db->query(
            "SELECT ql.`query`, MAX(ql.`created_at`) AS created_at,
                    IF(qr.id IS NOT NULL, 1, 0) AS has_rule
             FROM `" . DB_PREFIX . "asp_query_log` ql
             LEFT JOIN `" . DB_PREFIX . "asp_query_rule` qr ON qr.query_normalized = ql.`query`
             WHERE ql.`results` = 0 AND ql.`query` <> '' AND ql.`query` IS NOT NULL
             GROUP BY ql.`query`, qr.id
             ORDER BY MAX(ql.`id`) DESC
             LIMIT " . $limit
        );
        $items = [];
        foreach ($query->rows as $row) {
            $items[] = [
                'query'      => $row['query'],
                'created_at' => $row['created_at'],
                'has_rule'   => (bool)$row['has_rule'],
            ];
        }
        return $items;
    }

    public function getCronLog($limit = 20) {
        $limit = (int)$limit;
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "asp_cron_log` ORDER BY id DESC LIMIT " . $limit);
        return $rows->rows;
    }

    /** Per-day aggregate rows for the stats export (newest first). */
    public function getDailyStats($days = 90) {
        $days = max(1, min(365, (int)$days));
        $rows = $this->db->query(
            "SELECT `date`, `queries`, `no_results`, `avg_latency_ms`, `p95_latency_ms`, `cache_hit_percent`, `errors`, `ai_tokens`, `ai_cost`
             FROM `" . DB_PREFIX . "asp_stats`
             WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
             ORDER BY `date` DESC"
        );
        return $rows->rows;
    }

    public function clearCronLog() {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "asp_cron_log`");
    }

    public function clearQueryLog() {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "asp_query_log`");
    }

    public function queueIndex($entityType, $entityId, $action = 'upsert') {
        // Skip real-time queue in cron-only mode
        $trigger = (string)$this->config->get('module_oc_kit_advanced_search_pro_index_trigger');
        if ($trigger === 'cron') {
            return;
        }

        $entityType = $this->db->escape($entityType);
        $entityId = (int)$entityId;
        $action = $this->db->escape($action);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_index_queue` WHERE entity_type = '" . $entityType . "' AND entity_id = '" . $entityId . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_index_queue` SET entity_type = '" . $entityType . "', entity_id = '" . $entityId . "', action = '" . $action . "', status = 'pending', attempts = 0, created_at = NOW(), updated_at = NOW()");
    }

    public function syncSynonymsFromRaw($raw) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym` ");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym_group` ");

        $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_filter(array_map('trim', explode(',', $line)));
            if (!$parts) {
                continue;
            }
            $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_synonym_group` SET name = '" . $this->db->escape(implode(', ', $parts)) . "', created_at = NOW()");
            $group_id = (int)$this->db->getLastId();
            foreach ($parts as $term) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_synonym` SET group_id = '" . $group_id . "', term = '" . $this->db->escape($term) . "'");
            }
        }
    }

    public function getSynonymGroupsDetailed() {
        $groups = $this->db->query("SELECT group_id, name, created_at FROM `" . DB_PREFIX . "asp_synonym_group` ORDER BY group_id DESC")->rows;
        if (!$groups) {
            return [];
        }

        $groupIds = [];
        foreach ($groups as $g) {
            $groupIds[] = (int)$g['group_id'];
        }

        $termsRows = $this->db->query(
            "SELECT group_id, term
             FROM `" . DB_PREFIX . "asp_synonym`
             WHERE group_id IN (" . implode(',', $groupIds) . ")
             ORDER BY synonym_id ASC"
        )->rows;

        $termsMap = [];
        foreach ($termsRows as $row) {
            $gid = (int)$row['group_id'];
            if (!isset($termsMap[$gid])) {
                $termsMap[$gid] = [];
            }
            $term = trim((string)$row['term']);
            if ($term !== '') {
                $termsMap[$gid][] = $term;
            }
        }

        $result = [];
        foreach ($groups as $group) {
            $gid = (int)$group['group_id'];
            $terms = isset($termsMap[$gid]) ? array_values(array_unique($termsMap[$gid])) : [];
            $result[] = [
                'group_id' => $gid,
                'name' => (string)$group['name'],
                'created_at' => (string)$group['created_at'],
                'terms' => $terms,
                'terms_raw' => implode(', ', $terms)
            ];
        }

        return $result;
    }

    public function addSynonymGroup(array $terms, $name = '') {
        $clean = [];
        foreach ($terms as $term) {
            $term = trim((string)$term);
            if ($term !== '') {
                $clean[] = $term;
            }
        }
        $clean = array_values(array_unique($clean));
        if (count($clean) < 2) {
            return 0;
        }

        if ($name === '') {
            $name = implode(', ', $clean);
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_synonym_group` SET name = '" . $this->db->escape($name) . "', created_at = NOW()");
        $group_id = (int)$this->db->getLastId();

        foreach ($clean as $term) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_synonym` SET group_id = '" . $group_id . "', term = '" . $this->db->escape($term) . "'");
        }

        return $group_id;
    }

    public function updateSynonymGroup($group_id, array $terms, $name = '') {
        $group_id = (int)$group_id;
        if ($group_id <= 0) {
            return false;
        }

        $clean = [];
        foreach ($terms as $term) {
            $term = trim((string)$term);
            if ($term !== '') {
                $clean[] = $term;
            }
        }
        $clean = array_values(array_unique($clean));
        if (count($clean) < 2) {
            return false;
        }

        if ($name === '') {
            $name = implode(', ', $clean);
        }

        $exists = $this->db->query("SELECT group_id FROM `" . DB_PREFIX . "asp_synonym_group` WHERE group_id = '" . $group_id . "' LIMIT 1")->row;
        if (!$exists) {
            return false;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "asp_synonym_group` SET name = '" . $this->db->escape($name) . "' WHERE group_id = '" . $group_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id = '" . $group_id . "'");
        foreach ($clean as $term) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_synonym` SET group_id = '" . $group_id . "', term = '" . $this->db->escape($term) . "'");
        }

        return true;
    }

    public function deleteSynonymGroup($group_id) {
        $group_id = (int)$group_id;
        if ($group_id <= 0) {
            return false;
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym` WHERE group_id = '" . $group_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_synonym_group` WHERE group_id = '" . $group_id . "'");

        return true;
    }

    public function syncAttributesFromRaw($raw) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_attribute_map` ");

        $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 4) {
                continue;
            }
            $attribute_id = (int)$parts[0];
            $type = strtolower($parts[1]);
            if (!in_array($type, ['text', 'numeric', 'bool'], true)) {
                $type = 'text';
            }
            $is_filter = (int)(bool)$parts[2];
            $is_search = (int)(bool)$parts[3];

            $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_attribute_map` SET attribute_id = '" . $attribute_id . "', type = '" . $this->db->escape($type) . "', is_filter = '" . $is_filter . "', is_search = '" . $is_search . "', created_at = NOW()");
        }
    }

    public function getAttributeCatalog($filter_name = '', $page = 1, $limit = 20) {
        $language_id = (int)$this->config->get('config_language_id');
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $start = ($page - 1) * $limit;
        $filter_name = trim((string)$filter_name);

        $sql = "SELECT a.attribute_id, ad.name, agd.name AS group_name
                FROM `" . DB_PREFIX . "attribute` a
                INNER JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.attribute_id = ad.attribute_id AND ad.language_id = '" . $language_id . "')
                LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd ON (a.attribute_group_id = agd.attribute_group_id AND agd.language_id = '" . $language_id . "')";

        if ($filter_name !== '') {
            $sql .= " WHERE ad.name LIKE '%" . $this->db->escape($filter_name) . "%'";
        }

        $sql .= " ORDER BY ad.name ASC LIMIT " . (int)$start . "," . (int)$limit;

        return $this->db->query($sql)->rows;
    }

    public function getAttributeCatalogTotal($filter_name = '') {
        $language_id = (int)$this->config->get('config_language_id');
        $filter_name = trim((string)$filter_name);

        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "attribute` a
                INNER JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.attribute_id = ad.attribute_id AND ad.language_id = '" . $language_id . "')";

        if ($filter_name !== '') {
            $sql .= " WHERE ad.name LIKE '%" . $this->db->escape($filter_name) . "%'";
        }

        $row = $this->db->query($sql)->row;

        return (int)($row['total'] ?? 0);
    }

    public function getAttributeNamesByIds(array $attribute_ids) {
        $attribute_ids = array_values(array_unique(array_map('intval', $attribute_ids)));
        if (!$attribute_ids) {
            return [];
        }

        $language_id = (int)$this->config->get('config_language_id');
        $result = $this->db->query(
            "SELECT a.attribute_id, ad.name, agd.name AS group_name
             FROM `" . DB_PREFIX . "attribute` a
             INNER JOIN `" . DB_PREFIX . "attribute_description` ad
                ON (a.attribute_id = ad.attribute_id AND ad.language_id = '" . $language_id . "')
             LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd
                ON (a.attribute_group_id = agd.attribute_group_id AND agd.language_id = '" . $language_id . "')
             WHERE a.attribute_id IN (" . implode(',', $attribute_ids) . ")"
        );

        $map = [];
        foreach ($result->rows as $row) {
            $map[(int)$row['attribute_id']] = [
                'name'       => (string)$row['name'],
                'group_name' => (string)$row['group_name'],
            ];
        }

        return $map;
    }

    /** Product autocomplete for the admin popular-products picker. */
    public function getProductCatalog($filter_name = '', $limit = 50) {
        $language_id = (int)$this->config->get('config_language_id');
        $limit = max(1, (int)$limit);
        $filter_name = trim((string)$filter_name);

        $sql = "SELECT p.product_id, pd.name
                FROM `" . DB_PREFIX . "product` p
                INNER JOIN `" . DB_PREFIX . "product_description` pd
                   ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "')
                WHERE p.status = '1'";
        if ($filter_name !== '') {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($filter_name) . "%'";
        }
        $sql .= " ORDER BY pd.name ASC LIMIT " . (int)$limit;

        return $this->db->query($sql)->rows;
    }

    /** Manufacturer/brand autocomplete for the admin popular-brands picker. */
    public function getManufacturerCatalog($filter_name = '', $limit = 50) {
        $limit = max(1, (int)$limit);
        $filter_name = trim((string)$filter_name);

        $sql = "SELECT manufacturer_id, name FROM `" . DB_PREFIX . "manufacturer`";
        if ($filter_name !== '') {
            $sql .= " WHERE name LIKE '%" . $this->db->escape($filter_name) . "%'";
        }
        $sql .= " ORDER BY name ASC LIMIT " . (int)$limit;

        return $this->db->query($sql)->rows;
    }

    /** Resolve product ids → [{id, name}] preserving the given order (for saved chips). */
    public function getProductNamesByIds(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return []; }

        $language_id = (int)$this->config->get('config_language_id');
        $rows = $this->db->query(
            "SELECT p.product_id, pd.name
             FROM `" . DB_PREFIX . "product` p
             INNER JOIN `" . DB_PREFIX . "product_description` pd
                ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "')
             WHERE p.product_id IN (" . implode(',', $ids) . ")"
        )->rows;

        $map = [];
        foreach ($rows as $r) { $map[(int)$r['product_id']] = (string)$r['name']; }

        $out = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) { $out[] = ['id' => $id, 'name' => $map[$id]]; }
        }
        return $out;
    }

    /** Resolve manufacturer ids → [{id, name}] preserving the given order (for saved chips). */
    public function getManufacturerNamesByIds(array $ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return []; }

        $rows = $this->db->query(
            "SELECT manufacturer_id, name FROM `" . DB_PREFIX . "manufacturer`
             WHERE manufacturer_id IN (" . implode(',', $ids) . ")"
        )->rows;

        $map = [];
        foreach ($rows as $r) { $map[(int)$r['manufacturer_id']] = (string)$r['name']; }

        $out = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) { $out[] = ['id' => $id, 'name' => $map[$id]]; }
        }
        return $out;
    }

    public function logCron($type, $status, $message = '') {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_cron_log` SET `type` = '" . $this->db->escape($type) . "', `status` = '" . $this->db->escape($status) . "', `message` = '" . $this->db->escape($message) . "', `created_at` = NOW()");
    }

    /**
     * Returns dictionary entry counts per language for the Dictionary tab.
     */
    public function getDictionaryCounts(): array {
        $res = $this->db->query(
            "SELECT `language`, COUNT(*) AS cnt FROM `" . DB_PREFIX . "asp_dictionary` GROUP BY `language`"
        );
        $out = [];
        foreach ($res->rows as $row) {
            $out[(string)$row['language']] = (int)$row['cnt'];
        }
        return $out;
    }
}
