/**
 * Description: Used to manage the plug-in on the client side.
 *
 * @category    Wms7_frontend.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

/**
 * Process Control Server Sent Events on the client side (frontend).
 */
function wms7_sse_frontend() {
	var myElement = document.getElementById( 'counter' );

	if (myElement) {
		document.cookie = 'wms7_sse_frontend=on';
		if ( ! ! window.EventSource ) {
			var source = new EventSource( wms7_url + 'includes/wms7-sse-frontend.php' );

			source.addEventListener(
				'message',
				function(e) {
					data = e.data.split( '"' ).join( '' );
					if (wms7_get_cookie( 'wms7_widget_counter' ) !== data) {
							document.cookie = 'wms7_widget_counter=' + data;
							var arr         = data.split( '|' );
					} else {
						var arr = wms7_get_cookie( 'wms7_widget_counter' ).split( '|' );
					}
					// Redraw the widget - counter of visits.
					var counter_month_visits   = document.getElementById( 'counter_month_visits' );
					var counter_month_visitors = document.getElementById( 'counter_month_visitors' );
					var counter_month_robots   = document.getElementById( 'counter_month_robots' );
					var counter_week_visits    = document.getElementById( 'counter_week_visits' );
					var counter_week_visitors  = document.getElementById( 'counter_week_visitors' );
					var counter_week_robots    = document.getElementById( 'counter_week_robots' );
					var counter_today_visits   = document.getElementById( 'counter_today_visits' );
					var counter_today_visitors = document.getElementById( 'counter_today_visitors' );
					var counter_today_robots   = document.getElementById( 'counter_today_robots' );
					if ( 'WatchMan-site7 deactivated' === arr[0] || '' === arr[0] ) {
						console.log( 'WatchMan-site7 deactivated' + ' Server time=' + arr[1] + ' Origin=' + e.origin );
						// Redraw the widget - counter of visits.
						counter_month_visits.innerHTML   = '';
						counter_month_visitors.innerHTML = '';
						counter_month_robots.innerHTML   = '';
						counter_week_visits.innerHTML    = '';
						counter_week_visitors.innerHTML  = '';
						counter_week_robots.innerHTML    = '';
						counter_today_visits.innerHTML   = '';
						counter_today_visitors.innerHTML = '';
						counter_today_robots.innerHTML   = '';
					} else {
						console.log( 'Counter=' + arr[0] + '|' + arr[1] + '|' + arr[3] + '|' + arr[4] + '|' + arr[5] + '|' + arr[5] + '|' + arr[6] + '|' + arr[7] + '|' + arr[8] + '| Server time=' + arr[9] + ' Origin=' + e.origin );
						// Redraw the widget - counter of visits.
						counter_month_visits.innerHTML   = arr[0];
						counter_month_visitors.innerHTML = arr[1];
						counter_month_robots.innerHTML   = arr[2];
						counter_week_visits.innerHTML    = arr[3];
						counter_week_visitors.innerHTML  = arr[4];
						counter_week_robots.innerHTML    = arr[5];
						counter_today_visits.innerHTML   = arr[6];
						counter_today_visitors.innerHTML = arr[7];
						counter_today_robots.innerHTML   = arr[8];
						}
				},
				false
			);

			source.addEventListener(
				'open',
				function(e) {
					//console.log( 'Open connection.' );
				},
				false
			);

			source.addEventListener(
				'error',
				function(e) {
					//console.log( 'Error connection'. );
				},
				false
			);

		} else {
			alert( 'Your browser does not support Server-Sent Events. Please upgrade it.' );
			return;
		}
	} else {
		// stop SSE frontend.
		document.cookie = 'wms7_sse_frontend=off';
	}
}

/**
 * Get content cookie.
 *
 * @param string cookie_name Cookie name.
 * @return string $result.
 */
function wms7_get_cookie(cookie_name) {
	var results = document.cookie.match( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );

	if ( results ) {
		result = decodeURI( results[2] );
		return ( result );
	} else {
		result = null;
		return result;
	}
}

/**
 * Main function onload.
 */
window.onload = function() {
	var myElement = document.getElementById( 'counter' );
	if (myElement) {
		wms7_sse_frontend();
	}
}
