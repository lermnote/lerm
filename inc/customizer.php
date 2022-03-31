<?php if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Handles the theme's theme customizer functionality.
 *
 * @author Lerm http://www.hanost.com
 * @since  Lerm 1.0
 */

/**
 * Displays the optional custom logo.
 *
 * Does nothing if the custom logo is not available.
 *
 * @since Lerm 3.1
 */
function lerm_custom_logo() {
	$large_logo     = lerm_options( 'large_logo', 'id' );
	$mobile_logo    = lerm_options( 'mobile_logo', 'id' );
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	if ( wp_is_mobile() && ! empty( $mobile_logo ) ) {
		$custom_logo_id = $mobile_logo;
	}
	if ( ! empty( $large_logo ) ) {
		$custom_logo_id = $large_logo;
	}
	if ( $custom_logo_id ) {
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link" rel="home" itemprop="url">%2$s</a>',
			esc_url( home_url( '/' ) ),
			wp_get_attachment_image(
				$custom_logo_id,
				'full',
				false,
				array(
					'class' => 'custom-logo me-1',
				)
			)
		);
		return $html;
	}
}
add_filter( 'get_custom_logo', 'lerm_custom_logo' );

/**
 * custom color
 *
 * @since  2.0
 */
function lerm_custom_site_width() {
	$custom_width = '
		@media (min-width:992px) {
			#primary{
				width:%1$s%%
			}
			#secondary{
				width:%2$s%%
			}
		}
		';
	wp_add_inline_style( 'lerm_style', sprintf( $custom_width, lerm_options( 'content_width' ), lerm_options( 'sidebar_width' ) ) );
}
// add_action( 'wp_enqueue_scripts', 'lerm_custom_site_width', 21 );
/**
 * custom css
 *
 * @since Lerm 2.0
 */
function lerm_custom_css() {
	wp_add_inline_style(
		'lerm_style',
		sprintf( '%s', lerm_options( 'custom_css' ) )
	);
}
add_action( 'wp_enqueue_scripts', 'lerm_custom_css', 21 );


function lerm_post_image( $args = array() ) {
	$default        = array( 'default' => lerm_options( 'thumbnail_gallery' ) );
	$args           = wp_parse_args( $args, $default );
	$post_thumbnail = new \Lerm\Inc\Image( $args );
	return $post_thumbnail->get_image();
}
