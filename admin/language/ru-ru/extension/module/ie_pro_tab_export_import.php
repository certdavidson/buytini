<?php
$_['export_import_launch_profile_main_title'] = 'Запуск профиля';
$_['export_import_launch_profile_description'] = '<p>Добро пожаловать в <b>Import Export PRO</b>. Здесь Вы можете запустить созданные ранее профили для импорта и экспорта. Если Вы еще не создали ни одного профиля перейдите во вкладку "<b>Создание или редактирование профилей</b>", где Вы сможете создать его.</p>
<p>Для запуска профиля выберете его справа и нажмите кнопку "<b>Запустить выбранный профиль</b>".</p>
<p>Во вкладке "<a href="javascript:{}" onclick="$(\'a.tab_cron-jobs, a.tab_cron-задания\').click()"><b>задания CRON</b></a>", Вы можете настраивать автоматический запуск профилей импорта или экспорта в удобное для Вас время. Это идеальное решение для создания резервных копий каталога или загрузки данных от поставщиков (товары, цены, атрибуты и пр.).</p>';
$_['export_import_profile_legend_text'] = 'Выберете профиль для продолжения процесса импорта или экспорта.';
$_['export_import_profile_load_select'] = 'Выбрать профиль';
$_['export_import_profile_upload_file'] = 'Загрузить файл';
$_['export_import_profile_input_from'] = 'От (число)';
$_['export_import_profile_input_from_help'] = 'Оставьте поле пустым, если не хотите указывать диапазон';
$_['export_import_profile_input_to'] = 'До (число)';
$_['export_import_profile_input_to_help'] = 'Оставьте поле пустым, если не хотите указывать диапазон';
$_['export_import_profile_upload_file_help'] = 'Помните, что формат файла с тем, который сохранен в настройках профиля.';
$_['export_import_start_button'] = 'Запустить выбранный профиль';
$_['export_import_error_empty_profile'] = '<b>Ошибка:</b> Выберете профиль';
$_['export_import_error_profile_not_found'] = '<b>Ошибка:</b> Профиль не найден.';
$_['export_import_error_xml_item_node'] = '<b>Ошибка:</b> Элемент XML node пуст. Загрузите конфигурацию Вашего профиля и настройте элементы XML node.';
$_['export_import_error_xml_item_node_not_found'] = '<b>Ошибка:</b> XML Node "<b>%s</b>" не найден в xml файле.';
$_['export_import_remodal_process_title'] = 'Прогресс операции';
$_['export_import_remodal_process_subtitle'] = 'PHP процесс запущенный из браузера не может быть остановлен вручную. Процесс будет завершен по истечении тайм-аута в настройках сервера или при ручном обновлении страницы. Все это влияет только на Ваш сейнс в конкретном браузере и не доставляет никаких проблем посетителям сайта.';
$_['export_import_remodal_server_config_title'] = 'Требования к серверу';
$_['export_import_remodal_server_config_description'] = '        <p>Наша команда постоянно работает над максимальной оптимизацией процессов импорта и экспорта. Однако, если Ваш сервер не соответствует требованиям, а Вы запустите один из процессов, то в этом случае велика вероятность ошибок, а так же прерывание запущенного процесса.</p>
        <p><b>Это не ошибка данного модуля</b>, ниже представлены настройки PHP, которые необходимо проверить, а <b>в случае необходимости и возможности</b>, изменить на Вашем сервере:</p>
        
        <p>Единицы измерения для некоторых ключевых параметров PHP:</p>
        <ul>
            <li><b>memory_limit</b> (Мегабайты)</li>
            <li><b>max_execution_time</b> (Секунды)</li>
            <li><b>upload_max_filesize</b> (Мегабайты)</li>
            <li><b>post_max_size</b> (Мегабайты)</li>
        </ul>
        
        <p>В зависимости от данных и размера файла, который Вы пытаетесь обработать, может потребоваться изменение указанных выше параметров PHP, рекомендуемые значения представлены ниже:</p>
        <ul>
            <li><b>memory_limit</b>: 512M</li>
            <li><b>max_execution_time</b>: 800</li>
            <li><b>upload_max_filesize</b>: 240M</li>
            <li><b>post_max_size</b>: 250M</li>
        </ul>    
                
        <p>Лучшим способом изменения данных параметров является их редактирование прямо в конфигурации сервера, однако, если Вы не можете или не знаете как это сделать, мы рекомендуем обратиться в службу поддержки Вашего хостинга.</p>
        <p>---------------------------</p>
        <p>Так же Вы можете попытаться изменить данные параметры одним из следующих способов:</p>
        
        <p><b>СПОСОБ 1: Изменить файл <em>"php.ini"</em> в папке <em>admin</em> Вашего сайта, прописав следующие параметры:</b></p>
        <p>
        memory_limit = 512M<br>
        max_execution_time = 800<br>
        upload_max_filesize = 240M<br>
        post_max_size = 250M<br>
        </p>
        
        <p><b>СПОСОБ 2: Создать файл <em>".htaccess"</em> в папке <em>admin</em> Вашего сайта, прописав эти же параметры:</b></p>
        <p>
        php_value memory_limit 512M<br>
        php_value max_execution_time 800<br>
        php_value upload_max_filesize 240M<br>
        php_value post_max_size 250M<br>
        </p>
        
        <p><b>КАК ПРОВЕРИТЬ, ЧТО ИЗМЕНЕНИЯ ПРИМЕНЕНЫ:</b></p>
        <ol>
            <li>Создайте в корневой директории Вашего сайта файл <b>"phpinfo.php"</b> следующего содержания:<pre>&#60;?php phpinfo(); ?></pre></li>
            <li>В браузере перейдите по ссылке http://yourdomain.com/phpinfo.php</li>
            <li>Нажмите CTRL+F или COMMAND+F и найдите значения редактируемых параметров, они должны соответствовать тем, которые были прописаны в файле <em>"php.ini"</em> или <em>".htaccess"</em></li>
        </ol>
       
        <p>Если Вы видите, что у редактируемых параметров значения остались меньше, чем было прописано в одном из файлов, по одному из методов, это говорит о том, что хостинг не разрешает редактировать системные настройки сервера путем изменения файлов в выделенной клиенту директории. Изменения должны быть прописаны непосредственно в конфигурационных файлах сервера, после чего он должен быть перезагружен. Для решения данного вопроса нужно обратиться в службу поддержки хостинга.</p>
        ';
$_['export_import_remodal_server_config_link'] = 'ВАЖНО: ПРОЧТИТЕ ЭТО ПЕРЕД ЗАПУСКОМ ПРОФИЛЯ';
$_['progress_export_starting_process'] = 'Запуск процесса экспорта...';
$_['progress_export_element_numbers'] = 'Элементов в экспорте <b>%s</b>';
$_['progress_export_processing_elements'] = 'В работе элементов экспорта...';
$_['progress_export_processing_elements_processed'] = 'Обработано элементов: <b>%s</b> из <b>%s</b>';
$_['progress_export_elements_inserted'] = 'Элементов в работе: <b>%s</b> из <b>%s</b>';
$_['progress_export_error_range'] = '<b>Ошибка:</b> Значение "ОТ" больше чем значение "ДО"';
$_['progress_import_error_columns'] = '<b>Ошибка:</b> Система обнаружила, что некоторые колонки загруженного файла не были загружены в соответствии с настройками профиля:
        <br><br>
        <b>Колонок в ФАЙЛЕ:</b>
        %s
        <br>
        <b>Колонок в ПРОФИЛЕ:</b>
        %s
    ';
$_['progress_import_starting_process'] = 'Запуск процесса импорта...';
$_['progress_import_from_product_creating_categories'] = '<b>Создание категорий...</b>';
$_['progress_import_from_product_created_categories'] = 'Создано категорий: <b>%s</b> ';
$_['progress_import_from_product_error_cat_repeat_categories'] = '<b>Ошибка:</b> Имя категории <a href="%s" target="_blank"><b>%s</b></a> повторяется, переименуйте его или используйте "Дерево категорий"  в настройках профиля.';
$_['progress_import_from_product_creating_filter_groups'] = '<b>Создание групп фильтров...</b>';
$_['progress_import_from_product_created_filter_groups'] = 'Созданы группы фильтров <b>%s</b>';
$_['progress_import_from_product_creating_filter_groups_error_repeat'] = '<b>Ошибка:</b> Группа фильтров с именем <a href="%s">"<b>%s</b>"</a> повторяется.';
$_['progress_import_from_product_creating_filters'] = '<b>Создание фильтров...</b>';
$_['progress_import_from_product_created_filters'] = 'Фильтров создано <b>%s</b>';
$_['progress_import_from_product_creating_filters_error_no_group'] = 'Система не может создать фильтр "<b>%s</b>", группа фильтров для него не назначена.';
$_['progress_import_from_product_creating_attribute_groups'] = '<b>Создание группы атрибутов...</b>';
$_['progress_import_from_product_created_attribute_groups'] = 'Созданы группы атрибутов <b>%s</b>';
$_['progress_import_from_product_creating_attribute_groups_error_repeat'] = '<b>Ошибка:</b> Группа атрибутов с именем <a href="%s">"<b>%s</b>"</a> повторяется.';
$_['progress_import_from_product_creating_attributes'] = '<b>Создание атрибутов...</b>';
$_['progress_import_from_product_created_attributes'] = 'Атрибутов создано <b>%s</b>';
$_['progress_import_from_product_creating_attributes_error_no_group'] = 'Система не может создать атрибут "<b>%s</b>", группа атрибутов для него не назначена.';
$_['progress_import_from_product_creating_manufacturers'] = '<b>Создание производителей...</b>';
$_['progress_import_from_product_created_manufacturers'] = 'Производителей создано <b>%s</b>';
$_['progress_import_from_product_creating_options_error_empty_main_field'] = '<b>Ошибка:</b> Для работы с опцией ее идентификатор "<b>%s</b>" должен быть прописан в файле excel. Подключите данный столбец в настройках Вашего профиля, или отключите все "<b>Опции XXXX</b>", если Вы действительно не хотите их менять.';
$_['progress_import_from_product_creating_options'] = '<b>Создание опций...</b>';
$_['progress_import_from_product_created_options'] = 'Опций создано <b>%s</b>';
$_['progress_import_from_product_creating_options_error_repeat'] = '<b>Ошибка:</b> Опция <a href="%s">"<b>%s</b>"</a>, тип "<b>%s</b>" повторяется.';
$_['progress_import_from_product_creating_options_error_option_type'] = '<b>Ошибка:</b> Для работы с опциями, необходимо указать тип опций в настройках "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values'] = '<b>Создание значений опций...</b>';
$_['progress_import_from_product_created_option_values'] = 'Значений опций создано <b>%s</b>';
$_['progress_import_from_product_creating_option_values_error_option_type'] = 'Ошибка в строке <b>%s</b>: Для работы с опциями, необходимо указать тип опций в настройках "<b>%s</b>"';
$_['progress_import_from_product_creating_option_values_error_option'] = 'Ошибка в строке <b>%s</b>: Для работы со значениями опций, опцию необходимо назначить "<b>%s</b>"';
$_['progress_import_from_product_creating_downloads'] = '<b>Создание загрузок...</b>';
$_['progress_import_from_product_created_downloads'] = 'Загрузок создано <b>%s</b>';
$_['progress_import_product_error_option_data_in_main_row'] = '<b>Ошибка в строке %s</b>: Обнаружены данные опций в строке товара. Удалите содержимое в столбце "<b>Опция xxxxx</b>".';
$_['progress_import_product_error_product_related_not_found'] = '<b>Ошибка в строке %s</b>: Товар с моделью <b>%s</b> не найден в Вашем магазине. Если этот продукт содержится в файле excel, убедитесь, значение Модели находится не <b>перед</b> строкой "Опции товара".';
$_['progress_import_elements_process_start'] = '<b>Запуск обработки элементов...</b>';
$_['progress_import_elements_processed'] = 'Элементов обработано: <b>%s</b> из <b>%s</b>';
$_['progress_import_error_main_identificator'] = 'Главный идентификатор товара "<b>%s</b>" не существует в Ваших данных, убедитесь, что данная колонка включена в "<b>Сопоставлении столбцов</b>" или она <b>существует</b> в файле, который Вы хотите импортировать.';
$_['progress_import_process_format_data_file'] = '<b>Форматирование данных файла...</b>';
$_['progress_import_process_format_data_file_progress'] = 'Отформатировано элементов: <b>%s</b> из <b>%s</b>';
$_['progress_import_elements_conversion_start'] = '<b>Запуск преобразования значений элементов...</b>';
$_['progress_import_elements_converted'] = 'Преобразовано значений элементов:  <b>%s</b> из <b>%s</b>';
$_['progress_import_process_start'] = '<b>Запуск процесса импорта...</b> Расслабьтесь и возьмите чашечку чая %s';
$_['progress_import_process_imported'] = 'Импортировано элементов:  <b>%s</b> из <b>%s</b>';
$_['progress_import_applying_changes_safely'] = '<b>Внесенные изменения безопасны</b>';
$_['progress_import_finished'] = '<b>%s</b><b>Импорт успешно завершен!</b>
                <ul>
                    <li>Элементов создано: <b>%s</b></li>
                    <li>Элементов изменено: <b>%s</b></li>
                    <li>Элементов удалено: <b>%s</b></li>
                </ul>';
