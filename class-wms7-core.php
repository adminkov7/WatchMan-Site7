<?php
/**
 * Description: Used to records data about visiting the site.
 *
 * @category    Wms7_Core
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description: Used to records data about visiting the site.
 *
 * @category    Class
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */
class Wms7_Core {
	/**
	 * Type of site visit. (Logged, Unlogged, Success, Failed, Robot).
	 *
	 * @var string
	 */
	private $login_result;
	/**
	 * User IP of visitor.
	 *
	 * @var string
	 */
	private $user_ip;
	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		add_action( 'plugins_loaded', array( $this, 'wms7_load_locale' ) );
		add_action( 'init', array( $this, 'wms7_session' ) );
		add_action( 'init', array( $this, 'wms7_init_visit_actions' ) );
		add_action( 'init', array( $this, 'wms7_lat_lon_save' ) );
		add_action( 'admin_init', array( $this, 'wms7_main_settings' ) );
		add_action( 'admin_init', 'wms7_output_csv' );
		add_action( 'admin_menu', array( $this, 'wms7_admin_menu' ) );
		add_action( 'admin_head', array( $this, 'wms7_screen_options' ) );

		add_action( 'login_head', array( $this, 'wms7_login_logo' ) );

		add_filter( 'wp_authenticate_user', array( $this, 'wms7_authenticate_user' ) );
		add_filter( 'user_contactmethods', array( $this, 'wms7_user_contactmethods' ) );
		add_action( 'user_register', array( $this, 'wms7_registered_user' ) );
		add_filter( 'screen_settings', array( $this, 'wms7_screen_settings_add' ), 10, 2 );
		add_filter( 'set-screen-option', array( $this, 'wms7_screen_settings_save' ), 11, 3 );

