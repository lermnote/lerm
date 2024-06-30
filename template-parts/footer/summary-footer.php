
<footer class="summary-footer">
	<?php
	if ( is_home() ) {
		\Lerm\Inc\Core\Tags::post_meta( array_keys( (array) lerm_options( 'summary_meta', 'enabled' ) ), 'justify-content-center justify-content-sm-start mb-0' );
	}
	?>
</footer>

