<?php // phpcs:disable WordPress.Files.FileName
/**
 * Handle ajax login
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;

class AjaxReset extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION           = 'front_reset';
	protected const PUBLIC                = true;
	protected const RETRY_PAUSE           = 5;
	protected const REGIST_FORM_FILE_NAME = 'form-reset.php';

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
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'l10n_data' ) );
	}

	/**
	 * The main function will try to log in to the site by sanitising and
	 * authenticating $_POST['username'] and $_POST['password']
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'reset_nonce', 'security' );

		$request_data = $_POST;
		$username     = $request_data['username'];
		$email        = $request_data['reset_email'];
		$password     = $request_data['regist_password'];

		// Check client IP for any login attempt limits.
		$client_ip_address = self::client_ip();
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
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	private static function client_ip() {
		return lerm_client_ip();
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
			'reset_nonce'  => wp_create_nonce( 'reset_nonce' ),
			'reset_action' => self::AJAX_ACTION,
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
