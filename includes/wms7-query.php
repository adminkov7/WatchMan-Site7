<?php
/**
 * Description: Main function to build console.
 *
 * @category    wms7-query.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

/**
 * Used to create plugin console.
 */
require_once 'wms7-common.php';

set_error_handler( 'console_error_handler' );

$secret = $_SESSION['wms7-console-secret'];
if ( ! $secret ) {
	return;
}
$_signature = filter_input( INPUT_POST, 'signature', FILTER_SANITIZE_STRING );
if ( ! isset( $_signature ) || ! $_signature ) {
	return;
}
$_query = filter_input( INPUT_POST, 'query', FILTER_SANITIZE_STRING );
if ( ! isset( $_query ) || ! $_query ) {
	return;
}
$query = str_replace( '&#39;', "'", $_query );
if ( hash_hmac( 'sha1', $query, $secret ) !== $_signature ) {
	return;
}
$existing_vars = get_defined_vars();

// restore session variables if they exist.
if ( isset( $_SESSION['console_vars'] ) ) {
	extract( eval( 'return ' . $_SESSION['console_vars'] . ';' ) );
}

// append query to current partial query if there is one.
if ( isset( $_SESSION['partial'] ) ) {
	$query = $_SESSION['partial'] . $query;
}

try {
	if ( parse( $query ) === false ) {
		$response = array();
		// start output buffer (to capture prints).
		ob_start();
		$rval               = eval( $_SESSION['code'] );
		$response['output'] = ob_get_contents();
		// quietly discard buffered output.
		ob_end_clean();

		if ( isset( $rval ) ) {
			// do it again, this time for the return value.
			ob_start();
			print_r( $rval );
			$response['rval'] = ob_get_contents();
			ob_end_clean();
		}

		// clear the code buffer.
		$_SESSION['code']    = '';
		$_SESSION['partial'] = '';

		print json_encode( $response );
	} else {
		print json_encode( array( 'output' => 'partial' ) );
	}
} catch ( Exception $exception ) {
	error( $exception->getMessage() );
}

// store variables to session.
$current_vars = get_defined_vars();
$ignore       = array( 'query', 'response', 'rval', 'existing_vars', 'current_vars', '_SESSION' );

save_variables( $existing_vars, $current_vars, $ignore );
