<?php
/**
 * Handle ajax login
 */

namespace Lerm\Inc\User;

class User {

	// secs
	public const LERM_LOGIN_RETRY_PAUSE    = 5;
	public const LERM_LOGIN_ACTION         = 'lerm_ajax_login';
	public const LERM_LOGIN_FORM_FILE_NAME = 'login.php';

	public static $args = array(
		'login_enable'         => true,
		'post_type'            => array(),
		'post_exclude'         => array(),
		'page_exclude'         => array(),
		'login_from_file_name' => 'login.php',
		'login_redirect'       => 'home_url()',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_user_', wp_parse_args( $params, self::$args ) );
		self::hooks();
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
	}

	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'lerm_enqueue_scripts' ) );
		/**
		* Listen for incoming login requests from the Ajax form.
		*/
		add_action( 'wp_ajax_nopriv_' . LERM_LOGIN_ACTION, array( __CLASS__, 'ajax_login' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'my_login_redirect' ), 10, 3 );
	}

	/**
	 * The main function will try to log in to the site by sanitising and
	 * authenticating $_POST['username'] and $_POST['password']
	 */
	public static function ajax_login() {

		// If we can't determine the client's IP address then something is very
		// wrong - possibly a hack attempt. Don't do anything.
		$client_ip_address = self::lerm_client_ip();
		if ( empty( $client_ip_address ) ) {
			die();
		}

		$client_key = 'login_attempt_' . $client_ip_address;

		if ( check_ajax_referer( 'user_nonce', 'security', false ) ) {

			$credentials['user_login']    = sanitize_text_field( $_POST['username'] );
			$credentials['user_password'] = sanitize_text_field( $_POST['password'] );
			$credentials['remember']      = isset( $_POST['rememberme'] ) ? true : false;

			$user = wp_signon( $credentials, false );

			$status_code = 200;
			$response    = array(
				'loggedin' => false,
				'message'  => __( 'Incorrect username or password.', 'lerm' ),
			);

			if ( empty( $credentials['user_login'] ) ) {
				$response['message'] = __( 'The username field is empty.', 'lerm' );
			} elseif ( empty( $credentials['user_password'] ) ) {
				$response['message'] = __( 'The password field is empty.', 'lerm' );
			} elseif ( get_transient( $client_key ) !== false ) {
				$response['message'] = __( 'Slow down a bit.', 'lerm' );
			}

			if ( is_a( $user, 'WP_User' ) ) {
				// Logged in OK.
				$response['loggedin'] = true;
				$response['message']  = __( 'Logedin successful,redirecting…', 'lerm' );

				wp_send_json_success( $response, $status_code );
			} else {
				// 验证失败
				set_transient( $client_key, '1', LERM_LOGIN_RETRY_PAUSE );
				wp_send_json_error( $response, $status_code );
			}
		}
	}

	public static function user_profile() {
	}

	/**
	 * WordPress function for redirecting users on login based on user role
	 */
	private static function my_login_redirect( $url, $request, $user ) {
		if ( $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
			if ( $user->has_cap( 'administrator' ) ) {
				$url = admin_url();
			} else {
				$url = home_url( '/members-only/' );
			}
		}
		return $url;
	}

	/**
	 * We add our assets to every page of the site, because the primay
	 * nav menu is probably on every page.
	 */
	private static function lerm_enqueue_scripts() {
		if ( lerm_is_login_menu_required() ) {
			$base_uri = get_stylesheet_directory_uri();
			$version  = wp_get_theme()->get( 'Version' );

			// Enqueue our main JavaScript file.
			wp_enqueue_script( 'lerm', $base_uri . '/assets/js/ajax-login.js', array(), $version, true );

			// Pass some settings and variables to the browser in a
			// JavaScript global variable called lermData.
			wp_localize_script(
				'lerm',
				'lermData',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'user_nonce' => wp_create_nonce( 'user_nonce' ),
					'action'     => LERM_LOGIN_ACTION,
					'frontDoor'  => self::lerm_front_door_url(),

				)
			);
		}
	}

	/**
	 * Get the IP address of the current browser.
	 */
	private static function lerm_client_ip() {
		global $lerm_client_ip;

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$lerm_client_ip = filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$lerm_client_ip = filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP );
		} else {
			$lerm_client_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
		}

		return $lerm_client_ip;
	}

	/**
	 * Get the URL for the site's main login page, with the redirect
	 * (after login) set to the page we're currently viewing.
	 * This is useful for mobile devices where the Ajax login form might
	 * not be available.
	 */
	private static function lerm_front_door_url() {

		$login_page_url = wp_login_url( home_url( $_SERVER['REQUEST_URI'] ) );

		// If WooCommerce is installed, use the my-account page as the frontdoor,
		// so we get a nice front-end login form.
		if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
			$frontdoor_url = wc_get_account_endpoint_url( 'dashboard' );
		}

		return $login_page_url;
	}
}
