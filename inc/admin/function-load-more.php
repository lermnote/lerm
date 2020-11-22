<?php
/**
 * Ajax load more posts handle
 *
 * @package Lerm
 * @author http://www.hanost.com
 * @since lerm 3.1
 */
add_action( 'wp_ajax_lerm_load_more', 'lerm_load_more' );
add_action( 'wp_ajax_nopriv_lerm_load_more', 'lerm_load_more' );

function lerm_load_more() {
	// Check ajax nonce first
	check_ajax_referer( 'ajax_nonce', 'security' );

	$next_page = $_POST['current_page'] + 1;
	$max_pages = $_POST['max_pages'];

	$args  = array(
		'post_per_page' => 10,
		'paged'         => $next_page,
	);
	$query = new WP_Query( $args );

	if ( $query->have_posts() && $next_page <= $max_pages ) :
		ob_start();

		while ( $query->have_posts() ) :
			$query->the_post();
			get_template_part( 'template-parts/content/content', get_post_format() );
		endwhile;

		wp_send_json_success( ob_get_clean() );

		else :

			wp_send_json_error( 'No more posts!' );

	endif;

		wp_die();
}
