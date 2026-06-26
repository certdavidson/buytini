<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 * @author oc-kit.com | https://oc-kit.com
 */

$_['heading_title']            = 'oc-kit.com — EasyCheckout';
$_['module_name']              = 'EasyCheckout';

$_['sidebar_assistant']        = 'Помощник по настройке';
$_['sidebar_general']          = 'Общие';
$_['sidebar_pages']            = 'Разметка блоков';
$_['sidebar_page_checkout']    = 'Заказ';
$_['sidebar_page_general']     = 'Общие';
$_['sidebar_page_blocks']      = 'Разметка блоков';
$_['sidebar_page_block_settings'] = 'Настройки блоков';

$_['layout_heading']           = 'Разметка блоков страницы checkout';
$_['layout_help']              = 'Перетаскивайте блоки между шагами и внутри шага. Изменения сохраняются кнопкой «Сохранить разметку».';
$_['layout_btn_save']          = 'Сохранить разметку';
$_['layout_btn_reset']         = 'Стандартные настройки';
$_['layout_confirm_reset']     = 'Сбросить разметку к стандартным настройкам? Несохранённые изменения будут потеряны (пока не нажмёте «Сохранить разметку»).';
$_['layout_reset_done']        = 'Разметка сброшена к стандартным настройкам. Нажмите «Сохранить разметку», чтобы применить.';
$_['layout_group_selector_label'] = 'Группа:';
$_['layout_group_selector_help']  = 'Активная группа для редактирования — её разметка загрузится при переключении.';
$_['layout_columns_label']     = 'Колонки:';
$_['layout_btn_add_step']      = 'Добавить шаг';
$_['layout_btn_add_row']       = 'Добавить ряд';
$_['layout_btn_remove_row']    = 'Удалить ряд';
$_['layout_row_1_col']         = '1 колонка';
$_['layout_row_2_col']         = '2 колонки';
$_['layout_row_3_col']         = '3 колонки';
$_['layout_row_cols']          = 'Колонки:';
$_['layout_stack_hint']        = 'На этом viewport — 1 колонка (стак). Перетаскивай блоки, чтобы изменить порядок именно для этого вида.';
$_['layout_custom_order_label']= 'Кастомный порядок';
$_['layout_reset_order']       = 'Сбросить к desktop-порядку';
$_['layout_viewport_label']    = 'Просмотр:';
$_['layout_btn_add_block']     = 'Добавить блок';
$_['layout_btn_remove_step']   = 'Удалить шаг';
$_['layout_btn_remove_block']  = 'Удалить блок';
$_['layout_btn_settings']      = 'Настройки блока';
$_['layout_step_title']        = 'Название шага';
$_['layout_step_placeholder']  = 'Шаг {n}';
$_['layout_no_more_blocks']    = 'Все уникальные блоки уже добавлены.';
$_['layout_saved']             = 'Разметка сохранена';
$_['layout_block_settings_soon'] = 'Настройки этого блока — в следующем обновлении.';

$_['block_settings_visibility']        = 'Видимость';
$_['block_settings_visibility_help']   = 'Управляет, кому и где показывать блок. Все toggle выключены = блок виден всегда.';
$_['block_settings_audience']          = 'Аудитория';
$_['block_settings_hide_for_guests']   = 'Скрыть для гостей';
$_['block_settings_hide_for_logged_in']= 'Скрыть для авторизованных';
$_['block_settings_viewports']         = 'Viewports';
$_['block_settings_viewports_help']    = 'Отмеченные viewports — на них блок скрыт.';
$_['block_settings_text_content']      = 'Текст блока';
$_['block_settings_html_content']      = 'HTML-содержимое';
$_['block_settings_advanced']          = 'Расширенные настройки';
$_['block_settings_advanced_soon']     = 'Type-specific опции для этого блока (поля, наборы, фильтры модулей) добавим в следующих обновлениях.';
$_['block_settings_options']           = 'Опции блока';
$_['block_settings_display']           = 'Отображение';

$_['block_settings_agreement_required']      = 'Согласие обязательно';
$_['block_settings_agreement_required_help'] = 'Если включено — пользователь не сможет оформить заказ без галочки.';

$_['block_settings_registration_mode']  = 'Регистрация при заказе';
$_['registration_mode_optional']        = 'По выбору пользователя';
$_['registration_mode_required']        = 'Обязательна';
$_['registration_mode_disabled']        = 'Не нужна';
$_['block_settings_show_login_link']    = 'Показывать ссылку для входа';

$_['block_settings_show_image']             = 'Изображение';
$_['block_settings_show_model']             = 'Артикул';
$_['block_settings_show_quantity_controls'] = 'Кнопки +/- количества';
$_['block_settings_show_remove_btn']        = 'Кнопка удаления';
$_['block_settings_show_cart_subtotal']     = 'Итог в корзине';

$_['block_settings_show_subtotal']      = 'Подытог';
$_['block_settings_show_taxes']         = 'Налоги';
$_['block_settings_show_coupon_input']  = 'Поле купона';
$_['block_settings_show_voucher_input'] = 'Поле ваучера';
$_['block_settings_show_reward_input']  = 'Поле бонусных баллов';

$_['block_settings_display_mode']      = 'Способ отображения вариантов';
$_['block_settings_display_radio']     = 'Радио-кнопки';
$_['block_settings_display_select']    = 'Выпадающий список';
$_['block_settings_auto_select_first'] = 'Автоматически выбирать первый вариант';
$_['block_settings_show_description']  = 'Показывать описания вариантов';

$_['block_settings_submit_text']             = 'Текст кнопки «Оформить»';
$_['block_settings_submit_text_help']        = 'Если пусто — используется стандартный текст из языкового файла.';
$_['block_settings_show_agreement_inline']   = 'Показывать чекбокс согласия рядом с кнопкой';
$_['block_settings_show_agreement_inline_help']= 'Удобно, если в разметке нет отдельного блока «Соглашение».';
$_['block_settings_sticky_on_mobile']        = 'Sticky на мобильном';
$_['block_settings_sticky_on_mobile_help']   = 'Кнопка прилипает к низу экрана при скролле (только mobile).';

$_['block_settings_show_company']            = 'Показывать поле «Компания»';
$_['block_settings_address_fieldset_hint']   = 'Конкретный набор полей адреса настраивается в разделе «Поля».';

$_['block_settings_payment_form_hint']       = 'Этот блок рендерится выбранным модулем оплаты — отдельных опций здесь не нужно.';

$_['block_settings_fields']         = 'Поля в этом блоке';
$_['block_settings_fields_help']    = 'Выберите из реестра поля, которые использовать в этом блоке. Порядок задаёт последовательность на странице.';
$_['block_settings_fields_empty']   = 'Полей нет. Добавьте из реестра кнопкой ниже.';
$_['block_settings_field_add']      = 'Добавить поле';
$_['block_settings_field_no_more']  = 'Подходящих полей в реестре больше нет. Создайте новые в разделе «Поля».';
$_['block_settings_field_remove']   = 'Убрать поле';
$_['block_settings_field_up']       = 'Выше';
$_['block_settings_field_down']     = 'Ниже';
$_['block_settings_field_reorder']  = 'Перетащить';
$_['block_settings_field_required'] = 'Обязательное';
$_['block_settings_field_reload']   = 'Перезагружать блоки при изменении';
$_['block_settings_field_vis_always']= 'Всегда';
$_['block_settings_field_vis_guests']= 'Только гостям';
$_['block_settings_field_vis_logged']= 'Только авторизованным';
$_['block_settings_field_width']     = 'Ширина поля';
$_['block_settings_field_width_full']       = 'На всю ширину';
$_['block_settings_field_width_two_thirds'] = '2/3';
$_['block_settings_field_width_half']       = '1/2';
$_['block_settings_field_width_third']      = '1/3';

// Native field labels (fallback для отсутствующих в sale/order)
$_['entry_password']        = 'Пароль';
$_['entry_confirm']         = 'Подтверждение пароля';
$_['entry_newsletter']      = 'Подписка на рассылку';
$_['text_account_register'] = 'Регистрация';
$_['text_agree']            = 'Согласие с условиями';

