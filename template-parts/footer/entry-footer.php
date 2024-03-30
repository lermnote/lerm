<?php
$like_class = isset( $_COOKIE[ 'post_like_' . $post->ID ] ) ? '' : '';
$like_count = get_post_meta( $post->ID, '_post_like_count', true ) ? get_post_meta( $post->ID, '_post_like_count', true ) : 0;
?>
<footer class="mt-5">
	<div class="line-text d-flex justify-content-center align-items-center">如果您觉得有用就请点赞和分享</div>
	<div class="btn-toolbar d-flex justify-content-center mt-4 mb-3">
		<div class="text-center">
		<?php //\Lerm\Inc\PostLike::get_simple_likes_button( get_the_ID() ); ?>
			<button id="like-button" data-id="<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" data-like-count="<?php echo esc_attr( $like_count ); ?>" class="like-button btn <?php echo esc_attr( $like_class ); ?>">
				<span><i class="fa fa-heart"></i></span>
				<span class="count">
					<?php echo esc_attr( $like_count ); ?>
				</span>
			</button>
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

