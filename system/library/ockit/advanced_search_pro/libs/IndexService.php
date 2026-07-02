<?php
/**
 * Advanced Search Pro — Index Service
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\AdvancedSearchPro\Libs;

use OcKit\AdvancedSearchPro\SearchMode;
use OcKit\AdvancedSearchPro\ManticoreClient;
use OcKit\AdvancedSearchPro\SphinxClient;

class IndexService {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db       = $registry->get('db');
        $this->config   = $registry->get('config');
    }

    public function getProductIndexData($product_id) {
        $product_id = (int)$product_id;
        $batch = $this->getBulkProductIndexData([$product_id]);
        return $batch[$product_id] ?? null;
    }

    public function getBulkProductIndexData(array $productIds) {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (!$productIds) {
            return [];
        }

        $inClause = implode(',', $productIds);

        // 1. Core product data (manufacturer is language-independent).
        $rows = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.quantity, p.price,
                    IFNULL(m.name, '') AS manufacturer
             FROM " . DB_PREFIX . "product p
             LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
             WHERE p.product_id IN (" . $inClause . ")
               AND p.status = '1' AND p.date_available <= NOW()"
        );

        $data = [];
        foreach ($rows->rows as $row) {
            $pid = (int)$row['product_id'];
            $row['name']        = '';
            $row['description'] = '';
            $row['tag']         = '';
            $data[$pid] = $row;
        }
        if (!$data) {
            return [];
        }

        // 2. Names / descriptions / tags — ALL active languages, merged. This is
        //    what lets Russian and English searches match on a Ukrainian-primary
        //    catalogue (e.g. "мужские кроссовки" hits the RU category/description).
        $nameParts = [];
        $descParts = [];
        $tagParts  = [];
        $descRows = $this->db->query(
            "SELECT product_id, name, description, tag
             FROM " . DB_PREFIX . "product_description
             WHERE product_id IN (" . $inClause . ")"
        );
        foreach ($descRows->rows as $row) {
            $pid = (int)$row['product_id'];
            if (!isset($data[$pid])) {
                continue;
            }
            if ($row['name'] !== '')        { $nameParts[$pid][] = $row['name']; }
            if ($row['description'] !== '') { $descParts[$pid][] = strip_tags($row['description']); }
            if ($row['tag'] !== '')         { $tagParts[$pid][]  = $row['tag']; }
        }

        // 3. Category names — ALL languages.
        $catByProduct = [];
        $catRows = $this->db->query(
            "SELECT pc.product_id, cd.name
             FROM " . DB_PREFIX . "product_to_category pc
             INNER JOIN " . DB_PREFIX . "category_description cd ON (pc.category_id = cd.category_id)
             WHERE pc.product_id IN (" . $inClause . ") AND cd.name <> ''"
        );
        foreach ($catRows->rows as $row) {
            $catByProduct[(int)$row['product_id']][] = $row['name'];
        }

        // 4. Attribute values — ALL languages.
        $attrByProduct = [];
        $attrRows = $this->db->query(
            "SELECT product_id, text
             FROM " . DB_PREFIX . "product_attribute
             WHERE product_id IN (" . $inClause . ") AND text <> ''"
        );
        foreach ($attrRows->rows as $row) {
            $attrByProduct[(int)$row['product_id']][] = $row['text'];
        }

        // 5. Assemble. name = every language's product name; description carries
        //    descriptions + category names + attribute values across all languages.
        foreach ($data as $pid => &$item) {
            $item['name'] = trim(implode(' ', array_unique($nameParts[$pid] ?? [])));
            $descPieces = array_merge(
                array_unique($descParts[$pid] ?? []),
                array_unique($catByProduct[$pid] ?? []),
                array_unique($attrByProduct[$pid] ?? [])
            );
            $item['description'] = trim(implode(' ', $descPieces));
            $item['tag'] = trim(implode(' ', array_unique($tagParts[$pid] ?? [])));
        }
        unset($item);

        return $data;
    }

    public function ensureManticoreIndex($settings) {
        $client = $this->getManticoreClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');
        // manufacturer is a full-text field so brand names are MATCH()-searchable.
        // min_infix_len enables substring matching and is required for OPTION fuzzy=1.
        $sql = "CREATE TABLE IF NOT EXISTS " . $index . " (\n" .
            "id BIGINT,\n" .
            "name TEXT,\n" .
            "description TEXT,\n" .
            "tag TEXT,\n" .
            "manufacturer TEXT,\n" .
            "model TEXT,\n" .
            "sku TEXT,\n" .
            "price FLOAT,\n" .
            "quantity INT\n" .
            ") min_infix_len='2'" . $this->buildMorphologyOptions($settings);
        $client->query($sql);
    }

    /**
     * Assemble the morphology/wordforms CREATE TABLE options.
     *
     * RU + EN use Manticore's native lemmatizers (ru.pak/en.pak ship with the
     * server). Ukrainian has no official .pak, so it's handled by a
     * catalog-scoped wordforms file (form > lemma) generated from brown-uk.
     * Both are opt-in via settings and degrade silently when unavailable.
     */
    private function buildMorphologyOptions($settings) {
        $opts = '';

        $morphology = trim((string)($settings['morphology'] ?? 'lemmatize_ru_all, lemmatize_en_all'));
        if ($morphology !== '') {
            // Whitelist tokens to avoid injecting arbitrary text into the DDL.
            // lemmatize_uk_all/lemmatize_uk need lemmatize_uk.so (a pymorphy2-uk
            // wrapper that links libpython3.9) in Manticore's plugin dir. Where it
            // is installed they give broader Ukrainian coverage than the wordforms
            // file, at ~20x slower index time — so they are opt-in via the
            // morphology setting and simply ignored on builds without the plugin.
            $allowed = [
                'lemmatize_uk_all', 'lemmatize_ru_all', 'lemmatize_en_all', 'lemmatize_de_all',
                'lemmatize_uk', 'lemmatize_ru', 'lemmatize_en', 'lemmatize_de',
                'stem_ru', 'stem_en', 'stem_enru', 'soundex', 'metaphone',
            ];
            $tokens = array_filter(array_map('trim', explode(',', $morphology)));
            $tokens = array_values(array_intersect($tokens, $allowed));
            if ($tokens) {
                $opts .= " morphology='" . implode(', ', $tokens) . "'";
            }
        }

        $wf = trim((string)($settings['morphology_uk_wordforms'] ?? '/var/lib/manticore/wordforms/uk.txt'));
        if ($wf !== '' && preg_match('#^/[A-Za-z0-9_./-]+$#', $wf) && is_file($wf)) {
            $opts .= " wordforms='" . $wf . "'";
        }

        return $opts;
    }

    public function ensureSphinxIndex($settings) {
        $client = $this->getSphinxClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['sphinx_index'] ?? 'products');
        $sql = "CREATE TABLE IF NOT EXISTS " . $index . " (\n" .
            "id BIGINT,\n" .
            "name TEXT,\n" .
            "description TEXT,\n" .
            "tag TEXT,\n" .
            "model STRING,\n" .
            "sku STRING,\n" .
            "manufacturer STRING,\n" .
            "price FLOAT,\n" .
            "quantity INT\n" .
            ")";
        $client->query($sql);
    }

    public function ensureSearchIndex($settings) {
        $mode = SearchMode::normalize($settings['mode'] ?? SearchMode::NATIVE);
        if ($mode === SearchMode::SPHINX) {
            $this->ensureSphinxIndex($settings);
        } elseif ($mode === SearchMode::MANTICORE || $mode === SearchMode::HYBRID) {
            $this->ensureManticoreIndex($settings);
        }
    }

    public function indexProductToManticore($product_id, $settings) {
        $data = $this->getProductIndexData($product_id);
        if (!$data) {
            // Product is missing, disabled or not yet available — drop it from the index.
            $this->deleteProductFromManticore($product_id, $settings);
            return true;
        }

        $client = $this->getManticoreClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');

        $sql = "REPLACE INTO " . $index . " (id, name, description, tag, model, sku, manufacturer, price, quantity) VALUES (" .
            (int)$data['product_id'] . ", '" .
            $client->escape($data['name']) . "', '" .
            $client->escape($data['description']) . "', '" .
            $client->escape($data['tag']) . "', '" .
            $client->escape($data['model']) . "', '" .
            $client->escape($data['sku']) . "', '" .
            $client->escape($data['manufacturer']) . "', " .
            (float)$data['price'] . ", " .
            (int)$data['quantity'] . ")";

        $client->query($sql);
        return true;
    }

    public function indexProductToSphinx($product_id, $settings) {
        $data = $this->getProductIndexData($product_id);
        if (!$data) {
            return false;
        }

        $client = $this->getSphinxClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['sphinx_index'] ?? 'products');

        $sql = "REPLACE INTO " . $index . " (id, name, description, tag, model, sku, manufacturer, price, quantity) VALUES (" .
            (int)$data['product_id'] . ", '" .
            $client->escape($data['name']) . "', '" .
            $client->escape($data['description']) . "', '" .
            $client->escape($data['tag']) . "', '" .
            $client->escape($data['model']) . "', '" .
            $client->escape($data['sku']) . "', '" .
            $client->escape($data['manufacturer']) . "', " .
            (float)$data['price'] . ", " .
            (int)$data['quantity'] . ")";

        $client->query($sql);
        return true;
    }

    public function deleteProductFromManticore($product_id, $settings) {
        $client = $this->getManticoreClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');
        $client->query("DELETE FROM " . $index . " WHERE id = " . (int)$product_id);
        return true;
    }

    public function deleteProductFromSphinx($product_id, $settings) {
        $client = $this->getSphinxClient($settings);
        $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['sphinx_index'] ?? 'products');
        $client->query("DELETE FROM " . $index . " WHERE id = " . (int)$product_id);
        return true;
    }

    // -------------------------------------------------------------------------
    // Native MySQL index helpers
    // -------------------------------------------------------------------------

    private function normalizeIndexText(string $text): string {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return mb_strtolower(trim($text), 'UTF-8');
    }

    private function getActiveLanguageIds(): array {
        $rows = $this->db->query("SELECT language_id FROM `" . DB_PREFIX . "language` WHERE status = '1'");
        $ids = [];
        foreach ($rows->rows as $row) {
            $ids[] = (int)$row['language_id'];
        }
        return $ids ?: [(int)$this->config->get('config_language_id')];
    }

    private function getBulkNativeFieldData(array $productIds, int $languageId): array {
        if (!$productIds) {
            return [];
        }
        $inClause = implode(',', $productIds);

        $rows = $this->db->query(
            "SELECT p.product_id,
                    IFNULL(p.model, '') AS model,
                    IFNULL(p.sku, '') AS sku,
                    IFNULL(p.upc, '') AS upc,
                    IFNULL(p.ean, '') AS ean,
                    IFNULL(m.name, '') AS manufacturer,
                    IFNULL(pd.name, '') AS name,
                    IFNULL(pd.description, '') AS description,
                    IFNULL(pd.tag, '') AS tag
             FROM `" . DB_PREFIX . "product` p
             LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON p.manufacturer_id = m.manufacturer_id
             LEFT JOIN `" . DB_PREFIX . "product_description` pd
                ON pd.product_id = p.product_id AND pd.language_id = '" . $languageId . "'
             WHERE p.product_id IN (" . $inClause . ")"
        );

        $data = [];
        foreach ($rows->rows as $row) {
            $data[(int)$row['product_id']] = $row;
        }
        if (!$data) {
            return [];
        }

        $catRows = $this->db->query(
            "SELECT pc.product_id, cd.name
             FROM `" . DB_PREFIX . "product_to_category` pc
             INNER JOIN `" . DB_PREFIX . "category_description` cd
                ON cd.category_id = pc.category_id AND cd.language_id = '" . $languageId . "'
             WHERE pc.product_id IN (" . $inClause . ") AND cd.name <> ''"
        );
        $catByProduct = [];
        foreach ($catRows->rows as $row) {
            $catByProduct[(int)$row['product_id']][] = $row['name'];
        }

        $attrRows = $this->db->query(
            "SELECT product_id, text
             FROM `" . DB_PREFIX . "product_attribute`
             WHERE product_id IN (" . $inClause . ")
               AND language_id = '" . $languageId . "'
               AND text <> ''"
        );
        $attrByProduct = [];
        foreach ($attrRows->rows as $row) {
            $attrByProduct[(int)$row['product_id']][] = $row['text'];
        }

        foreach ($data as $pid => &$item) {
            $item['category']  = isset($catByProduct[$pid])  ? implode(' ', $catByProduct[$pid])  : '';
            $item['attribute'] = isset($attrByProduct[$pid]) ? implode(' ', $attrByProduct[$pid]) : '';
        }
        unset($item);

        return $data;
    }

    private function buildNativeIndexRows(array $productIds, int $languageId, array $settings = []): array {
        $settingsKeyMap = [
            'title' => 'name', 'model' => 'model', 'sku' => 'sku', 'upc' => 'upc', 'ean' => 'ean',
            'manufacturer' => 'manufacturer', 'tags' => 'tag',
            'categories' => 'category', 'attributes' => 'attribute', 'description' => 'description',
        ];
        $defaultWeights = [
            'name' => 80, 'model' => 60, 'sku' => 60, 'upc' => 20, 'ean' => 20,
            'manufacturer' => 40, 'tag' => 30, 'category' => 20, 'attribute' => 15, 'description' => 10,
        ];

        $searchFields = isset($settings['search_fields']) && is_array($settings['search_fields'])
            ? $settings['search_fields']
            : [];

        $fieldWeights = [];
        foreach ($settingsKeyMap as $settingKey => $fieldType) {
            if ($searchFields && isset($searchFields[$settingKey]) && empty($searchFields[$settingKey]['enabled'])) {
                continue; // skip disabled fields
            }
            $fieldWeights[$fieldType] = (!empty($searchFields[$settingKey]['weight']))
                ? max(1, min(100, (int)$searchFields[$settingKey]['weight']))
                : $defaultWeights[$fieldType];
        }

        if (!$fieldWeights) {
            return [];
        }

        $batch = $this->getBulkNativeFieldData($productIds, $languageId);
        $rows = [];

        foreach ($batch as $pid => $item) {
            foreach ($fieldWeights as $field => $weight) {
                $raw = (string)($item[$field] ?? '');
                if ($raw === '') {
                    continue;
                }
                $content = $this->normalizeIndexText($raw);
                if ($content === '') {
                    continue;
                }
                $rows[] = "(" . (int)$pid . ", " . (int)$languageId . ", '" .
                    $this->db->escape($field) . "', " . (int)$weight . ", '" .
                    $this->db->escape($content) . "')";
            }
        }

        return $rows;
    }

    public function indexProductToNative($product_id, array $settings): bool {
        $product_id = (int)$product_id;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_native_index` WHERE product_id = '" . $product_id . "'");

        $languages = $this->getActiveLanguageIds();
        $hasData = false;
        foreach ($languages as $langId) {
            $rows = $this->buildNativeIndexRows([$product_id], $langId, $settings);
            if ($rows) {
                $this->db->query(
                    "INSERT INTO `" . DB_PREFIX . "asp_native_index` (product_id, language_id, field_type, weight, content) VALUES " .
                    implode(', ', $rows)
                );
                $hasData = true;
            }
        }
        return $hasData;
    }

    public function deleteProductFromNative($product_id): bool {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_native_index` WHERE product_id = '" . (int)$product_id . "'");
        return true;
    }

    public function bulkIndexToNative(array $productIds, array $settings): int {
        if (!$productIds) {
            return 0;
        }
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        $inClause = implode(',', $productIds);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "asp_native_index` WHERE product_id IN (" . $inClause . ")");

        $languages = $this->getActiveLanguageIds();
        $allRows = [];
        foreach ($languages as $langId) {
            $allRows = array_merge($allRows, $this->buildNativeIndexRows($productIds, $langId, $settings));
        }

        if (!$allRows) {
            return 0;
        }

        foreach (array_chunk($allRows, 500) as $chunk) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "asp_native_index` (product_id, language_id, field_type, weight, content) VALUES " .
                implode(', ', $chunk)
            );
        }

        return count($productIds);
    }

    // -------------------------------------------------------------------------

    public function indexProduct($product_id, $settings) {
        $mode = SearchMode::normalize($settings['mode'] ?? SearchMode::NATIVE);
        if ($mode === SearchMode::NATIVE) {
            return $this->indexProductToNative($product_id, $settings);
        }
        if ($mode === SearchMode::SPHINX) {
            return $this->indexProductToSphinx($product_id, $settings);
        }
        if ($mode === SearchMode::MANTICORE || $mode === SearchMode::HYBRID) {
            return $this->indexProductToManticore($product_id, $settings);
        }
        return false;
    }

    public function deleteProduct($product_id, $settings) {
        $mode = SearchMode::normalize($settings['mode'] ?? SearchMode::NATIVE);
        if ($mode === SearchMode::NATIVE) {
            return $this->deleteProductFromNative($product_id);
        }
        if ($mode === SearchMode::SPHINX) {
            return $this->deleteProductFromSphinx($product_id, $settings);
        }
        if ($mode === SearchMode::MANTICORE || $mode === SearchMode::HYBRID) {
            return $this->deleteProductFromManticore($product_id, $settings);
        }
        return false;
    }

    public function bulkIndexToManticore(array $productIds, array $settings) {
        if (!$productIds) {
            return 0;
        }

        $client = $this->getManticoreClient($settings);
        $index  = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');

        $batch   = $this->getBulkProductIndexData($productIds);
        $indexed = 0;
        $valueParts = [];

        foreach ($batch as $data) {
            $valueParts[] = "(" .
                (int)$data['product_id'] . ", '" .
                $client->escape($data['name'])         . "', '" .
                $client->escape($data['description'])  . "', '" .
                $client->escape($data['tag'])           . "', '" .
                $client->escape($data['model'])         . "', '" .
                $client->escape($data['sku'])           . "', '" .
                $client->escape($data['manufacturer'])  . "', " .
                (float)$data['price']   . ", " .
                (int)$data['quantity']  .
            ")";
            $indexed++;
        }

        if ($valueParts) {
            $client->query(
                "REPLACE INTO " . $index . " (id, name, description, tag, model, sku, manufacturer, price, quantity) VALUES " .
                implode(', ', $valueParts)
            );
        }

        // Drop requested IDs that are disabled / unavailable (absent from the batch).
        $missing = array_diff(array_map('intval', $productIds), array_keys($batch));
        if ($missing) {
            $client->query("DELETE FROM " . $index . " WHERE id IN (" . implode(',', $missing) . ")");
        }

        return $indexed;
    }

    public function bulkIndexToSphinx(array $productIds, array $settings) {
        if (!$productIds) {
            return 0;
        }

        $client = $this->getSphinxClient($settings);
        $index  = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['sphinx_index'] ?? ($settings['index'] ?? 'products'));

        $batch   = $this->getBulkProductIndexData($productIds);
        $indexed = 0;
        $valueParts = [];

        foreach ($batch as $data) {
            $valueParts[] = "(" .
                (int)$data['product_id'] . ", '" .
                $client->escape($data['name'])         . "', '" .
                $client->escape($data['description'])  . "', '" .
                $client->escape($data['tag'])           . "', '" .
                $client->escape($data['model'])         . "', '" .
                $client->escape($data['sku'])           . "', '" .
                $client->escape($data['manufacturer'])  . "', " .
                (float)$data['price']   . ", " .
                (int)$data['quantity']  .
            ")";
            $indexed++;
        }

        if ($valueParts) {
            $client->query(
                "REPLACE INTO " . $index . " (id, name, description, tag, model, sku, manufacturer, price, quantity) VALUES " .
                implode(', ', $valueParts)
            );
        }

        return $indexed;
    }

    public function bulkIndexProducts(array $productIds, array $settings) {
        $mode = SearchMode::normalize($settings['mode'] ?? SearchMode::NATIVE);
        if ($mode === SearchMode::NATIVE) {
            return $this->bulkIndexToNative($productIds, $settings);
        }
        if ($mode === SearchMode::SPHINX) {
            return $this->bulkIndexToSphinx($productIds, $settings);
        }
        if ($mode === SearchMode::MANTICORE || $mode === SearchMode::HYBRID) {
            return $this->bulkIndexToManticore($productIds, $settings);
        }
        return 0;
    }

    public function getIndexDocumentsCount($settings = []) {
        $settings = array_merge([
            'mode' => SearchMode::NATIVE,
            'index' => 'products',
            'sphinx_index' => 'products'
        ], $settings);

        $mode = SearchMode::normalize($settings['mode']);
        if ($mode === SearchMode::NATIVE) {
            $row = $this->db->query("SELECT COUNT(DISTINCT product_id) AS total FROM `" . DB_PREFIX . "asp_native_index`")->row;
            return (int)($row['total'] ?? 0);
        }

        try {
            if ($mode === SearchMode::SPHINX) {
                $client = $this->getSphinxClient($settings);
                $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['sphinx_index'] ?? 'products');
            } else {
                $client = $this->getManticoreClient($settings);
                $index = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');
            }

            $rows = $client->query("SELECT COUNT(*) AS total FROM " . $index);
            if (!empty($rows[0])) {
                if (isset($rows[0]['total'])) {
                    return (int)$rows[0]['total'];
                }
                foreach ($rows[0] as $value) {
                    return (int)$value;
                }
            }
        } catch (\Exception $e) {
            error_log('[ASP] getIndexDocumentsCount failed: ' . $e->getMessage());
            return 0;
        }

        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Vector (semantic) search — Manticore native KNN over a `*_vec` table
    // ─────────────────────────────────────────────────────────────────────

    /** Embedding dimensions per OpenAI model. */
    private function embeddingDims($model) {
        $model = strtolower(trim((string)$model));
        if ($model === 'text-embedding-3-small' || $model === 'text-embedding-ada-002') {
            return 1536;
        }
        return 3072; // text-embedding-3-large
    }

    /** Name of the Manticore KNN table (separate from the text index). */
    private function vectorIndexName($settings) {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $settings['index'] ?? 'products');
        return ($base !== '' ? $base : 'products') . '_vec';
    }

    /** Format a numeric vector as a Manticore float_vector literal. */
    private function formatVector(array $vec) {
        $parts = [];
        foreach ($vec as $v) {
            $parts[] = rtrim(rtrim(sprintf('%.7f', (float)$v), '0'), '.');
        }
        return '(' . implode(',', $parts) . ')';
    }

    /** Create the Manticore KNN table for product embeddings. */
    public function ensureVectorIndex($settings, $recreate = false) {
        $client = $this->getManticoreClient($settings);
        $index  = $this->vectorIndexName($settings);
        $dims   = $this->embeddingDims($settings['ai_embedding_model'] ?? 'text-embedding-3-large');
        if ($recreate) {
            $client->query("DROP TABLE IF EXISTS " . $index);
        }
        $client->query(
            "CREATE TABLE IF NOT EXISTS " . $index . " (" .
            "id BIGINT, " .
            "embedding FLOAT_VECTOR KNN_TYPE='hnsw' KNN_DIMS='" . (int)$dims . "' HNSW_SIMILARITY='COSINE'" .
            ")"
        );
    }

    /** Push one product's embedding vector into the Manticore KNN table. */
    public function pushProductVector($product_id, array $vector, $settings) {
        if (!$vector) {
            return false;
        }
        $client = $this->getManticoreClient($settings);
        $index  = $this->vectorIndexName($settings);
        $client->query(
            "REPLACE INTO " . $index . " (id, embedding) VALUES (" .
            (int)$product_id . ", " . $this->formatVector($vector) . ")"
        );
        return true;
    }

    /** Rebuild the whole Manticore KNN table from the asp_embedding store (active products only). */
    public function syncVectorsToManticore($settings) {
        $this->ensureVectorIndex($settings, true); // drop + recreate — keeps KNN dims correct
        $client = $this->getManticoreClient($settings);
        $index  = $this->vectorIndexName($settings);

        $synced = 0;
        $lastId = 0;
        while (true) {
            $rows = $this->db->query(
                "SELECT e.product_id, e.vector_json
                 FROM `" . DB_PREFIX . "asp_embedding` e
                 INNER JOIN `" . DB_PREFIX . "product` p ON p.product_id = e.product_id
                 WHERE e.product_id > " . (int)$lastId . "
                   AND p.status = '1' AND p.date_available <= NOW()
                 ORDER BY e.product_id ASC
                 LIMIT 200"
            )->rows;
            if (!$rows) {
                break;
            }
            $valueParts = [];
            foreach ($rows as $row) {
                $lastId = (int)$row['product_id'];
                $vec = json_decode((string)$row['vector_json'], true);
                if (!is_array($vec) || !$vec) {
                    continue;
                }
                $valueParts[] = "(" . (int)$row['product_id'] . ", " . $this->formatVector($vec) . ")";
            }
            // float_vector rows are large — flush in small sub-batches.
            foreach (array_chunk($valueParts, 50) as $chunk) {
                $client->query("REPLACE INTO " . $index . " (id, embedding) VALUES " . implode(', ', $chunk));
                $synced += count($chunk);
            }
        }

        return $synced;
    }

    /**
     * KNN search over product embeddings.
     *
     * @return array  [product_id => cosine_similarity], nearest first, filtered by $minScore.
     */
    public function knnSearch(array $queryVector, $k, $minScore, $settings) {
        if (!$queryVector) {
            return [];
        }
        $client   = $this->getManticoreClient($settings);
        $index    = $this->vectorIndexName($settings);
        $k        = max(1, min(1000, (int)$k));
        $minScore = (float)$minScore;

        $rows = $client->query(
            "SELECT id, knn_dist() AS d FROM " . $index .
            " WHERE knn(embedding, " . $k . ", " . $this->formatVector($queryVector) . ")"
        );
        $result = [];
        foreach ($rows as $row) {
            $cos = 1.0 - (float)$row['d']; // COSINE: knn_dist() = 1 - similarity
            if ($cos >= $minScore) {
                $result[(int)$row['id']] = $cos;
            }
        }
        return $result;
    }

    public function queueEmbedding($product_id) {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return;
        }

        // Only embed active, available products — disabled ones waste API budget.
        $active = $this->db->query(
            "SELECT product_id FROM `" . DB_PREFIX . "product`
             WHERE product_id = '" . $product_id . "' AND status = '1' AND date_available <= NOW()"
        )->row;
        if (!$active) {
            return;
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_embedding_queue`
             SET product_id = '" . $product_id . "',
                 status = 'pending',
                 attempts = 0,
                 created_at = NOW(),
                 updated_at = NOW()
             ON DUPLICATE KEY UPDATE
                 status = 'pending',
                 updated_at = NOW()"
        );
    }

    public function queueAllProductsForEmbedding() {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_embedding_queue`
                (product_id, status, attempts, created_at, updated_at)
             SELECT p.product_id, 'pending', 0, NOW(), NOW()
             FROM `" . DB_PREFIX . "product` p
             WHERE p.status = '1' AND p.date_available <= NOW()
             ON DUPLICATE KEY UPDATE
                status = 'pending',
                attempts = 0,
                updated_at = NOW()"
        );
        return $this->db->countAffected();
    }

    /**
     * Queue ONLY products that have no embedding for the current model — the
     * incremental "fill the gaps" path behind the admin button. Already-embedded
     * products keep their state (not reset to pending), so re-running costs
     * nothing for what is done; a model change is handled by the full re-queue.
     */
    public function queueMissingProductsForEmbedding() {
        $model = (string)($this->config->get('module_oc_kit_advanced_search_pro_ai_embedding_model') ?: 'text-embedding-3-large');
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_embedding_queue`
                (product_id, status, attempts, created_at, updated_at)
             SELECT p.product_id, 'pending', 0, NOW(), NOW()
             FROM `" . DB_PREFIX . "product` p
             WHERE p.status = '1' AND p.date_available <= NOW()
               AND NOT EXISTS (
                   SELECT 1 FROM `" . DB_PREFIX . "asp_embedding` e
                   WHERE e.product_id = p.product_id
                     AND e.model = '" . $this->db->escape($model) . "'
               )
             ON DUPLICATE KEY UPDATE
                status = 'pending',
                attempts = 0,
                updated_at = NOW()"
        );
        return $this->db->countAffected();
    }

    public function processEmbeddingQueue($limit = 100) {
        $limit = max(1, (int)$limit);
        $settings = [
            // Manticore connection + target index — syncProductEmbedding mirrors
            // each vector into <index>_vec via pushProductVector. Without 'index'
            // here vectorIndexName() falls back to "products_vec", so on any store
            // whose index isn't the default ("kiborg_products", "militex_products")
            // the vectors land in the WRONG table and semantic search sees nothing.
            'index'    => $this->config->get('module_oc_kit_advanced_search_pro_index') ?: 'products',
            'host'     => $this->config->get('module_oc_kit_advanced_search_pro_host') ?: '127.0.0.1',
            'port'     => $this->config->get('module_oc_kit_advanced_search_pro_port') ?: '9306',
            'login'    => $this->config->get('module_oc_kit_advanced_search_pro_login') ?: '',
            'password' => $this->config->get('module_oc_kit_advanced_search_pro_password') ?: '',
            'vector_enabled' => $this->config->get('module_oc_kit_advanced_search_pro_vector_enabled'),
            'ai_provider' => $this->config->get('module_oc_kit_advanced_search_pro_ai_provider') ?: 'openai',
            'ai_api_key' => $this->config->get('module_oc_kit_advanced_search_pro_ai_api_key') ?: '',
            'ai_embedding_model' => $this->config->get('module_oc_kit_advanced_search_pro_ai_embedding_model') ?: 'text-embedding-3-large',
            'ai_budget_monthly' => $this->config->get('module_oc_kit_advanced_search_pro_ai_budget_monthly') ?: 50,
            'ai_budget_daily_limit' => $this->config->get('module_oc_kit_advanced_search_pro_ai_budget_daily_limit') ?: 1000,
            'ai_auto_block' => $this->config->get('module_oc_kit_advanced_search_pro_ai_auto_block') ?: 1,
            'ai_embed_fields' => $this->config->get('module_oc_kit_advanced_search_pro_ai_embed_fields') ?: [
                'name' => 1,
                'description' => 1,
                'attributes' => 0,
                'categories' => 0,
                'manufacturer' => 0,
                'tags' => 0
            ]
        ];

        if (empty($settings['vector_enabled'])) {
            return 0;
        }

        $settings['ai_api_key'] = $this->decryptRuntimeSecret((string)$settings['ai_api_key']);
        if ($settings['ai_api_key'] === '') {
            return 0;
        }

        // Make sure the KNN table exists before any vector is pushed. A freshly
        // enabled vector store has no <index>_vec yet, and pushProductVector would
        // silently fail to mirror (embeddings would sit in the DB only). Non-
        // destructive (CREATE TABLE IF NOT EXISTS); cheap enough to run per batch.
        try {
            $this->ensureVectorIndex($settings, false);
        } catch (\Throwable $e) {
            error_log('[ASP] ensureVectorIndex in processEmbeddingQueue failed: ' . $e->getMessage());
        }

        // Detect embedding model change — auto-queue all products for re-embed.
        $currentModel = (string)($settings['ai_embedding_model'] ?? '');
        $storedModel  = (string)$this->getMeta('embed_model', '');
        if ($currentModel !== '' && $storedModel !== '' && $currentModel !== $storedModel) {
            $this->queueAllProductsForEmbedding();
        }
        if ($currentModel !== '') {
            $this->setMeta('embed_model', $currentModel);
        }

        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "asp_embedding_queue`
             WHERE status = 'pending'
             ORDER BY id ASC
             LIMIT " . $limit
        );

        if (!$rows->rows) {
            return 0;
        }

        $processed = 0;
        foreach ($rows->rows as $row) {
            $queueId = (int)$row['id'];
            $productId = (int)$row['product_id'];

            try {
                $ok = $this->syncProductEmbedding($productId, $settings);
                if ($ok) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "asp_embedding_queue` SET status = 'done', updated_at = NOW() WHERE id = '" . $queueId . "'");
                    $processed++;
                } else {
                    $this->db->query("UPDATE `" . DB_PREFIX . "asp_embedding_queue` SET status = 'error', attempts = attempts + 1, updated_at = NOW() WHERE id = '" . $queueId . "'");
                }
            } catch (\Throwable $e) {
                error_log('[ASP] Embedding queue item #' . $queueId . ' failed: ' . $e->getMessage());
                $this->db->query("UPDATE `" . DB_PREFIX . "asp_embedding_queue` SET status = 'error', attempts = attempts + 1, updated_at = NOW() WHERE id = '" . $queueId . "'");
            }
        }

        return $processed;
    }

    public function syncProductEmbedding($product_id, $settings = []) {
        $data = $this->getProductIndexData((int)$product_id);
        if (!$data) {
            return false;
        }

        $provider = strtolower((string)($settings['ai_provider'] ?? 'openai'));
        $apiKey = (string)($settings['ai_api_key'] ?? '');
        $model = (string)($settings['ai_embedding_model'] ?? 'text-embedding-3-large');
        $fields = $settings['ai_embed_fields'] ?? [];

        $text = $this->buildEmbeddingText($data, $fields, (int)$product_id);
        if ($text === '') {
            return false;
        }

        $estimatedTokens = $this->estimateTokens($text);
        $estimatedCost = $this->estimateEmbeddingCost($model, $estimatedTokens);
        if (!empty($settings['ai_auto_block']) && $this->isAiBudgetExceeded($estimatedTokens, $estimatedCost, $settings)) {
            return false;
        }

        $vector = $this->generateEmbeddingVector($text, $provider, $apiKey, $model);
        if (!$vector) {
            return false;
        }

        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "asp_embedding`
             SET product_id = '" . (int)$product_id . "',
                 provider = '" . $this->db->escape($provider) . "',
                 model = '" . $this->db->escape($model) . "',
                 vector_json = '" . $this->db->escape(json_encode($vector)) . "',
                 updated_at = NOW()
             ON DUPLICATE KEY UPDATE
                 provider = VALUES(provider),
                 model = VALUES(model),
                 vector_json = VALUES(vector_json),
                 updated_at = NOW()"
        );

        // Mirror the vector into the Manticore KNN table so semantic search sees it.
        try {
            $this->pushProductVector((int)$product_id, $vector, $settings);
        } catch (\Throwable $e) {
            error_log('[ASP] pushProductVector failed for #' . (int)$product_id . ': ' . $e->getMessage());
        }

        $this->logAiUsage($estimatedTokens, $estimatedCost);

        return true;
    }

    public function buildEmbeddingText($productData, $fields, $product_id) {
        $parts = [];

        $includeName = !isset($fields['name']) || (int)$fields['name'] === 1;
        $includeDescription = !isset($fields['description']) || (int)$fields['description'] === 1;
        $includeAttributes = !empty($fields['attributes']);
        $includeCategories = !empty($fields['categories']);
        $includeManufacturer = !empty($fields['manufacturer']);
        $includeTags = !empty($fields['tags']);

        if ($includeName && !empty($productData['name'])) {
            $parts[] = $productData['name'];
        }
        if ($includeDescription && !empty($productData['description'])) {
            $parts[] = strip_tags(html_entity_decode((string)$productData['description'], ENT_QUOTES, 'UTF-8'));
        }
        if ($includeManufacturer && !empty($productData['manufacturer'])) {
            $parts[] = $productData['manufacturer'];
        }
        if ($includeTags && !empty($productData['tag'])) {
            $parts[] = $productData['tag'];
        }

        if ($includeCategories) {
            $rows = $this->db->query(
                "SELECT cd.name
                 FROM " . DB_PREFIX . "product_to_category pc
                 INNER JOIN " . DB_PREFIX . "category_description cd
                    ON (cd.category_id = pc.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                 WHERE pc.product_id = '" . (int)$product_id . "'"
            );
            foreach ($rows->rows as $row) {
                if (!empty($row['name'])) {
                    $parts[] = $row['name'];
                }
            }
        }

        if ($includeAttributes) {
            $rows = $this->db->query(
                "SELECT text
                 FROM " . DB_PREFIX . "product_attribute
                 WHERE product_id = '" . (int)$product_id . "'
                   AND language_id = '" . (int)$this->config->get('config_language_id') . "'"
            );
            foreach ($rows->rows as $row) {
                if (!empty($row['text'])) {
                    $parts[] = $row['text'];
                }
            }
        }

        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));
        if (utf8_strlen($text) > 8000) {
            $text = utf8_substr($text, 0, 8000);
        }

        return $text;
    }

    public function ensureNativeFulltextIndex() {
        $dbName = defined('DB_DATABASE') ? (string)DB_DATABASE : '';
        if ($dbName === '') {
            return;
        }

        try {
            $row = $this->db->query(
                "SELECT COUNT(*) AS total
                 FROM information_schema.statistics
                 WHERE table_schema = '" . $this->db->escape($dbName) . "'
                   AND table_name = '" . $this->db->escape(DB_PREFIX . "product_description") . "'
                   AND index_name = 'asp_pd_ft'"
            )->row;

            if ((int)($row['total'] ?? 0) > 0) {
                return;
            }

            $this->db->query(
                "ALTER TABLE `" . DB_PREFIX . "product_description`
                 ADD FULLTEXT `asp_pd_ft` (`name`, `description`, `tag`)"
            );
        } catch (\Exception $e) {
            // Keep compatibility with DB engines/configs where FULLTEXT creation is restricted.
        }
    }

    public function install() {
        $this->createTables();
    }

    public function uninstall() {
        // Keep data by default
    }

    public function createTables() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_stats` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `date` DATE NOT NULL,
            `queries` INT NOT NULL DEFAULT 0,
            `no_results` INT NOT NULL DEFAULT 0,
            `avg_latency_ms` INT NOT NULL DEFAULT 0,
            `p95_latency_ms` INT NOT NULL DEFAULT 0,
            `cache_hit_percent` INT NOT NULL DEFAULT 0,
            `errors` INT NOT NULL DEFAULT 0,
            `ai_tokens` INT NOT NULL DEFAULT 0,
            `ai_cost` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
            PRIMARY KEY (`id`),
            UNIQUE KEY `date_unique` (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_query_log` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `query` VARCHAR(255) NOT NULL,
            `results` INT NOT NULL DEFAULT 0,
            `latency_ms` INT NOT NULL DEFAULT 0,
            `session_id` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `query_idx` (`query`),
            KEY `created_at_idx` (`created_at`),
            KEY `session_idx` (`session_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_query_rule` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `query_normalized` VARCHAR(255) NOT NULL,
            `rewritten_query` VARCHAR(255) NOT NULL DEFAULT '',
            `expanded_json` TEXT NULL,
            `intent` VARCHAR(64) NOT NULL DEFAULT '',
            `source` VARCHAR(32) NOT NULL DEFAULT 'manual',
            `hits` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `query_unique` (`query_normalized`),
            KEY `source_idx` (`source`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_synonym_group` (
            `group_id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(128) DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_synonym` (
            `synonym_id` INT NOT NULL AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `term` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`synonym_id`),
            KEY `group_id_idx` (`group_id`),
            KEY `term_idx` (`term`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_synonym_pending` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `query` VARCHAR(128) NOT NULL,
            `terms_json` TEXT NOT NULL,
            `source` VARCHAR(32) NOT NULL DEFAULT 'ai',
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `created_at` DATETIME NOT NULL,
            `reviewed_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `query_unique` (`query`),
            KEY `status_idx` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_cron_log` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(32) NOT NULL,
            `status` VARCHAR(16) NOT NULL,
            `message` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `created_at_idx` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_index_queue` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `entity_type` VARCHAR(32) NOT NULL,
            `entity_id` INT NOT NULL,
            `action` VARCHAR(16) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `attempts` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `entity_idx` (`entity_type`,`entity_id`),
            KEY `status_idx` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_attribute_map` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `attribute_id` INT NOT NULL,
            `type` VARCHAR(16) NOT NULL,
            `is_filter` TINYINT(1) NOT NULL DEFAULT 0,
            `is_search` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `attribute_unique` (`attribute_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_meta` (
            `meta_key` VARCHAR(64) NOT NULL,
            `meta_value` TEXT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`meta_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_embedding` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `product_id` INT NOT NULL,
            `provider` VARCHAR(32) NOT NULL,
            `model` VARCHAR(128) NOT NULL,
            `vector_json` MEDIUMTEXT NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_unique` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_embedding_queue` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `product_id` INT NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `attempts` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_queue_unique` (`product_id`),
            KEY `status_idx` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        // Speeds up semantic candidate fetches by model + recency.
        // Check before adding to avoid a PHP warning on duplicate key (mysqli raises
        // a warning before the exception, which cannot be suppressed by try/catch alone).
        $idxCheck = $this->db->query(
            "SHOW INDEX FROM `" . DB_PREFIX . "asp_embedding` WHERE Key_name = 'model_updated_idx'"
        );
        if (!$idxCheck->rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "asp_embedding` ADD KEY `model_updated_idx` (`model`, `updated_at`)");
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_dictionary` (
            `id`       INT          NOT NULL AUTO_INCREMENT,
            `word`     VARCHAR(120) NOT NULL,
            `stem`     VARCHAR(120) NOT NULL,
            `language` VARCHAR(5)   NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            UNIQUE KEY `word_lang_unique` (`word`, `language`),
            KEY `language_idx` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asp_native_index` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` INT UNSIGNED NOT NULL,
            `language_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `field_type` VARCHAR(20) NOT NULL,
            `weight` TINYINT UNSIGNED NOT NULL DEFAULT 10,
            `content` TEXT NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_product_lang` (`product_id`, `language_id`),
            FULLTEXT KEY `ft_content` (`content`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->ensureNativeFulltextIndex();
    }

    private function getManticoreClient($settings) {
        // require_once is already done by the facade
        return new ManticoreClient([
            'host'    => $settings['host']     ?? '127.0.0.1',
            'port'    => $settings['port']     ?? 9306,
            'index'   => $settings['index']    ?? 'products',
            'user'    => $settings['login']    ?? '',
            'pass'    => $settings['password'] ?? '',
            'timeout' => 2
        ]);
    }

    private function getSphinxClient($settings) {
        return new SphinxClient([
            'host'    => $settings['sphinx_host']     ?? ($settings['host'] ?? '127.0.0.1'),
            'port'    => $settings['sphinx_port']     ?? 9306,
            'index'   => $settings['sphinx_index']    ?? ($settings['index'] ?? 'products'),
            'user'    => $settings['sphinx_login']    ?? ($settings['login'] ?? ''),
            'pass'    => $settings['sphinx_password'] ?? ($settings['password'] ?? ''),
            'timeout' => 2
        ]);
    }

    private function estimateTokens($text) {
        $text = (string)$text;
        if ($text === '') {
            return 0;
        }

        $chars = utf8_strlen($text);
        return max(1, (int)ceil($chars / 4));
    }

    private function estimateEmbeddingCost($model, $tokens) {
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

    private function isAiBudgetExceeded($tokens, $cost, $settings) {
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

    private function decryptRuntimeSecret($value) {
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

    private function generateEmbeddingVector($text, $provider, $apiKey, $model) {
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

    private function getMeta($key, $default = null) {
        $query = $this->db->query(
            "SELECT meta_value FROM `" . DB_PREFIX . "asp_meta` WHERE meta_key = '" . $this->db->escape((string)$key) . "' LIMIT 1"
        );

        if (!empty($query->row) && array_key_exists('meta_value', $query->row)) {
            return $query->row['meta_value'];
        }

        return $default;
    }

    private function setMeta($key, $value) {
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
}
