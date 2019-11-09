<?php
/**
 * Functions and definitions
 *
 * @author https://www.hanost.com
 * @date   2016-08-28
 * @since  lerm 2.0
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
define( 'LERM_URI', trailingslashit( get_template_directory_uri() ) );
// Directory path to the theme folder.
define( 'LERM_DIR', trailingslashit( get_template_directory() ) );
/**
 * This function sets up support for various WordPress and framework functionality.
 *
 * @since  1.0.0
 * @return void
 */
function lerm_theme_setup() {
	// site title
	add_theme_support( 'title-tag' );

	// site logo
	add_theme_support(
		'custom-logo',
		array(
			'height'           => 50,
			'flex-width'       => true,
			'flex-height'      => true,
			'uploads'          => true,
			'header-text'      => array( 'site-title', 'site-description' ),
			'wp-head-callback' => 'lerm_header_style',
		)
	);

	// Adds core WordPress HTML5 support.
	add_theme_support(
		'html5',
		array( 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' )
	);

	// Automatically add feed links to <head>.
	add_theme_support( 'automatic-feed-links' );

	// Feature
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 200, 128 );

	// Post formats.
	add_theme_support(
		'post-formats',
		array( 'aside', 'audio', 'chat', 'image', 'gallery', 'link', 'quote', 'status', 'video' )
	);

	// Registers nav menu locations.
	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'lerm' ),
			'mobile'  => __( 'Mobile', 'lerm' ),
			'social'  => __( 'Social Links Menu', 'lerm' ),
		)
	);
	// Set the default content width.
	$GLOBALS['content_width'] = 525;
	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory
	 */
	load_theme_textdomain( DOMAIN, LERM_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'lerm_theme_setup', 2 );
/**
* Requre admin framework
*
* @since 2.0
*/
require_once LERM_DIR . 'inc/options/codestar-framework.php';

$lerm = get_option( 'lerm_theme_options' );

/**
 * Theme options functions.
 *
 * @param  $id ; $args
 * @return $options
 */
function lerm_options( $id, $arg = null ) {
	$lerm = get_option( 'lerm_theme_options' );
	if ( isset( $lerm[ $id ] ) ) {
		if ( $arg ) {
			$option = $lerm[ $id ][ $arg ];
		} else {
			$option = $lerm[ $id ];
		}
		return $option;
	}
}

/**
 * Load scripts/styles on the front-end.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
function lerm_enqueue_styles() {
	// bootstrap style.
	wp_enqueue_style( 'lerm_bootstrap', LERM_URI . 'assets/css/bootstrap.min.css', array(), '4.3.1' );
	// fontawesome-all.
	wp_enqueue_style( 'lerm_font', LERM_URI . 'assets/css/lerm-font.min.css', array(), '1.0.0' );

	// Theme stylesheet.
	if ( is_singular( 'post' ) && lerm_options( 'enable_code_highlight' ) ) {
		// code highlight.
		wp_enqueue_style( 'lerm_solarized', LERM_URI . 'assets/css/solarized-dark.min.css', array(), LERM_VERSION );
	}
	wp_enqueue_style( 'lerm_style', get_stylesheet_uri(), array(), LERM_VERSION );
}
add_action( 'wp_enqueue_scripts', 'lerm_enqueue_styles' );

/**
 * Load scripts/styles on the front end
 *
 * @since  3.0.0
 * @access public
 * @return void
 */

