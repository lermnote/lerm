<?php
if ( ! function_exists( 'lerm_header_style' ) ) :
/**
	*Styles the header text displayed on the site
	*/
function lerm_header_style() {
	if (display_header_text() ) {
		return;
	}?>
	<style type="text/css">
		.site-title,
		.site-description {
			clip: rect(1px, 1px, 1px, 1px);
			position: absolute;
		}
	</style>
	<?php
}
endif;
if ( ! function_exists( 'lerm_the_custom_logo' ) ) :
/**
 * Displays the optional custom logo.
 */
function lerm_the_custom_logo() {
	if ( function_exists( 'the_custom_logo' ) ) {

		the_custom_logo();
	}
}
endif;
