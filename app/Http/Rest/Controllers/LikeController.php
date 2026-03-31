<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\LikeRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function Lerm\Support\get_like_user_id;

/**
 * Like endpoint controller.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class LikeController {

	private static function likes_enabled( string $type ): bool {
		$options = function_exists( 'lerm_get_template_options' ) ? \lerm_get_template_options() : array();

		if ( 'comment' === $type ) {
			return ! isset( $options['comment_likes_enable'] ) || ! empty( $options['comment_likes_enable'] );
		}

		return ! isset( $options['post_likes_enable'] ) || ! empty( $options['post_likes_enable'] );
	}

	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request->get_param( 'id' ) );
		$type = sanitize_key( $request->get_param( 'type' ) !== null ? $request->get_param( 'type' ) : 'post' );

		if ( ! self::likes_enabled( $type ) ) {
			return new WP_REST_Response(
				array(
					'count'  => 0,
					'liked'  => false,
					'status' => 'disabled',
				),
				200
			);
		}

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

	public static function toggle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request->get_param( 'id' ) );
		$type = sanitize_key( $request->get_param( 'type' ) !== null ? $request->get_param( 'type' ) : 'post' );

		if ( ! self::likes_enabled( $type ) ) {
			return new WP_Error(
				'likes_disabled',
				__( 'Like button is disabled.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		if ( headers_sent() && ! is_user_logged_in() ) {
			return new WP_Error(
				'headers_already_sent',
				__( 'Cannot set like tracking cookie. Response headers already sent.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		$check = Middleware::chain(
			fn() => self::validate_object( $id, $type ),
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
