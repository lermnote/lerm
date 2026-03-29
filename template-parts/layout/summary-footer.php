<?php

/**
 * The template part for displaying the summary footer.
 *
 * @package Lerm
 */
use Lerm\View\PostMeta;

$template_options = lerm_get_template_options();
?>
<footer class="summary-footer">
	<?php
	if ( is_home() ) {
		PostMeta::post_meta( array_keys( (array) ( $template_options['summary_meta']['enabled'] ?? array() ) ), 'justify-content-center justify-content-sm-start mb-0' );
	}
	?>
</footer>
