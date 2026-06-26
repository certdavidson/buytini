<?php
// Heading
$_['heading_title']         = 'oc-kit.com — Related Categories';

// Text
$_['text_home']             = 'Главная';
$_['text_extension']        = 'Расширения';
$_['text_pagination']       = 'Показано с %d по %d из %d (%d страниц)';
$_['text_no_results']       = 'Категории не найдены';
$_['text_saved']            = 'Сохранено';
$_['text_add_category']     = 'Введите название категории...';
$_['text_ai_hint']          = 'Выберите категории в таблице и нажмите «Сгенерировать AI-связи». AI подберёт сопутствующие категории только для выбранных — запрос будет коротким и быстрым.';
$_['text_ai_done']          = 'AI-связи сгенерированы и применены';
$_['text_no_categories_selected'] = 'Выберите хотя бы одну категорию';
$_['text_selected']               = 'выбрано';

// Tabs
$_['tab_relations']         = 'Связи';
$_['tab_ai']                = 'AI Auto-Link';
$_['tab_settings']          = 'Настройки';

// Columns
$_['column_category']       = 'Категория';
$_['column_related']        = 'Сопутствующие категории';
$_['column_action']         = '';

// Buttons
$_['button_save']           = 'Сохранить';
$_['button_ai_generate']    = 'Сгенерировать AI-связи';
$_['button_clear']          = 'Очистить';
$_['text_cleared']          = 'Связи очищены';

// Labels
$_['label_status']            = 'Статус';
$_['label_products_count']    = 'Товаров из каждой категории';
$_['label_products_count_help'] = 'Сколько товаров показывать из каждой связанной категории';
$_['label_max_related']       = 'Максимум связанных категорий';
$_['label_max_related_help']  = 'Максимальное количество связанных категорий для выборки товаров';
$_['label_bidirectional']     = 'Двусторонние связи';
$_['label_bidirectional_help'] = 'Если A → B, автоматически создавать B → A';
$_['label_show_on_category']  = 'Показывать на странице категории';
$_['label_show_on_category_help'] = 'Отображать блок также на страницах категорий (опционально, требует размещения в Layout)';
$_['label_display_title']     = 'Заголовок блока';
$_['label_display_title_help'] = 'Отображается на фронтенде над блоком';
$_['label_ai_provider']       = 'AI провайдер';
$_['label_ai_api_key']        = 'API ключ';
$_['label_ai_model']          = 'Модель (необязательно)';
$_['label_ai_model_help']     = 'Оставьте пустым для использования модели по умолчанию';
$_['label_cache']             = 'Кэширование';
$_['label_cache_status']      = 'Кэшировать вывод блока';
$_['label_cache_ttl']         = 'Время кэша (минут)';
$_['label_cache_ttl_help']    = 'Список товаров сохраняется в файловом кэше OpenCart. При изменении настроек очистите кэш вручную: Система → Настройки → Кэш.';

// Errors
$_['error_permission']        = 'Недостаточно прав для изменения данных';
$_['error_invalid']           = 'Неверные данные запроса';
$_['error_no_categories']     = 'Не выбрано ни одной категории';
$_['error_ai_no_key']         = 'API ключ AI не настроен. Перейдите на вкладку «AI Auto-Link» и сохраните ключ.';
