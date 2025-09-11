<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker
 *
 * @package Lerm https://www.hanost.com
 *
 * @since lerm 3.0
 */

namespace Lerm\Core;

use Walker_Comment;
use Lerm\Traits\Singleton;
use Lerm\Http\PostLikeController;

class CommentWalker extends Walker_Comment {
	use Singleton;

	// Default arguments
	protected static $argss = array(
		'make_clickable' => true,
		'escape_html'    => true, // NOTE: If true, this will escape comment content BEFORE saving (pre_comment_content). See doc below.
	);

	/**
	 * Constructor
	 *
	 * @param array $args Optional parameters array.
	 */
	public function __construct( array $argss = array() ) {
		// Merge defaults with provided args, then expose a filter for external modification.
		self::$argss = apply_filters( 'lerm_comment_args', wp_parse_args( $argss, self::$argss ) );

		add_action( 'comment_form', array( __CLASS__, 'comment_form_message' ) );

		// If requested, remove make_clickable filter *only if it exists*.
		if ( ! empty( self::$args['make_clickable'] ) && has_filter( 'comment_text', 'make_clickable' ) ) {
			// remove all instances to be sure
			remove_filter( 'comment_text', 'make_clickable' );
		}

		// NOTE: binding esc_html to pre_comment_content will escape HTML BEFORE saving to DB.
		// This is intentional in some setups (completely strip HTML), but it is irreversible and
		// will store the escaped text in the DB. If you prefer to allow HTML in DB and escape on output,
		// set 'escape_html' => false and rely on output escaping.
		if ( ! empty( self::$args['escape_html'] ) ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}
	}

	/**
	 * Displays an extra text area for more comments.
	 *
	 * @param int $post_id The ID of the post where the comment form was rendered.
	 */
	public static function comment_form_message( $post_id ) {
		// This label looks like a placeholder/debug — keep for JS hooks or remove if unused.
		echo '<label id="commentform-msg" class="wow invisible" aria-hidden="true">#</label>';
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

		// Only allow 'div' or 'li' as tag.
		$tag = ( isset( $args['style'] ) && 'div' === $args['style'] ) ? 'div' : 'li';

		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );

		// Prepare safe id attribute.
		$comment_id_attr = 'comment-' . (int) $comment->comment_ID;
		?>
		<<?php echo esc_html( $tag ); ?> <?php comment_class( ( $depth > 1 ) ? 'list-group-item p-0' : 'list-group-item' ); ?> id="<?php echo esc_attr( $comment_id_attr ); ?>">
			<article id="div-comment-<?php echo esc_attr( (int) $comment->comment_ID ); ?>" class="comment-body">
				<footer class="comment-meta mb-1">
					<span class="comment-author vcard">
					<?php
					if ( isset( $args['avatar_size'] ) && 0 !== $args['avatar_size'] ) {
						// Ensure integer avatar size and minimum 1px.
						$avatar_size = ( $comment->comment_parent ) ? round( $args['avatar_size'] * 2 / 3 ) : $args['avatar_size'];
						$avatar_size = max( 1, intval( $avatar_size ) );
						echo get_avatar( $comment, $avatar_size );
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
						// Cast timestamps to int to be safe.
						$comment_timestamp = (int) get_comment_time( 'U', true, $comment );
						$current_timestamp = (int) current_datetime()->getTimestamp();
						// human_time_diff expects unix timestamps.
						$time_diff = human_time_diff( $comment_timestamp, $current_timestamp );

						// i18n: wrap localized "ago" so translators can reorder if needed.
						/* translators: %s: human-readable time difference as returned by human_time_diff(), for example "2 days". */
						$time_text = sprintf( __( '%s ago', 'lerm' ), $time_diff );

						printf(
							'<span aria-hidden="true">&bull;</span><a href="%s"><time datetime="%s">%s</time></a>',
							esc_url( get_comment_link( $comment, $args ) ),
							esc_attr( get_comment_time( 'c', true, $comment ) ),
							esc_html( $time_text )
						);
						edit_comment_link( __( 'Edit', 'lerm' ), '<span aria-hidden="true">&bull;</span><span class="edit-link">', '</span>' );
						?>
					</span>
					<div class="reply float-end">
						<?php
						if ( '0' !== $comment->comment_approved ) :
							comment_reply_link(
								array_merge(
									(array) $args,
									array(
										'reply_text' => '<i class="li li-comment me-2"></i><span class="screen-reader-text">' . __( 'Respond', 'lerm' ) . '</span>',
										'depth'      => $depth,
										'max_depth'  => $args['max_depth'],
									)
								)
							);

							PostLikeController::get_likes_button(
								$post->ID,
								true,
								array(
									'style' => 'a',
									'class' => 'text-danger',
									'text'  => __( 'Like', 'lerm' ),
									'echo'  => true,
								)
							);
							endif;
						?>
					</div>
				</footer>
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<span class="comment-awaiting-moderation badge rounded-pill bg-info"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lerm' ); ?></span>
				<?php endif; ?>
				<section class="comment-content" style="margin-left: <?php echo ( ( ( wp_is_mobile() ? 32 : 48 ) + 8 ) . 'px' ); ?>">
					<?php
					comment_text( $comment, $args );
					?>
				</section>
			</article>
		<?php
	}
}
