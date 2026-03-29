<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\SearchRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Live search endpoint controller.
 *
 * @package Lerm\Http\Rest\Controllers
 */
final class SearchController {

	/**
	 * Handle a search request.
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$check = Middleware::rate_limit( 'search', 30 );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$keyword   = (string) $request->get_param( 'q' );
		$post_type = (string) $request->get_param( 'post_type' );
		$per_page  = absint( $request->get_param( 'per_page' ) );

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'Invalid post type.', 'lerm' ),
				array( 'status' => 400 )
			);
		}

		if ( mb_strlen( $keyword ) < 2 ) {
			return new WP_REST_Response(
				array(
					'results' => array(),
					'total'   => 0,
				),
				200
			);
		}

		$result = SearchRepository::search( $keyword, $post_type, $per_page );

		$safe_kw = preg_quote( $keyword, '/' );
		$results = array_map(
			static function ( array $item ) use ( $safe_kw ): array {
				$item['title'] = preg_replace(
					'/(' . $safe_kw . ')/iu',
					'<mark>$1</mark>',
					esc_html( $item['title'] )
				);

				return $item;
			},
			$result['items']
		);

		add_action(
			'shutdown',
			static function () use ( $keyword ) {
				SearchRepository::record_keyword( $keyword );
			}
		);

		return new WP_REST_Response(
			array(
				'results' => $results,
				'total'   => $result['total'],
			),
			200
		);
	}
}
