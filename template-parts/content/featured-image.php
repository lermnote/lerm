<?php
/**
 * Displays the featured image
 *
 * @package Lerm
 */
?>
<figure class="figure w-100 m-0" style="max-height:140px; overflow:hidden">
		<?php
		if ( ! is_singular() ) {
			get_the_image(
				array(
					'size'    => 'home-thumb',
					// 'class'   => 'w-100',
					'echo'    => true
				)
			);
		}
		?>
</figure><!-- .featured -->
