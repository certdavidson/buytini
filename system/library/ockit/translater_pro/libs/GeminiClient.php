<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Libs;

use OcKit\TranslaterPro\Exceptions\ApiException;

class GeminiClient
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gemini-2.0-flash')
    {
        $this->apiKey = $apiKey;
        $this->model  = $model;
    }

    /**
     * @param  array  $fields         field_name => source_text
     * @param  string $sourceLangName Human-readable source language name
     * @param  string $targetLangName Human-readable target language name
     * @param  string $customPrompt   Additional instructions from settings
     * @return array                  field_name => translated_text
     * @throws ApiException
     */
    public function translate(array $fields, string $sourceLangName, string $targetLangName, string $customPrompt = ''): array
    {
        $systemPrompt = $this->buildSystemPrompt($sourceLangName, $targetLangName, $customPrompt);
        $userContent  = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $userContent]]],
            ],
            'generationConfig' => [
                'temperature'     => 0.3,
                'responseMimeType' => 'application/json',
            ],
        ];

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $body = $this->request($url, $payload);

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $this->parseJson($text);
    }

    private function buildSystemPrompt(string $source, string $target, string $customPrompt): string
    {
        $base = $customPrompt !== '' ? $customPrompt : OpenAiClient::DEFAULT_PROMPT;
        return str_replace(['{source}', '{target}'], [$source, $target], $base);
    }

    private function request(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new ApiException("cURL error: {$err}");
        }

        $decoded = json_decode($body, true);

        if ($code !== 200) {
            $errMsg = $decoded['error']['message'] ?? $body;
            throw new ApiException("Gemini API error [{$code}]: {$errMsg}", $code);
        }

        return $decoded;
    }

    private function parseJson(string $text): array
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            throw new ApiException("API returned invalid JSON: " . substr($text, 0, 200));
        }

        return array_filter($data, fn($v) => is_string($v) && trim(strip_tags($v)) !== '');
    }
}
