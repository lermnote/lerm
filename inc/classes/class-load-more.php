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

	private $query_args;

	public function __construct( $query_args = array() ) {
		$this->register( 'load_more' );

		$default_args =
			array(
				'post_per_page' => get_option( 'posts_per_page' ),
			);

		$this->query_args = wp_parse_args( $query_args, $default_args );
	}

	public static function instance( $query_args = array() ) {
		return new self( $query_args );
	}

	/** Load more posts on blog page.
	 *
	 * @return void
	 */
	public function load_more() {
		$nonce = sanitize_text_field( $_POST['security'] );
		if ( ! wp_verify_nonce( $nonce, 'ajax_nonce' ) ) {
			$this->error( __( 'Invalid nonce', 'lerm' ) );
		}

		$requested_query_args = json_decode( stripslashes( $_POST['query'] ), true );

		$query_args = array_merge( $this->query_args, $requested_query_args );

		$query_args['paged']       = (int) $_POST['current_page'] + 1;
		$query_args['post_status'] = 'publish';

		$posts = new WP_Query( $query_args );

		if ( ! $posts->have_posts() || $query_args['paged'] > $posts->max_num_pages ) {
			$this->error( __( 'No more posts!', 'lerm' ) );
		}

		ob_start();
		while ( $posts->have_posts() ) :
			$posts->the_post();
			get_template_part( 'template-parts/content/content', get_post_format() );
		endwhile;
		$content = ob_get_clean();
		$this->success( $content );
	}
}
