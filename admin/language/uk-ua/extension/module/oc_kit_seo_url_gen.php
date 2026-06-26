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
$_['text_home']      = 'Головна';
$_['text_extension'] = 'Доповнення';

// Tabs
$_['tab_dashboard'] = 'Генерація';
$_['tab_masks']     = 'Маски';
$_['tab_logs']      = 'Журнал';
$_['tab_cron']      = 'Крон';
$_['tab_selective'] = 'Вибіркова';

// Dashboard
$_['text_stats_title']       = 'Статистика';
$_['text_generate_settings'] = 'Параметри генерації';
$_['text_copy']              = 'Копіювати';
$_['text_copied']            = 'Скопійовано!';
$_['text_total']         = 'Всього';
$_['text_with_seo_url']  = 'Згенеровано SEO URL';
$_['text_remaining']     = 'Ще не згенеровано';
$_['text_with_meta']     = 'Meta Title';
$_['text_generate_btn']  = 'Генерувати';
$_['text_generating']    = 'Генерація...';
$_['text_done']          = 'Готово!';
$_['text_generated']     = 'Згенеровано';
$_['text_skipped']       = 'Пропущено';
$_['text_errors']        = 'Помилок';
$_['text_loading']       = 'Завантаження...';
$_['text_progress']      = 'Прогрес';
$_['text_type_progress'] = 'Тип';

// Type labels
$_['type_product']      = 'Товари';
$_['type_category']     = 'Категорії';
$_['type_manufacturer'] = 'Виробники';
$_['type_information']  = 'Інформація';
$_['type_article']      = 'Блог (статті)';
$_['type_blog_category']= 'Блог (категорії)';

// Settings / Masks tab
$_['text_settings_title']  = 'Налаштування';
$_['entry_status']         = 'Статус';
$_['entry_overwrite']      = 'Перезаписувати існуючі';
$_['entry_batch_size']     = 'Розмір пакету';
$_['entry_active_types']   = 'Типи контенту';
$_['entry_active_fields']  = 'Поля для генерації';
$_['text_masks_title']     = 'Маски за типами';
$_['entry_seo_url']        = 'SEO URL';
$_['entry_meta_title']     = 'Meta Title';
$_['entry_meta_description']= 'Meta Description';
$_['entry_meta_keyword']   = 'Meta Keywords';
$_['entry_meta_h1']        = 'H1';
$_['text_available_tags']  = 'Доступні теги';
$_['help_masks']           = 'Використовуйте теги {name}, {model}, {sku}, {category}, {manufacturer}, {price} у масках';

// Fields checkboxes
$_['field_seo_url']         = 'SEO URL';
$_['field_meta_title']      = 'Meta Title';
$_['field_meta_description']= 'Meta Description';
$_['field_meta_keyword']    = 'Meta Keywords';
$_['field_meta_h1']         = 'H1';

// Logs tab
$_['text_logs_title']   = 'Журнал помилок';
$_['text_log_type']     = 'Тип';
$_['text_log_item']     = 'ID';
$_['text_log_lang']     = 'Мова';
$_['text_log_field']    = 'Поле';
$_['text_log_message']  = 'Повідомлення';
$_['text_log_date']     = 'Дата';
$_['text_no_logs']      = 'Помилок немає';
$_['button_clear_logs'] = 'Очистити журнал';
$_['text_logs_cleared'] = 'Журнал очищено';
$_['text_confirm_clear']= 'Очистити журнал помилок?';

// Cron tab
$_['text_cron_title']        = 'Крон-завдання';
$_['text_cron_help']         = 'Додайте до crontab для автоматичної генерації SEO URL:';
$_['text_cron_note']         = 'Запускати раз на добу або після масового імпорту товарів.';
$_['text_cron_types_title']  = 'Типи контенту для крону';
$_['text_cron_fields_title'] = 'Поля для крону';
$_['entry_cron_batch']       = 'Елементів за раз';

// Buttons
$_['button_save']   = 'Зберегти';
$_['button_cancel'] = 'Назад';

// Selective generation tab
$_['text_selective_title']           = 'Вибіркова генерація';
$_['entry_selective_type']           = 'Тип контенту';
$_['entry_selective_ids']            = 'ID елементів';
$_['help_selective_ids']             = 'Через кому: 1, 5, 53. Необов\'язково якщо обрані виробники або категорії.';
$_['text_selective_mask_override']   = 'Маски (необов\'язково)';
$_['help_selective_mask_override']   = 'Залиште порожнім — використати збережену маску';
$_['entry_selective_overwrite']      = 'Перезаписувати';
$_['text_selective_run']             = 'Виконати';
$_['text_selective_running']         = 'Виконується...';
$_['error_selective_ids_required']   = 'Вкажіть ID, виробників або категорії для вибіркової генерації.';
$_['entry_selective_manufacturers']  = 'Виробники';
$_['entry_selective_categories']     = 'Категорії';
$_['text_sel_manufacturer_ph']       = 'Пошук виробника...';
$_['text_sel_category_ph']           = 'Пошук категорії...';
$_['help_selective_product_filters'] = 'Фільтри для товарів. Якщо задано обидва — генерується перетин (товари, що відповідають виробнику І категорії).';
$_['text_sel_no_results']            = 'Нічого не знайдено';

// Messages
$_['text_success_save'] = 'Налаштування збережено';
$_['text_error']        = 'Сталася помилка';
$_['error_permission']  = 'Недостатньо прав для змін у модулі SEO URL Generator';
