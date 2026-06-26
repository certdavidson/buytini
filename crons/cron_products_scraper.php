<?php
/**
 * Products Scraper Pro — Cron Script
 *
 * Processes a batch of pending enrichment jobs.
 * Pipeline per job:
 *   1. Google Search (or manual URL) → fetch HTML/PDF
 *   2. Donor config check → selector extract (skip AI) or DataExtractor (AI)
 *   3. moderation_mode=0 → ProductEnricher → done → optional Translater Pro
 *      moderation_mode=1 → save extracted_data, status=moderation
 *   4. After batch → optional Telegram notification
 *
 * CLI params:
 *   --batch=N   Override cron_batch_size from settings
 *
 * Crontab example:
 *   * * * * * php /var/www/www-root/data/www/buytini.com/crons/cron_products_scraper.php
 *
 * @package   OcKit\ProductsScraper
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

chdir(dirname(__FILE__));

require_once '../config.php';
require_once DIR_SYSTEM . '../startup.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

$registry = new Registry();

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);

$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0");
foreach ($query->rows as $result) {
    $config->set(
        $result['key'],
        $result['serialized'] ? json_decode($result['value'], true) : $result['value']
    );
}

// ── Check module status ───────────────────────────────────────────────────────

$prefix = 'module_oc_kit_products_scraper_';

if (!$config->get($prefix . 'status')) {
    echo ts() . " Products Scraper: module disabled — skip.\n";
    exit(0);
}

// ── Load library ──────────────────────────────────────────────────────────────

require_once DIR_SYSTEM . 'library/ockit/products_scraper/ProductsScraper.php';
require_once DIR_SYSTEM . 'library/ockit/products_scraper/libs/SelectorExtractor.php';
require_once DIR_SYSTEM . 'library/ockit/products_scraper/libs/TranslaterProBridge.php';
require_once DIR_SYSTEM . 'library/ockit/products_scraper/libs/TelegramNotifier.php';

use OcKit\ProductsScraper\ProductsScraper;
use OcKit\ProductsScraper\Libs\SelectorExtractor;
use OcKit\ProductsScraper\Libs\TranslaterProBridge;
use OcKit\ProductsScraper\Libs\TelegramNotifier;

$lib = new ProductsScraper($registry);

// ── Build config array from store settings ────────────────────────────────────

$cfg = [];
$keys = [
    'status', 'moderation_mode',
    'search_provider', 'search_results_count', 'search_gl', 'search_hl', 'search_query_template',
    'serper_api_key', 'brave_api_key', 'bing_api_key', 'google_api_key', 'google_cx',
    'ai_provider', 'openai_api_key', 'openai_model', 'deepseek_api_key', 'deepseek_model',
    'claude_api_key', 'claude_model', 'gemini_api_key', 'gemini_model',
    'fetch_timeout', 'fetch_throttle_ms', 'max_html_length',
    'use_markdown', 'fields_to_fill', 'overwrite_existing', 'default_language_id',
    'auto_translate', 'translate_language_ids', 'cron_batch_size', 'default_attribute_group_id',
    'auto_enrich_new_products', 'auto_enrich_days', 'telegram_notify', 'telegram_bot_token',
    'telegram_chat_id',
    'scrape_attr_create_new', 'auto_create_donor', 'skip_if_no_attributes', 'auto_blacklist_no_attributes',
    'skip_if_has_attributes', 'attributes_mode',
    'min_content_length', 'first_donor_html', 'moderation_hide_inactive',
    'scrape_image_main', 'scrape_image_additional', 'scrape_image_additional_count', 'scrape_image_dir', 'min_image_size',
    'images_mode',
];
foreach ($keys as $key) {
    $cfg[$key] = $config->get($prefix . $key);
}

// Per-field write modes (authoritative); derive fields_to_fill for downstream code.
$fieldKeys = ['description', 'meta_title', 'meta_description', 'meta_keyword',
              'tag', 'attributes', 'upc', 'ean', 'jan', 'mpn'];
$savedModes = $config->get($prefix . 'field_modes');
$fieldModes = [];
if (is_array($savedModes) && $savedModes) {
    foreach ($fieldKeys as $fk) {
        $m = $savedModes[$fk] ?? 'off';
        $fieldModes[$fk] = in_array($m, ['off', 'fill', 'overwrite'], true) ? $m : 'off';
    }
} else {
    $legacyFill = (array)($cfg['fields_to_fill'] ?: ['description', 'meta_title', 'meta_description', 'attributes']);
    $legacyOver = (bool)($cfg['overwrite_existing'] ?? false);
    foreach ($fieldKeys as $fk) {
        $fieldModes[$fk] = in_array($fk, $legacyFill, true) ? ($legacyOver ? 'overwrite' : 'fill') : 'off';
    }
}
$cfg['field_modes']    = $fieldModes;
$cfg['fields_to_fill'] = array_values(array_filter($fieldKeys, fn($fk) => $fieldModes[$fk] !== 'off'));

// ── CLI overrides ─────────────────────────────────────────────────────────────

$cliOpts = getopt('', ['batch:']);
$batchSize = isset($cliOpts['batch'])
    ? max(1, (int)$cliOpts['batch'])
    : max(1, (int)($cfg['cron_batch_size'] ?? 5));

// ── Auto-enqueue new products without description ─────────────────────────────

if (!empty($cfg['auto_enrich_new_products'])) {
    $days       = max(1, (int)($cfg['auto_enrich_days'] ?? 7));
    $languageId = (int)($cfg['default_language_id'] ?? 0);

    $newProducts = $db->query(
        "SELECT p.product_id
         FROM `" . DB_PREFIX . "product` p
         LEFT JOIN `" . DB_PREFIX . "product_description` pd
           ON pd.product_id = p.product_id AND pd.language_id = '" . (int)$languageId . "'
         WHERE p.date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
           AND (pd.description IS NULL OR pd.description = '')
           AND NOT EXISTS (
               SELECT 1 FROM `" . DB_PREFIX . "products_scraper_job` j
               WHERE j.product_id = p.product_id
                 AND j.status IN ('pending','running')
           )
         LIMIT 100"
    );

    $autoQueued = 0;
    foreach ($newProducts->rows as $row) {
        $lib->getJobQueue()->createOrGetExisting((int)$row['product_id'], $languageId);
        $autoQueued++;
    }

    if ($autoQueued > 0) {
        echo ts() . " Auto-queued {$autoQueued} new product(s) without description.\n";
    }
}

// ── Get pending jobs ──────────────────────────────────────────────────────────

$jobs = $lib->getJobQueue()->getPending($batchSize);

if (empty($jobs)) {
    echo ts() . " No pending jobs — skip.\n";
    exit(0);
}

echo ts() . " Starting batch: " . count($jobs) . " job(s).\n";

// ── Prepare services ──────────────────────────────────────────────────────────

$jobQueue        = $lib->getJobQueue();
$logger          = $lib->getDbLogger();
$searchClient    = $lib->makeSearchClient($cfg);
$fetcher         = $lib->makePageFetcher($cfg);
$dataExtractor   = $lib->makeDataExtractor($cfg);
$enricher        = $lib->makeProductEnricher($cfg);
$selectorExt     = new SelectorExtractor();
$translaterBridge = new TranslaterProBridge($registry, $logger);

$minContentLength  = max(0, (int)($cfg['min_content_length'] ?? 3000));
$fieldsToFill      = (array)($cfg['fields_to_fill']       ?? ['description', 'meta_title', 'meta_description', 'attributes']);
$overwriteExisting = (bool)($cfg['overwrite_existing']    ?? false);
$moderationMode    = (bool)($cfg['moderation_mode']       ?? false);
$scrapeImageMain   = (bool)($cfg['scrape_image_main']     ?? false);
$scrapeImageAdditional = (bool)($cfg['scrape_image_additional'] ?? false);
$imageAdditionalCount  = max(1, (int)($cfg['scrape_image_additional_count'] ?? 5));
$imageDir          = rtrim($cfg['scrape_image_dir'] ?? 'catalog/scraped/{product_id}', '/');
$autoTranslate     = (bool)($cfg['auto_translate']        ?? false);
$sourceLangCode    = (string)($cfg['default_language_id'] ?? ''); // resolved below
$targetLangCodes   = (array)($cfg['translate_language_ids'] ?? []);
$languageId        = (int)($cfg['default_language_id']    ?? 0);

// Resolve language code for Translater Pro (needs 'uk-ua' not language_id int)
$langRow = $db->query(
    "SELECT `code` FROM `" . DB_PREFIX . "language`
     WHERE `language_id` = '" . (int)$languageId . "' LIMIT 1"
);
$sourceLangCode = $langRow->row['code'] ?? 'uk-ua';

// Resolve language name for AI prompt
$langNameRow = $db->query(
    "SELECT `name` FROM `" . DB_PREFIX . "language`
     WHERE `language_id` = '" . (int)$languageId . "' LIMIT 1"
);
$languageName = $langNameRow->row['name'] ?? 'Ukrainian';

// ── Process jobs ──────────────────────────────────────────────────────────────

$stats = ['done' => 0, 'moderation' => 0, 'skipped' => 0, 'error' => 0];

foreach ($jobs as $job) {
    $jobId     = $job->jobId;
    $productId = $job->productId;

    echo ts() . " Job #{$jobId} product #{$productId}…\n";

    $jobQueue->markRunning($jobId);

    try {
        // ── 0. Fetch product row (always needed for AI prompt name + images) ──

        $product = $db->query(
            "SELECT p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.mpn, pd.name, m.name AS manufacturer
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
               ON pd.product_id = p.product_id AND pd.language_id = '" . (int)$languageId . "'
             LEFT JOIN `" . DB_PREFIX . "manufacturer` m
               ON m.manufacturer_id = p.manufacturer_id
             WHERE p.product_id = '" . (int)$productId . "' LIMIT 1"
        )->row;

        if (empty($product)) {
            throw new \RuntimeException("Product #{$productId} not found in DB");
        }

        // ── 0b. Skip products that already have attributes (saves API calls) ──
        if (!empty($cfg['skip_if_has_attributes']) && $lib->productHasAttributes($productId, $languageId)) {
            $jobQueue->markSkipped($jobId);
            $logger->info($jobId, 'skipped_has_attributes', ['product_id' => $productId]);
            $stats['skipped']++;
            echo ts() . " Job #{$jobId} → skipped (product already has attributes).\n";
            continue;
        }

        // ── 1. Fetch content & 2. Extract data ───────────────────────────────
        // Each candidate URL is tried in order. On fetch error or missing attributes
        // (when skip_if_no_attributes is enabled) the next URL is attempted.

        $scraped            = null;
        $sourceUrl          = '';
        $aiResponseRaw      = '';
        $aiProvider         = $cfg['ai_provider'] ?? 'openai';
        $aiModel            = $cfg[$aiProvider . '_model'] ?? '';

        $skipIfNoAttributes  = !empty($cfg['skip_if_no_attributes'])
            && in_array('attributes', $fieldsToFill, true);
        $autoBlacklistNoAttrs = !empty($cfg['auto_blacklist_no_attributes']);
        $triedNoAttrUrls      = [];

        if ($job->manualUrl) {
            // Manual URL: single attempt, no fallback to other URLs
            $logger->info($jobId, 'manual_url', ['url' => $job->manualUrl]);
            $candidateUrls = [$job->manualUrl];
        } else {
            // ── Google Search ─────────────────────────────────────────────────
            $queryTemplate   = trim((string)($cfg['search_query_template'] ?? ''));
            $customColumns   = [];

            // Detect custom oc_product column masks (anything not in the standard list and not attribute_NNN)
            if ($queryTemplate !== '') {
                $standardMasks = ['name', 'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn'];
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $queryTemplate, $found);
                foreach ($found[1] as $mask) {
                    if (!in_array(strtolower($mask), $standardMasks, true)
                        && !preg_match('/^attribute_\d+$/', strtolower($mask))
                    ) {
                        if (preg_match('/^[a-zA-Z0-9_]+$/', $mask)) {
                            $customColumns[] = $mask;
                        }
                    }
                }
            }

            // Merge any custom oc_product columns into the already-fetched $product row
            if (!empty($customColumns)) {
                $customSelect = '';
                foreach ($customColumns as $col) {
                    $customSelect .= ", p.`" . $col . "`";
                }
                $extraRow = $db->query(
                    "SELECT {$customSelect} FROM `" . DB_PREFIX . "product`
                     WHERE `product_id` = '" . (int)$productId . "' LIMIT 1"
                )->row;
                $product = array_merge($product, $extraRow ?: []);
            }

            $identifier = $product['upc'] ?: $product['ean'] ?: $product['jan'] ?: null;

            // Resolve search query — from template or default buildQuery
            if ($queryTemplate !== '') {
                $productData = [
                    'name'         => $product['name']         ?? '',
                    'model'        => $product['model']        ?? '',
                    'sku'          => $product['sku']          ?? '',
                    'upc'          => $product['upc']          ?? '',
                    'ean'          => $product['ean']          ?? '',
                    'jan'          => $product['jan']          ?? '',
                    'isbn'         => $product['isbn']         ?? '',
                    'mpn'          => $product['mpn']          ?? '',
                    'manufacturer' => $product['manufacturer'] ?? '',
                ];
                // Merge custom oc_product columns
                foreach ($customColumns as $col) {
                    $productData[strtolower($col)] = $product[$col] ?? '';
                }
                // Merge attribute values for {attribute_NNN} masks (lookup by attribute_id)
                preg_match_all('/\{attribute_(\d+)\}/i', $queryTemplate, $attrMatches);
                if (!empty($attrMatches[1])) {
                    $attrIds = array_unique(array_map('intval', $attrMatches[1]));
                    $attrIdList = implode(',', $attrIds);
                    $attrRows = $db->query(
                        "SELECT pa.attribute_id, pa.`text` AS attr_val
                         FROM `" . DB_PREFIX . "product_attribute` pa
                         WHERE pa.product_id  = '" . (int)$productId . "'
                           AND pa.language_id = '" . (int)$languageId . "'
                           AND pa.attribute_id IN ({$attrIdList})"
                    )->rows;
                    foreach ($attrRows as $attrRow) {
                        $productData['attribute_' . $attrRow['attribute_id']] = $attrRow['attr_val'];
                    }
                }
                $resolved = \OcKit\ProductsScraper\Libs\AbstractSearchClient::resolveTemplate($queryTemplate, $productData);
                if ($resolved !== '') {
                    $searchClient->setPrebuiltQuery($resolved);
                }
            }

            $searchQuery = $searchClient->buildQuery($product['name'], $product['model'], $identifier);

            $logger->info($jobId, 'search', ['query' => $searchQuery]);

            $results = $searchClient->search($product['name'], $product['model'], $identifier);

            $logger->info($jobId, 'search_results', [
                'count' => count($results),
                'urls'  => array_map(fn($r) => $r->url, $results),
            ]);

            $jobQueue->saveSearchResults($jobId, $searchQuery, array_map(fn($r) => $r->toArray(), $results));

            if (empty($results)) {
                throw new \RuntimeException("No search results found for: {$searchQuery}");
            }

            // Prefer priority donor URLs if any appear in results
            $priorityDomains = $lib->getDonorConfig()->getPriorityDomains();
            if (!empty($priorityDomains)) {
                $donorConfig = $lib->getDonorConfig();
                $priorityResults = array_filter($results, function ($r) use ($priorityDomains, $donorConfig) {
                    return in_array($donorConfig->extractDomain($r->url), $priorityDomains, true);
                });
                if (!empty($priorityResults)) {
                    // Put priority results first, then the rest
                    $results = array_values(array_merge($priorityResults, array_diff($results, $priorityResults)));
                    $logger->info($jobId, 'priority_donor', ['url' => reset($priorityResults)->url]);
                }
            }

            $candidateUrls = array_map(fn($r) => $r->url, $results);
        }

        // ── Try each candidate URL in order ───────────────────────────────────

        $lastError = '';

        foreach ($candidateUrls as $candidateUrl) {
            // Fetch
            try {
                $fetched = $fetcher->fetchUrl($candidateUrl);
            } catch (\OcKit\ProductsScraper\Exceptions\ProductsScraperException $e) {
                $lastError = $e->getMessage();
                $logger->info($jobId, 'fetch_error_skip', ['url' => $candidateUrl, 'error' => $lastError]);
                continue;
            }

            // Skip SPA/JS pages that are too short (search results only)
            if (!$job->manualUrl && $minContentLength > 0 && !$fetched['is_pdf']
                && mb_strlen($fetched['content']) < $minContentLength
            ) {
                $lastError = sprintf('Content too short (%d chars): %s', mb_strlen($fetched['content']), $candidateUrl);
                $logger->info($jobId, 'fetch_too_short_skip', ['url' => $candidateUrl, 'length' => mb_strlen($fetched['content'])]);
                continue;
            }

            $sourceUrl = $fetched['url'];
            $content   = $fetched['content'];
            $rawHtml   = $fetched['raw_html'] ?? $content;
            $isPdf     = $fetched['is_pdf'];

            $logger->info($jobId, 'fetch', [
                'url'    => $sourceUrl,
                'is_pdf' => $isPdf,
                'length' => mb_strlen($content),
            ]);

            // ── Extract data ───────────────────────────────────────────────────

            $donor = $lib->getDonorConfig()->getForUrl($sourceUrl);

            if ($donor) {
                $logger->info($jobId, 'donor_config', [
                    'domain'   => $donor['domain'],
                    'skip_ai'  => (bool)$donor['skip_ai'],
                ]);
            }

            $attrsSel = $donor['attributes_selector']  ?? '';
            $descSel  = $donor['description_selector'] ?? '';
            $imgSel   = $donor['image_selector']       ?? '';

            if ($donor && $donor['skip_ai']) {
                // Selector-based extraction — needs raw HTML, not Markdown
                $attempted = $selectorExt->extract($rawHtml, $descSel, $attrsSel, $imgSel);

                // If stored XPath pattern exists and DOM heuristics returned no attrs, try it
                if ($attempted->attributes === null && !empty($donor['attributes_pattern']) && $attrsSel !== '') {
                    $patternAttrs = $selectorExt->extractByPattern($rawHtml, $attrsSel, $donor['attributes_pattern']);
                    if ($patternAttrs !== null) {
                        $attempted->attributes = $patternAttrs;
                    }
                }

                $logger->info($jobId, 'selector_extract', [
                    'has_description' => $attempted->description !== null,
                    'has_attributes'  => $attempted->attributes  !== null,
                ]);

                // Fall back to AI if selectors returned nothing useful
                if ($attempted->description === null && $attempted->attributes === null) {
                    $logger->info($jobId, 'selector_empty_ai_fallback', ['url' => $sourceUrl]);
                    $attempted  = $dataExtractor->extract(
                        $jobId, $content,
                        $product['name'] ?? ('Product #' . $productId),
                        $languageName,
                        $donor['custom_prompt'] ?? null
                    );
                    $aiProvider = $cfg['ai_provider'] ?? 'openai';
                    $aiModel    = $cfg[$aiProvider . '_model'] ?? '';
                } else {
                    $aiResponseRaw = '';
                    $aiProvider    = 'selector';
                    $aiModel       = '';
                }
            } else {
                // AI extraction
                $customPrompt = $donor['custom_prompt'] ?? null;
                $productName  = $product['name'] ?? ('Product #' . $productId);

                // Send raw HTML when donor has no selectors yet — so AI can detect CSS selectors
                // (Markdown has no DOM structure). Triggered by first_donor_html setting OR auto_create_donor.
                $donorHasAllSelectors = $donor && (!empty($descSel) || !empty($attrsSel)) && !empty($imgSel);
                $needsRawHtml         = !$isPdf && !$donorHasAllSelectors
                    && (!empty($cfg['first_donor_html']) || !empty($cfg['auto_create_donor']));
                $aiContent         = $needsRawHtml ? $rawHtml : $content;

                $attempted     = $dataExtractor->extract($jobId, $aiContent, $productName, $languageName, $customPrompt);
                $aiResponseRaw = '';
                $aiProvider    = $cfg['ai_provider'] ?? 'openai';
                $aiModel       = $cfg[$aiProvider . '_model'] ?? '';

                // If AI returned no attributes but donor has attributes_selector:
                // 1) try stored XPath pattern (free)
                // 2) AI on HTML snippet only (cheap) + store the pattern
                if ($attempted->attributes === null && $donor && $attrsSel !== '') {
                    $donorCfg = $lib->getDonorConfig();
                    if (!empty($donor['attributes_pattern'])) {
                        $patternAttrs = $selectorExt->extractByPattern($rawHtml, $attrsSel, $donor['attributes_pattern']);
                        if ($patternAttrs !== null) {
                            $attempted->attributes = $patternAttrs;
                            $logger->info($jobId, 'attrs_from_pattern', ['xpath' => $donor['attributes_pattern']]);
                        }
                    }
                    if ($attempted->attributes === null) {
                        $htmlSnippet = $selectorExt->getMatchedHtml($rawHtml, $attrsSel);
                        if ($htmlSnippet !== '') {
                            $logger->info($jobId, 'attrs_snippet_ai_fallback', ['selector' => $attrsSel]);
                            $attrResult = $dataExtractor->extractAttributesFromHtml($jobId, $htmlSnippet);
                            if (!empty($attrResult['attributes'])) {
                                $attempted->attributes = $attrResult['attributes'];
                            }
                            if (!empty($attrResult['xpath_pattern']) && !empty($donor['donor_id'])) {
                                $donorCfg->updatePattern((int)$donor['donor_id'], $attrResult['xpath_pattern']);
                                $logger->info($jobId, 'attrs_pattern_stored', ['xpath' => $attrResult['xpath_pattern']]);
                            }
                        }
                    }
                }

                // ── Auto-fill empty donor selectors from AI result ────────────
                // Runs whenever AI extracted data and donor exists with empty selector fields.
                // Only fills fields that are currently empty — never overwrites manual settings.
                if ($donor) {
                    $donorCfgObj  = $lib->getDonorConfig();
                    $needsUpdate  = false;
                    $donorUpdate  = [
                        'donor_id'             => (int)$donor['donor_id'],
                        'domain'               => $donor['domain'],
                        'name'                 => $donor['name']          ?? '',
                        'status'               => (int)($donor['status']   ?? 1),
                        'skip_ai'              => (int)($donor['skip_ai']  ?? 0),
                        'priority'             => (int)($donor['priority'] ?? 0),
                        'description_selector' => $donor['description_selector'] ?? '',
                        'attributes_selector'  => $donor['attributes_selector']  ?? '',
                        'image_selector'       => $donor['image_selector']       ?? '',
                        'attributes_pattern'   => $donor['attributes_pattern']   ?? '',
                        'search_url_template'  => $donor['search_url_template']  ?? '',
                        'custom_prompt'        => $donor['custom_prompt']        ?? '',
                    ];

                    if (empty($donorUpdate['description_selector']) && $attempted->descriptionSelector !== null) {
                        $donorUpdate['description_selector'] = $attempted->descriptionSelector;
                        $needsUpdate = true;
                    }
                    if (empty($donorUpdate['attributes_selector']) && $attempted->attributesSelector !== null) {
                        $donorUpdate['attributes_selector'] = $attempted->attributesSelector;
                        $needsUpdate = true;
                    }
                    if (empty($donorUpdate['image_selector']) && $attempted->imageSelector !== null) {
                        $donorUpdate['image_selector'] = $attempted->imageSelector;
                        $needsUpdate = true;
                    }

                    if ($needsUpdate) {
                        $donorCfgObj->save($donorUpdate);
                        $logger->info($jobId, 'donor_selectors_updated', [
                            'desc'  => $donorUpdate['description_selector'],
                            'attrs' => $donorUpdate['attributes_selector'],
                            'imgs'  => $donorUpdate['image_selector'],
                        ]);
                    }
                }

                // ── Auto-create donor with selectors returned by AI ────────────
                if (!empty($cfg['auto_create_donor'])) {
                    $donorConfig   = $lib->getDonorConfig();
                    $existingDonor = $donorConfig->getForUrl($sourceUrl);
                    $hasSelectors  = $attempted->descriptionSelector !== null;

                    if ($existingDonor === null || ($hasSelectors && empty($existingDonor['description_selector']) && empty($existingDonor['attributes_selector']))) {
                        $donorConfig->save([
                            'donor_id'             => (int)($existingDonor['donor_id'] ?? 0),
                            'domain'               => $donorConfig->extractDomain($sourceUrl),
                            'status'               => 1,
                            'skip_ai'              => $hasSelectors ? 1 : 0,
                            'priority'             => (int)($existingDonor['priority'] ?? 0),
                            'description_selector' => $attempted->descriptionSelector ?? '',
                            'attributes_selector'  => $attempted->attributesSelector  ?? '',
                            'image_selector'       => $attempted->imageSelector       ?? '',
                            'custom_prompt'        => $existingDonor['custom_prompt'] ?? '',
                            'name'                 => $existingDonor['name'] ?? '',
                        ]);

                        $logger->info($jobId, 'auto_donor', [
                            'domain'               => $donorConfig->extractDomain($sourceUrl),
                            'skip_ai'              => $hasSelectors,
                            'description_selector' => $attempted->descriptionSelector,
                            'attributes_selector'  => $attempted->attributesSelector,
                            'image_selector'       => $attempted->imageSelector,
                        ]);
                    }
                }
            }

            // When attributes required but not found — try next candidate URL
            if ($skipIfNoAttributes && empty($attempted->attributes)) {
                $lastError = 'No attributes extracted from: ' . $sourceUrl;
                $triedNoAttrUrls[] = $sourceUrl;
                $logger->info($jobId, 'no_attributes_try_next', ['url' => $sourceUrl]);
                continue;
            }

            // This URL provided usable data
            $scraped = $attempted;
            break;
        }

        // All candidate URLs exhausted without a successful extraction
        if ($scraped === null) {
            // Auto-blacklist all donors that returned no attributes
            if ($skipIfNoAttributes && $autoBlacklistNoAttrs && !empty($triedNoAttrUrls)) {
                $blacklistManager = $lib->getBlacklistManager();
                foreach ($triedNoAttrUrls as $blockedUrl) {
                    $blacklistManager->add($blockedUrl, 'No attributes extracted (auto-blacklist)');
                }
                $logger->info($jobId, 'auto_blacklisted', ['count' => count($triedNoAttrUrls), 'urls' => $triedNoAttrUrls]);
            }

            if ($skipIfNoAttributes) {
                $jobQueue->markSkipped($jobId);
                $logger->info($jobId, 'skipped_no_attributes', ['tried' => count($candidateUrls)]);
                $stats['skipped']++;
                echo ts() . " Job #{$jobId} → skipped (no attributes from any source).\n";
                continue;
            }
            throw new \RuntimeException($lastError ?: 'All URLs failed with no usable content');
        }

        // ── 3. Save or queue for moderation ──────────────────────────────────

        if ($moderationMode) {
            $jobQueue->markModeration(
                $jobId,
                $scraped->toArray(),
                $sourceUrl,
                $aiProvider,
                $aiModel,
                $aiResponseRaw,
                $content
            );

            $logger->info($jobId, 'queued_moderation', ['url' => $sourceUrl]);
            $stats['moderation']++;
            echo ts() . " Job #{$jobId} → moderation.\n";
        } else {
            $fieldsApplied = $enricher->enrich(
                $productId,
                $languageId,
                $scraped,
                $fieldsToFill,
                $overwriteExisting,
                $jobId
            );

            // ── Images ───────────────────────────────────────────────────────
            if ($scrapeImageMain || $scrapeImageAdditional) {
                $productRow = $product ?? $db->query(
                    "SELECT model, upc AS sku, ean, jan FROM `" . DB_PREFIX . "product`
                     WHERE `product_id` = '" . (int)$productId . "' LIMIT 1"
                )->row;

                $imgProductData = [
                    'product_id' => $productId,
                    'model'      => $productRow['model'] ?? '',
                    'sku'        => $productRow['sku']   ?? $productRow['upc'] ?? '',
                    'ean'        => $productRow['ean']   ?? '',
                    'jan'        => $productRow['jan']   ?? '',
                ];

                $imageDownloader = $lib->makeImageDownloader([
                    'image_dir'      => DIR_IMAGE,
                    'min_image_size' => (int)($cfg['min_image_size'] ?? 0),
                ]);
                $imgApplied = $enricher->saveImages(
                    $productId, $scraped,
                    $scrapeImageMain, $scrapeImageAdditional, $imageAdditionalCount,
                    $imageDir, $imageDownloader, (string)($cfg['images_mode'] ?? 'replace'), $imgProductData, $jobId
                );
                $fieldsApplied = array_merge($fieldsApplied, $imgApplied);
            }

            $jobQueue->markDone($jobId, $fieldsApplied);
            $stats['done']++;
            echo ts() . " Job #{$jobId} → done. Applied: " . implode(', ', $fieldsApplied) . "\n";

            // Auto-translate via Translater Pro
            if ($autoTranslate && !empty($targetLangCodes)) {
                $translated = $translaterBridge->translate(
                    $jobId,
                    $productId,
                    $sourceLangCode,
                    $targetLangCodes
                );
                if (!empty($translated)) {
                    $jobQueue->saveTranslatedLanguages($jobId, $translated);
                    echo ts() . " Job #{$jobId} translated: " . implode(', ', $translated) . "\n";
                }
            }
        }

    } catch (\OcKit\ProductsScraper\Exceptions\TokenLimitException $e) {
        // Token-limit policy: skip → markSkipped, error/fallback → markError
        // 'fallback' arrives here only when no fallback provider is configured or it's also exhausted.
        $logger->warning($jobId, 'token_limit_reached', [
            'provider' => $e->provider,
            'action'   => $e->action,
        ]);
        if ($e->action === 'skip') {
            $jobQueue->markSkipped($jobId);
            $stats['skipped']++;
        } else {
            $jobQueue->markError($jobId, $e->getMessage());
            $stats['error']++;
        }
        continue;
    } catch (\Throwable $e) {
        $jobQueue->markError($jobId, $e->getMessage());
        $logger->error($jobId, 'pipeline_error', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);
        $stats['error']++;
        echo ts() . " Job #{$jobId} → ERROR: " . $e->getMessage() . "\n";
    }
}

// ── Summary ───────────────────────────────────────────────────────────────────

$stats['pending'] = (int)($jobQueue->getStats()['pending'] ?? 0);

echo ts() . " Batch complete."
    . " done={$stats['done']}"
    . " moderation={$stats['moderation']}"
    . " skipped={$stats['skipped']}"
    . " error={$stats['error']}"
    . " pending={$stats['pending']}\n";

// ── Telegram notification ─────────────────────────────────────────────────────

if (!empty($cfg['telegram_notify'])) {
    $botToken = trim((string)($cfg['telegram_bot_token'] ?? ''));
    $chatId   = trim((string)($cfg['telegram_chat_id']   ?? ''));

    // Fallback to main Telegram notification module settings
    if ($botToken === '') {
        $botToken = (string)($config->get('module_telegram_notification_bot_token') ?? '');
        $chatId   = $chatId ?: (string)($config->get('module_telegram_notification_chat_id') ?? '');
    }

    $notifier = new TelegramNotifier($botToken, $chatId);

    if ($notifier->isConfigured()) {
        $sent = $notifier->sendBatchSummary($stats);
        echo ts() . " Telegram notification " . ($sent ? "sent." : "failed.") . "\n";
    }
}

exit(0);

// ── Helpers ───────────────────────────────────────────────────────────────────

function ts(): string
{
    return date('[Y-m-d H:i:s]');
}
