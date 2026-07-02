<?php
/**
 * Advanced Search Pro — Dictionary Service
 *
 * Provides custom morphology overrides loaded from the database table
 * `oc_asp_dictionary`. When a word is found here it takes priority over
 * the algorithmic Stemmer.
 *
 * Table structure (created in AdvancedSearchPro::install()):
 *   id       INT AUTO_INCREMENT PRIMARY KEY
 *   word     VARCHAR(120)  — lowercase inflected form
 *   stem     VARCHAR(120)  — lowercase base/stem form
 *   language VARCHAR(5)    — 'uk'|'ru'|'en'|'' (empty = all)
 *
 * Cache: in-memory static array, refreshed every CACHE_TTL seconds.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

class DictionaryService {

    private $db;

    private const CACHE_TTL = 300; // 5 minutes

    /** @var array|null  [word => stem, ...] — merged across all languages */
    private static ?array $cache     = null;
    private static int    $cacheTime = 0;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Look up a word in the custom dictionary.
     * Returns the stem string if found, null otherwise.
     */
    public function lookup(string $word): ?string {
        $word = mb_strtolower(trim($word), 'UTF-8');
        if ($word === '') {
            return null;
        }
        $dict = $this->getDict();
        return $dict[$word] ?? null;
    }

    /**
     * Import entries from a parsed array.
     * Each entry: ['word' => string, 'stem' => string, 'language' => string]
     * Returns number of rows inserted/replaced.
     */
    public function import(array $entries): int {
        $count = 0;
        foreach ($entries as $entry) {
            $word = mb_strtolower(trim((string)($entry['word'] ?? '')), 'UTF-8');
            $stem = mb_strtolower(trim((string)($entry['stem'] ?? '')), 'UTF-8');
            $lang = mb_strtolower(trim((string)($entry['language'] ?? $entry['lang'] ?? '')), 'UTF-8');

            if ($word === '' || $stem === '') {
                continue;
            }

            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "asp_dictionary` (`word`, `stem`, `language`)
                 VALUES ('" . $this->db->escape($word) . "',
                         '" . $this->db->escape($stem) . "',
                         '" . $this->db->escape($lang) . "')
                 ON DUPLICATE KEY UPDATE `stem` = VALUES(`stem`), `language` = VALUES(`language`)"
            );
            $count++;
        }

        // Invalidate cache
        self::$cache = null;

        return $count;
    }

    /**
     * Delete all entries for a given language (or all entries if '' passed).
     */
    public function deleteByLanguage(string $lang): void {
        if ($lang === '') {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_dictionary`");
        } else {
            $this->db->query(
                "DELETE FROM `" . DB_PREFIX . "asp_dictionary`
                 WHERE `language` = '" . $this->db->escape($lang) . "'"
            );
        }
        self::$cache = null;
    }

    /**
     * Returns entry counts grouped by language.
     * ['uk' => 1234, 'ru' => 567, 'en' => 890, '' => 12]
     */
    public function getCounts(): array {
        $res = $this->db->query(
            "SELECT `language`, COUNT(*) AS cnt FROM `" . DB_PREFIX . "asp_dictionary` GROUP BY `language`"
        );
        $out = [];
        foreach ($res->rows as $row) {
            $out[(string)$row['language']] = (int)$row['cnt'];
        }
        return $out;
    }

    /**
     * Returns a preview of dictionary entries with optional language filter.
     */
    public function getEntries(string $lang = '', int $limit = 100, int $offset = 0): array {
        $where = $lang !== ''
            ? "WHERE `language` = '" . $this->db->escape($lang) . "'"
            : '';

        $res = $this->db->query(
            "SELECT `id`, `word`, `stem`, `language`
             FROM `" . DB_PREFIX . "asp_dictionary`
             {$where}
             ORDER BY `word` ASC
             LIMIT {$offset},{$limit}"
        );

        return $res->rows ?? [];
    }

    /**
     * Total entry count (optionally filtered by language).
     */
    public function getTotal(string $lang = ''): int {
        $where = $lang !== ''
            ? "WHERE `language` = '" . $this->db->escape($lang) . "'"
            : '';

        $res = $this->db->query(
            "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "asp_dictionary` {$where}"
        );
        return (int)($res->row['total'] ?? 0);
    }

    // ── Internal ──────────────────────────────────────────────────────────

    private function getDict(): array {
        $now = time();
        if (self::$cache !== null && ($now - self::$cacheTime) < self::CACHE_TTL) {
            return self::$cache;
        }

        $res = $this->db->query(
            "SELECT `word`, `stem` FROM `" . DB_PREFIX . "asp_dictionary` LIMIT 100000"
        );

        $dict = [];
        foreach ($res->rows as $row) {
            $dict[(string)$row['word']] = (string)$row['stem'];
        }

        self::$cache     = $dict;
        self::$cacheTime = $now;

        return $dict;
    }
}
