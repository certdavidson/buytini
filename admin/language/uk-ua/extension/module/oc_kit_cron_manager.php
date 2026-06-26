<?php
// Cron Manager | © 2026 oc-kit.com | https://oc-kit.com

$_['heading_title']        = 'oc-kit.com — Cron Manager';
$_['heading_title_simple'] = 'Cron Manager';
$_['text_module_name']     = 'Cron Manager';
$_['text_home']            = 'Головна';
$_['text_extension']       = 'Доповнення';
$_['text_success']         = 'Задачу збережено.';
$_['text_success_delete']  = 'Задачу видалено.';

// Table
$_['text_no_jobs']         = 'Задач ще немає. Натисніть «Додати задачу».';
$_['column_name']          = 'Назва';
$_['column_type']          = 'Тип';
$_['column_command']       = 'Команда / URL';
$_['column_schedule']      = 'Розклад';
$_['column_last_run']      = 'Останній запуск';
$_['column_next_run']      = 'Наступний запуск';
$_['column_status']        = 'Статус';
$_['column_enabled']       = 'Активна';
$_['column_actions']       = 'Дії';
$_['column_date']          = 'Дата';
$_['column_duration']      = 'Тривалість';
$_['column_output']        = 'Вивід';
$_['column_triggered_by']  = 'Ким запущено';

// Types
$_['text_type_php']        = 'PHP';
$_['text_type_shell']      = 'Shell';
$_['text_type_url']        = 'URL';

// Statuses
$_['text_status_never']    = 'Не запускалась';
$_['text_status_success']  = 'Успішно';
$_['text_status_error']    = 'Помилка';
$_['text_status_running']  = 'Виконується';

// Triggered by
$_['text_triggered_scheduler'] = 'Планувальник';
$_['text_triggered_manual']    = 'Вручну';

// Form
$_['entry_name']           = 'Назва задачі';
$_['entry_description']    = 'Опис';
$_['entry_type']           = 'Тип';
$_['entry_command']        = 'Команда';
$_['entry_schedule']       = 'Розклад (cron)';
$_['entry_timeout']        = 'Таймаут (сек)';
$_['entry_status']         = 'Активна';
$_['help_schedule']        = 'Формат: хв год д.міс міс д.тиж  |  Приклад: <code>0 2 * * *</code> — щодня о 2:00';
$_['help_command_php']     = 'Абсолютний шлях до PHP файлу, наприклад: <code>/var/www/site/crons/cron_notify.php</code>';
$_['help_command_shell']   = 'Shell-команда, наприклад: <code>/bin/bash /path/to/script.sh</code>';
$_['help_command_url']     = 'URL для GET-запиту, наприклад: <code>https://site.com/cron?token=xxx</code>';

// Buttons
$_['button_add']           = 'Додати задачу';
$_['button_scan']          = 'Сканувати /crons/';
$_['button_save']          = 'Зберегти';
$_['button_cancel']        = 'Скасувати';
$_['button_run']           = 'Запустити';
$_['button_logs']          = 'Логи';
$_['button_edit']          = 'Редагувати';
$_['button_delete']        = 'Видалити';
$_['button_clear_logs']    = 'Очистити логи';

// Run / Logs
$_['text_running']         = 'Виконується…';
$_['text_run_output']      = 'Результат виконання';
$_['text_no_logs']         = 'Журнал порожній.';
$_['text_loading']         = 'Завантаження…';
$_['text_ms']              = 'мс';
$_['text_sec']             = 'сек';

// Scan
$_['text_scan_title']      = 'Знайдено файли';
$_['text_scan_none']       = 'Нових файлів у /crons/ не знайдено.';

// Schedule preview
$_['text_schedule_next']   = 'Наступний запуск:';
$_['text_schedule_invalid']= 'Невірний вираз cron';

// Confirm
$_['text_confirm_delete']  = 'Видалити задачу?';
$_['text_confirm_run']     = 'Запустити задачу зараз?';

// Cron setup
$_['text_cron_setup']      = 'Системний cron (запускати щохвилини):';

// Cron presets
$_['text_cron_every_min']   = 'Щохвилини';
$_['text_cron_every_5min']  = 'Кожні 5 хв';
$_['text_cron_every_15min'] = 'Кожні 15 хв';
$_['text_cron_hourly']      = 'Щогодини';
$_['text_cron_daily_2am']   = 'Щодня о 02:00';
$_['text_cron_weekly']      = 'Щотижня (Нд)';
$_['text_cron_monthly']     = 'Щомісяця (1)';

// Errors
$_['error_permission']       = 'Недостатньо прав для керування Cron Manager!';
$_['error_name_required']    = 'Вкажіть назву задачі.';
$_['error_command_required'] = 'Вкажіть команду або URL.';
$_['error_schedule_invalid'] = 'Невірний формат cron-виразу.';
