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
use OcKit\SitemapGenerator\Dto\SitemapEntry;
use OcKit\SitemapGenerator\Libs\SitemapXslTemplate;

/**
 * Orchestrates full sitemap generation for one or all language maps.
 * Used by both the CLI cron script and the HTTP cron endpoint.
 */
class CronRunner
{
    private UrlCollectorFactory $collectorFactory;
    private SitemapBuilder      $builder;
    private SitemapWriter       $writer;
    private HreflangBuilder     $hreflang;
    private DbLogger            $logger;
    private array               $config;   // module settings
    /** @var SitemapConfig[] */
    private array               $maps;
    private string              $baseUrl;
    /** @var array [mapId => [keyword => true]] pre-loaded for hreflang skip logic */
    private array               $keywordIndex = [];

    const CHUNK_SIZE = 1000; // rows per DB iteration

    /** Maps internal type keys to file name slugs used in by_type mode */
    const TYPE_NAMES = [
        'home'          => 'home',
        'category'      => 'categories',
        'product'       => 'products',
        'manufacturer'  => 'manufacturers',
        'information'   => 'information',
        'blog_post'     => 'blog',
        'blog_category' => 'blog-categories',
        'special'       => 'special',
        'contact'       => 'contact',
    ];

    public function __construct(
        UrlCollectorFactory $collectorFactory,
        SitemapBuilder      $builder,
        SitemapWriter       $writer,
        HreflangBuilder     $hreflang,
        DbLogger            $logger,
        array               $config,
        array               $maps,
        string              $baseUrl
    ) {
        $this->collectorFactory = $collectorFactory;
        $this->builder          = $builder;
        $this->writer           = $writer;
        $this->hreflang         = $hreflang;
        $this->logger           = $logger;
        $this->config           = $config;
        $this->maps             = $maps;
        $this->baseUrl          = rtrim($baseUrl, '/');
    }

    /**
     * Main entry point.
     *
     * @param int|null $mapId     Generate only this map; null = all active maps
     * @param bool     $dryRun    Count URLs without writing files
     * @param string   $triggeredBy  'cron' | 'manual' | 'http'
     * @return array   ['urls_total', 'files_total', 'maps', 'errors']
     */
    public function run(?int $mapId = null, bool $dryRun = false, string $triggeredBy = 'cron'): array
    {
        $logId = $this->logger->startRun($mapId, $triggeredBy);

        $urlsTotal  = 0;
        $filesTotal = 0;
        $mapResults = [];
        $errors     = [];

        try {
            $mapsToProcess = $this->getMapsToProcess($mapId);

            // Pre-load keyword index for ALL active maps (not just the ones being generated),
            // so hreflang links can reference all language versions even in single-map runs.
            if ($this->hreflang->isEnabled()) {
                $allActiveMaps = array_filter($this->maps, fn($m) => $m->status);
                $this->buildKeywordIndex($allActiveMaps);
            }

            $byType = (($this->config['grouping_mode'] ?? 'combined') === 'by_type');

            foreach ($mapsToProcess as $map) {
                try {
                    $result = $byType
                        ? $this->generateMapByType($map, $dryRun)
                        : $this->generateMap($map, $dryRun);
                    $urlsTotal  += $result['urls_count'];
                    $filesTotal += $result['files_count'];
                    $mapResults[] = $result;
                } catch (\Throwable $e) {
                    $errors[] = "Map {$map->mapId} ({$map->languageCode}): " . $e->getMessage();
                }
            }

            // Build global sitemap index by scanning disk for all active maps' {filename}.xml.
            // Scanning ensures the index stays consistent when individual maps are regenerated.
            if (!$dryRun) {
                $dir = $this->writer->getOutputDir();
                $globalEntries = [];
                foreach ($this->maps as $m) {
                    if (!$m->status) continue;
                    $path = $dir . $m->filename . '.xml';
                    if (file_exists($path)) {
                        $mtime = @filemtime($path);
                        $globalEntries[] = [
                            'loc'     => $this->baseUrl . '/' . $m->filename . '.xml',
                            'lastmod' => $mtime ? date('Y-m-d', $mtime) : date('Y-m-d'),
                        ];
                    }
                }
                if (count($globalEntries) > 1) {
                    $indexFilename = ($this->config['index_filename'] ?? 'sitemap') . '.xml';
                    $this->writer->write($indexFilename, $this->builder->renderSitemapIndex($globalEntries));
                    $filesTotal++;
                }
            }

            // Write XSL stylesheet for browser-friendly display
            if (!$dryRun && !empty($this->config['enable_xsl'])) {
                $this->writer->write('sitemap.xsl', SitemapXslTemplate::get());
            }

        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
            $this->logger->failRun($logId, implode('; ', $errors));
            return [
                'urls_total'  => $urlsTotal,
                'files_total' => $filesTotal,
                'maps'        => $mapResults,
                'errors'      => $errors,
            ];
        }

        if (empty($errors)) {
            $this->logger->finishRun($logId, $urlsTotal, $filesTotal);
        } else {
            $this->logger->failRun($logId, implode('; ', $errors));
        }

        return [
            'urls_total'  => $urlsTotal,
            'files_total' => $filesTotal,
            'maps'        => $mapResults,
            'errors'      => $errors,
        ];
    }

