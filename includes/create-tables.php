<?php
/*
Slave module: create-tables.php
Description:  Creates 2 tables in the database of the website for the plugin
Version:      2.2.7
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

//For use dbDelta();
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

function wms7_create_tables(){
	//create new role for manage this plugin no administrator
	$result = add_role( 'analyst_wms7', 'Analyst wms7',
		array(
			'read'         				=> true,
			'activate_plugins'		=> true,
		)
	);
	//create tables
  global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site "."
	(
	id INT( 11 ) NOT NULL AUTO_INCREMENT ,
	uid INT( 11 ) NOT NULL ,
	user_login VARCHAR( 60 ) NOT NULL ,
	user_role VARCHAR( 30 ) NOT NULL ,
	time_visit DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL ,
	user_ip VARCHAR( 100 ) NOT NULL ,
	user_ip_info LONGTEXT NOT NULL ,
	black_list LONGTEXT NOT NULL ,
	whois_service VARCHAR( 30 ) NOT NULL ,
	country LONGTEXT NOT NULL ,
	provider LONGTEXT NOT NULL ,
	geo_ip LONGTEXT NOT NULL ,
	geo_wifi LONGTEXT NOT NULL ,
	login_result VARCHAR( 1 ) NOT NULL ,
	robot VARCHAR( 100 ) NOT NULL ,
	page_visit LONGTEXT NOT NULL ,
	page_from LONGTEXT NOT NULL ,
	info LONGTEXT NOT NULL ,
	PRIMARY KEY ( id ) ,
	INDEX ( uid, user_ip, login_result )
	);";
	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site_countries "."
	(
	cid int(4) unsigned NOT NULL AUTO_INCREMENT,
	code char(2) NOT NULL,
	name varchar(150) NOT NULL,
	latitude float NOT NULL,
	longitude float NOT NULL,
	PRIMARY KEY (`cid`)
	);";
	$wpdb->query($sql);

  $table_name = $wpdb->prefix . 'watchman_site_countries';
  $sql = "SELECT count(*) FROM $table_name ";
  $count_rows = $wpdb->get_var($sql);
	//count rows
	if($count_rows !== '249'){
		$sql='INSERT INTO '.$table_name.' (`cid`, `code`, `name`, `latitude`, `longitude`) VALUES ';
		$sql = $sql.wms7_sql_countries();

		$wpdb->query($sql);
	}

	return TRUE;
}