<?php
/**
 * Load more posts button
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use Lerm\Inc\Traits\Ajax;
use WP_Query;

class Load_More {

	use Ajax;

	public static $args = array();

	public function __construct( $args = array() ) {
		$this->register( 'load_more' );
		$default = array(
			'post_per_page' => get_option( 'posts_per_page' ),
		);
		$args    = wp_parse_args( $args, $default );
	}

	// instance
	public static function instance( $params = array() ) {
		return new self( $params );
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

		// prepare our arguments for the query
		$args                = json_decode( stripslashes( $_POST['query'] ), true );
		$args['paged']       = $_POST['current_page'] + 1; // we need next page to be loaded
		$args['post_status'] = 'publish';

		// it is always better to use WP_Query but not here
		query_posts( $args );

		if ( have_posts() && $next_page <= $max_pages ) {
			ob_start();

			while ( have_posts() ) :

				the_post();
				get_template_part( 'template-parts/content/content', get_post_format() );

			endwhile;

			return ob_get_clean();
		}
	}
}
