<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $post;
use Lerm\Inc\Misc\Image;
use Lerm\Inc\Core\Tags;
use function Lerm\Inc\Functions\Helpers\link_pagination;
$image = new Image(
	array(
		'post_id' => get_the_ID(),
		'size'    => 'thumbnail',
		'lazy'    => 'lazy',
		'order'   => array( 'featured', 'block', 'scan', 'default' ),
		'default' => lerm_options( 'thumbnail_gallery' ),
	)
);
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>

	<?php if ( is_singular() ) : ?>
		<div class="content-area">
			<?php get_template_part( 'template-parts/header/entry-header' ); ?>

			<div class="entry-content clearfix mb-3">
				<?php
					the_content(
						sprintf(
							/* translators: %s = post title */
							__( 'Continue reading<span class="screen-reader-text">"%s"</span>', 'lerm' ),
							get_the_title()
						)
					);
					link_pagination();
				?>
			</div>
			<?php
			if ( is_singular( 'post' ) ) {
				Tags::post_meta( array_keys( (array) lerm_options( 'single_bottom', 'enabled' ) ), 'justify-content-between mb-1' );
			}
			?>
			<?php the_tags( '<ul class="list-unstyled m-0 small text-muted"><li class="d-inline ms-2">#', '</li><li class="d-inline ms-2">#', '</li></ul>' ); ?>
			<?php get_template_part( 'template-parts/footer/entry-footer' ); ?>
		</div>
	<?php else : ?>
		<div class="row no-gutters align-items-md-center">
			<?php if ( ! empty( $image->attachment_id ) ) : ?>
				<div class="col-md-3 ">

					<?php
					get_template_part( 'template-parts/components/featured-image' );
					?>
				</div>
			<?php endif; ?>
			<div class="<?php echo ! empty( $image->attachment_id ) ? 'col-md-9' : 'col-md-12'; ?>">
				<div class="card-body p-md-0">
					<h2 class="entry-title card-title">
						<?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?>
						<?php if ( is_sticky() ) : ?>
							<label class="sticky-label badge bg-danger m-0"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
						<?php endif; ?>
					</h2>

					<?php the_excerpt(); ?>
					<?php get_template_part( 'template-parts/footer/summary-footer' ); ?>
				</div>
			</div>
		</div>

	<?php endif; ?>

</article>
