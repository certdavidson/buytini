<?php
// Content Blocks | © 2026 oc-kit.com | https://oc-kit.com

// ─── Heading ─────────────────────────────────────────────────────────────────
$_['heading_title']        = 'oc-kit.com — Content Blocks';
$_['heading_title_simple'] = 'Content Blocks';

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────
$_['text_extensions']      = 'Расширения';
$_['text_modules']         = 'Модули';
$_['text_settings']        = 'Настройках';
$_['text_module_disabled'] = 'Content Blocks выключен. Включите в';
$_['el_divider']           = 'Разделитель';

// ─── Tabs ────────────────────────────────────────────────────────────────────
$_['tab_general']          = 'Общие';
$_['tab_types']            = 'Типы блоков';
$_['tab_stickers']         = 'Стикеры';
$_['tab_presets']          = 'Пресеты CSS';
$_['tab_integrations']     = 'Интеграции';
$_['tab_migration']        = 'Миграция';
$_['tab_faq']              = 'FAQ';
$_['tab_license']          = 'Лицензия';

// ─── General ─────────────────────────────────────────────────────────────────
$_['entry_status']         = 'Статус модуля';
$_['entry_wysiwyg']        = 'Редактор для текста';
$_['entry_openai_key']     = 'OpenAI API ключ';
$_['entry_openai_key_help'] = 'Для функции AI-перевода блоков';
$_['entry_license_key']    = 'Ключ лицензии';

$_['entry_upload_dir']     = 'Директория загрузки';
$_['entry_upload_dir_help'] = 'Относительный путь от корня магазина, напр.: image/catalog/content-blocks';
$_['entry_enable_cache']   = 'Кеширование блоков';
$_['entry_custom_css']     = 'Кастомный CSS';
$_['entry_custom_js']      = 'Кастомный JS';
$_['entry_custom_css_help'] = 'Выполняется на всех страницах с Content Blocks. Только для администратора — любой CSS может сломать вёрстку.';
$_['entry_custom_js_help']  = 'Выполняется в браузере посетителя на всех страницах с Content Blocks. Не вставляйте сюда недоверенный код — он имеет полный доступ к странице и сессии покупателя.';

// Form-element global defaults (apply to all <form> elements)
$_['entry_form_max_size']        = 'Формы: макс. размер файла (KB)';
$_['entry_form_max_size_help']   = 'Предельный размер для одного загружаемого файла. Больше — будет отклонено.';
$_['entry_form_accept_file']     = 'Формы: разрешённые файлы (.pdf,.doc,…)';
$_['entry_form_accept_file_help'] = 'Расширения или MIME-типы через запятую. Применяется к полям типа «Файл».';
$_['entry_form_accept_image']    = 'Формы: разрешённые изображения';
$_['entry_form_accept_image_help'] = 'Обычно image/* — только картинки. Применяется к полям типа «Изображение».';

$_['wysiwyg_jodit']        = 'Jodit (рекомендуется)';
$_['wysiwyg_summernote']   = 'Summernote';
$_['wysiwyg_ckeditor']     = 'CKEditor';

// ─── Block types ─────────────────────────────────────────────────────────────
$_['type_grid']               = 'Сетка';
$_['type_video']              = 'Видео';
$_['type_accordion']          = 'Аккордеон';
$_['type_faq']                = 'FAQ';
$_['type_reviews']            = 'Отзывы';
$_['type_products_carousel']  = 'Карусель товаров';
$_['type_images_carousel']    = 'Карусель изображений';
$_['type_product']            = 'Товар (карточка)';
$_['type_categories']         = 'Категории';
$_['type_blog_article']       = 'Статьи блога';

$_['entry_type_status']    = 'Активный';
$_['entry_image_width']    = 'Ширина изображения';
$_['entry_image_height']   = 'Высота изображения';
$_['entry_author_img_w']   = 'Фото автора: ширина';
$_['entry_author_img_h']   = 'Фото автора: высота';

