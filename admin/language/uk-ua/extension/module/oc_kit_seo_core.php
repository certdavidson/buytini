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
$_['text_extension']             = 'Розширення';
$_['text_home']                  = 'Головна';
$_['text_success']               = 'Налаштування збережено!';
$_['text_license_activated']     = 'Ліцензію активовано!';
$_['text_enabled']               = 'Увімкнено';
$_['text_disabled']              = 'Вимкнено';

// Tab labels
$_['tab_settings']               = 'Налаштування';
$_['tab_meta']                   = 'Мета-теги';
$_['tab_redirects']              = 'Редиректи';
$_['tab_urls']                   = 'SEO URL';
$_['tab_headers']                = 'Заголовки';
$_['tab_audit']                  = 'Аудит';
$_['tab_robots']                 = 'robots.txt';
$_['tab_sitemap']                = 'Sitemap';
$_['tab_absurl']                 = 'Заміна домену';
$_['tab_dashboard']              = 'Дашборд';
$_['tab_faq']                    = 'FAQ';
$_['tab_masks']                  = 'Маски URL';
$_['tab_canonical']              = 'Канонікали';
$_['tab_hreflang']                = 'Hreflang';
$_['tab_opengraph']               = 'Open Graph';
$_['tab_schema']                  = 'Schema.org';

// Canonical tab
$_['text_canonical_auto']              = 'Автоматичні правила';
$_['text_canonical_overrides']         = 'Ручні override';
$_['text_canonical_test']              = 'Тест canonical';
$_['label_canonical_pagination']       = 'Canonical для пагінації';
$_['text_canonical_pagination_first']  = 'Перша сторінка';
$_['text_canonical_pagination_self']   = 'Поточна сторінка';
$_['label_canonical_filters']          = 'Canonical для фільтрів';
$_['text_canonical_filters_base']      = 'Базова категорія';
$_['text_canonical_filters_self']      = 'Поточна URL';
$_['text_canonical_filters_noindex']   = 'noindex robots';
$_['label_canonical_cross_domain']     = 'Cross-domain canonical';
$_['text_canonical_cross_domain_hint'] = 'Для дзеркальних сайтів: всі canonical будуть вказувати на цей домен. Залиште порожнім — використовується поточний домен.';
$_['button_test']                      = 'Перевірити';

// Placeholders for tabs in development
$_['text_hreflang_coming']  = 'Налаштування hreflang будуть додані в наступній фазі. Наразі бібліотека HreflangBuilder вже інтегрована й рендерить теги автоматично для всіх активних мов.';
$_['text_hreflang_about']   = '<strong>Hreflang</strong> повідомляє пошуковим системам про альтернативні мовні версії сторінки: <code>&lt;link rel="alternate" hreflang="uk" href="..."&gt;</code>. Це запобігає плутанині між схожими URL і покращує правильне відображення регіональних версій у видачі. Теги генеруються автоматично для всіх мов, де у сторінки є SEO URL.';
$_['text_hreflang_preview'] = 'Превью тегів';
$_['label_hreflang_enabled']= 'Увімкнути hreflang';
$_['label_hreflang_format'] = 'Формат коду';
$_['text_opengraph_coming'] = 'Панель Open Graph з налаштуваннями тегів og:* та Twitter Card буде додана в наступній фазі. Наразі стандартні og-теги рендеряться автоматично через OpenGraphRenderer.';
$_['text_og_about']            = '<strong>Open Graph</strong> (Facebook, LinkedIn, Telegram тощо) і <strong>Twitter Card</strong> задають як сторінка виглядатиме при поширенні в соцмережах: заголовок, опис, зображення. Якщо шаблони порожні — використовуються значення з Meta-тегів.';
$_['text_og_templates']        = 'Шаблони тегів';
$_['text_og_templates_hint']   = 'Змінні: <code>{name}</code>, <code>{description}</code>, <code>{price}</code>, <code>{manufacturer}</code>, <code>{store_name}</code>, <code>{year}</code>';
$_['label_og_enabled']         = 'Увімкнути Open Graph';
$_['label_og_twitter_card']    = 'Twitter Card';
$_['label_og_twitter_handle']  = 'Twitter handle';
$_['label_og_image_fallback']  = 'URL зображення';
$_['text_og_twitter_handle_hint']= 'Username аккаунта в Twitter/X з символом @, наприклад @yoursite. Використовується в <code>twitter:site</code>.';
$_['text_og_image_fallback_hint']= 'URL зображення за замовчуванням — використовується коли у сутності немає власного зображення. Рекомендовано 1200×630px.';
$_['text_schema_coming']    = 'Schema.org панель з toggles стандартних типів (Product, Organization, Breadcrumb, Article, WebSite) та кастомним JSON-LD редактором буде додана в наступній фазі.';
$_['text_schema_about']         = '<strong>Schema.org</strong> — структурована розмітка JSON-LD для пошукових систем. Вмикаючи тип, ви додаєте відповідний блок у <code>&lt;head&gt;</code> сторінок. Це потрібно для Rich Snippets у Google (рейтинг, ціна, наявність, хлібні крихти), Organization-card тощо.';
$_['text_schema_standard']      = 'Стандартні типи';
$_['text_schema_organization']  = 'Organization';
$_['text_schema_custom']        = 'Custom правила (JSON-LD редактор)';
$_['text_schema_custom_hint']   = 'Створюйте власні JSON-LD блоки для кастомних сторінок. У шаблоні підтримуються <code>{{var}}</code> змінні контексту, цикли <code>{{#each}}</code>, умови <code>{{#if}}</code>.';
$_['label_schema_min_reviews']  = 'Мін. відгуків для aggregateRating';
$_['text_schema_min_reviews_hint']= 'Нижче цієї кількості aggregateRating не додається в Product JSON-LD (щоб уникнути недостовірних даних).';
$_['label_schema_org_name']     = 'Назва організації';
$_['label_schema_org_logo']     = 'URL логотипу';
$_['label_schema_org_phone']    = 'Телефон (E.164)';
$_['label_schema_org_email']    = 'Email';
$_['text_schema_modal_title']   = 'Custom Schema.org правило';
$_['label_schema_route']        = 'Route pattern';
$_['label_schema_preview']      = 'Превью';
$_['label_schema_template']     = 'JSON-LD шаблон';
$_['label_schema_priority']     = 'Пріоритет';
$_['label_schema_status']       = 'Статус';
$_['button_validate']           = 'Перевірити';

// Cache section
$_['text_section_cache']  = 'Кеш SEO URL';
$_['text_cache_hint']     = 'Згенерований кеш містить усі <code>keyword → query</code> мапи в пам\'яті, що усуває DB-запит при кожному URL. Рекомендується перегенерувати після масової генерації URL або після зміни глибини URL.';
$_['button_warm_cache']   = 'Згенерувати кеш';
$_['button_clear_cache']  = 'Очистити кеш';
$_['button_export_csv']   = 'Експорт CSV';
$_['button_delete_stale'] = 'Старі (0 hits)';
$_['text_redirects_stale_hint'] = 'Видалити редиректи без використання (0 hits) старіші за N днів';
$_['redirects_stale_days_prompt']= 'Видалити редиректи з 0 hits старіші N днів:';
$_['confirm_delete_stale']= 'Видалити всі редиректи з 0 hits старіші';
$_['days']                = 'днів';
$_['deleted']             = 'Видалено';

