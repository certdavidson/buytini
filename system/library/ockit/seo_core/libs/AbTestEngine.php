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
 * Title A/B test engine. For an entity (product/category/manufacturer/information)
 * + language, you can register two title variants. On each catalog request the
 * engine picks variant A or B and tracks impressions in `kit_seo_ab_tests`.
 *
 * Variant choice is stable per visitor (cookie-based), so refreshing doesn't
 * flip the title — that would inflate impressions and confuse users.
 *
 * Lifecycle:
 *   - `create()`        — register a new active test (one per entity+lang)
 *   - `pickTitle()`     — called from MetaTemplateEngine, returns winning variant or null
 *   - `end()`           — stop test (winner stays in entity meta override manually)
 *   - `getStats()`      — impressions per variant
 *
 * Limitations: tracks impressions only, not conversions. The "winner" is the
 * variant with more impressions over a comparable period, or the user picks
 * based on Search Console CTR after each variant has run.
 */
class AbTestEngine
{
    private const TABLE = 'kit_seo_ab_tests';
    private const COOKIE = 'scf_ab';

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ensureSchema(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                `test_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity_type`     VARCHAR(32) NOT NULL,
                `entity_id`       INT(11) NOT NULL,
                `language_id`     INT(11) NOT NULL,
                `variant_a_title` VARCHAR(255) NOT NULL,
                `variant_b_title` VARCHAR(255) NOT NULL,
                `hits_a`          INT UNSIGNED NOT NULL DEFAULT 0,
                `hits_b`          INT UNSIGNED NOT NULL DEFAULT 0,
                `status`          ENUM('active','ended') NOT NULL DEFAULT 'active',
                `started_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ended_at`        DATETIME NULL,
                PRIMARY KEY (`test_id`),
                KEY `idx_entity` (`entity_type`,`entity_id`,`language_id`,`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function create(string $type, int $entityId, int $languageId, string $titleA, string $titleB): int
    {
        $this->ensureSchema();
        // End any existing active test for this entity+lang
        $this->db->query(
            "UPDATE `" . DB_PREFIX . self::TABLE . "`
             SET `status`='ended', `ended_at`=NOW()
             WHERE `entity_type`='" . $this->db->escape($type) . "'
               AND `entity_id`=" . (int)$entityId . "
               AND `language_id`=" . (int)$languageId . "
               AND `status`='active'"
        );

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . self::TABLE . "`
             (`entity_type`,`entity_id`,`language_id`,`variant_a_title`,`variant_b_title`)
             VALUES (
                '" . $this->db->escape($type) . "',
                " . (int)$entityId . ",
                " . (int)$languageId . ",
                '" . $this->db->escape(mb_substr($titleA, 0, 255, 'UTF-8')) . "',
                '" . $this->db->escape(mb_substr($titleB, 0, 255, 'UTF-8')) . "')"
        );
        return (int)$this->db->getLastId();
    }

    public function end(int $testId): void
    {
        $this->ensureSchema();
        $this->db->query(
            "UPDATE `" . DB_PREFIX . self::TABLE . "`
             SET `status`='ended', `ended_at`=NOW()
             WHERE `test_id`=" . (int)$testId
        );
    }

    public function delete(int $testId): void
    {
        $this->ensureSchema();
        $this->db->query("DELETE FROM `" . DB_PREFIX . self::TABLE . "` WHERE `test_id`=" . (int)$testId);
    }

    /**
     * Pick a title for the current visitor. Returns null if no active test.
     * Stable per-visitor via cookie. Increments hit counter.
     */
    public function pickTitle(string $type, int $entityId, int $languageId): ?string
    {
        $this->ensureSchema();
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "`
             WHERE `entity_type`='" . $this->db->escape($type) . "'
               AND `entity_id`=" . (int)$entityId . "
               AND `language_id`=" . (int)$languageId . "
               AND `status`='active'
             ORDER BY `test_id` DESC LIMIT 1"
        )->row;

        if (!$row) return null;

        $variant = $this->resolveVariant();
        $col     = $variant === 'A' ? 'hits_a' : 'hits_b';
        $title   = $variant === 'A' ? $row['variant_a_title'] : $row['variant_b_title'];

        $this->db->query(
            "UPDATE `" . DB_PREFIX . self::TABLE . "`
             SET `{$col}` = `{$col}` + 1
             WHERE `test_id`=" . (int)$row['test_id']
        );
        return (string)$title;
    }

    public function listTests(int $limit = 200): array
    {
        $this->ensureSchema();
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "`
             ORDER BY `status`='active' DESC, `test_id` DESC
             LIMIT " . (int)$limit
        )->rows;
    }

    public function getStats(int $testId): ?array
    {
        $this->ensureSchema();
        $row = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "` WHERE `test_id`=" . (int)$testId
        )->row;
        return $row ?: null;
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function resolveVariant(): string
    {
        $cookie = $_COOKIE[self::COOKIE] ?? '';
        if ($cookie === 'A' || $cookie === 'B') return $cookie;

        $variant = (mt_rand(0, 1) === 0) ? 'A' : 'B';
        // 30-day cookie, samesite lax — stable assignment per visitor
        if (!headers_sent()) {
            setcookie(self::COOKIE, $variant, [
                'expires'  => time() + 60 * 60 * 24 * 30,
                'path'     => '/',
                'samesite' => 'Lax',
            ]);
        }
        $_COOKIE[self::COOKIE] = $variant;
        return $variant;
    }
}
