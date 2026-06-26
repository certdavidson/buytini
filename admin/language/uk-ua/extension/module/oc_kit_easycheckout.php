<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 * @author oc-kit.com | https://oc-kit.com
 */

// Heading
$_['heading_title']            = 'oc-kit.com — EasyCheckout';
$_['module_name']              = 'EasyCheckout';

// Sidebar
$_['sidebar_assistant']        = 'Помічник по налаштуванню';
$_['sidebar_general']          = 'Загальні';
$_['sidebar_pages']            = 'Розмітка блоків';
$_['sidebar_page_checkout']    = 'Заказ';
$_['sidebar_page_general']     = 'Загальні';
$_['sidebar_page_blocks']      = 'Розмітка блоків';
$_['sidebar_page_block_settings'] = 'Налаштування блоків';

// Layout / pages section
$_['layout_heading']           = 'Розмітка блоків сторінки checkout';
$_['layout_help']              = 'Перетягуйте блоки між кроками й усередині кроку. Зміни зберігаються кнопкою «Зберегти розмітку».';
$_['layout_btn_save']          = 'Зберегти розмітку';
$_['layout_btn_reset']         = 'Стандартні налаштування';
$_['layout_confirm_reset']     = 'Скинути розкладку до стандартних налаштувань? Незбережені зміни будуть втрачені (поки не натиснете «Зберегти розмітку»).';
$_['layout_reset_done']        = 'Розкладку скинуто до стандартних налаштувань. Натисніть «Зберегти розмітку», щоб застосувати.';
$_['layout_group_selector_label'] = 'Група:';
$_['layout_group_selector_help']  = 'Активна група для редагування — її розкладка завантажиться при перемиканні.';
$_['layout_columns_label']     = 'Колонки:';
$_['layout_btn_add_step']      = 'Додати крок';
$_['layout_btn_add_row']       = 'Додати рядок';
$_['layout_btn_remove_row']    = 'Видалити рядок';
$_['layout_row_1_col']         = '1 колонка';
$_['layout_row_2_col']         = '2 колонки';
$_['layout_row_3_col']         = '3 колонки';
$_['layout_row_cols']          = 'Колонки:';
$_['layout_stack_hint']        = 'На цьому viewport — 1 колонка (стак). Перетягуй блоки, щоб змінити порядок саме для цього виду.';
$_['layout_custom_order_label']= 'Кастомний порядок';
$_['layout_reset_order']       = 'Скинути до desktop-порядку';
$_['layout_viewport_label']    = 'Перегляд:';
$_['layout_btn_add_block']     = 'Додати блок';
$_['layout_btn_remove_step']   = 'Видалити крок';
$_['layout_btn_remove_block']  = 'Видалити блок';
$_['layout_btn_settings']      = 'Налаштування блоку';
$_['layout_step_title']        = 'Назва кроку';
$_['layout_step_placeholder']  = 'Крок {n}';
$_['layout_no_more_blocks']    = 'Усі унікальні блоки вже додано.';
$_['layout_saved']             = 'Розкладку збережено';
$_['layout_block_settings_soon'] = 'Налаштування цього блоку — у наступному оновленні.';

// Block settings modal
$_['block_settings_visibility']        = 'Видимість';
$_['block_settings_visibility_help']   = 'Керує тим, кому і де показувати блок. Усі toggle вимкнені = блок видно завжди.';
$_['block_settings_audience']          = 'Аудиторія';
$_['block_settings_hide_for_guests']   = 'Сховати для гостей';
$_['block_settings_hide_for_logged_in']= 'Сховати для авторизованих';
$_['block_settings_viewports']         = 'Viewports';
$_['block_settings_viewports_help']    = 'Помічені viewports — на них блок прихований.';
$_['block_settings_text_content']      = 'Текст блоку';
$_['block_settings_html_content']      = 'HTML-вміст';
$_['block_settings_advanced']          = 'Розширені налаштування';
$_['block_settings_advanced_soon']     = 'Type-specific опції для цього блоку (поля, набори, фільтри модулів) додамо в наступних оновленнях.';
$_['block_settings_options']           = 'Опції блоку';
$_['block_settings_display']           = 'Відображення';

// Agreement
$_['block_settings_agreement_required']      = 'Згода обов\'язкова';
$_['block_settings_agreement_required_help'] = 'Якщо ввімкнено — користувач не зможе оформити замовлення без поставленої галочки.';

// Customer
$_['block_settings_registration_mode']  = 'Реєстрація при заказі';
$_['registration_mode_optional']        = 'За вибором користувача';
$_['registration_mode_required']        = 'Обов\'язкова';
$_['registration_mode_disabled']        = 'Не потрібна';
$_['block_settings_show_login_link']    = 'Показувати посилання для входу';

// Cart
$_['block_settings_show_image']             = 'Зображення';
$_['block_settings_show_model']             = 'Артикул';
$_['block_settings_show_quantity_controls'] = 'Кнопки +/- кількості';
$_['block_settings_show_remove_btn']        = 'Кнопка видалення';
$_['block_settings_show_cart_subtotal']     = 'Підсумок у корзині';

// Summary
$_['block_settings_show_subtotal']      = 'Підсумок';
$_['block_settings_show_taxes']         = 'Податки';
$_['block_settings_show_coupon_input']  = 'Поле купона';
$_['block_settings_show_voucher_input'] = 'Поле ваучера';
$_['block_settings_show_reward_input']  = 'Поле бонусних балів';

// Shipping / Payment
$_['block_settings_display_mode']      = 'Спосіб відображення варіантів';
$_['block_settings_display_radio']     = 'Радіо-кнопки';
$_['block_settings_display_select']    = 'Випадаючий список';
$_['block_settings_auto_select_first'] = 'Автоматично обирати перший варіант';
$_['block_settings_show_description']  = 'Показувати описи варіантів';

// Buttons
$_['block_settings_submit_text']             = 'Текст кнопки «Оформити»';
$_['block_settings_submit_text_help']        = 'Якщо порожнє — використовується стандартний текст з мовного файлу.';
$_['block_settings_show_agreement_inline']   = 'Показувати чекбокс згоди поряд з кнопкою';
$_['block_settings_show_agreement_inline_help']= 'Зручно, якщо в розкладці немає окремого блоку «Угода / згода».';
$_['block_settings_sticky_on_mobile']        = 'Sticky на мобільному';
$_['block_settings_sticky_on_mobile_help']   = 'Кнопка прилипає до низу екрана при скролі (тільки mobile).';

// Address
$_['block_settings_show_company']            = 'Показувати поле «Компанія»';
$_['block_settings_address_fieldset_hint']   = 'Конкретний набір полів адреси налаштовується в розділі «Поля» (для типів країна/область/місто/НП тощо).';

// Payment form
$_['block_settings_payment_form_hint']       = 'Цей блок рендериться обраним модулем оплати — окремих опцій тут не потрібно.';

// Block fields section
$_['block_settings_fields']         = 'Поля у цьому блоці';
$_['block_settings_fields_help']    = 'Виберіть з реєстру полів які саме використовувати в цьому блоці. Порядок задає послідовність на сторінці. Іконки керують видимістю / обов\'язковістю / AJAX-перезавантаженням.';
$_['block_settings_fields_empty']   = 'Полів немає. Додайте з реєстру кнопкою нижче.';
$_['block_settings_field_add']      = 'Додати поле';
$_['block_settings_field_no_more']  = 'Більше підходящих полів у реєстрі немає. Додайте нові у розділі «Поля».';
$_['block_settings_field_remove']   = 'Прибрати поле';
$_['block_settings_field_up']       = 'Вище';
$_['block_settings_field_down']     = 'Нижче';
$_['block_settings_field_reorder']  = 'Перетягнути';
$_['block_settings_field_required'] = 'Обов\'язкове';
$_['block_settings_field_reload']   = 'Перезавантажувати блоки при зміні';
$_['block_settings_field_vis_always']= 'Завжди';
$_['block_settings_field_vis_guests']= 'Тільки гостям';
$_['block_settings_field_vis_logged']= 'Тільки авторизованим';
$_['block_settings_field_width']     = 'Ширина поля';
$_['block_settings_field_width_full']       = 'На всю ширину';
$_['block_settings_field_width_two_thirds'] = '2/3';
$_['block_settings_field_width_half']       = '1/2';
$_['block_settings_field_width_third']      = '1/3';

