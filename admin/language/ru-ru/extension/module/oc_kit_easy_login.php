<?php
// Heading
$_['heading_title']    = 'oc-kit.com — Easy Login';

// Text
$_['text_extension']   = 'Расширения';
$_['text_success']     = 'Настройки успешно сохранены!';
$_['text_edit']        = 'Настройки модуля';
$_['text_module_name'] = 'Easy Login';
$_['text_module_description'] = 'Быстрая авторизация через Google, Telegram, Apple, Facebook, Email Magic Link и SMS OTP.';
$_['text_enabled']     = 'Включено';
$_['text_disabled']    = 'Выключено';
$_['text_yes']         = 'Да';
$_['text_no']          = 'Нет';
$_['text_log_empty']   = 'Журнал пуст';
$_['text_confirm_clear_log'] = 'Очистить весь журнал? Это действие нельзя отменить.';
$_['text_confirm_clear_old'] = 'Удалить старые записи?';
$_['text_log_cleared'] = 'Журнал очищен.';
$_['text_old_cleared'] = 'Удалено старых записей: %d';
$_['text_records_total'] = 'Всего записей: %d';

// Tabs
$_['tab_general']      = 'Общее';
$_['tab_google']       = 'Google';
$_['tab_telegram']     = 'Telegram';
$_['tab_apple']        = 'Apple';
$_['tab_facebook']     = 'Facebook';
$_['tab_email_magic']  = 'Email Magic Link';
$_['tab_sms_otp']      = 'SMS OTP';
$_['tab_log']          = 'Журнал';
$_['tab_faq']          = 'FAQ';
$_['tab_license']      = 'Лицензия';

// General — section titles
$_['text_section_status']       = 'Статус модуля';
$_['text_section_display']      = 'Где показывать кнопки логина';
$_['text_section_policies']     = 'Политики безопасности и регистрации';
$_['text_section_rate_limits']  = 'Ограничения частоты запросов';
$_['text_section_log_settings'] = 'Настройки журнала';

// General — fields
$_['entry_status']                       = 'Статус';
$_['entry_display_in_popup']             = 'Popup логина';
$_['help_display_in_popup']              = 'Внедрять кнопки во всплывающее окно логина (через OCMOD).';
$_['entry_display_on_login_page']        = 'Страница <code>/account/login</code>';
$_['entry_display_on_register_page']     = 'Страница <code>/account/register</code>';
$_['entry_display_on_account_page']      = 'Страница личного кабинета';
$_['help_display_on_account_page']       = 'Показывать секцию "Привязанные аккаунты" с управлением провайдерами.';
$_['entry_require_phone_after_oauth']    = 'Требовать телефон после OAuth-логина';
$_['help_require_phone_after_oauth']     = 'Если новый пользователь зашёл через OAuth без телефона — форсировать заполнение перед доступом к кабинету.';
$_['entry_default_redirect_route']       = 'Редирект после входа';
$_['help_default_redirect_route']        = 'OC route куда перенаправить после успешного входа. Оставьте пустым для стандартного поведения (account/account). Примеры: <code>common/home</code>, <code>account/account</code>, <code>information/contact</code>.';
$_['entry_log_retention_days']           = 'Хранить журнал (дней)';
$_['help_log_retention_days']            = 'Записи старше указанного количества дней будут удаляться автоматически кроном.';
$_['entry_rate_limit_per_ip_per_hour']   = 'Запросов с одного IP в час';
$_['entry_rate_limit_per_email_per_hour']= 'Запросов на один email/телефон в час';
$_['entry_trust_cf_ip']                  = 'Доверять Cloudflare-IP';
$_['help_trust_cf_ip']                   = 'Если сайт за Cloudflare — включите, чтобы для rate-limit использовался реальный IP клиента (CF-Connecting-IP) вместо IP CF-прокси. Без CF — оставьте выключенным.';

// Log — table headers
$_['column_provider']    = 'Провайдер';
$_['column_status']      = 'Статус';
$_['column_email']       = 'Email';
$_['column_customer_id'] = 'Customer';
$_['column_ip']          = 'IP';
$_['column_user_agent']  = 'User Agent';
$_['column_error']       = 'Ошибка';
$_['column_created_at']  = 'Дата';

// Log — filters
$_['entry_filter_provider']  = 'Провайдер';
$_['entry_filter_status']    = 'Статус';
$_['entry_filter_email']     = 'Email';
$_['entry_filter_ip']        = 'IP';
$_['entry_filter_date_from'] = 'Дата с';
$_['entry_filter_date_to']   = 'Дата по';
$_['button_filter']          = 'Фильтровать';
$_['button_reset_filter']    = 'Сбросить';
$_['button_clear_log']       = 'Очистить весь журнал';
$_['button_clear_old']       = 'Удалить старые';