// ─── Element types ───────────────────────────────────────────────────────────
$_['el_text']              = 'Текст';
$_['el_image']             = 'Изображение';
$_['el_html']              = 'HTML код';
$_['el_video']             = 'Видео';

// ─── Placeholders ────────────────────────────────────────────────────────────
$_['placeholder_search_product']  = 'Поиск товара...';
$_['placeholder_search_article']  = 'Поиск статьи...';
$_['placeholder_search_category'] = 'Поиск категории...';
$_['placeholder_accordion_title'] = 'Заголовок панели...';
$_['placeholder_faq_question']    = 'Вопрос...';
$_['placeholder_faq_answer']      = 'Ответ...';
$_['placeholder_review_author']   = 'Автор';
$_['placeholder_review_text']     = 'Текст отзыва...';
$_['text_pick_product']           = 'Выберите товар...';
$_['text_pick_article']           = 'Выберите статью...';
$_['text_pick_category']          = 'Выберите категорию...';
$_['button_pick_product']         = 'Выбрать товар';
$_['button_pick_article']         = 'Выбрать статью';
$_['button_pick_category']        = 'Выбрать категорию';

// ─── Item types ──────────────────────────────────────────────────────────────
$_['item_faq']             = 'Вопрос / Ответ';
$_['item_reviews']         = 'Отзыв';
$_['item_product']         = 'Товар';
$_['item_carousel_product'] = 'Товар';
$_['item_carousel_image']  = 'Изображение';
$_['item_categories']      = 'Категория';
$_['item_blog_article']    = 'Статья';

// ─── Block params ─────────────────────────────────────────────────────────────
$_['param_device_display']       = 'Скрыть на устройствах';
$_['param_responsive_order']     = 'Порядок на устройствах';
$_['param_collapse_in']          = 'Открыть первый';
$_['param_vertical']             = 'Вертикальный';
$_['param_autoplay']             = 'Автопрокрутка';
$_['param_video_autoplay']       = 'Автовоспроизведение';
$_['param_source_categories']    = 'Категории-источники';
$_['param_source_products']      = 'Товары-источники';

// ─── Form element ────────────────────────────────────────────────────────────
$_['el_form']                    = 'Форма';
$_['form_builder_title']         = 'Конструктор формы';
$_['form_builder_tab_general']   = 'Общие';
$_['form_builder_tab_fields']    = 'Поля';
$_['form_builder_add_field']     = 'Добавить поле';
$_['form_builder_no_fields']     = 'Полей ещё нет. Нажмите «Добавить поле».';
$_['form_builder_configure']     = 'Настроить';
$_['form_builder_field_count']   = 'Полей';
$_['form_builder_no_recipient']  = 'Email получателя не указан — используется админский email магазина.';

$_['param_recipient_email']      = 'Email получателя (пусто — основной админ)';
$_['param_redirect_url_help']    = 'Пусто — после отправки посетитель остаётся на этой же странице и видит сообщение об успехе в форме.';
$_['param_form_subject']         = 'Тема письма';
$_['param_success_message']      = 'Сообщение после отправки';
$_['param_redirect_url']         = 'Redirect URL после отправки';
$_['param_submit_label']         = 'Текст кнопки';
$_['param_max_file_size']        = 'Макс. размер файла (KB)';
$_['param_captcha_enabled']      = 'Captcha';

$_['entry_field_type']           = 'Тип поля';
$_['entry_field_name']           = 'Имя поля (name)';
$_['entry_field_label']          = 'Подпись';
$_['entry_field_placeholder']    = 'Placeholder';
$_['entry_field_required']       = 'Обязательное';
$_['entry_field_options']        = 'Варианты (по одному в строке)';
$_['entry_field_accept']         = 'Разрешённые mime/extensions';