// Native field labels (fallback для тих, яких нема в sale/order)
$_['entry_password']        = 'Пароль';
$_['entry_confirm']         = 'Підтвердження паролю';
$_['entry_newsletter']      = 'Підписка на розсилку';
$_['text_account_register'] = 'Реєстрація';
$_['text_agree']            = 'Згода з умовами';

// ── Settings groups ─────────────────────────────────────────────────
$_['groups_heading']           = 'Групи налаштувань';
$_['groups_help']               = 'Альтернативні розкладки checkout. Кожна група — окремий «проект» з власною розміткою блоків, налаштуваннями полів і т.д. Використовуються через URL `/easycheckout?group=slug` для лендингів, B2B, акційних воронок.';
$_['groups_empty']              = 'Груп ще немає. Створіть першу — наприклад «B2B» чи «Wholesale».';
$_['groups_btn_add']            = 'Додати групу';
$_['groups_btn_clone']          = 'Клонувати';
$_['groups_btn_clone_create']   = 'Створити клон';
$_['groups_col_id']             = 'ID';
$_['groups_col_name']           = 'Назва';
$_['groups_col_slug']           = 'Slug';
$_['groups_col_default']        = 'За замовчуванням';
$_['groups_col_sort']           = 'Сортування';
$_['groups_col_url_example']    = 'URL';
$_['groups_col_actions']        = 'Дії';
$_['groups_is_default']         = 'Default';

$_['groups_modal_title_add']    = 'Створити групу';
$_['groups_modal_title_edit']   = 'Редагувати групу';
$_['groups_modal_title_clone']  = 'Клонувати групу';
$_['groups_clone_help']         = 'Створиться нова група з повною копією layout, settings і фільтрами існуючої. Після клонування — можеш редагувати незалежно.';

$_['entry_group_name']          = 'Назва';
$_['entry_group_slug']          = 'Slug (для URL)';
$_['entry_group_is_default']    = 'За замовчуванням';
$_['entry_group_sort_order']    = 'Сортування';
$_['help_group_name']           = 'Внутрішня назва, видна тільки в адмінці.';
$_['help_group_slug']           = 'Латинські літери, цифри, дефіс. Використовується в URL: /easycheckout?group={slug}.';
$_['help_group_is_default']     = 'Активна група коли в URL немає параметра `group`. Лише одна група може бути default.';

$_['text_group_saved']          = 'Групу збережено';
$_['text_group_deleted']        = 'Групу видалено';
$_['text_group_cloned']         = 'Групу клоновано';
$_['text_group_validation_error']= 'Перевірте правильність даних';
$_['text_confirm_delete_group']  = 'Видалити групу «{name}» разом з усіма її налаштуваннями?';
$_['error_group_required']           = 'Обов\'язкове поле';
$_['error_group_invalid_format']     = 'Невірний формат (латинські літери, цифри, дефіс)';
$_['error_group_duplicate']          = 'Slug вже використовується';
$_['error_group_too_long']           = 'Занадто довге значення';
$_['error_group_cannot_delete_default']= 'Не можна видалити групу за замовчуванням. Спочатку зробіть default іншу.';
$_['error_group_not_found']          = 'Групу не знайдено';

// Block type names
$_['block_type_customer']         = 'Покупець';
$_['block_type_cart']             = 'Корзина';
$_['block_type_payment_address']  = 'Адреса оплати';
$_['block_type_shipping_address'] = 'Адреса доставки';
$_['block_type_shipping']         = 'Доставка';
$_['block_type_payment']          = 'Оплата';
$_['block_type_comment']          = 'Коментар';
$_['block_type_agreement']        = 'Угода / згода';
$_['block_type_help']             = 'Допомога';
$_['block_type_summary']          = 'Підсумок';
$_['block_type_payment_form']     = 'Форма модуля оплати';
$_['block_type_buttons']          = 'Кнопки і чекбокси';
$_['block_type_custom_html']      = 'Довільний HTML';
$_['sidebar_fields']           = 'Поля';
$_['sidebar_headings']         = 'Заголовки';
$_['sidebar_misc']             = 'Інше';
$_['sidebar_misc_link_replace']  = 'Заміна посилань';
$_['sidebar_misc_error_display'] = 'Відображення помилок';
$_['sidebar_misc_theme']         = 'Інтеграція з темою';
$_['sidebar_misc_javascript']    = 'JavaScript';
$_['sidebar_misc_modules']       = 'Модулі';
$_['sidebar_misc_address_format']= 'Формати адреси';
$_['sidebar_groups']           = 'Групи налаштувань';
$_['sidebar_abandoned']        = 'Покинуті кошики';
$_['sidebar_health']           = 'Проверка состания';
$_['sidebar_presets']          = 'Пресети';
$_['presets_heading']          = 'Стартові пресети';
$_['presets_help']             = 'Готові шаблони розкладок. Натисніть Apply щоб перезаписати поточну активну групу обраним пресетом.';
$_['preset_applied']           = 'Пресет застосовано';
$_['preset_apply_confirm']     = 'Замінити поточну розкладку обраним пресетом?';
$_['sidebar_address_formats']  = 'Формати адреси';
$_['sidebar_restrictions']     = 'Обмеження замовлення';
$_['address_formats_heading']  = 'Формати адреси';
$_['address_formats_help']     = 'Шаблони форматування адреси для emails і панелі замовлень. Підтримуються плейсхолдери: {firstname}, {lastname}, {company}, {address_1}, {city}, {postcode}, {country}, {zone} + {custom.field_code}';
$_['address_formats_col_scope']    = 'Тип';
$_['address_formats_col_scope_id'] = 'Значення';
$_['address_formats_col_language'] = 'Мова';
$_['address_formats_col_template'] = 'Шаблон';
$_['address_formats_help_scope_id']= 'Для shipping — code модуля доставки (np, flat). Для customer_group — id групи покупців.';
$_['address_formats_help_template']= 'Підставляйте дані через {placeholder}. Кожен рядок — окремий рядок адреси.';
$_['address_formats_placeholders_label'] = 'Доступні плейсхолдери:';
$_['address_formats_placeholders_insert'] = 'Вставити в шаблон';
$_['address_formats_empty']    = 'Форматів ще нема. Додайте перший — для конкретного варіанту доставки або групи покупців.';
$_['restrictions_heading']     = 'Обмеження замовлення';
$_['restrictions_help']        = 'Блокування замовлення за умовами: сума / кількість товарів / вага. Якщо хоч одне обмеження виконується — confirm зупиняється з error_text.';
$_['restrictions_col_groups']  = 'Групи покупців';
$_['restrictions_col_total']   = 'Total (мін/макс)';
$_['restrictions_col_qty']     = 'Кількість (мін/макс)';
$_['restrictions_col_weight']  = 'Вага (мін/макс)';
$_['restrictions_col_error']   = 'Текст помилки';
$_['restrictions_help_groups'] = 'Виберіть групи покупців. Порожнє — застосовується до всіх.';
$_['restrictions_groups_placeholder'] = 'Натисніть щоб обрати';
$_['restrictions_help_error']  = 'Текст показується клієнту коли кнопка оформлення заблокована.';
$_['restrictions_label_total']  = 'Сума замовлення';
$_['restrictions_label_qty']    = 'Кількість товарів';
$_['restrictions_label_weight'] = 'Вага';
$_['restrictions_label_sort']   = 'Порядок сортування';
$_['address_formats_scope_customer_group'] = 'Група покупців';
$_['address_formats_scope_shipping']       = 'Метод доставки';
$_['address_formats_scope_id_ph_shipping'] = 'flat / np / cod';
$_['address_formats_scope_id_ph_groups']   = '1, 2, 3 (id груп)';
$_['restrictions_empty']       = 'Обмежень ще нема.';
$_['misc_heading']             = 'Інше';
$_['misc_help']                = 'Налаштування відображення помилок, інтеграції з темою, JS-injection.';
$_['misc_error_heading']       = 'Відображення помилок';
$_['entry_error_display_mode'] = 'Спосіб показу:';
$_['error_mode_inline']        = 'Inline під полем';
$_['error_mode_top']           = 'Сумарний блок зверху';
$_['error_mode_toast']         = 'Toast-нотифікація';
$_['help_error_display_mode']  = 'Як показувати помилки валідації клієнту.';
$_['entry_error_scroll_to_first']= 'Прокручувати до першої помилки';
$_['help_error_scroll_to_first']= 'Автоматично scroll до першого invalid-поля при confirm.';
$_['misc_theme_heading']       = 'Інтеграція з темою';
$_['entry_theme_wrapper']      = 'CSS-селектор обгортки:';
$_['help_theme_wrapper']       = 'Куди вставляти checkout-сторінку. Default: .main-container';
$_['entry_theme_remove_breadcrumbs']= 'Прибрати breadcrumbs';
$_['help_theme_remove_breadcrumbs']= 'Не показувати хлібні крихти на checkout-сторінці.';
$_['misc_js_heading']          = 'JavaScript';
$_['misc_js_help']             = 'Кастомні JS-сніпети, що виконуються на frontend в певні моменти. Корисно для GA, Pixel, GTM.';
$_['entry_js_before_init']     = 'Before init:';
$_['entry_js_after_init']      = 'After init:';
$_['entry_js_before_confirm']  = 'Before confirm:';
$_['license_heading']          = 'Ліцензія';
$_['license_help']              = 'Стан ліцензії модуля та активація ключа.';
$_['license_status_active']    = 'Ліцензія активна';
$_['license_status_invalid']   = 'Ліцензія недійсна';
$_['license_label_plan']       = 'Тариф:';
$_['license_label_domain']     = 'Домен:';
$_['license_label_updates']    = 'Оновлення до:';
$_['license_activate_heading'] = 'Активація ключа';
$_['license_label_key']        = 'Ліцензійний ключ:';
$_['license_key_help']         = 'Введіть ключ і натисніть Activate щоб прив\'язати модуль до домену.';
$_['license_activated']        = 'Ліцензію активовано';
$_['license_activate_failed']  = 'Не вдалось активувати ліцензію';
$_['button_activate']          = 'Активувати';
$_['sidebar_modules']          = 'Оплата / Доставка';
$_['modules_heading']          = 'Модулі оплати і доставки';
$_['modules_help']             = 'Перейменуйте, змініть іконку чи порядок методів доставки й оплати — так, як їх бачитиме покупець на сторінці оформлення. Самі модулі не змінюються, налаштовується лише їхній вигляд у цьому checkout.';
$_['modules_payment_heading']  = 'Модулі оплати';
$_['modules_shipping_heading'] = 'Модулі доставки';
$_['modules_col_status']       = 'Активний';
$_['modules_col_override_title']= 'Змінена назва';
$_['modules_col_override_description'] = 'Опис';
$_['modules_col_override_icon']        = 'Іконка';
$_['modules_col_sort']         = 'Сортування';
$_['modules_col_hide']         = 'Приховати';
$_['modules_empty']            = 'Не знайдено встановлених extensions.';
$_['sidebar_license']          = 'Ліцензія';

