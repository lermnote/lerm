<?php
/**
 * archives list function
 *
 * @author Lerm http://www.hanost.com
 * @since lerm 2.0
 */
// 文章归档
function lerm_archives_list() {
	$output = '<div id="archives" class="archives-page"><button type="button" class="btn btn-success" id="al_expand_collapse" style="margin-bottom:1rem">全部展开/收缩</button>';

	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => -1,
		'ignore_sticky_posts' => 1,
	);

	$the_query     = new WP_Query( $args );
	$posts_rebuild = array();
	$year          = $mon = 0;

	while ( $the_query->have_posts() ) :
		$the_query->the_post();

		$year  = get_the_date( _x( 'Y', 'yearly archives date format', 'lerm' ) );
		$month = get_the_date( _x( 'm', 'monthly archives date format', 'lerm' ) );
		$day   = get_the_date( _x( 'd', 'daily archives date format', 'lerm' ) );

		$posts_rebuild[ $year ][ $month ][ $day ] = sprintf(
			'<span class="entry-published">%s</span><a href="%s" >%s <span class="badge bg-primary">%s</span></a>',
			$day,
			get_permalink(),
			get_the_title(),
			get_comments_number( '0', '1', '%' )
		);
	endwhile;

	wp_reset_postdata();

	foreach ( $posts_rebuild as $key_y => $y ) {
		$output .= '<h2 class="year-list">' . $key_y . ' 年</h2><ul class="list-unstyled month-list">';
		foreach ( $y as $key_m => $m ) {
			$posts = '';
			$i     = 0;
			foreach ( $m as $p ) {
				++$i;
				$posts .= '<li class="list-group-item d-flex justify-content-between align-items-center archives-post">' . $p . '</li>';
			}
			$output .= sprintf( '<li class="list-item"><span class="month-post-list">%s<label class="badge bg-danger">%s篇文章</label></span><ul class="list-group post-list">%s</ul></li>', $key_m, $i, $posts );
		}
		$output .= '</ul>';
	}
	$output .= '</div>';
	echo $output;
}
