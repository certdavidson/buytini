<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\SeoCore\Libs;

use OcKit\SeoCore\Dto\LangPrefixDto;

/**
 * Resolves language from URL prefix and builds prefix strings for URL generation.
 *
 * Config key: module_oc_kit_seo_core_lang_prefixes
 * Format: JSON array [{"language_id":1,"code":"uk","prefix":"","default":true}, ...]
 * Empty prefix = main language (no prefix in URL).
 */
class LanguagePrefixConfig
{
    /** @var LangPrefixDto[] indexed by language_id */
    private array $byId = [];

    /** @var LangPrefixDto[] indexed by prefix string */
    private array $byPrefix = [];

    private ?LangPrefixDto $default = null;

    private $config;
    public function __construct($config) {
        $this->config = $config;
    }

    private function load(): void
    {
        if ($this->byId) return;

        $raw = $this->config->get('module_oc_kit_seo_core_lang_prefixes');
        $entries = $raw ? json_decode($raw, true) : [];

        if (!is_array($entries)) $entries = [];

        foreach ($entries as $entry) {
            $dto = new LangPrefixDto(
                (int)($entry['language_id'] ?? 0),
                (string)($entry['code']        ?? ''),
                (string)($entry['prefix']      ?? ''),
                !empty($entry['default'])
            );

            $this->byId[$dto->languageId]  = $dto;
            $this->byPrefix[$dto->prefix]  = $dto;

            if ($dto->isDefault) {
                $this->default = $dto;
            }
        }
    }

    /**
     * Detect language_id from REQUEST_URI path.
     * Returns null if no prefix matched (caller should use store default).
     */
    public function detectFromUri(string $requestUri): ?int
    {
        $this->load();

        $path = trim(parse_url($requestUri, PHP_URL_PATH) ?: '', '/');
        $firstSegment = explode('/', $path)[0] ?? '';

        foreach ($this->byPrefix as $prefix => $dto) {
            if ($prefix === '') continue; // empty prefix = default, checked last
            if ($firstSegment === $prefix) {
                return $dto->languageId;
            }
        }

        // No prefix matched — use default language
        return $this->default ? $this->default->languageId : null;
    }

    /**
     * Returns the URL prefix string for a given language_id (e.g. "ru", "").
     */
    public function getPrefixById(int $languageId): string
    {
        $this->load();
        return $this->byId[$languageId]->prefix ?? '';
    }

    /**
     * Strips the language prefix from the beginning of a path segment array.
     * Returns [stripped_parts, detected_language_id].
     *
     * @param  string[] $parts
     * @return array{0: string[], 1: int|null}
     */
    public function stripPrefix(array $parts, string $requestUri): array
    {
        $this->load();

        $path = trim(parse_url($requestUri, PHP_URL_PATH) ?: '', '/');
        $firstSegment = explode('/', $path)[0] ?? '';

        foreach ($this->byPrefix as $prefix => $dto) {
            if ($prefix === '') continue;
            if ($firstSegment === $prefix && count($parts) > 0 && $parts[0] === $prefix) {
                return [array_slice($parts, 1), $dto->languageId];
            }
        }

        return [$parts, $this->default ? $this->default->languageId : null];
    }

    /** @return LangPrefixDto[] */
    public function all(): array
    {
        $this->load();
        return array_values($this->byId);
    }
}
