<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

/**
 * Search/autocomplete for products, categories, and blog articles.
 * Used by AJAX endpoints when adding items to blocks.
 */
class AutocompleteService
{
    private $db;
    private int $languageId;

    public function __construct($db, int $languageId)
    {
        $this->db         = $db;
        $this->languageId = $languageId;
    }

    /**
     * Escapes a LIKE search term: handles SQL injection (db->escape) AND
     * neutralises LIKE-specific wildcards (% and _) so user input
     * "100%" doesn't match every row.
     */
    private function escapeLike(string $query): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->db->escape($query));
    }

    /**
     * Search products by name or model.
     *
     * @return array [{product_id, name, model, image}]
     */
    public function searchProducts(string $query, int $limit = 20): array
    {
        $query = $this->escapeLike($query);

        return $this->db->query(
            "SELECT p.`product_id`, pd.`name`, p.`model`, p.`image`
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
                    ON pd.`product_id` = p.`product_id`
                   AND pd.`language_id` = '" . (int)$this->languageId . "'
             WHERE pd.`name` LIKE '%{$query}%'
                OR p.`model` LIKE '%{$query}%'
             ORDER BY pd.`name` ASC
             LIMIT " . (int)$limit
        )->rows;
    }

    /**
     * Search categories by name.
     *
     * @return array [{category_id, name, image}]
     */
    public function searchCategories(string $query, int $limit = 20): array
    {
        $query = $this->escapeLike($query);

        return $this->db->query(
            "SELECT c.`category_id`,
                    GROUP_CONCAT(cd1.`name` ORDER BY cp.`level` SEPARATOR ' › ') AS `name`,
                    c.`image`
             FROM `" . DB_PREFIX . "category_path` cp
             LEFT JOIN `" . DB_PREFIX . "category` c
                    ON c.`category_id` = cp.`category_id`
             LEFT JOIN `" . DB_PREFIX . "category_description` cd1
                    ON cd1.`category_id` = cp.`path_id`
                   AND cd1.`language_id` = '" . (int)$this->languageId . "'
             LEFT JOIN `" . DB_PREFIX . "category_description` cd2
                    ON cd2.`category_id` = cp.`category_id`
                   AND cd2.`language_id` = '" . (int)$this->languageId . "'
             WHERE cd2.`name` LIKE '%{$query}%'
             GROUP BY cp.`category_id`
             ORDER BY cd2.`name` ASC
             LIMIT " . (int)$limit
        )->rows;
    }

    /**
     * Search blog articles by name.
     * Supports both default blog and octemplates blog.
     *
     * @param string $blogType 'default' or 'octemplates'
     * @return array [{article_id, name, image}]
     */
    public function searchArticles(string $query, string $blogType = 'default', int $limit = 20): array
    {
        $query = $this->escapeLike($query);

        if ($blogType === 'octemplates') {
            // octemplates may store the localised title either on the base table
            // (`oct_blogarticle.title`) or in a separate description table — try the
            // description-join variant first and fall back to the flat schema.
            $hasDescTable = (bool)$this->db->query(
                "SHOW TABLES LIKE '" . DB_PREFIX . "oct_blogarticle_description'"
            )->num_rows;

            if ($hasDescTable) {
                $rows = $this->db->query(
                    "SELECT a.`blogarticle_id` AS `article_id`,
                            COALESCE(ad.`title`, a.`title`) AS `name`,
                            a.`image`
                     FROM `" . DB_PREFIX . "oct_blogarticle` a
                     LEFT JOIN `" . DB_PREFIX . "oct_blogarticle_description` ad
                            ON ad.`blogarticle_id` = a.`blogarticle_id`
                           AND ad.`language_id` = '" . (int)$this->languageId . "'
                     WHERE COALESCE(ad.`title`, a.`title`) LIKE '%{$query}%'
                     ORDER BY name ASC
                     LIMIT " . (int)$limit
                )->rows;
            } else {
                $rows = $this->db->query(
                    "SELECT a.`blogarticle_id` AS `article_id`, a.`title` AS `name`, a.`image`
                     FROM `" . DB_PREFIX . "oct_blogarticle` a
                     WHERE a.`title` LIKE '%{$query}%'
                     ORDER BY a.`title` ASC
                     LIMIT " . (int)$limit
                )->rows;
            }
        } else {
            $rows = $this->db->query(
                "SELECT a.`article_id`, ad.`name`, a.`image`
                 FROM `" . DB_PREFIX . "article` a
                 LEFT JOIN `" . DB_PREFIX . "article_description` ad
                        ON ad.`article_id` = a.`article_id`
                       AND ad.`language_id` = '" . (int)$this->languageId . "'
                 WHERE ad.`name` LIKE '%{$query}%'
                 ORDER BY ad.`name` ASC
                 LIMIT " . (int)$limit
            )->rows;
        }

        return $rows;
    }
}
