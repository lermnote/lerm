<?php
/**
* @authors lerm http://lerm.net
* @date    2018-03-18
* @since   lerm 3.0
*
* shortcode
*/
//show blogroll on page;
function links_list_shortcode( $atts, $content = null ) {
	extract(
		shortcode_atts(
			array(
				'page_type' => 'links',
			),
			$atts,
			'links_list'
		)
	);
	$output  = '<div>';
	$output .= wp_list_bookmarks(
		array(
			'before'           => '<li class="list-inline-item border p-2 blogroll-item">',
			'after'            => '</li>',
			'categorize'       => 1,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'show_images'      => true,
			'show_name'        => true,
			'title_before'     => '<h2>',
			'title_after'      => '</h2>',
			'category_orderby' => 'name',
			'category_order'   => 'ASC',
			'class'            => 'linkcat',
			'category_before'  => '<div id=%id class=%class>',
			'category_after'   => '</div>',
			'link_before'      => '',
			'link_after'       => '',
			'between'          => '',
			'echo'             => 0,
		)
	);
	$output .= '</div>';
	return $output;
}
add_shortcode( 'links_list', 'links_list_shortcode' );

// show recent posts on certain page
function recent_posts_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'posts' => '5',
		),
		$atts,
		'recent-posts'
	);

	$the_query = new WP_Query( array( 'posts_per_page' => $atts['posts'] ) );

	$output = '<ul>';
	while ( $the_query->have_posts() ) :
		$the_query->the_post();
		$output .= '<li>' . get_the_title() . '</li>';
	endwhile;
	$output .= '</ul>';

	wp_reset_postdata();

	return $output;
}
add_shortcode( 'recent-posts', 'recent_posts_shortcode' );

// show archies on page
function archives_list_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'title' => '',
		),
		$atts,
		'archives_list'
	);

	echo $atts['title'] . '<br />' . lerm_archives_list();
}
add_shortcode( 'archives_list', 'archives_list_shortcode' );

//use bing background image on some pages

function bing_image_shortcode( $atts, $content = null ) {
	$url        = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=';
	$resolution = '1920x1080';
	$request    = wp_remote_get( $url );
	$data       = wp_remote_retrieve_body( $request );
	$json       = json_decode( trim( $data ), true );
	if ( $json ) {
		$images = $json['images'];
		foreach ( $images as $image ) {
			$urlbase   = $image['urlbase'];
			$image_url = 'https://www.bing.com' . $urlbase . '_' . $resolution . '.jpg';
		}
	}
	wp_upload_bits();
	return sprintf( '<img src="%1$s" alt="bing image">', $image_url );
}
add_shortcode( 'bing_image', 'bing_image_shortcode' );

//code highlight
if ( $lerm['enable_code_highlight'] ) :
	//if  cs_get_option('enable_code_highlight') is true

	function code_shortcode( $atts, $content = '' ) {
		$atts    = shortcode_atts(
			array(
				'lang'         => '',
				'line_numbers' => '',
			),
			$atts,
			'code'
		);
		$output  = '<pre class="prettyprint ' . $atts['line_numbers'] . ' lang-' . $atts['lang'] . '">';
		$output .= $content;
		$output .= '</pre>';
		return $output;
	}
	add_shortcode( 'code', 'code_shortcode' );
endif;
