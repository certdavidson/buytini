<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2023, http://sergetkach.com/
 */

// Heading
$_['heading_title'] = 'Branched Sitemap';

// Text
$_['text_author']         = 'Автор';
$_['text_author_support'] = 'Поддержка';
$_['text_module_version'] = 'Версия модуля';
$_['text_system_version'] = 'Предназначено для системы версии';

$_['text_feed']      = 'Каналы продвижения';
$_['text_success']   = 'Настройки модуля обновлены!';
$_['text_edit']      = 'Редактировать Branched Sitemap';
$_['text_extension'] = 'Каналы продвижения';
$_['text_yes']       = 'Да';
$_['text_no']        = 'Нет';

// Entry
$_['fieldset_main']  = 'Основные настройки';
$_['entry_licence']          = 'Код лицензии:';
$_['entry_status']           = 'Статус:';
$_['entry_system']           = 'Используемая система:';
$_['entry_cachetime']        = 'Кешировать карту на:';
$_['cachetime_values_0']     = 'Не кэшировать';
$_['cachetime_values_1hour'] = '1 час';
$_['cachetime_values_6hours'] = '6 часов';
$_['cachetime_values_12hours'] = '12 часов';
$_['cachetime_values_24hours'] = 'Сутки';
$_['cachetime_values_1week'] = 'Неделя';
$_['entry_limit']            = 'Максимальное кол-во ссылок в одном ответвлении карты:';
$_['help_limit']             = 'Чем слабее сервер, тем меньше должно быть значение';
$_['entry_multishop']        = 'Использую ли я мультимагазин?';


$_['fieldset_feed']							 = 'Настройки фида';
//$_['entry_priority_category_level_1']	= 'Значение тега priority для категорий верхнего уровня';
$_['entry_priority_category_level_1']	= 'Значение тега priority для категорий';
//$_['entry_priority_category_level_2']	= 'Значение тега priority для категорий 2-го уровня вложенности';
//$_['entry_priority_category_level_more']	= 'Значение тега priority для категорий 3-го и более уровней вложенности';
$_['entry_priority_product']	   = 'Значение тега priority для товаров';
$_['entry_priority_manufacturer']	= 'Значение тега priority для производителей';
$_['entry_priority_blog']	       = 'Значение тега priority для блога';
$_['entry_priority_other']	     = 'Значение тега priority для других страниц (информация/статьи)';
$_['entry_data_feed']						 = 'Адрес карты сайта:';
$_['entry_sitemapindex_status']	 = 'Использовать Sitemapindex';
$_['help_sitemapindex_status']	 = 'Sitemapindex — это особый формат карты сайта, разбивающий карту сайта на несколько файлов. Это снижает нагрузку на сервер. Не выключайте эту настройку, если у Вас более 10 тыс товаров.';
$_['entry_off_description']			 = 'Ради ускорения, пропускать проверку на наличие текста товара';
$_['help_off_description']			 = 'При включенной настройке запрос товаров будет работать немного быстрее, но в карте могут присутствовать &quot;битые&quot; ссылки';
$_['entry_blogs']								 = 'Включать в карту сайта блог:';
$_['text_blogs_ocstore_default'] = 'Дефолтный блог ocStore 3+';
$_['text_blogs_newsblog']				 = 'NewsBlog от netruxa';
$_['text_blogs_octemplates']		 = 'Блог шаблонов от OCTemplates';
$_['text_blogs_aridius']				 = 'Блог шаблонов от Aridius';
$_['text_blogs_technics']				 = 'Блог шаблона Техникс';


$_['fieldset_feed_image']  = 'Изображения в карте сайта';
$_['alert_feed_image']  = 'Изображения в Файлах Sitemap нужны только в конкретных случаях. Этот способ позволит роботам найти труднодоступные фото, например, если они загружаются с помощью JavaScript. См <a href="https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps?hl=ru" target="_blank">инструкцию Google</a>, где это все рассказано официально. Используйте изображения в карте только если вы осознаете, зачем это нужно. В других случаях настоятельно рекомендую не использовать эту настройку или консультироваться с вашим SEO-специалистом.';
$_['entry_image_status']           = 'Статус изображений';
$_['entry_off_check_image_file']	 = 'Оптимизировать обработку изображений';
$_['help_off_check_image_file']		 = 'Значительно снижает нагрузку на сервер при работе с изображениями. Но в карте могут фигурировать несуществующие ссылки на изображения. В общем, включив эту опцию, следите за отчетами гугла :)';
$_['entry_webp_status']	 = 'На сайте используется WebP';
$_['help_webp_status']		 = 'Использование этого формата требует другой обработки изображений';
$_['entry_require_image_caption']	 = 'Включать ли для товаров описание картинок';
$_['help_require_image_caption']	 = 'Это немного замедлит Ваш Sitemap';

// Sitemap Rewrite Url
$_['rewrite_url_btn_1'] = 'Установить адрес';
$_['rewrite_url_btn_2'] = 'Установить другой адрес';

$_['modal_title'] = 'Настройка ссылки для карты сайта';
$_['modal_input_seo_url'] = 'ЧПУ для sitemap';
$_['modal_language_url'] = 'Укажите адрес главной страницы сайта для каждого языка';
$_['modal_btn'] = 'Применить';

$_['error_empty_language_url'] = 'Заполните адреса главной страницы сайта для каждого языка';
$_['error_equals_url'] = 'Недопустимо указывать одинаковые адреса главной страницы сайта для 2 и более языков';
$_['error_page_response_code'] = 'Страница <a href="%s" target="_blank">%s</a> не существует на Вашем сайте';
$_['error_writable'] = 'Файл %s недоступен для записи.';
$_['error_file_exist'] = 'Файл %s не найден в корневой директории сайта.';
$_['error_url_format'] = 'Неверный формат поля <b>ЧПУ для sitemap</b>. Допускается использование букв латинского алфавита в нижнем регистре, цифр, черточки и точки.';
$_['error_absent_line'] = 'Не найдена строка, к которой можно зацепиться';
$_['error_todo'] = 'Что делать? Впишите нужные правила вручную. Смотрите справку в Базе знаний — <a href="https://support.sergetkach.com/knowledge/details/44/" target="_blank">https://support.sergetkach.com/knowledge/details/44/</a>';

//$_['success_added'] = 'В %s были добавлены следующие правила:';
$_['success_todo_1'] = 'Для проверки откройте карту сайта в браузере по следующей ссылке:';
$_['success_todo_2'] = 'Для проверки откройте карты сайта в браузере по следующим ссылкам:';

// Button
$_['button_save_licence'] = 'Сохранить лицензию';

// Error
$_['error_permission'] = 'У вас нет прав для управления этим модулем!';
