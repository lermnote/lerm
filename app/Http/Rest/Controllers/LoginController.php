<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Support\Utilities;

/**
 * 前台登录接口
 *
 * POST /lerm/v1/auth/login
 *
 * 请求体（application/x-www-form-urlencoded 或 JSON）：
 *   username  string  必填
 *   password  string  必填
 *   remember  bool    可选，默认 false
 *
 * 响应：
 *   200 { loggedin: true, message: '...', redirect: 'https://...' }
 *   401 { code: 'invalid_credentials', message: '...' }
 *   429 { code: 'rate_limited', message: '...' }
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class LoginController {

	private const MAX_ATTEMPTS  = 5;
	private const LOCKOUT_MINS  = 5;

	// -------------------------------------------------------------------------
	// 路由入口
	// -------------------------------------------------------------------------

	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// 已登录直接返回
		if ( is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'loggedin' => true,
					'message'  => __( 'Already logged in.', 'lerm' ),
					'redirect' => self::get_redirect_url( wp_get_current_user() ),
				),
				200
			);
		}

		// 频率限制：每 IP 每 5 分钟最多 10 次请求
		$check = Middleware::rate_limit( 'auth_login', 10, self::LOCKOUT_MINS * 60 );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		// 解析参数（兼容 JSON body 和表单提交）
		$username = sanitize_user( (string) ( $request->get_param( 'username' ) ?? '' ), true );
		$password = (string) ( $request->get_param( 'password' ) ?? '' );
		$remember = (bool) $request->get_param( 'remember' );

		if ( empty( $username ) || empty( $password ) ) {
			return new WP_Error(
				'empty_fields',
				__( 'Username or password cannot be empty.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		// 登录尝试次数限制（IP + 用户名组合，防止暴力破解）
		$ip          = Utilities::client_ip();
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

		// 执行登录
		// wp_signon 在 REST API 请求中同样会设置认证 cookie
		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			// 递增失败计数
			set_transient( $attempt_key, $attempts + 1, self::LOCKOUT_MINS * MINUTE_IN_SECONDS );

			return new WP_Error(
				'invalid_credentials',
				__( 'Invalid username or password.', 'lerm' ),
				array( 'status' => 401 )
			);
		}

		// 登录成功，清除限流计数
		delete_transient( $attempt_key );

		return new WP_REST_Response(
			array(
				'loggedin' => true,
				'message'  => __( 'Login successful.', 'lerm' ),
				'redirect' => self::get_redirect_url( $user ),
			),
			200
		);
	}

	// -------------------------------------------------------------------------
	// 私有方法
	// -------------------------------------------------------------------------

	private static function get_redirect_url( \WP_User $user ): string {
		$url = (string) apply_filters( 'lerm_login_redirect_url', home_url( '/' ), $user );
		return esc_url_raw( $url );
	}
}
