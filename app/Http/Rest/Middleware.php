<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest;

use WP_Error;
use WP_REST_Request;
use Lerm\Support\Utilities;

/**
 * REST API 中间件
 *
 * 提供可组合的验证方法，Controller 按需调用。
 * 每个方法成功返回 true，失败返回 WP_Error。
 *
 * @package Lerm\Http\Rest
 */
final class Middleware {

	/**
	 * 频率限制
	 *
	 * 基于 IP + action 组合键，使用 transient 计数。
	 * 超出限制时返回 429 错误。
	 *
	 * @param string $action 用于区分不同接口的标识符
	 * @param int    $limit  时间窗口内的最大请求次数
	 * @param int    $window 时间窗口（秒），默认 60
	 */
	public static function rate_limit( string $action, int $limit = 10, int $window = 60 ): true|WP_Error {
		$ip  = Utilities::client_ip();
		$key = 'lerm_rl_' . md5( $action . $ip );

		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return new WP_Error(
				'rate_limited',
				__( '请求过于频繁，请稍后再试', 'lerm' ),
				[ 'status' => 429 ]
			);
		}

		// 第一次写入时建立窗口；后续只递增不重置过期时间（近似限速）
		if ( 0 === $count ) {
			set_transient( $key, 1, $window );
		} else {
			// get_transient 拿到的是 int，直接覆盖写（过期时间由第一次写入决定）
			set_transient( $key, $count + 1, $window );
		}

		return true;
	}

	/**
	 * Nonce 验证
	 *
	 * 从请求 Header（X-WP-Nonce）或参数（_wpnonce）读取。
	 * 用于需要用户身份但不强制登录的操作（如点赞）。
	 *
	 * @param WP_REST_Request $request
	 * @param string          $action  nonce action 名
	 */
	public static function verify_nonce( WP_REST_Request $request, string $action = 'wp_rest' ): true|WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' )
			?? $request->get_param( '_wpnonce' )
			?? '';

		if ( ! wp_verify_nonce( (string) $nonce, $action ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( '安全验证失败，请刷新页面后重试', 'lerm' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * 登录检查
	 *
	 * 用于仅允许已登录用户访问的接口。
	 */
	public static function require_login(): true|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				__( '请先登录', 'lerm' ),
				[ 'status' => 401 ]
			);
		}
		return true;
	}

	/**
	 * 权限检查
	 *
	 * @param string $capability WordPress capability，如 'manage_options'
	 */
	public static function require_capability( string $capability ): true|WP_Error {
		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'forbidden',
				__( '权限不足', 'lerm' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * 验证文章存在且已发布
	 *
	 * @param int $post_id
	 */
	public static function require_published_post( int $post_id ): true|WP_Error {
		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'post_not_found',
				__( '文章不存在', 'lerm' ),
				[ 'status' => 404 ]
			);
		}

		return true;
	}

	/**
	 * 组合多个中间件，任意一个失败即中止
	 *
	 * 用法：Middleware::chain( fn() => Middleware::require_login(), fn() => Middleware::rate_limit('x') )
	 *
	 * @param callable ...$checks 每个 callable 返回 true|WP_Error
	 */
	public static function chain( callable ...$checks ): true|WP_Error {
		foreach ( $checks as $check ) {
			$result = $check();
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		return true;
	}
}
