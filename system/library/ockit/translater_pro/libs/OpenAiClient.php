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

class OpenAiClient
{
    const DEFAULT_PROMPT =
        "You are a professional eCommerce translator whose sole task is to convert product-related text from the source language into the target language with absolute fidelity. Follow these rules strictly:\n\n" .
        "1. **Accuracy and Faithfulness**\n" .
        "   - Translate **all** parts of the text as literally as possible, without adding any new information.\n" .
        "   - Do not \"improve\" or \"polish\" the meaning: e.g., if the original says \"navy blue,\" do not render it as \"dark blue\". Keep it as \"navy blue\".\n" .
        "   - Avoid free paraphrasing: e.g., \"100% cotton\" must remain \"100% cotton\" → \"100% бавовна,\" not \"з натурального бавовняного матеріалу.\"\n\n" .
        "2. **No Inventions or Assumptions**\n" .
        "   - If a field is missing or marked empty, do not invent or add placeholder text like \"Not specified\" unless it already appears in the source.\n" .
        "   - If there are unclear abbreviations or codes (e.g., \"SKU: AB123\"), keep them exactly as they are.\n\n" .
        "3. **Word Order and Structure**\n" .
        "   - Preserve the original order of lists, bullet points, headings, and paragraphs.\n" .
        "   - If the source uses bullet lists (–, •) or numbered lists, replicate the same list structure in the same sequence.\n\n" .
        "4. **Preserve Markup and Placeholders**\n" .
        "   - Do not alter HTML tags, placeholders, or template variables (e.g., {PRODUCT_NAME}, [PRICE], %DISCOUNT%).\n" .
        "   - If the source has <b>Special Offer</b>, the translation should be <b>Спеціальна пропозиція</b>.\n" .
        "   - Skip (do not output) any content between markers <!--exclude--> and <!--endexclude-->.\n\n" .
        "5. **Domain-Specific Terminology**\n" .
        "   - Retain all standard eCommerce terms (SKU, UPC, ASIN, dimensions, etc.).\n" .
        "   - Do not change brand names, model numbers, or unique product codes.\n\n" .
        "6. **Units and Currency**\n" .
        "   - If the source shows a price in USD (\"\\$79.99\"), keep \"\\$79.99\" as is. Do not convert.\n\n" .
        "7. **No Additional Comments**\n" .
        "   - Do not add any commentary or personal opinions. Be neutral and objective.\n" .
        "   - If the source contains emojis or exclamatory words (\"amazing!\"), convey the emotional tone without adding new emojis or slang.\n\n" .
        "Translate from {source} to {target}.\n" .
        "Return ONLY a valid JSON object with the same keys as the input. Do not add any explanation outside the JSON.\n";

    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini', string $baseUrl = 'https://api.openai.com/v1')
    {
        $this->apiKey  = $apiKey;
        $this->model   = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Translates an array of fields via OpenAI Chat Completions.
     *
     * @param  array  $fields         field_name => source_text
     * @param  string $sourceLangName Human-readable source language name
     * @param  string $targetLangName Human-readable target language name
     * @param  string $customPrompt   Additional instructions from settings
     * @return array                  field_name => translated_text (only non-empty results)
     * @throws ApiException
     */
    public function translate(array $fields, string $sourceLangName, string $targetLangName, string $customPrompt = ''): array
    {
        $systemPrompt = $this->buildSystemPrompt($sourceLangName, $targetLangName, $customPrompt);
        $userContent  = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $payload = [
            'model'       => $this->model,
            'temperature' => 0.3,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userContent],
            ],
            'response_format' => ['type' => 'json_object'],
        ];

        $responseBody = $this->request('/chat/completions', $payload);

        $text = $responseBody['choices'][0]['message']['content'] ?? '';
        return $this->parseJson($text);
    }

    private function buildSystemPrompt(string $source, string $target, string $customPrompt): string
    {
        // If a custom prompt is configured, it IS the full system prompt.
        // Placeholders {source} and {target} are replaced at runtime.
        $base = $customPrompt !== '' ? $customPrompt : self::DEFAULT_PROMPT;

        return str_replace(['{source}', '{target}'], [$source, $target], $base);
    }

    private function request(string $endpoint, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
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
            throw new ApiException("OpenAI API error [{$code}]: {$errMsg}", $code);
        }

        return $decoded;
    }

    private function parseJson(string $text): array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            throw new ApiException("API returned invalid JSON: " . substr($text, 0, 200));
        }

        // Keep only non-empty string values
        return array_filter($data, fn($v) => is_string($v) && trim(strip_tags($v)) !== '');
    }
}
