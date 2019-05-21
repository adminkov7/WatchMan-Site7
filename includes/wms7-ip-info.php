<?php
/**
 * Description: Takes the IP of the visitor. Returns an array of information about IP.
 *
 * @category    wms7-ip-info.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Used for select of name who is provider.
 *
 * @param string $user_ip         User ip.
 * @param string $provider_who_is Name of who is provider.
 * @return array.
 */
function wms7_who_is( $user_ip, $provider_who_is ) {

	switch ( $provider_who_is ) {
		case 'IP-API':
			return wms7_ip_api( $user_ip );
		case 'IP-Info':
			return wms7_ip_info( $user_ip );
		case 'Geobytes':
			return wms7_geobytes( $user_ip );
		case 'SxGeo':
			return wms7_sx_geo( $user_ip );
		case 'none':
			return array();
	}
}
/**
 * Used to retrieve ip information from the provider IP_API.
 *
 * @param string $user_ip User ip.
 * @return array.
 */
function wms7_ip_api( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_https       = filter_input( INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING );
	$_server_port = filter_input( INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING );
	$str_info     = array();
	// Receiving data from API  JSON - format.
	$country_info = json_decode( $wp_filesystem->get_contents( 'http://ip-api.com/json/' . $user_ip ), true );
	// get user country code.
	$country_code = isset( $country_info['countryCode'] ) ? $country_info['countryCode'] : 'AA';
	// get user country flag.
	if ( is_ssl() ) {
		$path_img = set_url_scheme( WP_PLUGIN_URL, 'https' ) . '/watchman-site7/images/flags/' . $country_code . '.gif';
	} else {
		$path_img = WP_PLUGIN_URL . '/watchman-site7/images/flags/' . $country_code . '.gif';
	}
	$flag = '<img src=' . $path_img . '>';
	// get user country region.
	$region = isset( $country_info['regionName'] ) ? $country_info['regionName'] : '';
	// get user country city.
	$city = isset( $country_info['city'] ) ? $country_info['city'] : '';
	// get user country name.
	$country_name = wms7_country_name( $country_code );
	// info.
	$str_info['country']  = ( '[' . $country_code . '] ' . $flag . '<br>'
							. 'Country: ' . $country_name . '<br>'
							. 'Region: ' . $region . '<br>'
							. 'City: ' . $city );
	$provider             = isset( $country_info['as'] ) ? $country_info['as'] : 'no data';
	$str_info['provider'] = $provider;
	// coordinates.
	$lat = isset( $country_info['lat'] ) ? sanitize_text_field( $country_info['lat'] ) : '';
	$lon = isset( $country_info['lon'] ) ? sanitize_text_field( $country_info['lon'] ) : '';

	$lat                = ( isset( $lat ) && strlen( $lat ) !== 0 ) ? 'Lat_ip=' . $lat . '<br>' : '';
	$lon                = ( isset( $lon ) && strlen( $lon ) !== 0 ) ? 'Lon_ip=' . $lon : '';
	$str_info['geo_ip'] = $lat . $lon;

	return $str_info;
}
/**
 * Used to retrieve ip information from the provider Geobytes.
 *
 * @param string $user_ip User ip.
 * @return array.
 */
function wms7_geobytes( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_https       = filter_input( INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING );
	$_server_port = filter_input( INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING );
	$str_info     = array();
	// Receiving data from API  JSON - format.
	$country_info = json_decode( $wp_filesystem->get_contents( 'http://gd.geobytes.com/GetCityDetails?fqcn=' . $user_ip ), true );
	// get user country code.
	$country_code = isset( $country_info['geobytesinternet'] ) ? $country_info['geobytesinternet'] : 'AA';
	if ( '' === $country_info['geobytesinternet'] ) {
		$country_code = 'AA';
	}
	// get user country flag.
	if ( is_ssl() ) {
		$path_img = set_url_scheme( WP_PLUGIN_URL, 'https' ) . '/watchman-site7/images/flags/' . $country_code . '.gif';
	} else {
		$path_img = WP_PLUGIN_URL . '/watchman-site7/images/flags/' . $country_code . '.gif';
	}
	$flag = '<img src=' . $path_img . '>';
	// get user country region.
	$region = isset( $country_info['geobytesregion'] ) ? $country_info['geobytesregion'] : '';
	// get user country city.
	$city = isset( $country_info['geobytescity'] ) ? $country_info['geobytescity'] : '';
	// get user country name.
	$country_name = ( 'AA' !== $country_code ) ? wms7_country_name( $country_code ) : '';
	// info.
	$str_info['country']  = ( '[' . $country_code . '] ' . $flag . '<br>'
							. 'Country: ' . $country_name . '<br>'
							. 'Region: ' . $region . '<br>'
							. 'City: ' . $city );
	$provider             = 'no data';
	$str_info['provider'] = $provider;
	// coordinates.
	$lat = isset( $country_info['geobyteslatitude'] ) ? sanitize_text_field( $country_info['geobyteslatitude'] ) : '';
	$lon = isset( $country_info['geobyteslongitude'] ) ? sanitize_text_field( $country_info['geobyteslongitude'] ) : '';

	$lat                = ( isset( $lat ) && strlen( $lat ) !== 0 ) ? 'Lat_ip=' . $lat . '<br>' : '';
	$lon                = ( isset( $lon ) && strlen( $lon ) !== 0 ) ? 'Lon_ip=' . $lon : '';
	$str_info['geo_ip'] = $lat . $lon;

	return $str_info;
}
/**
 * Used to retrieve ip information from the provider IP_info.
 *
 * @param string $user_ip User ip.
 * @return array.
 */