// FAQ section titles
$_['text_faq_security']     = 'Security Headers та HSTS';
$_['text_faq_audit']        = 'Аудит SEO';
$_['text_faq_schema']       = 'Schema.org (JSON-LD)';
$_['text_faq_sitemap']      = 'Sitemap';
$_['text_faq_hreflang']     = 'Hreflang';
$_['text_faq_troubleshoot'] = 'Troubleshooting';

// Settings
$_['label_status']               = 'Статус';
$_['label_url_depth']            = 'Глибина URL категорій';
$_['label_trailing_slash']       = 'Кінцевий слеш';
$_['label_lang_prefixes']        = 'Мовні префікси';
$_['label_custom_routes']        = 'Кастомні роути';
$_['label_pagination_mode']      = 'Пагінація';
$_['label_noindex_all_pagination'] = 'Noindex для всіх сторінок пагінації';
$_['label_mask_product']         = 'Маска URL — товар';
$_['label_mask_category']        = 'Маска URL — категорія';
$_['label_mask_manufacturer']    = 'Маска URL — виробник';
$_['label_mask_information']     = 'Маска URL — інформація';
$_['label_auto_generate_url']    = 'Авто-генерація URL при відвідуванні';
$_['help_auto_generate_url']     = 'Якщо увімкнено, відсутній SEO URL для товару/категорії/виробника/статті створюється автоматично при першому відвідуванні сторінки — за заданою маскою. Регенерувати вручну в адмінці не потрібно.';

// URL depth options
$_['text_depth_flat']            = 'Без вкладеності (плоский)';
$_['text_depth_1']               = '1 рівень';
$_['text_depth_2']               = '2 рівні';
$_['text_depth_full']            = 'Повна ієрархія';

// Pagination mode options
$_['text_pagination_off']        = 'Вимкнено';
$_['text_pagination_404']        = '404 для зайвих сторінок';
$_['text_pagination_redirect_last'] = '301 редирект на останню валідну';
$_['text_pagination_robots']     = 'Noindex через X-Robots-Tag';

// Redirects
$_['label_from_url']             = 'Звідки';
$_['label_to_url']               = 'Куди';
$_['label_redirect_code']        = 'Код';
$_['label_hits']                 = 'Переходів';
$_['button_redirect_add']        = 'Додати редирект';
$_['button_import_csv']          = 'Масова вставка';
$_['text_bulk_paste_title']      = 'Масова вставка редиректів';
$_['text_bulk_paste_hint']       = 'По одному редиректу на рядок. Формат: <code>/звідки, /куди, код</code>. Код необов’язковий (за замовчуванням 301). Підтримуються коди: 301, 302, 303, 307, 308, 410.';
$_['text_code_301']              = '<span class="ok-badge ok-badge-success-soft">301</span> Постійний';
$_['text_code_302']              = '<span class="ok-badge ok-badge-warning-soft">302</span> Тимчасовий';
$_['text_code_303']              = '<span class="ok-badge ok-badge-warning-soft">303</span> See Other (POST → GET)';
$_['text_code_307']              = '<span class="ok-badge ok-badge-warning-soft">307</span> Тимчасовий (зберігає метод)';
$_['text_code_308']              = '<span class="ok-badge ok-badge-success-soft">308</span> Постійний (зберігає метод)';
$_['text_code_410']              = '<span class="ok-badge ok-badge-error-soft">410</span> Gone (сторінка видалена)';

$_['text_code_guide_title']      = 'Який код вибрати?';
$_['text_code_301_use']          = 'Стандартний вибір для SEO. Сторінка переїхала назавжди (перейменування URL, зміна структури). Пошуковики передають вагу посилань на новий URL.';
$_['text_code_302_use']          = 'Тимчасове перенаправлення (акція, A/B-тест, техобслуговування). Пошуковики НЕ передають вагу — старий URL залишається в індексі.';
$_['text_code_303_use']          = 'Після POST-запиту — щоб браузер перейшов GET-ом на результат і не повторив форму при F5. Рідко використовується для SEO.';
$_['text_code_307_use']          = 'Як 302, але гарантує збереження HTTP-методу (POST → POST). Використовується коли важливо не переривати метод.';
$_['text_code_308_use']          = 'Як 301, але зберігає HTTP-метод. Рідкісний випадок: постійний редирект для API-ендпоінтів, які приймають POST/PUT.';
$_['text_code_410_use']          = 'Сторінка видалена назавжди, повертатися не буде. Пошуковики швидко прибирають її з індексу (швидше ніж 404). Поле «Куди» не потрібне.';
$_['placeholder_search_redirects'] = 'Пошук редиректів...';

// License
$_['tab_license']                = 'Ліцензія';
$_['label_license_key']          = 'Ліцензійний ключ';
$_['entry_license_key']          = 'Ліцензійний ключ';
$_['button_activate']            = 'Активувати';
$_['text_license_version']       = 'Версія';
$_['text_license_activating']    = 'Активація...';
$_['text_license_buy']           = 'Придбати ліцензію';
$_['text_license_trial']         = 'Пробний період: %d дн. залишилось';
$_['text_license_expired']       = 'Ліцензія прострочена';
$_['text_license_invalid']       = 'Недійсна ліцензія';
$_['text_license_api_error']     = 'Сервер ліцензій недоступний';
$_['text_license_not_validated'] = 'Ліцензію не перевірено';
$_['text_license_status_active'] = 'Активна';
$_['text_license_status_trial']  = 'Пробний період';
$_['text_license_status_expired'] = 'Прострочено';
$_['text_license_status_grace']  = 'Пільговий період';
$_['text_license_status_invalid'] = 'Недійсна';
$_['text_license_status_not_validated'] = 'Не перевірено';

// Errors
$_['error_license_invalid_key']  = 'Неправильний ліцензійний ключ.';
$_['error_license_api_unreachable'] = 'Сервер ліцензій недоступний. Спробуйте пізніше.';
$_['error_redirect_fields']      = 'Вкажіть URL "Звідки" та "Куди".';
$_['error_redirect_loop']        = 'Редирект створює ланцюг або петлю.';
$_['error_import_empty']         = 'CSV-дані порожні.';

$_['label_route_pattern']        = 'Шаблон роуту';
$_['label_entity_id']            = 'Query-параметр';
$_['label_route']                = 'Роут OpenCart';
$_['text_skip_routes_hint']      = '<strong>Що це таке?</strong> OpenCart всередині ідентифікує кожну сторінку по "роуту" — наприклад <code>product/search</code>, <code>account/login</code>, <code>information/information</code>. Тут ви вказуєте, для яких роутів <em>не треба</em> генерувати SEO URL. Корисно для сторінок пошуку, особистого кабінету та інших системних сторінок, які не мають сенсу мати красивий ЧПУ. Підтримуються маски: <code>account/*</code>.';
$_['text_entity_routes_hint']    = '<strong>Що це таке?</strong> За замовчуванням SEO Core знає лише стандартні роути OpenCart (товар, категорія, виробник, інформаційна сторінка). Якщо у вас є нестандартна сторінка від стороннього модуля — наприклад вендор <code>index.php?route=vendor/vendor/view&amp;vendor_id=4</code> — модуль не здогадається, що параметр <code>vendor_id</code> належить роуту <code>vendor/vendor/view</code>, тому SEO URL для неї не працює.<br><br><strong>Рішення:</strong> додайте цей зв\'язок сюди <strong>один раз</strong> — у поле «Query-параметр» впишіть <code>vendor_id</code>, у поле «Роут OpenCart» — <code>vendor/vendor/view</code>. Після збереження всі записи <code>oc_seo_url</code> з <code>query=vendor_id=N</code> почнуть працювати автоматично: і відкриватись по красивому URL, і підмінятись у посиланнях по сайту. Лізти в код не треба.';

