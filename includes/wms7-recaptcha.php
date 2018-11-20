<?php
/**
 * Description: Used to create Google reCAPTCHA.
 *
 * @category    wms7-recaptcha.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$val       = get_option( 'wms7_main_settings' );
$recaptcha = isset( $val['recaptcha'] ) ? $val['recaptcha'] : '';
if ( ! $recaptcha ) {
	return;
}

/**
 * Used for frontend recaptcha script.
 */
function frontend_recaptcha_script() {
	// Thus, we check whether the Contact Form 7 plugin is installed.
	if ( ! wp_script_is( 'google-recaptcha', 'registered' ) ) {
		wp_register_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), 'v.3.0.0', false );
	}
	wp_enqueue_script( 'google-recaptcha' );
}
add_action( 'wp_enqueue_scripts', 'frontend_recaptcha_script' );

/**
 * Used for display comment recaptcha.
 */
function display_comment_recaptcha() {
	$val      = get_option( 'wms7_main_settings' );
	$site_key = isset( $val['recaptcha_site_key'] ) ? $val['recaptcha_site_key'] : '';

	if ( is_user_logged_in() ) {
		return;
	}
	?>
	<div class="g-recaptcha" data-sitekey="<?php echo esc_html( $site_key ); ?>"></div>
	<?php
}
add_action( 'comment_form', 'display_comment_recaptcha' );

/**
 * Used for verify comment captcha.
 *
 * @param string $commentdata Comment data.
 * @return array.
 */
function verify_comment_captcha( $commentdata ) {
	if ( is_user_logged_in() ) {
		return $commentdata;
	}
	$_recaptcha_resp = filter_input( INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING );
	$val             = get_option( 'wms7_main_settings' );
	$secret_key      = isset( $val['recaptcha_secret_key'] ) ? $val['recaptcha_secret_key'] : '';

	if ( isset( $_recaptcha_resp ) ) {
		$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_recaptcha_resp );
		$response = json_decode( $response['body'], true );

		if ( true === $response['success'] ) {
			return $commentdata;
		} else {
			echo esc_html( 'Bots are not allowed to submit comments.', 'watchman-site7' );
			return null;
		}
	} else {
		echo esc_html( 'Bots are not allowed to submit comments. If you are not a bot then please enable JavaScript in browser.', 'watchman-site7' );
		return null;
	}
}
add_filter( 'preprocess_comment', 'verify_comment_captcha' );

/**
 * Used for login recaptcha script.
 */
function login_recaptcha_script() {
	wp_register_script( 'recaptcha_login', 'https://www.google.com/recaptcha/api.js', array(), 'v.3.0.0', false );
	wp_enqueue_script( 'recaptcha_login' );
}
add_action( 'login_enqueue_scripts', 'login_recaptcha_script' );

/**
 * Used for display login captcha.
 */
function display_login_captcha() {
	$val      = get_option( 'wms7_main_settings' );
	$site_key = isset( $val['recaptcha_site_key'] ) ? $val['recaptcha_site_key'] : '';
	?>
	<div class="g-recaptcha" style="transform: scale(0.90);padding: 10px 0 5px 0;transform-origin: 0 0;" data-sitekey="<?php echo esc_html( $site_key ); ?>"></div>
	<?php
}
add_action( 'login_form', 'display_login_captcha' );

/**
 * Used for verify login captcha.
 *
 * @param string $user User.
 * @param string $password User password.
 * @return array.
 */
function verify_login_captcha( $user, $password ) {
	$_recaptcha_resp = filter_input( INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING );
	$val             = get_option( 'wms7_main_settings' );
	$secret_key      = isset( $val['recaptcha_secret_key'] ) ? $val['recaptcha_secret_key'] : '';

	if ( isset( $_recaptcha_resp ) ) {
		$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_recaptcha_resp );
		$response = json_decode( $response['body'], true );
		if ( true === $response['success'] ) {
			return $user;
		} else {
			return new WP_Error( 'Captcha Invalid', __( '<strong>ERROR</strong>: You are a bot' ) );
		}
	} else {
		return new WP_Error( 'Captcha Invalid', __( '<strong>ERROR</strong>: You are a bot. If not then enable JavaScript' ) );
	}
}
add_filter( 'wp_authenticate_user', 'verify_login_captcha', 10, 2 );

/**
 * Used for display register captcha.
 */
function display_register_captcha() {
	$val      = get_option( 'wms7_main_settings' );
	$site_key = isset( $val['recaptcha_site_key'] ) ? $val['recaptcha_site_key'] : '';
	?>
	<div class="g-recaptcha" style="transform: scale(0.90);padding: 10px 0 5px 0;transform-origin: 0 0;" data-sitekey="<?php echo esc_html( $site_key ); ?>"></div>
	<?php
}
add_action( 'register_form', 'display_register_captcha' );

/**
 * Used for verify registration captcha.
 *
 * @param string $errors Errors.
 * @param string $sanitized_user_login User login.
 * @param string $user_email User email.
 * @return array.
 */
function verify_registration_captcha( $errors, $sanitized_user_login, $user_email ) {
	$_recaptcha_resp = filter_input( INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING );
	$val             = get_option( 'wms7_main_settings' );
	$secret_key      = isset( $val['recaptcha_secret_key'] ) ? $val['recaptcha_secret_key'] : '';

	if ( isset( $_recaptcha_resp ) ) {
		$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_recaptcha_resp );
		$response = json_decode( $response['body'], true );
		if ( true === $response['success'] ) {
			return $errors;
		} else {
			$errors->add( 'Captcha Invalid', __( '<strong>ERROR</strong>: You are a bot' ) );
		}
	} else {
		$errors->add( 'Captcha Invalid', __( '<strong>ERROR</strong>: You are a bot. If not then enable JavaScript' ) );
	}
	return $errors;
}
add_filter( 'registration_errors', 'verify_registration_captcha', 10, 3 );

/**
 * Used for verify lostpassword captcha.
 *
 * @return array.
 */
function verify_lostpassword_captcha() {
	$_recaptcha_resp = filter_input( INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING );
	$val             = get_option( 'wms7_main_settings' );
	$secret_key      = isset( $val['recaptcha_secret_key'] ) ? $val['recaptcha_secret_key'] : '';

	if ( isset( $_recaptcha_resp ) ) {
		$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_recaptcha_resp );
		$response = json_decode( $response['body'], true );
		if ( true === $response['success'] ) {
			return;
		} else {
			wp_die( esc_html( '<strong>ERROR</strong>: You are a bot', 'watchman-site7' ) );
		}
	} else {
		wp_die( esc_html( '<strong>ERROR</strong>: You are a bot. If not then enable JavaScript', 'watchman-site7' ) );
	}
	return $errors;
}
add_action( 'lostpassword_form', 'display_login_captcha' );
add_action( 'lostpassword_post', 'verify_lostpassword_captcha' );
