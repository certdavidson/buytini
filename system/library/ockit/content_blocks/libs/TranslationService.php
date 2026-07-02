<?php
/**
 * Content Blocks Pro — OpenCart 3.x Module
 *
 * @package   OcKit\ContentBlocks
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Libs;

use OcKit\ContentBlocks\Exceptions\TranslationException;

/**
 * Translates text content within a block using OpenAI API.
 *
 * Only text-bearing fields are translated (content, question, answer, etc.).
 * URLs, IDs, image paths, and CSS values are preserved as-is.
 */
class TranslationService
{
    private string $apiKey;
    private string $model;
    private int    $timeout;

    /** Fields in element data that contain translatable text */
    private const TEXT_FIELDS = ['content', 'question', 'answer', 'title', 'caption', 'alt'];

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini', int $timeout = 30)
    {
        $this->apiKey  = $apiKey;
        $this->model   = $model;
        $this->timeout = $timeout;
    }

    /**
     * Translates all text content in a block data array into the target language.
     * The block data is the raw JS-sent structure (not a DTO).
     *
     * @param array  $blockData   Block structure from JS (rows/elements with data fields)
     * @param string $targetLang  Language name for the prompt, e.g. 'Ukrainian', 'English'
     * @param int    $targetLangId  Language ID to store translated content under
     * @return array Modified block data with translated content
     * @throws TranslationException
     */
    public function translateBlock(array $blockData, string $targetLang, int $targetLangId, string $sourceLang = '', int $sourceLangId = 0): array
    {
        // Collect all translatable strings with their paths
        $strings = [];
        $this->collectStrings($blockData, [], $strings, $sourceLangId);

        if (empty($strings)) {
            return $blockData;
        }

        // Translate in one batch request
        $texts     = array_column($strings, 'text');
        $translated = $this->translateTexts($texts, $targetLang);

        // Apply translations back to the data structure
        foreach ($strings as $i => $item) {
            if (isset($translated[$i])) {
                $blockData = $this->setValueAtPath($blockData, $item['path'], $translated[$i], $targetLangId);
            }
        }

        return $blockData;
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    /**
     * Recursively collect translatable strings from block data.
     * Only collects from element 'data' arrays, not from params/css.
     */
    private function collectStrings(array $data, array $path, array &$strings, int $sourceLangId = 0): void
    {
        foreach ($data as $key => $value) {
            $currentPath = array_merge($path, [$key]);

            if ($key === 'data' && is_array($value)) {
                // CB stores element.data as {lang_id: {field: text}}.
                // Pick the source-language slot (or any non-empty slot as fallback).
                $sourceData = null;
                if ($sourceLangId > 0 && isset($value[$sourceLangId]) && is_array($value[$sourceLangId])) {
                    $sourceData = $value[$sourceLangId];
                }
                if (!$sourceData) {
                    foreach ($value as $perLang) {
                        if (is_array($perLang)) {
                            // Pick the first slot that has any TEXT_FIELDS filled
                            foreach (self::TEXT_FIELDS as $f) {
                                if (!empty($perLang[$f]) && is_string($perLang[$f]) && trim($perLang[$f]) !== '') {
                                    $sourceData = $perLang;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if (is_array($sourceData)) {
                    foreach (self::TEXT_FIELDS as $field) {
                        $text = $sourceData[$field] ?? '';
                        if (is_string($text) && trim($text) !== '') {
                            $strings[] = [
                                'text' => $text,
                                'path' => array_merge($currentPath, [$field]),
                            ];
                        }
                    }
                }
                continue;
            }

            if (is_array($value)) {
                $this->collectStrings($value, $currentPath, $strings, $sourceLangId);
            }
        }
    }

    /**
     * Batch translate an array of strings via OpenAI.
     *
     * @param string[] $texts
     * @return string[]
     * @throws TranslationException
     */
    private function translateTexts(array $texts, string $targetLang): array
    {
        if (empty($texts)) {
            return [];
        }

        // Build a JSON object {"1":"...","2":"..."} so multi-line content,
        // HTML, lists and quotes survive intact — line-based numbering breaks
        // any text that contains a newline.
        $input = [];
        foreach ($texts as $i => $text) {
            $input[(string)($i + 1)] = (string)$text;
        }
        $jsonInput = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = "Translate every value in the JSON object below to {$targetLang}. "
            . "Return ONLY a single valid JSON object with the SAME keys and translated values. "
            . "Preserve HTML tags, line breaks, and inline formatting. Do not add explanations or wrap in code fences.\n\n"
            . $jsonInput;

        $response = $this->callOpenAi($prompt);

        return $this->parseJsonResponse($response, count($texts));
    }

    /**
     * @throws TranslationException
     */
    private function callOpenAi(string $prompt): string
    {
        if (empty($this->apiKey)) {
            throw new TranslationException('OpenAI API key is not configured');
        }

        $payload = json_encode([
            'model'    => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            throw new TranslationException('OpenAI request failed (cURL error ' . $errno . ')');
        }

        $json = json_decode($body, true);

        if (!isset($json['choices'][0]['message']['content'])) {
            $error = $json['error']['message'] ?? 'Unknown API error';
            throw new TranslationException('OpenAI error: ' . $error);
        }

        return $json['choices'][0]['message']['content'];
    }

    /**
     * Parse JSON response {"1":"...","2":"..."} into a 0-indexed array.
     * Tolerates code fences and surrounding text.
     */
    private function parseJsonResponse(string $response, int $count): array
    {
        $result = array_fill(0, $count, '');

        $body = trim($response);
        // Strip ``` or ```json fences if the model added them anyway
        $body = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $body);
        // Extract the first {...} block to be defensive
        if (preg_match('/\{.*\}/s', $body, $m)) {
            $body = $m[0];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            // Surface malformed AI responses instead of silently returning empty
            // strings — caller can show a real error to the admin and we don't
            // wipe original content with blanks.
            $snippet = mb_substr(trim($response), 0, 300);
            throw new TranslationException('Translation API returned non-JSON response: ' . $snippet);
        }
        foreach ($decoded as $k => $v) {
            $idx = (int)$k - 1;
            if ($idx >= 0 && $idx < $count && is_string($v)) {
                $result[$idx] = $v;
            }
        }

        return $result;
    }

    /**
     * Set a value at $blockData[...]['data'][$targetLangId][$field] = $value.
     * Path ends with [..., 'data', $field] (penultimate is 'data', last is field name).
     */
    private function setValueAtPath(array $data, array $path, string $value, int $targetLangId): array
    {
        $field = array_pop($path);   // e.g. 'content'
        // path now ends with 'data' — descend up to 'data', then write into the
        // target-language slot inside it.
        $current = &$data;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return $data;
            }
            $current = &$current[$key];
        }
        // $current is the 'data' dict; ensure target-language slot exists.
        if (!isset($current[$targetLangId]) || !is_array($current[$targetLangId])) {
            $current[$targetLangId] = [];
        }
        $current[$targetLangId][$field] = $value;
        return $data;
    }
}