// Meta templates
$_['text_section_meta_templates']  = 'Шаблони мета-тегів';
$_['label_meta_title_tpl']         = 'Шаблон Title';
$_['label_meta_desc_tpl']          = 'Шаблон Description';
$_['label_meta_h1_tpl']            = 'Шаблон H1';
$_['text_meta_vars_product']       = 'Доступні змінні: <code>{name}</code> — назва, <code>{sku}</code> — артикул, <code>{manufacturer}</code> — виробник, <code>{category}</code> — категорія, <code>{price}</code> — ціна, <code>{description}</code> — 160 символів опису, <code>{store_name}</code> — назва магазину, <code>{year}</code> — поточний рік, <code>{page}</code> — № сторінки (порожнє на page 1).<br>Умовні блоки: <code>{{#if page}} — сторінка {page}{{/if}}</code> — рендериться лише якщо змінна непорожня.';
$_['text_meta_vars_category']      = 'Доступні змінні: <code>{name}</code> — назва, <code>{count}</code> — кількість товарів, <code>{store_name}</code> — назва магазину, <code>{year}</code> — поточний рік, <code>{page}</code> — № сторінки (порожнє на page 1).<br>Умовні блоки: <code>{{#if page}} — сторінка {page}{{/if}}</code> — рендериться лише якщо змінна непорожня.';
$_['text_meta_vars_manufacturer']  = 'Доступні змінні: <code>{name}</code> — назва бренду, <code>{store_name}</code> — назва магазину, <code>{year}</code> — поточний рік, <code>{page}</code> — № сторінки (порожнє на page 1).<br>Умовні блоки: <code>{{#if page}} — сторінка {page}{{/if}}</code> — рендериться лише якщо змінна непорожня.';
$_['text_meta_vars_information']   = 'Доступні змінні: <code>{name}</code> — назва сторінки, <code>{description}</code> — 160 символів вмісту, <code>{store_name}</code> — назва магазину, <code>{year}</code> — поточний рік, <code>{page}</code> — № сторінки (порожнє на page 1).<br>Умовні блоки: <code>{{#if page}} — сторінка {page}{{/if}}</code> — рендериться лише якщо змінна непорожня.';
$_['text_meta_tpl_hint']           = '<strong>Як працює шаблон:</strong> рендериться в реальному часі на публічній частині — поведінка керується налаштуванням <em>«Пріоритет шаблонів»</em> вище.<br><strong>Порядок пріоритетів:</strong> Ручний override (таблиця нижче) → Шаблон/OC-поле (залежно від режиму) → порожнє значення.<br><strong>«Масове заповнення»</strong> нижче — це разова операція: прорендерить шаблон для вибраних сутностей і <em>запише результат у таблицю override</em> (не в рідні OC-поля товара!). Після цього такі значення стають жорстко прибитими до сутності, їх можна редагувати через «Ручні override» нижче, а зміни шаблону на них уже не вплинуть.';

// Settings sections
$_['text_section_general']       = 'Загальні';
$_['text_section_url']           = 'URL';
$_['text_section_url_masks']     = 'Маски URL';
$_['text_section_pagination']    = 'Пагінація';
$_['text_section_lang_prefixes'] = 'Мовні префікси';
$_['label_lang_default']         = 'За замовч.';
$_['text_section_custom_routes'] = 'Кастомні роути';
$_['text_lang_prefix_hint']      = 'Залиште префікс порожнім для основної мови (без префіксу в URL). Інші мови використовують свій префікс, наприклад /ru/slug.';
$_['text_skip_routes']           = 'Пропущені роути (без генерації SEO URL)';
$_['text_entity_routes']         = 'Кастомні роути';
$_['text_depth_hint']            = 'Без вкладень: /slug · 1 рівень: /category/slug · Повна: /cat/sub/slug';
$_['text_depth_flat_ex']         = 'site.com/slug';
$_['text_depth_1_ex']            = 'site.com/category/slug';
$_['text_depth_2_ex']            = 'site.com/cat/sub/slug';
$_['text_depth_full_ex']         = 'site.com/cat/sub/sub/slug';

$_['label_product_include_category'] = 'Префікс категорії в URL товару';
$_['text_product_category_off']  = 'Ні — site.com/product-slug';
$_['text_product_category_on']   = 'Так — site.com/category/product-slug';
$_['text_product_category_hint'] = 'Чи підставляти шлях категорії перед slug-ом товару. Вимкнено — плоскі URL товарів (рекомендується для більшості магазинів).';

$_['text_skip_routes_why']       = 'Для службових сторінок (пошук, особистий кабінет, кошик) красивий URL не потрібен і може ламати функціонал. Такі роути треба виключити з генерації SEO URL.';
$_['text_entity_routes_why']     = 'Сторінки сторонніх модулів (вендори, блог тощо) мають нестандартний роут. Прив\'яжіть query-параметр до роуту — і SEO URL для них почнуть працювати без правок коду.';

$_['text_section_skip_routes']   = 'Пропущені роути';
$_['text_section_entity_routes'] = 'Кастомні роути';

$_['label_noindex_from_page']    = 'Починати noindex зі сторінки №';
$_['text_noindex_from_page_hint']= '1 — всі сторінки пагінації. 2 — перша залишається індексованою, а ?page=2, 3… отримують noindex. Працює разом з налаштуванням вище.';
$_['text_pagination_mode_hint']  = 'Як обробляти зайві / невалідні сторінки пагінації. Може поєднуватися з noindex нижче.';
$_['text_pagination_intro']         = 'Ці два налаштування незалежні і можуть працювати одночасно: одне керує долею невалідних сторінок, інше — індексацією валідних.';
$_['text_pagination_invalid_title'] = 'Невалідні сторінки (поза діапазоном)';
$_['text_pagination_noindex_title'] = 'Noindex для валідних сторінок пагінації';
$_['label_noindex_delivery']        = 'Спосіб блокування';
$_['text_noindex_delivery_meta']    = '<meta name="robots" content="noindex">';
$_['text_noindex_delivery_header']  = 'HTTP-заголовок X-Robots-Tag';
$_['text_noindex_delivery_both']    = 'Обидва (мета-тег + HTTP-заголовок)';
$_['text_noindex_delivery_hint']    = 'Мета-тег читається ботами при парсингу HTML. X-Robots-Tag — серверний заголовок, ефективний для будь-яких ресурсів (включно з нон-HTML) і працює раніше — до завантаження тіла.';

$_['label_mask_product_ex']      = '{name}-{product_id}';
$_['label_mask_category_ex']     = '{name}';
$_['text_mask_section_hint']     = 'Маски задають формат SEO URL при автоматичній генерації. Працюють для нових записів і при регенерації нижче.';

