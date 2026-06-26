<?php
$_['cron_version'] = 'EXTENSION_VERSION';
$_['thead_cron_cron'] = 'Профіль';
$_['thead_cron_status'] = 'Статус';
$_['thead_cron_email'] = 'Звіт на email';
$_['thead_cron_period'] = 'Період повторень';
$_['thead_cron_configurator'] = 'Налаштування завдань CRON';
$_['thead_cron_configurator'] = 'Короткий посібник';
$_['cron_all_minutes'] = 'Хвилини';
$_['cron_all_hours'] = 'Години';
$_['cron_all_days'] = 'Дні місяця';
$_['cron_all_months'] = 'Місяці';
$_['cron_all_weekdays'] = 'Дні тижня';
$_['cron_php_path'] = 'Шлях до PHP';
$_['cron_php_path_remodal_title'] = 'Шлях до PHP';
$_['cron_php_path_remodal_description'] = '        <p>Введіть тут свій шлях до PHP, якщо ви не впевнені, зв’яжіться з технічною підтримкою хостингової компанії для з’ясування.</p>
        <p>Приклади:</p>
        <ul>
            <li>/usr/bin/php</li>
            <li>/usr/local/bin/php</li>
            <li>/usr/local/cpanel/3rdparty/bin/php</li>
        </ul>
    ';
$_['cron_php_path_remodal_link'] = '<b>ВАЖЛИВО:</b> дізнатися детальніше';
$_['cron_config_remodal_title'] = 'Про завдання CRON';
$_['cron_config_remodal_description'] = '<p>Для зручності нижче представлені варіанти конфігурації завдань CRON <b>в налаштуваннях вашого серверу</b>. Інформація носить ознайомлювальний характер, ми не несемо відповідальності за наслідки конфігурації Вашого сервера для виконання завдань за розкладом. Окремо відзначимо, що роботи з налаштування завдань CRON <b>НЕ входять у підтримку модуля</b>.</p>
<p>Щоб завдання за розкладом CRON працювали, Вам потрібно в панелі адміністратора сайту, у відповідній вкладці просто <b>включити їх</b> і більш нічого. Додатково, якщо ви зазначите свою адресу електронної пошти у відповідному полі "<b>Email</b>", то на вказану адресу будуть приходити звіти про виконання завдань CRON.</p>
<p>Не забудьте натиснути кнопку "<b>Зберегти налаштування CRON</b>" для збереження налаштувань.</p>
<br>
<h1>Налаштування CRON на сервері</h1>
<p style="color: #0D4AA2;"><b>ВАРІАНТ 1 - НАЛАШТУВАННЯ CRON З ВИКОРИСТАННЯМ ІНТЕРФЕЙСУ ХОСТИНГУ:</b></p>
<p>На багатьох хостингах у якості панелі керування використовується "Plesk" або "Cpanel". В налаштуваннях цих панелей передбачений інтерфейс для роботи із завданнями CRON. Розглянемо приклад:</p>

<ol>
    <li>Оберіть свою робочу область (може бути не активною у вашій панелі).</li>
    <li><b>Тип завдання</b>: Запуск скрипта PHP</li>
    <li>Шлях до файлу із завданням CRON: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати шлях</a><div class="to_copy" style="display: none">CRON_PATH</div></a></li>
    <li>Аргументи розкладу CRON: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати аргументи</a><div class="to_copy" style="display: none">CRON_ARGUMENTS</div></a></li>
    <li>Зазначте період <b>виконання</b> для завдань CRON.</li>
    <li>Заповніть <b>Опис</b></li>
    <li>Натисніть кнопку <b>ОК</b>. (або ЗБЕРЕГТИ, або ЗАСТОСУВАТИ)</li>
</ol>
<img style="width: 605px;" src="%s">
<br><br>
<p style="color: #0D4AA2;"><b>ВАРІАНТ 2 - НАЛАШТУВАННЯ CRON ПО SSH:</b></p>
<ol>
    <li>Підключіться до свого серверу <b>по SSH</b>. </li>
    <li>ВАиконайте команду: <b>crontab –e</b></li>
    <li><b>Вставте</b> необхідні команди для налаштування CRON (приклади нижче).</li>
    <li>Внесіть необхідні зміни та натисніть “<b>Ctr+X</b>”, а потім “<b>Y</b>”.</li>
