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

	$args = json_decode( stripslashes( $_POST['query'] ), true );

	$args['paged'] = $_POST['page'] + 1;

	$args['post_status'] = 'publish';
	query_posts( $args );

	if (have_posts() ) :
		while (have_posts() ) :

			the_post();

			get_template_part( '/template/content/content', 'excerpt' );

		endwhile;
	endif;
	wp_reset_postdata();
	wp_die();
}
