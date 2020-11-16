<?php
/**
 * Display post header
 *
 * @package Lerm
 */
?>

<header class="entry-header d-flex flex-column text-center mb-md-2">
	<?php
	if ( is_singular() ) {
		the_title( '<h1 class="entry-title">', '</h1>' );
		lerm_post_meta( 'single_top' );
	} else {

	}
	?>
</header>
