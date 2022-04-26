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

	$args = apply_filters(
		'theme_option',
		array(
			'id'      => $id,
			'tag'     => $tag,
			'default' => $default,
		)
	);
	// Get theme options.
	$options = get_option( 'lerm_theme_options' );
	if ( ! array_key_exists( $id, $options ) ) {
		return;
	}

	if ( is_array( $options[ $id ] ) ) {
		if ( ! empty( $tag ) && array_key_exists( $tag, $options[ $id ] ) ) {
			return $options[ $id ][ $tag ] ? $options[ $id ][ $tag ] : $default;
		} else {
			return $options[ $id ] ? $options[ $id ] : $default;
		}
	} else {
		$default = $tag;
		return $options[ $id ] ? $options[ $id ] : $default;
	}
}


/**
 * Navigation post.
 *
 * @since  1.0.0
 * @return void
 */
function lerm_post_navigation() {
	if ( is_singular( 'post' ) ) :
		// Previous/next post navigation.
		the_post_navigation(
			array(
				'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next Post', 'lerm' ) . '</span><i class="fa fa-chevron-right"></i>' .
				'<span class="screen-reader-text">' . __( 'Next post:', 'lerm' ) . '</span> <br/>' .
				'<span class="post-title d-none d-md-block">%title</span>',
				'prev_text' => '<i class="fa fa-chevron-left"></i><span class="meta-nav" aria-hidden="true">' . __( 'Previous Post', 'lerm' ) . '</span> ' .
				'<span class="screen-reader-text">' . __( 'Previous post:', 'lerm' ) . '</span> <br/>' .
				'<span class="post-title d-none d-md-block">%title</span>',
			)
		);
	endif;
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
function post_views( $after = '' ) {
	global $post;
	$post_ID = $post->ID;
	$views   = (int) get_post_meta( $post_ID, 'pageviews', true );
	return number_format( $views ) . $after;
}

// Update post views count.
function addpageviews() {
	if ( is_singular( 'post' ) ) {
		global $post;
		$post_ID = $post->ID;
		if ( $post_ID ) {
			$post_views = (int) get_post_meta( $post_ID, 'pageviews', true );
			if ( ! update_post_meta( $post_ID, 'pageviews', ( $post_views + 1 ) ) ) {
				add_post_meta( $post_ID, 'pageviews', 1, true );
			}
		}
	}
}
add_action( 'wp_head', 'addpageviews' );

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

// code hightlight
function code_highlight_esc_html( $content ) {
	$regex = '/(\[code\s+[^\]]*?\])(.*?)(\[\/code\])/sim';

	return preg_replace_callback( $regex, 'dangopress_esc_callback', $content );
}

function pre_esc_html( $content ) {
	$regex = '/(<pre\s+[^>]*?class\s*?=\s*?[",\'].*?prettyprint.*?[",\'].*?>)(.*?)(<\/pre>)/sim';

	return preg_replace_callback( $regex, 'dangopress_esc_callback', $content );
}

function dangopress_esc_html( $content ) {
	$regex = '/(<code.*?>)(.*?)(<\/code>)/sim';

	return preg_replace_callback( $regex, 'dangopress_esc_callback', $content );
}

function dangopress_esc_callback( $matches ) {
	if ( stripos( $matches[1], 'code' ) !== false ) {
		$tag_open  = $matches[1];
		$content   = $matches[2];
		$tag_close = $matches[3];
	}
	if ( stripos( $matches[1], 'pre' ) !== false ) {
		$tag_open  = $matches[1];
		$content   = $matches[2];
		$tag_close = $matches[3];
	}
	if ( stripos( $matches[1], 'code' ) !== false ) {
		$tag_open  = $matches[1];
		$content   = $matches[2];
		$tag_close = $matches[3];
	}
	$content = esc_html( $content );

	return $tag_open . $content . $tag_close;
}
add_filter( 'the_content', 'code_highlight_esc_html', 2 );
add_filter( 'comment_text', 'code_highlight_esc_html', 2 );
add_filter( 'the_content', 'pre_esc_html', 2 );
add_filter( 'comment_text', 'pre_esc_html', 2 );
add_filter( 'the_content', 'dangopress_esc_html', 2 );
add_filter( 'comment_text', 'dangopress_esc_html', 2 );

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
function lerm_create_copyright( string $type = '' ) {
	$type = 'short';

	$output   = '';
	$blogname = '<strong>' . get_bloginfo( 'name' ) . '</strong>';

	$all_posts  = get_posts( 'post_status=publish&order=ASC' );
	$first_post = $all_posts[0];
	$first_date = $first_post->post_date_gmt;
	if ( 'short' === $type ) {
		$copyright = esc_html__( '&copy; ', 'lerm' );
		$rights    = '';
	} else {
		$copyright = esc_html__( 'Copyright &copy; ', 'lerm' );
		$rights    = esc_html__( ' All rights reserved ', 'lerm' );
	}

	if ( substr( $first_date, 0, 4 ) === gmdate( 'Y' ) || 'short' === $type ) {
		$date = esc_html( gmdate( 'Y ' ) );
	} else {
		$date = esc_html( substr( $first_date, 0, 4 ) . '-' . gmdate( 'Y ' ) );
	}

	$output = $copyright . $date . $blogname . $rights;
	echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
