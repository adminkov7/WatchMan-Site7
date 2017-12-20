var alert;
var console;
var EventSource;
var wms7_url;
function wms7_sse() {
  var myElement = document.getElementById('sse');

  if (myElement.checked) {
      document.cookie = 'wms7_sse=on';
      if (!! window.EventSource ) {
          var source = new EventSource ( wms7_url ); 

          source.addEventListener('message', function(e) {
              console.log(e.data);
              if (get_cookie( 'wms7_count' ) !== e.data) {
                  document.cookie = 'wms7_count=' + e.data;
                  location.replace(window.location.href);
              }
          }, false);

          source.addEventListener('open', function(e) {
              console.log('Connection was opened.');
          }, false);

          source.addEventListener('error', function(e) {
              console.log('Error - connection was lost.');
          }, false);

        }else{
          alert('Your browser does not support Server-Sent Events. Please upgrade it.');
          return;
      }
    }else{
      //stop SSE
      document.cookie = 'wms7_sse=off';
      location.replace(window.location.href);
  }
}

function wms7_getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

function get_cookie ( cookie_name ){
  var results = document.cookie.match ( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );
 
  if ( results )
    return ( decodeURI ( results[2] ) );
  else
    return null;
}

window.onload = function () {
  var page = wms7_getUrlVars()['page'];
  var result = wms7_getUrlVars()['result'];

  wms7_link_focus(page, result);
  if (!get_cookie( 'wms7_sse' )){
    document.cookie = 'wms7_sse=off';
    }else{
    if (get_cookie( 'wms7_sse' ) == 'on'){
      if (page =='wms7_visitors') {
        var myElement = document.getElementById('sse');
        myElement.checked = true;
        //start SSE
        wms7_sse();
      }
    }
  }
}

function wms7_popup_close() {
  var page = 'page='+wms7_getUrlVars()['page'];
  var paged = 'paged='+wms7_getUrlVars()['paged'];
  var result = wms7_getUrlVars()['result'];
  if (result) {
    result = 'result='+wms7_getUrlVars()['result']
  }else{
    result='result=5';
  };
  var url = window.location.href.slice(0,window.location.href.indexOf('\?'));

  url = url + '?'+ page + '&' + result + '&' + paged;
  location.replace(url);
 }

function wms7_link_focus(page, result) {
  var myElement;
  if (page =='wms7_visitors') {
    switch (result) {
      case '0' : {myElement = document.getElementById('radio-4'); break;}
      case '1' : {myElement = document.getElementById('radio-3'); break;}
      case '2' : {myElement = document.getElementById('radio-2'); break;}
      case '3' : {myElement = document.getElementById('radio-5'); break;}
      case '4' : {myElement = document.getElementById('radio-6'); break;}
      case '5' : {myElement = document.getElementById('radio-1'); break;}
      case undefined : {myElement = document.getElementById('radio-1');}
    }
      myElement.checked = true;
  }
}

function visit(visit) {
  var url = window.location.href;
  switch (visit) {
    case 'radio-1': {location.replace(url+'&result=5'); break;}
    case 'radio-2': {location.replace(url+'&result=2'); break;}
    case 'radio-3': {location.replace(url+'&result=1'); break;}
    case 'radio-4': {location.replace(url+'&result=0'); break;}
    case 'radio-5': {location.replace(url+'&result=3'); break;}    
    case 'radio-6': {location.replace(url+'&result=4');}
  }
}

function wms7_initMap(Login, Lat, Lon, Acc, Err, Msg) {

  // Create a map object and specify the DOM element for display.
  var map = new google.maps.Map(document.getElementById('map'), {
  	scrollwheel: true,
  	zoom: 10,
  	draggable: true,
  	zoomControlOptions: {
  		position: google.maps.ControlPosition.RIGHT_TOP
  	}
  });

  map.setCenter(new google.maps.LatLng(Lat, Lon));

	var geocoder = new google.maps.Geocoder;
  var infowindow = new google.maps.InfoWindow;
	// Create a marker object
	var marker = new google.maps.Marker({
		position: new google.maps.LatLng(Lat, Lon),
		map: map,
		title:''
	});
	marker.title = marker.title + Login;
	google.maps.event.addListener(marker, 'click',
	function() {
		infowindow.open(map,marker);
	});
	document.getElementById('lat').textContent = 'Latitude: '+ Lat+'°';
	document.getElementById('lon').textContent = 'Longitude: ' + Lon+'°';
	document.getElementById('acc').textContent = 'Accuracy: ' + Acc+'m';
	document.getElementById('err').textContent = 'Error: (' + Err + ') ' + Msg;

  document.getElementById('get_location').addEventListener('click',
  	geocodeLatLng(geocoder, map, infowindow, Lat, Lon ,marker)
  );	
}

function geocodeLatLng(geocoder, map, infowindow, myLat, myLon, marker) {

  var latlng = {lat: myLat, lng: myLon};
  geocoder.geocode({'location': latlng}, function(results, status) {
    if (status === 'OK') {
      if (results[0]) {
        infowindow.setContent(results[0].formatted_address);
        infowindow.open(map, marker);
      } else {
        infowindow.setContent(results[1].formatted_address);
        infowindow.open(map, marker);
      }
    } else {
    	document.getElementById('err').textContent = 
    	document.getElementById('err').textContent + ' (Geocoder failed due to: ' + status +')';
    }
  });
}

function wms7_stat_graph(result) {

alert(result);
}