<?php
// Cron Manager | © 2026 oc-kit.com | https://oc-kit.com

$_['heading_title']        = 'oc-kit.com — Cron Manager';
$_['heading_title_simple'] = 'Cron Manager';
$_['text_module_name']     = 'Cron Manager';
$_['text_home']            = 'Главная';
$_['text_extension']       = 'Дополнения';
$_['text_success']         = 'Задача сохранена.';
$_['text_success_delete']  = 'Задача удалена.';

$_['text_no_jobs']         = 'Задач ещё нет. Нажмите «Добавить задачу».';
$_['column_name']          = 'Название';
$_['column_type']          = 'Тип';
$_['column_command']       = 'Команда / URL';
$_['column_schedule']      = 'Расписание';
$_['column_last_run']      = 'Последний запуск';
$_['column_next_run']      = 'Следующий запуск';
$_['column_status']        = 'Статус';
$_['column_enabled']       = 'Активна';
$_['column_actions']       = 'Действия';
$_['column_date']          = 'Дата';
$_['column_duration']      = 'Длительность';
$_['column_output']        = 'Вывод';
$_['column_triggered_by']  = 'Кем запущено';

$_['text_type_php']        = 'PHP';
$_['text_type_shell']      = 'Shell';
$_['text_type_url']        = 'URL';

$_['text_status_never']    = 'Не запускалась';
$_['text_status_success']  = 'Успешно';
$_['text_status_error']    = 'Ошибка';
$_['text_status_running']  = 'Выполняется';

$_['text_triggered_scheduler'] = 'Планировщик';
$_['text_triggered_manual']    = 'Вручную';

$_['entry_name']           = 'Название задачи';
$_['entry_description']    = 'Описание';
$_['entry_type']           = 'Тип';
$_['entry_command']        = 'Команда';
$_['entry_schedule']       = 'Расписание (cron)';
$_['entry_timeout']        = 'Таймаут (сек)';
$_['entry_status']         = 'Активна';
$_['help_schedule']        = 'Формат: мин час д.мес мес д.нед  |  Пример: <code>0 2 * * *</code> — ежедневно в 2:00';
$_['help_command_php']     = 'Абсолютный путь к PHP файлу, например: <code>/var/www/site/crons/cron_notify.php</code>';
$_['help_command_shell']   = 'Shell-команда, например: <code>/bin/bash /path/to/script.sh</code>';
$_['help_command_url']     = 'URL для GET-запроса, например: <code>https://site.com/cron?token=xxx</code>';

$_['button_add']           = 'Добавить задачу';
$_['button_scan']          = 'Сканировать /crons/';
$_['button_save']          = 'Сохранить';
$_['button_cancel']        = 'Отмена';
$_['button_run']           = 'Запустить';
$_['button_logs']          = 'Логи';
$_['button_edit']          = 'Редактировать';
$_['button_delete']        = 'Удалить';
$_['button_clear_logs']    = 'Очистить логи';

$_['text_running']         = 'Выполняется…';
$_['text_run_output']      = 'Результат выполнения';
$_['text_no_logs']         = 'Журнал пуст.';
$_['text_loading']         = 'Загрузка…';
$_['text_ms']              = 'мс';
$_['text_sec']             = 'сек';

$_['text_scan_title']      = 'Найденные файлы';
$_['text_scan_none']       = 'Новых файлов в /crons/ не найдено.';

$_['text_schedule_next']   = 'Следующий запуск:';
$_['text_schedule_invalid']= 'Неверное выражение cron';

$_['text_confirm_delete']  = 'Удалить задачу?';
$_['text_confirm_run']     = 'Запустить задачу сейчас?';

$_['text_cron_setup']      = 'Системный cron (запускать каждую минуту):';

// Cron presets
$_['text_cron_every_min']   = 'Каждую минуту';
$_['text_cron_every_5min']  = 'Каждые 5 мин';
$_['text_cron_every_15min'] = 'Каждые 15 мин';
$_['text_cron_hourly']      = 'Ежечасно';
$_['text_cron_daily_2am']   = 'Ежедневно в 02:00';
$_['text_cron_weekly']      = 'Еженедельно (Вс)';
$_['text_cron_monthly']     = 'Ежемесячно (1)';

$_['error_permission']       = 'Недостаточно прав для управления Cron Manager!';
$_['error_name_required']    = 'Укажите название задачи.';
$_['error_command_required'] = 'Укажите команду или URL.';
$_['error_schedule_invalid'] = 'Неверный формат cron-выражения.';
