<?php
/**
 * Products Scraper Pro — Language: uk-ua
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// ── Heading ───────────────────────────────────────────────────────────────────
$_['heading_title'] = 'oc-kit.com — Products Scraper Pro';

// ── Common ────────────────────────────────────────────────────────────────────
$_['text_success']      = 'Зміни збережено.';
$_['text_edit']         = 'Редагувати';
$_['text_enabled']      = 'Активний';
$_['text_disabled']     = 'Вимкнено';
$_['text_approved']     = 'Схвалено та застосовано.';
$_['text_rejected']     = 'Відхилено.';
$_['text_no_jobs']      = 'Задач не знайдено.';
$_['text_no_donors']    = 'Донори не налаштовано.';
$_['text_no_entries']   = 'Записів не знайдено.';
$_['text_confirm_delete']    = 'Видалити запис?';
$_['text_confirm_reject']    = 'Відхилити дані та пропустити цю задачу?';
$_['text_confirm_blacklist'] = 'Додати до чорного списку:';

// ── Tabs ──────────────────────────────────────────────────────────────────────
$_['tab_settings']      = 'Налаштування';
$_['tab_products']      = 'Товари';
$_['tab_moderation']    = 'Модерація';
$_['tab_donors']        = 'Донори';
$_['tab_log']           = 'Журнал';
$_['tab_general']       = 'Загальні';
$_['tab_google']        = 'Google Search';
$_['tab_ai']            = 'AI провайдер';
$_['tab_translation']   = 'Переклад';
$_['tab_attributes']    = 'Атрибути';
$_['tab_notifications'] = 'Сповіщення';
$_['tab_advanced']      = 'Додатково';
$_['tab_integrations']  = 'Інтеграції';
$_['tab_stats']         = 'Статистика';
$_['tab_faq']           = 'FAQ';
$_['tab_license']       = 'Ліцензія';

// ── Settings labels ───────────────────────────────────────────────────────────
$_['label_status']                   = 'Статус модуля';
$_['label_moderation_mode']          = 'Режим модерації';
$_['label_default_language']         = 'Мова скрапінгу';
$_['label_attribute_group']          = 'Група атрибутів (за замовч.)';
$_['label_fields_to_fill']           = 'Поля для заповнення';
$_['label_field_modes']              = 'Поля та режим запису';
$_['help_field_modes']               = 'Для кожного поля: <strong>Неактивне</strong> — не чіпати; <strong>Якщо порожнє</strong> — заповнювати лише коли значення відсутнє; <strong>Перезаписувати</strong> — завжди замінювати наявне значення зіскрапленим.';
$_['column_field']                   = 'Поле';
$_['text_mode_off']                  = 'Неактивне';
$_['text_mode_fill']                 = 'Якщо порожнє';
$_['text_mode_overwrite']            = 'Перезаписувати';
$_['label_overwrite_existing']       = 'Перезаписувати існуючі поля';
$_['label_search_provider']          = 'Пошуковий провайдер';
$_['label_search_results_count']        = 'Результатів пошуку';
$_['label_search_query_template']       = 'Шаблон пошукового запиту';
// AI token limits & cost
$_['label_auto_fill_selectors']    = 'Авто-заповнення CSS-селекторів';
$_['help_auto_fill_selectors']     = 'Після успішного AI-скраппінгу автоматично заповнювати порожні поля <code>description_selector</code>, <code>attributes_selector</code>, <code>image_selector</code> у донорі. Ручні значення не перезаписуються.';
$_['label_token_limit_daily']      = 'Ліміт токенів (доба / місяць)';
$_['help_token_limits']             = 'Денний та місячний ліміт. <code>0</code> = без обмежень. При перевищенні застосовується дія з налаштування "При перевищенні ліміту".';
$_['label_on_limit_exceeded']       = 'При перевищенні ліміту';
$_['help_on_limit_exceeded']        = 'Як реагувати, коли AI-провайдер вичерпав денний або місячний ліміт токенів.';
$_['text_on_limit_error']           = 'Помилка (помітити job як error)';
$_['text_on_limit_skip']            = 'Пропустити (job у статусі skipped)';
$_['text_on_limit_fallback']        = 'Перемкнутись на резервного провайдера';
$_['label_fallback_provider']       = 'Резервний провайдер';
$_['text_token_usage_title']        = 'Використання токенів та витрати';
$_['text_col_provider']             = 'Провайдер';
$_['text_col_today']                = 'Сьогодні';
$_['text_col_month']                = 'Цей місяць';
$_['text_col_limits']               = 'Ліміти (день / міс)';
$_['text_col_used_pct']             = 'Використано (день / міс)';
$_['text_requests']                 = 'запитів';

$_['help_search_query_template']        = 'Стандартні маски: <code>{name}</code>, <code>{model}</code>, <code>{manufacturer}</code>, <code>{sku}</code>, <code>{upc}</code>, <code>{ean}</code>, <code>{jan}</code>, <code>{isbn}</code>, <code>{mpn}</code>. Для підстановки значення атрибута товару використовуйте <code>{attribute_ID}</code>, де ID — числовий ідентифікатор атрибута в БД (напр. <code>{attribute_42}</code>). Будь-яка інша невідома маска розглядається як колонка <code>oc_product</code>. <strong>Умовні блоки:</strong> <code>{if VAR}…{endif}</code> та <code>{if VAR}…{else}…{endif}</code>. Напр. <code>{name}{if manufacturer} Бренд: {manufacturer}{endif}</code> → "Air Max Бренд: Nike" або "Air Max" коли бренд порожній. Вкладені блоки не підтримуються. Залиште порожнім для дефолту: <code>{name} {model}</code>.';
$_['placeholder_search_query_template'] = '{name} {model}';
$_['label_search_gl']                = 'Країна пошуку (geo)';
$_['label_search_hl']                = 'Мова пошуку';
$_['help_search_gl']                 = 'Код країни ISO 3166-1 alpha-2 у нижньому регістрі. Напр. <code>us</code>, <code>gb</code>, <code>ua</code>, <code>de</code>. Залиште порожнім, щоб використовувати дефолт провайдера.';
$_['placeholder_search_gl']          = 'ua';
$_['placeholder_search_hl']          = 'uk';
$_['label_serper_api_key']           = 'Serper API Key';
$_['label_brave_api_key']            = 'Brave Search API Key';
$_['label_bing_api_key']             = 'Bing Web Search API Key';
$_['label_google_api_key']           = 'Google API Key';
$_['label_google_cx']                = 'Custom Search Engine ID';
$_['label_ai_provider']              = 'AI провайдер';
$_['label_openai_api_key']           = 'OpenAI API Key';
$_['label_openai_model']             = 'OpenAI модель';
$_['label_deepseek_api_key']         = 'DeepSeek API Key';
$_['label_deepseek_model']           = 'DeepSeek модель';
$_['label_claude_api_key']           = 'Claude API Key';
$_['label_claude_model']             = 'Claude модель';
$_['label_gemini_api_key']           = 'Gemini API Key';
$_['label_gemini_model']             = 'Gemini модель';
$_['label_fetch_timeout']            = 'Таймаут запиту';
$_['label_fetch_throttle_ms']        = 'Затримка між запитами (мс)';
$_['label_max_html_length']          = 'Макс. довжина контенту';
$_['label_min_content_length']       = 'Мін. довжина контенту (SPA-skip)';
$_['help_min_content_length']        = 'Пропускати URL, якщо очищений контент коротший за вказану кількість символів (наприклад, JS-рендеровані / SPA-сайти, що повертають порожню HTML-оболонку). 0 — вимкнено.';
$_['unit_seconds']                   = 'секунд';
$_['unit_ms']                        = 'мілісекунд';
$_['unit_chars']                     = 'символів';
$_['label_use_markdown']             = 'Конвертувати HTML → Markdown';
$_['label_first_donor_html']         = 'HTML для нових донорів (для виявлення селекторів)';
$_['help_first_donor_html']          = 'Якщо домен зустрічається вперше і не має збережених CSS-селекторів — відправляти в AI сирий HTML замість Markdown. Це дозволяє AI коректно визначити селектори для авто-донора. Наступні скрапінги цього донора вже використовуватимуть Markdown.';
$_['label_moderation_hide_inactive'] = 'Приховувати неактивні поля у попапі перегляду';
$_['help_moderation_hide_inactive']  = 'При перегляді даних у розділі Модерація — приховувати поля, які не відмічені в «Поля для заповнення». Дані все одно зберігаються в job, лише не відображаються.';
$_['label_cron_batch_size']          = 'Задач за один запуск';
$_['label_auto_enrich_new_products'] = 'Авто-скрапінг нових товарів';
$_['label_auto_enrich_days']         = 'Товари за останні N днів';
$_['label_auto_translate']           = 'Авто-переклад (Translater Pro)';
$_['label_translate_languages']      = 'Мови для перекладу';
$_['label_telegram_notify']          = 'Сповіщення Telegram';
$_['label_telegram_bot_token']       = 'Bot Token';
$_['label_telegram_chat_id']         = 'ID користувача';
$_['label_cron_command']             = 'Команда крону';
$_['label_editor_pro_enabled']       = 'Кнопка в Editor Pro';
$_['help_editor_pro_enabled']        = 'Показувати кнопку «Скрапити» в модулі Editor Pro для кожного товару.';
$_['help_editor_pro_tip']            = 'З модулем <strong>Editor Pro</strong> кнопка «Скрапити» з\'являється прямо у візуальному редакторі — скрапінг можна запускати, не виходячи зі сторінки товару. Без Editor Pro запуск доступний лише зі списку Products Scraper.';
$_['help_editor_pro_unavailable']    = 'Editor Pro не встановлено. Інтеграція недоступна.';

// ── Navigation text keys (aliases for breadcrumb/page titles) ────────────────
$_['text_products']     = 'Товари';
$_['text_moderation']   = 'Модерація';
$_['text_donors']       = 'Донори';
$_['text_blacklist']    = 'Чорний список';
$_['text_log']          = 'Журнал';
$_['text_stats']        = 'Статистика';

// ── Field labels (fields_to_fill checkboxes) ──────────────────────────────────
$_['text_field_description'] = 'Опис';
$_['text_field_meta_title']  = 'Meta Title';
$_['text_field_meta_desc']   = 'Meta Description';
$_['text_field_meta_kw']     = 'Meta Keywords';
$_['text_field_tag']         = 'Теги';
$_['text_field_attributes']  = 'Характеристики';
$_['text_field_upc']         = 'UPC';
$_['text_field_ean']         = 'EAN';
$_['text_field_jan']         = 'JAN';
$_['text_field_mpn']         = 'MPN';
$_['text_field_images']      = 'Зображення';

// ── AI provider names ─────────────────────────────────────────────────────────
$_['text_provider_serper']   = 'Serper.dev (рекомендовано, 2 500 безкоштовно)';
$_['text_provider_brave']    = 'Brave Search (2 000 безкоштовно/місяць)';
$_['text_provider_bing']     = 'Bing Web Search (1 000 безкоштовно/місяць)';
$_['text_provider_google']   = 'Google Custom Search (legacy)';
$_['text_provider_openai']   = 'OpenAI';
$_['text_provider_deepseek'] = 'DeepSeek';
$_['text_provider_claude']   = 'Claude (Anthropic)';
$_['text_provider_gemini']   = 'Google Gemini';

// ── Table columns ─────────────────────────────────────────────────────────────
$_['column_job_id']    = 'ID';
$_['column_product']   = 'Товар';
$_['column_name']      = 'Назва';
$_['column_url']       = 'URL донора';
$_['column_model']     = 'Модель';
$_['column_status']    = 'Статус';
$_['column_priority']  = 'Пріоритет';
$_['priority_normal']  = 'Низький';
$_['priority_high']    = 'Звичайний';
$_['priority_urgent']  = 'Високий';
$_['column_source_url']= 'Джерело';
$_['column_ai_provider'] = 'AI';
$_['column_created']   = 'Створено';
$_['column_updated']   = 'Оновлено';
$_['column_actions']   = 'Дії';
$_['column_domain']    = 'Домен';
$_['column_reason']    = 'Причина';
$_['column_skip_ai']   = 'Режим';
$_['column_selectors'] = 'CSS-селектори';
$_['column_level']     = 'Рівень';
$_['column_message']   = 'Повідомлення';

// ── Statuses ──────────────────────────────────────────────────────────────────
$_['status_pending']    = 'Очікує';
$_['status_running']    = 'Виконується';
$_['status_done']       = 'Готово';
$_['status_error']      = 'Помилка';
$_['status_moderation'] = 'Модерація';
$_['status_skipped']    = 'Пропущено';

// ── Buttons ───────────────────────────────────────────────────────────────────
$_['text_copied']             = 'Скопійовано!';
$_['button_save']             = 'Зберегти';
$_['button_add_job']          = 'Додати задачу';
$_['button_enrich']           = 'Скрапити';
$_['button_set_url']          = 'Вказати URL';
$_['button_enrich_selected']  = 'Скрапити вибрані';
$_['button_urgent_selected']  = 'Терміново';
$_['button_clear_queue']      = 'Скасувати чергу';
$_['button_run_now']          = 'Запустити';
$_['text_running_jobs']       = 'Виконується {current} з {total}…';
$_['text_run_complete']       = 'Готово: {done}, Модерація: {moderation}, Помилки: {errors}';
$_['button_retry']            = 'Повторити';
$_['button_skip']             = 'Пропустити';
$_['button_approve']           = 'Схвалити';
$_['button_approve_translate'] = 'Схвалити і перекласти';
$_['button_approve_all']      = 'Схвалити всі';
$_['button_reject']           = 'Відхилити';
$_['button_view_log']         = 'Лог';
$_['button_view_data']        = 'Переглянути дані';
$_['tab_edit_data']           = 'Редагування';
$_['tab_preview_card']        = 'Попередній перегляд';
$_['button_bulk_approve']     = 'Схвалити';
$_['button_bulk_approve_translate'] = 'Схвалити і перекласти';
$_['button_bulk_reject']      = 'Відхилити';
$_['button_reject_and_block']  = 'Відхилити і заблокувати';
$_['text_confirm_reject_block'] = 'Відхилити та додати донора в чорний список?';
$_['text_blocked_via_moderation'] = 'Заблоковано через модерацію';
$_['text_blacklisted']            = 'Додано до чорного списку';

$_['help_notifications_setup_title'] = 'Як налаштувати Telegram-сповіщення';
$_['help_notifications_step1']       = 'Створіть бота:';
$_['help_notifications_step2']       = 'Скопіюйте отриманий токен і вставте у поле "Telegram bot token".';
$_['help_notifications_step3']       = 'Дізнайтесь свій chat_id через бота:';
$_['help_notifications_step4']       = 'Вставте chat_id (особистий або groupId з мінусом) у поле "Telegram chat ID".';
$_['help_notifications_step5']       = 'Увімкніть тумблер "Telegram notify" і збережіть налаштування.';

$_['label_auto_blacklist_no_attributes'] = 'Авто-блекліст донорів без атрибутів';
$_['help_auto_blacklist_no_attributes']  = 'Якщо донор не повернув атрибутів, додавати його домен у чорний список з причиною. Працює лише разом з "Пропускати товар якщо AI не знайшов атрибутів". Допомагає швидко відсіювати неякісних донорів — вони більше не з\'являтимуться в результатах пошуку.';

$_['button_ai_aliases']             = 'Згенерувати синоніми (AI)';
$_['label_ai_aliases_overwrite']    = 'Перезаписати існуючі';
$_['help_ai_aliases']               = 'AI створить синоніми для назв активних атрибутів мовою, що вибрана у "Мова скраппінгу". За замовчуванням заповнюються лише порожні поля.';
$_['text_ai_aliases_loading']       = 'Генерація синонімів...';
$_['text_ai_aliases_done']          = 'Згенеровано синонімів для {n} атрибутів';
$_['text_ai_aliases_no_targets']    = 'Немає атрибутів для оновлення';
$_['text_selected']           = 'обрано';
$_['text_records']            = 'записів';
$_['label_per_page']          = 'На сторінці';
$_['text_per_page']           = 'на сторінці';
$_['text_bulk_processing']    = 'Обробка...';
$_['text_bulk_done']          = 'Готово';
$_['text_confirm_bulk_reject'] = 'Відхилити всі обрані товари?';
$_['button_add_donor']        = 'Додати донора';
$_['button_delete']           = 'Видалити';
$_['button_add_blacklist']   = 'До чорного списку';
$_['button_blacklist']       = 'До чорного списку';
$_['button_go_to_product']   = 'Перейти до товару';
$_['label_mod_images']       = 'Зображення';
$_['button_del_image']       = 'Видалити';
$_['label_img_upload']       = 'Перетягніть зображення або натисніть для завантаження';
$_['button_test_selector']    = 'Тест селектора';
$_['button_test_ai']          = 'Тест AI';
$_['button_test_search']      = 'Тест пошуку';
$_['button_cancel']           = 'Скасувати';
$_['button_add']              = 'Додати';
$_['button_add_attribute']    = 'Додати атрибут';
$_['label_attr_name']         = 'Назва';
$_['label_attr_value']        = 'Значення';

// ── Donor form labels ─────────────────────────────────────────────────────────
$_['label_domain']               = 'Домен';
$_['label_donor_status']         = 'Статус';
$_['label_donor_priority']       = 'Пріоритетний донор';
$_['help_donor_priority']        = 'Якщо цей донор потрапляє в результати пошуку — він буде обраний першим. Якщо пріоритетного донора в результатах немає — пошук відбувається звичайно.';
$_['label_desc_selector']           = 'Селектор опису';
$_['label_attrs_selector']          = 'Селектор атрибутів';
$_['label_search_url_template']     = 'Шаблон URL пошуку';
$_['placeholder_search_url_template'] = 'https://example.com/search?q={query}';
$_['help_search_url_template']      = 'Необов\'язково. Якщо задано — скрапер використовуватиме цей шаблон (з маскою <code>{query}</code>) для пошуку товару безпосередньо на доноре, не через пошуковик.';
$_['label_custom_prompt']           = 'Власний промпт';
$_['label_image_selector']           = 'Селектор зображень';
$_['placeholder_image_selector']     = '.product-gallery img';
$_['help_image_selector']            = 'CSS-селектор для зображень товару. Підходить для <code>&lt;img&gt;</code> або їх контейнерів. Атрибути: <code>src</code> / <code>data-src</code>.';
$_['label_attributes_pattern']      = 'XPath атрибутів';
$_['placeholder_attributes_pattern'] = 'Генерується автоматично при першому скрапінгу з AI-fallback';
$_['help_attributes_pattern']       = 'Відносний XPath для вибору рядків атрибутів усередині знайденого контейнера. Генерується автоматично AI, коли DOM-стратегії не спрацьовують. Скиньте для повторної генерації.';
$_['button_reset_pattern']          = 'Скинути';
$_['label_bl_reason']            = 'Причина (необов\'язково)';
$_['help_skip_ai']               = 'Використовувати CSS-селектори замість AI для цього домену';
$_['label_ai_fallback']          = 'AI-fallback для відсутніх даних';
$_['column_ai_fallback']         = 'AI fallback';
$_['help_ai_fallback']           = 'Якщо CSS-режим активний, але селектор зображень не задано — використовувати AI для вилучення зображень та автоматичного визначення селектора на майбутні скраппінги.';
$_['placeholder_desc_selector']  = '.product-description';
$_['placeholder_attrs_selector'] = '.product-attributes table';
$_['placeholder_custom_prompt']  = 'Додаткові інструкції для AI-вилучення...';
$_['placeholder_bl_url']         = 'https://example.com/page';
$_['text_no_blacklist']          = 'Заблокованих доменів немає';

// ── Help texts ────────────────────────────────────────────────────────────────
$_['help_moderation_mode']              = 'При увімкненні — дані зберігаються для перевірки, не застосовуються автоматично.';
$_['help_use_markdown']                 = 'Конвертація HTML у Markdown зменшує кількість токенів AI на ~40–60%.';
$_['help_overwrite_existing']           = 'Якщо вимкнено — поля, які вже заповнені, не будуть перезаписані.';
$_['help_auto_enrich']                  = 'Автоматично додавати до черги нові товари без опису.';
$_['help_throttle']                     = 'Мінімальна затримка між HTTP-запитами до одного домену (мс). 0 = без затримки.';
$_['help_serper']                       = 'Зареєструйтесь на <strong>serper.dev</strong> → API Key. 2 500 безкоштовних запитів при реєстрації.';
$_['help_brave']                        = 'Зареєструйтесь на <strong>api.search.brave.com</strong> → Subscriptions → тариф "Data for AI" (2 000 безкоштовно/місяць).';
$_['help_bing']                         = 'Azure portal → створіть ресурс "Bing Web Search" → безкоштовний тариф F1 (1 000/місяць). Скопіюйте ключ з розділу Keys and Endpoint.';
$_['help_google_cx']                    = 'ID пошукової системи Google CSE. Отримати на <strong>programmablesearchengine.google.com</strong>. Увага: API закрито для нових акаунтів Google Cloud.';
$_['help_translater_pro_unavailable']   = 'Translater Pro не встановлено. Авто-переклад недоступний.';
$_['help_translater_pro_tip']           = 'З модулем <strong>Translater Pro</strong> скрапер запускається один раз, і контент автоматично перекладається на всі потрібні мови — без повторного скрапінгу. Без Translater Pro доведеться запускати скрапер окремо для кожної мови.';

// ── Attribute mapping ─────────────────────────────────────────────────────────
$_['label_auto_create_donor']      = 'Авто-збереження донора з селекторами';
$_['help_auto_create_donor']       = 'Після AI-скрапінгу автоматично зберігає домен як донора з CSS-селекторами, які визначив AI. При наступному скрапінгу цього домену дані будуть братись напряму через селектори — без витрат токенів AI.';
$_['label_scrape_attr_create_new']      = 'Автоствоення нових атрибутів';
$_['help_scrape_attr_create_new']       = 'Якщо увімкнено, атрибути, яких немає в магазині, будуть створюватись автоматично у вибраній групі за замовчуванням. Вимкніть, щоб заповнювати лише існуючі атрибути.';
$_['label_skip_if_no_attributes']       = 'Пропускати, якщо атрибутів немає';
$_['help_skip_if_no_attributes']        = 'Якщо увімкнено і «Характеристики» обрані в «Полях для заповнення» — задача позначається як <em>Пропущено</em>, якщо донор не повернув жодного атрибута. Корисно, щоб не витрачати токени AI на сторінки без характеристик.';
$_['label_skip_if_has_attributes']      = 'Пропускати, якщо атрибути вже є';
$_['help_skip_if_has_attributes']       = 'Якщо увімкнено — товари, в яких уже є заповнені атрибути, пропускаються до скраппінгу (економить виклики AI). Перевірка виконується перед пошуком і вилученням даних.';
$_['label_attributes_mode']             = 'Режим атрибутів';
$_['help_attributes_mode']              = '<strong>Об\'єднання</strong> — наявні атрибути товару зберігаються, нові додаються/оновлюються. <strong>Заміна</strong> — всі наявні атрибути товару видаляються, лишаються лише зіскраплені.';
$_['text_attributes_merge']             = 'Об\'єднання (зберігати наявні)';
$_['text_attributes_replace']           = 'Заміна (тільки зіскраплені)';
$_['col_attr_name']      = 'Назва атрибута';
$_['col_attr_group']     = 'Група';
$_['col_attr_aliases']   = 'Псевдоніми (через кому)';
$_['col_attr_overwrite'] = 'Перезапис';
$_['col_attr_status']    = 'Активний';
$_['placeholder_attr_aliases'] = 'напр. колір, цвет, colour';

// ── Images ────────────────────────────────────────────────────────────────────
$_['tab_images']                    = 'Зображення';
$_['label_scrape_image_main']       = 'Головне зображення';
$_['label_scrape_image_additional'] = 'Додаткові зображення';
$_['label_scrape_image_count']      = 'К-сть додаткових';
$_['label_images_mode']             = 'Режим зображень';
$_['help_images_mode']              = '<strong>Заміна</strong> — головне перезаписується, додаткові видаляються й завантажуються заново. <strong>Доповнення</strong> — головне лишається (ставиться лише якщо порожнє), нові додаткові додаються до наявних. <strong>Якщо порожнє</strong> — заповнювати лише коли зображень ще немає.';
$_['text_images_replace']           = 'Заміна (перезаписати всі)';
$_['text_images_append']            = 'Доповнення (додати до наявних)';
$_['text_images_fill']              = 'Якщо порожнє';
$_['label_scrape_image_dir']        = 'Директорія збереження';
$_['help_scrape_image_dir']         = 'Піддиректорія відносно <code>image/</code>. Маски: <code>{product_id}</code>, <code>{model}</code>, <code>{sku}</code>, <code>{ean}</code>, <code>{jan}</code>.';
$_['placeholder_scrape_image_dir']  = 'catalog/scraped/{product_id}';
$_['unit_images']                   = 'зображень';
$_['label_min_image_size']          = 'Мін. розмір зображення (px)';
$_['help_min_image_size']           = 'Пропускати зображення, де ширина або висота менша за це значення (px). 0 — вимкнено.';

// ── Errors ────────────────────────────────────────────────────────────────────
// ── Filter options ────────────────────────────────────────────────────────────
$_['filter_all']              = 'Всі товари';
$_['filter_no_description']   = 'Без опису';
$_['filter_no_attributes']    = 'Без атрибутів';
$_['label_category']          = 'Всі категорії';
$_['label_language']          = 'Мова';

// ── Statistics ────────────────────────────────────────────────────────────────
$_['text_stats_jobs']         = 'Всього задач у черзі';
$_['text_stats_enriched_7d']  = 'Збагачено за 7 днів';
$_['text_stats_enriched_30d'] = 'Збагачено за 30 днів';
$_['text_stats_tokens_7d']    = 'Токенів витрачено (7 дн)';
$_['text_stats_tokens_30d']   = 'Токенів витрачено (30 дн)';
$_['text_stats_blacklist']    = 'Заблокованих доменів';
$_['text_stats_donors']       = 'Активних донорів';
$_['text_loading']            = 'Завантаження...';
$_['text_no_products']        = 'Товарів не знайдено.';
$_['text_select_all']         = 'Вибрати всі';
$_['text_extensions']         = 'Розширення';
$_['placeholder_url']         = 'https://...';
$_['placeholder_domain']      = 'example.com';
$_['placeholder_product_id']  = 'ID товару';
$_['placeholder_test_search'] = 'Nike Air Max 90 CW7887-100';
$_['text_confirm_enrich']     = 'Скрапити вибрані товари?';
$_['text_confirm_clear_queue'] = 'Видалити завдання (pending/error) для вибраних товарів?';

// ── Errors ────────────────────────────────────────────────────────────────────
$_['error_search_key']           = 'Вкажіть API Key для обраного пошукового провайдера.';
$_['error_google_key']           = 'Вкажіть Google API Key.';
$_['error_google_cx']            = 'Вкажіть ID пошукової системи (CX).';
$_['error_ai_key']               = 'Вкажіть API Key для обраного провайдера.';
$_['error_product_id']           = 'Вкажіть ID товару.';
$_['error_job_id']               = 'ID задачі не вказано.';
$_['error_domain_required']      = 'Вкажіть домен.';
$_['error_url_required']         = 'Вкажіть URL.';
$_['error_url_selector_required']= 'Вкажіть URL та CSS-селектор.';
$_['error_query_required']       = 'Введіть пошуковий запит.';
$_['error_generic']              = 'Виникла помилка. Спробуйте ще раз.';

// ── FAQ ───────────────────────────────────────────────────────────────────────
$_['faq_section_google']       = 'Google Search API';
$_['faq_google_api_title']     = 'Як отримати Google API Key?';
$_['faq_google_api_text']      = 'Перейдіть на console.cloud.google.com → Оберіть або створіть проект → APIs &amp; Services → Credentials → Create Credentials → API Key. Потім увімкніть Custom Search API: Library → Custom Search API → Enable.';
$_['faq_google_cx_title']      = 'Як отримати Search Engine ID (CX)?';
$_['faq_google_cx_text']       = 'Перейдіть на programmablesearchengine.google.com → New search engine → оберіть «Search the entire web» → Після створення скопіюйте Search engine ID з налаштувань.';
$_['faq_section_ai']           = 'AI провайдери';
$_['faq_ai_openai_title']      = 'Як отримати OpenAI API Key?';
$_['faq_ai_openai_text']       = 'Зареєструйтесь на platform.openai.com → API Keys → Create new secret key. Поповніть баланс у Billing. Рекомендована модель: gpt-4o-mini (найкращий баланс ціни та якості).';
$_['faq_ai_deepseek_title']    = 'Як отримати DeepSeek API Key?';
$_['faq_ai_deepseek_text']     = 'Зареєструйтесь на platform.deepseek.com → API Keys → Create API Key. Модель deepseek-chat підходить для більшості завдань за низькою ціною.';
$_['faq_ai_claude_title']      = 'Як отримати Claude API Key?';
$_['faq_ai_claude_text']       = 'Зареєструйтесь на console.anthropic.com → API Keys → Create Key. Рекомендована модель: claude-haiku-4-5-20251001 — швидка та дешева.';
$_['faq_ai_gemini_title']      = 'Як отримати Gemini API Key?';
$_['faq_ai_gemini_text']       = 'Перейдіть на aistudio.google.com → Get API Key → Create API Key. При реєстрації Google надає $300 кредитів на Google Cloud. Рекомендована модель: gemini-2.0-flash — швидка та дешева.';
$_['faq_section_scraping']     = 'Скрапінг';
$_['faq_donors_title']         = 'Що таке Донор?';
$_['faq_donors_text']          = 'Донор — сайт-джерело, з якого скрапер бере дані. Налаштовується на вкладці «Донори». Для кожного домену можна вказати CSS-селектори вмісту або дозволити AI аналізувати сторінку автоматично.';
$_['faq_moderation_title']     = 'Що таке Модерація?';
$_['faq_moderation_text']      = 'При ввімкненому режимі модерації скрапер не застосовує дані до товару автоматично. Ви переглядаєте результат у розділі «Модерація», схвалюєте або відхиляєте кожен товар вручну.';
$_['faq_cron_title']           = 'Як налаштувати cron?';
$_['faq_cron_text']            = 'Додайте задачу в crontab сервера. Команда крону відображається на вкладці «Додатково». Рекомендований інтервал: раз на 5 хвилин.';

// ── License ───────────────────────────────────────────────────────────────────
$_['entry_license_key']          = 'Ліцензійний ключ';
$_['button_activate']            = 'Активувати';
$_['button_recheck']             = 'Перевірити';
$_['text_license_active']        = 'Ліцензія активна';
$_['text_license_trial']         = 'Пробний період: %s дн.';
$_['text_license_invalid']       = 'Невалідний ключ';
$_['text_license_expired']       = 'Ліцензія прострочена';
$_['text_license_no_key']        = 'Ключ не введено';
$_['text_license_grace']         = 'Відновлення зв\'язку...';
$_['text_license_api_error']     = 'Не вдається зв\'язатися з сервером активації';
$_['text_license_not_validated'] = 'Очікує перевірки';
$_['text_license_buy']           = 'Придбати ліцензію';
$_['text_license_version']       = 'Версія';
$_['text_license_domain']        = 'Домен';
