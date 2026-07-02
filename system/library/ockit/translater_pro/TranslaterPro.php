<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @package   OcKit\TranslaterPro
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro;

// ─── Autoload dependencies ─────────────────────────────────────────────────────
require_once __DIR__ . '/exceptions/TranslaterProException.php';
require_once __DIR__ . '/exceptions/ApiException.php';
require_once __DIR__ . '/dto/TranslationItem.php';
require_once __DIR__ . '/libs/TypeDefinitions.php';
require_once __DIR__ . '/libs/DbLogger.php';
require_once __DIR__ . '/libs/Stats.php';
require_once __DIR__ . '/libs/ContentProvider.php';
require_once __DIR__ . '/libs/ContentSaver.php';
require_once __DIR__ . '/libs/OpenAiClient.php';
require_once __DIR__ . '/libs/DeepSeekClient.php';
require_once __DIR__ . '/libs/GeminiClient.php';
require_once __DIR__ . '/libs/ApiClientFactory.php';
require_once __DIR__ . '/libs/Translator.php';
require_once __DIR__ . '/libs/StoreContext.php';

use OcKit\TranslaterPro\Libs\ApiClientFactory;
use OcKit\TranslaterPro\Libs\ContentProvider;
use OcKit\TranslaterPro\Libs\ContentSaver;
use OcKit\TranslaterPro\Libs\DbLogger;
use OcKit\TranslaterPro\Libs\Stats;
use OcKit\TranslaterPro\Libs\StoreContext;
use OcKit\TranslaterPro\Libs\Translator;
use OcKit\TranslaterPro\Libs\TypeDefinitions;

/**
 * Main facade — single entry point for OC controllers.
 */
class TranslaterPro
{
    private $registry;
    private DbLogger        $dbLogger;
    private Stats           $stats;
    private ContentProvider $contentProvider;
    private ContentSaver    $contentSaver;
    private StoreContext    $storeContext;
    private bool            $licensed = false;

    public function __construct($registry)
    {
        $this->registry = $registry;

        $db     = $registry->get('db');
        $config = $registry->get('config');

        $this->dbLogger        = new DbLogger($db);
        $this->stats           = new Stats($db);
        $this->contentProvider = new ContentProvider($db);
        $this->contentSaver    = new ContentSaver($db);
        $this->storeContext    = new StoreContext($db, $config);
        $this->licensed        = $this->storeContext->isActive();

        if (!$this->licensed && php_sapi_name() !== 'cli') {
            $token = $registry->get('session')->data['user_token'] ?? '';
            if ($token !== '') {
                $registry->get('response')->redirect(
                    $registry->get('url')->link(
                        'extension/module/oc_kit_translater_pro/license',
                        'user_token=' . $token,
                        true
                    )
                );
                exit;
            }
        }
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Translate one item (called from AJAX controller action).
     *
     * @return array{success: bool, translated_count: int, error: string}
     */
    public function translateOne(string $type, int $itemId, string $sourceLang, string $targetLang): array
    {
        return $this->makeTranslator()->translateOne($type, $itemId, $sourceLang, $targetLang);
    }

    /**
     * Translate a batch (used by cron).
     *
     * @return array{done: int, failed: int}
     */
    public function translateBatch(string $type, string $sourceLang, string $targetLang, int $batchSize): array
    {
        return $this->makeTranslator()->translateBatch($type, $sourceLang, $targetLang, $batchSize);
    }

    /**
     * Returns untranslated counts for all content types.
     *
     * @return array<string, int>
     */
    public function getStats(string $sourceLang, string $targetLang): array
    {
        return $this->stats->getAll($sourceLang, $targetLang);
    }

    /**
     * Returns paginated list of items for the UI table.
     * When $overwrite is true, already-translated items are included as well.
     */
    public function getItems(string $type, string $sourceLang, string $targetLang, int $start, int $limit, bool $overwrite = false): array
    {
        $items = $this->contentProvider->getItems($type, $sourceLang, $targetLang, $start, $limit, $overwrite);

        // Convert DTOs to plain arrays for the controller/template
        return array_map(fn($item) => [
            'item_id'      => $item->itemId,
            'display_name' => $item->displayName,
            'fields'       => array_keys($item->fields),
            'preview'      => mb_substr(strip_tags(reset($item->fields)), 0, 120),
        ], $items);
    }

    /**
     * Returns item count for one type.
     * When $overwrite is true, already-translated items are counted as well.
     */
    public function countItems(string $type, string $sourceLang, string $targetLang, bool $overwrite = false): int
    {
        return $this->stats->getOne($type, $sourceLang, $targetLang, $overwrite);
    }

    // ─── Log access ───────────────────────────────────────────────────────────

    public function getLogs(int $start = 0, int $limit = 50, string $status = ''): array
    {
        return $this->dbLogger->getLogs($start, $limit, $status);
    }

    public function countLogs(string $status = ''): int
    {
        return $this->dbLogger->countLogs($status);
    }

    public function clearLogs(): void
    {
        $this->dbLogger->clearLogs();
    }

    // ─── Type helpers ─────────────────────────────────────────────────────────

    public function getTypes(): array
    {
        return TypeDefinitions::keys();
    }

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->dbLogger->install();
    }

    public function uninstall(): void
    {
        $this->dbLogger->uninstall();
    }

    // ─── Static license helpers (lightweight — no full init) ─────────────────

    public static function guardAdmin($registry): void
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        if ($ctx->isActive()) return;
        $token = $registry->get('session')->data['user_token'] ?? '';
        $registry->get('response')->redirect(
            $registry->get('url')->link(
                'extension/module/oc_kit_translater_pro/license',
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

    // ─── Private ──────────────────────────────────────────────────────────────

    private function makeTranslator(): Translator
    {
        $config       = $this->registry->get('config');
        $apiClient    = ApiClientFactory::create($config);
        $providerName = ApiClientFactory::getProvider($config);
        $customPrompt = (string)$config->get('module_oc_kit_translater_pro_prompt');

        return new Translator(
            $apiClient,
            $this->contentProvider,
            $this->contentSaver,
            $this->dbLogger,
            $providerName,
            $customPrompt
        );
    }
}
