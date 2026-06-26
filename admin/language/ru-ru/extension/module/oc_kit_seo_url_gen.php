<?php
/**
 * SEO URL Generator — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title']        = 'oc-kit.com — SEO URL Generator';
$_['heading_title_simple'] = 'SEO URL Generator';

// Breadcrumb / nav
$_['text_home']      = 'Главная';
$_['text_extension'] = 'Дополнения';

// Tabs
$_['tab_dashboard'] = 'Генерация';
$_['tab_masks']     = 'Маски';
$_['tab_logs']      = 'Журнал';
$_['tab_cron']      = 'Крон';
$_['tab_selective'] = 'Выборочная';

// Dashboard
$_['text_stats_title']       = 'Статистика';
$_['text_generate_settings'] = 'Параметры генерации';
$_['text_copy']              = 'Копировать';
$_['text_copied']            = 'Скопировано!';
$_['text_total']         = 'Всего';
$_['text_with_seo_url']  = 'Сгенерировано SEO URL';
$_['text_remaining']     = 'Ещё не сгенерировано';
$_['text_with_meta']     = 'Meta Title';
$_['text_generate_btn']  = 'Генерировать';
$_['text_generating']    = 'Генерация...';
$_['text_done']          = 'Готово!';
$_['text_generated']     = 'Сгенерировано';
$_['text_skipped']       = 'Пропущено';
$_['text_errors']        = 'Ошибок';
$_['text_loading']       = 'Загрузка...';
$_['text_progress']      = 'Прогресс';
$_['text_type_progress'] = 'Тип';

// Type labels
$_['type_product']      = 'Товары';
$_['type_category']     = 'Категории';
$_['type_manufacturer'] = 'Производители';
$_['type_information']  = 'Информация';
$_['type_article']      = 'Блог (статьи)';
$_['type_blog_category']= 'Блог (категории)';

// Settings / Masks tab
$_['text_settings_title']  = 'Настройки';
$_['entry_status']         = 'Статус';
$_['entry_overwrite']      = 'Перезаписывать существующие';
$_['entry_batch_size']     = 'Размер пакета';
$_['entry_active_types']   = 'Типы контента';
$_['entry_active_fields']  = 'Поля для генерации';
$_['text_masks_title']     = 'Маски по типам';
$_['entry_seo_url']        = 'SEO URL';
$_['entry_meta_title']     = 'Meta Title';
$_['entry_meta_description']= 'Meta Description';
$_['entry_meta_keyword']   = 'Meta Keywords';
$_['entry_meta_h1']        = 'H1';
$_['text_available_tags']  = 'Доступные теги';
$_['help_masks']           = 'Используйте теги {name}, {model}, {sku}, {category}, {manufacturer}, {price} в масках';

// Fields checkboxes
$_['field_seo_url']         = 'SEO URL';
$_['field_meta_title']      = 'Meta Title';
$_['field_meta_description']= 'Meta Description';
$_['field_meta_keyword']    = 'Meta Keywords';
$_['field_meta_h1']         = 'H1';

// Logs tab
$_['text_logs_title']   = 'Журнал ошибок';
$_['text_log_type']     = 'Тип';
$_['text_log_item']     = 'ID';
$_['text_log_lang']     = 'Язык';
$_['text_log_field']    = 'Поле';
$_['text_log_message']  = 'Сообщение';
$_['text_log_date']     = 'Дата';
$_['text_no_logs']      = 'Ошибок нет';
$_['button_clear_logs'] = 'Очистить журнал';
$_['text_logs_cleared'] = 'Журнал очищен';
$_['text_confirm_clear']= 'Очистить журнал ошибок?';

// Cron tab
$_['text_cron_title']        = 'Крон-задание';
$_['text_cron_help']         = 'Добавьте в crontab для автоматической генерации SEO URL:';
$_['text_cron_note']         = 'Запускать раз в сутки или после массового импорта товаров.';
$_['text_cron_types_title']  = 'Типы контента для крона';
$_['text_cron_fields_title'] = 'Поля для крона';
$_['entry_cron_batch']       = 'Элементов за раз';

// Buttons
$_['button_save']   = 'Сохранить';
$_['button_cancel'] = 'Назад';

// Selective generation tab
$_['text_selective_title']           = 'Выборочная генерация';
$_['entry_selective_type']           = 'Тип контента';
$_['entry_selective_ids']            = 'ID элементов';
$_['help_selective_ids']             = 'Через запятую: 1, 5, 53. Необязательно если выбраны производители или категории.';
$_['text_selective_mask_override']   = 'Маски (необязательно)';
$_['help_selective_mask_override']   = 'Оставьте пустым — использовать сохранённую маску';
$_['entry_selective_overwrite']      = 'Перезаписывать';
$_['text_selective_run']             = 'Выполнить';
$_['text_selective_running']         = 'Выполняется...';
$_['error_selective_ids_required']   = 'Укажите ID, производителей или категории для выборочной генерации.';
$_['entry_selective_manufacturers']  = 'Производители';
$_['entry_selective_categories']     = 'Категории';
$_['text_sel_manufacturer_ph']       = 'Поиск производителя...';
$_['text_sel_category_ph']           = 'Поиск категории...';
$_['help_selective_product_filters'] = 'Фильтры для товаров. Если заданы оба — пересечение (товары, соответствующие производителю И категории).';
$_['text_sel_no_results']            = 'Ничего не найдено';

// Messages
$_['text_success_save'] = 'Настройки сохранены';
$_['text_error']        = 'Произошла ошибка';
$_['error_permission']  = 'Недостаточно прав для изменения модуля SEO URL Generator';
