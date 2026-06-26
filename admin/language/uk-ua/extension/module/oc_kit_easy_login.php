<?php
// Heading
$_['heading_title']    = 'oc-kit.com — Easy Login';

// Text
$_['text_extension']   = 'Розширення';
$_['text_success']     = 'Налаштування успішно збережено!';
$_['text_edit']        = 'Налаштування модуля';
$_['text_module_name'] = 'Easy Login';
$_['text_module_description'] = 'Швидка авторизація через Google, Telegram, Apple, Facebook, Email Magic Link і SMS OTP.';
$_['text_enabled']     = 'Увімкнено';
$_['text_disabled']    = 'Вимкнено';
$_['text_yes']         = 'Так';
$_['text_no']          = 'Ні';
$_['text_log_empty']   = 'Журнал порожній';
$_['text_confirm_clear_log'] = 'Очистити весь журнал? Цю дію не можна скасувати.';
$_['text_confirm_clear_old'] = 'Видалити старі записи?';
$_['text_log_cleared'] = 'Журнал очищено.';
$_['text_old_cleared'] = 'Видалено старих записів: %d';
$_['text_records_total'] = 'Усього записів: %d';

// Tabs
$_['tab_general']      = 'Загальне';
$_['tab_google']       = 'Google';
$_['tab_telegram']     = 'Telegram';
$_['tab_apple']        = 'Apple';
$_['tab_facebook']     = 'Facebook';
$_['tab_email_magic']  = 'Email Magic Link';
$_['tab_sms_otp']      = 'SMS OTP';
$_['tab_log']          = 'Журнал';
$_['tab_faq']          = 'FAQ';
$_['tab_license']      = 'Ліцензія';

// General — section titles & descriptions
$_['text_section_status']         = 'Статус модуля';
$_['text_section_display']        = 'Де показувати кнопки логіну';
$_['text_section_policies']       = 'Політики безпеки і реєстрації';
$_['text_section_rate_limits']    = 'Обмеження частоти запитів';
$_['text_section_log_settings']   = 'Налаштування журналу';

// General — fields
$_['entry_status']                       = 'Статус';
$_['entry_display_in_popup']             = 'Popup логіну';
$_['help_display_in_popup']              = 'Інжектити кнопки в спливне вікно логіну (через OCMOD).';
$_['entry_display_on_login_page']        = 'Сторінка <code>/account/login</code>';
$_['entry_display_on_register_page']     = 'Сторінка <code>/account/register</code>';
$_['entry_display_on_account_page']      = 'Сторінка особистого кабінету';
$_['help_display_on_account_page']       = 'Показувати секцію "Привʼязані акаунти" з керуванням провайдерами.';
$_['entry_require_phone_after_oauth']    = 'Вимагати телефон після OAuth-логіну';
$_['help_require_phone_after_oauth']     = 'Якщо новий користувач залогінився через OAuth і не має телефону — форсити заповнення перед доступом до кабінету.';
$_['entry_default_redirect_route']       = 'Редірект після входу';
$_['help_default_redirect_route']        = 'OC route куди перенаправити після успішного входу. Залиште порожнім для стандартної поведінки (account/account). Приклади: <code>common/home</code>, <code>account/account</code>, <code>information/contact</code>.';
$_['entry_log_retention_days']           = 'Зберігати журнал (днів)';
$_['help_log_retention_days']            = 'Записи старші за вказану кількість днів видалятимуться автоматично кроном.';
$_['entry_rate_limit_per_ip_per_hour']   = 'Запитів з одного IP за годину';
$_['entry_rate_limit_per_email_per_hour']= 'Запитів на один email/телефон за годину';
$_['entry_trust_cf_ip']                  = 'Довіряти Cloudflare-IP';
$_['help_trust_cf_ip']                   = 'Якщо сайт за Cloudflare — увімкніть, щоб для rate-limit використовувався реальний IP клієнта (CF-Connecting-IP) замість IP CF-проксі. Без CF — залиште вимкненим.';

// Log — table headers
$_['column_provider']    = 'Провайдер';
$_['column_status']      = 'Статус';
$_['column_email']       = 'Email';
$_['column_customer_id'] = 'Customer';
$_['column_ip']          = 'IP';
$_['column_user_agent']  = 'User Agent';
$_['column_error']       = 'Помилка';
$_['column_created_at']  = 'Дата';

// Log — filters
$_['entry_filter_provider']  = 'Провайдер';
$_['entry_filter_status']    = 'Статус';
$_['entry_filter_email']     = 'Email';
$_['entry_filter_ip']        = 'IP';
$_['entry_filter_date_from'] = 'Дата від';
$_['entry_filter_date_to']   = 'Дата до';
$_['button_filter']          = 'Фільтрувати';
$_['button_reset_filter']    = 'Скинути';
$_['button_clear_log']       = 'Очистити весь журнал';
$_['button_clear_old']       = 'Видалити старі';

