<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AutoRelated;

use OcKit\AutoRelated\Dto\ScoringWeights;
use OcKit\AutoRelated\Dto\GenerationResult;
use OcKit\AutoRelated\Libs\SimilarityScorer;
use OcKit\AutoRelated\Libs\RelatedWriter;
use OcKit\AutoRelated\Libs\GeneratorQueue;
use OcKit\AutoRelated\Libs\StatsRepository;
use OcKit\AutoRelated\Libs\StoreContext;
use OcKit\AutoRelated\Libs\RuleRepository;
use OcKit\AutoRelated\Libs\RuleEngine;

// Autoload exceptions (no version differences)
require_once __DIR__ . '/exceptions/AutoRelatedException.php';
require_once __DIR__ . '/libs/StoreContext.php';
require_once __DIR__ . '/libs/RuleRepository.php';
require_once __DIR__ . '/libs/RuleEngine.php';

// ⚠ Dual fork is intentional: ships PHP 7.4 fallbacks so the module installs
//   cleanly on legacy hosts. When editing libs/SimilarityScorer.php or
//   libs/GeneratorQueue.php, mirror the change into libs/php80/. Same for
//   dto/* ↔ dto/php81/*. Keep behaviour identical between forks.
// DTOs: PHP 8.1+ uses readonly properties; PHP 7.4 uses plain public properties
if (PHP_VERSION_ID >= 80100) {
    require_once __DIR__ . '/dto/php81/ScoringWeights.php';
    require_once __DIR__ . '/dto/php81/ProductSignals.php';
    require_once __DIR__ . '/dto/php81/GenerationResult.php';
} else {
    require_once __DIR__ . '/dto/ScoringWeights.php';
    require_once __DIR__ . '/dto/ProductSignals.php';
    require_once __DIR__ . '/dto/GenerationResult.php';
}

// Libs: PHP 8.0+ uses constructor property promotion; PHP 7.4 uses explicit properties
require_once __DIR__ . '/libs/RelatedWriter.php';
require_once __DIR__ . '/libs/StatsRepository.php';

if (PHP_VERSION_ID >= 80000) {
    require_once __DIR__ . '/libs/php80/SimilarityScorer.php';
    require_once __DIR__ . '/libs/php80/GeneratorQueue.php';
} else {
    require_once __DIR__ . '/libs/SimilarityScorer.php';
    require_once __DIR__ . '/libs/GeneratorQueue.php';
}

/**
 * Main facade. Single entry point from OpenCart controllers/models.
 *
 * Usage:
 *   require_once DIR_SYSTEM . 'library/ockit/auto_related/AutoRelated.php';
 *   $lib = \OcKit\AutoRelated\AutoRelated::getInstance($this->registry);
 */
class AutoRelated
{
    private static ?self $instance = null;

    private \DB              $db;
    private \Registry        $registry;
    private array            $config;
    private ScoringWeights   $weights;
    private RelatedWriter    $writer;
    private StoreContext     $storeContext;
    private bool             $licensed = false;
    // Lazy services — built on first use to avoid paying for unused
    // dependencies on light calls (isEnabled, getConfig, license helpers).
    private ?GeneratorQueue  $queue      = null;
    private ?StatsRepository $stats      = null;
    private ?RuleRepository  $ruleRepo   = null;
    private ?RuleEngine      $ruleEngine = null;

