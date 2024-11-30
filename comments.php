<?php
/**
 * The template for displaying comments
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 *
* @package Lerm https://lerm.net
 * @date   2019-12-11 21:57:52
 * @since  2.0
 */
global $post_id;
if ( post_password_required() ) {
	return;
}
use Lerm\Inc\Core\CommentWalker;
?>
<div id="comments" class="comments">
	<?php if ( comments_open() || pings_open() ) : ?>
		<?php
		$commenter = wp_get_current_commenter();
		sanitize_comment_cookies();
		$comment_author       = $commenter ['comment_author'];
		$comment_author_email = $commenter ['comment_author_email'];
		$comment_author_url   = $commenter ['comment_author_url'];

		$args = array(
			'comment_notes_before' => '<p class="logged-in-as">' . sprintf(
				'<cite class="fn">%1$s<strong class="ps-2">%2$s</strong></cite><span class="ms-2">%3$s</span>',
				get_avatar( $comment_author_email, 32 ),
				$comment_author ? $comment_author : __( 'Visitor', 'lerm' ),
				$comment_author_email ? __( 'Welcome Back', 'lerm' ) : __( 'Welcome ', 'lerm' )
			) . '</p>',

			'comment_field'        => '<fieldset class="form-group mb-2">
			<textarea id="comment" class="rq form-control mb-1"  required="required" placeholder="留下评论，天下太平" name="comment"></textarea>',

			'fields'               => array(
				'author' => '<div class="form-group input-form"><label class="visually-hidden-focusable" for="author">Username</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" name="author" class="form-control form-control-sm" id="author" value="' . esc_attr( $comment_author ) . '" placeholder="' . __( 'Nickname', 'lerm' ) . '" required></div>',
				'email'  => '<label class="visually-hidden-focusable" for="email">Email</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" name="email" class="form-control form-control-sm" id="email" value="' . esc_attr( $comment_author_email ) . '" placeholder="' . __( 'E-mail', 'lerm' ) . '" required></div>',
				'url'    => '<label class="visually-hidden-focusable" for="url">Url</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-link"></i></span><input type="url" name="url" class="form-control form-control-sm" id="url" value="' . esc_attr( $comment_author_url ) . '" placeholder="' . __( 'Website', 'lerm' ) . '"></div></div></fieldset>',
			),

			'logged_in_as'         => '<p class="logged-in-as">' . sprintf(
				/* translators: 1: edit user link, 2: accessibility text, 3: user name, 4: logout URL */
				'%1$s<cite class="fn"><a href="%2$s" aria-label="%3$s" class="px-2">%4$s</a></cite><a href="%5$s">%6$s</a>',
				get_avatar( get_current_user_id(), 32 ),
				get_edit_user_link(),
				/* translators: %s: user name */
				esc_html( sprintf( __( 'Logged in as %s. Edit your profile.', 'lerm' ), $user_identity ) ),
				$user_identity,
				wp_logout_url( apply_filters( 'the_permalink', get_permalink() ) ),
				__( 'Log out', 'lerm' )
			) . '</p>',
			'must_log_in'          => sprintf(
				'<div class="must-log-in card-body">%s</div>',
				sprintf(
					/* translators: %s: Login URL. */
					__( 'You must be <a class="badge rounded-pill bg-primary text-light" href="%s">logged in</a> to post a comment.', 'lerm' ),
					/** This filter is documented in wp-includes/link-template.php */
					wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ), $post_id ) )
				)
			),
			'class_container'      => 'card comment-respond mb-3',
			'class_form'           => 'card-body comment-form',
			'id_submit'            => 'commentform-submit',
			'submit_button'        => '<button type="submit" class="btn btn-sm btn-custom" id="%1$s">%4$s</button>',
			'title_reply'          => '<i class="fa fa-comments"></i><span>' . esc_html__( 'Leave a Reply', 'lerm' ) . '</span>',
			'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title card-header border-bottom-0">',
		);
		comment_form( $args );
		?>
	<?php endif; ?>

	<?php if ( $comments ) : ?>
		<?php get_template_part( 'template-parts/components/comment-pagination' ); ?>
		<div class="card mb-3">
		<h3 class="comment-title card-header border-bottom-0">
			<?php
			printf(
				/* translators: 1: number of comments, 2: post title */
				esc_html( _nx( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', get_comments_number(), 'comments title', 'lerm' ) ),
				esc_html( number_format_i18n( get_comments_number() ) ),
				esc_html( get_the_title() )
			);
			?>
		</h3>
		<ol class="comment-list p-0 m-0 list-group list-group-flush">
			<?php
			wp_list_comments(
				array(
					'walker'      => CommentWalker::instance(),
					'short_ping'  => true,
					'avatar_size' => wp_is_mobile() ? 32 : 48,
				)
			);
			?>
		</ol><!-- .comment-list -->
		</div>
		<?php if ( ! comments_open() && get_comments_number() ) : ?>
			<p class=" alert alert-info mb-3"><?php esc_html_e( 'Comments are closed.', 'lerm' ); ?></p>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/components/comment-pagination' ); ?>
	<?php endif; // have comments. ?>
</div>
