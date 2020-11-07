<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
$like_class = isset( $_COOKIE[ 'post_like_' . $post->ID ] ) ? 'done' : '';
$like_count = get_post_meta( $post->ID, 'lerm_post_like', true ) ? get_post_meta( $post->ID, 'lerm_post_like', true ) : 0;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>  itemscope itemtype="http://schema.org/BlogPosting">
	<header class="entry-header d-flex flex-column justify-content-between mb-md-2 text-center pb-3">
		<?php
		if ( $post->post_title ) :
			the_title( '<h1 class="entry-title" itemprop="headline">', '</h1>' );
			lerm_post_meta( 'single_top' );
		endif;
		?>
	</header>

	<div class="entry-content clearfix pt-2">
		<?php
		the_content(
			sprintf(
				// translators: %s is the title
				__( 'Continue reading <span class="screen-reader-text">"%s"</span>', 'lerm' ),
				get_the_title()
			)
		);
		?>
	</div>
	<div class="py-3 clearfix">
		<?php lerm_link_pagination(); ?>
	</div>
	<footer class="mt-5">
		<div class="line-text d-flex justify-content-center align-items-center">如果您觉得有用就请点赞和分享</div>
		<div class="btn-toolbar  d-flex justify-content-center mt-4 mb-3">
			<div class="text-center">
				<button id="like-button" data-id="<?php the_ID(); ?>" class="like-button btn <?php echo esc_attr( $like_class ); ?>">
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
		<?php get_template_part( 'template/post/share' ); ?>
	</footer>
</article>
