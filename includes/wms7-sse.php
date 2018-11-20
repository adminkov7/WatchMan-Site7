<?php
/**
 * Description: Used to send a count of records of visitor, number of unseen emails.
 *
 * PHP version 5
 *
 * @category   Wms7-sse.php
 * @package    WatchMan-Site7
 * @author     Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version    3.0.1
 * @license    GPLv2 or later
 */

/**
 * We specify that we need at least WP.
 */
define( 'SHORTINIT', true );
// loadable environment WordPress.
$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
require_once $_document_root . '/wp-load.php';

/**
 * Used for mail inbox connection.
 * Function with the same name is in the file wms7-mail.php plugin.
 *
 * @return object.
 */
function wms7_mail_inbox_connection() {
	$val        = get_option( 'wms7_main_settings' );
	$select_box = $val['mail_select'];
	$box        = $val[ $select_box ];

	$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}INBOX';

	$username = $box['mail_box_name'];
	$password = $box['mail_box_pwd'];

	if ( $box && '' !== $username && '' !== $password ) {
		try {
			$imap = imap_open( $server, $username, $password );
		} catch ( Exception $e ) {
				$imap =
				$e->getMessage() .
				'<br>server: ' . $server .
				'<br>username: ' . $username .
				'<br>password: ' . $password;
		}
		return $imap;
	}
}
/**
 * Used for mail inbox unseen. Function with the same name is in the file wms7-mail.php plugin.
 *
 * @return number.
 */
function wms7_mail_unseen() {
	$imap = wms7_mail_inbox_connection();
	$i    = 0;
	if ( $imap ) {
		$mc = imap_check( $imap );
		// Get an overview of all the letters in the box.
		$result = imap_fetch_overview( $imap, "1:{$mc->Nmsgs}", 0 );
		foreach ( $result as $overview ) {
			if ( 0 === $overview->seen ) {
				$i++;
			}
		}
		imap_close( $imap );
	}
	return $i;
}
/**
 * Used for to obtain the number of visits records.
 *
 * @return number.
 */
function wms7_count_rows() {
	global $wpdb;

	$cache_key = 'all_total';
	$results   = wp_cache_get( $cache_key );
	if ( ! $results ) {
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"
                SELECT count(%s) FROM {$wpdb->prefix}watchman_site
                ",
				'*'
			)
		);// db call ok; cache ok.
	}
	return $results;
}
/**
 * Used for Serves to send information to the client browser (admin-panel of site).
 *
 * @param string $data1 Number of visits records.
 * @param string $data2 Number of mails inbox unseen.
 */
function wms7_send_message( $data1, $data2 ) {
	if ( ! headers_sent() ) {
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
	}
	echo 'data: ' . intval( $data1 );
	echo '|' . intval( $data2 );
	echo "\n\n";
	// check for output_buffering activation.
	if ( 0 !== count( ob_get_status() ) ) {
		ob_flush();
	}
	flush();
}

while ( true ) {
	$new_count_rows = wms7_count_rows();
	$mail_unseen    = wms7_mail_unseen();
	wms7_send_message( $new_count_rows, $mail_unseen );
	sleep( 10 );
}
