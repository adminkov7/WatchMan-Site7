<?php
/*
Slave module:   uninstall.php
Description:    Deletes the tables of the plugin and plugin settings from the database of the website
Version:        2.2.3
Author:         Oleg Klenitskiy
Author URI: 		https://www.adminkov.bcr.by/category/wordpress/
*/

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit ();

global $wpdb;

//Delete options
$option_name = 'wms7_screen_settings';
delete_option($option_name);
$option_name = 'wms7_visitors_per_page';
delete_option($option_name);
$option_name = 'wms7_main_settings';
delete_option($option_name);
$option_name = 'wms7_current_page';
delete_option($option_name);
$option_name = 'wms7_current_url';
delete_option($option_name);
$option_name = 'wms7_last_id';
delete_option($option_name);
$option_name = 'wms7_action';
delete_option($option_name);
$option_name = 'wms7_id';
delete_option($option_name);
$option_name = 'widget_wms7_widget';
delete_option($option_name);

//Delete table watchman_site
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site";
$wpdb->query($sql);

//Delete table watchman_site_countries
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_countries";
$wpdb->query($sql);

//Delete table watchman_site_cross_table
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_cross_table";
$wpdb->query($sql);