$_['text_var_name']              = 'назва';
$_['text_var_model']             = 'артикул';
$_['text_var_sku']               = 'SKU';
$_['text_var_product_id']        = 'ID товару';
$_['text_var_category_id']       = 'ID категорії';
$_['text_var_manufacturer_id']   = 'ID виробника';
$_['text_var_information_id']    = 'ID сторінки';
$_['text_trailing_slash_hint']   = 'Увімк.: /url/ · Вимк.: /url';
$_['text_mask_hint']             = 'Приклад: {name} → nike-air-max';

// Regen
$_['label_regen_type']           = 'Тип';
$_['label_regen_lang']           = 'Мова';
$_['label_regen_mode']           = 'Режим';
$_['text_regen_empty']           = 'Тільки порожні';
$_['text_regen_all']             = 'Всі (перезаписати)';
$_['button_regen']               = 'Регенерувати SEO URL';
$_['text_regen_note']            = 'Збережіть налаштування перед регенерацією';

// Shared type/mode options
$_['text_type_product']          = 'Товар';
$_['text_type_category']         = 'Категорія';
$_['text_type_manufacturer']     = 'Виробник';
$_['text_type_information']      = 'Сторінка';
$_['text_mode_empty']            = 'Тільки порожні';
$_['text_mode_all']              = 'Всі (перезаписати)';
$_['text_all_types']             = 'Всі типи';
$_['text_all_langs']             = 'Всі мови';
$_['text_all_levels']            = 'Всі рівні';
$_['text_loading']               = 'Завантаження...';

// Column labels
$_['column_from']                = 'Звідки';
$_['column_to']                  = 'Куди';
$_['column_code']                = 'Код';
$_['column_hits']                = 'Переходів';
$_['column_date']                = 'Дата';
$_['column_type']                = 'Тип';
$_['column_severity']            = 'Рівень';
$_['column_entity']              = 'Сутність';
$_['column_issue']               = 'Проблема';
$_['column_detail']              = 'Деталь';
$_['column_file']                = 'Файл';
$_['column_size']                = 'Розмір';
$_['column_field']               = 'Поле';
$_['column_count']               = 'Знайдено';
$_['column_status']              = 'Статус';

$_['status_new']                 = 'Нове';
$_['status_in_progress']         = 'В роботі';
$_['status_fixed']               = 'Виправлено';
$_['status_ignored']             = 'Ігнор';

$_['button_diff']                = 'Відмінності';
$_['button_close']               = 'Закрити';
$_['button_edit']                = 'Редагувати';
$_['text_diff_backup']           = 'з резервної копії';
$_['text_diff_current']          = 'в поточному файлі';
$_['text_no_diff']               = 'Резервна копія ідентична поточному файлу — відмінностей немає.';

// Audit issue short labels (used as badge text)
$_['issue_missing_title']          = 'meta_title';
$_['issue_missing_description']    = 'meta_description';
$_['issue_missing_seo_url']        = 'SEO URL';
$_['issue_title_too_short']        = 'meta_title короткий';
$_['issue_title_too_long']         = 'meta_title довгий';
$_['issue_title_equals_name']      = 'title = назва';
$_['issue_description_too_short']  = 'meta_description короткий';
$_['issue_description_too_long']   = 'meta_description довгий';
$_['issue_duplicate_title']        = 'Дублікат title';
$_['issue_duplicate_description']  = 'Дублікат description';
$_['issue_no_image']               = 'без зображення';
$_['issue_no_brand']               = 'без виробника';
$_['issue_no_body_description']    = 'без опису';
$_['issue_body_too_short']         = 'короткий опис';
$_['issue_short_content']          = 'мало контенту';
$_['issue_images_no_alt']          = 'img без alt';
$_['issue_no_category']            = 'без категорії';
$_['issue_empty_category']         = 'порожня категорія';
$_['issue_no_price']               = 'нульова ціна';
$_['issue_no_model']               = 'без моделі';
$_['issue_orphan_keyword']         = 'сирітський keyword';
$_['issue_duplicate_keyword']      = 'дублікат keyword';
$_['issue_keyword_too_long']       = 'URL довгий';
$_['issue_keyword_too_short']      = 'URL короткий';
$_['issue_uppercase_in_keyword']   = 'великі літери в URL';
$_['issue_special_chars_in_keyword'] = 'спецсимволи в URL';

// Redirects page
$_['text_from_uri']              = 'Звідки (URI)';
$_['text_to_url']                = 'Куди (URL або URI)';
$_['text_redirect_modal_title']  = 'Редирект';
$_['button_add']                 = 'Додати';

// Meta page
$_['text_bulk_fill']             = 'Масова генерація';
$_['text_meta_overrides']        = 'Ручні override';
$_['text_meta_overrides_hint']   = 'Тут зберігаються мета-теги, що ви вручну задали для конкретних товарів, категорій тощо. Ручний override має найвищий пріоритет — він завжди перекриває шаблон і рідне поле OpenCart.';
$_['text_meta_entity_hint']      = 'Почніть вводити назву — автопідказка запропонує сутність. ID підставиться автоматично.';
$_['label_entity_search']        = 'Сутність';
$_['text_entity_search_placeholder'] = 'Почніть вводити назву...';

// Meta template mode toggle
$_['label_meta_tpl_mode']        = 'Пріоритет шаблонів';
$_['text_meta_tpl_mode_override']= 'Перекривати OC (жорсткий режим)';
$_['text_meta_tpl_mode_fallback']= 'Тільки як резерв';
$_['text_meta_tpl_mode_hint']    = '<strong>«Перекривати OC»</strong> — шаблон завжди підставляється в HTML, навіть якщо у товара/категорії заповнене рідне meta_title/description. Зручно коли заповнюєте мета-теги лише через цей модуль.<br><strong>«Тільки як резерв»</strong> — якщо у OC-полі щось є, воно має пріоритет; шаблон спрацьовує лише для порожніх OC-полів. Зручно коли частину сторінок заповнюєте вручну в OC, а решту — через шаблон.<br>Ручні override (таблиця нижче) завжди мають найвищий пріоритет.';
$_['text_meta_modal_title']      = 'Мета-тег override';
$_['label_search_meta']          = 'Пошук за title...';
$_['button_bulk_start']          = 'Заповнити автоматично';
$_['label_category']             = 'Категорія';
$_['text_all_categories']        = 'Всі категорії';
$_['text_mask_hint_prefix']      = 'Маска URL:';
$_['text_title_hint']            = 'Рекомендовано до 60 символів';
$_['text_desc_hint']             = 'Рекомендовано до 160 символів';

// Audit page
$_['text_audit_run']             = 'Запуск аудиту';
$_['text_audit_results']         = 'Результати';
$_['text_audit_empty']           = 'Проблем не знайдено';
$_['text_selected']              = 'обрано';
$_['text_analyzing']             = 'Аналіз бази даних...';
$_['text_level_error']           = 'Помилки';
$_['text_level_warning']         = 'Попередження';
$_['text_level_info']            = 'Інформація';
$_['button_audit_run']           = 'Запустити аудит';
$_['text_per_page']              = 'На сторінку';

// Robots page
$_['text_robots_editor']         = 'Редактор robots.txt';
$_['text_robots_backups']        = 'Резервні копії';
$_['text_no_backups']            = 'Резервних копій немає';
$_['button_restore']             = 'Відновити';

