<?php
/**
 * The template part for displaying content
 *
 * @package Lerm
 * @since Lerm 2.0
 */
global $lerm;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> >
	<?php
	lerm_thumb_nail(
		array(
			'classes' => 'post-thumbnail',
			'height'  => '110',
			'width'   => '180',
		)
	);
	?>
		<div class="content-area d-flex flex-column  justify-content-between">
				<header class="summary-header">
					<?php
							the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a><label class="badge badge-danger sticky-label">' . __( 'Sticky', 'lerm' ) . '</label></h2>' );
					?>
				</header>
				<div class="summary-content d-none d-md-block">
						<?php the_excerpt(); ?>
				</div>
				<footer class="text-muted d-flex justify-content-between">
						<?php
							echo '<small class="entry-meta">';
							lerm_entry_meta( 'summary' );
							echo '</small>';
						?>
						<?php //excerpt_more(); ?>
				</footer>
		</div>
</article>
