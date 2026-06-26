<?php
$_['cron_version'] = 'Версия_расширения';
$_['thead_cron_cron'] = 'Профиль';
$_['thead_cron_status'] = 'Статус';
$_['thead_cron_email'] = 'Отчет на email';
$_['thead_cron_period'] = 'Период повторений';
$_['thead_cron_configurator'] = 'Настройка заданий CRON';
$_['cron_all_minutes'] = 'Минуты';
$_['cron_all_hours'] = 'Часы';
$_['cron_all_days'] = 'Дни месяца';
$_['cron_all_months'] = 'Месяцы';
$_['cron_all_weekdays'] = 'Дни недели';
$_['cron_php_path'] = 'Путь PHP';
$_['cron_php_path_remodal_title'] = 'Путь PHP';
$_['cron_php_path_remodal_description'] = '        <p>Путь PHP, если Вы не знаете что это такое, свяжитесь со специалистами технической поддержки хостинга.</p>
        <p>Примеры пути:</p>
        <ul>
            <li>/usr/bin/php</li>
            <li>/usr/local/bin/php</li>
            <li>/usr/local/cpanel/3rdparty/bin/php</li>
        </ul>
    ';
$_['cron_php_path_remodal_link'] = '<b>ВАЖНО:</b> узнать подробнее';
$_['cron_config_remodal_title'] = 'О CRON заданиях';
$_['cron_config_remodal_description'] = '<p>Для удобства ниже представлены 3 варианта конфигурации заданий CRON <b>в настройках Вашего сервера</b>. Информация носит ознакомительный характер, мы не несем ответственности за последствия конфигурации Вашего сервера, для выполнения заданий по расписанию. Отдельно отметим, что работы по настройке заданий CRON <b>НЕ входят в поддержку модуля</b>.</p>
<p>Чтобы задания по расписанию CRON работали, Вам нужно в панели администратора сайта, в соответствующей вкладке просто <b>включить их</b> и больше ничего. Дополнительно, если Вы укажете свой адрес электронной почты внутри соответствующего поля "<b>Email</b>", то на указанный адрес будут приходить отчеты о выполнении заданий CRON.</p>
<p>Не забудьте нажать кнопку "<b>Сохранить настройки CRON</b>" для сохранения настроек.</p>
<br>
<h1>Настройка CRON на стороне сервера</h1>
<p style="color: #0D4AA2;"><b>ВАРИАНТ 1 - НАСТРОЙКА CRON С ИСПОЛЬЗОВАНИЕМ ИНТЕРФЕЙСА ХОСТИНГА:</b></p>
<p>На многих современных хостингах в качестве панели управления используется "Plesk" или "Cpanel". В настройках этих панелей предусмотрен интерфейс для работы с заданиями CRON, рассмотрим пример:</p>

<ol>
    <li>Выберете свою рабочую область (может быть не активна в Вашей панели).</li>
    <li><b>Тип задания</b>: Запуск скрипта PHP</li>
    <li>Путь к файлу с заданиями CRON: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать путь</a><div class="to_copy" style="display: none">CRON_PATH</div></a></li>
    <li>Аргументы расписаний CRON: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать аргументы</a><div class="to_copy" style="display: none">CRON_ARGUMENTS</div></a></li>
    <li>Установите периоды <b>исполнения</b> для заданий CRON.</li>
    <li>Заполните <b>Описание</b></li>
    <li>Нажмите кнопку <b>ОК</b>. (или СОХРАНИТЬ, или ПРИМЕНИТЬ)</li>
</ol>
<img style="width: 605px;" src="%s">
<br><br>
<p style="color: #0D4AA2;"><b>ВАРИАНТ 2 - НАСТРОЙКА CRON ПО SSH:</b></p>
<ol>
    <li>Подключитесь к своему серверу <b>по SSH</b>. </li>
    <li>Выполните команду: <b>crontab –e</b></li>
    <li><b>Вставьте</b> нужные команды для настройки CRON (примеры ниже).</li>
    <li>Внесите нужные изменения и нажмите “<b>Ctr+X</b>”, а следом “<b>Y</b>”.</li>
