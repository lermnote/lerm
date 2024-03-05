<?php // phpcs:disable WordPress.Files.FileName
/**
 * Load more posts button
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc;

use WP_Query;
use Lerm\Inc\Traits\Ajax;
use Lerm\Inc\Traits\Singleton;
class LoadMore {
	use Ajax;

	use singleton;

	private $query_args;

	public function __construct( $query_args = array() ) {
		$this->register( 'load_more' );

		$default_args =
			array(
				'post_per_page' => get_option( 'posts_per_page' ),
			);

		$this->query_args = wp_parse_args( $query_args, $default_args );
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'ajax_l10n_data' ) );
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
		$postdata             = wp_unslash( $_POST );
		$requested_query_args = json_decode( stripslashes( $postdata['query'] ), true );

		$query_args = array_merge( $this->query_args, $requested_query_args );

		$query_args['paged']       = (int) $postdata['current_page'] + 1;
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

	/**
	 * Generate AJAX localization data.
	 *
	 * This function generates an array of localized data for use in AJAX requests.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {
		global $wp_query;
		$data = array(
			'posts'    => wp_json_encode( $wp_query->query_vars ), // everything about your loop is here.
			'loadmore' => __( 'Load more', 'lerm' ),
			'loading'  => '<i class="fa fa-spinner fa-spin me-1"></i>' . __( 'Loading...', 'lerm' ),
			'noposts'  => __( 'No older posts found', 'lerm' ),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