$_['progress_import_error_updating_conditions'] = 'ВНУТРЕННЯЯ ОШИБКА: Попытка обновить строку таблицы, без условия: <b>%s</b>';
$_['progress_import_error_skipped_all_elements'] = 'Все элементы внутри этого файла были пропущены, проверьте настройку «<b>предварительного фильтра</b>» в профиле.';
$_['progress_import_error_empty_data'] = '<b>Ошибка:</b> Нет данных, убедитесь, что загружаемый файл соответствует настройкам профиля.';
$_['export_import_download_empy_file'] = 'Нажмите, чтобы скачать пример файла, для данного профиля';
$_['progress_import_elements_splitted_values_start'] = '<b>Деление и получение значений...</b>';
$_['progress_import_elements_splitted_progress'] = 'Элементов обработано:  <b>%s</b>из <b>%s</b>';
$_['progress_import_export_error_wrong_conditional_value'] = 'Условное значение "<b>%s</b>" построено не верно. Уточните детали  "<b>Условных значений</b>" в помощи.';
$_['progress_import_export_error_wrong_conditional_value_multiple_symbols'] = 'Условное значение "<b>%s</b>" построено не верно. Найдено более чем одно условное значение "<b>%s</b>". Уточните детали  "<b>Условных значений</b>" в помощи.';
$_['progress_import_product_error_empty_description'] = '<b>Ошибка при создании товара</b>: Попытка создания товара без нужных данных (имя, описание...), json товар: %s.';
$_['progress_import_elements_no_numeric_id'] = '<b>Ошибка в ID нет чисел</b>: Вы включили параметр "ID вместо имени", но в нескольких колонках система не видит чисел в ID: <b>%s</b>.';
$_['progress_import_product_option_values_error_option_doesnt_exist'] = '<b>Ошибка в файле, строка %s:</b> Опция "<b>%s</b>" не создана, убедитесь, что Вы импортировали все опции, прежде чем импортировать связанные с ними значения.';
$_['progress_import_product_option_values_error_not_product_identificator'] = '<b>Ошибка в файле, строка %s:</b> Идентификатор продукта не создан';
$_['progress_import_applying_pre_filters'] = '<b>Применение предварительных фильтров</b>';
$_['progress_import_applying_file_filters'] = 'Применение <b>файловых фильтров</b>';
$_['progress_import_applying_shop_filters'] = 'Применение <b>фильтров магазина</b>';
$_['progress_import_elements_deleted'] = '<b>%s</b> элементов удалено';
$_['progress_import_elements_skipped'] = '<b>%s</b> элементов пропущено';
$_['progress_import_elements_disabled'] = '<b>%s</b> элементов отключено';
$_['progress_import_mapping_categories'] = '<b>Разметка категорий</b>';

$_['progress_import_updating_combinations_as_products_index'] = '<b>Обновление комбинации как индекса продукта ... </b> Предупреждение: проявите терпение, это может занять время! %s';

$_['progress_import_downloading_remote_images'] = '<b>Удаленная загрузка изображений...</b>';
$_['progress_import_downloading_remote_images_progress'] = 'Обработанные изображения: <b>%s</b> из <b>%s</b>';

?>