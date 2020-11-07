<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
$post_title  = $post->post_title;
$summary_col = $post_title ? 'col-sm-9' : 'col-sm-12';
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'col' ); ?> >
		<div class="row no-gutters">
		<?php if ( $post_title ) : ?>
			<div class="col-sm-12">
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
		<?php endif; ?>
		<div class="<?php echo esc_attr( $summary_col ); ?> d-flex">
			<div class="card-body p-3 p-sm-1 p-md-0 d-flex flex-column  justify-content-between">
				<h2 class=" card-title entry-title">
					<?php
					the_title( '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a>' );
					?>
					<?php if ( is_sticky() ) { ?>
					<label class="sticky-label badge badge-danger"><?php echo esc_html__( 'Sticky', 'lerm' ); ?></label>
					<?php } ?>
				</h2>
				<div class="d-sm-none d-md-block text-muted">
					<?php
					if ( $post_title ) :
						the_excerpt();
					else :
						the_content();
					endif;
					?>
				</div>
				<?php
				if ( $post_title ) :
					lerm_post_meta( 'summary_bottom' );
				endif;
				?>
			</div>
		</div>
	</div>
</article>
