<?php // phpcs:disable WordPress.Files.FileName
/**
 * Comments walker.
 *
 * @package Lerm
 */
declare( strict_types = 1 );
namespace Lerm\Core;

use Walker_Comment;
use Lerm\View\LikeButton;
use Lerm\Traits\Singleton;


class CommentWalker extends Walker_Comment {
	use Singleton;

	protected static $args = array(
		'make_clickable' => true,
		'escape_html'    => true,
	);

	public function __construct( array $args = array() ) {
		self::$args = apply_filters( 'lerm_comment_args', wp_parse_args( $args, self::$args ) );
		$this->hooks();
	}

	public static function hooks() {
		add_action( 'comment_form', array( __CLASS__, 'comment_form_message' ) );

		if ( ! empty( self::$args['make_clickable'] ) && has_filter( 'comment_text', 'make_clickable' ) ) {
			remove_filter( 'comment_text', 'make_clickable' );
		}

		if ( ! empty( self::$args['escape_html'] ) ) {
			add_filter( 'pre_comment_content', 'esc_html' );
		}
	}

	public static function comment_form_message( $post_id ) {
		echo '<label id="commentform-msg" class="wow invisible" aria-hidden="true">#</label>';
	}

	public function html5_comment( $comment, $depth, $args ) {
		global $post;

		$tag = ( isset( $args['style'] ) && 'div' === $args['style'] ) ? 'div' : 'li';

		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );

		$comment_id_attr = 'comment-' . (int) $comment->comment_ID;
		?>
		<<?php echo esc_html( $tag ); ?> <?php comment_class( ( $depth > 1 ) ? 'list-group-item p-0' : 'list-group-item' ); ?> id="<?php echo esc_attr( $comment_id_attr ); ?>">
			<article id="div-comment-<?php echo esc_attr( (int) $comment->comment_ID ); ?>" class="comment-body">
				<footer class="comment-meta mb-1">
					<span class="comment-author vcard">
						<?php
						if ( isset( $args['avatar_size'] ) && 0 !== $args['avatar_size'] ) {
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
					<span class="comment-metadata">
					<?php
					$comment_timestamp = (int) get_comment_time( 'U', true, $comment );
					$current_timestamp = (int) current_datetime()->getTimestamp();
					$time_diff         = human_time_diff( $comment_timestamp, $current_timestamp );

					/* translators: %s: human-readable time difference. */
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
							LikeButton::render(
								(int) $comment->comment_ID,
								true,
								array( 'class' => 'text-danger me-2' )
							);

							comment_reply_link(
								array_merge(
									(array) $args,
									array(
										'reply_text' => '<i class="fa fa-comment"></i><span class="screen-reader-text">' . __( 'Respond', 'lerm' ) . '</span>',
										'depth'      => $depth,
										'max_depth'  => $args['max_depth'],
									)
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
