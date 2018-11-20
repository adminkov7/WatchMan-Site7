<?php
/**
 * Description: Analyzes attacks targeting a website.
 *
 * @category    wms7-attack-analyzer.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Block a visitor.
 *
 * @param string $ban_notes Ban notes.
 */
function wms7_block_visitor( $ban_notes ) {
	// baned user ip.
	$arr        = array(
		'ban_start_date' => date( 'Y-m-d' ),
		'ban_end_date'   => date( 'Y-m-d', ( strtotime( 'next month', strtotime( date( 'Y-m-d' ) ) ) ) ),
		'ban_message'    => 'Attack analyzer',
		'ban_notes'      => $ban_notes,
	);
	$black_list = wp_json_encode( $arr );

	return $black_list;
}

/**
 * Analyzes the nature of the site visit.
 *
 * @param string $login_result     Login result.
 * @param string $_log             Login.
 * @param string $user_role        User role.
 * @param string $page_visit       Page visit.
 * @param string $_http_user_agent User agent.
 * @param string $user_ip          User IP.
 */
function wms7_attack_analyzer( $login_result, $_log, $user_role, $page_visit, $_http_user_agent, $user_ip ) {
	$black_list = '';
	// Первый раз посещения запоминаем User Agent - даем шанс на испраление.
	// Второй раз посещения с этой целью (перебор авторов) - баним на 1 месяц.
	if ( strpos( $page_visit, 'author=' ) ) {
		if ( ! get_option( 'wms7_warning_user_agent' ) ) {
			update_option( 'wms7_warning_user_agent', $_http_user_agent );
		} else {
			if ( get_option( 'wms7_warning_user_agent' ) === $_http_user_agent ) {
				// Insert to black_list.
				$black_list = wms7_block_visitor( 'Scan the site structure' );
				// Insert to htaccess.
				wms7_ip_insert_to_file( $user_ip );
				// Waiting for a new attack.
				delete_option( 'wms7_warning_user_agent' );

				return $black_list;
			} else {
				update_option( 'wms7_warning_user_agent', $_http_user_agent );
			}
		}
	}
	// Первый раз посещения запоминаем User Agent - даем шанс на испраление.
	// Второй раз посещения с этой целью (доступ через xmlrpc) - баним на 1 месяц.
	if ( strpos( $page_visit, 'xmlrpc' ) ) {
		if ( ! get_option( 'wms7_warning_user_agent' ) ) {
			update_option( 'wms7_warning_user_agent', $_http_user_agent );
		} else {
			if ( get_option( 'wms7_warning_user_agent' ) === $_http_user_agent ) {
				// Insert to black_list.
				$black_list = wms7_block_visitor( 'Access via xmlrpc' );
				// Insert to htaccess.
				wms7_ip_insert_to_file( $user_ip );
				// Waiting for a new attack.
				delete_option( 'wms7_warning_user_agent' );

				return $black_list;
			} else {
				update_option( 'wms7_warning_user_agent', $_http_user_agent );
			}
		}
	}
	// Если Brute force - баним IP.
	if ( $_log ) {
		$user_info = get_user_by( 'login', $_log );
		if ( $user_info ) {
			$user_role = array_shift( $user_info->roles );
			if ( ( 'administrator' === $user_role ) || ( 'author' === $user_role ) ) {
				// Insert to black_list.
				$black_list = wms7_block_visitor( 'Brute force: ' . $user_role );
				// Insert to htaccess.
				wms7_ip_insert_to_file( $user_ip );
				// Waiting for a new attack.
				delete_option( 'wms7_warning_user_agent' );

				return $black_list;
			}
		}
	}

	return $black_list;
}