// General page
$_['tab_general']              = 'Загальне';
$_['entry_status']             = 'Статус модуля';
$_['entry_route']              = 'Маршрут сторінки';
$_['entry_default_group']      = 'Група за замовчуванням';
$_['entry_replace_checkout_links'] = 'Замінити стандартні посилання /checkout';
$_['help_replace_checkout_links']  = 'Якщо ввімкнено, OCMOD підмінить всі лінки на /checkout/checkout у каталозі на /easycheckout. Можна вимкнути для співіснування зі стандартним чекаутом.';

// Integration
$_['entry_integration']         = 'Інтеграція з фронтом';
$_['help_integration']           = 'Активує URL-маршрут /easycheckout (SEO URL) та реєструє редірект зі стандартного /checkout. Без цього буде 404 на /easycheckout.';
$_['integration_active']         = 'Активна';
$_['integration_inactive']       = 'Не активована';
$_['integration_btn_setup']      = 'Активувати';
$_['integration_btn_remove']     = 'Деактивувати';
$_['integration_languages']      = 'мов';
$_['integration_event_active']   = 'Редірект /checkout → /easycheckout активний';
$_['integration_event_inactive'] = 'Редірект /checkout → /easycheckout не зареєстрований';
$_['integration_activated']      = 'Інтеграція активована. Тепер /easycheckout доступний на фронті.';
$_['integration_deactivated']    = 'Інтеграція деактивована.';
$_['help_route']                   = 'Налаштовано через OCMOD як псевдонім /easycheckout. Зміна вимагає оновлення модифікаторів.';

// Buttons
$_['button_save']              = 'Зберегти';
$_['button_cancel']            = 'Скасувати';
$_['button_apply']             = 'Застосувати';
$_['button_add']               = 'Додати';
$_['button_bulk_edit']         = 'Масове редагування';
$_['bulk_edit_modal_title']    = 'Bulk-редагування виділених полів';
$_['bulk_edit_apply_to']       = 'Зміни застосуються до';
$_['bulk_edit_apply_to_suffix']= 'виділених полів. Залиште — щоб не змінювати.';
$_['fields_filter_usage']      = 'Використання';
$_['bulk_edit_no_change']      = 'не змінювати';
$_['bulk_edit_yes']            = 'Так';
$_['bulk_edit_no']             = 'Ні';
$_['button_close']             = 'Закрити';
$_['button_delete']            = 'Видалити';
$_['button_edit']              = 'Редагувати';

// Fields
$_['fields_heading']           = 'Поля';
$_['fields_help']              = 'Глобальний реєстр полів. Поля підставляються в блоки сторінки checkout. Тут визначається тип, маска, дефолтне значення та правила валідації.';
$_['fields_native_heading']    = 'Стандартні поля OpenCart';
$_['fields_native_help']       = 'Назви, placeholder та підказки стандартних полів OC (імʼя, телефон, місто тощо). Порожнє поле — використовується типова назва OpenCart.';
$_['fields_native_modal_title'] = 'Стандартне поле';
$_['fields_empty']             = 'Поки що немає жодного поля. Додайте перше — наприклад телефон, ім\'я або коментар.';
$_['fields_filter_search']     = 'Пошук за назвою або кодом';
$_['fields_filter_type']       = 'Тип';
$_['fields_filter_belongs_to'] = 'Належність';
$_['fields_filter_all']        = 'Усі';
$_['fields_btn_add']           = 'Додати поле';
$_['fields_btn_delete_selected'] = 'Видалити обрані';
$_['fields_col_id']            = 'ID';
$_['fields_col_code']          = 'Код';
$_['fields_col_type']          = 'Тип';
$_['fields_col_belongs_to']    = 'Належність';
$_['fields_col_name']          = 'Назва';
$_['fields_col_modified']      = 'Змінено';
$_['fields_col_actions']       = 'Дії';
$_['fields_modal_title_add']   = 'Створити поле';
$_['fields_modal_title_edit']  = 'Редагувати поле';
$_['fields_section_text']      = 'Тексти';
$_['fields_section_params']    = 'Параметри';
$_['fields_section_mask']      = 'Маска';
$_['fields_section_default']   = 'Значення за замовчуванням';
$_['fields_section_validation']= 'Правила перевірки';
$_['fields_section_options']   = 'Варіанти';
$_['entry_field_code']         = 'Ідентифікатор поля';
$_['entry_field_type']         = 'Тип поля';
$_['entry_field_belongs_to']   = 'Принадлежність';
$_['entry_field_name']         = 'Назва';
$_['entry_field_tooltip']      = 'Підказка (тултип)';
$_['entry_field_placeholder']  = 'Плейсхолдер';
$_['entry_field_use_mask']     = 'Використовувати маску';
$_['help_field_use_mask']      = 'Увімкніть лише якщо поле потребує форматованого вводу — телефон, поштовий індекс, номер картки. Для звичайного тексту, email, імен — лишайте вимкненим.';
$_['entry_field_use_default']  = 'Задати значення за замовчуванням';
$_['help_field_use_default']   = 'Якщо ввімкнено, поле буде попередньо заповнене вказаним значенням (або значенням з API-методу).';
$_['entry_field_mask_mode']    = 'Спосіб маски';
$_['entry_field_mask_value']   = 'Значення маски';
$_['entry_field_default_mode'] = 'Спосіб значення';
$_['entry_field_default_value']= 'Значення';
$_['entry_field_save_to_comment'] = 'Зберігати значення поля в коментар до замовлення';
$_['entry_field_options']      = 'Список варіантів';
$_['help_field_code']          = 'Латиниця/цифри/підкреслення, починається з літери. Унікальний у межах модуля. Використовується в коді шаблонів.';
$_['help_field_save_to_comment'] = 'Якщо ввімкнено, після оформлення замовлення значення поля буде додано до коментаря замовлення.';
$_['help_field_mask']          = 'Шаблон вводу IMask. Наприклад: +38(999) 999-99-99 — для телефону. «9» означає будь-яку цифру. Літери/символи беруться як є.';
$_['help_field_default']       = 'Значення, яке підставиться у поле, якщо не введене вручну. Можна задати API-методом — він повинен повертати рядок.';
$_['help_field_options']       = 'Один варіант на рядок: значення=Підпис. Підписи можна задавати багатомовно через багатомовні таби.';
$_['mode_manual']              = 'Установити вручну';
$_['mode_api']                 = 'Через API модуля (catalog/model/tool/easycheckoutapi.php)';
$_['entry_field_api_method']   = 'Назва методу';
$_['help_field_api_method']    = 'Public-метод класу ModelToolEasycheckoutapi. Має приймати ($field_code, $context) і повертати рядок.';
$_['belongs_to_order']         = 'Замовлення';
$_['belongs_to_customer']      = 'Покупець';
$_['belongs_to_address']       = 'Адреса';
$_['option_label']             = 'Підпис';
$_['option_value']             = 'Значення';
$_['option_add']               = 'Додати варіант';
$_['option_remove']            = 'Видалити';

