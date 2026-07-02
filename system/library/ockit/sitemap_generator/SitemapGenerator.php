<?php
/**
 * Sitemap Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\SitemapGenerator;

use OcKit\SitemapGenerator\Dto\SitemapConfig;
use OcKit\SitemapGenerator\Libs\CronRunner;
use OcKit\SitemapGenerator\Libs\DbLogger;
use OcKit\SitemapGenerator\Libs\HreflangBuilder;
use OcKit\SitemapGenerator\Libs\ImageResolver;
use OcKit\SitemapGenerator\Libs\SitemapBuilder;
use OcKit\SitemapGenerator\Libs\SitemapRouter;
use OcKit\SitemapGenerator\Libs\SitemapWriter;
use OcKit\SitemapGenerator\Libs\StoreContext;
use OcKit\SitemapGenerator\Libs\UrlCollector;
use OcKit\SitemapGenerator\Libs\UrlCollectorFactory;

/**
 * Main module facade — single entry point from OC controllers/models/cron.
 */
class SitemapGenerator
{
    private $registry;
    private string $prefix;
    private string $configPrefix = 'module_oc_kit_sitemap_generator_';
    private string $storeUrl;
    private string $outputDir;
    private string $imageDir;

    private ?DbLogger            $logger      = null;
    private ?StoreContext        $storeCtx    = null;
    private ?SitemapWriter       $writer      = null;
    private ?SitemapBuilder      $builder     = null;
    private ?HreflangBuilder     $hreflang    = null;
    private ?UrlCollectorFactory $collFactory = null;
    private ?ImageResolver       $imageRes    = null;
    private ?CronRunner          $cronRunner  = null;
    private ?SitemapRouter       $router      = null;

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->prefix   = DB_PREFIX;

        $config = $registry->get('config');

        $this->storeUrl = defined('HTTP_CATALOG')
            ? rtrim(HTTP_CATALOG, '/')
            : rtrim($config->get('config_url') ?: (defined('HTTP_SERVER') ? HTTP_SERVER : ''), '/');

        $siteRoot = defined('DIR_APPLICATION')
            ? dirname(DIR_APPLICATION)
            : rtrim(DIR_SYSTEM, '/') . '/..';

        $outDirCfg = trim((string)($config->get($this->configPrefix . 'output_directory') ?? ''));
        if ($outDirCfg === '') {
            $this->outputDir = rtrim($siteRoot, '/') . '/';
        } elseif ($outDirCfg[0] === '/' || (strlen($outDirCfg) > 1 && $outDirCfg[1] === ':')) {
            // Absolute path
            $this->outputDir = rtrim($outDirCfg, '/') . '/';
        } else {
            // Relative path from site root
            $this->outputDir = rtrim($siteRoot, '/') . '/' . ltrim(rtrim($outDirCfg, '/'), '/') . '/';
        }

        $this->imageDir = defined('DIR_IMAGE') ? DIR_IMAGE : (DIR_SYSTEM . '../image/');

