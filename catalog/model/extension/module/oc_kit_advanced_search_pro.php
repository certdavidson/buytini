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
    public function logCron($type, $status, $message = '') {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_cron_log` SET `type` = '" . $this->db->escape($type) . "', `status` = '" . $this->db->escape($status) . "', `message` = '" . $this->db->escape($message) . "', `created_at` = NOW()");
    }

    public function processQueue($limit = 500) {
        $limit = max(1, (int)$limit);

        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        $settings = $asp->getSettings([
            'mode' => 'native',
            'host' => '127.0.0.1',
            'port' => '9306',
            'index' => 'products',
            'login' => '',
            'password' => '',
            'sphinx_host' => '127.0.0.1',
            'sphinx_port' => '9306',
            'sphinx_index' => 'products',
            'sphinx_login' => '',
            'sphinx_password' => '',
            'vector_enabled' => 0,
            'search_fields' => [],
            'queue_max_attempts' => 5,
            'queue_retry_after_sec' => 120,
            'queue_recover_stuck_sec' => 900,
            'morphology' => 'lemmatize_ru_all, lemmatize_en_all',
            'morphology_uk_wordforms' => '/var/lib/manticore/wordforms/uk.txt',
        ]);

        $maxAttempts = max(1, (int)$settings['queue_max_attempts']);
        $retryAfter = max(10, (int)$settings['queue_retry_after_sec']);
        $recoverStuck = max(60, (int)$settings['queue_recover_stuck_sec']);

        // Recover items stuck in processing state.
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "asp_index_queue`
             SET status = 'pending', updated_at = NOW()
             WHERE status = 'processing'
               AND updated_at <= DATE_SUB(NOW(), INTERVAL " . (int)$recoverStuck . " SECOND)"
        );

        // Retry failed entries if cooldown has passed and attempts budget remains.
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "asp_index_queue`
             SET status = 'pending', updated_at = NOW()
             WHERE status = 'error'
               AND attempts < '" . (int)$maxAttempts . "'
               AND updated_at <= DATE_SUB(NOW(), INTERVAL " . (int)$retryAfter . " SECOND)"
        );

        $query = $this->db->query(
            "SELECT *
             FROM `" . DB_PREFIX . "asp_index_queue`
             WHERE status = 'pending'
             ORDER BY id ASC
             LIMIT " . (int)$limit
        );
        if (!$query->rows) {
            return 0;
        }

        if ($settings['mode'] !== 'native') {
            $asp->ensureSearchIndex($settings);
        }

        $processed = 0;
        foreach ($query->rows as $row) {
            $id = (int)$row['id'];
            $entity_type = $row['entity_type'];
            $entity_id = (int)$row['entity_id'];
            $action = $row['action'];

            $this->db->query("UPDATE `" . DB_PREFIX . "asp_index_queue` SET status = 'processing', updated_at = NOW() WHERE id = '" . $id . "'");

            try {
                if ($entity_type === 'product') {
                    if ($action === 'delete') {
                        $asp->deleteProduct($entity_id, $settings);
                    } else {
                        $asp->indexProduct($entity_id, $settings);
                        if (!empty($settings['vector_enabled'])) {
                            $asp->queueEmbedding($entity_id);
                        }
                    }
                }

                $this->db->query("UPDATE `" . DB_PREFIX . "asp_index_queue` SET status = 'done', updated_at = NOW() WHERE id = '" . $id . "'");
                $processed++;
            } catch (Exception $e) {
                $attempts = (int)$row['attempts'] + 1;
                $nextStatus = $attempts >= $maxAttempts ? 'failed' : 'error';
                $this->db->query(
                    "UPDATE `" . DB_PREFIX . "asp_index_queue`
                     SET status = '" . $this->db->escape($nextStatus) . "',
                         attempts = '" . (int)$attempts . "',
                         updated_at = NOW()
                     WHERE id = '" . $id . "'"
                );
            }
        }

        if ($processed > 0) {
            $asp->setMeta('last_indexed_at', date('Y-m-d H:i:s'));
            $asp->setMeta('index_docs', (string)$asp->getIndexDocumentsCount($settings));
        }

        return $processed;
    }

    public function reindexAll($limit = 500, $offset = 0) {
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        $settings = $asp->getSettings([
            'mode' => 'native',
            'host' => '127.0.0.1',
            'port' => '9306',
            'index' => 'products',
            'login' => '',
            'password' => '',
            'sphinx_host' => '127.0.0.1',
            'sphinx_port' => '9306',
            'sphinx_index' => 'products',
            'sphinx_login' => '',
            'sphinx_password' => '',
            'vector_enabled' => 0,
            'search_fields' => [],
            'morphology' => 'lemmatize_ru_all, lemmatize_en_all',
            'morphology_uk_wordforms' => '/var/lib/manticore/wordforms/uk.txt',
        ]);

        if ($settings['mode'] === 'native') {
            $offset = (int)$offset;
            $limit  = (int)$limit;

            if ($offset === 0) {
                // Clear native index and rebuild MySQL FULLTEXT on product_description
                $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "asp_native_index`");
                try {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . "product_description` DROP INDEX `asp_pd_ft`");
                } catch (\Exception $e) {}
                try {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . "product_description` ADD FULLTEXT `asp_pd_ft` (`name`, `description`, `tag`)");
                } catch (\Exception $e) {}
            }

            $rows = $this->db->query(
                "SELECT product_id FROM `" . DB_PREFIX . "product` ORDER BY product_id ASC LIMIT " . $offset . "," . $limit
            );
            if (!$rows->rows) {
                return 0;
            }

            $ids   = array_map('intval', array_column($rows->rows, 'product_id'));
            $count = $asp->bulkIndexProducts($ids, $settings);

            if ($count > 0) {
                $asp->setMeta('last_indexed_at', date('Y-m-d H:i:s'));
                $asp->setMeta('index_docs', (string)$asp->getIndexDocumentsCount($settings));
            }
            return $count;
        }

        $asp->ensureSearchIndex($settings);

        $limit = (int)$limit;
        $offset = (int)$offset;
        $rows = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` ORDER BY product_id ASC LIMIT " . $offset . "," . $limit);

        if (!$rows->rows) {
            return 0;
        }

        $ids = array_map('intval', array_column($rows->rows, 'product_id'));
        $count = $asp->bulkIndexProducts($ids, $settings);

        if (!empty($settings['vector_enabled'])) {
            foreach ($ids as $id) {
                $asp->queueEmbedding($id);
            }
        }

        if ($count > 0) {
            $asp->setMeta('last_indexed_at', date('Y-m-d H:i:s'));
            $asp->setMeta('index_docs', (string)$asp->getIndexDocumentsCount($settings));
        }
        return $count;
    }

    public function processEmbeddingQueue($limit = 100) {
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        return (int)$asp->processEmbeddingQueue((int)$limit);
    }

    public function queueIndex($entityType, $entityId, $action = 'upsert') {
        $entityType = $this->db->escape($entityType);
        $entityId   = (int)$entityId;
        $action     = $this->db->escape($action);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_index_queue` WHERE entity_type = '" . $entityType . "' AND entity_id = '" . $entityId . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_index_queue` SET entity_type = '" . $entityType . "', entity_id = '" . $entityId . "', action = '" . $action . "', status = 'pending', attempts = 0, created_at = NOW(), updated_at = NOW()");
    }

    public function syncModifiedProducts($limit = 1000, $minutes = 180) {
        $limit = max(1, (int)$limit);
        $minutes = max(1, (int)$minutes);

        $rows = $this->db->query(
            "SELECT p.product_id
             FROM `" . DB_PREFIX . "product` p
             WHERE p.date_modified >= DATE_SUB(NOW(), INTERVAL " . $minutes . " MINUTE)
             ORDER BY p.date_modified DESC
             LIMIT " . $limit
        );

        if (!$rows->rows) {
            return 0;
        }

        $count = 0;
        foreach ($rows->rows as $row) {
            $productId = (int)$row['product_id'];
            $this->queueIndex('product', $productId, 'upsert');
            $count++;
        }

        return $count;
    }

    public function generateQueryRules($limit = 100, $days = 30, $minCount = 2, array $extraSettings = []) {
        require_once(DIR_SYSTEM . 'library/ockit/advanced_search_pro/AdvancedSearchPro.php');
        $asp = new AdvancedSearchPro($this->registry);
        $settings = array_merge($asp->getSettings([
            'ai_provider'         => 'openai',
            'ai_api_key'          => '',
            'ai_model'            => 'gpt-4o-mini',
            'ai_expand_query'     => 1,
            'ai_rewrite_query'    => 1,
            'ai_intent_detection' => 1,
            'ai_budget_monthly'   => 50,
            'ai_budget_daily_limit' => 1000,
            'ai_auto_block'       => 1,
        ]), $extraSettings);

        return $asp->generateQueryRules((int)$limit, (int)$days, (int)$minCount, $settings);
    }
}
