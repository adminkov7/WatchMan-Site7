<?php
/*
Slave module: ip-info7.php
Description:  Takes the IP of the visitor. Returns an array of information about IP.
Version:      2.2.1
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

function wms7_who_is($user_IP,$provider_who_is){

  switch ($provider_who_is) {
    case "IP-API":
      return wms7_IP_API($user_IP);     	
    case "IP-Info":
      return wms7_IP_info($user_IP);
    case "Geobytes":
      return wms7_Geobytes($user_IP);        
    case "SxGeo":
      return wms7_SxGeo($user_IP); 
    case "":
      return "";             
  }
}

function wms7_IP_API($user_IP){
	$str_info = array();
	// Получаем данные от API в JSON - формате
  $country_info = json_decode(file_get_contents('http://ip-api.com/json/'. $user_IP), true);
	//get user country code
	$country_code = isset($country_info['countryCode']) ? $country_info['countryCode'] : 'AA';
	//get user country flag
  $path_img = plugins_url('../images/flags/', __FILE__).$country_code.".gif";
  if (strpos($path_img, 'http:') && $_SERVER['HTTPS']){
  	$path_img = str_replace("http:", "https:", $path_img);
  }
  $flag = "<img src=".$path_img.">";
	//get user country region
	$region = isset($country_info['regionName']) ? $country_info['regionName'] : '';
	//get user country city
	$city = isset($country_info['city']) ? $country_info['city'] : '';
	//get user country name
	$country_name = wms7_country_name($country_code);
  //info
	$str_info['country'] = ('['.$country_code.'] '.$flag.'<br>'
							.'Country: '.$country_name.'<br>'
							.'Region: '.$region.'<br>'
							.'City: '.$city);
	$provider = isset($country_info['as']) ? $country_info['as'] : 'no data';
	$str_info['provider'] = $provider;
	//coordinates
	$lat = isset($country_info['lat']) ? sanitize_text_field($country_info['lat']) : '';
	$lon = isset($country_info['lon']) ? sanitize_text_field($country_info['lon']) : '';

	$lat = (isset($lat) && strlen($lat) !== 0) ? 'Lat_ip='.$lat.'<br>' : '';
	$lon = (isset($lon) && strlen($lon) !== 0) ? 'Lon_ip='.$lon : '';
	$str_info['geo_ip'] = $lat.$lon;
	
	return $str_info;
}

function wms7_Geobytes($user_IP){
	$str_info = array();	
	// Получаем данные от API в JSON - формате
  $country_info = json_decode(file_get_contents('http://gd.geobytes.com/GetCityDetails?fqcn='. $user_IP), true);
	//get user country code
	$country_code = isset($country_info['geobytesinternet']) ? $country_info['geobytesinternet'] : 'AA';
	if ($country_info['geobytesinternet'] == '') $country_code = 'AA';
	//get user country flag
  $path_img = plugins_url('../images/flags/', __FILE__).$country_code.".gif";
  if (strpos($path_img, 'http:') && $_SERVER['HTTPS']){
  	$path_img = str_replace("http:", "https:", $path_img);
  }  
  $flag = "<img src=".$path_img.">";
	//get user country region
	$region = isset($country_info['geobytesregion']) ? $country_info['geobytesregion'] : '';
	//get user country city
	$city = isset($country_info['geobytescity']) ? $country_info['geobytescity'] : '';
	//get user country name
	$country_name = ($country_code !=='AA') ? wms7_country_name($country_code) : '';
  //info
	$str_info['country'] = ('['.$country_code.'] '.$flag.'<br>'
							.'Country: '.$country_name.'<br>'
							.'Region: '.$region.'<br>'
							.'City: '.$city);
	$provider = 'no data';
	$str_info['provider'] = $provider;
	//coordinates
	$lat = isset($country_info['geobyteslatitude']) ? sanitize_text_field($country_info['geobyteslatitude']) : '';
	$lon = isset($country_info['geobyteslongitude']) ? sanitize_text_field($country_info['geobyteslongitude']) : '';

	$lat = (isset($lat) && strlen($lat) !== 0) ? 'Lat_ip='.$lat.'<br>' : '';
	$lon = (isset($lon) && strlen($lon) !== 0) ? 'Lon_ip='.$lon : '';
	$str_info['geo_ip'] = $lat.$lon;

	return $str_info;
}

function wms7_IP_info($user_IP){
	$str_info = array();	
	// Получаем данные от API в JSON - формате
	$country_info = json_decode(file_get_contents("http://ipinfo.io/".$user_IP."/json")); 
	//get user country code
	$country_code = isset($country_info->country) ? $country_info->country : 'AA';
	//get user country flag
  $path_img = plugins_url('../images/flags/', __FILE__).$country_code.".gif";
  if (strpos($path_img, 'http:') && $_SERVER['HTTPS']){
  	$path_img = str_replace("http:", "https:", $path_img);
  }  
  $flag = "<img src=".$path_img.">";
	//get user country region
	$region = isset($country_info->region) ? $country_info->region : '';
	//get user country city
	$city = isset($country_info->city) ? $country_info->city : '';
	//get user country name
	$country_name = wms7_country_name($country_code);
  //info
	$str_info['country'] = ('['.$country_code.'] '.$flag.'<br>'
							.'Country: '.$country_name.'<br>'
							.'Region: '.$region.'<br>'
							.'City: '.$city);
	$provider = isset($country_info->org) ? $country_info->org : 'no data';
	$str_info['provider'] = $provider;
	//coordinates
	$loc = isset($country_info->loc) ? explode(',', $country_info->loc) : array();
	$loc[0] = isset($loc[0]) ? sanitize_text_field($loc[0]) : '';
	$loc[1] = isset($loc[1]) ? sanitize_text_field($loc[1]) : '';

	$lat = (isset($loc[0]) && strlen($loc[0]) !== 0) ? 'Lat_ip='.$loc[0].'<br>' : '';
	$lon = (isset($loc[1]) && strlen($loc[1]) !== 0) ? 'Lon_ip='.$loc[1] : '';
	$str_info['geo_ip'] = $lat.$lon;

	return $str_info;
}

function wms7_SxGeo($user_IP){
	$str_info = array();	
	// Получаем данные от API в JSON - формате
	$geo_api_request = "http://api.sypexgeo.net/json/".$user_IP;
	$geo_info_json = file_get_contents ($geo_api_request);
	// Получаем ассоциативный php-массив с результатом запроса к API
	$country_info = json_decode($geo_info_json, true);

	//get user country code
	$country_code = isset($country_info['country']['iso']) ? $country_info['country']['iso'] : 'AA';
	//get user country name
	$country_name = wms7_country_name($country_code);
	//get user country region
	$region = isset($country_info['region']['name_en']) ? $country_info['region']['name_en'] : '';
	//get user country city
	$city = isset($country_info['city']['name_en']) ? $country_info['city']['name_en'] : '';
	//get user country flag
  $path_img = plugins_url('../images/flags/', __FILE__).$country_code.".gif";
  if (strpos($path_img, 'http:') && $_SERVER['HTTPS']){
  	$path_img = str_replace("http:", "https:", $path_img);
  }  
  $flag = "<img src=".$path_img.">";
  //info
	$str_info['country'] = ('['.$country_code.'] '.$flag.'<br>'
							.'Country: '.$country_name.'<br>'
							.'Region: '.$region.'<br>'
							.'City: '.$city);
	$provider = 'no data';
	$str_info['provider'] = $provider;
  //coordinates
	$lat = isset($country_info['city']['lat']) ? sanitize_text_field($country_info['city']['lat']) : '';
	$lon = isset($country_info['city']['lon']) ? sanitize_text_field($country_info['city']['lon']) : '';

  $lat = (isset($lat) && strlen($lat) !== 0) ? 'Lat_ip='.$lat.'<br>' : '';
  $lon = (isset($lon) && strlen($lon) !== 0) ? 'Lon_ip='.$lon : '';
	$str_info['geo_ip'] = $lat.$lon;

	return $str_info;
}

function wms7_country_name($country_code){
  global $wpdb;
	if 	($country_code == 'AA')	return;
  $sql = "SELECT * FROM `wp_watchman_site_countries` WHERE `code` = '".$country_code."'";
	$result = $wpdb->get_row($sql);
	return $result->name = (isset($result)) ? $result->name : '';
}