    private function __construct(\Registry $registry)
    {
        $this->db = $registry->get('db');

        // Load config from OC config system
        $cfg = $registry->get('config');
        $prefix = 'module_oc_kit_auto_related_';

        $this->config = [
            'status'          => (bool)$cfg->get($prefix . 'status'),
            'related_limit'   => (int)($cfg->get($prefix . 'related_limit')   ?? 8),
            'overwrite'       => (bool)$cfg->get($prefix . 'overwrite'),
            'on_visit'        => (bool)$cfg->get($prefix . 'on_visit'),
            'visit_mode'      => (string)($cfg->get($prefix . 'visit_mode')   ?? 'async'),
            'exclude_oos'     => (bool)($cfg->get($prefix . 'exclude_oos')    ?? true),
            'exclude_disabled'=> (bool)($cfg->get($prefix . 'exclude_disabled') ?? true),
            'cache'           => (bool)($cfg->get($prefix . 'cache')          ?? true),
            'cache_ttl'       => (int)($cfg->get($prefix . 'cache_ttl')       ?? 72),
            'candidate_limit' => (int)($cfg->get($prefix . 'candidate_limit') ?? 400),
            'language_id'     => (int)($cfg->get('config_language_id')        ?? 1),
            'store_id'        => (int)($cfg->get('config_store_id')           ?? 0),

            // Weights
            'weight_category'     => (int)($cfg->get($prefix . 'weight_category')     ?? 30),
            'weight_name'         => (int)($cfg->get($prefix . 'weight_name')         ?? 20),
            'weight_neighbor_id'  => (int)($cfg->get($prefix . 'weight_neighbor_id')  ?? 5),
            'weight_fields'       => (int)($cfg->get($prefix . 'weight_fields')        ?? 25),
            'weight_manufacturer' => (int)($cfg->get($prefix . 'weight_manufacturer') ?? 20),
            'weight_attributes'   => (int)($cfg->get($prefix . 'weight_attributes')   ?? 30),
            'weight_coorders'     => (int)($cfg->get($prefix . 'weight_coorders')     ?? 40),

            // Signal config
            'neighbor_range'      => (int)($cfg->get($prefix . 'neighbor_range')      ?? 50),
            'field_list'          => (array)($cfg->get($prefix . 'field_list')         ?? ['sku', 'mpn']),
            'field_separator'     => (string)($cfg->get($prefix . 'field_separator')  ?? ','),
            'attribute_ids'       => (array)($cfg->get($prefix . 'attribute_ids')     ?? []),
            'attribute_min_match' => (int)($cfg->get($prefix . 'attribute_min_match') ?? 1),
            'coorders_days'       => (int)($cfg->get($prefix . 'coorders_days')       ?? 365),
            'coorders_min'        => (int)($cfg->get($prefix . 'coorders_min')        ?? 2),
            'coorders_statuses'   => (array)($cfg->get($prefix . 'coorders_statuses') ?? []),

            // Result sort & special filter (global defaults)
            'result_sort'         => (string)($cfg->get($prefix . 'result_sort')      ?? 'score'),
            'only_special'        => (bool)($cfg->get($prefix . 'only_special')       ?? false),

            // Brand priority & blacklists
            'brand_priority'      => (bool)($cfg->get($prefix . 'brand_priority')     ?? false),
            'blacklist_products'  => (array)($cfg->get($prefix . 'blacklist_products')   ?? []),
            'blacklist_categories'=> (array)($cfg->get($prefix . 'blacklist_categories') ?? []),

            // Price range signal
            'weight_price_range'  => (int)($cfg->get($prefix . 'weight_price_range')  ?? 0),
            'price_range_pct'     => (int)($cfg->get($prefix . 'price_range_pct')     ?? 20),
        ];

        $this->registry     = $registry;
        $this->weights      = new ScoringWeights($this->config);
        $this->writer       = new RelatedWriter($this->db);
        $this->storeContext = new StoreContext($this->db, $registry->get('config'));
        $this->licensed     = $this->storeContext->isActive();

        // In admin context: redirect to license page from inside the encoded library.
        // user_token exists only in admin sessions — not in catalog or CLI.
        if (!$this->licensed && php_sapi_name() !== 'cli') {
            $token = $registry->get('session')->data['user_token'] ?? '';
            if ($token !== '') {
                $registry->get('response')->redirect(
                    $registry->get('url')->link(
                        'extension/module/oc_kit_auto_related/license',
                        'user_token=' . $token,
                        true
                    )
                );
                exit;
            }
        }
    }

    public static function getInstance(\Registry $registry): self
    {
        if (self::$instance === null) {
            self::$instance = new self($registry);
        }
        return self::$instance;
    }

    private function getQueue(): GeneratorQueue
    {
        if ($this->queue === null) {
            $scorer = new SimilarityScorer($this->weights);
            $this->queue = new GeneratorQueue($this->db, $this->weights, $scorer, $this->writer, $this->config);
        }
        return $this->queue;
    }

