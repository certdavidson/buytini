<?php
include('version.php'); 

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
$_['heading_dev'] = 'Розробник <a href="' . $_['ocmod_jetsitemap_link'] . '" target="_blank">' . $_['ocmod_jetsitemap_author'] . '</a><br>&copy; 2011-' . date('Y') . ' Всі права захищені';


$_['error_text_jetsitemap_permission'] = 'У Вас немає прав для зміни модуля!';
$_['error_text_jetsitemap_modify'] = 'У вас немає прав на зміни модуля!';

$_['url_text_jetsitemap_opencartadmin'] = $_['ocmod_jetsitemap_link'];
$_['url_text_jetsitemap_create_text'] = '<div style="text-align: center; text-decoration: none;">Створення та оновлення<br>даних для модуля<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(при установці і оновлення модуля)</ins></div>';
$_['url_text_jetsitemap_delete_text'] = '<div style="text-align: center; text-decoration: none;">Видалення всіх<br>налаштувань модуля<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(всі налаштування будуть видалені)</ins></div>';
$_['url_text_jetsitemap_delete_sure_text'] = '<div style="text-align: center; text-decoration: none;">Ви впевнені<br>що хочете видалити усі налаштування?<br><ins style="text-align: center; text-decoration: none; font-size: 13px;">(всі налаштування будуть видалені)</ins></div>';
$_['url_text_jetsitemap_create_text'] = '<div style="text-align: center; text-decoration: none;">Установка та оновлення<br>модифікаторів, даних модуля<br>(виконується при установці або оновлення модуля)</div>';

$_['url_text_jetsitemap_ocmodrefresh'] = 'Оновити';
$_['url_text_jetsitemap_cacheremove'] = 'Видалити кеш';

$_['ocmod_jetsitemap_name'] = $_['jetsitemap_model'];

$_['ocmod_jetsitemap_name_15'] = $_['jetsitemap_model'].' 15';
$_['ocmod_jetsitemap_menu_name'] = $_['jetsitemap_model'] . " Меню";
$_['ocmod_jetsitemap_menu_mod'] = $_['jetsitemap_model_code'] . '_menu';
$_['ocmod_jetsitemap_menu_html'] = $_['ocmod_jetsitemap_menu_name'] . ' модифікатор успішно встановлено';

$_['ocmod_jetsitemap_mod'] = $_['jetsitemap_model_code'];
$_['ocmod_jetsitemap_mod_15'] = $_['jetsitemap_model_code'].'_15';
$_['ocmod_jetsitemap_html'] = $_['ocmod_jetsitemap_name'].' модифікатор успішно встановлено';
$_['ocmod_jetsitemap_name'] = $_['jetsitemap_model'];
$_['ocmod_jetsitemap_version'] = $_['jetsitemap_version'] ;
$_['ocmod_jetsitemap_text_on'] = '<span style="color:№007f35;"><b>увімкнуто</b></span>';
$_['ocmod_jetsitemap_text_off'] = '<span style="color:red;"><b>вимкнуто</b></span>';

$_['tab_text_jetsitemap_options'] = 'Налаштування';
$_['tab_text_jetsitemap_position'] = 'Макети та позиції';
$_['tab_text_jetsitemap_doc'] = 'Документація';
$_['tab_text_jetsitemap_menu'] = 'Меню';
$_['tab_text_jetsitemap_main'] = 'Віджети';
$_['tab_text_jetsitemap_service'] = 'Сервіс';
$_['tab_text_jetsitemap_access'] = 'Доступи';

