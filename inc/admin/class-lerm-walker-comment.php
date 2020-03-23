<?php
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */
class Lerm_Walker_Comment extends Walker_Comment {
	public function __construct() {
		$this->register();
	}
	public function register() {
		add_action( 'wp_ajax_nopriv_ajax_comment', array( $this, 'ajax_comment' ) );
		add_action( 'wp_ajax_ajax_comment', array( $this, 'ajax_comment' ) );
		//add_filter( 'preprocess_comment', array( $this, 'lerm_comment_post' ), '', 1 );
	}
	public function ajax_comment( $comment ) {
		// Check ajax nonce first
		check_ajax_referer( 'ajax_nonce', 'security' );

		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );
		if ( is_wp_error( $comment ) ) {
			$error = intval( $comment->get_error_data() );
			if ( ! empty( $error ) ) {
				wp_die(
					wp_kses( $comment->get_error_message(), array( 'strong' => array() ) ),
					array(
						'response'  => esc_attr( $error ),
						'back_link' => true,
					)
				);
			}
		}
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
		$this->html5_comment( $comment, '1', array( 'avatar_size' => '48' ) );
		wp_die();
	}
	protected function html5_comment( $comment, $depth, $args ) {
		?>
		<li <?php comment_class( 'card p-3 mb-2' ); ?> id="comment-<?php comment_ID(); ?>">
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
					</span><!--.comment-author -->
					<span>
						<a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
							<?php
							/* Translators: 1 = comment date, 2 = comment time */
							$comment_timestamp = sprintf( __( '%1$s at %2$s', 'lerm' ), get_comment_date( '', $comment ), get_comment_time() );
							?>
							<time datetime="<?php comment_time( 'c' ); ?>" title="<?php echo esc_attr( $comment_timestamp ); ?>">
								<?php echo esc_html( human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
								<span> <?php echo esc_html__( 'ago', 'lerm' ); ?></span>
							</time>
						</a>
					<?php
					if ( get_edit_comment_link() ) {
						echo ' <span aria-hidden="true">&bull;</span> <a class="comment-edit-link" href="' . esc_url( get_edit_comment_link() ) . '">' . esc_html__( 'Edit', 'lerm' ) . '</a>';
					}
					?>
					</span>
					<span class="reply float-right">
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
					</span>
				</footer>
				<?php if ( '0' === $comment->comment_approved ) { ?>
					<p class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lerm' ); ?></p>
				<?php } ?>
				<section class="comment-content">
					<?php comment_text(); ?>
				</section>
			</article>
			<?php
	}

	// Disable HTML in comment
	public function lerm_comment_post( $incoming_comment ) {
		// convert everything in a comment to display literally
		$incoming_comment['comment_content'] = htmlspecialchars( $incoming_comment['comment_content'] );
		// the one exception is single quotes, which cannot be #03d; because WordPress marks it as spam
		return( $incoming_comment );
	}
	//disable html in comment content
	// add_filter( 'pre_comment_content', 'wp_specialchars' );
}
function lerm_comment() {
	return new Lerm_Walker_Comment();
}
lerm_comment();

/**
 * Make url unclickable in comment content.
 *
 *  @author 智慧宫
 * @link   https://www.hanost.com
 */
remove_filter( 'comment_text', 'make_clickable', 9 );
// add_filter( 'pre_comment_content', 'wp_specialchars' );

