<?php
// EasyCheckout — NovaPoshta integration | © 2026 oc-kit.com
return [
    'name'          => 'Нова Пошта',
    'description'   => 'Автокомплейт міст та відділень/поштоматів НП. Потрібен API-ключ з кабінету відправника.',
    'field.np_city'      => 'НП: Населений пункт',
    'field.np_warehouse' => 'НП: Відділення',
    'block.np_shipping_block' => 'Доставка Нова Пошта',
    'settings.api_key'         => 'API-ключ',
    'settings.api_key.help'    => 'Згенеруйте в кабінеті Нової Пошти (Налаштування → Безпека).',
    'settings.warehouse_types' => 'Типи відділень',
    'settings.warehouse_types.help' => 'Які типи показувати клієнту в автокомплейті.',
    'settings.cache_ttl_hours' => 'TTL кешу (год.)',
    'settings.cache_ttl_hours.help' => 'Як часто оновлювати локальний кеш через cron. 24 = раз на добу.',
    'option.branch'   => 'Звичайне відділення',
    'option.cargo'    => 'Вантажне відділення',
    'option.postomat' => 'Поштомат',
];
