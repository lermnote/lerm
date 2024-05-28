<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc;

use Walker_Comment;
use Lerm\Inc\Traits\Singleton;

class CommentWalker extends Walker_Comment {
	// Instance
	use singleton;

	public const AJAX_ACTION = 'ajax_comment';

	public static $args = array(
		'make_clickable' => true,
		'escape_html'    => true,
	);

	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		if ( self::$args['make_clickable'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );}
		if ( self::$args['escape_html'] ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}
		$this->register();
	}

	public static function register() {
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handle' ) );
	}

	public static function ajax_handle() {
		// Check ajax nonce first
		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );

		if ( check_ajax_referer( 'ajax_nonce', 'security', false ) && ! is_wp_error( $comment ) ) {

			$comment_post_id = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;
			if ( 0 === $comment_post_id ) {
				return;
			}
			$avatar_url  = get_avatar_url( $comment );
			$avatar_size = ( 0 !== $_POST['comment_parent'] ) ? ( wp_is_mobile() ? 32 : 48 ) * 2 / 3 : ( wp_is_mobile() ? 32 : 48 );
			/**
			 * Set Cookies checkbox
			 *
			 * @since 3.2
			 */
			$user = wp_get_current_user();
			if ( isset( $_POST['wp-comment-cookies-consent'] ) && 'yes' === $_POST['wp-comment-cookies-consent'] ) {
				do_action( 'set_comment_cookies', $comment, $user );
			}

			wp_send_json_success(
				array(
					'comment'     => $comment,
					'avatar_url'  => $avatar_url,
					'avatar_size' => $avatar_size,
				)
			);
		} else {
			$error = intval( $comment->get_error_data() );
			if ( ! empty( $error ) ) {
				wp_send_json_error( $comment->get_error_message() );
			}
		}
		wp_die();
	}

	public function html5_comment( $comment, $depth, $args ) {
		global $post;
		$tag                = ( 'div' === $args['style'] ) ? 'div' : 'li';
		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );

		if ( $commenter['comment_author_email'] ) {
			$moderation_note = __( 'Your comment is awaiting moderation.', 'lerm' );
		} else {
			$moderation_note = __( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'lerm' );
		}
		?>
		<li <?php comment_class( ( $depth > 1 ) ? 'list-group-item p-0' : 'list-group-item' ); ?> id="comment-<?php comment_ID(); ?>">
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta mb-1">
					<span class="comment-author vcard">
						<?php
						if ( 0 !== $args['avatar_size'] ) {
							$args['avatar_size'] = ( $comment->comment_parent ) ? $args['avatar_size'] * 2 / 3 : $args['avatar_size'];
							echo get_avatar( $comment, $args['avatar_size'] );
						}

						$comment_author = get_comment_author_link( $comment );

						if ( '0' === $comment->comment_approved && ! $show_pending_links ) {
							$comment_author = get_comment_author( $comment );
						}

						printf(
							/* translators: %s: Comment author link. */
							'<b class="fn">%s</b>',
							wp_kses(
								$comment_author,
								array(
									'a' => array(
										'href'  => array(),
										'class' => array(),
										'rel'   => array(),
									),
								)
							)
						);
						?>
					</span>
					<!--.comment-author -->
					<span class="comment-metadata">
						<?php
						$comment_timestamp = get_comment_time( 'U', true );
						$current_timestamp = current_datetime()->getTimestamp();
						$time_diff         = human_time_diff( $comment_timestamp, $current_timestamp ) ?? 'unknown time';

						printf(
							'<span aria-hidden="true">&bull;</span><a href="%s"><time datetime="%s">%s</time></a>',
							esc_url( get_comment_link( $comment, $args ) ),
							esc_attr( get_comment_time( 'c' ) ),
							sprintf(
								/* translators: 1: Comment date, 2: Comment time. */
								esc_html( '%1$s %2$s' ),
								esc_html( $time_diff ),
								esc_html__( 'ago', 'lerm' )
							)
						);
						edit_comment_link( __( 'Edit', 'lerm' ), '<span aria-hidden="true">&bull;</span><span class="edit-link">', '</span>' );
						?>
					</span>
					<div class="reply float-end">
						<?php
						comment_reply_link(
							array_merge(
								$args,
								array(
									'depth'     => $depth,
									'max_depth' => $args['max_depth'],
								)
							)
						);
						?>
						<?php echo \Lerm\Inc\PostLike::get_likes_button( $post->ID, true ); ?>
					</div>
				</footer>
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<span class="comment-awaiting-moderation badge rounded-pill bg-info"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lerm' ); ?></span>
				<?php endif; ?>
				<section class="comment-content" style="margin-left: <?php echo ( ( ( wp_is_mobile() ? 32 : 48 ) + 8 ) . 'px' ); ?>" >
					<?php comment_text(); ?>
				</section>
			</article>
		<?php
	}
}