// Log — status badges
$_['status_success']      = 'Успіх';
$_['status_failed']       = 'Помилка';
$_['status_rate_limited'] = 'Обмежено';
$_['status_linked']       = 'Привʼязано';
$_['status_registered']   = 'Зареєстровано';

// Log — stats
$_['text_stats_total']        = 'Усього';
$_['text_stats_success']      = 'Успіх';
$_['text_stats_failed']       = 'Помилки';
$_['text_stats_rate_limited'] = 'Обмежено';
$_['text_stats_linked']       = 'Привʼязки';
$_['text_stats_registered']   = 'Реєстрації';

// Google
$_['text_section_google_credentials'] = 'Credentials';
$_['text_section_google_appearance']  = 'Зовнішній вигляд';
$_['entry_google_enabled']            = 'Увімкнути Google';
$_['entry_google_mode']               = 'Режим';
$_['entry_google_client_id']          = 'Client ID';
$_['entry_google_client_secret']      = 'Client Secret';
$_['entry_google_one_tap_position']   = 'Позиція One Tap';
$_['entry_google_button_theme']       = 'Тема кнопки';
$_['entry_google_button_text']        = 'Текст кнопки';
$_['help_google_callback_url']        = 'Скопіюйте цю URL у Google Cloud Console → Authorized redirect URIs:';
$_['help_google_mode']                = 'Кнопка — звичайний OAuth-логін через клік. One Tap — нативний попап Google у куті екрану. Обидва — кнопка + One Tap одночасно.';
$_['mode_button']                     = 'Тільки кнопка';
$_['mode_one_tap']                    = 'Тільки One Tap';
$_['mode_both']                       = 'Кнопка + One Tap';
$_['pos_top_right']                   = 'Верхній правий';
$_['pos_top_left']                    = 'Верхній лівий';
$_['pos_bottom_right']                = 'Нижній правий';
$_['pos_bottom_left']                 = 'Нижній лівий';
$_['entry_one_tap_top_offset']        = 'Відступ зверху/знизу (px)';
$_['entry_one_tap_side_offset']       = 'Відступ збоку (px)';
$_['help_one_tap_offset']             = 'Зміщення вікна One Tap від краю екрана. Корисно якщо хедер сайту перекриває попап. Default: top/bottom = 0, side = 20.';
$_['theme_outline']                   = 'Контурна';
$_['theme_filled_blue']               = 'Залита (синя)';
$_['theme_filled_black']              = 'Залита (чорна)';
$_['btn_text_signin_with']            = 'Увійти через Google';
$_['btn_text_signup_with']            = 'Реєстрація через Google';
$_['btn_text_continue_with']          = 'Продовжити з Google';

// Telegram
$_['text_section_telegram_credentials'] = 'Credentials';
$_['text_section_telegram_appearance']  = 'Зовнішній вигляд';
$_['entry_telegram_enabled']            = 'Увімкнути Telegram';
$_['entry_telegram_bot_token']          = 'Bot Token';
$_['entry_telegram_bot_username']       = 'Bot Username';
$_['entry_telegram_button_size']        = 'Розмір кнопки';
$_['entry_telegram_request_phone']      = 'Запитувати телефон';
$_['help_telegram_setup']               = 'Створіть бота через @BotFather у Telegram, отримайте токен.';
$_['help_telegram_domain']              = 'Після створення бота виконайте у @BotFather: /setdomain — і вкажіть домен сайту (без https://).';
$_['help_telegram_bot_username']        = 'Username бота без @ (наприклад, MyShopBot).';
$_['help_telegram_request_phone']       = 'Якщо увімкнено, віджет попросить користувача поділитися номером телефону.';
$_['btn_size_large']                    = 'Великий';
$_['btn_size_medium']                   = 'Середній';
$_['btn_size_small']                    = 'Малий';

// Apple
$_['text_section_apple_credentials'] = 'Credentials';
$_['text_section_apple_appearance']  = 'Зовнішній вигляд';
$_['entry_apple_enabled']            = 'Увімкнути Apple';
$_['entry_apple_service_id']         = 'Service ID';
$_['entry_apple_team_id']            = 'Team ID';
$_['entry_apple_key_id']             = 'Key ID';
$_['entry_apple_private_key']        = 'Private Key (.p8)';
$_['entry_apple_button_theme']       = 'Тема кнопки';
$_['help_apple_setup']                = 'Створіть Service ID + Sign in with Apple key у Apple Developer Console. Потрібен платний акаунт ($99/рік).';
$_['help_apple_private_key']          = 'Вставте вміст файлу .p8 (повністю, з рядками BEGIN/END PRIVATE KEY).';
$_['theme_black']                     = 'Чорна';
$_['theme_white']                     = 'Біла';
$_['theme_white_outline']             = 'Біла з обведенням';

