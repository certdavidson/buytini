<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

use OcKit\TranslaterPro\Dto\TranslationItem;
use OcKit\TranslaterPro\Exceptions\ApiException;
use OcKit\TranslaterPro\Exceptions\TranslaterProException;

/**
 * Orchestrates translation of a single content item:
 * fetch source → call API → save result → log errors.
 */
class Translator
{
    private $apiClient;        // OpenAiClient|DeepSeekClient|GeminiClient
    private ContentProvider $provider;
    private ContentSaver    $saver;
    private DbLogger        $dbLogger;
    private string          $provider_name;
    private string          $customPrompt;

    /** Human-readable language names for the API prompt. */
    private array $langNames = [
        'uk-ua' => 'Ukrainian',
        'uk'    => 'Ukrainian',
        'ru-ru' => 'Russian',
        'ru'    => 'Russian',
        'en-gb' => 'English',
        'en-us' => 'English',
        'en'    => 'English',
        'de-de' => 'German',
        'de'    => 'German',
        'pl-pl' => 'Polish',
        'pl'    => 'Polish',
        'fr-fr' => 'French',
        'fr'    => 'French',
    ];

    public function __construct(
        object      $apiClient,
        ContentProvider $provider,
        ContentSaver    $saver,
        DbLogger        $dbLogger,
        string          $providerName,
        string          $customPrompt = ''
    ) {
        $this->apiClient    = $apiClient;
        $this->provider     = $provider;
        $this->saver        = $saver;
        $this->dbLogger     = $dbLogger;
        $this->provider_name = $providerName;
        $this->customPrompt = $customPrompt;
    }

    /**
     * Translates one item.
     *
     * @return array{success: bool, translated_count: int, error: string}
     */
    public function translateOne(string $type, int $itemId, string $sourceLang, string $targetLang): array
    {
        $item = $this->provider->getOne($type, $itemId, $sourceLang);

        if (!$item || empty($item->fields)) {
            return ['success' => false, 'translated_count' => 0, 'error' => 'Source content not found or empty.'];
        }

        $sourceName = $this->langNames[$sourceLang] ?? $sourceLang;
        $targetName = $this->langNames[$targetLang] ?? $targetLang;

        try {
            $translated = $this->apiClient->translate($item->fields, $sourceName, $targetName, $this->customPrompt);

            if (empty($translated)) {
                $this->dbLogger->logError($type, $itemId, $sourceLang, $targetLang, $this->provider_name,
                    'API returned empty result — item skipped.');
                return ['success' => false, 'translated_count' => 0, 'error' => 'API returned empty result.'];
            }

            $this->saver->save($type, $itemId, $targetLang, $translated);

            $this->dbLogger->logSuccess($type, $itemId, $sourceLang, $targetLang, $this->provider_name, count($translated));

            return ['success' => true, 'translated_count' => count($translated), 'error' => ''];

        } catch (ApiException $e) {
            $this->dbLogger->logError($type, $itemId, $sourceLang, $targetLang, $this->provider_name, $e->getMessage());
            return ['success' => false, 'translated_count' => 0, 'error' => $e->getMessage()];

        } catch (TranslaterProException $e) {
            $this->dbLogger->logError($type, $itemId, $sourceLang, $targetLang, $this->provider_name, $e->getMessage());
            return ['success' => false, 'translated_count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Translates a batch of items (used by cron).
     *
     * @return array{done: int, failed: int}
     */
    public function translateBatch(string $type, string $sourceLang, string $targetLang, int $batchSize): array
    {
        $items = $this->provider->getItems($type, $sourceLang, $targetLang, 0, $batchSize);
        $done  = 0;
        $failed = 0;

        foreach ($items as $item) {
            $result = $this->translateOne($type, $item->itemId, $sourceLang, $targetLang);
            if ($result['success']) {
                $done++;
            } else {
                $failed++;
            }
        }

        return ['done' => $done, 'failed' => $failed];
    }
}
