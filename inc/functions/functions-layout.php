<?php
/**
 * Layouts API - An API for themes to build layout options.
 *
 * Theme Layouts was created to allow theme developers to easily style themes with dynamic layout
 * structures. This file merely contains the API function calls at theme developers' disposal.
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array $classes[]
 */
function lerm_body_classes( $classes ) {
	$classes[] = 'body-bg';

	// Check singular
	if ( is_single() || is_page() ) {
		$classes[] = 'singular';
		if ( has_post_thumbnail() ) {
			$classes[] = 'has-post-thumbnail';
		}
	}

	// Add class on front page.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$lerm_front_page = get_option( 'page_on_front' );
		if ( $lerm_front_page && is_page( $lerm_front_page ) ) {
			$classes[] = 'lerm-front-page';
		}
	}

	// Output layout
	$classes[]    = lerm_site_layout();
	$layout_style = lerm_options( 'layout_style' );
	if ( $layout_style ) {
		$classes[] = $layout_style;
	}

	return $classes;
}
add_filter( 'body_class', 'lerm_body_classes' );

// add CSS class in WordPress post list and single page
function lerm_post_class( $classes ) {
	$loading_animate = lerm_options( 'loading-animate' );

	if ( is_page() || is_single() ) {
		$classes[] = implode( ' ', array( 'entry', 'p-3', 'mb-2' ) );
	} else {
		$classes[] = implode( ' ', array( 'summary', 'mb-3', 'p-0', 'p-md-3' ) );
	}

	if ( $loading_animate ) {
		$classes[] = implode( ' ', array( 'loading-animate', 'fadeIn' ) );
	}

	return $classes;
}
add_filter( 'post_class', 'lerm_post_class' );





function lerm_site_layout( $layout = '' ) {

	$post_layout = lerm_post_layout( lerm_options( 'global_layout' ) );

	$layout = apply_filters( 'lerm_site_layout', $post_layout, $layout );

	return $layout;
}

/**
 * Add a filter to modify the site layout.
 *
 * @param string $layout The current theme layout.
 * @return string The filtered layout.
 */
function lerm_post_layout( string $layout = '' ): string {
	$metabox = array(); // Initialize $metabox variable

	$queried_object_id = get_queried_object_id();

	if ( $queried_object_id ) {
		$metabox = get_post_meta( $queried_object_id, '_lerm_metabox_options', true );
	}

	// Use short-circuit evaluation and strict comparison
	return isset( $metabox['page_layout'] ) ? $metabox['page_layout'] : $layout;
}

/**
 * Output the classes for a row element.
 *
 * @param string $class The row class.
 */
function lerm_row_class( $css_class = '' ) {
	// Separates classes with a single space, collates classes for post DIV.
	echo 'class="' . esc_attr( implode( ' ', lerm_get_row_class( $css_class ) ) ) . '"';
}

/**
 * Get the classes for a row element.
 *
 * @param string $class The row class.
 * @return array The row classes.
 */
function lerm_get_row_class( $css_class = '' ) {
	$classes = array( 'row' );

	if ( 'layout-1c-narrow' === lerm_site_layout() ) {
		$classes[] = 'justify-content-md-center';
	}

	if ( ! empty( $css_class ) ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = preg_split( '#\s+#', $css_class );
		}
		$classes = array_merge( $classes, $css_class );
	} else {
		// Ensure that we always coerce class to being an array.
		$css_class = array();
	}

	$classes = array_map( 'esc_attr', $classes );

	/**
	 * Filters the list of CSS body class names for the current post or page.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $classes   An array of body class names.
	 * @param string[] $css_class An array of additional class names added to the body.
	 */
	$classes = apply_filters( 'lerm_row_class', $classes, $css_class );

	return array_unique( $classes );
}

/**
 * Output the column class for the Bootstrap grid
 *
 * @param string|array $class Additional column classes
 */
function lerm_column_class( $css_class = '' ) {
	// Separates classes with a single space, collates classes for post DIV.
	echo 'class="' . esc_attr( implode( ' ', lerm_get_column_class( $css_class ) ) ) . '"';
}

/**
 * Get the column classes for the Bootstrap grid based on the site layout
 *
 * @param string|array $class Additional column classes
 * @return array The filtered column classes
 */
function lerm_get_column_class( $css_class = '' ) {
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

	if ( ! empty( $css_class ) ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = preg_split( '#\s+#', $css_class );
		}
		$classes = array_merge( $classes, $css_class );
	} else {
		// Ensure that we always coerce class to being an array.
		$css_class = array();
	}

	$classes = array_map( 'esc_attr', $classes );

	/**
	 * Filters the list of CSS body class names for the current post or page.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $classes   An array of body class names.
	 * @param string[] $css_class An array of additional class names added to the body.
	 */
	$classes = apply_filters( 'lerm_column_class', $classes, $css_class );

	return array_unique( $classes );
}

function layout_style() {
	$layout_style = lerm_options( 'layout_style' );
	if ( $layout_style ) {
		$classes[] = $layout_style;
	}
}