// Log — status badges
$_['status_success']      = 'Успех';
$_['status_failed']       = 'Ошибка';
$_['status_rate_limited'] = 'Ограничено';
$_['status_linked']       = 'Привязано';
$_['status_registered']   = 'Зарегистрировано';

// Log — stats
$_['text_stats_total']        = 'Всего';
$_['text_stats_success']      = 'Успех';
$_['text_stats_failed']       = 'Ошибки';
$_['text_stats_rate_limited'] = 'Ограничено';
$_['text_stats_linked']       = 'Привязки';
$_['text_stats_registered']   = 'Регистрации';

// Google
$_['text_section_google_credentials'] = 'Credentials';
$_['text_section_google_appearance']  = 'Внешний вид';
$_['entry_google_enabled']            = 'Включить Google';
$_['entry_google_mode']               = 'Режим';
$_['entry_google_client_id']          = 'Client ID';
$_['entry_google_client_secret']      = 'Client Secret';
$_['entry_google_one_tap_position']   = 'Позиция One Tap';
$_['entry_google_button_theme']       = 'Тема кнопки';
$_['entry_google_button_text']        = 'Текст кнопки';
$_['help_google_callback_url']        = 'Скопируйте этот URL в Google Cloud Console → Authorized redirect URIs:';
$_['help_google_mode']                = 'Кнопка — обычный OAuth-логин через клик. One Tap — нативный попап Google в углу экрана. Оба — кнопка + One Tap одновременно.';
$_['mode_button']                     = 'Только кнопка';
$_['mode_one_tap']                    = 'Только One Tap';
$_['mode_both']                       = 'Кнопка + One Tap';
$_['pos_top_right']                   = 'Сверху справа';
$_['pos_top_left']                    = 'Сверху слева';
$_['pos_bottom_right']                = 'Снизу справа';
$_['pos_bottom_left']                 = 'Снизу слева';
$_['entry_one_tap_top_offset']        = 'Отступ сверху/снизу (px)';
$_['entry_one_tap_side_offset']       = 'Отступ сбоку (px)';
$_['help_one_tap_offset']             = 'Смещение окна One Tap от края экрана. Полезно если хедер сайта перекрывает попап. Default: top/bottom = 0, side = 20.';
$_['theme_outline']                   = 'Контурная';
$_['theme_filled_blue']               = 'Заливка (синяя)';
$_['theme_filled_black']              = 'Заливка (чёрная)';
$_['btn_text_signin_with']            = 'Войти через Google';
$_['btn_text_signup_with']            = 'Регистрация через Google';
$_['btn_text_continue_with']          = 'Продолжить с Google';

// Telegram
$_['text_section_telegram_credentials'] = 'Credentials';
$_['text_section_telegram_appearance']  = 'Внешний вид';
$_['entry_telegram_enabled']            = 'Включить Telegram';
$_['entry_telegram_bot_token']          = 'Bot Token';
$_['entry_telegram_bot_username']       = 'Bot Username';
$_['entry_telegram_button_size']        = 'Размер кнопки';
$_['entry_telegram_request_phone']      = 'Запрашивать телефон';
$_['help_telegram_setup']               = 'Создайте бота через @BotFather в Telegram, получите токен.';
$_['help_telegram_domain']              = 'После создания бота выполните в @BotFather: /setdomain — и укажите домен сайта (без https://).';
$_['help_telegram_bot_username']        = 'Username бота без @ (например, MyShopBot).';
$_['help_telegram_request_phone']       = 'Если включено, виджет попросит пользователя поделиться номером телефона.';
$_['btn_size_large']                    = 'Большая';
$_['btn_size_medium']                   = 'Средняя';
$_['btn_size_small']                    = 'Маленькая';

// Apple
$_['text_section_apple_credentials'] = 'Credentials';
$_['text_section_apple_appearance']  = 'Внешний вид';
$_['entry_apple_enabled']            = 'Включить Apple';
$_['entry_apple_service_id']         = 'Service ID';
$_['entry_apple_team_id']            = 'Team ID';
$_['entry_apple_key_id']             = 'Key ID';
$_['entry_apple_private_key']        = 'Private Key (.p8)';
$_['entry_apple_button_theme']       = 'Тема кнопки';
$_['help_apple_setup']                = 'Создайте Service ID + Sign in with Apple key в Apple Developer Console. Нужен платный аккаунт ($99/год).';
$_['help_apple_private_key']          = 'Вставьте содержимое файла .p8 (полностью, со строками BEGIN/END PRIVATE KEY).';
$_['theme_black']                     = 'Чёрная';
$_['theme_white']                     = 'Белая';
$_['theme_white_outline']             = 'Белая с обводкой';