// Sitemap page
$_['text_sitemap_status_title']  = 'Статус Sitemap';
$_['text_sitemap_actions']       = 'Дії';
$_['text_jetsitemap_installed']  = 'Sitemap Generator встановлено';
$_['text_jetsitemap_missing']    = 'Модуль OcKit Sitemap Generator не знайдено';
$_['text_no_sitemap_file']       = 'Файли sitemap відсутні';
$_['text_sitemap_open_settings'] = 'Налаштування Sitemap Generator';
$_['button_sitemap_generate']    = 'Оновити карту сайту';

// AbsURL page
$_['text_absurl_about_title']    = 'Що це за інструмент';
$_['text_absurl_about']         = 'В описах товарів і категорій можуть зберігатися абсолютні URL вигляду <code>&lt;img src="http://old-domain.com/..."&gt;</code> або <code>&lt;a href="http://old-domain.com/..."&gt;</code>. Це трапляється після зміни домену або переходу з HTTP на HTTPS. Інструмент сканує всі описи, знаходить URL зі старим доменом і замінює їх на нові — не зачіпаючи інший контент.';
$_['text_absurl_scan_title']     = 'Сканування абсолютних URL';
$_['text_absurl_replace_title']  = 'Заміна URL';
$_['text_absurl_log_title']      = 'Журнал змін';
$_['label_search_domain']        = 'Домен для пошуку';
$_['label_old_domain']           = 'Старий домен';
$_['label_new_domain']           = 'Новий домен';
$_['label_https_only']           = 'Тільки <code>http</code> → <code>https</code>';
$_['button_scan']                = 'Сканувати';
$_['button_replace_selected']    = 'Замінити вибрані';

// Dashboard page
$_['text_stat_seo_urls']         = 'SEO URL записів';
$_['text_stat_redirects']        = 'Активних редиректів';
$_['text_stat_audit_errors']     = 'SEO помилок';
$_['text_stat_audit_warnings']   = 'Попереджень аудиту';
$_['text_stat_redirect_hits']    = 'Переходів по редиректах';
$_['text_stat_chains']           = 'Ланцюгів редиректів';
$_['text_quick_actions']         = 'Швидкі дії';
$_['text_audit_issues_top']      = 'Топ SEO проблем';
$_['text_all_audit_results']     = 'Всі результати аудиту';
$_['text_top_redirects']         = 'Топ редиректів за переходами';
$_['text_chain_warning']         = 'Виявлено ланцюги редиректів';

// FAQ page
$_['text_faq_title']             = 'Часті запитання';

// Headers page
$_['text_headers_test']          = 'Тест правила';
$_['text_headers_rules']         = 'Правила заголовків';
$_['text_headers_about_title']   = 'Що це за інструмент';
$_['text_headers_about']         = 'Дозволяє керувати заголовками <code>X-Robots-Tag</code> та мета-тегом <code>robots</code> для конкретних URL без правки <code>robots.txt</code>. Наприклад, заборонити індексацію сторінок пошуку, фільтрів, кабінету — або тимчасово закрити розділ, що готується. Для кожного URI-шаблону (підтримуються wildcards <code>*</code>) ви вказуєте значення <code>robots</code>, вирішуєте чи відправляти HTTP-заголовок, чи вставляти <code>&lt;meta name="robots"&gt;</code> (або обидва). Тест правил нижче перевіряє, який саме запис спрацює для конкретного URI.';
$_['text_no_headers_rules']      = 'Правил заголовків ще немає. Натисніть «Додати правило», щоб створити перше.';
$_['button_add_rule']            = 'Додати правило';
$_['label_hdr_uri']              = 'URI';
$_['label_hdr_robots']           = 'Robots';
$_['label_hdr_sort_order']       = 'Порядок сортування';
$_['label_hdr_comment']          = 'Коментар';
$_['label_hdr_status']           = 'Активно';
$_['placeholder_hdr_uri']        = '/catalog/product/* або /admin/*';

// JS i18n (passed to window.scfI18n)
$_['js_saving']                  = 'Збереження...';
$_['js_saved']                   = 'Збережено!';
$_['js_error_save']              = 'Помилка збереження.';
$_['js_regen_done']              = 'Регенеровано:';
$_['js_regen_inserted']          = 'створено';
$_['js_regen_updated']           = 'оновлено';
$_['js_regen_skipped']           = 'пропущено';
$_['js_regen_confirm_all']       = 'Перезаписати ВСІ SEO URL для цього типу?';
$_['js_confirm_delete_redirect'] = 'Видалити цей редирект?';
$_['js_import_success']          = 'Імпортовано:';
$_['js_import_skipped']          = 'Пропущено:';
$_['js_error_redirect_fields']   = 'Заповніть поля Звідки і Куди.';
$_['js_confirm_delete_meta']     = 'Видалити цей override?';
$_['js_bulk_complete']           = 'Заповнення завершено:';
$_['js_bulk_filled']             = 'заповнено';
$_['js_bulk_skipped']            = 'пропущено';
$_['js_audit_running']           = 'Виконується аудит...';
$_['js_audit_done']              = 'Аудит завершено';
$_['js_audit_errors']            = 'помилок';
$_['js_audit_warnings']          = 'попереджень';
$_['js_audit_info']              = 'інфо';
$_['js_confirm_restore_robots']  = 'Відновити цю резервну копію? Поточний файл буде замінено.';
$_['js_robots_saved']            = 'robots.txt збережено';
$_['js_sm_generate_ok']          = 'Генерацію запущено у фоні';
$_['js_sm_generate_fail']        = 'Не вдалося запустити генерацію';
$_['js_sm_ping_ok']              = 'Google успішно повідомлено';
$_['js_sm_ping_fail']            = 'Ping не вдався';
$_['js_absurl_scan_found']       = 'знайдено входжень';
$_['js_absurl_replaced']         = 'Замінено рядків';
$_['js_confirm_replace_absurl']  = 'Замінити URL у вибраних записах?';
$_['js_confirm_flatten']         = 'Виправити всі ланцюги редиректів автоматично?';
$_['js_flatten_done']            = 'Виправлено ланцюгів:';

// Buttons
$_['button_save']                = 'Зберегти';
$_['button_cancel']              = 'Скасувати';
$_['button_delete']              = 'Видалити';
$_['button_flatten_chains']      = 'Виправити ланцюги';

