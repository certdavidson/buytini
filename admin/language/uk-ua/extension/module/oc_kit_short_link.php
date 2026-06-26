<?php
// Short Link | © 2026 oc-kit.com | https://oc-kit.com
$_['heading_title']        = 'oc-kit.com — Short Links';

$_['text_home']            = 'Головна';
$_['text_module_name']     = 'Короткі посилання';
$_['text_tab_links']       = 'Всі посилання';
$_['text_link_list']       = 'Список посилань';
$_['text_settings']        = 'Налаштування';
$_['text_success']         = 'Посилання оновлено';
$_['text_settings_saved']  = 'Налаштування збережено';
$_['text_no_results']      = 'Посилань не знайдено';
$_['text_total_links']     = 'посилань всього';
$_['text_missing_affiliate'] = 'вендорів без affiliate URL';
$_['text_open_link']       = 'Відкрити посилання';

$_['column_short_code']    = 'Код';
$_['column_target_url']    = 'Цільове посилання';
$_['column_vendor']        = 'Вендор';
$_['column_product']       = 'Продукт';
$_['column_clicks']        = 'Кліки';
$_['column_active']        = 'Активне';
$_['column_direct']        = 'Прямий редірект';
$_['column_type']          = 'Тип';
$_['column_action']        = 'Дія';

$_['type_vendor']          = 'Вендор';
$_['type_product']         = 'Продукт';
$_['type_custom']          = 'Власний';
$_['type_all']             = 'Всі типи';

$_['text_filter']          = 'Фільтр';
$_['text_filter_type']     = 'Тип';
$_['text_filter_active']   = 'Статус';
$_['text_filter_clicks']   = 'Мін. кліків';
$_['text_filter_url']      = 'Пошук по URL';
$_['text_filter_code']     = 'Пошук по коду';
$_['text_active_all']      = 'Всі';
$_['text_active_yes']      = 'Активні';
$_['text_active_no']       = 'Неактивні';
$_['button_filter']        = 'Застосувати';
$_['button_clear']         = 'Скинути';

$_['button_save']          = 'Зберегти';
$_['button_export']        = 'Експорт CSV';
$_['button_delete']        = 'Видалити';
$_['button_add_link']      = 'Додати посилання';
$_['button_add']           = 'Додати';
$_['button_delete_old']    = 'Видалити старі';

$_['text_confirm_delete']  = 'Видалити це посилання?';
$_['text_deleted']         = 'Посилання видалено';
$_['text_copy']            = 'Скопіювати код';
$_['text_copied']          = 'Скопійовано!';
$_['text_error_save']      = 'Помилка збереження';
$_['text_error_delete']    = 'Помилка видалення';
$_['text_add_success']     = 'Посилання додано';
$_['text_add_error']       = 'Помилка додавання посилання';
$_['text_extension']       = 'Доповнення';

// Add form
$_['entry_add_url']        = 'Введіть цільовий URL...';
$_['entry_add_direct']     = 'Прямий редірект';

// Settings
$_['text_tab_general']     = 'Загальне';
$_['text_tab_maintenance'] = 'Обслуговування';
$_['text_tab_docs']        = 'Документація';
$_['entry_status']         = 'Статус модуля';
$_['entry_show_generate']  = 'Кнопка генерації';
$_['help_show_generate']   = 'Показувати кнопку «Згенерувати коротке посилання» при редагуванні товарів, категорій, виробників та інформаційних сторінок';

// Maintenance
$_['help_delete_old']      = 'Видалення посилань, які не використовувались більше вказаної кількості днів (за датою створення).';
$_['text_days']            = 'днів';
$_['text_delete_old_success'] = 'Видалено %d посилань';

// Documentation
$_['text_doc_api_title']    = 'PHP API (виклик з іншого контролера)';
$_['text_doc_api_desc']     = 'Скоротити довільний URL можна з будь-якого admin-контролера через внутрішній виклик:';
$_['text_doc_nginx_title']  = 'Nginx — правило для /link/{code}';
$_['text_doc_nginx_desc']   = 'Додайте до конфігурації вашого сервера:';
$_['text_doc_htaccess_title'] = 'Apache (.htaccess) — правило для /link/{code}';
$_['text_doc_htaccess_desc']  = 'Додайте до файлу .htaccess перед рядком RewriteRule .* index.php:';

$_['error_url_required']   = 'Вкажіть цільовий URL';
$_['error_permission']     = 'Недостатньо прав для зміни модуля Short Link!';