$_['groups_heading']           = 'Группы настроек';
$_['groups_help']               = 'Альтернативные разметки checkout. Каждая группа — отдельный «проект» со своей разметкой, полями и т.д. Используются через URL `/easycheckout?group=slug`.';
$_['groups_empty']              = 'Групп ещё нет. Создайте первую — например «B2B» или «Wholesale».';
$_['groups_btn_add']            = 'Добавить группу';
$_['groups_btn_clone']          = 'Клонировать';
$_['groups_btn_clone_create']   = 'Создать клон';
$_['groups_col_id']             = 'ID';
$_['groups_col_name']           = 'Название';
$_['groups_col_slug']           = 'Slug';
$_['groups_col_default']        = 'По умолчанию';
$_['groups_col_sort']           = 'Сортировка';
$_['groups_col_url_example']    = 'URL';
$_['groups_col_actions']        = 'Действия';
$_['groups_is_default']         = 'Default';

$_['groups_modal_title_add']    = 'Создать группу';
$_['groups_modal_title_edit']   = 'Редактировать группу';
$_['groups_modal_title_clone']  = 'Клонировать группу';
$_['groups_clone_help']         = 'Создастся новая группа с полной копией layout, settings и фильтров существующей.';

$_['entry_group_name']          = 'Название';
$_['entry_group_slug']          = 'Slug (для URL)';
$_['entry_group_is_default']    = 'По умолчанию';
$_['entry_group_sort_order']    = 'Сортировка';
$_['help_group_name']           = 'Внутреннее название, видно только в админке.';
$_['help_group_slug']           = 'Латинские буквы, цифры, дефис. Используется в URL: /easycheckout?group={slug}.';
$_['help_group_is_default']     = 'Активная группа когда в URL нет параметра `group`. Только одна группа может быть default.';

$_['text_group_saved']          = 'Группа сохранена';
$_['text_group_deleted']        = 'Группа удалена';
$_['text_group_cloned']         = 'Группа клонирована';
$_['text_group_validation_error']= 'Проверьте правильность данных';
$_['text_confirm_delete_group']  = 'Удалить группу «{name}» вместе со всеми её настройками?';
$_['error_group_required']           = 'Обязательное поле';
$_['error_group_invalid_format']     = 'Неверный формат (латинские буквы, цифры, дефис)';
$_['error_group_duplicate']          = 'Slug уже используется';
$_['error_group_too_long']           = 'Слишком длинное значение';
$_['error_group_cannot_delete_default']= 'Нельзя удалить группу по умолчанию. Сначала сделайте default другую.';
$_['error_group_not_found']          = 'Группа не найдена';

$_['block_type_customer']         = 'Покупатель';
$_['block_type_cart']             = 'Корзина';
$_['block_type_payment_address']  = 'Адрес оплаты';
$_['block_type_shipping_address'] = 'Адрес доставки';
$_['block_type_shipping']         = 'Доставка';
$_['block_type_payment']          = 'Оплата';
$_['block_type_comment']          = 'Комментарий';
$_['block_type_agreement']        = 'Соглашение / согласие';
$_['block_type_help']             = 'Помощь';
$_['block_type_summary']          = 'Итог';
$_['block_type_payment_form']     = 'Форма модуля оплаты';
$_['block_type_buttons']          = 'Кнопки и чекбоксы';
$_['block_type_custom_html']      = 'Произвольный HTML';
$_['sidebar_fields']           = 'Поля';
$_['sidebar_headings']         = 'Заголовки';
$_['sidebar_misc']             = 'Прочее';
$_['sidebar_misc_link_replace']  = 'Замена ссылок';
$_['sidebar_misc_error_display'] = 'Отображение ошибок';
$_['sidebar_misc_theme']         = 'Интеграция с темой';
$_['sidebar_misc_javascript']    = 'JavaScript';
$_['sidebar_misc_modules']       = 'Модули';
$_['sidebar_misc_address_format']= 'Форматы адреса';
$_['sidebar_groups']           = 'Группы настроек';
$_['sidebar_abandoned']        = 'Брошенные корзины';
$_['sidebar_health']           = 'Перевірка стану';
$_['sidebar_presets']          = 'Пресеты';
$_['presets_heading']          = 'Стартовые пресеты';
$_['presets_help']             = 'Готовые шаблоны разметок. Нажмите Apply чтобы перезаписать текущую активную группу выбранным пресетом.';
$_['preset_applied']           = 'Пресет применён';
$_['preset_apply_confirm']     = 'Заменить текущую разметку выбранным пресетом?';
$_['sidebar_address_formats']  = 'Форматы адреса';
$_['sidebar_restrictions']     = 'Ограничения заказа';
$_['address_formats_heading']  = 'Форматы адреса';
$_['address_formats_help']     = 'Шаблоны форматирования адреса для emails и панели заказов. Поддерживаются плейсхолдеры: {firstname}, {lastname}, {company}, {address_1}, {city}, {postcode}, {country}, {zone} + {custom.field_code}';
$_['address_formats_col_scope']    = 'Тип';
$_['address_formats_col_scope_id'] = 'Значение';
$_['address_formats_col_language'] = 'Язык';
$_['address_formats_col_template'] = 'Шаблон';
$_['address_formats_help_scope_id']= 'Для shipping — code модуля доставки. Для customer_group — id группы покупателей.';
$_['address_formats_help_template']= 'Подставляйте данные через {placeholder}. Каждая строка — отдельная строка адреса.';
$_['address_formats_placeholders_label'] = 'Доступные плейсхолдеры:';
$_['address_formats_placeholders_insert'] = 'Вставить в шаблон';
$_['address_formats_empty']    = 'Форматов ещё нет. Добавьте первый.';
$_['restrictions_heading']     = 'Ограничения заказа';
$_['restrictions_help']        = 'Блокирование заказа по условиям: сумма / количество / вес. При срабатывании — confirm останавливается с error_text.';
$_['restrictions_col_groups']  = 'Группы покупателей';
$_['restrictions_col_total']   = 'Total (мин/макс)';
$_['restrictions_col_qty']     = 'Количество (мин/макс)';
$_['restrictions_col_weight']  = 'Вес (мин/макс)';
$_['restrictions_col_error']   = 'Текст ошибки';
$_['restrictions_help_groups'] = 'Выберите группы покупателей. Пусто — применяется ко всем.';
$_['restrictions_groups_placeholder'] = 'Нажмите чтобы выбрать';
$_['restrictions_help_error']  = 'Текст показывается клиенту когда кнопка оформления заблокирована.';
$_['restrictions_label_total']  = 'Сумма заказа';
$_['restrictions_label_qty']    = 'Количество товаров';
$_['restrictions_label_weight'] = 'Вес';
$_['restrictions_label_sort']   = 'Порядок сортировки';
$_['address_formats_scope_customer_group'] = 'Группа покупателей';
$_['address_formats_scope_shipping']       = 'Метод доставки';
$_['address_formats_scope_id_ph_shipping'] = 'flat / np / cod';
$_['address_formats_scope_id_ph_groups']   = '1, 2, 3 (id групп)';
$_['restrictions_empty']       = 'Ограничений ещё нет.';
$_['misc_heading']             = 'Прочее';
$_['misc_help']                = 'Настройки отображения ошибок, интеграции с темой, JS-injection.';
$_['misc_error_heading']       = 'Отображение ошибок';
$_['entry_error_display_mode'] = 'Способ показа:';
$_['error_mode_inline']        = 'Inline под полем';
$_['error_mode_top']           = 'Сводный блок сверху';
$_['error_mode_toast']         = 'Toast-уведомление';
$_['help_error_display_mode']  = 'Как показывать ошибки валидации клиенту.';
$_['entry_error_scroll_to_first']= 'Прокручивать к первой ошибке';
$_['help_error_scroll_to_first']= 'Автоматически scroll к первому invalid-полю при confirm.';
$_['misc_theme_heading']       = 'Интеграция с темой';
$_['entry_theme_wrapper']      = 'CSS-селектор обёртки:';
$_['help_theme_wrapper']       = 'Куда вставлять checkout-страницу. Default: .main-container';
$_['entry_theme_remove_breadcrumbs']= 'Убрать breadcrumbs';
$_['help_theme_remove_breadcrumbs']= 'Не показывать хлебные крошки на checkout-странице.';
$_['misc_js_heading']          = 'JavaScript';
$_['misc_js_help']             = 'Кастомные JS-сниппеты на frontend в определённые моменты. Полезно для GA, Pixel, GTM.';
$_['entry_js_before_init']     = 'Before init:';
$_['entry_js_after_init']      = 'After init:';
$_['entry_js_before_confirm']  = 'Before confirm:';
$_['license_heading']          = 'Лицензия';
$_['license_help']              = 'Статус лицензии модуля и активация ключа.';
$_['license_status_active']    = 'Лицензия активна';
$_['license_status_invalid']   = 'Лицензия недействительна';
$_['license_label_plan']       = 'Тариф:';
$_['license_label_domain']     = 'Домен:';
$_['license_label_updates']    = 'Обновления до:';
$_['license_activate_heading'] = 'Активация ключа';
$_['license_label_key']        = 'Лицензионный ключ:';
$_['license_key_help']         = 'Введите ключ и нажмите Activate чтобы привязать модуль к домену.';
$_['license_activated']        = 'Лицензия активирована';
$_['license_activate_failed']  = 'Не удалось активировать лицензию';
$_['button_activate']          = 'Активировать';
$_['sidebar_modules']          = 'Оплата / Доставка';
$_['modules_heading']          = 'Модули оплаты и доставки';
$_['modules_help']             = 'Переименуйте, измените иконку или порядок методов доставки и оплаты — так, как их увидит покупатель на странице оформления. Сами модули не меняются, настраивается только их вид в этом checkout.';
$_['modules_payment_heading']  = 'Модули оплаты';
$_['modules_shipping_heading'] = 'Модули доставки';
$_['modules_col_status']       = 'Активен';
$_['modules_col_override_title']= 'Изменённое название';
$_['modules_col_override_description'] = 'Описание';
$_['modules_col_override_icon']        = 'Иконка';
$_['modules_col_sort']         = 'Сортировка';
$_['modules_col_hide']         = 'Скрыть';
$_['modules_empty']            = 'Не найдено установленных extensions.';
$_['sidebar_license']          = 'Лицензия';