// Google Search Console (Google tab)
$_['tab_google']                  = 'Google';
$_['text_section_gsc']            = 'Google Search Console';
$_['text_section_gsc_stats']      = 'Пошукова аналітика';
$_['text_section_gsc_sitemaps']   = 'Sitemap-и в GSC';
$_['text_gsc_about']              = 'Інтеграція з Search Console + Indexing API через OAuth2. Дозволяє: переглядати search-аналітику (запити, кліки, CTR, позиції), керувати sitemap-ами, інспектувати індексацію URL і миттєво нотифікувати Google про оновлення/видалення сторінок (Indexing API).';
$_['text_gsc_redirect_hint']      = 'Скопіюй цей URL у Google Cloud Console → Credentials → OAuth client → Authorized redirect URIs.';
$_['text_gsc_site_property_hint'] = 'Точне значення з GSC → Settings → Property. Для URL-prefix — з кінцевим слешем; для Domain property — формат <code>sc-domain:example.com</code>.';
$_['text_gsc_connect_hint']       = 'Спочатку збережи Client ID/Secret кнопкою «Зберегти», потім натисни «Підключити Google».';
$_['text_gsc_not_loaded']         = 'Натисни «Завантажити», щоб отримати дані за останні 28 днів.';
$_['text_gsc_connected']          = 'Підключено';
$_['text_gsc_disconnected']       = 'Не підключено';
$_['text_gsc_confirm_disconnect'] = 'Відключити Google і видалити збережений токен?';
$_['text_gsc_submitted']          = 'URL надіслано в Google';
$_['text_no_data']                = 'Немає даних';
$_['text_confirm_delete']         = 'Видалити?';
$_['label_gsc_redirect']          = 'Redirect URI';
$_['label_gsc_site_property']     = 'Site property';
$_['label_gsc_status']            = 'Підключення';
$_['button_gsc_connect']          = 'Підключити Google';
$_['button_gsc_disconnect']       = 'Відключити';
$_['button_load']                 = 'Завантажити';
$_['button_submit']               = 'Надіслати';
$_['gsc_col_key']                 = 'Запит / сторінка';
$_['gsc_col_clicks']              = 'Кліки';
$_['gsc_col_impressions']         = 'Покази';
$_['gsc_col_ctr']                 = 'CTR';
$_['gsc_col_position']            = 'Позиція';
$_['gsc_col_path']                = 'Шлях';
$_['gsc_col_last_submitted']      = 'Останній submit';
$_['gsc_col_errors']              = 'Помилок';
$_['gsc_col_warnings']            = 'Попереджень';
$_['gsc_dim_query']               = 'Запит';
$_['gsc_dim_page']                = 'Сторінка';
$_['gsc_dim_country']             = 'Країна';
$_['gsc_dim_device']              = 'Пристрій';


// Preview / Apply (bulk operations) + cache labels
$_['button_preview']             = 'Передогляд';
$_['button_apply']                = 'Застосувати';
$_['js_running_preview']         = 'Шукаю…';
$_['js_running_apply']           = 'Застосовую…';
$_['js_matched_preview']         = 'Знайдено: ';
$_['js_matched_apply']           = 'Оновлено: ';
$_['js_confirm_bulk_apply']      = 'Застосувати заміну? Дію не можна скасувати без бекапу БД (історичні записи створюються автоматично).';
$_['cache_warm']          = 'згенеровано';
$_['cache_cold']                 = 'холодний';
$_['cache_entries']              = 'записів';
$_['cache_kb']                   = 'КБ';

// Schema Data Providers — explainer (rewritten in formal "Ви" form)
$_['text_schema_providers_about']      = 'Data Provider — це власний PHP-клас, який <b>підтягує додаткові дані</b> для Schema.org-розмітки (наприклад: залишок на складі, ціни оптом, рейтинги з зовнішнього сервісу) і робить ці дані доступними у шаблонах правил Schema через підстановки <code>{{var.path}}</code>. Якщо вам вистачає стандартних полів (Product, Offer, Organization тощо) — провайдери не потрібні.';
$_['text_schema_providers_howto_title']= 'Як це працює — короткий приклад';
$_['text_schema_providers_step_1']     = '<b>1.</b> Створіть PHP-клас, який реалізує інтерфейс <code>OcKit\\SeoCore\\Libs\\SchemaDataProviderInterface</code>. Клас повинен бути autoloadable (наприклад, лежати в окремому OCMOD-модулі з власним <code>$autoload</code>). Метод <code>getData()</code> повертає масив, який стане доступним у шаблоні Schema:';
$_['text_schema_providers_step_2']     = '<b>2.</b> Зареєструйте FQCN класу в полі нижче (через кому, крапку з комою або новий рядок). Тепер у будь-якому шаблоні Schema (вкладка Schema → Custom Rules) ви можете використовувати ці змінні:';
$_['text_schema_providers_hint']       = 'Перелічуйте повні FQCN-и (з namespace) через кому або новий рядок. Якщо вам не потрібен власний код — лишіть поле порожнім, стандартні Schema-типи (Product, BreadcrumbList, Organization, WebSite, Article) працюють і так.';

// GSC how-to (inline OAuth setup guide)
$_['text_gsc_howto_title'] = 'Як отримати Client ID / Client Secret';
$_['text_gsc_howto_1']     = 'Зайдіть у <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> і створіть новий проект (або оберіть існуючий).';
$_['text_gsc_howto_2']     = 'У меню зліва: <b>APIs &amp; Services → Library</b>. Увімкніть два API: <code>Google Search Console API</code> і <code>Web Search Indexing API</code> (натисніть Enable на кожному).';
$_['text_gsc_howto_3']     = '<b>APIs &amp; Services → OAuth consent screen</b>. Тип <i>External</i>, заповніть назву додатку, support email, developer email. На кроці Scopes — пропустіть. На кроці Test users — додайте свою Google-пошту, яка має доступ до GSC властивості. Збережіть.';
$_['text_gsc_howto_4']     = '<b>APIs &amp; Services → Credentials → Create Credentials → OAuth client ID</b>. Тип <i>Web application</i>. У поле <b>Authorized redirect URIs</b> вставте URL, який ви бачите в полі <b>Redirect URI</b> нижче — точна копія, без змін.';
$_['text_gsc_howto_5']     = 'Натисніть Create — Google покаже <b>Client ID</b> і <b>Client Secret</b>. Скопіюйте обидва в поля нижче.';
$_['text_gsc_howto_6']     = 'У поле <b>Site property</b> вставте точне значення вашої властивості з GSC (для URL-prefix — з кінцевим слешем; для Domain property — у форматі <code>sc-domain:example.com</code>). Натисніть <b>Зберегти</b>, потім <b>Підключити Google</b>.';

// Sitemap ping (modernized)
$_['text_sm_ping_google_hint']        = 'Submit sitemap до Google Search Console через API (потрібно підключити OAuth у вкладці Google).';
$_['text_sm_ping_bing_hint']          = 'Bing sitemap ping вимкнено в 2022. Використовуйте Bing Webmaster Tools або IndexNow.';
$_['text_sm_ping_google_ok']          = 'Sitemap надіслано в Google Search Console.';
$_['text_sm_ping_google_need_oauth']  = 'Спочатку підключіть Google у вкладці «Google» (OAuth). Старий /ping endpoint Google вимкнено в 2023.';
$_['text_sm_ping_bing_deprecated']    = 'Bing /ping endpoint вимкнено в 2022. Користуйтесь Bing Webmaster Tools або протоколом IndexNow.';

