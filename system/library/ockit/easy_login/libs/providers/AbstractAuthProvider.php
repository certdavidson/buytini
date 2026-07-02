<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs\Providers;

abstract class AbstractAuthProvider
{
    protected $config;
    private ?bool $enabledCache = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    abstract public function name(): string;

    /**
     * Per-request cache around the concrete isEnabled() check. The buttons()
     * controller hits each provider's isEnabled() multiple times per page
     * render; caching avoids repeated config-table reads without changing
     * subclass implementations.
     */
    public function isEnabled(): bool
    {
        if ($this->enabledCache === null) {
            $this->enabledCache = $this->checkEnabled();
        }
        return $this->enabledCache;
    }

    /**
     * Subclasses implement the actual check here. Kept abstract so each
     * provider declares its own "enabled" criteria (config flags + creds).
     */
    abstract protected function checkEnabled(): bool;
}
