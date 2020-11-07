<?php
/**
 * Set title separator, default "|"
 *
 * @since  1.0.0
 * @return  string title_sepa
 */
function lerm_title_separator() {
	return lerm_options( 'title_sepa' ) ? lerm_options( 'title_sepa' ) : '|';
}
add_filter( 'document_title_separator', 'lerm_title_separator' );

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
 * Remove WordPress  JS and CSS version info
 *
 * @return sring $src
 */
function lerm_remove_css_and_js_ver( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'lerm_remove_css_and_js_ver', 999 );
add_filter( 'script_loader_src', 'lerm_remove_css_and_js_ver', 999 );

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
 * Displays the optional excerpt.
 *
 * @since Lerm 2.0
 */
function lerm_excerpt_length( $length ) {
	$length = lerm_options( 'excerpt_length' );
	return $length;
}
add_filter( 'excerpt_length', 'lerm_excerpt_length', 999 );


/**
 * Use btn btn-sm btn-custom class replace comment-reply-link class.
 *
 * @since Lerm 3.0
 *
 * @param string
 *
 * @return string $class;
 */

function replace_reply_link_class( $class ) {
	$class = str_replace( "class='comment-reply-link", "class='btn btn-sm btn-custom", $class );
	return $class;
}
add_filter( 'comment_reply_link', 'replace_reply_link_class' );

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
// code highlight deprecate
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
