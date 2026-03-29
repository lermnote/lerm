<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * Response helpers for REST and AJAX endpoints.
 *
 * @package Lerm\Support
 */
final class Response {

	/**
	 * Return a generic 200 response.
	 */
	public static function ok( mixed $data = null ): \WP_REST_Response {
		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Return a 201 Created response.
	 */
	public static function created( mixed $data = null, string $location = '' ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data, 201 );

		if ( '' !== $location ) {
			$response->header( 'Location', $location );
		}

		return $response;
	}

	/**
	 * Return a 204 No Content response.
	 */
	public static function no_content(): \WP_REST_Response {
		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Return a generic error object.
	 */
	public static function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Return a 400 Bad Request error.
	 */
	public static function bad_request( string $message, string $code = 'bad_request' ): \WP_Error {
		return self::error( $code, $message, 400 );
	}

	/**
	 * Return a 401 Unauthorized error.
	 */
	public static function unauthorized( string $message = '' ): \WP_Error {
		return self::error(
			'unauthorized',
			'' !== $message ? $message : __( 'Please log in first.', 'lerm' ),
			401
		);
	}

	/**
	 * Return a 403 Forbidden error.
	 */
	public static function forbidden( string $message = '' ): \WP_Error {
		return self::error(
			'forbidden',
			'' !== $message ? $message : __( 'You do not have permission to perform this action.', 'lerm' ),
			403
		);
	}

	/**
	 * Return a 404 Not Found error.
	 */
	public static function not_found( string $message = '', string $code = 'not_found' ): \WP_Error {
		return self::error(
			$code,
			'' !== $message ? $message : __( 'Resource not found.', 'lerm' ),
			404
		);
	}

	/**
	 * Return a 422 Unprocessable Entity error.
	 */
	public static function unprocessable( string $message, string $code = 'unprocessable_entity' ): \WP_Error {
		return self::error( $code, $message, 422 );
	}

	/**
	 * Return a 429 Too Many Requests error.
	 */
	public static function too_many_requests( string $message = '' ): \WP_Error {
		return self::error(
			'rate_limited',
			'' !== $message ? $message : __( 'Too many requests. Please try again later.', 'lerm' ),
			429
		);
	}

	/**
	 * Return a 500 Internal Server Error.
	 */
	public static function server_error( string $message = '', string $code = 'internal_server_error' ): \WP_Error {
		return self::error(
			$code,
			'' !== $message ? $message : __( 'An internal server error occurred.', 'lerm' ),
			500
		);
	}
}
