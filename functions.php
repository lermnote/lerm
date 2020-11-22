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

// Directory URI to the theme folder.
if ( ! defined( 'LERM_URI' ) ) {
	define( 'LERM_URI', trailingslashit( get_template_directory_uri() ) );
}

// Directory path to the theme folder.
if ( ! defined( 'LERM_DIR' ) ) {
	define( 'LERM_DIR', trailingslashit( get_template_directory() ) );
}

require_once LERM_DIR . 'inc/autoloader.php';
function lerm_get_theme_instance() {
	\Lerm\Inc\THEME_SETUP::get_instance();
}
lerm_get_theme_instance();


/**
 * Requre admin framework
 */
require_once LERM_DIR . 'inc/options/codestar-framework.php';

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
		[
			'id'      => $id,
			'tag'     => $tag,
			'default' => $default,
		]
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
 * Keywords
 *
 * @since  1.0.0
 * @return string $keywords
 */
function lerm_keywords() {
	global $post;
	$keywords = array();
	if ( is_singular() ) {
		foreach ( get_the_category( $post->ID ) as $cat ) {
			$keywords[] = $cat->cat_name;
		}
		if ( has_tag() ) {
			foreach ( get_the_tags() as $tag ) {
				$keywords[] = $tag->name;
			}
		}
		$keywords = array_unique( $keywords );
	}
	if ( is_archive() ) {
		$keywords[] = single_cat_title( '', false );
		$keywords[] = single_tag_title( '', false );
		$keywords[] = get_the_author();
	}
	if ( is_home() ) {
		$keywords[] = lerm_options( 'keywords' ) ? lerm_options( 'keywords' ) : get_bloginfo( 'name' );
	}
	$keywords = array_unique( $keywords );
	if ( $keywords ) :
		echo '<meta name="keywords" content="' . ( esc_attr( implode( ',', $keywords ) ) ) . '">';
	endif;
}
add_action( 'wp_head', 'lerm_keywords', 1 );

/**
 * Description
 *
 * @since  1.0.0
 * @return string $dsecription
 */
function lerm_description() {
	global $post;
	$description = ' ';
	if ( is_singular() ) {
		$description = wp_strip_all_tags( $post->post_content );
		$description = strip_shortcodes( $description );
		$description = str_replace( array( "\n", "\r", "\t" ), ' ', $description );
		if ( $post->post_excerpt ) {
			$description = $post->post_excerpt;
		}
	}
	if ( is_archive() ) {
		$description = term_description() ? wp_strip_all_tags( term_description() ) : BLOGNAME . '-' . single_term_title( '', false );
	}
	if ( is_home() ) {
		$description = lerm_options( 'description' ) ? lerm_options( 'description' ) : get_bloginfo( 'description' );
	}
	$description = mb_substr( $description, 0, 200, 'utf8' );
	echo '<meta name="description" content="' . esc_attr( $description ) . '">';
}
add_action( 'wp_head', 'lerm_description', 1 );

/**
 * WordPress baidu submit
 *
 * @since  1.0.0
 */
if ( ! function_exists( 'lerm_baidu_submit' ) && lerm_options( 'sitemap_submit' ) ) :
	function lerm_baidu_submit( $post_ID ) {
		$web_domain = LERM_URI;
		$web_token  = lerm_options( 'submit_token' );
		// Do not submit again
		if ( 1 === get_post_meta( $post_ID, 'Baidusubmit', true ) ) {
			return;
		}
		$url     = get_permalink( $post_ID );
		$api     = 'http://data.zz.baidu.com/urls?site=' . $web_domain . '&token=' . $web_token;
		$request = new WP_Http();
		$result  = $request->request(
			$api,
			array(
				'method'  => 'POST',
				'body'    => $url,
				'headers' => 'Content-Type: text/plain',
			)
		);
		$result  = json_decode( $result['body'], true );
		// if submit success, add post meta 'Baidusubmit'，value is 1
		if ( array_key_exists( 'success', $result ) ) {
			add_post_meta( $post_ID, 'Baidusubmit', 1, true );
		}
	}
	add_action( 'publish_post', 'lerm_baidu_submit', 0 );
endif;

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

/**
 * Shows a pagination for posts list.
 *
 * @since  1.0.0
 * @return void
 */
