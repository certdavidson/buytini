<?php
include('version.php'); 
include(DIR_LANGUAGE . 'uk-ua/jetsitemap/jetsitemap.php');

$_['text_jetsitemap_title'] = "Jet Sitemap";
$_['url_text_jetsitemap_module_text'] = $_['text_jetsitemap_title'];
$_['jetsitemap_model_code'] = "jetsitemap";
$_['order_jetsitemap'] = '0';

 

$_['ocmod_jetsitemap_author'] = 'support.opencartadmin.com ';
$_['ocmod_jetsitemap_link'] = 'https://support.opencartadmin.com';

$_['jetsitemap_model_settings'] = $_['heading_title'] = $_['jetsitemap_model'] . ' ' . $_['jetsitemap_version'];
$_['heading_title'] = '<span style="color: ' . $_['jetsitemap_color'] . '; font-size: 15px; font-weight: 400;"><a href="' . $_['ocmod_jetsitemap_link'] . '" style="color: ' . $_['jetsitemap_color'] . ';" target="_blank" data-toggle="tooltip" title="" data-original-title="' . $_['ocmod_jetsitemap_author'] . '">'  . $_['ico_jetsitemap'] . '</a>  ' . $_['heading_title'] . '</span>';

$_['widget_jetsitemap_version'] = $_['jetsitemap_version'];

$_['heading_title_jetsitemap'] = $_['jetsitemap_model'];
$_['heading_dev'] = 'Разработчик <a href="' . $_['ocmod_jetsitemap_link'] . '" target="_blank">' . $_['ocmod_jetsitemap_author'] . '</a><br>&copy; 2011-' . date('Y') . ' Все права защищены';


$_['error_text_jetsitemap_permission'] = 'У вас нет прав для изменения модуля!';
$_['error_text_jetsitemap_modify'] = 'У вас нет прав на изменение модуля!';

$_['url_text_jetsitemap_opencartadmin'] = $_['ocmod_jetsitemap_link'];
$_['url_text_jetsitemap_create_text'] = '<div style="text-align: center; text-decoration: none;">Создание и обновление<br>данных для модуля<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(при установке и обновлении модуля)</ins></div>';
$_['url_text_jetsitemap_delete_text'] = '<div style="text-align: center; text-decoration: none;">Удаление всех<br>настроек модуля<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(все настройки будут удалены)</ins></div>';
$_['url_text_jetsitemap_delete_sure_text'] = '<div style="text-align: center; text-decoration: none;">Вы уверены<br>что хотите удалить все настройки?<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(все настройки будут удалены)</ins></div>';
$_['url_text_jetsitemap_create_text'] = '<div style="text-align: center; text-decoration: none;">Установка и обновление<br>модификаторов, данных модуля<br>(выполняется при установке или обновлении)</div>';

$_['url_text_jetsitemap_ocmodrefresh'] = 'Обновить';
$_['url_text_jetsitemap_cacheremove'] = 'Удалить кеш';

$_['ocmod_jetsitemap_name'] = $_['jetsitemap_model'];

$_['ocmod_jetsitemap_name_15'] = $_['jetsitemap_model'].' 15';
$_['ocmod_jetsitemap_menu_name'] = $_['jetsitemap_model'] . " Меню";
$_['ocmod_jetsitemap_menu_mod'] = $_['jetsitemap_model_code'] . '_menu';
$_['ocmod_jetsitemap_menu_html'] = $_['ocmod_jetsitemap_menu_name'] . ' модификатор успешно установлен';

$_['ocmod_jetsitemap_mod'] = $_['jetsitemap_model_code'];
$_['ocmod_jetsitemap_mod_15'] = $_['jetsitemap_model_code'].'_15';
$_['ocmod_jetsitemap_html'] = $_['ocmod_jetsitemap_name'].' модификатор успешно установлен';
$_['ocmod_jetsitemap_name'] = $_['jetsitemap_model'];
$_['ocmod_jetsitemap_version'] = $_['jetsitemap_version'] ;
$_['ocmod_jetsitemap_text_on'] = '<span style="color:№007f35;"><b>включено</b></span>';
$_['ocmod_jetsitemap_text_off'] = '<span style="color:red;"><b>выключено</b></span>';

