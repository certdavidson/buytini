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
 * Per-URL header and meta-robots rules.
 *
 * Rules are stored in oc_kit_seo_header_rules.
 * Each rule matches a URI pattern (fnmatch-style) for a given store
 * and can apply: X-Robots-Tag HTTP header and/or <meta name="robots">.
 */
class HeaderRuleEngine
{
    /** @var array<int, array>|null loaded rules keyed by store_id */
    private ?array $rules = null;

    private $db;
    private $config;
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Lazy migration: add header_name / header_value columns for generic
     * (non-robots) headers like Cache-Control, X-Frame-Options, CSP.
     */
    public function ensureSchema(): void
    {
        $cols = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "kit_seo_header_rules` LIKE 'header_name'");
        if (!$cols->num_rows) {
            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "kit_seo_header_rules`
                 ADD COLUMN `header_name`  VARCHAR(64)  NOT NULL DEFAULT 'X-Robots-Tag' AFTER `url_pattern`,
                 ADD COLUMN `header_value` VARCHAR(512) NOT NULL DEFAULT '' AFTER `header_name`"
            );
        }
    }

    /**
     * Whitelist of header names that the module is allowed to emit.
     * Security: prevents arbitrary injection via admin UI.
     */
    public function allowedHeaders(): array
    {
        return [
            'X-Robots-Tag',
            'Cache-Control',
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Referrer-Policy',
            'Permissions-Policy',
            'Content-Security-Policy',
            'Content-Security-Policy-Report-Only',
        ];
    }

    /**
     * Headers that PHP cannot effectively emit on every response — they MUST
     * be set at the web-server level (nginx/apache). The admin UI shows a
     * notice that these are not configurable here. See ТЗ §11.4 + §18.1.5.
     */
    public function forbiddenHeaders(): array
    {
        return [
            'Strict-Transport-Security', // HSTS — must be at server level
        ];
    }

    /**
     * Presets for the admin "Add rule" dropdown.
     */
    public function presets(): array
    {
        return [
            // Robots presets (show two checkboxes)
            ['group' => 'robots', 'name' => 'X-Robots-Tag',          'value' => 'noindex',            'apply_header' => 1, 'apply_meta' => 1, 'label' => 'noindex'],
            ['group' => 'robots', 'name' => 'X-Robots-Tag',          'value' => 'noindex, nofollow',  'apply_header' => 1, 'apply_meta' => 1, 'label' => 'noindex, nofollow'],
            ['group' => 'robots', 'name' => 'X-Robots-Tag',          'value' => 'noarchive',          'apply_header' => 1, 'apply_meta' => 0, 'label' => 'noarchive'],
            ['group' => 'robots', 'name' => 'X-Robots-Tag',          'value' => 'nosnippet',          'apply_header' => 1, 'apply_meta' => 1, 'label' => 'nosnippet'],
            // Generic headers (HTTP only, meta checkbox hidden in UI)
            ['group' => 'http',   'name' => 'Cache-Control',           'value' => 'public, max-age=3600',     'apply_header' => 1, 'apply_meta' => 0, 'label' => 'Cache-Control: 1h public'],
            ['group' => 'http',   'name' => 'Cache-Control',           'value' => 'no-cache',                 'apply_header' => 1, 'apply_meta' => 0, 'label' => 'Cache-Control: no-cache'],
            ['group' => 'http',   'name' => 'X-Frame-Options',         'value' => 'SAMEORIGIN',               'apply_header' => 1, 'apply_meta' => 0, 'label' => 'X-Frame-Options: SAMEORIGIN'],
            ['group' => 'http',   'name' => 'X-Frame-Options',         'value' => 'DENY',                     'apply_header' => 1, 'apply_meta' => 0, 'label' => 'X-Frame-Options: DENY'],
            ['group' => 'http',   'name' => 'X-Content-Type-Options',  'value' => 'nosniff',                  'apply_header' => 1, 'apply_meta' => 0, 'label' => 'X-Content-Type-Options: nosniff'],
            ['group' => 'http',   'name' => 'Referrer-Policy',         'value' => 'strict-origin-when-cross-origin', 'apply_header' => 1, 'apply_meta' => 0, 'label' => 'Referrer-Policy: strict'],
            ['group' => 'http',   'name' => 'Permissions-Policy',      'value' => 'geolocation=(), microphone=()',    'apply_header' => 1, 'apply_meta' => 0, 'label' => 'Permissions-Policy'],
            ['group' => 'http',   'name' => 'Content-Security-Policy', 'value' => '',                          'apply_header' => 1, 'apply_meta' => 0, 'label' => 'Content-Security-Policy (custom)'],
        ];
    }

    /**
     * Check URI against all rules for $storeId.
     * Applies HTTP headers and/or document meta directly.
     * Returns the matching rule or null.
     */
    public function checkAndApply(string $uri, string $route, int $storeId, $document = null): ?array
    {
        $this->loadRules($storeId);

        foreach ($this->rules[$storeId] ?? [] as $rule) {
            if (!$rule['status']) continue;

            $pattern = (string)$rule['url_pattern'];
            if (!$this->matchesPattern($uri, $pattern) && !$this->matchesPattern($route, $pattern)) {
                continue;
            }

            $this->applyRobots($rule, $document);
            return $rule;
        }

        return null;
    }

    /**
     * Apply robots directive from a rule.
     * - apply_header=1: send X-Robots-Tag HTTP header
     * - apply_meta=1: set <meta name="robots"> via document
     */
    public function applyRobots(array $rule, $document = null): void
    {
        $name  = (string)($rule['header_name']  ?? 'X-Robots-Tag');
        // Legacy rows used `robots_value`; new rows use `header_value`.
        $value = (string)($rule['header_value'] ?? $rule['robots_value'] ?? '');
        if (!$value) return;

        // Whitelist enforcement — never emit unknown header names.
        if (!in_array($name, $this->allowedHeaders(), true)) return;

        if (!empty($rule['apply_header'])) {
            header($name . ': ' . $value);
        }

        // Meta-robots tag only makes sense for X-Robots-Tag-style content
        if ($name === 'X-Robots-Tag' && !empty($rule['apply_meta'])) {
            DocumentExtra::addMeta(['name' => 'robots', 'content' => $value]);
        }
    }

    /**
     * Get all rules for a store (or all stores if storeId=-1).
     */
    public function getAll(int $storeId = -1): array
    {
        $where = $storeId >= 0 ? "WHERE `store_id` = {$storeId}" : '';
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_header_rules` {$where} ORDER BY `sort_order`, `rule_id`"
        )->rows;
    }

    /**
     * Insert or update a rule.
     */
    public function save(array $data): int
    {
        $this->ensureSchema();

        $ruleId      = (int)($data['rule_id']  ?? 0);
        $storeId     = (int)($data['store_id'] ?? 0);
        $pattern     = $this->db->escape((string)($data['url_pattern'] ?? ''));

        $name        = (string)($data['header_name'] ?? 'X-Robots-Tag');
        if (!in_array($name, $this->allowedHeaders(), true)) $name = 'X-Robots-Tag';
        $nameEsc     = $this->db->escape($name);
        $value       = $this->db->escape((string)($data['header_value'] ?? $data['robots_value'] ?? ''));

        // Keep legacy column populated for backward compat reads
        $robotsLegacy = $this->db->escape((string)($data['robots_value'] ?? ($data['header_value'] ?? '')));

        $applyHeader = (int)!empty($data['apply_header']);
        $applyMeta   = (int)!empty($data['apply_meta']);
        $status      = (int)!empty($data['status']);
        $sortOrder   = (int)($data['sort_order'] ?? 0);
        $comment     = $this->db->escape((string)($data['comment'] ?? ''));

        if ($ruleId) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "kit_seo_header_rules`
                 SET `store_id`={$storeId}, `url_pattern`='{$pattern}',
                     `header_name`='{$nameEsc}', `header_value`='{$value}',
                     `robots_value`='{$robotsLegacy}',
                     `apply_header`={$applyHeader}, `apply_meta`={$applyMeta}, `status`={$status},
                     `sort_order`={$sortOrder}, `comment`='{$comment}'
                 WHERE `rule_id`={$ruleId}"
            );
        } else {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "kit_seo_header_rules`
                 (`store_id`,`url_pattern`,`header_name`,`header_value`,`robots_value`,
                  `apply_header`,`apply_meta`,`status`,`sort_order`,`comment`)
                 VALUES ({$storeId},'{$pattern}','{$nameEsc}','{$value}','{$robotsLegacy}',
                         {$applyHeader},{$applyMeta},{$status},{$sortOrder},'{$comment}')"
            );
            $ruleId = (int)$this->db->getLastId();
        }

        $this->rules = null; // invalidate cache
        return $ruleId;
    }

    public function delete(int $ruleId): void
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_seo_header_rules` WHERE `rule_id` = {$ruleId}");
        $this->rules = null;
    }

    /**
     * Test which rule would match a given URI without applying headers.
     */
    public function test(string $uri, int $storeId): ?array
    {
        $this->loadRules($storeId);

        foreach ($this->rules[$storeId] ?? [] as $rule) {
            if (!$rule['status']) continue;
            if ($this->matchesPattern($uri, (string)$rule['url_pattern'])) {
                return $rule;
            }
        }

        return null;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function loadRules(int $storeId): void
    {
        if (isset($this->rules[$storeId])) return;

        $this->rules[$storeId] = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_header_rules`
             WHERE `store_id` = {$storeId} AND `status` = 1
             ORDER BY `sort_order`, `rule_id`"
        )->rows;
    }

    private function matchesPattern(string $subject, string $pattern): bool
    {
        if (!$pattern) return false;
        // Support exact match and fnmatch wildcards
        return $subject === $pattern || fnmatch($pattern, $subject);
    }
}
