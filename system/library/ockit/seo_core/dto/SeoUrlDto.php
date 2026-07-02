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

final class SeoUrlDto
{
    /** @var string */
    public $keyword;
    /** @var string */
    public $query;
    /** @var int */
    public $storeId;
    /** @var int */
    public $languageId;
    public function __construct(string $keyword, string $query, int $storeId, int $languageId) {
        $this->keyword = $keyword;
        $this->query = $query;
        $this->storeId = $storeId;
        $this->languageId = $languageId;
    }
}