// Inline-text localization (removed from twig)
$_['title_store_selector']        = 'Магазин (для багатомагазинних установок)';
$_['placeholder_optional']        = '(опційно)';
$_['js_error']                    = 'Помилка';
$_['text_org_type_organization']  = 'Organization (універсальний)';
$_['text_org_type_online_store']  = 'OnlineStore (онлайн-магазин)';
$_['text_org_type_store']         = 'Store (роздрібний магазин)';
$_['text_org_type_local_business']= 'LocalBusiness (локальний бізнес)';
$_['text_org_type_restaurant']    = 'Restaurant';
$_['ph_address_street']           = 'вул. Хрещатик, 1';
$_['ph_address_city']             = 'Київ';
$_['ph_address_region']           = 'Київська обл.';
$_['ph_price_range']              = '$$ або 100-500 UAH';
$_['ph_founding_date']            = '2015 або 2015-03-01';
$_['ph_founders']                 = "Іван Петренко\nОлена Сидоренко";
$_['text_schema_placeholders_hint']= 'Підстановки: <code>{{product.name}}</code>, <code>{{category.name}}</code>, <code>{{get.param}}</code>, <code>{{config.store_name}}</code>, <code>{{page.url}}</code>, <code>{{page.title}}</code>. Блоки: <code>{{#each items}}…{{/each}}</code>, <code>{{#if cond}}…{{/if}}</code>.';
$_['text_meta_mode_override']     = 'template → HTML (завжди)';
$_['text_meta_mode_fallback']     = 'OC field → HTML, template → резерв';
$_['text_canonical_pagination_first_ex']= 'canonical → /sukni';
$_['text_canonical_pagination_self_ex'] = 'canonical → /sukni?page=2';
$_['text_canonical_filters_base_ex']    = 'canonical → base';
$_['text_canonical_filters_self_ex']    = 'canonical → self';

$_['text_image_alt_about']        = 'Знаходить <code>&lt;img&gt;</code> теги <strong>всередині HTML-опису товару</strong> (поле <code>oc_product_description.description</code>, яке редагується в WYSIWYG-редакторі) і додає <code>alt="назва товару"</code> до тих, де alt відсутній. Існуючі alt-атрибути НЕ перезаписуються.<br><br><strong>Що НЕ робить:</strong> не зачіпає галерею (thumb/oc_product_image) — це контролюється шаблоном теми. Не зачіпає опис категорій / статей / виробників.<br><br><strong>Preview</strong>: симулює — повертає скільки записів та <code>&lt;img&gt;</code>-тегів буде зачеплено, БЕЗ запису в БД.<br><strong>Apply</strong>: реально UPDATE-ить <code>product_description.description</code>.';

$_['js_url_not_set']         = 'URL не визначений';
$_['js_running']             = 'Виконую…';
$_['js_preview_label']       = 'Preview: ';
$_['js_done_label']          = 'Готово: ';
$_['js_products_label']      = 'товарів ';
$_['js_alts_added']          = ', alt додано ';
$_['js_confirm_img_alt']     = 'Запустити масове заповнення alt для всіх продуктів? Дію не можна скасувати без бекапу БД.';
$_['js_broken_none']         = 'Битих посилань не знайдено';
$_['js_loading']             = 'Завантаження…';
$_['js_records_label']       = 'Записів: ';

// Missing keys after |default strip — restored
$_['tab_home']                       = 'Головна сторінка';
$_['label_home_redirect_index']      = 'Прибрати <code>/index.php?route=common/home</code>';
$_['label_schema_org_contact_languages']= 'Мови підтримки';
$_['label_schema_org_country']       = 'Країна (ISO-код)';
$_['label_schema_org_founders']      = 'Засновники';
$_['label_schema_org_founding_date'] = 'Дата заснування';
$_['label_schema_org_geo']           = 'Latitude / Longitude';
$_['label_schema_org_locality']      = 'Місто';
$_['label_schema_org_opening_hours'] = 'Години роботи';
$_['label_schema_org_postal_code']   = 'Поштовий індекс';
$_['label_schema_org_price_range']   = 'Цінова категорія (priceRange)';
$_['label_schema_org_region']        = 'Область / штат';
$_['label_schema_org_same_as']       = 'sameAs (соцмережі)';
$_['label_schema_org_street']        = 'Вулиця, будинок';
$_['label_schema_org_type']          = 'Тип організації';
$_['label_schema_org_vat_id']        = 'vatID / Tax ID';
$_['label_schema_providers']         = 'Provider classes';
$_['label_strip_query_params']       = 'Видаляти query-параметри з canonical';
$_['label_trailing_slash_all']       = 'Всі URL';
$_['label_trailing_slash_categories']= 'Тільки категорії';
$_['label_trailing_slash_off']       = 'Без слешу';
$_['label_trailing_slash_products']  = 'Тільки товари';
$_['label_webhook_secret']           = 'Shared secret';
$_['label_webhook_url']              = 'Endpoint URL';
$_['text_ab_test_enabled']           = 'Увімкнено';
$_['text_ab_test_enabled_hint']      = 'Увімкнути ротацію title-варіантів на каталозі';
$_['text_ab_test_help']              = 'Створіть два варіанти title для сутності — модуль показує A або B випадково (стабільно для відвідувача через cookie). Лічильники показів допомагають визначити переможця за CTR з Search Console.';
$_['text_ab_test_new']               = 'Новий A/B тест title';
$_['text_ab_test_title']             = 'A/B тест title';
$_['text_broken_links']              = 'Биті посилання';
$_['text_broken_links_about']        = 'Сканер шукає <code>&lt;a href&gt;</code> у описах товарів/категорій/виробників/інформаційних сторінок і HEAD-пінгує кожен унікальний URL. Результати зберігаються в БД, повторний скан — перезаписує.';
$_['text_hints_examples']            = 'Приклади: rel=dns-prefetch href=//fonts.gstatic.com | rel=preconnect href=https://cdn.example.com | rel=preload href=/font.woff2 as=font type=font/woff2 crossorigin=anonymous';
$_['text_home_about']                = 'Мета-теги для головної сторінки. Заповніть для кожної мови — порожні поля заміняються значеннями за замовчуванням магазину.';
$_['text_home_redirect_index_hint']  = 'Коли ввімкнено: усі внутрішні посилання на головну генеруються як <code>/</code> або <code>/{prefix}/</code> без <code>/index.php?route=common/home</code>';
$_['text_image_alt_tools']           = 'Image alt — масове заповнення';
$_['text_redirect_expires']          = 'Дата закінчення';
$_['text_redirect_expires_hint']     = 'Опційно: після цієї дати редирект автоматично припиняє діяти (можна видалити крон-задачею).';
$_['text_resource_hints_about']      = 'Додає теги &lt;link rel="preload"&gt; / &lt;link rel="dns-prefetch"&gt; / &lt;link rel="preconnect"&gt; в &lt;head&gt; усіх сторінок. Корисно для прискорення завантаження критичних ресурсів (шрифтів, ключових скриптів) та DNS-резолву зовнішніх доменів.';
$_['text_robots_quick_block_index']  = 'Заблокувати ?route=*';
$_['text_robots_quick_default']      = 'Стандартний шаблон';
$_['text_robots_quick_sitemap']      = 'Додати Sitemap URL';
$_['text_schema_org_address']        = 'Адреса (PostalAddress)';
$_['text_schema_org_contact_languages_hint']= 'Список мов, якими відповідає ваша служба підтримки. Через кому або з нового рядка.';
$_['text_schema_org_country_hint']   = 'Двобуквений код ISO 3166-1 alpha-2: UA, PL, US тощо.';
$_['text_schema_org_founders_hint']  = 'Імена засновників, по одному на рядок.';
$_['text_schema_org_founding_date_hint']= 'Рік або повна дата у форматі ISO 8601 (YYYY-MM-DD).';
$_['text_schema_org_geo']            = 'Геокоординати + години';
$_['text_schema_org_geo_hint']       = 'Координати потрібні для LocalBusiness/Restaurant. Можна скопіювати з Google Maps (правий клік → Координати).';
$_['text_schema_org_meta']           = 'Профілі, реквізити, засновники';
$_['text_schema_org_opening_hours_hint']= 'Один рядок на період. Дні: Mo, Tu, We, Th, Fr, Sa, Su (можна діапазон Mo-Fr). Час у форматі HH:MM-HH:MM. Слово <code>closed</code> або просто пропустіть рядок для вихідних.';
$_['text_schema_org_price_range_hint']= 'Лише для LocalBusiness/Store/Restaurant/OnlineStore. Допустимо $-$$$$ або діапазон у валюті.';
$_['text_schema_org_same_as_hint']   = 'URL ваших офіційних профілів (один на рядок). Google використовує для зв\'язування знань про бренд.';
$_['text_schema_org_type_hint']      = 'Для LocalBusiness/Store/Restaurant Google вимагає адресу і години роботи. Для онлайн-магазину обирайте OnlineStore або Organization.';
$_['text_schema_providers']          = 'Data Providers';
$_['text_section_resource_hints']    = 'Preload / DNS prefetch';
$_['text_section_webhook']           = 'Webhook';
$_['text_stat_seo_score']            = 'SEO Score';
$_['text_stat_seo_score_hint']       = 'Узагальнений 0–100 бал на основі останнього аудиту: 100 - (errors*30 + warnings*10 + info*2) / N сутностей';
$_['text_strip_query_params_hint']   = 'Список параметрів, які треба викидати з канонічного URL при 301-редіректі (трекінг-мітки). Через кому або з нового рядка. Підтримується wildcard в кінці: <code>utm_*</code> покриє <code>utm_source</code>, <code>utm_medium</code> тощо. Інші GET-параметри (page, ajax, sort, filter…) зберігаються автоматично.';
$_['text_url_bulk_find']             = 'Знайти';
$_['text_url_bulk_regex']            = 'PCRE-режим';
$_['text_url_bulk_replace']          = 'Масовий пошук/заміна в keyword';
$_['text_url_bulk_replace_about']    = 'Знайти підрядок (або PCRE-шаблон) і замінити його у всіх SEO URL keyword. Preview — без запису. Apply — оновлює DB, додає історичні записи та auto-redirect 301 для зміненого слага.';
$_['text_url_bulk_replace_with']     = 'Замінити на';
$_['text_url_history']               = 'Історія змін URL';
$_['text_webhook_about']             = 'HTTP POST до зовнішнього URL при зміні SEO URL/редіректів. Корисно для CDN purge, Slack-нотіфікацій, ETL pipeline. Payload: { event, timestamp, payload }. Підпис у заголовку X-SCF-Signature: sha256=&lt;HMAC&gt;.';
$_['text_webhook_secret_hint']       = 'Якщо задано — кожен POST матиме заголовок X-SCF-Signature: sha256=HMAC(body, secret) для верифікації на стороні приймача.';