function lerm_enqueue_scripts() {
	global $wp_query;
	// register script
	wp_register_script( 'jquery-min', LERM_URI . 'assets/js/jquery.min.js', array(), '3.1.0', true );
	wp_register_script( 'bootstrap', LERM_URI . 'assets/js/bootstrap.min.js', array(), '4.3.1', true );
	wp_register_script( 'lazyload', LERM_URI . 'assets/js/lazyload.min.js', array(), '2.0.0', true );
	wp_register_script( 'lightbox', LERM_URI . 'assets/js/ekko-lightbox.min.js', array(), '2.0.0', true );
	wp_register_script( 'qrcode', LERM_URI . 'assets/js/qrcode.min.js', array(), '2.0', true );
	wp_register_script( 'highlight', LERM_URI . 'assets/js/highlight.pack.js', array(), '9.14.2', true );
	wp_register_script( 'lerm_js', LERM_URI . 'assets/js/lerm.min.js', array(), LERM_VERSION, true );

	// enqueue script
	if ( lerm_options( 'cdn_jquery' ) ) {
		wp_enqueue_script( 'jquery_cdn', lerm_options( 'cdn_jquery' ), array(), LERM_VERSION, true );
	} else {
		wp_enqueue_script( 'jquery-min' );
	}
	wp_enqueue_script( 'bootstrap' );
	wp_enqueue_script( 'lazyload' );
	wp_enqueue_script( 'lightbox' );

	if ( is_singular( 'post' ) ) {
		wp_enqueue_script( 'qrcode' );

		if ( lerm_options( 'enable_code_highlight' ) ) {
			wp_enqueue_script( 'highlight' );
		}
	}

	wp_localize_script(
		'lerm_js',
		'adminajax',
		array(
			'url'      => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ajax_nonce' ),
			'noposts'  => __( 'No older posts found', 'lerm' ),
			'loadmore' => __( 'Load more', 'lerm' ),
			'loading'  => __( 'Loading...', 'lerm' ),
			'posts'    => wp_json_encode( $wp_query->query_vars ),
			'current'  => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
			// 'maxpage'  => $wp_query->max_num_pages,
		)
	);
	wp_enqueue_script( 'lerm_js' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lerm_enqueue_scripts' );

/**
 * Set title separator, default "|"
 *
 * @since  1.0.0
 * @return $lerm['title_sepa'] or "|"
 */
function lerm_title_separator() {
	return lerm_options( 'title_sepa' ) ? lerm_options( 'title_sepa' ) : '|';
}
add_filter( 'document_title_separator', 'lerm_title_separator' );

/**
 * keywords and description
 *
 * @since  1.0.0
 * @return void
 */
function lerm_keywords_and_description() {
	global $post;
	$keywords    = array();
	$description = '';
	if ( is_home() ) {
		$keywords[]  = lerm_options( 'keywords' );
		$description = trim( lerm_options( 'description' ) );
	} elseif ( is_singular() ) {
		if ( has_tag() ) {
			foreach ( ( get_the_tags() ) as $tag ) {
				$keywords[] = $tag->name;
			}
		} else {
			$keywords[] = trim( apply_filters( 'wp_title', '', '', '' ) );
		}
		if ( $post->post_excerpt ) {
			$text = $post->post_excerpt;
		} else {
			$text = $post->post_content;
		}
		$description = trim( str_replace( array( "\r\n", "\r", "\n", '　', ' ' ), ' ', str_replace( '"', "'", wp_strip_all_tags( $text ) ) ) );
		if ( ! ( $description ) ) {
			$description = BLOGNAME . '-' . trim( apply_filters( 'wp_title', '', '', '' ) );
		}
	} else {
		$keywords[]  = single_term_title( '', false );
		$description = term_description() ? wp_strip_all_tags( term_description() ) : BLOGNAME . '-' . single_term_title( '', false );
	}
	$description = mb_substr( $description, 0, 200, 'utf-8' );
	echo '<meta name="keywords" content="' . ( esc_attr( implode( ',', $keywords ) ) ) . '">
	      <meta name="description" content="' . esc_html( $description ) . '">';
}
add_action( 'wp_head', 'lerm_keywords_and_description', 1 );

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
if ( lerm_options( 'post_navigation' ) ) :
	function lerm_post_navigation() {
		if ( is_singular( 'post' ) ) :
			// Previous/next post navigation.
			the_post_navigation(
				array(
					'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next Post', 'lerm' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Next post:', 'lerm' ) . '</span> <br/>' .
						'<span class="post-title d-none d-md-block">%title</span>',
					'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous Post', 'lerm' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Previous post:', 'lerm' ) . '</span> <br/>' .
						'<span class="post-title d-none d-md-block">%title</span>',
				)
			);
		endif;
	}
endif;

/**
 * Shows a pagination for posts list.
 *
 * @since  1.0.0
 * @return void
 */
function lerm_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 3,
			'prev_text'          => '<span class="screen-reader-text">' . __( 'Previous page', 'lerm' ) . '</span>',
			'next_text'          => '<span class="screen-reader-text">' . __( 'Next page', 'lerm' ),
			'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'The', 'lerm' ) . ' </span>',
			'after_page_number'  => '<span class="meta-nav screen-reader-text">' . __( ' Page', 'lerm' ) . ' </span>',
		)
	);
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

