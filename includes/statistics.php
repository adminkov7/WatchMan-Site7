<?php
/*
Slave module: statistics.php
Description:  Create statistics table of visits
Version:      2.2.1
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

ini_set('max_execution_time', 30); //120 seconds = 2 minutes

function wms7_create_table_statistics(){
	global $wpdb;
	$sql = "TRUNCATE TABLE {$wpdb->prefix}watchman_site_cross_table";
	$wpdb->query($sql);

	$sql = "CREATE TABLE {$wpdb->prefix}watchman_site_cross_table (`tbl_date_visit` longtext NOT NULL,`tbl_country` longtext NOT NULL,`tbl_result` longtext NOT NULL);
	INSERT INTO {$wpdb->prefix}watchman_site_cross_table (`tbl_date_visit`, `tbl_country`, `tbl_result`)
	SELECT DATE_FORMAT(`time_visit`,'%Y %m') as `tbl_date_visit`, LEFT(`country`,4) as `tbl_country`, COUNT(`user_ip`) as `tbl_result` FROM {$wpdb->prefix}watchman_site GROUP BY `tbl_date_visit`, `tbl_country` ORDER BY `tbl_country`
	";
	dbDelta($sql);

	$sql = "SELECT DISTINCT `tbl_country` FROM {$wpdb->prefix}watchman_site_cross_table";
	$DataArray =  $wpdb->get_results($sql, 'ARRAY_A');
	$sql = "SELECT `tbl_date_visit`, ";
	foreach($DataArray as $values){
		$sql = $sql . "group_concat(IF(`tbl_country`='".$values['tbl_country']."', tbl_result, NULL)) as `".$values['tbl_country']."`, ";
	}
	unset($values);
	$sql = substr($sql, 0, -2);
	$sql = $sql . " FROM {$wpdb->prefix}watchman_site_cross_table GROUP BY `tbl_date_visit`";

	$result = mysql_query($sql);	
	$rows=mysql_num_rows($result); 
	$cols=mysql_num_fields($result);

	$tbl = "<table class='win_modal2' name='wms4'><tr>";
	for($j=0; $j<$cols; $j++){
		$f=mysql_field_name($result, $j);
		$tbl = $tbl . "<td style='font-weight:bold;'>$f</td>";
	}
	$tbl = $tbl . "</tr>";
	for($i=0; $i<$rows; $i++){
		$row = mysql_fetch_array($result);
		$tbl = $tbl . "<tr>";
		for($j=0; $j<$cols; $j++){
			$tbl = $tbl . "<td>$row[$j]</td>";
		}
		$tbl = $tbl . "</tr>";
	}
	$tbl = $tbl . "</table>";
	return $tbl;
}