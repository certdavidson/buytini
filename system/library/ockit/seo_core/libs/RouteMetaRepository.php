<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

/**
 * Per-route meta tags (title / description / keywords / h1) keyed by
 * (store_id, route, language_id). Used for routes that don't have an
 * entity-based meta engine path: manufacturer list page, blog index,
 * any third-party module page, etc.
 *
 * Route may include `*` wildcards (fnmatch syntax): `vendor/*` matches
 * `vendor/vendor`, `vendor/vendor/view`, etc. Wildcards are evaluated
 * with last-write-wins per (store, language) tuple.
 *
 * Resolution order in catalog (in SeoCore::injectHeadTags):
 *   entity meta (per-product/category/etc.) → route meta → store defaults
 *
 * Schema is created on first read via ensureSchema().
 */
class RouteMetaRepository
{
    private const TABLE = 'kit_seo_route_meta';

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ensureSchema(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                `route_meta_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id`      INT(11) NOT NULL DEFAULT 0,
                `route`         VARCHAR(128) NOT NULL,
                `language_id`   INT(11) NOT NULL,
                `title`         VARCHAR(255) NOT NULL DEFAULT '',
                `description`   VARCHAR(512) NOT NULL DEFAULT '',
                `keywords`      VARCHAR(255) NOT NULL DEFAULT '',
                `h1`            VARCHAR(255) NOT NULL DEFAULT '',
                `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`route_meta_id`),
                UNIQUE KEY `uq_route_lang` (`store_id`,`route`,`language_id`),
                KEY `idx_route` (`route`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Resolve meta for a specific (route, store, language). Tries:
     *   1. exact match store+route+lang
     *   2. exact match store=0+route+lang
     *   3. wildcard route patterns (fnmatch) for current/global store
     *
     * @return array{title:string,description:string,keywords:string,h1:string}|null
     */
    public function getForRoute(string $route, int $storeId, int $languageId): ?array
    {
        $this->ensureSchema();

        // 1+2: exact lookup with store fallback
        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "`
             WHERE `route` = '" . $this->db->escape($route) . "'
               AND `language_id` = " . (int)$languageId . "
               AND (`store_id` = " . (int)$storeId . " OR `store_id` = 0)
             ORDER BY `store_id` DESC LIMIT 1"
        )->rows;
        if ($rows) {
            return $this->normaliseRow($rows[0]);
        }

        // 3: wildcard patterns. Pull all rows for this language, fnmatch in PHP.
        $candidates = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "`
             WHERE `language_id` = " . (int)$languageId . "
               AND (`store_id` = " . (int)$storeId . " OR `store_id` = 0)
               AND `route` LIKE '%*%'
             ORDER BY LENGTH(`route`) DESC, `store_id` DESC"
        )->rows;
        foreach ($candidates as $r) {
            if (fnmatch((string)$r['route'], $route)) {
                return $this->normaliseRow($r);
            }
        }
        return null;
    }

    /** Save or update a row. */
    public function save(int $storeId, string $route, int $languageId, array $values): void
    {
        $this->ensureSchema();
        $sql = "INSERT INTO `" . DB_PREFIX . self::TABLE . "`
                 (`store_id`,`route`,`language_id`,`title`,`description`,`keywords`,`h1`)
                 VALUES (
                    " . (int)$storeId . ",
                    '" . $this->db->escape($route) . "',
                    " . (int)$languageId . ",
                    '" . $this->db->escape((string)($values['title']       ?? '')) . "',
                    '" . $this->db->escape((string)($values['description'] ?? '')) . "',
                    '" . $this->db->escape((string)($values['keywords']    ?? '')) . "',
                    '" . $this->db->escape((string)($values['h1']          ?? '')) . "'
                 )
                 ON DUPLICATE KEY UPDATE
                    `title`       = VALUES(`title`),
                    `description` = VALUES(`description`),
                    `keywords`    = VALUES(`keywords`),
                    `h1`          = VALUES(`h1`)";
        $this->db->query($sql);
    }

    public function delete(int $id): void
    {
        $this->ensureSchema();
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . self::TABLE . "` WHERE `route_meta_id` = " . (int)$id
        );
    }

    /** @return array[] */
    public function listAll(int $storeId = -1, int $limit = 500): array
    {
        $this->ensureSchema();
        $where = $storeId < 0 ? '' : " WHERE `store_id` = " . (int)$storeId;
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "` {$where}
             ORDER BY `route` ASC, `language_id` ASC LIMIT " . (int)$limit
        )->rows;
    }

    private function normaliseRow(array $r): array
    {
        return [
            'title'       => (string)$r['title'],
            'description' => (string)$r['description'],
            'keywords'    => (string)$r['keywords'],
            'h1'          => (string)$r['h1'],
        ];
    }
}
