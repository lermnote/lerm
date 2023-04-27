<?php
/**
 * Layouts API - An API for themes to build layout options.
 *
 * Theme Layouts was created to allow theme developers to easily style themes with dynamic layout
 * structures. This file merely contains the API function calls at theme developers' disposal.
 *
 * @package Lerm/Inc
 */

namespace Lerm\Inc;

class Layout {

	private $site_layout; // Add a class property to cache the site layout.

	private static $default_row_classes = array( 'row' ); // Define default row classes as a static variable.

	private static $global_layout;

	public static function instance( $params = array() ) {
		return new self( $params );
	}
	/**
	 * Add a filter to modify the site layout.
	 *
	 * @param string $layout The current theme layout.
	 * @return string The filtered layout.
	 */
	public function post_layout( $layout ) {
		$queried_object_id = get_queried_object_id();
		$metabox           = array();

		if ( $queried_object_id ) {
			$metabox = get_post_meta( $queried_object_id, '_lerm_metabox_options', true );
		}

		$layout = isset( $metabox['page_layout'] ) && $metabox['page_layout'] ? $metabox['page_layout'] : $layout;
		add_filter( 'post_layout', $layout );
	}

	/**
	 * Get the global site layout.
	 *
	 * @return string The global layout.
	 */
	public function lerm_global_layout() {
		return lerm_options( 'global_layout' );
	}

	/**
	 * Get the site layout.
	 *
	 * @return string The site layout.
	 */
	public function lerm_site_layout() {
		return apply_filters( 'lerm_site_layout', $this->lerm_global_layout() );
	}

	/**
	 * Get the classes for a row element.
	 *
	 * @param string $class The row class.
	 * @return array The row classes.
	 */
	public function lerm_get_row_class( $class = '' ) {

		$classes = array( 'row' );

		// Add justify-content-md-center class for narrow 1 column layout.
		if ( 'layout-1c-narrow' === $this->lerm_site_layout() ) {
			$classes[] = 'justify-content-md-center';
		}

		if ( ! empty( $class ) ) {
			$classes = array_merge( $classes, (array) $class );
		}

		// Escape and apply any filters to the classes.
		$classes = array_map( 'esc_attr', $classes );
		return array_unique( apply_filters( 'lerm_row_class', $classes, $class ) );
	}

	/**
	 * Output the classes for a row element.
	 *
	 * @param string $class The row class.
	 */
	public function lerm_row_class( $class = '' ) {
		echo 'class="' . esc_attr( join( ' ', $this->lerm_get_row_class( $class ) ) ) . '"';
	}

	/**
	 * Get the classes for a column element.
	 *
	 * @param string $class The column class.
	 * @return array The column classes.
	 */
	public function lerm_get_column_class( $class = '' ) {

		$classes = array();

		$site_layout = $this->lerm_site_layout();

		if ( 'layout-2c-l' === $site_layout ) {
			$classes = array(
				'col-lg-8',
				'ps-lg-0',
				'order-lg-last',
			);
		} elseif ( 'layout-2c-r' === $site_layout ) {
			$classes = array(
				'col-lg-8',
				'pe-lg-0',
			);
		} elseif ( in_array( $site_layout, array( 'layout-1c', 'layout-1c-narrow' ) ) ) {
			$classes = array(
				'col-12',
			);
		}

		if ( ! empty( $class ) ) {
			$classes = array_merge( $classes, (array) $class );
		}

		// Escape and apply any filters to the classes.
		$classes = array_map( 'esc_attr', $classes );
		return array_unique( apply_filters( 'lerm_column_class', $classes, $class ) );
	}
}
