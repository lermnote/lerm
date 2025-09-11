<?php
/**
 * SVG icons related functions
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */


/**
 * Gets the SVG code for a given icon.
 */
function lerm_get_icon_svg( $icon, $size = 24 ) {
	return Lerm\Inc\SVG_Icons::get_svg( 'ui', $icon, $size );
}

/**
 * Gets the SVG code for a given social icon.
 */
function lerm_get_social_icon_svg( $icon, $size = 24 ) {
	return Lerm\Inc\SVG_Icons::get_svg( 'social', $icon, $size );
}

/**
 * Detects the social network from a URL and returns the SVG code for its icon.
 */
function lerm_get_social_link_svg( $uri, $size = 24 ) {
	return Lerm\Inc\SVG_Icons::get_social_link_svg( $uri, $size );
}

/**
 * Display SVG icons in social links menu.
 *
 * @param  string  $item_output The menu item output.
 * @param  WP_Post $item        Menu item object.
 * @param  int     $depth       Depth of the menu.
 * @param  wp_nav_menu  $args        wp_nav_menu() arguments.
 * @return string  $item_output The menu item output with social icon.
 */
function lerm_nav_menu_social_icons( $item_output, $item, $depth, $args ) {
	// Change SVG icon inside social links menu if there is supported URL.
	if ( 'social' === $args->theme_location ) {
		$svg = lerm_get_social_link_svg( $item->url, 26 );
		if ( empty( $svg ) ) {
			$svg = lerm_get_icon_svg( 'link' );
		}
		$item_output = str_replace( $args['link_after'], '</span>' . $svg, $item_output );
	}

	return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'lerm_nav_menu_social_icons', 10, 4 );

/**
 * Share icon template
 *
 * @since lerm 3.0.0
 */
function lerm_social_icons( $icons = array( 'weibo', 'wechat', 'qq' ) ) {
	if ( ! empty( $icons ) && is_array( $icons ) ) {
		?>
		<div class="social-share d-flex justify-content-center gap-1" data-initialized="true">
			<?php foreach ( $icons as &$icon ) : ?>
				<a href="#" class="social-share-icon icon-<?php echo esc_attr( $icon ); ?> btn-light btn-sm">
					<i class="fa fa-<?php echo esc_attr( $icon ); ?>"></i>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
