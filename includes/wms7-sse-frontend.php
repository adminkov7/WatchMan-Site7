<?php
/**
 * Description: Used to transfer data about site visits to a widget - counter of visits.
 *
 * PHP version 5
 *
 * @category   Wms7-sse-frontend.php
 * @package    WatchMan-Site7
 * @author     Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version    3.1.1
 * @license    GPLv2 or later
 */

/**
 * Used for Serves to send information to the client browser (frontend of site).
 *
 * @param string $data1 Number of visits records.
 */
function wms7_send_frontend( $data1 ) {
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
if ( file_exists( 'frontend.txt' ) ) {
	$tmp = file_get_contents( 'frontend.txt' );
	wms7_send_frontend( $tmp );
} else {
	wms7_send_frontend( 'WatchMan-site7 deactivated' );
}
