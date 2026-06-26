<?php
// Heading
$_['heading_title'] = 'monopay';

// Text
$_['text_mono'] = '<a onclick="window.open(\'https://www.monobank.ua/e-comm\');"><img src="view/image/payment/monopay_logo.svg" alt="monopay" title="monopay" width="100px"/></a>';
$_['text_success'] = 'Настройки модуля обновлены :)';
$_['text_pay'] = 'Monobank';
$_['mono_text'] = 'Узнать свой X-Token вы можете по ссылке:';
$_['invoice_settings'] = 'Управление оплатой';
$_['invoice_amount'] = 'Сумма';
$_['invoice_on_hold'] = ' холда';
$_['amount_finalized'] = 'Финализированная сумма холда';
$_['finalized_at'] = 'Дата и время финализации холда';
$_['invoice_amount_refunded'] = 'Сумма, которая уже возвращена';
$_['invoice_amount_to_refund'] = 'Сумма, которую можно вернуть';
$_['invoice_refund'] = 'Вернуть сумму';
$_['invoice_finalize_hold'] = 'Финализировать холд';
$_['invoice_cancel_hold'] = 'Отменить холд';
$_['text_cancel'] = 'Отменить';
$_['text_enter_amount'] = 'Введите сумму в гривнах';

// Entry
$_['entry_merchant'] = 'X-Token';
$_['entry_geo_zone'] = 'Географическая зона';
$_['entry_order_default_status'] = 'Статус нового заказа';
$_['entry_order_success_status'] = 'Статус оплаченного заказа';
$_['entry_order_reversed_status'] = 'Статус заказа после возврата средств';
$_['entry_order_process_status'] = 'Статус заказа в обработке';
$_['entry_order_cancelled_status'] = 'Статус отмененного заказа';
$_['entry_order_hold_status'] = 'Статус заказа, находящегося в холде';
$_['entry_status'] = 'Статус';
$_['entry_sort_order'] = 'Порядок сортировки';
$_['entry_redirect'] = 'Перенаправление после оплаты. Образец: "index.php?route=information/pay-success"';
$_['entry_destination'] = 'Назначение платежа';
$_['entry_hold'] = 'Режим холдов';
$_['entry_total'] = 'Нижний порог суммы заказа';
$_['entry_totalmax'] = 'Верхний порог суммы заказа';
$_['entry_fiscalization_code_field'] = 'Значение для параметра "code", если фискализация активирована (monopay, checkbox...)';

// Error
$_['error_merchant'] = 'Неверный X-Token!';
$_['error_permission'] = 'Недостаточно прав!';
//Button
$_['save_btn'] = 'Сохранить';