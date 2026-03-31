<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function Lerm\Support\client_ip;

/**
 * Frontend authentication controller.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class LoginController {

	private const MAX_ATTEMPTS = 5;
	private const LOCKOUT_MINS = 5;

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		if ( is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'loggedin' => true,
					'message'  => __( 'Already logged in.', 'lerm' ),
					'redirect' => self::get_redirect_url( $request, wp_get_current_user() ),
				),
				200
			);
		}

		$check = Middleware::chain(
			fn() => Middleware::verify_nonce( $request ),
			fn() => Middleware::rate_limit( 'auth_login', 10, self::LOCKOUT_MINS * MINUTE_IN_SECONDS )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$username = trim( (string) ( $request->get_param( 'username' ) ?? '' ) );
		$password = (string) ( $request->get_param( 'password' ) ?? '' );
		$remember = self::to_bool( $request->get_param( 'remember' ) );

		if ( '' === $username || '' === $password ) {
			return new WP_Error(
				'empty_fields',
				__( 'Username or password cannot be empty.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		$ip          = client_ip();
		$attempt_key = 'lerm_login_' . md5( strtolower( $username ) . '|' . $ip );
		$attempts    = (int) get_transient( $attempt_key );

		if ( $attempts >= self::MAX_ATTEMPTS ) {
			return new WP_Error(
				'too_many_attempts',
				sprintf(
					/* translators: %d: minutes */
					__( 'Too many login attempts. Please try again in %d minutes.', 'lerm' ),
					self::LOCKOUT_MINS
				),
				array( 'status' => 429 )
			);
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			set_transient( $attempt_key, $attempts + 1, self::LOCKOUT_MINS * MINUTE_IN_SECONDS );

			return new WP_Error(
				'invalid_credentials',
				__( 'Invalid username or password.', 'lerm' ),
				array( 'status' => 401 )
			);
		}

		delete_transient( $attempt_key );

		return new WP_REST_Response(
			array(
				'loggedin' => true,
				'message'  => __( 'Login successful.', 'lerm' ),
				'redirect' => self::get_redirect_url( $request, $user ),
			),
			200
		);
	}

	public static function register( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		if ( ! get_option( 'users_can_register' ) ) {
			return new WP_Error(
				'registration_closed',
				__( 'Registration is currently disabled.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		$check = Middleware::chain(
			fn() => Middleware::verify_nonce( $request ),
			fn() => Middleware::rate_limit( 'auth_register', 5, HOUR_IN_SECONDS )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$username         = sanitize_user( trim( (string) ( $request->get_param( 'username' ) ?? '' ) ), true );
		$email            = sanitize_email( (string) ( $request->get_param( 'email' ) ?? '' ) );
		$password         = self::get_request_param( $request, array( 'password', 'regist_password' ) );
		$password_confirm = self::get_request_param( $request, array( 'password_confirm', 'confirm_password' ) );

		if ( '' === $username || '' === $email || '' === $password ) {
			return new WP_Error(
				'empty_fields',
				__( 'Please complete all required fields.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( strlen( $username ) < 3 || ! validate_username( $username ) ) {
			return new WP_Error(
				'invalid_username',
				__( 'Please choose a valid username.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( username_exists( $username ) ) {
			return new WP_Error(
				'username_exists',
				__( 'This username is already registered.', 'lerm' ),
				array( 'status' => 409 )
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( email_exists( $email ) ) {
			return new WP_Error(
				'email_exists',
				__( 'This email is already used by another account.', 'lerm' ),
				array( 'status' => 409 )
			);
		}

		if ( $password !== $password_confirm ) {
			return new WP_Error(
				'password_mismatch',
				__( 'Passwords do not match.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( strlen( $password ) < 8 ) {
			return new WP_Error(
				'password_too_short',
				__( 'Password must be at least 8 characters long.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'role'       => (string) get_option( 'default_role', 'subscriber' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return new WP_Error(
				'registration_failed',
				$user_id->get_error_message(),
				array( 'status' => 400 )
			);
		}

		if ( function_exists( 'wp_send_new_user_notifications' ) ) {
			wp_send_new_user_notifications( $user_id, 'both' );
		}

		$redirect = self::get_frontend_auth_url( 'login' );
		$target   = self::sanitize_redirect_target( $request->get_param( 'redirect_to' ), '' );

		if ( '' !== $target ) {
			$redirect = add_query_arg( 'redirect_to', $target, $redirect );
		}

		return new WP_REST_Response(
			array(
				'message'  => __( 'Registration successful. Please log in.', 'lerm' ),
				'redirect' => $redirect,
			),
			201
		);
	}

	public static function reset( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::chain(
			fn() => Middleware::verify_nonce( $request ),
			fn() => Middleware::rate_limit( 'auth_reset', 5, 15 * MINUTE_IN_SECONDS )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$identifier = trim(
			(string) (
				$request->get_param( 'login' )
				?? $request->get_param( 'email' )
				?? $request->get_param( 'user_login' )
				?? ''
			)
		);

		if ( '' === $identifier ) {
			return new WP_Error(
				'empty_fields',
				__( 'Please enter your username or email address.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		$user = is_email( $identifier )
			? get_user_by( 'email', $identifier )
			: get_user_by( 'login', $identifier );

		if ( ! $user && ! is_email( $identifier ) ) {
			$user = get_user_by( 'email', $identifier );
		}

		$success_message = __( 'If the account exists, a password reset link has been sent.', 'lerm' );
		$redirect        = self::get_frontend_auth_url( 'login' );
		$target          = self::sanitize_redirect_target( $request->get_param( 'redirect_to' ), '' );

		if ( '' !== $target ) {
			$redirect = add_query_arg( 'redirect_to', $target, $redirect );
		}

		if ( ! $user instanceof \WP_User ) {
			return new WP_REST_Response(
				array(
					'message'  => $success_message,
					'redirect' => $redirect,
				),
				200
			);
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return new WP_Error(
				'reset_key_failed',
				$key->get_error_message(),
				array( 'status' => 500 )
			);
		}

		do_action( 'retrieve_password_key', $user->user_login, $key );

		$reset_url = network_site_url(
			'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user->user_login ),
			'login'
		);
		$blogname  = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$title     = sprintf( __( '[%s] Password Reset', 'lerm' ), $blogname );
		$message   = sprintf(
			/* translators: 1: site name, 2: username, 3: reset URL */
			__(
				"Hi,\n\nA password reset was requested for your account on %1\$s.\n\nUsername: %2\$s\n\nReset your password here:\n%3\$s\n\nIf you did not request this, you can ignore this email.",
				'lerm'
			),
			$blogname,
			$user->user_login,
			$reset_url
		);

		$title   = (string) apply_filters( 'retrieve_password_title', $title, $user->user_login, $user );
		$message = (string) apply_filters( 'retrieve_password_message', $message, $key, $user->user_login, $user );

		if ( '' === trim( $message ) ) {
			return new WP_Error(
				'empty_message',
				__( 'Password reset email content is empty.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		if ( ! wp_mail( $user->user_email, wp_specialchars_decode( $title ), $message ) ) {
			return new WP_Error(
				'mail_failed',
				__( 'Unable to send the password reset email.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'message'  => $success_message,
				'redirect' => $redirect,
			),
			200
		);
	}

	private static function get_redirect_url( WP_REST_Request $request, \WP_User $user ): string {
		$requested = self::sanitize_redirect_target( $request->get_param( 'redirect_to' ), '' );

		if ( '' !== $requested ) {
			return $requested;
		}

		$url = (string) apply_filters( 'lerm_login_redirect_url', self::get_frontend_account_url(), $user );

		return esc_url_raw( $url ?: home_url( '/' ) );
	}

	private static function get_frontend_auth_url( string $tab = 'login' ): string {
		return function_exists( 'lerm_get_frontend_auth_page_url' )
			? lerm_get_frontend_auth_page_url( $tab )
			: home_url( '/' );
	}

	private static function get_frontend_account_url(): string {
		return function_exists( 'lerm_get_frontend_account_page_url' )
			? lerm_get_frontend_account_page_url()
			: home_url( '/' );
	}

	private static function sanitize_redirect_target( mixed $candidate, string $fallback ): string {
		$candidate = is_scalar( $candidate ) ? trim( (string) $candidate ) : '';
		if ( '' === $candidate ) {
			return $fallback;
		}

		return (string) wp_validate_redirect( $candidate, $fallback );
	}

	private static function get_request_param( WP_REST_Request $request, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		}

		return '';
	}

	private static function to_bool( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'yes', 'on', 'rememberme' ), true );
	}
}
