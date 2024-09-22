<?php // phpcs:disable WordPress.Files.FileName
/**
 * Handle ajax login
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;
class AjaxLogin extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION          = 'front_login';
	protected const PUBLIC               = true;
	protected const RETRY_PAUSE          = 5;
	protected const LOGIN_FORM_FILE_NAME = 'form-login.php';

	public const LERM_MENU_LOCATION = 'primary';

	public static $args = array(
		'login_enable'         => true,
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
		add_action( 'wp_logout', array( __CLASS__, 'loginout' ) );
		if ( self::$args['menu_login_item'] ) {
			add_filter( 'wp_nav_menu_items', array( __CLASS__, 'add_menu_item' ), 10, 2 );
		}

		add_filter( 'lerm_l10n_data', array( static::class, 'ajax_l10n_data' ) );
	}

	/**
	 * The main function will try to log in to the site by sanitising and
	 * authenticating $_POST['username'] and $_POST['password']
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'login_nonce', 'security' );

		// Check client IP for any login attempt limits.
		$client_ip_address = self::client_ip();
		if ( empty( $client_ip_address ) ) {
			self::error( __( 'Cannot determine IP address.', 'lerm' ) );
		}

		$response = self::validate_login_data( $_POST );

		if ( is_wp_error( $response ) ) {
			self::error( $response->get_error_message() );
		}

		$user = wp_signon( $response['credentials'], false );

		if ( is_wp_error( $user ) ) {
			// 限制登录尝试次数
			self::track_login_attempts( $response['credentials']['user_login'] );
			self::error( $user->get_error_message() );
		}

		// Login successful
		self::success(
			array(
				'loggedin' => true,
				'message'  => __( 'Login successful. Redirecting...', 'lerm' ),
				'redirect' => self::login_redirect( '', $user ),
			)
		);
	}
	private static function validate_login_data( $data ) {
		$username = isset( $data['login_username'] ) ? sanitize_text_field( wp_unslash( $data['login_username'] ) ) : '';
		$password = isset( $data['login_password'] ) ? sanitize_text_field( wp_unslash( $data['login_password'] ) ) : '';

		if ( empty( $username ) || empty( $password ) ) {
			return new \WP_Error( 'empty_fields', __( 'Username or password cannot be empty.', 'lerm' ) );
		}
		// 检查用户名是否存在
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			return new \WP_Error( 'invalid_username', __( 'Invalid username. Please try again.', 'lerm' ) );
		}

		// 检查密码是否正确
		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return new \WP_Error( 'incorrect_password', __( 'Incorrect password. Please try again.', 'lerm' ) );
		}

		// 可以进一步检查密码的复杂性或其他规则
		if ( strlen( $password ) < 8 ) {
			return new \WP_Error( 'password_too_short', __( 'Password must be at least 8 characters long.', 'lerm' ) );
		}

		return array(
			'credentials' => array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => isset( $data['rememberme'] ),
			),
		);
	}

	private static function track_login_attempts( $username ) {
		$attempt_key = 'login_attempt_' . $username;
		$attempts    = get_transient( $attempt_key );
		if ( $attempts >= 5 ) {
			return new \WP_Error( 'too_many_attempts', __( 'Too many login attempts. Please try again later.', 'lerm' ) );
		}
		set_transient( $attempt_key, $attempts + 1, MINUTE_IN_SECONDS * self::RETRY_PAUSE );
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
	 * Render the login menu item and form.
	 */
	public static function add_menu_item( $items, $args ) {
		if ( self::LERM_MENU_LOCATION !== $args->theme_location ) {
			return $items; // Not the correct menu location.
		}
		if ( ! self::is_login_menu_required() ) {
			$outer_classes = array( 'nav-item', 'dropdown', 'menu-item-login' );
		} else {
			$outer_classes = array( 'nav-item', 'menu-item-login' );
		}

		$items .= sprintf( '<li class="%s">', esc_attr( implode( ' ', $outer_classes ) ) );

		if ( ! self::is_login_menu_required() ) {
			$items .= sprintf(
				'<a class="nav-link dropdown-toggle" href="%s" role="button" data-bs-toggle="dropdown" aria-expanded="false">%s</a>',
				esc_url( self::lerm_front_door_url() ),
				get_avatar( get_current_user_id(), 16 ),
			);

			$sub_menu_classes = array( 'dropdown-menu' );

			$items .= sprintf( '<ul class="%s">', esc_attr( implode( ' ', $sub_menu_classes ) ) );

			$current_user = wp_get_current_user();

			$items .= '<li><a class="dropdown-item" href="' . self::login_redirect( '', $current_user ) . '">' . $current_user->user_login . '</a></li>
						<li><a class="dropdown-item" href="#">Another action</a></li>
						<li><a class="dropdown-item" href="' . esc_url( wp_logout_url( self::lerm_front_door_url() ) ) . '">' . __( 'Log out' ) . '</a></li>';

			$items .= '</ul></li>';
		} else {
			$items .= sprintf(
				'<a class="nav-link" href="%s">%s</a>',
				esc_url( self::lerm_front_door_url() ),
				esc_html__( 'Login', 'lerm' )
			);

			$items .= '</li>';
		}

		return $items;
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
	 * Get the URL for the site's main login page, with the redirect
	 * (after login) set to the page we're currently viewing.
	 * This is useful for mobile devices where the Ajax login form might
	 * not be available.
	 */
	private static function lerm_front_door_url() {

		$login_page_url = home_url( '/login.html' );

		// If WooCommerce is installed, use the my-account page as the frontdoor,
		// so we get a nice front-end login form.
		// if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		// 	//$frontdoor_url = wc_get_account_endpoint_url( 'dashboard' );
		// }

		return $login_page_url;
	}
	/**
	 * A convenience function that lets us disable the login menu item under
	 * certain conditions.
	 */
	/**
	 * Determine if login menu is required.
	 */
	private static function is_login_menu_required() {
		return ! is_user_logged_in();
	}
	public static function loginout() {
		wp_safe_redirect( home_url( '/login.html' ) );
		exit;
	}


	public static function regist_form() {
		ob_start();
		include LERM_DIR . '/template-parts/account/regist.php';
		$form = ob_get_clean();
		return $form;
	}
	public static function login_form() {
		ob_start();
		include LERM_DIR . '/template-parts/account/login.php';
		$form = ob_get_clean();
		return $form;
	}
	public static function reset_form() {
		ob_start();
		include LERM_DIR . '/template-parts/account/reset.php';
		$form = ob_get_clean();
		return $form;
	}
	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function scripts( $l10n ) {
		wp_register_script( 'frontlogin', LERM_URI . 'assets/js/front-login.js', array(), LERM_VERSION, true );
		$data = array(
			'ajaxURL'      => admin_url( 'admin-ajax.php' ),
			'login_nonce'  => wp_create_nonce( 'login_nonce' ),
			'login_action' => self::AJAX_ACTION,
			'logged'       => is_user_logged_in(),
			'frontDoor'    => self::lerm_front_door_url(),
		);

		$l10n = apply_filters( 'lerm_l10n_user_data', $data );

		wp_localize_script( 'frontlogin', 'lermDatas', $l10n );
		wp_enqueue_script( 'frontlogin' );
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
			'frontDoor'    => self::lerm_front_door_url(),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
