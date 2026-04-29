<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Http\Rest\Controllers;

use Lerm\Http\Rest\Middleware;
use Lerm\Http\Rest\Repository\ViewsRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function Lerm\Support\client_ip;

final class ViewsController {

	private static function views_enabled(): bool {
		$options = function_exists( 'lerm_get_template_options' ) ? \lerm_get_template_options() : array();

		return ! isset( $options['post_views_enable'] ) || ! empty( $options['post_views_enable'] );
	}

	private static function unique_views_only(): bool {
		$options = function_exists( 'lerm_get_template_options' ) ? \lerm_get_template_options() : array();

		return ! isset( $options['views_unique_only'] ) || ! empty( $options['views_unique_only'] );
	}

	public static function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::require_published_post( $post_id );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		if ( ! self::views_enabled() ) {
			return new WP_REST_Response( array( 'count' => 0 ), 200 );
		}

		return new WP_REST_Response(
			array( 'count' => ViewsRepository::get_count( $post_id ) ),
			200
		);
	}

	public static function increment( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = absint( $request->get_param( 'id' ) );

		$check = Middleware::chain(
			fn() => Middleware::require_published_post( $post_id ),
			fn() => Middleware::rate_limit( 'views_' . $post_id, 5, 60 )
		);
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		if ( ! self::views_enabled() ) {
			return new WP_Error(
				'views_disabled',
				__( 'View counter is disabled.', 'lerm' ),
				array( 'status' => 403 )
			);
		}

		$client_key = substr( md5( client_ip() . wp_salt() ), 0, 16 );
		$result     = self::unique_views_only()
			? ViewsRepository::record( $post_id, $client_key )
			: array(
				'count'    => ViewsRepository::increment( $post_id ),
				'recorded' => true,
			);

		return new WP_REST_Response( $result, 200 );
	}
}
