/**
 * Description: Used to retrieve and transfer geolocation data to the server.
 *
 * @category    Wms7_navigator.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if (navigator.geolocation) {
	/* Geolocation enabled */
	navigator.geolocation.getCurrentPosition( wms7_successCallback, wms7_errorCallback );
} else {
	/* Geolocation not enabled */
	alert( 'GPS not supported' );
}

/**
 * Receiving data about the geolocation of a site visitor.
 *
 * @param object position Position of visitor of site.
 */
function wms7_successCallback(position) {
	var lat     = position.coords.latitude;
	var lon     = position.coords.longitude;
	var acc     = position.coords.accuracy;
	var xmlhttp = wms7_getXmlHttp();

	var pos = wms7_url.indexOf( '//' );
	var url = wms7_url.substr( pos + 2 ) + 'class-wms7-core.php';

	// variable wms7_url from module watchman-site7.php
	// Open an asynchronous connection.
	xmlhttp.open( 'POST', url, true );
	// Sent encoding.
	xmlhttp.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	// Send a POST request.
	xmlhttp.send( 'lat_wifi_js=' + encodeURIComponent( lat ) + '&lon_wifi_js=' + encodeURIComponent( lon ) + '&acc_wifi_js=' + encodeURIComponent( acc ) );
	xmlhttp.onreadystatechange = function() { // Waiting for a response from the server.
		if (xmlhttp.readyState == 4) { // The response is received.
			if (xmlhttp.status == 200) { // The server returned code 200 (which is good).
			}
		}
	};
}

/**
 * Returns the error of visiting the site.
 *
 * @param object error Error visiting the site.
 */
function wms7_errorCallback(error) {

	var xmlhttp = wms7_getXmlHttp();

	var pos = wms7_url.indexOf( '//' );
	var url = wms7_url.substr( pos + 2 );

	// variable wms7_url from module watchman-site7.php
	// Open an asynchronous connection.
	xmlhttp.open( 'POST', url + 'class-wms7-core.php', true );
	// Sent encoding.
	xmlhttp.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	// Send a POST request.
	xmlhttp.send( 'err_code_js=' + encodeURIComponent( error.code ) + '&err_msg_js=' + encodeURIComponent( error.message ) );
	xmlhttp.onreadystatechange = function() { // Waiting for a response from the server.
		if (xmlhttp.readyState == 4) { // The response is received.
			if (xmlhttp.status == 200) { // The server returned code 200 (which is good).
			}
		}
	};
}

/**
 * Creates a cross-browser object XMLHTTP.
 *
 * @param object XMLHTTP.
 */
function wms7_getXmlHttp() {
	var xmlhttp;
	try {
		xmlhttp = new ActiveXObject( 'Msxml2.XMLHTTP' );
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject( 'Microsoft.XMLHTTP' );
		} catch (E) {
			xmlhttp = false;
		}
	}
	if ( ! xmlhttp && typeof XMLHttpRequest != 'undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}
