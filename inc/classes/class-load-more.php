<?php
/**
 * Load more posts button
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Ajax;
use Lerm\Inc\Traits\Singleton;
use WP_Query;

class Load_More {

	use Ajax, Singleton;

	public function __construct() {
		$this->register( 'load_more' );
	}
	public function load_more( $action ) {
		$this->verify_nonce( 'ajax_nonce' );
		check_ajax_referer( 'ajax_nonce', 'security' );

		$this->success( $this->pagination() );

		$this->error( 'No more posts!' );
	}

	public function pagination() {

		$next_page = $_POST['current_page'] + 1;
		$max_pages = $_POST['max_pages'];

		$args  = array(
			'post_per_page' => 10,
			'paged'         => $next_page,
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() && $next_page <= $max_pages ) {
			ob_start();
			while ( $query->have_posts() ) :
				$query->the_post();
				get_template_part( 'template-parts/content/content', get_post_format() );
			endwhile;
			ob_get_clean();
		}
	}
}
