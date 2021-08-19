<?php
/**
 * Display post header
 *
 * @package Lerm
 */
?>

<header class="entry-header d-flex flex-column text-center mb-2">
	<?php
	the_title( '<h1 class="entry-title">', '</h1>' );
	if ( is_singular( 'post' ) ) {
		lerm_post_meta( 'single_top' );
	}
	?>
</header>
