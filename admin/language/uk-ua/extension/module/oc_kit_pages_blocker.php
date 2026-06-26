<?php
/**
 * Pages Blocker — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title']              = 'oc-kit.com — Pages Blocker';

// Tabs
$_['text_rules']                 = 'Правила';
$_['text_notifications']         = 'Сповіщення';
$_['text_settings']              = 'Налаштування';

// General text
$_['text_add_rule']              = 'Додати правило';
$_['text_edit_rule']             = 'Редагувати правило';
$_['text_no_rules']              = 'Правил ще немає. Натисніть «Додати правило», щоб створити перше.';
$_['text_no_notifications']      = 'Заявок поки немає.';
$_['text_confirm_delete']        = 'Ви впевнені, що хочете видалити?';
$_['text_success_save']          = 'Зміни збережено';
$_['text_success_delete']        = 'Запис видалено';
$_['text_guests']                = 'Гості (не авторизовані)';
$_['text_unread']                = 'непрочитаних';
$_['text_records']               = 'записів';
$_['text_yes']                   = 'Так';
$_['text_no']                    = 'Ні';
$_['text_enabled']               = 'Увімкнено';
$_['text_disabled']              = 'Вимкнено';
$_['text_no_results']            = 'Нічого не знайдено';
$_['text_extension']             = 'Розширення';

// Table columns — Rules
$_['column_id']                  = '#';
$_['column_name']                = 'Назва';
$_['column_type']                = 'Тип';
$_['column_entities']            = 'Сутності';
$_['column_groups']              = 'Групи';
$_['column_countries']           = 'Країни';
$_['column_action']              = 'Дія';
$_['column_status']              = 'Статус';
$_['column_actions']             = 'Дії';

// Table columns — Notifications
$_['column_date']                = 'Дата';
$_['column_email']               = 'Email';
$_['column_sender_name']         = "Ім'я";
$_['column_page']                = 'Сторінка';
$_['column_message']             = 'Повідомлення';

// Form fields — Rule
$_['entry_name']                 = 'Назва правила';
$_['entry_entity_type']          = 'Тип сутності';
$_['entry_entities']             = 'Сутності';
$_['entry_search']               = 'Пошук...';
$_['entry_search_product']       = 'Пошук товарів...';
$_['entry_search_category']      = 'Пошук категорій...';
$_['entry_search_manufacturer']  = 'Пошук брендів...';
$_['entry_groups']               = 'Дозволені групи';
$_['entry_countries']            = 'Дозволені країни (ISO)';
$_['entry_fallback']             = 'Дія при блокуванні';
$_['entry_sort_order']           = 'Порядок сортування';
$_['entry_status']               = 'Статус';

// Form fields — Settings
$_['entry_default_action']       = 'Дія за замовчуванням';
$_['entry_allow_bots']           = 'Не блокувати пошукових ботів';
$_['entry_bot_patterns']         = 'Список ботів (User-Agent)';
$_['entry_bot_patterns_placeholder'] = 'googlebot';
$_['help_bot_patterns']          = 'Один патерн на рядок. Порівняння без урахування регістру, пошук підрядка у User-Agent. Якщо поле порожнє — використовується вбудований список.';
$_['entry_extend_category']      = 'Розширене блокування категорій';
$_['entry_extend_manufacturer']  = 'Розширене блокування брендів';
$_['entry_geo_source']           = 'Джерело геолокації';
$_['entry_unknown_geo']          = 'Блокувати невизначену геолокацію';
$_['entry_notify_title']         = 'Заголовок форми сповіщення';
$_['entry_notify_text']          = 'Текст форми';
$_['entry_notify_button']        = 'Текст кнопки';
$_['entry_admin_email']          = 'Email адміна';
$_['entry_send_confirmation']    = 'Надсилати підтвердження покупцю';

// Help texts
$_['help_allow_bots']            = 'Google, Bing, Yandex та інші пошукові боти зможуть індексувати заблоковані сторінки';
$_['help_extend_category']       = 'Якщо увімкнено — товари заблокованих категорій також зникають із каталогу, пошуку та схожих товарів';
$_['help_extend_manufacturer']   = 'Якщо увімкнено — товари заблокованих брендів також зникають із каталогу та пошуку';
$_['help_unknown_geo']           = 'Якщо заголовок геолокації відсутній або невалідний — блокувати такого відвідувача';
$_['help_geo_source']            = 'Джерело для визначення країни відвідувача. «Авто» перевіряє CF-IPCountry, X-Real-IP, потім REMOTE_ADDR';
$_['help_notify_text']           = 'Звичайний текст. Для відображення назви сайту використовуйте статичний текст.';

// Entity types
$_['type_product']               = 'Товар';
$_['type_category']              = 'Категорія';
$_['type_manufacturer']          = 'Бренд';

// Actions
$_['action_global']              = 'Глобальне';
$_['action_404']                 = '404';
$_['action_notify_form']         = 'Форма сповіщення';

// Geo sources
$_['geo_cloudflare']             = 'Cloudflare (CF-IPCountry)';
$_['geo_x_country_code']         = 'X-Country-Code';
$_['geo_geoip']                  = 'Geoip-Country-Code';
$_['geo_x_real_country']         = 'X-Real-Country';
$_['geo_auto']                   = 'Авто (всі по черзі)';
$_['geo_disabled']               = 'Вимкнено';

// Buttons
$_['button_save']                = 'Зберегти';
$_['button_cancel']              = 'Скасувати';
$_['button_add']                 = 'Додати';
$_['button_delete']              = 'Видалити';
$_['button_edit']                = 'Редагувати';
$_['button_mark_read']           = 'Прочитано';
$_['button_mark_unread']         = 'Непрочитано';

// Filters
$_['filter_type']                = 'Тип';
$_['filter_status']              = 'Статус';
$_['filter_read']                = 'Статус читання';
$_['status_read']                = 'Прочитані';
$_['status_unread']              = 'Непрочитані';

// Errors
$_['error_permission']           = 'Недостатньо прав для виконання цієї дії';
$_['error_name']                 = 'Вкажіть назву правила';
$_['error_entity_type']          = 'Виберіть тип сутності';
$_['error_entities']             = 'Виберіть хоча б одну сутність';
$_['error_no_conditions']        = 'Вкажіть хоча б одну умову доступу (групу або країну)';
