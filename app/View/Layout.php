<?php  // phpcs:disable WordPress.Files.FileName

// phpcs:disable WordPress.Files.FileName
/**
 * Layouts API - An API for themes to build layout options.
 *
 * Theme Layouts was created to allow theme developers to easily style themes with dynamic layout
 * structures. This file contains the API function calls at theme developers' disposal.
 *
 * @package Lerm
 */

declare (strict_types=1);

/**
 * Adds custom classes to the array of body classes.
 *
 * @param string[] $classes Classes for the body element.
 * @return string[] Filtered classes.
 */
function lerm_body_classes( array $classes ): array {
	$classes[] = 'body-bg';

	// Check singular (pages, posts, attachments).
	if ( is_singular() ) {
		$classes[] = 'singular';
		if ( has_post_thumbnail() ) {
			$classes[] = 'has-post-thumbnail';
		}
	}

	// Add class on front page if a static front page is set.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$lerm_front_page = get_option( 'page_on_front' );
		if ( $lerm_front_page && is_page( (int) $lerm_front_page ) ) {
			$classes[] = 'lerm-front-page';
		}
	}

	// Output layout
	$classes[]    = lerm_site_layout();
	$layout_style = lerm_options( 'layout_style' );
	if ( ! empty( $layout_style ) ) {
		$classes[] = (string) $layout_style;
	}

	// Allow others to filter/modify.
	/** @var string[] $classes */
	return array_unique( apply_filters( 'lerm_body_classes', $classes ) );
}
add_filter( 'body_class', 'lerm_body_classes' );

/**
 * Add CSS classes for posts and pages.
 *
 * Note: we add individual tokens (not one string with spaces) so filters and inspectors see each class.
 *
 * @param string[] $classes Existing classes.
 * @return string[] Modified classes.
 */
function lerm_post_class( array $classes ): array {
	$loading_animate = lerm_options( 'loading-animate' );

	if ( is_singular() ) {
		$classes = array_merge( $classes, array( 'entry', 'p-3', 'mb-2' ) );
	} else {
		$classes = array_merge( $classes, array( 'summary', 'mb-3', 'p-0', 'p-md-3' ) );
	}

	if ( $loading_animate ) {
		$classes = array_merge( $classes, array( 'loading-animate', 'animate__fadeIn' ) );
	}

	return array_unique( apply_filters( 'lerm_post_class', $classes ) );
}
add_filter( 'post_class', 'lerm_post_class' );

/**
 * Returns the active site layout (takes metabox override into account).
 *
 * @param string|null $fallback Optional default layout to fallback to.
 * @return string Layout slug.
 */
function lerm_site_layout( ?string $fallback = null ): string {
	$global = (string) lerm_options( 'global_layout' );

	// Allow a caller fallback, but prefer global option if provided.
	$base_layout = $fallback ?? $global;

	$post_layout = lerm_post_layout( $base_layout );

	$layout = (string) apply_filters( 'lerm_site_layout', $post_layout, $base_layout );

	return $layout;
}

/**
 * Determine post/page-specific layout from _lerm_metabox_options (if present).
 *
 * @param string $layout Current layout fallback.
 * @return string Filtered layout.
 */
function lerm_post_layout( string $layout = '' ): string {
	$metabox = array();

	$queried_object_id = get_queried_object_id();
	if ( $queried_object_id ) {
		$meta = get_post_meta( $queried_object_id, '_lerm_metabox_options', true );
		if ( is_array( $meta ) ) {
			$metabox = $meta;
		}
	}

	if ( isset( $metabox['page_layout'] ) && '' !== (string) $metabox['page_layout'] ) {
		return (string) $metabox['page_layout'];
	}

	return (string) $layout;
}

/**
 * Echo the class attribute for a row element.
 *
 * @param string|array $css_class Extra classes to add.
 */
function lerm_row_class( $css_class = '' ): void {
	echo 'class="' . esc_attr( implode( ' ', lerm_get_row_class( $css_class ) ) ) . '"';
}

/**
 * Get the classes for a row element.
 *
 * @param string|array $css_class Extra classes to add; accepts space-separated string or array.
 * @return string[] Array of classes.
 */
function lerm_get_row_class( $css_class = '' ): array {
	$classes = array( 'row' );

	if ( 'layout-1c-narrow' === lerm_site_layout() ) {
		$classes[] = 'justify-content-md-center';
	}

	// Normalize incoming classes.
	if ( ! empty( $css_class ) ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = preg_split( '#\s+#', (string) $css_class, -1, PREG_SPLIT_NO_EMPTY );
		}
		$classes = array_merge( $classes, $css_class );
	}

	$classes = array_map( 'sanitize_html_class', $classes );

	/** Filters the row classes. @param string[] $classes Row classes. @param string[] $css_class Additional classes passed in. */
	return array_unique( apply_filters( 'lerm_row_class', $classes, (array) $css_class ) );
}

/**
 * Echo the class attribute for a column element.
 *
 * @param string|array $css_class Additional classes.
 */
function lerm_column_class( $css_class = '' ): void {
	echo 'class="' . esc_attr( implode( ' ', lerm_get_column_class( $css_class ) ) ) . '"';
}

/**
 * Get the column classes for the Bootstrap grid based on the site layout.
 *
 * @param string|array $css_class Additional classes.
 * @return string[] Column classes.
 */
function lerm_get_column_class( $css_class = '' ): array {
	$classes = array();

	$layout = lerm_site_layout();

	if ( 'layout-2c-l' === $layout ) {
		$classes[] = 'col-lg-8';
		$classes[] = 'ps-lg-0';
		$classes[] = 'order-lg-last';
	} elseif ( 'layout-2c-r' === $layout ) {
		$classes[] = 'col-lg-8';
		$classes[] = 'pe-lg-0';
	} else {
		$classes[] = 'col-12';
	}

	// Normalize incoming classes.
	if ( ! empty( $css_class ) ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = preg_split( '#\s+#', (string) $css_class, -1, PREG_SPLIT_NO_EMPTY );
		}
		$classes = array_merge( $classes, $css_class );
	}

	$classes = array_map( 'sanitize_html_class', $classes );

	/** Filters the column classes. @param string[] $classes Column classes. @param string[] $css_class Additional classes passed in. */
	return array_unique( apply_filters( 'lerm_column_class', $classes, (array) $css_class ) );
}