$_['field_text']                 = 'Текст';
$_['field_email']                = 'Email';
$_['field_tel']                  = 'Телефон';
$_['field_number']               = 'Число';
$_['field_textarea']             = 'Текстовая область';
$_['field_select']               = 'Выпадающий список';
$_['field_checkbox']             = 'Чекбокс';
$_['field_radio']                = 'Radio-кнопки';
$_['field_file']                 = 'Файл';
$_['field_image']                = 'Изображение';

$_['tab_form_submissions']       = 'Заявки с форм';
$_['column_submission_id']       = 'ID';
$_['column_submission_block']    = 'Блок';
$_['column_submission_page']     = 'Страница';
$_['column_submission_ip']       = 'IP';
$_['column_submission_date']     = 'Дата';
$_['column_submission_actions']  = 'Действия';
$_['param_pagination']           = 'Пагинация';
$_['param_arrows']               = 'Стрелки';
$_['param_loop']                 = 'Циклическое повторение';
$_['param_per_view']             = 'Элементов в ряд';
$_['param_carousel']             = 'Карусель';
$_['param_random']               = 'Случайный порядок';
$_['param_playerjs_enable']      = 'PlayerJS плеер';
$_['param_playerjs_poster']      = 'PlayerJS постер';
$_['param_video_local']          = 'Видео с сервера';
$_['param_video_poster']         = 'Превью собственное';
$_['param_video_thumb_auto']     = 'Превью видеохостинга';
$_['entry_video_url']            = 'Youtube или Vimeo ссылка';

// ─── Demo page ───────────────────────────────────────────────────────────────
$_['text_demo_page_title']       = 'Демо-страница с блоками';
$_['text_demo_page_desc']        = 'Создаёт тестовую страницу (Information) со всеми типами блоков × все дизайны. Удобно для просмотра и сравнения.';
$_['button_demo_create']         = 'Создать демо-страницу';
$_['button_demo_delete']         = 'Удалить демо-страницу';
$_['text_demo_created']          = 'Демо-страница создана: %d блоков';
$_['text_demo_deleted']          = 'Демо-страница удалена';
$_['text_demo_exists']           = 'Демо-страница уже существует (id=%d)';
$_['text_demo_not_found']        = 'Демо-страница не найдена';

$_['param_limit']                = 'Лимит';
$_['text_no_type_params']        = 'Дополнительные параметры отсутствуют';

$_['param_show_price']           = 'Показывать цену';
$_['param_show_button']          = 'Показывать кнопку';
$_['param_show_rating']          = 'Показывать рейтинг';
$_['param_show_description']     = 'Показывать описание';
$_['param_show_attributes']      = 'Показывать характеристики';
$_['param_attributes_count']     = 'Кол-во характеристик';
$_['param_show_options']         = 'Показывать опции';
$_['param_options_count']        = 'Кол-во опций';
$_['param_features_disadvantages'] = 'Преимущества / Недостатки';
$_['param_description_length']   = 'Длина описания (символов)';
$_['param_img_override']         = 'Переопределение изображения';
$_['param_name_override']        = 'Переопределение названия';
$_['param_description_override'] = 'Переопределение описания';
$_['param_popup_enable']         = 'Попап изображения';
$_['param_popup_img_w']          = 'Ширина изображения в попапе (px)';
$_['param_popup_img_h']          = 'Высота изображения в попапе (px)';
$_['param_additional_images']        = 'Дополнительные изображения';
$_['param_additional_images_count']  = 'Кол-во дополнительных изображений';
$_['param_additional_img_w']         = 'Ширина миниатюры (px)';
$_['param_additional_img_h']         = 'Высота миниатюры (px)';
$_['param_cart_add_fn']              = 'JS-функция добавления в корзину';

// ─── Devices ─────────────────────────────────────────────────────────────────
$_['device_mobile']        = 'Мобильный';
$_['device_tablet']        = 'Планшет';
$_['device_desktop']       = 'Десктоп';

