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
	<div class="content-area">
		<?php if ( is_singular() ) : ?>
			<?php get_template_part( 'template-parts/header/entry-header' ); ?>

			<div class="entry-content clearfix pt-2">
				<?php
					the_content(
						sprintf(
							/* translators: %s = post title */
							__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
							get_the_title()
						)
					);
				?>
			</div>
		<?php else : ?>
			<div class="row no-gutters">
				<div class="col-md-3">
					<?php get_template_part( 'template-parts/content/featured-image' ); ?>
				</div>
				<div class="col-md-9">
					<div class="card-body py-md-0">
					<?php the_title( '<h2 class="entry-title card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a><label class="sticky-label badge badge-danger">' . __( 'Sticky', 'lerm' ) . '</label></h2>' ); ?>
						<!-- <h2 class="card-title">Card title</h2> -->
						<p class="card-text">
						<?php the_excerpt(); ?>
							<!-- This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer. -->
						</p>
						<p class="card-text">
								<?php
								//if ( $post_title ) :
									lerm_post_meta( 'summary_bottom' );
								//endif;
								?>
						</p>
					</div>
				</div>
			</div>

		<?php endif; ?>
	</div>
</article>
