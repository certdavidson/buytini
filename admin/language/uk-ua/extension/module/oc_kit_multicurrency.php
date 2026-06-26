<?php
/**
 * Multicurrency Products — uk-ua
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

// Heading
$_['heading_title']           = 'oc-kit.com — Мультивалютні ціни товарів';

// Breadcrumbs / common
$_['text_home']               = 'Головна';
$_['text_extension']          = 'Розширення';
$_['text_success']            = 'Налаштування збережено';

// Blocks
$_['text_general']            = 'Загальні налаштування';
$_['text_tools']              = 'Інструменти';

// Entries
$_['entry_status']            = 'Увімкнено';
$_['entry_default_currency']  = 'Валюта-джерело за замовчуванням';
$_['entry_base_currency']     = 'Базова валюта магазину';

// Help
$_['help_default_currency']   = 'Підставляється в нові поля ціни на картці товару.';
$_['help_recalc']             = 'Перерахувати всі ціни (товари, спеції, знижки) за поточним курсом oc_currency.';
$_['help_import']             = 'Одноразовий перенос цін зі старих полів. Вкажіть назви колонок (валюта = currency_id з oc_currency). Порожні рядки пропускаються; неіснуючі колонки — skip.';

// Legacy import map
$_['entry_legacy_product']    = 'Товар (oc_product)';
$_['entry_legacy_special']    = 'Спецціна (oc_product_special)';
$_['entry_legacy_discount']   = 'Знижка (oc_product_discount)';
$_['entry_legacy_table']      = 'Таблиця';
$_['entry_legacy_amount_col'] = 'Колонка суми';
$_['entry_legacy_currency_col']= 'Колонка валюти';
$_['text_import_skip']        = 'нема колонок';

// Buttons
$_['button_save']             = 'Зберегти';
$_['button_cancel']           = 'Назад';
$_['button_recalc']           = 'Перерахувати ціни';
$_['button_import']           = 'Імпорт зі старих полів';

// JS / AJAX texts
$_['text_confirm_recalc']     = 'Перерахувати всі ціни за поточним курсом?';
$_['text_confirm_import']     = 'Перенести ціни зі старих полів у новий модуль?';
$_['text_working']            = 'Виконується…';
$_['text_recalc_done']        = 'Перераховано позицій: %d';
$_['text_import_done']        = 'Імпортовано позицій: %d';

// Product form (через OCMOD)
$_['entry_mc_amount']         = 'Ціна у валюті';
$_['entry_mc_currency']       = 'Валюта';
$_['text_mc_none']            = '— базова —';
$_['help_mc_price']           = 'Якщо задано — ціна конвертується в базову валюту за курсом. Інакше діє звичайна ціна.';

// Errors
$_['error_permission']        = 'У вас немає прав керувати цим модулем';
