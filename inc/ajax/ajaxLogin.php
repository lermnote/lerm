<?php // phpcs:disable WordPress.Files.FileName
/**
 * Handle ajax login
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;
use function Lerm\Inc\Functions\Helpers\client_ip;

final class AjaxLogin extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION = 'front_login';
	protected const PUBLIC      = true;
	protected const RETRY_PAUSE = 5;

	private static $args = array(
		'front_login_enable'  => true,
		'login_page_id'       => '',
		'login_redirect_url'  => '',
		'logout_redirect_url' => 'home_url()',
	);

	public function __construct( $params = array() ) {
		self::$args = apply_filters( 'lerm_user_args', wp_parse_args( $params, self::$args ) );
		parent::__construct( self::$args );
		self::hooks();
	}

	public static function hooks() {
		if ( self::$args['menu_login_item'] ) {
			add_filter( 'wp_nav_menu_items', array( __CLASS__, 'add_menu_item' ), 10, 2 );
		}

		add_filter( 'lerm_l10n_data', array( static::class, 'ajax_l10n_data' ) );
	}

	/**
	 * The main function will try to log in to the site by sanitising and
	 * authenticating $_POST['username'] and $_POST['password']
	 * and return the result.
	 *
	 * @return void
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'login_nonce', 'security' );

		// Check client IP for any login attempt limits.
		$client_ip = client_ip();
		if ( empty( $client_ip ) ) {
			self::error( __( 'Cannot determine IP address.', 'lerm' ) );
		}

		// Validate login data.
		$credentials = self::validate_login_data( $_POST );
		if ( is_wp_error( $credentials ) ) {
			self::error( $credentials->get_error_message() );
		}

		// Authenticate user.
		$user = wp_signon( $credentials, false );
		if ( is_wp_error( $user ) ) {
			self::limit_login_attempts( $credentials['user_login'] );
			self::error( $user->get_error_message() );
		}

		// Login successful
		self::success(
			array(
				'loggedin' => true,
				'message'  => __( 'Login successful. Redirecting...', 'lerm' ),
				'redirect' => self::get_redirect_url( $user ),
			)
		);
	}

	/**
	 * Validate login data
	 *
	 * @param array $data Submitted data.
	 * @return array|WP_Error Validated data or error object.
	 */
	private static function validate_login_data( $data ) {
		$username = sanitize_text_field( wp_unslash( $data['username'] ?? '' ) );
		$password = wp_unslash( $data['password'] ?? '' );

		if ( empty( $username ) || empty( $password ) ) {
			return new \WP_Error( 'empty_fields', __( 'Username or password cannot be empty.', 'lerm' ) );
		}

		$user = get_user_by( 'login', $username );
		if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return new \WP_Error( 'invalid_credentials', __( 'Invalid username or password.', 'lerm' ) );
		}

		return array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => ! empty( $data['rememberme'] ),
		);
	}

	/**
	 * Limit login attempts
	 *
	 * @param string $username Username.
	 * @return WP_Error|null Error object if login attempts exceeded.
	 */
	private static function limit_login_attempts( $username ) {
		$attempt_key = 'login_attempt_' . $username;
		$attempts    = (int) get_transient( $attempt_key );
		if ( $attempts >= 5 ) {
			return new \WP_Error( 'too_many_attempts', __( 'Too many login attempts. Please try again later.', 'lerm' ) );
		}
		set_transient( $attempt_key, $attempts + 1, MINUTE_IN_SECONDS * self::RETRY_PAUSE );
	}

	/**
	 * Get redirect URL after login
	 *
	 * @param object $user User object.
	 * @return string Redirect URL.
	 */
	private static function get_redirect_url( $user ) {
		$url = ( self::$args['login_redirect_url'] ) ? ( self::$args['login_redirect_url'] ) : home_url();
		return apply_filters( 'lerm_custom_login_redirect', $url, $user );
	}

	/**
	 * Render the login menu item and form.
	 *
	 * @param string $items Menu items.
	 * @param object $args Menu arguments.
	 * @return string Modified menu items.
	 */
	public static function add_menu_item( $items, $args ) {
		if ( 'primary' !== $args->theme_location ) {
			return $items;
		}

		if ( is_user_logged_in() ) {
			$outer_classes = array( 'nav-item', 'dropdown', 'menu-item-login' );
		} else {
			$outer_classes = array( 'nav-item', 'menu-item-login' );
		}

		$items .= sprintf( '<li class="%s">', esc_attr( implode( ' ', $outer_classes ) ) );

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();

			$items .= sprintf(
				'<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">%s %s</a>',
				get_avatar( get_current_user_id(), 20 ),
				$current_user->user_login,
			);

			$sub_menu_classes = array( 'dropdown-menu' );

			$items .= sprintf( '<ul class="%s">', esc_attr( implode( ' ', $sub_menu_classes ) ) );

			$items .= '<li class="text-center"><h6 class="dropdown-header text-center">' . get_avatar( get_current_user_id(), 64 ) . '</h6><label class="text-info">' . $current_user->user_login . '</label></li>
			<li><a class="dropdown-item" href="' . self::$args['login_redirect_url'] . '">Account</a></li>
						 <li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="' . esc_url( wp_logout_url( self::$args['logout_redirect_url'] ) ) . '">' . __( 'Log out' ) . '</a></li>';
			$items .= '</ul></li>';
		} else {
			$items .= sprintf(
				'<a class="nav-link" href="%s">%s</a>',
				esc_url( self::get_login_page_url() ),
				esc_html__( 'Login', 'lerm' )
			);
			$items .= '</li>';
		}

		return $items;
	}

	/**
	 * Get login page URL
	 *
	 * @return string
	 */
	private static function get_login_page_url() {
		return get_permalink( absint( self::$args['login_page_id'] ) ) ? get_permalink( absint( self::$args['login_page_id'] ) ) : wp_login_url();
	}

	/**
	 * Generate AJAX localization data.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {
		$data = array(
			'login_nonce'  => wp_create_nonce( 'login_nonce' ),
			'login_action' => self::AJAX_ACTION,
			'logged'       => is_user_logged_in(),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
