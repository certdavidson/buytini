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
$_['text_home']                 = 'Главная';
$_['text_extension']            = 'Расширения';
$_['text_settings']             = 'Настройки';
$_['text_success']              = 'Настройки сохранены!';

// Buttons
$_['button_save']               = 'Сохранить';
$_['button_cancel']             = 'Отмена';

// Tabs
$_['tab_general']               = 'Общее';
$_['tab_weights']               = 'Сигналы схожести';
$_['tab_performance']           = 'Производительность';
$_['tab_generate']              = 'Генерация';
$_['tab_stats']                 = 'Статистика';
$_['tab_rules']                 = 'Блоки правил';

// General
$_['entry_status']              = 'Статус';
$_['entry_related_limit']       = 'Количество рекомендованных';
$_['entry_overwrite']           = 'Перезаписывать существующие';
$_['entry_on_visit']            = 'Генерировать при посещении';
$_['entry_visit_mode']          = 'Режим генерации';
$_['entry_visit_mode_async']    = 'Асинхронный (JS fetch, не блокирует рендер)';
$_['entry_visit_mode_sync']     = 'Синхронный (рекомендации сразу в HTML)';
$_['entry_exclude_oos']         = 'Исключать товары с количеством < 1';
$_['entry_exclude_disabled']    = 'Исключать отключённые товары';
$_['entry_cache']               = 'Кэширование';
$_['entry_cache_ttl']           = 'Время жизни кэша (ч.)';

// Weights
$_['entry_weight_category']     = 'Категория';
$_['entry_weight_name']         = 'Название';
$_['entry_weight_neighbor_id']  = 'Соседние ID';
$_['entry_weight_fields']       = 'Поля (MPN/SKU/…)';
$_['entry_weight_manufacturer'] = 'Производитель';
$_['entry_weight_attributes']   = 'Характеристики';
$_['entry_weight_coorders']     = 'Часто заказывают вместе';
$_['entry_weight_price_range']  = 'Диапазон цен';

$_['entry_neighbor_enabled']    = 'Учитывать соседние ID';
$_['entry_neighbor_range']      = 'Диапазон соседних ID (±N)';
$_['entry_field_list']          = 'Поля для сравнения';
$_['entry_field_separator']     = 'Разделитель значений в поле';
$_['entry_attribute_ids']       = 'Характеристики для сравнения';
$_['entry_attribute_min_match'] = 'Мин. совпадений характеристик';
$_['entry_coorders_days']       = 'Заказы за N дней';
$_['entry_coorders_min']        = 'Мин. совместных заказов';
$_['entry_coorders_statuses']   = 'Статусы заказов';

// Price range signal
$_['entry_price_range_pct']     = 'Макс. отклонение цены (%)';
$_['text_price_range_pct_help'] = 'Товары, цена которых отличается больше чем на этот % от цены исходного товара, получают оценку 0. Например, 20 означает ±20%.';

// Result sort & only_special (global)
$_['entry_result_sort']         = 'Порядок отображения';
$_['entry_result_sort_score']   = 'По оценке (лучшие совпадения первыми)';
$_['entry_result_sort_random']  = 'Случайный';
$_['entry_result_sort_price_asc']  = 'Цена: по возрастанию';
$_['entry_result_sort_price_desc'] = 'Цена: по убыванию';
$_['entry_result_sort_new']     = 'Сначала новые';
$_['entry_result_sort_name']    = 'По алфавиту';
$_['entry_only_special']        = 'Только товары со скидкой';
$_['text_only_special_help']    = 'Если включено, рекомендуются только товары с активной акционной ценой.';

// Brand priority & blacklist
$_['entry_brand_priority']      = 'Сначала тот же бренд';
$_['text_brand_priority_help']  = 'Если включено, товары того же производителя перемещаются в начало списка (после скоринга).';
$_['entry_blacklist_products']  = 'Исключить товары';
$_['entry_blacklist_categories']= 'Исключить категории';
$_['text_blacklist_help']       = 'Товары, соответствующие этим критериям, никогда не попадут в список рекомендованных.';

// Preview
$_['tab_preview']               = 'Превью';
$_['text_preview_product']      = 'Введите название товара…';
$_['button_preview']            = 'Просмотр';
$_['text_preview_results']      = 'Результаты скоринга (тестовый запуск, ничего не сохраняется)';
$_['column_preview_score']      = 'Оценка';
$_['text_preview_empty']        = 'Кандидаты не найдены';
$_['text_no_results']           = 'Нет результатов';

