<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */

namespace Lerm\Inc\Core;

use Walker_Comment;
use Lerm\Inc\Traits\Singleton;

class CommentWalker extends Walker_Comment {
	use singleton;

	// Default arguments
	protected static $args = array(
		'make_clickable' => true,
		'escape_html'    => true,
	);

	/**
	 * Constructor
	 *
	 * @param array $params Optional parameters.
	 */
	public function __construct( $params ) {
		self::$args = apply_filters( 'lerm_comment_args', wp_parse_args( $params, self::$args ) );
		add_action( 'comment_form', array( $this, 'comment_form_message' ) );

		if ( self::$args['make_clickable'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );
		}

		if ( self::$args['escape_html'] ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}

	}
	/**
	 * Displays an extra text area for more comments.
	 *
	 * @param int $post_id The ID of the post where the comment form was rendered.
	 */
	public function comment_form_message( $post_id ) {
		echo '<label id="commentform-msg" class="wow invisible">#</label>';
	}


	/**
	 * Output a comment in the HTML5 format.
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	public function html5_comment( $comment, $depth, $args ) {
		global $post;
		$tag                = ( 'div' === $args['style'] ) ? 'div' : 'li';
		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );
		?>
		<<?php echo esc_html( $tag ); ?> <?php comment_class( ( $depth > 1 ) ? 'list-group-item p-0' : 'list-group-item' ); ?> id="comment-<?php comment_ID(); ?>">
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
							'<span aria-hidden="true">&bull;</span><a href="%s"><time datetime="%s">%s %s</time></a>',
							esc_url( get_comment_link( $comment, $args ) ),
							esc_attr( get_comment_time( 'c' ) ),
							esc_html( $time_diff ),
							esc_html__( 'ago', 'lerm' )
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
						<?php
						\Lerm\Inc\Ajax\PostLike::get_likes_button(
							$post->ID,
							true,
							array(
								'style' => 'a',
								'class' => 'text-danger',
								'text'  => __( 'Like', 'lerm' ),
								'echo'  => true,
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
