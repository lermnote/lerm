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
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry p-3 mb-2' ); ?> >
	<header class="entry-header d-flex flex-column justify-content-between mb-md-2 text-center pb-2">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<small class="entry-meta text-muted">;
			<?php lerm_post_meta( 'single_top' ); ?>
		</small>
	</header>

	<div class="entry-content pt-2">
		<?php
		the_content(
			sprintf(
				// translators: %s is the title
				__( 'Continue reading <span class="screen-reader-text">"%s"</span>', 'lerm' ),
				get_the_title()
			)
		);
		?>
		<?php
			wp_link_pages(
				array(
					'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'lerm' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'lerm' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				)
			);
			?>
	</div>
	<footer>
		<div class="text-center position-relative mt-5">
			<div class="line" ></div>
			<span class="line-text">如果您觉得有用就请点赞和分享</span>
		</div>
		<div class="btn-toolbar  d-flex justify-content-center mt-4 mb-3">
			<div class="text-center">
				<button  id="like-button" data-id="<?php the_ID(); ?>" class="like-button btn <?php echo esc_attr( $like_class ); ?>">
					<span><i class="fa fa-heart"></i></span>
					<span class="count">
						<?php echo esc_attr( $like_count ); ?>
					</span>
				</button>
				<button class="btn-custom btn entry-comment-btn">
					<a href="<?php comments_link(); ?>">
						<i class="fa fa-comment"></i>
						<?php printf( _nx( '%s comment', '%s comments', get_comments_number(), 'comments title', 'lerm' ), esc_attr( number_format_i18n( get_comments_number() ) ) ); ?>
					</a>
				</button>
			</div><!-- like -->
		</div><!-- toolbar -->
		<div class="d-flex justify-content-center justify-content-md-end">
			<?php get_template_part( 'template/post/share' ); ?>
		</div>
	</footer>
</article>
