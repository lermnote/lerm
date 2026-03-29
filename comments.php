<?php
/**
 * The template for displaying comments.
 *
 * @package Lerm
 */

use Lerm\Core\CommentWalker;

if ( post_password_required() ) {
	return;
}

$commenter       = wp_get_current_commenter();
$comment_post_id = get_the_ID();

sanitize_comment_cookies();

$comment_author       = sanitize_text_field( $commenter['comment_author'] ?? '' );
$comment_author_email = sanitize_email( $commenter['comment_author_email'] ?? '' );
$comment_author_url   = esc_url_raw( $commenter['comment_author_url'] ?? '' );
$commenter_identity   = is_user_logged_in()
	? wp_get_current_user()->display_name
	: ( $commenter['comment_author'] ?? '' );
$require_identity     = (bool) get_option( 'require_name_email' );

$args = array(
	'comment_notes_before' => '<p class="logged-in-as">' . sprintf(
		'<cite class="fn">%1$s<strong class="ps-2">%2$s</strong></cite><span class="ms-2">%3$s</span>',
		get_avatar( $comment_author_email, 32 ),
		$comment_author ? $comment_author : __( 'Visitor', 'lerm' ),
		$comment_author_email ? __( 'Welcome Back', 'lerm' ) : __( 'Welcome ', 'lerm' )
	) . '</p>',
	'comment_field'        => '<div class="form-group mb-2"><label class="visually-hidden-focusable" for="comment">' . esc_html__( 'Comment', 'lerm' ) . '</label><textarea id="comment" class="rq form-control mb-1" name="comment" required="required" placeholder="' . esc_attr__( 'Leave a comment...', 'lerm' ) . '" aria-label="' . esc_attr__( 'Comment content', 'lerm' ) . '" rows="4"></textarea></div>',
	'fields'               => array(
		'author' => '<div class="form-group input-form mb-2"><label class="visually-hidden-focusable" for="author">' . esc_html__( 'Username', 'lerm' ) . '</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" name="author" class="form-control form-control-sm" id="author" value="' . esc_attr( $comment_author ) . '" placeholder="' . esc_attr__( 'Nickname', 'lerm' ) . '"' . ( $require_identity ? ' required' : '' ) . '></div></div>',
		'email'  => '<div class="form-group mb-2"><label class="visually-hidden-focusable" for="email">' . esc_html__( 'Email', 'lerm' ) . '</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" name="email" class="form-control form-control-sm" id="email" value="' . esc_attr( $comment_author_email ) . '" placeholder="' . esc_attr__( 'E-mail', 'lerm' ) . '"' . ( $require_identity ? ' required' : '' ) . '></div></div>',
		'url'    => '<div class="form-group mb-2"><label class="visually-hidden-focusable" for="url">' . esc_html__( 'Url', 'lerm' ) . '</label><div class="input-group mb-1"><span class="input-group-text"><i class="fa fa-link"></i></span><input type="url" name="url" class="form-control form-control-sm" id="url" value="' . esc_attr( $comment_author_url ) . '" placeholder="' . esc_attr__( 'Website', 'lerm' ) . '"></div></div>',
	),
	'logged_in_as'         => '<p class="logged-in-as">' . sprintf(
		/* translators: 1: edit user link, 2: accessibility text, 3: user name, 4: logout URL */
		'%1$s<cite class="fn"><a href="%2$s" aria-label="%3$s" class="px-2">%4$s</a></cite><a href="%5$s">%6$s</a>',
		get_avatar( get_current_user_id(), 32 ),
		get_edit_user_link(),
		/* translators: %s: user name */
		esc_html( sprintf( __( 'Logged in as %s. Edit your profile.', 'lerm' ), $commenter_identity ) ),
		$commenter_identity,
		wp_logout_url( apply_filters( 'the_permalink', get_permalink() ) ),
		__( 'Log out', 'lerm' )
	) . '</p>',
	'must_log_in'          => sprintf(
		'<div class="must-log-in card-body">%s</div>',
		sprintf(
			/* translators: %s: Login URL. */
			__( 'You must be <a class="badge rounded-pill bg-primary text-light" href="%s">logged in</a> to post a comment.', 'lerm' ),
			wp_login_url( apply_filters( 'the_permalink', get_permalink( $comment_post_id ), $comment_post_id ) )
		)
	),
	'class_container'      => 'card comment-respond mb-3',
	'class_form'           => 'card-body comment-form',
	'id_submit'            => 'commentform-submit',
	'submit_button'        => '<button type="submit" class="btn btn-sm btn-custom" id="%1$s">%4$s</button>',
	'title_reply'          => '<i class="fa fa-comments"></i><span>' . esc_html__( 'Leave a Reply', 'lerm' ) . '</span>',
	'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title card-header border-bottom-0">',
);
?>
<div id="comments" class="comments">
	<?php if ( comments_open() || pings_open() ) : ?>
		<?php comment_form( $args ); ?>
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
						'walker'      => new CommentWalker(),
						'style'       => 'ol',
						'format'      => 'html5',
						'avatar_size' => wp_is_mobile() ? 32 : 48,
					)
				);
				?>
			</ol>
		</div>
		<?php if ( ! comments_open() && get_comments_number() ) : ?>
			<p class="alert alert-info mb-3"><?php esc_html_e( 'Comments are closed.', 'lerm' ); ?></p>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/components/comment-pagination' ); ?>
	<?php endif; ?>
</div>