// ─── Editor form (inline in entity pages) ────────────────────────────────────
$_['text_content_blocks']       = 'Content Blocks';
$_['button_add_block']          = 'Добавить блок';
$_['text_row']                  = 'Строка';
$_['button_add_from_template']  = 'Из шаблона';
$_['button_save_blocks']        = 'Сохранить блоки';
$_['button_add_row']            = 'Строка';
$_['button_add_col']            = 'Колонка';
$_['button_add_element']        = 'Элемент';
$_['button_duplicate']          = 'Дублировать';
$_['button_translate']          = 'Перевести';
$_['button_save_as_template']   = 'Сохранить как шаблон';
$_['button_settings']           = 'Настройки';
$_['button_delete']             = 'Удалить';
$_['button_collapse']           = 'Свернуть';
$_['button_copy_shortcode']     = 'Скопировать шорткод';
$_['button_pick_image']         = 'Выбрать';
$_['button_upload_image']       = 'Загрузить';
$_['button_clear_image']        = 'Очистить';
$_['text_select_theme']         = 'Шаблон блока';
$_['button_activate']           = 'Активировать';
$_['text_license']                     = 'Лицензия';
$_['text_license_buy']                 = 'Приобрести лицензию';
$_['text_license_active']              = 'Лицензия активна';
$_['text_license_invalid']             = 'Невалидный ключ';
$_['text_license_api_error']           = 'Сервер лицензий недоступен';
$_['text_license_status_active']       = 'Активна';
$_['text_license_status_trial']        = 'Пробный период (дней осталось)';
$_['text_license_status_expired']      = 'Просрочена';
$_['text_license_status_grace']        = 'Льготный период';
$_['text_license_status_invalid']      = 'Недействительна';
$_['text_license_status_not_validated'] = 'Не проверено';
$_['text_license_not_validated']       = 'Не проверено';
$_['text_license_version']             = 'Версия';
$_['text_license_domain']              = 'Домен';
$_['text_shortcode_copied']     = 'Шорткод скопирован!';
$_['entry_block_status']        = 'Статус блока';

$_['entry_block_name']          = 'Название блока';
$_['entry_block_theme']         = 'Тема';
$_['entry_col_width']           = 'Ширина колонки';
$_['col_width_auto']            = 'Авто';
$_['text_no_blocks']            = 'Нет блоков. Нажмите "+ Добавить блок"';

// ─── Modal settings ──────────────────────────────────────────────────────────
$_['modal_title_block']         = 'Настройки блока';
$_['modal_title_row']           = 'Настройки строки';
$_['modal_title_col']           = 'Настройки колонки';
$_['modal_title_element']       = 'Настройки элемента';
$_['tab_style']                 = 'Стиль';
$_['tab_class']                 = 'Класс';
$_['tab_display']               = 'Отображение';
$_['button_apply']              = 'Применить';
$_['button_save']               = 'Сохранить';
$_['button_cancel']             = 'Отмена';

$_['entry_bg_color']            = 'Фон';
$_['entry_text_color']          = 'Цвет текста';
$_['entry_font_size']           = 'Размер шрифта (px)';
$_['entry_font_weight']         = 'Толщина шрифта';
$_['entry_text_align']          = 'Выравнивание текста';
$_['entry_padding']             = 'Отступы внутри (px)';
$_['entry_margin']              = 'Отступы снаружи (px)';
$_['entry_border_radius']       = 'Скругление (px)';
$_['entry_border']              = 'Рамка';
$_['entry_custom_class']        = 'Свой CSS класс';
$_['entry_preset']              = 'Пресет классов';
$_['entry_no_preset']           = '— без пресета —';
$_['entry_element_tag']         = 'HTML тег';

// ─── Templates ───────────────────────────────────────────────────────────────
$_['text_templates']            = 'Шаблоны блоков';
$_['text_no_templates']         = 'Нет сохранённых шаблонов';
$_['entry_template_name']       = 'Название шаблона';
$_['button_save_template']      = 'Сохранить';
$_['button_load_template']      = 'Загрузить';
$_['button_delete_template']    = 'Удалить';
$_['filter_all_types']          = 'Все типы';
$_['text_template_saved']       = 'Шаблон сохранён';

