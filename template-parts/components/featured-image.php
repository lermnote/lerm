<?php

/**
 * Displays the featured image
 *
 * @package Lerm https://lerm.net
 */
use Lerm\Inc\Misc\Image;
$image = new Image(
	array(
		'post_id' => get_the_ID(),
		'size'    => 'home-thumb',
		'lazy'    => 'lazy',
		'order'   => array( 'featured', 'block', 'scan', 'default' ),
		'default' => lerm_options( 'thumbnail_gallery' ),
	)
);
if ( empty( $image ) ) {
	return;
}
?>
<figure class="figure w-100 m-0" style="max-height:115px; overflow:hidden">
	<?php
	echo $image->generate_image_html();// phpcs:ignore WordPress.Security.EscapeOutput -- Reason: has been escaped.
	?>
</figure><!-- .featured -->
