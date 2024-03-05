
<footer class="summary-footer">
	<?php
	if ( ! is_singular() ) {
		\Lerm\Inc\Tags::post_meta( array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) ), 'justify-content-center justify-content-sm-start mb-0' );
	}
	?>
</footer>