// Inline help texts
$_['text_weights_help']         = 'Сумма не обязательно 100 — веса нормализуются автоматически. 0 = сигнал игнорируется.';
$_['text_field_separator_help'] = 'Например: запятая (,) или точка с запятой (;). Пусто — точное совпадение.';
$_['text_coorders_statuses_help']= 'Пусто = учитывать все статусы заказов.';

// Field names
$_['field_sku']                 = 'SKU';
$_['field_mpn']                 = 'MPN';
$_['field_ean']                 = 'EAN';
$_['field_jan']                 = 'JAN';
$_['field_isbn']                = 'ISBN';
$_['field_upc']                 = 'UPC';

// Performance
$_['entry_candidate_limit']     = 'Макс. кандидатов для scoring';
$_['text_candidate_limit_help'] = 'Размер пула предфильтрации до полного скоринга. Меньше — быстрее; больше — точнее на больших каталогах. Рекомендуется: 500–2000.';

// Generate
$_['entry_id_from']             = 'ID от';
$_['entry_id_to']               = 'ID до';
$_['entry_gen_categories']      = 'Категории';
$_['entry_gen_manufacturers']   = 'Производители';
$_['entry_gen_overwrite']       = 'Перезаписать существующие';
$_['button_generate']           = 'Генерировать';
$_['button_stop']               = 'Остановить';
$_['text_processed']            = 'Обработано';
$_['text_of']                   = 'из';
$_['text_generating']           = 'Генерация…';
$_['text_done']                 = 'Готово!';

// Stats
$_['text_total_products']       = 'Всего товаров';
$_['text_with_related']         = 'С рекомендациями';
$_['text_coverage']             = 'Покрытие';
$_['text_without_related']      = 'Без рекомендаций';
$_['text_recent_generated']     = 'Недавно сгенерированные';
$_['column_product']            = 'Товар';
$_['column_generated_at']       = 'Дата';
$_['column_source']             = 'Источник';
$_['column_count']              = 'Кол-во';
$_['source_cron']               = 'Крон';
$_['source_visit']              = 'Посещение';
$_['source_manual']             = 'Вручную';

// Cron
$_['text_cron']                 = 'Задача Cron';
$_['text_cron_command']         = 'Команда';
$_['text_cron_schedule']        = 'Расписание';
$_['text_cron_daily_2']         = 'Ежедневно в 02:00';
$_['text_cron_daily_3']         = 'Ежедневно в 03:00';
$_['text_cron_daily_4']         = 'Ежедневно в 04:00';
$_['text_cron_every_6h']        = 'Каждые 6 часов';
$_['text_cron_every_1h']        = 'Каждый час';
$_['text_cron_all']             = 'все';
$_['text_cron_param_limit']     = 'Товаров за запуск';
$_['text_cron_param_force']     = 'Принудительная регенерация';
$_['text_cron_param_category']  = 'Категории';
$_['text_cron_param_mf']        = 'Производители';

// Preset scenarios
$_['text_presets']              = 'Пресеты';
$_['text_preset_balanced']      = 'Сбалансированный';
$_['text_preset_coorders']      = 'Акцент на совместных заказах';
$_['text_preset_category']      = 'Та же категория';
$_['text_preset_variants']      = 'Варианты товара';
$_['text_preset_help']          = 'Нажмите на пресет, чтобы заполнить ползунки весов. Затем можно скорректировать и сохранить.';

// Rule Builder
$_['tab_rules']                      = 'Блоки правил';
$_['text_rules_intro']               = 'Блоки на основе правил отображаются рядом со стандартными рекомендациями. Каждое правило — конструктор: укажите ГДЕ показывать блок (условия источника) и ЧТО показывать (условия цели).';
$_['button_add_rule']                = 'Добавить правило';
$_['button_edit_rule']               = 'Редактировать';
$_['button_delete_rule']             = 'Удалить';
$_['button_save_rule']               = 'Сохранить правило';
$_['button_cancel_rule']             = 'Отмена';
$_['column_rule_name']               = 'Название';
$_['column_rule_source']             = 'Где показывать';
$_['column_rule_target']             = 'Что показывать';
$_['column_rule_sort']               = 'Порядок';
$_['column_rule_status']             = 'Статус';
$_['column_rule_actions']            = 'Действия';
$_['entry_rule_name']                = 'Название правила';
$_['entry_rule_status']              = 'Статус';
$_['entry_rule_sort_order']          = 'Порядок сортировки';
$_['entry_rule_block_title']         = 'Заголовок блока';
$_['entry_rule_result_limit']        = 'Количество товаров';
$_['entry_rule_result_sort']         = 'Сортировка';
$_['entry_result_sort_bestseller']   = 'Бестселлеры';

