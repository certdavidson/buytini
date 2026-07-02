<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

use OcKit\TranslaterPro\Exceptions\TranslaterProException;

class ApiClientFactory
{
    /**
     * Creates the correct API client based on the configured provider.
     *
     * @return OpenAiClient|DeepSeekClient|GeminiClient
     * @throws TranslaterProException when provider is unknown or key is missing
     */
    public static function create($config): object
    {
        $prefix   = 'module_oc_kit_translater_pro_';
        $provider = (string)$config->get($prefix . 'api_provider') ?: 'openai';

        switch ($provider) {
            case 'openai':
                $key   = (string)$config->get($prefix . 'openai_key');
                $model = (string)$config->get($prefix . 'openai_model') ?: 'gpt-4o-mini';
                if (!$key) {
                    throw new TranslaterProException('OpenAI API key is not configured.');
                }
                return new OpenAiClient($key, $model);

            case 'deepseek':
                $key   = (string)$config->get($prefix . 'deepseek_key');
                $model = (string)$config->get($prefix . 'deepseek_model') ?: 'deepseek-chat';
                if (!$key) {
                    throw new TranslaterProException('DeepSeek API key is not configured.');
                }
                return new DeepSeekClient($key, $model);

            case 'gemini':
                $key   = (string)$config->get($prefix . 'gemini_key');
                $model = (string)$config->get($prefix . 'gemini_model') ?: 'gemini-2.0-flash';
                if (!$key) {
                    throw new TranslaterProException('Gemini API key is not configured.');
                }
                return new GeminiClient($key, $model);

            default:
                throw new TranslaterProException("Unknown API provider: {$provider}");
        }
    }

    /**
     * Returns the current provider name from config.
     */
    public static function getProvider($config): string
    {
        return (string)$config->get('module_oc_kit_translater_pro_api_provider') ?: 'openai';
    }
}
