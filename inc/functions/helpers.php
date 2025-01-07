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
 * Applies Bootstrap classes to specific blocks.
 *
 * @param string $block_content The block content.
 * @param array  $block         The full block, including name and attributes.
 * @param array  $search_replace An associative array with `search` and `replace` keys.
 * @return string The filtered block content.
 */
function apply_bootstrap_classes( $block_content, $block, $search_replace = array() ) {
	if ( isset( $search_replace['search'], $search_replace['replace'] ) ) {
		$block_content = str_replace( $search_replace['search'], $search_replace['replace'], $block_content );
	}
	return apply_filters( "lerm/block/{$block['blockName']}/content", $block_content, $block );
}

/**
 * Filters archive block content.
 */
function archives_classes( $block_content, $block ) {
	$search_replace = array(
		'search'  => array(
			'wp-block-archives-list',
			'<li',
			'<a',
			'(',
			')',
		),
		'replace' => array(
			'wp-block-archives-list bs-list-group list-group',
			'<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"',
			'<a class="stretched-link text-decoration-none"',
			'<span class="badge bg-primary-subtle text-primary-emphasis">',
			'</span>',
		),
	);
	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/archives', __NAMESPACE__ . '\archives_classes', 10, 2 );

/**
 * Filters calendar block content.
 */
function calendar_classes( $block_content, $block ) {
	$search_replace = array(
		'search'  => array(
			'wp-block-calendar',
			'wp-calendar-table',
			'<a',
		),
		'replace' => array(
			'table-responsive',
			'table mb-0',
			'<a class="stretched-link link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"',
		),
	);
	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/calendar', __NAMESPACE__ . '\calendar_classes', 10, 2 );

/**
 * Filters categories block content.
 */
function categories_classes( $block_content, $block ) {
	if ( strpos( $block_content, 'wp-block-categories-list' ) === false ) {
		return $block_content;
	}
	$search_replace = array(
		'search'  => array(
			'wp-block-categories-list',
			'cat-item',
			'current-cat',
			'<a',
			'(',
			')',
		),
		'replace' => array(
			'wp-block-categories-list bs-list-group list-group',
			'cat-item list-group-item list-group-item-action d-flex justify-content-between align-items-center',
			'current-cat active',
			'<a class="stretched-link text-decoration-none"',
			'<span class="badge bg-primary-subtle text-primary-emphasis">',
			'</span>',
		),
	);
	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/categories', __NAMESPACE__ . '\categories_classes', 10, 2 );

/**
 * Filters latest comments block content.
 */
function latest_comments_classes( $block_content, $block ) {
	$search_replace = array(
		'search'  => array(
			'wp-block-latest-comments',
			'<li class="wp-block-latest-comments bs-list-group list-group__comment">',
			'avatar avatar-48 photo wp-block-latest-comments bs-list-group list-group__comment-avatar',
			'list-group__comment-meta',
			'<a class="wp-block-latest-comments bs-list-group list-group__comment-author',
			'<a class="wp-block-latest-comments bs-list-group list-group__comment-link',
			'wp-block-latest-comments bs-list-group list-group__comment-date',
			'<p',
		),
		'replace' => array(
			'wp-block-latest-comments bs-list-group list-group',
			'<li class="list-group-item list-group-item-action text-body-secondary d-flex align-items-start border-start-0 border-end-0">',
			'rounded-pill border p-1 me-2',
			'list-group__comment-meta lh-base',
			'<a class="text-decoration-none text-body-secondary',
			'<a class="stretched-link text-decoration-none',
			'small d-block',
			'<p class="text-body mt-2 mb-0"',
		),
	);
	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/latest-comments', __NAMESPACE__ . '\latest_comments_classes', 10, 2 );

/**
 * Filters latest posts block content.
 */
function latest_posts_classes( $block_content, $block ) {
	$search_replace = array(
		'search'  => array(
			'wp-block-latest-posts__list',
			'<li',
			'wp-post-image',
			'<a',
			'wp-block-latest-posts__post-author',
			'wp-block-latest-posts__post-date',
			'wp-block-latest-posts__post-excerpt',
		),
		'replace' => array(
			'wp-block-latest-posts__list bs-list-group list-group',
			'<li class="list-group-item list-group-item-action border-start-0 border-end-0"',
			'wp-post-image rounded mb-3',
			'<a class="stretched-link text-decoration-none"',
			'small text-body-secondary',
			'small text-body-secondary d-block',
			'wp-block-latest-posts__post-excerpt mb-0',
		),
	);
	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/latest-posts', __NAMESPACE__ . '\latest_posts_classes', 10, 2 );

/**
 * Filters search block content.
 */
function search_classes( $block_content, $block ) {
	$search_replace = array(
		'search'  => array(
			'<form ',
			'wp-block-search__input ',
			'wp-block-search__input"',
			'wp-block-search__button ',
			'<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24">
			<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
			</svg>',
		),
		'replace' => array(
			'<form novalidate="novalidate" ',
			'wp-block-search__input form-control ',
			'wp-block-search__input form-control"',
			'wp-block-search__button btn btn-outline-secondary ',
			'<i class="li li-solid li-magnifying-glass"></i>',
		),
	);

	if ( isset( $block['attrs']['buttonPosition'] ) && 'button-inside' === $block['attrs']['buttonPosition'] ) {
		$search_replace['search'][]  = 'wp-block-search__inside-wrapper';
		$search_replace['replace'][] = 'wp-block-search input-group';
	}

	return apply_bootstrap_classes( $block_content, $block, $search_replace );
}
add_filter( 'render_block_core/search', __NAMESPACE__ . '\search_classes', 10, 2 );

/**
 * Filters the social link block content and replaces or adds custom icons.
 *
 * @param string $block_content The block content.
 * @param array  $block         The full block, including name and attributes.
 * @return string The filtered block content.
 */
function social_link_icon_classes( $block_content, $block ) {
	// Ensure this only modifies the social-link block.
	if ( strpos( $block_content, 'wp-social-link' ) === false ) {
		return $block_content;
	}

	// Define the mapping of social platforms to custom icons or styles.
	$custom_svgs = array(
		'weibo'     => array(
			'domains' => array( 'weibo.com', 'weibo.cn' ),
			'svg'     => '<svg  width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M19.097 8.638c0.357-1.126-0.629-2.196-1.755-1.957-1.032 0.225-1.351-1.318-0.333-1.539 2.351-0.511 4.331 1.741 3.589 3.979-0.319 0.995-1.821 0.507-1.501-0.483zM10.079 21.265c-4.988 0-10.079-2.412-10.079-6.395 0-2.079 1.314-4.476 3.58-6.743 4.678-4.678 9.534-4.734 8.145-0.267-0.188 0.615 0.577 0.267 0.577 0.282 3.73-1.577 6.592-0.788 5.349 2.412-0.174 0.441 0.052 0.511 0.389 0.615 6.367 1.985 1.633 10.097-7.962 10.097zM16.821 14.4c-0.253-2.613-3.683-4.411-7.667-4.021-3.979 0.404-6.982 2.829-6.728 5.443s3.683 4.411 7.667 4.021c3.979-0.404 6.982-2.829 6.728-5.443zM16.324 1.952c-1.215 0.263-0.788 2.050 0.389 1.797 3.392-0.713 6.325 2.477 5.241 5.818-0.347 1.135 1.365 1.736 1.755 0.563 1.497-4.683-2.585-9.192-7.385-8.178zM12.64 16.544c-0.802 1.821-3.134 2.815-5.119 2.172-1.914-0.615-2.721-2.506-1.891-4.209 0.831-1.661 2.961-2.599 4.852-2.116 1.971 0.507 2.961 2.355 2.158 4.152zM8.591 15.137c-0.605-0.253-1.408 0.014-1.783 0.605-0.389 0.605-0.202 1.314 0.404 1.595 0.615 0.282 1.445 0.014 1.835-0.605 0.375-0.615 0.174-1.328-0.455-1.595zM10.121 14.508c-0.239-0.080-0.535 0.028-0.671 0.253-0.136 0.239-0.066 0.497 0.174 0.605 0.239 0.094 0.549-0.014 0.685-0.253 0.131-0.244 0.052-0.511-0.188-0.605z"></path></svg>',
		),
		'qq'        => array(
			'domains' => array( 'qq.com' ),
			'svg'     => '<svg  width="21" height="24" viewBox="0 0 21 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M20.352 20.033c-0.541 0.065-2.105-2.475-2.105-2.475 0 1.471-0.757 3.39-2.395 4.776 0.79 0.244 2.573 0.899 2.149 1.615-0.343 0.579-5.889 0.37-7.49 0.189-1.601 0.18-7.147 0.39-7.49-0.189-0.424-0.716 1.357-1.371 2.148-1.615-1.638-1.386-2.396-3.305-2.396-4.776 0 0-1.564 2.54-2.105 2.475-0.252-0.030-0.583-1.391 0.439-4.678 0.481-1.55 1.032-2.838 1.884-4.963-0.143-5.485 2.123-10.086 7.52-10.086 5.337 0 7.655 4.511 7.52 10.086 0.85 2.122 1.403 3.418 1.884 4.963 1.021 3.287 0.69 4.648 0.439 4.678z"></path></svg>',
		),
		'weixin'    => array(
			'domains' => array( '#wechat', '#weixin' ),
			'svg'     => '<svg  width="27" height="24" viewBox="0 0 27 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M18.074 8.169c0.3 0 0.591 0.014 0.882 0.052-0.779-3.679-4.725-6.414-9.211-6.414-5.030 0-9.135 3.416-9.135 7.761 0 2.506 1.375 4.575 3.655 6.175l-0.906 2.75 3.191-1.6c1.145 0.225 2.055 0.455 3.2 0.455 0.291 0 0.568-0.014 0.859-0.038-0.188-0.605-0.291-1.248-0.291-1.914-0.005-3.984 3.421-7.226 7.756-7.226zM13.171 5.687c0.68 0 1.135 0.455 1.135 1.145 0 0.68-0.455 1.135-1.135 1.135-0.694 0-1.375-0.455-1.375-1.135 0.005-0.69 0.685-1.145 1.375-1.145zM6.771 7.967c-0.68 0-1.375-0.455-1.375-1.135 0-0.694 0.694-1.145 1.375-1.145 0.694 0 1.145 0.455 1.145 1.145 0 0.685-0.45 1.135-1.145 1.135zM26.416 15.291c0-3.655-3.655-6.63-7.761-6.63-4.35 0-7.761 2.975-7.761 6.63s3.416 6.63 7.761 6.63c0.906 0 1.825-0.239 2.75-0.465l2.506 1.375-0.694-2.28c1.839-1.379 3.2-3.205 3.2-5.26zM16.136 14.142c-0.455 0-0.906-0.455-0.906-0.92 0-0.455 0.455-0.906 0.906-0.906 0.694 0 1.145 0.455 1.145 0.906 0 0.469-0.455 0.92-1.145 0.92zM21.161 14.142c-0.455 0-0.906-0.455-0.906-0.92 0-0.455 0.455-0.906 0.906-0.906 0.68 0 1.145 0.455 1.145 0.906 0.005 0.469-0.465 0.92-1.145 0.92z"></path></svg>',
		),
		'bilibili'  => array(
			'domains' => array( 'bilibili.com' ),
			'svg'     => '<svg  width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M22.926 5.189c0.784 0.849 1.145 1.863 1.093 3.083v9.497c-0.019 1.239-0.432 2.257-1.243 3.055-0.807 0.798-1.835 1.215-3.073 1.253h-15.384c-1.241-0.038-2.262-0.46-3.063-1.276s-1.219-1.891-1.255-3.219v-9.309c0.036-1.22 0.454-2.233 1.255-3.083 0.801-0.767 1.822-1.189 3.063-1.225h1.379l-1.189-1.211c-0.27-0.269-0.405-0.61-0.405-1.022s0.135-0.754 0.405-1.023c0.27-0.269 0.612-0.404 1.025-0.404s0.755 0.135 1.028 0.404l3.439 3.256h4.129l3.496-3.256c0.286-0.269 0.638-0.404 1.051-0.404s0.755 0.135 1.028 0.404c0.267 0.269 0.404 0.61 0.404 1.023s-0.136 0.754-0.404 1.022l-1.187 1.211h1.375c1.239 0.036 2.252 0.457 3.036 1.225v0zM21.105 8.46c-0.019-0.45-0.174-0.816-0.502-1.103-0.244-0.286-0.657-0.441-1.065-0.46h-15.031c-0.45 0.019-0.819 0.174-1.106 0.46s-0.441 0.652-0.459 1.103v9.121c0 0.432 0.153 0.798 0.459 1.103s0.675 0.46 1.106 0.46h15.031c0.432 0 0.798-0.155 1.093-0.46s0.455-0.671 0.474-1.103v-9.121zM8.704 10.463c0.296 0.296 0.455 0.662 0.474 1.089v1.562c-0.019 0.432-0.174 0.793-0.46 1.089-0.291 0.296-0.657 0.446-1.107 0.446s-0.821-0.15-1.107-0.446c-0.286-0.296-0.441-0.657-0.46-1.089v-1.562c0.019-0.427 0.178-0.793 0.474-1.089s0.619-0.45 1.093-0.469c0.432 0.019 0.798 0.174 1.093 0.469v0zM17.689 10.463c0.296 0.296 0.455 0.662 0.474 1.089v1.562c-0.019 0.432-0.174 0.793-0.46 1.089s-0.657 0.446-1.107 0.446c-0.45 0-0.816-0.15-1.107-0.446-0.328-0.296-0.441-0.657-0.455-1.089v-1.562c0.014-0.427 0.174-0.793 0.469-1.089s0.662-0.45 1.093-0.469c0.432 0.019 0.798 0.174 1.093 0.469v0z"></path></svg>',
		),
	);
		// Replace default SVGs with custom icons.
		$block_content = preg_replace_callback(
			'/<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i',
			function ( $matches ) use ( $custom_svgs ) {
				$url     = $matches[1];
				$content = $matches[2];

				foreach ( $custom_svgs as $platform ) {
					foreach ( $platform['domains'] as $domain ) {
						if ( strpos( $url, $domain ) !== false ) {
							return str_replace( $content, $platform['svg'], $matches[0] );
						}
					}
				}

				// Default fallback (if no custom icon is matched).
				return $matches[0];
			},
			$block_content
		);

	// Add Bootstrap classes for styling.
	// $search_replace = array();

	return apply_bootstrap_classes( $block_content, $block );
}

add_filter( 'render_block_core/social-link', __NAMESPACE__ . '\social_link_icon_classes', 10, 2 );