// Validation rules
$_['rules_help']               = 'Правила застосовуються тільки коли поле відображається на сторінці. «Обов’язковість» поля задається окремо в наборі полів блоку.';
$_['rules_empty']              = 'Правил немає. Додайте перше — «Не пусте» або «Регулярний вираз».';
$_['rules_btn_add']            = 'Додати правило';
$_['rules_error_text']         = 'Текст помилки';
$_['rules_remove']             = 'Видалити правило';
$_['rule_type_not_empty']      = 'Не пусте поле';
$_['rule_type_length']         = 'По довжині';
$_['rule_type_regex']          = 'Регулярний вираз';
$_['rule_type_api']            = 'Через API модуля';
$_['rule_type_match']          = 'Збіг з іншим полем';
$_['rule_param_min']           = 'Мінімум';
$_['rule_param_max']           = 'Максимум';
$_['rule_param_pattern']       = 'Регулярний вираз (PCRE)';
$_['rule_param_method']        = 'Назва методу в easycheckoutapi.php';
$_['rule_param_field_code']    = 'Код поля для звірки';
$_['placeholder_rule_pattern'] = '^[^\s@]+@[^\s@]+\.[^\s@]+$';
$_['placeholder_rule_error']   = 'Невірне значення';
// Mask preview
$_['mask_preview_label']       = 'Тестовий ввід';
$_['mask_preview_placeholder'] = 'Спробуйте ввести значення для перевірки маски...';

// Type-specific params
$_['fields_section_type_params'] = 'Параметри типу';
// Consent
$_['entry_consent_policy_url'] = 'URL політики/угоди';
$_['entry_consent_version']    = 'Версія політики';
$_['entry_consent_store_meta'] = 'Зберігати метадані згоди (IP, час, версія)';
$_['help_consent_version']     = 'При оновленні політики увімкніть нову версію — щоб старі згоди ставали неактуальними.';
// Tel-intl
$_['entry_tel_default_country']    = 'Країна за замовчуванням (ISO2)';
$_['entry_tel_preferred_countries']= 'Бажані країни (через кому)';
$_['help_tel_preferred']           = 'Список ISO2-кодів (UA, PL, US, ...) для верху селектора. Решта — нижче в алфавітному порядку.';
// Nova Poshta
$_['entry_np_scope']           = 'Тип автокомплейту';
$_['entry_np_api_key']         = 'API-ключ Нової Пошти';
$_['help_np_api_key']          = 'Отримати в особистому кабінеті Нової Пошти.';
$_['help_integration_global_keys'] = 'API-ключ Нової Пошти задається глобально в розділі «Загальні налаштування → Інтеграції» (буде додано в наступній ітерації).';
$_['np_scope_city']            = 'Населений пункт';
$_['np_scope_warehouse']       = 'Відділення';
// Computed hidden
$_['entry_computed_source']    = 'Джерело значення';
$_['help_computed_source']     = 'Оберіть звідки брати значення поля. Для cookie/expression потрібно вказати конкретний ключ або вираз.';
$_['computed_source_utm_source']  = 'UTM Source';
$_['computed_source_utm_medium']  = 'UTM Medium';
$_['computed_source_utm_campaign']= 'UTM Campaign';
$_['computed_source_utm_content'] = 'UTM Content';
$_['computed_source_utm_term']    = 'UTM Term';
$_['computed_source_referrer']    = 'Referer (HTTP)';
$_['computed_source_cookie']      = 'Cookie (вкажіть назву)';
$_['computed_source_expression']  = 'JS-вираз (advanced)';
$_['entry_computed_extra']     = 'Параметр джерела';
// Group
$_['entry_group_columns']      = 'Кількість колонок';

// Date constraints
$_['entry_date_disable_past']    = 'Заборонити минулі дати';
$_['entry_date_min_days_ahead']  = 'Мінімум днів від сьогодні';
$_['entry_date_max_days_ahead']  = 'Максимум днів від сьогодні';
$_['help_date_min_days_ahead']   = 'Наприклад, 1 = можна обирати від завтра. 0 = з сьогодні.';
$_['help_date_max_days_ahead']   = 'Залиш порожнім — без обмеження. Наприклад, 14 = на 2 тижні вперед.';
$_['entry_date_weekends']        = 'Вихідні дні';
$_['help_date_weekends']         = 'Дні тижня, які заборонено вибирати. Утримуй Ctrl/Cmd для множинного вибору.';

// Time params
$_['entry_time_working_hours']   = 'Робочий діапазон';
$_['entry_time_working_from']    = 'Від';
$_['entry_time_working_to']      = 'До';
$_['entry_time_slot_minutes']    = 'Інтервал слотів';
$_['help_time_slot_minutes']     = 'Час буде поділено на слоти заданої тривалості (за замовчуванням 30 хв).';
$_['entry_time_min_hours_ahead'] = 'Мінімум годин від поточного часу';
$_['help_time_min_hours_ahead']  = 'Наприклад, 2 = найранніший слот сьогодні — через 2 години. На наступні дні правило знімається.';
$_['entry_time_weekends']        = 'Вихідні';

$_['weekday_0'] = 'Неділя';
$_['weekday_1'] = 'Понеділок';
$_['weekday_2'] = 'Вівторок';
$_['weekday_3'] = 'Середа';
$_['weekday_4'] = 'Четвер';
$_['weekday_5'] = 'П\'ятниця';
$_['weekday_6'] = 'Субота';

// Consent rework
$_['entry_consent_information_id']  = 'Інформаційна сторінка';
$_['help_consent_information_id']   = 'Почни вводити назву — система знайде сторінку зі стандартного OpenCart Каталог → Інформація. Текст лінку береться з назви сторінки (або з кастомної назви нижче).';
$_['entry_consent_custom_label']    = 'Кастомна назва (опційно)';
$_['help_consent_custom_label']     = 'Якщо заповнено — використовується замість назви сторінки. Можна задати багатомовно.';
$_['placeholder_information_search']= 'Почати вводити назву сторінки...';

// Integrations (general settings)
$_['settings_section_integrations']      = 'Інтеграції';
$_['settings_help_integrations']         = 'Глобальні API-ключі та налаштування для зовнішніх сервісів. Використовуються полями типу автокомплейт.';
$_['settings_section_country']           = 'Країна за замовчуванням';
$_['entry_default_country']              = 'Країна за замовчуванням';
$_['help_default_country']               = 'Підставляється, коли поле «Країна» не виведено у форму, але є поле області/міста (зони залежать від країни).';
$_['entry_integration_np_api_key']       = 'API-ключ Нової Пошти';
$_['help_integration_np_api_key']        = 'Отримати в особистому кабінеті Нової Пошти. Використовується полями автокомплейту НП.';
$_['entry_integration_ukrposhta_api_key']= 'API-ключ Укрпошти';
$_['help_integration_ukrposhta_api_key'] = 'Отримати в особистому кабінеті Укрпошти.';

