<?php
return [
    'name'          => 'Новая Почта',
    'description'   => 'Автокомплит городов и отделений/почтоматов НП. Нужен API-ключ из кабинета отправителя.',
    'field.np_city'      => 'НП: Населённый пункт',
    'field.np_warehouse' => 'НП: Отделение',
    'block.np_shipping_block' => 'Доставка Новая Почта',
    'settings.api_key'         => 'API-ключ',
    'settings.api_key.help'    => 'Сгенерируйте в кабинете Новой Почты (Настройки → Безопасность).',
    'settings.warehouse_types' => 'Типы отделений',
    'settings.warehouse_types.help' => 'Какие типы показывать клиенту в автокомплите.',
    'settings.cache_ttl_hours' => 'TTL кэша (час)',
    'settings.cache_ttl_hours.help' => 'Как часто обновлять локальный кэш через cron. 24 = раз в сутки.',
    'option.branch'   => 'Обычное отделение',
    'option.cargo'    => 'Грузовое отделение',
    'option.postomat' => 'Почтомат',
];
