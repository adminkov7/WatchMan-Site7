<?php
/**
 * Description: Create statistics table of visits.
 *
 * @category    wms7-statistic.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Parsing User Agent to extract data: name browser, name platform, name operating system.
 *
 * @param string $user_agent User Agent of visitor.
 * @return array.
 */
function wms7_parse_user_agent( $user_agent ) {
	$browser  = 'unknown';
	$platform = 'unknown';
	$device   = 'unknown';

	if ( ! $user_agent ) {
		return $empty;
	}

	$browser_info = new Wms7_Browser();
	$browser_info->browser( $user_agent );

	$browser = $browser_info->get_browser();

	if ( $browser_info->is_robot() ) {
		return array( 'browser' => $browser );
	} else {
		$platform = $browser_info->get_platform();
		if ( $browser_info->is_mobile() ) {
			$device = 'mobile';
		} else {
			$device = 'desktop';
		}
		return array(
			'browser'  => $browser,
			'platform' => $platform,
			'device'   => $device,
		);
	}
}
/**
 * Used for create graph statistic of visits.
 *
 * @param string $where Login result (0, 1, 2, 3).
 * @return records.
 */
function wms7_create_graph_stat( $where ) {
	global $wpdb;

	$_radio_stat = filter_input( INPUT_POST, 'radio_stat', FILTER_SANITIZE_STRING );
	$_graph_type = filter_input( INPUT_POST, 'graph_type', FILTER_SANITIZE_STRING );

	switch ( $_radio_stat ) {
		case 'visits':
			$where = ( $where ) ? $where : '';
			break;
		case 'unlogged':
			$where = ( $where ) ? $where . ' AND `login_result` = 2' : 'WHERE `login_result` = 2';
			break;
		case 'success':
			$where = ( $where ) ? $where . ' AND `login_result` = 1' : 'WHERE `login_result` = 1';
			break;
		case 'failed':
			$where = ( $where ) ? $where . ' AND `login_result` = 0' : 'WHERE `login_result` = 0';
			break;
		case 'robots':
			$where = ( $where ) ? $where . ' AND `login_result` = 3' : 'WHERE `login_result` = 3';
			break;
		case 'blacklist':
			$none  = "''";
			$where = ( $where ) ? $where . ' AND `black_list` <> ' . $none : 'WHERE `black_list` <> ' . $none;
			break;
	}
	$cache_key = 'wms7_user_agent_' . $_radio_stat;
	$results   = wp_cache_get( $cache_key );
	if ( ! $results ) {
		$results = $wpdb->get_results(
			str_replace(
				"'",
				'',
				str_replace(
					"\'",
					'"',
					$wpdb->prepare(
						"
				SELECT `info` 
				FROM {$wpdb->prefix}watchman_site
				%s
				",
						$where
					)
				)
			),
			'ARRAY_A'
		);// unprepared sql ok;db call ok;cache ok.
		wp_cache_set( $cache_key, $results );
	}
	$data_graph = array();
	foreach ( $results as $part ) {
		$part = array_shift( $part );
		$part = stripcslashes( $part );
		if ( ! strpos( $part, 'null' ) ) {
			$part1  = substr( $part, strpos( $part, 'User Agent' ) + 13, -2 );
			$result = wms7_parse_user_agent( $part1 );
			switch ( $_graph_type ) {
				case 'browser':
					if ( isset( $result['browser'] ) ) {
						$data_graph['browser'][] = $result['browser'];
					}
					break;
				case 'device':
					if ( isset( $result['device'] ) ) {
						$data_graph['device'][] = $result['device'];
					}
					break;
				case 'platform':
					if ( isset( $result['platform'] ) ) {
						$data_graph['platform'][] = $result['platform'];
					}
					break;
			}
		}
	}
	$data_graph = array_count_values( array_shift( $data_graph ) );

	return $data_graph;
}
/**
 * Used for create table statistic of visits.
 *
 * @param string $where Login result (0, 1, 2, 3).
 * @return records.
 */
function wms7_create_table_stat( $where ) {
	global $wpdb;

	$_radio_stat = filter_input( INPUT_POST, 'radio_stat', FILTER_SANITIZE_STRING );

	switch ( $_radio_stat ) {
		case 'visits':
			$where = ( $where ) ? $where : '';
			break;
		case 'unlogged':
			$where = ( $where ) ? $where . ' AND `login_result` = 2' : 'WHERE `login_result` = 2';
			break;
		case 'success':
			$where = ( $where ) ? $where . ' AND `login_result` = 1' : 'WHERE `login_result` = 1';
			break;
		case 'failed':
			$where = ( $where ) ? $where . ' AND `login_result` = 0' : 'WHERE `login_result` = 0';
			break;
		case 'robots':
			$where = ( $where ) ? $where . ' AND `login_result` = 3' : 'WHERE `login_result` = 3';
			break;
		case 'blacklist':
			$where = ( $where ) ? $where . ' AND `black_list` <> ""' : 'WHERE `black_list` <> ""';
			break;
	}

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}watchman_site_cross_table (`date_country` longtext NOT NULL,`tbl_country` longtext NOT NULL,`tbl_result` longtext NOT NULL)";
	$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.
	$sql = "TRUNCATE TABLE {$wpdb->prefix}watchman_site_cross_table";
	$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

	$sql = "INSERT INTO {$wpdb->prefix}watchman_site_cross_table (`date_country`, `tbl_country`, `tbl_result`)
	SELECT DATE_FORMAT(`time_visit`,'%Y %m') as `date_country`, LEFT(`country`,4) as `tbl_country`, COUNT(`user_ip`) as `tbl_result` FROM {$wpdb->prefix}watchman_site $where GROUP BY `date_country`, `tbl_country` ORDER BY `tbl_country`
	";
	$wpdb->query( $sql );// unprepared sql ok;db call ok;cache ok.

	$sql        = "SELECT DISTINCT `tbl_country` FROM {$wpdb->prefix}watchman_site_cross_table";
	$data_array = $wpdb->get_results( $sql, 'ARRAY_A' );// unprepared sql ok;db call ok;cache ok.

	$sql = 'SELECT `date_country`, ';
	foreach ( $data_array as $values ) {
		$sql = $sql . "group_concat(IF(`tbl_country`='" . $values['tbl_country'] . "', tbl_result, NULL)) as `" . $values['tbl_country'] . '`, ';
	}
	$sql     = substr( $sql, 0, -2 );
	$sql     = $sql . " FROM {$wpdb->prefix}watchman_site_cross_table GROUP BY `date_country`";
	$records = $wpdb->get_results( $sql, 'ARRAY_A' );// unprepared sql ok;db call ok;cache ok.

	return ( $records );
}