    /**
     * Generates sitemap files for a single language map.
     */
    private function generateMap(SitemapConfig $map, bool $dryRun): array
    {
        $collector  = $this->collectorFactory->make($map);
        $urlsPerFile = max(1, min(50000, (int)($this->config['urls_per_file'] ?? 10000)));
        $types       = $this->getEnabledTypes();

        $buffer     = [];   // accumulated SitemapEntry[]
        $fileIndex  = 1;
        $urlsCount  = 0;
        $files      = [];   // list of written filenames (relative, without outputDir)

        $flush = function () use ($map, &$buffer, &$fileIndex, &$files, $dryRun) {
            if (empty($buffer)) return;

            $filename = $this->buildFilename($map->filename, $fileIndex);
            $xml      = $this->builder->openUrlset();
            foreach ($buffer as $entry) {
                $xml .= $this->builder->renderEntry($entry);
            }
            $xml .= $this->builder->closeUrlset();

            if (!$dryRun) {
                $this->writer->write($filename, $xml);
            }

            $files[] = $filename;
            $fileIndex++;
            $buffer = [];
        };

        foreach ($types as $type => $cfg) {
            $entries = $this->collectType($type, $cfg, $collector, $map);

            foreach ($entries as $entry) {
                // Attach hreflang links
                if ($this->hreflang->isEnabled()) {
                    $slug = $this->extractSlug($entry->loc, $map);
                    $entry->hreflangLinks = $this->hreflang->buildLinks(
                        $this->baseUrl, $slug, $map->urlPrefix, $this->keywordIndex, $entry->query
                    );
                }

                $buffer[] = $entry;
                $urlsCount++;

                if (count($buffer) >= $urlsPerFile) {
                    $flush();
                }
            }
        }

        // Flush remaining
        $flush();

        $representativeName = $map->filename . '.xml';

        if (count($files) === 1) {
            // Single paginated file → rename to {filename}.xml
            if (!$dryRun) {
                $dir = $this->writer->getOutputDir();
                if (file_exists($dir . $files[0])) {
                    rename($dir . $files[0], $dir . $representativeName);
                }
            }
        } elseif (count($files) > 1) {
            // Multiple paginated files → write {filename}.xml as a per-map sub-index
            if (!$dryRun) {
                $subEntries = array_map(fn($f) => [
                    'loc'     => $this->baseUrl . '/' . $f,
                    'lastmod' => date('Y-m-d'),
                ], $files);
                $this->writer->write($representativeName, $this->builder->renderSitemapIndex($subEntries));
            }
        }

        return [
            'map_id'      => $map->mapId,
            'language'    => $map->languageCode,
            'urls_count'  => $urlsCount,
            'files_count' => count($files),
            'files'       => count($files) > 0 ? [$representativeName] : [],
        ];
    }

