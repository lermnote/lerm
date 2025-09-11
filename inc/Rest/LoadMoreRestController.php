<?php // phpcs:disable WordPress.Files.FileName
/**
 * REST API controller for "Load More" posts functionality.
 *
 * @package Lerm\Inc\Rest
 */

declare(strict_types=1);

namespace Lerm\Inc\Rest;

use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Lerm\Inc\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LoadMoreRestController
 *
 * Provides a REST API endpoint for loading additional posts via pagination.
 * This replaces the legacy admin-ajax.php based implementation.
 */
final class LoadMoreRestController extends BaseRestController {
	use Singleton;

	/**
	 * REST API route slug.
	 */
	protected const ROUTE = 'load_more';

	/**
	 * Public access flag.
	 *
	 * Indicates this endpoint is available without authentication,
	 * but requires a valid nonce.
	 */
	protected const PUBLIC = true;

	/**
	 * Default query arguments.
	 *
	 * @var array<string, int>
	 */
	protected static array $args = array(
		'posts_count' => 10,
	);

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Optional arguments to override defaults.
	 */
	public function __construct() {
		//self::$args = apply_filters( 'lerm_loadmore_args', wp_parse_args( $params, self::$args ) );
		parent::__construct();
	}

	/**
	 * Permission check — keep legacy nonce 'like_nonce' for backward compatibility.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return true|WP_Error
	 */
	public function permission_check( $request ): bool|WP_Error {
		$nonce = $request->get_header( 'x-wp-nonce' ) ?: $request->get_param( self::NONCE_FIELD );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'lerm' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Handle the "load more" request.
	 *
	 * Expects:
	 *  - `archive` (JSON string of WP_Query arguments)
	 *  - `currentPage` (int: current page number)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error REST response or error.
	 */
	public function create_item( $request ) {
		$postdata = wp_unslash( (array) ( $request->get_body_params() ?: $request->get_params() ) );

		if ( ! isset( $postdata['archive'], $postdata['currentPage'] ) ) {
			return $this->error( __( 'Invalid request data', 'lerm' ), 400, 'invalid_request' );
		}

		$query_args = json_decode( stripslashes( (string) $postdata['archive'] ), true );

		if ( ! is_array( $query_args ) ) {
			return $this->error( __( 'Invalid query parameters', 'lerm' ), 400, 'invalid_query' );
		}

		$load_count = min( self::$args['posts_count'], (int) get_option( 'posts_per_page' ) );

		$query_args = array_merge(
			$query_args,
			array(
				'post_type'      => 'post',
				'posts_per_page' => $load_count,
				'paged'          => (int) $postdata['currentPage'] + 1,
				'post_status'    => 'publish',
			)
		);

		$posts = new WP_Query( $query_args );

		if ( ! $posts->have_posts() || $query_args['paged'] > $posts->max_num_pages ) {
			return $this->error( __( 'No more posts!', 'lerm' ), 404, 'no_more_posts' );
		}

		ob_start();

		while ( $posts->have_posts() ) {
			$posts->the_post();
			get_template_part( 'template-parts/content/content', get_post_format() );
		}

		wp_reset_postdata();

		$content = ob_get_clean();

		$data = array(
			'content'     => $content,
			'maxPage'     => $posts->max_num_pages,
			'currentPage' => $query_args['paged'],
			'nextPage'    => $query_args['paged'] + 1,
			'hasMore'     => $query_args['paged'] < $posts->max_num_pages,
		);

		return $this->success( $data );
	}

	/**
	 * Provide localized strings for the load more UI.
	 *
	 * @param array<string, mixed> $l10n Existing localization data.
	 * @return array<string, mixed> Merged localization data.
	 */
	public static function rest_l10n_data( $l10n ) {
		$l10n = parent::rest_l10n_data( $l10n );
		$data = array(
			'loadmore_action' => self::ROUTE,
			'loadmore'        => __( 'Load more', 'lerm' ),
			'loading'         => '<i class="li li-spinner me-1"></i>' . __( 'Loading...', 'lerm' ),
			'noposts'         => __( 'No older posts found', 'lerm' ),

		);
		return wp_parse_args( $data, $l10n );
	}
}
