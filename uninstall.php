<?php
/**
 * Description: Deletes the tables of the plugin and plugin settings from the database of the website.
 *
 * @category    uninstall.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

// Delete options.
$option_name = 'wms7_screen_settings';
delete_option( $option_name );

$option_name = 'wms7_visitors_per_page';
delete_option( $option_name );

$option_name = 'wms7_main_settings';
delete_option( $option_name );

$option_name = 'wms7_current_url';
delete_option( $option_name );

// Delete table watchman_site.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

// Delete table watchman_site_countries.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_countries";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

// Delete table watchman_site_cross_table.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_cross_table";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.