// Headings
$_['headings_heading']         = 'Заголовки';
$_['headings_help']            = 'Глобальні текстові заголовки, які можна вставляти між полями всередині блоків. Тег визначає семантику й розмір тексту.';
$_['headings_empty']           = 'Заголовків немає. Додайте перший — наприклад «Контакти» або «Доставка».';
$_['headings_filter_search']   = 'Пошук за кодом або текстом';
$_['headings_filter_tag']      = 'Тег';
$_['headings_btn_add']         = 'Додати заголовок';
$_['headings_btn_delete_selected'] = 'Видалити обрані';
$_['headings_col_id']          = 'ID';
$_['headings_col_code']        = 'Код';
$_['headings_col_tag']         = 'Тег';
$_['headings_col_text']        = 'Текст';
$_['headings_col_modified']    = 'Змінено';
$_['headings_col_actions']     = 'Дії';
$_['headings_modal_title_add'] = 'Створити заголовок';
$_['headings_modal_title_edit']= 'Редагувати заголовок';
$_['entry_heading_code']       = 'Ідентифікатор';
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
$_['text_heading_saved']       = 'Заголовок збережено';
$_['text_heading_deleted']     = 'Заголовок видалено';
$_['text_heading_validation_error'] = 'Перевірте правильність даних';
$_['text_confirm_delete_heading']   = 'Видалити цей заголовок?';
$_['text_confirm_delete_headings']  = 'Видалити обрані заголовки?';
$_['error_heading_text_required']   = 'Текст заголовка обов\'язковий хоча б однією мовою';
$_['text_field_saved']         = 'Поле збережено';
$_['text_field_deleted']       = 'Поле видалено';
$_['text_field_validation_error']= 'Перевірте правильність даних';
$_['text_confirm_delete_field']  = 'Видалити це поле?';
$_['text_confirm_delete_fields'] = 'Видалити обрані поля?';
$_['error_field_code_required']  = 'Вкажіть ідентифікатор';
$_['error_field_code_format']    = 'Ідентифікатор має формат: латинська літера, далі літери/цифри/підкреслення (до 64 символів)';
$_['error_field_code_duplicate'] = 'Ідентифікатор вже використовується';
$_['error_field_code_reserved']  = 'Цей ідентифікатор зарезервовано стандартним полем checkout (email, city, address_1 тощо). Оберіть інший.';
$_['error_field_type_invalid']   = 'Невідомий тип поля';
$_['error_field_name_required']  = 'Назва поля обов\'язкова хоча б однією мовою';

// Field type groups (optgroup labels)
$_['fields_group_basic']    = 'Базові';
$_['fields_group_datetime'] = 'Дата та час';
$_['fields_group_hidden']   = 'Приховані / технічні';
$_['fields_group_address']  = 'Адреса';
$_['fields_group_special']  = 'Спеціальні';
$_['fields_group_struct']   = 'Структура';

// Field type labels
$_['field_type_text']                  = 'Текст';
$_['field_type_textarea']              = 'Багатострокове поле';
$_['field_type_select']                = 'Випадаючий список';
$_['field_type_radio']                 = 'Радіо-кнопки';
$_['field_type_checkbox']              = 'Чекбокс';
$_['field_type_date']                  = 'Дата';
$_['field_type_hidden']                = 'Приховане';
$_['field_type_html']                  = 'HTML';
$_['field_type_segmented']             = 'Кнопкова група';
$_['field_type_consent']               = 'Згода з документом';
$_['field_type_tel_intl']              = 'Телефон (з кодом країни)';
$_['field_type_autocomplete_np']       = 'Автокомплейт Нова Пошта';
$_['field_type_autocomplete_ukrposhta']= 'Автокомплейт Укрпошта';
$_['field_type_country']               = 'Країна';
$_['field_type_zone']                  = 'Область / регіон';
$_['field_type_city']                  = 'Місто / населений пункт';
$_['field_type_time']                  = 'Час';
$_['field_type_computed_hidden']       = 'Авто-параметр';
$_['field_type_group']                 = 'Група полів';
$_['field_type_address_select']        = 'Вибір з адресної книги';
$_['field_type_file']                  = 'Завантаження файлу';

// Toasts / messages
$_['text_enabled']             = 'Увімкнено';
$_['text_disabled']            = 'Вимкнено';
$_['text_yes']                 = 'Так';
$_['text_no']                  = 'Ні';
$_['text_success']             = 'Налаштування збережено!';
$_['text_extension']           = 'Розширення';
$_['text_module_brand']        = 'oc-kit.com';
$_['text_version']             = 'Версія';
$_['text_dev_stage']           = 'Модуль у стадії розробки. Доступні розділи з\'являтимуться поетапно.';
$_['text_coming_soon']         = 'Розділ у розробці';

// License
$_['tab_license']              = 'Ліцензія';
$_['entry_license_key']        = 'Ліцензійний ключ';
$_['text_extensions']          = 'Розширення';
$_['text_license_active']      = 'Ліцензія активна';
$_['text_license_invalid']     = 'Невалідний ключ';
$_['text_license_expired']     = 'Ліцензія прострочена';
$_['text_license_trial']       = 'Trial: залишилось %d днів';
$_['text_license_not_validated']= 'Ключ не введено';
$_['text_license_version']     = 'Версія';
$_['text_license_domain']      = 'Домен';
$_['text_license_buy']         = 'Купити ліцензію';
$_['text_license_api_error']   = 'API недоступне, спробуйте пізніше';

// Errors
$_['error_permission']         = 'У вас недостатньо прав для зміни цього модуля!';
$_['error_install']            = 'Помилка встановлення модуля.';

// JS strings
$_['js_saving']                = 'Збереження...';
$_['js_saved']                 = 'Збережено';
$_['js_error']                 = 'Помилка';
$_['js_network_error']         = 'Помилка мережі. Спробуйте ще раз.';
$_['js_confirm']               = 'Ви впевнені?';

// Order info admin tab
$_['text_order_tab_col_field']  = 'Поле';
$_['text_order_tab_col_value']  = 'Значення';
$_['text_order_tab_col_type']   = 'Тип';

// Abandoned section
$_['abandoned_heading']        = 'Незавершені checkout';
$_['abandoned_help']           = 'Користувачі почали оформлення але не завершили. Можна скопіювати recovery URL і надіслати клієнту.';
$_['cron_last_run_label']      = 'Останній запуск cron:';
$_['cron_never_ran']           = 'Cron-job ніколи не запускався';
$_['cron_never_ran_help']      = 'Налаштуйте cron-job для запуску crons/cron_easycheckout_reminder.php — інакше нагадування не надсилатимуться.';
$_['abandoned_empty']          = 'Немає лежачих checkout-ів — все повертається замовленнями.';
$_['abandoned_col_name']       = 'Імʼя';
$_['abandoned_col_customer']  = 'Покупець';
$_['abandoned_col_phone']      = 'Телефон';
$_['abandoned_col_total']      = 'Сума';
$_['abandoned_col_products']   = 'Товарів';
$_['abandoned_col_modified']   = 'Оновлено';
$_['text_copy_recovery_url']   = 'Скопіювати посилання для відновлення';
$_['text_copied']              = 'Скопійовано';

// Abandoned reminder
$_['entry_reminder']         = 'Email-нагадування про незавершений checkout';
$_['help_reminder']          = 'Автоматично надсилає клієнтам email з посиланням на відновлення кошика, якщо вони не завершили оформлення.';
$_['entry_reminder_enabled'] = 'Увімкнути нагадування';
$_['entry_reminder_delay']   = 'Затримка (хвилин)';
$_['help_reminder_delay']    = 'Скільки хвилин чекати від останньої активності перед надсиланням нагадування.';

// Layout preview
$_['layout_btn_preview']     = 'Перегляд';
$_['layout_preview_title']   = 'Перегляд розкладки';

// Reminder template
$_['entry_reminder_template'] = 'Шаблон email-нагадування';
$_['help_reminder_template']  = 'Підстановки: <code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code>, <code>{store_name}</code>, <code>{recovery_url}</code>, <code>{total}</code>, <code>{currency}</code>.';
$_['entry_reminder_subject']  = 'Тема листа';
$_['entry_reminder_body']     = 'Тіло листа (HTML)';

