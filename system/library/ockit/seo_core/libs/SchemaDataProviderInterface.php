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
 * Pluggable data provider for custom Schema.org JSON-LD templates.
 *
 * Third-party modules implement this interface to expose extra variables
 * to {{var}}/{{#each}} placeholders in `kit_seo_schema_rules.template`.
 *
 * Registration:
 *   Add the FQCN of an implementing class to admin config
 *   `module_oc_kit_seo_core_schema_providers` (JSON array of strings).
 *   The provider class must be autoloadable (typically registered through
 *   a separate OCMOD or in `system/library/ockit/.../`).
 *
 * Each provider's `getData($route, $params, $languageId)` is called for
 * the active route. The returned associative array is merged into the
 * shared template context (later providers override earlier keys, so the
 * order in the config matters).
 *
 * Reference: TZ §15 + §26 "Data Provider for Schema custom".
 */
interface SchemaDataProviderInterface
{
    /**
     * Returns true if the provider has data to contribute for the given route.
     * Used for early-skip optimisation — implementers can match by route prefix
     * or query-key presence to avoid unnecessary DB queries.
     */
    public function supportsRoute(string $route, array $params): bool;

    /**
     * Build the additional context data for the current request.
     *
     * @param string $route       OC route, e.g. 'product/product'
     * @param array  $params      Decoded request->get without _route_/route
     * @param int    $languageId  Active language_id
     * @return array<string,mixed> Variables exposed to template engine
     */
    public function getData(string $route, array $params, int $languageId): array;
}
