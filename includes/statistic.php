<?php
/*
Slave module: statistic.php
Description:  Create statistics table of visits
Version:      2.2.2
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

ini_set('max_execution_time', 30); //120 seconds = 2 minutes

function wms7_create_table_stat(){
	global $wpdb;
	
  $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site_cross_table (`date_country` longtext NOT NULL,`tbl_country` longtext NOT NULL,`tbl_result` longtext NOT NULL)";
	$wpdb->query($sql);
	$sql = "TRUNCATE TABLE {$wpdb->prefix}watchman_site_cross_table";
	$wpdb->query($sql);

	$sql = "INSERT INTO {$wpdb->prefix}watchman_site_cross_table (`date_country`, `tbl_country`, `tbl_result`)
	SELECT DATE_FORMAT(`time_visit`,'%Y %m') as `date_country`, LEFT(`country`,4) as `tbl_country`, COUNT(`user_ip`) as `tbl_result` FROM {$wpdb->prefix}watchman_site GROUP BY `date_country`, `tbl_country` ORDER BY `tbl_country`
	";
	$wpdb->query($sql);

	$sql = "SELECT DISTINCT `tbl_country` FROM {$wpdb->prefix}watchman_site_cross_table";
	$DataArray =  $wpdb->get_results($sql, 'ARRAY_A');
	$sql = "SELECT `date_country`, ";
	foreach($DataArray as $values){
		$sql = $sql . "group_concat(IF(`tbl_country`='".$values['tbl_country']."', tbl_result, NULL)) as `".$values['tbl_country']."`, ";
	}
	unset($values);
	$sql = substr($sql, 0, -2);
	$sql = $sql . " FROM {$wpdb->prefix}watchman_site_cross_table GROUP BY `date_country`";

	$records = $wpdb->get_results($sql, 'ARRAY_A');

	return ($records);
}

function wms7_table_stat($records){

	$tbl_head = '<table class="table"><thead class="thead"><tr class="tr">';
	$tbl_foot = '<tfoot class="tfoot"><tr class="tr">';

	foreach($records[0] as $key=>$value){
		$tbl_head = $tbl_head . "<th class='td'>$key</th>";
		$tbl_foot = $tbl_foot . "<th class='td'>$key</th>";
	}
	$tbl_head = $tbl_head . '</tr></thead><tbody class="tbody">';
	$tbl_foot = $tbl_foot . '</tr></tfoot>';

	$tbl_body = '';
	foreach($records as $record){
		$i = 0;
		$tbl_body = $tbl_body . '<tr class="tr">';
		foreach($record as $key=>$value){
			if ($i == 0) {
					$tbl_body = $tbl_body . "<td class='td' width='80px;'>$value</td>";
				}else{
					$tbl_body = $tbl_body . "<td class='td'>$value</td>";
			}
			$i++;
		}
		$tbl_body = $tbl_body . '</tr>';
	}
	$tbl = $tbl_head . $tbl_body . '</tbody>'.$tbl_foot.'</table>';

	return $tbl;
}