</ol>
<b><u>Приклад 1 - кожні 15 хвилин</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати приклад</a><div class="to_copy" style="display: none">*/15 * * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Приклад 2 - один раз на день в 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати приклад</a><div class="to_copy" style="display: none">0 0 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Приклад 3 - два рази на день в 00:00 и 12:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати приклад</a><div class="to_copy" style="display: none">0 */12 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Приклад 4 - кожну неділю в 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати приклад</a><div class="to_copy" style="display: none">0 0 * * 0 PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Приклад 5 - кожний місяць</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіювати приклад</a><div class="to_copy" style="display: none">0 0 30 * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<br>
<b style="color: #f00;">PATH_TO_PHP:</b> у прикладах вище зазначений параметр шляху до РНР файлу "PATH_TO_PHP", його необхідно буде замінити на актуальний шлях конкретно для вашого серверу. Якщо ви не впевнені у правильності даного шляху або не знаєте де його подивитись, будь ласка, зверніться у технічну підтримку вашого хостингу.
<br><br>
<p style="color: #0D4AA2;"><b>ВАРІАНТ 3 - за допомогою команд WGET:</b></p>
<p>Якщо у вас виникли труднощі з налаштуванням завдань CRON за прикладами, наведеними вище, пропонуємо використання команд "wget" для вирішення питання.</p>
<ol>
    <li>Переконайтесь, що ваше завдання CRON наразі в режимі "<b>Command</b>".</li>
    <li><a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Скопіюйте дану команду</a><div class="to_copy" style="display: none">CRON_WGET_COMMAND</div></a> та вставте у поле "command".</li>
    <li>Збережіть налаштування CRON.</li>
</ol>
<h1>Ручне налаштування завдань CRON</h1>
Ви можете провести імітацію виконання завдання CRON з використанням <a href="EXECUTE_PROFILE_NOW" target="_blank">цього посилання</a>. При виконанні імітації буде виконаний вихід з панелі адміністратора сайту.';

$_['cron_config_remodal_link'] = 'Посібник';
$_['cron_error_profile_id'] = 'Помилка: оберіть профіль для створення завдання CRON.';
$_['cron_error_path_to_php'] = 'Помилка: закрийте це вікно та заповніть поле \"<b>Шлях до PHP</b>\" - це необхідно для правильної роботи команд CRON.';
$_['cron_command_copied'] = 'Скопійовано у буфер обміну';
$_['cron_month_1'] = 'Січень';
$_['cron_month_2'] = 'Лютий';
$_['cron_month_3'] = 'Березень';
$_['cron_month_4'] = 'Квітень';
$_['cron_month_5'] = 'Травень';
$_['cron_month_6'] = 'Червень';
$_['cron_month_7'] = 'Липень';
$_['cron_month_8'] = 'Серпень';
$_['cron_month_9'] = 'Вересень';
$_['cron_month_10'] = 'Жовтень';
$_['cron_month_11'] = 'Листопад';
$_['cron_month_12'] = 'Грудень';
$_['cron_weekday_0'] = 'Понеділок';
$_['cron_weekday_1'] = 'Вівторок';
$_['cron_weekday_2'] = 'Середа';
$_['cron_weekday_3'] = 'Четвер';
$_['cron_weekday_4'] = 'П’ятниця';
$_['cron_weekday_5'] = 'Субота';
$_['cron_weekday_6'] = 'Неділя';
$_['cron_save'] = 'Зберегти налаштування CRON';
$_['cron_config_save_sucessfully'] = 'Налаштування збережені!';
$_['cron_config_save_error_repeat_profiles'] = '<b>Помилка:</b> виявлено дублі профілів';
$_['cron_error_disabled'] = 'Профіль "<b>%s</b>" відключений у налаштуваннях CRON.';
$_['cron_error_not_found'] = 'Профіль "<b>%s</b>" не знайдений у налаштуваннях CRON.';
?>