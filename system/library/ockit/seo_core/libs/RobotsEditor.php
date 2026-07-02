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
 * Reads, validates, and saves the site robots.txt.
 *
 * Backups are stored as timestamped copies in the same directory
 * (robots.txt.bak.<timestamp>) to allow rollback.
 */
class RobotsEditor
{
    private string $webRoot;

    private $config;
    public function __construct($config)
    {
        $this->config = $config;
        // DIR_APPLICATION = /path/to/site/admin/ → strip to get webroot
        $this->webRoot = rtrim(str_replace('admin/', '', DIR_APPLICATION), '/');
    }

    public function getPath(): string
    {
        return $this->webRoot . '/robots.txt';
    }

    public function read(): string
    {
        $path = $this->getPath();
        if (!file_exists($path)) return '';
        return (string)file_get_contents($path);
    }

    /**
     * Save new content after creating a backup of the current file.
     * Returns true on success.
     */
    public function save(string $content): bool
    {
        $path = $this->getPath();

        // Backup current version first
        if (file_exists($path)) {
            $this->createBackup($path);
        }

        return (bool)file_put_contents($path, $content);
    }

    /**
     * Basic syntax validation. Returns array of error strings (empty = valid).
     */
    public function validate(string $content): array
    {
        $errors     = [];
        $lines      = explode("\n", str_replace("\r\n", "\n", $content));
        $lineNum    = 0;
        $hasAgent   = false;

        foreach ($lines as $raw) {
            $lineNum++;
            $line = rtrim($raw);
            if ($line === '' || strncmp($line, '#', 1) === 0) continue;

            if (!preg_match('/^([A-Za-z\-]+)\s*:\s*(.*)$/', $line, $m)) {
                $errors[] = "Line {$lineNum}: unrecognised directive — «{$line}»";
                continue;
            }

            $directive = strtolower($m[1]);
            $value     = trim($m[2]);

            if ($directive === 'user-agent') {
                $hasAgent = true;
                if ($value === '') {
                    $errors[] = "Line {$lineNum}: empty User-agent value";
                }
            }

            if (in_array($directive, ['allow', 'disallow'], true) && !$hasAgent) {
                $errors[] = "Line {$lineNum}: {$directive} before any User-agent";
            }

            if ($directive === 'sitemap' && $value && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[] = "Line {$lineNum}: Sitemap value is not a valid URL — «{$value}»";
            }
        }

        return $errors;
    }

    /**
     * Ensure a Sitemap: directive pointing to the OC sitemap URL exists.
     * Appends it if not present.
     */
    public function autoInjectSitemap(string $sitemapUrl): bool
    {
        $content = $this->read();
        if (strpos($content, $sitemapUrl) !== false) return true;

        $line = "\nSitemap: " . $sitemapUrl . "\n";
        return $this->save(rtrim($content) . $line);
    }

    /**
     * List all backup files sorted newest-first.
     */
    public function getBackups(): array
    {
        $dir     = dirname($this->getPath());
        $pattern = $dir . '/robots.txt.bak.*';
        $files   = glob($pattern) ?: [];

        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(static fn($f) => [
            'path'     => $f,
            'filename' => basename($f),
            'date'     => date('Y-m-d H:i:s', (int)filemtime($f)),
            'size'     => filesize($f),
        ], $files);
    }

    /**
     * Restore a specific backup file as current robots.txt.
     */
    public function restore(string $backupPath): bool
    {
        if (!file_exists($backupPath)) return false;
        $content = (string)file_get_contents($backupPath);
        return $this->save($content);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function createBackup(string $path): void
    {
        $backupPath = $path . '.bak.' . time();
        copy($path, $backupPath);
    }
}
