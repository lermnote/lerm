<?php
/**
 * Related posts show on single bottom;
 *
 * @package Lerm https://lerm.net
 *
 * @since 2.0
 */
$tags = get_the_tags();
if ( ! $tags ) {
	return;
}

$tag_ids = wp_list_pluck( $tags, 'term_id' );
$number  = lerm_options( 'related_number' ) ? lerm_options( 'related_number' ) : 5;

$args = array(
	'post_status'            => 'publish',
	'tag__in'                => $tag_ids,
	'orderby'                => 'comment_date',
	'posts_per_page'         => $number,
	'no_found_rows'          => true,
	'update_post_meta_cache' => false,
	'update_post_term_cache' => false,
);

$query = new WP_Query( $args );
if ( $query->have_posts() ) :?>
	<section id="related" class="card mb-3">
	<ul class="list-unstyled card-body m-0">
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			printf(
				'<li class="mb-1"><i class="li li-chevron-right me-1"></i><a href="%s" rel="bookmark">%s</a></li>',
				esc_url( get_permalink() ),
				esc_html( get_the_title() )
			);
		}
		?>
	</ul>
	</section>
	<?php
endif;
wp_reset_postdata();
