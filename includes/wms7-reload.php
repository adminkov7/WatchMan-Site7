<?php
/**
 * Description: Used to update the session in the console of the plugin.
 *
 * @category    wms7-reload.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

/**
 * Used to create plugin console ???
 */
require_once __DIR__ . '/wms7-common.php';

if ( isset( $_SESSION['console_vars'] ) ) {
	unset( $_SESSION['console_vars'] );
}
if ( isset( $_SESSION['partial'] ) ) {
	unset( $_SESSION['partial'] );
}

echo wp_json_encode( array( 'output' => 'Success reload!' ) );
