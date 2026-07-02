<?php
/**
 * Advanced Search Pro — Main Facade
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro;

use OcKit\AdvancedSearchPro\Config\ModuleSettings;
use OcKit\AdvancedSearchPro\Contracts\SearchEngineInterface;
use OcKit\AdvancedSearchPro\Factory\SearchEngineFactory;
use OcKit\AdvancedSearchPro\Libs\SearchService;
use OcKit\AdvancedSearchPro\Libs\IndexService;
use OcKit\AdvancedSearchPro\Libs\AiService;
use OcKit\AdvancedSearchPro\Libs\StatsService;
use OcKit\AdvancedSearchPro\Libs\HomoglyphNormalizer;
use OcKit\AdvancedSearchPro\Libs\Transliterator;
use OcKit\AdvancedSearchPro\Libs\TypoCorrector;
use OcKit\AdvancedSearchPro\Libs\WordSplitter;
use OcKit\AdvancedSearchPro\Libs\MmrService;
use OcKit\AdvancedSearchPro\Libs\ProductGroupService;

spl_autoload_register(function ($class) {
    $prefix = 'OcKit\\AdvancedSearchPro\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    // Namespace 'Libs' maps to lowercase 'libs/' folder on disk
    $relative = preg_replace('#^Libs/#', 'libs/', $relative);
    $file = DIR_SYSTEM . 'library/ockit/advanced_search_pro/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

class AdvancedSearchPro {
    protected $registry;
    protected $db;
    protected $config;
    protected $log;
    private $productGroupService;

    const VERSION    = '0.2.0';
    const KEY_PREFIX = 'module_oc_kit_advanced_search_pro_';

    private ?SearchService       $searchService      = null;
    private ?IndexService        $indexService       = null;
    private ?AiService           $aiService          = null;
    private ?StatsService        $statsService       = null;
    private ?HomoglyphNormalizer $homoglyphNorm      = null;
    private ?Transliterator      $transliterator     = null;
    private ?TypoCorrector       $typoCorrector      = null;
    private ?WordSplitter        $wordSplitter       = null;
    private ?MmrService          $mmrService         = null;
    private ?bool                $catalogLicensed    = null;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db       = $registry->get('db');
        $this->config   = $registry->get('config');
        $this->log      = $registry->has('log') ? $registry->get('log') : null;
    }

    // -------------------------------------------------------------------------
    // Licensing — standard oc-kit StoreContext pattern (static helpers)
    // -------------------------------------------------------------------------

    private static ?self $instance = null;

    public static function getInstance($registry): self
    {
        if (self::$instance === null) {
            self::$instance = new self($registry);
        }
        return self::$instance;
    }

    /**
     * Returns the saved license key, or empty string if none.
     */
    public static function getLicenseKey($registry): string
    {
        return (string)($registry->get('config')->get(self::KEY_PREFIX . 'license_key') ?? '');
    }

    /**
     * Returns license status array: ['status' => active|trial|expired|invalid|grace|not_validated,
     *                                 'version' => string, 'domain' => string, 'trial_days_left' => ?int].
     */
    public static function getLicenseStatus($registry): array
    {
        $key = self::getLicenseKey($registry);
        if ($key === '') {
            return ['status' => 'not_validated', 'version' => self::VERSION, 'domain' => ''];
        }

        // Guard the validator load — if the file/class is missing or throws
        // (attacker tampering, broken install, deletion attempt), return a
        // dedicated "tampered" status. Admin then shows the activation page,
        // catalog falls back to native MySQL — never a 500.
        $validatorFile = __DIR__ . '/libs/SearchData.php';
        if (!is_file($validatorFile)) {
            return ['status' => 'tampered', 'version' => self::VERSION, 'domain' => ''];
        }
        try {
            require_once $validatorFile;
            if (!class_exists('\OcKit\AdvancedSearchPro\Libs\SearchData', false)) {
                return ['status' => 'tampered', 'version' => self::VERSION, 'domain' => ''];
            }
            $v = new \OcKit\AdvancedSearchPro\Libs\SearchData($key);
            $status = method_exists($v, 'getStatus') ? $v->getStatus() : ($v->isValid() ? 'active' : 'invalid');
            $domain = method_exists($v, 'getBoundDomain') ? $v->getBoundDomain() : '';
        } catch (\Throwable $e) {
            return ['status' => 'tampered', 'version' => self::VERSION, 'domain' => ''];
        }

        $info = ['status' => $status, 'version' => self::VERSION, 'domain' => (string)$domain];

        if ($status === 'trial' && method_exists($v, 'getExpiry')) {
            $exp = $v->getExpiry();
            if ($exp) {
                $info['trial_days_left'] = max(0, (int)floor((strtotime($exp) - time()) / 86400));
            }
        }
        return $info;
    }

    /**
     * Throws when license is invalid for admin actions. Trial/grace are allowed.
     * Caller should catch \RuntimeException and respond with a friendly error.
     */
    public static function guardAdmin($registry): void
    {
        $info = self::getLicenseStatus($registry);
        if (in_array($info['status'], ['active', 'trial', 'grace', 'not_validated'], true)) {
            return; // not_validated is permissive on admin to let the user enter a key
        }
        throw new \RuntimeException('Advanced Search license is not valid: ' . $info['status']);
    }

    /**
     * Returns true if the license permits catalog-side feature use (active/trial/grace).
     */
    public static function isLicensedCatalog($registry): bool
    {
        $info = self::getLicenseStatus($registry);
        return in_array($info['status'], ['active', 'trial', 'grace'], true);
    }

    /**
     * Per-instance memoized catalog-licence check. Enforcement lives HERE in the
     * library (encodable with ionCube) rather than only in the OC controllers,
     * so removing the controller-level gate cannot unlock Pro features. The
     * licence validation is pure local HMAC (no network), so this is cheap.
     */
    private function catalogAllowed(): bool
    {
        if ($this->catalogLicensed === null) {
            $this->catalogLicensed = self::isLicensedCatalog($this->registry);
        }
        return $this->catalogLicensed;
    }

    // -------------------------------------------------------------------------
    // Service accessors (lazy init)
    // -------------------------------------------------------------------------

    public function search(): SearchService {
        if ($this->searchService === null) {
            $this->searchService = new SearchService($this->registry);
        }
        return $this->searchService;
    }

    public function index(): IndexService {
        if ($this->indexService === null) {
            $this->indexService = new IndexService($this->registry);
        }
        return $this->indexService;
    }

    public function ai(): AiService {
        if ($this->aiService === null) {
            $this->aiService = new AiService($this->registry);
        }
        return $this->aiService;
    }

    public function stats(): StatsService {
        if ($this->statsService === null) {
            $this->statsService = new StatsService($this->registry);
        }
        return $this->statsService;
    }

    // -------------------------------------------------------------------------
    // NLP preprocessing — new features
    // -------------------------------------------------------------------------

    /**
     * Normalize mixed-script homoglyphs in a query.
     * e.g. "сухий" with Latin 'х' → "сухий" with Cyrillic 'х'.
     */
    public function normalizeHomoglyphs(string $query): string {
        if ($this->homoglyphNorm === null) {
            $this->homoglyphNorm = new HomoglyphNormalizer();
        }
        return $this->homoglyphNorm->normalize($query);
    }

    /**
     * Return transliteration variants (Cyrillic↔Latin).
     * e.g. "Сімпаріка" → ["Simparika"] and "Simparica" → ["Сімаріка"]
     */
    public function getTransliterationVariants(string $query): array {
        if ($this->transliterator === null) {
            $this->transliterator = new Transliterator();
        }
        return $this->transliterator->getVariants($query);
    }

    /**
     * Return the most likely typo correction, or null if query seems correct.
     * e.g. "Сімпарікп" → "Сімпаріка"
     */
    public function getTypoCorrection(string $query, int $maxDistance = 2, int $minLen = 4): ?string {
        if ($this->typoCorrector === null) {
            $this->typoCorrector = new TypoCorrector($this->db);
        }
        // History-driven correction (asp_query_log). Manticore-native fuzzy
        // (engine OPTION fuzzy=1 via HTTP /search) handles the indexed-token
        // path inside ManticoreSearchEngine, so this stays a thin DB-only
        // helper for "Did you mean?" UI text.
    return $this->typoCorrector->correct($query, $maxDistance, $minLen);
    }

    /**
     * Try to split a compound query into words.
     * e.g. "Кормдлясобак" → "Корм для собак"
     * Returns null if splitting is not confident.
     */
    public function splitCompoundQuery(string $query): ?string {
        if ($this->wordSplitter === null) {
            $this->wordSplitter = new WordSplitter($this->db);
        }
        return $this->wordSplitter->trySplit($query);
    }

    /**
     * Rerank product IDs using Maximal Marginal Relevance.
     * λ=0.5 balances relevance and diversity.
     */
    public function mmrRerank(array $ids, float $lambda = 0.5, int $limit = 0): array {
        if ($this->mmrService === null) {
            $this->mmrService = new MmrService($this->db, $this->config);
        }
        return $this->mmrService->rerank($ids, $lambda, $limit);
    }

    /**
     * Collapse product variant groups (colour/size variants of one item) onto a
     * single representative each — the first, i.e. best-ranked, match of each
     * group — so search results mirror the catalog's product grouping. A no-op
     * where the grouping module is absent; gated per store via the
     * autocomplete_group_collapse setting.
     */
    public function collapseProductGroups(array $ids, int $collapseAttrId = 0): array {
        if ($this->productGroupService === null) {
            $this->productGroupService = new ProductGroupService($this->db);
        }
        $langId = (int)$this->config->get('config_language_id');
        return $this->productGroupService->collapse($ids, $collapseAttrId, $langId);
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function getSettings(array $defaults): array {
        $settings = new ModuleSettings($this->config, self::KEY_PREFIX);
        return $settings->get($defaults);
    }

    // -------------------------------------------------------------------------
    // Meta store
    // -------------------------------------------------------------------------

    public function setMeta($key, $value): void {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_meta`
             SET meta_key = '" . $this->db->escape((string)$key) . "',
                 meta_value = '" . $this->db->escape((string)$value) . "',
                 updated_at = NOW()
             ON DUPLICATE KEY UPDATE
                 meta_value = VALUES(meta_value),
                 updated_at = NOW()"
        );
    }

    public function getMeta($key, $default = null) {
        $query = $this->db->query(
            "SELECT meta_value FROM `" . DB_PREFIX . "asp_meta`
             WHERE meta_key = '" . $this->db->escape((string)$key) . "'
             LIMIT 1"
        );
        if (!empty($query->row) && array_key_exists('meta_value', $query->row)) {
            return $query->row['meta_value'];
        }
        return $default;
    }

    // -------------------------------------------------------------------------
    // Daemon clients (used by admin controller and search engines)
    // -------------------------------------------------------------------------

    public function getManticoreClient(array $settings): ManticoreClient {
        $sqlPort  = (int)($settings['port'] ?? 9306);
        // HTTP port: explicit setting wins, else derive from SQL port
        // (Manticore's default 9306→9308 offset). Falls back to 9308.
        $httpPort = (int)($settings['http_port'] ?? 0);
        if ($httpPort <= 0) {
            $httpPort = ($sqlPort === 9306) ? 9308 : ($sqlPort + 2);
        }
        return new ManticoreClient([
            'host'      => $settings['host']     ?? '127.0.0.1',
            'port'      => $sqlPort,
            'http_port' => $httpPort,
            'index'     => $settings['index']    ?? 'products',
            'user'      => $settings['login']    ?? '',
            'pass'      => $settings['password'] ?? '',
            'timeout'   => 2
        ]);
    }

    public function getSphinxClient(array $settings): SphinxClient {
        return new SphinxClient([
            'host'    => $settings['sphinx_host']     ?? ($settings['host'] ?? '127.0.0.1'),
            'port'    => $settings['sphinx_port']     ?? 9306,
            'index'   => $settings['sphinx_index']    ?? ($settings['index'] ?? 'products'),
            'user'    => $settings['sphinx_login']    ?? ($settings['login'] ?? ''),
            'pass'    => $settings['sphinx_password'] ?? ($settings['password'] ?? ''),
            'timeout' => 2
        ]);
    }

    public function getSearchEngine(array $settings = []): SearchEngineInterface {
        $merged = array_merge($this->getSettings([
            'mode'           => SearchMode::NATIVE,
            'host'           => '127.0.0.1',
            'port'           => '9306',
            'index'          => 'products',
            'login'          => '',
            'password'       => '',
            'sphinx_host'    => '127.0.0.1',
            'sphinx_port'    => '9306',
            'sphinx_index'   => 'products',
            'sphinx_login'   => '',
            'sphinx_password'=> '',
            'vector_ratio'   => 30
        ]), $settings);

        // Library-level licence enforcement (ionCube-protected). Unlicensed
        // stores fall back to native MySQL search regardless of the requested
        // mode — this is the real gate; controller checks are only for UX.
        if (!$this->catalogAllowed()) {
            $merged['mode'] = SearchMode::NATIVE;
        }

        $factory = new SearchEngineFactory(
            $this->db,
            $this->config,
            $this->getManticoreClient($merged),
            $this->getSphinxClient($merged)
        );

        return $factory->create($merged['mode'], $merged);
    }

    // -------------------------------------------------------------------------
    // Install / Uninstall (delegates to IndexService)
    // -------------------------------------------------------------------------

    public function install(): void {
        $this->index()->install();
    }

    public function uninstall(): void {
        $this->index()->uninstall();
    }

    // -------------------------------------------------------------------------
    // Convenience proxy methods (backwards-compatible with controller code)
    // -------------------------------------------------------------------------

    public function normalizeQuery($query): string {
        return $this->search()->normalizeQuery($query);
    }

    public function getLayoutVariants($query, $max = 2): array {
        return $this->search()->getLayoutVariants($query, $max);
    }

    public function getCrossLangVariants($query): array {
        return $this->search()->getCrossLangVariants($query);
    }

    public function getScriptRepairVariants($query, $max = 10): array {
        return $this->search()->getScriptRepairVariants($query, $max);
    }

    public function getSynonymTerms($query): array {
        return $this->search()->getSynonymTerms($query);
    }

    public function extractPriceFilters($query): array {
        return $this->search()->extractPriceFilters($query);
    }

    public function getSynonymWordVariants($query, $maxVariants = 6): array {
        return $this->search()->getSynonymWordVariants($query, $maxVariants);
    }

    public function getStemVariants(string $query): array {
        return $this->search()->getStemVariants($query);
    }

    /**
     * Trigram fallback — final recall net, call only when the primary search
     * (any engine mode) returned 0 results. Matches products by character
     * trigrams over product names via MySQL, so it works regardless of the
     * active engine.
     *
     * @return array{ids: int[], total: int}
     */
    public function trigramFallback(string $query, int $limit = 100): array {
        if (!class_exists('\\OcKit\\AdvancedSearchPro\\Libs\\TrigramMatcher')) {
            require_once DIR_SYSTEM . 'library/ockit/advanced_search_pro/libs/TrigramMatcher.php';
        }
        $matcher = new \OcKit\AdvancedSearchPro\Libs\TrigramMatcher($this->db, $this->config);
        return $matcher->match($query, $limit);
    }

    public function logQuery($query, $results, $latencyMs, $sessionId = ''): void {
        $this->stats()->logQuery($query, $results, $latencyMs, $sessionId);
    }

    public function logSearchError($message = ''): void {
        $this->stats()->logSearchError($message);
    }

    public function registerCacheHit($isHit): void {
        $this->stats()->registerCacheHit($isHit);
    }

    public function getPopularQueries($limit = 10, $days = 30): array {
        return $this->stats()->getPopularQueries($limit, $days);
    }

    public function purgeOldData($logTtlDays = 90): array {
        return $this->stats()->purgeOldData($logTtlDays);
    }

    public function applyStoredQueryRule($query, $settings = []): array {
        if (!$this->catalogAllowed()) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }
        return $this->ai()->applyStoredQueryRule($query, $settings);
    }

    public function enhanceQueryWithAi($query, $settings = []): array {
        if (!$this->catalogAllowed()) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }
        return $this->ai()->enhanceQueryWithAi($query, $settings);
    }

    public function saveQueryRule($query, $rewrite = '', array $expanded = [], $intent = '', $source = 'manual'): bool {
        return $this->ai()->saveQueryRule($query, $rewrite, $expanded, $intent, $source);
    }

    public function generateQueryRules($limit = 100, $days = 30, $minCount = 2, $settings = []): array {
        return $this->ai()->generateQueryRules($limit, $days, $minCount, $settings);
    }

    public function proposeSynonyms($query, $settings = []): array {
        return $this->ai()->proposeSynonyms($query, $settings);
    }

    public function getPendingSynonyms($status = 'pending', $limit = 200): array {
        return $this->ai()->getPendingSynonyms($status, $limit);
    }

    public function markSynonymPending($id, $status): bool {
        return $this->ai()->markSynonymPending($id, $status);
    }

    public function warmQueryRuleCache($limit = 500): array {
        return $this->ai()->warmQueryRuleCache($limit);
    }

    public function getProductIndexData($product_id) {
        return $this->index()->getProductIndexData($product_id);
    }

    public function getBulkProductIndexData(array $productIds): array {
        return $this->index()->getBulkProductIndexData($productIds);
    }

    public function indexProduct($product_id, $settings): bool {
        return $this->index()->indexProduct($product_id, $settings);
    }

    public function deleteProduct($product_id, $settings): bool {
        return $this->index()->deleteProduct($product_id, $settings);
    }

    public function bulkIndexProducts(array $productIds, array $settings): int {
        return $this->index()->bulkIndexProducts($productIds, $settings);
    }

    public function ensureSearchIndex($settings): void {
        $this->index()->ensureSearchIndex($settings);
    }

    public function getIndexDocumentsCount($settings = []): int {
        return $this->index()->getIndexDocumentsCount($settings);
    }

    public function queueEmbedding($product_id): void {
        $this->index()->queueEmbedding($product_id);
    }

    public function queueAllProductsForEmbedding(): int {
        return $this->index()->queueAllProductsForEmbedding();
    }

    public function queueMissingProductsForEmbedding(): int {
        return $this->index()->queueMissingProductsForEmbedding();
    }

    public function processEmbeddingQueue($limit = 100): int {
        return $this->index()->processEmbeddingQueue($limit);
    }

    // ── Vector (semantic) search ─────────────────────────────────────────────

    public function ensureVectorIndex(array $settings, $recreate = false): void {
        $this->index()->ensureVectorIndex($settings, $recreate);
    }

    public function syncVectorsToManticore(array $settings): int {
        return $this->index()->syncVectorsToManticore($settings);
    }

    /**
     * Semantic search: embed the query and run Manticore KNN.
     * @return array  [product_id => cosine_similarity], nearest first.
     */
    public function semanticSearch($query, array $settings, $k = 200): array {
        $query = trim((string)$query);
        if ($query === '' || empty($settings['vector_enabled']) || !$this->catalogAllowed()) {
            return [];
        }

        $provider = strtolower((string)($settings['ai_provider'] ?? 'openai'));
        $model    = trim((string)($settings['ai_embedding_model'] ?? 'text-embedding-3-large'));
        $apiKey   = $this->ai()->decryptRuntimeSecret((string)($settings['ai_api_key'] ?? ''));
        if ($apiKey === '' || $model === '') {
            return [];
        }

        // Cache the query embedding (APCu) — avoids an OpenAI call per identical search.
        $qvec     = null;
        $cacheKey = 'asp_qvec_' . md5($model . '|' . mb_strtolower($query));
        $useApcu  = function_exists('apcu_fetch');
        if ($useApcu) {
            $ok = false;
            $cached = apcu_fetch($cacheKey, $ok);
            if ($ok && is_array($cached) && $cached) {
                $qvec = $cached;
            }
        }
        if ($qvec === null) {
            $qvec = $this->ai()->generateEmbeddingVector($query, $provider, $apiKey, $model);
            if (!$qvec) {
                return [];
            }
            if ($useApcu) {
                apcu_store($cacheKey, $qvec, 3600);
            }
        }

        $minScore = (float)($settings['vector_min_score'] ?? 0.35);
        try {
            return $this->index()->knnSearch($qvec, $k, $minScore, $settings);
        } catch (\Throwable $e) {
            $this->logSearchError('Vector KNN search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Blend a lexical (text) ranked id list with semantic scores via weighted RRF.
     * $vectorRatioPercent — 0..100, share of the vector signal.
     * @return int[]  merged product ids, best first (union of both lists).
     */
    public function blendVectorResults(array $textIds, array $semScores, $vectorRatioPercent): array {
        $ratio = max(0.0, min(1.0, (float)$vectorRatioPercent / 100));
        $k     = 60;
        $scores = [];

        $rank = 0;
        foreach ($textIds as $id) {
            $id = (int)$id;
            $scores[$id] = ($scores[$id] ?? 0.0) + (1.0 - $ratio) * (1.0 / ($k + $rank + 1));
            $rank++;
        }
        $rank = 0;
        foreach ($semScores as $id => $cos) {
            $id = (int)$id;
            $scores[$id] = ($scores[$id] ?? 0.0) + $ratio * (1.0 / ($k + $rank + 1));
            $rank++;
        }

        arsort($scores);
        return array_map('intval', array_keys($scores));
    }

    // -------------------------------------------------------------------------
    // Post-search filtering and pagination
    // -------------------------------------------------------------------------

    /**
     * Filter a pre-ranked list of product IDs by category, manufacturer, price,
     * stock, rating, and attribute constraints, then paginate.
     *
     * @param  int[]  $ids    Product IDs ordered by relevance (from search engine)
     * @param  array  $params Filter/pagination options:
     *   category_id, sub_category, manufacturer_id, price_min, price_max,
     *   stock, rating, attr (array), sort, order, start, limit,
     *   rank_stock_first, rank_boost_new, rank_boost_popular
     * @return array{ids: int[], total: int}
     */
    public function filterProductIds(array $ids, array $params = []): array {
        if (empty($ids)) {
            return ['ids' => [], 'total' => 0];
        }

        $db        = $this->db;
        $storeId   = (int)$this->config->get('config_store_id');
        $langId    = (int)$this->config->get('config_language_id');
        $limit     = isset($params['limit']) ? max(1, (int)$params['limit']) : 20;
        $start     = isset($params['start']) ? max(0, (int)$params['start']) : 0;
        $sort      = isset($params['sort'])  ? (string)$params['sort']       : '';
        $sortOrder = (!empty($params['order']) && strtoupper($params['order']) === 'DESC') ? 'DESC' : 'ASC';

        $safeIds = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        if (empty($safeIds)) {
            return ['ids' => [], 'total' => 0];
        }
        $idList = implode(',', $safeIds);

        $from  = "FROM `" . DB_PREFIX . "product` p"
               . " INNER JOIN `" . DB_PREFIX . "product_to_store` p2s"
               .   " ON (p.product_id = p2s.product_id AND p2s.store_id = '" . $storeId . "')"
               . " LEFT JOIN `" . DB_PREFIX . "product_description` pd"
               .   " ON (p.product_id = pd.product_id AND pd.language_id = '" . $langId . "')";

        $where = " WHERE p.product_id IN (" . $idList . ")"
               . "   AND p.status = '1'"
               . "   AND p.date_available <= NOW()";

        // Category filter
        $categoryId  = isset($params['category_id'])  ? (int)$params['category_id']    : 0;
        $subCategory = !empty($params['sub_category']);
        if ($categoryId) {
            if ($subCategory) {
                $catIds = [$categoryId];
                $this->collectSubCategoryIds($categoryId, $catIds);
                $where .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_category` p2c"
                        . "   WHERE p2c.product_id = p.product_id"
                        . "   AND p2c.category_id IN (" . implode(',', array_map('intval', $catIds)) . "))";
            } else {
                $where .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_to_category` p2c"
                        . "   WHERE p2c.product_id = p.product_id"
                        . "   AND p2c.category_id = '" . $categoryId . "')";
            }
        }

        // Manufacturer filter
        if (!empty($params['manufacturer_id'])) {
            $where .= " AND p.manufacturer_id = '" . (int)$params['manufacturer_id'] . "'";
        }

        // Price filter
        if (!empty($params['price_min'])) {
            $where .= " AND p.price >= '" . (float)$params['price_min'] . "'";
        }
        if (!empty($params['price_max'])) {
            $where .= " AND p.price <= '" . (float)$params['price_max'] . "'";
        }

        // Stock filter
        if (!empty($params['stock'])) {
            $where .= " AND p.quantity > 0";
        }

        // Rating filter
        if (!empty($params['rating'])) {
            $where .= " AND (SELECT IFNULL(AVG(r.rating), 0) FROM `" . DB_PREFIX . "review` r"
                    . "   WHERE r.product_id = p.product_id AND r.status = 1) >= '" . (int)$params['rating'] . "'";
        }

        // Attribute filters
        if (!empty($params['attr']) && is_array($params['attr'])) {
            foreach ($params['attr'] as $attrId => $attrText) {
                $attrText = trim((string)$attrText);
                if ($attrText !== '') {
                    $where .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_attribute` pa"
                            . "   WHERE pa.product_id = p.product_id"
                            . "   AND pa.attribute_id = '" . (int)$attrId . "'"
                            . "   AND pa.language_id = '" . $langId . "'"
                            . "   AND pa.text = '" . $db->escape($attrText) . "')";
                }
            }
        }

        // Total count
        $totalRes = $db->query("SELECT COUNT(*) AS total " . $from . $where);
        $total    = (int)($totalRes->row['total'] ?? 0);

        if ($total === 0) {
            return ['ids' => [], 'total' => 0];
        }

        // Ranking is layered so relevance is NEVER overridden by popularity:
        //   1. lead bucket  — stock-first (in-stock as a group), the only signal
        //                     allowed to precede relevance, and only because the
        //                     admin explicitly asked to surface in-stock first.
        //   2. primary      — engine relevance via FIELD(), or the chosen sort.
        //   3. tiebreakers  — "new" / "popular" break ties WITHIN equal primary
        //                     rank. They must trail relevance: adding raw p.viewed
        //                     up front made a popular shirt (5000 views) outrank
        //                     the exact match (50 views), which is the relevance
        //                     bug reported on autocomplete.
        $leadOrder = [];
        if (!empty($params['rank_stock_first'])) {
            $leadOrder[] = '(CASE WHEN p.quantity > 0 THEN 1 ELSE 0 END) DESC';
        }

        $tieOrder = [];
        if (!empty($params['rank_boost_new'])) {
            $tieOrder[] = '(CASE WHEN p.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) DESC';
        }
        if (!empty($params['rank_boost_popular'])) {
            $tieOrder[] = 'p.viewed DESC';
        }

        // Build ORDER BY
        $sortMap = [
            'p.sort_order' => 'p.sort_order ' . $sortOrder . ', pd.name ASC',
            'pd.name'      => 'pd.name '      . $sortOrder,
            'p.model'      => 'p.model '      . $sortOrder,
            'p.price'      => 'p.price '      . $sortOrder,
            'p.date_added' => 'p.date_added ' . $sortOrder,
            'rating'       => '(SELECT IFNULL(AVG(r.rating),0) FROM `' . DB_PREFIX . 'review` r'
                            . ' WHERE r.product_id = p.product_id AND r.status = 1) ' . $sortOrder,
        ];

        $primaryOrder = isset($sortMap[$sort])
            ? $sortMap[$sort]
            : 'FIELD(p.product_id, ' . $idList . ')'; // engine relevance order

        $orderBy = implode(', ', array_merge($leadOrder, [$primaryOrder], $tieOrder));

        $rows = $db->query(
            "SELECT p.product_id " . $from . $where
            . " ORDER BY " . $orderBy
            . " LIMIT " . $start . "," . $limit
        );

        $resultIds = [];
        foreach ($rows->rows as $row) {
            $resultIds[] = (int)$row['product_id'];
        }

        return ['ids' => $resultIds, 'total' => $total];
    }

    /**
     * Return the full list of product IDs matching the active filters, with
     * one dimension optionally excluded (facet-exclusion pattern). Used by
     * facets so each facet group is counted against the filters from OTHER
     * groups but not its own — which makes zero-count options like
     * "Adidas (0)" appear instead of disappearing.
     *
     * @param array  $ids        Universe of candidate IDs (from search engine).
     * @param array  $params     Same filter struct as filterProductIds.
     * @param string $excludeDim One of: '', 'category', 'manufacturer', 'price',
     *                           'stock', 'rating', or 'attr:<id>' for a single
     *                           attribute. Empty = full filter.
     * @return int[]
     */
    private function facetExclusionIds(array $ids, array $params, string $excludeDim = ''): array {
        if (empty($ids)) {
            return [];
        }
        $clone = $params;
        switch ($excludeDim) {
            case 'category':     unset($clone['category_id'], $clone['sub_category']); break;
            case 'manufacturer': unset($clone['manufacturer_id']); break;
            case 'price':        unset($clone['price_min'], $clone['price_max']);      break;
            case 'stock':        unset($clone['stock']);                                break;
            case 'rating':       unset($clone['rating']);                               break;
            default:
                if (strpos($excludeDim, 'attr:') === 0) {
                    $aid = (int)substr($excludeDim, 5);
                    if (!empty($clone['attr']) && is_array($clone['attr'])) {
                        unset($clone['attr'][$aid]);
                    }
                }
                break;
        }
        $clone['limit'] = count($ids);
        $clone['start'] = 0;
        $res = $this->filterProductIds($ids, $clone);
        return $res['ids'] ?? [];
    }

    /**
     * Compute search facets (categories, manufacturers, price range, stock, attributes)
     * for the given query and active filters.
     *
     * @param  array $params       Search query + active filter values
     * @param  array $engineParams Engine overrides (mode, etc.)
     * @return array{
     *   categories:    array,
     *   manufacturers: array,
     *   price:         array{min:float,max:float},
     *   stock:         array{in:int,out:int},
     *   attributes:    array
     * }
     */
    public function getEngineFacets(array $params = [], array $engineParams = []): array {
        $empty = [
            'categories'    => [],
            'manufacturers' => [],
            'price'         => ['min' => 0.0, 'max' => 0.0],
            'stock'         => ['in' => 0, 'out' => 0],
            'attributes'    => [],
        ];

        $query = trim((string)($params['search'] ?? $params['tag'] ?? ''));
        if ($query === '') {
            return $empty;
        }

        $mode   = isset($engineParams['mode']) ? (string)$engineParams['mode'] : 'native';
        $engine = $this->getSearchEngine(array_merge(['mode' => $mode], $engineParams));
        $raw    = $engine->search($query, 5000, 0);
        $ids    = $raw['ids'] ?? [];

        if (empty($ids)) {
            return $empty;
        }

        // Build the canonical filter struct once — reused for every facet
        // scope so that filter semantics stay consistent.
        $baseFilter = [
            'category_id'     => isset($params['category_id'])     ? (int)$params['category_id']    : 0,
            'sub_category'    => !empty($params['sub_category']),
            'manufacturer_id' => isset($params['manufacturer_id']) ? (int)$params['manufacturer_id'] : 0,
            'price_min'       => isset($params['price_min'])       ? (float)$params['price_min']    : 0,
            'price_max'       => isset($params['price_max'])       ? (float)$params['price_max']    : 0,
            'stock'           => !empty($params['in_stock']),
            'rating'          => isset($params['rating'])          ? (int)$params['rating']         : 0,
            'attr'            => isset($params['attr'])            ? (array)$params['attr']         : [],
        ];

        // Facet-exclusion: for each dimension, drop *that* dimension from the
        // filter so the dimension's counts reflect "what would I get if I
        // changed THIS choice while keeping all other filters?" — that's what
        // produces familiar "Nike (42) / Adidas (0)" displays.
        $idsByDim = [
            'category'     => $this->facetExclusionIds($ids, $baseFilter, 'category'),
            'manufacturer' => $this->facetExclusionIds($ids, $baseFilter, 'manufacturer'),
            'price'        => $this->facetExclusionIds($ids, $baseFilter, 'price'),
            'stock'        => $this->facetExclusionIds($ids, $baseFilter, 'stock'),
            'rating'       => $this->facetExclusionIds($ids, $baseFilter, 'rating'),
        ];
        // Universe — every product the engine matched against the raw query,
        // before any active filter is applied. Drives the *display* list so
        // values like "Adidas (0)" still surface when the current filters
        // exclude them.
        $universeIds = array_values(array_unique(array_map('intval', $ids)));

        $langId  = (int)$this->config->get('config_language_id');
        $result  = $empty;

        // Categories — list ALL categories in the universe; counts derived
        // from idsByDim['category'] so options not in the filtered set show 0.
        $uniList = implode(',', $universeIds) ?: '0';
        $catSetList = implode(',', $idsByDim['category']) ?: '0';
        $catRes = $this->db->query(
            "SELECT p2c.category_id, cd.name,"
            . " SUM(CASE WHEN p2c.product_id IN (" . $catSetList . ") THEN 1 ELSE 0 END) AS cnt"
            . " FROM `" . DB_PREFIX . "product_to_category` p2c"
            . " INNER JOIN `" . DB_PREFIX . "category_description` cd"
            .   " ON (cd.category_id = p2c.category_id AND cd.language_id = '" . $langId . "')"
            . " INNER JOIN `" . DB_PREFIX . "category` c"
            .   " ON (c.category_id = p2c.category_id AND c.status = 1)"
            . " WHERE p2c.product_id IN (" . $uniList . ")"
            . " GROUP BY p2c.category_id"
            . " ORDER BY cnt DESC, cd.name ASC LIMIT 20"
        );
        foreach ($catRes->rows as $row) {
            $result['categories'][] = [
                'category_id' => (int)$row['category_id'],
                'name'        => $row['name'],
                'count'       => (int)$row['cnt'],
                'href'        => '',
            ];
        }

        // Manufacturers — same idea (facet exclusion + universe).
        $mfSetList = implode(',', $idsByDim['manufacturer']) ?: '0';
        $mfRes = $this->db->query(
            "SELECT p.manufacturer_id, m.name,"
            . " SUM(CASE WHEN p.product_id IN (" . $mfSetList . ") THEN 1 ELSE 0 END) AS cnt"
            . " FROM `" . DB_PREFIX . "product` p"
            . " INNER JOIN `" . DB_PREFIX . "manufacturer` m ON (m.manufacturer_id = p.manufacturer_id)"
            . " WHERE p.product_id IN (" . $uniList . ") AND p.manufacturer_id > 0"
            . " GROUP BY p.manufacturer_id"
            . " ORDER BY cnt DESC, m.name ASC LIMIT 20"
        );
        foreach ($mfRes->rows as $row) {
            $result['manufacturers'][] = [
                'manufacturer_id' => (int)$row['manufacturer_id'],
                'name'            => $row['name'],
                'count'           => (int)$row['cnt'],
                'href'            => '',
            ];
        }

        // Price range — taken from the price-excluded scope so the slider
        // shows the full range available before clamping by current price.
        $priceList = implode(',', $idsByDim['price']) ?: '0';
        $priceRes = $this->db->query(
            "SELECT MIN(p.price) AS pmin, MAX(p.price) AS pmax"
            . " FROM `" . DB_PREFIX . "product` p"
            . " WHERE p.product_id IN (" . $priceList . ")"
        );
        if (!empty($priceRes->row)) {
            $result['price'] = [
                'min' => (float)($priceRes->row['pmin'] ?? 0),
                'max' => (float)($priceRes->row['pmax'] ?? 0),
            ];
        }

        // Stock counts — counted on the stock-excluded scope.
        $stockList = implode(',', $idsByDim['stock']) ?: '0';
        $stockRes = $this->db->query(
            "SELECT (CASE WHEN p.quantity > 0 THEN 1 ELSE 0 END) AS in_stock, COUNT(*) AS cnt"
            . " FROM `" . DB_PREFIX . "product` p"
            . " WHERE p.product_id IN (" . $stockList . ")"
            . " GROUP BY in_stock"
        );
        foreach ($stockRes->rows as $row) {
            if ((int)$row['in_stock']) {
                $result['stock']['in'] = (int)$row['cnt'];
            } else {
                $result['stock']['out'] = (int)$row['cnt'];
            }
        }

        // Attribute facets — per-attribute exclusion. Each attribute's counts
        // are computed against the filter scope that DROPS that single
        // attribute, so the user can re-pick within the same attribute
        // without losing their other filters.
        $attrRes = $this->db->query(
            "SELECT DISTINCT pa.attribute_id, ad.name AS attr_name"
            . " FROM `" . DB_PREFIX . "product_attribute` pa"
            . " INNER JOIN `" . DB_PREFIX . "attribute_description` ad"
            .   " ON (ad.attribute_id = pa.attribute_id AND ad.language_id = '" . $langId . "')"
            . " WHERE pa.product_id IN (" . $uniList . ")"
            .   " AND pa.language_id = '" . $langId . "' AND pa.text <> ''"
            . " ORDER BY pa.attribute_id"
        );
        $attrMap = [];
        foreach ($attrRes->rows as $row) {
            $attrId   = (int)$row['attribute_id'];
            $perAttr  = $this->facetExclusionIds($ids, $baseFilter, 'attr:' . $attrId);
            $setList  = implode(',', $perAttr) ?: '0';

            $valRes = $this->db->query(
                "SELECT pa.text,"
                . " SUM(CASE WHEN pa.product_id IN (" . $setList . ") THEN 1 ELSE 0 END) AS cnt"
                . " FROM `" . DB_PREFIX . "product_attribute` pa"
                . " WHERE pa.attribute_id = '" . $attrId . "'"
                .   " AND pa.product_id IN (" . $uniList . ")"
                .   " AND pa.language_id = '" . $langId . "' AND pa.text <> ''"
                . " GROUP BY pa.text"
                . " ORDER BY cnt DESC, pa.text ASC"
                . " LIMIT 15"
            );
            $values = [];
            foreach ($valRes->rows as $vrow) {
                $values[] = [
                    'value' => $vrow['text'],
                    'count' => (int)$vrow['cnt'],
                    'href'  => '',
                ];
            }
            if ($values) {
                $attrMap[$attrId] = [
                    'attribute_id' => $attrId,
                    'name'         => $row['attr_name'],
                    'values'       => $values,
                ];
            }
        }
        $result['attributes'] = array_values($attrMap);

        return $result;
    }

    private function collectSubCategoryIds(int $parentId, array &$out): void {
        $res = $this->db->query(
            "SELECT category_id FROM `" . DB_PREFIX . "category` WHERE parent_id = '" . $parentId . "'"
        );
        foreach ($res->rows as $row) {
            $childId = (int)$row['category_id'];
            if (!in_array($childId, $out, true)) {
                $out[] = $childId;
                $this->collectSubCategoryIds($childId, $out);
            }
        }
    }
}
