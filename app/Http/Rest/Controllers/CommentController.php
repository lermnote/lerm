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
		$options = self::theme_options();

		$check = Middleware::chain(
			fn() => Middleware::verify_nonce( $request ),
			fn() => Middleware::require_published_post( $post_id ),
			fn() => Middleware::rate_limit( sprintf( 'comment_%d', $post_id ), 10, 60 )
		);

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		if ( isset( $options['comments_enable'] ) && empty( $options['comments_enable'] ) ) {
			return new WP_Error(
				'comments_disabled',
				__( 'Comments are disabled.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		if ( ! empty( $options['comments_require_login'] ) && ! is_user_logged_in() ) {
			return new WP_Error(
				'comments_login_required',
				__( 'You must be logged in to comment.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		if ( ! comments_open( $post_id ) ) {
			return new WP_Error(
				'comments_closed',
				__( 'Comments are closed.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		$data  = self::request_data( $request );
		$rules = self::validate_theme_comment_rules( $data, $options );

		if ( is_wp_error( $rules ) ) {
			return $rules;
		}

		$comment = wp_handle_comment_submission( $data );
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
			'comment'                     => trim( self::request_string( $request, 'comment' ) ),
			'author'                      => sanitize_text_field( self::request_string( $request, 'author' ) ),
			'email'                       => sanitize_email( self::request_string( $request, 'email' ) ),
			'url'                         => esc_url_raw( self::request_string( $request, 'url' ) ),
			'_wp_unfiltered_html_comment' => self::request_string( $request, '_wp_unfiltered_html_comment' ),
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
		$options     = self::theme_options();
		$avatar_size = max( 24, (int) ( $options['comment_avatar_size'] ?? ( wp_is_mobile() ? 32 : 48 ) ) );

		ob_start();

		wp_list_comments(
			array(
				'walker'            => new CommentWalker(),
				'style'             => 'ol',
				'format'            => 'html5',
				'avatar_size'       => wp_is_mobile() ? min( $avatar_size, 32 ) : $avatar_size,
				'per_page'          => 1,
				'reverse_top_level' => false,
			),
			array( $comment )
		);

		return trim( (string) ob_get_clean() );
	}

	/**
	 * Theme-level comment constraints layered on top of WordPress core checks.
	 *
	 * @param array<string, mixed> $data Normalized comment request data.
	 * @param array<string, mixed> $options Template options.
	 */
	private static function validate_theme_comment_rules( array $data, array $options ): true|WP_Error {
		$comment = trim( (string) ( $data['comment'] ?? '' ) );
		$min     = max( 0, (int) ( $options['comment_min_length'] ?? 0 ) );
		$max     = max( 0, (int) ( $options['comment_max_length'] ?? 0 ) );

		if ( $min > 0 && mb_strlen( $comment, 'UTF-8' ) < $min ) {
			return new WP_Error(
				'comment_too_short',
				sprintf( __( 'Comment must be at least %d characters long.', 'lerm' ), $min ),
				array( 'status' => 400 )
			);
		}

		if ( $max > 0 && mb_strlen( $comment, 'UTF-8' ) > $max ) {
			return new WP_Error(
				'comment_too_long',
				sprintf( __( 'Comment must be no more than %d characters long.', 'lerm' ), $max ),
				array( 'status' => 400 )
			);
		}

		if ( is_user_logged_in() ) {
			return true;
		}

		$required = array_map( 'strval', (array) ( $options['comment_form_fields'] ?? array( 'name', 'email' ) ) );

		if ( in_array( 'name', $required, true ) && '' === trim( (string) ( $data['author'] ?? '' ) ) ) {
			return new WP_Error(
				'comment_author_required',
				__( 'Name is required.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( in_array( 'email', $required, true ) && '' === trim( (string) ( $data['email'] ?? '' ) ) ) {
			return new WP_Error(
				'comment_email_required',
				__( 'Email is required.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( in_array( 'website', $required, true ) && '' === trim( (string) ( $data['url'] ?? '' ) ) ) {
			return new WP_Error(
				'comment_website_required',
				__( 'Website is required.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Load template options without hard-coupling the controller to bootstrap globals.
	 *
	 * @return array<string, mixed>
	 */
	private static function theme_options(): array {
		return function_exists( 'lerm_get_template_options' ) ? \lerm_get_template_options() : array();
	}

	private static function request_string( WP_REST_Request $request, string $key ): string {
		$value = $request->get_param( $key );

		return is_scalar( $value ) ? (string) $value : '';
	}
}