// Layout store selector
$_['layout_store_label']    = 'Магазин:';
$_['layout_store_help']     = 'Розкладка зберігається окремо для кожного магазину. Якщо для магазину нема записів — використовується default-розкладка.';
$_['layout_copy_from_label']= 'Скопіювати розкладку з магазину:';
$_['layout_copy_from_btn']  = 'Скопіювати';
$_['layout_copy_from_help'] = 'Завантажує розкладку з обраного магазину в поточний як неузгоджений стан. Натисніть Save, щоб застосувати.';
$_['layout_copy_from_confirm'] = 'Замінити поточну розкладку копією з обраного магазину?';
$_['layout_copied']         = 'Розкладку скопійовано — перегляньте та натисніть Save';
$_['layout_warnings_heading'] = 'Попередження розкладки';
$_['layout_warn_loc_step']    = 'крок';
$_['layout_warn_loc_row']     = 'рядок';
$_['layout_warn_loc_cell']    = 'комірка';
$_['layout_warn_loc_multiple']= 'кілька блоків';
$_['layout_warn_empty_cell']            = 'Порожня комірка — у ній немає блоків';
$_['layout_warn_empty_row']             = 'Порожній рядок — у ньому немає комірок';
$_['layout_warn_empty_step']            = 'Порожній крок — у ньому немає рядків';
$_['layout_warn_block_condition_broken']= 'Умова показу блоку посилається на видалене поле: %source_code%';
$_['layout_warn_field_missing']         = 'Блок посилається на видалене поле (ID %field_id%)';
$_['layout_warn_field_condition_broken']= 'Умова поля посилається на видалене поле: %source_code%';
$_['layout_warn_heading_missing']       = 'Блок посилається на видалений заголовок (ID %heading_id%)';
$_['layout_warn_field_duplicate']       = 'Поле (ID %field_id%) використано у %count% блоках — імовірно, дубль';

// Abandoned stats
$_['abandoned_stats_days']      = 'Період (днів):';
$_['abandoned_stats_total']     = 'Розпочатих checkout';
$_['abandoned_stats_recovered'] = 'Завершено';
$_['abandoned_stats_lost']      = 'Втрачена сума';
$_['abandoned_stats_reminder']  = 'Reminder-конверсії';

// CSV export
$_['button_export_csv'] = 'Експорт CSV';

// Reminder test
$_['button_reminder_test']     = 'Надіслати тестовий лист';
$_['button_reminder_preview']  = 'Прев\'ю шаблону';
$_['entry_reminder_delays']    = 'Стадії нагадувань (хвилини, через кому):';
$_['help_reminder_delays']     = 'Затримки для multi-cadence нагадувань. Приклад: "60, 1440, 4320" = 1 година, 1 день, 3 дні. Залиште порожнім для одного нагадування з затримкою вище.';
$_['health_heading']           = 'Перевірка стану';
$_['health_help']              = 'Перевірка налаштувань модуля. Виправте все червоне щоб модуль працював коректно.';
$_['health_check_module_status']= 'Модуль увімкнено';
$_['health_check_cron_recent']  = 'Автоматичні нагадування';
$_['health_check_mail_engine']  = 'Надсилання листів';
$_['health_check_db_tables']    = 'Дані модуля в базі';
$_['health_check_ocmod_active'] = 'Інтеграція з темою';
$_['health_check_default_country']= 'Країна за замовчуванням';
$_['health_check_layout_valid'] = 'Розкладка чекауту';
$_['health_status_ok']          = 'OK';
$_['health_status_warn']        = 'Попередження';
$_['health_status_fail']        = 'Помилка';
$_['entry_check']               = 'Що перевіряємо';
$_['entry_status_label']        = 'Результат';

// Health-check — детальні описи стану
$_['health_msg_generic_ok']             = 'Все гаразд';
$_['health_msg_generic_warn']           = 'Потрібна увага';
$_['health_msg_generic_fail']           = 'Потрібно виправити';
$_['health_msg_module_status_fail']     = 'Модуль вимкнено — увімкніть його в розділі «Загальні налаштування».';
$_['health_msg_cron_recent_warn']       = 'Cron не запускався більше доби — нагадування можуть не надсилатися. Перевірте cron-завдання.';
$_['health_msg_cron_recent_fail']       = 'Cron-завдання ще жодного разу не спрацювало. Налаштуйте його на сервері — інакше нагадування про покинуті кошики не працюватимуть.';
$_['health_msg_mail_engine_warn']       = 'Не налаштовано надсилання пошти (SMTP). Листи-нагадування можуть не доходити до клієнтів.';
$_['health_msg_db_tables_fail']         = 'У базі даних бракує таблиць модуля. Перевстановіть модуль у списку розширень.';
$_['health_msg_ocmod_active_warn']      = 'Модифікації теми не активні. Перейдіть у Розширення → Модифікації та натисніть «Оновити».';
$_['health_msg_default_country_warn']   = 'Не обрано країну за замовчуванням — у формі адреси не буде підставлятися країна. Оберіть її в «Загальних налаштуваннях».';
$_['health_msg_layout_valid_warn']      = 'У розкладці чекауту є попередження (порожні комірки чи неоптимальні налаштування). Перегляньте розділ «Розмітка блоків».';
$_['health_msg_layout_valid_fail']      = 'У розкладці чекауту є биті посилання на видалені поля чи заголовки. Відкрийте «Розмітка блоків» і виправте.';
$_['button_add_format']         = 'Додати формат';
$_['button_add_restriction']    = 'Додати обмеження';
$_['sidebar_js']                = 'JavaScript';
$_['js_heading']                = 'JavaScript-інтеграції';
$_['js_help']                   = 'Кастомні JS-сніпети що виконуються на frontend в певні моменти + повна документація pub/sub Events API. Корисно для GA4, Pixel, GTM, кастомних інтеграцій.';
$_['help_js_before_init']       = 'Виконується до ініціалізації OkEasyCheckout. Тут можна, наприклад, заздалегідь підписатись на події через document.addEventListener.';
$_['help_js_after_init']        = 'Виконується після ініціалізації. window.OkEasyCheckout вже доступний — можна підписуватись на події напряму.';
$_['help_js_before_confirm']    = 'Виконується перед сабмітом замовлення. Можна перервати — кинути виняток або скасувати через подію okec:beforeConfirm.';
$_['js_api_heading']            = 'API подій';
$_['js_api_help']               = 'Модуль експонує window.OkEasyCheckout — pub/sub шину подій + методи стейту. Усі події починаються з префіксу "okec:".';
$_['js_api_events_heading']     = 'Доступні події';
$_['js_api_methods_heading']    = 'API-методи';
$_['js_api_when_heading']       = 'Коли спрацьовує';
$_['js_event_ready']            = 'Сторінка ініціалізована';
$_['js_event_field_change']     = 'Зміна будь-якого поля';
$_['js_event_field_focus']      = 'Фокус на полі';
$_['js_event_field_blur']       = 'Втрата фокусу';
$_['js_event_payment_select']   = 'Вибір способу оплати';
$_['js_event_shipping_select']  = 'Вибір способу доставки';
$_['js_event_before_reload']    = 'Перед AJAX-перезавантаженням блоків';
$_['js_event_after_reload']     = 'Після AJAX-перезавантаження';
$_['js_event_abandoned_saved']  = 'Покинутий кошик збережено';
$_['js_event_before_confirm']   = 'Перед сабмітом — можна перервати';
$_['js_event_order_confirmed']  = 'Замовлення створено';

