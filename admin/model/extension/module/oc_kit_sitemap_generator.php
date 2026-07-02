<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ModelExtensionModuleOcKitSitemapGenerator extends Model
{
    private ?\OcKit\SitemapGenerator\SitemapGenerator $lib = null;

    private function getLib(): \OcKit\SitemapGenerator\SitemapGenerator
    {
        if ($this->lib === null) {
            require_once DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
            $this->lib = new \OcKit\SitemapGenerator\SitemapGenerator($this->registry);
        }
        return $this->lib;
    }

    public function install(): void
    {
        $this->getLib()->install();
    }

    public function uninstall(): void
    {
        $this->getLib()->uninstall();
    }

    // ── Maps ──────────────────────────────────────────────────────────────────

    public function getMaps(): array
    {
        return $this->getLib()->getMapsArray();
    }

    public function getMap(int $mapId): ?array
    {
        return $this->getLib()->getMap($mapId);
    }

    public function saveMap(array $data): int
    {
        return $this->getLib()->saveMap($data);
    }

    public function deleteMap(int $mapId): void
    {
        $this->getLib()->deleteMap($mapId);
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public function getLogs(array $filter = []): array
    {
        return $this->getLib()->getLogs($filter);
    }

    public function clearLogs(?int $mapId = null): void
    {
        $this->getLib()->clearLogs($mapId);
    }

    // ── Files ─────────────────────────────────────────────────────────────────

    public function listGeneratedFiles(): array
    {
        return $this->getLib()->listGeneratedFiles();
    }

    public function deleteGeneratedFiles(): void
    {
        $this->getLib()->deleteGeneratedFiles();
    }

    public function checkOutputDir(string $dir): bool
    {
        return is_dir($dir) && is_writable($dir);
    }

    // ── Generation ────────────────────────────────────────────────────────────

    public function generate(?int $mapId = null, bool $dryRun = false, string $triggeredBy = 'manual'): array
    {
        return $this->getLib()->generate($mapId, $dryRun, $triggeredBy);
    }

    public function generateResizes(): array
    {
        return $this->getLib()->generateResizes();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getLanguages(): array
    {
        return $this->db->query(
            "SELECT language_id, name, code FROM `" . DB_PREFIX . "language` WHERE status = '1' ORDER BY sort_order, name"
        )->rows;
    }

    public function hasBlogModule(): bool
    {
        $result = $this->db->query(
            "SHOW TABLES LIKE '" . DB_PREFIX . "blog_post'"
        );
        return !empty($result->rows);
    }

    public function getRobotsHint(): string
    {
        return $this->getLib()->getRobotsHint();
    }

    public function getOutputDirStatus(): array
    {
        $dir      = $this->getLib()->getOutputDir();
        $writable = is_dir($dir) && is_writable($dir);

        // Strip absolute site root — show only relative part for display
        $siteRoot   = rtrim(dirname(DIR_APPLICATION), '/') . '/';
        $displayDir = (strpos($dir, $siteRoot) === 0)
            ? ltrim(substr($dir, strlen($siteRoot)), '/')
            : $dir;
        $displayDir = rtrim($displayDir, '/') ?: '/';

        return ['dir' => $displayDir, 'writable' => $writable, 'abs' => rtrim($dir, '/')];
    }

    public function getCronKey(): string
    {
        $result = $this->db->query(
            "SELECT value FROM `" . DB_PREFIX . "setting`
             WHERE code = 'module_oc_kit_sitemap_generator'
               AND `key` = 'module_oc_kit_sitemap_generator_cron_key'
               AND store_id = 0 LIMIT 1"
        );
        return !empty($result->row['value']) ? $result->row['value'] : '';
    }

    public function regenerateCronKey(): string
    {
        $key = bin2hex(random_bytes(16));
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "setting`
             WHERE code = 'module_oc_kit_sitemap_generator'
               AND `key` = 'module_oc_kit_sitemap_generator_cron_key'
               AND store_id = 0"
        );
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "setting` (store_id, code, `key`, value, serialized)
             VALUES (0, 'module_oc_kit_sitemap_generator',
                     'module_oc_kit_sitemap_generator_cron_key',
                     '" . $this->db->escape($key) . "', 0)"
        );
        return $key;
    }
}
