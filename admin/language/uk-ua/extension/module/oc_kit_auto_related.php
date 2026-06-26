<?php
/**
 * Auto Related Products — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title']             = 'oc-kit.com — Auto Related Products';

// Breadcrumb
$_['text_home']                 = 'Головна';
$_['text_extension']            = 'Розширення';
$_['text_settings']             = 'Налаштування';
$_['text_success']              = 'Налаштування збережено!';

// Buttons
$_['button_save']               = 'Зберегти';
$_['button_cancel']             = 'Скасувати';

// Tabs
$_['tab_general']               = 'Загальне';
$_['tab_weights']               = 'Сигнали схожості';
$_['tab_performance']           = 'Продуктивність';
$_['tab_generate']              = 'Генерація';
$_['tab_stats']                 = 'Статистика';
$_['tab_rules']                 = 'Правила блоків';

// General
$_['entry_status']              = 'Статус';
$_['entry_related_limit']       = 'Кількість рекомендованих';
$_['entry_overwrite']           = 'Перезаписувати існуючі';
$_['entry_on_visit']            = 'Генерувати при відвідуванні';
$_['entry_visit_mode']          = 'Режим генерації';
$_['entry_visit_mode_async']    = 'Асинхронний (JS fetch, не блокує рендер)';
$_['entry_visit_mode_sync']     = 'Синхронний (рекомендовані одразу в HTML)';
$_['entry_exclude_oos']         = 'Виключати товари з кількістю < 1';
$_['entry_exclude_disabled']    = 'Виключати вимкнені товари';
$_['entry_cache']               = 'Кешування';
$_['entry_cache_ttl']           = 'Час життя кешу (год.)';

// Weights
$_['entry_weight_category']     = 'Категорія';
$_['entry_weight_name']         = 'Назва';
$_['entry_weight_neighbor_id']  = 'Сусідні ID';
$_['entry_weight_fields']       = 'Поля (MPN/SKU/…)';
$_['entry_weight_manufacturer'] = 'Виробник';
$_['entry_weight_attributes']   = 'Характеристики';
$_['entry_weight_coorders']     = 'Часто замовляють разом';
$_['entry_weight_price_range']  = 'Діапазон цін';

$_['entry_neighbor_enabled']    = 'Враховувати сусідні ID';
$_['entry_neighbor_range']      = 'Діапазон сусідніх ID (±N)';
$_['entry_field_list']          = 'Поля для порівняння';
$_['entry_field_separator']     = 'Роздільник значень у полі';
$_['entry_attribute_ids']       = 'Характеристики для порівняння';
$_['entry_attribute_min_match'] = 'Мін. збіг характеристик';
$_['entry_coorders_days']       = 'Замовлення за N днів';
$_['entry_coorders_min']        = 'Мін. спільних замовлень';
$_['entry_coorders_statuses']   = 'Статуси замовлень';

// Price range signal
$_['entry_price_range_pct']     = 'Макс. відхилення ціни (%)';
$_['text_price_range_pct_help'] = 'Товари, ціна яких відрізняється більше ніж на цей % від ціни вихідного товару, отримують оцінку 0. Наприклад, 20 означає ±20%.';

// Result sort & only_special (global)
$_['entry_result_sort']         = 'Порядок відображення';
$_['entry_result_sort_score']   = 'За оцінкою (найбільш схожі першими)';
$_['entry_result_sort_random']  = 'Випадково';
$_['entry_result_sort_price_asc']  = 'Ціна: від меншої до більшої';
$_['entry_result_sort_price_desc'] = 'Ціна: від більшої до меншої';
$_['entry_result_sort_new']     = 'Спочатку новіші';
$_['entry_result_sort_name']    = 'За алфавітом';
$_['entry_only_special']        = 'Тільки товари зі знижкою';
$_['text_only_special_help']    = 'Якщо увімкнено, рекомендуватимуться лише товари з активною акційною ціною.';

// Brand priority & blacklist
$_['entry_brand_priority']      = 'Спочатку той самий бренд';
$_['text_brand_priority_help']  = 'Якщо увімкнено, товари того самого виробника переміщуються на початок списку (після скорингу).';
$_['entry_blacklist_products']  = 'Виключити товари';
$_['entry_blacklist_categories']= 'Виключити категорії';
$_['text_blacklist_help']       = 'Товари, що відповідають цим критеріям, ніколи не потраплять до списку рекомендованих.';

// Preview
$_['tab_preview']               = 'Прев\'ю';
$_['text_preview_product']      = 'Введіть назву товару…';
$_['button_preview']            = 'Переглянути';
$_['text_preview_results']      = 'Результати скорингу (тестовий запуск, нічого не зберігається)';
$_['column_preview_score']      = 'Оцінка';
$_['text_preview_empty']        = 'Кандидатів не знайдено';
$_['text_no_results']           = 'Немає результатів';

// Inline help texts
$_['text_weights_help']         = 'Сума не обов\'язково 100 — ваги нормалізуються автоматично. 0 = сигнал ігнорується.';
$_['text_field_separator_help'] = 'Наприклад: кома (,) або крапка з комою (;). Порожньо — точний збіг.';
$_['text_coorders_statuses_help']= 'Порожньо = враховувати всі статуси замовлень.';

// Field names
$_['field_sku']                 = 'SKU';
$_['field_mpn']                 = 'MPN';
$_['field_ean']                 = 'EAN';
$_['field_jan']                 = 'JAN';
$_['field_isbn']                = 'ISBN';
$_['field_upc']                 = 'UPC';

// Performance
$_['entry_candidate_limit']     = 'Макс. кандидатів для scoring';
$_['text_candidate_limit_help'] = 'Розмір пулу передфільтрації перед повним скорингом. Менше — швидше; більше — точніше на великих каталогах. Рекомендується: 500–2000.';

// Generate
$_['entry_id_from']             = 'ID від';
$_['entry_id_to']               = 'ID до';
$_['entry_gen_categories']      = 'Категорії';
$_['entry_gen_manufacturers']   = 'Виробники';
$_['entry_gen_overwrite']       = 'Перезаписати існуючі';
$_['button_generate']           = 'Генерувати';
$_['button_stop']               = 'Зупинити';
$_['text_processed']            = 'Оброблено';
$_['text_of']                   = 'з';
$_['text_generating']           = 'Генерація…';
$_['text_done']                 = 'Готово!';

// Stats
$_['text_total_products']       = 'Всього товарів';
$_['text_with_related']         = 'З рекомендованими';
$_['text_coverage']             = 'Покриття';
$_['text_without_related']      = 'Без рекомендованих';
$_['text_recent_generated']     = 'Нещодавно згенеровані';
$_['column_product']            = 'Товар';
$_['column_generated_at']       = 'Дата';
$_['column_source']             = 'Джерело';
$_['column_count']              = 'К-сть';
$_['source_cron']               = 'Крон';
$_['source_visit']              = 'Відвідування';
$_['source_manual']             = 'Вручну';

// Cron
$_['text_cron']                 = 'Завдання Cron';
$_['text_cron_command']         = 'Команда';
$_['text_cron_schedule']        = 'Розклад';
$_['text_cron_daily_2']         = 'Щодня о 02:00';
$_['text_cron_daily_3']         = 'Щодня о 03:00';
$_['text_cron_daily_4']         = 'Щодня о 04:00';
$_['text_cron_every_6h']        = 'Кожні 6 годин';
$_['text_cron_every_1h']        = 'Щогодини';
$_['text_cron_all']             = 'всі';
$_['text_cron_param_limit']     = 'Товарів за запуск';
$_['text_cron_param_force']     = 'Примусова регенерація';
$_['text_cron_param_category']  = 'Категорії';
$_['text_cron_param_mf']        = 'Виробники';

// Preset scenarios
$_['text_presets']              = 'Пресети';
$_['text_preset_balanced']      = 'Збалансований';
$_['text_preset_coorders']      = 'Акцент на спільних замовленнях';
$_['text_preset_category']      = 'Та сама категорія';
$_['text_preset_variants']      = 'Варіанти товару';
$_['text_preset_help']          = 'Натисніть на пресет щоб заповнити повзунки ваг. Потім можна відкоригувати і зберегти.';

// Rule Builder
$_['tab_rules']                      = 'Блоки правил';
$_['text_rules_intro']               = 'Блоки на основі правил відображаються поряд із стандартними рекомендаціями. Кожне правило — конструктор: вкажіть ДЕ показувати блок (умови джерела) і ЩО показувати (умови цілі).';
$_['button_add_rule']                = 'Додати правило';
$_['button_edit_rule']               = 'Редагувати';
$_['button_delete_rule']             = 'Видалити';
$_['button_save_rule']               = 'Зберегти правило';
$_['button_cancel_rule']             = 'Скасувати';
$_['column_rule_name']               = 'Назва';
$_['column_rule_source']             = 'Де показувати';
$_['column_rule_target']             = 'Що показувати';
$_['column_rule_sort']               = 'Порядок';
$_['column_rule_status']             = 'Статус';
$_['column_rule_actions']            = 'Дії';
$_['entry_rule_name']                = 'Назва правила';
$_['entry_rule_status']              = 'Статус';
$_['entry_rule_sort_order']          = 'Порядок сортування';
$_['entry_rule_block_title']         = 'Заголовок блоку';
$_['entry_rule_result_limit']        = 'Кількість товарів';
$_['entry_rule_result_sort']         = 'Сортування';
$_['entry_result_sort_bestseller']   = 'Бестселери';

// Rule constructor — source conditions (ДЕ показувати)
$_['text_source_conditions']         = 'Де показувати';
$_['text_source_conditions_help']    = 'Блок з\'явиться на сторінках, де виконуються ВСІ перелічені умови. Без умов — показувати на всіх сторінках товарів.';
$_['button_add_source_cond']         = '+ Додати умову';
$_['cond_src_category']              = 'Категорія';
$_['cond_src_manufacturer']          = 'Бренд';
$_['cond_src_attribute']             = 'Значення атрибута';
$_['cond_src_name_contains']         = 'Назва містить';

// Rule constructor — target conditions (ЩО показувати)
$_['text_target_conditions']         = 'Що показувати';
$_['text_target_conditions_help']    = 'Товари мають відповідати ВСІМ перерахованим умовам. Комбінуйте довільно.';
$_['button_add_target_cond']         = '+ Додати умову';
$_['cond_tgt_same_category']         = 'Та сама категорія';
$_['cond_tgt_same_manufacturer']     = 'Той самий бренд';
$_['cond_tgt_category']              = 'Конкретні категорії';
$_['cond_tgt_manufacturer']          = 'Конкретні бренди';
$_['cond_tgt_attribute']             = 'Атрибут = значення';
$_['cond_tgt_dynamic_attribute']     = 'Той самий атрибут';
$_['cond_tgt_name_contains']         = 'Назва містить';
$_['cond_tgt_price_range']           = 'Діапазон ціни ±%';
$_['cond_tgt_only_special']          = 'Тільки акційні';
$_['cond_tgt_exclude_oos']           = 'Тільки в наявності';
$_['cond_tgt_brand_priority']        = 'Спочатку той самий бренд';

// Condition field labels / hints
$_['entry_cond_attribute_id']        = 'Атрибут';
$_['entry_cond_attribute_value']     = 'Значення';
$_['entry_cond_price_pct']           = '±%';
$_['entry_cond_name_text']           = 'Текст';
$_['entry_cond_ids_placeholder']     = 'Пошук…';
$_['text_cond_same_cat_help']        = 'Товари з тієї самої категорії, що й поточний товар';
$_['text_cond_same_mf_help']         = 'Товари того самого бренду, що й поточний товар';
$_['text_cond_dyn_attr_help']        = 'Відповідає товарам із таким самим значенням цього атрибута (наприклад, тип цоколя E27)';
$_['text_cond_brand_priority_help']  = 'Товари того самого бренду будуть першими в блоці';
$_['text_cond_only_special_help']    = 'Тільки товари з активною акційною ціною';
$_['text_cond_exclude_oos_help']     = 'Виключити товари з нульовим залишком';

$_['text_no_rules']                  = 'Правил ще немає. Натисніть «Додати правило».';
$_['confirm_delete_rule']            = 'Видалити це правило?';
$_['text_rule_saved']                = 'Правило збережено.';
$_['text_rule_deleted']              = 'Правило видалено.';

// Errors
$_['error_permission']          = 'Недостатньо прав для зміни налаштувань!';

// License
$_['tab_license']               = 'Ліцензія';
$_['entry_license_key']         = 'Ліцензійний ключ';
$_['button_activate']           = 'Активувати';
$_['text_license_not_validated']= 'Ключ не введено';
$_['text_license_invalid']      = 'Ключ недійсний';
$_['text_license_active']       = 'Ліцензія активна';
$_['text_license_expired']      = 'Пробний період закінчився';
$_['text_license_trial']        = 'Пробний період: залишилось %d дн.';
$_['text_license_grace']        = 'Пільговий період (API недоступний)';
$_['text_license_api_error']    = 'Помилка перевірки ліцензії';
$_['text_license_version']      = 'Версія';
$_['text_license_buy']          = 'Придбати ліцензію';
