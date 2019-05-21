/**
 * Description: Used to manage the plug-in on the client side.
 *
 * @category    Wms7_backend.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

/**
 * Process Control Server Sent Events on the client side (backend).
 */
function wms7_sse_backend() {
	var myElement = document.getElementById( 'sse' );

	if (myElement.checked) {
		var snd = new Audio;
		snd.src = wms7_url + 'sound/sse_on.wav';
		snd.play();
		// start SSE backend.
		document.cookie = 'wms7_sse_backend=on';
		if ( ! ! window.EventSource ) {
			var source = new EventSource( wms7_url + 'includes/wms7-sse-backend.php', {withCredentials: true} );
			console.group( 'EventSource Created' );
			console.dir( source );
			console.groupEnd();
			source.addEventListener(
				'message',
				function(e) {
					var arr = e.data.split( '|' );
					console.log( 'All visits=' + arr[0] + ' New letters=' + arr[1] + ' Server time=' + arr[2] + ' Origin=' + e.origin );
					if (wms7_get_cookie( 'wms7_records_count' ) !== arr[0] || wms7_get_cookie( 'wms7_unseen_count' ) !== arr[1]) {
						document.cookie = 'wms7_records_count=' + arr[0];
						document.cookie = 'wms7_unseen_count=' + arr[1];
						wms7_beep();
						location.replace( window.location.href );
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
		var snd = new Audio;
		snd.src = wms7_url + 'sound/sse_off.wav';
		snd.play();
		// stop SSE backend.
		document.cookie = 'wms7_sse_backend=off';
		location.replace( window.location.href );
	}
	wms7_ctrl_btn_href();
}

/**
 * Blocks the controls (buttons) on the plug-in screen if SSE mode is enabled.
 */
function wms7_ctrl_btn_href() {
	var sse = document.getElementById( 'sse' );

	if (sse.checked) {
		// disable all controls.
		if (document.getElementById( 'doaction' )) {
			document.getElementById( 'doaction' ).disabled = true;
		}
		document.getElementById( 'doaction1' ).disabled = true;
		if (document.getElementById( 'doaction' )) {
			document.getElementById( 'doaction2' ).disabled = true;
		}
		document.getElementById( 'doaction3' ).disabled   = true;
		document.getElementById( 'btn_bottom1' ).disabled = true;
		document.getElementById( 'btn_bottom2' ).disabled = true;
		document.getElementById( 'btn_bottom3' ).disabled = true;
		document.getElementById( 'btn_bottom4' ).disabled = true;
		document.getElementById( 'btn_bottom5' ).disabled = true;
		document.getElementById( 'btn_bottom6' ).disabled = true;
		document.getElementById( 'btn_bottom7' ).disabled = true;
		document.getElementById( 'btn_bottom8' ).disabled = true;

		// create a new style sheet.
		var styleTag = document.createElement( "style" );
		var a        = document.getElementsByTagName( "a" )[0];
		a.appendChild( styleTag );

		var sheet = styleTag.sheet ? styleTag.sheet : styleTag.styleSheet;

		// add a new rule to the style sheet.
		if (sheet.insertRule) {
			sheet.insertRule( "a {pointer-events: none;}", 0 );
		} else {
			sheet.addRule( "a", "pointer-events: none;", 0 );
		}
	} else {
		// enable all controls.
		if (document.getElementById( 'doaction' )) {
			document.getElementById( 'doaction' ).disabled = false;
		}
		document.getElementById( 'doaction1' ).disabled = false;
		if (document.getElementById( 'doaction' )) {
			document.getElementById( 'doaction2' ).disabled = false;
		}
		document.getElementById( 'doaction3' ).disabled   = false;
		document.getElementById( 'btn_bottom1' ).disabled = false;
		document.getElementById( 'btn_bottom2' ).disabled = false;
		document.getElementById( 'btn_bottom3' ).disabled = false;
		document.getElementById( 'btn_bottom4' ).disabled = false;
		document.getElementById( 'btn_bottom5' ).disabled = false;
		document.getElementById( 'btn_bottom6' ).disabled = false;
		document.getElementById( 'btn_bottom7' ).disabled = false;
		document.getElementById( 'btn_bottom8' ).disabled = false;
	}
}

/**
 * Get vars from URL address.
 *
 * @return array.
 */
function wms7_getUrlVars() {
	var vars  = {};
	var url   = decodeURIComponent( window.location.href );
	var parts = url.replace(
		/[?&]+([^=&]+)=([^&]*)/gi,
		function(m,key,value) {
			vars[key] = value;
		}
	);
	return vars;
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
 * Set cookie for button #doaction1.
 */
function wms7_cookie_doaction1() {
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	odj1    = document.getElementById( 'filter_role' );
	odj2    = document.getElementById( 'filter_time' );
	odj3    = document.getElementById( 'filter_country' );

	fld1 = (odj1.value) ? '&filter_role=' + odj1.value : '';
	fld2 = (odj2.value) ? '&filter_time=' + odj2.value : '';
	fld3 = (odj3.value) ? '&filter_country=' + odj3.value : '';

	str             = fld1 + ',' + fld2 + ',' + fld3;
	document.cookie = 'wms7_doaction1=' + str;
	document.cookie = 'wms7_doaction3=';
}

/**
 * Set cookie for button #doaction3.
 */
function wms7_cookie_doaction3() {
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	odj1 = document.getElementById( 'filter_login_ip' );

	str             = (odj1.value) ? '&filter=' + odj1.value : '';
	document.cookie = 'wms7_doaction1=';
	document.cookie = 'wms7_doaction3=' + str;
}

/**
 * Main function onload.
 */
window.onload = function() {
	var arr    = [];
	var page   = wms7_getUrlVars()['page'];
	var result = (wms7_getUrlVars()['result']);
	var paged  = (wms7_getUrlVars()['paged']) ? '&paged=' + wms7_getUrlVars()['paged'] : '';
	var action = (wms7_getUrlVars()['action']) ? '&action=' + wms7_getUrlVars()['action'] : '';

	if (page == 'wms7_settings' || page == 'wms7_visitors') {
		var elements = document.getElementsByTagName('select');
			for (i in elements) {
				elements[i].onclick = function() {
					var snd = new Audio;
					snd.src = wms7_url + 'sound/select.wav';
					snd.play();
				};
			}
		var elements = document.getElementsByName('footer');
			for (i in elements) {
				elements[i].onclick = function() {
					var snd = new Audio;
					snd.src = wms7_url + 'sound/button.wav';
					snd.play();
				};
			}
	}
	if (page == 'wms7_settings') {
		wms7_check_pwd( 'pwd_box0' );
		wms7_check_pwd( 'pwd_box1' );
		wms7_check_pwd( 'pwd_box2' );
		wms7_check_pwd( 'pwd_box3' );
		wms7_check_pwd( 'pwd_box4' );

		wms7_show();
	}
	if (page == 'wms7_visitors') {
		wms7_link_focus( page, result );
		wms7_stat_focus();
		wms7_mail_focus();
		wms7_file_attach();
		if ( ! wms7_get_cookie( 'wms7_sse_backend' )) {
			document.cookie = 'wms7_sse_backend=off';
		} else {
			if (wms7_get_cookie( 'wms7_sse_backend' ) == 'on') {
				var myElement     = document.getElementById( 'sse' );
				myElement.checked = true;
				// start SSE.
				wms7_sse_backend();
			}
		}
		if ( 'delete' == action || 'clear' == action ) {
			filter_role     = '';
			filter_time     = '';
			filter_country  = '';
			filter_login_ip = '';
			switch (result) {
				case '5': {result = '&result=5'; break;}
				case '2': {result = '&result=2'; break;}
				case '1': {result = '&result=1'; break;}
				case '0': {result = '&result=0'; break;}
				case '3': {result = '&result=3'; break;}
				case '4': {result = '&result=4'; break;}
				default : {result = '&result=5';}
			}
			if (wms7_get_cookie( 'wms7_doaction1' )) {
				arr            = wms7_get_cookie( 'wms7_doaction1' ).split( ',' );
				filter_role    = (arr[0] != '') ? arr[0] : '';
				filter_time    = (arr[1] != '') ? arr[1] : '';
				filter_country = (arr[2] != '') ? arr[2] : '';
			}
			if (wms7_get_cookie( 'wms7_doaction3' )) {
				filter_login_ip = (wms7_get_cookie( 'wms7_doaction3' ) != '') ? wms7_get_cookie( 'wms7_doaction3' ) : '';
			}
			var page    = 'page=' + wms7_getUrlVars()['page'];
			var paged   = (wms7_getUrlVars()['paged']) ? '&paged=' + wms7_getUrlVars()['paged'] : '';
			var orderby = (wms7_getUrlVars()['orderby']) ? '&orderby=' + wms7_getUrlVars()['orderby'] : '';
			var order   = (wms7_getUrlVars()['order']) ? '&order=' + wms7_getUrlVars()['order'] : '';

			var stateParameters = { page: page, paged: paged, result: result, filter_role: filter_role, filter_time: filter_time, filter_country: filter_country, orderby: orderby, order: order };
			var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
			url                 = url + '?' + page + paged + result + filter_role + filter_time + filter_country + orderby + order;

			history.pushState( stateParameters, "WatchMan-Site7", url );
			window.location.replace( url );
		}
	}
}

/**
 * Visualization of the process of loading a modal window.
 */
function wms7_popup_loader() {
	var loader              = document.getElementById( 'win-loader' );
	loader.style.visibility = 'visible';
}

/**
 * Reload the plug-in page after closing the modal window.
 */
function wms7_popup_close() {
	var page           = 'page=' + wms7_getUrlVars()['page'];
	var paged          = (wms7_getUrlVars()['paged']) ? '&paged=' + wms7_getUrlVars()['paged'] : '';
	var result         = (wms7_getUrlVars()['result']) ? '&result=' + wms7_getUrlVars()['result'] : '&result=5';
	var filter_role    = (wms7_getUrlVars()['filter_role']) ? '&filter_role=' + wms7_getUrlVars()['filter_role'] : '';
	var filter_time    = (wms7_getUrlVars()['filter_time']) ? '&filter_time=' + wms7_getUrlVars()['filter_time'] : '';
	var filter_country = (wms7_getUrlVars()['filter_country']) ? '&filter_country=' + wms7_getUrlVars()['filter_country'] : '';
	var orderby        = (wms7_getUrlVars()['orderby']) ? '&orderby=' + wms7_getUrlVars()['orderby'] : '';
	var order          = (wms7_getUrlVars()['order']) ? '&order=' + wms7_getUrlVars()['order'] : '';

	var stateParameters = { page: page, paged: paged, result: result, filter_role: filter_role, filter_time: filter_time, filter_country: filter_country, orderby: orderby, order: order };
	var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
	url                 = url + '?' + page + paged + result + filter_role + filter_time + filter_country + orderby + order;

	history.pushState( stateParameters, "WatchMan-Site7", url );
	window.location.replace( url );
}

/**
 * Setting radio buttons in the modal Statistics window.
 */
function wms7_stat_focus() {
	var btn;
	var myElement;
	btn = wms7_get_cookie( 'wms7_stat_btn' );
	if (document.getElementsByName( 'radio_stat' )) {
		switch (btn) {
			case 'visits' : {myElement = document.getElementById( 'visits' ); break;}
			case 'unlogged' : {myElement = document.getElementById( 'unlogged' ); break;}
			case 'success' : {myElement = document.getElementById( 'success' ); break;}
			case 'failed' : {myElement = document.getElementById( 'failed' ); break;}
			case 'robots' : {myElement = document.getElementById( 'robots' ); break;}
			case 'blacklist' : {myElement = document.getElementById( 'blacklist' ); break;}
		}
		if (myElement) {
			myElement.checked = true;}
	}
}

/**
 * Setting radio buttons in the modal Mailbox window.
 */
function wms7_mail_focus() {
	var mailbox = wms7_getUrlVars()['mailbox'];

	if (document.getElementsByName( 'radio_mail' )) {
		switch (mailbox) {
			case 'folder1' : {myElement = document.getElementById( 'folder1' ); break;}
			case 'folder2' : {myElement = document.getElementById( 'folder2' ); break;}
			case 'folder3' : {myElement = document.getElementById( 'folder3' ); break;}
			case 'folder4' : {myElement = document.getElementById( 'folder4' ); break;}
			default : {
				var folders = document.getElementsByName( 'radio_mail' );
				fldsLength  = folders.length;
				for (var index = 0; index < fldsLength; index++) {
					if (folders[index].value.toLowerCase() == 'inbox') {
						var myElement = document.getElementById( folders[index].id );
						break;
					}
				}
				}
		}
		if (myElement) {
			myElement.checked  = true;
			myElement.selected = true;
		}
	}
}

/**
 * Setting radio buttons visits in the main window of plugin.
 *
 * @param string page Plugin page name.
 * @param string result Item of visit.
 */
function wms7_link_focus(page, result) {
	var myElement;
	if (page == 'wms7_visitors') {
		switch (result) {
			case '0' : {myElement = document.getElementById( 'radio-4' ); break;}
			case '1' : {myElement = document.getElementById( 'radio-3' ); break;}
			case '2' : {myElement = document.getElementById( 'radio-2' ); break;}
			case '3' : {myElement = document.getElementById( 'radio-5' ); break;}
			case '4' : {myElement = document.getElementById( 'radio-6' ); break;}
			case '5' : {myElement = document.getElementById( 'radio-1' ); break;}
			default : {myElement = document.getElementById( 'radio-1' );}
		}
		myElement.checked = true;
	}
}

/**
 * Refresh the main plugin screen.
 *
 * @param string visit Item of visit.
 */
function wms7_visit(visit) {
	var url = window.location.href;
	var snd = new Audio;
		snd.src = wms7_url + 'sound/button.wav';
		snd.play();
	switch (visit) {
		case 'radio-1': {location.replace( url + '&result=5' ); break;}
		case 'radio-2': {location.replace( url + '&result=2' ); break;}
		case 'radio-3': {location.replace( url + '&result=1' ); break;}
		case 'radio-4': {location.replace( url + '&result=0' ); break;}
		case 'radio-5': {location.replace( url + '&result=3' ); break;}
		case 'radio-6': {location.replace( url + '&result=4' ); break;}
		default : {location.replace( url + '&result=5' );}
	}
}

/**
 * Updating the modal Mailbox window when saving the URL address of the main plugin pages.
 *
 * @param string folder Item of folder mailbox.
 * @param string mailbox_nonce Mailbox nonce.
 * @param string mail_new_nonce Mail new nonce.
 */
function wms7_mailbox_select(folder, mailbox_nonce, mail_new_nonce) {
	switch (folder) {
		case 'folder1': {document.cookie = 'wms7_mail_btn=folder1';break;}
		case 'folder2': {document.cookie = 'wms7_mail_btn=folder2';break;}
		case 'folder3': {document.cookie = 'wms7_mail_btn=folder3';break;}
		case 'folder4': {document.cookie = 'wms7_mail_btn=folder4';break;}
	}
	var page    = 'page=' + wms7_getUrlVars()['page'];
	var paged   = (wms7_getUrlVars()['paged']) ? '&paged=' + wms7_getUrlVars()['paged'] : '';
	var mailbox = (wms7_get_cookie( 'wms7_mail_btn' ) ) ? '&mailbox=' + wms7_get_cookie( 'wms7_mail_btn' ) : '';
	var result  = (wms7_getUrlVars()['result']) ? '&result=' + wms7_getUrlVars()['result'] : '&result=5';

	if ( mailbox_nonce ) {
		mailbox_nonce = 'mailbox_nonce=' + mailbox_nonce;

		var stateParameters = { page: page, result: result, paged: paged, mailbox: mailbox,  mailbox_nonce: mailbox_nonce };
		var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
		url                 = url + '?' + page + '&' + result + '&' + paged + '&' + mailbox + '&' + mailbox_nonce;
	}
	if ( mail_new_nonce ) {
		mail_new_nonce = 'mail_new_nonce=' + mail_new_nonce;

		var stateParameters = { page: page, result: result, paged: paged, mailbox: mailbox,  mail_new_nonce: mail_new_nonce };
		var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
		url                 = url + '?' + page + '&' + result + '&' + paged + '&' + mailbox + '&' + mail_new_nonce;
	}
	history.pushState( stateParameters, "WatchMan-Site7", url );
	window.location.replace( url );
}

/**
 * Output of data on visitor's geolocation in the modal window Map.
 *
 * @param string Login Item of visitor.
 * @param string Lat Latitude.
 * @param string Lon Longitude.
 * @param string Acc Accuracy.
 * @param string Err Error.
 * @param string Msg Message.
 */
function wms7_initMap(Login, Lat, Lon, Acc, Err, Msg) {

	// Create a map object and specify the DOM element for display.
	var map = new google.maps.Map(
		document.getElementById( 'map' ),
		{
			scrollwheel: true,
			zoom: 10,
			draggable: true,
			zoomControlOptions: {
				position: google.maps.ControlPosition.RIGHT_TOP
			}
		}
	);

	map.setCenter( new google.maps.LatLng( Lat, Lon ) );

	var geocoder   = new google.maps.Geocoder();
	var infowindow = new google.maps.InfoWindow();
	// Create a marker object.
	var marker   = new google.maps.Marker(
		{
			position: new google.maps.LatLng( Lat, Lon ),
			map: map,
			title:''
		}
	);
	marker.title = marker.title + Login;
	google.maps.event.addListener(
		marker,
		'click',
		function() {
			infowindow.open( map,marker );
		}
	);
	document.getElementById( 'lat' ).textContent = 'Latitude: ' + Lat + '°';
	document.getElementById( 'lon' ).textContent = 'Longitude: ' + Lon + '°';
	document.getElementById( 'acc' ).textContent = 'Accuracy: ' + Acc + ' m';
	document.getElementById( 'err' ).textContent = 'Error: (' + Err + ') ' + Msg;

	document.getElementById( 'get_location' ).addEventListener(
		'click',
		wms7_geocodeLatLng( geocoder, map, infowindow, Lat, Lon ,marker )
	);
}

/**
 * The subordinate function for wms7_initMap().
 *
 * @param string geocoder Object google.maps.
 * @param string map Object google.maps.
 * @param string infowindow Object google.maps.
 * @param string myLat Latitude.
 * @param string myLon Longitude.
 * @param string marker Marker on the map.
 */
function wms7_geocodeLatLng(geocoder, map, infowindow, myLat, myLon, marker) {

	var latlng = {lat: myLat, lng: myLon};
	geocoder.geocode(
		{'location': latlng},
		function(results, status) {
			if (status === 'OK') {
				if (results[0]) {
					infowindow.setContent( results[0].formatted_address );
					infowindow.open( map, marker );
				} else {
					infowindow.setContent( results[1].formatted_address );
					infowindow.open( map, marker );
				}
			} else {
				document.getElementById( 'err' ).textContent =
				document.getElementById( 'err' ).textContent + ' (Geocoder failed due to: ' + status + ')';
			}
		}
	);
}

/**
 * Saving the current value of the radio button Statistics to cookies.
 */
function wms7_stat_btn(){
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	myElement 		= document.getElementsByName( 'radio_stat' );
	myElementLength = myElement.length;
	for (i = 0; i < myElementLength; i++) {
		if (myElement[i].checked) {
			break;
		}
	}
	btn             = myElement[i].value;
	document.cookie = 'wms7_stat_btn=' + btn;
}

/**
 * Reset the modal Mailbox window after pressing the Quit button.
 */
function wms7_quit_btn(){
	var page    = 'page=' + wms7_getUrlVars()['page'];
	var paged   = 'paged=' + wms7_getUrlVars()['paged'];
	var mailbox = 'mailbox=' + wms7_get_cookie( 'wms7_mail_btn' );
	var result  = wms7_getUrlVars()['result'];
	if (result) {
		result = 'result=' + wms7_getUrlVars()['result'];
	} else {
		result = 'result=5';
	}
	var stateParameters = { page: page, result: result, paged: paged, mailbox: mailbox };
	var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
	url                 = url + '?' + page + '&' + result + '&' + paged + '&' + mailbox;

	history.pushState( stateParameters, "WatchMan-Site7", url );
}

/**
 * Check access to the current mailbox.
 *
 * @param string id Item of mailbox.
 */
function wms7_check_boxes(id){
	var page     = 'page=' + wms7_getUrlVars()['page'];
	var paged    = 'paged=' + wms7_getUrlVars()['paged'];
	var checkbox = 'checkbox=' + id;
	var result   = wms7_getUrlVars()['result'];

	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	if (result) {
		result = 'result=' + wms7_getUrlVars()['result'];
	} else {
		result = 'result=5';
	}
	var stateParameters = { page: page, result: result, paged: paged, checkbox: checkbox };
	var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
	url                 = url + '?' + page + '&' + result + '&' + paged + '&' + checkbox;

	history.pushState( stateParameters, "WatchMan-Site7", url );
	window.location.replace( url );
}

/**
 * Check access to the current smtp server.
 *
 * @param string id Item of smtp server.
 */
function wms7_check_smtp(id){
	var page   = 'page=' + wms7_getUrlVars()['page'];
	var paged  = 'paged=' + wms7_getUrlVars()['paged'];
	var smtp   = 'smtp=' + id;
	var result = wms7_getUrlVars()['result'];

	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	if (result) {
		result = 'result=' + wms7_getUrlVars()['result'];
	} else {
		result = 'result=5';
	}
	var stateParameters = { page: page, result: result, paged: paged, smtp: smtp };
	var url             = window.location.href.slice( 0,window.location.href.indexOf( '?' ) );
	url                 = url + '?' + page + '&' + result + '&' + paged + '&' + smtp;

	history.pushState( stateParameters, "WatchMan-Site7", url );
	window.location.replace( url );
}

/**
 * Hide password value.
 *
 * @param string id Item of mailbox.
 */
function wms7_check_pwd(id){
	myElementChk = document.getElementById( id );
	if ( myElementChk ) {
		switch (id) {
			case 'pwd_box0': {myElementPwd = document.getElementById( 'mail_box_pwd_box0' ); break;}
			case 'pwd_box1': {myElementPwd = document.getElementById( 'mail_box_pwd_box1' ); break;}
			case 'pwd_box2': {myElementPwd = document.getElementById( 'mail_box_pwd_box2' ); break;}
			case 'pwd_box3': {myElementPwd = document.getElementById( 'mail_box_pwd_box3' ); break;}
			case 'pwd_box4': {myElementPwd = document.getElementById( 'mail_box_pwd_box4' ); break;}
		}
		if (myElementChk.checked) {
			myElementPwd.setAttribute( 'type', 'password' );
		} else {
			myElementPwd.setAttribute( 'type', 'text' );
		}
	}
}

/**
 * Saving access settings to the folders of the current mailbox.
 *
 * @param string box Item of mailbox.
 * @param string id_tbl Item folder of mailbox.
 * @param string id_textarea Item of textarea.
 * @param string id_textarea_alt Item of textarea_alt.
 */
function wms7_mail_folders(box,id_tbl,id_textarea,id_textarea_alt){
	var myElement1 = document.getElementById( id_tbl );
	var myElement2 = document.getElementById( id_textarea );
	var myElement3 = document.getElementById( id_textarea_alt );
	if (! myElement1) {
		alert('Please before press Check and select items');
		return;
	}
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	myElement1.style.display    = 'none';
	myElement2.style.visibility = 'visible';
	myElement3.style.visibility = 'hidden';

	mylist      = '';
	mylist_alt  = '';
	mytr        = myElement1.getElementsByTagName( 'tr' );
	mychk       = document.getElementsByName( box + '_chk' );
	mychkLength = mychk.length;

	for (var i = 0; i < mychkLength; i++) {
		element = mychk[i];
		if (element.checked) {
			id         = element.id;
			mytrLength = mytr.length;
			for (var j = 0; j < mytrLength; j++) {
				var row = mytr[j];
				if (row.cells[0].innerHTML.indexOf( id ) > '0') {
					mylist     = mylist + row.cells[1].innerHTML.trim() + ';\n';
					mylist_alt = mylist_alt + row.cells[1].getAttribute( 'data' ).trim() + ';\n';
				}
			}
		}
	}
	myElement2.innerHTML = mylist;
	myElement3.innerHTML = mylist_alt;
}

/**
 * Settings for the sound notification object when visit the site.
 */
function wms7_show(){
	frequency                                   = document.getElementById( "fIn" ).value;
	document.getElementById( "fOut" ).innerHTML = frequency + ' Hz';

	switch (document.getElementById( "tIn" ).value * 1) {
		case 0: type = 'sine'; break;
		case 1: type = 'square'; break;
		case 2: type = 'sawtooth'; break;
		case 3: type = 'triangle'; break;
	}
	document.getElementById( "tOut" ).innerHTML = type;

	volume                                      = document.getElementById( "vIn" ).value / 100;
	document.getElementById( "vOut" ).innerHTML = volume;

	duration                                    = document.getElementById( "dIn" ).value;
	document.getElementById( "dOut" ).innerHTML = duration + ' ms';
}

/**
 * Start of sound notification when visiting the site.
 */
function wms7_beep() {
	var AudioContext = window.AudioContext || window.webkitAudioContext;
	var audioCtx     = new AudioContext();

	if ( ! wms7_get_cookie( 'wms7_sound_volume' ) &&
	! wms7_get_cookie( 'wms7_sound_frequency' ) &&
	! wms7_get_cookie( 'wms7_sound_type' ) &&
	! wms7_get_cookie( 'wms7_sound_duration' )) {

		var volume      = '0.1';
		var frequency   = '260';
		var type        = 'square';
		var duration    = '400';
		document.cookie = 'wms7_sound_volume=' + volume;
		document.cookie = 'wms7_sound_frequency=' + frequency;
		document.cookie = 'wms7_sound_type=' + type;
		document.cookie = 'wms7_sound_duration=' + duration;
	} else {
		var volume    = wms7_get_cookie( 'wms7_sound_volume' );
		var frequency = wms7_get_cookie( 'wms7_sound_frequency' );
		var type      = wms7_get_cookie( 'wms7_sound_type' );
		var duration  = wms7_get_cookie( 'wms7_sound_duration' );
	}
	var oscillator = audioCtx.createOscillator();
	var gainNode   = audioCtx.createGain();

	oscillator.connect( gainNode );
	gainNode.connect( audioCtx.destination );

	gainNode.gain.value        = volume;
	oscillator.frequency.value = frequency;
	oscillator.type            = type;

	oscillator.start();

	setTimeout(
		function(){
			oscillator.stop();
		},
		duration
	);
}

/**
 * Saving audio alert settings to cookie.
 */
function wms7_setup_sound(){
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();

	document.cookie = 'wms7_sound_volume=' + volume;
	document.cookie = 'wms7_sound_frequency=' + frequency;
	document.cookie = 'wms7_sound_type=' + type;
	document.cookie = 'wms7_sound_duration=' + duration;
}

var dataChart = [];
/**
 * Data preparation for chart statistic.
 *
 * @param array data Used to pass data into a chart.
 */
function wms7_graph_statistic(data){
	var arr = [];
	var i   = 0;

	data = data.replace( /&quot;/g, '"' );
	data = JSON.parse( data );

	for ( key in data ) {
		arr[i] = [key,data[key]];
		i++;
	}
	dataChart = arr;
	// Load the Visualization API and the piechart package.
	google.charts.load( 'current', {'packages':['corechart', 'controls']} );
	// Set a callback to run when the Google Visualization API is loaded.
	google.charts.setOnLoadCallback( wms7_drawChart );
}

/**
 * Callback that creates and populates a data table, instantiates the pie chart,
 * passes in the data and draws it.
 */
function wms7_drawChart() {
	var sel = document.getElementById( "graph_type" );
		sel = sel.options[sel.selectedIndex].value;

	// Create the data table.
	var data = new google.visualization.DataTable();
		data.addColumn( 'string', 'Items' );
		data.addColumn( 'number', 'Count' );
		data.addRows( dataChart );

	// Create a dashboard.
	var dashboard = new google.visualization.Dashboard(
		document.getElementById( 'dashboard_chart' )
	);

	// Create a range slider, passing some options.
	var chartRangeSlider = new google.visualization.ControlWrapper(
		{
			'controlType': 'NumberRangeFilter',
			'containerId': 'filter_chart',
			'options': {
				'filterColumnLabel': 'Count',
				'ui': {
					'format': {'pattern':'#'}
				}
			}
		}
	);

	// Create a pie chart, passing some options.
	var pieChart = new google.visualization.ChartWrapper(
		{
			'chartType': 'PieChart',
			'containerId': 'piechart',
			'options': {
				'pieSliceText': 'value',
				'title': sel + ' visitors to the site:',
				'legend': 'left'
			}
		}
	);

	// Establish dependencies, declaring that 'filter' drives 'pieChart',
	// so that the pie chart will only display entries that are let through
	// given the chosen slider range.
	dashboard.bind( chartRangeSlider, pieChart );

	// Draw the dashboard.
	dashboard.draw( data );
}

/**
 * Controls the type of files to attach.
 */
function wms7_file_attach() {
	var file = document.getElementById( 'file_attach' );
	if (file) {
		file.onchange = function(e){
			var ext = this.value.match( /\.([^\.]+)$/ )[1];
			switch (ext) {
				case 'zip':
					break;
				default:
					alert( 'Allowed to attach only *.zip files.' );
					this.value = '';
			}
		};
	}
}

/**
 * Sound of click buttons on page General settings.
 */
function wms7_settings_sound() {
	var snd = new Audio;
	snd.src = wms7_url + 'sound/button.wav';
	snd.play();
}