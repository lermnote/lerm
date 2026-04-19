<?php
/**
 * Shared validation flash helpers for native WordPress admin containers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ValidationFlash {

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function store( string $scope, string $schema_id, string $resource_key, array $payload ): void {
		set_transient( self::key( $scope, $schema_id, $resource_key ), $payload, MINUTE_IN_SECONDS );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function consume( string $scope, string $schema_id, string $resource_key ): ?array {
		$flash = get_transient( self::key( $scope, $schema_id, $resource_key ) );

		if ( ! is_array( $flash ) ) {
			return null;
		}

		delete_transient( self::key( $scope, $schema_id, $resource_key ) );

		return $flash;
	}

	public static function clear( string $scope, string $schema_id, string $resource_key ): void {
		delete_transient( self::key( $scope, $schema_id, $resource_key ) );
	}

	/**
	 * Collapse dotted nested error paths into top-level field messages.
	 *
	 * @param array<string, array<int, string>> $errors
	 * @return array<string, string>
	 */
	public static function collapse_errors( array $errors ): array {
		$collapsed = array();

		foreach ( $errors as $path => $messages ) {
			if ( ! is_array( $messages ) || empty( $messages ) ) {
				continue;
			}

			$field_id = sanitize_key( (string) strtok( (string) $path, '.' ) );
			$message  = trim( implode( ' ', array_filter( array_map( 'strval', $messages ) ) ) );

			if ( '' === $field_id || '' === $message || isset( $collapsed[ $field_id ] ) ) {
				continue;
			}

			$collapsed[ $field_id ] = $message;
		}

		return $collapsed;
	}

	/**
	 * @param array<string, mixed>      $values
	 * @param array<string, mixed>|null $flash
	 * @return array<string, mixed>
	 */
	public static function render_values( array $values, ?array $flash ): array {
		$submitted = is_array( $flash['submitted'] ?? null ) ? $flash['submitted'] : array();

		return wp_parse_args( $submitted, $values );
	}

	/**
	 * @param array<string, mixed>|null $flash
	 * @return array<string, mixed>
	 */
	public static function field_errors( ?array $flash ): array {
		if ( ! is_array( $flash ) || ! is_array( $flash['errors'] ?? null ) ) {
			return array();
		}

		return (array) $flash['errors'];
	}

	/**
	 * @param array<string, mixed>|null $flash
	 * @return array{class: string, message: string}|null
	 */
	public static function notice( ?array $flash ): ?array {
		if ( ! is_array( $flash ) ) {
			return null;
		}

		$message = isset( $flash['message'] ) && is_scalar( $flash['message'] ) ? trim( (string) $flash['message'] ) : '';
		$class   = isset( $flash['class'] ) && is_scalar( $flash['class'] ) ? trim( (string) $flash['class'] ) : 'notice-error';

		if ( '' === $message ) {
			return null;
		}

		return array(
			'class'   => '' !== $class ? $class : 'notice-error',
			'message' => $message,
		);
	}

	private static function key( string $scope, string $schema_id, string $resource_key ): string {
		return 'lerm_admin_config_flash_' . md5(
			$scope
			. ':'
			. $schema_id
			. ':'
			. $resource_key
			. ':'
			. (string) get_current_user_id()
		);
	}
}