        $this->autoload();
    }

    private function autoload(): void
    {
        $dir = __DIR__ . '/';
        require_once $dir . 'dto/SitemapConfig.php';
        require_once $dir . 'dto/SitemapEntry.php';
        require_once $dir . 'dto/ImageEntry.php';
        require_once $dir . 'exceptions/SitemapGeneratorException.php';
        require_once $dir . 'exceptions/SitemapWriteException.php';
        require_once $dir . 'libs/DbLogger.php';
        require_once $dir . 'libs/StoreContext.php';
        require_once $dir . 'libs/UrlCollector.php';
        require_once $dir . 'libs/UrlCollectorFactory.php';
        require_once $dir . 'libs/ImageResolver.php';
        require_once $dir . 'libs/HreflangBuilder.php';
        require_once $dir . 'libs/SitemapBuilder.php';
        require_once $dir . 'libs/SitemapWriter.php';
        require_once $dir . 'libs/SitemapXslTemplate.php';
        require_once $dir . 'libs/CronRunner.php';
        require_once $dir . 'libs/SitemapRouter.php';
    }

    // ── Lazy getters ──────────────────────────────────────────────────────────

    private function db()
    {
        return $this->registry->get('db');
    }

    private function config()
    {
        return $this->registry->get('config');
    }

    private function logger(): DbLogger
    {
        if ($this->logger === null) {
            $this->logger = new DbLogger($this->db(), $this->prefix);
        }
        return $this->logger;
    }

    private function storeCtx(): StoreContext
    {
        if ($this->storeCtx === null) {
            $this->storeCtx = new StoreContext($this->db(), $this->config());
        }
        return $this->storeCtx;
    }

    private function writer(): SitemapWriter
    {
        if ($this->writer === null) {
            $gzip = (bool)(int)$this->cfg('enable_gzip');
            $this->writer = new SitemapWriter($this->outputDir, $gzip);
        }
        return $this->writer;
    }

    private function builder(): SitemapBuilder
    {
        if ($this->builder === null) {
            $enableXsl = (bool)(int)$this->cfg('enable_xsl');
            $this->builder = new SitemapBuilder(
                (bool)(int)$this->cfg('include_images'),
                (bool)(int)$this->cfg('hreflang_enable'),
                $enableXsl,
                $enableXsl ? ($this->storeUrl . '/sitemap.xsl') : ''
            );
        }
        return $this->builder;
    }

    private function imageResolver(): ?ImageResolver
    {
        if (!((bool)(int)$this->cfg('include_images'))) return null;
        if ($this->imageRes === null) {
            $resizeFn = null;
            if (($this->cfg('image_type') ?? 'original') === 'resized') {
                $resizeFn = static function(string $src, string $dst, int $w, int $h): void {
                    if (!class_exists('Image')) {
                        require_once DIR_SYSTEM . 'library/image.php';
                    }
                    $img = new \Image($src);
                    $img->resize($w, $h);
                    $img->save($dst);
                };
            }
            $this->imageRes = new ImageResolver($this->storeUrl, $this->imageDir, [
                'image_type'               => $this->cfg('image_type')               ?? 'original',
                'image_width'              => (int)($this->cfg('image_width')         ?? 800),
                'image_height'             => (int)($this->cfg('image_height')        ?? 800),
                'include_additional_images'=> (bool)(int)($this->cfg('include_additional_images') ?? 0),
                'max_images_per_product'   => (int)($this->cfg('max_images_per_product') ?? 10),
            ], $resizeFn);
        }
        return $this->imageRes;
    }

    private function hreflang(): HreflangBuilder
    {
        if ($this->hreflang === null) {
            $maps = $this->getMapsObjects();
            $this->hreflang = new HreflangBuilder($maps, [
                'enable_hreflang'               => (bool)(int)($this->cfg('hreflang_enable')              ?? 0),
                'xdefault_map_id'               => (int)($this->cfg('hreflang_xdefault_map_id')           ?? 0),
                'missing_translation_behavior'  => $this->cfg('hreflang_missing_behavior')                ?? 'skip',
            ]);
        }
        return $this->hreflang;
    }

    private function collectorFactory(): UrlCollectorFactory
    {
        if ($this->collFactory === null) {
            $storeId = (int)($this->config()->get('config_store_id') ?? 0);
            $this->collFactory = new UrlCollectorFactory(
                $this->db(),
                $this->prefix,
                $this->storeUrl,
                $storeId,
                $this->imageResolver()
            );
        }
        return $this->collFactory;
    }

    private function cronRunner(): CronRunner
    {
        if ($this->cronRunner === null) {
            $this->cronRunner = new CronRunner(
                $this->collectorFactory(),
                $this->builder(),
                $this->writer(),
                $this->hreflang(),
                $this->logger(),
                $this->getAllConfig(),
                $this->getMapsObjects(),
                $this->storeUrl
            );
        }
        return $this->cronRunner;
    }

    private function router(): SitemapRouter
    {
        if ($this->router === null) {
            $cacheDir = defined('DIR_CACHE') ? DIR_CACHE . 'sitemap/' : DIR_SYSTEM . '../storage/cache/sitemap/';
            $this->router = new SitemapRouter(
                array_merge($this->getAllConfig(), ['store_url' => $this->storeUrl]),
                $this->getMapsObjects(),
                $cacheDir
            );
        }
        return $this->router;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Guard for admin pages: redirects to license page if not active.
     */
    public static function guardAdmin($registry): void
    {
        require_once __DIR__ . '/SitemapGenerator.php';
        $sg = new self($registry);
        if (!$sg->storeCtx()->isActive()) {
            $token = $registry->get('session')->data['user_token'] ?? '';
            $registry->get('response')->redirect(
                $registry->get('url')->link(
                    'extension/module/oc_kit_sitemap_generator/license',
                    'user_token=' . $token,
                    true
                )
            );
        }
    }

    public static function getLicenseStatus($registry): array
    {
        $sg = new self($registry);
        return $sg->storeCtx()->getInfo();
    }

    public static function activateLicenseKey($registry, string $key): array
    {
        $sg = new self($registry);
        return $sg->storeCtx()->activate($key);
    }

    /**
     * Runs sitemap generation synchronously and persists per-map stats.
     */
    public function generate(?int $mapId = null, bool $dryRun = false, string $triggeredBy = 'manual'): array
    {
        $result = $this->cronRunner()->run($mapId, $dryRun, $triggeredBy);

        if (!$dryRun) {
            foreach ($result['maps'] as $mapResult) {
                $this->db()->query(
                    "UPDATE `{$this->prefix}sitemap_generator_maps`
                     SET urls_count        = " . (int)$mapResult['urls_count'] . ",
                         files_count       = " . (int)$mapResult['files_count'] . ",
                         last_generated_at = NOW()
                     WHERE map_id = " . (int)$mapResult['map_id']
                );
            }
        }

        return $result;
    }

    /**
     * Generates all sitemap-specific image resizes without writing XML.
     * Resized files are stored under image/cache/sitemap/{product_id}/.
     * Returns ['processed', 'created', 'cached', 'original', 'missing'].
     */
    public function generateResizes(): array
    {
        if (($this->cfg('image_type') ?? 'original') !== 'resized') {
            return ['error' => 'image_type_not_resized', 'processed' => 0, 'created' => 0, 'cached' => 0];
        }

        $imageRes = $this->imageResolver();
        if ($imageRes === null) {
            return ['error' => 'images_disabled', 'processed' => 0, 'created' => 0, 'cached' => 0];
        }

        $imageRes->resetStats();

        $maps    = $this->getMapsObjects();
        $firstMap = null;
        foreach ($maps as $m) {
            if ($m->status) { $firstMap = $m; break; }
        }
        if ($firstMap === null) {
            return ['error' => 'no_active_maps', 'processed' => 0, 'created' => 0, 'cached' => 0];
        }

        $storeId   = (int)($this->config()->get('config_store_id') ?? 0);
        $collector = new UrlCollector(
            $this->db(), $this->prefix, $this->storeUrl, '',
            $firstMap->languageId, $storeId
        );

        $includeAdditional = (bool)(int)($this->cfg('include_additional_images') ?? 0);
        $total      = $collector->countProducts();
        $processed  = 0;
        $offset     = 0;
        $chunkSize  = 100;

        while ($offset < $total) {
            $rows = $collector->getProductsWithImages([], $offset, $chunkSize);
            foreach ($rows as $row) {
                $addRows = $includeAdditional
                    ? $collector->getProductAdditionalImages((int)$row['product_id'])
                    : [];
                $imageRes->resolve((string)($row['image'] ?? ''), $addRows, (int)$row['product_id']);
                $processed++;
            }
            $offset += $chunkSize;
        }

        $stats = $imageRes->getStats();
        return array_merge(['processed' => $processed], $stats);
    }

    // ── Maps CRUD ─────────────────────────────────────────────────────────────

    /** @return SitemapConfig[] */
    public function getMapsObjects(): array
    {
        $rows = $this->db()->query(
            "SELECT * FROM `{$this->prefix}sitemap_generator_maps` ORDER BY sort_order, map_id"
        )->rows;
        return array_map([SitemapConfig::class, 'fromRow'], $rows);
    }

    public function getMapsArray(): array
    {
        return $this->db()->query(
            "SELECT * FROM `{$this->prefix}sitemap_generator_maps` ORDER BY sort_order, map_id"
        )->rows;
    }

    public function getMap(int $mapId): ?array
    {
        $result = $this->db()->query(
            "SELECT * FROM `{$this->prefix}sitemap_generator_maps` WHERE map_id = " . (int)$mapId . " LIMIT 1"
        );
        return !empty($result->row) ? $result->row : null;
    }

    public function saveMap(array $data): int
    {
        $db     = $this->db();
        $mapId  = (int)($data['map_id'] ?? 0);
        // Resolve language_code from DB (never trust client input)
        $langId   = (int)($data['language_id'] ?? 0);
        $langRow  = $langId ? $db->query(
            "SELECT code FROM `{$this->prefix}language` WHERE language_id = {$langId} LIMIT 1"
        )->row : [];
        $langCode = $langRow['code'] ?? (string)($data['language_code'] ?? '');

        $fields = [
            'language_id'     => $langId,
            'language_code'   => $db->escape($langCode),
            'url_prefix'      => $db->escape((string)($data['url_prefix']      ?? '')),
            'filename'        => $db->escape((string)($data['filename']        ?? 'sitemap')),
            'hreflang_locale' => $db->escape((string)($data['hreflang_locale'] ?? '')),
            'is_default'      => (int)!empty($data['is_default']),
            'status'          => (int)!empty($data['status']),
            'sort_order'      => (int)($data['sort_order']     ?? 0),
        ];

        // Enforce only one x-default
        if ($fields['is_default']) {
            $db->query("UPDATE `{$this->prefix}sitemap_generator_maps` SET is_default = 0");
        }

        if ($mapId > 0) {
            $sets = [];
            foreach ($fields as $col => $val) {
                $sets[] = "`{$col}` = " . (is_int($val) ? $val : "'{$val}'");
            }
            $db->query(
                "UPDATE `{$this->prefix}sitemap_generator_maps`
                 SET " . implode(', ', $sets) . "
                 WHERE map_id = {$mapId}"
            );
            return $mapId;
        }

        $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($fields)));
        $vals = implode(', ', array_map(fn($v) => is_int($v) ? $v : "'{$v}'", array_values($fields)));
        $db->query("INSERT INTO `{$this->prefix}sitemap_generator_maps` ({$cols}) VALUES ({$vals})");
        return (int)$db->getLastId();
    }

    public function deleteMap(int $mapId): void
    {
        $this->db()->query(
            "DELETE FROM `{$this->prefix}sitemap_generator_maps` WHERE map_id = " . (int)$mapId
        );
    }

    // ── Logs ─────────────────────────────────────────────────────────────────

    public function getLogs(array $filter = []): array
    {
        return $this->logger()->getLogs($filter);
    }

    public function clearLogs(?int $mapId = null): void
    {
        $this->logger()->clearLogs($mapId);
    }

    // ── Files ─────────────────────────────────────────────────────────────────

    public function listGeneratedFiles(): array
    {
        return $this->writer()->listFiles();
    }

    public function deleteGeneratedFiles(): void
    {
        $this->writer()->deleteByPattern('sitemap');
    }

    public function checkOutputDirWritable(): bool
    {
        return $this->writer()->isWritable();
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public function getRobotsHint(): string
    {
        $indexFile = ($this->cfg('index_filename') ?? 'sitemap') . '.xml';
        return 'Sitemap: ' . $this->storeUrl . '/' . $indexFile;
    }

    // ── Dynamic mode ─────────────────────────────────────────────────────────

    public function serveXml(string $requestedFile): void
    {
        $this->router()->serve($requestedFile, $this->cronRunner(), $this->builder());
    }

    // ── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $db     = $this->db();
        $prefix = $this->prefix;

        $db->query(
            "CREATE TABLE IF NOT EXISTS `{$prefix}sitemap_generator_maps` (
                `map_id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
                `language_id`      INT UNSIGNED     NOT NULL DEFAULT 0,
                `language_code`    VARCHAR(5)       NOT NULL DEFAULT '',
                `url_prefix`       VARCHAR(20)      NOT NULL DEFAULT '',
                `filename`         VARCHAR(100)     NOT NULL DEFAULT 'sitemap',
                `hreflang_locale`  VARCHAR(10)      NOT NULL DEFAULT '',
                `is_default`       TINYINT(1)       NOT NULL DEFAULT 0,
                `status`           TINYINT(1)       NOT NULL DEFAULT 1,
                `last_generated_at` DATETIME        NULL,
                `urls_count`       INT UNSIGNED     NOT NULL DEFAULT 0,
                `files_count`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `sort_order`       INT UNSIGNED     NOT NULL DEFAULT 0,
                PRIMARY KEY (`map_id`),
                KEY `status` (`status`),
                KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->logger()->install();

        // Generate cron key if not set
        $existing = $db->query(
            "SELECT setting_id FROM `{$prefix}setting`
             WHERE code = 'module_oc_kit_sitemap_generator'
               AND `key` = 'module_oc_kit_sitemap_generator_cron_key'
               AND store_id = 0 LIMIT 1"
        );
        if (empty($existing->row)) {
            $key = bin2hex(random_bytes(16));
            $db->query(
                "INSERT INTO `{$prefix}setting` (store_id, code, `key`, value, serialized)
                 VALUES (0, 'module_oc_kit_sitemap_generator',
                         'module_oc_kit_sitemap_generator_cron_key',
                         '" . $db->escape($key) . "', 0)"
            );
        }
    }

    public function uninstall(): void
    {
        $prefix = $this->prefix;
        $this->db()->query("DROP TABLE IF EXISTS `{$prefix}sitemap_generator_maps`");
        $this->logger()->uninstall();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function cfg(string $key)
    {
        return $this->config()->get($this->configPrefix . $key);
    }

    private function getAllConfig(): array
    {
        $cfg = [];
        $result = $this->db()->query(
            "SELECT `key`, `value` FROM `{$this->prefix}setting`
             WHERE code = 'module_oc_kit_sitemap_generator' AND store_id = 0"
        );
        foreach ($result->rows as $row) {
            $shortKey = str_replace($this->configPrefix, '', $row['key']);
            $cfg[$shortKey] = $row['value'];
        }
        return $cfg;
    }

    public function getStoreUrl(): string
    {
        return $this->storeUrl;
    }
}
