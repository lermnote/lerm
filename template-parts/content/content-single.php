<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $post;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
	<div class="row no-gutters">
		<?php if ( null !== lerm_post_image() ) : ?>
			<div class="col-md-3">
				<?php get_template_part( 'template-parts/content/featured-image' ); ?>
			</div>
		<?php endif; ?>
		<div class="<?php lerm_post_image() ? 'col-md-9' : 'col-md-12'; ?>">
			<div class="card-body py-md-0">
				<h2 class="entry-title card-title">
					<?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?>
					<?php if ( is_sticky() ) : ?>
						<label class="sticky-label badge bg-danger m-0"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
					<?php endif; ?>
				</h2>

					<?php the_excerpt(); ?>

			</div>
		</div>
	</div>

</article>
