if (navigator.geolocation) {
  /* Geolocation enabled */
 	navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
} else {
  /* Geolocation not enabled */
  //alert('GPS not supported');
}

function successCallback(position) {
var lat = position.coords.latitude;
var lon = position.coords.longitude;
var acc = position.coords.accuracy;
var xmlhttp = getXmlHttp();

// variable wms7_url from module watchman-site7.php
// Open an asynchronous connection
xmlhttp.open('POST', wms7_url, true); 
// Sent encoding
xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
// Send a POST request
xmlhttp.send('Lat_wifi_js=' + encodeURIComponent(lat) + '&Lon_wifi_js=' + encodeURIComponent(lon) + '&Acc_wifi_js=' + encodeURIComponent(acc)); 
//alert(encodeURIComponent(lat)+\n'+encodeURIComponent(lon)+'\n'+encodeURIComponent(acc)+'\n'+wms7_url);
    xmlhttp.onreadystatechange = function() { // Waiting for a response from the server
      if (xmlhttp.readyState == 4) { // The response is received
        if(xmlhttp.status == 200) { // The server returned code 200 (which is good)
        	//alert('successCallback'+'\n'+xmlhttp.responseText+'\n'+'readyState='+xmlhttp.readyState+'\n'+'status='+xmlhttp.status);
        }
      }
      //alert(xmlhttp.responseText+'\n'+'readyState='+xmlhttp.readyState+'\n'+'status='+xmlhttp.status);
    };
}

function errorCallback(error) {
var xmlhttp = getXmlHttp();

// variable wms7_url from module watchman-site7.php
// Open an asynchronous connection
xmlhttp.open('POST', wms7_url, true);
// Sent encoding
xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
// Send a POST request
xmlhttp.send('Err_code_js=' + encodeURIComponent(error.code) + '&Err_msg_js=' + encodeURIComponent(error.message)); 
//alert(error.message);
    xmlhttp.onreadystatechange = function() { // Waiting for a response from the server
      if (xmlhttp.readyState == 4) { // The response is received
        if(xmlhttp.status == 200) { // The server returned code 200 (which is good)
        	//alert('errorCallback'+'\n'+xmlhttp.responseText+'\n'+'readyState='+xmlhttp.readyState+'\n'+'status='+xmlhttp.status);
        }
      }
    //alert(xmlhttp.responseText+'\n'+'readyState='+xmlhttp.readyState+'\n'+'status='+xmlhttp.status);
    };
}

/* This function creates a cross-browser object XMLHTTP */
function getXmlHttp() {
	var xmlhttp;
	try {
		xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}