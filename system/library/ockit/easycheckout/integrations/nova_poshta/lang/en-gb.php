<?php
return [
    'name'          => 'Nova Poshta',
    'description'   => 'Autocomplete for Nova Poshta cities and branches/postomats. Requires a sender-cabinet API key.',
    'field.np_city'      => 'NP: City',
    'field.np_warehouse' => 'NP: Branch',
    'block.np_shipping_block' => 'Nova Poshta shipping',
    'settings.api_key'         => 'API key',
    'settings.api_key.help'    => 'Generate in Nova Poshta cabinet (Settings → Security).',
    'settings.warehouse_types' => 'Branch types',
    'settings.warehouse_types.help' => 'Which types to show in autocomplete.',
    'settings.cache_ttl_hours' => 'Cache TTL (hours)',
    'settings.cache_ttl_hours.help' => 'How often to refresh local cache via cron. 24 = once a day.',
    'option.branch'   => 'Standard branch',
    'option.cargo'    => 'Cargo branch',
    'option.postomat' => 'Postomat',
];
