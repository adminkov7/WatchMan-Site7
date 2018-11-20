<?php
/**
 * Description: Use to create shortcode black_list.
 *
 * @category    Wms7_Shortcode
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description: Use to create shortcode black_list.
 *
 * @category    Class
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.0
 * @license     GPLv2 or later
 */
class Wms7_Shortcode {
	/**
	 * Add new shortcode [black_list].
	 */
	public static function init() {
		add_shortcode( 'black_list', array( __CLASS__, 'wms7_black_list_tbl' ) );
	}
	/**
	 * Creates a visit table of HTML format for counter of visits.
	 *
	 * @return string.
	 */
	public static function wms7_black_list_tbl() {
		global $wpdb;

		$cache_key = 'wms7_black_list_tbl';
		$results   = wp_cache_get( $cache_key );
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
	                SELECT `id`, `user_ip`, `black_list`
	                FROM {$wpdb->prefix}watchman_site
	                WHERE `black_list` <> %s
	                ORDER BY `user_ip` DESC
	                ",
					''
				)
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $results );
		}
		$rows_table = '';
		$item       = 0;
		foreach ( $results as $row ) {
			$item++;
			$row_ip      = $row->user_ip;
			$row         = json_decode( $row->black_list, true );
			$rows_table .= '<tr>
                            <th>' . $item . '</th>
                            <th>' . $row_ip . '</th>
                            <th>' . $row['ban_start_date'] . '</th>
                            <th>' . $row['ban_end_date'] . '</th>
                            <th>' . $row['ban_message'] . '</th>
                            <th>' . $row['ban_notes'] . '</th>
                        </tr>';
		}
		$head_table = '<tr>
                            <th>№</th>
                            <th>' . __( 'IP address', 'wms7' ) . '</th>
                            <th>' . __( 'Ban start', 'wms7' ) . '</th>
                            <th>' . __( 'Ban end', 'wms7' ) . '</th>
                            <th>' . __( 'Description', 'wms7' ) . '</th>
                            <th>' . __( 'Notes', 'wms7' ) . '</th>
                        </tr>';

		return '<table>' . $head_table . $rows_table . '</table>';
	}
}