$_['tab_text_jetsitemap_options'] = 'Настройки';
$_['tab_text_jetsitemap_position'] = 'Макеты и позиции';
$_['tab_text_jetsitemap_doc'] = 'Документация';
$_['tab_text_jetsitemap_menu'] = 'Меню';
$_['tab_text_jetsitemap_main'] = 'Виджеты';
$_['tab_text_jetsitemap_service'] = 'Сервис';
$_['tab_text_jetsitemap_access'] = 'Доступы';

$_['entry_jetsitemap_widjet'] = 'Виджет';
$_['entry_jetsitemap_id'] = "ID";
$_['entry_jetsitemap_copy'] = 'Копировать';
$_['entry_jetsitemap_install_update'] = 'Установка / Обновление';
$_['entry_jetsitemap_position'] = 'Позиция';
$_['entry_jetsitemap_copy_rules'] = 'Скопировать правила';
$_['entry_jetsitemap_title_values'] = 'Переменные';
$_['entry_jetsitemap_add_rule'] = 'Добавить';
$_['entry_jetsitemap_widget_status'] = "Статус";
$_['entry_jetsitemap_jetsitemap_ocmodrefresh'] = 'Обновить <br><span class="sc-color-clearcache">модификаторы</span>';
$_['entry_jetsitemap_jetsitemap_cacheremove'] = 'Удалить кеш <br><span class="sc-color-clearcache">файлы</span>';
$_['entry_jetsitemap_store'] = 'Магазины:';
$_['entry_jetsitemap_jetsitemap_menu_status'] = 'Статус <i class="fa fa-dot-circle-o"></i> SEO ОБЗОР в меню';
$_['entry_jetsitemap_jetsitemap_menu_order'] = 'Порядок пункта <i class="fa fa-dot-circle-o"></i> SEO ОБЗОР в меню, после "номера"<br>пункта в меню <br>номер:';
$_['entry_jetsitemap_jetsitemap_widget_status'] = 'Статус модуля';
$_['entry_jetsitemap_jetsitemap_widget_install_success'] = 'Таблицы виджета ' . $_['jetsitemap_model'] . ' успешно установлены<br>';
$_['entry_jetsitemap_jetsitemap_widget_install'] = 'Подключение виджета ' . $_['jetsitemap_model'] . ' - успешно<br>';
$_['entry_jetsitemap_jetsitemap_widget_types'] = 'Удаляются элементы <br>из шаблона';
$_['entry_jetsitemap_number'] = 'Номер';
$_['entry_jetsitemap_add_jetsitemap_widget_type'] = 'Добавить элемент';
$_['entry_jetsitemap_html'] = 'HTML';
$_['entry_jetsitemap_add'] = 'Добавить';
$_['entry_jetsitemap_lang_default'] = 'Язык по умолчанию';
$_['entry_jetsitemap_name'] = 'Имя';
$_['entry_jetsitemap_access'] = 'Доступ';
$_['entry_jetsitemap_add_rule']  = 'Добавить правило';
$_['entry_jetsitemap_title_template']    = 'Имя файла шаблона';
$_['entry_jetsitemap_editor'] = 'Графический редактор';
$_['entry_jetsitemap_switch'] = 'Включить модуль';
$_['entry_jetsitemap_about'] = 'О модуле';
$_['entry_jetsitemap_category_status'] = 'Показывать категорию';
$_['entry_jetsitemap_reserved'] = 'Зарезервировано';
$_['entry_jetsitemap_service'] = 'Сервис';
$_['entry_jetsitemap_layout'] = 'Макеты:';
$_['entry_jetsitemap_position'] = 'Позиция';
$_['entry_jetsitemap_status'] = 'Статус:';
$_['entry_jetsitemap_sort_order'] = 'Порядок:';
$_['entry_jetsitemap_template'] = 'Шаблон';
$_['entry_jetsitemap_install_update'] = 'Установка и обновление';
$_['entry_jetsitemap_show'] = 'Показать';
$_['entry_jetsitemap_positions'] = 'Позиции';
$_['entry_jetsitemap_hide'] = 'Скрыть';
$_['entry_jetsitemap_uri'] = "URI";
$_['entry_jetsitemap_add_position_type'] = 'Добавить, не стандартную,<br> существующую в opencart, <br>настраиваемую позицию';
$_['entry_jetsitemap_layouts'] = 'Макеты';
$_['entry_jetsitemap_menu_status'] = 'Меню статус';
$_['entry_jetsitemap_menu_order'] = 'Порядок в меню';
$_['entry_jetsitemap_widgets_options'] = 'Глобальные настройки виджетов';
$_['entry_jetsitemap_customer_groups'] = 'Группы покупателей';
$_['entry_jetsitemap_complete_status'] = 'Статус тех, кто купил товар:<br /><span class="vhelp">Статус заказа, при котором покупатель<br>получает статус купившего "этот" товар</span>';
$_['entry_jetsitemap_complete'] = 'Статус тех, кто купил товар';
$_['entry_jetsitemap_complete_choice'] = 'Выберите статусы заказов для тех, кто купил товар';
$_['entry_jetsitemap_position_types']    = 'Позиции / Пользовательские позиции';
$_['entry_jetsitemap_position_controller']   = 'Контроллер обработки';
$_['entry_jetsitemap_position_name'] = 'Имя переменной вывода';
$_['entry_jetsitemap_sort'] = 'Порядок';
$_['entry_jetsitemap_show_pro_settings'] = 'Показать PRO настройки';
$_['entry_jetsitemap_hide_pro_settings'] = 'Скрыть PRO настройки';