		add_action( 'wms7_truncate', array( $this, 'wms7_truncate_log' ) );
		if ( ! wp_next_scheduled( 'wms7_truncate' ) ) {
			wp_schedule_event( time(), 'daily', 'wms7_truncate' );
		}
		add_action( 'wms7_htaccess', array( $this, 'wms7_ctrl_htaccess' ) );
		if ( ! wp_next_scheduled( 'wms7_htaccess' ) ) {
			wp_schedule_event( time(), 'hourly', 'wms7_htaccess' );
		}
	}
	/**
	 * Start session.
	 */
	public function wms7_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}
	/**
	 * Change the logo on the site login page.
	 */
	public function wms7_login_logo() {
		$img1 = plugins_url( '/images/wms7_logo.png', __FILE__ );
		?>
		<style type="text/css">
			#login h1 a { background: url('<?php echo esc_url( $img1 ); ?>') no-repeat 0 0 !important; }
		</style>';
		<?php
	}
	/**
	 * It works when registered of user.
	 *
	 * @param string $user_id Registered user.
	 */
	public function wms7_registered_user( $user_id ) {
		// get whois_service.
		$val           = get_option( 'wms7_main_settings' );
		$whois_service = isset( $val['whois_service'] ) ? $val['whois_service'] : 'none';

		$arr = wms7_who_is( $this->user_ip, $whois_service );

		$country = isset( $arr['country'] ) ? ( $arr['country'] ) : 'none';

		$arr = explode( '<br>', $country );

		update_user_meta( $user_id, 'user_country', substr( $arr[0], 0, 4 ) );
		update_user_meta( $user_id, 'user_city', substr( $arr[3], 6 ) );
	}
	/**
	 * It works when authenticate user.
	 *
	 * @param string $user Authenticate user.
	 * @return object $user.
	 */
	public function wms7_authenticate_user( $user ) {
		// Return error if user account is blocked.
		$wms_blocked = new wms7_List_Table();
		$blocked     = $wms_blocked->wms7_login_compromising( $user->id );
		if ( $blocked ) {
			$this->login_result = 0;
			return new WP_Error( 'broke', __( '<strong>ERROR</strong>: Access denied for: ', 'watchman-site7' ) . $user->user_login );
		} else {
			return $user;
		}
	}
	/**
	 * It works when open profile of user.
	 *
	 * @param string $user_contact Registered user.
	 * @return object $user_contact.
	 */
	public function wms7_user_contactmethods( $user_contact ) {
		$user_contact['user_country'] = __( 'Country', 'watchman-site7' );
		$user_contact['user_city']    = __( 'City', 'watchman-site7' );

		return $user_contact;
	}
	/**
	 * Insert/delete - Deny from IP.
	 */
	public function wms7_ctrl_htaccess() {
		// insert/delete - Deny from IP.
		$arr            = $this->wms7_black_list_info();
		$output_id      = explode( '&#010;', $arr[0] );
		$output         = explode( '&#010;', $arr[1] );
		$output_agent   = explode( '&#010;', $arr[2] );
		$ban_user_agent = explode( '&#010;', $arr[3] );
		$i              = 0;
		foreach ( $output as $step1 ) {
			if ( ! empty( $step1 ) ) {
				$step2 = explode( '&#009;', $step1 );
				if ( date( 'Y-m-d' ) >= $step2[0] && date( 'Y-m-d' ) <= $step2[1] ) {
					wms7_ip_insert_to_file( $step2[2] );
					if ( 1 === $ban_user_agent[ $i ] && ! empty( $output_agent[ $i ] ) ) {
						wms7_rewritecond_insert( $output_agent[ $i ] );
					}
				} else {
					wms7_ip_delete_from_file( $step2[2] );
					if ( 1 === $ban_user_agent[ $i ] && ! empty( $output_agent[ $i ] ) ) {
						wms7_rewritecond_delete( $output_agent[ $i ] );
					}
					$this->wms7_login_unbaned( $output_id[ $i ] );
				}
			}
			$i++;
		}
	}
	/**
	 * It works when login unbaned.
	 *
	 * @param string $id Record id of visit.
	 */
	private function wms7_login_unbaned( $id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT `black_list` 
				FROM {$wpdb->prefix}watchman_site
				WHERE `id` = %s
				",
				$id
			)
		);// db call ok; no cache ok.
		$results = $results[0];
		$results = json_decode( $results->black_list, true );
		// unbaned user login.
		$results['ban_login'] = '0';
		// save result into field black_list.
		$results = wp_json_encode( $results );
		$wpdb->update(
			$wpdb->prefix . 'watchman_site',
			array( 'black_list' => $results ),
			array( 'ID' => $id )
		);// unprepared sql ok;db call ok;no cache ok.
	}
	/**
	 * It works when the deadline for deleting records is.
	 */
	public function wms7_truncate_log() {
		global $wpdb;

		$opt          = get_option( 'wms7_main_settings' );
		$log_duration = (int) $opt['log_duration'] - 1;
		if ( 0 < $log_duration ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        DELETE 
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `black_list` = ''
			        AND `time_visit` < DATE_SUB(CURDATE(),INTERVAL %d DAY)
			        ",
					$log_duration
				)
			);// db call ok; no cache ok.
		}
	}
	/**
	 * It works when receiving geolocation data in $_POST.
	 */
	public function wms7_lat_lon_save() {
		$_err_msg_js  = filter_input( INPUT_POST, 'err_msg_js', FILTER_SANITIZE_STRING );
		$_err_code_js = filter_input( INPUT_POST, 'err_code_js', FILTER_SANITIZE_STRING );
		$_lat_wifi_js = filter_input( INPUT_POST, 'lat_wifi_js', FILTER_SANITIZE_STRING );
		$_lon_wifi_js = filter_input( INPUT_POST, 'lon_wifi_js', FILTER_SANITIZE_STRING );
		$_acc_wifi_js = filter_input( INPUT_POST, 'acc_wifi_js', FILTER_SANITIZE_STRING );

		$err_msg  = ( $_err_msg_js ) ? str_replace( "\'", '', $_err_msg_js ) : 'ok.';
		$err_code = ( $_err_code_js ) ? $_err_code_js : '0';
		$lat_wifi = ( $_lat_wifi_js ) ? $_lat_wifi_js : '0';
		$lon_wifi = ( $_lon_wifi_js ) ? $_lon_wifi_js : '0';
		$acc_wifi = ( $_acc_wifi_js ) ? $_acc_wifi_js : '0';

		if ( ( '0' !== $lat_wifi && '0' !== $lon_wifi && '0' !== $acc_wifi ) || '0' !== $err_code ) {

			$this->wms7_save_geolocation( $lat_wifi, $lon_wifi, $acc_wifi, $err_code, $err_msg );

			if ( ! headers_sent() ) {
				header( 'Content-Type: text/event-stream' );
				header( 'Cache-Control: no-cache' );
				header( 'Connection: keep-alive' );
			}
			echo 'Data received and saved.';

			// check for output_buffering activation.
			if ( 0 !== count( ob_get_status() ) ) {
				ob_flush();
			}
			flush();
		}
	}
	/**
	 * Save data of geolocation of visitor.
	 *
	 * @param string $lat_wifi Latitude.
	 * @param string $lon_wifi Longitude.
	 * @param string $acc_wifi Accuracy.
	 * @param string $err_code Error code.
	 * @param string $err_msg  Error message.
	 */
	private function wms7_save_geolocation( $lat_wifi, $lon_wifi, $acc_wifi, $err_code, $err_msg ) {
		global $wpdb;

		$id  = $wpdb->insert_id;
		$geo = 'err_code=' . $err_code . '<br>' .
			'err_msg=' . $err_msg . '<br>' .
			'lat_wifi=' . $lat_wifi . '<br>' .
			'lon_wifi=' . $lon_wifi . '<br>' .
			'acc_wifi=' . $acc_wifi;

		$wpdb->update(
			$wpdb->prefix . 'watchman_site',
			array( 'geo_wifi' => $geo ),
			array( 'ID' => $id ),
			array( '%s' )
		);// unprepared sql ok;db call ok;no cache ok.
	}
	/**
	 * It works at the beginning load locale.
	 */
	public function wms7_load_locale() {
		load_plugin_textdomain( 'wms7', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * It works at the beginning of any visit to the site.
	 */
	public function wms7_init_visit_actions() {

		// Action on successful login.
		add_action( 'wp_login', array( $this, 'wms7_login_success' ), 9 );

		// Action on failed login.
		add_action( 'wp_login_failed', array( $this, 'wms7_login_failed' ), 9 );

		// Action visit unlogged to site.
		$this->wms7_visit_site();
	}
	/**
	 * It works when a visitor visits the site with or without a login.
	 */
	public function wms7_visit_site() {
		$this->login_result = 2;
		$this->wms7_login_action();
	}
	/**
	 * It works when add_action( 'wp_login') starts.
	 */
	public function wms7_login_success() {
		$this->login_result = 1;
		$this->wms7_login_action();
	}
	/**
	 * It works when add_action( 'wp_login_failed') starts.
	 */
	public function wms7_login_failed() {
		$this->login_result = 0;
		$this->wms7_login_action();
	}
	/**
	 * Forms IP data from global variables.
	 *
	 * @return string data of IP visitor.
	 */
	private function wms7_get_user_ip() {
		// We get the headers or use the global SERVER.
		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
			$list    = '---Apache request headers-------------&#010;';
			foreach ( $headers as $header => $value ) {
				$list = $list . $header . ': ' . $value . '&#010;';
			}
		} else {
			$headers = filter_input_array( INPUT_SERVER, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$list    = '---$_SERVER request headers-----------&#010;';
			foreach ( $headers as $header => $value ) {
				if ( substr( $header, 0, 5 ) === 'HTTP_' ) {

					$header = substr( $header, 5 );
					$header = str_replace( '_', ' ', $header );
					$header = strtolower( $header );
					$header = ucwords( $header );
					$header = str_replace( ' ', '-', $header );

					$list = $list . $header . ': ' . $value . '&#010;';
				}
			}
		}
		$the_ip = '';
		// We get the redirected IP-address, if it exists.
		$_x_forwarded_for = filter_input( INPUT_SERVER, 'X-Forwarded-For', FILTER_SANITIZE_STRING );
		if ( $_x_forwarded_for ) {
			$the_ip .= 'X-Forwarded-For = ' . $_x_forwarded_for . '&#010;';
		}
		$_http_x_forwarded_for = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING );
		if ( $_http_x_forwarded_for ) {
			$the_ip .= 'HTTP_X_FORWARDED_FOR = ' . $_http_x_forwarded_for . '&#010;';
		}
		$_http_x_forwarded = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED', FILTER_SANITIZE_STRING );
		if ( $_http_x_forwarded ) {
			$the_ip .= 'HTTP_X_FORWARDED = ' . $_http_x_forwarded . '&#010;';
		}
		$http_x_cluster_client_ip = filter_input( INPUT_SERVER, 'HTTP_X_CLUSTER_CLIENT_IP', FILTER_SANITIZE_STRING );
		if ( $http_x_cluster_client_ip ) {
			$the_ip .= 'HTTP_X_CLUSTER_CLIENT_IP = ' . $http_x_cluster_client_ip . '&#010;';
		}
		$_http_forwarded_for = filter_input( INPUT_SERVER, 'HTTP_FORWARDED_FOR', FILTER_SANITIZE_STRING );
		if ( $_http_forwarded_for ) {
			$the_ip .= 'HTTP_FORWARDED_FOR = ' . $_http_forwarded_for . '&#010;';
		}
		$_http_forwarded = filter_input( INPUT_SERVER, 'HTTP_FORWARDED', FILTER_SANITIZE_STRING );
		if ( $_http_forwarded ) {
			$the_ip .= 'HTTP_FORWARDED = ' . $_http_forwarded . '&#010;';
		}
		$_http_client_ip = filter_input( INPUT_SERVER, 'HTTP_CLIENT_IP', FILTER_SANITIZE_STRING );
		if ( $_http_client_ip ) {
			$the_ip .= 'HTTP_CLIENT_IP = ' . $_http_client_ip . '&#010;';
		}
		$_remote_addr = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );
		if ( $_remote_addr ) {
			$the_ip .= 'REMOTE_ADDR = ' . $_remote_addr;
		}
		return $list . '---' . $the_ip . '---';
	}
	/**
	 * Forms data of visit to site.
	 */
	private function wms7_login_action() {
		global $current_user;

		$_forward_for     = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING );
		$_remote_addr     = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );
		$_request_uri     = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
		$_server_addr     = filter_input( INPUT_SERVER, 'SERVER_ADDR', FILTER_SANITIZE_STRING );
		$_server_name     = filter_input( INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING );
		$_server_software = filter_input( INPUT_SERVER, 'SERVER_SOFTWARE', FILTER_SANITIZE_STRING );
		$_http_referer    = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING );
		$_http_user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
		$_arr_cookie      = filter_var_array( $_COOKIE );// WPCS: input var ok.
		$black_list       = '';

		// get user cookie.
		$user_cookie = '';
		foreach ( $_arr_cookie  as $key => $value ) {
			$user_cookie = $user_cookie . $key . '=' . $value . '&#010;';
		}
		// get user IP.
		$this->user_ip = ( $_forward_for ) ? $_forward_for : $_remote_addr;
		// get user info.
		$info_add     = $this->wms7_get_user_ip();
		$user_ip_info = '---Visit page information-------------&#010;' .
						'REQUEST_URI = ' . $_request_uri . '&#010;' .
						'SERVER_ADDR = ' . $_server_addr . '&#010;' .
						'SERVER_NAME = ' . $_server_name . '&#010;' .
						'SERVER_SOFTWARE = ' . $_server_software . '&#010;' .
						$info_add . '&#010;' .
						'---Information about the Cookie visitor---&#010;' .
						$user_cookie;
		// get page_visit.
		$page_visit = $_request_uri;
		if ( stristr( $page_visit, 'watchman-site7/class-wms7-core' ) ) {
			$page_visit = '<b style="border: 1px solid black;padding: 1px;">' . esc_html( 'geolocation', 'watchman-site7' ) . '</b>';
		}
		// get page_from.
		$page_from = ( $_http_referer ) ? $_http_referer : '';

		// Check $user_ip is excluded from the protocol visits.
		if ( $this->wms7_ip_excluded( $this->user_ip ) ) {
			return;
		}
		$userdata = wp_get_current_user();

		$uid = ( $userdata->ID ) ? $userdata->ID : 0;
		if ( 0 !== $uid ) {
			$this->login_result = 1;
		}
		$_log = filter_input( INPUT_POST, 'log', FILTER_SANITIZE_STRING );
		$_pwd = filter_input( INPUT_POST, 'pwd', FILTER_SANITIZE_STRING );
		$_rmb = filter_input( INPUT_POST, 'rememberme', FILTER_SANITIZE_STRING );

		$user_login = ( $userdata->user_login ) ? $userdata->user_login : '';
		$log        = ( $_log ) ? ( 'log: ' . $_log ) : '';
		$pwd        = ( $_pwd ) ? ( 'pwd: ' . $_pwd ) : '';
		$rmbr       = ( $_rmb ) ? ( 'rmbr: ' . $_rmb ) : '';

		$user = ( $_log || $_pwd ) ? $log . '<br>' . $pwd . '<br>' . $rmbr : $user_login;
		// get user role.
		$user_roles = $current_user->roles;
		$user_role  = array_shift( $user_roles );
		if ( is_null( $user_role ) ) {
			$user_role = '';
		}
		if ( ( 2 === $this->login_result ) && ( $_log || $_pwd ) ) {
			return;
		}
		if ( stristr( $page_visit, 'wp-admin' ) || stristr( $page_from, 'wp-admin' ) ) {
			return;
		}
		if ( stristr( $page_visit, 'wp-cron.php' ) ) {
			return;
		}
		if ( stristr( $page_visit, 'login=failed' ) ) {
			return;
		}
		if ( stristr( $page_visit, 'admin-ajax.php' ) ) {
			return;
		}
		if ( stristr( $page_visit, 'admin-ajax.php' ) ) {
			return;
		}
		// check if is robot.
		$robot = $this->wms7_robots( $_http_user_agent );
		if ( '' !== $robot ) {
			$val        = get_option( 'wms7_main_settings' );
			$robots_reg = isset( $val['robots_reg'] ) ? $val['robots_reg'] : '';
			if ( $robots_reg ) {
				$this->login_result = '3';
			} else {
				return;
			}
		}
		// get whois_service.
		$val           = get_option( 'wms7_main_settings' );
		$whois_service = isset( $val['whois_service'] ) ? $val['whois_service'] : 'none';

		$arr = wms7_who_is( $this->user_ip, $whois_service );

		$country       = isset( $arr['country'] ) ? ( $arr['country'] ) : 'none';
		$geo_ip        = isset( $arr['geo_ip'] ) ? $arr['geo_ip'] : 'none';
		$provider      = isset( $arr['provider'] ) ? $arr['provider'] : 'none';

		// get add info.
		if ( 0 === $this->login_result ) {
			$login = '<span class="failed">' . esc_html( 'Failed', 'watchman-site7' ) . '</span>';
		}
		if ( 1 === $this->login_result ) {
			$login = '<span class="successful">' . esc_html( 'Success', 'watchman-site7' ) . '</span>';
		}
		if ( 2 === $this->login_result ) {
			$login = '<span class="unlogged">' . esc_html( 'Unlogged', 'watchman-site7' ) . '</span>';
		}
		$data['Login'] = $login;

		$val             = get_option( 'wms7_main_settings' );
		$attack_analyzer = isset( $val['attack_analyzer'] ) ? $val['attack_analyzer'] : '';

		if ( '' !== $attack_analyzer ) {
			$black_list = wms7_attack_analyzer( $_log, $page_visit, $_http_user_agent, $this->user_ip );
			if ( '' !== $black_list ) {
				delete_option( 'wms7_ip_compromising' );
			}
		}
		$data['User Agent'] = $_http_user_agent;
		$serialized_data    = wp_json_encode( $data );

		$values = array(
			'uid'           => $uid,
			'user_login'    => $user,
			'user_role'     => $user_role,
			'time_visit'    => current_time( 'mysql' ),
			'user_ip'       => $this->user_ip,
			'user_ip_info'  => $user_ip_info,
			'black_list'    => $black_list,
			'whois_service' => $whois_service,
			'country'       => $country,
			'provider'      => $provider,
			'geo_ip'        => $geo_ip,
			'login_result'  => $this->login_result,
			'robot'         => $robot,
			'page_visit'    => $page_visit,
			'page_from'     => $page_from,
			'info'          => $serialized_data,
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		$this->wms7_save_data( $values, $format );
	}
	/**
	 * Used for to obtain the number of visits records.
	 *
	 * @return number.
	 */
	private function wms7_count_rows() {
		global $wpdb;

		$results = $wpdb->get_var(
			$wpdb->prepare(
				"
	            SELECT count(%s) FROM {$wpdb->prefix}watchman_site
	            ",
				'*'
			)
		);// db call ok; no cache ok.

		return $results;
	}
	/**
	 * Used for create the number of visits to different categories of visitors and different time.
	 *
	 * @return number.
	 */
	public function wms7_widget_counter() {
		global $wpdb;

		$data_month_visits = $wpdb->get_var(
			$wpdb->prepare(
				"
	            SELECT count(%s) FROM {$wpdb->prefix}watchman_site
	            WHERE login_result <> %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
	            ",
				'*',
				3
			)
		);// db call ok; no cache ok.

		$data_month_visitors = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}watchman_site
				WHERE login_result <> %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
				",
				3
			)
		);// db call ok; no cache ok.

		$data_month_robots = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT robot) FROM {$wpdb->prefix}watchman_site
				WHERE login_result = %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
				",
				3
			)
		);// db call ok; no cache ok.

		$data_week_visits = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(%s) FROM {$wpdb->prefix}watchman_site
				WHERE login_result <> %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
				",
				'*',
				3
			)
		);// db call ok; no cache ok.

		$data_week_visitors = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}watchman_site
				WHERE login_result <> %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
				",
				3
			)
		);// db call ok; no cache ok.

		$data_week_robots = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT robot) FROM {$wpdb->prefix}watchman_site
				WHERE login_result = %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
				",
				3
			)
		);// db call ok; no cache ok.

		$data_today_visits = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(%s) FROM {$wpdb->prefix}watchman_site
				WHERE login_result <> %d AND time_visit >= CURDATE()
				",
				'*',
				3
			)
		);// db call ok; cache ok.

		$data_today_visitors = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}watchman_site
				WHERE login_result <> %d AND time_visit >= CURDATE()
				",
				3
			)
		);// db call ok; no cache ok.

		$data_today_robots = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT count(DISTINCT robot) FROM {$wpdb->prefix}watchman_site
				WHERE login_result = %d AND time_visit >= CURDATE()
				",
				3
			)
		);// db call ok; no cache ok.

		$result = intval( $data_month_visits ) . '|' . intval( $data_month_visitors ) . '|' . intval( $data_month_robots ) . '|' .
				intval( $data_week_visits ) . '|' . intval( $data_week_visitors ) . '|' . intval( $data_week_robots ) . '|' .
				intval( $data_today_visits ) . '|' . intval( $data_today_visitors ) . '|' . intval( $data_today_robots );

		return $result;
	}
	/**
	 * Saves site visits.
	 *
	 * @param string $values   Data of visit.
	 * @param string $format   Format data of visit.
	 */
	private function wms7_save_data( $values, $format ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'watchman_site', $values, $format );// db call ok; no-cache ok.
		// Delete caches/options.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_delete( 'wms7_role_time_country_filter_part1' );
			wp_cache_delete( 'wms7_role_time_country_filter_part2' );
			wp_cache_delete( 'wms7_role_time_country_filter_part3' );

			wp_cache_delete( 'wms7_history_list_info' );
			wp_cache_delete( 'wms7_robot_visit_info' );
			wp_cache_delete( 'wms7_black_list_info' );
		} else {
			delete_option( 'wms7_role_time_country_filter_part1' );
			delete_option( 'wms7_role_time_country_filter_part2' );
			delete_option( 'wms7_role_time_country_filter_part3' );

			delete_option( 'wms7_history_list_info' );
			delete_option( 'wms7_robot_visit_info' );
			delete_option( 'wms7_black_list_info' );
		}
		// for admin (backend).
		$new_count_rows = $this->wms7_count_rows();
		if ( ! extension_loaded('imap') ) {
			$mail_unseen = 'undefined';
		} else {
			$mail_unseen = wms7_mail_unseen();
		}
		wms7_save_backend( $new_count_rows, $mail_unseen );
		// for client (frontend).
		$content = $this->wms7_widget_counter();
		wms7_save_frontend( $content );
	}
	/**
	 * Does not register a visit with this IP.
	 *
	 * @param string $user_ip IP of visitor.
	 * @return boolean true or false.
	 */
	private function wms7_ip_excluded( $user_ip ) {

		$val = get_option( 'wms7_main_settings' );
		$val = ( isset( $val['ip_excluded'] ) ) ? $val['ip_excluded'] : '';

		if ( empty( $val ) ) {
			return false;
		} else {
			if ( ! stristr( $val, $user_ip ) ) {
				return false;
			} else {
				return true;
			}
		}
	}
	/**
	 * Identifies the visitor as a robot or false.
	 *
	 * @param string $_http_user_agent Contains the name of the robot.
	 * @return string name of the robot or false.
	 */
	private function wms7_robots( $_http_user_agent ) {

		$val   = get_option( 'wms7_main_settings' );
		$val   = isset( $val['robots'] ) ? $val['robots'] : '';
		$robot = '';
		if ( ! empty( $val ) ) {
			$result = explode( '|', $val );
			foreach ( $result as $item ) {
				$robot = trim( $item );
				if ( '' !== ( $robot ) ) {
					if ( stristr( $_http_user_agent, $robot ) ) {
						break;
					}
				}
				$robot = '';
			}
		}
		return $robot;
	}
	/**
	 * Add menu page for admin panel.
	 */
	public function wms7_admin_menu() {
		$current_user = wp_get_current_user();
		$roles        = $current_user->roles;
		$role         = array_shift( $roles );

		if ( 'administrator' === $role ) {
			add_menu_page( esc_html( 'Visitors', 'watchman-site7' ), esc_html( 'Visitors', 'watchman-site7' ), 'activate_plugins', 'wms7_visitors', array( $this, 'wms7_visit_manager' ), 'dashicons-shield', '71' );

			add_submenu_page( 'wms7_visitors', esc_html( 'Settings', 'watchman-site7' ), esc_html( 'Settings', 'watchman-site7' ), 'activate_plugins', 'wms7_settings', array( $this, 'wms7_settings' ) );

			add_submenu_page( 'NULL', esc_html( 'Black list', 'watchman-site7' ), esc_html( 'Black list', 'watchman-site7' ), 'activate_plugins', 'wms7_black_list', array( $this, 'wms7_black_list' ) );
		}
	}
	/**
	 * Get data in the prepare_items(). The question: whether to cache this data or not is controversial.
	 * Data can be very large.
	 *
	 * @param string $orderby Order by.
	 * @param string $order   Order.
	 * @param string $limit   Limit of records.
	 * @param string $offset  Offset.
	 * @return array data.
	 */
	public function wms7_visit_get_data( $orderby = false, $order = false, $limit = 0, $offset = 0 ) {
		global $wpdb;

		$where = '';

		$where = $this->wms7_make_where_query();

		$orderby = ( ! isset( $orderby ) || '' === $orderby ) ? 'time_visit' : $orderby;
		$order   = ( ! isset( $order ) || '' === $order ) ? 'DESC' : $order;

		if ( is_array( $where ) && ! empty( $where ) ) {
			$where = ' WHERE ' . implode( ' AND ', $where );
		} else {
			$where = '';
		}
		$results = $wpdb->get_results(
			str_replace(
				"'",
				'',
				str_replace(
					"\'",
					'"',
					$wpdb->prepare(
						"
				SELECT *
				FROM {$wpdb->prefix}watchman_site
				%s
				ORDER BY %s %s
				LIMIT %d
				OFFSET %d
				",
						$where,
						$orderby,
						$order,
						$limit,
						$offset
					)
				)
			),
			'ARRAY_A'
		);// db call ok;cache ok.

		return $results;
	}

	/**
	 * Forms the string where for the main SQL query to get data in the prepare_items().
	 *
	 * @return string where.
	 */
	public function wms7_make_where_query() {
		$where           = array();
		$_filter         = filter_input( INPUT_GET, 'filter', FILTER_SANITIZE_STRING );
		$_filter_country = filter_input( INPUT_GET, 'filter_country', FILTER_SANITIZE_STRING );
		$_filter_role    = filter_input( INPUT_GET, 'filter_role', FILTER_SANITIZE_STRING );
		$_filter_time    = filter_input( INPUT_GET, 'filter_time', FILTER_SANITIZE_STRING );
		$_result         = filter_input( INPUT_GET, 'result', FILTER_SANITIZE_STRING );

		if ( ( $_filter ) && '' !== $_filter ) {
			$where['filter'] = "(user_login LIKE '%{$_filter}%' OR user_ip LIKE '%{$_filter}%')";
		}
		if ( ( $_filter_country ) && '' !== $_filter_country ) {
			$where['filter_country'] = "country LIKE '%{$_filter_country}%'";
		}
		if ( ( $_filter_role ) && '' !== $_filter_role ) {
			if ( 0 === $_filter_role ) {
				$where['filter_role'] = "uid <> 0 AND user_role = '$_filter_role'";
			} else {
				$where['filter_role'] = "user_role = '$_filter_role'";
			}
		}
		if ( ( $_filter_time ) && '' !== $_filter_time ) {
			$filter_time          = $_filter_time;
			$year                 = substr( $_filter_time, 0, 4 );
			$month                = substr( $_filter_time, -2 );
			$where['filter_time'] = "YEAR(time_visit) = $year AND MONTH(time_visit) = $month";
		}
		if ( '4' === $_result ) {
			$where['result'] = "black_list <> ''";
		} elseif ( '0' === $_result || '1' === $_result || '2' === $_result || '3' === $_result ) {
			$where['result'] = "login_result = $_result";
		} else {
			unset( $where['result'] );
		}
		return $where;
	}
	/**
	 * Add help tabs of plugin.
	 *
	 * @return object manage_options.
	 */
	public function wms7_screen_options() {
		$url_api_doc  = 'https://www.adminkov.bcr.by/doc/watchman-site7/api_doc/index.html';
		$url_user_doc = 'https://www.adminkov.bcr.by/doc/watchman-site7/user_doc/index.htm';
		// execute only on wms7 pages, othewise return null.
		$_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		if ( 'wms7_visitors' !== $_page && 'wms7_settings' !== $_page && 'wms7_black_list' !== $_page ) {
			return;
		}
		if ( 'wms7_visitors' === $_page ) {
			// define options.
			$per_page_field  = 'per_page';
			$per_page_option = 'wms7_visitors_per_page';
			$img1            = plugins_url( '/images/filters_1level.png', __FILE__ );
			$img2            = plugins_url( '/images/filters_2level.png', __FILE__ );
			$img3            = plugins_url( '/images/panel_info.png', __FILE__ );
			$img4            = plugins_url( '/images/bulk_actions.png', __FILE__ );
			$img5            = plugins_url( '/images/screen_options.png', __FILE__ );
			$img6            = plugins_url( '/images/other_functions.png', __FILE__ );
			$img7            = plugins_url( '/images/map1.png', __FILE__ );
			$img8            = plugins_url( '/images/map2.png', __FILE__ );
			$img9            = plugins_url( '/images/sse.png', __FILE__ );
			$url             = site_url();
			// if per page option is not set, use default.
			$per_page_val = get_option( $per_page_option, '10' );

			$args = array(
				'label'   => esc_html( 'The number of elements on the page:', 'watchman-site7' ),
				'default' => $per_page_val,
			);
			// display options.
			add_screen_option( $per_page_field, $args );

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-1',
					'title'   => esc_html( '1.Description', 'watchman-site7' ),
					'content' => '<p>' . esc_html( 'This plugin is written for administrators of sites created on Wordpress. The main functions of the plugin are:', 'watchman-site7' ) . '<br>' . esc_html( '1. Record the date and time of visit to the site by people, robots.', 'watchman-site7' ) . '<br>' . esc_html( '2. The entry registration site visit: successful, unsuccessful, no registration.', 'watchman-site7' ) . '<br>' . esc_html( '3. The entry address of the visitor: country of the visitor, the address of a provider.', 'watchman-site7' ) . '<br>' . esc_html( '4. Record information about the browser, OS of the visitor.', 'watchman-site7' ) . '<br>' . esc_html( '5. A visitor record in the category of unwelcome and a ban on visiting the site in a set period of time.', 'watchman-site7' ) . '<br>' . esc_html( 'For convenience the administrator of the site plugin used:', 'watchman-site7' ) . '<br>' . esc_html( '1. Filters 1 level.', 'watchman-site7' ) . '<br>' . esc_html( '2. Filters 2 level.', 'watchman-site7' ) . '<br>' . esc_html( '3. The deletion of unnecessary records on the visit in automatic and manual modes.', 'watchman-site7' ) . '<br>' . esc_html( '4. Export records of visits to the site in an external file for later analysis.', 'watchman-site7' ) . '</p>',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-2',
					'title'   => esc_html( '2.Filters level I', 'watchman-site7' ),
					'content' => esc_html( 'The first level filters are filters located in the upper part of the main page of the plugin:', 'watchman-site7' ) . '<br>' . esc_html( '- (group 1) ', 'watchman-site7' ) . '<a href="https://codex.wordpress.org/Roles_and_Capabilities#Roles" target="_blank">role</a>' . esc_html( ' of visitors.', 'watchman-site7' ) . '<br>' . esc_html( '- (group 1) date (month/year) visiting the site.', 'watchman-site7' ) . '<br>' . esc_html( '- (group 1) country of visitors to the site.', 'watchman-site7' ) . '<br>' . esc_html( '- (group 2), the username or IP of the visitor of the website.', 'watchman-site7' ) . '<br>' . esc_html( 'Filters of the first level are major and affect the operation of filters of the second level. At the first level of filters in groups I and II are mutually exclusive and can simultaneously work with only one group of filters of the first level. The range of values in the drop-down filter list level I group 1 is based on actual visits to the site visitors.', 'watchman-site7' ) . '<br><br>' . esc_html( 'Filter level I (groups 1 and 2)', 'watchman-site7' ) . '<br><img src=' . $img1 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-3',
					'title'   => esc_html( '3.Filters level II', 'watchman-site7' ),
					'content' => esc_html( 'The second level filters are filters located in the upper part of the main page of the plugin under the colour panel:', 'watchman-site7' ) . '<br>' . esc_html( '- All visits (number of visits).', 'watchman-site7' ) . '<br>' . esc_html( '- Visit without registering on the website (number of visits).', 'watchman-site7' ) . '<br>' . esc_html( '- Visits to successful registered users of the website (number of visits).', 'watchman-site7' ) . '<br>' . esc_html( '- Unsuccessful attempts to register on the website website visitors (number of attempts).', 'watchman-site7' ) . '<br>' . esc_html( '- list of the robots visiting the website (number of visits).', 'watchman-site7' ) . '<br>' . esc_html( '- Visitors to the website listed in the black list (the number).', 'watchman-site7' ) . '<br>' . esc_html( 'Filter level II, working within the rules set by the filters level I.', 'watchman-site7' ) . '<br><br>' . esc_html( 'Filter level II (6 pieces)', 'watchman-site7' ) . '<br><img src=' . $img2 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-4',
					'title'   => esc_html( '4.Panel info', 'watchman-site7' ),
					'content' => esc_html( 'Dashboard (panel - info) consists of four information blocks:', 'watchman-site7' ) . '<br>' . esc_html( '- Block « Settings » it displays the settings of the plugin installed on the Settings page.', 'watchman-site7' ) . '<br>' . esc_html( '- Block « History of visits » to the site it displays the types of site visits (A-all visits, U-unregistered visit, S was visiting, F-unsuccessful registration attempts, R-robots). Then - the number of visits.', 'watchman-site7' ) . '<br>' . esc_html( '- Block « Robots List » it displays the date, the time of the last visit robots entered in the list of robots on the Settings page.', 'watchman-site7' ) . '<br>' . esc_html( '- Block  « Black List » it shows ip of the site visitors who were blocking access to the site. Display format: date of commencement of lock-access website, end date start blocking access to the site, the ip block address of the visitor.', 'watchman-site7' ) . '<br><br>' . esc_html( 'Panel info', 'watchman-site7' ) . '<br><img src=' . $img3 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-5',
					'title'   => esc_html( '5.Bulk actions', 'watchman-site7' ),
					'content' => esc_html( 'In the category of mass actions are included:', 'watchman-site7' ) . '<br>' . esc_html( '- delete. This action allows you to delete a selected check box record in the main table - visits to the site. If any record is marked for deletion and will be marked in the black list, then the entry is NOT removed until before the administrator will deselect (command - clean) black list of this record.', 'watchman-site7' ) . '<br>' . esc_html( ' - export. This action allows you to export the selected record (visit site external Excel file. Subsequently, this file can be formatted into the desired form teams and use Excel as report. In the export file enter the following fields from the main table site visit: id, uid, login, role, date visit, visitor ip, black list, page visit, page from, info.', 'watchman-site7' ) . '<br><br>' . esc_html( 'Bulk actions', 'watchman-site7' ) . '<br><img src=' . $img4 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-6',
					'title'   => esc_html( '6.Settings screen', 'watchman-site7' ),
					'content' => esc_html( 'Group screen settings: « column » and « pagination » are the standard settings of the Wordpress screen and no additional comments need.', 'watchman-site7' ) . '<br>' . esc_html( 'Group screen settings: « Display panel-info » used to display or hide the 4 dashboard: setting list, history list, robots list, black list.', 'watchman-site7' ) . '<br>' . esc_html( 'Group screen settings: « Display filters level II » are used to display or hide filters of the 2nd level, which obey to filters of the 1st level. Are located under the info-panel and executed in the form of radio buttons.', 'watchman-site7' ) . '<br>' . esc_html( 'Group screen settings: « Display buttons add functions of bottom screen » used to display or hide the call buttons of various service functions available only to the administrator.', 'watchman-site7' ) . '<br><br>' . esc_html( 'screen Settings', 'watchman-site7' ) . '<br><img src=' . $img5 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-7',
					'title'   => esc_html( '7.Other functions', 'watchman-site7' ),
					'content' => esc_html( 'Additional features of the plugin are in the form of buttons located at the bottom of the main table, visit the website:', 'watchman-site7' ) . '<br>' . esc_html( '- « index » feature edit and save in a modal window file index.php', 'watchman-site7' ) . '<br>' . esc_html( '- « robots » feature edit and save in a modal window file rorots.txt', 'watchman-site7' ) . '<br>' . esc_html( '- « htaccess » edit function and save in a modal window file.htaccess', 'watchman-site7' ) . '<br>' . esc_html( '- « wp-config » function to edit and save it in a modal window file wp-config.php', 'watchman-site7' ) . '<br>' . esc_html( '- « wp-cron » output function and removal of task wp-cron in a modal window', 'watchman-site7' ) . '<br>' . esc_html( '- « statistic » statistic of visits to the site', 'watchman-site7' ) . '<br>' . esc_html( '- « sma » managing of current mail box', 'watchman-site7' ) . '<br><br>' . esc_html( 'Additional features', 'watchman-site7' ) . '<br><img src=' . $img6 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-8',
					'title'   => esc_html( '8.Map', 'watchman-site7' ),
					'content' => esc_html( 'The function « Map » is in each row of the main table of the plugin, in the field « Visitor IP ». For use this function, you must register ', 'watchman-site7' ) . '<a href="https://console.developers.google.com/apis/credentials" target="_blank">Google Maps API key</a>' . esc_html( ' and save it on the plugin page ', 'watchman-site7' ) . ' <a href="' . $url . '/wp-admin/admin.php?page=wms7_settings " target="_blank">Settings</a>' . esc_html( ' in the field - Google Maps API key', 'watchman-site7' ) . '<br><br>' . esc_html( 'Example of displaying the location of the visitor and of the this provider:', 'watchman-site7' ) . '<br><img src=' . $img7 . ' style="float: left;margin: 0;"><img src=' . $img8 . ' style="float: right;margin: 0;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-9',
					'title'   => esc_html( '9.SSE', 'watchman-site7' ),
					'content' => esc_html( 'The SSE function (Server Send Events) is made in the form of a button located at the top of the plugin main screen. The function is designed to automatically update the screen when new visitors to the site or new mail to the Inbox of the current mailbox. Access and mailbox name is defined in the settings on the basic plug - in settings page. If you are actively working with the plug - in, it is recommended to disable SSE mode, and after the work-re-enable SSE mode', 'watchman-site7' ) . '<br><br><img src=' . $img9 . '>',
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . esc_html( 'Additional information:', 'watchman-site7' ) . '</strong></p>' .
				'<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' . esc_html( 'page the WordPress repository', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_api_doc . '" target="_blank">' . esc_html( 'API Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_user_doc . '" target="_blank">' . esc_html( 'User Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.adminkov.bcr.by/category/wordpress/" target="_blank">' . esc_html( 'home page support plugin', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt" target="_blank">' . esc_html( 'training video', 'watchman-site7' ) . '</a></p>'
			);
		}
		if ( 'wms7_settings' === $_page ) {
			$img1  = plugins_url( '/images/options.png', __FILE__ );
			$img3  = plugins_url( '/images/ip_excluded.png', __FILE__ );
			$img4  = plugins_url( '/images/whois_service.png', __FILE__ );
			$img5  = plugins_url( '/images/robots.png', __FILE__ );
			$img7  = plugins_url( '/images/robots_banned.png', __FILE__ );
			$img8  = plugins_url( '/images/google_map_api.png', __FILE__ );
			$img9  = plugins_url( '/images/export_fields_csv.png', __FILE__ );
			$img10 = plugins_url( '/images/mail_ru.png', __FILE__ );
			$img11 = plugins_url( '/images/yandex_ru.png', __FILE__ );
			$img12 = plugins_url( '/images/yahoo_com.png', __FILE__ );
			$img13 = plugins_url( '/images/gmail_com.png', __FILE__ );

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-1',
					'title'   => esc_html( '1.General settings', 'watchman-site7' ),
					'content' => esc_html( 'Basic settings of the plugin are formed on this page and stored in the table: prefix_options in the site database. Basic settings sgruppirovany in the option: wms7_main_settings. Additionally: the screen settings are stored in the same table in option wms7_screen_settings. If you delete the plugin the above options will be removed. And will also be deleted table: prefix_watchman_site and prefix_watchman_site_countries.', 'watchman-site7' ) . '<br><br>' . esc_html( 'Fragment table prefix_options', 'watchman-site7' ) . '<br><img src=' . $img1 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-2',
					'title'   => esc_html( '2.fields: Number of records of visits', 'watchman-site7' ),
					'content' => esc_html( 'The value of this field determines for what period of time need to store information about the website visit.', 'watchman-site7' ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-3',
					'title'   => esc_html( '3.field: Do not register visits to:', 'watchman-site7' ),
					'content' => esc_html( 'Lists the ip addresses that will not be shown in the table of visits to the site. This can be useful for the ip of the administrator of the site, which makes no sense to bring to the table of visits to the site. Enumeration of ip addresses you need to divide the sign - semicolon (;) the List of ip addresses that will not be recorded in the table of visits to the site', 'watchman-site7' ) . '<br><img src=' . $img3 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-4',
					'title'   => esc_html( '4.field: WHO-IS service', 'watchman-site7' ),
					'content' => esc_html( 'Preoutboxed to choose one of the 4 WHO-is providers. Information about the site visitor is provided in the form: country code of the visitor, country name of visitor, city visitor. The quality and reliability of the information provided varies from region to region. Information provided to who-is service provider in the column of User IP', 'watchman-site7' ) . '<br><img src=' . $img4 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-5',
					'title'   => esc_html( '5.field: Robots', 'watchman-site7' ),
					'content' => esc_html( 'Lists the names of the robots that are of interest to track the frequency of visits to the site. An enumeration of the names of the robots need to share the sign - semicolon (;) a List of robots that will be recorded in the table of visits to the site', 'watchman-site7' ) . '<br><img src=' . $img5 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-6',
					'title'   => esc_html( '6.field: Visits of robots', 'watchman-site7' ),
					'content' => esc_html( 'In the case of setting the flag, all visits by robots will be recorded in the database. The names of the robots are taken from section 5 of the Robots', 'watchman-site7' ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-7',
					'title'   => esc_html( '7.field: Robots banned', 'watchman-site7' ),
					'content' => esc_html( 'A list of names of robots whose access to the site is denided. The enumeration of the names of the robots need to share the sign - semicolon (;) a List of robots that will be recorded into the file .htaccess. If this field is clear, all record lock be removed from the file .htaccess', 'watchman-site7' ) . '<br><img src=' . $img7 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-8',
					'title'   => esc_html( '8.field: Google Maps API key', 'watchman-site7' ),
					'content' => esc_html( 'API key required to display in a modal window, Google maps - location of a website visitor. The map window appears when you click the Map link in the column to Visit the IP in the table main page of the plugin. Detailed information about obtaining the key is on the support page of the plugin.', 'watchman-site7' ) . '<br><img src=' . $img8 . ' style="float: left;"><br><br><br><br><br><br>' .
					esc_html( 'Log console Google API Console, create your project and enable Google Maps JavaScript API, Google Maps Geocoding API in this project.<br>To view the list of enabled APIs:', 'watchman-site7' ) . '<br>' . esc_html( '1.Go to Google API Console: ', 'watchman-site7' ) . '<a href="https://console.developers.google.com/apis/credentials" target="_blank">Page registration Google Maps API key</a><br>' . esc_html( '2.Click Select a project, then select the same project you created and click Open.', 'watchman-site7' ) . '<br>' . esc_html( '3.In the API list on the Dashboard page, find Google Maps JavaScript API and Google Maps Geocoding API.', 'watchman-site7' ) . '<br>' . esc_html( '4.If these APIs are listed all installed. If these APIs are not in the list, add them:', 'watchman-site7' ) . '<br>' . esc_html( '-At the top of the page, select ENABLE API to open the Library tab. Alternatively, you can select Library in the left menu.', 'watchman-site7' ) . '<br>' . esc_html( '-Find the Google Maps JavaScript API and Google Maps Geocoding API and select them from the list of results.', 'watchman-site7' ) . '<br>' . esc_html( '-Click ENABLE. When the process is complete, Google Maps JavaScript API and Google Maps Geocoding API will appear in the API list on the Dashboard', 'watchman-site7' ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-9',
					'title'   => esc_html( '9.field: Exporting Table Fields', 'watchman-site7' ),
					'content' => esc_html( 'Select the fields you want in the export file -csv. The Export - command is located on the main page of the plug-in in the drop-down list - Bulk action.', 'watchman-site7' ) . '<br><br><img src=' . $img9 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-9',
					'title'   => esc_html( '10.field: MailBoxes', 'watchman-site7' ),
					'content' => esc_html( 'Examples of settings for a mailbox access the main providers of postal services.', 'watchman-site7' ) . '<br><br><img src=' . $img10 . ' style="float: left;"><img src=' . $img11 . ' style="float: right;"><img src=' . $img12 . ' style="float: left;"><img src=' . $img13 . ' style="float: right;">',
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . esc_html( 'Additional information:', 'watchman-site7' ) . '</strong></p>' .
				'<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' . esc_html( 'page the WordPress repository', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_api_doc . '" target="_blank">' . esc_html( 'API Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_user_doc . '" target="_blank">' . esc_html( 'User Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.adminkov.bcr.by/category/wordpress/" target="_blank">' . esc_html( 'home page support plugin', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt" target="_blank">' . esc_html( 'training video', 'watchman-site7' ) . '</a></p>'
			);
			return current_user_can( 'manage_options' );
		}
		if ( 'wms7_black_list' === $_page ) {
			$img1 = plugins_url( '/images/black_list.png', __FILE__ );
			$img2 = plugins_url( '/images/ban_start_date.png', __FILE__ );
			$img3 = plugins_url( '/images/ban_end_date.png', __FILE__ );

			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-1',
					'title'   => esc_html( '1. Black list', 'watchman-site7' ),
					'content' => esc_html( 'On this page information is generated to block access to the IP of the visitor to the site visit. Information to lock is stored in the file .htaccess in a string (for example): Deny from 104.223.44.213', 'watchman-site7' ) . '<br><br>' . esc_html( 'Information about blocking the IP of the visitor is stored in the form of:', 'watchman-site7' ) . '<br><img src=' . $img1 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-2',
					'title'   => esc_html( '2.field: Ban start date', 'watchman-site7' ),
					'content' => esc_html( 'This field indicates the start date of blocking the IP address of the visitor. An example of selecting the date of blocking the IP of the visitor:', 'watchman-site7' ) . '<br><br><img src=' . $img2 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-3',
					'title'   => esc_html( '3.field: Ban end date', 'watchman-site7' ),
					'content' => esc_html( 'On this page information is generated about the end of the lock IP of the visitor to the site. The reservation is removed from the file .htaccess end IP block the visitor:', 'watchman-site7' ) . '<br><br><img src=' . $img3 . ' style="float: left;">',
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-4',
					'title'   => esc_html( '4.field: Ban message', 'watchman-site7' ),
					'content' => esc_html( 'This field is used to store information as to why the decision of the administrator about the IP blocking the website visitor.', 'watchman-site7' ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'wms7-tab-5',
					'title'   => esc_html( '5.field: Ban notes', 'watchman-site7' ),
					'content' => esc_html( 'Additional, redundant field. Is used for convenience by the site administrator.', 'watchman-site7' ),
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . esc_html( 'Additional information:', 'watchman-site7' ) . '</strong></p>' .
				'<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' . esc_html( 'page the WordPress repository', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_api_doc . '" target="_blank">' . esc_html( 'API Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="' . $url_user_doc . '" target="_blank">' . esc_html( 'User Documentation', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.adminkov.bcr.by" target="_blank">' . esc_html( 'home page support plugin', 'watchman-site7' ) . '</a></p>' .
				'<p><a href="https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt" target="_blank">' . esc_html( 'training video', 'watchman-site7' ) . '</a></p>'
			);
			return current_user_can( 'manage_options' );
		}
		$table = new wms7_List_Table();
	}

	/**
	 * Add custom screen settings of plugin.
	 *
	 * @param string $status status.
	 * @param string $args option.
	 */
	public function wms7_screen_settings_add( $status, $args ) {

		$custom_fieds = '';
		if ( 'toplevel_page_wms7_visitors' === $args->base ) {

			$val          = get_option( 'wms7_screen_settings' );
			$setting_list = checked( 1, isset( $val['setting_list'] ) ? $val['setting_list'] : 0, false );
			$history_list = checked( 1, isset( $val['history_list'] ) ? $val['history_list'] : 0, false );
			$robots_list  = checked( 1, isset( $val['robots_list'] ) ? $val['robots_list'] : 0, false );
			$black_list   = checked( 1, isset( $val['black_list'] ) ? $val['black_list'] : 0, false );

			$banner1 = checked( 1, isset( $val['banner1'] ) ? $val['banner1'] : 0, false );
			$banner2 = checked( 1, isset( $val['banner2'] ) ? $val['banner2'] : 0, false );
			$banner3 = checked( 1, isset( $val['banner3'] ) ? $val['banner3'] : 0, false );

			$all_link        = checked( 1, isset( $val['all_link'] ) ? $val['all_link'] : 0, false );
			$unlogged_link   = checked( 1, isset( $val['unlogged_link'] ) ? $val['unlogged_link'] : 0, false );
			$successful_link = checked( 1, isset( $val['successful_link'] ) ? $val['successful_link'] : 0, false );
			$failed_link     = checked( 1, isset( $val['failed_link'] ) ? $val['failed_link'] : 0, false );
			$robots_link     = checked( 1, isset( $val['robots_link'] ) ? $val['robots_link'] : 0, false );
			$blacklist_link  = checked( 1, isset( $val['blacklist_link'] ) ? $val['blacklist_link'] : 0, false );

			$index_php     = checked( 1, isset( $val['index_php'] ) ? $val['index_php'] : 0, false );
			$robots_txt    = checked( 1, isset( $val['robots_txt'] ) ? $val['robots_txt'] : 0, false );
			$htaccess      = checked( 1, isset( $val['htaccess'] ) ? $val['htaccess'] : 0, false );
			$wp_config_php = checked( 1, isset( $val['wp_config_php'] ) ? $val['wp_config_php'] : 0, false );
			$wp_cron       = checked( 1, isset( $val['wp_cron'] ) ? $val['wp_cron'] : 0, false );
			$statistic     = checked( 1, isset( $val['statistic'] ) ? $val['statistic'] : 0, false );
			$mail          = checked( 1, isset( $val['mail'] ) ? $val['mail'] : 0, false );
			$console       = checked( 1, isset( $val['console'] ) ? $val['console'] : 0, false );

			$legend_panel_info = esc_html( 'Display panel info', 'watchman-site7' );
			$lbl_setting_list  = esc_html( 'Setting list', 'watchman-site7' );
			$lbl_history_list  = esc_html( 'History list', 'watchman-site7' );
			$lbl_robots_list   = esc_html( 'Robots list', 'watchman-site7' );
			$lbl_black_list    = esc_html( 'Black list', 'watchman-site7' );

			$legend_banners = esc_html( 'Hide advertising banners', 'watchman-site7' );
			$lbl_banner1    = esc_html( 'Union State', 'watchman-site7' );
			$lbl_banner2    = esc_html( 'WatchMan-Site7', 'watchman-site7' );
			$lbl_banner3    = esc_html( 'PluginTests', 'watchman-site7' );

			$legend_filters_level2 = esc_html( 'Display filters level II', 'watchman-site7' );
			$lbl_all_link          = esc_html( 'All visits', 'watchman-site7' );
			$lbl_unlogged_link     = esc_html( 'Unlogged visits', 'watchman-site7' );
			$lbl_successful_link   = esc_html( 'Success visits', 'watchman-site7' );
			$lbl_failed_link       = esc_html( 'Failed visits', 'watchman-site7' );
			$lbl_robots_link       = esc_html( 'Robots visits', 'watchman-site7' );
			$lbl_blacklist_link    = esc_html( 'Black list', 'watchman-site7' );

			$legend_button_bottom = esc_html( 'Display buttons add functions of bottom screen', 'watchman-site7' );
			$lbl_index            = esc_html( 'index', 'watchman-site7' );
			$lbl_robots           = esc_html( 'robots', 'watchman-site7' );
			$lbl_htaccess         = esc_html( 'htaccess', 'watchman-site7' );
			$lbl_wp_config        = esc_html( 'wp-config', 'watchman-site7' );
			$lbl_wp_cron          = esc_html( 'wp-cron', 'watchman-site7' );
			$lbl_statistic        = esc_html( 'statistic', 'watchman-site7' );
			$lbl_mail             = esc_html( 'sma', 'watchman-site7' );
			$lbl_console          = esc_html( 'console', 'watchman-site7' );

			$custom_fieds = "
			<fieldset class='banners-screen-setting'>
				<legend>$legend_banners</legend>

				<input type='checkbox' id='banner1' name='wms7_screen_settings[banner1]' value='1' $banner1 />
				<label for='banner1'>$lbl_banner1</label>

				<input type='checkbox' id='banner2' name='wms7_screen_settings[banner2]' value='1' $banner2 />
				<label for='banner2'>$lbl_banner2</label>

				<input type='checkbox' id='banner3' name='wms7_screen_settings[banner3]' value='1' $banner3 />
				<label for='banner3'>$lbl_banner3</label>				
			</fieldset>			
			<fieldset class='panel-info-screen-setting'>
				<legend>$legend_panel_info</legend>
				
				<input type='checkbox' id='setting_list' name='wms7_screen_settings[setting_list]' value='1' $setting_list />
				<label for='setting_list'>$lbl_setting_list</label>

				<input type='checkbox' id='history_list' name='wms7_screen_settings[history_list]' value='1' $history_list />
				<label for='history_list'>$lbl_history_list</label>

				<input type='checkbox' id='robots_list' name='wms7_screen_settings[robots_list]' value='1' $robots_list />
				<label for='robots_list'>$lbl_robots_list</label>

				<input type='checkbox' id='black_list' name='wms7_screen_settings[black_list]' value='1' $black_list />
				<label for='black_list'>$lbl_black_list</label>
			</fieldset>
			<fieldset style='border: 1px solid black; padding: 0 10px;'>
				<legend>$legend_filters_level2</legend>

				<input type='checkbox' id='all_link' name='wms7_screen_settings[all_link]' value='1' $all_link />
				<label for='all_link'>$lbl_all_link</label>

				<input type='checkbox' id='unlogged_link' name='wms7_screen_settings[unlogged_link]' value='1' $unlogged_link />
				<label for='unlogged_link'>$lbl_unlogged_link</label>

				<input type='checkbox' id='successful_link' name='wms7_screen_settings[successful_link]' value='1' $successful_link />
				<label for='successful_link'>$lbl_successful_link</label>

				<input type='checkbox' id='failed_link' name='wms7_screen_settings[failed_link]' value='1' $failed_link />
				<label for='failed_link'>$lbl_failed_link</label>

				<input type='checkbox' id='robots_link' name='wms7_screen_settings[robots_link]' value='1' $robots_link />
				<label for='robots_link'>$lbl_robots_link</label>

				<input type='checkbox' id='blacklist_link' name='wms7_screen_settings[blacklist_link]' value='1' $blacklist_link />
				<label for='blacklist_link'>$lbl_blacklist_link</label>
			</fieldset>
			<fieldset style='border: 1px solid black; padding: 0 10px;'>
				<legend>$legend_button_bottom</legend>

				<input type='checkbox' id='index_php' name='wms7_screen_settings[index_php]' value='1' $index_php />
				<label for='index_php'>$lbl_index</label>

				<input type='checkbox' id='robots_txt' name='wms7_screen_settings[robots_txt]' value='1' $robots_txt />
				<label for='robots_txt'>$lbl_robots</label>

				<input type='checkbox' id='htaccess' name='wms7_screen_settings[htaccess]' value='1' $htaccess />
				<label for='htaccess'>$lbl_htaccess</label>

				<input type='checkbox' id='wp_config_php' name='wms7_screen_settings[wp_config_php]' value='1' $wp_config_php />
				<label for='wp_config_php'>$lbl_wp_config</label>

				<input type='checkbox' id='wp_cron' name='wms7_screen_settings[wp_cron]' value='1' $wp_cron />
				<label for='wp_cron'>$lbl_wp_cron</label>

				<input type='checkbox' id='statistic' name='wms7_screen_settings[statistic]' value='1' $statistic />
				<label for='statistic'>$lbl_statistic</label>

				<input type='checkbox' id='mail' name='wms7_screen_settings[mail]' value='1' $mail />
				<label for='mail'>$lbl_mail</label>

				<input type='checkbox' id='console' name='wms7_screen_settings[console]' value='1' $console />
				<label for='console'>$lbl_console</label>				
			</fieldset>
			";
		}
		return $status . $custom_fieds;
	}
	/**
	 * Create and save screen settings of plugin.
	 *
	 * @param string  $status status.
	 * @param string  $option option.
	 * @param integer $value value.
	 */
	public function wms7_screen_settings_save( $status, $option, $value ) {
		$_wms7_screen_settings = filter_input( INPUT_POST, 'wms7_screen_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( $_wms7_screen_settings ) {
			foreach ( $_wms7_screen_settings as $key => $wms7_value ) {
				$_wms7_screen_settings[ $key ] = sanitize_text_field( $wms7_value );
			}
			update_option( 'wms7_screen_settings', $_wms7_screen_settings );
		} else {
			update_option( 'wms7_screen_settings', null );
		}
		update_option( 'wms7_visitors_per_page', sanitize_option( $option, $value ) );
	}
	/**
	 * Filter data about visits by role or time or country.
	 */
	private function wms7_role_time_country_filter() {
		global $wpdb;
		$_filter_role    = filter_input( INPUT_GET, 'filter_role', FILTER_SANITIZE_STRING );
		$_filter_time    = filter_input( INPUT_GET, 'filter_time', FILTER_SANITIZE_STRING );
		$_filter_country = filter_input( INPUT_GET, 'filter_country', FILTER_SANITIZE_STRING );
		// create $option_role.
		$cache_key = 'wms7_role_time_country_filter_part1';
		if ( wp_using_ext_object_cache() ) {
			$results1 = wp_cache_get( $cache_key );
		} else {
			$results1 = get_option( $cache_key );
		}
		if ( ! $results1 ) {
			$results1 = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT DISTINCT user_role
					FROM {$wpdb->prefix}watchman_site
					WHERE user_role <> %s
					ORDER BY user_role ASC
					",
					''
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results1 );
			} else {
				update_option( $cache_key, $results1 );
			}
		}
		$role_option = '';
		$filter_role = isset( $_filter_role ) ? $_filter_role : false;

		// create $option_date.
		$cache_key = 'wms7_role_time_country_filter_part2';
		if ( wp_using_ext_object_cache() ) {
			$results2 = wp_cache_get( $cache_key );
		} else {
			$results2 = get_option( $cache_key );
		}
		if ( ! $results2 ) {
			$results2 = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT DISTINCT YEAR(time_visit) as %s, MONTH(time_visit) as %s
					FROM {$wpdb->prefix}watchman_site
					ORDER BY YEAR(time_visit), MONTH(time_visit) DESC
					",
					'year',
					'month'
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results2 );
			} else {
				update_option( $cache_key, $results2 );
			}
		}
		$time_option = '';
		$filter_time = isset( $_filter_time ) ? $_filter_time : false;

		// create $option_country.
		$cache_key = 'wms7_role_time_country_filter_part3';
		if ( wp_using_ext_object_cache() ) {
			$results3 = wp_cache_get( $cache_key );
		} else {
			$results3 = get_option( $cache_key );
		}
		if ( ! $results3 ) {
			$results3 = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT DISTINCT LEFT(`country`,%d) as code_country
					FROM {$wpdb->prefix}watchman_site
					ORDER BY country ASC
					",
					4
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results3 );
			} else {
				update_option( $cache_key, $results3 );
			}
		}
		$country_option = '';
		$filter_country = isset( $_filter_country ) ? $_filter_country : false;

		$title1 = esc_html( 'Select role of visitors', 'watchman-site7' );
		$value1 = esc_html( 'Role All', 'watchman-site7' );
		$title2 = esc_html( 'Select time of visits', 'watchman-site7' );
		$value2 = esc_html( 'Time All', 'watchman-site7' );
		$title3 = esc_html( 'Select country of visitors', 'watchman-site7' );
		$value3 = esc_html( 'Country All', 'watchman-site7' );
		$title4 = esc_html( 'Filter  level I, group 1', 'watchman-site7' );
		?>
		<form method="GET">
			<input type="hidden" name="page" value="wms7_visitors" />

			<select name="filter_role" id="filter_role" title="<?php echo esc_html( $title1 ); ?>"><option value="" ><?php echo esc_html( $value1 ); ?></option>
				<?php
				if ( $results1 ) {
					foreach ( $results1 as $row ) {
						?>
						<option value="<?php echo esc_html( $row->user_role ); ?>"<?php	echo esc_html( selected( $row->user_role, $filter_role, false ) ); ?> ><?php echo esc_html( $row->user_role ); ?></option>
						<?php
					}
				}
				?>
			</select>
			<select name="filter_time" id="filter_time" title="<?php echo esc_html( $title2 ); ?>"><option value="" ><?php echo esc_html( $value2 ); ?></option>
				<?php
				if ( $results2 ) {
					foreach ( $results2 as $row ) {
						$time_stamp = mktime( 0, 0, 0, $row->month, 1, $row->year );
						$month      = ( 1 === strlen( $row->month ) ) ? '0' . $row->month : $row->month;
						?>
						<option value="<?php echo esc_html( $row->year ) . esc_html( $month ); ?>"
						<?php
						echo esc_html( selected( $row->year . $month, $filter_time, false ) );
						?>
						><?php echo esc_html( date( 'F', $time_stamp ) ) . ' ' . esc_html( $row->year ); ?></option>
						<?php
					}
				}
				?>
			</select>
			<select name="filter_country" id="filter_country" title="<?php echo esc_html( $title3 ); ?>"><option value="" ><?php echo esc_html( $value3 ); ?></option>
				<?php
				if ( $results3 ) {
					foreach ( $results3 as $row ) {
						?>
						<option value="<?php echo esc_html( $row->code_country ); ?>"<?php	echo esc_html( selected( $row->code_country, $filter_country, false ) ); ?>	><?php	echo esc_html( $row->code_country ); ?></option>
						<?php
					}
				}
				?>
			</select>
			<input class="button" id="doaction1" type="submit" title="<?php echo esc_html( $title4 ); ?>" value="Filter" onClick="wms7_cookie_doaction1()"/>
			<input type='hidden' name='filter_left_nonce' value='<?php echo esc_html( wp_create_nonce( 'filter_left' ) ); ?>'>
		</form>
		<?php
	}
	/**
	 * Filter data about visits by ip or login.
	 */
	private function wms7_login_ip_filter() {
		$_filter = filter_input( INPUT_GET, 'filter', FILTER_SANITIZE_STRING );
		$title1  = esc_html( 'Enter login or visitor IP', 'watchman-site7' );
		$title2  = esc_html( 'Filter level I, group 2', 'watchman-site7' );
		?>
		<form method="GET">
			<input type="hidden" name="page" value="wms7_visitors" />
			<input type="text" id="filter_login_ip" title="<?php echo esc_html( $title1 ); ?>" placeholder = "Login or Visitor IP" name="filter" size="18" value="<?php echo esc_html( $_filter ); ?>" />
			<input class="button" id="doaction3" type="submit" title="<?php echo esc_html( $title2 ); ?>" value="Filter" onClick="wms7_cookie_doaction3()"/>
			<input type='hidden' name='filter_right_nonce' value='<?php echo esc_html( wp_create_nonce( 'filter_right' ) ); ?>'>
		</form>
		<?php
	}
	/**
	 * For access to google api maps.
	 */
	private function wms7_geolocation_visitor() {
		$_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $_id ) {
			return;
		}
		?>
		<div id='map' style='width:660px;height:260px;padding:0;margin:10px;background-color:#D4D0C8;'></div>
		<?php
	}
	/**
	 * Returns data of geolocation visitor of site for popup winndow Map.
	 *
	 * @return array Returns data of geolocation of visitor.
	 */
	private function wms7_geo_wifi() {
		global $wpdb;

		$table = new wms7_List_Table();
		$_id   = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );

		if ( 'map' === $table->current_action() ) {
			// get login.
			$cache_key = 'wms7_geo_wifi_part1';
			if ( wp_using_ext_object_cache() ) {
				$results = wp_cache_get( $cache_key );
			} else {
				$results = get_option( $cache_key );
			}
			if ( ! $results ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"
						SELECT `user_login`
						FROM {$wpdb->prefix}watchman_site
						WHERE `id` = %d
						",
						$_id
					),
					'ARRAY_A'
				);// db call ok; cache ok.
				if ( wp_using_ext_object_cache() ) {
					wp_cache_set( $cache_key, $results );
				} else {
					update_option( $cache_key, $results );
				}
			}
			$login = ( ( '' !== $results[0]['user_login'] ) ) ? array_shift( $results[0] ) : 'unlogged';
			// get ip.
			$cache_key = 'wms7_geo_wifi_part2';
			if ( wp_using_ext_object_cache() ) {
				$results = wp_cache_get( $cache_key );
			} else {
				$results = get_option( $cache_key );
			}
			if ( ! $results ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"
						SELECT `user_ip`
						FROM {$wpdb->prefix}watchman_site
						WHERE `id` = %d
						",
						$_id
					),
					'ARRAY_A'
				);// db call ok; cache ok.
				if ( wp_using_ext_object_cache() ) {
					wp_cache_set( $cache_key, $results );
				} else {
					update_option( $cache_key, $results );
				}
			}
			$ip = array_shift( $results[0] );
			// get coords.
			$cache_key = 'wms7_geo_wifi_part3';
			if ( wp_using_ext_object_cache() ) {
				$results = wp_cache_get( $cache_key );
			} else {
				$results = get_option( $cache_key );
			}
			if ( ! $results ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"
						SELECT `geo_wifi`
						FROM {$wpdb->prefix}watchman_site
						WHERE `id` = %d
						",
						$_id
					),
					'ARRAY_A'
				);// db call ok;no cache ok.
				if ( wp_using_ext_object_cache() ) {
					wp_cache_set( $cache_key, $results );
				} else {
					update_option( $cache_key, $results );
				}
			}
			$results = array_shift( $results[0] );
			$results = explode( '<br>', $results );

			$lat  = 0;
			$lon  = 0;
			$acc  = 0;
			$code = 0;
			$msg  = 0;

			foreach ( $results as $coord ) {
				if ( 0 === strpos( $coord, 'lat_wifi' ) ) {
					$lat = mb_strcut( $coord, 9 );
					if ( '' === $lat ) {
						$lat = 0;
					}
				}
				if ( 0 === strpos( $coord, 'lon_wifi' ) ) {
					$lon = mb_strcut( $coord, 9 );
					if ( '' === $lon ) {
						$lon = 0;
					}
				}
				if ( 0 === strpos( $coord, 'acc_wifi=' ) ) {
					$acc = mb_strcut( $coord, 9 );
					$acc = round( $acc, 2 );
				}
				if ( 0 === strpos( $coord, 'err_code' ) ) {
					$code = mb_strcut( $coord, 9 );
				}
				if ( 0 === strpos( $coord, 'err_msg' ) ) {
					$msg = mb_strcut( $coord, 8 );
				}
			}
			unset( $coord );
		}
		$login = 'login: ' . $login;
		$arr   = array(
			'ID'       => $_id,
			'Login'    => $login,
			'IP'       => $ip,
			'lat_wifi' => $lat,
			'lon_wifi' => $lon,
			'acc_wifi' => $acc,
			'err_code' => $code,
			'err_msg'  => $msg,
		);
		return $arr;
	}
	/**
	 * Returns data of geolocation provider of visitor for popup winndow Map.
	 *
	 * @return array Returns data of geolocation provider of visitor.
	 */
	private function wms7_geo_ip() {
		global $wpdb;

		$table = new wms7_List_Table();
		$_id   = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( 'map' === $table->current_action() ) {
			// provider.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        SELECT `provider` 
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `id` = %s
			        ",
					$_id
				),
				'ARRAY_A'
			);// db call ok;no cache ok.
			$provider = array_shift( $results[0] );
			// get ip.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        SELECT `user_ip` 
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `id` = %s
			        ",
					$_id
				),
				'ARRAY_A'
			);// db call ok;no cache ok.
			$ip      = array_shift( $results[0] );
			// get coords.
			$lat     = '';
			$lon     = '';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        SELECT `geo_ip` 
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `id` = %s
			        ",
					$_id
				),
				'ARRAY_A'
			);// db call ok; no cache ok.
			$results = array_shift( $results[0] );
			$results = explode( '<br>', $results );
			foreach ( $results as $coord ) {
				if ( 0 === strpos( $coord, 'Lat_ip' ) ) {
					$lat = mb_strcut( $coord, 7 );
				}
				if ( 0 === strpos( $coord, 'Lon_ip' ) ) {
					$lon = mb_strcut( $coord, 7 );
				}
			}
		}
		$provider = 'provider: ' . $provider;
		$arr      = array(
			'ID'       => $_id,
			'Provider' => $provider,
			'IP'       => $ip,
			'Lat'      => $lat,
			'Lon'      => $lon,
			'Acc'      => 'Not defined',
			'err_code' => '0',
			'err_msg'  => 'ok',
		);
		return $arr;
	}
	/**
	 * Enable popup window for role administrator only.
	 */
	private function wms7_win_popup() {
		$_footer = filter_input( INPUT_POST, 'footer', FILTER_SANITIZE_STRING );
		switch ( $_footer ) {
			case 'index':
				$str_head = 'index.php';
				$this->wms7_file_editor( $str_head );
				break;
			case 'robots':
				$str_head = 'robots.txt';
				$this->wms7_file_editor( $str_head );
				break;
			case 'htaccess':
				$str_head = '.htaccess';
				$this->wms7_file_editor( $str_head );
				break;
			case 'wp_config':
				$str_head = 'wp-config.php';
				$this->wms7_file_editor( $str_head );
				break;
			case 'wp_cron':
				$str_head = 'wp-cron tasks';
				$this->wms7_wp_cron( $str_head );
				break;
			case 'statistic':
				$str_head = 'statistic of visits';
				$this->wms7_stat( $str_head );
				break;
			case 'sma':
				if ( extension_loaded('imap') ) {
					$val        = get_option( 'wms7_main_settings' );
					$select_box = $val['mail_select'];
					$box        = $val[ $select_box ];
					$str_head   = 'MailBox: ' . $box['mail_box_name'];
					$this->wms7_mail( $str_head );
				}
				break;
			case 'console':
				$str_head = 'WordPress console';
				$this->wms7_console( $str_head );
				break;
		}
	}
	/**
	 * Enable or Disable button 'locate IP' into popup window Map.
	 *
	 * @return string Returns 'disabled' or '' for button in HTML code.
	 */
	private function wms7_ip_enabled() {
		global $wpdb;
		$_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		if ( $_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        SELECT `geo_ip`
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `id` = %s AND `geo_ip` <> %s
			        ",
					$_id,
					''
				),
				'ARRAY_A'
			);// db call ok; no cache ok.
			if ( ! $results ) {
				return 'disabled';
			}
			$geo_ip = array_shift( $results[0] );
			if ( 'none' === $geo_ip ) {
				return 'disabled';
			} else {
				return '';
			}
		}
	}
	/**
	 * Enable or Disable button 'locate wi-fi' into popup window Map.
	 *
	 * @return string Returns 'disabled' or '' for button in HTML code.
	 */
	private function wms7_wifi_enabled() {
		global $wpdb;
		$_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		if ( $_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
			        SELECT `geo_wifi`
			        FROM {$wpdb->prefix}watchman_site
			        WHERE `id` = %s AND `geo_wifi` <> %s
			        ",
					$_id,
					''
				),
				'ARRAY_A'
			);// db call ok; no cache ok.
			if ( 0 === count( $results ) ) {
				return 'disabled';
			} else {
				return '';
			}
		}
	}
	/**
	 * Create modal window for map of geolocation of visitor.
	 */
	private function wms7_map() {
		$img1           = plugins_url( '/images/screw.png', __FILE__ );
		$geo_ip         = $this->wms7_geo_ip();
		$btn_ip_enabled = $this->wms7_ip_enabled();

		$provider = '"' . str_replace( '"', '',$geo_ip['Provider'] ) . '"';
		$lat      = $geo_ip['Lat'];
		$lon      = $geo_ip['Lon'];
		$acc      = '"' . $geo_ip['Acc'] . '"';
		$err_code = $geo_ip['err_code'];
		$err_msg  = '"' . str_replace( "'", '', stripcslashes( $geo_ip['err_msg'] ) ) . '"';

		$geo_wifi         = $this->wms7_geo_wifi();
		$btn_wifi_enabled = $this->wms7_wifi_enabled();

		$login         = '"' . $geo_wifi['Login'] . '"';
		$lat_wifi      = $geo_wifi['lat_wifi'];
		$lon_wifi      = $geo_wifi['lon_wifi'];
		$acc_wifi      = '"' . $geo_wifi['acc_wifi'] . '"';
		$err_code_wifi = $geo_wifi['err_code'];
		$err_msg_wifi  = '"' . str_replace( "'", '', stripcslashes( $geo_wifi['err_msg'] ) ) . '"';

		?>
		<div class='win-popup'>
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( 'Geolocation visitor of site', 'watchman-site7' ); ?> ip=<?php echo esc_html( $geo_ip['IP'] ); ?> (id=<?php echo esc_html( $geo_ip['ID'] ); ?>)
					</h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<?php echo esc_html( $this->wms7_geolocation_visitor() ); ?>
				<div class='popup-footer'>
					<input type='submit' value='locate IP' id='get_location' class='button-primary' name='map_ip' <?php echo esc_html( $btn_ip_enabled ); ?>
					onClick='wms7_initMap(<?php echo esc_html( $provider ) . ',' . esc_html( $lat ) . ',' . esc_html( $lon ) . ',' . esc_html( $acc ) . ',' . esc_html( $err_code ) . ',' . esc_html( $err_msg ); ?> )'>
					<input type='submit' value='locate WiFi' id='get_location' class='button-primary' name='map_wifi' <?php echo esc_html( $btn_wifi_enabled ); ?>
					onClick='wms7_initMap(<?php echo esc_html( $login ) . ',' . esc_html( $lat_wifi ) . ',' . esc_html( $lon_wifi ) . ',' . esc_html( $acc_wifi ) . ',' . esc_html( $err_code_wifi ) . ',' . esc_html( $err_msg_wifi ); ?> )'>
					<div id='lat' style='margin:-32px 0 2px 200px; width:220px;'>
						<label>Latitude: Not defined</label>
					</div>
					<div id='lon' style='margin:-5px 0 2px 200px; width:220px;'>
						<label>Longitude: Not defined</label>
					</div>
					<div id='acc' style='margin:-37px 0 2px 420px;'>
						<label>Accuracy: Not defined</label>
					</div>
					<div id='err' style='margin-top:20px;'>Message:
						<label></label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Create modal window for control console PHP.
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_console( $str_head ) {
		$img1 = plugins_url( '/images/screw.png', __FILE__ );
		$img2 = plugins_url( '/images/screw_l.png', __FILE__ );
		$img3 = plugins_url( '/images/screw_r.png', __FILE__ );
		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>    
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='popup_win' method='POST'>
					<div class='popup-body'>
						<div style='width: 660px; height: 330px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;'>
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 365px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 365px;left: 96%;z-index:2;">						
							<div id="wms7_console">
								<script language="javascript">window.onload = wms7_console()</script>
							</div>
						</div>
					</div>
					<div class='popup-footer' style='margin: -10px 0 0 10px;'>
						<h4 style='margin-top:12px;'>Use: "?" for help menu</h4>
					</div>
				</form> 
			</div>
		</div>
		<?php
	}
	/**
	 * Create modal window for create mail new.
	 *
	 * @param string $str_head Head of modal window.
	 * @param string $draft    Letter - answer or draft.
	 */
	private function wms7_mail_new( $str_head, $draft ) {
		$img1             = plugins_url( '/images/screw.png', __FILE__ );
		$_uid             = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_STRING );
		$_msgno           = filter_input( INPUT_GET, 'msgno', FILTER_SANITIZE_STRING );
		$_mailbox         = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
		$_mailbox         = isset( $_mailbox ) ? wms7_mail_folder_name( $_mailbox ) : 'none';
		$_mail_view_reply = filter_input( INPUT_POST, 'mail_view_reply', FILTER_SANITIZE_STRING );

		if ( $draft && ! $_mail_view_reply ) {
			$str_head = $str_head . " ($_mailbox id=$_msgno)";
			$header   = wms7_mail_header( $_msgno );

			$subject = iconv_mime_decode( $header['subject'], 0, 'UTF-8' );
			$to      = iconv_mime_decode( $header['to'], 0, 'UTF-8' );

			$arr  = wms7_mail_body( $_msgno );
			$body = $arr[0];
		} elseif ( $_uid ) {
				$str_head = $str_head . ' (message for ' . get_user_option( 'user_login', $_uid ) . ')';
				$subject  = 'Message for ' . get_user_option( 'user_login', $_uid );
				$to       = get_user_option( 'user_email', $_uid );
				$body     = '';
				$arr[1]   = array();
		} elseif ( $_mail_view_reply ) {
				$str_head = $str_head . " (reply to $_mailbox id=$_msgno)";
				$header   = wms7_mail_header( $_msgno );

				$subject = 'Re: ' . iconv_mime_decode( $header['subject'], 0, 'UTF-8' );
				$to      = iconv_mime_decode( $header['from'], 0, 'UTF-8' );

				$arr  = wms7_mail_body( $_msgno );
				$body = 'Please enter a response here' . PHP_EOL . '-------------------------------' . PHP_EOL . $arr[0];
		} else {
			$str_head = $str_head . ' (new letter)';
			$subject  = '';
			$to       = '';
			$body     = '';
			$arr[1]   = array();
		}
		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='popup_mail_view' method='POST' enctype='multipart/form-data'>
					<label style='margin-left:10px;font-weight:bold;'><big>Subject: </label></big>
					<input type='text' placeholder = 'Subject' style='width: 590px; margin-left: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;text-overflow:ellipsis;' name='mail_new_subject' value='<?php echo esc_html( $subject ); ?>' ><br>
					<label style='margin-left:10px;font-weight:bold;'><big>To: </label></big>
					<input type='text' placeholder = 'user name <mail@address>' size='18' style='width: 590px; margin-left: 40px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;' name='mail_new_to' value='<?php echo esc_html( $to ); ?>' >
					<div class='popup-body-mail' style='margin-top: -5px;'>
						<textarea name='mail_new_content'><?php echo esc_textarea( $body ); ?></textarea>
					</div>
					<div class='popup-footer'>
						<input type='submit' value='save' id='submit' class='button-primary' name='mail_new_save'>
						<input type='submit' value='send' id='submit' class='button-primary' name='mail_new_send'>
						<input type='submit' value='quit' id='submit' class='button-primary' name='mail_new_quit'>
						<input type='file' id='file_attach' name='mail_new_attach' accept='.zip'>
						<?php
						$_http_host = filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_STRING );
						$item       = get_option( 'wms7_main_settings' );
						$path_tmp   = $item['mail_box_tmp'] . '/';
						$path       = 'http://' . $_http_host . $path_tmp;
						$icon_path  = plugins_url( '/images/attachment.png', __FILE__ );
						foreach ( $arr[1] as $attach ) {
							$filename = $attach['filename'];
							?>
							<a class='alignright' href='<?php echo esc_url( $path . $filename ); ?>' download><big><?php echo esc_html( $filename ); ?></big></a><img src='<?php echo esc_url( $icon_path ); ?>' class='alignright' style='padding:0;'>
							<?php
						}
						?>
					</div>
					<input type='hidden' name='mail_new_nonce' value='<?php echo esc_html( wp_create_nonce( 'mail_new_nonce' ) ); ?>'>
				</form>
			</div>
		</div>
		<?php
	}
	/**
	 * Create modal window for mail view.
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_mail_view( $str_head ) {
		$_nonce = filter_input( INPUT_GET, 'mail_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'mail_nonce' ) ) {
			$img1            = plugins_url( '/images/screw.png', __FILE__ );
			$_mail_view_code = filter_input( INPUT_POST, 'mail_view_code', FILTER_SANITIZE_STRING );
			$_msgno          = filter_input( INPUT_GET, 'msgno', FILTER_SANITIZE_STRING );
			$_box_name       = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
			$_box_name       = wms7_mail_folder_name( $_box_name );
			$header          = wms7_mail_header( $_msgno );
			$subject         = iconv_mime_decode( $header['subject'], 0, 'UTF-8' );
			$from            = iconv_mime_decode( $header['from'], 0, 'UTF-8' );
			$to              = iconv_mime_decode( $header['to'], 0, 'UTF-8' );
			$date            = $header['date'];
			$arr             = wms7_mail_body( $_msgno );
			$body            = $arr[0];
			?>
			<div class='win-popup'>
				<label class='btn' for='win-popup'></label>
				<input type='checkbox' style='display: none;' checked>
				<div class='popup-content'>
					<div class='popup-header'>
						<h2><?php echo esc_html( $str_head . ' (' . $_box_name . ' id=' . $_msgno . ')' ); ?></h2>
						<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
						<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
					</div>
					<form id='popup_mail_view' method='POST'>
						<label style='margin-left:10px;font-weight:bold;'><big>Subject: </label><?php echo esc_html( $subject ); ?></big><br>
						<label style='margin-left:10px;font-weight:bold;width:600px;'><big>From: </label><?php echo esc_html( $from ); ?></big><br>
						<label style='margin-left:10px;font-weight:bold;'><big>To: </label><?php echo esc_html( $to ); ?></big><br>
						<label style='margin-left:10px;font-weight:bold;'><big>Date: </label><?php echo esc_html( $date ); ?></big><br>
						<div class='popup-body-mail'>
							<?php
							if ( ( $_mail_view_code ) && ( 'text' === $_mail_view_code ) ) {
								$code = 'html';
								?>
								<textarea name='content' style='height:200px'><?php echo esc_textarea( $body ); ?></textarea>
								<?php
							} else {
								$code = 'text';
								?>
								<div id='basis'><?php echo filter_var( $body ); ?></div>
								<?php
							}
							?>
						</div>
						<div class='popup-footer'>
							<input type='submit' value='reply' id='submit' class='button-primary' name='mail_view_reply'/>
							<input type='submit' value='quit' id='submit' class='button-primary' name='mail_view_quit' onClick='wms7_quit_btn()'/>
							<input type='submit' value='<?php echo esc_html( $code ); ?>' id='submit' class='button-primary' name='mail_view_code'/>
							<?php
							$_http_host = filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_STRING );
							$item       = get_option( 'wms7_main_settings' );
							$path_tmp   = $item['mail_box_tmp'] . '/';
							$path       = 'http://' . $_http_host . $path_tmp;
							$icon_path  = plugins_url( '/images/attachment.png', __FILE__ );
							foreach ( $arr[1] as $attach ) {
								$filename = $attach['filename'];
								?>
								<a class='alignright' href='<?php echo esc_url( $path . $filename ); ?>' download><big><?php echo esc_html( $filename ); ?></big></a><img src='<?php echo esc_url( $icon_path ); ?>' class='alignright' style='padding:0;'>
								<?php
							}
							?>
						</div>
						<input type='hidden' name='mail_view_nonce' value='<?php echo esc_html( wp_create_nonce( 'mail_view_nonce' ) ); ?>'>
					</form>  
				</div>
			</div>
			<?php
		}
	}
	/**
	 * Create modal window for control mailboxes.
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_mail( $str_head ) {
		$img1            = plugins_url( '/images/screw.png', __FILE__ );
		$img2            = plugins_url( '/images/screw_l.png', __FILE__ );
		$img3            = plugins_url( '/images/screw_r.png', __FILE__ );
		$_mailbox        = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
		$_search_context = filter_input( INPUT_POST, 'mail_search_context', FILTER_SANITIZE_STRING );
		$_mail_search    = filter_input( INPUT_POST, 'mail_search', FILTER_SANITIZE_STRING );
		$val             = get_option( 'wms7_main_settings' );
		$select_box      = $val['mail_select'];
		$box             = $val[ $select_box ];
		$folders         = explode( ';', $box['mail_folders'] );
		$folder0         = '';
		$folder1         = '';
		$folder2         = '';
		$folder3         = '';
		$url             = get_option( 'wms7_current_url' );
		$mailbox_nonce   = wp_create_nonce( 'mailbox_nonce' );

		if ( '' !== $folders[0] ) {
			$folders[0] = str_replace( ' ', '', $folders[0] );
			$folder0    = substr( $folders[0], strpos( $folders[0], '}' ) + 1 );
		}
		if ( '' !== $folders[1] ) {
			$folders[1] = str_replace( ' ', '', $folders[1] );
			$folder1    = substr( $folders[1], strpos( $folders[1], '}' ) + 1 );
		}
		if ( '' !== $folders[2] ) {
			$folders[2] = str_replace( ' ', '', $folders[2] );
			$folder2    = substr( $folders[2], strpos( $folders[2], '}' ) + 1 );
		}
		if ( '' !== $folders[3] ) {
			$folders[3] = str_replace( ' ', '', $folders[3] );
			$folder3    = substr( $folders[3], strpos( $folders[3], '}' ) + 1 );
		}
		$mail_box_selector = wms7_mailbox_selector();
		$context           = ( $_search_context ) ? $_search_context : '';
		if ( $_mail_search ) {
			$arr        = wms7_mail_search();
			$mail_table = $arr[0];
			$str_head   = $str_head . ' (found:' . $arr[1] . ')';
		} else {
			$mail_table = wms7_mail_inbox();
		}
		if ( ! $_mailbox ) {
			if ( 'INBOX' === strtoupper( $folder0 ) ) {
				$_mailbox = 'folder1';
			} elseif ( 'INBOX' === strtoupper( $folder1 ) ) {
				$_mailbox = 'folder2';
			} elseif ( 'INBOX' === strtoupper( $folder2 ) ) {
				$_mailbox = 'folder3';
			} elseif ( 'INBOX' === strtoupper( $folder3 ) ) {
				$_mailbox = 'folder4';
			}
		}
		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>    
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='mailbox' method='POST'>
					<div style='position:relative; margin: -5px 0 10px 15px;'>
						<input class='radio' type='radio' id='folder1' name='radio_mail' value=<?php echo esc_html( $mail_box_selector[0]['name'] ); ?> onclick='wms7_mailbox_select(id, "<?php echo esc_html( $mailbox_nonce ); ?>")'/>
						<label for='folder1' style='color:black;'><?php echo esc_html( $mail_box_selector[0]['name'] ); ?><?php echo esc_html( $mail_box_selector[0]['count'] ); ?></label>
						<input class='radio' type='radio' id='folder2' name='radio_mail' value=<?php echo esc_html( $mail_box_selector[1]['name'] ); ?> onclick='wms7_mailbox_select(id, "<?php echo esc_html( "$mailbox_nonce" ); ?>")'/>
						<label for='folder2' style='color:black;'><?php echo esc_html( $mail_box_selector[1]['name'] ); ?><?php echo esc_html( $mail_box_selector[1]['count'] ); ?></label>
						<input class='radio' type='radio' id='folder3' name='radio_mail' value=<?php echo esc_html( $mail_box_selector[2]['name'] ); ?> onclick='wms7_mailbox_select(id, "<?php echo esc_html( "$mailbox_nonce" ); ?>")'/>
						<label for='folder3' style='color:black;'><?php echo esc_html( $mail_box_selector[2]['name'] ); ?><?php echo esc_html( $mail_box_selector[2]['count'] ); ?></label>
						<input class='radio' type='radio' id='folder4' name='radio_mail' value=<?php echo esc_html( $mail_box_selector[3]['name'] ); ?> onclick='wms7_mailbox_select(id, "<?php echo esc_html( "$mailbox_nonce" ); ?>")'/>
						<label for='folder4' style='color:black;'><?php echo esc_html( $mail_box_selector[3]['name'] ); ?><?php echo esc_html( $mail_box_selector[3]['count'] ); ?></label>
						<input type='submit' value='search' id='doaction4' class='button alignright' name='mail_search' style='position:relative;float:right;margin-right: 10px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;' />
						<input type='text' class='text alignright' placeholder = 'context' size='18' style='width: 80px; margin-right: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;' name='mail_search_context' value='<?php echo esc_html( $context ); ?>' >
					</div>
					<div class='popup-body'>
						<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 90px;left: 15px;z-index:2;">
						<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 320px;left: 15px;z-index:2;">
						<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 90px;left: 95%;z-index:2;">
						<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 320px;left: 95%;z-index:2;">						
						<table class='table' style='margin-left:10px;'>
							<tr class='tr'>
								<thead class='thead' style='margin-left:20px;width:610px;'>
									<th class='td' width='10%' style='cursor: pointer;'>id</th>
									<th class='td' width='30%' style='cursor: pointer;'>Date</th>
									<th class='td' width='30%' style='cursor: pointer;'>From</th>
									<th class='td' width='30%' style='cursor: pointer;'>Subject</th>
								</thead>
							</tr>
							<tbody class="tbody" style= "margin-left:20px;height:190px;max-height:190px;width:630px;">
							<?php
							foreach ( $mail_table as $item ) {
								$msgno = $item['msgno'];
								$i     = 0;
								if ( 0 === $item['seen'] ) {
									?>
										<tr class='tr' style='background-color: #75A3FF;'> 
										<?php
								} else {
									?>
										<tr class='tr'>
									<?php
								}
								foreach ( $item as $key => $value ) {
									switch ( $i ) {
										case 0:
											?>
											<td class='td' width='10%'><input type='checkbox' name=<?php echo esc_html( $msgno ); ?> value='mail_number'><?php echo esc_html( $value ); ?></td>
											<?php
											break;
										case 1:
											?>
											<td class='td' width='30%'><?php echo esc_html( $value ); ?></td>
											<?php
											break;
										case 2:
											?>
											<td class='td' width='30%'><?php echo esc_html( $value ); ?></td>
												<?php
											break;
										case 3:
											?>
											<td class='td' width='30%'><a href="<?php echo esc_html(wp_nonce_url( $url, 'mail_nonce', 'mail_nonce' ) ); ?>&msgno=<?php echo esc_html( $msgno ); ?>&mailbox=<?php echo esc_html( $_mailbox ); ?>"><?php echo esc_html( $value ); ?></a></td>
												<?php
											break;
									}
									$i++;
								}
								?>
									</tr>
									<?php
							}
							?>
							</tbody>
							<tr class='tr'>
								<tfoot class='tfoot' style='margin-left:20px;width:610px;'>
									<th class='td' width='10%' style='cursor: pointer;'>id</th>
									<th class='td' width='30%' style='cursor: pointer;'>Date</th>
									<th class='td' width='30%' style='cursor: pointer;'>From</th>
									<th class='td' width='30%' style='cursor: pointer;'>Subject</th>
								</tfoot>
							</tr>
						</table>
					</div>
					<div class='popup-footer'>
						<input type='submit' value='delete' id='mail_delete' class='button-primary' name='mail_delete'/>
						<input type='submit' value='new' id='mail_new' class='button-primary' name='mail_new'/>
						<input type='submit' value='move' id='doaction5' class='button alignright' name='mail_move' style='-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin: 0 0 5px 0;'/>
						<select name='move_box' class='text alignright' style='width: 80px; margin-right: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;'><option value='<?php echo esc_html( $folder0 ); ?>'><?php echo esc_html( $folder0 ); ?></option><option value='<?php echo esc_html( $folder1 ); ?>'><?php echo esc_html( $folder1 ); ?></option><option value='<?php echo esc_html( $folder2 ); ?>'><?php echo esc_html( $folder2 ); ?></option><option value='<?php echo esc_html( $folder3 ); ?>'><?php echo esc_html( $folder3 ); ?></option></select>
					</div>
					<input type='hidden' name='mailbox_nonce' value='<?php echo esc_html( $mailbox_nonce ); ?>'>
				</form> 
			</div>
		</div>  
		<?php
	}

	/**
	 * Create modal window for control statistic of visits.
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_stat( $str_head ) {
		$img1            = plugins_url( '/images/screw.png', __FILE__ );
		$img2            = plugins_url( '/images/screw_l.png', __FILE__ );
		$img3            = plugins_url( '/images/screw_r.png', __FILE__ );
		$all_total       = Wms7_List_Table::wms7_get( 'allTotal' );
		$visits_total    = Wms7_List_Table::wms7_get( 'visitsTotal' );
		$success_total   = Wms7_List_Table::wms7_get( 'successTotal' );
		$failed_total    = Wms7_List_Table::wms7_get( 'failedTotal' );
		$robots_total    = Wms7_List_Table::wms7_get( 'robotsTotal' );
		$blacklist_total = Wms7_List_Table::wms7_get( 'blacklistTotal' );
		$where           = Wms7_List_Table::wms7_get( 'where' );
		$_stat_table     = filter_input( INPUT_POST, 'stat_table', FILTER_SANITIZE_STRING );
		$_stat_graph     = filter_input( INPUT_POST, 'stat_graph', FILTER_SANITIZE_STRING );
		$_graph_type     = filter_input( INPUT_POST, 'graph_type', FILTER_SANITIZE_STRING );

		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>    
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='popup_win' method='POST'>
					<div style='position:relative; float:left; margin: -5px 0 10px 10px;'>
						<input class='radio' type='radio' id='visits' name='radio_stat' value='visits' onClick='wms7_stat_btn()'/>
						<label for='visits' style='color:black;'><?php echo esc_html( 'Visits All', 'watchman-site7' ); ?>(<?php echo esc_html( $all_total ); ?>)</label>
						<input class='radio' type='radio' id='unlogged' name='radio_stat' value='unlogged' onClick='wms7_stat_btn()'/>
						<label for='unlogged' style='color:black;'><?php echo esc_html( 'Unlogged', 'watchman-site7' ); ?>(<?php echo esc_html( $visits_total ); ?>)</label>
						<input class='radio' type='radio' id='success' name='radio_stat' value='success' onClick='wms7_stat_btn()'/>
						<label for='success' style='color:black;'><?php echo esc_html( 'Success', 'watchman-site7' ); ?>(<?php echo esc_html( $success_total ); ?>)</label>
						<input class='radio' type='radio' id='failed' name='radio_stat' value='failed' onClick='wms7_stat_btn()'/>
						<label for='failed' style='color:black;'><?php echo esc_html( 'Failed', 'watchman-site7' ); ?>(<?php echo esc_html( $failed_total ); ?>)</label>
						<input class='radio' type='radio' id='robots' name='radio_stat' value='robots' onClick='wms7_stat_btn()'/>
						<label for='robots' style='color:black;'><?php echo esc_html( 'Robots', 'watchman-site7' ); ?>(<?php echo esc_html( $robots_total ); ?>)</label> 
						<input class='radio' type='radio' id='blacklist' name='radio_stat' value='blacklist' onClick='wms7_stat_btn()'/>
						<label for='blacklist' style='color:black;'><?php echo esc_html( 'Black List', 'watchman-site7' ); ?>(<?php echo esc_html( $blacklist_total ); ?>)</label>
					</div>
					<div class='popup-body' style="overflow-x: scroll;margin-left:10px;margin-right:10px;width: 660px;">
						<div style='width: 660px; height: 260px; padding: 5px 0 0 0; margin:0; background-color: #D4D0C8;'>
							<?php
							if ( $_stat_table ) {
								$records = wms7_create_table_stat( $where );
								?>
								<table class="table" style="margin-left:10px;max-width:640px;">
									<thead class="thead">
										<tr class="tr">
								<?php
								$i = 0;
								if ( ! empty( $records ) ) {
									foreach ( $records[0] as $key => $value ) {
										if ( 0 === $i ) {
											?>
											<th class='td' width='90px;'><?php echo esc_html( $key ); ?></th>
											<?php
										} else {
											?>
											<th class='td' width='30px;'><?php echo esc_html( $key ); ?></th>
											<?php
										}
										$i++;
									}
								}
								?>
										</tr>
									</thead>
									<tbody class="tbody">
								<?php
								if ( ! empty( $records ) ) {
									foreach ( $records as $record ) {
										$i = 0;
										?>
										<tr class="tr">
										<?php
										foreach ( $record as $key => $value ) {
											if ( 0 === $i ) {
												?>
													<td class='td' width='90px;'><?php echo esc_html( $value ); ?></td>
													<?php
											} else {
												?>
												<td class='td' width='30px;'><?php echo esc_html( $value ); ?></td>
													<?php
											}
											$i++;
										}
										?>
										</tr>
										<?php
									}
								}
								?>
									</tbody>
									<tfoot class="tfoot">
										<tr class="tr">
								<?php
								$i = 0;
								if ( ! empty( $records ) ) {
									foreach ( $records[0] as $key => $value ) {
										if ( 0 === $i ) {
											?>
										<th class='td' width='90px;'><?php echo esc_html( $key ); ?></th>
											<?php
										} else {
											?>
										<th class='td' width='30px;'><?php echo esc_html( $key ); ?></th>
											<?php
										}
										$i++;
									}
								}
								?>
										</tr>
									</tfoot>								
								</table>	
								<?php
							} else {
								?>
								<div id="dashboard_chart">
									<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 73px;left: 12px;z-index:2;">
									<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 320px;left: 12px;z-index:2;">
									<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 73px;left: 96%;z-index:2;">
									<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 320px;left: 96%;z-index:2;">
									<div style="text-align:center;"><div id="filter_chart"></div></div>
									<div id="piechart" style="margin: 5px;padding:0;width: 650px;height: 218px;position:absolute;">
										<?php
										if ( $_stat_graph ) {
											$records = wms7_create_graph_stat( $where );
											$records = wp_json_encode( $records );
											?>
											<script>wms7_graph_statistic('<?php echo esc_html( $records ); ?>');</script>
											<?php
										}
										?>
									</div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<div class='popup-footer'>
						<input type='submit' value='Table' id='submit' class='button-primary' name='stat_table'>
						<input style="float:right;margin-left:5px;" type='submit' value='Graph' id='submit' class='button-primary' name='stat_graph'>
						<select class='alignright actions' id='graph_type' name='graph_type' form='popup_win'>
							<option value='browser' <?php echo esc_html( selected( 'browser', $_graph_type, false ) ); ?>><?php echo esc_html( 'browser', 'watchman-site7' ); ?></option>
							<option value='device' <?php echo esc_html( selected( 'device', $_graph_type, false ) ); ?>><?php echo esc_html( 'device', 'watchman-site7' ); ?></option>
							<option value='platform' <?php echo esc_html( selected( 'platform', $_graph_type, false ) ); ?>><?php echo esc_html( 'platform', 'watchman-site7' ); ?></option>
						</select>
					</div>
					<input type='hidden' name='stat_nonce' value='<?php echo esc_html( wp_create_nonce( 'stat' ) ); ?>'>
				</form> 
			</div>
		</div>
		<?php
	}
	/**
	 * Create modal window for control of cron events.
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_wp_cron( $str_head ) {
		$img1       = plugins_url( '/images/screw.png', __FILE__ );
		$img2       = plugins_url( '/images/screw_l.png', __FILE__ );
		$img3       = plugins_url( '/images/screw_r.png', __FILE__ );
		$wms7_cron  = new wms7_cron();
		$cron_table = $wms7_cron->wms7_create_cron_table();
		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='popup_win' method='POST'>
					<div class='popup-body'>
						<div style='width: 660px; height: 300px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;'>
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 335px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 335px;left: 96%;z-index:2;">
							<ul class='tasks' style='margin-left: 5%; width: 90%;'>
							<li class = 'tasks' style='color: red;font-weight:bold;'><?php echo esc_html( 'Not found', 'watchman-site7' ); ?> : <?php echo esc_html( $wms7_cron->orphan_count ); ?></li>
							<li class = 'tasks' style='color: blue;font-weight:bold;'><?php echo esc_html( 'Plugin task', 'watchman-site7' ); ?> : <?php echo esc_html( $wms7_cron->plugin_count ); ?></li>
							<li class = 'tasks' style='color: green;font-weight:bold;'><?php echo esc_html( 'Themes task', 'watchman-site7' ); ?> : <?php echo esc_html( $wms7_cron->themes_count ); ?></li>
							<li class = 'tasks' style='color: brown;font-weight:bold;'><?php echo esc_html( 'WP task', 'watchman-site7' ); ?> : <?php echo esc_html( $wms7_cron->wp_count ); ?></li>
							</ul>
							<table class='table'>
								<div class='loader' id='win-loader'></div>
								<thead class='thead'>
									<tr class='tr'>
										<th class='th' width='9%'>id</th>
										<th class='th' width='35%'><?php echo esc_html( 'Task name', 'watchman-site7' ); ?></th>
										<th class='th' width='15%'><?php echo esc_html( 'Recurrence', 'watchman-site7' ); ?></th>
										<th class='th' width='20%'><?php echo esc_html( 'Next run', 'watchman-site7' ); ?></th>
										<th class='th' width='21%'><?php echo esc_html( 'Source task', 'watchman-site7' ); ?></th>
									</tr>
								</thead>
								<tbody class="tbody" style="max-height: 190px;height: 190px;">
								<?php
								$i = 0;
								foreach ( $cron_table as $item ) {
									$i++;
									$val = explode( '|', $item );
									?>
									<tr class="tr">
										<td class="td" width="8%" style="padding-left:5px;">
											<input type="checkbox" name=<?php echo esc_html( $val[0] ); ?> value=cron<?php echo esc_html( $i ); ?> > <?php echo esc_html( $i ); ?>
										</td>
										<td class="td" width="36%">
											<?php echo esc_html( $val[0] ); ?>
										</td>
										<td class="td" width="15%">
											<?php echo esc_html( $val[1] ); ?>
										</td>
										<td class="td" width="20%">
											<?php echo esc_html( $val[2] ); ?>
										</td>
										<td class="td" width="21%" title='<?php echo esc_html( $val[3] ); ?>' >
										<?php
										switch ( $val[5] ) {
											case '':
												?>
												<?php echo esc_html( 'Source task', 'watchman-site7' ); ?></td>
												<?php
												break;
											case 'step1':
												?>
											<span style="color: brown;"><?php echo esc_html( $val[4] ); ?></span></td>
												<?php
												break;
											case 'step2':
												?>
											<span style="color: brown;"><?php echo esc_html( $val[4] ); ?></span></td>
												<?php
												break;
											case 'step3':
												?>
											<span style="color: green;"><?php echo esc_html( $val[4] ); ?></span></td>
												<?php
												break;
											case 'step4':
												?>
											<span style="color: blue;"><?php echo esc_html( $val[4] ); ?></span></td>
												<?php
												break;
											case 'step5':
												?>
											<span style="color: red;"><?php echo esc_html( $val[4] ); ?></span></td>
												<?php
												break;
										}
										?>
									</tr>
									<?php
								}
								?>
								</tbody>
								<tfoot class='tfoot'>
									<tr class='tr'><th class='th' width='9%'>id</th>
										<th class='th' width='35%'><?php echo esc_html( 'Task name', 'watchman-site7' ); ?></th>
										<th class='th' width='15%'><?php echo esc_html( 'Recurrence', 'watchman-site7' ); ?></th>
										<th class='th' width='20%'><?php echo esc_html( 'Next run', 'watchman-site7' ); ?></th>
										<th class='th' width='21%'><?php echo esc_html( 'Source task', 'watchman-site7' ); ?></th>
									</tr>
								</tfoot>
							</table>
						</div>	
					</div>
					<div class='popup-footer'>
						<input type='submit' value='Delete' id='submit' class='button-primary'  name='cron_delete'>
						<input type='submit' value='Refresh' id='submit' class='button-primary'  name='cron_refresh' onClick='wms7_popup_loader()'>
					</div>
					<input type='hidden' name='cron_nonce' value='<?php echo esc_html( wp_create_nonce( 'cron' ) ); ?>'>
				</form>
			</div>
		</div>
		<?php
	}
	/**
	 * Create modal window for files editor (index.php robots.txt .htaccess wp-config.php).
	 *
	 * @param string $str_head Head of modal window.
	 */
	private function wms7_file_editor( $str_head ) {
		WP_Filesystem();
		global $wp_filesystem;

		$img1     = plugins_url( '/images/screw.png', __FILE__ );
		$img2     = plugins_url( '/images/screw_l.png', __FILE__ );
		$img3     = plugins_url( '/images/screw_r.png', __FILE__ );
		$_footer  = filter_input( INPUT_POST, 'footer', FILTER_SANITIZE_STRING );
		$str_body = '';
		if ( ! file_exists( ABSPATH . $str_head ) ) {
			$str_body = 'File not found: ' . ABSPATH . $str_head;
		} else {
			$filename = ABSPATH . $str_head;
			$str_body = $wp_filesystem->get_contents( $filename );
		}
		?>
		<div class='win-popup'>
			<label class='btn' for='win-popup'></label>
			<input type='checkbox' style='display: none;' checked>
			<div class='popup-content'>
				<div class='popup-header'>
					<h2><?php echo esc_html( $str_head ); ?></h2>
					<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
					<label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
				</div>
				<form id='popup_save' method='POST'>
					<div class='popup-body'>
						<div style='width: 660px; height: 300px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;'>
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 335px;left: 12px;z-index:2;">
							<img src="<?php echo esc_html( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
							<img src="<?php echo esc_html( $img2 ); ?>" style="position: absolute;top: 335px;left: 96%;z-index:2;">
							<textarea name='content'><?php echo esc_textarea( $str_body ); ?></textarea>
						</div>
					</div>
					<div class='popup-footer'>
						<input type='submit' value='Save' id='submit' class='button-primary'  name=<?php echo esc_html( $_footer ); ?> >
						<label style='margin: 0;padding: 0;'><?php echo esc_html( ABSPATH ); ?> </label> 
					</div>
					<input type='hidden' name='file_editor_nonce' value='<?php echo esc_html( wp_create_nonce( 'file_editor' ) ); ?>'>
				</form>
			</div>
		</div>
		<?php
	}
	/**
	 * Create main page of plugin.
	 */
	public function wms7_visit_manager() {
		$current_user = wp_get_current_user();
		$roles        = $current_user->roles;
		$role         = array_shift( $roles );
		if ( 'administrator' !== $role ) {
			exit;
		}
		$plugine_info = get_plugin_data( WMS7_PLUGIN_DIR . '/watchman-site7.php' );
		$table        = new wms7_List_Table();
		$table->prepare_items();
		$message     = '';
		$id          = Wms7_List_Table::wms7_get( 'wms7_id' );
		$id_del      = Wms7_List_Table::wms7_get( 'wms7_id_del' );
		$wms7_action = Wms7_List_Table::wms7_get( 'wms7_action' );

		if ( 'delete' === $wms7_action ) {
			if ( ( $id ) && ( $id_del ) && count( $id ) === $id_del ) {
				?>
				<div class="notice notice-success is-dismissible" id="message">
					<p><?php echo esc_html( 'Items deleted', 'watchman-site7' ); ?> : (count=<?php echo count( $id ); ?>) date-time: ( <?php echo esc_html( current_time( 'mysql' ) ); ?>)
					</p>
				</div>
				<?php
			} elseif ( $id ) {
				?>
				<div class="notice notice-warning is-dismissible" id="message">
					<p><?php echo esc_html( 'Attention! Not all records deleted since the field "Black list" is not empty.', 'watchman-site7' ); ?> Records to delete: <?php echo count( $id ); ?>  Deleted records: <?php echo esc_html( $id_del ); ?>
					</p>
				</div>
				<?php
			} else {
				?>
				<div class="notice notice-warning is-dismissible" id="message">
					<p><?php echo esc_html( 'No items selected for delete.', 'watchman-site7' ); ?>
					</p>
				</div>
				<?php
			}
		}
		if ( 'clear' === $wms7_action ) {
			if ( ( $id ) ) {
				?>
				<div class="notice notice-success is-dismissible" id="message">
					<p><?php echo esc_html( 'Black list item data cleaned successful', 'watchman-site7' ); ?> : (count=<?php echo esc_html( count( $id ) ); ?>) date-time: (<?php echo esc_html( current_time( 'mysql' ) ); ?>)
					</p>
				</div>
				<?php
			} else {
				?>
				<div class="notice notice-warning is-dismissible" id="message">
					<p><?php echo esc_html( 'No items selected for clear.', 'watchman-site7' ); ?>
					</p>
				</div>
				<?php
			}				
		}
		if ( 'export' === $wms7_action ) {
			if ( ( $id ) ) {
				?>
				<div class="notice notice-success is-dismissible" id="message">
					<p><?php echo esc_html( 'Export data items executed successful', 'watchman-site7' ); ?> : (count=<?php echo esc_html( count( $id ) ); ?>) date-time: (<?php echo esc_html( current_time( 'mysql' ) ); ?>)
					</p>
				</div>
				<?php
			} else {
				?>
				<div class="notice notice-warning is-dismissible" id="message">
					<p><?php echo esc_html( 'No items selected for export.', 'watchman-site7' ); ?>
					</p>
				</div>
				<?php
			}
		}

		$val            = get_option( 'wms7_screen_settings' );
		$banner1        = isset( $val['banner1'] ) ? $val['banner1'] : 0;
		$hidden_banner1 = ( 0 === $banner1 ) ? '' : 'hidden="true"';
		$img1           = plugins_url( '/images/Belarus_Russia.png', __FILE__ );

		$banner2        = isset( $val['banner2'] ) ? $val['banner2'] : 0;
		$hidden_banner2 = ( 0 === $banner2 ) ? '' : 'hidden="true"';
		$img2           = plugins_url( '/images/wms7_logo.png', __FILE__ );

		$banner3 = isset( $val['banner3'] ) ? $val['banner3'] : 0;
		$img3_1  = plugins_url( '/images/php-badge.png', __FILE__ );
		$img3_2  = plugins_url( '/images/wp-badge.png', __FILE__ );

		if ( 0 === $banner3 ) {
			?>
			<a href="https://plugintests.com/plugins/watchman-site7/latest-report"><img src="<?php echo esc_html( $img3_1 ); ?>" style="position:absolute; margin:0 5px 5px 23px;"></a>
			<a href="https://plugintests.com/plugins/watchman-site7/latest-report"><img src="<?php echo esc_html( $img3_2 ); ?>" style="position:absolute;margin:0 0 0 150px;"></a>
			<?php
		}
		?>
		<div class="sse" onclick="wms7_sse_backend()" title="<?php echo esc_html( 'Refresh table of visits', 'watchman-site7' ); ?>">
			<input type="checkbox" id="sse">
			<label><i></i></label>     
		</div>

		<div class="wrap">
			<span class="dashicons dashicons-shield" style="float: left;"></span>
			<h1><?php echo esc_html( $plugine_info['Name'] ) . ': ' . esc_html( 'visitors of site', 'watchman-site7' ) . '<span style="font-size:70%;"> (v.' . esc_html( $plugine_info['Version'] ) . ')</span>'; ?></h1>
			<div class="alignleft actions">
				<?php echo esc_html( $this->wms7_role_time_country_filter() ); ?>
			</div>

			<div class="banners">
				<img src="<?php echo esc_html( $img1 ); ?>" style="width:90px;height:40px;" <?php echo esc_html( $hidden_banner1 ); ?> title="Belarus - Russia">
				<img src="<?php echo esc_html( $img2 ); ?>" style="width:40px;height:40px;" <?php echo esc_html( $hidden_banner2 ); ?> title="WatchMan-Site7">
			</div>

			<div class="alignright actions">
				<?php echo esc_html( $this->wms7_login_ip_filter() ); ?>
			</div>
			<?php echo esc_html( $this->wms7_info_panel() ); ?>
			<form id="visitors-table" method="POST">
				<input type='hidden' name='visit_manager' value='<?php echo esc_html( wp_create_nonce( 'nonce_visit_manager' ) ); ?>'>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
		$_action    = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		$_map_nonce = filter_input( INPUT_GET, 'map_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_map_nonce, 'map_nonce' ) ) {
			if ( ( $_action ) && ( 'map' === $_action ) ) {
				$this->wms7_map();
			}
		}
		$_footer       = filter_input( INPUT_POST, 'footer', FILTER_SANITIZE_STRING );
		$_footer_nonce = filter_input( INPUT_POST, 'footer_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_footer_nonce, 'footer' ) ) {
			if ( $_footer ) {
				$this->wms7_win_popup();
			}
		}
		// save index.php.
		$_index = filter_input( INPUT_POST, 'index', FILTER_SANITIZE_STRING );
		$_nonce = filter_input( INPUT_POST, 'file_editor_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'file_editor' ) ) {
			if ( ( $_index ) && ( 'Save' === $_index ) ) {
				$_content = filter_input( INPUT_POST, 'content' );
				wms7_save_index_php( sanitize_post( $_content, 'edit' ) );
			}
		}
		// save robots.txt.
		$_robots = filter_input( INPUT_POST, 'robots', FILTER_SANITIZE_STRING );
		$_nonce  = filter_input( INPUT_POST, 'file_editor_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'file_editor' ) ) {
			if ( ( $_robots ) && ( 'Save' === $_robots ) ) {
				$_content = filter_input( INPUT_POST, 'content' );
				wms7_save_robots_txt( sanitize_post( $_content, 'edit' ) );
			}
		}
		// save htaccess.
		$_htaccess = filter_input( INPUT_POST, 'htaccess', FILTER_SANITIZE_STRING );
		$_nonce    = filter_input( INPUT_POST, 'file_editor_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'file_editor' ) ) {
			if ( ( $_htaccess ) && ( 'Save' === $_htaccess ) ) {
				$_content = filter_input( INPUT_POST, 'content' );
				wms7_save_htaccess( sanitize_post( $_content, 'edit' ) );
			}
		}
		// save wp-config.
		$_wp_config = filter_input( INPUT_POST, 'wp_config', FILTER_SANITIZE_STRING );
		$_nonce     = filter_input( INPUT_POST, 'file_editor_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'file_editor' ) ) {
			if ( ( $_wp_config ) && ( 'Save' === $_wp_config ) ) {
				$_content = filter_input( INPUT_POST, 'content' );
				wms7_save_wp_config( sanitize_post( $_content, 'edit' ) );
			}
		}
		// refresh cron table.
		$_cron_refresh = filter_input( INPUT_POST, 'cron_refresh', FILTER_SANITIZE_STRING );
		$_cron_delete  = filter_input( INPUT_POST, 'cron_delete', FILTER_SANITIZE_STRING );
		$_nonce        = filter_input( INPUT_POST, 'cron_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'cron' ) ) {
			if ( ( $_cron_refresh ) || ( $_cron_delete ) ) {
				$str_head = 'wp-cron tasks';
				$this->wms7_wp_cron( $str_head );
			}
		}
		// refresh stat table and graph.
		$_stat_table = filter_input( INPUT_POST, 'stat_table', FILTER_SANITIZE_STRING );
		$_stat_graph = filter_input( INPUT_POST, 'stat_graph', FILTER_SANITIZE_STRING );
		$_nonce      = filter_input( INPUT_POST, 'stat_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_nonce, 'stat' ) ) {
			if ( $_stat_table || $_stat_graph ) {
				if ( $_stat_table ) {
					$str_head = 'statistic of visits: table';
				}
				if ( $_stat_graph ) {
					$str_head = 'statistic of visits: graph';
				}
				$this->wms7_stat( $str_head );
			}
		}
		// view mail №msgno.
		$_msgno           = filter_input( INPUT_GET, 'msgno', FILTER_SANITIZE_NUMBER_INT );
		$_mail_view_reply = filter_input( INPUT_POST, 'mail_view_reply', FILTER_SANITIZE_STRING );
		$_mailbox         = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
		$_mail_nonce      = filter_input( INPUT_GET, 'mail_nonce', FILTER_SANITIZE_STRING );
		if ( $_msgno ) {
			if ( wp_verify_nonce( $_mail_nonce, 'mail_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				$num        = substr( $_mailbox, -1 );
				// check - draft folder.
				$folder = explode( ';', $box['mail_folders'], -1 );
				$draft  = false;
				$pos    = strpos( $folder[ $num - 1 ], 'Draft' );
				if ( $pos ) {
					$draft = true;
				} else {
					$pos = strpos( $folder[ $num - 1 ], 'Черновик' );
					if ( $pos ) {
						$draft = true;
					}
				}
				if ( ! $draft && ! $_mail_view_reply ) {
					$this->wms7_mail_view( $str_head );
				} else {
					$this->wms7_mail_new( $str_head, true );
				}
			}
		}
		// move mail.
		$_mail_move = filter_input( INPUT_POST, 'mail_move', FILTER_SANITIZE_STRING );
		if ( $_mail_move ) {
			$_mailbox_nonce = filter_input( INPUT_POST, 'mailbox_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $_mailbox_nonce, 'mailbox_nonce' ) ) {
				wms7_mail_move();
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				$this->wms7_mail( $str_head );
			}
		}
		// send mail.
		$_mail_new_send = filter_input( INPUT_POST, 'mail_new_send', FILTER_SANITIZE_STRING );
		if ( $_mail_new_send ) {
			$_mail_new_nonce = filter_input( INPUT_POST, 'mail_new_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $_mail_new_nonce, 'mail_new_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				wms7_mail_send();
				wms7_goto_sent( $_mail_new_nonce );
			}
		}
		// new mail.
		$_mail_new = filter_input( INPUT_POST, 'mail_new', FILTER_SANITIZE_STRING );
		// reply mail.
		$_mail_view_reply = filter_input( INPUT_POST, 'mail_view_reply', FILTER_SANITIZE_STRING );
		// send mail to a registered website visitor.
		$_uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_NUMBER_INT );
		if ( $_mail_new || $_mail_view_reply || $_uid ) {
			$_nonce     = filter_input( INPUT_POST, 'mailbox_nonce', FILTER_SANITIZE_STRING );
			$_msg_nonce = filter_input( INPUT_GET, 'msg_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $_nonce, 'mailbox_nonce' ) || wp_verify_nonce( $_msg_nonce, 'msg_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				$this->wms7_mail_new( $str_head, false );
			}
		}
		// new mail save.
		$_mail_new_save  = filter_input( INPUT_POST, 'mail_new_save', FILTER_SANITIZE_STRING );
		$_mail_new_nonce = filter_input( INPUT_POST, 'mail_new_nonce', FILTER_SANITIZE_STRING );
		if ( $_mail_new_save ) {
			if ( wp_verify_nonce( $_mail_new_nonce, 'mail_new_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				wms7_mail_save_to_draft();
				wms7_goto_draft( $_mail_new_nonce );
			}
		}
		// delete mail.
		$_mail_delete = filter_input( INPUT_POST, 'mail_delete', FILTER_SANITIZE_STRING );
		if ( $_mail_delete ) {
			$_mailbox_nonce = filter_input( INPUT_POST, 'mailbox_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $_mailbox_nonce, 'mailbox_nonce' ) ) {
				wms7_mail_delete();
				$_mailbox = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
				?>
				<div class='loader' id='win-loader' style='top:350px;'></div>
				<script>wms7_popup_loader();</script>
				<script>wms7_mailbox_select('<?php echo esc_html( $_mailbox ); ?>','<?php echo esc_html( $_mailbox_nonce ); ?>');</script>
				<?php
			}
		}
		// view mail box.
		$_mailbox         = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
		$_msgno           = filter_input( INPUT_GET, 'msgno', FILTER_SANITIZE_STRING );
		$_mail_new        = filter_input( INPUT_POST, 'mail_new', FILTER_SANITIZE_STRING );
		$_mail_view_quit  = filter_input( INPUT_POST, 'mail_view_quit', FILTER_SANITIZE_STRING );
		$_mail_new_quit   = filter_input( INPUT_POST, 'mail_new_quit', FILTER_SANITIZE_STRING );
		$_mail_view_nonce = filter_input( INPUT_POST, 'mail_view_nonce', FILTER_SANITIZE_STRING );
		if ( isset( $_GET['mail_new_nonce'] ) ) {
			$_mail_new_nonce = filter_input( INPUT_GET, 'mail_new_nonce', FILTER_SANITIZE_STRING );
		}
		if ( isset( $_POST['mail_new_nonce'] ) ) {
			$_mail_new_nonce = filter_input( INPUT_POST, 'mail_new_nonce', FILTER_SANITIZE_STRING );
		}
		$_mailbox_nonce = filter_input( INPUT_GET, 'mailbox_nonce', FILTER_SANITIZE_STRING );
		if ( ( $_mail_view_quit ) || ( $_mail_new_quit ) || ( $_mail_new_nonce ) || ( ! is_null( $_mailbox ) && is_null( $_mail_new ) && is_null( $_msgno ) ) ) {
			if ( wp_verify_nonce( $_mail_new_nonce, 'mail_new_nonce' ) || wp_verify_nonce( $_mail_view_nonce, 'mail_view_nonce' ) || wp_verify_nonce( $_mailbox_nonce, 'mailbox_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'MailBox: ' . $box['mail_box_name'];
				$this->wms7_mail( $str_head );
			}
		}
		// mail_search_context.
		$_mail_search = filter_input( INPUT_POST, 'mail_search', FILTER_SANITIZE_STRING );
		if ( $_mail_search ) {
			$_mailbox_nonce = filter_input( INPUT_POST, 'mailbox_nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $_mailbox_nonce, 'mailbox_nonce' ) ) {
				$val        = get_option( 'wms7_main_settings' );
				$select_box = $val['mail_select'];
				$box        = $val[ $select_box ];
				$str_head   = 'search in: ' . $box['mail_box_name'];
				$this->wms7_mail( $str_head );
			}
		}
	}
	/**
	 * If the Attack analyzer option is enabled, then insert the current IP into the field: do not register visits.
	 */
	public function wms7_check_ip_admin() {
		$_forward_for    = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING );
		$_remote_addr    = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );
		$user_ip         = ( $_forward_for ) ? $_forward_for : $_remote_addr;
		$val             = get_option( 'wms7_main_settings' );
		$attack_analyzer = isset( $val['attack_analyzer'] ) ? $val['attack_analyzer'] : '';

		if ( '' !== $attack_analyzer ) {
			$pos = strpos( $val['ip_excluded'], $user_ip );
			if ( false === $pos ) {
				if ( '' !== $val['ip_excluded'] ) {
					$val['ip_excluded'] = $val['ip_excluded'] . '|' . $user_ip;
				} else {
					$val['ip_excluded'] = $user_ip;
				}
				update_option( 'wms7_main_settings', $val );
			}
		}
	}
	/**
	 * Create and control page Settings.
	 */
	public function wms7_settings() {
		$current_user = wp_get_current_user();
		$roles        = $current_user->roles;
		$role         = array_shift( $roles );
		if ( 'administrator' !== $role ) {
			exit;
		}
		$plugine_info = get_plugin_data( WMS7_PLUGIN_DIR . '/watchman-site7.php' );
		$url          = get_option( 'wms7_current_url' );
		?>
		<div class="wrap">
			<span class="dashicons dashicons-shield" style="float: left;"></span>
			<h1><?php echo esc_html( $plugine_info['Name'] ) . ': ' . esc_html( 'settings', 'watchman-site7' ); ?></h1>
			<br>
			<?php
			if ( ! extension_loaded('imap') ) {
			?>
				<div class="notice notice-success is-dismissible" id="message">
					<p><?php echo esc_html( 'Recommendation: for use additional functions of the plug-in (mailbox management) - install the PHP IMAP extension module on your site hosting service.', 'watchman-site7' ); ?>
					</p>
				</div>
			<?php
			}
			$_settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_VALIDATE_BOOLEAN );
			if ( $_settings_updated ) {
				$this->wms7_check_ip_admin();
				$msg = esc_html( 'Settings data saved successful', 'watchman-site7' );
				?>
				<div class="updated notice is-dismissible" ><p><strong><?php echo esc_html( $msg ) . '; date-time: (' . esc_html( current_time( 'mysql' ) ) . ')'; ?></strong></p></div>
				<?php
			}
			?>
			<form method="POST" action="options.php">
				<table bgcolor="white" width="100%" cellspacing="2" cellpadding="5" RULES="rows" style="border:1px solid #DDDDDD";>
					<tr>
						<td height="25"><font size="4"><b><?php esc_html_e( 'General settings. (changes take effect after you click Save)', 'watchman-site7' ); ?></b></font></td>
						</tr>
					<tr>
						<td>
							<?php
							settings_fields( 'option_group' );
							do_settings_sections( 'wms7_settings' );
							?>
						</td>
					</tr>
				</table>
				<br>
				<button type="submit" class="button-primary" name="save" onClick="wms7_setup_sound()">Save</button>
				<button type="button" class="button-primary" name="quit" onClick="location.href='<?php echo esc_url( $url ); ?>'">Quit</button>
			</form>
		</div>
		<?php
	}
	/**
	 * Add fields to page Settings.
	 */
	public function wms7_main_settings() {

		register_setting( 'option_group', 'wms7_main_settings' );

		add_settings_section( 'wms7_section', '', '', 'wms7_settings' );

		add_settings_field(
			'field1',
			'<label for="wms7_main_settings[log_duration]">' . __( 'Duration log entries', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field1' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field2',
			'<label for="wms7_main_settings[ip_excluded]">' . __( 'Do not register visits for', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field2' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field3',
			'<label for="wms7_main_settings[whois_service]">' . __( 'WHO-IS service', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field3' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field4',
			'<label for="wms7_main_settings[robots]">' . __( 'Robots', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field4' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field5',
			'<label for="wms7_main_settings[robots_reg]">' . __( 'Visits of robots', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field5' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field7',
			'<label for="wms7_main_settings[key_api]">' . __( 'Google Maps API key', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field7' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field8',
			'<label for="wms7_main_settings[export_csv]">' . __( 'Exporting Table Fields', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field8' ),
			'wms7_settings',
			'wms7_section'
		);
		if ( extension_loaded('imap') ) {
			add_settings_field(
				'field9',
				'<label for="wms7_main_settings[mail_boxes]">' . __( 'MailBoxes', 'watchman-site7' ) . ':</label>',
				array( $this, 'wms7_main_setting_field9' ),
				'wms7_settings',
				'wms7_section'
			);
			add_settings_field(
				'field10',
				'<label for="wms7_main_settings[mail_box_select]">' . __( 'MailBox select', 'watchman-site7' ) . ':</label>',
				array( $this, 'wms7_main_setting_field10' ),
				'wms7_settings',
				'wms7_section'
			);
			add_settings_field(
				'field11',
				'<label for="wms7_main_settings[mail_box_tmp]">' . __( 'E-mail folder tmp', 'watchman-site7' ) . ':</label>',
				array( $this, 'wms7_main_setting_field11' ),
				'wms7_settings',
				'wms7_section'
			);
		}
		add_settings_field(
			'field12',
			'<label for="wms7_main_settings[sse_sound]">' . __( 'SSE sound', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field12' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field13',
			'<label for="wms7_main_settings[recaptcha]">' . __( 'Google reCAPTCHA', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field13' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field14',
			'<label for="wms7_main_settings[recaptcha_site_key]">' . __( 'Site Key', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field14' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field15',
			'<label for="wms7_main_settings[recaptcha_secret_key]">' . __( 'Secret Key', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field15' ),
			'wms7_settings',
			'wms7_section'
		);
		add_settings_field(
			'field16',
			'<label for="wms7_main_settings[attack_analyzer]">' . __( 'Attack analyzer', 'watchman-site7' ) . ':</label>',
			array( $this, 'wms7_main_setting_field16' ),
			'wms7_settings',
			'wms7_section'
		);
	}
	/**
	 * Filling option1 (Duration log entries) on page Settings.
	 */
	public function wms7_main_setting_field1() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['log_duration'] ) ? $val['log_duration'] : '3';
		?>
		<input id="wms7_main_settings[log_duration]" name="wms7_main_settings[log_duration]" type="number" step="1" min="0" max="365" value="<?php echo esc_html( $val ); ?>" /><br><label><?php esc_html_e( 'days. Leave empty or enter 0 if you not want the log to be truncated', 'watchman-site7' ); ?></label>
		<?php
		// since we're on the General Settings page - update cron schedule if settings has been updated.
		$_settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_VALIDATE_BOOLEAN );
		if ( $_settings_updated ) {
			wp_clear_scheduled_hook( 'wms7_truncate' );
		}
	}
	/**
	 * Filling option2 (Do not register visits for) on page Settings.
	 */
	public function wms7_main_setting_field2() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['ip_excluded'] ) ? $val['ip_excluded'] : '';
		?>
		<textarea id="wms7_main_settings[ip_excluded]" name="wms7_main_settings[ip_excluded]" placeholder="IP1|IP2|IP3|IP4"  style="margin: 0px; width: 320px; height: 45px;"><?php echo esc_textarea( $val ); ?></textarea><br><label><?php esc_html_e( 'Visits from these IP addresses will be excluded from the protocol visits', 'watchman-site7' ); ?></label><br><label><?php esc_html_e( 'These IPs will be ignored by the Attack analyzer. It is recommended to register one or several trusted IP addresses of the site administrator and authors.', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option3 (WHO-IS service) on page Settings.
	 */
	public function wms7_main_setting_field3() {
		$val      = get_option( 'wms7_main_settings' );
		$val      = isset( $val['whois_service'] ) ? $val['whois_service'] : 'none';
		$checked0 = '';
		$checked1 = '';
		$checked2 = '';
		$checked3 = '';
		$checked4 = '';

		switch ( $val ) {
			case 'none':
				$checked0 = 'checked';
				break;
			case 'IP-API':
				$checked1 = 'checked';
				break;
			case 'IP-Info':
				$checked2 = 'checked';
				break;
			case 'Geobytes':
				$checked3 = 'checked';
				break;
			case 'SxGeo':
				$checked4 = 'checked';
				break;
		}
		?>
		<input type="radio" value="none" <?php echo esc_html( $checked0 ); ?> id="who_0" name="wms7_main_settings[whois_service]" onClick="wms7_settings_sound()">
		<label for="who_0"><?php esc_html_e( 'none', 'watchman-site7' ); ?></label><br>

		<input type="radio" value="IP-API" <?php echo esc_html( $checked1 ); ?> id="who_1" name="wms7_main_settings[whois_service]" onClick="wms7_settings_sound()">
		<label for="who_1">IP-API</label><br>

		<input type="radio" value="IP-Info" <?php echo esc_html( $checked2 ); ?> id="who_2" name="wms7_main_settings[whois_service]" onClick="wms7_settings_sound()">
		<label for="who_2">IP-Info</label><br>

		<input type="radio" value="Geobytes" <?php echo esc_html( $checked3 ); ?> id="who_3" name="wms7_main_settings[whois_service]" onClick="wms7_settings_sound()">
		<label for="who_3">Geobytes</label><br>

		<input type="radio" value="SxGeo" <?php echo esc_html( $checked4 ); ?> id="who_4" name="wms7_main_settings[whois_service]" onClick="wms7_settings_sound()">
		<label for="who_4">SxGeo</label>

		<?php
	}
	/**
	 * Filling option4 (Robots) on page Settings.
	 */
	public function wms7_main_setting_field4() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['robots'] ) ? $val['robots'] : 'Mail.RU_Bot|YandexBot|Googlebot|bingbot|Virusdie|AhrefsBot|YandexMetrika|MJ12bot|BegunAdvertising|Slurp|DotBot|YandexMobileBot|MegaIndex|Google|YandexAccessibilityBot|SemrushBot|Baiduspider|SEOkicks-Robot|BingPreview|rogerbot|Applebot|Qwantify|DuckDuckBot|Cliqzbot|NetcraftSurveyAgent|SeznamBot|CCBot|linkdexbot|Barkrowler|Wget|ltx71|Slackbot|Nimbostratus-Bot|Crawler|Thither.Direct|Moreover|LetsearchBot|';
		?>
		<textarea id="wms7_main_settings[robots]" name="wms7_main_settings[robots]" placeholder="Name1|Name2|Name3|"  style="margin: 0px; width: 320px; height: 45px;"><?php echo esc_textarea( $val ); ?></textarea><br><label><?php esc_html_e( 'Visits this name will be marked - Robot', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option5 (Visits of robots) on page Settings.
	 */
	public function wms7_main_setting_field5() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['robots_reg'] ) ? $val['robots_reg'] : '';
		?>
		<input id="wms7_main_settings[robots_reg]" name="wms7_main_settings[robots_reg]" type="checkbox" value="1" <?php checked( $val ); ?> /><br><label for="wms7_main_settings[robots_reg]"><?php esc_html_e( 'Register visits by robots.', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option7 (Google Maps API key) on page Settings.
	 */
	public function wms7_main_setting_field7() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['key_api'] ) ? $val['key_api'] : '';
		?>
		<input id="wms7_main_settings[key_api]" style="margin: 0px; width: 320px; height: 25px;" name="wms7_main_settings[key_api]" type="text" placeholder="Google Maps API key" value="<?php echo esc_html( $val ); ?>" /><br><label><?php esc_html_e( 'Insert Google Maps API key (for Google Maps JavaScript API and Google Maps Geocoding API). Visit ', 'watchman-site7' ); ?></label><a href="https://console.developers.google.com/apis/credentials" target="_blank">Page registration Google Maps API key</a>
		<?php
	}
	/**
	 * Filling option8 (Exporting Table Fields) on page Settings.
	 */
	public function wms7_main_setting_field8() {
		$val = get_option( 'wms7_main_settings' );

		$id         = isset( $val['id'] ) ? $val['id'] : '';
		$uid        = isset( $val['uid'] ) ? $val['uid'] : '';
		$user_login = isset( $val['user_login'] ) ? $val['user_login'] : '';
		$user_role  = isset( $val['user_role'] ) ? $val['user_role'] : '';
		$time_visit = isset( $val['time_visit'] ) ? $val['time_visit'] : '';
		$user_ip    = isset( $val['user_ip'] ) ? $val['user_ip'] : '';
		$black_list = isset( $val['black_list'] ) ? $val['black_list'] : '';
		$page_visit = isset( $val['page_visit'] ) ? $val['page_visit'] : '';
		$page_from  = isset( $val['page_from'] ) ? $val['page_from'] : '';
		$info       = isset( $val['info'] ) ? $val['info'] : '';
		?>
		<input id="id" name="wms7_main_settings[id]" type="checkbox" value="1" 
		<?php checked( $id ); ?> /><label for='id'><?php esc_html_e( 'ID', 'watchman-site7' ); ?></label>
		<input id="uid" name="wms7_main_settings[uid]" type="checkbox" value="1" 
		<?php checked( $uid ); ?> /><label for='uid'><?php esc_html_e( 'UID', 'watchman-site7' ); ?></label>
		<input id="user_login" name="wms7_main_settings[user_login]" type="checkbox" value="1" 
		<?php checked( $user_login ); ?> /><label for='user_login'><?php esc_html_e( 'Login', 'watchman-site7' ); ?></label>
		<input id="user_role" name="wms7_main_settings[user_role]" type="checkbox" value="1" 
		<?php checked( $user_role ); ?> /><label for='user_role'><?php esc_html_e( 'Role', 'watchman-site7' ); ?></label>
		<input id="time_visit" name="wms7_main_settings[time_visit]" type="checkbox" value="1" 
		<?php checked( $time_visit ); ?> /><label for='time_visit'><?php esc_html_e( 'Time', 'watchman-site7' ); ?></label>
		<input id="user_ip" name="wms7_main_settings[user_ip]" type="checkbox" value="1" 
		<?php checked( $user_ip ); ?> /><label for='user_ip'><?php esc_html_e( 'Visitor IP', 'watchman-site7' ); ?></label>
		<input id="black_list" name="wms7_main_settings[black_list]" type="checkbox" value="1" 
		<?php checked( $black_list ); ?> /><label for='black_list'><?php esc_html_e( 'Black list', 'watchman-site7' ); ?></label>
		<input id="page_visit" name="wms7_main_settings[page_visit]" type="checkbox" value="1" 
		<?php checked( $page_visit ); ?> /><label for='page_visit'><?php esc_html_e( 'Page Visit', 'watchman-site7' ); ?></label>
		<input id="page_from" name="wms7_main_settings[page_from]" type="checkbox" value="1" 
		<?php checked( $page_from ); ?> /><label for='page_from'><?php esc_html_e( 'Page From', 'watchman-site7' ); ?></label>
		<input id="info" name="wms7_main_settings[info]" type="checkbox" value="1" 
		<?php checked( $info ); ?> /><label for='info'><?php esc_html_e( 'Info', 'watchman-site7' ); ?></label><br>
		<label><?php esc_html_e( 'Select the fields to export to the report file', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option9 (MailBoxes) on page Settings.
	 */
	public function wms7_main_setting_field9() {
		// this_site.
		$val       = get_option( 'wms7_main_settings' );
		$val1_box0 = isset( $val['box0']['imap_server'] ) ? $val['box0']['imap_server'] : '';
		$val2_box0 = isset( $val['box0']['mail_box_name'] ) ? $val['box0']['mail_box_name'] : '';
		$val3_box0 = isset( $val['box0']['mail_box_pwd'] ) ? $val['box0']['mail_box_pwd'] : '';
		$val4_box0 = isset( $val['box0']['mail_box_encryption'] ) ? $val['box0']['mail_box_encryption'] : '';
		$val5_box0 = isset( $val['box0']['mail_box_port'] ) ? $val['box0']['mail_box_port'] : '';
		$val6_box0 = isset( $val['box0']['pwd_box'] ) ? $val['box0']['pwd_box'] : '';

		$val7_box0     = isset( $val['box0']['mail_folders'] ) ? $val['box0']['mail_folders'] : '';
		$val7_box0_alt = isset( $val['box0']['mail_folders_alt'] ) ? $val['box0']['mail_folders_alt'] : '';

		$val8_box0  = isset( $val['box0']['smtp_server'] ) ? $val['box0']['smtp_server'] : '';
		$val9_box0  = isset( $val['box0']['smtp_box_encryption'] ) ? $val['box0']['smtp_box_encryption'] : '';
		$val10_box0 = isset( $val['box0']['smtp_box_port'] ) ? $val['box0']['smtp_box_port'] : '';
		$sel1       = '';
		$sel2       = '';
		$sel3       = '';
		$sel4       = '';
		switch ( $val4_box0 ) {
			case 'Auto':
				$sel1 = 'selected';
				break;
			case 'No':
				$sel2 = 'selected';
				break;
			case 'SSL':
				$sel3 = 'selected';
				break;
			case 'TLS':
				$sel4 = 'selected';
				break;
		}
		$sel1_smtp = '';
		$sel2_smtp = '';
		$sel3_smtp = '';
		$sel4_smtp = '';
		switch ( $val9_box0 ) {
			case 'Auto':
				$sel1_smtp = 'selected';
				break;
			case 'No':
				$sel2_smtp = 'selected';
				break;
			case 'SSL':
				$sel3_smtp = 'selected';
				break;
			case 'TLS':
				$sel4_smtp = 'selected';
				break;
		}
		$_smtp = filter_input( INPUT_GET, 'smtp', FILTER_SANITIZE_STRING );
		if ( 'box0_smtp' === $_smtp ) {
			$message_smtp = wms7_msg_smtp( 'Checking the connection to the server: ' . $val8_box0 );
		} else {
			$message_smtp = '';
		}

		?>
		<div id="param_mail_box_this_site" style="width:100%;">
			<fieldset style="width:860px;height:210px;border: 2px groove;margin:0;padding:0;">
				<legend><b>This site</b></legend>

				<table  cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="padding:0;">
							<input id="imap_server_box0" style="width: 200px;" name="wms7_main_settings[box0][imap_server]" type="text" placeholder="imap server" value="<?php echo esc_html( $val1_box0 ); ?>" /><br>
							<label for="imap_server_box0" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'IMAP Server name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_name_box0" style="width: 200px;" name="wms7_main_settings[box0][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo esc_html( $val2_box0 ); ?>" /><br>
							<label for="mail_box_name_box0" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_pwd_box0" style="width: 200px;" name="wms7_main_settings[box0][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo esc_html( $val3_box0 ); ?>" /><br>
							<label for="mail_box_pwd_box0" style="overflow: hidden;text-overflow: ellipsis;width: 180px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox password', 'watchman-site7' ); ?></label>

							<input id="pwd_box0" name="wms7_main_settings[box0][pwd_box]" type="checkbox" value="1" style="float: right;margin-top:7px;"<?php checked( $val6_box0 ); ?> onClick="wms7_check_pwd(id)"/>
						</td>
						<td style="padding:0px 5px;">
							<select id="encryption_box0" name="wms7_main_settings[box0][mail_box_encryption]" ><option <?php echo esc_html( $sel1 ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2 ); ?> value="No">No</option><option <?php echo esc_html( $sel3 ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4 ); ?> value="TLS">TLS</option></select><br>

							<label for="encryption_box0" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Encrypt', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_port_box0" name="wms7_main_settings[box0][mail_box_port]" type=text placeholder="993" value="<?php echo esc_html( $val5_box0 ); ?>" style="width: 60px;"/><br>
							<label for="mail_box_port_box0" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box0" style="margin-top:-12px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" />Check</button><br>

							<button type="button" id="textbox0" style="margin-top:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box0','tbl_folders_box0','text_folders_box0','text_folders_box0_alt')" />Select</button>
						</td>
						<td style="padding:0;">
							<?php
							$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
							if ( 'box0' === $_checkbox ) {
								?>
							<textarea readonly id="text_folders_box0" name="wms7_main_settings[box0][mail_folders]" style="position:relative;z-index:100;width:350px;height:180px;"><?php echo esc_textarea( $val7_box0 ); ?></textarea>
							<div id="tbl_folders_box0" style="position:relative;z-index:101;margin-top:-183px;padding:0;width:350px;height:180px;">
								<table class="table_box" style="background-color: #5B5B59;">
									<thead class="thead_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</thead>
									<tbody class="tbody" style= "height:120px;max-height:180px;width:345px;">
									<?php
									$imap_list0 = $this->wms7_imap_list( 'box0' );
									$i          = 0;
									foreach ( $imap_list0 as $value ) {
										$input_id = 'box0_chk' . $i;
										$str      = explode( '|', $value );
										?>
										<tr class='tr_box'>
											<td class='td_box' width='19%'>
											<input name='box0_chk' id=<?php echo esc_html( $input_id ); ?> type='checkbox' ><?php echo esc_html( $i ); ?>
											</td>
											<td class='td_box' width='81%' data="<?php echo esc_html( $str[1] ); ?>" ><?php echo esc_html( $str[0] ); ?>
											</td>
										</tr>
										<?php
										$i++;
									}
									?>
									</tbody>
									<tfoot class="tfoot_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</tfoot>
								</table>
							</div>
								<?php
							} else {
								?>
							<textarea readonly id="text_folders_box0" name="wms7_main_settings[box0][mail_folders]" style="position:relative;z-index:102;width:350px;height:180px;"><?php echo esc_textarea( $val7_box0 ); ?></textarea>
								<?php
							}
							?>
						</td>
						<td style="padding:0px 5px;">
							<input id="smtp_server_box0" style="width: 200px;" name="wms7_main_settings[box0][smtp_server]" type="text" placeholder="smtp server" value="<?php echo esc_html( $val8_box0 ); ?>" /><br>

							<label for="smtp_server_box0" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Server name', 'watchman-site7' ); ?></label><br>

							<select id="smtp_encryption_box0" name="wms7_main_settings[box0][smtp_box_encryption]" ><option <?php echo esc_html( $sel1_smtp ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2_smtp ); ?> value="No">No</option><option <?php echo esc_html( $sel3_smtp ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4_smtp ); ?> value="TLS">TLS</option></select>

							<input id="smtp_box_port_box0" style="margin-left:75px;width:60px;" name="wms7_main_settings[box0][smtp_box_port]" type=text placeholder="465" value="<?php echo esc_html( $val10_box0 ); ?>" /><br>

							<label for="smtp_encryption_box0" style="overflow: hidden;text-overflow: ellipsis;width: 90px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Encrypt', 'watchman-site7' ); ?></label>
							<label for="smtp_box_port_box0" style="float: right;overflow: hidden;text-overflow: ellipsis;width: 80px;height:20px;white-space: nowrap;padding:0;" ><?php esc_html_e( 'SMTP Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box0_smtp" name="check_smtp" class="button-primary" style="margin-top:3px;margin-bottom:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" onClick="wms7_check_smtp(id)"  />Check</button>
							<input id="box0_msg_smtp" type="text" name="msg_smtp" style="width:200px;"  value="<?php echo esc_html( $message_smtp ); ?>" />
						</td>
					</tr>
				</table>
				<textarea id="text_folders_box0_alt" name="wms7_main_settings[box0][mail_folders_alt]" style="visibility:hidden;"><?php echo esc_textarea( $val7_box0_alt ); ?></textarea>
			</fieldset>
		</div>
		<?php
		// mail.ru.
		$val       = get_option( 'wms7_main_settings' );
		$val1_box1 = isset( $val['box1']['imap_server'] ) ? $val['box1']['imap_server'] : '';
		$val2_box1 = isset( $val['box1']['mail_box_name'] ) ? $val['box1']['mail_box_name'] : '';
		$val3_box1 = isset( $val['box1']['mail_box_pwd'] ) ? $val['box1']['mail_box_pwd'] : '';
		$val4_box1 = isset( $val['box1']['mail_box_encryption'] ) ? $val['box1']['mail_box_encryption'] : '';
		$val5_box1 = isset( $val['box1']['mail_box_port'] ) ? $val['box1']['mail_box_port'] : '';
		$val6_box1 = isset( $val['box1']['pwd_box'] ) ? $val['box1']['pwd_box'] : '';

		$val7_box1     = isset( $val['box1']['mail_folders'] ) ? $val['box1']['mail_folders'] : '';
		$val7_box1_alt = isset( $val['box1']['mail_folders_alt'] ) ? $val['box1']['mail_folders_alt'] : '';

		$val8_box1  = isset( $val['box1']['smtp_server'] ) ? $val['box1']['smtp_server'] : '';
		$val9_box1  = isset( $val['box1']['smtp_box_encryption'] ) ? $val['box1']['smtp_box_encryption'] : '';
		$val10_box1 = isset( $val['box1']['smtp_box_port'] ) ? $val['box1']['smtp_box_port'] : '';
		$sel1       = '';
		$sel2       = '';
		$sel3       = '';
		$sel4       = '';
		switch ( $val4_box1 ) {
			case 'Auto':
				$sel1 = 'selected';
				break;
			case 'No':
				$sel2 = 'selected';
				break;
			case 'SSL':
				$sel3 = 'selected';
				break;
			case 'TLS':
				$sel4 = 'selected';
				break;
		}
		$sel1_smtp = '';
		$sel2_smtp = '';
		$sel3_smtp = '';
		$sel4_smtp = '';
		switch ( $val9_box1 ) {
			case 'Auto':
				$sel1_smtp = 'selected';
				break;
			case 'No':
				$sel2_smtp = 'selected';
				break;
			case 'SSL':
				$sel3_smtp = 'selected';
				break;
			case 'TLS':
				$sel4_smtp = 'selected';
				break;
		}
		$_smtp = filter_input( INPUT_GET, 'smtp', FILTER_SANITIZE_STRING );
		if ( 'box1_smtp' === $_smtp ) {
			$message_smtp = wms7_msg_smtp( 'Checking the connection to the server: ' . $val8_box1 );
		} else {
			$message_smtp = '';
		}
		?>
		<div id="param_mail_box_mail_ru" style="width:100%;">
			<fieldset style="width:860px;height:210px;border: 2px groove;margin:0;padding:0;">
				<legend><b>Mail.ru</b></legend>
				<table  cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="padding:0;">
							<input id="imap_server_box1" style="width: 200px;" name="wms7_main_settings[box1][imap_server]" type="text" placeholder="imap server" value="<?php echo esc_html( $val1_box1 ); ?>" /><br>
							<label for="imap_server_box1" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'IMAP Server name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_name_box1" style="width: 200px;" name="wms7_main_settings[box1][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo esc_html( $val2_box1 ); ?>" /><br>
							<label for="mail_box_name_box1" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_pwd_box1" style="width: 200px;" name="wms7_main_settings[box1][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo esc_html( $val3_box1 ); ?>" /><br>
							<label for="mail_box_pwd_box1" style="overflow: hidden;text-overflow: ellipsis;width: 180px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox password', 'watchman-site7' ); ?></label>

							<input id="pwd_box1" name="wms7_main_settings[box1][pwd_box]" type="checkbox" value="1" style="float: right;margin-top:7px;"<?php checked( $val6_box1 ); ?> onClick="wms7_check_pwd(id)"/>
						</td>
						<td style="padding:0px 5px;">
							<select id="encryption_box1" name="wms7_main_settings[box1][mail_box_encryption]" ><option <?php echo esc_html( $sel1 ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2 ); ?> value="No">No</option><option <?php echo esc_html( $sel3 ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4 ); ?> value="TLS">TLS</option></select><br>

							<label for="encryption_box1" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Encrypt', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_port_box1" name="wms7_main_settings[box1][mail_box_port]" type=text placeholder="993" value="<?php echo esc_html( $val5_box1 ); ?>" style="width: 60px;"/><br>
							<label for="mail_box_port_box1" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box1" style="margin-top:-12px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" />Check</button><br>

							<button type="button" id="textbox1" style="margin-top:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box1','tbl_folders_box1','text_folders_box1','text_folders_box1_alt')" />Select</button>
						</td>
						<td style="padding:0;">
							<?php
							$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
							if ( 'box1' === $_checkbox ) {
								?>
							<textarea readonly id="text_folders_box1" name="wms7_main_settings[box1][mail_folders]" style="position:relative;z-index:100;width:350px;height:180px;"><?php echo esc_textarea( $val7_box1 ); ?></textarea>
							<div id="tbl_folders_box1" style="position:relative;z-index:101;margin-top:-183px;padding:0;width:350px;height:180px;">
								<table class="table_box" style="background-color: #5B5B59;">
									<thead class="thead_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</thead>
									<tbody class="tbody" style= "height:120px;max-height:180px;width:345px;">
									<?php
									$imap_list1 = $this->wms7_imap_list( 'box1' );
									$i          = 0;
									foreach ( $imap_list1 as $value ) {
										$input_id = 'box1_chk' . $i;
										$str      = explode( '|', $value );
										?>
										<tr class='tr_box'>
											<td class='td_box' width='19%'>
											<input name='box1_chk' id=<?php echo esc_html( $input_id ); ?> type='checkbox' ><?php echo esc_html( $i ); ?>
											</td>
											<td class='td_box' width='81%' data="<?php echo esc_html( $str[1] ); ?>" ><?php echo esc_html( $str[0] ); ?>
											</td>
										</tr>
										<?php
										$i++;
									}
									?>
									</tbody>
									<tfoot class="tfoot_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</tfoot>
								</table>
							</div>
								<?php
							} else {
								?>
							<textarea readonly id="text_folders_box1" name="wms7_main_settings[box1][mail_folders]" style="position:relative;z-index:102;width:350px;height:180px;"><?php echo esc_textarea( $val7_box1 ); ?></textarea>
								<?php
							}
							?>
						</td>
						<td style="padding:0px 5px;">
							<input id="smtp_server_box1" style="width: 200px;" name="wms7_main_settings[box1][smtp_server]" type="text" placeholder="smtp server" value="<?php echo esc_html( $val8_box1 ); ?>" /><br>

							<label for="smtp_server_box1" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Server name', 'watchman-site7' ); ?></label><br>

							<select id="smtp_encryption_box1" name="wms7_main_settings[box1][smtp_box_encryption]" ><option <?php echo esc_html( $sel1_smtp ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2_smtp ); ?> value="No">No</option><option <?php echo esc_html( $sel3_smtp ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4_smtp ); ?> value="TLS">TLS</option></select>

							<input id="smtp_box_port_box1" style="margin-left:75px;width:60px;" name="wms7_main_settings[box1][smtp_box_port]" type=text placeholder="465" value="<?php echo esc_html( $val10_box1 ); ?>" /><br>

							<label for="smtp_encryption_box1" style="overflow: hidden;text-overflow: ellipsis;width: 90px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Encrypt', 'watchman-site7' ); ?></label>
							<label for="smtp_box_port_box1" style="float: right;overflow: hidden;text-overflow: ellipsis;width: 80px;height:20px;white-space: nowrap;padding:0;" ><?php esc_html_e( 'SMTP Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box1_smtp" name="check_smtp" class="button-primary" style="margin-top:3px;margin-bottom:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" onClick="wms7_check_smtp(id)"  />Check</button>
							<input id="box1_msg_smtp" type="text" name="msg_smtp" style="width:200px;"  value="<?php echo esc_html( $message_smtp ); ?>" />
						</td>
					</tr>
				</table>
				<textarea id="text_folders_box1_alt" name="wms7_main_settings[box1][mail_folders_alt]" style="visibility:hidden;"><?php echo esc_textarea( $val7_box1_alt ); ?></textarea>
			</fieldset>
		</div>
		<?php
		// yandex.ru.
		$val       = get_option( 'wms7_main_settings' );
		$val1_box2 = isset( $val['box2']['imap_server'] ) ? $val['box2']['imap_server'] : '';
		$val2_box2 = isset( $val['box2']['mail_box_name'] ) ? $val['box2']['mail_box_name'] : '';
		$val3_box2 = isset( $val['box2']['mail_box_pwd'] ) ? $val['box2']['mail_box_pwd'] : '';
		$val4_box2 = isset( $val['box2']['mail_box_encryption'] ) ? $val['box2']['mail_box_encryption'] : '';
		$val5_box2 = isset( $val['box2']['mail_box_port'] ) ? $val['box2']['mail_box_port'] : '';
		$val6_box2 = isset( $val['box2']['pwd_box'] ) ? $val['box2']['pwd_box'] : '';

		$val7_box2     = isset( $val['box2']['mail_folders'] ) ? $val['box2']['mail_folders'] : '';
		$val7_box2_alt = isset( $val['box2']['mail_folders_alt'] ) ? $val['box2']['mail_folders_alt'] : '';

		$val8_box2  = isset( $val['box2']['smtp_server'] ) ? $val['box2']['smtp_server'] : '';
		$val9_box2  = isset( $val['box2']['smtp_box_encryption'] ) ? $val['box2']['smtp_box_encryption'] : '';
		$val10_box2 = isset( $val['box2']['smtp_box_port'] ) ? $val['box2']['smtp_box_port'] : '';
		$sel1       = '';
		$sel2       = '';
		$sel3       = '';
		$sel4       = '';
		switch ( $val4_box2 ) {
			case 'Auto':
				$sel1 = 'selected';
				break;
			case 'No':
				$sel2 = 'selected';
				break;
			case 'SSL':
				$sel3 = 'selected';
				break;
			case 'TLS':
				$sel4 = 'selected';
				break;
		}
		$sel1_smtp = '';
		$sel2_smtp = '';
		$sel3_smtp = '';
		$sel4_smtp = '';
		switch ( $val9_box2 ) {
			case 'Auto':
				$sel1_smtp = 'selected';
				break;
			case 'No':
				$sel2_smtp = 'selected';
				break;
			case 'SSL':
				$sel3_smtp = 'selected';
				break;
			case 'TLS':
				$sel4_smtp = 'selected';
				break;
		}
		$_smtp = filter_input( INPUT_GET, 'smtp', FILTER_SANITIZE_STRING );
		if ( 'box2_smtp' === $_smtp ) {
			$message_smtp = wms7_msg_smtp( 'Checking the connection to the server:' . $val8_box2 );
		} else {
			$message_smtp = '';
		}
		?>
		<div id="param_mail_box_yandex_ru" style="width:100%;">
			<fieldset style="width:860px;height:210px;border: 2px groove;margin:0;padding:0;">
				<legend><b>Yandex.ru</b></legend>
				<table  cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="padding:0;">
							<input id="imap_server_box2" style="width: 200px;" name="wms7_main_settings[box2][imap_server]" type="text" placeholder="imap server" value="<?php echo esc_html( $val1_box2 ); ?>" /><br>
							<label for="imap_server_box2" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'IMAP Server name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_name_box2" style="width: 200px;" name="wms7_main_settings[box2][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo esc_html( $val2_box2 ); ?>" /><br>
							<label for="mail_box_name_box2" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_pwd_box2" style="width: 200px;" name="wms7_main_settings[box2][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo esc_html( $val3_box2 ); ?>" /><br>
							<label for="mail_box_pwd_box2" style="overflow: hidden;text-overflow: ellipsis;width: 180px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox password', 'watchman-site7' ); ?></label>

							<input id="pwd_box2" name="wms7_main_settings[box2][pwd_box]" type="checkbox" value="1" style="float: right;margin-top:7px;"<?php checked( $val6_box2 ); ?> onClick="wms7_check_pwd(id)"/>
						</td>

						<td style="padding:0px 5px;">
							<select id="encryption_box2" name="wms7_main_settings[box2][mail_box_encryption]" ><option <?php echo esc_html( $sel1 ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2 ); ?> value="No">No</option><option <?php echo esc_html( $sel3 ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4 ); ?> value="TLS">TLS</option></select><br>

							<label for="encryption_box2" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Encrypt', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_port_box2" name="wms7_main_settings[box2][mail_box_port]" type=text placeholder="993" value="<?php echo esc_html( $val5_box2 ); ?>" style="width: 60px;"/><br>
							<label for="mail_box_port_box2" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box2" style="margin-top:-12px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" />Check</button><br>

							<button type="button" id="textbox2" style="margin-top:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box2','tbl_folders_box2','text_folders_box2','text_folders_box2_alt')" />Select</button>
						</td>
						<td style="padding:0;">
							<?php
							$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
							if ( 'box2' === $_checkbox ) {
								?>
							<textarea readonly id="text_folders_box2" name="wms7_main_settings[box2][mail_folders]" style="position:relative;z-index:100;width:350px;height:180px;"><?php echo esc_textarea( $val7_box2 ); ?></textarea>
							<div id="tbl_folders_box2" style="position:relative;z-index:101;margin-top:-183px;padding:0;width:350px;height:180px;">
								<table class="table_box" style="background-color: #5B5B59;">
									<thead class="thead_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</thead>
									<tbody class="tbody" style= "height:120px;max-height:180px;width:345px;">
									<?php
									$imap_list2 = $this->wms7_imap_list( 'box2' );
									$i          = 0;
									foreach ( $imap_list2 as $value ) {
										$input_id = 'box2_chk' . $i;
										$str      = explode( '|', $value );
										?>
										<tr class='tr_box'>
											<td class='td_box' width='19%'>
											<input name='box2_chk' id=<?php echo esc_html( $input_id ); ?> type='checkbox' ><?php echo esc_html( $i ); ?>
											</td>
											<td class='td_box' width='81%' data="<?php echo esc_html( $str[1] ); ?>" ><?php echo esc_html( $str[0] ); ?>
											</td>
										</tr>
										<?php
										$i++;
									}
									?>
									</tbody>
									<tfoot class="tfoot_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</tfoot>
								</table>
							</div>
								<?php
							} else {
								?>
							<textarea readonly id="text_folders_box2" name="wms7_main_settings[box2][mail_folders]" style="position:relative;z-index:102;width:350px;height:180px;"><?php echo esc_textarea( $val7_box2 ); ?></textarea>
								<?php
							}
							?>
						</td>
						<td style="padding:0px 5px;">
							<input id="smtp_server_box2" style="width: 200px;" name="wms7_main_settings[box2][smtp_server]" type="text" placeholder="smtp server" value="<?php echo esc_html( $val8_box2 ); ?>" /><br>

							<label for="smtp_server_box2" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Server name', 'watchman-site7' ); ?></label><br>

							<select id="smtp_encryption_box2" name="wms7_main_settings[box2][smtp_box_encryption]" ><option <?php echo esc_html( $sel1_smtp ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2_smtp ); ?> value="No">No</option><option <?php echo esc_html( $sel3_smtp ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4_smtp ); ?> value="TLS">TLS</option></select>

							<input id="smtp_box_port_box2" style="margin-left:75px;width:60px;" name="wms7_main_settings[box2][smtp_box_port]" type=text placeholder="465" value="<?php echo esc_html( $val10_box2 ); ?>" /><br>

							<label for="smtp_encryption_box2" style="overflow: hidden;text-overflow: ellipsis;width: 90px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Encrypt', 'watchman-site7' ); ?></label>
							<label for="smtp_box_port_box2" style="float: right;overflow: hidden;text-overflow: ellipsis;width: 80px;height:20px;white-space: nowrap;padding:0;" ><?php esc_html_e( 'SMTP Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box2_smtp" name="check_smtp" class="button-primary" style="margin-top:3px;margin-bottom:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" onClick="wms7_check_smtp(id)"  />Check</button>
							<input id="box2_msg_smtp" type="text" name="msg_smtp" style="width:200px;"  value="<?php echo esc_html( $message_smtp ); ?>" />
						</td>
					</tr>
				</table>
				<textarea id="text_folders_box2_alt" name="wms7_main_settings[box2][mail_folders_alt]" style="visibility:hidden;"><?php echo esc_textarea( $val7_box2_alt ); ?></textarea>
			</fieldset>
		</div>
		<?php
		// yahoo.com.
		$val       = get_option( 'wms7_main_settings' );
		$val1_box3 = isset( $val['box3']['imap_server'] ) ? $val['box3']['imap_server'] : '';
		$val2_box3 = isset( $val['box3']['mail_box_name'] ) ? $val['box3']['mail_box_name'] : '';
		$val3_box3 = isset( $val['box3']['mail_box_pwd'] ) ? $val['box3']['mail_box_pwd'] : '';
		$val4_box3 = isset( $val['box3']['mail_box_encryption'] ) ? $val['box3']['mail_box_encryption'] : '';
		$val5_box3 = isset( $val['box3']['mail_box_port'] ) ? $val['box3']['mail_box_port'] : '';
		$val6_box3 = isset( $val['box3']['pwd_box'] ) ? $val['box3']['pwd_box'] : '';

		$val7_box3     = isset( $val['box3']['mail_folders'] ) ? $val['box3']['mail_folders'] : '';
		$val7_box3_alt = isset( $val['box3']['mail_folders_alt'] ) ? $val['box3']['mail_folders_alt'] : '';

		$val8_box3  = isset( $val['box3']['smtp_server'] ) ? $val['box3']['smtp_server'] : '';
		$val9_box3  = isset( $val['box3']['smtp_box_encryption'] ) ? $val['box3']['smtp_box_encryption'] : '';
		$val10_box3 = isset( $val['box3']['smtp_box_port'] ) ? $val['box3']['smtp_box_port'] : '';
		$sel1       = '';
		$sel2       = '';
		$sel3       = '';
		$sel4       = '';
		switch ( $val4_box3 ) {
			case 'Auto':
				$sel1 = 'selected';
				break;
			case 'No':
				$sel2 = 'selected';
				break;
			case 'SSL':
				$sel3 = 'selected';
				break;
			case 'TLS':
				$sel4 = 'selected';
				break;
		}
		$sel1_smtp = '';
		$sel2_smtp = '';
		$sel3_smtp = '';
		$sel4_smtp = '';
		switch ( $val9_box3 ) {
			case 'Auto':
				$sel1_smtp = 'selected';
				break;
			case 'No':
				$sel2_smtp = 'selected';
				break;
			case 'SSL':
				$sel3_smtp = 'selected';
				break;
			case 'TLS':
				$sel4_smtp = 'selected';
				break;
		}
		$_smtp = filter_input( INPUT_GET, 'smtp', FILTER_SANITIZE_STRING );
		if ( 'box3_smtp' === $_smtp ) {
			$message_smtp = wms7_msg_smtp( 'Checking the connection to the server: ' . $val8_box3 );
		} else {
			$message_smtp = '';
		}
		?>
		<div id="param_mail_box_yahoo_com" style="width:100%;">
			<fieldset style="width:860px;height:210px;border: 2px groove;margin:0;padding:0;">
			<legend><b>Yahoo.com</b></legend>
				<table  cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="padding:0;">
							<input id="imap_server_box3" style="width: 200px;" name="wms7_main_settings[box3][imap_server]" type="text" placeholder="imap server" value="<?php echo esc_html( $val1_box3 ); ?>" /><br>
							<label for="imap_server_box3" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'IMAP Server name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_name_box3" style="width: 200px;" name="wms7_main_settings[box3][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo esc_html( $val2_box3 ); ?>" /><br>
							<label for="mail_box_name_box3" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_pwd_box3" style="width: 200px;" name="wms7_main_settings[box3][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo esc_html( $val3_box3 ); ?>" /><br>
							<label for="mail_box_pwd_box3" style="overflow: hidden;text-overflow: ellipsis;width: 180px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox password', 'watchman-site7' ); ?></label>

							<input id="pwd_box3" name="wms7_main_settings[box3][pwd_box]" type="checkbox" value="1" style="float: right;margin-top:7px;"<?php checked( $val6_box3 ); ?> onClick="wms7_check_pwd(id)"/>
						</td>
						<td style="padding:0px 5px;">
							<select id="encryption_box3" name="wms7_main_settings[box3][mail_box_encryption]" ><option <?php echo esc_html( $sel1 ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2 ); ?> value="No">No</option><option <?php echo esc_html( $sel3 ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4 ); ?> value="TLS">TLS</option></select><br>

							<label for="encryption_box3" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Encrypt', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_port_box3" name="wms7_main_settings[box3][mail_box_port]" type=text placeholder="993" value="<?php echo esc_html( $val5_box3 ); ?>" style="width: 60px;"/><br>
							<label for="mail_box_port_box3" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box3" style="margin-top:-12px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" />Check</button><br>

							<button type="button" id="textbox3" style="margin-top:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box3','tbl_folders_box3','text_folders_box3','text_folders_box3_alt')" />Select</button>
						</td>
						<td style="padding:0;">
							<?php
							$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
							if ( 'box3' === $_checkbox ) {
								?>
							<textarea readonly id="text_folders_box3" name="wms7_main_settings[box3][mail_folders]" style="position:relative;z-index:100;width:350px;height:180px;"><?php echo esc_textarea( $val7_box3 ); ?></textarea>
							<div id="tbl_folders_box3" style="position:relative;z-index:101;margin-top:-183px;padding:0;width:350px;height:180px;">
								<table class="table_box" style="background-color: #5B5B59;">
									<thead class="thead_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</thead>
									<tbody class="tbody" style= "height:120px;max-height:180px;width:345px;">
									<?php
									$imap_list3 = $this->wms7_imap_list( 'box3' );
									$i          = 0;
									foreach ( $imap_list3 as $value ) {
										$input_id = 'box3_chk' . $i;
										$str      = explode( '|', $value );
										?>
										<tr class='tr_box'>
											<td class='td_box' width='19%'>
											<input name='box3_chk' id=<?php echo esc_html( $input_id ); ?> type='checkbox' ><?php echo esc_html( $i ); ?>
											</td>
											<td class='td_box' width='81%' data="<?php echo esc_html( $str[1] ); ?>" ><?php echo esc_html( $str[0] ); ?>
											</td>
										</tr>
										<?php
										$i++;
									}
									?>
									</tbody>
									<tfoot class="tfoot_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</tfoot>
								</table>
							</div>
								<?php
							} else {
								?>
							<textarea readonly id="text_folders_box3" name="wms7_main_settings[box3][mail_folders]" style="position:relative;z-index:102;width:350px;height:180px;"><?php echo esc_textarea( $val7_box3 ); ?></textarea>
								<?php
							}
							?>
						</td>
						<td style="padding:0px 5px;">
							<input id="smtp_server_box3" style="width: 200px;" name="wms7_main_settings[box3][smtp_server]" type="text" placeholder="smtp server" value="<?php echo esc_html( $val8_box3 ); ?>" /><br>

							<label for="smtp_server_box3" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Server name', 'watchman-site7' ); ?></label><br>

							<select id="smtp_encryption_box3" name="wms7_main_settings[box3][smtp_box_encryption]" ><option <?php echo esc_html( $sel1_smtp ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2_smtp ); ?> value="No">No</option><option <?php echo esc_html( $sel3_smtp ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4_smtp ); ?> value="TLS">TLS</option></select>

							<input id="smtp_box_port_box3" style="margin-left:75px;width:60px;" name="wms7_main_settings[box3][smtp_box_port]" type=text placeholder="465" value="<?php echo esc_html( $val10_box3 ); ?>" /><br>

							<label for="smtp_encryption_box3" style="overflow: hidden;text-overflow: ellipsis;width: 90px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Encrypt', 'watchman-site7' ); ?></label>
							<label for="smtp_box_port_box3" style="float: right;overflow: hidden;text-overflow: ellipsis;width: 80px;height:20px;white-space: nowrap;padding:0;" ><?php esc_html_e( 'SMTP Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box3_smtp" name="check_smtp" class="button-primary" style="margin-top:3px;margin-bottom:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" onClick="wms7_check_smtp(id)"  />Check</button>
							<input id="box3_msg_smtp" type="text" name="msg_smtp" style="width:200px;"  value="<?php echo esc_html( $message_smtp ); ?>" />    
						</td>
					</tr>
				</table>
				<textarea id="text_folders_box3_alt" name="wms7_main_settings[box3][mail_folders_alt]" style="visibility:hidden;"><?php echo esc_textarea( $val7_box3_alt ); ?></textarea>
			</fieldset>
		</div>
		<?php
		// gmail.com.
		$val       = get_option( 'wms7_main_settings' );
		$val1_box4 = isset( $val['box4']['imap_server'] ) ? $val['box4']['imap_server'] : '';
		$val2_box4 = isset( $val['box4']['mail_box_name'] ) ? $val['box4']['mail_box_name'] : '';
		$val3_box4 = isset( $val['box4']['mail_box_pwd'] ) ? $val['box4']['mail_box_pwd'] : '';
		$val4_box4 = isset( $val['box4']['mail_box_encryption'] ) ? $val['box4']['mail_box_encryption'] : '';
		$val5_box4 = isset( $val['box4']['mail_box_port'] ) ? $val['box4']['mail_box_port'] : '';
		$val6_box4 = isset( $val['box4']['pwd_box'] ) ? $val['box4']['pwd_box'] : '';

		$val7_box4     = isset( $val['box4']['mail_folders'] ) ? $val['box4']['mail_folders'] : '';
		$val7_box4_alt = isset( $val['box4']['mail_folders_alt'] ) ? $val['box4']['mail_folders_alt'] : '';

		$val8_box4  = isset( $val['box4']['smtp_server'] ) ? $val['box4']['smtp_server'] : '';
		$val9_box4  = isset( $val['box4']['smtp_box_encryption'] ) ? $val['box4']['smtp_box_encryption'] : '';
		$val10_box4 = isset( $val['box4']['smtp_box_port'] ) ? $val['box4']['smtp_box_port'] : '';
		$sel1       = '';
		$sel2       = '';
		$sel3       = '';
		$sel4       = '';
		switch ( $val4_box4 ) {
			case 'Auto':
				$sel1 = 'selected';
				break;
			case 'No':
				$sel2 = 'selected';
				break;
			case 'SSL':
				$sel3 = 'selected';
				break;
			case 'TLS':
				$sel4 = 'selected';
				break;
		}
		$sel1_smtp = '';
		$sel2_smtp = '';
		$sel3_smtp = '';
		$sel4_smtp = '';
		switch ( $val9_box4 ) {
			case 'Auto':
				$sel1_smtp = 'selected';
				break;
			case 'No':
				$sel2_smtp = 'selected';
				break;
			case 'SSL':
				$sel3_smtp = 'selected';
				break;
			case 'TLS':
				$sel4_smtp = 'selected';
				break;
		}
		$_smtp = filter_input( INPUT_GET, 'smtp', FILTER_SANITIZE_STRING );
		if ( 'box4_smtp' === $_smtp ) {
			$message_smtp = wms7_msg_smtp( 'Checking the connection to the server: ' . $val8_box4 );
		} else {
			$message_smtp = '';
		}
		?>
		<div id="param_mail_box_gmail_com" style="width:100%;">
			<fieldset style="width:860px;height:210px;border: 2px groove;margin:0;padding:0;">
				<legend><b>Gmail.com</b></legend>

				<table  cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="padding:0;">
							<input id="imap_server_box4" style="width: 200px;" name="wms7_main_settings[box4][imap_server]" type="text" placeholder="imap server" value="<?php echo esc_html( $val1_box4 ); ?>" /><br>
							<label for="imap_server_box4" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'IMAP Server name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_name_box4" style="width: 200px;" name="wms7_main_settings[box4][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo esc_html( $val2_box4 ); ?>" /><br>
							<label for="mail_box_name_box4" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox name', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_pwd_box4" style="width: 200px;" name="wms7_main_settings[box4][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo esc_html( $val3_box4 ); ?>" /><br>
							<label for="mail_box_pwd_box4" style="overflow: hidden;text-overflow: ellipsis;width: 180px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'MailBox password', 'watchman-site7' ); ?></label>

							<input id="pwd_box4" name="wms7_main_settings[box4][pwd_box]" type="checkbox" value="1" style="float: right;margin-top:7px;"<?php checked( $val6_box0 ); ?> onClick="wms7_check_pwd(id)"/>    
						</td>
						<td style="padding:0px 5px;">
							<select id="encryption_box4" name="wms7_main_settings[box4][mail_box_encryption]" ><option <?php echo esc_html( $sel1 ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2 ); ?> value="No">No</option><option <?php echo esc_html( $sel3 ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4 ); ?> value="TLS">TLS</option></select><br>

							<label for="encryption_box4" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Encrypt', 'watchman-site7' ); ?></label><br>

							<input id="mail_box_port_box4" name="wms7_main_settings[box4][mail_box_port]" type=text placeholder="993" value="<?php echo esc_html( $val5_box0 ); ?>" style="width: 60px;"/><br>
							<label for="mail_box_port_box4" style="overflow: hidden;text-overflow: ellipsis;width: 60px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box4" style="margin-top:-12px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" />Check</button><br>

							<button type="button" id="textbox4" style="margin-top:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box4','tbl_folders_box4','text_folders_box4','text_folders_box4_alt')" />Select</button>
						</td>
						<td style="padding:0;">
							<?php
							$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
							if ( 'box4' === $_checkbox ) {
								?>
							<textarea readonly id="text_folders_box4" name="wms7_main_settings[box4][mail_folders]" style="position:relative;z-index:100;width:350px;height:180px;"><?php echo esc_textarea( $val7_box4 ); ?></textarea>
							<div id="tbl_folders_box4" style="position:relative;z-index:101;margin-top:-183px;padding:0;width:350px;height:180px;">
								<table class="table_box" style="background-color: #5B5B59;">
									<thead class="thead_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</thead>
									<tbody class="tbody" style= "height:120px;max-height:180px;width:345px;">
									<?php
									$imap_list4 = $this->wms7_imap_list( 'box4' );
									$i          = 0;
									foreach ( $imap_list4 as $value ) {
										$input_id = 'box4_chk' . $i;
										$str      = explode( '|', $value );
										?>
										<tr class='tr_box'>
											<td class='td_box' width='19%'>
											<input name='box4_chk' id=<?php echo esc_html( $input_id ); ?> type='checkbox' ><?php echo esc_html( $i ); ?>
											</td>
											<td class='td_box' width='81%' data="<?php echo esc_html( $str[1] ); ?>" ><?php echo esc_html( $str[0] ); ?>
											</td>
										</tr>
										<?php
										$i++;
									}
									?>
									</tbody>
									<tfoot class="tfoot_box">
										<tr class="tr_box">
											<td class='td_box' width='16%' style='cursor: pointer;'>id</td>
											<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>
										</tr>
									</tfoot>
								</table>
							</div>
								<?php
							} else {
								?>
							<textarea readonly id="text_folders_box4" name="wms7_main_settings[box4][mail_folders]" style="position:relative;z-index:102;width:350px;height:180px;"><?php echo esc_textarea( $val7_box4 ); ?></textarea>
								<?php
							}
							?>
						</td>
						<td style="padding:0px 5px;">
							<input id="smtp_server_box4" style="width: 200px;" name="wms7_main_settings[box4][smtp_server]" type="text" placeholder="smtp server" value="<?php echo esc_html( $val8_box4 ); ?>" /><br>

							<label for="smtp_server_box4" style="overflow: hidden;text-overflow: ellipsis;width: 200px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Server name', 'watchman-site7' ); ?></label><br>

							<select id="smtp_encryption_box4" name="wms7_main_settings[box4][smtp_box_encryption]" ><option <?php echo esc_html( $sel1_smtp ); ?> value="Auto">Auto</option><option <?php echo esc_html( $sel2_smtp ); ?> value="No">No</option><option <?php echo esc_html( $sel3_smtp ); ?> value="SSL">SSL</option><option <?php echo esc_html( $sel4_smtp ); ?> value="TLS">TLS</option></select>

							<input id="smtp_box_port_box4" style="margin-left:75px;width:60px;" name="wms7_main_settings[box4][smtp_box_port]" type=text placeholder="465" value="<?php echo esc_html( $val10_box4 ); ?>" /><br>

							<label for="smtp_encryption_box4" style="overflow: hidden;text-overflow: ellipsis;width: 90px;height:20px;white-space: nowrap;padding:0;"><?php esc_html_e( 'SMTP Encrypt', 'watchman-site7' ); ?></label>
							<label for="smtp_box_port_box4" style="float: right;overflow: hidden;text-overflow: ellipsis;width: 80px;height:20px;white-space: nowrap;padding:0;" ><?php esc_html_e( 'SMTP Port', 'watchman-site7' ); ?></label><br>

							<button type="button" id="box4_smtp" name="check_smtp" class="button-primary" style="margin-top:3px;margin-bottom:3px;overflow: hidden;text-overflow: ellipsis;width:63px;" onClick="wms7_check_smtp(id)"  />Check</button>
							<input id="box4_msg_smtp" type="text" name="msg_smtp" style="width:200px;"  value="<?php echo esc_html( $message_smtp ); ?>" />
						</td>
					</tr>
				</table>
				<textarea id="text_folders_box4_alt" name="wms7_main_settings[box4][mail_folders_alt]" style="visibility:hidden;"><?php echo esc_textarea( $val7_box4_alt ); ?></textarea>
			</fieldset>
		</div>
		<?php
	}
	/**
	 * Filling option10 (MailBox select) on page Settings.
	 */
	public function wms7_main_setting_field10() {
		$val      = get_option( 'wms7_main_settings' );
		$val      = isset( $val['mail_select'] ) ? $val['mail_select'] : 'box0';
		$checked0 = '';
		$checked1 = '';
		$checked2 = '';
		$checked3 = '';
		$checked4 = '';

		switch ( $val ) {
			case 'box0':
				$checked0 = 'checked';
				break;
			case 'box1':
				$checked1 = 'checked';
				break;
			case 'box2':
				$checked2 = 'checked';
				break;
			case 'box3':
				$checked3 = 'checked';
				break;
			case 'box4':
				$checked4 = 'checked';
				break;
		}
		?>
		<input type="radio" value="box0" <?php echo esc_html( $checked0 ); ?> id="mailbox0" name="wms7_main_settings[mail_select]" onClick="wms7_settings_sound()">
		<label for="mailbox0"><?php esc_html_e( 'This site', 'watchman-site7' ); ?></label><br>

		<input type="radio" value="box1" <?php echo esc_html( $checked1 ); ?> id="mailbox1" name="wms7_main_settings[mail_select]" onClick="wms7_settings_sound()">
		<label for="mailbox1">Mail.ru</label><br>

		<input type="radio" value="box2" <?php echo esc_html( $checked2 ); ?> id="mailbox2" name="wms7_main_settings[mail_select]" onClick="wms7_settings_sound()">
		<label for="mailbox2">Yandex.ru</label><br>

		<input type="radio" value="box3" <?php echo esc_html( $checked3 ); ?> id="mailbox3" name="wms7_main_settings[mail_select]" onClick="wms7_settings_sound()">
		<label for="mailbox3">Yahoo.com</label><br>

		<input type="radio" value="box4" <?php echo esc_html( $checked4 ); ?> id="mailbox4" name="wms7_main_settings[mail_select]" onClick="wms7_settings_sound()">
		<label for="mailbox4">Gmail.com</label>

		<?php
	}
	/**
	 * Filling option11 (E-mail folder tmp) on page Settings.
	 */
	public function wms7_main_setting_field11() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['mail_box_tmp'] ) ? $val['mail_box_tmp'] : '/tmp';
		?>
		<input id="wms7_main_settings[mail_box_tmp]" style="margin: 0px; width: 320px; height: 25px;" name="wms7_main_settings[mail_box_tmp]" type="text" placeholder="/tmp"value="<?php echo esc_html( $val ); ?>" /><br><label><?php esc_html_e( 'Create a directory in the root of the site for temporary storage of files attached to the mail', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option12 (SSE sound) on page Settings.
	 */
	public function wms7_main_setting_field12() {
		$val = get_option( 'wms7_main_settings' );
		$fIn = isset( $val['fIn'] ) ? $val['fIn'] : '';
		$tIn = isset( $val['tIn'] ) ? $val['tIn'] : '';
		$vIn = isset( $val['vIn'] ) ? $val['vIn'] : '';
		$dIn = isset( $val['dIn'] ) ? $val['dIn'] : '';
		?>
		<table>
			<tr>
				<td style='height:20px;padding:0;margin:0;'>
					<label>frequency</label>
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<input type="range" id="fIn" name="wms7_main_settings[fIn]" value="<?php echo esc_html( $fIn ); ?>" min="40" max="6000" oninput="wms7_show()" />
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<span id="fOut"></span>
				</td>
			</tr>
			<tr>
				<td style='height:20px;padding:0;margin:0;'>
					<label>type</label>
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<input type="range" id="tIn" name="wms7_main_settings[tIn]" value="<?php echo esc_html( $tIn ); ?>" min="0" max="3" oninput="wms7_show()" />
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<span id="tOut"></span>
				</td>
			</tr>
			<tr>
				<td style='height:20px;padding:0;margin:0;'>
					<label>volume</label>
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<input type="range" id="vIn" name="wms7_main_settings[vIn]" value="<?php echo esc_html( $vIn ); ?>" min="0" max="100" oninput="wms7_show()" />
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<span id="vOut"></span>
				</td>
			</tr>
			<tr>
				<td style='height:20px;padding:0;margin:0;'>
					<label>duration</label>
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<input type="range" id="dIn" name="wms7_main_settings[dIn]" value="<?php echo esc_html( $dIn ); ?>" min="1" max="5000" oninput="wms7_show()" />
				</td>
				<td style='height:20px;padding:0;margin:0;'>
					<span id="dOut"></span>
				</td>
			</tr>      
		</table>
		<br>
		<input type="button" value="Play" onclick='wms7_beep();' />
		<br>
		<label><?php esc_html_e( 'It is intended for sound maintenance of updating of the screen at receipt of new visitors of the website', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Filling option13 (Google reCAPTCHA) on page Settings.
	 */
	public function wms7_main_setting_field13() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['recaptcha'] ) ? $val['recaptcha'] : '';
		?>
		<input id="wms7_main_settings[recaptcha]" name="wms7_main_settings[recaptcha]" type="checkbox" value="1" <?php checked( $val ); ?> /><br><label for="wms7_main_settings[recaptcha]"><?php esc_html_e( 'Google reCAPTCHA on the website (on / off). You have to', 'watchman-site7' ); ?></label> <a href="https://www.google.com/recaptcha/admin" target="_blank">register this website </a><?php esc_html_e( 'first, get required keys (reCAPTCHA v2) from Google and save them bellow.', 'watchman-site7' ); ?>
		<?php
	}
	/**
	 * Filling option14 (Google reCAPTCHA Site Key) on page Settings.
	 */
	public function wms7_main_setting_field14() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['recaptcha_site_key'] ) ? $val['recaptcha_site_key'] : '';
		?>
		<input id="wms7_main_settings[recaptcha_site_key]" style="margin: 0px; width: 350px; height: 25px;" name="wms7_main_settings[recaptcha_site_key]" type="text" placeholder="Site Key"value="<?php echo esc_html( $val ); ?>" />
		<?php
	}
	/**
	 * Filling option15 (Google reCAPTCHA Secret Key) on page Settings.
	 */
	public function wms7_main_setting_field15() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['recaptcha_secret_key'] ) ? $val['recaptcha_secret_key'] : '';
		?>
		<input id="wms7_main_settings[recaptcha_secret_key]" style="margin: 0px; width: 350px; height: 25px;" name="wms7_main_settings[recaptcha_secret_key]" type="text" placeholder="Secret Key"value="<?php echo esc_html( $val ); ?>" />
		<?php
	}
	/**
	 * Filling option16 (Attack analyzer) on page Settings.
	 */
	public function wms7_main_setting_field16() {
		$val = get_option( 'wms7_main_settings' );
		$val = isset( $val['attack_analyzer'] ) ? $val['attack_analyzer'] : '';
		?>
		<input id="wms7_main_settings[attack_analyzer]" name="wms7_main_settings[attack_analyzer]" type="checkbox" value="1" <?php checked( $val ); ?> /><br><label for="wms7_main_settings[attack_analyzer]"><?php esc_html_e( 'In a critical situation, increases the security of the site. Protects categories of visitors: administrator, author', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Forms a table of all mailbox folders for Settins page.
	 *
	 * @return string.
	 */
	private function wms7_imap_list() {
		$_checkbox = filter_input( INPUT_GET, 'checkbox', FILTER_SANITIZE_STRING );
		$val       = get_option( 'wms7_main_settings' );
		$box       = $val[ $_checkbox ];
		if ( $box['imap_server'] ) {
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}INBOX';
			$imap   = imap_open( $server, $box['mail_box_name'], $box['mail_box_pwd'] );

			if ( is_resource( $imap ) ) {
				$list = imap_list( $imap, '{' . $box['imap_server'] . '}', '*' );
				if ( is_array( $list ) ) {
					$list = wms7_imap_list_decode( $list );
				} else {
					$list = 'imap_list failed: ' . imap_last_error();
				}
			} else {
				$list = imap_last_error() .
				'<br>server: ' . $server .
				'<br>username: ' . $box['mail_box_name'] .
				'<br>password: ' . $box['mail_box_pwd'];
			}
			return $list;
		}
	}
	/**
	 * Generates data for the InfoPanel.
	 */
	private function wms7_info_panel() {
		$val          = get_option( 'wms7_screen_settings' );
		$setting_list = isset( $val['setting_list'] ) ? $val['setting_list'] : 0;
		$history_list = isset( $val['history_list'] ) ? $val['history_list'] : 0;
		$robots_list  = isset( $val['robots_list'] ) ? $val['robots_list'] : 0;
		$black_list   = isset( $val['black_list'] ) ? $val['black_list'] : 0;

		$val       = $setting_list + $history_list + $robots_list + $black_list;
		$width_box = '';
		switch ( $val ) {
			case '1':
				$width_box = '98%';
				break;
			case '2':
				$width_box = '49%';
				break;
			case '3':
				$width_box = '32.5%';
				break;
			case '4':
				$width_box = '24.5%';
				break;
		}

		$panel_info_hidden = ( '1' !== $setting_list && '1' !== $history_list && '1' !== $robots_list && '1' !== $black_list ) ? 'hidden' : '';

		$hidden_setting_list = ( '1' === $setting_list ) ? '' : 'hidden';
		$hidden_history_list = ( '1' === $history_list ) ? '' : 'hidden';
		$hidden_robots_list  = ( '1' === $robots_list ) ? '' : 'hidden';
		$hidden_black_list   = ( '1' === $black_list ) ? '' : 'hidden';

		$val             = get_option( 'wms7_main_settings' );
		$log_duration    = isset( $val['log_duration'] ) ? $val['log_duration'] : 0;
		$ip_excluded     = isset( $val['ip_excluded'] ) ? $val['ip_excluded'] : '';
		$robots_reg      = isset( $val['robots_reg'] ) ? 'Yes' : 'No';
		$whois_service   = isset( $val['whois_service'] ) ? $val['whois_service'] : 'none';
		$recaptcha       = isset( $val['recaptcha'] ) ? 'Yes' : 'No';
		$attack_analyzer = isset( $val['attack_analyzer'] ) ? 'Yes' : 'No';

		if ( ! extension_loaded('imap') ) {
			$imap = '';
		} else {
			$val        = get_option( 'wms7_main_settings' );
			$select_box = isset( $val['mail_select'] ) ? $val['mail_select'] : '';
			$box        = isset( $val[ $select_box ] ) ? $val[ $select_box ] : '';
			$box        = isset( $box['imap_server'] ) ? $box['imap_server'] : '';
			$pos        = strpos( $box, '.' );
			$box        = substr( $box, $pos + 1 );

			$unseen = wms7_mail_unseen();
			$imap   = esc_html( 'Mail box', 'watchman-site7' ) . ': ' . esc_html( $box ) . ' (' . esc_html( $unseen ) . ')&#010;';
		}
		$black_list = $this->wms7_black_list_info();

		$str = esc_html( $imap ) .
		esc_html( 'Google reCAPTCHA', 'watchman-site7' ) . ': ' . esc_html( $recaptcha ) . '&#010;' .
		esc_html( 'Attack analyzer', 'watchman-site7' ) . ': ' . esc_html( $attack_analyzer ) . '&#010;' .
		esc_html( 'Visits of robots', 'watchman-site7' ) . ': ' . esc_html( $robots_reg ) . '&#010;' .
		esc_html( 'WHO-IS service', 'watchman-site7' ) . ': ' . esc_html( $whois_service ) . '&#010;' .
		esc_html( 'Duration log entries', 'watchman-site7' ) . ': ' . esc_html( $log_duration ) . ' ' . esc_html( 'day', 'watchman-site7' ) . '&#010;' .
		esc_html( 'Do not include visits for', 'watchman-site7' ) . ': ' . esc_html( $ip_excluded );
		?>
		<fieldset class = "info_panel" title="<?php echo esc_html( 'Panel info', 'watchman-site7' ); ?>" <?php echo esc_html( $panel_info_hidden ); ?> >
			<fieldset class = "info_settings" title="<?php echo esc_html( 'General settings', 'watchman-site7' ); ?>" <?php echo esc_html( $hidden_setting_list ); ?> style="width:<?php echo esc_html( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo esc_html( 'Settings', 'watchman-site7' ); ?></legend>
				<textarea class = "textarea_panel_info"><?php echo esc_html( $str ); ?></textarea>
			</fieldset>

			<fieldset class = "info_whois" title="<?php echo esc_html( 'History visits', 'watchman-site7' ); ?>" <?php echo esc_html( $hidden_history_list ); ?> style="width:<?php echo esc_html( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo esc_html( 'History list', 'watchman-site7' ); ?></legend>
				<textarea class = "textarea_panel_info"><?php echo esc_html( $this->wms7_history_list_info( $whois_service ) ); ?></textarea>
			</fieldset>

			<fieldset class="info_robots" title="<?php echo esc_html( 'Robots-last day visit', 'watchman-site7' ); ?>" <?php echo esc_html( $hidden_robots_list ); ?> style="width:<?php echo esc_html( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo esc_html( 'Robots list', 'watchman-site7' ); ?></legend>
				<textarea class ="textarea_panel_info"><?php echo esc_html( $this->wms7_robot_visit_info() ); ?></textarea>
			</fieldset>

			<fieldset class="info_blacklist" title="<?php echo esc_html( 'Black list', 'watchman-site7' ); ?>" <?php echo esc_html( $hidden_black_list ); ?> style="width:<?php echo esc_html( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo esc_html( 'Black list', 'watchman-site7' ); ?></legend>
				<textarea class = "textarea_panel_info"><?php echo esc_html( $black_list[1] ); ?></textarea>
			</fieldset>
		</fieldset>
		<?php
	}
	/**
	 * Generates data for the InfoPanel, Section4 - black list.
	 *
	 * @return array.
	 */
	private function wms7_black_list_info() {
		global $wpdb;
		$cache_key = 'wms7_black_list_info';
		if ( wp_using_ext_object_cache() ) {
			$results = wp_cache_get( $cache_key );
		} else {
			$results = get_option( $cache_key );
		}
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT `id`, `user_ip`, `black_list`, `info`
                    FROM {$wpdb->prefix}watchman_site
                    WHERE TRIM(`black_list`) <> %s
                    ",
					''
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results );
			} else {
				update_option( $cache_key, $results );
			}
		}
		$output                = '';
		$output_id             = '';
		$output_info           = '';
		$output_ban_user_agent = '';
		foreach ( $results as $row ) {
			$row_id                 = $row->id;
			$row_ip                 = $row->user_ip;
			$row_black_list         = json_decode( $row->black_list, true );
			$output_id             .= $row_id . '&#010;';
			$output                .= $row_black_list['ban_start_date'] . '&#009;' . $row_black_list['ban_end_date'] . '&#009;' . $row_ip . '&#010;';
			$row_info               = json_decode( $row->info, true );
			$row_info               = $row_info['User Agent'];
			$output_info           .= $row_info . '&#010;';
			$output_ban_user_agent .= $row_black_list['ban_user_agent'] . '&#010;';
		}
		$arr[0] = $output_id;
		$arr[1] = $output;
		$arr[2] = $output_info;
		$arr[3] = $output_ban_user_agent;

		return $arr;
	}
	/**
	 * Generates data for the InfoPanel, Section3 - robots.
	 *
	 * @return string.
	 */
	private function wms7_robot_visit_info() {
		global $wpdb;
		$cache_key = 'wms7_robot_visit_info';
		if ( wp_using_ext_object_cache() ) {
			$results = wp_cache_get( $cache_key );
		} else {
			$results = get_option( $cache_key );
		}
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT MAX(`time_visit`) as `date_visit`, `robot` 
                    FROM {$wpdb->prefix}watchman_site
                    WHERE `login_result`=%d
                    GROUP BY (`robot`)
                    ORDER BY `date_visit` DESC
                    ",
					3
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results );
			} else {
				update_option( $cache_key, $results );
			}
		}
		$output = '';
		foreach ( $results as $row ) {
			$output .= $row->date_visit . '&#009;' .
			$row->robot . '&#010;';
		}

		return $output;
	}
	/**
	 * Generates data for the InfoPanel, Section2 - history list.
	 *
	 * @param string $whois_service Property to get.
	 * @return string.
	 */
	private function wms7_history_list_info( $whois_service ) {
		global $wpdb;
		$cache_key = 'wms7_history_list_info';
		if ( wp_using_ext_object_cache() ) {
			$results = wp_cache_get( $cache_key );
		} else {
			$results = get_option( $cache_key );
		}
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT left(`time_visit`,10) as
                    `date_visit`, count(`login_result`) as
                    `count_all`, sum(`login_result`='0') as
                    `count0`, sum(`login_result`='1') as
                    `count1`, sum(`login_result`='2') as
                    `count2` , sum(`login_result`='3') as
                    `count3`
                    FROM {$wpdb->prefix}watchman_site
                    WHERE whois_service = %s
                    GROUP BY `date_visit`
                    ORDER BY `date_visit` DESC
                    ",
					$whois_service
				)
			);// db call ok; cache ok.
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $cache_key, $results );
			} else {
				update_option( $cache_key, $results );
			}
		}
		$output = '';
		foreach ( $results as $row ) {
			$output .= $row->date_visit . '&#009;' .
			'A' . $row->count_all . '&#009;' .
			'U' . $row->count2 . '&#009;' .
			'S' . $row->count1 . '&#009;' .
			'F' . $row->count0 . '&#009;' .
			'R' . $row->count3 . '&#010;';
		}

		return $output;
	}
	/**
	 * Creates Black list page of plugin.
	 */
	public function wms7_black_list() {
		$current_user = wp_get_current_user();
		$roles        = $current_user->roles;
		$role         = array_shift( $roles );
		if ( 'administrator' !== $role ) {
			exit;
		}
		global $wpdb;
		$_edit_nonce = filter_input( INPUT_GET, 'edit_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $_edit_nonce, 'edit_nonce' ) ) {
			$plugine_info    = get_plugin_data( WMS7_PLUGIN_DIR . '/watchman-site7.php' );
			$_id             = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			$_blacklist_save = filter_input( INPUT_POST, 'blacklist-save', FILTER_SANITIZE_STRING );
			$url             = get_option( 'wms7_current_url' );

			$_ban_start_date = filter_input( INPUT_POST, 'ban_start_date', FILTER_SANITIZE_NUMBER_INT );
			$_ban_end_date   = filter_input( INPUT_POST, 'ban_end_date', FILTER_SANITIZE_NUMBER_INT );
			$_ban_message    = filter_input( INPUT_POST, 'ban_message', FILTER_SANITIZE_STRING );
			$_ban_notes      = filter_input( INPUT_POST, 'ban_notes', FILTER_SANITIZE_STRING );
			$_ban_login      = filter_input( INPUT_POST, 'ban_login', FILTER_SANITIZE_NUMBER_INT );
			$_ban_user_agent = filter_input( INPUT_POST, 'ban_user_agent', FILTER_SANITIZE_NUMBER_INT );
			if ( $_ban_start_date && $_ban_end_date ) {
				$arr = array(
					'ban_start_date' => ( $_ban_start_date ) ? $_ban_start_date : '',
					'ban_end_date'   => ( $_ban_end_date ) ? $_ban_end_date : '',
					'ban_message'    => ( $_ban_message ) ? $_ban_message : '',
					'ban_notes'      => ( $_ban_notes ) ? $_ban_notes : '',
					'ban_login'      => ( $_ban_login ) ? $_ban_login : '',
					'ban_user_agent' => ( $_ban_user_agent ) ? $_ban_user_agent : '',
				);

				$serialized_data = wp_json_encode( $arr );

				$wpdb->update(
					$wpdb->prefix . 'watchman_site',
					array( 'black_list' => $serialized_data ),
					array( 'ID' => $_id ),
					array( '%s' )
				);// db call ok; no-cache ok.
			}
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT `user_ip`, `info`
                    FROM {$wpdb->prefix}watchman_site
                    WHERE `id` = %s
                    ",
					$_id
				),
				'ARRAY_A'
			);// db call ok; no-cache ok.
			$user_ip    = array_shift( $results[0] );
			$user_agent = json_decode( array_shift( $results[0] ), true );
			$user_agent = $user_agent['User Agent'];
			// here we adding our custom meta box.
			add_meta_box(
				'wms7_black_list_meta_box',
				'<font size="4">' . esc_html( 'Black list data for', 'watchman-site7' )
				. ': IP = ' . $user_ip . ' (id=' . $_id . '</font>)',
				array( $this, 'wms7_black_list_visitor' ),
				'wms7_black_list',
				'normal',
				'default'
			);
			?>
			<div class="wrap"><span class="dashicons dashicons-shield" style="float: left;"></span>
				<h1><?php echo esc_html( $plugine_info['Name'] ) . ': ' . esc_html( 'black list', 'watchman-site7' ); ?></h1>
				<?php
				if ( $_blacklist_save ) {
					$msg = esc_html( 'Black list item data saved successful:', 'watchman-site7' );
					?>
					<div class="updated notice is-dismissible" ><p><strong><?php echo esc_html( $msg ) . ' (id=' . esc_html( $_id ) . ') date-time: (' . esc_html( current_time( 'mysql' ) ) . ')'; ?></strong></p></div>
					<?php
					// Delete item from options.
					delete_option( 'wms7_login_compromising' );
					delete_option( 'wms7_ip_compromising' );
					delete_option( 'wms7_user_agent_compromising' );
					delete_option( 'wms7_black_list_info' );
					// Clear variables into $_SESSION.
					if ( isset( $_SESSION['wms7_black_list_tbl'] ) ) {
						unset( $_SESSION['wms7_black_list_tbl'] );
					}
					if ( function_exists( 'session_unregister' ) ) {
						session_unregister( 'wms7_black_list_tbl' );
					}
					// Insert user_agent into .htaccess.
					if ( $_ban_user_agent &&
						( date( 'Y-m-d' ) >= $_ban_start_date && date( 'Y-m-d' ) <= $_ban_end_date ) ) {
						wms7_rewritecond_insert( $user_agent );
					} else {
						// Delete user_agent into .htaccess.
						wms7_rewritecond_delete( $user_agent );
					}
					// Insert user_ip into .htaccess.
					if ( $user_ip &&
						( date( 'Y-m-d' ) >= $_ban_start_date && date( 'Y-m-d' ) <= $_ban_end_date ) ) {
						wms7_ip_insert_to_file( $user_ip );
					} else {
						// Delete user_ip into .htaccess.
						wms7_ip_delete_from_file( $user_ip );
					}
				}
				?>
				<form id="form" method="POST">
					<div class="metabox-holder" id="poststuff">
						<?php do_meta_boxes( 'wms7_black_list', 'normal', '' ); ?>
						<input type="submit" value="<?php esc_html_e( 'Save', 'watchman-site7' ); ?>" id="submit" class="button-primary" name="blacklist-save">
						<input type="button" value="<?php esc_html_e( 'Quit', 'watchman-site7' ); ?>" id="quit" class="button-primary" name="quit" onClick="location.href='<?php echo esc_url( $url ); ?>'">
					</div>
				</form>
			</div>
			<?php
		}
	}
	/**
	 * Creates custom fields on the Black list page.
	 */
	public function wms7_black_list_visitor() {
		global $wpdb;
		$result = array();
		$uid    = '';
		$_id    = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );

		if ( $_id ) {
			$cache_key = 'wms7_black_list_visitor';
			if ( wp_using_ext_object_cache() ) {
				$results = wp_cache_get( $cache_key );
			} else {
				$results = get_option( $cache_key );
			}
			if ( ! $results ) {
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"
	                    SELECT `uid`, `black_list`
	                    FROM {$wpdb->prefix}watchman_site
	                    WHERE `id` = %d
	                    ",
						$_id
					),
					'ARRAY_A'
				);// db call ok; cache ok.
				if ( wp_using_ext_object_cache() ) {
					wp_cache_set( $cache_key, $results );
				} else {
					update_option( $cache_key, $results );
				}
			}
		}
		$result     = array_shift( $result );
		$uid        = $result['uid'];
		$black_list = json_decode( $result['black_list'], true );
		?>
		<table class="form-table" cellspacing="0" cellpadding="10">
			<tr>        
				<th>
					<label for="ban_start_date"><?php esc_html_e( 'Ban start date', 'watchman-site7' ); ?></label>
				</th>      
				<td width="300">
					<input id="ban_start_date" name="ban_start_date" type="date" value="<?php echo esc_html( sanitize_text_field( $black_list['ban_start_date'] ) ); ?>"  placeholder="<?php esc_html_e( 'Ban start date', 'watchman-site7' ); ?>" required>
				</td>
				<th rowspan="2" style="width:50px;">
					<label for="ip_info"><?php esc_html_e( 'IP info', 'watchman-site7' ); ?></label>
				</th>
				<td rowspan="2">
					<textarea readonly id ="ip_info" name="ip_info" rows="6" style="width:100%;"><?php echo esc_html( $this->wms7_ip_info() ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="ban_end_date"><?php esc_html_e( 'Ban end date', 'watchman-site7' ); ?></label>
				</th>
				<td>
					<input id="ban_end_date" name="ban_end_date" type="date" value="<?php echo esc_html( sanitize_text_field( $black_list['ban_end_date'] ) ); ?>"  placeholder="<?php esc_html_e( 'Ban end date', 'watchman-site7' ); ?>" required>
				</td>
			</tr>
			<tr>
				<th>
					<label for="ban_message"><?php esc_html_e( 'Ban message', 'watchman-site7' ); ?></label>
				</th>
				<td colspan="3">
					<input id="ban_message" name="ban_message" type="text" value="<?php echo esc_html( sanitize_text_field( $black_list['ban_message'] ) ); ?>"  placeholder="<?php esc_html_e( 'Ban message', 'watchman-site7' ); ?>" required style="width:100%;">
				</td>
			</tr>
			<tr>
				<th>
					<label for="ban_notes"><?php esc_html_e( 'Ban notes', 'watchman-site7' ); ?></label>
			</th>
				<td colspan="3">
					<input id="ban_notes" name="ban_notes" type="text" value="<?php echo esc_html( sanitize_text_field( $black_list['ban_notes'] ) ); ?>" placeholder="<?php esc_html_e( 'Ban notes', 'watchman-site7' ); ?>" required style="width:100%;">
				</td>
			</tr>
			<?php
			if ( '0' !== $uid ) {
				?>
			<tr>
				<th>
					<label for="ban_login"><?php esc_html_e( 'Ban user login', 'watchman-site7' ); ?></label>
				</th>
				<td colspan="3">
					<input id="ban_login" name="ban_login" type="checkbox" value="1" <?php checked( sanitize_text_field( $black_list['ban_login'] ) ); ?>" >
				</td>
			</tr>
				<?php
			}
			?>
			<tr>
				<th>
					<label for="ban_user_agent"><?php esc_html_e( 'Ban user agent', 'watchman-site7' ); ?></label>
				</th>
				<td colspan="3">
					<input id="ban_user_agent" name="ban_user_agent" type="checkbox" value="1" <?php checked( sanitize_text_field( $black_list['ban_user_agent'] ) ); ?>" >
				</td>
			</tr>			
			</table>
			<label><?php esc_html_e( 'Note: Insert the shortcode - [black_list] in a page or an entry to display the table compromised IP addresses stored in the database -Black list.', 'watchman-site7' ); ?></label>
		<?php
	}
	/**
	 * Provides additional information about the ip visitor from the database.
	 *
	 * @return string Return info of the IP adress of visitor from the database.
	 */
	private function wms7_ip_info() {
		global $wpdb;
		$user_ip_info = '';

		$_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );

		if ( $_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT `user_ip_info`
                    FROM {$wpdb->prefix}watchman_site
                    WHERE `id` = %d
                    ",
					$_id
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			if ( ! empty( $results ) ) {
				$user_ip_info = array_shift( $results[0] );
			}
		}
		return $user_ip_info;
	}
}
