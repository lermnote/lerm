<?php
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\LikeRepository;
use Lerm\Support\Utilities;

/**
 * 点赞接口控制器
 *
 * GET  /lerm/v1/like/{id}  — 查询点赞数及当前用户状态
 * POST /lerm/v1/like/{id}  — 切换点赞（已赞则取消，未赞则点赞）
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class LikeController {

	/**
	 * 查询点赞状态
	 */
	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::require_published_post( $post_id );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user_id = Utilities::get_like_user_id();
		$data    = LikeRepository::get( $post_id, $user_id );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * 切换点赞状态
	 *
	 * 频率限制：每 IP 每分钟最多 30 次（防刷）
	 */
	public static function toggle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		// 中间件链：先验文章，再限速
		$check = Middleware::chain(
			fn() => Middleware::require_published_post( $post_id ),
			fn() => Middleware::rate_limit( 'like_' . $post_id, 30, 60 )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user_id = Utilities::get_like_user_id();
		$result  = LikeRepository::toggle( $post_id, $user_id );

		return new WP_REST_Response( $result, 200 );
	}
}