$_['tab_general']              = 'Общее';
$_['entry_status']             = 'Статус модуля';
$_['entry_route']              = 'Маршрут страницы';
$_['entry_default_group']      = 'Группа по умолчанию';
$_['entry_replace_checkout_links'] = 'Заменить стандартные ссылки /checkout';
$_['help_replace_checkout_links']  = 'Если включено, OCMOD подменит все ссылки на /checkout/checkout в каталоге на /easycheckout. Можно выключить для сосуществования со стандартным чекаутом.';

$_['entry_integration']         = 'Интеграция с фронтом';
$_['help_integration']           = 'Активирует URL-маршрут /easycheckout (SEO URL) и регистрирует редирект со стандартного /checkout. Без этого будет 404 на /easycheckout.';
$_['integration_active']         = 'Активна';
$_['integration_inactive']       = 'Не активирована';
$_['integration_btn_setup']      = 'Активировать';
$_['integration_btn_remove']     = 'Деактивировать';
$_['integration_languages']      = 'языков';
$_['integration_event_active']   = 'Редирект /checkout → /easycheckout активен';
$_['integration_event_inactive'] = 'Редирект /checkout → /easycheckout не зарегистрирован';
$_['integration_activated']      = 'Интеграция активирована.';
$_['integration_deactivated']    = 'Интеграция деактивирована.';
$_['help_route']                   = 'Настроен через OCMOD как алиас /easycheckout. Изменение требует обновления модификаторов.';

$_['button_save']              = 'Сохранить';
$_['button_cancel']            = 'Отмена';
$_['button_apply']             = 'Применить';
$_['button_add']               = 'Добавить';
$_['button_bulk_edit']         = 'Массовое редактирование';
$_['bulk_edit_modal_title']    = 'Bulk-редактирование выделенных полей';
$_['bulk_edit_apply_to']       = 'Изменения применятся к';
$_['bulk_edit_apply_to_suffix']= 'выделенным полям. Оставьте — чтобы не менять.';
$_['fields_filter_usage']      = 'Использование';
$_['bulk_edit_no_change']      = 'не менять';
$_['bulk_edit_yes']            = 'Да';
$_['bulk_edit_no']             = 'Нет';
$_['button_close']             = 'Закрыть';
$_['button_delete']            = 'Удалить';
$_['button_edit']              = 'Редактировать';

$_['fields_heading']           = 'Поля';
$_['fields_help']              = 'Глобальный реестр полей. Поля подставляются в блоки страницы checkout. Здесь определяется тип, маска, значение по умолчанию и правила валидации.';
$_['fields_native_heading']    = 'Стандартные поля OpenCart';
$_['fields_native_help']       = 'Названия, placeholder и подсказки стандартных полей OC (имя, телефон, город и т.д.). Пустое поле — используется типовое название OpenCart.';
$_['fields_native_modal_title'] = 'Стандартное поле';
$_['fields_empty']             = 'Пока нет ни одного поля. Добавьте первое — например телефон, имя или комментарий.';
$_['fields_filter_search']     = 'Поиск по названию или коду';
$_['fields_filter_type']       = 'Тип';
$_['fields_filter_belongs_to'] = 'Принадлежность';
$_['fields_filter_all']        = 'Все';
$_['fields_btn_add']           = 'Добавить поле';
$_['fields_btn_delete_selected'] = 'Удалить выбранные';
$_['fields_col_id']            = 'ID';
$_['fields_col_code']          = 'Код';
$_['fields_col_type']          = 'Тип';
$_['fields_col_belongs_to']    = 'Принадлежность';
$_['fields_col_name']          = 'Название';
$_['fields_col_modified']      = 'Изменено';
$_['fields_col_actions']       = 'Действия';
$_['fields_modal_title_add']   = 'Создать поле';
$_['fields_modal_title_edit']  = 'Редактировать поле';
$_['fields_section_text']      = 'Тексты';
$_['fields_section_params']    = 'Параметры';
$_['fields_section_mask']      = 'Маска';
$_['fields_section_default']   = 'Значение по умолчанию';
$_['fields_section_validation']= 'Правила проверки';
$_['fields_section_options']   = 'Варианты';
$_['entry_field_code']         = 'Идентификатор поля';
$_['entry_field_type']         = 'Тип поля';
$_['entry_field_belongs_to']   = 'Принадлежность';
$_['entry_field_name']         = 'Название';
$_['entry_field_tooltip']      = 'Подсказка (тултип)';
$_['entry_field_placeholder']  = 'Плейсхолдер';
$_['entry_field_use_mask']     = 'Использовать маску';
$_['help_field_use_mask']      = 'Включайте только если поле требует форматированного ввода — телефон, почтовый индекс, номер карты. Для обычного текста, email, имён — оставляйте выключенным.';
$_['entry_field_use_default']  = 'Задать значение по умолчанию';
$_['help_field_use_default']   = 'Если включено, поле будет предзаполнено указанным значением (или значением из API-метода).';
$_['entry_field_mask_mode']    = 'Способ маски';
$_['entry_field_mask_value']   = 'Значение маски';
$_['entry_field_default_mode'] = 'Способ значения';
$_['entry_field_default_value']= 'Значение';
$_['entry_field_save_to_comment'] = 'Сохранять значение поля в комментарий к заказу';
$_['entry_field_options']      = 'Список вариантов';
$_['help_field_code']          = 'Латиница/цифры/подчёркивание, начинается с буквы. Уникальный в рамках модуля.';
$_['help_field_save_to_comment'] = 'Если включено, после оформления заказа значение поля будет добавлено к комментарию заказа.';
$_['help_field_mask']          = 'Шаблон ввода IMask. Например: +38(999) 999-99-99 — для телефона. «9» означает любую цифру. Буквы/символы берутся как есть.';
$_['help_field_default']       = 'Значение, которое подставится в поле, если не введено вручную. Можно задать API-методом — он должен возвращать строку.';
$_['help_field_options']       = 'Один вариант на строку: значение=Подпись. Подписи можно задавать многоязычно.';
$_['mode_manual']              = 'Установить вручную';
$_['mode_api']                 = 'Через API модуля (catalog/model/tool/easycheckoutapi.php)';
$_['entry_field_api_method']   = 'Название метода';
$_['help_field_api_method']    = 'Public-метод класса ModelToolEasycheckoutapi. Принимает ($field_code, $context) и возвращает строку.';
$_['belongs_to_order']         = 'Заказ';
$_['belongs_to_customer']      = 'Покупатель';
$_['belongs_to_address']       = 'Адрес';
$_['option_label']             = 'Подпись';
$_['option_value']             = 'Значение';
$_['option_add']               = 'Добавить вариант';
$_['option_remove']            = 'Удалить';

