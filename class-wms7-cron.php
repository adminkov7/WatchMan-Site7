<?php
/**
 * Description:  Used to control the cron events of the site.
 *
 * @category    Wms7_Cron
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description:  Used to control the cron events of the site.
 *
 * @category    Class
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */
class Wms7_Cron {
	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Used to control the cron events of the site.
		 *
		 * @var integer
		 */
		WP_Filesystem();

		global $wp_filesystem;

		$this->dirname_wp      = $wp_filesystem->abspath() . 'wp-admin';
		$this->dirname_wp_add  = $wp_filesystem->abspath() . 'wp-includes';
		$this->dirname_themes  = $wp_filesystem->wp_themes_dir();
		$this->dirname_plugins = $wp_filesystem->wp_plugins_dir();

		$this->file_name    = [];
		$this->orphan_count = 0;
		$this->plugin_count = 0;
		$this->themes_count = 0;
		$this->wp_count     = 0;
	}
	/**
	 * Delete item cron event.
	 */
	private function wms7_delete_item_cron() {
		$_cron = filter_input_array( INPUT_POST );
		foreach ( $_cron as $key => $value ) {
			$pos = strpos( $value, 'cron' );
			if ( 0 === $pos ) {
				$timestamp = wp_next_scheduled( $key );
				wp_unschedule_event( $timestamp, $key );
			}
		}
	}
	/**
	 * Create cron table.
	 */
	public function wms7_create_cron_table() {

		$this->wms7_delete_item_cron();

		$arr_cron      = $this->wms7_cron_view();
		$table_row     = explode( ';', $arr_cron );
		$_cron_refresh = filter_input( INPUT_POST, 'cron_refresh', FILTER_SANITIZE_STRING );
		$new_table_row = array();
		foreach ( $table_row as $item ) {
			$val = explode( '|', $item );
			if ( '' !== $val[0] ) {
				if ( $_cron_refresh ) {
					$source_task     = $this->wms7_search_into_directory( $val[0] );
					$new_table_row[] = $item . '|' . $source_task[0] . '|' . $source_task[1] . '|' . $source_task[2];
				} else {
					$source_task[0]  = esc_html( 'Source task', 'wms7' );
					$source_task[1]  = esc_html( 'press Refresh', 'wms7' );
					$new_table_row[] = $item . '|' . $source_task[0] . '|' . $source_task[1] . '|';
				}
			}
		}
		return $new_table_row;
	}
	/**
	 * Collecting all the cron events on the site.
	 */
	private function wms7_cron_view() {
		$val3    = _get_cron_array();
		$wp_cron = '';
		foreach ( $val3 as $timestamp => $cron ) {
			foreach ( $cron as $key1 => $value1 ) {
				foreach ( $value1 as $key2 => $value2 ) {
					$wp_cron = $wp_cron . $key1 . '|' . $value2 ['schedule'] . '|' . get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), 'M j, Y -> H:i:s' ) . ';';
				}
			}
		}
		return $wp_cron;
	}
	/**
	 * Scanning the current directory.
	 *
	 * @param string $dirname Dyrectory name.
	 * @param string $context Context.
	 */
	private function wms7_scan_dir( $dirname, $context ) {
		global $wp_filesystem;

		$dirlist = $wp_filesystem->dirlist( $dirname );
		if ( $dirlist ) {
			foreach ( $dirlist as $filename => $dirattr ) {
				$path = str_replace( '//', '/', $dirname . '/' . $dirattr['name'] );
				if ( 'f' === $dirattr['type'] ) {
					if ( '.php' === substr( $dirattr['name'], -4 ) ) {
						// If the file *.php processed content.
						$search  = strpos( $wp_filesystem->get_contents( $path ), $context );
						if ( $search ) {
							$this->file_name[0] = $dirname;
							$this->file_name[1] = $dirattr['name'];

							return $this->file_name;
						}
					}
				} elseif ( 'd' === $dirattr['type'] ) {
						// If it is a directory, recursively called function wms7_scan_dir.
						$ret = $this->wms7_scan_dir( $path, $context );
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}
		return false;
	}
	/**
	 * Search into directory.
	 *
	 * @param string $context Context.
	 */
	private function wms7_search_into_directory( $context ) {
		$step1 = $this->wms7_scan_dir( $this->dirname_wp, $context );
		if ( $step1 ) {
			$this->wp_count++;
			$step1[2] = 'step1';
			return $step1;
		}
		$step2 = $this->wms7_scan_dir( $this->dirname_wp_add, $context );
		if ( $step2 ) {
			$this->wp_count++;
			$step2[2] = 'step2';
			return $step2;
		}

		$step3 = $this->wms7_scan_dir( $this->dirname_themes, $context );
		if ( $step3 ) {
			$this->themes_count++;
			$step3[2] = 'step3';
			return $step3;
		}
		$step4 = $this->wms7_scan_dir( $this->dirname_plugins, $context );
		if ( $step4 ) {
			$this->plugin_count++;
			$step4[2] = 'step4';
			return $step4;
		}
		$this->orphan_count++;
		$step5[0] = esc_html( 'Source not found', 'wms7' );
		$step5[1] = esc_html( 'Source not found', 'wms7' );
		$step5[2] = 'step5';

		return $step5;
	}
}
