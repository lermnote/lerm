<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\ViewsRepository;
use Lerm\Support\Utilities;

/**
 * 浏览数接口控制器
 *
 * GET  /lerm/v1/views/{id}  — 查询浏览数
 * POST /lerm/v1/views/{id}  — 异步递增浏览数（替换原 Setup::add_post_views 同步写入）
 *
 * 迁移说明：
 *   原 Setup.php 中 add_post_views() 在 template_redirect 同步写 meta，
 *   对 TTFB 有影响。新方案改为前端页面加载后异步 POST。
 *   Setup.php 中对应的 add_action('template_redirect', ...) 可移除。
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class ViewsController {

	/**
	 * 查询浏览数
	 */
	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::require_published_post( $post_id );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return new WP_REST_Response(
			[ 'count' => ViewsRepository::get_count( $post_id ) ],
			200
		);
	}

	/**
	 * 递增浏览数
	 *
	 * 频率限制：每 IP 每分钟最多 5 次（正常浏览不会触发）
	 */
	public static function increment( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::chain(
			fn() => Middleware::require_published_post( $post_id ),
			fn() => Middleware::rate_limit( 'views_' . $post_id, 5, 60 )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		// 用 IP hash 作为防重复标识，不直接暴露 IP
		$client_key = substr( md5( Utilities::client_ip() . wp_salt() ), 0, 16 );
		$result     = ViewsRepository::increment( $post_id, $client_key );

		return new WP_REST_Response( $result, 200 );
	}
}
