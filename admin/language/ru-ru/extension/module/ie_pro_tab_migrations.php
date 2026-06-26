<?php
$_['migration_export_legend'] = '<b>ЭКСПОРТ</b>: Массовый экспорт в резервную копию всех данных текущего каталога для переноса в другой магазин OpenCart';
$_['migration_export_button'] = 'Запустить процесс экспорта';
$_['migration_export_legend_destiny_label'] = 'Операция';
$_['migration_export_legend_destiny_none'] = 'Резервное копирование (не перенос)';
$_['migration_export_legend_destiny_oc1'] = 'Opencart 1.5.x';
$_['migration_export_legend_destiny_oc2'] = 'Opencart 2.x';
$_['migration_export_legend_destiny_oc3'] = 'Opencart 3.x';
$_['migration_export_legend_destiny_oc4'] = 'Opencart 4.x';
$_['migration_export_legend_destiny_oc4.1'] = 'Opencart 4.1+';
$_['migration_export_legend_format_label'] = 'Формат файла';
$_['migration_export_select_all_label'] = 'Выбрать все позиции';
$_['migration_export_error_select_category'] = '<b>Ошибка:</b> Не выбрано ни одной позиции для экспорта';
$_['migration_export_error_empty_data'] = '<b>Ошибка:</b> Нет данных для экспорта';

$_['migration_import_legend'] = '<b>ИМПОРТ</b>: Импорт файла другого магазина OpenCart или полное восстановление из резервной копии.';
$_['migration_import_upload_file_button'] = 'Загрузить файл';
$_['migration_import_warning_message_link'] = 'ВАЖНО: ПРОЧТИТЕ ЭТО ПЕРЕД ЗАПУСКОМ ПРОЦЕССА ИМПОРТА ДАННЫХ';
$_['migration_import_warning_message_title'] = 'Важное сообщение';
$_['migration_import_warning_message_description'] = '
    <p></p><b>Будьте осторожны</b>: После импорта данных из файла, соответствующие поля в базе данных будут изменены. Перед этим настоятельно рекомендуем сделать <b>полную резервную копию базы данных MySQL</b> или <b>отдельных полей, которые предстоит изменить</b> в процессе импорта данных.</p>
    <p>Если Вы осуществляете перенос данных из OpenCart 1.5.х в OpenCart 2.х или OpenCart 3.х, возможно, будет показано предупреждение, связанное с языковыми настройками. Просто перейдите в меню "<b>Система > Локализация > Языки</b>", откройте для редактирования каждый из языков и нажмите кнопку <b>"Сохранить".</b></p>';
$_['migration_import_button'] = 'Запустить процесс импорта';
$_['migration_import_error_xml_incompatible'] = '<b>Ошибка:</b> XML файл не может быть импортирован, убедитесь, что структура файла не нарушена.';
$_['migration_import_error_empty_file'] = '<b>Ошибка:</b> Загрузите файл перед запуском процесса импорта.';
$_['migration_import_error_extension'] = '<b>Ошибка:</b> недопустимый формат файла, допускаются только "<b>%s</b>"';
$_['migration_import_processing_table'] = 'Индикатор процесса "<b>%s</b>": обработано <b>%s</b> из <b>%s</b>';
$_['migration_import_empty_table'] = 'Нет данных в "<b>%s</b>"';
$_['migration_import_finished'] = '<b><i class="fa fa-check"></i>&nbsp;&nbsp;&nbsp;Импорт успешно завершен!</b>';
?>