$_['entry_jetsitemap_widjet'] = 'Віджет';
$_['entry_jetsitemap_id'] = "ID";
$_['entry_jetsitemap_copy'] = 'Копіювати';
$_['entry_jetsitemap_install_update'] = 'Установка / Оновлення';
$_['entry_jetsitemap_position'] = 'Позиція';
$_['entry_jetsitemap_copy_rules'] = 'Скопіювати правила';
$_['entry_jetsitemap_title_values'] = 'Змінні';
$_['entry_jetsitemap_add_rule'] = 'Додати';
$_['entry_jetsitemap_widget_status'] = "Статус";
$_['entry_jetsitemap_jetsitemap_ocmodrefresh'] = 'Оновити <br><span class="sc-color-clearcache">модифікатори</span>';
$_['entry_jetsitemap_jetsitemap_cacheremove'] = 'Видалити кеш <br><span class="sc-color-clearcache">файлів</span>';
$_['entry_jetsitemap_store'] = 'Магазини:';
$_['entry_jetsitemap_jetsitemap_menu_status'] = 'Статус <i class="fa fa-dot-circle-o"></i> ПЕРЕГЛЯД SEO в меню';
$_['entry_jetsitemap_jetsitemap_menu_order'] = 'Порядок пункту <i class="fa fa-dot-circle-o"></i> ПЕРЕГЛЯД SEO в меню, після "номери"<br>пункту в меню <br>номер:';
$_['entry_jetsitemap_jetsitemap_widget_status'] = 'Статус модуля';
$_['entry_jetsitemap_jetsitemap_widget_install_success'] = 'Таблиці віджету ' . $_['jetsitemap_model'] . ' успішно встановлена<br>';
$_['entry_jetsitemap_jetsitemap_widget_install'] = 'Підключення віджету ' . $_['jetsitemap_model'] . ' - успішно<br>';
$_['entry_jetsitemap_jetsitemap_widget_types'] = 'Видаляються елементи <br>з шаблону';
$_['entry_jetsitemap_number'] = 'Номер';
$_['entry_jetsitemap_add_jetsitemap_widget_type'] = 'Додати елемент';
$_['entry_jetsitemap_html'] = 'HTML';
$_['entry_jetsitemap_add'] = 'Додати';
$_['entry_jetsitemap_lang_default'] = 'Мова за промовчанням';
$_['entry_jetsitemap_name'] = 'Ім`я';
$_['entry_jetsitemap_access'] = 'Доступ';
$_['entry_jetsitemap_add_rule']  = 'Додати правило';
$_['entry_jetsitemap_title_template']    = 'Ім`я файлу шаблону';
$_['entry_jetsitemap_editor'] = 'Графічний редактор';
$_['entry_jetsitemap_switch'] = 'Включити модуль';
$_['entry_jetsitemap_about'] = 'Про модулі';
$_['entry_jetsitemap_category_status'] = 'Показувати категорію';
$_['entry_jetsitemap_reserved'] = 'Зарезервовано';
$_['entry_jetsitemap_service'] = 'Сервіс';
$_['entry_jetsitemap_layout'] = 'Макети:';
$_['entry_jetsitemap_position'] = 'Позиція';
$_['entry_jetsitemap_status'] = 'Статус:';
$_['entry_jetsitemap_sort_order'] = 'Порядок:';
$_['entry_jetsitemap_template'] = 'Шаблон';
$_['entry_jetsitemap_install_update'] = 'Установка та оновлення';
$_['entry_jetsitemap_show'] = 'Показати';
$_['entry_jetsitemap_positions'] = 'Позиції';
$_['entry_jetsitemap_hide'] = 'Приховати';
$_['entry_jetsitemap_uri'] = "URI";
$_['entry_jetsitemap_add_position_type'] = 'Додати, не стандартну,<br> наявну в opencart, <br>налаштовувану позицію';
$_['entry_jetsitemap_layouts'] = 'Макети';
$_['entry_jetsitemap_menu_status'] = 'Меню статус';
$_['entry_jetsitemap_menu_order'] = 'Порядок в меню';
$_['entry_jetsitemap_widgets_options'] = 'Глобальні налаштування віджетів';
$_['entry_jetsitemap_customer_groups'] = 'Групи покупців';
$_['entry_jetsitemap_complete_status'] = 'Статус того хто купив товар:<br /><span class="vhelp">Статус замовлення, при якому покупець <br>отримує статус що купив "цей" товар</span>';
$_['entry_jetsitemap_complete'] = 'Статус того хто купив товар';
$_['entry_jetsitemap_complete_choice'] = 'Оберіть статуси замовлення для того хто купив товар';
$_['entry_jetsitemap_position_types']    = 'Позиції / Користувальницькі позиції';
$_['entry_jetsitemap_position_controller']   = 'Контролер обробки';
$_['entry_jetsitemap_position_name'] = 'Ім`я змінної виведення';
$_['entry_jetsitemap_sort'] = 'Порядок';
$_['entry_jetsitemap_show_pro_settings'] = 'Показати PRO налаштування';
$_['entry_jetsitemap_hide_pro_settings'] = 'Приховати PRO налаштування';

