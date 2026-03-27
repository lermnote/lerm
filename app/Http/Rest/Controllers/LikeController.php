<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\LikeRepository;
use function Lerm\Support\get_like_user_id;

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
		$id   = absint( $request->get_param( 'id' ) );
		$type = sanitize_key( $request->get_param( 'type' ) !== null ? $request->get_param( 'type' ) : 'post' );

		// Ensure cookie is set before performing any business logic
		// to avoid data inconsistency when headers are already sent
		if ( headers_sent() && ! is_user_logged_in() ) {
			return new WP_Error(
				'headers_already_sent',
				__( 'Cannot set like tracking cookie. Response headers already sent.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		$check = self::validate_object( $id, $type );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user_id = get_like_user_id();
		$liked   = LikeRepository::has_liked( $id, $user_id, $type );

		return new WP_REST_Response(
			array(
				'count'  => LikeRepository::get_count( $id, $type ),
				'liked'  => $liked,
				'status' => $liked ? 'liked' : 'unliked',
			),
			200
		);
	}

	/**
	 * 切换点赞状态
	 *
	 * 频率限制：每 IP 每分钟最多 30 次（防刷）
	 */
	public static function toggle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request->get_param( 'id' ) );
		$type = sanitize_key( $request->get_param( 'type' ) !== null ? $request->get_param( 'type' ) : 'post' );

		// Ensure cookie can be set before processing the request
		// This prevents data inconsistency when headers are already sent
		if ( headers_sent() && ! is_user_logged_in() ) {
			return new WP_Error(
				'headers_already_sent',
				__( 'Cannot set like tracking cookie. Response headers already sent.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		// 中间件链：先验文章，再限速
		$check = Middleware::chain(
			fn() =>self::validate_object( $id, $type ),
			fn() => Middleware::rate_limit( sprintf( 'like_%s_%d', $type, $id ), 30, 60 )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$user_id = get_like_user_id();
		$result  = LikeRepository::toggle( $id, $user_id, $type );

		return new WP_REST_Response( $result, 200 );
	}

	private static function validate_object( int $id, string $type ): true|WP_Error {
		if ( 'comment' === $type ) {
			return Middleware::require_approved_comment( $id );
		}

		return Middleware::require_published_post( $id );
	}
}
