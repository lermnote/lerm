<?php // phpcs:disable WordPress.Files.FileName
/**
 * Improved SVG icons related functions for Lerm theme
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

// -----------------------------------------------------------------------------
// Lightweight wrappers for SVG_Icons class with sanity checks
// -----------------------------------------------------------------------------

if ( ! function_exists( 'lerm_get_icon_svg' ) ) {
	/**
	 * Get UI icon SVG code.
	 *
	 * @param string $icon Icon slug.
	 * @param int    $size Size in pixels.
	 * @return string SVG markup or empty string on failure.
	 */
	function lerm_get_icon_svg( $icon, $size = 24 ) {
		if ( empty( $icon ) ) {
			return '';
		}

		if ( ! class_exists( 'Lerm\\SVG_Icons' ) || ! method_exists( 'Lerm\\SVG_Icons', 'get_svg' ) ) {
			return '';
		}

		$size = (int) $size;

		return Lerm\SVG_Icons::get_svg( 'ui', $icon, $size );
	}
}

if ( ! function_exists( 'lerm_get_social_icon_svg' ) ) {
	/**
	 * Get social icon SVG code.
	 *
	 * @param string $icon Icon slug (e.g. "twitter").
	 * @param int    $size Size in pixels.
	 * @return string SVG markup or empty string on failure.
	 */
	function lerm_get_social_icon_svg( $icon, $size = 24 ) {
		if ( empty( $icon ) ) {
			return '';
		}

		if ( ! class_exists( 'Lerm\\SVG_Icons' ) || ! method_exists( 'Lerm\\SVG_Icons', 'get_svg' ) ) {
			return '';
		}

		$size = (int) $size;

		return Lerm\SVG_Icons::get_svg( 'social', $icon, $size );
	}
}

if ( ! function_exists( 'lerm_get_social_link_svg' ) ) {
	/**
	 * Detect social network from URL and return corresponding SVG.
	 *
	 * @param string $uri  URL to inspect.
	 * @param int    $size Icon size.
	 * @return string SVG markup or empty string.
	 */
	function lerm_get_social_link_svg( $uri, $size = 24 ) {
		if ( empty( $uri ) ) {
			return '';
		}

		if ( ! class_exists( 'Lerm\\SVG_Icons' ) || ! method_exists( 'Lerm\\SVG_Icons', 'get_social_link_svg' ) ) {
			return '';
		}

		return Lerm\SVG_Icons::get_social_link_svg( $uri, (int) $size );
	}
}

// -----------------------------------------------------------------------------
// Nav menu filter: inject social SVG into 'social' menu location
// -----------------------------------------------------------------------------

if ( ! function_exists( 'lerm_nav_menu_social_icons' ) ) {
	/**
	 * Replace or append the social icon SVG inside menu item output for the
	 * 'social' theme location.
	 *
	 * @param string  $item_output The menu item output.
	 * @param WP_Post $item        Menu item object.
	 * @param int     $depth       Depth of the menu.
	 * @param object  $args        wp_nav_menu() arguments.
	 * @return string Filtered menu item output.
	 */
	function lerm_nav_menu_social_icons( $item_output, $item, $depth, $args ) {
		// Only alter the menu when location is the theme's social menu.
		if ( empty( $args ) || ! isset( $args->theme_location ) || 'social' !== $args->theme_location ) {
			return $item_output;
		}

		// Ensure we have a URL to detect social network from.
		$url = ( isset( $item->url ) ) ? trim( $item->url ) : '';
		if ( '' === $url ) {
			return $item_output;
		}

		// Try to get a social SVG; fallback to generic link icon.
		$svg = lerm_get_social_link_svg( $url, 26 );
		if ( empty( $svg ) ) {
			$svg = lerm_get_icon_svg( 'link' );
		}

		// If the theme provided link_after, replace it; otherwise insert before </a>.
		$link_after = '';
		if ( is_object( $args ) && property_exists( $args, 'link_after' ) ) {
			$link_after = $args->link_after;
		} elseif ( is_array( $args ) && isset( $args['link_after'] ) ) {
			$link_after = $args['link_after'];
		}

		if ( '' !== $link_after && false !== strpos( $item_output, $link_after ) ) {
			$item_output = str_replace( $link_after, '</span>' . $svg, $item_output );
		} else {
			// Safe fallback: insert the SVG right before the closing anchor tag.
			$pos = strripos( $item_output, '</a>' );
			if ( false !== $pos ) {
				$item_output = substr_replace( $item_output, $svg . '</a>', $pos, 4 );
			} else {
				// As a last resort append.
				$item_output .= $svg;
			}
		}

		return $item_output;
	}
}
add_filter( 'walker_nav_menu_start_el', 'lerm_nav_menu_social_icons', 10, 4 );

// -----------------------------------------------------------------------------
// Simple, accessible social icons block (improved)
// -----------------------------------------------------------------------------

if ( ! function_exists( 'lerm_social_icons' ) ) {
	/**
	 * Output a group of social icons.
	 *
	 * Accepts either a list of icon slugs (strings) or an associative array of
	 * slug => url pairs. Example:
	 *   lerm_social_icons( array( 'twitter', 'facebook' ) );
	 *   lerm_social_icons( array( 'twitter' => 'https://twitter.com/..' ) );
	 *
	 * @param array $icons Icon slugs or slug=>url pairs. Defaults to common Chinese platforms.
	 */
	function lerm_social_icons( $icons = array( 'weibo', 'wechat', 'qq' ) ) {
		if ( empty( $icons ) || ! is_array( $icons ) ) {
			return;
		}

		// Normalize to slug => url where url may be empty string.
		$normalized = array();
		foreach ( $icons as $k => $v ) {
			if ( is_int( $k ) ) {
				// value is slug
				$normalized[ $v ] = '';
			} else {
				// key is slug, value is url
				$normalized[ $k ] = $v;
			}
		}

		$items = array();
		foreach ( $normalized as $slug => $url ) {
			$slug_attr = esc_attr( $slug );
			/* translators: %s: social network name */
			$aria_label = sprintf( __( 'Share on %s', 'lerm' ), ucfirst( $slug ) );

			$href  = '#';
			$extra = '';
			if ( ! empty( $url ) ) {
				$href = esc_url( $url );

				// open external links in new tab with safe rel attributes
				$extra = ' target="_blank" rel="noopener noreferrer"';
			}

			// Try to get an SVG first; fall back to a font icon <i> if necessary.
			$svg       = lerm_get_social_icon_svg( $slug, 20 );
			$icon_html = '';
			if ( ! empty( $svg ) ) {
				$icon_html = $svg;
			} else {
				// Keep legacy font-awesome fallback but mark it as presentational.
				$icon_html = '<i class="fa fa-' . $slug_attr . '" aria-hidden="true"></i>';
			}

			$items[] = sprintf(
				'<a class="social-share-icon icon-%1$s btn-light btn-sm" href="%2$s" aria-label="%3$s"%4$s>%5$s</a>',
				$slug_attr,
				esc_attr( $href ),
				esc_attr( $aria_label ),
				$extra,
				$icon_html
			);
		}

		// Build final output; allow themes/plugins to modify.
		$output = '<div class="social-share d-flex justify-content-center gap-1" data-initialized="true">' . implode( "\n", $items ) . '</div>';
		$output = apply_filters( 'lerm_social_icons_output', $output, $normalized );

		// Echo safely: $output contains only tags we generated above and escaped pieces.
		echo wp_kses_post( $output );
	}
}