$_['rules_help']               = 'Правила применяются только когда поле отображается на странице. «Обязательность» задаётся отдельно в наборе полей блока.';
$_['rules_empty']              = 'Правил нет. Добавьте первое — «Не пустое» или «Регулярное выражение».';
$_['rules_btn_add']            = 'Добавить правило';
$_['rules_error_text']         = 'Текст ошибки';
$_['rules_remove']             = 'Удалить правило';
$_['rule_type_not_empty']      = 'Не пустое поле';
$_['rule_type_length']         = 'По длине';
$_['rule_type_regex']          = 'Регулярное выражение';
$_['rule_type_api']            = 'Через API модуля';
$_['rule_type_match']          = 'Совпадение с другим полем';
$_['rule_param_min']           = 'Минимум';
$_['rule_param_max']           = 'Максимум';
$_['rule_param_pattern']       = 'Регулярное выражение (PCRE)';
$_['rule_param_method']        = 'Название метода в easycheckoutapi.php';
$_['rule_param_field_code']    = 'Код поля для сверки';
$_['placeholder_rule_pattern'] = '^[^\s@]+@[^\s@]+\.[^\s@]+$';
$_['placeholder_rule_error']   = 'Неверное значение';
$_['mask_preview_label']       = 'Тестовый ввод';
$_['mask_preview_placeholder'] = 'Попробуйте ввести значение для проверки маски...';

$_['fields_section_type_params'] = 'Параметры типа';
$_['entry_consent_policy_url'] = 'URL политики/соглашения';
$_['entry_consent_version']    = 'Версия политики';
$_['entry_consent_store_meta'] = 'Сохранять метаданные согласия (IP, время, версия)';
$_['help_consent_version']     = 'При обновлении политики увеличьте версию — старые согласия станут неактуальными.';
$_['entry_tel_default_country']    = 'Страна по умолчанию (ISO2)';
$_['entry_tel_preferred_countries']= 'Предпочитаемые страны (через запятую)';
$_['help_tel_preferred']           = 'Список ISO2-кодов (UA, PL, US, ...) для верха селектора.';
$_['entry_np_scope']           = 'Тип автокомплита';
$_['entry_np_api_key']         = 'API-ключ Новой Почты';
$_['help_np_api_key']          = 'Получить в личном кабинете Новой Почты.';
$_['help_integration_global_keys'] = 'API-ключ Новой Почты задаётся глобально в «Общие настройки → Интеграции» (будет добавлено в следующей итерации).';
$_['np_scope_city']            = 'Населённый пункт';
$_['np_scope_warehouse']       = 'Отделение';
$_['entry_computed_source']    = 'Источник значения';
$_['help_computed_source']     = 'Откуда брать значение поля.';
$_['computed_source_utm_source']  = 'UTM Source';
$_['computed_source_utm_medium']  = 'UTM Medium';
$_['computed_source_utm_campaign']= 'UTM Campaign';
$_['computed_source_utm_content'] = 'UTM Content';
$_['computed_source_utm_term']    = 'UTM Term';
$_['computed_source_referrer']    = 'Referer (HTTP)';
$_['computed_source_cookie']      = 'Cookie (укажите имя)';
$_['computed_source_expression']  = 'JS-выражение (advanced)';
$_['entry_computed_extra']     = 'Параметр источника';
$_['entry_group_columns']      = 'Количество колонок';

$_['entry_date_disable_past']    = 'Запретить прошедшие даты';
$_['entry_date_min_days_ahead']  = 'Минимум дней от сегодня';
$_['entry_date_max_days_ahead']  = 'Максимум дней от сегодня';
$_['help_date_min_days_ahead']   = 'Например, 1 = можно выбирать с завтра. 0 = с сегодня.';
$_['help_date_max_days_ahead']   = 'Оставьте пустым — без ограничения. Например, 14 = на 2 недели вперёд.';
$_['entry_date_weekends']        = 'Выходные дни';
$_['help_date_weekends']         = 'Дни недели, которые запрещено выбирать. Удерживайте Ctrl/Cmd для множественного выбора.';

$_['entry_time_working_hours']   = 'Рабочий диапазон';
$_['entry_time_working_from']    = 'От';
$_['entry_time_working_to']      = 'До';
$_['entry_time_slot_minutes']    = 'Интервал слотов';
$_['help_time_slot_minutes']     = 'Время будет поделено на слоты указанной длительности (по умолчанию 30 мин).';
$_['entry_time_min_hours_ahead'] = 'Минимум часов от текущего времени';
$_['help_time_min_hours_ahead']  = 'Например, 2 = самый ранний слот сегодня — через 2 часа. Для следующих дней правило снимается.';
$_['entry_time_weekends']        = 'Выходные';

$_['weekday_0'] = 'Воскресенье';
$_['weekday_1'] = 'Понедельник';
$_['weekday_2'] = 'Вторник';
$_['weekday_3'] = 'Среда';
$_['weekday_4'] = 'Четверг';
$_['weekday_5'] = 'Пятница';
$_['weekday_6'] = 'Суббота';

$_['entry_consent_information_id']  = 'Информационная страница';
$_['help_consent_information_id']   = 'Начните вводить название — система найдёт страницу из Каталог → Информация. Текст ссылки берётся из названия страницы (или из кастомного названия ниже).';
$_['entry_consent_custom_label']    = 'Кастомное название (опционально)';
$_['help_consent_custom_label']     = 'Если заполнено — используется вместо названия страницы. Можно задать многоязычно.';
$_['placeholder_information_search']= 'Начните вводить название страницы...';

$_['settings_section_integrations']      = 'Интеграции';
$_['settings_section_country']           = 'Страна по умолчанию';
$_['settings_help_integrations']         = 'Глобальные API-ключи и настройки для внешних сервисов. Используются полями типа автокомплит.';
$_['entry_default_country']              = 'Страна по умолчанию';
$_['help_default_country']               = 'Подставляется, когда поле «Страна» не выведено в форму, но есть поле области/города.';
$_['entry_integration_np_api_key']       = 'API-ключ Новой Почты';
$_['help_integration_np_api_key']        = 'Получить в личном кабинете Новой Почты.';
$_['entry_integration_ukrposhta_api_key']= 'API-ключ Укрпочты';
$_['help_integration_ukrposhta_api_key'] = 'Получить в личном кабинете Укрпочты.';

