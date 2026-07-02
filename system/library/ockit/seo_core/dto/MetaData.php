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

final class MetaData
{
    /** @var string */
    public $title;
    /** @var string */
    public $description;
    /** @var string */
    public $h1;
    /** @var string */
    public $robots;
    /** @var string */
    public $canonical;
    /** @var string */
    public $ogTitle;
    /** @var string */
    public $ogDescription;
    /** @var string */
    public $ogImage;
    public function __construct(
        string $title,
        string $description,
        string $h1,
        string $robots,
        string $canonical,
        string $ogTitle,
        string $ogDescription,
        string $ogImage
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->h1 = $h1;
        $this->robots = $robots;
        $this->canonical = $canonical;
        $this->ogTitle = $ogTitle;
        $this->ogDescription = $ogDescription;
        $this->ogImage = $ogImage;
    }

    public function withTitle(string $title): self
    {
        return new self($title, $this->description, $this->h1, $this->robots, $this->canonical, $this->ogTitle, $this->ogDescription, $this->ogImage);
    }

    public function withDescription(string $description): self
    {
        return new self($this->title, $description, $this->h1, $this->robots, $this->canonical, $this->ogTitle, $this->ogDescription, $this->ogImage);
    }

    public function withH1(string $h1): self
    {
        return new self($this->title, $this->description, $h1, $this->robots, $this->canonical, $this->ogTitle, $this->ogDescription, $this->ogImage);
    }
}
