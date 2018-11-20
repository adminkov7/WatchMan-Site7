/**
 * Description: Used to create a console in the modal window.
 *
 * @category    Wms7_console.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

var queries      = [];
var counter      = 0;
var historyIndex = 0;

/**
 * Main function fo create console.
 */
function wms7_console() {
	// create shell div.
	var shell = document.createElement( "div" );
	shell.id  = 'shell';
	document.getElementById( 'wms7_console' ).appendChild( shell );

	about();
	doPrompt();

	addEventListener( "keyup", wms7_keyup );
	addEventListener( "keydown", wms7_keydown );

	// listen for clicks on the shell.
	shell.addEventListener(
		"click",
		function() {
			input_id  = 'input_console' + String( counter );
			var input = document.getElementById( input_id );
			input.focus();
		}
	);
}
/**
 * Listen for key presses (up, down).
 *
 * @param object $e Name of e-event.
 */
function wms7_keyup(e) {
	var key      = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
	var input_id = 'input_console' + String( counter );
	var input    = document.getElementById( input_id );

	switch (key) {
		case 38: // up.
			var lastQuery = queries[historyIndex];
			if (typeof lastQuery != "undefined") {
				historyIndex--;
				input.value = lastQuery;
				input.focus();
			}
			// no negative history allowed.
			if (historyIndex < 0) {
				historyIndex = 0;
			}
		break;
		case 40: // down.
			var nextQuery = queries[historyIndex + 1];
			if (typeof nextQuery != "undefined") {
				historyIndex++;
				input.value = nextQuery;
				input.focus();
			} else {
				// put it at the end.
				historyIndex = queries.length - 1;
				input.value  = "";
			}
		break;
	}
}
/**
 * Listen for key presses (up, down, tab).
 *
 * @param object $e Name of e-event.
 */
function wms7_keydown(e) {
	var key      = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
	var input_id = 'input_console' + String( counter );
	var input    = document.getElementById( input_id );

	switch (key) {
		case 9: // tab.
			e.preventDefault();
			// save in history and handle accordingly.
			historyIndex          = queries.length;
			queries[historyIndex] = input.value;

			if (input.value.length !== 0) {
				signature = hex_hmac_sha1( wms7_sec, input.value );
				request   = 'partial=' + encodeURIComponent( input.value ) + '&signature=' + encodeURIComponent( signature );
				wms7_post(
					'wms7-complete',
					request,
					function(data){
						if ( '[]' !== data.responseText && 200 === data.status ) {
							responseText = JSON.parse( data.responseText );
							// print 2-column listing of array values.
							buffer_to_longest( responseText );
							responseText_length = responseText.length;
							while (responseText_length > 0) {
								var line = responseText.splice( 0,2 );
								print( line.join( " " ) );
								responseText_length = responseText.length;
							}
						} else if ( data.status > 200 ) {
							print( 'Error(' + data.status + '): ' + data.statusText );
						} else if ('[]' === data.responseText ) {
							print( 'Error: not found...' );
						}
						doPrompt();
					}
				);
			}
			break;
		case 38: // up arrow.
		case 40: // down arrow.
			e.preventDefault();
			break;
	}
}
/**
 * Listen for key presses (up, down, tab).
 *
 * @param object $e Name of e-event.
 */