$_['headings_heading']         = 'Заголовки';
$_['headings_help']            = 'Глобальные текстовые заголовки, которые можно вставлять между полями внутри блоков.';
$_['headings_empty']           = 'Заголовков нет. Добавьте первый — например «Контакты» или «Доставка».';
$_['headings_filter_search']   = 'Поиск по коду или тексту';
$_['headings_filter_tag']      = 'Тег';
$_['headings_btn_add']         = 'Добавить заголовок';
$_['headings_btn_delete_selected'] = 'Удалить выбранные';
$_['headings_col_id']          = 'ID';
$_['headings_col_code']        = 'Код';
$_['headings_col_tag']         = 'Тег';
$_['headings_col_text']        = 'Текст';
$_['headings_col_modified']    = 'Изменено';
$_['headings_col_actions']     = 'Действия';
$_['headings_modal_title_add'] = 'Создать заголовок';
$_['headings_modal_title_edit']= 'Редактировать заголовок';
$_['entry_heading_code']       = 'Идентификатор';
$_['entry_heading_tag']        = 'Тег';
$_['entry_heading_text']       = 'Текст';
$_['heading_tag_none']         = 'Без тега';
$_['heading_tag_h1']           = 'H1';
$_['heading_tag_h2']           = 'H2';
$_['heading_tag_h3']           = 'H3';
$_['heading_tag_h4']           = 'H4';
$_['heading_tag_h5']           = 'H5';
$_['heading_tag_p']            = 'p';
$_['heading_tag_legend']       = 'Legend';
$_['text_heading_saved']       = 'Заголовок сохранён';
$_['text_heading_deleted']     = 'Заголовок удалён';
$_['text_heading_validation_error'] = 'Проверьте правильность данных';
$_['text_confirm_delete_heading']   = 'Удалить этот заголовок?';
$_['text_confirm_delete_headings']  = 'Удалить выбранные заголовки?';
$_['error_heading_text_required']   = 'Текст заголовка обязателен хотя бы на одном языке';
$_['text_field_saved']         = 'Поле сохранено';
$_['text_field_deleted']       = 'Поле удалено';
$_['text_field_validation_error']= 'Проверьте правильность данных';
$_['text_confirm_delete_field']  = 'Удалить это поле?';
$_['text_confirm_delete_fields'] = 'Удалить выбранные поля?';
$_['error_field_code_required']  = 'Укажите идентификатор';
$_['error_field_code_format']    = 'Идентификатор имеет формат: латинская буква, далее буквы/цифры/подчёркивание (до 64 символов)';
$_['error_field_code_duplicate'] = 'Идентификатор уже используется';
$_['error_field_code_reserved']  = 'Этот идентификатор зарезервирован стандартным полем checkout (email, city, address_1 и т.д.). Выберите другой.';
$_['error_field_type_invalid']   = 'Неизвестный тип поля';
$_['error_field_name_required']  = 'Название поля обязательно хотя бы на одном языке';

$_['fields_group_basic']    = 'Базовые';
$_['fields_group_datetime'] = 'Дата и время';
$_['fields_group_hidden']   = 'Скрытые / технические';
$_['fields_group_address']  = 'Адрес';
$_['fields_group_special']  = 'Специальные';
$_['fields_group_struct']   = 'Структура';

$_['field_type_text']                  = 'Текст';
$_['field_type_textarea']              = 'Многострочное поле';
$_['field_type_select']                = 'Выпадающий список';
$_['field_type_radio']                 = 'Радио-кнопки';
$_['field_type_checkbox']              = 'Чекбокс';
$_['field_type_date']                  = 'Дата';
$_['field_type_hidden']                = 'Скрытое';
$_['field_type_html']                  = 'HTML';
$_['field_type_segmented']             = 'Кнопочная группа';
$_['field_type_consent']               = 'Согласие с документом';
$_['field_type_tel_intl']              = 'Телефон (с кодом страны)';
$_['field_type_autocomplete_np']       = 'Автокомплит Новая Почта';
$_['field_type_autocomplete_ukrposhta']= 'Автокомплит Укрпочта';
$_['field_type_country']               = 'Страна';
$_['field_type_zone']                  = 'Область / регион';
$_['field_type_city']                  = 'Город / населённый пункт';
$_['field_type_time']                  = 'Время';
$_['field_type_computed_hidden']       = 'Авто-параметр';
$_['field_type_group']                 = 'Группа полей';
$_['field_type_address_select']        = 'Выбор из адресной книги';
$_['field_type_file']                  = 'Загрузка файла';

$_['text_enabled']             = 'Включено';
$_['text_disabled']            = 'Выключено';
$_['text_yes']                 = 'Да';
$_['text_no']                  = 'Нет';
$_['text_success']             = 'Настройки сохранены!';
$_['text_extension']           = 'Расширения';
$_['text_module_brand']        = 'oc-kit.com';
$_['text_version']             = 'Версия';
$_['text_dev_stage']           = 'Модуль в стадии разработки. Доступные разделы будут появляться поэтапно.';
$_['text_coming_soon']         = 'Раздел в разработке';

$_['tab_license']              = 'Лицензия';
$_['entry_license_key']        = 'Лицензионный ключ';
$_['text_extensions']          = 'Расширения';
$_['text_license_active']      = 'Лицензия активна';
$_['text_license_invalid']     = 'Невалидный ключ';
$_['text_license_expired']     = 'Лицензия просрочена';
$_['text_license_trial']       = 'Trial: осталось %d дней';
$_['text_license_not_validated']= 'Ключ не введён';
$_['text_license_version']     = 'Версия';
$_['text_license_domain']      = 'Домен';
$_['text_license_buy']         = 'Купить лицензию';
$_['text_license_api_error']   = 'API недоступен, попробуйте позже';

$_['error_permission']         = 'У вас недостаточно прав для изменения этого модуля!';
$_['error_install']            = 'Ошибка установки модуля.';

$_['js_saving']                = 'Сохранение...';
$_['js_saved']                 = 'Сохранено';
$_['js_error']                 = 'Ошибка';
$_['js_network_error']         = 'Ошибка сети. Попробуйте ещё раз.';
$_['js_confirm']               = 'Вы уверены?';

// Order info admin tab
$_['text_order_tab_col_field']  = 'Поле';
$_['text_order_tab_col_value']  = 'Значение';
$_['text_order_tab_col_type']   = 'Тип';

// Abandoned section
$_['abandoned_heading']        = 'Незавершённые checkout';
$_['abandoned_help']           = 'Пользователи начали оформление но не завершили. Можно скопировать recovery URL и отправить клиенту.';
$_['cron_last_run_label']      = 'Последний запуск cron:';
$_['cron_never_ran']           = 'Cron-job никогда не запускался';
$_['cron_never_ran_help']      = 'Настройте cron-job для запуска crons/cron_easycheckout_reminder.php — иначе напоминания не будут отправляться.';
$_['abandoned_empty']          = 'Нет незавершённых checkout-ов — всё конвертируется в заказы.';
$_['abandoned_col_name']       = 'Имя';
$_['abandoned_col_customer']  = 'Покупатель';
$_['abandoned_col_phone']      = 'Телефон';
$_['abandoned_col_total']      = 'Сумма';
$_['abandoned_col_products']   = 'Товаров';
$_['abandoned_col_modified']   = 'Обновлено';
$_['text_copy_recovery_url']   = 'Скопировать ссылку восстановления';
$_['text_copied']              = 'Скопировано';

// Abandoned reminder
$_['entry_reminder']         = 'Email-напоминание о незавершённом checkout';
$_['help_reminder']          = 'Автоматически отправляет клиентам email со ссылкой на восстановление корзины, если они не завершили оформление.';
$_['entry_reminder_enabled'] = 'Включить напоминание';
$_['entry_reminder_delay']   = 'Задержка (минут)';
$_['help_reminder_delay']    = 'Сколько минут ждать от последней активности перед отправкой напоминания.';

// Layout preview
$_['layout_btn_preview']     = 'Превью';
$_['layout_preview_title']   = 'Превью разметки';

// Reminder template
$_['entry_reminder_template'] = 'Шаблон email-напоминания';
$_['help_reminder_template']  = 'Подстановки: <code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code>, <code>{store_name}</code>, <code>{recovery_url}</code>, <code>{total}</code>, <code>{currency}</code>.';
$_['entry_reminder_subject']  = 'Тема письма';
$_['entry_reminder_body']     = 'Тело письма (HTML)';

