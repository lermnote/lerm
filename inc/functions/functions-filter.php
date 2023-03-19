<?php

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
