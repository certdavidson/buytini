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
 * Reads custom Schema.org rules from oc_kit_seo_schema_rules, matches the
 * current route, renders the template via SchemaTemplateEngine, and injects
 * the resulting JSON-LD into the document.
 */
class SchemaCustomEditor
{
    /** @var SchemaTemplateEngine */
    private $engine;
    private $db;
    private $config;
    private $document;

    public function __construct(SchemaTemplateEngine $engine, $db, $config, $document)
    {
        $this->engine   = $engine;
        $this->db       = $db;
        $this->config   = $config;
        $this->document = $document;
    }

    /**
     * Find the best-match rule for a route and render JSON-LD.
     * Returns the rendered JSON-LD string, or null if no rule matches.
     */
    public function renderForRoute(string $route, array $getParams, int $languageId): ?string
    {
        $storeId = (int)$this->config->get('config_store_id');

        $rows = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "kit_seo_schema_rules`
             WHERE `store_id` = {$storeId} AND `status` = 1
             ORDER BY `priority` DESC, `rule_id` DESC"
        )->rows;

        foreach ($rows as $r) {
            if (fnmatch((string)$r['route_pattern'], $route)) {
                $ctx = $this->engine->buildContext($route, $getParams, $languageId, $this->config);

                // Phase 2 — merge extra context from registered SchemaDataProviderInterface impls
                $registry = new SchemaProviderRegistry($this->config);
                $extra    = $registry->collect($route, $getParams, $languageId);
                if ($extra) $ctx = array_replace_recursive($ctx, $extra);

                return $this->engine->render((string)$r['template'], $ctx);
            }
        }
        return null;
    }

    public function inject(string $jsonLd): void
    {
        if (!$jsonLd) return;
        DocumentExtra::addJsonLd($jsonLd);
    }

    public function renderForRouteAndInject(string $route, array $getParams, int $languageId): void
    {
        $jsonLd = $this->renderForRoute($route, $getParams, $languageId);
        if ($jsonLd !== null) $this->inject($jsonLd);
    }
}