// Entry tags list.
function lerm_entry_tag() {
	$tags_list = get_the_tag_list( ' ', esc_html( ' ', 'lerm' ) );
	if ( $tags_list && is_singular( 'post' ) ) {
		printf(
			'<div class="entry-tags mb-2"><span class="wrap tags-links" itemprop="keywords" ><span class="screen-reader-text">%s</span>%s</span></div>',
			esc_html__( 'Tags', 'lerm' ),
			$tags_list
		);// WPCS: XSS OK.
	}
}

/**
 * Post likes button ajax handler function.
 *
 * @package Lerm
 * @since Lerm 2.0
 */
add_action( 'wp_ajax_nopriv_lerm_post_like', 'lerm_post_like' );
add_action( 'wp_ajax_lerm_post_like', 'lerm_post_like' );
function lerm_post_like() {

	// check ajax nonce
	check_ajax_referer( 'ajax_nonce', 'security' );

	$id         = $_POST['postID'];
	$like_count = get_post_meta( $id, 'lerm_post_like', true );
	$expire     = time() + 604800;
	$domain     = ( 'localhost' !== $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
		setcookie( 'post_like_' . $id, $id, $expire, '/', $domain, false );
	if ( ! $like_count || ! is_numeric( $like_count ) ) {
		update_post_meta( $id, 'lerm_post_like', 1 );
	} else {
		update_post_meta( $id, 'lerm_post_like', ( $like_count + 1 ) );
	}
	echo esc_attr( get_post_meta( $id, 'lerm_post_like', true ) );
	wp_die();
}

/**
 * Activate links manger section.
 *
 * @since  1.0.0
 * @return boolen
 */
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

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
 * Removes '/category' from your category permalinks
 */
if ( lerm_options( 'no_cat_base' ) ) :
	/* actions */
	add_action( 'created_category', 'no_category_base_refresh_rules' );
	add_action( 'delete_category', 'no_category_base_refresh_rules' );
	add_action( 'edited_category', 'no_category_base_refresh_rules' );
	add_action( 'init', 'no_category_base_permastruct' );

	/* filters */
	add_filter( 'category_rewrite_rules', 'no_category_base_rewrite_rules' );
	add_filter( 'query_vars', 'no_category_base_query_vars' );
	add_filter( 'request', 'no_category_base_request' );
endif;

function no_category_base_refresh_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
 * Removes category base.
 *
 * @return void
 */
function no_category_base_permastruct() {
	global $wp_rewrite;
	$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
}
remove_filter( 'comment_text', 'make_clickable', 9 );

/**
 * Adds our custom category rewrite rules.
 *
 * @param  array $category_rewrite Category rewrite rules.
 *
 * @return array
 */
function no_category_base_rewrite_rules( $category_rewrite ) {
	global $wp_rewrite;
	$category_rewrite = array();

	/* WPML is present: temporary disable terms_clauses filter to get all categories for rewrite */
	if ( class_exists( 'Sitepress' ) ) {
		global $sitepress;
		remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		$categories = get_categories( array( 'hide_empty' => false ) );
		// Fix provided by Albin here https://wordpress.org/support/topic/bug-with-wpml-2/#post-8362218
		// add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
	} else {
		$categories = get_categories( array( 'hide_empty' => false ) );
	}

	foreach ( $categories as $category ) {
		$category_nicename = $category->slug;

		if ( $category->parent === $category->cat_ID ) {
			$category->parent = 0;
		} elseif ( 0 !== $category->parent ) {
			$category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
		}

		$category_rewrite[ '(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ]    = 'index.php?category_name=$matches[1]&feed=$matches[2]';
		$category_rewrite[ "({$category_nicename})/{$wp_rewrite->pagination_base}/?([0-9]{1,})/?$" ] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$category_rewrite[ '(' . $category_nicename . ')/?$' ]                                       = 'index.php?category_name=$matches[1]';
	}

	// Redirect support from Old Category Base
	$old_category_base                                 = get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category';
	$old_category_base                                 = trim( $old_category_base, '/' );
	$category_rewrite[ $old_category_base . '/(.*)$' ] = 'index.php?category_redirect=$matches[1]';

	return $category_rewrite;
}

function no_category_base_query_vars( $public_query_vars ) {
	$public_query_vars[] = 'category_redirect';
	return $public_query_vars;
}

/**
 * Handles category redirects.
 *
 * @param $query_vars Current query vars.
 *
 * @return array $query_vars, or void if category_redirect is present.
 */
function no_category_base_request( $query_vars ) {
	if ( isset( $query_vars['category_redirect'] ) ) {
		$catlink = trailingslashit( home_url() ) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
		status_header( 301 );
		header( "Location: $catlink" );
		exit();
	}

	return $query_vars;
}

// Replace avatar url
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

// cache avatar local
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

// Remove post auto <p>
if ( lerm_options( 'post_wpautop' ) ) {
	remove_filter( 'the_content', 'wpautop' );
}

// remove excerpt auto <p>
if ( lerm_options( 'excerpt_wpautop' ) ) {
	remove_filter( 'the_excerpt', 'wpautop' );
}

// Remove comment auto <p>
if ( lerm_options( 'comment_wpautop' ) ) {
	remove_filter( 'comment_text', 'wpautop', 30 );
}

// disable emojis
function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	} else {
		return array();
	}
}

