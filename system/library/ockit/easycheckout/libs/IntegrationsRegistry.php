<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

use OcKit\EasyCheckout\Integrations\IntegrationInterface;

/**
 * Реєстр інтеграцій. Сканує `integrations/<code>/manifest.json`, для кожного
 * пакета викликає main file Integration.php і інстансіює клас
 * `\OcKit\EasyCheckout\Integrations\<StudlyCode>\Integration`.
 */
final class IntegrationsRegistry
{
    private const BASE_NS = '\\OcKit\\EasyCheckout\\Integrations\\';

    private ConfigStore $config;
    /** @var array<string,IntegrationInterface> */
    private array $instances = [];
    /** @var array<string,array> */
    private array $manifests = [];
    private ?array $listCache = null;

    public function __construct(ConfigStore $config)
    {
        $this->config = $config;
        $this->discoverAll();
    }

    private function discoverAll(): void
    {
        $dir = dirname(__DIR__) . '/integrations';
        if (!is_dir($dir)) return;

        // Завантажуємо базові класи
        require_once $dir . '/IntegrationInterface.php';
        require_once $dir . '/AbstractIntegration.php';

        foreach (glob($dir . '/*/manifest.json') ?: [] as $manifestFile) {
            $code   = basename(dirname($manifestFile));
            $folder = dirname($manifestFile);
            $main   = $folder . '/Integration.php';
            if (!is_file($main)) continue;

            $manifest = json_decode((string)file_get_contents($manifestFile), true) ?: [];

            require_once $main;
            $studly = $this->studly($code);
            $fqn    = self::BASE_NS . $studly . '\\Integration';
            if (!class_exists($fqn)) continue;
            try {
                $instance = new $fqn($this->config);
                if ($instance instanceof IntegrationInterface) {
                    $this->instances[$instance->getCode()] = $instance;
                    $this->manifests[$instance->getCode()] = $manifest;
                }
            } catch (\Throwable $e) { /* skip broken package */ }
        }
    }

    /** @return IntegrationInterface[] keyed by code */
    public function all(): array { return $this->instances; }

    public function get(string $code): ?IntegrationInterface
    {
        return $this->instances[$code] ?? null;
    }

    public function manifest(string $code): array
    {
        return $this->manifests[$code] ?? [];
    }

    /** Абсолютний шлях до icon.svg пакета, або null. */
    public function iconPath(string $code): ?string
    {
        if (!preg_match('/^[a-z0-9_]+$/', $code)) return null;
        $file = dirname(__DIR__) . '/integrations/' . $code . '/icon.svg';
        return is_file($file) ? $file : null;
    }

    /** Версія встановленого пакета з manifest.json. */
    public function installedVersion(string $code): ?string
    {
        return isset($this->manifests[$code]['version']) ? (string)$this->manifests[$code]['version'] : null;
    }

    /** Compact list for UI rendering (cards). */
    public function listForUi(): array
    {
        if ($this->listCache !== null) return $this->listCache;
        $out = [];
        foreach ($this->instances as $i) {
            $code = $i->getCode();
            $out[] = [
                'code'        => $code,
                'name'        => $i->getName(),
                'description' => $i->getDescription(),
                'category'    => $i->getCategory(),
                'icon'        => $i->getIcon(),
                'country'     => $i->getCountryIso2(),
                'enabled'     => $i->isEnabled(),
                'version'     => (string)($this->manifests[$code]['version'] ?? '1.0.0'),
                'has_icon'    => $this->iconPath($code) !== null,
            ];
        }
        usort($out, static function ($a, $b) {
            if ($a['enabled'] !== $b['enabled']) return $a['enabled'] ? -1 : 1;
            return strcmp($a['name'], $b['name']);
        });
        return $this->listCache = $out;
    }

    private function studly(string $snake): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $snake)));
    }
}