$_['text_jetsitemap_uri_template'] = 'По "словам" в URI';
$_['text_jetsitemap_uri'] = 'URI (URL без протокола и домена)<br><span class="vhelp">Не заполняйте, если используете макеты</span>';
$_['text_jetsitemap_error_name'] = 'Имя виджета содержит недопустимые символы<br><span class="vhelp">Допустимые символы: a-zA-Z0-9-_<br>нельзя использовать кириллицу и т.п.</span>';
$_['text_jetsitemap_status'] = 'Статус';
$_['text_jetsitemap_mod_add_jetsitemap'] = $_['jetsitemap_model'].' модификатор установлен<br>';
$_['text_jetsitemap_jetsitemap_success'] = 'Успешно';
$_['text_jetsitemap_ocmodrefresh_successfully'] = '<span style="color:green">Модификаторы успешно обновлены</span>';
$_['text_jetsitemap_ocmodrefresh_success'] = 'Модификаторы успешно обновлены';
$_['text_jetsitemap_ocmodrefresh_error'] = '<span style="color:red">Ошибка обновления модификаторов</span>';
$_['text_jetsitemap_ocmodrefresh_fail'] = 'Не удалось обновить';
$_['text_jetsitemap_ocmod'] = 'модификатор';
$_['text_jetsitemap_cacheremove'] = 'Удалить кеш';
$_['text_jetsitemap_cacheremove_success'] = 'Выполнено успешно';
$_['text_jetsitemap_cacheremove_fail'] = 'Не удалось удалить';
$_['text_jetsitemap_jetsitemap_about'] = 'О модуле';
$_['text_jetsitemap_default_store'] = 'Основной магазин';
$_['text_jetsitemap_loading_main'] = '<div style=&#92;\'color: #008000; &#92;\'>Загружается...<i class=&#92;\'fa fa-refresh fa-spin&#92;\'></i></div>';
$_['text_jetsitemap_loading_main_without'] = '<div style="color: #008000">Загружается...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_faq'] = '';
$_['text_jetsitemap_separator'] = ' > ';
$_['text_jetsitemap_status_on'] = 'включено';
$_['text_jetsitemap_status_off'] = 'выключено';
$_['text_jetsitemap_jetsitemap_status_on'] = $_['text_jetsitemap_title'] . ' <span style="margin-left: 6px; color: #eeffee;"> '.$_['text_jetsitemap_status_on'] .'</span>';
$_['text_jetsitemap_jetsitemap_status_off'] = $_['text_jetsitemap_title'] . ' <span style="margin-left: 6px; color: #fccccc;"> '.$_['text_jetsitemap_status_off'] .'</span>';
$_['text_jetsitemap_ocmod_refresh'] = 'Обновить&nbsp;модификаторы';
$_['text_jetsitemap_close'] = 'Закрыть';
$_['text_jetsitemap_loading_small'] = '<div style=&#92;\'color: #008000; &#92;\'>Загружается...<i class=&#92;\'fa fa-refresh fa-spin&#92;\'></i></div>';
$_['text_jetsitemap_loading'] = '<div>Загружается...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_loading_jetsitemap'] = '<div>Загружается...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_update_text'] = 'Нажмите на кнопку.<br>Вы обновили или установили модуль';
$_['text_jetsitemap_module'] = 'Модули';
$_['text_jetsitemap_add'] = 'Добавить';
$_['text_jetsitemap_action'] = 'Действие:';
$_['text_jetsitemap_success'] = 'Модуль успешно обновлен!';
$_['text_jetsitemap_content_top'] = 'Содержание шапки';
$_['text_jetsitemap_content_bottom'] = 'Содержание подвала';
$_['text_jetsitemap_column_left'] = 'Левая колонка';
$_['text_jetsitemap_column_right'] = 'Правая колонка';
$_['text_jetsitemap_what_lastest'] = 'Последние записи';
$_['text_jetsitemap_select_all'] = 'Выделить все';
$_['text_jetsitemap_unselect_all'] = 'Снять выделение';
$_['text_jetsitemap_sort_order'] = 'Порядок';
$_['text_jetsitemap_further'] = '...';
$_['text_jetsitemap_error'] = 'Ошибка';
$_['text_jetsitemap_layout_all'] = 'Все';
$_['text_jetsitemap_enabled'] = 'Включено';
$_['text_jetsitemap_disabled'] = 'Выключено';
$_['text_jetsitemap_multi_empty'] = 'Зайдите в таб "Установка и обновление" и нажмите кнопку "Создание и обновление данных для модуля (при установке и обновлении модуля)"';
$_['text_jetsitemap_install_ok'] = 'Данные успешно обновлены';
$_['text_jetsitemap_install_already'] = 'Данные присутствуют';
$_['text_jetsitemap_check_ver'] = 'Проверить новую версию';
$_['text_jetsitemap_server_date_state'] = 'По состоянию на';
$_['text_jetsitemap_current_version_text'] = '<div style="color: #306793;">Ваша текущая версия</div>';
$_['text_jetsitemap_last_version_text'] = '<div style="color: #306793;">Последняя версия</div>';
$_['text_jetsitemap_update_yes'] = '<div style="color: red;">Рекомендуется обновить модуль</div>';
$_['text_jetsitemap_update_no'] = '<div style="color: green;">Обновление не требуется, у вас последняя версия модуля</div>';
$_['text_jetsitemap_error_text_jetsitemap_server_connect'] = 'Ошибка соединения с сервером';
$_['text_jetsitemap_update_version_begin'] = "<div style='background: #F7FFF2; width: auto; border: 1px solid #E2EDDC; padding: 10px;'>Последняя доступная версия модуля: <span style='font-size: 21px;'>";
$_['text_jetsitemap_update_version_end'] = "</span></div>";
$_['text_jetsitemap_new_version'] = "<div style='background: #FFCFCE; border: 2px solid red; padding: 10px;'>Установленная версия модуля: <b><span style='color: red;'>" . $_['jetsitemap_version'] . "</span></b><br>"."Последняя версия модуля: <span style='color: green;'><b>";
$_['text_jetsitemap_new_version_end'] = '</b></span><br>Рекомендуется: <span style="color: green;"><b>обновите модуль до последней версии</b></span></div>';
$_['text_jetsitemap_group_reg'] = 'Зарегистрированные';
$_['text_jetsitemap_group_order'] = 'Те, кто купили товар в магазине';
$_['text_jetsitemap_group_order_this'] = 'Те, кто купили "этот" товар в магазине';
$_['text_jetsitemap_group_all'] = 'Все группы покупателей';
$_['text_jetsitemap_error_server_connect'] = 'Ошибка соединения с сервером';

