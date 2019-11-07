<?php
/**
 * The template for displaying comments
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 *
 * @since   2.0
 * @package https://www.hanost.com
 */

if ( post_password_required() ) {
	return;
}?>
<div id="comments" class="comments">
	<?php if ( comments_open() ) : ?>
		<?php
		$comment_author       = isset( $_COOKIE [ 'comment_author_' . COOKIEHASH ] ) ? $_COOKIE [ 'comment_author_' . COOKIEHASH ] : '';
		$comment_author_email = isset( $_COOKIE [ 'comment_author_email_' . COOKIEHASH ] ) ? $_COOKIE [ 'comment_author_email_' . COOKIEHASH ] : '';
		$comment_author_url   = isset( $_COOKIE [ 'comment_author_url_' . COOKIEHASH ] ) ? $_COOKIE [ 'comment_author_url_' . COOKIEHASH ] : '';
		$args                 = array(
			'comment_notes_before' => '<label class="logged-in-as pl-2">' . sprintf(
				'<cite class="fn">%1$s<strong class="pl-2">%2$s</strong></cite><span class="ml-2">%3$s</span>',
				get_avatar( $comment_author_email, 32 ),
				$comment_author ? $comment_author : __( 'Visitor', 'lerm' ),
				$comment_author_email ? __( 'Welcome Back', 'lerm' ) : __( 'Welcome ', 'lerm' )
			) . '</label>',
			'comment_field'        => '<fieldset class="form-group mb-2">
			<textarea id="comment" class="rq form-control"  required="required" placeholder="留下评论，天下太平" name="comment"></textarea></fieldset>',
			'fields'               => array(
				'author' => '<div class="form-group input-form"><label class="sr-only" for="author">Username</label><div class="input-group mb-2"><div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-user"></i></div></div><input type="text" name="author" class="rq form-control" id="author" value="' . esc_attr( $comment_author ) . '" placeholder="' . __( 'Nickname', 'lerm' ) . '" required></div>',
				'email'  => '<label class="sr-only" for="email">Email</label><div class="input-group mb-2"><div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-envelope"></i></div></div><input type="email" name="email" class="rq form-control" id="email" value="' . esc_attr( $comment_author_email ) . '" placeholder="' . __( 'E-mail', 'lerm' ) . '" required></div>',
				'url'    => '<label class="sr-only" for="url">Yrl</label><div class="input-group mb-2"><div class="input-group-prepend"><div class="input-group-text"><i class="fa fa-link"></i></div></div><input type="url" name="url" class="form-control" id="url" value="' . esc_attr( $comment_author_url ) . '" placeholder="' . __( 'Website', 'lerm' ) . '"></div></div>',
			),
			'logged_in_as'         => '<p class="logged-in-as">' . sprintf(
				/* translators: 1: edit user link, 2: accessibility text, 3: user name, 4: logout URL */
				'%1$s<cite class="fn"><a href="%2$s" aria-label="%3$s" class="px-2">%4$s</a></cite><a href="%5$s">%6$s</a>',
				get_avatar( get_current_user_id(), 32 ),
				get_edit_user_link(),
				/* translators: %s: user name */
				esc_attr( sprintf( __( 'Logged in as %s. Edit your profile.', 'lerm' ), $user_identity ) ),
				$user_identity,
				wp_logout_url( apply_filters( 'the_permalink', get_permalink() ) ),
				__( 'Log out', 'lerm' )
			) . '</p>',
			'id_submit'            => 'submit',
			'class_submit'         => 'btn btn-custom',
			'title_reply'          => '<span class="wrap fa p-2 d-inline-block">' . __( 'Leave a Reply', 'lerm' ) . '</span>',
		);
		comment_form( $args );
		?>
<?php endif; ?>
<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
	<p class="no-comments card card-block"><?php __( 'Comments are closed.', 'lerm' ); ?></p>
<?php endif; ?>
<?php if ( have_comments() ) : ?>
	<ol class="comment-list p-0 m-0">
		<?php
		wp_list_comments(
			array(
				'callback'    => 'lerm_comments',
				'type'        => 'comment',
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 50,
			)
		);
		?>
	</ol><!-- .comment-list -->
	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<nav class="comment-nav mt-3">
			<div class="comment-pager d-flex justify-content-center">
				<?php
				paginate_comments_links(
					array(
						'prev_text'          => '<span class="screen-reader-text">' . __( 'Previous', 'lerm' ) . '</span>',
						'next_text'          => '<span class="screen-reader-text">' . __( 'Next', 'lerm' ) . '</span>',
						'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'The', 'lerm' ) . ' </span>',
						'after_page_number'  => '<span class="meta-nav screen-reader-text">' . __( ' Page', 'lerm' ) . ' </span>',
					)
				);
				?>
			</div>
		</nav>
	<?php endif; // Check for comment navigation. ?>
<?php endif; // have comments. ?>
</div>
