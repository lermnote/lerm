<?php
/**
 * Get the client's IP address.
 */
namespace Lerm\Inc\Functions\Helpers;

use Lerm\Inc\Misc\Image;
function client_ip() {
	$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
			return $_SERVER[ $key ];
		}
	}
	return '';
}

function float_form_input( $args ) {
	$defaults = array(
		'container_class' => 'form-floating mb-3',
		'class'           => 'form-control',
		'type'            => 'text',
		'name'            => 'username',
		'id'              => 'username',
		'placeholder'     => 'name@example.com',
		'label_text'      => __( 'Username' ),
		'required'        => 'required',
		'input_attrs'     => '',
	);

	$args = wp_parse_args( $args, apply_filters( 'lerm_form_args', $defaults ) );

	$additional_attrs = '';
	if ( ! empty( $args['input_attrs'] ) && is_array( $args['input_attrs'] ) ) {
		foreach ( $args['input_attrs'] as $attr_name => $attr_value ) {
			$additional_attrs .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
		}
	}
	ob_start();
	echo sprintf(
		'<div class="%1$s">
			<input type="%2$s" name="%3$s" id="%4$s"  class="%5$s" placeholder="%6$s" %7$s %8$s>
			<label for="%5$s">%9$s</label>
		</div>',
		esc_attr( $args['container_class'] ),
		esc_attr( $args['type'] ),
		esc_attr( $args['name'] ),
		esc_attr( $args['id'] ),
		esc_attr( $args['class'] ),
		esc_attr( $args['placeholder'] ),
		esc_attr( $args['required'] ),
		$additional_attrs,
		esc_html( $args['label_text'] )
	);
	$input = ob_get_clean();
	return $input;
}
/**
 * Archive Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\archives_classes' ) ) {
	/**
	 * Adds Bootstrap classes to archive block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function archives_classes( $block_content, $block ) {

		// Check if the block contains the 'wp-block-archives-list' class, exclude the dropdown.
		if ( strpos( $block_content, 'wp-block-archives-list' ) !== false ) {
			$search  = array(
				'wp-block-archives-list',
				'<li',
				'<a',
				'(',
				')',
			);
			$replace = array(
				'wp-block-archives-list bs-list-group list-group',
				'<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"',
				'<a class="stretched-link text-decoration-none"',
				'<span class="badge bg-primary-subtle text-primary-emphasis">',
				'</span>',
			);

			$block_content = str_replace( $search, $replace, $block_content );
		}

		return apply_filters( 'lerm/block/archives/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/archives', 'Lerm\Inc\Functions\Helpers\archives_classes', 10, 2 );


/**
 * Calendar Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\calendar_classes' ) ) {
	/**
	 * Adds Bootstrap classes to calendar block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function calendar_classes( $block_content, $block ) {

		$search  = array(
			'wp-block-calendar',
			'wp-calendar-table',
			'<a',
		);
		$replace = array(
			'table-responsive',
			'table mb-0',
			'<a class="stretched-link link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		return apply_filters( 'lerm/block/calendar/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/calendar', 'Lerm\Inc\Functions\Helpers\calendar_classes', 10, 2 );

/**
 * Categories Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\categories_classes' ) ) {
	/**
	 * Adds Bootstrap classes to categories block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function categories_classes( $block_content, $block ) {

		// Check if the block contains the 'wp-block-categories-list' class, exclude the dropdown.
		if ( strpos( $block_content, 'wp-block-categories-list' ) !== false ) {
			$search  = array(
				'wp-block-categories-list',
				'cat-item',
				'current-cat',
				'<a',
				'(',
				')',
			);
			$replace = array(
				'wp-block-categories-list bs-list-group list-group',
				'cat-item list-group-item list-group-item-action d-flex justify-content-between align-items-center',
				'current-cat active',
				'<a class="stretched-link text-decoration-none"',
				'<span class="badge bg-primary-subtle text-primary-emphasis">',
				'</span>',
			);

			$block_content = str_replace( $search, $replace, $block_content );
		}

		return apply_filters( 'lerm/block/categories/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/categories', 'Lerm\Inc\Functions\Helpers\categories_classes', 10, 2 );

/**
 * Latest Comments Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\latest_commentss_classes' ) ) {
	/**
	 * Adds Bootstrap classes to latest comments block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function latest_commentss_classes( $block_content, $block ) {

		$search  = array(
			'wp-block-latest-comments',
			'<li class="wp-block-latest-comments bs-list-group list-group__comment">',
			'avatar avatar-48 photo wp-block-latest-comments bs-list-group list-group__comment-avatar',
			'list-group__comment-meta',
			'<a class="wp-block-latest-comments bs-list-group list-group__comment-author',
			'<a class="wp-block-latest-comments bs-list-group list-group__comment-link',
			'wp-block-latest-comments bs-list-group list-group__comment-date',
			'<p',
		);
		$replace = array(
			'wp-block-latest-comments bs-list-group list-group',
			'<li class="list-group-item list-group-item-action text-body-secondary d-flex align-items-start border-start-0 border-end-0">',
			'rounded-pill border p-1 me-2',
			'list-group__comment-meta lh-base',
			'<a class="text-decoration-none text-body-secondary',
			'<a class="stretched-link text-decoration-none',
			'small  d-block',
			'<p class="text-body mt-2 mb-0"',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		return apply_filters( 'lerm/block/latest-comments/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/latest-comments', 'Lerm\Inc\Functions\Helpers\latest_commentss_classes', 10, 2 );

/**
 * Latest Posts Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\latest_posts_classes' ) ) {
	/**
	 * Adds Bootstrap classes to latest post block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function latest_posts_classes( $block_content, $block ) {

		$search  = array(
			'wp-block-latest-posts__list',
			'<li',
			'wp-post-image',
			'<a',
			'wp-block-latest-posts__post-author',
			'wp-block-latest-posts__post-date',
			'wp-block-latest-posts__post-excerpt',
		);
		$replace = array(
			'wp-block-latest-posts__list bs-list-group list-group',
			'<li class="list-group-item list-group-item-action border-start-0  border-end-0"',
			'wp-post-image rounded mb-3',
			'<a class="stretched-link text-decoration-none"',
			'small text-body-secondary',
			'small text-body-secondary d-block',
			'wp-block-latest-posts__post-excerpt mb-0',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		return apply_filters( 'lerm/block/latest-posts/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/latest-posts', 'Lerm\Inc\Functions\Helpers\latest_posts_classes', 10, 2 );

  /**
 * Search Block
 */
if ( ! function_exists( 'Lerm\Inc\Functions\Helpers\search_classes' ) ) {
	/**
	 * Adds Bootstrap classes to search block widget.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	function search_classes( $block_content, $block ) {

		$search  = array(
			'<form ',
			'wp-block-search__input ',
			'wp-block-search__input"',
			'wp-block-search__button ',
			'<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24">
			<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
			</svg>',
		);
		$replace = array(
			'<form novalidate="novalidate" ',
			'wp-block-search__input form-control ',
			'wp-block-search__input form-control"',
			'wp-block-search__button btn btn-outline-secondary ',
			'<i class="fa-solid fa-magnifying-glass"></i>',
		);

		if ( isset( $block['attrs']['buttonPosition'] ) && 'button-inside' === $block['attrs']['buttonPosition'] ) {
			$search[]  = 'wp-block-search__inside-wrapper';
			$replace[] = 'wp-block-search input-group';
		}

		$block_content = str_replace( $search, $replace, $block_content );

		return apply_filters( 'lerm/block/search/content', $block_content, $block );
	}
}
add_filter( 'render_block_core/search', 'Lerm\Inc\Functions\Helpers\search_classes', 10, 2 );
