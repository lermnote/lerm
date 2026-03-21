<?php // phpcs:disable WordPress.Files.FileName
/**
 * Handle ajax login
 */

namespace Lerm\Http\Ajax;

use Lerm\Traits\Singleton;
use function Lerm\Support\client_ip;

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

	$client_ip = client_ip();
	if ( empty( $client_ip ) ) {
		self::error( __( 'Cannot determine IP address.', 'lerm' ) );
	}

	$credentials = self::validate_login_data( $_POST );
	if ( is_wp_error( $credentials ) ) {
		self::error( $credentials->get_error_message() );
	}

	$blocked = self::check_login_attempts( $credentials['user_login'], $client_ip );
	if ( is_wp_error( $blocked ) ) {
		self::error( $blocked->get_error_message() );
	}

	$user = wp_signon( $credentials, false );

	if ( is_wp_error( $user ) ) {
		self::increment_login_attempts( $credentials['user_login'], $client_ip );
		self::error( __( 'Invalid username or password.', 'lerm' ) );
	}

	self::clear_login_attempts( $credentials['user_login'], $client_ip );

	self::success(
		array(
			'loggedin' => true,
			'message'  => __( 'Login successful. Redirecting...', 'lerm' ),
			'redirect' => esc_url_raw( self::get_redirect_url( $user ) ),
		)
	);
}

private static function validate_login_data( $data ) {
	$username = sanitize_user( wp_unslash( $data['username'] ?? '' ), true );
	$password = wp_unslash( $data['password'] ?? '' );

	if ( empty( $username ) || empty( $password ) ) {
		return new \WP_Error( 'empty_fields', __( 'Username or password cannot be empty.', 'lerm' ) );
	}

	return array(
		'user_login'    => $username,
		'user_password' => $password,
		'remember'      => ! empty( $data['rememberme'] ),
	);
}

private static function get_attempt_key( $username, $client_ip ) {
	return 'login_attempt_' . md5( strtolower( $username ) . '|' . $client_ip );
}

private static function check_login_attempts( $username, $client_ip ) {
	$attempt_key = self::get_attempt_key( $username, $client_ip );
	$attempts    = (int) get_transient( $attempt_key );

	if ( $attempts >= 5 ) {
		return new \WP_Error(
			'too_many_attempts',
			sprintf(
				__( 'Too many login attempts. Please try again in %d minutes.', 'lerm' ),
				self::RETRY_PAUSE
			)
		);
	}

	return true;
}

private static function increment_login_attempts( $username, $client_ip ) {
	$attempt_key = self::get_attempt_key( $username, $client_ip );
	$attempts    = (int) get_transient( $attempt_key );
	set_transient( $attempt_key, $attempts + 1, MINUTE_IN_SECONDS * self::RETRY_PAUSE );
}

private static function clear_login_attempts( $username, $client_ip ) {
	delete_transient( self::get_attempt_key( $username, $client_ip ) );
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