// Schema org type groups (optgroup labels)
$_['text_org_group_general']  = 'Загальні';
$_['text_org_group_local']    = 'Магазини (LocalBusiness)';
$_['text_org_group_food']     = 'Їжа й напої';
$_['text_org_group_services'] = 'Послуги';
$_['text_org_group_auto_home']= 'Авто та будівництво';
$_['text_org_group_lodging']  = 'Готелі та оренда';
$_['text_org_group_other']    = 'Освіта, спорт, інше';
$_['button_send']           = 'Відправити';

// Allow same seo_url across languages of one entity
$_['label_allow_duplicate_keywords'] = 'Однакові SEO URL для різних мов';
$_['text_allow_duplicate_keywords_hint'] = 'За замовчуванням OpenCart 3 забороняє ввести однаковий <code>keyword</code> для різних мов одного товару/категорії/виробника/інфо-сторінки (помилка «має бути унікальним»). Коли увімкнено — цей крос-мовний чек обходиться, і ви можете писати один slug у всі мовні поля одночасно. Перевірка крос-сутність (один slug на різні товари) залишається в силі.';
$_['cache_warmed']        = 'Кеш згенеровано:';

// Blog meta types
$_['text_type_article']         = 'Блог-пост';
$_['text_type_blog_category']   = 'Категорія блогу';
$_['text_meta_vars_article']    = 'Доступні змінні: <code>{name}</code> — назва статті, <code>{description}</code> — 160 символів змісту, <code>{store_name}</code> — назва магазину, <code>{year}</code> — поточний рік, <code>{page}</code> — № сторінки (порожнє на page 1).<br>Умовні блоки: <code>{{#if page}} — сторінка {page}{{/if}}</code>.';
$_['text_meta_vars_blog_category'] = 'Доступні змінні: <code>{name}</code> — назва, <code>{description}</code> — 160 символів опису, <code>{count}</code> — кількість постів, <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>.';

// Route-level meta (custom routes, manufacturer list, blog index)
$_['text_section_route_meta']    = 'Meta для кастомних роутів';
$_['text_route_meta_about']      = '<strong>Що це таке?</strong> Сторінки без сутності (список виробників <code>product/manufacturer</code>, головна блогу <code>blog/menu</code>, треті модулі <code>vendor/vendor</code> тощо) не мають entity-meta. Тут ви задаєте title/description/keywords <strong>по роуту</strong>, окремо для кожної мови. Підтримуються wildcard-маски (<code>vendor/*</code>) та змінні <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code>, умовні блоки <code>{{#if page}}…{{/if}}</code>.';
$_['text_route_meta_route_hint'] = 'Точний роут (<code>product/manufacturer</code>) або маска з wildcard-ом (<code>vendor/*</code>). Якщо для сторінки вже працює entity-meta (товар/категорія) — route-meta не перебиває його, працює як fallback.';
$_['text_route_meta_vars_hint']  = 'Доступні змінні: <code>{store_name}</code>, <code>{year}</code>, <code>{page}</code> (порожнє на page 1). Умовні блоки: <code>{{#if page}} — стор. {page}{{/if}}</code>.';
$_['text_route_meta_modal_title']= 'Meta для роуту';
$_['button_add_manufacturer_list']= '+ Список виробників';
$_['button_add_blog_index']       = '+ Блог';

// Product image alt/title masks
$_['text_section_image_masks']    = 'Маски alt/title для зображень товару';
$_['text_image_masks_about']      = '<strong>Що це таке?</strong> Title і alt для всіх зображень галереї товару генеруються автоматично з маски — окремо для кожної мови. Зручно для SEO та accessibility, не треба заповнювати руками для кожного товару.';
$_['text_image_masks_vars']       = 'Змінні: <code>{name}</code> — назва, <code>{category}</code> — головна категорія, <code>{price}</code> — ціна, <code>{special}</code> — акційна ціна (порожнє якщо нема акції), <code>{sort_order}</code> — порядковий номер (1, 2, 3…), <code>{sku}</code>, <code>{model}</code>, <code>{manufacturer}</code> / <code>{brand}</code>, <code>{store_name}</code>, <code>{year}</code>.<br>Умовні блоки: <code>{{#if special}} акція {special}{{/if}}</code> — рендериться лише якщо змінна непорожня. <strong>Тема магазину</strong> має використовувати <code>image.alt</code> / <code>image.title</code> у тегу <code>&lt;img&gt;</code> (зазвичай уже так і є).';
$_['label_image_alt_tpl']         = 'Шаблон alt';
$_['label_image_title_tpl']       = 'Шаблон title';