// Layout store selector
$_['layout_store_label']    = 'Магазин:';
$_['layout_store_help']     = 'Разметка хранится отдельно для каждого магазина. Если для магазина нет записей — используется default-разметка.';
$_['layout_copy_from_label']= 'Скопировать разметку из магазина:';
$_['layout_copy_from_btn']  = 'Скопировать';
$_['layout_copy_from_help'] = 'Загружает разметку выбранного магазина в текущий как несохранённое состояние. Нажмите Save для применения.';
$_['layout_copy_from_confirm'] = 'Заменить текущую разметку копией из выбранного магазина?';
$_['layout_copied']         = 'Разметка скопирована — проверьте и нажмите Save';
$_['layout_warnings_heading'] = 'Предупреждения разметки';
$_['layout_warn_loc_step']    = 'шаг';
$_['layout_warn_loc_row']     = 'ряд';
$_['layout_warn_loc_cell']    = 'ячейка';
$_['layout_warn_loc_multiple']= 'несколько блоков';
$_['layout_warn_empty_cell']            = 'Пустая ячейка — в ней нет блоков';
$_['layout_warn_empty_row']             = 'Пустой ряд — в нём нет ячеек';
$_['layout_warn_empty_step']            = 'Пустой шаг — в нём нет рядов';
$_['layout_warn_block_condition_broken']= 'Условие показа блока ссылается на удалённое поле: %source_code%';
$_['layout_warn_field_missing']         = 'Блок ссылается на удалённое поле (ID %field_id%)';
$_['layout_warn_field_condition_broken']= 'Условие поля ссылается на удалённое поле: %source_code%';
$_['layout_warn_heading_missing']       = 'Блок ссылается на удалённый заголовок (ID %heading_id%)';
$_['layout_warn_field_duplicate']       = 'Поле (ID %field_id%) использовано в %count% блоках — вероятно, дубль';

// Abandoned stats
$_['abandoned_stats_days']      = 'Период (дней):';
$_['abandoned_stats_total']     = 'Начатых checkout';
$_['abandoned_stats_recovered'] = 'Завершено';
$_['abandoned_stats_lost']      = 'Потерянная сумма';
$_['abandoned_stats_reminder']  = 'Reminder-конверсии';

// CSV export
$_['button_export_csv'] = 'Экспорт CSV';

// Reminder test
$_['button_reminder_test']     = 'Отправить тестовое письмо';
$_['button_reminder_preview']  = 'Превью шаблона';
$_['entry_reminder_delays']    = 'Стадии напоминаний (минуты, через запятую):';
$_['help_reminder_delays']     = 'Задержки для multi-cadence напоминаний. Пример: "60, 1440, 4320" = 1 час, 1 день, 3 дня. Оставьте пустым для одного напоминания с задержкой выше.';
$_['health_heading']           = 'Проверка состояния';
$_['health_help']              = 'Проверка настроек модуля. Исправьте всё красное чтобы модуль работал корректно.';
$_['health_check_module_status']= 'Модуль включён';
$_['health_check_cron_recent']  = 'Автоматические напоминания';
$_['health_check_mail_engine']  = 'Отправка писем';
$_['health_check_db_tables']    = 'Данные модуля в базе';
$_['health_check_ocmod_active'] = 'Интеграция с темой';
$_['health_check_default_country']= 'Страна по умолчанию';
$_['health_check_layout_valid'] = 'Разметка чекаута';
$_['health_status_ok']          = 'OK';
$_['health_status_warn']        = 'Предупреждение';
$_['health_status_fail']        = 'Ошибка';
$_['entry_check']               = 'Что проверяем';

// Health-check — детальные описания состояния
$_['health_msg_generic_ok']             = 'Всё в порядке';
$_['health_msg_generic_warn']           = 'Требует внимания';
$_['health_msg_generic_fail']           = 'Нужно исправить';
$_['health_msg_module_status_fail']     = 'Модуль выключен — включите его в разделе «Общие настройки».';
$_['health_msg_cron_recent_warn']       = 'Cron не запускался больше суток — напоминания могут не отправляться. Проверьте cron-задание.';
$_['health_msg_cron_recent_fail']       = 'Cron-задание ещё ни разу не сработало. Настройте его на сервере — иначе напоминания о покинутых корзинах не будут работать.';
$_['health_msg_mail_engine_warn']       = 'Не настроена отправка почты (SMTP). Письма-напоминания могут не доходить до клиентов.';
$_['health_msg_db_tables_fail']         = 'В базе данных не хватает таблиц модуля. Переустановите модуль в списке расширений.';
$_['health_msg_ocmod_active_warn']      = 'Модификации темы не активны. Перейдите в Расширения → Модификации и нажмите «Обновить».';
$_['health_msg_default_country_warn']   = 'Не выбрана страна по умолчанию — в форме адреса не будет подставляться страна. Выберите её в «Общих настройках».';
$_['health_msg_layout_valid_warn']      = 'В разметке чекаута есть предупреждения (пустые ячейки или неоптимальные настройки). Просмотрите раздел «Разметка блоков».';
$_['health_msg_layout_valid_fail']      = 'В разметке чекаута есть битые ссылки на удалённые поля или заголовки. Откройте «Разметка блоков» и исправьте.';
$_['entry_status_label']        = 'Результат';
$_['button_add_format']         = 'Добавить формат';
$_['button_add_restriction']    = 'Добавить ограничение';
$_['sidebar_js']                = 'JavaScript';
$_['js_heading']                = 'JavaScript-интеграции';
$_['js_help']                   = 'Кастомные JS-сниппеты + документация pub/sub Events API. Полезно для GA4, Pixel, GTM, интеграций.';
$_['help_js_before_init']       = 'Выполняется до инициализации OkEasyCheckout.';
$_['help_js_after_init']        = 'Выполняется после инициализации. window.OkEasyCheckout уже доступен.';
$_['help_js_before_confirm']    = 'Выполняется перед сабмитом — можно прервать.';
$_['js_api_heading']            = 'API событий';
$_['js_api_help']               = 'Модуль экспонирует window.OkEasyCheckout — pub/sub шину событий + методы стейта.';
$_['js_api_events_heading']     = 'Доступные события';
$_['js_api_methods_heading']    = 'API-методы';
$_['js_api_when_heading']       = 'Когда срабатывает';
$_['js_event_ready']            = 'Страница инициализирована';
$_['js_event_field_change']     = 'Изменение любого поля';
$_['js_event_field_focus']      = 'Фокус на поле';
$_['js_event_field_blur']       = 'Потеря фокуса';
$_['js_event_payment_select']   = 'Выбор способа оплаты';
$_['js_event_shipping_select']  = 'Выбор способа доставки';
$_['js_event_before_reload']    = 'Перед AJAX-перезагрузкой блоков';
$_['js_event_after_reload']     = 'После AJAX-перезагрузки';
$_['js_event_abandoned_saved']  = 'Брошенная корзина сохранена';
$_['js_event_before_confirm']   = 'Перед сабмитом — можно прервать';
$_['js_event_order_confirmed']  = 'Заказ создан';