$_['text_jetsitemap_uri_template'] = 'За "слова" в URI';
$_['text_jetsitemap_uri'] = 'URI (URL без протоколу і домену)<br><span class="vhelp">Не заповнюйте якщо використовуєте макети</span>';
$_['text_jetsitemap_error_name'] = 'Ім`я віджета містить неприпустимі символи<br><span class="vhelp">Допустимі символи: a-zA-Z0-9-_<br>не можна використовувати кирилицю і т. п.</span>';
$_['text_jetsitemap_status'] = 'Статус';
$_['text_jetsitemap_mod_add_jetsitemap'] = $_['jetsitemap_model'].' модифікатор встановлено<br>';
$_['text_jetsitemap_jetsitemap_success'] = 'Успішно';
$_['text_jetsitemap_ocmodrefresh_successfully'] = '<span style="color:green">Модифікатори успішно оновлено</span>';
$_['text_jetsitemap_ocmodrefresh_success'] = 'Модифікатори успішно оновлено';
$_['text_jetsitemap_ocmodrefresh_error'] = '<span style="color:red">Помилка оновлення модифікаторів</span>';
$_['text_jetsitemap_ocmodrefresh_fail'] = 'Не вдалося оновити';
$_['text_jetsitemap_ocmod'] = 'модифікатор';
$_['text_jetsitemap_cacheremove'] = 'Видалити кеш';
$_['text_jetsitemap_cacheremove_success'] = 'Виконано успішно';
$_['text_jetsitemap_cacheremove_fail'] = 'Не вдалося видалити';
$_['text_jetsitemap_jetsitemap_about'] = 'Про модулі';
$_['text_jetsitemap_default_store'] = 'Основний магазин';
$_['text_jetsitemap_loading_main'] = '<div style=&#92;\'color: #008000; &#92;\'>Завантажується...<i class=&#92;\'fa fa-refresh fa-spin&#92;\'></i></div>';
$_['text_jetsitemap_loading_main_without'] = '<div style="color: #008000">Завантажується...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_faq'] = '';
$_['text_jetsitemap_separator'] = ' > ';
$_['text_jetsitemap_status_on'] = 'увімкнуто';
$_['text_jetsitemap_status_off'] = 'вимкнуто';
$_['text_jetsitemap_jetsitemap_status_on'] = $_['text_jetsitemap_title'] . ' <span style="margin-left: 6px; color: #eeffee;"> '.$_['text_jetsitemap_status_on'] .'</span>';
$_['text_jetsitemap_jetsitemap_status_off'] = $_['text_jetsitemap_title'] . ' <span style="margin-left: 6px; color: #fccccc;"> '.$_['text_jetsitemap_status_off'] .'</span>';
$_['text_jetsitemap_ocmod_refresh'] = 'Оновити&nbsp;модифікатори';
$_['text_jetsitemap_close'] = 'Закрити';
$_['text_jetsitemap_loading_small'] = '<div style=&#92;\'color: #008000; &#92;\'>Завантажується...<i class=&#92;\'fa fa-refresh fa-spin&#92;\'></i></div>';
$_['text_jetsitemap_loading'] = '<div>Завантажується...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_loading_jetsitemap'] = '<div>Завантажується...<i class="fa fa-refresh fa-spin"></i></div>';
$_['text_jetsitemap_update_text'] = 'Натисніть на кнопку.<br>Ви оновили або встановили модуль';
$_['text_jetsitemap_module'] = 'Модулі';
$_['text_jetsitemap_add'] = 'Додати';
$_['text_jetsitemap_action'] = 'Дія:';
$_['text_jetsitemap_success'] = 'Модуль успішно оновлено!';
$_['text_jetsitemap_content_top'] = 'Зміст шапки';
$_['text_jetsitemap_content_bottom'] = 'Зміст підвалу';
$_['text_jetsitemap_column_left'] = 'Ліва колонка';
$_['text_jetsitemap_column_right'] = 'Права колонка';
$_['text_jetsitemap_what_lastest'] = 'Останні записи';
$_['text_jetsitemap_select_all'] = 'Виділити всі';
$_['text_jetsitemap_unselect_all'] = 'Зняти виділення';
$_['text_jetsitemap_sort_order'] = 'Порядок';
$_['text_jetsitemap_further'] = '...';
$_['text_jetsitemap_error'] = 'Помилка';
$_['text_jetsitemap_layout_all'] = 'Всі';
$_['text_jetsitemap_enabled'] = 'Увімкнуто';
$_['text_jetsitemap_disabled'] = 'Вимкнуто';
$_['text_jetsitemap_multi_empty'] = 'Зайдіть в таб "Установка та оновлення" та натисніть кнопку "Створення та оновлення даних для модуля (при установці та оновлення модуля)"';
$_['text_jetsitemap_install_ok'] = 'Дані успішно оновлено';
$_['text_jetsitemap_install_already'] = 'Дані присутні';
$_['text_jetsitemap_check_ver'] = 'Перевірити нову версію';
$_['text_jetsitemap_server_date_state'] = 'За станом на';
$_['text_jetsitemap_current_version_text'] = '<div style="color: #306793;">Ваша поточна версія</div>';
$_['text_jetsitemap_last_version_text'] = '<div style="color: #306793;">Остання версія</div>';
$_['text_jetsitemap_update_yes'] = '<div style="color: red;">Рекомендується оновити модуль</div>';
$_['text_jetsitemap_update_no'] = '<div style="color: green;">Оновлення не потрібно, у вас остання версія модуля</div>';
$_['text_jetsitemap_error_text_jetsitemap_server_connect'] = 'Помилка з`єднання з сервером';
$_['text_jetsitemap_update_version_begin'] = "<div style='background: #F7FFF2; width: auto; border: 1px solid #E2EDDC; padding: 10px;'>Остання доступна версія модуля: <span style='font-size: 21px;'>";
$_['text_jetsitemap_update_version_end'] = "</span></div>";
$_['text_jetsitemap_new_version'] = "<div style='background: #FFCFCE; border: 2px solid red; padding: 10px;'>Встановлена версія модуля: <b><span style='color: red;'>" . $_['jetsitemap_version'] . "</span></b><br>"."Остання версія модуля: <span style='color: green;'><b>";
$_['text_jetsitemap_new_version_end'] = '</b></span><br>Рекомендується: <span style="color: green;"><b>оновіть модуль до останньої версії</b></span></div>';
$_['text_jetsitemap_group_reg'] = 'Зареєстровані';
$_['text_jetsitemap_group_order'] = 'Ті, що купили товар в магазині';
$_['text_jetsitemap_group_order_this'] = 'Ті, що купили "цей" товар в магазині';
$_['text_jetsitemap_group_all'] = 'Всі групи покупців';
$_['text_jetsitemap_error_server_connect'] = 'Помилка з`єднання з сервером';

