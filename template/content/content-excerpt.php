<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> >
	<div class="row no-gutters">
		<div class="col-md-3">
			<?php
			lerm_thumb_nail(
				array(
					'classes' => 'post-thumbnail',
					'height'  => '110',
					'width'   => '180',
				)
			);
			?>
		</div>
		<div class="col-md-9 d-flex">
			<div class="card-body p-md-0 p-3 ml-md-3 ml-0 d-flex flex-column  justify-content-between">
				<h2 class=" card-title entry-title">
					<?php the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' ); ?>
					<?php if ( is_sticky() ) { ?>
					<label class="sticky-label badge badge-danger"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
					<?php } ?>
				</h2>
				<?php the_excerpt(); ?>
				<?php lerm_post_meta( 'summary_bottom' ); ?>
			</div>
		</div>
	</div>
</article>
