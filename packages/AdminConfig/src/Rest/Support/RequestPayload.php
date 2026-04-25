<?php
/**
 * Helpers for extracting REST request payloads.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RequestPayload {

	/**
	 * @return array<string, mixed>
	 */
	public static function values( \WP_REST_Request $request, string $storage_key ): array {
		$json = $request->get_json_params();

		if ( is_array( $json ) && isset( $json['values'] ) && is_array( $json['values'] ) ) {
			return $json['values'];
		}

		$values = $request->get_param( 'values' );

		if ( is_array( $values ) ) {
			return $values;
		}

		$stored = $request->get_param( $storage_key );

		return is_array( $stored ) ? $stored : array();
	}

	public static function string( \WP_REST_Request $request, string $key, string $fallback = '' ): string {
		$value = $request->get_param( $key );

		return is_scalar( $value ) ? trim( (string) $value ) : $fallback;
	}
}
