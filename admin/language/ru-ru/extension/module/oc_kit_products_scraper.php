<?php
/**
 * Products Scraper Pro — Language: ru-ru
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// ── Heading ───────────────────────────────────────────────────────────────────
$_['heading_title'] = 'oc-kit.com — Products Scraper Pro';

// ── Common ────────────────────────────────────────────────────────────────────
$_['text_success']      = 'Изменения сохранены.';
$_['text_edit']         = 'Редактировать';
$_['text_enabled']      = 'Активен';
$_['text_disabled']     = 'Отключён';
$_['text_approved']     = 'Одобрено и применено.';
$_['text_rejected']     = 'Отклонено.';
$_['text_no_jobs']      = 'Заданий не найдено.';
$_['text_no_donors']    = 'Доноры не настроены.';
$_['text_no_entries']   = 'Записей не найдено.';
$_['text_confirm_delete']    = 'Удалить запись?';
$_['text_confirm_reject']    = 'Отклонить данные и пропустить это задание?';
$_['text_confirm_blacklist'] = 'Добавить в чёрный список:';

// ── Tabs ──────────────────────────────────────────────────────────────────────
$_['tab_settings']      = 'Настройки';
$_['tab_products']      = 'Товары';
$_['tab_moderation']    = 'Модерация';
$_['tab_donors']        = 'Доноры';
$_['tab_log']           = 'Журнал';
$_['tab_general']       = 'Основное';
$_['tab_google']        = 'Google Search';
$_['tab_ai']            = 'AI провайдер';
$_['tab_translation']   = 'Перевод';
$_['tab_attributes']    = 'Атрибуты';
$_['tab_notifications'] = 'Уведомления';
$_['tab_advanced']      = 'Дополнительно';
$_['tab_integrations']  = 'Интеграции';
$_['tab_stats']         = 'Статистика';
$_['tab_faq']           = 'FAQ';
$_['tab_license']       = 'Лицензия';

// ── Settings labels ───────────────────────────────────────────────────────────
$_['label_status']                   = 'Статус модуля';
$_['label_moderation_mode']          = 'Режим модерации';
$_['label_default_language']         = 'Язык скрапинга';
$_['label_attribute_group']          = 'Группа атрибутов (по умолч.)';
$_['label_fields_to_fill']           = 'Поля для заполнения';
$_['label_field_modes']              = 'Поля и режим записи';
$_['help_field_modes']               = 'Для каждого поля: <strong>Неактивно</strong> — не трогать; <strong>Если пусто</strong> — заполнять только когда значение отсутствует; <strong>Перезаписывать</strong> — всегда заменять существующее значение извлечённым.';
$_['column_field']                   = 'Поле';
$_['text_mode_off']                  = 'Неактивно';
$_['text_mode_fill']                 = 'Если пусто';
$_['text_mode_overwrite']            = 'Перезаписывать';
$_['label_overwrite_existing']       = 'Перезаписывать существующие поля';
$_['label_search_provider']          = 'Поисковый провайдер';
$_['label_search_results_count']        = 'Результатов поиска';
$_['label_search_query_template']       = 'Шаблон поискового запроса';
// AI token limits & cost
$_['label_auto_fill_selectors']    = 'Авто-заполнение CSS-селекторов';
$_['help_auto_fill_selectors']     = 'После успешного AI-скрапинга автоматически заполнять пустые поля <code>description_selector</code>, <code>attributes_selector</code>, <code>image_selector</code> в доноре. Ручные значения не перезаписываются.';
$_['label_token_limit_daily']      = 'Лимит токенов (день / месяц)';
$_['help_token_limits']             = 'Дневной и месячный лимит. <code>0</code> = без ограничений. При превышении применяется действие из настройки "При превышении лимита".';
$_['label_on_limit_exceeded']       = 'При превышении лимита';
$_['help_on_limit_exceeded']        = 'Как реагировать, когда AI-провайдер исчерпал дневной или месячный лимит токенов.';
$_['text_on_limit_error']           = 'Ошибка (пометить job как error)';
$_['text_on_limit_skip']            = 'Пропустить (job в статусе skipped)';
$_['text_on_limit_fallback']        = 'Переключиться на резервного провайдера';
$_['label_fallback_provider']       = 'Резервный провайдер';
$_['text_token_usage_title']        = 'Использование токенов и расходы';
$_['text_col_provider']             = 'Провайдер';
$_['text_col_today']                = 'Сегодня';
$_['text_col_month']                = 'Этот месяц';
$_['text_col_limits']               = 'Лимиты (день / мес)';
$_['text_col_used_pct']             = 'Использовано (день / мес)';
$_['text_requests']                 = 'запросов';

$_['help_search_query_template']        = 'Стандартные маски: <code>{name}</code>, <code>{model}</code>, <code>{manufacturer}</code>, <code>{sku}</code>, <code>{upc}</code>, <code>{ean}</code>, <code>{jan}</code>, <code>{isbn}</code>, <code>{mpn}</code>. Для подстановки значения атрибута товара используйте <code>{attribute_ID}</code>, где ID — числовой идентификатор атрибута в БД (напр. <code>{attribute_42}</code>). Любая другая неизвестная маска рассматривается как колонка <code>oc_product</code>. <strong>Условные блоки:</strong> <code>{if VAR}…{endif}</code> и <code>{if VAR}…{else}…{endif}</code>. Напр. <code>{name}{if manufacturer} Бренд: {manufacturer}{endif}</code> → "Air Max Бренд: Nike" или "Air Max" когда бренд пустой. Вложенные блоки не поддерживаются. Оставьте пустым для дефолта: <code>{name} {model}</code>.';
$_['placeholder_search_query_template'] = '{name} {model}';
$_['label_search_gl']                = 'Страна поиска (geo)';
$_['label_search_hl']                = 'Язык поиска';
$_['help_search_gl']                 = 'Код страны ISO 3166-1 alpha-2 в нижнем регистре. Напр. <code>us</code>, <code>gb</code>, <code>ua</code>, <code>de</code>. Оставьте пустым для использования дефолта провайдера.';
$_['placeholder_search_gl']          = 'ru';
$_['placeholder_search_hl']          = 'ru';
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
$_['label_fetch_timeout']            = 'Таймаут запроса';
$_['label_fetch_throttle_ms']        = 'Задержка между запросами (мс)';
$_['label_max_html_length']          = 'Макс. длина контента';
$_['label_min_content_length']       = 'Мин. длина контента (SPA-skip)';
$_['help_min_content_length']        = 'Пропускать URL, если очищенный контент короче указанного количества символов (например, JS-рендеренные / SPA-сайты, возвращающие пустую HTML-оболочку). 0 — отключено.';
$_['unit_seconds']                   = 'секунд';
$_['unit_ms']                        = 'миллисекунд';
$_['unit_chars']                     = 'символов';
$_['label_use_markdown']             = 'Конвертировать HTML → Markdown';
$_['label_first_donor_html']         = 'HTML для новых доноров (для определения селекторов)';
$_['help_first_donor_html']          = 'Если домен встречается впервые и не имеет сохранённых CSS-селекторов — отправлять в AI сырой HTML вместо Markdown. Это позволяет AI корректно определить селекторы для авто-донора.';
$_['label_moderation_hide_inactive'] = 'Скрывать неактивные поля в попапе просмотра';
$_['help_moderation_hide_inactive']  = 'При просмотре данных в разделе Модерация — скрывать поля, не отмеченные в «Поля для заполнения».';
$_['label_cron_batch_size']          = 'Заданий за один запуск';
$_['label_auto_enrich_new_products'] = 'Авто-скрапинг новых товаров';
$_['label_auto_enrich_days']         = 'Товары за последние N дней';
$_['label_auto_translate']           = 'Авто-перевод (Translater Pro)';
$_['label_translate_languages']      = 'Языки для перевода';
$_['label_telegram_notify']          = 'Уведомления Telegram';
$_['label_telegram_bot_token']       = 'Bot Token';
$_['label_telegram_chat_id']         = 'ID пользователя';
$_['label_cron_command']             = 'Команда крона';
$_['label_editor_pro_enabled']       = 'Кнопка в Editor Pro';
$_['help_editor_pro_enabled']        = 'Показывать кнопку «Скрапить» в модуле Editor Pro для каждого товара.';
$_['help_editor_pro_tip']            = 'С модулем <strong>Editor Pro</strong> кнопка «Скрапить» отображается прямо в визуальном редакторе — скрапинг можно запускать, не покидая страницу товара. Без Editor Pro запуск доступен только из списка Products Scraper.';
$_['help_editor_pro_unavailable']    = 'Editor Pro не установлен. Интеграция недоступна.';

// ── Navigation text keys ──────────────────────────────────────────────────────
$_['text_products']     = 'Товары';
$_['text_moderation']   = 'Модерация';
$_['text_donors']       = 'Доноры';
$_['text_blacklist']    = 'Чёрный список';
$_['text_log']          = 'Журнал';
$_['text_stats']        = 'Статистика';

// ── Field labels (fields_to_fill checkboxes) ──────────────────────────────────
$_['text_field_description'] = 'Описание';
$_['text_field_meta_title']  = 'Meta Title';
$_['text_field_meta_desc']   = 'Meta Description';
$_['text_field_meta_kw']     = 'Meta Keywords';
$_['text_field_tag']         = 'Теги';
$_['text_field_attributes']  = 'Характеристики';
$_['text_field_upc']         = 'UPC';
$_['text_field_ean']         = 'EAN';
$_['text_field_jan']         = 'JAN';
$_['text_field_mpn']         = 'MPN';
$_['text_field_images']      = 'Изображения';

// ── AI provider names ─────────────────────────────────────────────────────────
$_['text_provider_serper']   = 'Serper.dev (рекомендуется, 2 500 бесплатно)';
$_['text_provider_brave']    = 'Brave Search (2 000 бесплатно/месяц)';
$_['text_provider_bing']     = 'Bing Web Search (1 000 бесплатно/месяц)';
$_['text_provider_google']   = 'Google Custom Search (legacy)';
$_['text_provider_openai']   = 'OpenAI';
$_['text_provider_deepseek'] = 'DeepSeek';
$_['text_provider_claude']   = 'Claude (Anthropic)';
$_['text_provider_gemini']   = 'Google Gemini';

// ── Table columns ─────────────────────────────────────────────────────────────
$_['column_job_id']    = 'ID';
$_['column_product']   = 'Товар';
$_['column_name']      = 'Название';
$_['column_url']       = 'URL донора';
$_['column_model']     = 'Модель';
$_['column_status']    = 'Статус';
$_['column_priority']  = 'Приоритет';
$_['priority_normal']  = 'Низкий';
$_['priority_high']    = 'Обычный';
$_['priority_urgent']  = 'Высокий';
$_['column_source_url']= 'Источник';
$_['column_ai_provider'] = 'AI';
$_['column_created']   = 'Создано';
$_['column_updated']   = 'Обновлено';
$_['column_actions']   = 'Действия';
$_['column_domain']    = 'Домен';
$_['column_reason']    = 'Причина';
$_['column_skip_ai']   = 'Режим';
$_['column_selectors'] = 'CSS-селекторы';
$_['column_level']     = 'Уровень';
$_['column_message']   = 'Сообщение';

// ── Statuses ──────────────────────────────────────────────────────────────────
$_['status_pending']    = 'Ожидает';
$_['status_running']    = 'Выполняется';
$_['status_done']       = 'Готово';
$_['status_error']      = 'Ошибка';
$_['status_moderation'] = 'Модерация';
$_['status_skipped']    = 'Пропущено';

// ── Buttons ───────────────────────────────────────────────────────────────────
$_['text_copied']             = 'Скопировано!';
$_['button_save']             = 'Сохранить';
$_['button_add_job']          = 'Добавить задание';
$_['button_enrich']           = 'Скрапить';
$_['button_set_url']          = 'Указать URL';
$_['button_enrich_selected']  = 'Скрапить выбранные';
$_['button_urgent_selected']  = 'Срочно';
$_['button_clear_queue']      = 'Отменить очередь';
$_['button_run_now']          = 'Запустить';
$_['text_running_jobs']       = 'Выполняется {current} из {total}…';
$_['text_run_complete']       = 'Готово: {done}, Модерация: {moderation}, Ошибки: {errors}';
$_['button_retry']        = 'Повторить';
$_['button_skip']         = 'Пропустить';
$_['button_approve']           = 'Одобрить';
$_['button_approve_translate'] = 'Одобрить и перевести';
$_['button_approve_all']  = 'Одобрить все';
$_['button_reject']       = 'Отклонить';
$_['button_view_log']     = 'Лог';
$_['button_view_data']    = 'Просмотр данных';
$_['tab_edit_data']       = 'Редактирование';
$_['tab_preview_card']    = 'Предпросмотр';
$_['button_bulk_approve']     = 'Одобрить';
$_['button_bulk_approve_translate'] = 'Одобрить и перевести';
$_['button_bulk_reject']      = 'Отклонить';
$_['button_reject_and_block']  = 'Отклонить и заблокировать';
$_['text_confirm_reject_block'] = 'Отклонить и добавить донора в чёрный список?';
$_['text_blocked_via_moderation'] = 'Заблокировано через модерацию';
$_['text_blacklisted']            = 'Добавлено в чёрный список';

$_['help_notifications_setup_title'] = 'Как настроить Telegram-уведомления';
$_['help_notifications_step1']       = 'Создайте бота:';
$_['help_notifications_step2']       = 'Скопируйте полученный токен и вставьте в поле "Telegram bot token".';
$_['help_notifications_step3']       = 'Узнайте свой chat_id через бота:';
$_['help_notifications_step4']       = 'Вставьте chat_id (личный или groupId с минусом) в поле "Telegram chat ID".';
$_['help_notifications_step5']       = 'Включите тумблер "Telegram notify" и сохраните настройки.';

$_['label_auto_blacklist_no_attributes'] = 'Авто-блеклист доноров без атрибутов';
$_['help_auto_blacklist_no_attributes']  = 'Если донор не вернул атрибутов, добавлять его домен в чёрный список с причиной. Работает только вместе с "Пропускать товар если AI не нашёл атрибутов". Помогает быстро отсеивать некачественных доноров — они больше не будут появляться в результатах поиска.';

$_['button_ai_aliases']             = 'Сгенерировать синонимы (AI)';
$_['label_ai_aliases_overwrite']    = 'Перезаписать существующие';
$_['help_ai_aliases']               = 'AI создаст синонимы для названий активных атрибутов на языке, выбранном в "Язык скраппинга". По умолчанию заполняются только пустые поля.';
$_['text_ai_aliases_loading']       = 'Генерация синонимов...';
$_['text_ai_aliases_done']          = 'Сгенерировано синонимов для {n} атрибутов';
$_['text_ai_aliases_no_targets']    = 'Нет атрибутов для обновления';
$_['text_selected']           = 'выбрано';
$_['text_records']            = 'записей';
$_['label_per_page']          = 'На странице';
$_['text_per_page']           = 'на странице';
$_['text_bulk_processing']    = 'Обработка...';
$_['text_bulk_done']          = 'Готово';
$_['text_confirm_bulk_reject'] = 'Отклонить все выбранные товары?';
$_['button_add_donor']    = 'Добавить донора';
$_['button_delete']       = 'Удалить';
$_['button_add_blacklist']  = 'В чёрный список';
$_['button_blacklist']      = 'В чёрный список';
$_['button_go_to_product']  = 'Перейти к товару';
$_['label_mod_images']      = 'Изображения';
$_['button_del_image']      = 'Удалить';
$_['label_img_upload']      = 'Перетащите изображение или нажмите для загрузки';
$_['button_test_selector']= 'Тест селектора';
$_['button_test_ai']      = 'Тест AI';
$_['button_test_search']  = 'Тест поиска';
$_['button_cancel']       = 'Отмена';
$_['button_add']              = 'Добавить';
$_['button_add_attribute']    = 'Добавить атрибут';
$_['label_attr_name']         = 'Название';
$_['label_attr_value']        = 'Значение';

// ── Donor form labels ─────────────────────────────────────────────────────────
$_['label_domain']               = 'Домен';
$_['label_donor_status']         = 'Статус';
$_['label_donor_priority']       = 'Приоритетный донор';
$_['help_donor_priority']        = 'Если этот донор попадает в результаты поиска — он будет выбран первым. Если приоритетного донора в результатах нет — поиск происходит обычно.';
$_['label_desc_selector']            = 'Селектор описания';
$_['label_attrs_selector']           = 'Селектор атрибутов';
$_['label_search_url_template']      = 'Шаблон URL поиска';
$_['placeholder_search_url_template'] = 'https://example.com/search?q={query}';
$_['help_search_url_template']       = 'Необязательно. Если задан — скрапер будет использовать этот шаблон (с маской <code>{query}</code>) для поиска товара напрямую на доноре, минуя поисковик.';
$_['label_custom_prompt']            = 'Произвольный промпт';
$_['label_image_selector']            = 'Селектор изображений';
$_['placeholder_image_selector']      = '.product-gallery img';
$_['help_image_selector']             = 'CSS-селектор для изображений товара. Подходит для <code>&lt;img&gt;</code> или их контейнеров. Атрибуты: <code>src</code> / <code>data-src</code>.';
$_['label_attributes_pattern']       = 'XPath атрибутов';
$_['placeholder_attributes_pattern'] = 'Генерируется автоматически при первом скрапинге с AI-fallback';
$_['help_attributes_pattern']        = 'Относительный XPath для выбора строк атрибутов внутри найденного контейнера. Генерируется автоматически AI, когда DOM-стратегии не срабатывают. Сбросьте для повторной генерации.';
$_['button_reset_pattern']           = 'Сбросить';
$_['label_bl_reason']            = 'Причина (необязательно)';
$_['help_skip_ai']               = 'Использовать CSS-селекторы вместо AI для этого домена';
$_['label_ai_fallback']          = 'AI-fallback для отсутствующих данных';
$_['column_ai_fallback']         = 'AI fallback';
$_['help_ai_fallback']           = 'Если CSS-режим активен, но селектор изображений не задан — использовать AI для извлечения изображений и автоматического определения селектора для будущих скрапингов.';
$_['placeholder_desc_selector']  = '.product-description';
$_['placeholder_attrs_selector'] = '.product-attributes table';
$_['placeholder_custom_prompt']  = 'Дополнительные инструкции для AI-извлечения...';
$_['placeholder_bl_url']         = 'https://example.com/page';
$_['text_no_blacklist']          = 'Заблокированных доменов нет';

// ── Help texts ────────────────────────────────────────────────────────────────
$_['help_moderation_mode']              = 'При включении — данные сохраняются для проверки, не применяются автоматически.';
$_['help_use_markdown']                 = 'Конвертация HTML в Markdown сокращает количество токенов AI на ~40–60%.';
$_['help_overwrite_existing']           = 'Если выключено — уже заполненные поля не будут перезаписаны.';
$_['help_auto_enrich']                  = 'Автоматически добавлять в очередь новые товары без описания.';
$_['help_throttle']                     = 'Минимальная задержка между HTTP-запросами к одному домену (мс). 0 = без задержки.';
$_['help_serper']                       = 'Зарегистрируйтесь на <strong>serper.dev</strong> → API Key. 2 500 бесплатных запросов при регистрации.';
$_['help_brave']                        = 'Зарегистрируйтесь на <strong>api.search.brave.com</strong> → Subscriptions → тариф "Data for AI" (2 000 бесплатно/месяц).';
$_['help_bing']                         = 'Azure portal → создайте ресурс "Bing Web Search" → бесплатный тариф F1 (1 000/месяц). Скопируйте ключ из раздела Keys and Endpoint.';
$_['help_google_cx']                    = 'ID поисковой системы Google CSE. Получить на <strong>programmablesearchengine.google.com</strong>. Внимание: API закрыто для новых аккаунтов Google Cloud.';
$_['help_translater_pro_unavailable']   = 'Translater Pro не установлен. Авто-перевод недоступен.';
$_['help_translater_pro_tip']           = 'С модулем <strong>Translater Pro</strong> скрапер запускается один раз, а контент автоматически переводится на все нужные языки — без повторного скрапинга. Без Translater Pro нужно запускать скрапер отдельно для каждого языка.';

// ── Attribute mapping ─────────────────────────────────────────────────────────
$_['label_auto_create_donor']      = 'Авто-сохранение донора с селекторами';
$_['help_auto_create_donor']       = 'После AI-скрапинга автоматически сохраняет домен как донора с CSS-селекторами, определёнными AI. При следующем скрапинге этого домена данные берутся напрямую через селекторы — без расхода токенов AI.';
$_['label_scrape_attr_create_new']      = 'Автосоздание новых атрибутов';
$_['help_scrape_attr_create_new']       = 'Если включено, атрибуты, которых нет в магазине, будут создаваться автоматически в выбранной группе по умолчанию. Отключите, чтобы заполнять только существующие атрибуты.';
$_['label_skip_if_no_attributes']       = 'Пропускать, если атрибутов нет';
$_['help_skip_if_no_attributes']        = 'Если включено и «Характеристики» выбраны в «Полях для заполнения» — задача отмечается как <em>Пропущена</em>, если донор не вернул ни одного атрибута. Полезно, чтобы не тратить токены AI на страницы без характеристик.';
$_['label_skip_if_has_attributes']      = 'Пропускать, если атрибуты уже есть';
$_['help_skip_if_has_attributes']       = 'Если включено — товары, у которых уже есть заполненные атрибуты, пропускаются до скрапинга (экономит вызовы AI). Проверка выполняется перед поиском и извлечением данных.';
$_['label_attributes_mode']             = 'Режим атрибутов';
$_['help_attributes_mode']              = '<strong>Объединение</strong> — существующие атрибуты товара сохраняются, новые добавляются/обновляются. <strong>Замена</strong> — все существующие атрибуты товара удаляются, остаются только извлечённые.';
$_['text_attributes_merge']             = 'Объединение (сохранять существующие)';
$_['text_attributes_replace']           = 'Замена (только извлечённые)';
$_['col_attr_name']      = 'Название атрибута';
$_['col_attr_group']     = 'Группа';
$_['col_attr_aliases']   = 'Псевдонимы (через запятую)';
$_['col_attr_overwrite'] = 'Перезапись';
$_['col_attr_status']    = 'Активен';
$_['placeholder_attr_aliases'] = 'напр. цвет, colour, farbe';

// ── Images ────────────────────────────────────────────────────────────────────
$_['tab_images']                    = 'Изображения';
$_['label_scrape_image_main']       = 'Главное изображение';
$_['label_scrape_image_additional'] = 'Дополнительные изображения';
$_['label_scrape_image_count']      = 'Кол-во дополнительных';
$_['label_images_mode']             = 'Режим изображений';
$_['help_images_mode']              = '<strong>Замена</strong> — главное перезаписывается, дополнительные удаляются и загружаются заново. <strong>Добавление</strong> — главное остаётся (ставится только если пусто), новые дополнительные добавляются к существующим. <strong>Если пусто</strong> — заполнять только когда изображений ещё нет.';
$_['text_images_replace']           = 'Замена (перезаписать все)';
$_['text_images_append']            = 'Добавление (к существующим)';
$_['text_images_fill']              = 'Если пусто';
$_['label_scrape_image_dir']        = 'Директория сохранения';
$_['help_scrape_image_dir']         = 'Поддиректория относительно <code>image/</code>. Маски: <code>{product_id}</code>, <code>{model}</code>, <code>{sku}</code>, <code>{ean}</code>, <code>{jan}</code>.';
$_['placeholder_scrape_image_dir']  = 'catalog/scraped/{product_id}';
$_['unit_images']                   = 'изображений';
$_['label_min_image_size']          = 'Мин. размер изображения (px)';
$_['help_min_image_size']           = 'Пропускать изображения, где ширина или высота меньше этого значения (px). 0 — отключено.';

// ── Errors ────────────────────────────────────────────────────────────────────
// ── Filter options ────────────────────────────────────────────────────────────
$_['filter_all']              = 'Все товары';
$_['filter_no_description']   = 'Без описания';
$_['filter_no_attributes']    = 'Без атрибутов';
$_['label_category']          = 'Все категории';
$_['label_language']          = 'Язык';

// ── Statistics ────────────────────────────────────────────────────────────────
$_['text_stats_jobs']         = 'Всего задач в очереди';
$_['text_stats_enriched_7d']  = 'Обогащено за 7 дней';
$_['text_stats_enriched_30d'] = 'Обогащено за 30 дней';
$_['text_stats_tokens_7d']    = 'Токенов затрачено (7 дн)';
$_['text_stats_tokens_30d']   = 'Токенов затрачено (30 дн)';
$_['text_stats_blacklist']    = 'Заблокированных доменов';
$_['text_stats_donors']       = 'Активных доноров';
$_['text_loading']            = 'Загрузка...';
$_['text_no_products']        = 'Товаров не найдено.';
$_['text_select_all']         = 'Выбрать все';
$_['text_extensions']         = 'Расширения';
$_['placeholder_url']         = 'https://...';
$_['placeholder_domain']      = 'example.com';
$_['placeholder_product_id']  = 'ID товара';
$_['placeholder_test_search'] = 'Nike Air Max 90 CW7887-100';
$_['text_confirm_enrich']     = 'Скрапить выбранные товары?';
$_['text_confirm_clear_queue'] = 'Удалить задания (pending/error) для выбранных товаров?';

// ── Errors ────────────────────────────────────────────────────────────────────
$_['error_search_key']           = 'Укажите API Key для выбранного поискового провайдера.';
$_['error_google_key']           = 'Укажите Google API Key.';
$_['error_google_cx']            = 'Укажите ID поисковой системы (CX).';
$_['error_ai_key']               = 'Укажите API Key для выбранного провайдера.';
$_['error_product_id']           = 'Укажите ID товара.';
$_['error_job_id']               = 'ID задания не указан.';
$_['error_domain_required']      = 'Укажите домен.';
$_['error_url_required']         = 'Укажите URL.';
$_['error_url_selector_required']= 'Укажите URL и CSS-селектор.';
$_['error_query_required']       = 'Введите поисковый запрос.';
$_['error_generic']              = 'Произошла ошибка. Попробуйте ещё раз.';

// ── FAQ ───────────────────────────────────────────────────────────────────────
$_['faq_section_google']       = 'Google Search API';
$_['faq_google_api_title']     = 'Как получить Google API Key?';
$_['faq_google_api_text']      = 'Перейдите на console.cloud.google.com → Выберите или создайте проект → APIs &amp; Services → Credentials → Create Credentials → API Key. Затем включите Custom Search API: Library → Custom Search API → Enable.';
$_['faq_google_cx_title']      = 'Как получить Search Engine ID (CX)?';
$_['faq_google_cx_text']       = 'Перейдите на programmablesearchengine.google.com → New search engine → выберите «Search the entire web» → После создания скопируйте Search engine ID из настроек.';
$_['faq_section_ai']           = 'AI провайдеры';
$_['faq_ai_openai_title']      = 'Как получить OpenAI API Key?';
$_['faq_ai_openai_text']       = 'Зарегистрируйтесь на platform.openai.com → API Keys → Create new secret key. Пополните баланс в Billing. Рекомендованная модель: gpt-4o-mini (лучший баланс цены и качества).';
$_['faq_ai_deepseek_title']    = 'Как получить DeepSeek API Key?';
$_['faq_ai_deepseek_text']     = 'Зарегистрируйтесь на platform.deepseek.com → API Keys → Create API Key. Модель deepseek-chat подходит для большинства задач по низкой цене.';
$_['faq_ai_claude_title']      = 'Как получить Claude API Key?';
$_['faq_ai_claude_text']       = 'Зарегистрируйтесь на console.anthropic.com → API Keys → Create Key. Рекомендованная модель: claude-haiku-4-5-20251001 — быстрая и дешёвая.';
$_['faq_ai_gemini_title']      = 'Как получить Gemini API Key?';
$_['faq_ai_gemini_text']       = 'Перейдите на aistudio.google.com → Get API Key → Create API Key. При регистрации Google предоставляет $300 кредитов на Google Cloud. Рекомендованная модель: gemini-2.0-flash — быстрая и дешёвая.';
$_['faq_section_scraping']     = 'Скрапинг';
$_['faq_donors_title']         = 'Что такое Донор?';
$_['faq_donors_text']          = 'Донор — сайт-источник, с которого скрапер берёт данные. Настраивается на вкладке «Доноры». Для каждого домена можно указать CSS-селекторы содержимого или разрешить AI анализировать страницу автоматически.';
$_['faq_moderation_title']     = 'Что такое Модерация?';
$_['faq_moderation_text']      = 'При включённом режиме модерации скрапер не применяет данные к товару автоматически. Вы просматриваете результат в разделе «Модерация», одобряете или отклоняете каждый товар вручную.';
$_['faq_cron_title']           = 'Как настроить cron?';
$_['faq_cron_text']            = 'Добавьте задачу в crontab сервера. Команда крона отображается на вкладке «Дополнительно». Рекомендованный интервал: раз в 5 минут.';

// ── License ───────────────────────────────────────────────────────────────────
$_['entry_license_key']          = 'Лицензионный ключ';
$_['button_activate']            = 'Активировать';
$_['button_recheck']             = 'Перепроверить';
$_['text_license_active']        = 'Лицензия активна';
$_['text_license_trial']         = 'Пробный период: %s дн.';
$_['text_license_invalid']       = 'Невалидный ключ';
$_['text_license_expired']       = 'Лицензия истекла';
$_['text_license_no_key']        = 'Ключ не введён';
$_['text_license_grace']         = 'Восстановление связи...';
$_['text_license_api_error']     = 'Не удаётся связаться с сервером активации';
$_['text_license_not_validated'] = 'Ожидает проверки';
$_['text_license_buy']           = 'Купить лицензию';
$_['text_license_version']       = 'Версия';
$_['text_license_domain']        = 'Домен';