// Facebook
$_['text_section_facebook_credentials'] = 'Credentials';
$_['text_section_facebook_appearance']  = 'Внешний вид';
$_['entry_facebook_enabled']            = 'Включить Facebook';
$_['entry_facebook_app_id']             = 'App ID';
$_['entry_facebook_app_secret']         = 'App Secret';
$_['entry_facebook_button_size']        = 'Размер кнопки';
$_['help_facebook_setup']               = 'Создайте приложение в Meta for Developers, настройте Facebook Login → Valid OAuth Redirect URIs.';

// Email Magic
$_['text_email_magic_description']      = '<strong>Email Magic Link</strong> — вход без пароля. Пользователь вводит email, получает письмо с одноразовой ссылкой; клик по ссылке выполняет вход в аккаунт. Ссылка действует ограниченное время (настраивается ниже) и работает только один раз.';
$_['text_section_email_magic_settings'] = 'Настройки';
$_['text_section_email_magic_template'] = 'Шаблон письма';
$_['entry_email_magic_enabled']         = 'Включить Email Magic Link';
$_['entry_email_magic_token_ttl_minutes'] = 'Срок действия ссылки (минут)';
$_['entry_email_magic_from_name']       = 'Имя отправителя';
$_['entry_email_magic_subject']         = 'Тема письма';
$_['entry_email_magic_template']        = 'HTML-шаблон';
$_['help_email_magic_template']         = 'Доступные плейсхолдеры: {magic_url}, {ttl_minutes}, {store_name}.';

// SMS OTP
$_['text_section_sms_otp_settings']  = 'Настройки';
$_['text_section_sms_otp_text']      = 'Текст SMS';
$_['entry_sms_otp_enabled']          = 'Включить SMS OTP';
$_['entry_sms_otp_token']            = 'TurboSMS Token';
$_['entry_sms_otp_sender']           = 'Alpha-name (отправитель)';
$_['entry_sms_otp_code_length']      = 'Длина кода';
$_['entry_sms_otp_ttl_minutes']      = 'Срок действия кода (минут)';
$_['entry_sms_otp_max_attempts']     = 'Макс. попыток ввода';
$_['entry_sms_otp_message']          = 'Текст SMS';
$_['help_sms_otp_message']           = 'Плейсхолдер: {code} — будет заменён на сгенерированный код.';

// FAQ
$_['text_faq_intro']    = 'Готовится. В следующих обновлениях здесь будут инструкции по получению credentials для каждого провайдера.';

// Buttons
$_['button_save']      = 'Сохранить';
$_['button_cancel']    = 'Отмена';
$_['button_back']      = 'Назад к настройкам';

// Account-linked (frontend section)
$_['heading_linked']     = 'Привязанные аккаунты';
$_['text_no_identities'] = 'Нет привязанных аккаунтов.';
$_['text_link_more']     = 'Добавить ещё один аккаунт:';
$_['text_confirm_unlink']= 'Отвязать этот аккаунт?';
$_['button_unlink']      = 'Отвязать';

// Errors
$_['error_permission'] = 'У вас нет прав для изменения этого модуля!';
$_['error_network']    = 'Ошибка сети. Попробуйте ещё раз.';
$_['js_error_license_key_required'] = 'Введите лицензионный ключ';
$_['js_error_no_activate_url']      = 'URL активации не задан';
$_['text_https_required_title']     = 'Требуется HTTPS';
$_['text_https_required_body']      = 'Easy Login работает только на сайте с HTTPS — Google/Apple/Facebook отказывают в callback на http, а cookie Apple требует Secure;SameSite=None. Настройте SSL перед использованием.';

// License
$_['text_license_title']         = 'Активация лицензии';
$_['text_license_subtitle']      = 'Введите лицензионный ключ от oc-kit.com чтобы активировать модуль';
$_['text_license_status_active']  = 'Активна';
$_['text_license_status_invalid'] = 'Невалидный ключ';
$_['text_license_status_grace']   = 'Временный доступ (API недоступен)';
$_['text_license_status_trial']   = 'Trial';
$_['text_license_status_expired'] = 'Просрочено';
$_['text_license_status_not_validated'] = 'Не активирована';
$_['text_license_active']        = 'Лицензия активирована! Перенаправляем…';
$_['text_license_invalid']       = 'Невалидный ключ. Проверьте правильность ввода.';
$_['text_license_api_error']     = 'API недоступен. Попробуйте ещё раз позже.';
$_['text_license_domain']        = 'Домен';
$_['text_license_version']       = 'Версия';
$_['text_license_get_key']       = 'Купить лицензию на oc-kit.com';
$_['entry_license_key']          = 'Лицензионный ключ';
$_['button_activate']            = 'Активировать';
