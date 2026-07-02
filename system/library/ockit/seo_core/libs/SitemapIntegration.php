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
 * Integrates with OcKit Sitemap Generator module.
 * Does NOT generate sitemaps — only queries status and triggers re-generation.
 */
class SitemapIntegration
{
    private $registry;

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * Check if OcKit Sitemap Generator library is present.
     */
    public function isInstalled(): bool
    {
        $libPath = DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        return file_exists($libPath);
    }

    /**
     * Returns full status summary for the sitemap page.
     */
    public function getStatus(int $storeId = 0): array
    {
        if (!$this->isInstalled()) {
            return [
                'installed'      => false,
                'enabled'        => false,
                'files'          => [],
                'last_generated' => null,
                'files_missing'  => true,
                'sitemap_url'    => '',
            ];
        }

        $sg = $this->getSitemapGenerator();

        $enabled        = (bool)(int)$sg->cfg('status');
        $files          = $sg->listGeneratedFiles();
        $lastGenerated  = null;
        $filesMissing   = empty($files);

        if (!empty($files)) {
            $timestamps = array_filter(array_column($files, 'mtime'));
            if (!empty($timestamps)) {
                $lastGenerated = date('Y-m-d H:i:s', max($timestamps));
            }
        } else {
            // Try last_generated_at from maps table
            $maps = $sg->getMapsObjects();
            foreach ($maps as $map) {
                if (!empty($map->lastGeneratedAt)) {
                    if ($lastGenerated === null || $map->lastGeneratedAt > $lastGenerated) {
                        $lastGenerated = $map->lastGeneratedAt;
                    }
                }
            }
        }

        $config  = $this->registry->get('config');
        $baseUrl = rtrim((string)($config->get('config_url') ?: ''), '/');

        $files = array_map(function ($f) use ($baseUrl) {
            $name = $f['filename'] ?? ($f['name'] ?? 'sitemap.xml');
            $f['name'] = $name;
            if (!isset($f['url'])) {
                $f['url'] = $baseUrl . '/' . $name;
            }
            $bytes = (int)($f['size'] ?? 0);
            if ($bytes >= 1048576) {
                $f['size_human'] = round($bytes / 1048576, 1) . ' MB';
            } elseif ($bytes >= 1024) {
                $f['size_human'] = round($bytes / 1024, 1) . ' KB';
            } else {
                $f['size_human'] = $bytes . ' B';
            }
            return $f;
        }, $files);

        return [
            'installed'        => true,
            'enabled'          => $enabled,
            'files'            => $files,
            'last_generated'   => $lastGenerated,
            'files_missing'    => $filesMissing,
            'sitemap_url'      => $baseUrl . '/sitemap.xml',
            'settings_url'     => '',
        ];
    }

    /**
     * Trigger sitemap generation via SitemapGenerator.
     */
    public function triggerGeneration(): bool
    {
        if (!$this->isInstalled()) return false;

        try {
            $sg = $this->getSitemapGenerator();
            $result = $sg->generate(null, false, 'manual_scf');
            return isset($result['maps']) && count($result['maps']) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ping Google with the sitemap URL.
     */
    public function pingGoogle(string $sitemapUrl): bool
    {
        if (!function_exists('curl_init')) return false;

        $pingUrl = 'https://www.google.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        $ch      = curl_init($pingUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    /**
     * Ping Bing with the sitemap URL.
     */
    public function pingBing(string $sitemapUrl): bool
    {
        if (!function_exists('curl_init')) return false;

        $pingUrl = 'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        $ch      = curl_init($pingUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function getSitemapGenerator(): \OcKit\SitemapGenerator\SitemapGenerator
    {
        $libPath = DIR_SYSTEM . 'library/ockit/sitemap_generator/SitemapGenerator.php';
        if (!class_exists('\OcKit\SitemapGenerator\SitemapGenerator', false)) {
            require_once $libPath;
        }
        return new \OcKit\SitemapGenerator\SitemapGenerator($this->registry);
    }
}
