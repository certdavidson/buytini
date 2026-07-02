<?php
/**
 * Content Blocks Pro — Input DTO for saveBlocks().
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\ContentBlocks\Dto;

class BlockSaveRequest
{
    public string $pageRoute;
    public int    $pageId;
    public array  $blocks;

    public function __construct(string $pageRoute, int $pageId, array $blocks)
    {
        $this->pageRoute = $pageRoute;
        $this->pageId    = $pageId;
        $this->blocks    = $blocks;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['page_route'] ?? ''),
            (int)($data['page_id']    ?? 0),
            (array)($data['blocks']   ?? [])
        );
    }
}
