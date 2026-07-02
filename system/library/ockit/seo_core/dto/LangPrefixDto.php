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

final class LangPrefixDto
{
    /** @var int */
    public $languageId;
    /** @var string */
    public $code;
    /** @var string */
    public $prefix;
    /** @var bool */
    public $isDefault;
    public function __construct(int $languageId, string $code, string $prefix, bool $isDefault) {
        $this->languageId = $languageId;
        $this->code = $code;
        $this->prefix = $prefix;
        $this->isDefault = $isDefault;
    }
}