// Facebook
$_['text_section_facebook_credentials'] = 'Credentials';
$_['text_section_facebook_appearance']  = 'Зовнішній вигляд';
$_['entry_facebook_enabled']            = 'Увімкнути Facebook';
$_['entry_facebook_app_id']             = 'App ID';
$_['entry_facebook_app_secret']         = 'App Secret';
$_['entry_facebook_button_size']        = 'Розмір кнопки';
$_['help_facebook_setup']               = 'Створіть додаток у Meta for Developers, налаштуйте Facebook Login → Valid OAuth Redirect URIs.';

// Email Magic
$_['text_email_magic_description']      = '<strong>Email Magic Link</strong> — вхід без пароля. Користувач вводить email, отримує лист із одноразовим посиланням; клік на посилання логінить його в акаунт. Посилання має обмежений термін дії (налаштовується нижче) і працює лише один раз.';
$_['text_section_email_magic_settings'] = 'Налаштування';
$_['text_section_email_magic_template'] = 'Шаблон листа';
$_['entry_email_magic_enabled']         = 'Увімкнути Email Magic Link';
$_['entry_email_magic_token_ttl_minutes'] = 'Термін дії посилання (хвилин)';
$_['entry_email_magic_from_name']       = 'Ім\'я відправника';
$_['entry_email_magic_subject']         = 'Тема листа';
$_['entry_email_magic_template']        = 'HTML-шаблон';
$_['help_email_magic_template']         = 'Доступні плейсхолдери: {magic_url}, {ttl_minutes}, {store_name}.';

// SMS OTP
$_['text_section_sms_otp_settings']  = 'Налаштування';
$_['text_section_sms_otp_text']      = 'Текст SMS';
$_['entry_sms_otp_enabled']          = 'Увімкнути SMS OTP';
$_['entry_sms_otp_token']            = 'TurboSMS Token';
$_['entry_sms_otp_sender']           = 'Alpha-name (відправник)';
$_['entry_sms_otp_code_length']      = 'Довжина коду';
$_['entry_sms_otp_ttl_minutes']      = 'Термін дії коду (хвилин)';
$_['entry_sms_otp_max_attempts']     = 'Макс. спроб введення';
$_['entry_sms_otp_message']          = 'Текст SMS';
$_['help_sms_otp_message']           = 'Плейсхолдер: {code} — буде замінено на згенерований код.';

// FAQ
$_['text_faq_intro']    = 'Готується. У наступних оновленнях тут будуть інструкції з отримання credentials для кожного провайдера.';

// Buttons
$_['button_save']      = 'Зберегти';
$_['button_cancel']    = 'Скасувати';
$_['button_back']      = 'Назад до налаштувань';

// Account-linked (frontend section)
$_['heading_linked']     = 'Підвʼязані акаунти';
$_['text_no_identities'] = 'Немає підвʼязаних акаунтів.';
$_['text_link_more']     = 'Додати ще один акаунт:';
$_['text_confirm_unlink']= 'Відвʼязати цей акаунт?';
$_['button_unlink']      = 'Відвʼязати';

// Errors
$_['error_permission'] = 'У вас немає прав для зміни цього модуля!';
$_['error_network']    = 'Помилка мережі. Спробуйте ще раз.';
$_['js_error_license_key_required'] = 'Введіть ліцензійний ключ';
$_['js_error_no_activate_url']      = 'URL активації не задано';
$_['text_https_required_title']     = 'Потрібен HTTPS';
$_['text_https_required_body']      = 'Easy Login може працювати тільки на сайті з HTTPS — Google/Apple/Facebook відмовляють у callback на http, а cookie Apple вимагає Secure;SameSite=None. Налаштуйте SSL перед використанням.';

// License
$_['text_license_title']         = 'Активація ліцензії';
$_['text_license_subtitle']      = 'Введіть ліцензійний ключ від oc-kit.com щоб активувати модуль';
$_['text_license_status_active']  = 'Активна';
$_['text_license_status_invalid'] = 'Невалідний ключ';
$_['text_license_status_grace']   = 'Тимчасовий доступ (API недоступний)';
$_['text_license_status_trial']   = 'Trial';
$_['text_license_status_expired'] = 'Прострочено';
$_['text_license_status_not_validated'] = 'Не активовано';
$_['text_license_active']        = 'Ліцензію активовано! Перенаправляємо…';
$_['text_license_invalid']       = 'Невалідний ключ. Перевірте правильність вводу.';
$_['text_license_api_error']     = 'API недоступний. Спробуйте ще раз пізніше.';
$_['text_license_domain']        = 'Домен';
$_['text_license_version']       = 'Версія';
$_['text_license_get_key']       = 'Купити ліцензію на oc-kit.com';
$_['entry_license_key']          = 'Ліцензійний ключ';
$_['button_activate']            = 'Активувати';
