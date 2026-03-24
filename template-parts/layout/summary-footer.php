<?php

/**
 * The template part for displaying the summary footer.
 *
 * @package Lerm
 */
use Lerm\View\PostMeta;
?>
<footer class="summary-footer">
	<?php
	if ( is_home() ) {
		PostMeta::post_meta( array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) ), 'justify-content-center justify-content-sm-start mb-0' );
	}
	?>
</footer>

