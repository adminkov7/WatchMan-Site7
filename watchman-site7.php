<?php
/**
 * Description: Designed for site administrators and is used to control the visits of site.
 *
 * @category    WatchMan-Site7
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

/**
 * Plugin Name:  WatchMan-Site7
 * Plugin URI:   https://wordpress.org/plugins/watchman-site7/
 * Description:  Designed for site administrators and is used to control the visits of site.
 * Author:       Oleg Klenitskiy
 * Author URI:   https://www.adminkov.bcr.by/category/wordpress/
 * Contributors: adminkov
 * Version:      3.0.1
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  watchman-site7
 * Domain Path:  /languages
 * Initiation:   Is dedicated to Inna Voronich.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Analyzes attacks targeting a website.
 */
require_once __DIR__ . '/includes/wms7-attack-analyzer.php';
/**
 * Used to create Google reCAPTCHA.
 */
require_once __DIR__ . '/includes/wms7-recaptcha.php';
/**
 * Used to create tables in the database:prefix_watchman_site and prefix_watchman_site_countries.
 */
require_once __DIR__ . '/includes/wms7-create-tables.php';
/**
 * Contains reference data to populate the table prefix_watchman_site_countries in DB.
 */
require_once __DIR__ . '/settings/wms7-countries.php';
/**
 * Simple mail agent. It is used to manage the mailboxes of the site administrator.
 */
require_once __DIR__ . '/includes/wms7-mail.php';
/**
 * Used to generate and display statistics of site visits.
 */
require_once __DIR__ . '/includes/wms7-statistic.php';
/**
 * Used to obtain data from Who-Is providers about the IP addresses of visitors to the site.
 */
require_once __DIR__ . '/includes/wms7-ip-info.php';
/**
 * Used to work with external files.
 */
require_once __DIR__ . '/includes/wms7-io-interface.php';

if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Used standart class WP_List_Table.
	 */
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Localization of plugin.
 */
function wms7_languages() {
	load_plugin_textdomain( 'wms7', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'wms7_languages' );
/**
 * Register wms7-navigator.js. Used to transmit data about the location of the visitor.
 */
function wms7_load_script_css() {
	$_request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
	if ( $_request_uri ) {
		$pos = strpos( $_request_uri, 'wp-admin' );
		if ( ! $pos ) {
			wp_enqueue_script( 'wms7-navigator', plugins_url( '/js/wms7-navigator.js', __FILE__ ), array(), 'v.3.0.0', false );
		}
		// for use module wms7-navigator.js.
		$wms7_url = plugin_dir_url( __FILE__ );
		?>
		<script>
			var wms7_url = '<?php echo esc_html( $wms7_url ); ?>';
		</script>
		<?php
	}
}
add_action( 'wp_enqueue_scripts', 'wms7_load_script_css' );
/**
 * Register google-maps, wms7-script.js, wms7-style.css. Used to work with the main screen of the plugin.
 */
function wms7_load_css_js() {
	$_request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
	if ( $_request_uri ) {
		$pos = strpos( $_request_uri, 'wp-admin' );
		if ( $pos ) {
			$val = get_option( 'wms7_main_settings' );
			$val = isset( $val['key_api'] ) ? $val['key_api'] : '';

			wp_enqueue_script( 'google-graph', 'https://www.gstatic.com/charts/loader.js', array(), 'v.3.0.0', false );
			wp_enqueue_script( 'google-maps', "//maps.googleapis.com/maps/api/js?key=$val", array(), 'v.3.0.0', false );
			wp_enqueue_script( 'wms7-script', plugins_url( '/js/wms7-script.js', __FILE__ ), array(), 'v.3.0.0', false );
			wp_enqueue_script( 'wms7-sha1', plugins_url( '/js/wms7-sha1.js', __FILE__ ), array(), 'v.3.0.0', false );
			wp_enqueue_script( 'wms7-console', plugins_url( '/js/wms7-console.js', __FILE__ ), array(), 'v.3.0.0', false );
			wp_enqueue_style( 'wms7', plugins_url( '/css/wms7-style.css', __FILE__ ), false, 'v.3.0.0', 'all' );
		}
		// for use module wms7-script.js.
		$wms7_url = plugin_dir_url( __FILE__ );
		// for use module wms7-console.js.
		$plugine_info = get_plugin_data( __DIR__ . '/watchman-site7.php' );
		$wms7_ver     = $plugine_info['Version'];
		$wms7_sec     = get_option( 'wms7-console-secret' );
		if ( ! $wms7_sec ) {
			$wms7_sec = md5( time() * time() );
			update_option( 'wms7-console-secret', $wms7_sec );
		}
		?>
		<script>
			var wms7_url  = '<?php echo esc_html( $wms7_url ); ?>';
			var wms7_ver  = '<?php echo esc_html( $wms7_ver ); ?>';
			var wms7_sec  = '<?php echo esc_html( $wms7_sec ); ?>';
		</script>
		<?php
	}
}
add_action( 'admin_enqueue_scripts', 'wms7_load_css_js' );

if ( ! class_exists( 'wms7_List_Table' ) ) {
	/**
	 * Used to create table for plugin in the admin panel.
	 */
	require_once __DIR__ . '/class-wms7-list-table.php';
}
if ( ! class_exists( 'Wms7_Core' ) ) {
	/**
	 * Used to receive and process requests when visiting site.
	 */
	require_once __DIR__ . '/class-wms7-core.php';

	if ( class_exists( 'Wms7_Core' ) ) {
		$wms7 = new Wms7_core();
		// Activation hook.
		register_activation_hook( __FILE__, 'wms7_create_tables' );
		// Deactivation hook.
		register_deactivation_hook( __FILE__, 'wms7_deactivation' );
		/**
		 * Performed when the plugin is deactivation.
		 */
		function wms7_deactivation() {
			// clean up old cron jobs that no longer exist.
			wp_clear_scheduled_hook( 'wms7_truncate' );
			wp_clear_scheduled_hook( 'wms7_htaccess' );
			// remove role Analyst_wms7.
			remove_role( 'analyst_wms7' );
		}
	}
}
if ( ! class_exists( 'Wms7_Cron' ) ) {
	/**
	 * Used to control the cron events of the site.
	 */
	require_once __DIR__ . '/class-wms7-cron.php';
}
if ( ! class_exists( 'Wms7_Shortcode' ) ) {
	/**
	 * Used to creates a short code [black_list]. List of compromised IP addresses.
	 */
	require_once __DIR__ . '/class-wms7-shortcode.php';

	if ( class_exists( 'Wms7_Shortcode' ) ) {
		Wms7_Shortcode::init();
	}
}
if ( ! class_exists( 'Wms7_Widget' ) ) {
	/**
	 * Description: Used to create a widget - counter site visits.
	 */
	require_once __DIR__ . '/class-wms7-widget.php';
	/**
	 * Register widget - counter site visits.
	 */
	function wms7_load_widget() {
		register_widget( 'Wms7_Widget' );
	}
	add_action( 'widgets_init', 'wms7_load_widget' );
}
if ( ! class_exists( 'Wms7_Browser' ) ) {
	/**
	 * Description: Used to parses user-agent to get the names: browser, platform, device.
	 */
	require_once __DIR__ . '/class-wms7-browser.php';
}
