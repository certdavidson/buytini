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

namespace OcKit\SeoCore\Dto;

/**
 * Row from oc_kit_seo_schema_rules — a custom JSON-LD rule bound to a route.
 */
final class SchemaRule
{
    public int    $ruleId;
    public int    $storeId;
    public string $routePattern;
    public string $template;
    public int    $priority;
    public bool   $status;

    public function __construct(array $row)
    {
        $this->ruleId       = (int)($row['rule_id']     ?? 0);
        $this->storeId      = (int)($row['store_id']    ?? 0);
        $this->routePattern = (string)($row['route_pattern'] ?? '');
        $this->template     = (string)($row['template'] ?? '');
        $this->priority     = (int)($row['priority']    ?? 0);
        $this->status       = !empty($row['status']);
    }

    public function toArray(): array
    {
        return [
            'rule_id'       => $this->ruleId,
            'store_id'      => $this->storeId,
            'route_pattern' => $this->routePattern,
            'template'      => $this->template,
            'priority'      => $this->priority,
            'status'        => $this->status ? 1 : 0,
        ];
    }
}