// ── Integrations marketplace ────────────────────────────────────────
$_['sidebar_integrations']      = 'Інтеграції';
$_['integrations_heading']      = 'Інтеграції';
$_['integrations_help']         = 'Розширення для конкретних служб доставки/оплати/країн. Вмикайте лише ті що потрібні вашому магазину — інші не завантажуються в пам\'ять.';
$_['integrations_empty']        = 'Інтеграцій не знайдено. Перевірте `system/library/ockit/easycheckout/integrations/`.';
$_['integrations_marketplace_hint'] = 'Marketplace додаткових інтеграцій (KazPost, Meest, Apple Pay, Google Pay та ін.) — у наступних релізах. Зараз інтеграції встановлюються вручну файлами.';
$_['integration_status_active']    = 'Активна';
$_['integration_status_inactive']  = 'Не активна';
$_['integration_test_connection']  = 'Тест з\'єднання';
$_['integration_refresh_warehouses']= 'Оновити кеш';
$_['integration_purge_data']       = 'Очистити дані';
$_['integration_purge_confirm']    = 'Видалити всі локально кешовані дані цієї інтеграції? Дію не можна скасувати, але кеш можна повторно наповнити кнопкою «Оновити кеш».';
$_['integration_refresh_running']  = 'Оновлення кешу запущено — це може зайняти кілька хвилин.';
$_['integration_version']          = 'версія';
$_['integration_install_fields']   = 'Створити поля';
$_['integration_install_fields_help'] = 'Створює поля з preset-блоків інтеграції в розділі "Поля". Далі їх можна перетягнути в layout.';
$_['marketplace_heading']          = 'Marketplace інтеграцій';
$_['marketplace_help']              = 'Купуйте та встановлюйте додаткові інтеграції в один клік. Завантажуються з oc-kit.com.';
$_['marketplace_install']           = 'Встановити';
$_['marketplace_uninstall']         = 'Видалити';
$_['marketplace_installed']         = 'Встановлено';
$_['marketplace_install_confirm']   = 'Завантажити та встановити цю інтеграцію? Файли будуть розпаковані в integrations/.';
$_['marketplace_uninstall_confirm'] = 'Видалити інтеграцію разом з файлами та таблицями БД?';
$_['button_back']                  = 'Назад';
$_['integration_section_general_fallback'] = 'Загальні';
$_['integration_section_health']    = 'Стан і кеш';
$_['integration_health_last_refresh'] = 'Останнє оновлення';
$_['integration_health_records']    = 'Записів у кеші';
$_['integration_health_status']     = 'Статус';
$_['integration_health_ok']         = 'OK';
$_['integration_health_stale']      = 'Застарів';
$_['marketplace_search_placeholder']= 'Пошук інтеграції...';
$_['marketplace_filter_all_countries']= 'Усі країни';
$_['marketplace_filter_all_categories']= 'Усі категорії';
$_['marketplace_update']            = 'Оновити';
$_['integration_add_to_layout']     = 'Додати в layout';
$_['button_settings']           = 'Налаштування';
$_['entry_detail']              = 'Деталі';
$_['button_refresh']            = 'Оновити';
$_['presets_empty']             = 'Пресетів не знайдено. Перевірте <span class="ok-badge ok-badge-danger-soft">system/library/ockit/easycheckout/presets/*.json</span>.';
$_['entry_reminder_test_email']= 'Email для тесту';
$_['text_reminder_test_sent']  = 'Тестовий email надіслано на %s';

// Same as shipping toggle (admin-side)
$_['block_settings_same_as_shipping_toggle'] = 'Toggle "адреса оплати = адреса доставки"';
$_['help_same_as_shipping_toggle']           = 'Якщо увімкнено — користувач бачить чекбокс "Така ж, як адреса доставки" і поля платіжної адреси ховаються коли він активний. Якщо вимкнено — рендеряться окремі поля для платіжної адреси з префіксом billing_.';

// Conditional field
$_['block_settings_field_condition']      = 'Умова показу';
$_['block_settings_condition_show_if']    = 'Показувати, якщо';
$_['block_settings_condition_op_not_empty']= 'не порожнє';
$_['block_settings_condition_op_empty']   = 'порожнє';
$_['block_settings_condition_op_in']      = 'одне зі списку';
$_['block_settings_condition_op_eq']      = 'дорівнює';
$_['block_settings_condition_op_neq']     = 'не дорівнює';
$_['block_settings_condition_match']      = 'Показувати коли';
$_['block_settings_condition_match_all']  = 'виконані всі умови';
$_['block_settings_condition_match_any']  = 'виконана будь-яка умова';
$_['block_settings_condition_add_rule']   = 'Додати умову';
$_['block_settings_condition_remove_rule']= 'Прибрати умову';
$_['block_settings_condition_value_ph']   = 'Значення (для == / != / in)';

// Abandoned filter
$_['abandoned_search_ph']      = 'Пошук: email, телефон, ім\'я';
$_['abandoned_filter_pending'] = 'Очікують';
$_['abandoned_filter_notified']= 'Надіслано reminder';
$_['abandoned_filter_recovered']= 'Завершені';
$_['abandoned_filter_all']     = 'Всі';
$_['button_delete_selected']   = 'Видалити вибрані';
$_['text_selected']            = 'обрано';
$_['text_total']               = 'Усього';

// Field presets
$_['fields_btn_presets']      = 'Готові набори';
$_['text_apply_preset_confirm']= 'Створити поля з цього preset-у в реєстрі? Існуючі (за code) пропустяться.';
$_['text_preset_applied']     = 'Створено: %d, пропущено: %d (вже існують)';

// Bulk-import options
$_['option_bulk_import']         = 'Імпорт списком';
$_['option_bulk_import_help']    = 'По одному варіанту на рядок: value, label_{order}. Кому в значеннях беріть в лапки.';
$_['option_bulk_import_ph']      = "red,Червоний,Красный,Red\nblue,Синій,Синий,Blue";
$_['option_bulk_import_replace'] = 'Замінити поточні опції (інакше — додати в кінець)';

// Field deletion safety
$_['text_field_in_use']     = 'Поле використовується в %d блоках layout-ів. Видалити примусово (поле зникне з блоків)?';
$_['text_fields_in_use']    = '%d полів використовуються в layout-ах. Видалити всі примусово?';

$_['abandoned_show_products']  = 'Товари в кошику';

$_['abandoned_col_note']     = 'Нотатка';
$_['abandoned_note_ph']      = 'Коментар sales-команди';

// Abandoned retention
$_['entry_abandoned_retention'] = 'Зберігати покинуті кошики (днів)';
$_['help_abandoned_retention']  = 'Видаляти recovered/notified-записи старше N днів. Pending — не торкається. Cleanup виконується разом з reminder cron.';

// Field usage column
$_['fields_col_usage']         = 'У замовленнях';
$_['fields_col_usage_tooltip'] = 'Скільки разів це поле зустрічається у completed-замовленнях';
$_['fields_col_langs']         = 'Мови';
$_['fields_col_langs_tooltip'] = 'Скільки мов заповнено / всього налаштованих';

// Block condition
$_['block_settings_block_condition']        = 'Умова показу блоку';
$_['block_settings_block_condition_enable'] = 'Умовно показувати';
$_['block_settings_block_condition_help']   = 'Блок показується лише коли значення обраного поля відповідає умові. Наприклад: показувати «Коментар» лише коли «Тип доставки» = «Самовивіз».';
$_['block_settings_block_condition_source_ph']= 'field code (наприклад: register, country_id)';

$_['fields_filter_used']    = 'Використовуються';
$_['fields_filter_unused']  = 'Не використовуються';

$_['button_clone']         = 'Клонувати';
$_['text_field_cloned']    = 'Поле клоновано';

$_['layout_btn_clone_block']      = 'Дублювати блок';
$_['layout_block_cloned']         = 'Блок дубльовано';
$_['layout_block_unique_no_clone']= 'Цей блок унікальний — другий екземпляр не дозволено';
$_['abandoned_view_order']     = 'Перейти до замовлення';
$_['abandoned_send_reminder_now']    = 'Надіслати нагадування зараз';
$_['abandoned_send_reminder_confirm']= 'Надіслати нагадування на email клієнта зараз?';
$_['abandoned_reminder_sent']        = 'Нагадування надіслано';
$_['abandoned_no_email_or_token']    = 'У запису немає email або recovery-токена';
$_['abandoned_already_recovered']    = 'Замовлення вже відновлено';
$_['abandoned_notified_at_tooltip']  = 'Нагадування надіслано:';
$_['text_heading_cloned']  = 'Заголовок клоновано';
$_['button_print']         = 'Друк';

// Reminder reset + field code regen
$_['button_reminder_reset']      = 'Очистити шаблони';
$_['text_reminder_reset_confirm']= 'Видалити поточний шаблон email-нагадування і повернутись до дефолтного?';
$_['text_reminder_reset_done']   = 'Шаблони очищено. Натисніть «Зберегти» щоб застосувати.';
$_['button_field_code_regen']    = 'Згенерувати з назви';
$_['text_field_name_empty']      = 'Спершу введіть назву поля';
$_['text_field_name_unsupported']= 'Не вдалось згенерувати code з цієї назви';

$_['fields_col_usage_orders_short']    = 'зам.';
$_['fields_col_usage_orders_tooltip']  = 'Скільки разів поле зустрічається у completed-замовленнях';
$_['fields_col_usage_layouts_short']   = 'бл.';
$_['fields_col_usage_layouts_tooltip'] = 'Скільки разів поле розміщене у блоках layout-ів';

$_['fields_filter_layouts'] = 'У layouts (без зам.)';
$_['text_heading_in_use']   = 'Заголовок використовується в %d блоках. Видалити примусово?';

