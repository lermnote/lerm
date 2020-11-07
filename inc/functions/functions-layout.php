<?php
/**
 * Layouts API - An API for themes to build layout options.
 *
 * Theme Layouts was created to allow theme developers to easily style themes with dynamic layout
 * structures. This file merely contains the API function calls at theme developers' disposal.
 *
 */

add_filter( 'lerm_site_layout', 'lerm_layout_filter', 5 );


function lerm_site_layout() {
	return apply_filters( 'lerm_site_layout', lerm_global_layout() );
}

function lerm_global_layout() {
	return lerm_options( 'global_layout' );
}
/**
 * post layout
 *
 * @param int $post_id
 * @return void
 */
function lerm_post_layout( $post_id ) {
	// Get serialized layout metadata

	$metabox = get_post_meta( $post_id, '_lerm_metabox_options', true );
	return ! empty( $metabox['page_layout'] ) ? $metabox['page_layout'] : '';
}
/**
 * Layout filter function
 *
 * @param string $theme_layout
 * @return void
 */
function lerm_layout_filter( $theme_layout ) {

	//filter post layout.
	if ( is_singular() ) {
		$layout = lerm_post_layout( get_queried_object_id() );
	}

	return ! empty( $layout ) && isset( $layout ) ? $layout : $theme_layout;
}


/**
 * row class filter
 *
 * @param string $class
 * @return void
 */
function lerm_row_class( $class = '' ) {
	echo 'class="' . esc_attr( join( ' ', lerm_get_row_class( $class ) ) ) . '"';
}
function lerm_get_row_class( $class = '' ) {
	$classes = array();

	$classes[] = 'row';
	if ( 'layout-1c-narrow' === lerm_site_layout() ) {
		$classes[] = 'justify-content-md-center';
	}

	if ( ! empty( $class ) ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_merge( $classes, $class );
	} else {
		$class = array();
	}
	$classes = array_map( 'esc_attr', $classes );

	$classes = apply_filters( 'lerm_row_class', $classes, $class );

	return array_unique( $classes );
}


/**
 * column classes filter
 *
 * @param string $class
 * @return void
 */
function lerm_column_class( $class = '' ) {
	echo 'class="' . esc_attr( join( ' ', lerm_get_column_class( $class ) ) ) . '"';
}
function lerm_get_column_class( $class = '' ) {
	$classes = array();

	if ( 'layout-2c-l' === lerm_site_layout() ) {
		$classes[] = 'col-lg-8';
		$classes[] = 'pl-lg-0';
		$classes[] = 'order-lg-last';
	}
	if ( 'layout-2c-r' === lerm_site_layout() ) {
		$classes[] = 'col-lg-8';
		$classes[] = 'pr-lg-0';
	}
	if ( in_array( lerm_site_layout(), [ 'layout-1c', 'layout-1c-narrow' ] ) ) {
		$classes[] = 'col-12';
	}

	if ( ! empty( $class ) ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_merge( $classes, $class );
	} else {
		$class = array();
	}
	$classes = array_map( 'esc_attr', $classes );

	$classes = apply_filters( 'lerm_column_class', $classes, $class );

	return array_unique( $classes );
}
