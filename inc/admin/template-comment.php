<?php
/**
 * style the comments list
 *
 * @package https://www.hanost.com
 *
 * @since lerm 2.0
 */
class Lerm_Comment {
	public function lerm_comments( $comment, $args, $depth ) { ?>
		<li <?php comment_class( 'card p-3 mt-2' ); ?> id="comment-<?php comment_ID(); ?>">

			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<header class="comment-meta mb-1">
					<span class="comment-author vcard">
						<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
						<?php printf( '<cite class="fn">%s</cite> <span class="says"></span>', get_comment_author_link() ); ?>
					</span>
					<?php
					printf(
						'<time class="comment-published" datetime="%s" title="%s">%s' . esc_html__( ' ago', 'lerm' ) . '</time>',
						esc_attr( get_comment_date( 'c' ) ),
						esc_attr( get_comment_time( _x( 'l, F j, Y, g:i a', 'comment time format', 'lerm' ) ) ),
						esc_attr( human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) )
					);
					?>
					<?php edit_comment_link( __( 'Edit', 'lerm' ), '  ', '' ); ?>
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
				</header>

				<?php if ( '0' === $comment->comment_approved ) : ?>
					<em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lerm' ); ?></em>
					<br />
				<?php endif; ?>
				<?php comment_text(); ?>
			</article>
		<?php
	}
}



function lerm_comments( $comment, $args, $depth ) {
	?>
	<li <?php comment_class( 'card p-3 mt-2' ); ?> id="comment-<?php comment_ID(); ?>">

		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
			<header class="comment-meta mb-1">
				<span class="comment-author vcard">
					<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
					<?php printf( '<cite class="fn">%s</cite> <span class="says"></span>', get_comment_author_link() ); ?>
				</span>
				<?php
				printf(
					'<time class="comment-published" datetime="%s" title="%s">%s' . esc_html__( ' ago', 'lerm' ) . '</time>',
					esc_attr( get_comment_date( 'c' ) ),
					esc_attr( get_comment_time( _x( 'l, F j, Y, g:i a', 'comment time format', 'lerm' ) ) ),
					esc_attr( human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) )
				);
				?>
				<?php edit_comment_link( __( 'Edit', 'lerm' ), '  ', '' ); ?>
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
			</header>

			<?php if ( '0' === $comment->comment_approved ) : ?>
				<em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lerm' ); ?></em>
				<br />
			<?php endif; ?>
			<?php comment_text(); ?>
		</article>
	<?php
}

add_action( 'wp_ajax_ajax_comment', 'lerm_submit_ajax_comment' ); // wp_ajax_{action} for registered user
add_action( 'wp_ajax_nopriv_ajax_comment', 'lerm_submit_ajax_comment' ); // wp_ajax_nopriv_{action} for not registered users

function lerm_submit_ajax_comment( $comment ) {

	// Check ajax nonce first
	check_ajax_referer( 'ajax_nonce', 'security' );

	// Sanitize items;
	$email   = sanitize_email( $_POST['email'] );
	$author  = sanitize_user( $_POST['author'] );
	$url     = esc_url_raw( $_POST['url'] );
	$message = sanitize_textarea_field( $_POST['comment'] );
	$cookies = rest_sanitize_boolean( $_POST['wp-comment-cookies-consent'] );
	$data    = [ $email, $author, $url, $message, $cookies ];

	// Handles the submission of a comment
	$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );
	if ( is_wp_error( $comment ) ) {
		$error = intval( $comment->get_error_data() );
		if ( ! empty( $error ) ) {
			wp_die(
				$comment->get_error_message(),
				array(
					'response'  => $error,
					'back_link' => true,
				)
			);
		}
	}
	/**
	 * Validate comment
	 *
	 * @since 3.2
	 */

	$pattern = '/[一-龥]/u';
	$mail    = '/\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/';

	// Chinese
	if ( ! preg_match( $pattern, $_POST['comment'] ) ) {
		wp_die( '<strong>Error</strong>: Your comment must contain Chinese.', 'lerm' );
	}
	// Email;
	if ( preg_match( $mail, $_POST['comment'] ) ) {
		wp_die( '<strong>Error</strong>: Email is not allowed in comment content.', 'lerm' );
	}
	/**
	 * Set Cookies checkbox
	 *
	 * @since 3.2
	 */
	$user = wp_get_current_user();
	if ( 'yes' === @$_POST['wp-comment-cookies-consent'] ) {
		do_action( 'set_comment_cookies', $comment, $user );}

	/*
	 * Set the globals, so our comment functions below will work correctly
	 */
	$GLOBALS['comment'] = $comment;

	/*
	 * Here is the comment template, you can configure it for your website
	 * or you can try to find a ready function in your theme files
	 */
	lerm_comments( $comment, array( 'avatar_size' => '48' ), '1' );
	wp_die();
}

// Disable HTML in comment
function lerm_comment_post( $incoming_comment ) {
	// convert everything in a comment to display literally
	$incoming_comment['comment_content'] = htmlspecialchars( $incoming_comment['comment_content'] );
	// the one exception is single quotes, which cannot be #039; because WordPress marks it as spam
	return( $incoming_comment );
}
add_filter( 'preprocess_comment', 'lerm_comment_post', '', 1 );
