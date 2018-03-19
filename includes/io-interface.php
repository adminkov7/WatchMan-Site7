<?php
/*
Slave module: io-interface.php
Description:  Various interface functions processing of external data
Version:      2.2.5
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

function wms7_save_index_php($file_content){
//file name
	$filename = $_SERVER['DOCUMENT_ROOT'].'/index_new.php';
//remove the shielding
	$file_content = stripslashes($file_content);
//Write content to a file
	file_put_contents($filename, $file_content);
//rename the file
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/index.php');
}

function wms7_save_robots_txt($file_content){
//file name
	$filename = $_SERVER['DOCUMENT_ROOT'].'/robots_new.txt';
//remove the shielding
	$file_content = stripslashes($file_content);
//Write content to a file
	file_put_contents($filename, $file_content);
//rename the file
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/robots.txt');	
}

function wms7_save_htaccess($file_content){
//file name
	$filename = $_SERVER['DOCUMENT_ROOT'].'/new.htaccess';
//remove the shielding
	$file_content = stripslashes($file_content);	
//Write content to a file
	file_put_contents($filename, $file_content);
//rename the file
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/.htaccess');	
}

function wms7_save_wp_config($file_content){
//file name
	$filename = $_SERVER['DOCUMENT_ROOT'].'/wp-configs_new.php';
//remove the shielding
	$file_content = stripslashes($file_content);	
//Write content to a file
	file_put_contents($filename, $file_content);
//rename the file
	rename($filename, $_SERVER['DOCUMENT_ROOT'].'/wp-config.php');
}

function wms7_ip_delete_from_file($user_ip){
//file name
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
	//file name
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';	

	//search string in file
	if (strpos(file_get_contents($filename), $user_ip)) return;
	//Open the file to get existing content
	$current = file_get_contents($filename);
	//Add a new line to the file
	$current .= "\n"."Deny from ".$user_ip;
	//Write contents back to file
	file_put_contents($filename, $current);
}

function wms7_rewritecond_insert($robot_banned){
	//file name
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
	// insert Deny from env = wms7_bad_bot
	if (strpos(file_get_contents($filename), 'wms7_bad_bot') == false) {
		//Open the file to get existing content
		$current = file_get_contents($filename);
		$current = 'Deny from env=wms7_bad_bot'."\n".$current;
		//Write contents back to file
		file_put_contents($filename, $current);		
	}
	//search string in file
	if (strpos(file_get_contents($filename), $robot_banned)) return;
	//Open the file to get existing content
	$current = file_get_contents($filename);
	//Add a new line to the file
	$current = 'SetEnvIfNoCase User-Agent "'
		.$robot_banned
		.'" wms7_bad_bot'
		."\n"
		.$current;
	//Write contents back to file
	file_put_contents($filename, $current);
}

function wms7_rewritecond_delete(){
	//file name
	// Do not sanitize $filename
	$filename = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';

	//Open the file to get existing content
	$current = file($filename);

	foreach($current as $key => $value){
		if (strpos($value, 'wms7_bad_bot')) {
			unset($current[$key]);
		}
	}
	unset($key);
	//Write contents back to file
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
	$table_name = $wpdb->prefix . 'watchman_site';

	$export = get_option('wms7_action');
	if ($export == 'export') {
		//do not sanitize_text_field $_REQUEST['id']
		$ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : FALSE;
		if (!$ids) return;
		$flds = wms7_flds_csv();
		if (!$flds) return;

		if (is_array($ids)) $ids = implode(',', $ids);

		if (!empty($ids)) {
			$sql = "SELECT $flds FROM $table_name WHERE `id` IN($ids)";
		}

		$DataArray = $wpdb->get_results($sql, 'ARRAY_A');
		$filename = 'wms7_export_'.date("Y-m-d").'.csv';

		//reset the PHP output buffer
		if (ob_get_level()) {
		      ob_end_clean();
		}

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename='.$filename);
		$fp = fopen('php://output', 'w');
		foreach($DataArray as $values){
			fputcsv($fp, $values);
		}
		unset($values);
		fclose($fp);
		exit;
	}
}

function wms7_output_attach($filename) {

	$file_extension = strtolower(substr(strrchr($filename,"."),1));
	$path_file = $_SERVER['DOCUMENT_ROOT'].'/tmp/';

	if( $filename == "" )	{
	          echo "ОШИБКА: не указано имя файла.";
	          exit;
		}elseif ( ! file_exists( $path_file.$filename ) ) {
	          echo "ОШИБКА: данного файла не существует.";
	          exit;
	}
	switch( $file_extension ){
	  case "pdf": $ctype="application/pdf"; break;
	  case "exe": $ctype="application/octet-stream"; break;
	  case "zip": $ctype="application/zip"; break;
	  case "doc": $ctype="application/msword"; break;
	  case "xls": $ctype="application/vnd.ms-excel"; break;
	  case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
	  case "mp3": $ctype="audio/mp3"; break;
	  case "gif": $ctype="image/gif"; break;
	  case "png": $ctype="image/png"; break;  
	  case "jpeg":
	  case "jpg": $ctype="image/jpg"; break;
	  default: $ctype="application/force-download";
	}
	//reset the PHP output buffer
	if (ob_get_level()) {
	      ob_end_clean();
	}
	header("Pragma: public"); 
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false); // нужен для некоторых браузеров
	header("Content-Type: $ctype");
	header("Content-Disposition: attachment; filename=$filename");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($path_file.$filename));
	readfile($path_file.$filename);
	exit();
}