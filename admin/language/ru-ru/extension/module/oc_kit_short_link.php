<?php
// Short Link | © 2026 oc-kit.com | https://oc-kit.com
$_['heading_title']        = 'oc-kit.com — Short Links';

$_['text_home']            = 'Главная';
$_['text_module_name']     = 'Короткие ссылки';
$_['text_tab_links']       = 'Все ссылки';
$_['text_link_list']       = 'Список ссылок';
$_['text_settings']        = 'Настройки';
$_['text_success']         = 'Ссылка обновлена';
$_['text_settings_saved']  = 'Настройки сохранены';
$_['text_no_results']      = 'Ссылок не найдено';
$_['text_total_links']     = 'ссылок всего';
$_['text_missing_affiliate'] = 'вендоров без affiliate URL';
$_['text_open_link']       = 'Открыть ссылку';

$_['column_short_code']    = 'Код';
$_['column_target_url']    = 'Целевая ссылка';
$_['column_vendor']        = 'Вендор';
$_['column_product']       = 'Продукт';
$_['column_clicks']        = 'Клики';
$_['column_active']        = 'Активна';
$_['column_direct']        = 'Прямой редирект';
$_['column_type']          = 'Тип';
$_['column_action']        = 'Действие';

$_['type_vendor']          = 'Вендор';
$_['type_product']         = 'Продукт';
$_['type_custom']          = 'Свой';
$_['type_all']             = 'Все типы';

$_['text_filter']          = 'Фильтр';
$_['text_filter_type']     = 'Тип';
$_['text_filter_active']   = 'Статус';
$_['text_filter_clicks']   = 'Мин. кликов';
$_['text_filter_url']      = 'Поиск по URL';
$_['text_filter_code']     = 'Поиск по коду';
$_['text_active_all']      = 'Все';
$_['text_active_yes']      = 'Активные';
$_['text_active_no']       = 'Неактивные';
$_['button_filter']        = 'Применить';
$_['button_clear']         = 'Сбросить';

$_['button_save']          = 'Сохранить';
$_['button_export']        = 'Экспорт CSV';
$_['button_delete']        = 'Удалить';
$_['button_add_link']      = 'Добавить ссылку';
$_['button_add']           = 'Добавить';
$_['button_delete_old']    = 'Удалить старые';

$_['text_confirm_delete']  = 'Удалить эту ссылку?';
$_['text_deleted']         = 'Ссылка удалена';
$_['text_copy']            = 'Скопировать код';
$_['text_copied']          = 'Скопировано!';
$_['text_error_save']      = 'Ошибка сохранения';
$_['text_error_delete']    = 'Ошибка удаления';
$_['text_add_success']     = 'Ссылка добавлена';
$_['text_add_error']       = 'Ошибка добавления ссылки';

// Add form
$_['entry_add_url']        = 'Введите целевой URL...';
$_['entry_add_direct']     = 'Прямой редирект';

// Settings
$_['text_tab_general']     = 'Общее';
$_['text_tab_maintenance'] = 'Обслуживание';
$_['text_tab_docs']        = 'Документация';
$_['entry_status']         = 'Статус модуля';
$_['entry_show_generate']  = 'Кнопка генерации';
$_['help_show_generate']   = 'Показывать кнопку «Создать короткую ссылку» при редактировании товаров, категорий, производителей и информационных страниц';

// Maintenance
$_['help_delete_old']      = 'Удаление ссылок старше указанного количества дней (по дате создания).';
$_['text_days']            = 'дней';
$_['text_delete_old_success'] = 'Удалено %d ссылок';

// Documentation
$_['text_doc_api_title']    = 'PHP API (вызов из другого контроллера)';
$_['text_doc_api_desc']     = 'Сократить произвольный URL можно из любого admin-контроллера через внутренний вызов:';
$_['text_doc_nginx_title']  = 'Nginx — правило для /link/{code}';
$_['text_doc_nginx_desc']   = 'Добавьте в конфигурацию вашего сервера:';
$_['text_doc_htaccess_title'] = 'Apache (.htaccess) — правило для /link/{code}';
$_['text_doc_htaccess_desc']  = 'Добавьте в файл .htaccess перед строкой RewriteRule .* index.php:';

$_['error_url_required']   = 'Укажите целевой URL';
$_['error_permission']     = 'Недостаточно прав для изменения модуля Short Link!';
