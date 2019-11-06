<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Handles the theme's theme customizer functionality.
 *
 * @author Lerm http://www.hanost.com
 * @since  Lerm 1.0
 */
if ( ! function_exists( 'lerm_the_custom_logo' ) ) :
	/**
	 * Displays the optional custom logo.
	 *
	 * Does nothing if the custom logo is not available.
	 *
	 * @since Lerm 1.0
	 */
	function lerm_the_custom_logo() {
		if ( function_exists( 'the_custom_logo' ) ) {
			if ( wp_is_mobile() ) {
				$image_id   = lerm_options( 'mobile_logo', '' );
				$attachment = wp_get_attachment_image_src( $image_id, 'full' );
				echo $attachment[0];
			}
			the_custom_logo();
		}
	}
endif;

/**
 * custom color
 *
 * @since  2.0
 */
function lerm_custom_site_width() {
	$custom_width = '
		@media (min-width:1200px) {
			.container{
				max-width:%1$spx;
			}
		}
		';
	wp_add_inline_style( 'lerm_style', sprintf( $custom_width, lerm_options( 'site_width', 'width' ) ) );
}
add_action( 'wp_enqueue_scripts', 'lerm_custom_site_width', 21 );
/**
 * custom css
 *
 * @since Lerm 2.0
 */
function lerm_custom_css() {
	wp_add_inline_style(
		'lerm_style',
		sprintf( '%s', lerm_options( 'custom_css', '' ) )
	);
}
add_action( 'wp_enqueue_scripts', 'lerm_custom_css', 21 );
