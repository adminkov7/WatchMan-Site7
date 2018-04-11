function wms7_sse() {
  var myElement = document.getElementById('sse');

  if (myElement.checked) {
      document.cookie = 'wms7_sse=on';
      if (!! window.EventSource ) {
          var source = new EventSource ( wms7_url+'includes/sse.php' ); 

          source.addEventListener('message', function(e) {
              console.log(e.data);
              var arr = e.data.split('|');
              if (get_cookie('wms7_records_count') !== arr[0] || get_cookie('wms7_unseen_count') !== arr[1]) {
                  document.cookie = 'wms7_records_count=' + arr[0];
                  document.cookie = 'wms7_unseen_count=' + arr[1];
                  wms7_beep();
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
  wms_ctrl_btn_href();
}

function wms_ctrl_btn_href() {
  var sse = document.getElementById('sse');

  if (sse.checked) {
      //disable all controls
      document.getElementById('doaction1').disabled=true;
      document.getElementById('doaction2').disabled=true;
      document.getElementById('doaction3').disabled=true;
      document.getElementById('btn_bottom1').disabled=true;
      document.getElementById('btn_bottom2').disabled=true;
      document.getElementById('btn_bottom3').disabled=true;
      document.getElementById('btn_bottom4').disabled=true;
      document.getElementById('btn_bottom5').disabled=true;
      document.getElementById('btn_bottom6').disabled=true;
      document.getElementById('btn_bottom7').disabled=true;

      // create a new style sheet 
      var styleTag = document.createElement ("style");
      var a = document.getElementsByTagName ("a")[0];
      a.appendChild (styleTag);

      var sheet = styleTag.sheet ? styleTag.sheet : styleTag.styleSheet;
      
          // add a new rule to the style sheet
      if (sheet.insertRule) {
          sheet.insertRule ("a {pointer-events: none;}", 0);
        }else{
          sheet.addRule ("a", "pointer-events: none;", 0);
      }
    }else{
      //enable all controls
      document.getElementById('doaction1').disabled=false;
      document.getElementById('doaction2').disabled=false;
      document.getElementById('doaction3').disabled=false;
      document.getElementById('btn_bottom1').disabled=false;
      document.getElementById('btn_bottom2').disabled=false;
      document.getElementById('btn_bottom3').disabled=false;
      document.getElementById('btn_bottom4').disabled=false;
      document.getElementById('btn_bottom5').disabled=false;
      document.getElementById('btn_bottom6').disabled=false;
      document.getElementById('btn_bottom7').disabled=false;
  }
}

function wms7_getUrlVars() {
  var vars = {};
  var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
      vars[key] = value;
  });
  return vars;
}

function get_cookie(cookie_name){
  var results = document.cookie.match ('(^|;) ?' + cookie_name + '=([^;]*)(;|$)');
 
  if ( results ) {
      return ( decodeURI ( results[2] ) );
    }else{
      return null;
  }
}

window.onload = function() {
  var page = wms7_getUrlVars()['page'];
  var result = wms7_getUrlVars()['result'];
  var checkbox = wms7_getUrlVars()['checkbox'];
  audioCtx = new (window.AudioContext || window.webkitAudioContext)();

  if (page =='wms7_settings') {
    wms7_check_pwd('pwd_box0');
    wms7_check_pwd('pwd_box1');
    wms7_check_pwd('pwd_box2');
    wms7_check_pwd('pwd_box3');
    wms7_check_pwd('pwd_box4');

    wms7_show();    
  }
  if (page =='wms7_visitors') {
    wms7_link_focus(page, result);
    wms7_stat_focus();
    wms7_mail_focus();    
    if (!get_cookie('wms7_sse')){
        document.cookie = 'wms7_sse=off';
      }else{
        if (get_cookie('wms7_sse') == 'on'){
          var myElement = document.getElementById('sse');
          myElement.checked = true;
          //start SSE
          wms7_sse();
        }
    }
  }
}

function wms7_popup_loader() {
  var loader = document.getElementById('win-loader');
  loader.style.visibility='visible';
}

function wms7_popup_close() {
  var page = 'page='+wms7_getUrlVars()['page'];
  var paged = (wms7_getUrlVars()['paged']) ? '&paged='+wms7_getUrlVars()['paged'] : '';
  var result = (wms7_getUrlVars()['result']) ? '&result='+wms7_getUrlVars()['result'] : '&result=5';
  var filter_role = (wms7_getUrlVars()['filter_role']) ? '&filter_role='+wms7_getUrlVars()['filter_role'] : '';
  var filter_time = (wms7_getUrlVars()['filter_time']) ? '&filter_time='+wms7_getUrlVars()['filter_time'] : '';
  var filter_country = (wms7_getUrlVars()['filter_country']) ? '&filter_country='+wms7_getUrlVars()['filter_country'] : '';
  //переводим в исходное состояние
  document.cookie = 'wms7_mail_btn=folder1';
  //
  var url = window.location.href.slice(0,window.location.href.indexOf('\?'));

  url = url + '?' + page +  paged + result + filter_role + filter_time + filter_country;

  location.replace(url);
 }

function wms7_stat_focus() {
  var btn;
  var myElement;
  btn = get_cookie('wms7_stat_btn');
  if (document.getElementsByName('radio_stat')){
    switch (btn) {
      case 'visits' : {myElement = document.getElementById('visits'); break;}
      case 'unlogged' : {myElement = document.getElementById('unlogged'); break;}
      case 'success' : {myElement = document.getElementById('success'); break;}
      case 'failed' : {myElement = document.getElementById('failed'); break;}
      case 'robots' : {myElement = document.getElementById('robots'); break;}
      case 'blacklist' : {myElement = document.getElementById('blacklist'); break;}
    }
      if (myElement) {myElement.checked = true;}
  }    
}

function wms7_mail_focus() {
  var myElement;
  var btn = get_cookie('wms7_mail_btn');
  if (document.getElementsByName('radio_mail')){ 
    switch (btn) {
      case 'folder1' : {myElement = document.getElementById('folder1'); break;}
      case 'folder2' : {myElement = document.getElementById('folder2'); break;}
      case 'folder3' : {myElement = document.getElementById('folder3'); break;}
      case 'folder4' : {myElement = document.getElementById('folder4'); break;}
    }
    if (myElement) {
      myElement.checked = true;
      myElement.selected = true;
    }
  }
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
    case 'radio-6': {location.replace(url+'&result=4'); break;}
    case undefined : {location.replace(url+'&result=5');}
  }
}

function mailbox_selector(folder) {
  switch (folder) {
    case 'folder1': {document.cookie = 'wms7_mail_btn=folder1';break;}
    case 'folder2': {document.cookie = 'wms7_mail_btn=folder2';break;}
    case 'folder3': {document.cookie = 'wms7_mail_btn=folder3';break;}
    case 'folder4': {document.cookie = 'wms7_mail_btn=folder4';break;}
  }
  var page = 'page='+wms7_getUrlVars()['page'];
  var paged = 'paged='+wms7_getUrlVars()['paged'];
  var mailbox = 'mailbox='+get_cookie( 'wms7_mail_btn' );
  var result = wms7_getUrlVars()['result'];
  if (result) {
    result = 'result='+wms7_getUrlVars()['result'];
  }else{
    result='result=5';
  }
  var stateParameters = { page: page, result: result, paged: paged };
  var url = window.location.href.slice(0,window.location.href.indexOf('\?'));
  url = url + '?'+ page + '&' + result + '&' + paged + '&' + mailbox;

  history.pushState(stateParameters, "WatchMan-Site7", url);
  window.location.replace(url);
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

function wms7_stat_btn(){
  myElement = document.getElementsByName('radio_stat');

  for (i = 0; i<myElement.length; i++) {
    if (myElement[i].checked) break;
  }
  btn=myElement[i].value;
  document.cookie = 'wms7_stat_btn='+btn;
}

function wms7_quit_btn(){
  var page = 'page='+wms7_getUrlVars()['page'];
  var paged = 'paged='+wms7_getUrlVars()['paged'];
  var mailbox = 'mailbox='+get_cookie( 'wms7_mail_btn' );
  var result = wms7_getUrlVars()['result'];
  if (result) {
    result = 'result='+wms7_getUrlVars()['result'];
  }else{
    result='result=5';
  }
  var stateParameters = { page: page, result: result, paged: paged };
  var url = window.location.href.slice(0,window.location.href.indexOf('\?'));
  url = url + '?'+ page + '&' + result + '&' + paged + '&' + mailbox;

  history.pushState(stateParameters, "WatchMan-Site7", url);
}

function wms7_check_boxes(id){
  var page = 'page='+wms7_getUrlVars()['page'];
  var paged = 'paged='+wms7_getUrlVars()['paged'];
  var checkbox = 'checkbox='+id;
  var result = wms7_getUrlVars()['result'];
  if (result) {
    result = 'result='+wms7_getUrlVars()['result'];
  }else{
    result='result=5';
  }
  var stateParameters = { page: page, result: result, paged: paged };
  var url = window.location.href.slice(0,window.location.href.indexOf('\?'));
  url = url + '?'+ page + '&' + result + '&' + paged + '&' + checkbox;

  history.pushState(stateParameters, "WatchMan-Site7", url);
  window.location.replace(url);
}

function wms7_check_pwd(id){
  myElementChk = document.getElementById(id);
  switch (id) {
    case 'pwd_box0': {myElementPwd = document.getElementById('mail_box_pwd_box0'); break;}
    case 'pwd_box1': {myElementPwd = document.getElementById('mail_box_pwd_box1'); break;}
    case 'pwd_box2': {myElementPwd = document.getElementById('mail_box_pwd_box2'); break;}
    case 'pwd_box3': {myElementPwd = document.getElementById('mail_box_pwd_box3'); break;}
    case 'pwd_box4': {myElementPwd = document.getElementById('mail_box_pwd_box4'); break;}    
  }
  if (myElementChk.checked) {
      myElementPwd.setAttribute('type', 'password');
    }else{    
      myElementPwd.setAttribute('type', 'text');
  }
}

function wms7_mail_folders(box,id_tbl,id_textarea,id_textarea_alt){
  myElement1 = document.getElementById(id_tbl);
  myElement1.style.display = 'none';
  myElement2 = document.getElementById(id_textarea);
  myElement2.style.display = 'block';
  myElement3 = document.getElementById(id_textarea_alt);
  myElement3.style.display = 'none';

  mylist = '';
  mylist_alt = '';
  mytr = myElement1.getElementsByTagName('tr');
  mychk = document.getElementsByName(box+'_chk');
  for (var i = 0; i < mychk.length; i++) {
    element=mychk[i];
    if (element.checked) {
      id = element.id;
      for (var j = 0; j < mytr.length; j++) {
        var row = mytr[j];
        if (row.cells[0].innerHTML.indexOf(id) > '0') {
          mylist = mylist + row.cells[1].innerHTML + ';' + '\n';
          mylist_alt = mylist_alt + row.cells[1].getAttribute('data') + ';' + '\n';
        }  
      }
    }
  }  
  myElement2.innerHTML = mylist;
  myElement3.innerHTML = mylist_alt;
}

function wms7_show(){
  frequency = document.getElementById("fIn").value;
  document.getElementById("fOut").innerHTML=frequency + ' Hz';

  switch(document.getElementById("tIn").value * 1){
    case 0: type='sine'; break; 
    case 1: type='square'; break;
    case 2: type='sawtooth'; break;
    case 3: type='triangle'; break;
  }    
  document.getElementById("tOut").innerHTML=type;

  volume = document.getElementById("vIn").value / 100;
  document.getElementById("vOut").innerHTML=volume;

  duration = document.getElementById("dIn").value;
  document.getElementById("dOut").innerHTML=duration + ' ms';
}

function wms7_beep() {
  if (!get_cookie('wms7_sound_volume') && 
      !get_cookie('wms7_sound_frequency') && 
      !get_cookie('wms7_sound_type')&& 
      !get_cookie('wms7_sound_duration')){

      var volume = '0.12';
      var frequency = '570';
      var type = 'triangle';
      var duration = '344';
      document.cookie = 'wms7_sound_volume='+volume;
      document.cookie = 'wms7_sound_frequency='+frequency;
      document.cookie = 'wms7_sound_type='+type;
      document.cookie = 'wms7_sound_duration='+duration;
    }else{
      var volume = get_cookie('wms7_sound_volume');
      var frequency = get_cookie('wms7_sound_frequency');
      var type = get_cookie('wms7_sound_type');
      var duration = get_cookie('wms7_sound_duration');
  }
  var oscillator = audioCtx.createOscillator();
  var gainNode = audioCtx.createGain();

  oscillator.connect(gainNode);
  gainNode.connect(audioCtx.destination);

  gainNode.gain.value = volume;
  oscillator.frequency.value = frequency;
  oscillator.type = type;

  oscillator.start();

  setTimeout(
    function(){
      oscillator.stop();
    }, 
    duration
  );  
}

function wms7_setup_sound(){
  document.cookie = 'wms7_sound_volume='+volume;
  document.cookie = 'wms7_sound_frequency='+frequency;
  document.cookie = 'wms7_sound_type='+type;
  document.cookie = 'wms7_sound_duration='+duration;
}