function disable_emojis() {
	global $wp_version;
	if ( $wp_version >= 4.2 ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
	}
}
add_action( 'init', 'disable_emojis' );

// Clean up wp_head() from unused or unsecure stuff
remove_action( 'wp_head', 'wp_generator' );// version info
remove_action( 'wp_head', 'rsd_link' );// offline edit
remove_action( 'wp_head', 'wlwmanifest_link' );// offline edit
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );// context url
remove_action( 'wp_head', 'feed_links', 2 );// comment feed
remove_action( 'wp_head', 'feed_links_extra', 3 );// comment feed
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );// shot link
remove_action( 'wp_head', 'rel_canonical' );
remove_action( 'wp_head', 'wp_resource_hints', 2 );// s.w.org

// Remove WordPress  JS and CSS version info
function lerm_remove_css_and_js_ver( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'lerm_remove_css_and_js_ver', 999 );
add_filter( 'script_loader_src', 'lerm_remove_css_and_js_ver', 999 );

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

// remove_action( 'embed_footer', 'print_embed_sharing_icon' );
add_action( 'embed_footer', 'embed_custom_footer_style' );
function embed_custom_footer_style() { ?>
	<style>
		/* .wp-embed-share {
			display: none;
		} */
	</style>
	<?php
}

// Disable Pingback
function no_self_ping( &$links ) {
	$home = home_url();
	foreach ( $links as $l => $link ) {
		if ( 0 === strpos( $link, $home ) ) {
			unset( $links[ $l ] );
		}
	}
}
add_action( 'pre_ping', 'no_self_ping' );

/**
 * Fully disable wp-json.
 *
 * @since  3.0.0
 * @access public
 * @return void
 */
if ( lerm_options( 'disable_rest_api' ) ) :
	function lerm_disable_rest_api( $access ) {
		return new WP_Error(
			'Stop!',
			'Soooooryyyy',
			array(
				'status' => 403,
			)
		);
	}
	add_filter( 'rest_authentication_errors', 'lerm_disable_rest_api' );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
endif;

/**
 * Remove the default styles that are packaged with the Recent Comments widget.
 *
 * @since Twenty Ten 1.0
 */
function twentyten_remove_recent_comments_style() {
	add_filter( 'show_recent_comments_widget_style', '__return_false' );
}
add_action( 'widgets_init', 'twentyten_remove_recent_comments_style' );

/**
 * Use front-page.php when Front page displays is set to a static page.
 *
 * @since Lerm 3.0
 *
 * @param string $template front-page.php.
 *
 * @return string The template to be used: blank if is_home() is true (defaults to index.php), else $template.
 */
function lerm_front_page_template( $template ) {
	return is_home() ? '' : $template;
}
add_filter( 'frontpage_template', 'lerm_front_page_template' );

/**
 * Wether to show sidebar in webpage.
 *
 * @param string $template front-page.php.
 *
 * @return $layout
 */
function lerm_page_layout() {
	// page or post layout
	$custom_layout = get_post_meta( get_the_ID(), '_lerm_metabox_options', true );
	// global layout
	$global_layout = lerm_options( 'global_layout' );
	// if is mobile
	if ( wp_is_mobile() ) {
		$layout = 'mobile';
	} elseif ( is_home() ) {
		$layout = $global_layout;
	} elseif ( ! empty( $custom_layout['page_layout'] ) ) {
		$layout = $custom_layout['page_layout'];
	} else {
		$layout = $global_layout;
	}
	return $layout;
}
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
require LERM_DIR . 'inc/widgets.php';