// ── Integrations marketplace ────────────────────────────────────────
$_['sidebar_integrations']      = 'Интеграции';
$_['integrations_heading']      = 'Интеграции';
$_['integrations_help']         = 'Расширения для конкретных служб доставки/оплаты/стран. Включайте только нужные — остальные не грузятся в память.';
$_['integrations_empty']        = 'Интеграций не найдено.';
$_['integrations_marketplace_hint'] = 'Marketplace дополнительных интеграций (KazPost, Meest, Apple Pay, Google Pay) — в следующих релизах.';
$_['integration_status_active']    = 'Активна';
$_['integration_status_inactive']  = 'Неактивна';
$_['integration_test_connection']  = 'Тест соединения';
$_['integration_refresh_warehouses']= 'Обновить кэш';
$_['integration_purge_data']       = 'Очистить данные';
$_['integration_purge_confirm']    = 'Удалить все локально кэшированные данные этой интеграции? Действие необратимо, но кэш можно повторно наполнить кнопкой «Обновить кэш».';
$_['integration_refresh_running']  = 'Обновление кэша запущено — это может занять несколько минут.';
$_['integration_version']          = 'версия';
$_['integration_install_fields']   = 'Создать поля';
$_['integration_install_fields_help'] = 'Создаёт поля из preset-блоков интеграции в разделе "Поля". Далее их можно перетащить в layout.';
$_['marketplace_heading']          = 'Marketplace интеграций';
$_['marketplace_help']              = 'Покупайте и устанавливайте дополнительные интеграции в один клик. Загружаются с oc-kit.com.';
$_['marketplace_install']           = 'Установить';
$_['marketplace_uninstall']         = 'Удалить';
$_['marketplace_installed']         = 'Установлено';
$_['marketplace_install_confirm']   = 'Скачать и установить эту интеграцию? Файлы будут распакованы в integrations/.';
$_['marketplace_uninstall_confirm'] = 'Удалить интеграцию вместе с файлами и таблицами БД?';
$_['button_back']                  = 'Назад';
$_['integration_section_general_fallback'] = 'Общие';
$_['integration_section_health']    = 'Состояние и кэш';
$_['integration_health_last_refresh'] = 'Последнее обновление';
$_['integration_health_records']    = 'Записей в кэше';
$_['integration_health_status']     = 'Статус';
$_['integration_health_ok']         = 'OK';
$_['integration_health_stale']      = 'Устарел';
$_['marketplace_search_placeholder']= 'Поиск интеграции...';
$_['marketplace_filter_all_countries']= 'Все страны';
$_['marketplace_filter_all_categories']= 'Все категории';
$_['marketplace_update']            = 'Обновить';
$_['integration_add_to_layout']     = 'Добавить в layout';
$_['button_settings']           = 'Настройки';
$_['entry_detail']              = 'Детали';
$_['button_refresh']            = 'Обновить';
$_['presets_empty']             = 'Пресеты не найдены. Проверьте <span class="ok-badge ok-badge-danger-soft">system/library/ockit/easycheckout/presets/*.json</span>.';
$_['entry_reminder_test_email']= 'Email для теста';
$_['text_reminder_test_sent']  = 'Тестовый email отправлен на %s';

$_['block_settings_same_as_shipping_toggle'] = 'Toggle «адрес оплаты = адрес доставки»';
$_['help_same_as_shipping_toggle']           = 'Если включено — пользователь видит чекбокс «Такой же, как адрес доставки», поля платёжного адреса скрываются. Если выключено — рендерятся отдельные поля с префиксом billing_.';

$_['block_settings_field_condition']      = 'Условие показа';
$_['block_settings_condition_show_if']    = 'Показывать, если';
$_['block_settings_condition_op_not_empty']= 'не пусто';
$_['block_settings_condition_op_empty']   = 'пусто';
$_['block_settings_condition_op_in']      = 'одно из списка';
$_['block_settings_condition_op_eq']      = 'равно';
$_['block_settings_condition_op_neq']     = 'не равно';
$_['block_settings_condition_match']      = 'Показывать когда';
$_['block_settings_condition_match_all']  = 'выполнены все условия';
$_['block_settings_condition_match_any']  = 'выполнено любое условие';
$_['block_settings_condition_add_rule']   = 'Добавить условие';
$_['block_settings_condition_remove_rule']= 'Убрать условие';
$_['block_settings_condition_value_ph']   = 'Значение (для == / != / in)';

$_['abandoned_search_ph']      = 'Поиск: email, телефон, имя';
$_['abandoned_filter_pending'] = 'Ожидают';
$_['abandoned_filter_notified']= 'Отправлен reminder';
$_['abandoned_filter_recovered']= 'Завершенные';
$_['abandoned_filter_all']     = 'Все';
$_['button_delete_selected']   = 'Удалить выбранные';
$_['text_selected']            = 'выбрано';
$_['text_total']               = 'Всего';

$_['fields_btn_presets']      = 'Готовые наборы';
$_['text_apply_preset_confirm']= 'Создать поля из этого preset-а в реестре? Существующие (по code) пропустятся.';
$_['text_preset_applied']     = 'Создано: %d, пропущено: %d (уже существуют)';

$_['option_bulk_import']         = 'Импорт списком';
$_['option_bulk_import_help']    = 'По одному варианту на строку: value, label_{order}. Запятые в значениях — в кавычках.';
$_['option_bulk_import_ph']      = "red,Червоний,Красный,Red\nblue,Синій,Синий,Blue";
$_['option_bulk_import_replace'] = 'Заменить текущие опции (иначе — добавить в конец)';

$_['text_field_in_use']     = 'Поле используется в %d блоках разметки. Удалить принудительно (поле исчезнет из блоков)?';
$_['text_fields_in_use']    = '%d полей используются в разметках. Удалить все принудительно?';

$_['abandoned_show_products']  = 'Товары в корзине';

$_['abandoned_col_note']     = 'Заметка';
$_['abandoned_note_ph']      = 'Комментарий sales-команды';

$_['entry_abandoned_retention'] = 'Хранить покинутые корзины (дней)';
$_['help_abandoned_retention']  = 'Удалять recovered/notified-записи старше N дней. Pending — не трогает. Cleanup выполняется с reminder cron.';

$_['fields_col_usage']         = 'В заказах';
$_['fields_col_usage_tooltip'] = 'Сколько раз это поле встречается в completed-заказах';
$_['fields_col_langs']         = 'Языки';
$_['fields_col_langs_tooltip'] = 'Сколько языков заполнено / всего настроенных';

$_['block_settings_block_condition']        = 'Условие показа блока';
$_['block_settings_block_condition_enable'] = 'Условно показывать';
$_['block_settings_block_condition_help']   = 'Блок показывается только когда значение выбранного поля соответствует условию. Например: показывать «Комментарий» только когда «Тип доставки» = «Самовывоз».';
$_['block_settings_block_condition_source_ph']= 'field code (например: register, country_id)';

$_['fields_filter_used']    = 'Используются';
$_['fields_filter_unused']  = 'Не используются';

$_['button_clone']         = 'Клонировать';
$_['text_field_cloned']    = 'Поле клонировано';

$_['layout_btn_clone_block']      = 'Дублировать блок';
$_['layout_block_cloned']         = 'Блок продублирован';
$_['layout_block_unique_no_clone']= 'Этот блок уникальный — второй экземпляр не разрешён';
$_['abandoned_view_order']     = 'Перейти к заказу';
$_['abandoned_send_reminder_now']    = 'Отправить напоминание сейчас';
$_['abandoned_send_reminder_confirm']= 'Отправить напоминание на email клиента сейчас?';
$_['abandoned_reminder_sent']        = 'Напоминание отправлено';
$_['abandoned_no_email_or_token']    = 'У записи нет email или recovery-токена';
$_['abandoned_already_recovered']    = 'Заказ уже восстановлен';
$_['abandoned_notified_at_tooltip']  = 'Напоминание отправлено:';
$_['text_heading_cloned']  = 'Заголовок клонирован';
$_['button_print']         = 'Печать';

$_['button_reminder_reset']      = 'Очистить шаблоны';
$_['text_reminder_reset_confirm']= 'Удалить текущий шаблон email-напоминания и вернуться к дефолтному?';
$_['text_reminder_reset_done']   = 'Шаблоны очищены. Нажмите «Сохранить» для применения.';
$_['button_field_code_regen']    = 'Сгенерировать из названия';
$_['text_field_name_empty']      = 'Сначала введите название поля';
$_['text_field_name_unsupported']= 'Не удалось сгенерировать code из этого названия';

$_['fields_col_usage_orders_short']    = 'зак.';
$_['fields_col_usage_orders_tooltip']  = 'Сколько раз поле встречается в completed-заказах';
$_['fields_col_usage_layouts_short']   = 'бл.';
$_['fields_col_usage_layouts_tooltip'] = 'Сколько раз поле размещено в блоках разметки';

$_['fields_filter_layouts'] = 'В layouts (без зак.)';
$_['text_heading_in_use']   = 'Заголовок используется в %d блоках. Удалить принудительно?';

