<?php
/**
 * Functions and definitions
 *
 * @author Lerm https://www.hanost.com
 * @date   2016-08-28 21:57:52
 * @since  lerm 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'DOMAIN', 'lerm' );

// Theme vision
define( 'LERM_VERSION', wp_get_theme()->get( 'Version' ) );

// Define blog name
define( 'BLOGNAME', get_bloginfo( 'name' ) );

// Directory URI to the theme folder.
if ( ! defined( 'LERM_URI' ) ) {
	define( 'LERM_URI', trailingslashit( get_template_directory_uri() ) );
}

// Directory path to the theme folder.
if ( ! defined( 'LERM_DIR' ) ) {
	define( 'LERM_DIR', trailingslashit( get_template_directory() ) );
}
/**
 * Requre admin framework
 */
require_once LERM_DIR . 'inc/options/codestar-framework.php';
require_once LERM_DIR . 'inc/autoloader.php';
\Lerm\Inc\Setup::instance();
\Lerm\Inc\Setup::get_options( get_option( 'lerm_theme_options' ) );

/**
 * Theme options functions.
 *
 * @param string $id
 * @param string $tag
 * @return string $options
 */
function lerm_options( string $id, string $tag = '', $default = '' ) {
	$options = (array) get_option( 'lerm_theme_options', array() );
	$value   = $default;
	if ( array_key_exists( $id, $options ) ) {
		if ( is_array( $options[ $id ] ) ) {
			if ( ! empty( $tag ) && array_key_exists( $tag, $options[ $id ] ) ) {
				$value = $options[ $id ][ $tag ];
			} else {
				$value = $options[ $id ];
			}
		} else {
			$value = $options[ $id ];
		}
	}
	return $value;
}

function lerm_link_pagination() {
	wp_link_pages(
		array(
			'previouspagelink' => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
			'nextpagelink'     => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
			'pagelink'         => esc_html__( 'Page %', 'lerm' ),
		)
	);
}

/**
 * Shows a pagination for comments list.
 *
 * @since  3.0.0
 * @return void
 */
function lerm_paginate_comments() {?>
	<nav class="comment-nav mb-3">
		<div class="comment-pager d-flex justify-content-between">
			<div class="comment-prev prev btn btn-sm btn-custom">
				<i class="fa fa-chevron-left"></i>
				<?php previous_comments_link( esc_html__( 'Older Comments', 'lerm' ) ); ?>
			</div>
			<div class="comment-next btn btn-sm btn-custom">
				<?php next_comments_link( esc_html__( 'Newer Comments', 'lerm' ) ); ?>
				<i class=" fa fa-chevron-right"></i>
			</div>
		</div>
	</nav>
	<?php
}

// Get post views count.
function lerm_post_views( $after = '' ) {
	global $post;
	$post_ID = $post->ID;
	$views   = get_transient( 'pageviews_' . $post_ID );

	if ( false === $views ) {
		$views = (int) get_post_meta( $post_ID, 'pageviews', true );
		set_transient( 'pageviews_' . $post_ID, $views, 12 * HOUR_IN_SECONDS ); // Cache for 12 hours
	}

	return number_format( $views ) . $after;
}
// Update post views count.
function add_page_views() {
	if ( is_singular( 'post' ) ) {
		$post_ID    = get_queried_object_id();
		$post_views = (int) get_post_meta( $post_ID, 'pageviews', true );
		update_metadata( 'post', $post_ID, 'pageviews', $post_views + 1 );
	}
}
add_action( 'wp_footer', 'add_page_views' );

/**
 * Style embed front-end.
 *
 * @return void
 */
remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
remove_action( 'embed_footer', 'print_embed_sharing_icon' );
add_action( 'embed_footer', 'embed_custom_footer_style' );
function embed_custom_footer_style() {
	?>
	<style>
		.wp-embed-share {
			display: none;
		}
	</style>
	<?php
}

// Disable Pingback
if ( ! lerm_options( 'disable_pingback' ) ) :
	function no_self_ping( &$links ) {
		$home = home_url();
		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $l ] );
			}
		}
	}
	add_action( 'pre_ping', 'no_self_ping' );
endif;

/**
 * Custom template tags for this theme.
 */
require LERM_DIR . 'inc/template-tags.php';
require LERM_DIR . 'inc/customizer.php';
require LERM_DIR . 'inc/lerm.php';

/**
 *  Get copyright of website
 *
 * @since lerm 3.0
 */
function lerm_create_copyright( string $type = 'short' ) {
	$output   = '';
	$blogname = '<strong>' . get_bloginfo( 'name' ) . '</strong>';

	$all_posts  = get_posts( 'post_status=publish&order=ASC' );
	$first_post = $all_posts[0];
	$first_date = $first_post->post_date_gmt;
	$date       = esc_html( substr( $first_date, 0, 4 ) . ( ( substr( $first_date, 0, 4 ) === gmdate( 'Y' ) ) || ( 'short' === $type ) ? '' : '-' . gmdate( 'Y ' ) ) );
	if ( 'short' === $type ) {
		$output = sprintf(
			'&copy; %1$s %2$s',
			$date,
			$blogname
		);
	} else {
		$output = sprintf(
			'Copyright &copy; %1$s %2$s All rights reserved',
			$date,
			$blogname
		);
	}
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