$_['jetsitemap_ocas'] = $_['ocmod_jetsitemap_link'] . '/index.php?route=record/ver';

/* Add backup */
$_['entry_js_backup'] = 'Настройки <br><span style="color: green;">сохранить</span>';
$_['entry_js_restore'] = 'Настройки <br><span style="color: green;">восстановить</span>';

$_['text_js_url_backup'] = 'Сохранить';
$_['text_js_url_restore'] = 'Восстановить';

$_['text_js_backup_success'] = '<span style="color: green;">Настройки сохранены</span>';
$_['text_js_restore_success'] = '<span style="color: green">Настройки восстановлены</span>';

$_['text_js_backup_fail'] = 'Не удалось сохранить настройки';
$_['text_js_restore_fail'] = 'Не удалось восстановить настройки';

$_['text_js_backup_access'] = '<span style="color: red;">У вас нет прав доступа</span>';
$_['text_js_restore_access'] = '<span style="color: red;">У вас нет прав доступа</span>';

$_['text_js_settings_no_format'] = '<span style="color: red;">Неверный формат настроек</span>';
$_['text_js_json_error'] = '<span style="color: red;">Ошибка декодирования JSON</span>';
$_['text_js_error_filetype'] = '<span style="color: red;">Неверный тип файла</span>';
/* backup */

