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

	public function __construct( $args = array() ) {
		$this->register( 'load_more' );
		$default = array(
			'post_per_page' => get_option( 'posts_per_page' ),
		);
		$args    = wp_parse_args( $args, $default );
	}

	/**
	 * Load more posts on blog page.
	 *
	 * @return void
	 */
	public function load_more() {
		$this->verify_nonce( 'ajax_nonce' );

		$this->success( $this->render() );

		$this->error( 'No more posts!' );
	}

	/**
	 * Render the html
	 *
	 * @return string|false|void
	 */
	public function render() {
		check_ajax_referer( 'ajax_nonce', 'security' );

		$next_page = $_POST['current_page'] + 1;
		$max_pages = $_POST['max_pages'];

		$args = array(
			'post_per_page' => get_option( 'posts_per_page' ),
			'paged'         => $next_page,
			'post_status'   => 'publish',
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() && $next_page <= $max_pages ) {
			ob_start();

			while ( $query->have_posts() ) :

				$query->the_post();
				get_template_part( 'template-parts/content/content', get_post_format() );

			endwhile;

			return ob_get_clean();
		}
	}
}
