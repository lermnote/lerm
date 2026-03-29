<?php // phpcs:disable WordPress.Files.FileName
/**
 * Utilities for Lerm theme improved and hardened.
 *
 *
 * NOTE: Keep compatibility with WordPress coding practices. Avoid strict_types
 * for broad compatibility with older WP installs.
 */

namespace Lerm\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get tracking-related theme options prepared by bootstrap.
 *
 * @return array<string, mixed>
 */
function get_tracking_options(): array {
	return apply_filters(
		'lerm_tracking_options',
		array(
			'baidu_tongji' => '',
		)
	);
}

/**
 * Get the client's IP address.
 *
 * Tries several server headers and prefers the first public IP found in
 * X_FORWARDED_FOR. Falls back to REMOTE_ADDR. Returns empty string on failure.
 */
function client_ip(): string {
	$trusted_header = apply_filters( 'lerm_trusted_ip_header', null );

	$raw = '';
	if ( $trusted_header && ! empty( $_SERVER[ $trusted_header ] ) ) {
		// X-Forwarded-For may be a comma-separated list; take the first (client) IP.
		$raw = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $trusted_header ] ) ) )[0] );
	}

	if ( '' === $raw && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$raw = trim( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
	}

	return filter_var( $raw, FILTER_VALIDATE_IP ) ? $raw : '';
}

/**
 * Get a stable identifier for like tracking.
 *
 * Logged-in users are identified by their user ID; guests are assigned a
 * persistent per-browser token stored in a long-lived cookie.
 *
 * @return string
 */
function get_like_user_id(): string {
	if ( is_user_logged_in() ) {
		return 'user_' . get_current_user_id();
	}

	$cookie_name = 'lerm_like_id';
	$token       = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

	if ( '' === $token ) {
		$token = function_exists( 'wp_generate_uuid4' )
		? wp_generate_uuid4()
		: hash( 'sha256', wp_rand() . wp_rand() . microtime( true ) . wp_generate_password( 12 ) );

		if ( ! headers_sent() ) {
			setcookie(
				$cookie_name,
				$token,
				time() + YEAR_IN_SECONDS,
				defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
				defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				is_ssl(),
				true   // HttpOnly
			);
		}

		$_COOKIE[ $cookie_name ] = $token;
	}

	return 'guest_' . $token;
}

/**
 * Render a Bootstrap-style floating form input.
 *
 * @param array $args
 * @return string HTML markup for the form control
 */
function float_form_input( $args = array() ): string {
	$defaults = array(
		'container_class' => 'form-floating mb-3',
		'class'           => 'form-control',
		'type'            => 'text',
		'name'            => 'username',
		'id'              => '', // if empty will be derived from name
		'placeholder'     => 'name@example.com',
		'label_text'      => __( 'Username', 'lerm' ),
		'required'        => false, // boolean
		'input_attrs'     => array(), // associative array of additional attributes
	);

	$args = wp_parse_args( $args, apply_filters( 'lerm_form_args', $defaults ) );

	// Ensure id exists and is valid
	if ( empty( $args['id'] ) ) {
		$args['id'] = sanitize_title_with_dashes( $args['name'] );
	}

	// Build additional attributes safely
	$additional_attrs = '';
	if ( ! empty( $args['input_attrs'] ) && is_array( $args['input_attrs'] ) ) {
		foreach ( $args['input_attrs'] as $attr_name => $attr_value ) {
			$additional_attrs .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
		}
	}

	$html = sprintf(
		'<div class="%1$s">' .
		'<input type="%2$s" name="%3$s" id="%4$s" class="%5$s" placeholder="%6$s"%7$s%8$s>' .
		'<label for="%4$s">%9$s</label>' .
		'</div>',
		esc_attr( $args['container_class'] ),
		esc_attr( $args['type'] ),
		esc_attr( $args['name'] ),
		esc_attr( $args['id'] ),
		esc_attr( $args['class'] ),
		esc_attr( $args['placeholder'] ),
		$args['required'] ? ' required' : '',
		$additional_attrs,
		esc_html( $args['label_text'] )
	);

	return $html;
}

/**
 * Output site copyright text.
 *
 * @param string $type 'short' or 'long'
 * @param bool   $echo_output Whether to echo the result (default true). Returns string either way.
 * @return string
 */
