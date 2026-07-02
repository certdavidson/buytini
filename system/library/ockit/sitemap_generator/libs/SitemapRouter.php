<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator\Libs;

use OcKit\SitemapGenerator\Dto\SitemapConfig;

/**
 * Handles dynamic (real-time) sitemap generation and serving.
 * Resolves a requested filename to a language map and generates XML on-the-fly
 * with optional file caching.
 */
class SitemapRouter
{
    private array  $config;
    /** @var SitemapConfig[] */
    private array  $maps;
    private string $cacheDir;
    private int    $cacheTtl;

    public function __construct(array $config, array $maps, string $cacheDir)
    {
        $this->config   = $config;
        $this->maps     = $maps;
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->cacheTtl = (int)($config['dynamic_cache_ttl'] ?? 3600);
    }

    /**
     * Resolves a requested filename to a language map + page number.
     * Returns null if no map matches (→ 404).
     *
     * @return array|null  ['map' => SitemapConfig, 'page' => int, 'is_index' => bool]
     */
    public function resolve(string $requestedFile): ?array
    {
        // Strip .xml extension
        $base = preg_replace('/\.xml$/', '', $requestedFile);

        $indexFilename = $this->config['index_filename'] ?? 'sitemap';

        // Is this the index file?
        if ($base === $indexFilename) {
            return ['map' => null, 'page' => 0, 'is_index' => true];
        }

        // Try to match against each map's filename, with optional page suffix
        // Pattern: {filename}-{page} or just {filename}
        foreach ($this->maps as $map) {
            if (!$map->status) continue;

            $pattern = '/^' . preg_quote($map->filename, '/') . '(?:-(\d+))?$/';
            if (preg_match($pattern, $base, $m)) {
                return [
                    'map'      => $map,
                    'page'     => isset($m[1]) ? (int)$m[1] : 0,
                    'is_index' => false,
                ];
            }
        }

        return null;
    }

    /**
     * Outputs XML for the requested file directly to the browser.
     * Uses file cache if available and not expired.
     */
    public function serve(string $requestedFile, CronRunner $runner, SitemapBuilder $builder): void
    {
        $cacheFile = $this->cacheDir . 'sitemap_dynamic_' . md5($requestedFile) . '.xml';

        // Serve from cache if fresh
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTtl) {
            $this->outputXml(file_get_contents($cacheFile));
            return;
        }

        $resolved = $this->resolve($requestedFile);
        if ($resolved === null) {
            http_response_code(404);
            return;
        }

        if ($resolved['is_index']) {
            $xml = $this->generateIndex($builder);
        } else {
            // For dynamic mode, run generation and capture output
            // This is a simplified approach — for large catalogs use static mode
            $result = $runner->run($resolved['map']->mapId, false, 'dynamic');
            // Read the generated file from disk (static output dir)
            $outputDir = $this->config['output_dir'] ?? '';
            $filename  = $requestedFile;
            $filepath  = rtrim($outputDir, '/') . '/' . $filename;
            if (file_exists($filepath)) {
                $xml = file_get_contents($filepath);
            } else {
                http_response_code(404);
                return;
            }
        }

        // Cache the result
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, $xml);

        $this->outputXml($xml);
    }

    private function generateIndex(SitemapBuilder $builder): string
    {
        $entries = [];
        $baseUrl = rtrim($this->config['store_url'] ?? '', '/');

        foreach ($this->maps as $map) {
            if (!$map->status) continue;
            $entries[] = [
                'loc'     => $baseUrl . '/' . $map->filename . '.xml',
                'lastmod' => $map->lastGeneratedAt ? substr($map->lastGeneratedAt, 0, 10) : date('Y-m-d'),
            ];
        }

        return $builder->renderSitemapIndex($entries);
    }

    private function outputXml(string $xml): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        echo $xml;
    }
}