$_['jetsitemap_ocas'] = $_['ocmod_jetsitemap_link'] . '/index.php?route=record/ver';

/* Add backup */
$_['entry_js_backup'] = 'Налаштування <br><span style="color: green;">зберегти</span>';
$_['entry_js_restore'] = 'Налаштування <br><span style="color: green;">відновити</span>';

$_['text_js_url_backup'] = 'Зберегти';
$_['text_js_url_restore'] = 'Відновити';

$_['text_js_backup_success'] = '<span style="color: green;">Налаштування збережені</span>';
$_['text_js_restore_success'] = '<span style="color: green">Налаштування відновені</span>';

$_['text_js_backup_fail'] = 'Не вдалося зберегти налаштування';
$_['text_js_restore_fail'] = 'Не вдалося відновити налаштування';

$_['text_js_backup_access'] = '<span style="color: red;">У вас нема прав доступу</span>';
$_['text_js_restore_access'] = '<span style="color: red;">У вас нема прав доступу</span>';

$_['text_js_settings_no_format'] = '<span style="color: red;">Не вірний формат налаштувань</span>';
$_['text_js_json_error'] = '<span style="color: red;">Помилка декодування JSON</span>';
$_['text_js_error_filetype'] = '<span style="color: red;">Не вірний тип файлу</span>';
/* backup */