function copyright_text( string $type = 'short', bool $echo_output = true ): string {
	$type = ( 'long' === $type ) ? 'long' : 'short';

	$blogname = sprintf( '<strong>%s</strong>', esc_html( get_bloginfo( 'name' ) ) );

	// Use a lightweight query to fetch the earliest published post
	$year = get_transient( 'lerm_first_post_year' );
	if ( false === $year ) {
		global $wpdb;
		$first_date = $wpdb->get_var(
			"SELECT post_date_gmt FROM {$wpdb->posts}
			 WHERE post_status = 'publish'
			 ORDER BY post_date_gmt ASC
			 LIMIT 1"
		);

		$year = $first_date ? substr( $first_date, 0, 4 ) : gmdate( 'Y' );
		set_transient( 'lerm_first_post_year', $year, MONTH_IN_SECONDS );
	}

	$current_year = (int) gmdate( 'Y' );
	$date         = ( $year === $current_year ) ? (string) $current_year : sprintf( '%1$d-%2$d', $year, $current_year );

	if ( 'short' === $type ) {
		/* translators: 1: year or range, 2: blog name */
		$output = sprintf( '&copy; %1$s %2$s', esc_html( $date ), $blogname );
	} else {
		$output = sprintf(
			/* translators: 1: year or range, 2: blog name */
			__( 'Copyright &copy; %1$s %2$s. All rights reserved.', 'lerm' ),
			esc_html( $date ),
			$blogname
		);
	}

	$output = apply_filters( 'lerm_copyright_text', $output, $type );

	if ( $echo_output ) {
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
	}

	return $output;
}

/**
 * Render link pagination for paginated posts.
 */
function link_pagination(): void {
	wp_link_pages(
		array(
			'previouspagelink' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'lerm' ) . '</span>',
			'nextpagelink'     => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'lerm' ) . '</span>',
			'pagelink'         => esc_html__( 'Page %', 'lerm' ),
		)
	);
}

/**
 * Apply search/replace bootstrap classes to block content.
 */
function apply_bootstrap_classes( string $block_content, array $block, array $search_replace = array() ): string {
	if ( empty( $block['blockName'] ) ) {
		return $block_content;
	}

	if ( isset( $search_replace['search'], $search_replace['replace'] ) && is_array( $search_replace['search'] ) && is_array( $search_replace['replace'] ) ) {

		$block_content = str_replace( $search_replace['search'], $search_replace['replace'], $block_content );
	}

	return apply_filters( "lerm/block/{$block['blockName']}/content", $block_content, $block );
}

/**
 * Social link block: replace link content with custom SVGs for known platforms.
 */