// Rule constructor — source conditions (ГДЕ показывать)
$_['text_source_conditions']         = 'Где показывать';
$_['text_source_conditions_help']    = 'Блок появится на страницах, где выполняются ВСЕ перечисленные условия. Без условий — показывать на всех страницах товаров.';
$_['button_add_source_cond']         = '+ Добавить условие';
$_['cond_src_category']              = 'Категория';
$_['cond_src_manufacturer']          = 'Бренд';
$_['cond_src_attribute']             = 'Значение атрибута';
$_['cond_src_name_contains']         = 'Название содержит';

// Rule constructor — target conditions (ЧТО показывать)
$_['text_target_conditions']         = 'Что показывать';
$_['text_target_conditions_help']    = 'Товары должны соответствовать ВСЕМ перечисленным условиям. Комбинируйте произвольно.';
$_['button_add_target_cond']         = '+ Добавить условие';
$_['cond_tgt_same_category']         = 'Та же категория';
$_['cond_tgt_same_manufacturer']     = 'Тот же бренд';
$_['cond_tgt_category']              = 'Конкретные категории';
$_['cond_tgt_manufacturer']          = 'Конкретные бренды';
$_['cond_tgt_attribute']             = 'Атрибут = значение';
$_['cond_tgt_dynamic_attribute']     = 'Тот же атрибут';
$_['cond_tgt_name_contains']         = 'Название содержит';
$_['cond_tgt_price_range']           = 'Диапазон цены ±%';
$_['cond_tgt_only_special']          = 'Только акционные';
$_['cond_tgt_exclude_oos']           = 'Только в наличии';
$_['cond_tgt_brand_priority']        = 'Сначала тот же бренд';

// Condition field labels / hints
$_['entry_cond_attribute_id']        = 'Атрибут';
$_['entry_cond_attribute_value']     = 'Значение';
$_['entry_cond_price_pct']           = '±%';
$_['entry_cond_name_text']           = 'Текст';
$_['entry_cond_ids_placeholder']     = 'Поиск…';
$_['text_cond_same_cat_help']        = 'Товары из той же категории, что и текущий товар';
$_['text_cond_same_mf_help']         = 'Товары того же бренда, что и текущий товар';
$_['text_cond_dyn_attr_help']        = 'Совпадает с товарами, у которых то же значение этого атрибута (например, тип цоколя E27)';
$_['text_cond_brand_priority_help']  = 'Товары того же бренда будут первыми в блоке';
$_['text_cond_only_special_help']    = 'Только товары с активной акционной ценой';
$_['text_cond_exclude_oos_help']     = 'Исключить товары с нулевым остатком';

$_['text_no_rules']                  = 'Правил пока нет. Нажмите «Добавить правило».';
$_['confirm_delete_rule']            = 'Удалить это правило?';
$_['text_rule_saved']                = 'Правило сохранено.';
$_['text_rule_deleted']              = 'Правило удалено.';

// Errors
$_['error_permission']          = 'Недостаточно прав для изменения настроек!';

// License
$_['tab_license']               = 'Лицензия';
$_['entry_license_key']         = 'Лицензионный ключ';
$_['button_activate']           = 'Активировать';
$_['text_license_not_validated']= 'Ключ не введён';
$_['text_license_invalid']      = 'Ключ недействителен';
$_['text_license_active']       = 'Лицензия активна';
$_['text_license_expired']      = 'Пробный период истёк';
$_['text_license_trial']        = 'Пробный период: осталось %d дн.';
$_['text_license_grace']        = 'Льготный период (API недоступен)';
$_['text_license_api_error']    = 'Ошибка проверки лицензии';
$_['text_license_version']      = 'Версия';
$_['text_license_buy']          = 'Купить лицензию';