/* Menu */
$_['entry_jetsitemap_jetsitemap_options'] = 'Налаштування віджетів<br>' . $_['jetsitemap_model_settings'];
$_['text_jetsitemap_jetsitemap_options'] = 'Налаштування';

$_['entry_jetsitemap_langmark_options'] = 'Налаштування<br>' . $_['jetsitemap_model_settings'];
$_['text_jetsitemap_langmark_options'] = 'Налаштування';


$_['entry_jetsitemap_jetsitemap_adapter'] = 'Адаптер<br>перемикача мов';
$_['text_jetsitemap_jetsitemap_adapter'] = 'Адаптація';

$_['text_jetsitemap_widgets'] = 'Віджети';


/* Menu */

/* Icons */
$_['ocmod_jetsitemap_name_15'] = $_['jetsitemap_model'].' 15';
$_['ocmod_jetsitemap_icons_name'] = $_['jetsitemap_model'] . " CSS";
$_['ocmod_jetsitemap_icons_mod'] = $_['jetsitemap_model_code'] . '_icons';
$_['ocmod_jetsitemap_icons_html'] = $_['ocmod_jetsitemap_icons_name'] . ' модифікатор успішно встановлено';
/* Icons */

$_['text_jetsitemap_ocmod_none'] = $_['text_jetsitemap_ocmod'] . ' не встановлено';



$_['text_jetsitemap_device'] = 'Пристрої';
$_['text_jetsitemap_device_all'] = 'Усі пристрої';
$_['text_jetsitemap_device_comp'] = 'Комп&#39ютери';
$_['text_jetsitemap_device_mob'] = 'Мобільні пристрої';
$_['text_jetsitemap_device_smart'] = 'Смартфони';
$_['text_jetsitemap_device_pad'] = 'Планшети';

$_['entry_admin_status'] = 'Реагувати на події <br>в адмін. частині';



$_['url_text_jetsitemap_restore_text'] = '
<div style="text-align: center; text-decoration: none;">
Встановлення налаштувань
<br>
по замовчуванню
<br>
<ins style="text-align: center; text-decoration: none; font-size: 13px;">
всі "старі" налаштування
<br>
будуть видалені та втрачені<br>
Якщо вони були у вас то
<br>
рекомендовано спочатку зберегти їх
<br>
таб Сервіс -> Налаштування зберегти  -> <i class="fa fa-download" aria-hidden="true"></i> Зберегти
</ins></div>';
$_['url_text_jetsitemap_restore_sure_text'] = '
<div style="text-align: center; text-decoration: none;">
Ви впевнені
<br>
що хочете видалити усі старі налаштування
<br>
та встановити налаштування по замовчуванню?
<br>
<ins style="text-align: center; text-decoration: none; font-size: 13px;">
(всі "старі" (якщо вони були) налаштування будуть видалені.
<br>
Ви зберегли "старі" налаштування?
<br>
таб Сервіс -> Налаштування зберегти  -> <i class="fa fa-download" aria-hidden="true"></i> Зберегти)
</ins>
</div>';
