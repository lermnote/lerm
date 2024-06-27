<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;

class AjaxComment {

	// Instance
	use singleton;

	public const AJAX_ACTION = 'ajax_comment';

	public static $args = array();

	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_comment_', wp_parse_args( $params, self::$args ) );

		// Disable make_clickable filter if specified
		if ( self::$args['make_clickable'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );
		}

		// Enable esc_html filter for comment content if specified
		if ( self::$args['escape_html'] ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'ajax_l10n_data' ) );
		self::register();
	}

	public static function register( $public = false ) {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );

		// Register ajax handlers for both logged in and non-logged in users
		if ( $public ) {
			add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );
		}
	}

	public function ajax_handle() {
		// Check the AJAX nonce and handle comment submission
		check_ajax_referer( 'ajax_nonce', 'security', true );

		// Handle comment submission
		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );

		// Check if the comment submission was successful
		if ( ! is_wp_error( $comment ) ) {
			// Get comment post ID
			$comment_post_id = isset( $comment['comment_post_ID'] ) ? (int) $comment['comment_post_ID'] : 0;
			if ( 0 === $comment_post_id ) {
				wp_send_json_error( 'Invalid post ID.' );
				return;
			}

			// Set avatar URL and size
			$avatar_url  = get_avatar_url( $comment );
			$avatar_size = ( 0 !== $comment['comment_parent'] ) ? ( wp_is_mobile() ? 32 : 48 ) * 2 / 3 : ( wp_is_mobile() ? 32 : 48 );

			// Set comment cookies consent
			if ( isset( $comment['wp-comment-cookies-consent'] ) && 'yes' === $comment['wp-comment-cookies-consent'] ) {
				do_action( 'set_comment_cookies', $comment, wp_get_current_user() );
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

	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {

		$data = array();
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
