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

final class RedirectDto
{
    /** @var int */
    public $redirectId;
    /** @var string */
    public $fromUrl;
    /** @var string */
    public $toUrl;
    /** @var int */
    public $code;
    /** @var int */
    public $storeId;
    /** @var int */
    public $hits;
    /** @var string */
    public $createdAt;
    /** @var string|null */
    public $expiresAt;
    /** @var string|null */
    public $lastHitAt;
    public function __construct(
        int $redirectId,
        string $fromUrl,
        string $toUrl,
        int $code,
        int $storeId,
        int $hits,
        string $createdAt,
        ?string $expiresAt = null,
        ?string $lastHitAt = null
    ) {
        $this->redirectId = $redirectId;
        $this->fromUrl = $fromUrl;
        $this->toUrl = $toUrl;
        $this->code = $code;
        $this->storeId = $storeId;
        $this->hits = $hits;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->lastHitAt = $lastHitAt;
    }
}
