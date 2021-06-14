<?php
/**
 * Displays the featured image
 *
 * @package Lerm
 */
?>
<figure class="figure w-100 m-0" style="max-height:115px; overflow:hidden">
	<?php

		lerm_post_image(
			array(
				'size'  => 'home-thumb',
				'class' => 'w-100 h-100 rounded',
				'echo'  => true,
			)
		);
		?>
</figure><!-- .featured -->
