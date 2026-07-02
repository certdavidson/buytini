<?php
/**
 * Advanced Search Pro — Full-text search module for OpenCart
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 * @license   Commercial licence — all rights reserved. Redistribution prohibited.
 * @link      https://oc-kit.com
 */

/**
 * Catalog binding helper — verifies that the runtime context matches the
 * stored binding hash and signature. Lives in libs/ next to the other module
 * services on purpose: there is no separate "License" folder for attackers to
 * target. Replacing or stubbing this file is detected by the facade and
 * silently degrades the catalog to native MySQL search instead of crashing.
 *
 * KEY FORMAT (pipe-separated, then base64-encoded):
 *   <version>|<domain_hash>|<expires>|<product>|<features>|<sig>
 *
 * IONCUBE: this file MUST be encoded before release — its SALT is what
 *          protects the licensing scheme. Without encoding, anyone with the
 *          source can read SALT and forge keys.
 */

namespace OcKit\AdvancedSearchPro\Libs;

class SearchData
{
    // -----------------------------------------------------------------------
    // PRIVATE SALT — replace before IonCube encoding. Keep this secret.
    // -----------------------------------------------------------------------
    const SALT = 'aec1407123be60aebc5818603893effe64833057452e7ddc3875295269f98223';

    const PRODUCT = 'asp';
    const KEY_VERSION = '1';

    /** @var string */
    private $keyString = '';

    /** @var array|null */
    private $parsed = null;

    /** @var string */
    private $currentDomain = '';

    public function __construct($keyString = '') {
        $this->keyString     = (string)$keyString;
        $this->currentDomain = $this->normalizeDomain($this->detectDomain());
    }

    public function isValid() {
        $p = $this->parse();
        return $p !== null;
    }

    public function getStatus() {
        if ($this->keyString === '') {
            return 'no_key';
        }
        $p = $this->parse();
        if ($p === null) {
            return 'invalid';
        }
        if ($p['expires'] !== 0 && $p['expires'] < time()) {
            return 'expired';
        }
        return 'active';
    }

    public function getExpiry() {
        $p = $this->parse();
        if ($p === null) {
            return '—';
        }
        return $p['expires'] === 0 ? 'Never' : date('Y-m-d', $p['expires']);
    }

    public function hasFeature($feature) {
        $p = $this->parse();
        if ($p === null) {
            return false;
        }
        if ($p['features'] === ['*']) {
            return true;
        }
        return in_array($feature, $p['features'], true);
    }

    public function getBoundDomain() {
        $p = $this->parse();
        if ($p === null) {
            return '—';
        }
        return $this->currentDomain;
    }

    // -----------------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------------

    private function parse() {
        if ($this->parsed !== null) {
            return $this->parsed;
        }
        if ($this->keyString === '') {
            return null;
        }

        $raw = base64_decode(strtr($this->keyString, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        $parts = explode('|', $raw);
        if (count($parts) !== 6) {
            return null;
        }

        list($version, $domainHash, $expires, $product, $features, $sig) = $parts;

        if ($version !== self::KEY_VERSION) {
            return null;
        }
        if ($product !== self::PRODUCT) {
            return null;
        }

        $payload = implode('|', [$version, $domainHash, $expires, $product, $features]);
        $expected = hash_hmac('sha256', $payload, self::SALT);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $currentHash = md5($this->currentDomain);
        if ($currentHash !== $domainHash) {
            return null;
        }

        $expiresInt = (int)$expires;
        if ($expiresInt !== 0 && $expiresInt < time()) {
            return null;
        }

        $this->parsed = [
            'version'     => $version,
            'domain_hash' => $domainHash,
            'expires'     => $expiresInt,
            'product'     => $product,
            'features'    => array_map('trim', explode(',', $features)),
        ];

        return $this->parsed;
    }

    private function normalizeDomain($domain) {
        $domain = strtolower(trim((string)$domain));
        $domain = preg_replace('#^www\.#', '', $domain);
        return $domain;
    }

    private function detectDomain() {
        if (PHP_SAPI === 'cli') {
            return (string)(getenv('HTTP_HOST') ?: ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        }
        return (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    }
}
