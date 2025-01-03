<?php // phpcs:disable WordPress.Files.FileName
/**
 * Load more posts Ajax handler.
 *
 * @package Lerm\Inc
 */

namespace Lerm\Inc\Ajax;

use WP_Query;
use Lerm\Inc\Traits\Singleton;

final class LoadMore extends BaseAjax {
	use singleton;

	protected const AJAX_ACTION = 'load_more';

	public static $args = array(
		'posts_count' => 10,
	);

	public function __construct( $params = array() ) {
		parent::__construct( apply_filters( 'lerm_loadmore_args', wp_parse_args( $params, self::$args ) ) );
	}

	/**
	 * Load more posts on blog page.
	 *
	 * @return void
	 */
	public static function ajax_handle() {
		check_ajax_referer( 'ajax_nonce', 'security', true );

		$postdata = wp_unslash( $_POST );

		if ( ! isset( $postdata['archive'], $postdata['currentPage'] ) ) {
			self::error( __( 'Invalid request data', 'lerm' ) );
		}
		$query_args = json_decode( stripslashes( $postdata['archive'] ), true );

		if ( ! is_array( $query_args ) ) {
			self::error( __( 'Invalid query parameters', 'lerm' ) );
		}

		$load_count = min( self::$args['posts_count'], get_option( 'posts_per_page' ) );

		$query_args = array_merge(
			$query_args,
			array(
				'post_type'      => 'post',
				'posts_per_page' => $load_count,
				'paged'          => (int) $postdata['currentPage'] + 1,
				'post_status'    => 'publish',
			)
		);
		$posts      = new WP_Query( $query_args );

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

		self::success(
			array(
				'content'     => $content,
				'maxPage'     => $posts->max_num_pages,
				'currentPage' => $query_args['paged'],
				'nextPage'    => $query_args['paged'] + 1,
				'hasMore'     => $query_args['paged'] < $posts->max_num_pages,
			)
		);
	}

	/**
	 * Generate AJAX localization data.
	 *
	 * @param array $l10n Existing localization data.
	 * @return array Localized data for AJAX requests.
	 */
	public static function ajax_l10n_data( $l10n ) {
		$data = array(
			'loadmore' => __( 'Load more', 'lerm' ),
			'loading'  => '<i class="li li-spinner me-1"></i>' . __( 'Loading...', 'lerm' ),
			'noposts'  => __( 'No older posts found', 'lerm' ),
		);
		$data = wp_parse_args( $data, $l10n );
		return $data;
	}
}
