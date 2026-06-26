<?php
// Translater Pro | © 2026 oc-kit.com | https://oc-kit.com

$_['heading_title']        = 'oc-kit.com — Translater Pro';
$_['heading_title_simple'] = 'Translater Pro';

$_['text_home']            = 'Головна';
$_['text_extension']       = 'Доповнення';
$_['text_success']         = 'Налаштування збережено.';
$_['text_module_name']     = 'Translater Pro';

// Tabs
$_['tab_dashboard']        = 'Дашборд';
$_['tab_translate']        = 'Переклад';
$_['tab_logs']             = 'Журнал перекладів';
$_['tab_settings']         = 'Налаштування';

// Dashboard
$_['text_stats_title']     = 'Неперекладених записів';
$_['text_stats_hint']      = 'Кількість записів без перекладу для обраної пари мов';
$_['text_source_lang']     = 'Мова оригіналу';
$_['text_target_lang']     = 'Цільова мова';
$_['button_refresh_stats'] = 'Оновити статистику';

// Content types
$_['text_type_product']      = 'Товари';
$_['text_type_category']     = 'Категорії';
$_['text_type_manufacturer'] = 'Бренди / Виробники';
$_['text_type_article']      = 'Статті блогу';
$_['text_type_blog_category']= 'Категорії блогу';

// Translate tab
$_['text_select_type']         = 'Тип контенту';
$_['entry_overwrite']          = 'Перезаписувати наявні';
$_['text_items_to_translate']  = 'Записи для перекладу';
$_['text_loading']             = 'Завантаження…';
$_['text_no_results']          = 'Всі записи перекладено або немає даних.';
$_['text_select_items']        = 'Оберіть хоча б один запис.';
$_['text_translating']         = 'Перекладаю…';
$_['text_done']                = 'Готово!';
$_['text_error']               = 'Помилка';
$_['text_all_translated']      = 'Всі вибрані записи перекладено!';
$_['text_progress']            = 'Прогрес';
$_['text_page']                = 'Сторінка';
$_['text_of']                  = 'з';
$_['text_total']               = 'Всього';
$_['text_per_page']            = 'На сторінці';

$_['button_translate_selected']= 'Перекласти вибрані';
$_['button_translate_all']     = 'Перекласти всі';
$_['button_load']              = 'Завантажити';
$_['button_reset_prompt']      = 'Скинути до типового';
$_['button_prev_page']         = 'Попередня';
$_['button_next_page']         = 'Наступна';

// Table columns
$_['column_id']       = 'ID';
$_['column_name']     = 'Назва';
$_['column_fields']   = 'Поля';
$_['column_preview']  = 'Фрагмент';
$_['column_status']   = 'Статус';
$_['column_type']     = 'Тип';
$_['column_item']     = 'Запис';
$_['column_source']   = 'З мови';
$_['column_target']   = 'В мову';
$_['column_provider'] = 'API';
$_['column_error']    = 'Помилка';
$_['column_date']     = 'Дата';

// Logs tab
$_['text_no_logs']              = 'Журнал перекладів порожній.';
$_['text_confirm_clear_logs']   = 'Очистити всі записи журналу?';
$_['button_clear_logs']         = 'Очистити журнал';
$_['button_log_all']            = 'Всі';
$_['button_log_errors']         = 'Лише помилки';

// Settings tab — General
$_['entry_status']              = 'Модуль увімкнено';

// Settings tab — API
$_['text_api_section']          = 'API провайдер';
$_['entry_api_provider']        = 'Провайдер перекладу';
$_['entry_openai_key']          = 'OpenAI API Key';
$_['entry_openai_model']        = 'OpenAI Модель';
$_['entry_deepseek_key']        = 'DeepSeek API Key';
$_['entry_deepseek_model']      = 'DeepSeek Модель';
$_['entry_gemini_key']          = 'Gemini API Key';
$_['entry_gemini_model']        = 'Gemini Модель';
$_['entry_prompt']              = 'Промт для AI (повний)';
$_['help_prompt']               = 'Це повний системний промт, що відправляється до AI. Плейсхолдери {source} і {target} замінюються назвами мов при перекладі. Можна повністю налаштувати під свої потреби.';

// Settings tab — Cron
$_['text_cron_section']         = 'Автоматичний переклад (Крон)';
$_['entry_cron_auto']           = 'Автоматичний переклад';
$_['entry_cron_source_lang']    = 'Мова оригіналу (крон)';
$_['entry_cron_target_langs']   = 'Цільові мови (крон)';
$_['entry_cron_types']          = 'Типи контенту (крон)';
$_['entry_cron_batch']          = 'Записів за запуск';
$_['help_cron_batch']           = 'Кількість записів для перекладу за один запуск крону (на тип).';
$_['help_cron_multiselect']     = 'Утримуйте Ctrl для вибору кількох.';
$_['text_cron_command']         = 'Команда для crontab';
$_['help_cron_command']         = 'Команди генеруються автоматично на основі обраних налаштувань вище. Оберіть мови та типи — команди оновляться.';

$_['button_save']               = 'Зберегти';
$_['button_cancel']             = 'Скасувати';

$_['error_permission']          = 'Недостатньо прав для зміни модуля Translater Pro!';

// License
$_['tab_license']                = 'Ліцензія';
$_['entry_license_key']          = 'Ліцензійний ключ';
$_['button_activate']            = 'Активувати';
$_['text_license_active']        = 'Ліцензія активна';
$_['text_license_trial']         = 'Пробний період: %s дн.';
$_['text_license_invalid']       = 'Невалідний ключ';
$_['text_license_expired']       = 'Ліцензія прострочена';
$_['text_license_no_key']        = 'Ключ не введено';
$_['text_license_grace']         = 'Відновлення зв\'язку...';
$_['text_license_api_error']     = 'Не вдається зв\'язатися з сервером активації';
$_['text_license_not_validated'] = 'Невалідний ключ';
$_['text_license_buy']           = 'Придбати ліцензію';
$_['text_license_version']       = 'Версія';
$_['text_license_domain']        = 'Домен';
$_['text_license_activated']     = 'Ліцензію успішно активовано!';
$_['text_license_error']         = 'Помилка активації. Перевірте ключ і спробуйте ще раз.';
$_['js_license_activating']      = 'Активація...';
