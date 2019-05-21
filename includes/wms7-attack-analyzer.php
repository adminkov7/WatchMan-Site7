<?php
/**
 * Description: Analyzes attacks targeting a website.
 *
 * @category    wms7-attack-analyzer.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Block a visitor.
 *
 * @param string $ban_notes Ban notes.
 * @param boolean $ban_user_agent Ban user agent. 
 */
function wms7_block_visitor( $ban_notes, $ban_user_agent ) {
	// baned user ip.
	$arr        = array(
		'ban_start_date' => date( 'Y-m-d' ),
		'ban_end_date'   => date( 'Y-m-d', ( strtotime( 'next week', strtotime( date( 'Y-m-d' ) ) ) ) ),
		'ban_message'    => 'Attack analyzer',
		'ban_notes'      => $ban_notes,
		'ban_login'      => '',
		'ban_user_agent' => $ban_user_agent,
	);
	$black_list = wp_json_encode( $arr );

	return $black_list;
}

/**
 * Analyzes the nature of the site visit.
 *
 * @param string $_log             Login.
 * @param string $page_visit       Page visit.
 * @param string $_http_user_agent User agent.
 * @param string $user_ip          User IP.
 */
function wms7_attack_analyzer( $_log, $page_visit, $_http_user_agent, $user_ip ) {
	$_http_user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
	$black_list       = '';
	$ban_user_agent   = false;

	if ( ! get_option( 'user_agent_attack' ) ) {
		update_option( 'user_agent_attack', $_http_user_agent );
		update_option( 'user_ip_attack', $user_ip );
	} else {
		if ( get_option( 'user_agent_attack' ) === $_http_user_agent &&
			get_option( 'user_ip_attack' ) !== $user_ip ) {
			$ban_user_agent = true;
			// Delete item from options.
			delete_option( 'user_agent_attack' );
			delete_option( 'user_ip_attack' );

			delete_option( 'wms7_login_compromising' );
			delete_option( 'wms7_ip_compromising' );
			delete_option( 'wms7_user_agent_compromising' );
			delete_option( 'wms7_black_list_info' );
			// Clear variables into $_SESSION.
			if ( isset( $_SESSION['wms7_black_list_tbl'] ) ) {
				unset( $_SESSION['wms7_black_list_tbl'] );
			}
			if ( function_exists( 'session_unregister' ) ) {
				session_unregister( 'wms7_black_list_tbl' );
			}
		} else {
			$ban_user_agent = false;
			update_option( 'user_agent_attack', $_http_user_agent );
			update_option( 'user_ip_attack', $user_ip );
		}
	}
	// Если пустой User Agent - баним на 1 неделю.
	if ( '' === trim( $_http_user_agent ) ) {
		// Insert to black_list.
		$black_list = wms7_block_visitor( 'Empty User Agent', $ban_user_agent );
		// Insert to htaccess.
		wms7_rewritecond_insert( '' );
		wms7_ip_insert_to_file( $user_ip );
		delete_option( 'user_agent_attack' );

		return $black_list;
	}
	// Если доступ через xmlrpc - баним на 1 неделю.
	if ( strpos( $page_visit, 'xmlrpc' ) ) {
		// Insert to black_list.
		$black_list = wms7_block_visitor( 'Access via xmlrpc', $ban_user_agent );
		// Insert to htaccess.
		wms7_ip_insert_to_file( $user_ip );
		// Insert user_agent into .htaccess.
		if ( $ban_user_agent && '' !== trim( $_http_user_agent ) ) {
			wms7_rewritecond_insert( $_http_user_agent );
		}
		return $black_list;
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
				// Insert user_agent into .htaccess.
				if ( $ban_user_agent && '' !== trim( $_http_user_agent ) ) {
					wms7_rewritecond_insert( $_http_user_agent );
				}
				return $black_list;
			}
		}
	}
	// Если Сканирование структуры сайта - баним IP.
	if ( strpos( $page_visit, 'wp-content' ) || strpos( $page_visit, 'wp-includes' ) ) {
		// Insert to black_list.
		$black_list = wms7_block_visitor( 'Scan the site structure', $ban_user_agent );
		// Insert to htaccess.
		wms7_ip_insert_to_file( $user_ip );
		// Insert user_agent into .htaccess.
		if ( $ban_user_agent && '' !== trim( $_http_user_agent ) ) {
			wms7_rewritecond_insert( $_http_user_agent );
		}
		return $black_list;
	}
	// Если Сканирование авторов сайта - баним IP.
	if ( strpos( $page_visit, 'author=' ) ) {
		// Insert to black_list.
		$black_list = wms7_block_visitor( 'Scan authors site', $ban_user_agent );
		// Insert to htaccess.
		wms7_ip_insert_to_file( $user_ip );
		// Insert user_agent into .htaccess.
		if ( $ban_user_agent && '' !== trim( $_http_user_agent ) ) {
			wms7_rewritecond_insert( $_http_user_agent );
		}
		return $black_list;
	}

	return $black_list;
}
