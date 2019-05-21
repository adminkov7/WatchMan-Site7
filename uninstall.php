<?php
/**
 * Description: Deletes the tables of the plugin and plugin settings from the database of the website.
 *
 * @category    uninstall.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
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
delete_option( 'wms7_screen_settings' );
delete_option( 'wms7_visitors_per_page' );
delete_option( 'wms7_main_settings' );
delete_option( 'wms7_current_url' );

delete_option( 'wms7_login_compromising' );
delete_option( 'wms7_ip_compromising' );
delete_option( 'wms7_user_agent_compromising' );

delete_option( 'wms7_role_time_country_filter_part1' );
delete_option( 'wms7_role_time_country_filter_part2' );
delete_option( 'wms7_role_time_country_filter_part3' );

delete_option( 'wms7_black_list_info' );
delete_option( 'wms7_robot_visit_info' );
delete_option( 'wms7_history_list_info' );
delete_option( 'wms7_black_list_visitor' );


// Delete table watchman_site.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

// Delete table watchman_site_countries.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_countries";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

// Delete table watchman_site_cross_table.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}watchman_site_cross_table";
$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.
