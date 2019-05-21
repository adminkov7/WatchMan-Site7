<?php
/**
 * Description: Designed to work with the file system.
 *
 * @category    wms7-io-interface.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
if ( ! defined( 'FS_CHMOD_FILE' ) ) {
	define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
}

/**
 * Used for current data storage: total number of visits to different categories of visitors and different time.
 *
 * @param string $counter_list New count rows.
 */
function wms7_save_frontend( $counter_list ) {
	WP_Filesystem();
	global $wp_filesystem;

	$filename = __DIR__ . '/frontend.txt';
	$wp_filesystem->put_contents( $filename, $counter_list, FS_CHMOD_FILE );
}
/**
 * Used for current data storage: total number of visits and unread emails.
 *
 * @param string $new_count_rows New count rows.
 * @param string $mail_unseen New count mail unseen.
 */
function wms7_save_backend( $new_count_rows, $mail_unseen ) {
	WP_Filesystem();
	global $wp_filesystem;

	$filename = __DIR__ . '/backend.txt';
	$wp_filesystem->put_contents( $filename, $new_count_rows . '|' . $mail_unseen, FS_CHMOD_FILE );
}
/**
 * Used for save index.php.
 *
 * @param string $file_content File content.
 */
function wms7_save_index_php( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( 'manage_options' ) ) {
		// file name.
		$filename = ABSPATH . 'index.php';
		// save current version file.
		$current      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . 'index_old.php';
		$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Used for save robots.txt.
 *
 * @param string $file_content File content.
 */
function wms7_save_robots_txt( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( 'manage_options' ) ) {
		// file name.
		$filename = ABSPATH . 'robots.txt';
		// save current version file.
		$current      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . 'robots_old.txt';
		$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Used for save htaccess.
 *
 * @param string $file_content File content.
 */
function wms7_save_htaccess( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( 'manage_options' ) ) {
		// file name.
		$filename = ABSPATH . '.htaccess';
		// save current version file.
		$current      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . '.htaccess_old';
		$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Used for save wp_config.php.
 *
 * @param string $file_content File content.
 */
function wms7_save_wp_config( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( 'manage_options' ) ) {
		// file name.
		$filename = ABSPATH . 'wp-config.php';
		// save current version file.
		$current      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . 'wp-config_old.php';
		$wp_filesystem->put_contents( $filename_old, $current, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Used for ip delete from htaccess.
 *
 * @param string $user_ip User ip.
 */
function wms7_ip_delete_from_file( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . '.htaccess';

	// Open the file to get existing content.
	$current = $wp_filesystem->get_contents_array( $filename );

	foreach ( $current as $key => $value ) {
		if ( ! empty( $value ) ) {
			if ( strpos( $value, $user_ip ) ) {
				unset( $current[ $key ] );
			}
		} else {
			unset( $current[ $key ] );
		}
	}
	$current = array_filter( $current );
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, implode( '', $current ), FS_CHMOD_FILE );
}
/**
 * Used for ip insert into htaccess.
 *
 * @param string $user_ip User ip.
 */
function wms7_ip_insert_to_file( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . '.htaccess';

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
 * Used for rewritecond user_agent insert into htaccess.
 *
 * @param string $user_agent Robot name.
 */
function wms7_rewritecond_insert( $user_agent ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . '.htaccess';

	// Open the file to get existing content.
	$current = $wp_filesystem->get_contents( $filename );

	// insert Deny from env = wms7_bad_bot.
	if ( ! strpos( $current, 'wms7_bad_bot' ) ) {
		$current = 'Deny from env=wms7_bad_bot' . "\n" . $current;
		// Write contents back to file.
		$wp_filesystem->put_contents( $filename, $current, FS_CHMOD_FILE );
	}
	$user_agent = str_replace(array( '(', ')' ), '.', $user_agent);
	// search string in file.
	if ( '' !== trim( $user_agent ) && strpos( $current, $user_agent ) ) {
		return;
	}
	if ( '' === trim( $user_agent ) ) {
		$user_agent = '^$';
	}
	// Add a new line to the file.
	$current = 'SetEnvIfNoCase User-Agent "'
		. $user_agent
		. '" wms7_bad_bot'
		. "\n"
		. $current;
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, $current, FS_CHMOD_FILE );
}
/**
 * Used for rewritecond user_agent delete in htaccess.
 *
 * @param string $user_agent Robot name.
 */
function wms7_rewritecond_delete( $user_agent ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . '.htaccess';

	// Open the file to get existing content.
	$current    = $wp_filesystem->get_contents_array( $filename );
	if ( ! empty( $user_agent ) ) {
		$user_agent = str_replace(array( '(', ')' ), '.', $user_agent);
	} else {
		$user_agent = '^$';
	}

	foreach ( $current as $key => $value ) {
		if ( ! empty( $value ) ) {
			if ( strpos( $value, $user_agent ) ) {
				unset( $current[ $key ] );
			}
		} else {
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

	$_id     = filter_input( INPUT_POST, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$_action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

	if ( 'export' === $_action ) {

		$flds = wms7_flds_csv();
		if ( ! $flds || ! $_id ) {
			exit();
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
			);// prepared sql ok;db call ok;no-cache ok.
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

		if ( ! headers_sent() ) {
			header( 'Content-type: application/x-msdownload', true, 200 );
			header( 'Content-Disposition: attachment;filename=' . $filename );

			echo esc_html( $wp_filesystem->get_contents( $filename ) );
			exit;
		}
	}
}
