<?php 

// Heading
$_['v_mod'] = '64';
$_['vers_mod'] = '3.0.'.$_['v_mod'];
$_['p_mod'] = 'FilterVier_SEO';
$_['heading_title'] = '<b><img src="view/image/filter_vier/fv_logo.png" style="width:30px; height:30px; border:0;"><span style="color:blue; line-height: 30px;">'.$_['p_mod'].'_v.'.$_['vers_mod'].'</span></b>';
$_['title'] = $_['p_mod'].'_v.'.$_['vers_mod'];
if(defined('PHP_MAJOR_VERSION') && defined('PHP_MINOR_VERSION')) {$versi_php = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;}else{$versi_php = '?';}
$_['heading_title'] .= ' <span data-toggle="tooltip" title="site PHP '.$versi_php.'"><img src="view/image/filter_vier/helpis.png" style="border:0;width:12px;height:12px;"></span>';