function social_link_icon_classes( string $block_content, array $block ): string {
	if ( strpos( $block_content, 'wp-social-link' ) === false ) {
		return $block_content;
	}

	// Define the mapping of social platforms to custom icons or styles.
	$custom_svgs = array(
		'weibo'    => array(
			'match' => array( 'weibo.com', 'weibo.cn' ),
			'svg'   => '<svg  width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M19.097 8.638c0.357-1.126-0.629-2.196-1.755-1.957-1.032 0.225-1.351-1.318-0.333-1.539 2.351-0.511 4.331 1.741 3.589 3.979-0.319 0.995-1.821 0.507-1.501-0.483zM10.079 21.265c-4.988 0-10.079-2.412-10.079-6.395 0-2.079 1.314-4.476 3.58-6.743 4.678-4.678 9.534-4.734 8.145-0.267-0.188 0.615 0.577 0.267 0.577 0.282 3.73-1.577 6.592-0.788 5.349 2.412-0.174 0.441 0.052 0.511 0.389 0.615 6.367 1.985 1.633 10.097-7.962 10.097zM16.821 14.4c-0.253-2.613-3.683-4.411-7.667-4.021-3.979 0.404-6.982 2.829-6.728 5.443s3.683 4.411 7.667 4.021c3.979-0.404 6.982-2.829 6.728-5.443zM16.324 1.952c-1.215 0.263-0.788 2.050 0.389 1.797 3.392-0.713 6.325 2.477 5.241 5.818-0.347 1.135 1.365 1.736 1.755 0.563 1.497-4.683-2.585-9.192-7.385-8.178zM12.64 16.544c-0.802 1.821-3.134 2.815-5.119 2.172-1.914-0.615-2.721-2.506-1.891-4.209 0.831-1.661 2.961-2.599 4.852-2.116 1.971 0.507 2.961 2.355 2.158 4.152zM8.591 15.137c-0.605-0.253-1.408 0.014-1.783 0.605-0.389 0.605-0.202 1.314 0.404 1.595 0.615 0.282 1.445 0.014 1.835-0.605 0.375-0.615 0.174-1.328-0.455-1.595zM10.121 14.508c-0.239-0.080-0.535 0.028-0.671 0.253-0.136 0.239-0.066 0.497 0.174 0.605 0.239 0.094 0.549-0.014 0.685-0.253 0.131-0.244 0.052-0.511-0.188-0.605z"></path></svg>',
		),
		'qq'       => array(
			'match' => array( 'qq.com' ),
			'svg'   => '<svg  width="21" height="24" viewBox="0 0 21 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M20.352 20.033c-0.541 0.065-2.105-2.475-2.105-2.475 0 1.471-0.757 3.39-2.395 4.776 0.79 0.244 2.573 0.899 2.149 1.615-0.343 0.579-5.889 0.37-7.49 0.189-1.601 0.18-7.147 0.39-7.49-0.189-0.424-0.716 1.357-1.371 2.148-1.615-1.638-1.386-2.396-3.305-2.396-4.776 0 0-1.564 2.54-2.105 2.475-0.252-0.030-0.583-1.391 0.439-4.678 0.481-1.55 1.032-2.838 1.884-4.963-0.143-5.485 2.123-10.086 7.52-10.086 5.337 0 7.655 4.511 7.52 10.086 0.85 2.122 1.403 3.418 1.884 4.963 1.021 3.287 0.69 4.648 0.439 4.678z"></path></svg>',
		),
		'weixin'   => array(
			'match' => array( '#wechat', '#weixin' ),
			'svg'   => '<svg  width="27" height="24" viewBox="0 0 27 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M18.074 8.169c0.3 0 0.591 0.014 0.882 0.052-0.779-3.679-4.725-6.414-9.211-6.414-5.030 0-9.135 3.416-9.135 7.761 0 2.506 1.375 4.575 3.655 6.175l-0.906 2.75 3.191-1.6c1.145 0.225 2.055 0.455 3.2 0.455 0.291 0 0.568-0.014 0.859-0.038-0.188-0.605-0.291-1.248-0.291-1.914-0.005-3.984 3.421-7.226 7.756-7.226zM13.171 5.687c0.68 0 1.135 0.455 1.135 1.145 0 0.68-0.455 1.135-1.135 1.135-0.694 0-1.375-0.455-1.375-1.135 0.005-0.69 0.685-1.145 1.375-1.145zM6.771 7.967c-0.68 0-1.375-0.455-1.375-1.135 0-0.694 0.694-1.145 1.375-1.145 0.694 0 1.145 0.455 1.145 1.145 0 0.685-0.45 1.135-1.145 1.135zM26.416 15.291c0-3.655-3.655-6.63-7.761-6.63-4.35 0-7.761 2.975-7.761 6.63s3.416 6.63 7.761 6.63c0.906 0 1.825-0.239 2.75-0.465l2.506 1.375-0.694-2.28c1.839-1.379 3.2-3.205 3.2-5.26zM16.136 14.142c-0.455 0-0.906-0.455-0.906-0.92 0-0.455 0.455-0.906 0.906-0.906 0.694 0 1.145 0.455 1.145 0.906 0 0.469-0.455 0.92-1.145 0.92zM21.161 14.142c-0.455 0-0.906-0.455-0.906-0.92 0-0.455 0.455-0.906 0.906-0.906 0.68 0 1.145 0.455 1.145 0.906 0.005 0.469-0.465 0.92-1.145 0.92z"></path></svg>',
		),
		'bilibili' => array(
			'match' => array( 'bilibili.com' ),
			'svg'   => '<svg  width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M22.926 5.189c0.784 0.849 1.145 1.863 1.093 3.083v9.497c-0.019 1.239-0.432 2.257-1.243 3.055-0.807 0.798-1.835 1.215-3.073 1.253h-15.384c-1.241-0.038-2.262-0.46-3.063-1.276s-1.219-1.891-1.255-3.219v-9.309c0.036-1.22 0.454-2.233 1.255-3.083 0.801-0.767 1.822-1.189 3.063-1.225h1.379l-1.189-1.211c-0.27-0.269-0.405-0.61-0.405-1.022s0.135-0.754 0.405-1.023c0.27-0.269 0.612-0.404 1.025-0.404s0.755 0.135 1.028 0.404l3.439 3.256h4.129l3.496-3.256c0.286-0.269 0.638-0.404 1.051-0.404s0.755 0.135 1.028 0.404c0.267 0.269 0.404 0.61 0.404 1.023s-0.136 0.754-0.404 1.022l-1.187 1.211h1.375c1.239 0.036 2.252 0.457 3.036 1.225v0zM21.105 8.46c-0.019-0.45-0.174-0.816-0.502-1.103-0.244-0.286-0.657-0.441-1.065-0.46h-15.031c-0.45 0.019-0.819 0.174-1.106 0.46s-0.441 0.652-0.459 1.103v9.121c0 0.432 0.153 0.798 0.459 1.103s0.675 0.46 1.106 0.46h15.031c0.432 0 0.798-0.155 1.093-0.46s0.455-0.671 0.474-1.103v-9.121zM8.704 10.463c0.296 0.296 0.455 0.662 0.474 1.089v1.562c-0.019 0.432-0.174 0.793-0.46 1.089-0.291 0.296-0.657 0.446-1.107 0.446s-0.821-0.15-1.107-0.446c-0.286-0.296-0.441-0.657-0.46-1.089v-1.562c0.019-0.427 0.178-0.793 0.474-1.089s0.619-0.45 1.093-0.469c0.432 0.019 0.798 0.174 1.093 0.469v0zM17.689 10.463c0.296 0.296 0.455 0.662 0.474 1.089v1.562c-0.019 0.432-0.174 0.793-0.46 1.089s-0.657 0.446-1.107 0.446c-0.45 0-0.816-0.15-1.107-0.446-0.328-0.296-0.441-0.657-0.455-1.089v-1.562c0.014-0.427 0.174-0.793 0.469-1.089s0.662-0.45 1.093-0.469c0.432 0.019 0.798 0.174 1.093 0.469v0z"></path></svg>',
		),
	);

	$callback = static function ( array $matches ) use ( $custom_svgs ): string {
		$href    = isset( $matches[1] ) ? $matches[1] : '';
		$content = isset( $matches[2] ) ? $matches[2] : $matches[0];

		// Try to parse host for robust matching
		$host       = (string) wp_parse_url( $href, PHP_URL_HOST );
		$lower_href = strtolower( $href );
		$lower_host = strtolower( $host );

		foreach ( $custom_svgs as $platform ) {
			foreach ( $platform['match'] as $pattern ) {
				$pattern = strtolower( $pattern );
				// Exact host match or contains (for keywords like 'wechat')
				if ( $pattern === $lower_host || false !== strpos( $lower_href, $pattern ) ) {
					// Replace inner content with svg
					$safe_svg = Security::kses_svg( $platform['svg'] );
					return str_replace( $content, $safe_svg, $matches[0] );
				}
			}
		}

		return $matches[0];
	};

	// Replace only anchor tags (non-greedy for inner content)
	$pattern       = '#<a[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is';
	$block_content = preg_replace_callback( $pattern, $callback, $block_content );

	return apply_bootstrap_classes( (string) $block_content, $block );
}
add_filter( 'render_block_core/social-link', __NAMESPACE__ . '\social_link_icon_classes', 10, 2 );
// Register filters for core blocks
// add_filter( 'render_block_core/archives', __NAMESPACE__ . '\archives_classes', 10, 2 );
// add_filter( 'render_block_core/calendar', __NAMESPACE__ . '\calendar_classes', 10, 2 );
// add_filter( 'render_block_core/categories', __NAMESPACE__ . '\categories_classes', 10, 2 );
// add_filter( 'render_block_core/latest-comments', __NAMESPACE__ . '\latest_comments_classes', 10, 2 );
// add_filter( 'render_block_core/latest-posts', __NAMESPACE__ . '\latest_posts_classes', 10, 2 );
// add_filter( 'render_block_core/search', __NAMESPACE__ . '\search_classes', 10, 2 );


// End of file

add_action(
	'wp_head',
	function () {
		$tracking_options = get_tracking_options();
		$code             = $tracking_options['baidu_tongji'] ?? '';
		if ( $code ) {
			// 只允许纯文本 JS 内容，不允许外部 src
			echo '<script>' . wp_kses_post( $code ) . '</script>';
		}
	},
	99
);
