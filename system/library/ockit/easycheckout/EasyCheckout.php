<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout;

require_once __DIR__ . '/EasyCheckoutGuard.php';
require_once __DIR__ . '/exceptions/EasyCheckoutException.php';
require_once __DIR__ . '/exceptions/ConfigException.php';
require_once __DIR__ . '/exceptions/ValidationException.php';
require_once __DIR__ . '/libs/SchemaInstaller.php';
require_once __DIR__ . '/libs/ConfigStore.php';
require_once __DIR__ . '/libs/FieldsRepository.php';
require_once __DIR__ . '/libs/HeadingsRepository.php';
require_once __DIR__ . '/libs/BlockRegistry.php';
require_once __DIR__ . '/libs/NativeFieldsRegistry.php';
require_once __DIR__ . '/libs/PageLayoutRepository.php';
require_once __DIR__ . '/libs/GroupsRepository.php';
require_once __DIR__ . '/libs/CustomMethodsRepository.php';
require_once __DIR__ . '/libs/LayoutPresets.php';
require_once __DIR__ . '/integrations/IntegrationInterface.php';
require_once __DIR__ . '/integrations/AbstractIntegration.php';
require_once __DIR__ . '/libs/IntegrationsRegistry.php';
require_once __DIR__ . '/libs/Telemetry.php';
require_once __DIR__ . '/libs/MarketplaceClient.php';

use OcKit\EasyCheckout\Libs\ConfigStore;
use OcKit\EasyCheckout\Libs\SchemaInstaller;
use OcKit\EasyCheckout\Libs\FieldsRepository;
use OcKit\EasyCheckout\Libs\HeadingsRepository;
use OcKit\EasyCheckout\Libs\PageLayoutRepository;
use OcKit\EasyCheckout\Libs\GroupsRepository;
use OcKit\EasyCheckout\Libs\CustomMethodsRepository;
use OcKit\EasyCheckout\Libs\IntegrationsRegistry;

/**
 * Головний фасад модуля EasyCheckout.
 *
 * Контролери / моделі OpenCart створюють інстанс через `new EasyCheckout($registry)`
 * або у випадках потреби singleton-у — `EasyCheckout::getInstance($registry)`.
 *
 * Делегує роботу окремим сервісам (SchemaInstaller, ConfigStore, PageBuilder...).
 */
final class EasyCheckout
{
    private static ?self $instance = null;

    /** @var \Registry */
    private $registry;

    private int $storeId = 0;
    private int $groupId = 0;
    private string $page = 'checkout';

    private ?ConfigStore $configStore = null;
    private ?SchemaInstaller $schemaInstaller = null;
    private ?FieldsRepository $fieldsRepository = null;
    private ?HeadingsRepository $headingsRepository = null;
    private ?PageLayoutRepository $pageLayoutRepository = null;
    private ?GroupsRepository $groupsRepository = null;
    private ?CustomMethodsRepository $customMethodsRepository = null;

    public function __construct($registry)
    {
        $this->registry = $registry;

        $config = $registry->get('config');
        if ($config) {
            $this->storeId = (int)$config->get('config_store_id');
        }
    }

    public static function getInstance($registry): self
    {
        if (self::$instance === null) {
            self::$instance = new self($registry);
        }
        return self::$instance;
    }

    public static function guardAdmin($registry): void
    {
        EasyCheckoutGuard::guardAdmin($registry);
    }

    // ─── Context ──────────────────────────────────────────────────────────────

    public function setStore(int $storeId): self
    {
        $this->storeId = $storeId;
        $this->configStore = null;
        return $this;
    }

    public function setGroup(int $groupId): self
    {
        $this->groupId = $groupId;
        $this->configStore = null;
        return $this;
    }

    public function setPage(string $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getStoreId(): int { return $this->storeId; }
    public function getGroupId(): int { return $this->groupId; }
    public function getPage(): string { return $this->page; }

    // ─── Services (lazy) ──────────────────────────────────────────────────────

    public function getConfigStore(): ConfigStore
    {
        if ($this->configStore === null) {
            $this->configStore = new ConfigStore($this->registry->get('db'), $this->storeId, $this->groupId);
        }
        return $this->configStore;
    }

    public function getSchemaInstaller(): SchemaInstaller
    {
        if ($this->schemaInstaller === null) {
            $this->schemaInstaller = new SchemaInstaller($this->registry->get('db'));
        }
        return $this->schemaInstaller;
    }

    public function getFieldsRepository(): FieldsRepository
    {
        if ($this->fieldsRepository === null) {
            $this->fieldsRepository = new FieldsRepository($this->registry->get('db'));
        }
        return $this->fieldsRepository;
    }

    public function getHeadingsRepository(): HeadingsRepository
    {
        if ($this->headingsRepository === null) {
            $this->headingsRepository = new HeadingsRepository($this->registry->get('db'));
        }
        return $this->headingsRepository;
    }

    public function getPageLayoutRepository(): PageLayoutRepository
    {
        if ($this->pageLayoutRepository === null) {
            $this->pageLayoutRepository = new PageLayoutRepository($this->getConfigStore());
        }
        return $this->pageLayoutRepository;
    }

    public function getGroupsRepository(): GroupsRepository
    {
        if ($this->groupsRepository === null) {
            $this->groupsRepository = new GroupsRepository($this->registry->get('db'));
        }
        return $this->groupsRepository;
    }

    public function getCustomMethodsRepository(): CustomMethodsRepository
    {
        if ($this->customMethodsRepository === null) {
            $this->customMethodsRepository = new CustomMethodsRepository($this->registry->get('db'));
        }
        return $this->customMethodsRepository;
    }

    // ─── Admin lifecycle ──────────────────────────────────────────────────────

    /**
     * Викликається з адмін-моделі install().
     */
    public function install(): void
    {
        $schema = $this->getSchemaInstaller();
        $schema->createAll();
        $defaultGroupId = $schema->ensureDefaultGroup();
        $this->installIntegrationSchemas();
        // Початкові дефолти запишемо у наступних етапах (presets loader).
    }

    /**
     * Створити всі власні таблиці інтеграцій. Викликається з install().
     * Кожна інтеграція сама декларує свою схему — реєстр лиш проходить по них.
     */
    private function installIntegrationSchemas(): void
    {
        $reg = new IntegrationsRegistry($this->getConfigStore());
        $db  = $this->registry->get('db');
        foreach ($reg->all() as $integration) {
            try { $integration->installSchema($db); } catch (\Throwable $e) { /* skip */ }
        }
    }

    /**
     * Видалити налаштування модуля з `oc_setting` без дропу таблиць.
     * Дроп таблиць — окрема дія (deep uninstall).
     */
    public function uninstall(): void
    {
        // Залишаємо таблиці на випадок повторного встановлення.
        // Користувач може викликати dropAll() через кнопку "Видалити дані модуля".
    }

    public function uninstallDeep(): void
    {
        $this->getSchemaInstaller()->dropAll();
    }
}
