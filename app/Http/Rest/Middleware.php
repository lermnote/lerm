<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest;

use WP_Error;
use WP_REST_Request;
use function Lerm\Support\client_ip;

/**
 * Reusable REST middleware helpers.
 *
 * @package Lerm\Http\Rest
 */
final class Middleware {

	/**
	 * Apply a simple per-IP rate limit for a given action.
	 */
	public static function rate_limit( string $action, int $limit = 10, int $window = 60 ): true|WP_Error {
		$ip  = client_ip();
		$key = 'lerm_rl_' . md5( $action . $ip );

		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many requests. Please try again later.', 'lerm' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $key, $count + 1, $window );

		return true;
	}

	/**
	 * Verify a REST nonce from the request header or request params.
	 */
	public static function verify_nonce( WP_REST_Request $request, string $action = 'wp_rest' ): true|WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' )
			?? $request->get_param( '_wpnonce' )
			?? '';

		if ( ! wp_verify_nonce( (string) $nonce, $action ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Security verification failed. Please refresh the page and try again.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Require a logged-in user.
	 */
	public static function require_login(): true|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				__( 'Please log in first.', 'lerm' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Require a specific capability.
	 */
	public static function require_capability( string $capability ): true|WP_Error {
		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to perform this action.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Require a published post.
	 */
	public static function require_published_post( int $post_id ): true|WP_Error {
		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'lerm' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	/**
	 * Require an approved comment.
	 */
	public static function require_approved_comment( int $comment_id ): true|WP_Error {
		$comment = get_comment( $comment_id );

		if ( ! $comment || '1' !== (string) $comment->comment_approved ) {
			return new WP_Error(
				'comment_not_found',
				__( 'Comment not found.', 'lerm' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	/**
	 * Run multiple checks and stop at the first failure.
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
