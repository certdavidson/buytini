<?php
$_['export_import_launch_profile_main_title'] = 'Запуск профілю';
$_['export_import_launch_profile_description'] = '<p>Ласкаво просимо в <b>Import Export PRO</b>. Тут ви можете запустити створені раніше профілі для імпорту та експорту. Якщо ви ще не створили жодного профілю, перейдіть у вкладку "<b>Створення або редагування профілів</b>", де ви зможете створити його.</p>
<p>Для запуску профілю оберіть його праворуч та натисніть кнопку "<b>Запустити обраний профіль</b>".</p>
<p>У вкладці "<a href="javascript:{}" onclick="$(\'a.tab_cron-jobs, a.tab_cron-задания, a.tab_cron-завдання\').click()"><b>завдання CRON</b></a>" ви можете налаштовувати автоматичний запуск профілів імпорту та експорту у зручний для вас час. Це ідеальне рішення для створення резервних копій каталогу або завантаження даних від постачальників (товари, ціни, атрибути тощо).</p>';
$_['export_import_profile_legend_text'] = 'Оберіть профіль для продовження процесу імпорту або експорту.';
$_['export_import_profile_load_select'] = 'Обрати профіль';
$_['export_import_profile_upload_file'] = 'Додати файл';
$_['export_import_profile_input_from'] = 'Від (число)';
$_['export_import_profile_input_from_help'] = 'Залиште поле пустим, якщо не бажаєте задавати діапазон';
$_['export_import_profile_input_to'] = 'До (число)';
$_['export_import_profile_input_to_help'] = 'Залиште поле пустим, якщо не бажаєте задавати діапазон';
$_['export_import_profile_upload_file_help'] = 'Майте на увазі, що формат файлу має бути сумісним із форматом, збереженим у вашому профілі.';
$_['export_import_start_button'] = 'Запустити обраний профіль';
$_['export_import_error_empty_profile'] = '<b>Помилка:</b> оберіть профіль';
$_['export_import_error_profile_not_found'] = '<b>Помилка:</b> профіль не знайдений.';
$_['export_import_error_xml_item_node'] = '<b>Помилка:</b> вузол елемента XML порожній. Завантажте конфігурацію свого профілю та налаштуйте вузол XML-елемента.';
$_['export_import_error_xml_item_node_not_found'] = '<b>Помилка:</b> XML Node "<b>%s</b>" не знайдений у файлі xml';
$_['export_import_remodal_process_title'] = 'Прогрес операції';
$_['export_import_remodal_process_subtitle'] = 'PHP процес запущений з браузера не може бути зупинений вручну. Процес буде завершений після закінчення тайм-ауту, зазначеного в налаштуваннях серверу або при ручному оновленні сторінки. Все це впливає тільки на ваш сеанс у конкретному браузері та не завдає ніяких проблем відвідувачам сайту.';
$_['export_import_remodal_server_config_title'] = 'Вимоги до сервера';
$_['export_import_remodal_server_config_description'] = '        <p>Наша команда постійно працює над максимальною оптимізацією процесів імпорту та експорту. Однак, якщо ваш сервер не відповідає вимогам, а ви запустите один із процесів, то в цьому випадку велика ймовірність помилок, а також переривання запущеного процесу.</p>
        <p><b>Це не помилка даного модуля</b>, нижче представлені налаштування PHP, які необхідно перевірити, а, <b>в разі необхідності та можливості</b>, змінити на вашому сервері:</p>
        
        <p>Одиниці виміру для деяких ключових параметрів PHP:</p>
        <ul>
            <li><b>memory_limit</b> (Мегабайти)</li>
            <li><b>max_execution_time</b> (Секунди)</li>
            <li><b>upload_max_filesize</b> (Мегабайти)</li>
            <li><b>post_max_size</b> (Мегабайти)</li>
        </ul>
        
        <p>В залежності від даних та розміру файлу, який ви намагаєтесь обробити, може знадобитись змінення вказаних параметрів PHP, рекомендовані значення наведено нижче:</p>
        <ul>
            <li><b>memory_limit</b>: 512M</li>
            <li><b>max_execution_time</b>: 800</li>
            <li><b>upload_max_filesize</b>: 240M</li>
            <li><b>post_max_size</b>: 250M</li>
        </ul>    
                
        <p>Найкращим способом зміни даних параметрів є їхнє редагування прямо в конфігурації сервера, проте, якщо ви не або не знаєте як це зробити, ми рекомендуємо звернутися в службу підтримки вашого хостингу.</p>
        <p>---------------------------</p>
        <p>Також ви можете спробувати змінити дані параметри одним з наступних способів:</p>
        
        <p><b>СПОСІБ 1: Змінити файл <em>"php.ini"</em> в папці <em>admin</em> вашого сайту, прописавши в ньому наступні параметри:</b></p>
        <p>
        <b style="color: #ff0000;">Це орієнтовні значення, можливо, вам доведеться встановити більші значення.</b><br>
        memory_limit = 512M<br>
        max_execution_time = 800<br>
        upload_max_filesize = 240M<br>
        post_max_size = 250M<br>
        </p>
        
        <p><b>СПОСІБ 2: Створити файл <em>".htaccess"</em> в папці <em>admin</em> вашого сайту, прописавши в ньому наступні параметри:</b></p>
        <p>
        <b style="color: #ff0000;">Це орієнтовні значення, можливо, вам доведеться встановити більші значення.</b><br>
        php_value memory_limit 512M<br>
        php_value max_execution_time 800<br>
        php_value upload_max_filesize 240M<br>
        php_value post_max_size 250M<br>
        </p>

        <p><b>СПОСІБ 3: ЯКЩО ВИ ВИКОРИСТОВУЄТЕ nginx:</b></p>
        <p>
        Якщо ваш сервер використовує nginx, переконайтеся, що ви відредагували свій файл <b>nginx.conf</b> із такими налаштуваннями:<br>
        proxy_read_timeout 3000;
        proxy_connect_timeout 3000;
        proxy_send_timeout 3000;
        send_timeout 3000;
        </p>
        
        <p><b>ЯК ПЕРЕВІРИТИ ЧИ ЗАСТОСОВАНІ ЗМІНИ (способи 1 та 2):</b></p>
        <ol>
            <li>Створіть у кореневій директорії вашого сайту файл <b>"phpinfo.php"</b> з наступним вмістом:<pre>&#60;?php phpinfo(); ?></pre></li>
            <li>В браузері перейдіть за посиланням http://yourdomain.com/phpinfo.php</li>
            <li>Натисніть CTRL+F або COMMAND+F та знайдіть значення параметрів, які ви редагували, вони мають відповідати тим, які були прописані у файле <em>"php.ini"</em> або <em>".htaccess"</em></li>
        </ol>
       
        <p>Якщо ви бачите, що у параметрів, які ви редагували, значення залишились незмінними, це свідчить про те, що хостинг не дозволяє редагування системних налаштувань серверу за допомогою редагування файлів у виділеній клієнту директорії. Зміни налаштувань мають бути прописані безпосередньо в конфігураційних файлах серверу, після чого він має бути перезапущений. Для вирішення даного питання вам необхідно звернутись в службу технічної підтримки хостингу.</p>
        ';
$_['export_import_remodal_server_config_link'] = 'ВАЖЛИВО: ПРОЧИТАЙТЕ ПЕРЕД ЗАПУСКОМ';
$_['progress_export_starting_process'] = 'Запуск процесу експорту...';
$_['progress_export_element_numbers'] = 'Елементів в експорті: <b>%s</b>';
$_['progress_export_processing_elements'] = 'В роботі елементів експорту...';
$_['progress_export_processing_elements_processed'] = 'Оброблено елементів: <b>%s</b> з <b>%s</b>';
$_['progress_export_elements_inserted'] = 'Елементів в роботі: <b>%s</b> з <b>%s</b>';
$_['progress_export_error_range'] = '<b>Помилка:</b> значення "ВІД" більше ніж значення "ДО"';
$_['progress_export_error_fixed_columns_match_operation'] = 'Наступна математична операція неможлива: "<b>%s</b>" для елемента: %s';

$_['progress_import_error_columns'] = '<b>Помилка:</b> система виявила, що деякі колонки завантаженого файлу не були завантажені у відповідності до налаштувань профілю:
        <br><br>
        <b>Колонок у ФАЙЛІ:</b>
        %s
        <br>
        <b>Колонок у ПРОФІЛІ:</b>
        %s
    ';
$_['progress_import_starting_process'] = 'Запуск процесу імпорту...';
$_['progress_import_from_product_creating_categories'] = '<b>Створення категорій...</b>';
$_['progress_import_from_product_created_categories'] = 'Створено категорій: <b>%s</b> ';
$_['progress_import_from_product_error_cat_repeat_categories'] = '<b>Помилка:</b> назва категорії <a href="%s" target="_blank"><b>%s</b></a> повторюється, змініть її назву або використовуйте "Дерево категорій"  в налаштуваннях профілю.';
$_['progress_import_from_product_creating_filter_groups'] = '<b>Створення груп фільтрів...</b>';
$_['progress_import_from_product_created_filter_groups'] = 'Створено груп фільтрів: <b>%s</b>';
$_['progress_import_from_product_creating_filter_groups_error_repeat'] = '<b>Помилка:</b> група фільтрів з назвою <a href="%s">"<b>%s</b>"</a> повторюється.';
$_['progress_import_from_product_creating_filters'] = '<b>Створення фільтрів...</b>';
$_['progress_import_from_product_created_filters'] = 'Фільтрів створено: <b>%s</b>';
$_['progress_import_from_product_creating_filters_error_no_group'] = 'Система не може створити фільтр "<b>%s</b>", для нього не призначено групу фільтрів.';
$_['progress_import_from_product_creating_attribute_groups'] = '<b>Створення групи атрибутів...</b>';
$_['progress_import_from_product_created_attribute_groups'] = 'Створено груп атрибутів: <b>%s</b>';
$_['progress_import_from_product_creating_attribute_groups_error_repeat'] = '<b>Помилка:</b> група атрибутів з назвою <a href="%s">"<b>%s</b>"</a> повторюється.';
$_['progress_import_from_product_creating_attributes'] = '<b>Створення атрибутів...</b>';
$_['progress_import_from_product_created_attributes'] = 'Атрибутів створено: <b>%s</b>';
$_['progress_import_from_product_creating_attributes_error_no_group'] = 'Система не може створити атрибут "<b>%s</b>", для нього не призначено групу атрибутів.';
$_['progress_import_from_product_creating_manufacturers'] = '<b>Створення виробників...</b>';
$_['progress_import_from_product_created_manufacturers'] = 'Виробників створено: <b>%s</b>';
$_['progress_import_from_product_creating_options_error_empty_main_field'] = '<b>Помилка:</b> ID товару "<b>%s</b>" не знайдений у вашому файлі. Якщо ви бажаєте використовувати опції товару, увімкніть ідентифікатор товару в налаштуваннях профілю. В іншому випадку вимкніть стовпці опцій "<b>Option XXXX</b>".';
$_['progress_import_from_product_creating_options'] = '<b>Створення опцій...</b>';
$_['progress_import_from_product_created_options'] = 'Опцій створено: <b>%s</b>';
$_['progress_import_from_product_creating_options_error_repeat'] = '<b>Помилка:</b> опція <a href="%s">"<b>%s</b>"</a>, тип "<b>%s</b>" повторюється.';
$_['progress_import_from_product_creating_options_error_option_type'] = '<b>Помилка:</b> для обробки опцій, необхідно призначити тип для опції "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values'] = '<b>Створення значень опцій...</b>';
$_['progress_import_from_product_created_option_values'] = 'Значень опцій створено: <b>%s</b>';
$_['progress_import_from_product_creating_option_values_error_option_type'] = 'Помилка у рядку <b>%s</b>: для обробки опцій необхідно призначити тип для опції "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values_error_option'] = 'Помилка у рядку <b>%s</b>: для обробки значень опцій необхідно призначити значення для опції "<b>%s</b>"';
$_['progress_import_from_product_creating_downloads'] = '<b>Створення завантажень...</b>';
$_['progress_import_from_product_created_downloads'] = 'Завантажень створено: <b>%s</b>';
$_['progress_import_product_error_option_data_in_main_row'] = '<b>Помилка у рядку %s</b>: виявлено дані опцій в рядку товару. Видаліть вміст усіх стовпців "<b>Option xxxxx</b>".';
$_['progress_import_product_error_product_related_not_found'] = '<b>Помилка у рядку %s</b>: товар з моделлю <b>%s</b> не знайдений. Якщо цей товар присутній у вашому файлі, переконайтеся, що значення моделі знаходиться <b>не перед</b> рядком "Опції товару".';
$_['progress_import_product_error_product_id_limit'] = 'Виявлено некоректне значення <b>ID товару</b>: <b>%s</b>, має бути <b>ЧИСЛОВЕ ЗНАЧЕННЯ</b>. Edit import profile, disable column "Product ID", and use another column like product identifier (model, sku, ean...).';
$_['progress_import_elements_process_start'] = '<b>Запуск обробки елементів...</b>';
$_['progress_import_elements_processed'] = 'Елементів оброблено: <b>%s</b> з <b>%s</b>';
$_['progress_import_error_main_identificator'] = 'ID товару "<b>%s</b>" відсутній у ваших даних, переконайтеся, що дана колонка включена в "<b>Призначення стовпців</b>" або вона <b>існує</b> у файлі, який ви імпортуєте.';
$_['progress_import_process_format_data_file'] = '<b>Форматування даних файлу...</b>';
$_['progress_import_process_format_data_file_progress'] = 'Відформатовано елементів: <b>%s</b> з <b>%s</b>';
$_['progress_import_elements_conversion_start'] = '<b>Конвертація значень елементів...</b>';
$_['progress_import_elements_converted'] = 'Перетворені значення елементів:  <b>%s</b> з <b>%s</b>';
$_['progress_import_process_start'] = '<b>Запуск процесу імпорту...</b> Будь ласка, запасіться терпінням, цей процес зазвичай займає багато часу. %s';
$_['progress_import_process_imported'] = 'Імпортовано елементів:  <b>%s</b> з <b>%s</b>';
$_['progress_import_applying_changes_safely'] = '<b>Безпечне застосування змін...</b>';
$_['progress_import_finished'] = '<b>%s</b><b>Імпорт успішно завершений!</b>
                <ul>
                    <li>Елементів створено:	<b>%s</b></li>
                    <li>Елементів змінено:	<b>%s</b></li>
                    <li>Елементів видалено:	<b>%s</b></li>
                </ul>';
