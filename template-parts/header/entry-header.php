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
		\Lerm\Inc\Core\Tags::post_meta( array_keys( (array) lerm_options( 'single_top', 'enabled' ) ), 'justify-content-center  mb-0 ' );
	}
	?>
</header>
