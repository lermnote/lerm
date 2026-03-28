<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Core\CommentWalker;
use Lerm\Http\Rest\Middleware;
use WP_Comment;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Frontend comment submission endpoint.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class CommentController {

	/**
	 * Create a new comment via REST.
	 */
	public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'comment_post_ID' ) );

		$check = Middleware::chain(
			fn() => Middleware::verify_nonce( $request ),
			fn() => Middleware::require_published_post( $post_id ),
			fn() => Middleware::rate_limit( sprintf( 'comment_%d', $post_id ), 10, 60 )
		);

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		if ( ! comments_open( $post_id ) ) {
			return new WP_Error(
				'comments_closed',
				__( 'Comments are closed.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		$comment = wp_handle_comment_submission( self::request_data( $request ) );
		if ( is_wp_error( $comment ) ) {
			return $comment;
		}

		$user            = wp_get_current_user();
		$cookies_consent = ! empty( $request->get_param( 'wp-comment-cookies-consent' ) );

		do_action( 'set_comment_cookies', $comment, $user, $cookies_consent );

		$comment = get_comment( $comment->comment_ID );
		if ( ! $comment instanceof WP_Comment ) {
			return new WP_Error(
				'comment_not_found',
				__( 'Comment was created, but could not be loaded.', 'lerm' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'message'      => '1' === (string) $comment->comment_approved
					? __( 'Comment submitted successfully.', 'lerm' )
					: __( 'Your comment is awaiting moderation.', 'lerm' ),
				'comment'      => self::serialize_comment( $comment ),
				'comment_html' => self::render_comment_html( $comment ),
			),
			201
		);
	}

	/**
	 * Normalize the request payload into the shape expected by core comment APIs.
	 */
	private static function request_data( WP_REST_Request $request ): array {
		return array(
			'comment_post_ID'             => absint( $request->get_param( 'comment_post_ID' ) ),
			'comment_parent'              => absint( $request->get_param( 'comment_parent' ) ),
			'comment'                     => trim( (string) $request->get_param( 'comment' ) ),
			'author'                      => sanitize_text_field( (string) $request->get_param( 'author' ) ),
			'email'                       => sanitize_email( (string) $request->get_param( 'email' ) ),
			'url'                         => esc_url_raw( (string) $request->get_param( 'url' ) ),
			'_wp_unfiltered_html_comment' => (string) $request->get_param( '_wp_unfiltered_html_comment' ),
		);
	}

	/**
	 * Build the minimal comment data the frontend needs for placement.
	 */
	private static function serialize_comment( WP_Comment $comment ): array {
		return array(
			'comment_ID'       => (int) $comment->comment_ID,
			'comment_parent'   => (int) $comment->comment_parent,
			'comment_approved' => (string) $comment->comment_approved,
		);
	}

	/**
	 * Render a single comment item so AJAX output matches server-side output.
	 */
	private static function render_comment_html( WP_Comment $comment ): string {
		ob_start();

		wp_list_comments(
			array(
				'walker'            => new CommentWalker(),
				'style'             => 'ol',
				'format'            => 'html5',
				'avatar_size'       => wp_is_mobile() ? 32 : 48,
				'per_page'          => 1,
				'reverse_top_level' => false,
			),
			array( $comment )
		);

		return trim( (string) ob_get_clean() );
	}
}