$_['progress_import_error_updating_conditions'] = 'ВНУТРІШНЯ ПОМИЛКА: спроба оновити рядок таблиці без умов: <b>%s</b>';
$_['progress_import_error_skipped_all_elements'] = 'Всі елементи в цьому файлі було пропущено, перевірте налаштування «<b>попереднього фільтру</b>» у профілі.';
$_['progress_import_error_empty_data'] = '<b>Помилка:</b> відсутні дані. Переконайтеся, що завантажений файл сумісний зі стовпцями вашого профілю.';
$_['export_import_download_empy_file'] = 'Натисніть, щоб завантажити зразок файлу профілю.';
$_['progress_import_elements_splitted_values_start'] = '<b>Розщеплення та отримання значень...</b>';
$_['progress_import_elements_splitted_progress'] = 'Елементів оброблено:  <b>%s</b> з <b>%s</b>';
$_['progress_import_export_error_wrong_conditional_value'] = 'Умовне значення "<b>%s</b>" створено неправильно. Перегляньте довідку "<b>Умовне значення</b>".';
$_['progress_import_export_error_wrong_conditional_value_multiple_symbols'] = 'Умовне значення "<b>%s</b>" створено неправильно. Знайдено більш ніж одне умовне значення "<b>%s</b>". Перегляньте довідку "<b>Умовне значення</b>".';
$_['progress_import_export_error_incorrect_quoted_string'] = 'Неправильний використання лапок у рядку (відсутні початкові або завершальні лапки): %s';
$_['progress_import_export_error_missing_conditional_filter'] = 'Неправильна або відсутня назва умовного фільтра: "%s"';
$_['progress_import_export_error_evaluating_filter'] = 'Помилка під час оцінювання фільтра "%s": %s';
$_['progress_import_export_error_invalid_filter_syntax'] = 'Неправильний синтаксис фільтра: "%s"';
$_['progress_import_export_error_invalid_boolean_filter'] = 'Недійсне значення логічного фільтра (очікується 1 або 0): "%s"';
$_['progress_import_export_error_conditional_missing_symbol'] = 'Умовний вираз: відсутній компаратор: "%s"';
$_['progress_import_product_error_empty_description'] = '<b>Помилка створення товару</b>: спроба створити товар без обов’язкових даних (назва, модель, опис тощо), json товару: %s.
';
$_['progress_import_elements_no_numeric_id'] = '<b>Помилка нечислового ID</b>: Ви ввімкнули «ID замість імен» для кількох стовпців, система виявила нечисловий ідентифікатор: <b>%s</b>.';
$_['progress_import_product_option_values_error_option_doesnt_exist'] = '<b>Помилка у файлі, в рядку %s:</b> опція "<b>%s</b>" не існує, переконайтеся, що ви імпортували всі опції перш ніж імпортувати пов’язані з ними значення.';
$_['progress_import_product_option_values_error_not_product_identificator'] = '<b>Помилка у файлі, в рядку %s:</b> Ідентифікатор товару не існує';
$_['progress_import_applying_pre_filters'] = '<b>Застосування попередніх фільтрів</b>';
$_['progress_import_applying_file_filters'] = 'Застосування <b>файлових фільтрів</b>';
$_['progress_import_applying_shop_filters'] = 'Застосування <b>фільтрів магазину</b>';
$_['progress_import_elements_deleted'] = 'Видалено елементів: <b>%s</b>';
$_['progress_import_elements_skipped'] = 'Пропущено елементів: <b>%s</b>';
$_['progress_import_elements_disabled'] = 'Відключено елементів: <b>%s</b>';
$_['progress_import_elements_set_0'] = 'Елементів з нульовою кількістю: <b>%s</b>';
$_['progress_import_mapping_categories'] = '<b>Призначення категорій</b>';

$_['progress_import_updating_combinations_as_products_index'] = '<b>Оновлення комбінації як індексу товару... </b> Будь ласка, наберіться терпіння, це може зайняти деякий час. %s';

$_['export_import_server_error'] = '<b>Помилка на сервері:</b> сервер зупинив процес через одну з можливих причин: <ul><li>Недостатнє значення <b>max_execution_time</b></li><li>Недостатнє значення <b>memory_limit</b></li></ul><br>Для отримання додаткової інформації перегляньте <b>вкладку FAQ</b> пункт <b>1</b>';

$_['progress_import_downloading_remote_images'] = '<b>Завантаження віддалених зображень...</b>';
$_['progress_import_downloading_remote_images_progress'] = 'Завантажено зображень: <b>%s</b> з <b>%s</b>';

?>