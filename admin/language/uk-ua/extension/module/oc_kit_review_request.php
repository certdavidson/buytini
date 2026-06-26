<?php
// Review Request | © 2026 oc-kit.com | https://oc-kit.com

// Heading
$_['heading_title']              = 'oc-kit.com — Review Request';
$_['heading_title_simple']       = 'Review Request';

// Text
$_['text_extension']             = 'Розширення';
$_['text_success']               = 'Налаштування збережено!';
$_['text_edit']                  = 'Налаштування модуля';
$_['text_enabled']               = 'Увімк.';
$_['text_disabled']              = 'Вимкн.';
$_['text_yes']                   = 'Так';
$_['text_no']                    = 'Ні';
$_['text_hours']                 = 'Годин';
$_['text_days']                  = 'Днів';
$_['text_anchor']                = 'На сторінку товару (якір #review)';
$_['text_page']                  = 'На спеціальну сторінку відгуків';
$_['text_oc_reward_disabled']    = '⚠️ Система балів OpenCart вимкнена. Увімкніть її в Система → Налаштування → Магазин.';

// Tabs
$_['tab_general']                = 'Загальне';
$_['tab_mail']                   = 'Лист';
$_['tab_template']               = 'Шаблон листа';
$_['tab_test']                   = 'Тест';
$_['tab_log']                    = 'Журнал відправки';
$_['tab_orders']                 = 'Замовлення';

// Entries
$_['entry_status']               = 'Статус модуля';
$_['entry_trigger_statuses']     = 'Тригерні статуси замовлення';
$_['entry_delay_value']          = 'Затримка відправки';
$_['entry_max_attempts']         = 'Макс. спроб при помилці';
$_['entry_send_guests']          = 'Надсилати гостям';
$_['entry_skip_reviewed']        = 'Не надсилати якщо є відгуки на товари';
$_['entry_cron_token']           = 'Cron-токен';
$_['entry_products_status']      = 'Увімкнути блок товарів у листі';
$_['entry_max_products']         = 'Макс. товарів у листі';
$_['entry_skip_no_image']        = 'Не показувати товари без зображення';
$_['entry_link_mode']            = 'Тип посилань на відгуки';
$_['entry_anchor']               = 'Якір посилання';
$_['entry_token_days']           = 'Термін дії токена (днів)';
$_['entry_token_onetime']        = 'Токен одноразовий';
$_['entry_page_google_cta']      = 'Google CTA на фінальному екрані сторінки';
$_['entry_points_status']        = 'Нараховувати бонусні бали';
$_['entry_points_per_review']    = 'Балів за кожен відгук';
$_['entry_points_bonus']         = 'Бонус за всі відгуки замовлення';
$_['entry_points_min_chars']     = 'Мін. символів тексту для нарахування';
$_['entry_points_show_in_mail']  = 'Показувати бали у листі';
$_['entry_points_show_on_page']  = 'Показувати бали на сторінці відгуків';
$_['entry_sms_status']           = 'Статус SMS (TurboSMS)';
$_['entry_sms_token']            = 'TurboSMS API Token';
$_['entry_sms_sender']           = 'Відправник SMS (Sender ID)';
$_['entry_sms_short_link']       = 'Скорочувати посилання модулем oc-kit.com — Short Links';
$_['entry_sms_template']         = 'Шаблон SMS';
$_['tab_sms']                    = 'SMS';
$_['help_sms_short_link_off']    = 'Модуль <strong>oc-kit.com — Short Links</strong> не встановлений. Посилання у SMS будуть повними.';
$_['entry_google_status']        = 'Увімкнути блок Google у листі';
$_['entry_google_url']           = 'Посилання на Google Reviews';
$_['entry_from_name']            = 'Від кого (ім\'я)';
$_['entry_from_email']           = 'Від кого (email)';
$_['entry_subject']              = 'Тема листа';
$_['entry_footer']               = 'Підпис листа';
$_['entry_template']             = 'Контент листа (необов\'язково)';
$_['entry_test_email']           = 'Email для тесту';
$_['entry_test_firstname']       = 'Ім\'я клієнта (для тесту)';
$_['entry_test_lang']            = 'Мова листа';
$_['entry_test_order_id']        = 'ID замовлення (опційно)';
$_['entry_points_per_review_type'] = 'Тип нарахування балів';
$_['entry_email_logo']           = 'Логотип у листі';
$_['entry_email_logo_image']     = 'Зображення';
$_['entry_email_logo_text']      = 'Текст логотипу';
$_['entry_google_title']         = 'Заголовок';
$_['entry_google_text']          = 'Підзаголовок';
$_['entry_google_btn']           = 'Текст кнопки';
$_['entry_btn_page_text']        = 'Текст кнопки «Залишити відгуки»';

