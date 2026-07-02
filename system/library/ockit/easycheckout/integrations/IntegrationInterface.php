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

/**
 * Контракт інтеграції — окремий пакет у `integrations/<code>/` з власним
 * manifest.json, Integration.php, Client.php, lang/, assets/.
 *
 * Реєструється автоматично через IntegrationsRegistry::discoverAll().
 * Кожна інтеграція володіє СВОЇМИ таблицями (kit_easycheckout_<code>_*) —
 * їх схему декларує сама через installSchema/uninstallSchema/purgeData.
 */
interface IntegrationInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function getDescription(): string;

    /** Категорія: 'shipping' | 'payment' | 'address' | 'other'. */
    public function getCategory(): string;

    /** Іконка (lucide name або URL). */
    public function getIcon(): string;

    /** Країна-фокус (ISO-2). Порожнє — універсальна. */
    public function getCountryIso2(): string;

    /**
     * Схема налаштувань для settings-modal. Поля {key,type,label,help,default,options?}.
     * Типи: text|password|number|toggle|select|multiselect|textarea
     */
    public function getSettingsSchema(): array;

    public function isEnabled(): bool;
    public function setEnabled(bool $enabled): bool;

    public function getSettings(): array;
    public function saveSettings(array $patch): void;

    /**
     * Виконати дію (refresh_warehouses, test_connection, purge_data).
     * Повертає {success: bool, message: string, data?: array}.
     */
    public function runAction(string $action, array $payload = []): array;

    /**
     * Типи полів, що інтеграція додає до редактора (наприклад 'np_city',
     * 'np_warehouse'). З'являються в дропдауні тільки коли інтеграція enabled.
     */
    public function getProvidedFieldTypes(): array;

    /**
     * Готові preset-блоки, які можна додати в layout одним кліком
     * ("Додати в layout" на картці).
     */
    public function getDefaultBlocks(): array;

    /**
     * Імена власних таблиць (без DB_PREFIX), наприклад
     * ['kit_easycheckout_nova_poshta_warehouses', 'kit_easycheckout_nova_poshta_cities'].
     */
    public function getOwnedTables(): array;

    /** CREATE TABLE для власних таблиць. Викликається при install() / enable. */
    public function installSchema(\DB $db): void;

    /** DROP TABLE для власних таблиць. Викликається при purgeData() / uninstall. */
    public function uninstallSchema(\DB $db): void;

    /** Очистити дані без видалення схеми (TRUNCATE). UI-кнопка "Очистити дані". */
    public function purgeData(\DB $db): void;

    /**
     * Періодичне оновлення локального кешу (cron). Повертає
     * {success: bool, message: string, stats: array}.
     */
    public function refreshCache(\DB $db): array;

    /**
     * Catalog-side autocomplete пошук по локальному кешу.
     * @param string $type     'city'|'warehouse'|'region'|'district'|'postoffice'
     * @param string $query    те що ввів юзер (lower-cased substring)
     * @param array  $context  залежності (наприклад ['city_ref' => '...']).
     * @return array<int,array{id:string,label:string,meta?:array}>
     */
    public function searchLocations(string $type, string $query, array $context, \DB $db): array;
}
