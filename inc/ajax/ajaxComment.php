<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://lerm.net
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc\Ajax;

use Lerm\Inc\Traits\Singleton;

final class AjaxComment extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION = 'ajax_comment';

	protected static $args = array();

	public function __construct() {
		parent::__construct();
	}

	/**
	 * AJAX handler for processing comment submissions.
	 */
	public static function ajax_handle() {
		// Check the AJAX nonce and handle comment submission
		check_ajax_referer( static::AJAX_ACTION, 'security', true );

		$postdata = wp_unslash( $_POST );
		// Handle comment submission
		$comment = wp_handle_comment_submission( $postdata );

		// Check if the comment submission was successful
		if ( is_wp_error( $comment ) ) {
			 self::error([
                'code'    => $comment->get_error_code(),
                'message' => $comment->get_error_message(),
            ]);
			return;
		}

		// Get comment post ID
		$comment_post_id = isset( $postdata['comment_post_ID'] ) ? (int) $postdata['comment_post_ID'] : 0;
		if ( 0 === $comment_post_id ) {
			self::error( 'Invalid post ID.', 'lerm' );
			return;
		}

		// Set avatar URL and size
		$avatar_url     = get_avatar_url( $comment );
		$comment_parent = isset( $postdata['comment_parent'] ) ? absint( $postdata['comment_parent'] ) : 0;

        $base_size      = wp_is_mobile() ? 32 : 48;
        $avatar_size    = $comment_parent ? intval( $base_size * 2 / 3 ) : $base_size;


		// Set comment cookies consent
		if ( isset( $postdata['wp-comment-cookies-consent'] ) && 'yes' === $postdata['wp-comment-cookies-consent'] ) {
			do_action( 'set_comment_cookies', $comment, wp_get_current_user() );
		}

		// Send JSON success response with the comment data
		self::success(
			array(
				'comment'     => $comment,
				'avatar_url'  => $avatar_url,
				'avatar_size' => $avatar_size,
			)
		);
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
		$data = array(
			'ajax_nonce'  => wp_create_nonce( static::AJAX_ACTION ),
			'comment_actions' => self::AJAX_ACTION,
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}																							
}
