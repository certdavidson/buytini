<?php
/**
 * Advanced Search Pro — AI Service
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

use OcKit\AdvancedSearchPro\Config\ModuleSettings;

class AiService {
    private $db;
    private $config;
    private $searchService;

    public function __construct($registry) {
        $this->db            = $registry->get('db');
        $this->config        = $registry->get('config');
        $this->searchService = new SearchService($registry);
    }

    public function decryptRuntimeSecret($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        if (strpos($value, 'enc:') !== 0) {
            return $value;
        }

        $cipher = substr($value, 4);
        if ($cipher === '') {
            return '';
        }

        if (!class_exists('Encryption')) {
            require_once(DIR_SYSTEM . 'library/encryption.php');
        }
        $encryption = new Encryption();
        $key = (string)$this->config->get('config_encryption');
        if ($key === '') {
            return '';
        }

        return $encryption->decrypt($key, $cipher);
    }

    public function estimateTokens($text) {
        $text = (string)$text;
        if ($text === '') {
            return 0;
        }

        $chars = utf8_strlen($text);
        return max(1, (int)ceil($chars / 4));
    }

    public function estimateEmbeddingCost($model, $tokens) {
        $tokens = max(0, (int)$tokens);
        if ($tokens === 0) {
            return 0.0;
        }

        $model = strtolower(trim((string)$model));
        $costPer1k = 0.00013; // default close to low-cost embedding models

        if ($model === 'text-embedding-3-large') {
            $costPer1k = 0.00013;
        } elseif ($model === 'text-embedding-3-small') {
            $costPer1k = 0.00002;
        }

        return round(($tokens / 1000) * $costPer1k, 6);
    }

    public function estimateTextCost($model, $tokens) {
        $tokens = max(0, (int)$tokens);
        if ($tokens === 0) {
            return 0.0;
        }

        $model = strtolower(trim((string)$model));
        $costPer1k = 0.0003;

        if (strpos($model, 'gpt-4o-mini') !== false) {
            $costPer1k = 0.0003;
        } elseif (strpos($model, 'claude') !== false) {
            $costPer1k = 0.0005;
        } elseif (strpos($model, 'deepseek') !== false) {
            $costPer1k = 0.0002;
        }

        return round(($tokens / 1000) * $costPer1k, 6);
    }

    public function isAiBudgetExceeded($tokens, $cost, $settings) {
        $tokens = max(0, (int)$tokens);
        $cost = max(0.0, (float)$cost);
        $today = date('Y-m-d');

        $todayRow = $this->db->query(
            "SELECT ai_tokens, ai_cost
             FROM `" . DB_PREFIX . "asp_stats`
             WHERE `date` = '" . $this->db->escape($today) . "'
             LIMIT 1"
        )->row;

        $usedTokensToday = (int)($todayRow['ai_tokens'] ?? 0);
        $usedCostToday = (float)($todayRow['ai_cost'] ?? 0.0);

        $monthRow = $this->db->query(
            "SELECT SUM(ai_cost) AS total
             FROM `" . DB_PREFIX . "asp_stats`
             WHERE `date` >= DATE_SUB('" . $this->db->escape($today) . "', INTERVAL 29 DAY)"
        )->row;
        $usedCostMonth = (float)($monthRow['total'] ?? 0.0);

        $dailyLimit = max(0, (int)($settings['ai_budget_daily_limit'] ?? 0));
        $monthlyBudget = max(0.0, (float)($settings['ai_budget_monthly'] ?? 0.0));

        if ($dailyLimit > 0 && ($usedTokensToday + $tokens) > $dailyLimit) {
            return true;
        }

        if ($monthlyBudget > 0 && ($usedCostMonth + $cost) > $monthlyBudget) {
            return true;
        }

        // Also prevent unbounded daily spikes when budget is set but daily limit isn't.
        if ($dailyLimit === 0 && $monthlyBudget > 0 && ($usedCostToday + $cost) > ($monthlyBudget / 30)) {
            return true;
        }

        return false;
    }

    public function extractJsonObject($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public function deduplicateExpandedTerms($query, array $terms) {
        $result = [];
        foreach ($terms as $term) {
            $term = $this->searchService->normalizeQuery((string)$term);
            if ($term === '' || $term === $query) {
                continue;
            }
            if (mb_strlen($term) < 3) {
                continue;
            }
            $isDuplicate = false;
            foreach ($result as $accepted) {
                // Prefix match: one term is a prefix of the other.
                if (strpos($accepted, $term) === 0 || strpos($term, $accepted) === 0) {
                    $isDuplicate = true;
                    break;
                }
                // Levenshtein distance for short ASCII-compatible terms only.
                $lenA = strlen($accepted);
                $lenB = strlen($term);
                if ($lenA <= 32 && $lenB <= 32 && abs($lenA - $lenB) <= 2) {
                    if (levenshtein($accepted, $term) <= 2) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }
            if (!$isDuplicate) {
                $result[] = $term;
            }
        }
        return $result;
    }

    public function isApcuAvailable() {
        return function_exists('apcu_fetch')
            && (PHP_SAPI !== 'cli' ? ini_get('apc.enabled') : ini_get('apc.enable_cli'));
    }

    public function applyStoredQueryRule($query, $settings = []) {
        $query = $this->searchService->normalizeQuery($query);
        if ($query === '') {
            return ['query' => '', 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $settings = array_merge((new ModuleSettings($this->config))->get([
            'ai_expand_query' => 0,
            'ai_rewrite_query' => 0,
            'ai_intent_detection' => 0
        ]), $settings);

        $useApcu   = $this->isApcuAvailable();
        $cacheKey  = 'asp_qr_' . md5($query);
        $fromCache = false;
        $row       = false;

        if ($useApcu) {
            $ok  = false;
            $row = apcu_fetch($cacheKey, $ok);
            if (!$ok) {
                $row = false;
            } else {
                $fromCache = true;
            }
        }

        if ($row === false) {
            $row = $this->db->query(
                "SELECT *
                 FROM `" . DB_PREFIX . "asp_query_rule`
                 WHERE query_normalized = '" . $this->db->escape($query) . "'
                 LIMIT 1"
            )->row;

            if ($row && $useApcu) {
                apcu_store($cacheKey, $row, 300); // 5 min TTL
            }
        }

        if (!$row) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $resultQuery = $query;
        if (!empty($settings['ai_rewrite_query']) && !empty($row['rewritten_query'])) {
            $resultQuery = $this->searchService->normalizeQuery((string)$row['rewritten_query']);
        }

        $expanded = [];
        if (!empty($settings['ai_expand_query']) && !empty($row['expanded_json'])) {
            $json = json_decode((string)$row['expanded_json'], true);
            if (is_array($json)) {
                $expanded = $this->deduplicateExpandedTerms($query, $json);
            }
        }

        $intent = '';
        if (!empty($settings['ai_intent_detection']) && !empty($row['intent'])) {
            $intent = $this->searchService->normalizeQuery((string)$row['intent']);
        }

        // Increment hits counter only on DB hits (not on cache hits) to avoid per-request writes.
        if (!$fromCache) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "asp_query_rule`
                 SET hits = hits + 1, updated_at = NOW()
                 WHERE id = '" . (int)$row['id'] . "'"
            );
        }

        return [
            'query' => $resultQuery !== '' ? $resultQuery : $query,
            'expanded_terms' => $expanded,
            'intent' => $intent,
            'applied' => true
        ];
    }

    public function warmQueryRuleCache($limit = 500) {
        if (!$this->isApcuAvailable()) {
            return ['status' => 'apcu_unavailable', 'cached' => 0];
        }

        $limit = max(1, min(5000, (int)$limit));
        $rows  = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "asp_query_rule`
             ORDER BY hits DESC
             LIMIT " . $limit
        )->rows;

        $cached = 0;
        foreach ($rows as $row) {
            $key = 'asp_qr_' . md5((string)$row['query_normalized']);
            apcu_store($key, $row, 300);
            $cached++;
        }

        return ['status' => 'ok', 'cached' => $cached];
    }

    public function saveQueryRule($query, $rewrite = '', array $expanded = [], $intent = '', $source = 'manual') {
        $query = $this->searchService->normalizeQuery($query);
        if ($query === '') {
            return false;
        }

        $rewrite = $this->searchService->normalizeQuery((string)$rewrite);
        $intent = $this->searchService->normalizeQuery((string)$intent);
        $source = trim((string)$source);
        if ($source === '') {
            $source = 'manual';
        }

        $cleanExpanded = array_slice($this->deduplicateExpandedTerms($query, $expanded), 0, 12);

        // Pollution guard — never store a rule that contributes nothing or
        // that's wildly broader than the original query. Manual rules bypass
        // these checks because the admin explicitly chose the values.
        $isAiSource = strpos($source, 'ai') === 0;
        if ($isAiSource) {
            $hasUsefulRewrite = ($rewrite !== '' && $rewrite !== $query);
            $hasUsefulExpand  = !empty($cleanExpanded);
            $hasUsefulIntent  = ($intent !== '');
            if (!$hasUsefulRewrite && !$hasUsefulExpand && !$hasUsefulIntent) {
                return false;
            }

            // Reject overly-broad rewrites — AI sometimes returns whole
            // category descriptions ("чорні кеди" → "взуття чоловіче спортивне
            // кросівки кеди для бігу..."). Cap at 10 tokens AND length-ratio
            // ≤ 5× original, otherwise drop the rewrite but keep expand/intent.
            if ($hasUsefulRewrite) {
                $rewriteTokens = preg_split('/\s+/u', $rewrite, -1, PREG_SPLIT_NO_EMPTY);
                $queryLen = max(1, mb_strlen($query, 'UTF-8'));
                $rewriteLen = mb_strlen($rewrite, 'UTF-8');
                if (count($rewriteTokens) > 10 || $rewriteLen > $queryLen * 5) {
                    $rewrite = '';
                    $hasUsefulRewrite = false;
                    if (!$hasUsefulExpand && !$hasUsefulIntent) {
                        return false;
                    }
                }
            }
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_query_rule`
             SET query_normalized = '" . $this->db->escape($query) . "',
                 rewritten_query = '" . $this->db->escape($rewrite) . "',
                 expanded_json = '" . $this->db->escape(json_encode($cleanExpanded)) . "',
                 intent = '" . $this->db->escape($intent) . "',
                 source = '" . $this->db->escape($source) . "',
                 hits = 0,
                 created_at = NOW(),
                 updated_at = NOW()
             ON DUPLICATE KEY UPDATE
                 rewritten_query = VALUES(rewritten_query),
                 expanded_json = VALUES(expanded_json),
                 intent = VALUES(intent),
                 source = VALUES(source),
                 updated_at = NOW()"
        );

        return true;
    }

    public function generateQueryRules($limit = 100, $days = 30, $minCount = 2, $settings = []) {
        $limit    = max(1, min(1000, (int)$limit));
        $days     = max(1, min(365,  (int)$days));
        $minCount = max(1, min(1000, (int)$minCount));

        // Queries that already have a rule updated within this many days are skipped.
        $refreshDays  = max(1, min(365, (int)($settings['rule_refresh_days'] ?? 7)));
        // true → only process queries that returned zero results (users searched & found nothing).
        $noResultsOnly = !empty($settings['no_results_only']);

        $settings = array_merge((new ModuleSettings($this->config))->get([
            'ai_provider'        => 'openai',
            'ai_api_key'         => '',
            'ai_model'           => 'gpt-4o-mini',
            'ai_expand_query'    => 1,
            'ai_rewrite_query'   => 1,
            'ai_intent_detection'=> 1,
            'cross_lang_enabled' => 1,
        ]), $settings);

        // Build WHERE clause additions.
        $resultsFilter = $noResultsOnly
            ? "AND ql.`results` = 0"
            : "";

        // Skip queries that already have a fresh rule — avoids wasting AI budget on re-processing.
        $skipFresh = "AND NOT EXISTS (
                SELECT 1 FROM `" . DB_PREFIX . "asp_query_rule` r
                WHERE r.query_normalized = ql.`query`
                  AND r.updated_at >= DATE_SUB(NOW(), INTERVAL " . (int)$refreshDays . " DAY)
            )";

        $rows = $this->db->query(
            "SELECT ql.`query`, COUNT(*) AS total
             FROM `" . DB_PREFIX . "asp_query_log` ql
             WHERE ql.`query` <> ''
               AND ql.`created_at` >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
               " . $resultsFilter . "
               " . $skipFresh . "
             GROUP BY ql.`query`
             HAVING total >= '" . (int)$minCount . "'
             ORDER BY total DESC
             LIMIT " . (int)$limit
        );

        $created = 0;
        $failed  = 0;

        foreach ($rows->rows as $row) {
            $query = $this->searchService->normalizeQuery((string)$row['query']);
            if ($query === '') {
                continue;
            }

            try {
                $ai = $this->enhanceQueryWithAi($query, $settings);
                $ok = $this->saveQueryRule(
                    $query,
                    (string)($ai['query']          ?? $query),
                    (array) ($ai['expanded_terms'] ?? []),
                    (string)($ai['intent']         ?? ''),
                    $noResultsOnly ? 'ai_zero_results' : 'ai_generated'
                );
                if ($ok) {
                    $created++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return [
            'created' => $created,
            'failed'  => $failed,
            'total'   => count($rows->rows),
        ];
    }

    public function enhanceQueryWithAi($query, $settings = []) {
        $query = $this->searchService->normalizeQuery($query);
        if ($query === '') {
            return ['query' => '', 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $settings = array_merge((new ModuleSettings($this->config))->get([
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-4o-mini',
            'ai_expand_query' => 0,
            'ai_rewrite_query' => 0,
            'ai_intent_detection' => 0,
            'ai_budget_monthly' => 50,
            'ai_budget_daily_limit' => 1000,
            'ai_auto_block' => 1,
            'cross_lang_enabled' => 1,
            'ai_lang_hint' => '',
        ]), $settings);

        $needAi = !empty($settings['ai_expand_query']) || !empty($settings['ai_rewrite_query']) || !empty($settings['ai_intent_detection']);
        if (!$needAi) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $provider = strtolower((string)$settings['ai_provider']);
        $model = trim((string)$settings['ai_model']);
        $apiKey = $this->decryptRuntimeSecret((string)$settings['ai_api_key']);
        if ($apiKey === '' || $model === '') {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $defaultLangHint = "This is a Ukrainian e-commerce store where users also search in Russian. "
            . "When expanding queries: "
            . "1) always include both Ukrainian and Russian language variants; "
            . "2) if the query is a brand name transliteration or non-English brand name, always include the original English brand name in the expand array; "
            . "3) always include the base/root noun from the query as standalone terms in each relevant language (e.g. for 'чорні кеді' include standalone 'кеді', 'кеды', 'sneakers' — not only descriptive phrases). ";
        $langHint = !empty($settings['cross_lang_enabled'])
            ? (trim((string)($settings['ai_lang_hint'] ?? '')) ?: $defaultLangHint)
            : '';
        $systemPrompt = $langHint
            . "You optimize ecommerce search queries. Return strict JSON with keys: rewrite, expand, intent. "
            . "rewrite: rewrite stylistic, referential or descriptive queries into concrete, "
            . "catalog-searchable product terms. Strip references to people, brands-as-style, "
            . "characters or occasions and replace them with the actual product type plus its "
            . "searchable attributes. Examples: 'штани як у Бекхема' -> 'класичні чоловічі штани'; "
            . "'сукня як у принцеси' -> 'пишна вечірня сукня'; 'що взути на пробіжку' -> 'бігові кросівки'. "
            . "Keep the product type as the head noun. If the query is already a plain product query, "
            . "return it unchanged. rewrite must be a short string (max 6 words). "
            . "expand must be array of up to 10 terms, intent must be one short label.";
        $userPrompt = "Original query: " . $query . "\n"
            . "rewrite_enabled=" . (!empty($settings['ai_rewrite_query']) ? '1' : '0') . "\n"
            . "expand_enabled=" . (!empty($settings['ai_expand_query']) ? '1' : '0') . "\n"
            . "intent_enabled=" . (!empty($settings['ai_intent_detection']) ? '1' : '0');

        $estimatedTokens = $this->estimateTokens($systemPrompt . ' ' . $userPrompt);
        $estimatedCost = $this->estimateTextCost($model, $estimatedTokens);
        if (!empty($settings['ai_auto_block']) && $this->isAiBudgetExceeded($estimatedTokens, $estimatedCost, $settings)) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $raw = $this->callAiText($provider, $apiKey, $model, $systemPrompt, $userPrompt);
        if ($raw === '') {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $json = $this->extractJsonObject($raw);
        if (!$json) {
            return ['query' => $query, 'expanded_terms' => [], 'intent' => '', 'applied' => false];
        }

        $rewrite = $query;
        if (!empty($settings['ai_rewrite_query']) && !empty($json['rewrite'])) {
            $rewrite = $this->searchService->normalizeQuery((string)$json['rewrite']);
        }

        $expanded = [];
        if (!empty($settings['ai_expand_query']) && !empty($json['expand']) && is_array($json['expand'])) {
            foreach ($json['expand'] as $term) {
                $term = $this->searchService->normalizeQuery((string)$term);
                if ($term !== '' && $term !== $query && !in_array($term, $expanded, true)) {
                    $expanded[] = $term;
                }
                if (count($expanded) >= 10) {
                    break;
                }
            }
        }

        $intent = '';
        if (!empty($settings['ai_intent_detection']) && !empty($json['intent'])) {
            $intent = $this->searchService->normalizeQuery((string)$json['intent']);
        }

        $this->logAiUsage($estimatedTokens, $estimatedCost);

        return [
            'query' => $rewrite !== '' ? $rewrite : $query,
            'expanded_terms' => $expanded,
            'intent' => $intent,
            'applied' => true
        ];
    }

    public function generateEmbeddingVector($text, $provider, $apiKey, $model) {
        $provider = strtolower(trim((string)$provider));
        $apiKey = trim((string)$apiKey);
        if ($provider === '' || $apiKey === '' || $text === '') {
            return [];
        }

        $endpoint = '';
        $payload = '';
        $headers = ['Content-Type: application/json'];

        if ($provider === 'openai') {
            $endpoint = 'https://api.openai.com/v1/embeddings';
            $payload = json_encode([
                'model' => $model,
                'input' => $text
            ]);
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        } elseif ($provider === 'deepseek') {
            $endpoint = 'https://api.deepseek.com/v1/embeddings';
            $payload = json_encode([
                'model' => $model,
                'input' => $text
            ]);
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        } else {
            // Fallback: provider is not known for embeddings yet.
            return [];
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return [];
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return [];
        }

        if (!empty($json['data'][0]['embedding']) && is_array($json['data'][0]['embedding'])) {
            return array_map('floatval', $json['data'][0]['embedding']);
        }

        return [];
    }

    public function callAiText($provider, $apiKey, $model, $systemPrompt, $userPrompt) {
        $provider = strtolower(trim((string)$provider));
        $apiKey = trim((string)$apiKey);
        $model = trim((string)$model);
        if ($provider === '' || $apiKey === '' || $model === '') {
            return '';
        }

        $endpoint = '';
        $payload = '';
        $headers = ['Content-Type: application/json'];

        if ($provider === 'openai' || $provider === 'deepseek') {
            $endpoint = $provider === 'openai' ? 'https://api.openai.com/v1/chat/completions' : 'https://api.deepseek.com/v1/chat/completions';
            $payload = json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.1
            ]);
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        } elseif ($provider === 'claude') {
            $endpoint = 'https://api.anthropic.com/v1/messages';
            $payload = json_encode([
                'model' => $model,
                'max_tokens' => 300,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt]
                ]
            ]);
            $headers[] = 'x-api-key: ' . $apiKey;
            $headers[] = 'anthropic-version: 2023-06-01';
        } else {
            return '';
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return '';
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return '';
        }

        if (!empty($json['choices'][0]['message']['content'])) {
            return (string)$json['choices'][0]['message']['content'];
        }

        if (!empty($json['content'][0]['text'])) {
            return (string)$json['content'][0]['text'];
        }

        return '';
    }

    /**
     * Propose interchangeable synonyms for a single-word query.
     *
     * Strict pipeline: hard-rejects multi-word inputs (those belong to query
     * rules, not synonym groups). Calls AI with a synonym-only prompt and
     * applies guards before saving to `asp_synonym_pending` for admin review.
     *
     * @return array{status:string, message?:string, accepted?:int}
     */
    public function proposeSynonyms($query, $settings = []) {
        $query = $this->searchService->normalizeQuery($query);
        if ($query === '' || $this->isMultiWord($query)) {
            return ['status' => 'skipped', 'message' => 'not single-word'];
        }
        if (mb_strlen($query, 'UTF-8') < 2) {
            return ['status' => 'skipped', 'message' => 'too short'];
        }

        $settings = array_merge((new ModuleSettings($this->config))->get([
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-4o-mini',
            'ai_budget_monthly' => 50,
            'ai_budget_daily_limit' => 1000,
            'ai_auto_block' => 1,
        ]), $settings);

        $provider = strtolower((string)$settings['ai_provider']);
        $model    = trim((string)$settings['ai_model']);
        $apiKey   = $this->decryptRuntimeSecret((string)$settings['ai_api_key']);
        if ($apiKey === '' || $model === '') {
            return ['status' => 'skipped', 'message' => 'ai not configured'];
        }

        $systemPrompt =
            "You generate SYNONYMS for a single search term on a Ukrainian e-commerce store " .
            "(users also search in Russian and English). " .
            "A synonym is an INTERCHANGEABLE alternative for the SAME concept: " .
            "alternative spellings, transliterations, common typos, language variants " .
            "(uk/ru/en) of the same word. " .
            "STRICT RULES: " .
            "1) Never include broader categories (e.g. for 'nike' do NOT suggest 'sportswear' or 'shoes'). " .
            "2) Never include related products or accessories. " .
            "3) Never include descriptive attributes (color, size, style). " .
            "4) Each synonym must be a SINGLE word (no spaces, no multi-word phrases). " .
            "5) Do not repeat the original query in the output. " .
            "6) If you cannot find true interchangeable synonyms, return an empty array. " .
            "Return strict JSON: {\"synonyms\": [\"...\", \"...\"]}. Maximum 6 items.";
        $userPrompt = "Term: " . $query;

        $estimatedTokens = $this->estimateTokens($systemPrompt . ' ' . $userPrompt);
        $estimatedCost   = $this->estimateTextCost($model, $estimatedTokens);
        if (!empty($settings['ai_auto_block']) && $this->isAiBudgetExceeded($estimatedTokens, $estimatedCost, $settings)) {
            return ['status' => 'skipped', 'message' => 'budget exceeded'];
        }

        $raw = $this->callAiText($provider, $apiKey, $model, $systemPrompt, $userPrompt);
        if ($raw === '') {
            return ['status' => 'error', 'message' => 'ai empty response'];
        }
        $json = $this->extractJsonObject($raw);
        if (!$json || empty($json['synonyms']) || !is_array($json['synonyms'])) {
            return ['status' => 'skipped', 'message' => 'no synonyms returned'];
        }

        $accepted = $this->filterSynonymCandidates($query, $json['synonyms']);
        $this->logAiUsage($estimatedTokens, $estimatedCost);

        if (empty($accepted)) {
            return ['status' => 'skipped', 'message' => 'all candidates rejected'];
        }

        $this->upsertSynonymPending($query, $accepted, 'ai');
        return ['status' => 'ok', 'accepted' => count($accepted), 'terms' => $accepted];
    }

    /**
     * Apply input guards on raw AI output before storing in pending queue.
     *
     * Guards: ≤ 6 items, single-word only, ≥ 2 chars, not a stopword,
     * not a substring of input, not the input itself, unique.
     */
    public function filterSynonymCandidates($query, array $candidates) {
        $stopwords = $this->getSynonymStopwords();
        $out = [];
        $seen = [mb_strtolower($query, 'UTF-8') => true];
        foreach ($candidates as $term) {
            $term = $this->searchService->normalizeQuery((string)$term);
            if ($term === '' || $this->isMultiWord($term)) {
                continue;
            }
            if (mb_strlen($term, 'UTF-8') < 2) {
                continue;
            }
            $lower = mb_strtolower($term, 'UTF-8');
            if (isset($seen[$lower]) || isset($stopwords[$lower])) {
                continue;
            }
            // Reject substring matches in either direction — synonyms must be distinct words,
            // not morphological variants. "nike" ⊂ "nikee" or "nik" ⊂ "nike" both blocked.
            $lowerQuery = mb_strtolower($query, 'UTF-8');
            if (mb_strpos($lower, $lowerQuery) !== false || mb_strpos($lowerQuery, $lower) !== false) {
                continue;
            }
            $seen[$lower] = true;
            $out[] = $term;
            if (count($out) >= 6) {
                break;
            }
        }
        return $out;
    }

    private function isMultiWord($term) {
        return (bool)preg_match('/\s/u', trim((string)$term));
    }

    private function getSynonymStopwords() {
        // Common particles/articles in uk/ru/en that should never be synonyms.
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $list = [
            'і','та','а','але','або','чи','же','бо','на','в','у','з','до','для','по','за',
            'и','а','но','или','же','бы','на','в','у','с','до','для','по','за','к','от','об',
            'a','an','the','and','or','but','of','for','to','in','on','at','by','with','as'
        ];
        $cached = [];
        foreach ($list as $w) {
            $cached[$w] = true;
        }
        return $cached;
    }

    private function upsertSynonymPending($query, array $terms, $source) {
        $created = date('Y-m-d H:i:s');
        $payload = json_encode(array_values($terms), JSON_UNESCAPED_UNICODE);
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_synonym_pending`
                SET `query` = '" . $this->db->escape($query) . "',
                    `terms_json` = '" . $this->db->escape($payload) . "',
                    `source` = '" . $this->db->escape($source) . "',
                    `status` = 'pending',
                    `created_at` = '" . $created . "'
             ON DUPLICATE KEY UPDATE
                    `terms_json` = VALUES(`terms_json`),
                    `status` = 'pending',
                    `reviewed_at` = NULL"
        );
    }

    /**
     * Return pending synonym proposals, optionally filtered by status.
     *
     * @return array<int, array{id:int, query:string, terms:array, status:string, created_at:string}>
     */
    public function getPendingSynonyms($status = 'pending', $limit = 200) {
        $status = (string)$status;
        $limit  = max(1, min(1000, (int)$limit));
        $sql = "SELECT `id`, `query`, `terms_json`, `status`, `created_at`
                FROM `" . DB_PREFIX . "asp_synonym_pending`";
        if ($status !== '') {
            $sql .= " WHERE `status` = '" . $this->db->escape($status) . "'";
        }
        $sql .= " ORDER BY `id` DESC LIMIT " . $limit;

        $rows = $this->db->query($sql)->rows;
        $out = [];
        foreach ($rows as $row) {
            $terms = json_decode((string)$row['terms_json'], true);
            $out[] = [
                'id'         => (int)$row['id'],
                'query'      => (string)$row['query'],
                'terms'      => is_array($terms) ? $terms : [],
                'status'     => (string)$row['status'],
                'created_at' => (string)$row['created_at'],
            ];
        }
        return $out;
    }

    public function markSynonymPending($id, $status) {
        $id     = (int)$id;
        $status = in_array($status, ['accepted', 'rejected', 'pending'], true) ? $status : 'rejected';
        if ($id <= 0) {
            return false;
        }
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "asp_synonym_pending`
                SET `status` = '" . $this->db->escape($status) . "',
                    `reviewed_at` = NOW()
              WHERE `id` = " . $id
        );
        return true;
    }

    private function logAiUsage($tokens, $cost) {
        $tokens = max(0, (int)$tokens);
        $cost   = max(0.0, (float)$cost);
        $date   = date('Y-m-d');
        $this->db->query("INSERT INTO `" . DB_PREFIX . "asp_stats`
            SET `date` = '" . $this->db->escape($date) . "',
                `queries` = 0,
                `no_results` = 0,
                `avg_latency_ms` = 0,
                `p95_latency_ms` = 0,
                `cache_hit_percent` = 0,
                `errors` = 0,
                `ai_tokens` = '" . $tokens . "',
                `ai_cost` = '" . $cost . "'
            ON DUPLICATE KEY UPDATE
                `ai_tokens` = `ai_tokens` + " . $tokens . ",
                `ai_cost` = `ai_cost` + " . $cost);
    }

}
