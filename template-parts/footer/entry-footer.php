<?php
\Lerm\Inc\Ajax\PostLike::already_liked( $post->ID ) ? '' : '';
$like_class = 'like-post-' . $post->ID;
$like_count = get_post_meta( $post->ID, '_post_like_count', true ) ? get_post_meta( $post->ID, '_post_like_count', true ) : 0;
?>
<footer class="mt-5">
	<div class="line-text d-flex justify-content-center align-items-center">如果您觉得有用就请点赞和分享</div>
	<div class="btn-toolbar d-flex justify-content-center mt-4 mb-3">
		<div class="text-center">
		<?php echo \Lerm\Inc\Ajax\PostLike::get_likes_button( $post->ID ); ?>
			<a href="<?php comments_link(); ?>"  class="btn btn-custom entry-comment-btn">
				<i class="fa fa-comment"></i>
					<?php
					/* translators: %s = comment number */
					printf( esc_attr( _nx( '%s comment', '%s comments', get_comments_number(), 'comments title', 'lerm' ) ), esc_attr( number_format_i18n( get_comments_number() ) ) );
					?>
			</a>
		</div><!-- like -->
	</div><!-- toolbar -->
	<?php lerm_social_icons( lerm_options( 'social_share' ) ); ?>
</footer>