    private function getStatsRepo(): StatsRepository
    {
        if ($this->stats === null) {
            $this->stats = new StatsRepository($this->db, $this->registry->get('cache'));
        }
        return $this->stats;
    }

    private function getRuleRepo(): RuleRepository
    {
        if ($this->ruleRepo === null) {
            $this->ruleRepo = new RuleRepository($this->db);
        }
        return $this->ruleRepo;
    }

    private function getRuleEngine(): RuleEngine
    {
        if ($this->ruleEngine === null) {
            $this->ruleEngine = new RuleEngine($this->db, $this->getRuleRepo(), $this->config);
        }
        return $this->ruleEngine;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->config['status'];
    }

    public function isLicensed(): bool
    {
        return $this->licensed;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Generate related products for a single product (on-visit / manual / cron).
     */
    public function generateOne(int $productId, string $source = 'manual'): GenerationResult
    {
        if (!$this->licensed) {
            return new GenerationResult($productId, [], false, 'not_licensed');
        }
        return $this->getQueue()->generateOne($productId, $source);
    }

    /**
     * Ensure related products exist for a product (for on-visit trigger).
     * Respects TTL — skips if not expired.
     */
    public function ensureRelated(int $productId): GenerationResult
    {
        if (!$this->licensed) {
            return new GenerationResult($productId, [], false, 'not_licensed');
        }
        return $this->getQueue()->generateOne($productId, 'visit');
    }

    /**
     * Dry-run preview: returns scored candidates without writing to DB.
     */
    public function previewRelated(int $productId): array
    {
        return $this->getQueue()->previewOne($productId);
    }

    /**
     * Generate a batch for admin AJAX.
     */
    public function generateBatch(array $filters, int $batchSize, int $offset): array
    {
        if (!$this->licensed) {
            return ['processed' => 0, 'total' => 0, 'done' => true, 'error' => 'not_licensed'];
        }
        return $this->getQueue()->generateBatch($filters, $batchSize, $offset);
    }

    /**
     * Get next batch of pending product IDs for cron.
     */
    public function getPendingIds(bool $force, int $ttlHours, int $limit, int $offset, array $filters = []): array
    {
        if (!$this->licensed) {
            return [];
        }
        return $this->getQueue()->getPendingIds($force, $ttlHours, $limit, $offset, $filters);
    }

    /**
     * Get rule-based blocks for a product (catalog use).
     * Returns [['rule_id' => int, 'title' => string, 'product_ids' => int[]], ...]
     */
    public function getBlocks(int $productId): array
    {
        if (!$this->licensed) {
            return [];
        }
        return $this->getRuleEngine()->getBlocks($productId);
    }

    // ── Rule CRUD (admin) ─────────────────────────────────────────────────────

    public function getRules(): array
    {
        return $this->getRuleRepo()->getAll();
    }

    public function saveRule(array $data): int
    {
        return $this->getRuleRepo()->save($data);
    }

    public function deleteRule(int $id): void
    {
        $this->getRuleRepo()->delete($id);
    }

    // ── Static license helpers (lightweight — no full init) ───────────────────

    /**
     * Called from the admin controller at the top of any gated action.
     * Redirects to the license page from inside the encoded library if not licensed.
     */
    public static function guardAdmin($registry): void
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        if ($ctx->isActive()) return;
        $token = $registry->get('session')->data['user_token'] ?? '';
        $registry->get('response')->redirect(
            $registry->get('url')->link(
                'extension/module/oc_kit_auto_related/license',
                'user_token=' . $token,
                true
            )
        );
        exit;
    }

    public static function getLicenseStatus($registry): array
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        return $ctx->getInfo();
    }

    public static function activateLicenseKey($registry, string $key): array
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        return $ctx->activate($key);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        return $this->getStatsRepo()->getSummary();
    }

    public function getRecentLog(int $limit = 10): array
    {
        return $this->getStatsRepo()->getRecentLog($limit);
    }

    public function getSourceDistribution(): array
    {
        return $this->getStatsRepo()->getSourceDistribution();
    }

    public function getDailyTrend(int $days = 30): array
    {
        return $this->getStatsRepo()->getDailyTrend($days);
    }
}