function doPrompt(prompt) {
	counter++;

	// default prompt to > unless passed in as argument.
	prompt = (prompt) ? prompt : '>';

	var shell = document.getElementById( 'shell' );

	// append prompt to shell.
	var row       = document.createElement( "div" );
	row.className = 'row';
	row.id        = counter;
	row.innerHTML = "<span>" + prompt + "</span>";

	var form = document.createElement( "form" );

	var input       = document.createElement( "input" );
	input.className = 'current';
	input.id        = 'input_console' + counter;
	input.type      = 'text';

	form.appendChild( input );
	row.appendChild( form );
	shell.appendChild( row );

	// set focus input.
	input.focus();
	/**
	 * Listen for key presses (submit).
	 *
	 * @param object $e Name of e-event.
	 */
	form.onsubmit = function(e) {

		e.preventDefault();

		// save in history and handle accordingly.
		historyIndex          = queries.length;
		queries[historyIndex] = input.value;

		switch (input.value) {
			case 'clear': case 'c':
					clear();
				break;
			case 'help': case '?':
					print(
						"\n" +
						"You cannot correctly enter the function name ?\n" +
						"  Enter the first part of the function name and press the tab key.\n" +
						"Use ↑ or ↓ to display the history of previously entered commands.\n" +
						"\n" +
						"Special Commands:\n" +
						"  clear  (c) = clears the console output.\n" +
						"  help   (?) = prints this help text.\n" +
						"  reload (r) = flushes all variables and partial statements.\n" +
						"\n" +
						"How quickly, to look at the environment of execution PHP ?\n" +
						"  Just enter the phpinfo(); command in this console. " +
						"<img src='https://s.w.org/images/core/emoji/11/svg/1f609.svg' width='15'>"
					);
					doPrompt();
				break;
			case 'reload': case 'r':
					request = 'query=' + encodeURIComponent( input.value );
					wms7_post(
						'wms7-reload',
						request,
						function(data){
							responseText = JSON.parse( data.responseText );
							print( JSON.stringify( responseText.output ).replace( /"/g, '' ) );
							doPrompt();
						}
					);
				break;
			default:
				if (input.value.length !== 0) {
					signature = hex_hmac_sha1( wms7_sec, input.value );
					request   = 'query=' + encodeURIComponent( input.value ) + '&signature=' + encodeURIComponent( signature );
					wms7_post(
						'wms7-query',
						request,
						function(data){
							if ( data.responseText && 200 === data.status ) {
								responseText = JSON.parse( data.responseText );
								if ( responseText.rval && ! responseText.output) {
									print( responseText.rval );
								} else if ( responseText.output && ! responseText.rval) {
									print( responseText.output );
								} else if ( responseText.output && responseText.rval ) {
									print( responseText.output );
								} else if ( responseText.error ) {
									print( 'Error: ' + JSON.stringify( responseText.error ) );
								}
							} else if ( data.status > 200 ) {
								print( 'Error(' + data.status + '): ' + data.statusText );
							} else {
								print( 'Error: not found...' );
							}
							doPrompt();
						}
					);
				}
		}
	}
}
/**
 * Create POST request.
 *
 * @param string $page Name of php file.
 * @param object $data Data send to server.
 * @param object $callback  Data received from server.
 */
function wms7_post(page, data, callback) {
	var xmlhttp     = getXmlHttp();
	var request_url = wms7_url + 'includes/' + page + ".php";
	// Open an asynchronous connection.
	xmlhttp.open( 'POST', request_url );
	// Sent encoding.
	xmlhttp.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

	xmlhttp.onreadystatechange = function() { // Waiting for a response from the server.
		if (xmlhttp.readyState == 4) { // The response is received.
			if (xmlhttp.status >= 200) { // The server returned code 200 (which is good).
				callback( xmlhttp );
			}
		}
	};
	// Send a POST request.
	xmlhttp.send( data );
}
/**
 * Creates a cross-browser object XMLHTTP.
 *
 * @return object XMLHTTP.
 */
function getXmlHttp() {
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
/**
 * Clear of screen console.
 */
function clear() {
	child   = Array.prototype.filter.call(
		header.parentNode.children,
		function(child){
			child.innerHTML = '';
			return child !== header;
		}
	);
	counter = 0;
	about();
	doPrompt();
}
/**
 * Creates about info for console.
 */
function about() {
	header           = document.createElement( "div" );
	header.id        = 'header';
	header.innerHTML = '℗ Console build: october 2018, by Oleg Klenitsky.';
	shell.appendChild( header );
}
/**
 * Printing data to screen of console.
 *
 * @param string $string Data for print to console.
 */
function print(string) {
	// Using textContent() escapes HTML to output visible tags.
	var result   = document.createElement( "div" );
	var input_id = 'input_console' + String( counter );
	var input    = document.getElementById( input_id );

	if (input.value == 'phpinfo();') {

		// replace substr in style for php 7.0.
		str1   = 'td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}';
		str2_1 = '.wms7_td_e {border: 1px solid black;white-space:pre-line;background-color:#ccccff;font-weight:bold;color:#000000;word-wrap:break-word;width:25%;}';
		str2_2 = '.wms7_td_v {border: 1px solid black;white-space:pre-line;background-color:#cccccc;color:#000000;word-wrap:break-word;width:75%;}';
		str2   = str2_1 + '\n' + str2_2;
		string = string.replace( str1, str2	);

		str1   = 'a:link {color: #009; text-decoration: none; background-color: #fff;}';
		string = string.replace( str1, '' );

		str1   = 'table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}';
		str2   = 'table {border-collapse: collapse; border: 0; width: 600px; box-shadow: 1px 2px 3px #ccc;}';
		string = string.replace( str1, str2 );

		// replace substr in body.
		string = string.replace( /<table>/g, '<table style="width:600px;">' );

		// replace substr in style for php 5.2.
		str1   = 'td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}';
		str2_1 = '.wms7_td_e {border: 1px solid black;white-space:pre-line;background-color:#ccccff;font-weight:bold;color:#000000;word-wrap:break-word;width:25%;}';
		str2_2 = '.wms7_td_v {border: 1px solid black;white-space:pre-line;background-color:#cccccc;color:#000000;word-wrap:break-word;width:75%;}';
		str2   = str2_1 + '\n' + str2_2;
		string = string.replace( str1, str2 );

		str1   = 'a:link {color: #000099; text-decoration: none; background-color: #ffffff;}';
		string = string.replace( str1, '' );

		// replace substr in body.
		string = string.replace( /<td>/g, '<td style="color:#000000;>"' );
		string = string.replace( /<td class="e">/g, '<td class="wms7_td_e">' );
		string = string.replace( /<td class="v">/g, '<td class="wms7_td_v">' );
		string = string.replace( /<th>/g, '<th style="border: 1px solid #000000;">' );
		string = string.replace( /<p>/g, '<p style="width:600px;white-space:pre-line;margin:0;color:#000000;">' );
		string = string.replace( /<h1>/g, '<h1 style="color:white;">' );
		string = string.replace( /<h2>/g, '<h2 style="color:white;">' );

		result.innerHTML = '<br>' + string;
	} else {
		result.innerHTML = '<pre>' + string + '</pre>';
	}
	result.className = 'result';
	shell.appendChild( result );
}
/**
 * Formats data received from the server (list of functions).
 *
 * @param array $array Data (list of functions).
 *
 * @return array.
 */
function buffer_to_longest( array ) {
	var longest  = array[0].length;
	array_length = array.length;
	for (var i = 1; i < array_length; i++) {
		if (array[i].length > longest) {
			longest = array[i].length;
		}
	};
	array_length = array.length;
	for (var i = 0; i < array_length; i++) {
		array[i] = pad( array[i], longest );
	};
	return array;
}
/**
 * Helper function for buffer_to_longest().
 *
 * @param string $string Data (list of functions).
 * @param integer $length Length (length of string).
 *
 * @return string.
 */
function pad( string, length ) {
	string_length = string.length;
	while (string_length < length) {
		string        = string + " ";
		string_length = string.length;
	}
	return string;
}
