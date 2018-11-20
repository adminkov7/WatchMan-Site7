<?php
/**
 * Description: Designed to work with the file system.
 *
 * @category    wms7-io-interface.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Used for save index.php.
 *
 * @param string $file_content File content.
 */
function wms7_save_index_php( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/index.php';
	// save current version file.
	$current      = $wp_filesystem->get_contents( $filename );
	$filename_old = $_document_root . '/index_old.php';
	$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
	// remove the shielding.
	$file_content = stripslashes( $file_content );
	// Write content to a file.
	$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
}
/**
 * Used for save robots.txt.
 *
 * @param string $file_content File content.
 */
function wms7_save_robots_txt( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/robots.txt';
	// save current version file.
	$current      = $wp_filesystem->get_contents( $filename );
	$filename_old = $_document_root . '/robots_old.txt';
	$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
	// remove the shielding.
	$file_content = stripslashes( $file_content );
	// Write content to a file.
	$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
}
/**
 * Used for save htaccess.
 *
 * @param string $file_content File content.
 */
function wms7_save_htaccess( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/.htaccess';
	// save current version file.
	$current      = $wp_filesystem->get_contents( $filename );
	$filename_old = $_document_root . '/.htaccess_old';
	$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
	// remove the shielding.
	$file_content = stripslashes( $file_content );
	// Write content to a file.
	$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
}
/**
 * Used for save wp_config.php.
 *
 * @param string $file_content File content.
 */
function wms7_save_wp_config( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/wp-config.php';
	// save current version file.
	$current      = $wp_filesystem->get_contents( $filename );
	$filename_old = $_document_root . '/wp-config_old.php';
	$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
	// remove the shielding.
	$file_content = stripslashes( $file_content );
	// Write content to a file.
	$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
}
/**
 * Used for ip delete from htaccess.
 *
 * @param string $user_ip User ip.
 */
function wms7_ip_delete_from_file( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/.htaccess';
	$file     = $wp_filesystem->get_contents_array( $filename );

	if ( ! $file ) {
		return;
	}
	$size = count( $file );
	for ( $i = 0; $i < $size; $i++ ) {
		$pos = stristr( $file[ $i ], 'Deny from ' . $user_ip );

		if ( $pos ) {
			unset( $file[ $i ] );
		}
	}
	$wp_filesystem->put_contents( $filename, implode( '', $file ) );
}
/**
 * Used for ip insert into htaccess.
 *
 * @param string $user_ip User ip.
 */
function wms7_ip_insert_to_file( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/.htaccess';

	// Open the file to get existing content.
	$current = $wp_filesystem->get_contents( $filename );

	// search string in file.
	if ( strpos( $current, $user_ip ) ) {
		return;
	}
	// Add a new line to the file.
	$current .= "\n" . 'Deny from ' . $user_ip;

	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, $current, FS_CHMOD_FILE );
}
/**
 * Used for rewritecond insert into htaccess.
 *
 * @param string $robot_banned Robot name.
 */
function wms7_rewritecond_insert( $robot_banned ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/.htaccess';

	// Open the file to get existing content.
	$current = $wp_filesystem->get_contents( $filename );

	// insert Deny from env = wms7_bad_bot.
	if ( ! strpos( $current, 'wms7_bad_bot' ) ) {
		$current = 'Deny from env=wms7_bad_bot' . "\n" . $current;
		// Write contents back to file.
		$wp_filesystem->put_contents( $filename, $current, FS_CHMOD_FILE );
	}
	// search string in file.
	if ( strpos( $current, $robot_banned ) ) {
		return;
	}
	// Add a new line to the file.
	$current = 'SetEnvIfNoCase User-Agent "'
		. $robot_banned
		. '" wms7_bad_bot'
		. "\n"
		. $current;
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, $current, FS_CHMOD_FILE );
}
/**
 * Used for rewritecond delete in htaccess.
 */
function wms7_rewritecond_delete() {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	// file name.
	$filename = $_document_root . '/.htaccess';

	// Open the file to get existing content.
	$current = $wp_filesystem->get_contents_array( $filename );

	foreach ( $current as $key => $value ) {
		if ( strpos( $value, 'wms7_bad_bot' ) ) {
			unset( $current[ $key ] );
		}
	}
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, implode( '', $current ), FS_CHMOD_FILE );
}
/**
 * Used for resolve fields for export.
 *
 * @return string.
 */
function wms7_flds_csv() {
	$val = get_option( 'wms7_main_settings' );

	$flds['id']         = isset( $val['id'] ) ? $val['id'] : '';
	$flds['uid']        = isset( $val['uid'] ) ? $val['uid'] : '';
	$flds['user_login'] = isset( $val['user_login'] ) ? $val['user_login'] : '';
	$flds['user_role']  = isset( $val['user_role'] ) ? $val['user_role'] : '';
	$flds['time_visit'] = isset( $val['time_visit'] ) ? $val['time_visit'] : '';
	$flds['user_ip']    = isset( $val['user_ip'] ) ? $val['user_ip'] : '';
	$flds['black_list'] = isset( $val['black_list'] ) ? $val['black_list'] : '';
	$flds['page_visit'] = isset( $val['page_visit'] ) ? $val['page_visit'] : '';
	$flds['page_from']  = isset( $val['page_from'] ) ? $val['page_from'] : '';
	$flds['info']       = isset( $val['info'] ) ? $val['info'] : '';

	foreach ( $flds as $key => $value ) {
		if ( '' === $value ) {
			unset( $flds[ $key ] );
		}
	}
	if ( 0 === count( $flds ) ) {
		return false;
	}
	$str = '';
	foreach ( $flds as $key => $value ) {
		$str = $str . $key . ',';
	}
		$str = substr( $str, 0, -1 );
	return $str;
}
/**
 * Used for export data to external file.
 */
function wms7_output_csv() {
	WP_Filesystem();
	global $wp_filesystem;
	global $wpdb;

	$_id = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( ! $_id ) {
		return;
	}
	$flds = wms7_flds_csv();
	if ( ! $flds ) {
		return;
	}
	if ( is_array( $_id ) ) {
		$_id = implode( ',', $_id );
	}
	if ( ! empty( $_id ) ) {
		$result = $wpdb->get_results(
			str_replace(
				"'",
				'',
				str_replace(
					"\'",
					'"',
					$wpdb->prepare(
						"
		        SELECT %s
		        FROM {$wpdb->prefix}watchman_site
		        WHERE `id` IN(%s)
		        ",
						$flds,
						$_id
					)
				)
			),
			'ARRAY_A'
		);// unprepared sql ok;db call ok;no-cache ok.
	}
	$filename = 'wms7_export_' . date( 'Y-m-d' ) . '.csv';

	// reset the PHP output buffer.
	if ( ob_get_level() ) {
		ob_end_clean();
	}
	$content = '';
	foreach ( $result[0] as $key => $value ) {
		$content = $content . $key . ';';
	}
	$content = $content . Chr( 10 );
	foreach ( $result as $values ) {
		foreach ( $values as $key => $value ) {
			$content = $content . $value . ';';
		}
		$content = $content . Chr( 10 );
	}
	$wp_filesystem->put_contents( $filename, $content, FS_CHMOD_FILE );
	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment;filename=' . $filename );

	echo esc_html( $wp_filesystem->get_contents( $filename ) );
	exit;

}
