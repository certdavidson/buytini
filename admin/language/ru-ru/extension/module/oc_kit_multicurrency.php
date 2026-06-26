<?php
/**
 * Multicurrency Products — ru-ru
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

$_['heading_title']           = 'oc-kit.com — Мультивалютные цены товаров';

$_['text_home']               = 'Главная';
$_['text_extension']          = 'Расширения';
$_['text_success']            = 'Настройки сохранены';

$_['text_general']            = 'Общие настройки';
$_['text_tools']              = 'Инструменты';

$_['entry_status']            = 'Включено';
$_['entry_default_currency']  = 'Валюта-источник по умолчанию';
$_['entry_base_currency']     = 'Базовая валюта магазина';

$_['help_default_currency']   = 'Подставляется в новые поля цены в карточке товара.';
$_['help_recalc']             = 'Пересчитать все цены (товары, спеццены, скидки) по текущему курсу oc_currency.';
$_['help_import']             = 'Разовый перенос цен из старых полей. Укажите названия колонок (валюта = currency_id из oc_currency). Пустые строки пропускаются; несуществующие колонки — skip.';

// Legacy import map
$_['entry_legacy_product']    = 'Товар (oc_product)';
$_['entry_legacy_special']    = 'Спеццена (oc_product_special)';
$_['entry_legacy_discount']   = 'Скидка (oc_product_discount)';
$_['entry_legacy_table']      = 'Таблица';
$_['entry_legacy_amount_col'] = 'Колонка суммы';
$_['entry_legacy_currency_col']= 'Колонка валюты';
$_['text_import_skip']        = 'нет колонок';

$_['button_save']             = 'Сохранить';
$_['button_cancel']           = 'Назад';
$_['button_recalc']           = 'Пересчитать цены';
$_['button_import']           = 'Импорт из старых полей';

$_['text_confirm_recalc']     = 'Пересчитать все цены по текущему курсу?';
$_['text_confirm_import']     = 'Перенести цены из старых полей в новый модуль?';
$_['text_working']            = 'Выполняется…';
$_['text_recalc_done']        = 'Пересчитано позиций: %d';
$_['text_import_done']        = 'Импортировано позиций: %d';

$_['entry_mc_amount']         = 'Цена в валюте';
$_['entry_mc_currency']       = 'Валюта';
$_['text_mc_none']            = '— базовая —';
$_['help_mc_price']           = 'Если задано — цена конвертируется в базовую валюту по курсу. Иначе действует обычная цена.';

$_['error_permission']        = 'У вас нет прав управлять этим модулем';
