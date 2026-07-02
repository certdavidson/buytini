<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Integrations
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Integrations;

use OcKit\EasyCheckout\Libs\ConfigStore;

/**
 * Базовий клас інтеграції. Конкретні наслідники реалізують лише доменну
 * логіку (HTTP-клієнт, схема таблиць, refreshCache).
 */
abstract class AbstractIntegration implements IntegrationInterface
{
    protected ConfigStore $config;
    /** @var array<string,array<string,string>> language code → key/value */
    private array $langCache = [];

    public function __construct(ConfigStore $config)
    {
        $this->config = $config;
    }

    /**
     * Локалізована strings з `integrations/<code>/lang/<lang>.php`.
     * Fallback chain: requested → uk-ua → en-gb → key.
     */
    public function t(string $key, ?string $lang = null): string
    {
        $lang  = $lang ?: $this->detectLang();
        foreach ([$lang, 'uk-ua', 'en-gb'] as $candidate) {
            $strings = $this->loadLang($candidate);
            if (isset($strings[$key])) return (string)$strings[$key];
        }
        return $key;
    }

    private function detectLang(): string
    {
        if (!empty($_SESSION['language'])) return (string)$_SESSION['language'];
        return 'uk-ua';
    }

    private function loadLang(string $lang): array
    {
        if (isset($this->langCache[$lang])) return $this->langCache[$lang];
        $rc   = new \ReflectionClass(static::class);
        $file = dirname((string)$rc->getFileName()) . '/lang/' . $lang . '.php';
        if (is_file($file)) {
            $data = require $file;
            return $this->langCache[$lang] = is_array($data) ? $data : [];
        }
        return $this->langCache[$lang] = [];
    }

    abstract public function getCode(): string;
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function getSettingsSchema(): array;

    public function getCategory(): string    { return 'shipping'; }
    public function getIcon(): string        { return 'plug'; }
    public function getCountryIso2(): string { return ''; }

    public function isEnabled(): bool
    {
        $on = (bool)$this->config->get('integration.' . $this->getCode(), 'enabled', false);
        if (!$on) return false;
        if ($this->isPaid() && !$this->isRegistered()) return false;
        return true;
    }

    /** Free vs paid distribution. Override на true для marketplace-пакетів. */
    public function isPaid(): bool { return false; }

    /** Чи модуль зареєстрований на цьому домені (telemetry session valid). */
    protected function isRegistered(): bool
    {
        $token = (string)$this->config->get('telemetry.' . $this->getCode(), 'session', '');
        if ($token === '') return false;
        $expires = (int)$this->config->get('telemetry.' . $this->getCode(), 'expires_at', 0);
        if ($expires > 0 && $expires < time()) return false;
        return true;
    }

    public function setEnabled(bool $enabled): bool
    {
        $this->config->set('integration.' . $this->getCode(), 'enabled', $enabled ? 1 : 0);
        return $enabled;
    }

    public function getSettings(): array
    {
        $section = 'integration.' . $this->getCode() . '.settings';
        $values  = $this->config->getSection($section);
        // Наповнюємо defaults-ами зі схеми
        foreach ($this->getSettingsSchema() as $field) {
            $key = (string)($field['key'] ?? '');
            if ($key === '') continue;
            if (!array_key_exists($key, $values)) {
                $values[$key] = $field['default'] ?? null;
            }
        }
        return $values;
    }

    public function saveSettings(array $patch): void
    {
        $section = 'integration.' . $this->getCode() . '.settings';
        foreach ($patch as $key => $value) {
            $field = $this->findSchemaField((string)$key);
            if (!$field) continue;
            $this->config->set($section, (string)$key, $this->sanitizeValue($field, $value));
        }
    }

    public function runAction(string $action, array $payload = []): array
    {
        return ['success' => false, 'message' => 'Action not supported: ' . $action];
    }

    public function getProvidedFieldTypes(): array { return []; }
    public function getDefaultBlocks(): array      { return []; }
    public function getOwnedTables(): array        { return []; }

    public function installSchema(\DB $db): void   { /* override */ }
    public function uninstallSchema(\DB $db): void
    {
        foreach ($this->getOwnedTables() as $table) {
            $db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . str_replace('`', '', (string)$table) . "`");
        }
    }
    public function purgeData(\DB $db): void
    {
        foreach ($this->getOwnedTables() as $table) {
            $db->query("TRUNCATE TABLE `" . DB_PREFIX . str_replace('`', '', (string)$table) . "`");
        }
    }
    public function refreshCache(\DB $db): array
    {
        return ['success' => false, 'message' => 'refreshCache not implemented', 'stats' => []];
    }

    public function searchLocations(string $type, string $query, array $context, \DB $db): array
    {
        return [];
    }

    protected function findSchemaField(string $key): ?array
    {
        foreach ($this->getSettingsSchema() as $field) {
            if (($field['key'] ?? '') === $key) return $field;
        }
        return null;
    }

    protected function sanitizeValue(array $field, $value)
    {
        $type = (string)($field['type'] ?? 'text');
        switch ($type) {
            case 'toggle':      return !empty($value) ? 1 : 0;
            case 'number':      return (int)$value;
            case 'multiselect':
                if (is_array($value)) return array_values($value);
                return $value === '' ? [] : explode(',', (string)$value);
            default:            return (string)$value;
        }
    }
}
