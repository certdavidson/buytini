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

/**
 * Loads SchemaDataProviderInterface implementations registered in the admin
 * config and feeds their context into SchemaTemplateEngine.
 *
 * Config key: module_oc_kit_seo_core_schema_providers (JSON string[])
 * Each entry — fully-qualified class name implementing
 * {@see SchemaDataProviderInterface}.
 *
 * Security:
 *   - Class must exist (via autoloader) — bad entries are silently skipped.
 *   - Class must implement the interface (instanceof check).
 *   - No `new` of arbitrary admin-supplied strings without verification.
 */
class SchemaProviderRegistry
{
    private $config;
    /** @var SchemaDataProviderInterface[] */
    private ?array $providers = null;

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * @return SchemaDataProviderInterface[]
     */
    public function all(): array
    {
        if ($this->providers !== null) return $this->providers;
        $this->providers = [];

        $raw = (string)$this->config->get('module_oc_kit_seo_core_schema_providers');
        if ($raw === '') return $this->providers;

        $list = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);
        if (!is_array($list)) return $this->providers;

        foreach ($list as $fqcn) {
            $fqcn = (string)$fqcn;
            if ($fqcn === '' || !class_exists($fqcn)) continue;
            try {
                $obj = new $fqcn();
            } catch (\Throwable $e) {
                continue;
            }
            if ($obj instanceof SchemaDataProviderInterface) {
                $this->providers[] = $obj;
            }
        }
        return $this->providers;
    }

    /**
     * Aggregate the data of every supporting provider for the current request.
     * Later providers can override earlier keys (config order matters).
     *
     * @param  array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function collect(string $route, array $params, int $languageId): array
    {
        $merged = [];
        foreach ($this->all() as $p) {
            if (!$p->supportsRoute($route, $params)) continue;
            try {
                $data = $p->getData($route, $params, $languageId);
            } catch (\Throwable $e) {
                error_log('[SCF schema-provider] ' . get_class($p) . ' failed: ' . $e->getMessage());
                continue;
            }
            if (is_array($data) && $data) {
                $merged = array_replace($merged, $data);
            }
        }
        return $merged;
    }
}
