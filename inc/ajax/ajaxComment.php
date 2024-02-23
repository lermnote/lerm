<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc\Ajax;

use Walker_Comment;
use Lerm\Inc\Traits\Singleton;

class AjaxComment {

	// Instance
	use singleton;

	public const AJAX_ACTION = 'ajax_comment';

	public static $args = array(
		'make_clickable' => true,
		'escape_html'    => true,
	);

	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );

		// Disable make_clickable filter if specified
		if ( self::$args['make_clickable'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );
		}

		// Enable esc_html filter for comment content if specified
		if ( self::$args['escape_html'] ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}

		$this->register();
	}

	public static function register() {
		// Register ajax handlers for both logged in and non-logged in users
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );
	}

	public function ajax_handle() {
		// Check the AJAX nonce and handle comment submission
		check_ajax_referer( 'ajax_nonce', 'security', true );

		// Handle comment submission
		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );

		// Check if the comment submission was successful
		if ( ! is_wp_error( $comment ) ) {
			// Get comment post ID
			$comment_post_id = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;
			if ( 0 === $comment_post_id ) {
				return;
			}

			// Set avatar URL and size
			// $comment->avatar_url  = get_avatar_url( $comment );
			// $comment->avatar_size = ( 0 !== $_POST['comment_parent'] ) ? ( wp_is_mobile() ? 32 : 48 ) * 2 / 3 : ( wp_is_mobile() ? 32 : 48 );
			// Set avatar URL and size
			$avatar_url  = get_avatar_url( $comment );
			$avatar_size = ( 0 !== $_POST['comment_parent'] ) ? ( wp_is_mobile() ? 32 : 48 ) * 2 / 3 : ( wp_is_mobile() ? 32 : 48 );
			// Set comment cookies consent
			$user = wp_get_current_user();
			if ( isset( $_POST['wp-comment-cookies-consent'] ) && 'yes' === $_POST['wp-comment-cookies-consent'] ) {
				do_action( 'set_comment_cookies', $comment, $user );
			}
			// Send JSON success response with the comment data
			wp_send_json_success(
				array(
					'comment'     => $comment,
					'avatar_url'  => $avatar_url,
					'avatar_size' => $avatar_size,
				)
			);

		} else {
			// If there's an error, send JSON error response
			wp_send_json_error( $comment->get_error_message() );
		}
	}
}
