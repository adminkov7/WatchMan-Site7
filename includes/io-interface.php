<?php
/*
Slave module: io-interface.php
Description:  Various interface functions processing of external data
Version:      2.2.1
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

function wms7_save_index_php($file_content){
//имя файла
	$filename = $_SERVER['DOCUMENT_ROOT'].'/index_new.php';
// удаляем экранирование
	$file_content = stripslashes($file_content);
// Пишем содержимое в файл
	file_put_contents($filename, $file_content);
//переименовываем файл
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/index.php');
}

function wms7_save_robots_txt($file_content){
//имя файла
	$filename = $_SERVER['DOCUMENT_ROOT'].'/robots_new.txt';
// удаляем экранирование
	$file_content = stripslashes($file_content);
// Пишем содержимое в файл
	file_put_contents($filename, $file_content);
//переименовываем файл
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/robots.txt');	
}

function wms7_save_htaccess($file_content){
//имя файла
	$filename = $_SERVER['DOCUMENT_ROOT'].'/new.htaccess';
// удаляем экранирование
	$file_content = stripslashes($file_content);	
// Пишем содержимое в файл
	file_put_contents($filename, $file_content);
//переименовываем файл
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/.htaccess');	
}

function wms7_save_wp_config($file_content){
// имя файла
	$filename = $_SERVER['DOCUMENT_ROOT'].'/wp-configs_new.php';
// удаляем экранирование
	$file_content = stripslashes($file_content);	
// пишем содержимое в файл
	file_put_contents($filename, $file_content);
//переименовываем файл
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/wp-config.php');
}

function wms7_ip_delete_from_file($user_ip){
//имя файла
// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
	$file=file($filename);

	if (!$file) return;

	$fp=fopen($filename,'w');
	for($i=0;$i<sizeof($file);$i++)
	{
		$pos = stristr($file[$i], 'Deny from '.$user_ip);

		if ($pos){
			unset($file[$i]);
		}
	}	
		fputs($fp,implode("",$file));
		fclose($fp);
}

function wms7_ip_insert_to_file($user_ip){
	//имя файла
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';	

	// поиск строки в файле
	if (strpos(file_get_contents($filename), $user_ip)) return;
	// Открываем файл для получения существующего содержимого
	$current = file_get_contents($filename);
	// Добавляем новую строку в файл
	$current .= "\n"."Deny from ".$user_ip;
	// Пишем содержимое обратно в файл
	file_put_contents($filename, $current);
}

function wms7_rewritecond_insert($robot_banned){
	//имя файла
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
	// вставка Deny from env = wms7_bad_bot
	if (strpos(file_get_contents($filename), 'wms7_bad_bot') == false) {
		// Открываем файл для получения существующего содержимого
		$current = file_get_contents($filename);
		$current = 'Deny from env=wms7_bad_bot'."\n".$current;
		// Пишем содержимое обратно в файл
		file_put_contents($filename, $current);		
	}
	// поиск строки в файле
	if (strpos(file_get_contents($filename), $robot_banned)) return;
	// Открываем файл для получения существующего содержимого
	$current = file_get_contents($filename);
	// Добавляем новую строку в файл
	$current = 'SetEnvIfNoCase User-Agent "'
		.$robot_banned
		.'" wms7_bad_bot'
		."\n"
		.$current;
	// Пишем содержимое обратно в файл
	file_put_contents($filename, $current);
}

function wms7_rewritecond_delete(){
	//имя файла
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';

	// Открываем файл для получения существующего содержимого
	$current = file($filename);

	foreach($current as $key => $value){
		if (strpos($value, 'wms7_bad_bot')) {
			unset($current[$key]);
		}
	}
	unset($key);
	// Пишем содержимое обратно в файл
	file_put_contents($filename, $current);
}
function wms7_flds_csv() {
  $val = get_option('wms7_main_settings');

  $flds['id'] = isset($val['id']) ? $val['id'] : '';
  $flds['uid'] = isset($val['uid']) ? $val['uid'] : '';
  $flds['user_login'] = isset($val['user_login']) ? $val['user_login'] : '';
  $flds['user_role'] = isset($val['user_role']) ? $val['user_role'] : '';
  $flds['time_visit'] = isset($val['time_visit']) ? $val['time_visit'] : '';
  $flds['user_ip'] = isset($val['user_ip']) ? $val['user_ip'] : '';
  $flds['black_list'] = isset($val['black_list']) ? $val['black_list'] : '';
  $flds['page_visit'] = isset($val['page_visit']) ? $val['page_visit'] : '';
  $flds['page_from'] = isset($val['page_from']) ? $val['page_from'] : '';
  $flds['info'] = isset($val['info']) ? $val['info'] : '';

  foreach($flds as $key => $value) {
    if ($value == '') unset($flds[$key]);
	}
	if (count($flds) == 0) return FALSE;
	$str = '';
  foreach($flds as $key => $value) {
    $str = $str.$key.',';
	}
		$str = substr($str, 0, -1);
	return $str;
}

function wms7_output_csv() {
	global $wpdb;

	if (isset($_REQUEST["action"]) && ($_REQUEST["action"] == 'export') || 
		 (isset($_REQUEST["action2"]) && $_REQUEST["action2"] == 'export')) {
			$export = TRUE;
		}else{
			$export = FALSE;
	}
	if ($export) {
		//do not sanitize_text_field $_REQUEST['id']
		$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : FALSE;
		if (!$ids) return;
		$flds = wms7_flds_csv();
		if (!$flds) return;

		update_option('wms7_action','export');		
		update_option('wms7_id',($_REQUEST['id']));

		$table_name = $wpdb->prefix . 'watchman_site';

		if (is_array($ids)) $ids = implode(',', $ids);

		if (!empty($ids)) {
			$sql = "SELECT $flds FROM $table_name WHERE `id` IN($ids)";
		}

		$DataArray = $wpdb->get_results($sql, 'ARRAY_A');

		$filename = 'wms7_export_'.date("Y-m-d").'.csv';

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename='.$filename);

		$fp = fopen('php://output', 'w');
		fputcsv($fp, array_keys($DataArray['0']));

		foreach($DataArray as $values){
			fputcsv($fp, $values);
		}
		unset($values);
		fclose($fp);

		exit;
	}
}