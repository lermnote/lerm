<?php
/**
 * REST response helpers mirroring the legacy AJAX response shape.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResponseFactory {

	/**
	 * @param array<string, mixed> $data
	 */
	public static function success( array $data = array(), int $status = 200 ): \WP_REST_Response {
		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
		$response->set_status( $status );

		return $response;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function error( string $code, string $message, int $status, array $data = array() ): \WP_Error {
		return new \WP_Error(
			$code,
			$message,
			array_merge(
				array(
					'status'  => $status,
					'success' => false,
					'data'    => array_merge(
						array(
							'message' => $message,
						),
						$data
					),
				),
				$data
			)
		);
	}
}
