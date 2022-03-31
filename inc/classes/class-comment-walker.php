<?php
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

class Comment_Walker extends Walker_Comment {

	use Singleton;

	public static $args = array(
		'make_clickable' => true,
		'escape_html'    => true,
	);

	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_optimize_', wp_parse_args( $params, self::$args ) );
		$this->register();
	}
	public function register() {
		add_action( 'wp_ajax_nopriv_ajax_comment', array( $this, 'ajax_comment' ) );
		add_action( 'wp_ajax_ajax_comment', array( $this, 'ajax_comment' ) );
		if ( self::$args['make_clickable'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );}
		if ( self::$args['escape_html'] ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}
	}

	public function ajax_comment( $comment ) {
		// Check ajax nonce first
		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );

		$comment_post_id = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;

		if ( check_ajax_referer( 'ajax_nonce', 'security', false ) && ! is_wp_error( $comment ) && 0 !== $comment_post_id ) {
			ob_start();

			/**
			 * Set Cookies checkbox
			 *
			 * @since 3.2
			 */
			$user = wp_get_current_user();
			if ( isset( $_POST['wp-comment-cookies-consent'] ) && 'yes' === $_POST['wp-comment-cookies-consent'] ) {
				do_action( 'set_comment_cookies', $comment, $user );
			}
			// Set the globals, so our comment functions below will work correctly
			$GLOBALS['comment'] = $comment;

			$this->html5_comment( $comment, '1', array( 'avatar_size' => wp_is_mobile() ? 32 : 48 ) );

			wp_send_json_success( ob_get_clean() );

		} else {
			$error = intval( $comment->get_error_data() );
			if ( ! empty( $error ) ) {
				wp_send_json_error( $comment->get_error_message() );
			}
		}
		wp_die();
	}

	protected function html5_comment( $comment, $depth, $args ) {
		if ( 'div' === $args['style'] ) {
			$tag       = 'div';
			$add_below = 'comment';
		} else {
			$tag       = 'li';
			$add_below = 'div-comment';
		}
		?>
		<li <?php comment_class( ( $depth > 1 ) ? 'list-group-item p-0' : 'list-group-item' ); ?> id="comment-<?php comment_ID(); ?>">
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta mb-1">
					<span class="comment-author vcard">
						<?php
						$comment_author_url = get_comment_author_url( $comment );
						$comment_author     = get_comment_author( $comment );
						$avatar             = get_avatar( $comment, $args['avatar_size'] );
						if ( 0 !== $args['avatar_size'] ) {
							if ( empty( $comment_author_url ) ) {
								echo wp_kses_post( $avatar );
							} else {
								printf( '<a href="%s" rel="external nofollow" class="url">', $comment_author_url ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Escaped in https://developer.wordpress.org/reference/functions/get_comment_author_url/
								echo wp_kses_post( $avatar );
							}
						}
						printf(
							'<span class="fn">%1$s</span><span class="screen-reader-text says">%2$s</span>',
							esc_html( $comment_author ),
							esc_html__( 'says:', 'lerm' )
						);

						if ( ! empty( $comment_author_url ) ) {
							echo '</a>';
						}
						?>
					</span>
					<!--.comment-author -->
					<span>
						<a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
							<?php
							/* Translators: 1 = comment date, 2 = comment time */
							$comment_timestamp = sprintf( __( '%1$s at %2$s', 'lerm' ), get_comment_date( '', $comment ), get_comment_time() );
							?>
							<time datetime="<?php comment_time( 'c' ); ?>" title="<?php echo esc_attr( $comment_timestamp ); ?>">
								<?php echo esc_html( human_time_diff( get_comment_time( 'U' ), current_datetime()->getTimestamp() ) ); ?>
								<span> <?php echo esc_html__( 'ago', 'lerm' ); ?></span>
							</time>
						</a>
						<?php
						if ( get_edit_comment_link() ) {
							echo ' <span aria-hidden="true">&bull;</span> <a class="comment-edit-link" href="' . esc_url( get_edit_comment_link() ) . '">' . esc_html__( 'Edit', 'lerm' ) . '</a>';
						}
						?>
					</span>
					<div class="reply float-end">
						<?php
						comment_reply_link(
							array_merge(
								$args,
								array(
									'add_below' => 'div-comment',
									'depth'     => $depth,
									'max_depth' => $args['max_depth'],
								)
							)
						);
						?>
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