// Help text
$_['help_cron']                  = 'Команда для crontab: <code>*/30 * * * * php ' . DIR_APPLICATION . '../crons/cron_review_request.php</code>';
$_['help_subject_vars']          = 'Доступні змінні: {store_name}, {firstname}, {order_id}';
$_['help_template'] = 'Якщо заповнено — замінює дефолтний блок вітання та товарів всередині HTML-скелету листа (шапка, стилі та підпис завжди з шаблону модуля).<div>Змінні: <code>{firstname}</code> <code>{order_id}</code> <code>{products_block}</code> <code>{google_block}</code> <code>{review_url}</code> <code>{review_page_url}</code> <code>{store_name}</code> <code>{store_url}</code> <code>{unsubscribe_url}</code></div>';
$_['help_points_bonus']          = '0 = вимкнено. Бонус нараховується якщо клієнт залишив відгук на кожен товар замовлення.';
$_['help_points_min_chars']      = '0 = не перевіряти';
$_['help_anchor']                = 'Додається до URL товару. Наприклад: #review або #tab-review';
$_['help_google_url']            = 'URL вигляду: https://g.page/r/XXXXXXXXXXXXXXXX/review';
$_['help_email_logo_text']       = 'Залиште порожнім — відображатиметься лише зображення';
$_['help_btn_page_text']         = 'Лише для режиму «спільна сторінка відгуків»';
$_['help_sms_token']             = 'Отримайте токен у <a href="https://turbosms.ua" target="_blank">turbosms.ua</a> → Підключення → HTTP API';
$_['help_sms_sender']            = 'До 11 латинських символів або 16 цифр. Має бути зареєстрований у TurboSMS.';
$_['help_sms_short_link_on']     = 'Скорочує посилання у SMS через модуль <strong>oc-kit.com — Short Links</strong>. Економить символи.';
$_['help_sms_template']          = 'Доступні змінні: <code>{firstname}</code><code>{order_id}</code><code>{url}</code><code>{store_name}</code><br>Кирилиця: 1 SMS = 70 символів. Підтримуються багаточастинні SMS.';
$_['help_test_order_id']         = 'Якщо вказано — лист відправиться з реальними товарами замовлення';

// Text helpers
$_['text_fixed']                 = 'Фіксована кількість';
$_['text_percent']               = 'Відсоток від ціни товару';
$_['text_google_lang_block']     = 'Текст блоку Google по мовах';
$_['text_stats_title']           = 'Статистика';
$_['text_date_from']             = 'Від';
$_['text_date_to']               = 'До';
$_['text_records']               = 'записів';
$_['text_no_orders']             = 'Замовлень не знайдено';
$_['text_no_entries']            = 'Записів не знайдено';
$_['help_multiselect']           = 'Утримуйте Ctrl для множинного вибору';
$_['placeholder_google_title']   = 'Оцініть наш магазин у Google';
$_['placeholder_google_text']    = 'Ваш відгук допомагає іншим покупцям';
$_['placeholder_google_btn']     = '⭐ Залишити відгук у Google';
$_['placeholder_subject']        = '{firstname}, залиште відгук про замовлення #{order_id}';
$_['placeholder_btn_page_text']  = '⭐ Залишити відгуки про товари';
$_['placeholder_google_url']     = 'https://g.page/r/XXXXXXXXXXXXXXXX/review';
$_['placeholder_sms_token']      = 'Bearer token із кабінету turbosms.ua';
$_['placeholder_sms_sender']     = 'MyStore';

// Buttons
$_['button_save']                = 'Зберегти';
$_['button_cancel']              = 'Скасувати';
$_['button_test_send']           = 'Надіслати тестовий лист';
$_['button_preview']             = 'Переглянути HTML';
$_['button_insert_default']      = 'Вставити стандартний шаблон';
$_['button_retry']               = 'Повторити';
$_['button_delete']              = 'Видалити';
$_['button_clear_log']           = 'Очистити журнал';
$_['button_export_csv']          = 'Експортувати CSV';
$_['button_enqueue']             = 'В чергу';
$_['button_send_now']            = 'Відправити зараз';
$_['button_skip']                = 'Скасувати';
$_['button_view_reviews']        = 'Відгуки';
$_['button_bulk_apply']          = 'Застосувати';
$_['button_select_all']          = 'Вибрати всі';
$_['button_deselect_all']        = 'Зняти всі';

