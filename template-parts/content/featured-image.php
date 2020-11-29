<?php
/**
 * Displays the featured image
 *
 * @package Lerm
 */
?>
<figure class="figure">
		<?php
		if ( ! is_singluar() ) {
			lerm_post_image(
				array(
					'size'    => 'home-thumb',
					'default' => lerm_options( 'thumbnail_gallery' ),
				)
			);
		}
		?>
</figure><!-- .featured -->
