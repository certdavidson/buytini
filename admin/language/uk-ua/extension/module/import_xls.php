<?php
$api_url = defined('DEVMAN_SERVER_TEST') ? DEVMAN_SERVER_TEST : 'https://devmanextensions.com/';
$extension_name = "Import / Export Pro";
$extension_name_image = '<a href="https://devmanextensions.com/" target="_blank"><img src="'. $api_url . 'opencart_admin/common/img/devman_face.png"> DevmanExtensions.com</a> - '.$extension_name;
$ext_version = '9.12.2';

$_['extension_version'] = $ext_version;
// Heading
$_['heading_title']    = $extension_name_image.' (V.'.$ext_version.')';
$_['heading_title_2']  = $extension_name;

$_['text_buttom']      = 'Import / Export Pro';
$_['text_license_info'] = '<h3>Де можна знайти ID замовлення (ID ліцензії)?</h3>
<p>Після оформлення замовлення ви отримаєте повну інформацію про ліцензію на електронну пошту, яка використовувалась при оформленні замовлення. Обов’язково перевірте <b>папку SPAM</b>.</p>
<br>
<p>В залежності від того де ви придбали ліцензію, ідентифікатор замовлення відрізнятиметься:</p>
<ul>
<li>Ліцензія придбана у магазині <a href="https://devmanextensions.com/extensions-shop" target="_blank">Devman Store</a>: <b>MLXXXXXX</b></li>
<li>Ліцензія придбана у магазині Opencart: <b>XXXXXX</b> ("XXXXXX" числове значення).</li>
<li>Ліцензія придбана у магазині Opencartforum: <b>of-XXXXXX</b> ("XXXXXX" числове значення).</li>
<li>Ліцензія придбана у магазині IsenseLabs: <b>isenselabs-XXXXXX</b> ("XXXXXX" числове значення).</li>
</ul>
';
$_['curl_error'] = '<b>Помилка CURL: %s</b><br><br>
<p>З’єднання між вашим сервером та сервером для перевірки ліцензії не було встановлено.</p>
<p><b>Зв’яжіться зі службою підтримки вашого хостингу</b>, вони зможуть допомогти у вирішенні цієї проблеми.</p>
<p>Модуль відправляє простий запит CURL для домену https://devmanextensions.com (213.239.217.148).</p>';