$_['entry_reminder_blacklist'] = 'Чёрный список для покинутых корзин';
$_['help_reminder_blacklist']  = 'Email-адреса/домены которые НЕ получат напоминание. По одной записи на строку.';
$_['text_headings_in_use']  = '%d заголовков используются в layouts. Удалить все принудительно?';

$_['button_fields_export_tip'] = 'Скачать JSON-дамп всех fields registry';
$_['button_fields_import_tip'] = 'Импортировать fields из JSON-файла (существующие по code пропустятся)';
$_['text_fields_imported']     = 'Импортировано: %d, пропущено: %s';

$_['layout_btn_export_tip']     = 'Скачать текущую разметку как JSON';
$_['layout_btn_import_tip']     = 'Импортировать разметку из JSON (заменит текущую)';
$_['layout_btn_import_confirm'] = 'Импортировать? Текущая разметка будет заменена.';

$_['abandoned_filter_min_total'] = 'Мин. сумма';
$_['abandoned_filter_max_total'] = 'Макс. сумма';

$_['layout_btn_collapse_all']     = 'Свернуть все';
$_['layout_btn_expand_all']       = 'Развернуть все';
$_['layout_btn_collapse_all_tip'] = 'Скрыть детали блоков — оставить только заголовки';
$_['groups_inline_rename_hint'] = 'Двойной клик чтобы переименовать';
$_['groups_drag_hint']     = 'Перетащите чтобы изменить порядок';

// Backup (export/import all settings)
$_['settings_backup_heading']  = 'Резервная копия настроек';
$_['settings_backup_help']     = 'Выгрузите все настройки модуля в файл (поля, заголовки, группы, разметка блоков, форматы адресов, ограничения, настройки). Лицензия и данные покинутых корзин не входят. Загрузите файл, чтобы восстановить настройки — текущие будут заменены.';
$_['settings_export_btn']      = 'Экспортировать настройки';
$_['settings_import_btn']      = 'Импортировать настройки';
$_['settings_import_confirm']  = 'Импорт заменит ВСЕ текущие настройки модуля. Продолжить?';
$_['settings_import_done']     = 'Настройки импортированы';
$_['settings_import_no_file']  = 'Файл не получен';
$_['settings_import_invalid']  = 'Невалидный файл резервной копии';

// Custom methods (shipping/payment)
$_['cm_heading']               = 'Свои методы доставки и оплаты';
$_['cm_help']                  = 'Создавайте собственные варианты доставки и оплаты, которые показываются в чекауте рядом с установленными модулями.';
$_['cm_add_variant']           = 'Создать вариант';
$_['cm_add_group']             = 'Создать группу';
$_['cm_select_hint']           = 'Выберите вариант слева или создайте новый, чтобы редактировать.';
$_['cm_field_name']            = 'Название';
$_['cm_field_description']     = 'Описание';
$_['cm_cost_type']             = 'Тип стоимости доставки';
$_['cm_cost_fixed']            = 'Фиксированная стоимость';
$_['cm_cost_weight']           = 'Зависит от веса заказа';
$_['cm_cost_sum']              = 'Зависит от суммы заказа';
$_['cm_cost_sum_totals']       = 'Зависит от суммы с учётом подытогов';
$_['cm_cost_api']              = 'Расчёт через API';
$_['cm_cost_value_ph']         = 'Напр. 60.00';
$_['cm_cost_rules_hint']       = 'Таблица правил «от-до → стоимость» — редактор в следующем обновлении; пока используйте фиксированную стоимость.';
$_['cm_cost_api_hint']         = 'Стоимость вычисляется в catalog/model/extension/easycheckout/cm_api.php (следующее обновление).';
$_['cm_currency']              = 'Валюта стоимости';
$_['cm_currency_default']      = 'По умолчанию магазина';
$_['cm_tax_class']             = 'Налоговый класс';
$_['cm_tax_none']              = 'Без налога';
$_['cm_zero_cost_text']        = 'Текст для нулевой стоимости';
$_['cm_order_status']          = 'Статус заказа при выборе';
$_['cm_payment_form_heading']  = 'Заголовок формы оплаты';
$_['cm_payment_info_form']     = 'Информация по оплате (форма)';
$_['cm_payment_info_hint']     = 'HTML разрешён. Подстановки: <code>{total}</code>, <code>{subtotal}</code>, <code>{shipping}</code>, <code>{tax}</code>.';
$_['cm_payment_info_mail']     = 'Информация по оплате (письмо)';
$_['cm_conditions']            = 'Условия показа';
$_['cm_cond_source_ph']        = 'код поля (напр. country_id, shipping_method)';
$_['cm_placeholder']           = 'Заглушка варианта';
$_['cm_placeholder_always']    = 'Всегда показывать как заглушку';
$_['cm_placeholder_unavailable'] = 'Показывать заглушку когда недоступен';
$_['cm_confirm_delete_group']  = 'Удалить группу? Варианты внутри будут отвязаны, но не удалены.';
$_['cm_confirm_delete_method'] = 'Удалить этот вариант?';

// Custom methods — subtotal rows
$_['cm_subtotals_heading']     = 'Учёт в заказе (скидки/сборы)';
$_['cm_subtotals_help']        = 'Дополнительные строки подытога, применяемые когда выбран определённый метод доставки или оплаты. Напр. скидка за предоплату или сбор за наложенный платёж.';
$_['cm_sub_applies']           = 'Применяется к';
$_['cm_sub_any']               = 'Любой метод';
$_['cm_sub_amount_type']       = 'Тип суммы';
$_['cm_sub_fixed']             = 'Фиксированная';
$_['cm_sub_percent']           = 'Процент от подытога';
$_['cm_sub_amount']            = 'Сумма (− скидка)';
$_['cm_sub_methods']           = 'Для методов:';
$_['cm_sub_add']               = 'Создать строку подытога';
$_['cm_sub_value']             = 'Значение';
$_['cm_sub_value_hint']        = 'Фиксированная сумма (напр. -50) или процент от суммы заказа (напр. -1.3%). Отрицательное = скидка.';
$_['cm_sub_round']             = 'Округлять до целых';
$_['cm_confirm_delete_subtotal'] = 'Удалить эту строку подытога?';

// Condition types (custom methods)
$_['cm_cond_group_customer']   = 'Покупатель';
$_['cm_cond_group_cart']       = 'Корзина / сумма';
$_['cm_cond_group_address']    = 'Адрес';
$_['cm_cond_group_context']    = 'Контекст';
$_['cm_cond_group_methods']    = 'Методы';
$_['cm_cond_logged_in']        = 'Пользователь авторизован';
$_['cm_cond_customer_group']   = 'Группа покупателя';
$_['cm_cond_has_orders']       = 'У пользователя есть аккаунт';
$_['cm_cond_total']            = 'Общая сумма';
$_['cm_cond_total_no_shipping']= 'Сумма без доставки';
$_['cm_cond_total_quantity']   = 'Общее количество';
$_['cm_cond_total_weight']     = 'Общий вес (кг)';
$_['cm_cond_max_weight_single']= 'Макс. вес одного товара (кг)';
$_['cm_cond_coupon_used']      = 'Использован купон';
$_['cm_cond_reward_used']      = 'Использованы бонусы';
$_['cm_cond_voucher_used']     = 'Использован подарочный сертификат';
$_['cm_cond_products_no_shipping'] = 'Товары не требуют доставки';
$_['cm_cond_country']          = 'Страна';
$_['cm_cond_zone']             = 'Регион';
$_['cm_cond_city']             = 'Город';
$_['cm_cond_postcode']         = 'Индекс';
$_['cm_cond_language']         = 'Язык';
$_['cm_cond_currency']         = 'Валюта';
$_['cm_cond_store']            = 'Магазин';
$_['cm_cond_ip']               = 'IP-адрес';
$_['cm_cond_day']              = 'День недели (0=вс)';
$_['cm_cond_time']             = 'Время (HH:MM)';
$_['cm_cond_date']             = 'Дата (ГГГГ-ММ-ДД)';
$_['cm_cond_payment_variant']  = 'Вариант оплаты';
$_['cm_cond_shipping_variant'] = 'Вариант доставки';
