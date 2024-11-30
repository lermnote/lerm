<?php // phpcs:disable WordPress.Files.FileName
/**
 * Handle ajax login
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;
use function Lerm\Inc\Functions\Helpers\client_ip;

final class AjaxRegist extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION           = 'front_regist';
	protected const PUBLIC                = true;
	protected const RETRY_PAUSE           = 5;
	protected const REGIST_FORM_FILE_NAME = 'form-regist.php';

	public const LERM_MENU_LOCATION = 'primary';

	public static $args = array(
		'regist_enable'        => true,
		'post_type'            => array(),
		'post_exclude'         => array(),
		'page_exclude'         => array(),
		'login_from_file_name' => 'login.php',
		'login_redirect'       => 'home_url()',
		'menu_login_item'      => true,
	);

	public function __construct( $params = array() ) {
		parent::__construct( apply_filters( 'lerm_user_args', wp_parse_args( $params, self::$args ) ) );
		self::hooks();
	}

	public static function hooks() {
		add_filter( 'lerm_l10n_user_data', array( __CLASS__, 'l10n_data' ) );
	}

	/**
	 * The main function will try to log in to the site by sanitising and
	 * authenticating $_POST['username'] and $_POST['password']
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'regist_nonce', 'security' );

		$request_data = $_POST;
		$username     = sanitize_text_field( $request_data['username'] );
		$email        = $request_data['email'];
		$password     = $request_data['regist-password'];

		// Check client IP for any login attempt limits.
		$client_ip_address = client_ip();
		if ( empty( $client_ip_address ) ) {
			self::error( array( 'message' => __( 'Cannot determine IP address.', 'lerm' ) ) );
		}

		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			self::error( array( 'message' => 'Please fill in all required fields.' ) );
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			self::error(
				array(
					'message' => $user_id->get_error_message(),
				)
			);
		}

		// Login successful
		self::success(
			array(
				'message'  => __( 'Registration successful!', 'lerm' ),
				'redirect' => self::login_redirect( '', $user_id ),
			)
		);
		 // Post values
		//  $username = sanitize_text_field($_POST['register_username']);
		//  $password = sanitize_text_field($_POST['register_password']);
		//  $email = sanitize_text_field($_POST['register_email']);
		//  $name = sanitize_text_field($_POST['register_name']);
		//  $nick = sanitize_text_field($_POST['register_name']);

		//  $userdata = array(
		// 	 'user_login' => $username,
		// 	 'user_pass' => $password,
		// 	 'user_password' => $password,
		// 	 'user_email' => $email,
		// 	 'first_name' => $name,
		// 	 'nickname' => $nick,
		//  );

		//  $user_id = wp_insert_user($userdata);

		//  // add user meta
		//  $custom_user_meta_value = 'custom_user_meta_value';
		//  add_user_meta( $user_id, 'custom_user_meta', $custom_user_meta_value);

		//  // Return
		//  if (!is_wp_error($user_id)) {
		// 	 $user_signon = wp_signon($userdata, false);
		// 	 if (!is_wp_error($user_signon)) {
		// 		 wp_send_json(array('status' => 2, 'message' => __('your registration is successfuled and logined.')));
		// 	 } else {
		// 		 wp_send_json(array('status' => 1, 'message' => __('your registration is successfuled and logined')));
		// 	 }
		//  } else {
		// 	 wp_send_json(array('status' => 0, 'message' => __($user_id->get_error_message())));
		//  }
	}

	/**
	 * WordPress function for redirecting users on login based on user role
	 */
	public static function login_redirect( $url, $user ) {
		if ( is_a( $user, 'WP_User' ) ) {
			$url = home_url( '/user.html' );
		}
		return apply_filters( 'lerm_custom_login_redirect', $url, $user );
	}

	/**
	 * Determine if login menu is required.
	 */
	private static function is_login_menu_required() {
		return ! is_user_logged_in();
	}
	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function l10n_data( $l10n ) {
		$data = array(
			'regist_nonce'  => wp_create_nonce( 'regist_nonce' ),
			'regist_action' => self::AJAX_ACTION,
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
