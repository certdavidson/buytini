<?php
/**
 * Translater Pro — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\TranslaterPro\Dto;

/**
 * Immutable value object representing one record awaiting translation.
 */
class TranslationItem
{
    /** @var int */
    public $itemId;
    /** @var string */
    public $type;
    /** @var string shown in the table (source name or manufacturer name) */
    public $displayName;
    /** @var array field_name => source_value (non-empty only) */
    public $fields;

    public function __construct($itemId, $type, $displayName, array $fields)
    {
        $this->itemId      = (int)$itemId;
        $this->type        = (string)$type;
        $this->displayName = (string)$displayName;
        $this->fields      = $fields;
    }
}