// ─── Translation ─────────────────────────────────────────────────────────────
$_['text_select_language']      = 'Выберите язык перевода';
$_['text_translate_from']       = 'С языка';
$_['text_translate_to']         = 'На язык';
$_['text_translating']          = 'Переводим...';
$_['text_translated']           = 'Переведено на: ';
$_['error_translation_failed']  = 'Ошибка перевода. Проверьте API ключ.';
$_['error_no_openai_key']       = 'OpenAI API ключ не настроен';

// ─── Stickers ─────────────────────────────────────────────────────────────────
$_['text_stickers']             = 'Стикеры товаров';
$_['column_sticker_text']       = 'Текст';
$_['column_sticker_color']      = 'Цвет текста';
$_['column_sticker_bg']         = 'Фон';
$_['column_sticker_border']     = 'Обводка';
$_['column_sticker_radius']     = 'Радиус (px)';
$_['column_sticker_status']     = 'Активный';
$_['column_sticker_pos']        = 'Позиция';
$_['entry_pos_top_left']        = 'Верхний левый';
$_['entry_pos_top_right']       = 'Верхний правый';
$_['entry_pos_bottom_left']     = 'Нижний левый';
$_['entry_pos_bottom_right']    = 'Нижний правый';
$_['button_add_sticker']        = '+ Добавить стикер';

// ─── Presets ─────────────────────────────────────────────────────────────────
$_['text_presets_help']         = 'Именованные наборы CSS-классов для быстрого применения к элементам';
$_['column_preset_group']       = 'Группа';
$_['column_preset_name']        = 'Название';
$_['column_preset_classes']     = 'CSS классы';
$_['button_add_preset']         = '+ Добавить пресет';
$_['button_reset_presets']      = 'Сбросить к стандартным';
$_['text_reset_presets_confirm'] = 'Все пресеты будут заменены стандартными. Продолжить?';

// ─── Integrations ────────────────────────────────────────────────────────────
$_['entry_blog_type']           = 'Тип блога';
$_['blog_type_default']         = 'Стандартный блог OpenCart';
$_['blog_type_octemplates']     = 'OcTemplates Blog';

// ─── Migration ───────────────────────────────────────────────────────────────
$_['text_migration_desc']       = 'Перенести данные из старого модуля Simple Blocks в Content Blocks.';
$_['text_migration_warning']    = 'Шаблоны Simple Blocks не мигрируются (несовместимый формат). Старые таблицы не удаляются.';
$_['button_migrate']            = 'Мигрировать с Simple Blocks';
$_['text_migrating']            = 'Миграция...';
$_['text_migration_done']       = 'Мигрировано блоков: ';
$_['text_migration_no_data']    = 'Таблицы Simple Blocks не найдены';
$_['text_migration_available']  = 'Доступно до версии 1.2 включительно';

// ─── Notifications ───────────────────────────────────────────────────────────
$_['text_success']              = 'Настройки сохранены';
$_['text_blocks_saved']         = 'Блоки сохранены';
$_['text_block_deleted']        = 'Блок удалён';
$_['text_block_duplicated']     = 'Блок продублирован';

// ─── Errors ──────────────────────────────────────────────────────────────────
$_['error_permission']          = 'Ошибка: недостаточно прав для изменения Content Blocks';
$_['error_block_not_found']     = 'Блок не найден';
$_['error_invalid_type']        = 'Неверный тип блока';
$_['error_save_failed']         = 'Ошибка сохранения';
$_['error_generic']             = 'Ошибка';
$_['error_preset_name_empty']   = 'Название не может быть пустым';

// ─── Demo page ───────────────────────────────────────────────────────────────
$_['text_demo_delete_confirm']  = 'Удалить демо-страницу и все её блоки?';
$_['text_demo_warn_save_first'] = 'Сохраните блоки перед дублированием';