// Log columns
$_['column_id']                  = 'ID';
$_['column_order_id']            = 'Замовлення';
$_['column_email']               = 'Email';
$_['column_firstname']           = 'Клієнт';
$_['column_status']              = 'Статус';
$_['column_triggered_at']        = 'Дата тригера';
$_['column_sent_at']             = 'Дата відправки';
$_['column_attempts']            = 'Спроби';
$_['column_error']               = 'Помилка';
$_['column_reviews']             = 'Відгуків';
$_['column_order_status']        = 'Статус замовлення';
$_['column_queue_status']        = 'Статус відправки';
$_['column_date']                = 'Дата замовлення';
$_['column_actions']             = 'Дії';

// Queue statuses
$_['status_pending']             = 'В черзі';
$_['status_sent']                = 'Надіслано';
$_['status_failed']              = 'Помилка';
$_['status_skipped']             = 'Скасовано';
$_['status_not_queued']          = 'Не в черзі';

// Stats
$_['text_stats_sent']            = 'Надіслано';
$_['text_stats_pending']         = 'В черзі';
$_['text_stats_failed']          = 'Помилки';
$_['text_stats_period']          = '(за 30 днів)';

// Bulk actions
$_['text_bulk_enqueue']          = 'Поставити в чергу';
$_['text_bulk_send_now']         = 'Відправити зараз';
$_['text_bulk_skip']             = 'Скасувати';
$_['text_bulk_retry']            = 'Повторити';

// Ajax response messages
$_['text_mail_sent']             = 'Лист відправлено!';
$_['text_mail_error_prefix']     = 'Помилка: ';
$_['text_unknown_error']         = 'невідома помилка';
$_['js_copied']                  = 'Скопійовано!';

// Errors
$_['error_permission']           = 'Недостатньо прав для редагування модуля.';
$_['error_enqueue_failed']       = 'Не вдалося додати замовлення до черги відправки.';

// Ліцензія
$_['tab_license']                        = 'Ліцензія';
$_['entry_license_key']                  = 'Ліцензійний ключ';
$_['button_activate']                    = 'Активувати';
$_['button_recheck']                     = 'Перевірити знову';
$_['text_license_active']                = 'Ліцензія активна';
$_['text_license_trial']                 = 'Пробний період: %d днів залишилось';
$_['text_license_grace']                 = 'API недоступний — пільговий період: %d днів залишилось';
$_['text_license_invalid']               = 'Ліцензія недійсна або відкликана.';
$_['text_license_expired']               = 'Пробний період закінчився. Придбайте ліцензію.';
$_['text_license_not_validated']         = 'Ключ ще не перевірено. Натисніть «Активувати».';
$_['text_license_domain']                = 'Домен';
$_['text_license_plan']                  = 'Тариф';
$_['text_license_updates_until']         = 'Оновлення включені до';
$_['text_license_updates_expired']       = 'Підписка на оновлення закінчилась.';
$_['text_license_update_available']      = 'Доступне оновлення: v%s';
$_['text_license_next_check']            = 'Наступна перевірка';
$_['text_license_version']               = 'Встановлена версія';
$_['text_license_buy']                   = 'Придбати ліцензію →';
$_['text_license_api_error']             = 'Не вдалося підключитись до сервера ліцензій. Спробуйте пізніше.';
$_['js_license_activating']              = 'Активація...';
$_['error_trigger_statuses']     = 'Виберіть хоча б один тригерний статус замовлення.';

// JS strings (passed as JSON to admin.js via lang_js)
$_['js_sending']                 = 'Відправка...';
$_['js_success']                 = '✅ Відправлено!';
$_['js_error']                   = '❌ Помилка';
$_['js_network_error']           = '❌ Помилка мережі';
$_['js_network_error_retry']     = 'Помилка мережі. Спробуйте ще раз.';
$_['js_select_orders']           = 'Виберіть хоча б одне замовлення.';
$_['js_processing']              = 'Обробка...';
$_['js_processed']               = 'Оброблено:';
$_['js_sent_count']              = 'відправлено:';
$_['js_errors_count']            = 'помилок:';
$_['js_confirm_delete']          = 'Видалити запис?';
$_['js_confirm_clear']           = 'Очистити весь журнал відправки?';
$_['js_confirm_skip']            = 'Відмінити відправку?';
$_['js_confirm_send']            = 'Відправити лист зараз?';
$_['js_confirm_resend']          = 'Лист для цього замовлення вже відправлявся. Відправити повторно?';
$_['js_default_firstname']       = 'Клієнт';
$_['js_default_lang']            = 'uk-ua';
$_['js_modal_product']           = 'Товар';
$_['js_modal_review']            = 'Відгук';
$_['js_modal_points']            = 'Бали';
$_['js_product_num_prefix']      = 'Товар #';
