<?php
/**
 * Description: Used to send a count of records of visitor, number of unseen emails.
 *
 * PHP version 5
 *
 * @category   Wms7-sse-backend.php
 * @package    WatchMan-Site7
 * @author     Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version    3.1.1
 * @license    GPLv2 or later
 */

/**
 * Used for Serves to send information to the client browser (backend of site).
 *
 * @param string $data1 Number of visits records and mails inbox unseen.
 */
function wms7_send_backend( $data1 ) {

	if ( ! headers_sent() ) {
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'Access-Control-Expose-Headers: *' );
		header( 'Access-Control-Allow-Credentials: true' );

		echo 'data: ' . json_encode( $data1 );
		echo '|' . json_encode( date( 'h:i:s' ) );
		echo "\n\n";
	}
	// check for output_buffering activation.
	if ( 0 !== count( ob_get_status() ) ) {
		ob_flush();
	}
	flush();
}
if ( file_exists( 'backend.txt' ) ) {
	$tmp = file_get_contents( 'backend.txt' );

	wms7_send_backend( $tmp );
}