$_['entry_reminder_blacklist'] = 'Чорний список для покинутих кошиків';
$_['help_reminder_blacklist']  = 'Email-адреси/домени які НЕ отримуватимуть нагадування. По одному запису на рядок.';
$_['text_headings_in_use']  = '%d заголовків використовуються в layouts. Видалити всі примусово?';

// Fields export/import
$_['button_fields_export_tip'] = 'Завантажити JSON-дамп усіх fields registry';
$_['button_fields_import_tip'] = 'Імпортувати fields з JSON-файлу (існуючі за code пропустяться)';
$_['text_fields_imported']     = 'Імпортовано: %d, пропущено: %s';

// Layout export/import
$_['layout_btn_export_tip']     = 'Завантажити поточну розкладку як JSON';
$_['layout_btn_import_tip']     = 'Імпортувати розкладку з JSON-файлу (замінить поточну)';
$_['layout_btn_import_confirm'] = 'Імпортувати? Поточна розкладка буде замінена.';

$_['abandoned_filter_min_total'] = 'Мін. сума';
$_['abandoned_filter_max_total'] = 'Макс. сума';

$_['layout_btn_collapse_all']     = 'Згорнути всі';
$_['layout_btn_expand_all']       = 'Розгорнути всі';
$_['layout_btn_collapse_all_tip'] = 'Сховати деталі блоків — лишити тільки заголовки';
$_['groups_inline_rename_hint'] = 'Подвійний клік щоб перейменувати';
$_['groups_drag_hint']     = 'Перетягніть щоб змінити порядок';

// Backup (export/import all settings)
$_['settings_backup_heading']  = 'Резервна копія налаштувань';
$_['settings_backup_help']     = 'Вивантажте всі налаштування модуля у файл (поля, заголовки, групи, розмітка блоків, формати адрес, обмеження, налаштування). Ліцензія та дані покинутих кошиків не входять. Завантажте файл, щоб відновити налаштування — поточні буде замінено.';
$_['settings_export_btn']      = 'Експортувати налаштування';
$_['settings_import_btn']      = 'Імпортувати налаштування';
$_['settings_import_confirm']  = 'Імпорт замінить ВСІ поточні налаштування модуля. Продовжити?';
$_['settings_import_done']     = 'Налаштування імпортовано';
$_['settings_import_no_file']  = 'Файл не отримано';
$_['settings_import_invalid']  = 'Невалідний файл резервної копії';

// Custom methods (shipping/payment)
$_['cm_heading']               = 'Власні методи доставки та оплати';
$_['cm_help']                  = 'Створюйте власні варіанти доставки й оплати, що показуються в чекауті поряд зі встановленими модулями.';
$_['cm_add_variant']           = 'Створити варіант';
$_['cm_add_group']             = 'Створити групу';
$_['cm_select_hint']           = 'Оберіть варіант ліворуч або створіть новий, щоб редагувати.';
$_['cm_field_name']            = 'Назва';
$_['cm_field_description']     = 'Опис';
$_['cm_cost_type']             = 'Тип вартості доставки';
$_['cm_cost_fixed']            = 'Фіксована вартість';
$_['cm_cost_weight']           = 'Залежить від ваги замовлення';
$_['cm_cost_sum']              = 'Залежить від суми замовлення';
$_['cm_cost_sum_totals']       = 'Залежить від суми з урахуванням підсумків';
$_['cm_cost_api']              = 'Розрахунок через API';
$_['cm_cost_value_ph']         = 'Напр. 60.00';
$_['cm_cost_rules_hint']       = 'Таблиця правил «від-до → вартість» — редактор у наступному оновленні; поки що використайте фіксовану вартість.';
$_['cm_cost_api_hint']         = 'Вартість обчислюється у catalog/model/extension/easycheckout/cm_api.php (наступне оновлення).';
$_['cm_currency']              = 'Валюта вартості';
$_['cm_currency_default']      = 'За замовчуванням магазину';
$_['cm_tax_class']             = 'Податковий клас';
$_['cm_tax_none']              = 'Без податку';
$_['cm_zero_cost_text']        = 'Текст для нульової вартості';
$_['cm_order_status']          = 'Статус замовлення при виборі';
$_['cm_payment_form_heading']  = 'Заголовок форми оплати';
$_['cm_payment_info_form']     = 'Інформація по оплаті (форма)';
$_['cm_payment_info_hint']     = 'HTML дозволено. Підстановки: <code>{total}</code>, <code>{subtotal}</code>, <code>{shipping}</code>, <code>{tax}</code>.';
$_['cm_payment_info_mail']     = 'Інформація по оплаті (лист)';
$_['cm_conditions']            = 'Умови показу';
$_['cm_cond_source_ph']        = 'код поля (напр. country_id, shipping_method)';
$_['cm_placeholder']           = 'Заглушка варіанта';
$_['cm_placeholder_always']    = 'Завжди показувати як заглушку';
$_['cm_placeholder_unavailable'] = 'Показувати заглушку коли недоступний';
$_['cm_confirm_delete_group']  = 'Видалити групу? Варіанти всередині буде від\'єднано, але не видалено.';
$_['cm_confirm_delete_method'] = 'Видалити цей варіант?';

// Custom methods — subtotal rows
$_['cm_subtotals_heading']     = 'Облік у замовленні (знижки/збори)';
$_['cm_subtotals_help']        = 'Додаткові рядки підсумку, що застосовуються коли обрано певний метод доставки чи оплати. Напр. знижка за передоплату або збір за післяплату.';
$_['cm_sub_applies']           = 'Застосовується до';
$_['cm_sub_any']               = 'Будь-який метод';
$_['cm_sub_amount_type']       = 'Тип суми';
$_['cm_sub_fixed']             = 'Фіксована';
$_['cm_sub_percent']           = 'Відсоток від підсумку';
$_['cm_sub_amount']            = 'Сума (− знижка)';
$_['cm_sub_methods']           = 'Для методів:';
$_['cm_sub_add']               = 'Створити рядок підсумку';
$_['cm_sub_value']             = 'Значення';
$_['cm_sub_value_hint']        = 'Фіксована сума (напр. -50) або відсоток від суми замовлення (напр. -1.3%). Від\'ємне = знижка.';
$_['cm_sub_round']             = 'Округлювати до цілих';
$_['cm_confirm_delete_subtotal'] = 'Видалити цей рядок підсумку?';

// Condition types (custom methods)
$_['cm_cond_group_customer']   = 'Покупець';
$_['cm_cond_group_cart']       = 'Кошик / сума';
$_['cm_cond_group_address']    = 'Адреса';
$_['cm_cond_group_context']    = 'Контекст';
$_['cm_cond_group_methods']    = 'Методи';
$_['cm_cond_logged_in']        = 'Користувач авторизований';
$_['cm_cond_customer_group']   = 'Група покупця';
$_['cm_cond_has_orders']       = 'У користувача є акаунт';
$_['cm_cond_total']            = 'Загальна сума';
$_['cm_cond_total_no_shipping']= 'Сума без доставки';
$_['cm_cond_total_quantity']   = 'Загальна кількість';
$_['cm_cond_total_weight']     = 'Загальна вага (кг)';
$_['cm_cond_max_weight_single']= 'Макс. вага одного товару (кг)';
$_['cm_cond_coupon_used']      = 'Використано купон';
$_['cm_cond_reward_used']      = 'Використано бонуси';
$_['cm_cond_voucher_used']     = 'Використано подарунковий сертифікат';
$_['cm_cond_products_no_shipping'] = 'Товари не потребують доставки';
$_['cm_cond_country']          = 'Країна';
$_['cm_cond_zone']             = 'Регіон';
$_['cm_cond_city']             = 'Місто';
$_['cm_cond_postcode']         = 'Індекс';
$_['cm_cond_language']         = 'Мова';
$_['cm_cond_currency']         = 'Валюта';
$_['cm_cond_store']            = 'Магазин';
$_['cm_cond_ip']               = 'IP-адреса';
$_['cm_cond_day']              = 'День тижня (0=нд)';
$_['cm_cond_time']             = 'Час (HH:MM)';
$_['cm_cond_date']             = 'Дата (РРРР-ММ-ДД)';
$_['cm_cond_payment_variant']  = 'Варіант оплати';
$_['cm_cond_shipping_variant'] = 'Варіант доставки';