function lerm_pagination() {

	the_posts_pagination(
		array(
			'mid_size'           => 10,
			'prev_text'          => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
			'next_text'          => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
			'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'The', 'lerm' ) . ' </span>',
			'after_page_number'  => '<span class="meta-nav screen-reader-text">' . __( ' Page', 'lerm' ) . ' </span>',
		)
	);
}
function lerm_link_pagination() {
	wp_link_pages(
		array(
			'mid_size'           => 3,
			'prev_text'          => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
			'next_text'          => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
			'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'The', 'lerm' ) . ' </span>',
			'after_page_number'  => '<span class="meta-nav screen-reader-text">' . __( ' Page', 'lerm' ) . ' </span>',
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
	<nav class="comment-nav mt-2">
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
 * Entry tags list.
 *
 * @since  1.0.0
 * @return string tags_list
 */
function lerm_post_tag( $output = '' ) {
	$tags = get_the_tags();
	if ( $tags && is_singular( 'post' ) ) {
		$output  = '<div class="post-tag-list mb-2"><span class="wrap tags-links" itemprop="keywords" ><span class="screen-reader-text">';
		$output .= esc_html__( 'Tags', 'lerm' );
		$output .= '</span>';
		foreach ( $tags as $tag ) {
			$output .= sprintf( '<a class="btn btn-custom btn-sm mr-2" href="%s" rel="tag">%s</a>', esc_url( get_tag_link( $tag ) ), esc_html( $tag->name ) );
		}
		$output .= '</span></div>';
	}
	echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Fix end of archive url with slash.
 *
 * @param  array $args Arguments to pass to Breadcrumb_Trail.
 * @return $string
 */
function lerm_category_trailingslashit( $string, $type_of_url ) {
	if ( 'single' !== $type_of_url && 'page' !== $type_of_url && 'paged' !== $type_of_url && 'single_paged' !== $type_of_url ) {
		$string = trailingslashit( $string );
	}
	return $string;
}
add_filter( 'user_trailingslashit', 'lerm_category_trailingslashit', 10, 2 );

/**
 * Shows .html slug for pages.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
if ( lerm_options( 'html_slug' ) ) :
	// remove page slash
	function no_page_slash( $string, $type ) {
		global $wp_rewrite;
		if ( true === $wp_rewrite->using_permalinks() && $wp_rewrite->use_trailing_slashes && 'page' === $type ) {
			return untrailingslashit( $string );
		} else {
			return $string;
		}
	}
	add_filter( 'user_trailingslashit', 'no_page_slash', 66, 2 );

	// add html slug
	function html_page_permalink() {
		global $wp_rewrite;
		if ( ! strpos( $wp_rewrite->get_page_permastruct(), '.html' ) ) {
			$wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
		}
	}
	add_action( 'init', 'html_page_permalink', -1 );
endif;

/**
 * Replace avatar url.
 *
 * @return sring $avatar
 */
function lerm_replace_avatar( $avatar ) {
	$regexp = '/https?.*?\/avatar\//i';
	if ( lerm_options( 'replace_avatar' ) ) {
		$replacement = lerm_options( 'replace_avatar' );
	} else {
		$replacement = 'https://cn.gravatar.com/avatar/';
	}
	$avatar = preg_replace( $regexp, $replacement, $avatar );
	return $avatar;
}
add_filter( 'get_avatar', 'lerm_replace_avatar' );

/**
 * Cache avatar localhost.
 *
 * @return sring $avatar
 */
if ( lerm_options( 'avatar_cache' ) ) :
	function lerm_avatar_cache( $avatar ) {
		$tmp = strpos( $avatar, 'http' );
		$g   = substr( $avatar, $tmp, strpos( $avatar, '\'', $tmp ) - $tmp );
		$tmp = strpos( $g, 'avatar/' ) + 7;
		$f   = substr( $g, $tmp, strpos( $g, '?', $tmp ) - $tmp );
		$e   = LERM_DIR . 'assets/avatar/' . $f . '.png';
		$t   = 604800; // max age setting a week
		if ( ! is_file( $e ) || ( time() - filemtime( $e ) ) > $t ) {
			copy( htmlspecialchars_decode( $g ), $e );
		} else {
			$avatar = strtr( $avatar, array( $g => LERM_URI . 'assets/avatar/' . $f . '.png' ) );
		}
		if ( filesize( $e ) < 500 ) {
			copy( LERM_URI . 'assets/avatar/default.png', $e );
		}
		return $avatar;
	}
	add_filter( 'get_avatar', 'lerm_avatar_cache' );
endif;


/**
 * Disable Embeds.
 *
 * @return void
 */
if ( lerm_options( 'disable_embeds' ) ) :
	function lerm_disable_embeds_code_init() {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}
	add_action( 'init', 'lerm_disable_embeds_code_init', 9999 );
endif;

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

	if ( substr( $first_date, 0, 4 ) === date( 'Y' ) || 'short' === $type ) {
		$date = esc_html( date( 'Y ' ) );
	} else {
		$date = esc_html( substr( $first_date, 0, 4 ) . '-' . date( 'Y ' ) );
	}

	$output = $copyright . $date . $blogname . $rights;
	echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
