<?php
/**
 * Resolve runtime object context from REST requests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContextResolver {

	/**
	 * @return array<string, int>
	 */
	public static function from_request( \WP_REST_Request $request ): array {
		$raw_context = $request->get_param( 'context' );

		return self::from_array( is_array( $raw_context ) ? $raw_context : self::top_level_context_params( $request ) );
	}

	/**
	 * @param array<string, mixed> $source
	 * @return array<string, int>
	 */
	public static function from_array( array $source ): array {
		$context = array();
		$map     = array(
			'post_id'    => 'post_id',
			'term_id'    => 'term_id',
			'user_id'    => 'user_id',
			'comment_id' => 'comment_id',
			'network_id' => 'network_id',
		);

		foreach ( $map as $source_key => $target_key ) {
			$value = isset( $source[ $source_key ] ) ? absint( $source[ $source_key ] ) : 0;

			if ( $value > 0 ) {
				$context[ $target_key ] = $value;
			}
		}

		return $context;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function top_level_context_params( \WP_REST_Request $request ): array {
		$context = array();

		foreach ( array( 'post_id', 'term_id', 'user_id', 'comment_id', 'network_id' ) as $key ) {
			$value = $request->get_param( $key );

			if ( null !== $value ) {
				$context[ $key ] = $value;
			}
		}

		return $context;
	}
}
