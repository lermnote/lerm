<?php
/**
 * Related posts show on single bottom;
 *
 * @since 2.0
 */
function lerm_related_posts() {
	$tags        = get_the_tags();
	$tag_id      = '';
	$post_number = lerm_options( 'raleted_number' ) ? lerm_options( 'raleted_number' ) : 5;
	if ( $tags ) {
		foreach ( $tags as $tag ) {
			$tag_id .= $tag->term_id . ',';
		}
	}
	$arg = array(
		'post_status'            => 'publish',
		'tag__in'                => explode( ',', $tag_id ),
		'orderby'                => 'comment_date',
		'posts_per_page'         => $post_number,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);
	$query = new WP_Query( $arg );
	if ( $query->have_posts() ) :
		echo '<section id="related" class="card mb-3">';
		echo '<ul class="list-unstyled card-body m-0">';
		while ( $query->have_posts() ) :
			$query->the_post();
			the_title( '<li class="mb-1"><i class="fa fa-chevron-right me-1"></i><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></li>' );
			endwhile;
		echo '</ul>';
		echo '</section>';
	endif;
	wp_reset_postdata();
}