    /**
     * Generates per-type sitemap files for a single language map (by_type mode).
     * Naming: {base}-{type}.xml for page 1, {base}-{type}-2.xml for subsequent pages.
     */
    private function generateMapByType(SitemapConfig $map, bool $dryRun): array
    {
        $collector   = $this->collectorFactory->make($map);
        $urlsPerFile = max(1, min(50000, (int)($this->config['urls_per_file'] ?? 10000)));
        $types       = $this->getEnabledTypes();

        $urlsCount = 0;
        $files     = [];

        $representativeFiles = []; // one per type, for global index

        foreach ($types as $type => $cfg) {
            $entries = $this->collectType($type, $cfg, $collector, $map);
            if (empty($entries)) continue;

            $typeName    = self::TYPE_NAMES[$type] ?? $type;
            $typeBase    = $map->filename . '-' . $typeName;
            $pageIndex   = 1;
            $buffer      = [];
            $typeFiles   = []; // all paginated files for this type

            $flushType = function () use ($typeBase, &$buffer, &$pageIndex, &$typeFiles, $dryRun) {
                if (empty($buffer)) return;

                // Always number internally: {base}-{type}-1.xml, {base}-{type}-2.xml ...
                $filename = $typeBase . '-' . $pageIndex . '.xml';

                $xml = $this->builder->openUrlset();
                foreach ($buffer as $entry) {
                    $xml .= $this->builder->renderEntry($entry);
                }
                $xml .= $this->builder->closeUrlset();

                if (!$dryRun) {
                    $this->writer->write($filename, $xml);
                }

                $typeFiles[] = $filename;
                $pageIndex++;
                $buffer = [];
            };

            foreach ($entries as $entry) {
                if ($this->hreflang->isEnabled()) {
                    $slug = $this->extractSlug($entry->loc, $map);
                    $entry->hreflangLinks = $this->hreflang->buildLinks(
                        $this->baseUrl, $slug, $map->urlPrefix, $this->keywordIndex, $entry->query
                    );
                }

                $buffer[] = $entry;
                $urlsCount++;

                if (count($buffer) >= $urlsPerFile) {
                    $flushType();
                }
            }

            $flushType();

            if (empty($typeFiles)) continue;

            $typeRepresentative = $typeBase . '.xml';

            if (count($typeFiles) === 1) {
                // Single file → rename to {base}-{type}.xml
                if (!$dryRun) {
                    $dir = $this->writer->getOutputDir();
                    if (file_exists($dir . $typeFiles[0])) {
                        rename($dir . $typeFiles[0], $dir . $typeRepresentative);
                    }
                }
            } else {
                // Multiple files → write {base}-{type}.xml as sub-index
                if (!$dryRun) {
                    $subEntries = array_map(fn($f) => [
                        'loc'     => $this->baseUrl . '/' . $f,
                        'lastmod' => date('Y-m-d'),
                    ], $typeFiles);
                    $this->writer->write($typeRepresentative, $this->builder->renderSitemapIndex($subEntries));
                }
            }

            $files = array_merge($files, $typeFiles);
            $representativeFiles[] = $typeRepresentative;
        }

        // Write {map.filename}.xml as map-level index pointing to all type representative files.
        // This ensures a stable entry point for the global sitemap index.
        $mapRepresentative = $map->filename . '.xml';
        if (!$dryRun && !empty($representativeFiles)) {
            $mapSubEntries = array_map(fn($f) => [
                'loc'     => $this->baseUrl . '/' . $f,
                'lastmod' => date('Y-m-d'),
            ], $representativeFiles);
            $this->writer->write($mapRepresentative, $this->builder->renderSitemapIndex($mapSubEntries));
        }

        return [
            'map_id'      => $map->mapId,
            'language'    => $map->languageCode,
            'urls_count'  => $urlsCount,
            'files_count' => count($files),
            'files'       => [$mapRepresentative],
        ];
    }