function wms7_ip_info( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_https       = filter_input( INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING );
	$_server_port = filter_input( INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING );
	$str_info     = array();
	// Receiving data from API  JSON - format.
	$country_info = json_decode( $wp_filesystem->get_contents( 'http://ipinfo.io/' . $user_ip . '/json' ) );
	// get user country code.
	$country_code = isset( $country_info->country ) ? $country_info->country : 'AA';
	// get user country flag.
	if ( is_ssl() ) {
		$path_img = set_url_scheme( WP_PLUGIN_URL, 'https' ) . '/watchman-site7/images/flags/' . $country_code . '.gif';
	} else {
		$path_img = WP_PLUGIN_URL . '/watchman-site7/images/flags/' . $country_code . '.gif';
	}
	$flag = '<img src=' . $path_img . '>';
	// get user country region.
	$region = isset( $country_info->region ) ? $country_info->region : '';
	// get user country city.
	$city = isset( $country_info->city ) ? $country_info->city : '';
	// get user country name.
	$country_name = wms7_country_name( $country_code );
	// info.
	$str_info['country']  = ( '[' . $country_code . '] ' . $flag . '<br>'
							. 'Country: ' . $country_name . '<br>'
							. 'Region: ' . $region . '<br>'
							. 'City: ' . $city );
	$provider             = isset( $country_info->org ) ? $country_info->org : 'no data';
	$str_info['provider'] = $provider;
	// coordinates.
	$loc    = isset( $country_info->loc ) ? explode( ',', $country_info->loc ) : array();
	$loc[0] = isset( $loc[0] ) ? sanitize_text_field( $loc[0] ) : '';
	$loc[1] = isset( $loc[1] ) ? sanitize_text_field( $loc[1] ) : '';

	$lat                = ( isset( $loc[0] ) && strlen( $loc[0] ) !== 0 ) ? 'Lat_ip=' . $loc[0] . '<br>' : '';
	$lon                = ( isset( $loc[1] ) && strlen( $loc[1] ) !== 0 ) ? 'Lon_ip=' . $loc[1] : '';
	$str_info['geo_ip'] = $lat . $lon;

	return $str_info;
}
/**
 * Used to retrieve ip information from the provider SxGeo.
 *
 * @param string $user_ip User ip.
 * @return array.
 */
function wms7_sx_geo( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_https       = filter_input( INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING );
	$_server_port = filter_input( INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING );
	$str_info     = array();
	// Receiving data from API  JSON - format.
	$geo_api_request = 'http://api.sypexgeo.net/json/' . $user_ip;
	$geo_info_json   = $wp_filesystem->get_contents( $geo_api_request );
	// Receive associative array with result of request to API.
	$country_info = json_decode( $geo_info_json, true );

	// get user country code.
	$country_code = isset( $country_info['country']['iso'] ) ? $country_info['country']['iso'] : 'AA';
	// get user country name.
	$country_name = wms7_country_name( $country_code );
	// get user country region.
	$region = isset( $country_info['region']['name_en'] ) ? $country_info['region']['name_en'] : '';
	// get user country city.
	$city = isset( $country_info['city']['name_en'] ) ? $country_info['city']['name_en'] : '';
	// get user country flag.
	if ( is_ssl() ) {
		$path_img = set_url_scheme( WP_PLUGIN_URL, 'https' ) . '/watchman-site7/images/flags/' . $country_code . '.gif';
	} else {
		$path_img = WP_PLUGIN_URL . '/watchman-site7/images/flags/' . $country_code . '.gif';
	}
	$flag = '<img src=' . $path_img . '>';
	// info.
	$str_info['country']  = ( '[' . $country_code . '] ' . $flag . '<br>'
							. 'Country: ' . $country_name . '<br>'
							. 'Region: ' . $region . '<br>'
							. 'City: ' . $city );
	$provider             = 'no data';
	$str_info['provider'] = $provider;
	// coordinates.
	$lat = isset( $country_info['city']['lat'] ) ? sanitize_text_field( $country_info['city']['lat'] ) : '';
	$lon = isset( $country_info['city']['lon'] ) ? sanitize_text_field( $country_info['city']['lon'] ) : '';

	$lat                = ( isset( $lat ) && strlen( $lat ) !== 0 ) ? 'Lat_ip=' . $lat . '<br>' : '';
	$lon                = ( isset( $lon ) && strlen( $lon ) !== 0 ) ? 'Lon_ip=' . $lon : '';
	$str_info['geo_ip'] = $lat . $lon;

	return $str_info;
}
/**
 * Used to retrieve information of country of visitor.
 *
 * @param string $country_code Country code.
 * @return array.
 */
function wms7_country_name( $country_code ) {
	global $wpdb;
	if ( 'AA' === $country_code ) {
		return;
	}
	$cache_key = 'country_code_' . $country_code;
	$results   = wp_cache_get( $cache_key );
	if ( ! $results ) {
		$results = $wpdb->get_var(
			$wpdb->prepare(
				"
                SELECT `name`
                FROM {$wpdb->prefix}watchman_site_countries
                WHERE `code` = %s
                ",
				$country_code
			)
		);// db call ok; cache ok.
		wp_cache_set( $cache_key, $results );
	}
	return $results;
}
