<?php
/**
 * Display post header
 * 
 * @package Lerm
 */
?>

<header class="entry-header d-flex flex-column justify-content-between mb-md-2">
	<?php
	if ( is_singular() ) {
		the_title( '<h1 class="entry-title">', '</h1>' );
	} else {
		the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a><label class="sticky-label badge badge-danger">' . __( 'Sticky', 'lerm' ) . '</label></h2>' );
	}
	?>
	<small class="entry-meta text-muted">
		<?php lerm_post_meta( 'summary_bottom' ); ?>
	</small>
</header>
