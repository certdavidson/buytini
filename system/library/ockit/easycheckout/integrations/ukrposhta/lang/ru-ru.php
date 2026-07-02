<?php
return [
    'name'        => 'Укрпочта',
    'description' => 'Автокомплит областей/районов/городов/отделений Укрпочты.',
    'field.up_region'     => 'Укрпочта: Область',
    'field.up_district'   => 'Укрпочта: Район',
    'field.up_city'       => 'Укрпочта: Город',
    'field.up_postoffice' => 'Укрпочта: Отделение',
    'settings.bearer_token'    => 'Bearer-токен',
    'settings.bearer_token.help' => 'Выдается после подписания договора с Укрпочтой.',
    'settings.cache_ttl_hours' => 'TTL кэша (час)',
    'settings.cache_ttl_hours.help' => 'Реестр Укрпочты обновляется редко — рекомендуется 168 (раз в неделю).',
];
