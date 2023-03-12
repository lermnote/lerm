<?php
/**
 * Layouts API - An API for themes to build layout options.
 *
 * Theme Layouts was created to allow theme developers to easily style themes with dynamic layout
 * structures. This file merely contains the API function calls at theme developers' disposal.
 */

add_filter( 'lerm_site_layout', 'lerm_layout_filter', 5 );


function lerm_site_layout() {
	return apply_filters( 'lerm_site_layout', lerm_global_layout() );
}

function lerm_global_layout() {
	return lerm_options( 'global_layout' );
}

/**
 * Add a filter to modify the site layout.
 *
 * @param string $layout The current theme layout.
 * @return string The filtered layout.
 */
function lerm_post_layout( $layout ) {
	$queried_object_id = get_queried_object_id();
	$metabox           = array();

	if ( $queried_object_id ) {
		$metabox = get_post_meta( $queried_object_id, '_lerm_metabox_options', true );
	}

	return isset( $metabox['page_layout'] ) && $metabox['page_layout'] ? $metabox['page_layout'] : $layout;
}

	/**
	 * Output the classes for a row element.
	 *
	 * @param string $class The row class.
	 */
function lerm_row_class( $class = '' ) {
	$row_classes = lerm_get_row_class( $class );
	echo 'class="' . esc_attr( join( ' ', $row_classes ) ) . '"';
}
	/**
	 * Get the classes for a row element.
	 *
	 * @param string $class The row class.
	 * @return array The row classes.
	 */
function lerm_get_row_class( $class = '' ) {
	$classes = array( 'row' );
	if ( 'layout-1c-narrow' === lerm_site_layout() ) {
		$classes[] = 'justify-content-md-center';
	}

	if ( ! empty( $class ) ) {
		$classes = array_merge( $classes, (array) $class );
	}

	$classes = array_map( 'esc_attr', array_unique( $classes ) );
	return apply_filters( 'lerm_row_class', $classes, $class );
}

/**
 * Output the column class for the Bootstrap grid
 *
 * @param string|array $class Additional column classes
 */
function lerm_column_class( $class = '' ) {
	$column_classes = lerm_get_row_class( $class );
	echo 'class="' . esc_attr( join( ' ', $column_classes ) ) . '"';
}
/**
 * Get the column classes for the Bootstrap grid based on the site layout
 *
 * @param string|array $class Additional column classes
 * @return array The filtered column classes
 */
function lerm_get_column_class( $class = '' ) {
	$classes = array();
	$layout  = lerm_site_layout();

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

	if ( ! empty( $class ) ) {
		$classes = array_merge( $classes, (array) $class );
	}

	$classes = array_map( 'esc_attr', array_unique( $classes ) );
	return apply_filters( 'lerm_column_class', $classes, $class );
}