    /**
     * Collects SitemapEntry[] for one content type using chunked DB iteration.
     *
     * @return SitemapEntry[]
     */
    private function collectType(string $type, array $cfg, UrlCollector $collector, SitemapConfig $map): array
    {
        $entries = [];

        switch ($type) {
            case 'home':
                return $collector->getHome($cfg);

            case 'special':
                return $collector->getSpecialPage($cfg);

            case 'contact':
                return $collector->getContactPage($cfg);

            case 'category':
                $total  = $collector->countCategories();
                $offset = 0;
                while ($offset < $total) {
                    $chunk = $collector->getCategories($cfg, $offset, self::CHUNK_SIZE);
                    $entries = array_merge($entries, $chunk);
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;

            case 'product':
                $includeImages = !empty($this->config['include_images']);
                $imageResolver = $this->collectorFactory->getImageResolver();
                $total  = $collector->countProducts();
                $offset = 0;
                while ($offset < $total) {
                    if ($includeImages && $imageResolver !== null) {
                        $rows = $collector->getProductsWithImages($cfg, $offset, self::CHUNK_SIZE);
                        foreach ($rows as $row) {
                            $slug = $collector->getProductSeoUrl((int)$row['product_id']);
                            if ($slug === null) continue;

                            $addRows = !empty($this->config['include_additional_images'])
                                ? $collector->getProductAdditionalImages((int)$row['product_id'])
                                : [];

                            $images  = $imageResolver->resolve((string)($row['image'] ?? ''), $addRows, (int)$row['product_id']);
                            $entry   = new SitemapEntry(
                                $this->baseUrl . '/' . $map->getUrlSegment() . ltrim($slug, '/'),
                                isset($row['date_modified']) ? substr($row['date_modified'], 0, 10) : null,
                                $cfg['changefreq'] ?? 'weekly',
                                (float)($cfg['priority'] ?? 0.8),
                                $images
                            );
                            $entry->query = 'product_id=' . $row['product_id'];
                            $entries[] = $entry;
                        }
                    } else {
                        $chunk   = $collector->getProducts($cfg, $offset, self::CHUNK_SIZE);
                        $entries = array_merge($entries, $chunk);
                    }
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;

            case 'manufacturer':
                $total  = $collector->countManufacturers();
                $offset = 0;
                while ($offset < $total) {
                    $chunk = $collector->getManufacturers($cfg, $offset, self::CHUNK_SIZE);
                    $entries = array_merge($entries, $chunk);
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;

            case 'information':
                $includeBottom = !empty($cfg['include_bottom']);
                $total  = $collector->countInformation($includeBottom);
                $offset = 0;
                while ($offset < $total) {
                    $chunk = $collector->getInformation($cfg, $offset, self::CHUNK_SIZE);
                    $entries = array_merge($entries, $chunk);
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;

            case 'blog_post':
                $total  = $collector->countBlogPosts();
                $offset = 0;
                while ($offset < $total) {
                    $chunk = $collector->getBlogPosts($cfg, $offset, self::CHUNK_SIZE);
                    $entries = array_merge($entries, $chunk);
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;

            case 'blog_category':
                $total  = $collector->countBlogCategories();
                $offset = 0;
                while ($offset < $total) {
                    $chunk = $collector->getBlogCategories($cfg, $offset, self::CHUNK_SIZE);
                    $entries = array_merge($entries, $chunk);
                    $offset += self::CHUNK_SIZE;
                    if (!empty($cfg['max_urls']) && count($entries) >= (int)$cfg['max_urls']) break;
                }
                break;
        }

        return $entries;
    }

    private function buildFilename(string $base, int $index): string
    {
        return $base . '-' . $index . '.xml';
    }

    /**
     * Extracts the slug portion from a full URL by removing baseUrl + urlPrefix.
     */
    private function extractSlug(string $fullUrl, SitemapConfig $map): string
    {
        $prefix = $this->baseUrl . '/' . $map->getUrlSegment();
        if (strpos($fullUrl, $prefix) === 0) {
            return substr($fullUrl, strlen($prefix));
        }
        return ltrim(str_replace($this->baseUrl, '', $fullUrl), '/');
    }

    /**
     * Returns enabled content types with their config, sorted by sort_order.
     */
    private function getEnabledTypes(): array
    {
        $types  = ['home', 'category', 'product', 'manufacturer', 'information',
                   'blog_post', 'blog_category', 'special', 'contact'];
        $result = [];

        foreach ($types as $type) {
            $key = 'type_' . $type;
            if (empty($this->config[$key . '_enabled'])) continue;

            $result[$type] = [
                'changefreq'     => $this->config[$key . '_changefreq'] ?? null,
                'priority'       => $this->config[$key . '_priority']   ?? null,
                'lastmod'        => $this->config[$key . '_lastmod']    ?? 'auto',
                'max_urls'       => (int)($this->config[$key . '_max_urls'] ?? 0),
                'include_bottom' => !empty($this->config[$key . '_include_bottom']),
                'sort_order'     => (int)($this->config[$key . '_sort_order'] ?? 0),
            ];
        }

        // Sort by sort_order
        uasort($result, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $result;
    }

    /**
     * Pre-loads SEO keyword sets for all maps into $this->keywordIndex.
     * Structure: [mapId => [keyword => true]]
     *
     * @param SitemapConfig[] $maps
     */
    private function buildKeywordIndex(array $maps): void
    {
        $this->keywordIndex = [];
        foreach ($maps as $map) {
            $collector = $this->collectorFactory->make($map);
            $this->keywordIndex[$map->mapId] = $collector->getAllSeoUrlMap();
        }
    }

    private function getMapsToProcess(?int $mapId): array
    {
        if ($mapId !== null) {
            return array_filter($this->maps, fn($m) => $m->mapId === $mapId);
        }
        return array_filter($this->maps, fn($m) => $m->status);
    }
}
