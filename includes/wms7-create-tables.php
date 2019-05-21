<?php
/**
 * Description: Creates 2 tables in the database of the website for the plugin.
 *
 * @category    wms7-create-table.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * For use dbDelta.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * Used for create tables: watchman_site, watchman_site_countries.
 *
 * @return boolean.
 */
function wms7_create_tables() {
	// create tables.
	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site 
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
	$wpdb->query( $sql );// unprepared sql ok;db call ok;no-cache ok.

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site_countries 
	(
	cid int(4) unsigned NOT NULL AUTO_INCREMENT,
	code char(2) NOT NULL,
	name varchar(150) NOT NULL,
	latitude float NOT NULL,
	longitude float NOT NULL,
	PRIMARY KEY (`cid`)
	);";
	$wpdb->query( $sql );// unprepared sql ok;db call ok;no-cache ok.

	$sql        = "SELECT count(*) FROM {$wpdb->prefix}watchman_site_countries ";
	$count_rows = $wpdb->get_var( $sql );// unprepared sql ok;db call ok;no-cache ok.
	// count rows.
	if ( 249 !== (int) $count_rows ) {
		$sql = "INSERT INTO {$wpdb->prefix}watchman_site_countries (`cid`, `code`, `name`, `latitude`, `longitude`) VALUES ";
		$sql = $sql . wms7_sql_countries();

		$wpdb->query( $sql );// unprepared sql ok;db call ok;no-cache ok.
	}

	return true;
}
