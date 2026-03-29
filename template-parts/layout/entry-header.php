<?php
/**
 * Display post header
 *
 * @package Lerm
 */
use Lerm\View\PostMeta;

if ( empty( get_the_title() ) ) {
	return;
}

$template_options = lerm_get_template_options();
?>

<header class="entry-header d-flex flex-column text-center mb-2">
	<?php
	the_title( '<h1 class="entry-title">', '</h1>' );
	if ( is_singular( 'post' ) ) {
		PostMeta::post_meta( array_keys( (array) ( $template_options['single_top']['enabled'] ?? array() ) ), 'justify-content-center  mb-0 ' );
	}
	?>
</header>
