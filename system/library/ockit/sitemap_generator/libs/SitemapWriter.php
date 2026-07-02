<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Exceptions\SitemapWriteException;

/**
 * Handles atomic file writes for generated sitemap XML files.
 * Uses temp-file + rename to prevent serving incomplete files.
 */
class SitemapWriter
{
    private string $outputDir;
    private bool   $gzip;

    const MAX_FILE_BYTES = 52428800; // 50 MB

    public function __construct(string $outputDir, bool $gzip = false)
    {
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->gzip      = $gzip;
    }

    public function isWritable(): bool
    {
        return is_dir($this->outputDir) && is_writable($this->outputDir);
    }

    /**
     * Atomically writes XML content to {outputDir}/{filename}.
     * Optionally writes a .gz companion file.
     *
     * @throws SitemapWriteException
     */
    public function write(string $filename, string $xmlContent): void
    {
        if (strlen($xmlContent) > self::MAX_FILE_BYTES) {
            throw new SitemapWriteException(
                "Sitemap file exceeds 50 MB limit: {$filename}"
            );
        }

        $target = $this->outputDir . $filename;
        $tmp    = $target . '.tmp.' . getmypid();

        if (file_put_contents($tmp, $xmlContent) === false) {
            throw new SitemapWriteException("Cannot write temp file: {$tmp}");
        }

        if (!rename($tmp, $target)) {
            @unlink($tmp);
            throw new SitemapWriteException("Cannot rename temp file to: {$target}");
        }

        if ($this->gzip) {
            $this->writeGzip($filename . '.gz', $xmlContent);
        }
    }

    /**
     * Writes gzip-compressed companion file.
     *
     * @throws SitemapWriteException
     */
    public function writeGzip(string $gzFilename, string $xmlContent): void
    {
        $target = $this->outputDir . $gzFilename;
        $tmp    = $target . '.tmp.' . getmypid();

        $gz = gzopen($tmp, 'wb9');
        if (!$gz) {
            throw new SitemapWriteException("Cannot open gzip temp file: {$tmp}");
        }
        gzwrite($gz, $xmlContent);
        gzclose($gz);

        if (!rename($tmp, $target)) {
            @unlink($tmp);
            throw new SitemapWriteException("Cannot rename gzip temp file to: {$target}");
        }
    }

    /**
     * Deletes a specific file (and its .gz companion if present).
     */
    public function delete(string $filename): void
    {
        $path = $this->outputDir . $filename;
        if (file_exists($path)) @unlink($path);
        if (file_exists($path . '.gz')) @unlink($path . '.gz');
    }

    /**
     * Deletes all sitemap files matching a base filename pattern.
     * E.g. pattern "sitemap" deletes sitemap.xml, sitemap-1.xml, sitemap-en-1.xml …
     */
    public function deleteByPattern(string $pattern): void
    {
        foreach (glob($this->outputDir . $pattern . '*.xml') ?: [] as $file) {
            @unlink($file);
            if (file_exists($file . '.gz')) @unlink($file . '.gz');
        }
    }

    /**
     * Lists all XML sitemap files in the output directory.
     *
     * @return array  [['filename', 'path', 'size', 'mtime'], ...]
     */
    public function listFiles(): array
    {
        $files = [];
        foreach (glob($this->outputDir . 'sitemap*.xml') ?: [] as $path) {
            $files[] = [
                'filename' => basename($path),
                'path'     => $path,
                'size'     => filesize($path),
                'mtime'    => filemtime($path),
            ];
        }
        usort($files, fn($a, $b) => strcmp($a['filename'], $b['filename']));
        return $files;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }
}