/* Menu */

/* Icons */
$_['ocmod_jetsitemap_name_15'] = $_['jetsitemap_model'].' 15';
$_['ocmod_jetsitemap_icons_name'] = $_['jetsitemap_model'] . " CSS";
$_['ocmod_jetsitemap_icons_mod'] = $_['jetsitemap_model_code'] . '_icons';
$_['ocmod_jetsitemap_icons_html'] = $_['ocmod_jetsitemap_icons_name'] . ' модификатор успешно установлен';
/* Icons */

$_['text_jetsitemap_ocmod_none'] = $_['text_jetsitemap_ocmod'] . ' не установлен';

$_['text_jetsitemap_device'] = 'Устройства';
$_['text_jetsitemap_device_all'] = 'Все устройства';
$_['text_jetsitemap_device_comp'] = 'Компьютеры';
$_['text_jetsitemap_device_mob'] = 'Мобильные устройства';
$_['text_jetsitemap_device_smart'] = 'Смартфоны';
$_['text_jetsitemap_device_pad'] = 'Планшеты';

$_['entry_admin_status'] = 'Реагировать на события <br>в админ. части';

$_['url_text_jetsitemap_restore_text'] = '
<div style="text-align: center; text-decoration: none;">
Установка настроек
<br>
по умолчанию
<br>
<ins style="text-align: center; text-decoration: none; font-size: 13px;">
все "старые" настройки
<br>
будут удалены и утрачены<br>
Если они у вас были, то
<br>
рекомендуется сначала сохранить их
<br>
вкладка Сервис -> Сохранить настройки -> <i class="fa fa-download" aria-hidden="true"></i> Сохранить
</ins></div>';
$_['url_text_jetsitemap_restore_sure_text'] = '
<div style="text-align: center; text-decoration: none;">
Вы уверены
<br>
что хотите удалить все старые настройки
<br>
и установить настройки по умолчанию?
<br>
<ins style="text-align: center; text-decoration: none; font-size: 13px;">
(все "старые" настройки (если они были) будут удалены.
<br>
Вы сохранили "старые" настройки?
<br>
вкладка Сервис -> Сохранить настройки -> <i class="fa fa-download" aria-hidden="true"></i> Сохранить)
</ins>
</div>';
