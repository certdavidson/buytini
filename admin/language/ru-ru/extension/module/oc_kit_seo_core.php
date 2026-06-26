<?php
/**
 * SEO Core — OpenCart Module
 *
 * @package   OcKit\SeoCore
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title'] = 'oc-kit.com — SEO Core';

// Text
$_['text_extension']             = 'Расширения';
$_['text_home']                  = 'Главная';
$_['text_success']               = 'Настройки сохранены!';
$_['text_license_activated']     = 'Лицензия активирована!';
$_['text_enabled']               = 'Включено';
$_['text_disabled']              = 'Выключено';

// Tab labels
$_['tab_settings']               = 'Настройки';
$_['tab_meta']                   = 'Мета-теги';
$_['tab_redirects']              = 'Редиректы';
$_['tab_urls']                   = 'SEO URL';
$_['tab_headers']                = 'Заголовки';
$_['tab_audit']                  = 'Аудит';
$_['tab_robots']                 = 'robots.txt';
$_['tab_sitemap']                = 'Sitemap';
$_['tab_absurl']                 = 'Замена домена';
$_['tab_dashboard']              = 'Дашборд';
$_['tab_faq']                    = 'FAQ';
$_['tab_masks']                  = 'Маски URL';
$_['tab_canonical']              = 'Каноникалы';
$_['tab_hreflang']                = 'Hreflang';
$_['tab_opengraph']               = 'Open Graph';
$_['tab_schema']                  = 'Schema.org';

$_['text_canonical_auto']              = 'Автоматические правила';
$_['text_canonical_overrides']         = 'Ручные override';
$_['text_canonical_test']              = 'Тест canonical';
$_['label_canonical_pagination']       = 'Canonical для пагинации';
$_['text_canonical_pagination_first']  = 'Первая страница';
$_['text_canonical_pagination_self']   = 'Текущая страница';
$_['label_canonical_filters']          = 'Canonical для фильтров';
$_['text_canonical_filters_base']      = 'Базовая категория';
$_['text_canonical_filters_self']      = 'Текущий URL';
$_['text_canonical_filters_noindex']   = 'noindex robots';
$_['label_canonical_cross_domain']     = 'Cross-domain canonical';
$_['text_canonical_cross_domain_hint'] = 'Для зеркальных сайтов: все canonical будут указывать на этот домен. Оставьте пустым — используется текущий домен.';
$_['button_test']                      = 'Проверить';

$_['text_hreflang_coming']  = 'Настройки hreflang будут добавлены в следующей фазе. Сейчас HreflangBuilder уже интегрирован и рендерит теги автоматически для всех активных языков.';
$_['text_hreflang_about']   = '<strong>Hreflang</strong> сообщает поисковикам об альтернативных языковых версиях страницы: <code>&lt;link rel="alternate" hreflang="uk" href="..."&gt;</code>. Это устраняет путаницу между похожими URL и улучшает выдачу региональных версий. Теги генерируются автоматически для всех языков, где у страницы есть SEO URL.';
$_['text_hreflang_preview'] = 'Превью тегов';
$_['label_hreflang_enabled']= 'Включить hreflang';
$_['label_hreflang_format'] = 'Формат кода';
$_['text_opengraph_coming'] = 'Панель Open Graph с настройками тегов og:* и Twitter Card будет добавлена в следующей фазе. Сейчас стандартные og-теги рендерятся автоматически через OpenGraphRenderer.';
$_['text_og_about']            = '<strong>Open Graph</strong> (Facebook, LinkedIn, Telegram и т.д.) и <strong>Twitter Card</strong> задают как страница выглядит при шаринге в соцсетях: заголовок, описание, изображение. Если шаблоны пустые — используются значения из Meta-тегов.';
$_['text_og_templates']        = 'Шаблоны тегов';
$_['text_og_templates_hint']   = 'Переменные: <code>{name}</code>, <code>{description}</code>, <code>{price}</code>, <code>{manufacturer}</code>, <code>{store_name}</code>, <code>{year}</code> и т.д.';
$_['label_og_enabled']         = 'Включить Open Graph';
$_['label_og_twitter_card']    = 'Twitter Card';
$_['label_og_twitter_handle']  = 'Twitter handle';
$_['label_og_image_fallback']  = 'URL изображения';
$_['text_og_twitter_handle_hint']= 'Username аккаунта в Twitter/X с символом @, например @yoursite. Используется в <code>twitter:site</code>.';
$_['text_og_image_fallback_hint']= 'URL изображения по умолчанию — используется когда у сущности нет собственного изображения. Рекомендуется 1200×630px.';
$_['text_schema_coming']    = 'Schema.org панель с toggles стандартных типов и custom JSON-LD редактором будет добавлена в следующей фазе.';
$_['text_schema_about']         = '<strong>Schema.org</strong> — структурированная JSON-LD разметка для поисковиков. Включая тип, вы добавляете соответствующий блок в <code>&lt;head&gt;</code> страниц. Нужно для Rich Snippets в Google (рейтинг, цена, наличие, хлебные крошки), Organization-card и т.д.';
$_['text_schema_standard']      = 'Стандартные типы';
$_['text_schema_organization']  = 'Organization';
$_['text_schema_custom']        = 'Custom правила (JSON-LD редактор)';
$_['text_schema_custom_hint']   = 'Создавайте свои JSON-LD блоки для кастомных страниц. В шаблоне поддерживаются <code>{{var}}</code> переменные контекста, циклы <code>{{#each}}</code>, условия <code>{{#if}}</code>.';
$_['label_schema_min_reviews']  = 'Мин. отзывов для aggregateRating';
$_['text_schema_min_reviews_hint']= 'Ниже этого количества aggregateRating не добавляется в Product JSON-LD (чтобы избежать недостоверных данных).';
$_['label_schema_org_name']     = 'Название организации';
$_['label_schema_org_logo']     = 'URL логотипа';
$_['label_schema_org_phone']    = 'Телефон (E.164)';
$_['label_schema_org_email']    = 'Email';
$_['text_schema_modal_title']   = 'Custom Schema.org правило';
$_['label_schema_route']        = 'Route pattern';
$_['label_schema_preview']      = 'Превью';
$_['label_schema_template']     = 'JSON-LD шаблон';
$_['label_schema_priority']     = 'Приоритет';
$_['label_schema_status']       = 'Статус';
$_['button_validate']           = 'Проверить';

$_['text_section_cache']  = 'Кэш SEO URL';
$_['text_cache_hint']     = 'Сгенерированный кэш содержит все <code>keyword → query</code> мапы в памяти, что устраняет DB-запрос при каждом URL. Рекомендуется перегенерировать после массовой генерации URL или после смены глубины URL.';
$_['button_warm_cache']   = 'Сгенерировать кэш';
$_['button_clear_cache']  = 'Очистить кэш';
$_['button_export_csv']   = 'Экспорт CSV';
$_['button_delete_stale'] = 'Старые (0 hits)';
$_['text_redirects_stale_hint'] = 'Удалить редиректы без использования (0 hits) старше N дней';
$_['redirects_stale_days_prompt']= 'Удалить редиректы с 0 hits старше N дней:';
$_['confirm_delete_stale']= 'Удалить все редиректы с 0 hits старше';
$_['days']                = 'дней';
$_['deleted']             = 'Удалено';

$_['text_faq_security']     = 'Security Headers и HSTS';
$_['text_faq_audit']        = 'Аудит SEO';
$_['text_faq_schema']       = 'Schema.org (JSON-LD)';
$_['text_faq_sitemap']      = 'Sitemap';
$_['text_faq_hreflang']     = 'Hreflang';
$_['text_faq_troubleshoot'] = 'Troubleshooting';

// Settings
$_['label_status']               = 'Статус';
$_['label_url_depth']            = 'Глубина URL категорий';
$_['label_trailing_slash']       = 'Завершающий слеш';
$_['label_lang_prefixes']        = 'Языковые префиксы';
$_['label_custom_routes']        = 'Кастомные роуты';
$_['label_pagination_mode']      = 'Пагинация';
$_['label_noindex_all_pagination'] = 'Noindex для всех страниц пагинации';
$_['label_mask_product']         = 'Маска URL — товар';
$_['label_mask_category']        = 'Маска URL — категория';
$_['label_mask_manufacturer']    = 'Маска URL — производитель';
$_['label_mask_information']     = 'Маска URL — информация';
$_['label_auto_generate_url']    = 'Авто-генерация URL при посещении';
$_['help_auto_generate_url']     = 'Если включено, отсутствующий SEO URL для товара/категории/производителя/статьи создаётся автоматически при первом посещении страницы — по заданной маске. Запускать регенерацию вручную не нужно.';

// URL depth options
$_['text_depth_flat']            = 'Плоский (без вложенности)';
$_['text_depth_1']               = '1 уровень';
$_['text_depth_2']               = '2 уровня';
$_['text_depth_full']            = 'Полная иерархия';

// Pagination mode options
$_['text_pagination_off']        = 'Выключено';
$_['text_pagination_404']        = '404 для лишних страниц';
$_['text_pagination_redirect_last'] = '301 редирект на последнюю валидную';
$_['text_pagination_robots']     = 'Noindex через X-Robots-Tag';

// Redirects
$_['label_from_url']             = 'Откуда';
$_['label_to_url']               = 'Куда';
$_['label_redirect_code']        = 'Код';
$_['label_hits']                 = 'Переходов';
$_['button_redirect_add']        = 'Добавить редирект';
$_['button_import_csv']          = 'Массовая вставка';
$_['text_bulk_paste_title']      = 'Массовая вставка редиректов';
$_['text_bulk_paste_hint']       = 'По одному редиректу на строку. Формат: <code>/откуда, /куда, код</code>. Код необязателен (по умолчанию 301). Поддерживаются коды: 301, 302, 303, 307, 308, 410.';
$_['text_code_301']              = '<span class="ok-badge ok-badge-success">301</span> Постоянный';
$_['text_code_302']              = '<span class="ok-badge ok-badge-warning">302</span> Временный';
$_['text_code_303']              = '<span class="ok-badge ok-badge-warning">303</span> See Other (POST → GET)';
$_['text_code_307']              = '<span class="ok-badge ok-badge-warning">307</span> Временный (сохраняет метод)';
$_['text_code_308']              = '<span class="ok-badge ok-badge-success">308</span> Постоянный (сохраняет метод)';
$_['text_code_410']              = '<span class="ok-badge ok-badge-error">410</span> Gone (страница удалена)';

$_['text_code_guide_title']      = 'Какой код выбрать?';
$_['text_code_301_use']          = 'Стандартный выбор для SEO. Страница переехала навсегда (переименование URL, смена структуры). Поисковики передают вес ссылок на новый URL.';
$_['text_code_302_use']          = 'Временное перенаправление (акция, A/B-тест, техобслуживание). Поисковики НЕ передают вес — старый URL остаётся в индексе.';
$_['text_code_303_use']          = 'После POST-запроса — чтобы браузер перешёл GET-ом на результат и не повторил форму при F5. Редко используется для SEO.';
$_['text_code_307_use']          = 'Как 302, но гарантирует сохранение HTTP-метода (POST → POST). Используется когда важно не прерывать метод.';
$_['text_code_308_use']          = 'Как 301, но сохраняет HTTP-метод. Редкий случай: постоянный редирект для API-эндпоинтов, принимающих POST/PUT.';
$_['text_code_410_use']          = 'Страница удалена навсегда, возвращаться не будет. Поисковики быстро убирают её из индекса (быстрее чем 404). Поле «Куда» не нужно.';
$_['placeholder_search_redirects'] = 'Поиск редиректов...';

// License
$_['tab_license']                = 'Лицензия';
$_['label_license_key']          = 'Лицензионный ключ';
$_['entry_license_key']          = 'Лицензионный ключ';
$_['button_activate']            = 'Активировать';
$_['text_license_version']       = 'Версия';
$_['text_license_activating']    = 'Активация...';
$_['text_license_buy']           = 'Купить лицензию';
$_['text_license_trial']         = 'Пробный период: %d дн. осталось';
$_['text_license_expired']       = 'Лицензия истекла';
$_['text_license_invalid']       = 'Недействительная лицензия';
$_['text_license_api_error']     = 'Сервер лицензий недоступен';
$_['text_license_not_validated'] = 'Лицензия не проверена';
$_['text_license_status_active'] = 'Активна';
$_['text_license_status_trial']  = 'Пробный период';
$_['text_license_status_expired'] = 'Истекла';
$_['text_license_status_grace']  = 'Льготный период';
$_['text_license_status_invalid'] = 'Недействительна';
$_['text_license_status_not_validated'] = 'Не проверена';

// Errors
$_['error_license_invalid_key']  = 'Неверный лицензионный ключ.';
$_['error_license_api_unreachable'] = 'Сервер лицензий недоступен. Попробуйте позже.';
$_['error_redirect_fields']      = 'Укажите URL "Откуда" и "Куда".';
$_['error_redirect_loop']        = 'Редирект создаёт цепочку или петлю.';
$_['error_import_empty']         = 'CSV-данные пусты.';

$_['label_route_pattern']        = 'Шаблон роута';
$_['label_entity_id']            = 'Query-параметр';
$_['label_route']                = 'Роут OpenCart';
$_['text_skip_routes_hint']      = '<strong>Что это такое?</strong> OpenCart внутри идентифицирует каждую страницу по "роуту" — например <code>product/search</code>, <code>account/login</code>, <code>information/information</code>. Здесь вы указываете, для каких роутов <em>не нужно</em> генерировать SEO URL. Полезно для страниц поиска, личного кабинета и других системных страниц, которым не нужен красивый ЧПУ. Поддерживаются маски: <code>account/*</code>.';
$_['text_entity_routes_hint']    = '<strong>Что это такое?</strong> По умолчанию SEO Core знает только стандартные роуты OpenCart (товар, категория, производитель, информационная страница). Если у вас есть нестандартная страница от стороннего модуля — например вендор <code>index.php?route=vendor/vendor/view&amp;vendor_id=4</code> — модуль не догадается, что параметр <code>vendor_id</code> принадлежит роуту <code>vendor/vendor/view</code>, поэтому SEO URL для неё не работает.<br><br><strong>Решение:</strong> добавьте эту связку сюда <strong>один раз</strong> — в поле «Query-параметр» впишите <code>vendor_id</code>, в поле «Роут OpenCart» — <code>vendor/vendor/view</code>. После сохранения все записи <code>oc_seo_url</code> с <code>query=vendor_id=N</code> начнут работать автоматически: и открываться по красивому URL, и подменяться в ссылках по сайту. Лезть в код не нужно.';

// Meta templates
$_['text_section_meta_templates']  = 'Шаблоны мета-тегов';
$_['label_meta_title_tpl']         = 'Шаблон Title';
$_['label_meta_desc_tpl']          = 'Шаблон Description';
$_['label_meta_h1_tpl']            = 'Шаблон H1';
$_['text_meta_vars_product']       = 'Доступные переменные: <code>{name}</code> — название, <code>{sku}</code> — артикул, <code>{manufacturer}</code> — производитель, <code>{category}</code> — категория, <code>{price}</code> — цена, <code>{description}</code> — 160 символов описания, <code>{store_name}</code> — название магазина, <code>{year}</code> — текущий год, <code>{page}</code> — № страницы (пусто на page 1).<br>Условные блоки: <code>{{#if page}} — страница {page}{{/if}}</code> — рендерится только если переменная непустая.';
$_['text_meta_vars_category']      = 'Доступные переменные: <code>{name}</code> — название, <code>{count}</code> — количество товаров, <code>{store_name}</code> — название магазина, <code>{year}</code> — текущий год, <code>{page}</code> — № страницы (пусто на page 1).<br>Условные блоки: <code>{{#if page}} — страница {page}{{/if}}</code> — рендерится только если переменная непустая.';
$_['text_meta_vars_manufacturer']  = 'Доступные переменные: <code>{name}</code> — название бренда, <code>{store_name}</code> — название магазина, <code>{year}</code> — текущий год, <code>{page}</code> — № страницы (пусто на page 1).<br>Условные блоки: <code>{{#if page}} — страница {page}{{/if}}</code> — рендерится только если переменная непустая.';
$_['text_meta_vars_information']   = 'Доступные переменные: <code>{name}</code> — название страницы, <code>{description}</code> — 160 символов содержимого, <code>{store_name}</code> — название магазина, <code>{year}</code> — текущий год, <code>{page}</code> — № страницы (пусто на page 1).<br>Условные блоки: <code>{{#if page}} — страница {page}{{/if}}</code> — рендерится только если переменная непустая.';
$_['text_meta_tpl_hint']           = '<strong>Как работает шаблон:</strong> рендерится в реальном времени на публичной части — поведение управляется настройкой <em>«Приоритет шаблонов»</em> выше.<br><strong>Порядок приоритетов:</strong> Ручной override (таблица ниже) → Шаблон/OC-поле (в зависимости от режима) → пустое значение.<br><strong>«Массовое заполнение»</strong> ниже — это разовая операция: отрендерит шаблон для выбранных сущностей и <em>запишет результат в таблицу override</em> (не в родные OC-поля товара!). После этого такие значения жёстко прибиты к сущности, их можно редактировать через «Ручные override» ниже, а изменения шаблона на них уже не повлияют.';

// Settings sections
$_['text_section_general']       = 'Общие';
$_['text_section_url']           = 'URL';
$_['text_section_url_masks']     = 'Маски URL';
$_['text_section_pagination']    = 'Пагинация';
$_['text_section_lang_prefixes'] = 'Языковые префиксы';
$_['label_lang_default']         = 'По умолч.';
$_['text_section_custom_routes'] = 'Кастомные роуты';
$_['text_lang_prefix_hint']      = 'Оставьте префикс пустым для основного языка (без префикса в URL). Остальные языки используют свой префикс, например /ru/slug.';
$_['text_skip_routes']           = 'Пропускаемые роуты (без генерации SEO URL)';
$_['text_entity_routes']         = 'Кастомные роуты';
$_['text_depth_hint']            = 'Без вложений: /slug · 1 уровень: /category/slug · Полная: /cat/sub/slug';
$_['text_depth_flat_ex']         = 'site.com/slug';
$_['text_depth_1_ex']            = 'site.com/category/slug';
$_['text_depth_2_ex']            = 'site.com/cat/sub/slug';
$_['text_depth_full_ex']         = 'site.com/cat/sub/sub/slug';

$_['label_product_include_category'] = 'Префикс категории в URL товара';
$_['text_product_category_off']  = 'Нет — site.com/product-slug';
$_['text_product_category_on']   = 'Да — site.com/category/product-slug';
$_['text_product_category_hint'] = 'Подставлять ли путь категории перед slug-ом товара. Выключено — плоские URL товаров (рекомендуется для большинства магазинов).';

$_['text_skip_routes_why']       = 'Для служебных страниц (поиск, личный кабинет, корзина) красивый URL не нужен и может ломать функционал. Такие роуты нужно исключить из генерации SEO URL.';
$_['text_entity_routes_why']     = 'Страницы сторонних модулей (вендоры, блог и т.п.) имеют нестандартный роут. Привяжите query-параметр к роуту — и SEO URL для них заработают без правок кода.';

$_['text_section_skip_routes']   = 'Пропускаемые роуты';
$_['text_section_entity_routes'] = 'Кастомные роуты';

$_['label_noindex_from_page']    = 'Начинать noindex со страницы №';
$_['text_noindex_from_page_hint']= '1 — все страницы пагинации. 2 — первая остаётся индексируемой, а ?page=2, 3… получают noindex. Работает вместе с настройкой выше.';
$_['text_pagination_mode_hint']  = 'Как обрабатывать лишние / невалидные страницы пагинации. Может сочетаться с noindex ниже.';
$_['text_pagination_intro']         = 'Эти две настройки независимы и работают одновременно: одна управляет судьбой невалидных страниц, другая — индексацией валидных.';
$_['text_pagination_invalid_title'] = 'Невалидные страницы (вне диапазона)';
$_['text_pagination_noindex_title'] = 'Noindex для валидных страниц пагинации';
$_['label_noindex_delivery']        = 'Способ блокировки';
$_['text_noindex_delivery_meta']    = '<meta name="robots" content="noindex">';
$_['text_noindex_delivery_header']  = 'HTTP-заголовок X-Robots-Tag';
$_['text_noindex_delivery_both']    = 'Оба (мета-тег + HTTP-заголовок)';
$_['text_noindex_delivery_hint']    = 'Мета-тег читается ботами при парсинге HTML. X-Robots-Tag — серверный заголовок, эффективен для любых ресурсов (включая нон-HTML) и работает раньше — до загрузки тела.';

$_['label_mask_product_ex']      = '{name}-{product_id}';
$_['label_mask_category_ex']     = '{name}';
$_['text_mask_section_hint']     = 'Маски задают формат SEO URL при автоматической генерации. Работают для новых записей и при регенерации ниже.';

$_['text_var_name']              = 'название';
$_['text_var_model']             = 'артикул';
$_['text_var_sku']               = 'SKU';
$_['text_var_product_id']        = 'ID товара';
$_['text_var_category_id']       = 'ID категории';
$_['text_var_manufacturer_id']   = 'ID производителя';
$_['text_var_information_id']    = 'ID страницы';
$_['text_trailing_slash_hint']   = 'Вкл.: /url/ · Выкл.: /url';
$_['text_mask_hint']             = 'Пример: {name} → nike-air-max';

// Regen
$_['label_regen_type']           = 'Тип';
$_['label_regen_lang']           = 'Язык';
$_['label_regen_mode']           = 'Режим';
$_['text_regen_empty']           = 'Только пустые';
$_['text_regen_all']             = 'Все (перезаписать)';
$_['button_regen']               = 'Регенерировать SEO URL';
$_['text_regen_note']            = 'Сохраните настройки перед регенерацией';

// Shared type/mode options
$_['text_type_product']          = 'Товар';
$_['text_type_category']         = 'Категория';
$_['text_type_manufacturer']     = 'Производитель';
$_['text_type_information']      = 'Страница';
$_['text_mode_empty']            = 'Только пустые';
$_['text_mode_all']              = 'Все (перезаписать)';
$_['text_all_types']             = 'Все типы';
$_['text_all_langs']             = 'Все языки';
$_['text_all_levels']            = 'Все уровни';
$_['text_loading']               = 'Загрузка...';

// Column labels
$_['column_from']                = 'Откуда';
$_['column_to']                  = 'Куда';
$_['column_code']                = 'Код';
$_['column_hits']                = 'Переходов';
$_['column_date']                = 'Дата';
$_['column_type']                = 'Тип';
$_['column_severity']            = 'Уровень';
$_['column_entity']              = 'Сущность';
$_['column_issue']               = 'Проблема';
$_['column_detail']              = 'Деталь';
$_['column_file']                = 'Файл';
$_['column_size']                = 'Размер';
$_['column_field']               = 'Поле';
$_['column_count']               = 'Найдено';
$_['column_status']              = 'Статус';

$_['status_new']                 = 'Новое';
$_['status_in_progress']         = 'В работе';
$_['status_fixed']               = 'Исправлено';
$_['status_ignored']             = 'Игнор';

$_['button_diff']                = 'Отличия';
$_['button_close']               = 'Закрыть';
$_['button_edit']                = 'Редактировать';
$_['text_diff_backup']           = 'из резервной копии';
$_['text_diff_current']          = 'в текущем файле';
$_['text_no_diff']               = 'Резервная копия идентична текущему файлу — отличий нет.';

// Audit issue short labels
$_['issue_missing_title']          = 'meta_title';
$_['issue_missing_description']    = 'meta_description';
$_['issue_missing_seo_url']        = 'SEO URL';
$_['issue_title_too_short']        = 'meta_title короткий';
$_['issue_title_too_long']         = 'meta_title длинный';
$_['issue_title_equals_name']      = 'title = название';
$_['issue_description_too_short']  = 'meta_description короткий';
$_['issue_description_too_long']   = 'meta_description длинный';
$_['issue_duplicate_title']        = 'Дубликат title';
$_['issue_duplicate_description']  = 'Дубликат description';
$_['issue_no_image']               = 'без изображения';
$_['issue_no_brand']               = 'без производителя';
$_['issue_no_body_description']    = 'без описания';
$_['issue_body_too_short']         = 'короткое описание';
$_['issue_short_content']          = 'мало контента';
$_['issue_images_no_alt']          = 'img без alt';
$_['issue_no_category']            = 'без категории';
$_['issue_empty_category']         = 'пустая категория';
$_['issue_no_price']               = 'нулевая цена';
$_['issue_no_model']               = 'без модели';
$_['issue_orphan_keyword']         = 'сирота keyword';
$_['issue_duplicate_keyword']      = 'дубликат keyword';
$_['issue_keyword_too_long']       = 'URL длинный';
$_['issue_keyword_too_short']      = 'URL короткий';
$_['issue_uppercase_in_keyword']   = 'заглавные в URL';
$_['issue_special_chars_in_keyword'] = 'спецсимволы в URL';

// Redirects page
$_['text_from_uri']              = 'Откуда (URI)';
$_['text_to_url']                = 'Куда (URL или URI)';
$_['text_redirect_modal_title']  = 'Редирект';
$_['button_add']                 = 'Добавить';

// Meta page
$_['text_bulk_fill']             = 'Массовая генерация';
$_['text_meta_overrides']        = 'Ручные override';
$_['text_meta_overrides_hint']   = 'Здесь хранятся мета-теги, которые вы вручную задали для конкретных товаров, категорий и т.д. Ручной override имеет наивысший приоритет — всегда перекрывает шаблон и родное поле OpenCart.';
$_['text_meta_entity_hint']      = 'Начните вводить название — автоподсказка предложит сущность. ID подставится автоматически.';
$_['label_entity_search']        = 'Сущность';
$_['text_entity_search_placeholder'] = 'Начните вводить название...';

$_['label_meta_tpl_mode']        = 'Приоритет шаблонов';
$_['text_meta_tpl_mode_override']= 'Перекрывать OC (жёсткий режим)';
$_['text_meta_tpl_mode_fallback']= 'Только как резерв';
$_['text_meta_tpl_mode_hint']    = '<strong>«Перекрывать OC»</strong> — шаблон всегда подставляется в HTML, даже если у товара/категории заполнено родное meta_title/description. Удобно когда заполняете мета-теги только через этот модуль.<br><strong>«Только как резерв»</strong> — если в OC-поле что-то есть, оно имеет приоритет; шаблон срабатывает только для пустых OC-полей. Удобно когда часть страниц заполняете вручную в OC, а остальные — через шаблон.<br>Ручные override (таблица ниже) всегда имеют наивысший приоритет.';
$_['text_meta_modal_title']      = 'Мета-тег override';
$_['label_search_meta']          = 'Поиск по title...';
$_['button_bulk_start']          = 'Заполнить автоматически';
$_['label_category']             = 'Категория';
$_['text_all_categories']        = 'Все категории';
$_['text_mask_hint_prefix']      = 'Маска URL:';
$_['text_title_hint']            = 'Рекомендуется до 60 символов';
$_['text_desc_hint']             = 'Рекомендуется до 160 символов';

// Audit page
$_['text_audit_run']             = 'Запуск аудита';
$_['text_audit_results']         = 'Результаты';
$_['text_audit_empty']           = 'Проблем не найдено';
$_['text_selected']              = 'выбрано';
$_['text_analyzing']             = 'Анализ базы данных...';
$_['text_level_error']           = 'Ошибки';
$_['text_level_warning']         = 'Предупреждения';
$_['text_level_info']            = 'Информация';
$_['button_audit_run']           = 'Запустить аудит';
$_['text_per_page']              = 'На страницу';

// Robots page
$_['text_robots_editor']         = 'Редактор robots.txt';
$_['text_robots_backups']        = 'Резервные копии';
$_['text_no_backups']            = 'Резервных копий нет';
$_['button_restore']             = 'Восстановить';

// Sitemap page
$_['text_sitemap_status_title']  = 'Статус Sitemap';
$_['text_sitemap_actions']       = 'Действия';
$_['text_jetsitemap_installed']  = 'Sitemap Generator установлен';
$_['text_jetsitemap_missing']    = 'Модуль OcKit Sitemap Generator не найден';
$_['text_no_sitemap_file']       = 'Файлы sitemap отсутствуют';
$_['text_sitemap_open_settings'] = 'Настройки Sitemap Generator';
$_['button_sitemap_generate']    = 'Обновить карту сайта';

// AbsURL page
$_['text_absurl_about_title']    = 'Что это за инструмент';
$_['text_absurl_about']         = 'В описаниях товаров и категорий могут храниться абсолютные URL вида <code>&lt;img src="http://old-domain.com/..."&gt;</code> или <code>&lt;a href="http://old-domain.com/..."&gt;</code>. Это происходит после смены домена или перехода с HTTP на HTTPS. Инструмент сканирует все описания, находит URL со старым доменом и заменяет их на новые — не затрагивая другой контент.';
$_['text_absurl_scan_title']     = 'Сканирование абсолютных URL';
$_['text_absurl_replace_title']  = 'Замена URL';
$_['text_absurl_log_title']      = 'Журнал изменений';
$_['label_search_domain']        = 'Домен для поиска';
$_['label_old_domain']           = 'Старый домен';
$_['label_new_domain']           = 'Новый домен';
$_['label_https_only']           = 'Только <code>http</code> → <code>https</code>';
$_['button_scan']                = 'Сканировать';
$_['button_replace_selected']    = 'Заменить выбранные';

// Dashboard page
$_['text_stat_seo_urls']         = 'SEO URL записей';
$_['text_stat_redirects']        = 'Активных редиректов';
$_['text_stat_audit_errors']     = 'SEO ошибок';
$_['text_stat_audit_warnings']   = 'Предупреждений аудита';
$_['text_stat_redirect_hits']    = 'Переходов по редиректам';
$_['text_stat_chains']           = 'Цепочек редиректов';
$_['text_quick_actions']         = 'Быстрые действия';
$_['text_audit_issues_top']      = 'Топ SEO проблем';
$_['text_all_audit_results']     = 'Все результаты аудита';
$_['text_top_redirects']         = 'Топ редиректов по переходам';
$_['text_chain_warning']         = 'Обнаружены цепочки редиректов';

// FAQ page
$_['text_faq_title']             = 'Часто задаваемые вопросы';

// Headers page
$_['text_headers_test']          = 'Тест правила';
$_['text_headers_rules']         = 'Правила заголовков';
$_['text_headers_about_title']   = 'Что это за инструмент';
$_['text_headers_about']         = 'Позволяет управлять заголовками <code>X-Robots-Tag</code> и мета-тегом <code>robots</code> для конкретных URL без правки <code>robots.txt</code>. Например, запретить индексацию страниц поиска, фильтров, кабинета — или временно закрыть готовящийся раздел. Для каждого URI-шаблона (поддерживаются wildcards <code>*</code>) вы указываете значение <code>robots</code>, решаете отправлять ли HTTP-заголовок, вставлять ли <code>&lt;meta name="robots"&gt;</code> (или и то и другое). Тест правил ниже проверяет, какое именно правило сработает для конкретного URI.';
$_['text_no_headers_rules']      = 'Правил заголовков пока нет. Нажмите «Добавить правило», чтобы создать первое.';
$_['button_add_rule']            = 'Добавить правило';
$_['label_hdr_uri']              = 'URI';
$_['label_hdr_robots']           = 'Robots';
$_['label_hdr_sort_order']       = 'Порядок сортировки';
$_['label_hdr_comment']          = 'Комментарий';
$_['label_hdr_status']           = 'Активно';
$_['placeholder_hdr_uri']        = '/catalog/product/* или /admin/*';

// JS i18n
$_['js_saving']                  = 'Сохранение...';
$_['js_saved']                   = 'Сохранено!';
$_['js_error_save']              = 'Ошибка сохранения.';
$_['js_regen_done']              = 'Регенерировано:';
$_['js_regen_inserted']          = 'создано';
$_['js_regen_updated']           = 'обновлено';
$_['js_regen_skipped']           = 'пропущено';
$_['js_regen_confirm_all']       = 'Перезаписать ВСЕ SEO URL для этого типа?';
$_['js_confirm_delete_redirect'] = 'Удалить этот редирект?';
$_['js_import_success']          = 'Импортировано:';
$_['js_import_skipped']          = 'Пропущено:';
$_['js_error_redirect_fields']   = 'Заполните поля Откуда и Куда.';
$_['js_confirm_delete_meta']     = 'Удалить этот override?';
$_['js_bulk_complete']           = 'Заполнение завершено:';
$_['js_bulk_filled']             = 'заполнено';
$_['js_bulk_skipped']            = 'пропущено';
$_['js_audit_running']           = 'Выполняется аудит...';
$_['js_audit_done']              = 'Аудит завершён';
$_['js_audit_errors']            = 'ошибок';
$_['js_audit_warnings']          = 'предупреждений';
$_['js_audit_info']              = 'инфо';
$_['js_confirm_restore_robots']  = 'Восстановить эту резервную копию? Текущий файл будет заменён.';
$_['js_robots_saved']            = 'robots.txt сохранён';
$_['js_sm_generate_ok']          = 'Генерация запущена в фоне';
$_['js_sm_generate_fail']        = 'Не удалось запустить генерацию';
$_['js_sm_ping_ok']              = 'Google успешно уведомлён';
$_['js_sm_ping_fail']            = 'Ping не удался';
$_['js_absurl_scan_found']       = 'найдено вхождений';
$_['js_absurl_replaced']         = 'Заменено строк';
$_['js_confirm_replace_absurl']  = 'Заменить URL в выбранных записях?';
$_['js_confirm_flatten']         = 'Исправить все цепочки редиректов автоматически?';
$_['js_flatten_done']            = 'Исправлено цепочек:';

// Buttons
$_['button_save']                = 'Сохранить';
$_['button_cancel']              = 'Отмена';
$_['button_delete']              = 'Удалить';
$_['button_flatten_chains']      = 'Исправить цепочки';

// Google Search Console (Google tab)
$_['tab_google']                  = 'Google';
$_['text_section_gsc']            = 'Google Search Console';
$_['text_section_gsc_stats']      = 'Поисковая аналитика';
$_['text_section_gsc_sitemaps']   = 'Sitemap-ы в GSC';
$_['text_gsc_about']              = 'Интеграция с Search Console + Indexing API через OAuth2. Позволяет: просматривать search-аналитику (запросы, клики, CTR, позиции), управлять sitemap-ами, инспектировать индексацию URL и мгновенно уведомлять Google об обновлении/удалении страниц (Indexing API).';
$_['text_gsc_redirect_hint']      = 'Скопируй этот URL в Google Cloud Console → Credentials → OAuth client → Authorized redirect URIs.';
$_['text_gsc_site_property_hint'] = 'Точное значение из GSC → Settings → Property. Для URL-prefix — с конечным слешем; для Domain property — формат <code>sc-domain:example.com</code>.';
$_['text_gsc_connect_hint']       = 'Сначала сохрани Client ID/Secret кнопкой «Сохранить», затем нажми «Подключить Google».';
$_['text_gsc_not_loaded']         = 'Нажми «Загрузить», чтобы получить данные за последние 28 дней.';
$_['text_gsc_connected']          = 'Подключено';
$_['text_gsc_disconnected']       = 'Не подключено';
$_['text_gsc_confirm_disconnect'] = 'Отключить Google и удалить сохранённый токен?';
$_['text_gsc_submitted']          = 'URL отправлен в Google';
$_['text_no_data']                = 'Нет данных';
$_['text_confirm_delete']         = 'Удалить?';
$_['label_gsc_redirect']          = 'Redirect URI';
$_['label_gsc_site_property']     = 'Site property';
$_['label_gsc_status']            = 'Подключение';
$_['button_gsc_connect']          = 'Подключить Google';
$_['button_gsc_disconnect']       = 'Отключить';
$_['button_load']                 = 'Загрузить';
$_['button_submit']               = 'Отправить';
$_['gsc_col_key']                 = 'Запрос / страница';
$_['gsc_col_clicks']              = 'Клики';
$_['gsc_col_impressions']         = 'Показы';
$_['gsc_col_ctr']                 = 'CTR';
$_['gsc_col_position']            = 'Позиция';
$_['gsc_col_path']                = 'Путь';
$_['gsc_col_last_submitted']      = 'Последний submit';
$_['gsc_col_errors']              = 'Ошибок';
$_['gsc_col_warnings']            = 'Предупреждений';
$_['gsc_dim_query']               = 'Запрос';
$_['gsc_dim_page']                = 'Страница';
$_['gsc_dim_country']             = 'Страна';
$_['gsc_dim_device']              = 'Устройство';


// Preview / Apply (bulk operations) + cache labels
$_['button_preview']             = 'Предпросмотр';
$_['button_apply']                = 'Применить';
$_['js_running_preview']         = 'Ищу…';
$_['js_running_apply']           = 'Применяю…';
$_['js_matched_preview']         = 'Найдено: ';
$_['js_matched_apply']           = 'Обновлено: ';
$_['js_confirm_bulk_apply']      = 'Применить замену? Действие нельзя отменить без бэкапа БД (исторические записи создаются автоматически).';
$_['cache_warm']          = 'сгенерирован';
$_['cache_cold']                 = 'холодный';
$_['cache_entries']              = 'записей';
$_['cache_kb']                   = 'КБ';

// Schema Data Providers — explainer (formal "Вы" form)
$_['text_schema_providers_about']      = 'Data Provider — это собственный PHP-класс, который <b>подтягивает дополнительные данные</b> для Schema.org-разметки (например: остаток на складе, оптовые цены, рейтинги из внешнего сервиса) и делает эти данные доступными в шаблонах правил Schema через подстановки <code>{{var.path}}</code>. Если вам хватает стандартных полей (Product, Offer, Organization и т.д.) — провайдеры не нужны.';
$_['text_schema_providers_howto_title']= 'Как это работает — краткий пример';
$_['text_schema_providers_step_1']     = '<b>1.</b> Создайте PHP-класс, реализующий интерфейс <code>OcKit\\SeoCore\\Libs\\SchemaDataProviderInterface</code>. Класс должен быть autoloadable (например, находиться в отдельном OCMOD-модуле со своим <code>$autoload</code>). Метод <code>getData()</code> возвращает массив, который станет доступен в шаблоне Schema:';
$_['text_schema_providers_step_2']     = '<b>2.</b> Зарегистрируйте FQCN класса в поле ниже (через запятую, точку с запятой или новую строку). Теперь в любом шаблоне Schema (вкладка Schema → Custom Rules) можно использовать эти переменные:';
$_['text_schema_providers_hint']       = 'Перечисляйте полные FQCN (с namespace) через запятую или новую строку. Если собственный код не нужен — оставьте поле пустым, стандартные Schema-типы (Product, BreadcrumbList, Organization, WebSite, Article) работают и так.';

// GSC how-to
$_['text_gsc_howto_title'] = 'Как получить Client ID / Client Secret';
$_['text_gsc_howto_1']     = 'Зайдите в <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> и создайте новый проект (или выберите существующий).';
$_['text_gsc_howto_2']     = 'В меню слева: <b>APIs &amp; Services → Library</b>. Включите два API: <code>Google Search Console API</code> и <code>Web Search Indexing API</code> (Enable на каждом).';
$_['text_gsc_howto_3']     = '<b>APIs &amp; Services → OAuth consent screen</b>. Тип <i>External</i>, заполните название приложения, support email, developer email. На шаге Scopes — пропустите. На шаге Test users — добавьте свою Google-почту, имеющую доступ к свойству GSC. Сохраните.';
$_['text_gsc_howto_4']     = '<b>APIs &amp; Services → Credentials → Create Credentials → OAuth client ID</b>. Тип <i>Web application</i>. В поле <b>Authorized redirect URIs</b> вставьте URL, который вы видите в поле <b>Redirect URI</b> ниже — точная копия, без изменений.';
$_['text_gsc_howto_5']     = 'Нажмите Create — Google покажет <b>Client ID</b> и <b>Client Secret</b>. Скопируйте оба в поля ниже.';
$_['text_gsc_howto_6']     = 'В поле <b>Site property</b> вставьте точное значение вашего свойства из GSC (для URL-prefix — с конечным слешем; для Domain property — в формате <code>sc-domain:example.com</code>). Нажмите <b>Сохранить</b>, затем <b>Подключить Google</b>.';

// Sitemap ping (modernized)
$_['text_sm_ping_google_hint']        = 'Submit sitemap в Google Search Console через API (нужно подключить OAuth во вкладке Google).';
$_['text_sm_ping_bing_hint']          = 'Bing sitemap ping отключён в 2022. Используйте Bing Webmaster Tools или IndexNow.';
$_['text_sm_ping_google_ok']          = 'Sitemap отправлен в Google Search Console.';
$_['text_sm_ping_google_need_oauth']  = 'Сначала подключите Google во вкладке «Google» (OAuth). Старый /ping endpoint Google отключён в 2023.';
$_['text_sm_ping_bing_deprecated']    = 'Bing /ping endpoint отключён в 2022. Используйте Bing Webmaster Tools или протокол IndexNow.';

$_['title_store_selector']        = 'Магазин (для многомагазинных установок)';
$_['placeholder_optional']        = '(опционально)';
$_['js_error']                    = 'Ошибка';
$_['text_org_type_organization']  = 'Organization (универсальный)';
$_['text_org_type_online_store']  = 'OnlineStore (онлайн-магазин)';
$_['text_org_type_store']         = 'Store (розничный магазин)';
$_['text_org_type_local_business']= 'LocalBusiness (локальный бизнес)';
$_['text_org_type_restaurant']    = 'Restaurant';
$_['ph_address_street']           = 'ул. Крещатик, 1';
$_['ph_address_city']             = 'Киев';
$_['ph_address_region']           = 'Киевская обл.';
$_['ph_price_range']              = '$$ или 100-500 UAH';
$_['ph_founding_date']            = '2015 или 2015-03-01';
$_['ph_founders']                 = "Иван Петренко\nЕлена Сидоренко";
$_['text_schema_placeholders_hint']= 'Подстановки: <code>{{product.name}}</code>, <code>{{category.name}}</code>, <code>{{get.param}}</code>, <code>{{config.store_name}}</code>, <code>{{page.url}}</code>, <code>{{page.title}}</code>. Блоки: <code>{{#each items}}…{{/each}}</code>, <code>{{#if cond}}…{{/if}}</code>.';
$_['text_meta_mode_override']     = 'template → HTML (всегда)';
$_['text_meta_mode_fallback']     = 'OC field → HTML, template → резерв';
$_['text_canonical_pagination_first_ex']= 'canonical → /sukni';
$_['text_canonical_pagination_self_ex'] = 'canonical → /sukni?page=2';
$_['text_canonical_filters_base_ex']    = 'canonical → base';
$_['text_canonical_filters_self_ex']    = 'canonical → self';

$_['text_image_alt_about']        = '<strong>Что это делает:</strong> находит <code>&lt;img&gt;</code> теги <strong>внутри HTML-описания товара</strong> (поле <code>oc_product_description.description</code>, редактируемое в WYSIWYG) и добавляет <code>alt="название товара"</code> к тем, где alt отсутствует. Существующие alt-атрибуты НЕ перезаписываются.<br><br><strong>Что НЕ делает:</strong> не затрагивает галерею (thumb/oc_product_image) — это контролируется шаблоном темы. Не затрагивает описания категорий / статей / производителей.<br><br><strong>Preview</strong>: симулирует — возвращает, сколько записей и <code>&lt;img&gt;</code>-тегов будет затронуто, БЕЗ записи в БД.<br><strong>Apply</strong>: реально UPDATE-ит <code>product_description.description</code>.';

$_['js_url_not_set']         = 'URL не определён';
$_['js_running']             = 'Выполняю…';
$_['js_preview_label']       = 'Preview: ';
$_['js_done_label']          = 'Готово: ';
$_['js_products_label']      = 'товаров ';
$_['js_alts_added']          = ', alt добавлено ';
$_['js_confirm_img_alt']     = 'Запустить массовое заполнение alt для всех товаров? Действие нельзя отменить без бэкапа БД.';
$_['js_broken_none']         = 'Битых ссылок не найдено';
$_['js_loading']             = 'Загрузка…';
$_['js_records_label']       = 'Записей: ';

// Missing keys after |default strip — restored
$_['tab_home']                       = 'Главная страница';
$_['label_home_redirect_index']      = 'Убрать <code>/index.php?route=common/home</code>';
$_['label_schema_org_contact_languages']= 'Языки поддержки';
$_['label_schema_org_country']       = 'Страна (ISO-код)';
$_['label_schema_org_founders']      = 'Основатели';
$_['label_schema_org_founding_date'] = 'Дата основания';
$_['label_schema_org_geo']           = 'Latitude / Longitude';
$_['label_schema_org_locality']      = 'Город';
$_['label_schema_org_opening_hours'] = 'Часы работы';
$_['label_schema_org_postal_code']   = 'Почтовый индекс';
$_['label_schema_org_price_range']   = 'Ценовая категория (priceRange)';
$_['label_schema_org_region']        = 'Область / штат';
$_['label_schema_org_same_as']       = 'sameAs (соцсети)';
$_['label_schema_org_street']        = 'Улица, дом';
$_['label_schema_org_type']          = 'Тип организации';
$_['label_schema_org_vat_id']        = 'vatID / Tax ID';
$_['label_schema_providers']         = 'Provider classes';
$_['label_strip_query_params']       = 'Удалять query-параметры из canonical';
$_['label_trailing_slash_all']       = 'Все URL';
$_['label_trailing_slash_categories']= 'Только категории';
$_['label_trailing_slash_off']       = 'Без слеша';
$_['label_trailing_slash_products']  = 'Только товары';
$_['label_webhook_secret']           = 'Shared secret';
$_['label_webhook_url']              = 'Endpoint URL';
$_['text_ab_test_enabled']           = 'Включено';
$_['text_ab_test_enabled_hint']      = 'Включить ротацию title-вариантов на каталоге';
$_['text_ab_test_help']              = 'Создайте два варианта title для сущности — модуль показывает A или B случайно (стабильно для посетителя через cookie). Счётчики показов помогают определить победителя по CTR из Search Console.';
$_['text_ab_test_new']               = 'Новый A/B тест title';
$_['text_ab_test_title']             = 'A/B тест title';
$_['text_broken_links']              = 'Битые ссылки';
$_['text_broken_links_about']        = 'Сканер ищет <code>&lt;a href&gt;</code> в описаниях товаров/категорий/производителей/информационных страниц и HEAD-пингует каждый уникальный URL. Результаты сохраняются в БД, повторный скан — перезаписывает.';
$_['text_hints_examples']            = 'Примеры: rel=dns-prefetch href=//fonts.gstatic.com | rel=preconnect href=https://cdn.example.com | rel=preload href=/font.woff2 as=font type=font/woff2 crossorigin=anonymous';
$_['text_home_about']                = 'Мета-теги для главной страницы. Заполните для каждого языка — пустые поля заменяются значениями по умолчанию магазина.';
$_['text_home_redirect_index_hint']  = 'Когда включено: все внутренние ссылки на главную генерируются как <code>/</code> или <code>/{prefix}/</code> без <code>/index.php?route=common/home</code>';
$_['text_image_alt_tools']           = 'Image alt — массовое заполнение';
$_['text_redirect_expires']          = 'Дата окончания';
$_['text_redirect_expires_hint']     = 'Опционально: после этой даты редирект автоматически перестаёт действовать (можно удалить крон-задачей).';
$_['text_resource_hints_about']      = 'Добавляет теги &lt;link rel="preload"&gt; / &lt;link rel="dns-prefetch"&gt; / &lt;link rel="preconnect"&gt; в &lt;head&gt; всех страниц. Полезно для ускорения загрузки критических ресурсов (шрифты, ключевые скрипты) и DNS-резолва внешних доменов.';
$_['text_robots_quick_block_index']  = 'Заблокировать ?route=*';
$_['text_robots_quick_default']      = 'Стандартный шаблон';
$_['text_robots_quick_sitemap']      = 'Добавить Sitemap URL';
$_['text_schema_org_address']        = 'Адрес (PostalAddress)';
$_['text_schema_org_contact_languages_hint']= 'Список языков, на которых отвечает ваша служба поддержки. Через запятую или с новой строки.';
$_['text_schema_org_country_hint']   = 'Двухбуквенный код ISO 3166-1 alpha-2: UA, PL, US и т.д.';
$_['text_schema_org_founders_hint']  = 'Имена основателей, по одному на строку.';
$_['text_schema_org_founding_date_hint']= 'Год или полная дата в формате ISO 8601 (YYYY-MM-DD).';
$_['text_schema_org_geo']            = 'Геокоординаты + часы';
$_['text_schema_org_geo_hint']       = 'Координаты нужны для LocalBusiness/Restaurant. Можно скопировать из Google Maps (правый клик → Координаты).';
$_['text_schema_org_meta']           = 'Профили, реквизиты, основатели';
$_['text_schema_org_opening_hours_hint']= 'Одна строка на период. Дни: Mo, Tu, We, Th, Fr, Sa, Su (можно диапазон Mo-Fr). Время в формате HH:MM-HH:MM. Слово <code>closed</code> или просто пропустите строку для выходных.';
$_['text_schema_org_price_range_hint']= 'Только для LocalBusiness/Store/Restaurant/OnlineStore. Допустимо $-$$$$ или диапазон в валюте.';
$_['text_schema_org_same_as_hint']   = 'URL ваших официальных профилей (один на строку). Google использует для связывания данных о бренде.';
$_['text_schema_org_type_hint']      = 'Для LocalBusiness/Store/Restaurant Google требует адрес и часы работы. Для онлайн-магазина выбирайте OnlineStore или Organization.';
$_['text_schema_providers']          = 'Data Providers';
$_['text_section_resource_hints']    = 'Preload / DNS prefetch';
$_['text_section_webhook']           = 'Webhook';
$_['text_stat_seo_score']            = 'SEO Score';
$_['text_stat_seo_score_hint']       = 'Сводный балл 0–100 на основе последнего аудита: 100 - (errors*30 + warnings*10 + info*2) / N сущностей';
$_['text_strip_query_params_hint']   = 'Список параметров, которые нужно выбрасывать из канонического URL при 301-редиректе (трекинг-метки). Через запятую или с новой строки. Поддерживается wildcard в конце: <code>utm_*</code> покроет <code>utm_source</code>, <code>utm_medium</code> и т.д. Остальные GET-параметры (page, ajax, sort, filter…) сохраняются автоматически.';
$_['text_url_bulk_find']             = 'Найти';
$_['text_url_bulk_regex']            = 'PCRE-режим';
$_['text_url_bulk_replace']          = 'Массовый поиск/замена в keyword';
$_['text_url_bulk_replace_about']    = 'Найти подстроку (или PCRE-шаблон) и заменить её во всех SEO URL keyword. Preview — без записи. Apply — обновляет DB, добавляет исторические записи и auto-redirect 301 для изменённого слага.';
$_['text_url_bulk_replace_with']     = 'Заменить на';
$_['text_url_history']               = 'История изменений URL';
$_['text_webhook_about']             = 'HTTP POST на внешний URL при изменении SEO URL/редиректов. Полезно для CDN purge, Slack-уведомлений, ETL pipeline. Payload: { event, timestamp, payload }. Подпись в заголовке X-SCF-Signature: sha256=&lt;HMAC&gt;.';
$_['text_webhook_secret_hint']       = 'Если задано — каждый POST будет иметь заголовок X-SCF-Signature: sha256=HMAC(body, secret) для верификации на стороне получателя.';

$_['text_org_group_general']  = 'Общие';
$_['text_org_group_local']    = 'Магазины (LocalBusiness)';
$_['text_org_group_food']     = 'Еда и напитки';
$_['text_org_group_services'] = 'Услуги';
$_['text_org_group_auto_home']= 'Авто и строительство';
$_['text_org_group_lodging']  = 'Отели и аренда';
$_['text_org_group_other']    = 'Образование, спорт, прочее';
$_['button_send']           = 'Отправить';

$_['label_allow_duplicate_keywords'] = 'Одинаковые SEO URL для разных языков';
$_['text_allow_duplicate_keywords_hint'] = 'По умолчанию OpenCart 3 запрещает ввести одинаковый <code>keyword</code> для разных языков одного товара/категории/производителя/инфо-страницы (ошибка «должен быть уникальным»). Когда включено — эта кросс-языковая проверка обходится, и вы можете писать один slug во все языковые поля сразу. Кросс-сущностная проверка (один slug на разные товары) остаётся.';
$_['cache_warmed']        = 'Кэш сгенерирован:';

$_['text_type_article']         = 'Блог-пост';
$_['text_type_blog_category']   = 'Категория блога';
$_['text_meta_vars_article']    = 'Доступные переменные: <code>{name}</code> — название статьи, <code>{description}</code> — 160 символов содержания, <code>{store_name}</code> — название магазина, <code>{year}</code> — текущий год, <code>{page}</code> — № страницы (пусто на page 1).<br>Условные блоки: <code>{{#if page}} — страница {page}{{/if}}</code>.';
$_['text_meta_vars_blog_category'] = 'Доступные переменные: <code>{name}</code> — название, <code>{description}</code> — 160 символов описания, <code>{count}</code> — количество постов, <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>.';

$_['text_section_route_meta']    = 'Meta для кастомных роутов';
$_['text_route_meta_about']      = '<strong>Что это такое?</strong> Страницы без сущности (список производителей <code>product/manufacturer</code>, главная блога <code>blog/menu</code>, сторонние модули <code>vendor/vendor</code> и т.д.) не имеют entity-meta. Здесь вы задаёте title/description/keywords <strong>по роуту</strong>, отдельно для каждого языка. Поддерживаются wildcard-маски (<code>vendor/*</code>) и переменные <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>, условные блоки <code>{{#if page}}…{{/if}}</code>.';
$_['text_route_meta_route_hint'] = 'Точный роут (<code>product/manufacturer</code>) или маска с wildcard (<code>vendor/*</code>). Если для страницы уже работает entity-meta (товар/категория) — route-meta не перебивает её, работает как fallback.';
$_['text_route_meta_vars_hint']  = 'Доступные переменные: <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code> (пусто на page 1). Условные блоки: <code>{{#if page}} — стр. {page}{{/if}}</code>.';
$_['text_route_meta_modal_title']= 'Meta для роута';
$_['button_add_manufacturer_list']= '+ Список производителей';
$_['button_add_blog_index']       = '+ Блог';

$_['text_section_image_masks']    = 'Маски alt/title для изображений товара';
$_['text_image_masks_about']      = '<strong>Что это такое?</strong> Title и alt для всех изображений галереи товара генерируются автоматически из маски — отдельно для каждого языка. Удобно для SEO и accessibility, не нужно заполнять вручную для каждого товара.';
$_['text_image_masks_vars']       = 'Переменные: <code>{name}</code> — название, <code>{category}</code> — главная категория, <code>{price}</code> — цена, <code>{special}</code> — акционная цена (пусто если нет акции), <code>{sort_order}</code> — порядковый номер (1, 2, 3…), <code>{sku}</code>, <code>{model}</code>, <code>{manufacturer}</code> / <code>{brand}</code>, <code>{store_name}</code>, <code>{year}</code>.<br>Условные блоки: <code>{{#if special}} акция {special}{{/if}}</code> — рендерится только если переменная непустая. <strong>Тема магазина</strong> должна использовать <code>image.alt</code> / <code>image.title</code> в теге <code>&lt;img&gt;</code>.';
$_['label_image_alt_tpl']         = 'Шаблон alt';
$_['label_image_title_tpl']       = 'Шаблон title';
