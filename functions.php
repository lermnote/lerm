<?php
/**
 * Functions and definitions
 *
 * @author Lerm https://www.hanost.com
 * @date   2016-08-28 21:57:52
 * @package Lerm\Inc
 * @since  lerm 1.0
 */
use Lerm\Inc\Setup;
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'DOMAIN', 'lerm' );

// Theme vision.
define( 'LERM_VERSION', wp_get_theme()->get( 'Version' ) );

// Define blog name.
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
require LERM_DIR . 'inc/themeloader.php';
Setup::instance();
Setup::get_options( get_option( 'lerm_theme_options' ) );

/**
 * Shows a pagination for post page.
 *
 * @since  3.0.0
 * @return void
 */
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
function lerm_paginate_comments() {
	?>
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

/**
 * Get post views count.
 *
 * @since  3.0.0
 *
 * @param  string $after Text to append after the view count.
 * @return string        Formatted view count.
 */
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
function lerm_add_page_views() {
	if ( is_singular( 'post' ) && ! is_admin() ){
		$post_ID    = get_queried_object_id();
		$post_views = (int) get_post_meta( $post_ID, 'pageviews', true );
		update_post_meta( 'post', $post_ID, 'pageviews', $post_views + 1 );
	}
}
add_action( 'template_redirect', 'lerm_add_page_views' );

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
 *  Get copyright of website
 *
 * @param string $type type of copyright,'short', 'long'.
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

// functions used for debug mail errors, log is stored at SERVER_ROOT_DIR/mail.log
add_action( 'wp_mail_failed', 'smtplog_mailer_errors', 10, 1 );
function smtplog_mailer_errors( $wp_error ) {
	global $wp_filesystem;
	WP_Filesystem();

	$file = ABSPATH . '/mail.log';

	$timestamp   = time();
	$currenttime = gmdate( 'Y-m-d H:i:s', $timestamp );
	$wp_filesystem->put_contents( $file, $currenttime . ' Mailer Error: ' . $wp_error->get_error_message() . "\n", FS_CHMOD_FILE );
}