</ol>
<b><u>Пример 1 - каждые 15 минут</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать пример</a><div class="to_copy" style="display: none">*/15 * * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Пример 2 - один раз в день в 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать пример</a><div class="to_copy" style="display: none">0 0 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Пример 3 - два раза в день в 00:00 и 12:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать пример</a><div class="to_copy" style="display: none">0 */12 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Пример 4 - каждое воскресенье в 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать пример</a><div class="to_copy" style="display: none">0 0 * * 0 PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Пример 5 - каждый месяц</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопировать пример</a><div class="to_copy" style="display: none">0 0 30 * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<br>
<b style="color: #f00;">PATH_TO_PHP:</b> в примерах вышефигурирует параметр пути до РНР файла "PATH_TO_PHP", его необходимо будет заменить его на актуальный путь конкретно для Вашего сервера. Если Вы не уверены в правильности данного пути или не знаете, где его посмотреть, пожалуйста, обратитесь в службы поддержки хостинга.
<br><br>
<p style="color: #0D4AA2;"><b>ВАРИАНТ 3 - с помощью команд WGET:</b></p>
<p>Если у Вас возникли сложности с настройкой расписания CRON традиционными методами, мы предлагаем использовать команды "wget" для решения вопроса.</p>
<ol>
    <li>Убедитесь, что Ваше задание CRON находится в режиме "<b>Command</b>".</li>
    <li><a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопируйте данную команду</a><div class="to_copy" style="display: none">CRON_WGET_COMMAND</div></a> и вставьте в поле "command".</li>
    <li>Сохраните настройки CRON.</li>
</ol>
<h1>Ручная настройка заданий CRON</h1>
Вы можете провести имитацию исполнения задания CRON, используя <a href="EXECUTE_PROFILE_NOW" target="_blank">следующую ссылку</a>. При выполнении имитации Вы будуте разлогинены в панели администратора сайта.';
$_['cron_config_remodal_link'] = 'Руководство';
$_['cron_error_profile_id'] = 'Ошибка: Выберете профиль задания CRON.';
$_['cron_error_path_to_php'] = 'Ошибка: Закройте это окно и заполните полеt \"<b>Путь PHP</b>\" - это необходимо для правильной работы команд CRON.';
$_['cron_command_copied'] = 'Скопировано в буфер.';
$_['cron_month_1'] = 'Январь';
$_['cron_month_2'] = 'Февраль';
$_['cron_month_3'] = 'Март';
$_['cron_month_4'] = 'Апрель';
$_['cron_month_5'] = 'Май';
$_['cron_month_6'] = 'Июнь';
$_['cron_month_7'] = 'Июль';
$_['cron_month_8'] = 'Август';
$_['cron_month_9'] = 'Сентябрь';
$_['cron_month_10'] = 'Октябрь';
$_['cron_month_11'] = 'Ноябрь';
$_['cron_month_12'] = 'Декабрь';
$_['cron_weekday_0'] = 'Понедельник';
$_['cron_weekday_1'] = 'Вторник';
$_['cron_weekday_2'] = 'Среда';
$_['cron_weekday_3'] = 'Четверг';
$_['cron_weekday_4'] = 'Пятница';
$_['cron_weekday_5'] = 'Суббота';
$_['cron_weekday_6'] = 'SuВоскресеньеnday';
$_['cron_save'] = 'Сохранить настройки CRON';
$_['cron_config_save_sucessfully'] = 'Настройки успешно сохранены!';
$_['cron_config_save_error_repeat_profiles'] = '<b>Ошибка:</b> Обнаружены дубли профилей.';
$_['cron_error_disabled'] = 'Профиль "<b>%s</b>" отключен в настройках CRON.';
$_['cron_error_not_found'] = 'Профиль "<b>%s</b>" не найден в настройках CRON.';
?>