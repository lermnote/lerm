<?php // phpcs:disable WordPress.Files.FileName
/**
 * Load more posts button
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc\Ajax;

use WP_Query;
use Lerm\Inc\Traits\Singleton;

final class LoadMore extends BaseAjax {

	use singleton;

	private const ACTION = 'load_more';

	public static $default_args = array(
		'posts_per_page' => 10,
	);

	private static $query_args;

	public function __construct( $query_args = array() ) {
		self::register( self::ACTION, 'ajax_handle', true );

		self::$query_args = wp_parse_args( $query_args, self::$default_args );
		add_filter( 'lerm_l10n_data', array( __CLASS__, 'ajax_l10n_data' ) );
	}

	/** Load more posts on blog page.
	 *
	 * @return void
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'ajax_nonce', 'security', true );
		$postdata = wp_unslash( $_POST );

		$requested_query_args = json_decode( stripslashes( $postdata['query'] ), true );

		$query_args = array_merge( self::$query_args, $requested_query_args );

		$query_args['paged']       = isset( $postdata['current_page'] ) ? (int) $postdata['current_page'] + 1 : 1;
		$query_args['post_status'] = 'publish';

		$posts = new WP_Query( $query_args );

		if ( ! $posts->have_posts() || $query_args['paged'] > $posts->max_num_pages ) {
			self::error( __( 'No more posts!', 'lerm' ) );
		}

		ob_start();
		while ( $posts->have_posts() ) :
			$posts->the_post();
			get_template_part( 'template-parts/content/content', get_post_format() );
		endwhile;
		wp_reset_postdata();

		$content = ob_get_clean();

		self::success( $content );
	}
		/**
		 * Generate AJAX localization data.
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
