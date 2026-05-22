<?php
/**
 * Shared nested container sanitization helpers.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NestedFieldSanitizer {

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 * @return array<string, mixed>
	 */
	public static function sanitize_fieldset( array $field, $value, bool $strict, OptionStore $store, string $base_path = '' ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$data   = is_array( $value ) ? $value : array();

		return self::sanitize_child_fields( $fields, $data, $strict, $store, $store->field_container_path( $field, $base_path ) );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_group( array $field, $value, bool $strict, OptionStore $store, string $base_path = '' ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$items  = is_array( $value ) ? array_values( $value ) : array();
		$clean  = array();
		$path   = $store->field_container_path( $field, $base_path );

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized = self::sanitize_child_fields( $fields, $item, $strict, $store, self::compose_path( $path, (string) $index ) );

			if ( self::nested_values_empty( $sanitized ) ) {
				continue;
			}

			$clean[] = $sanitized;
		}

		return $clean;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @param array<string, mixed>             $submitted
	 * @return array<string, mixed>
	 */
	private static function sanitize_child_fields( array $fields, array $submitted, bool $strict, OptionStore $store, string $base_path = '' ): array {
		$clean = array();

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id           = (string) $child['id'];
			$clean[ $child_id ] = $store->sanitize_nested_field(
				$child,
				$submitted[ $child_id ] ?? null,
				$strict,
				self::compose_path( $base_path, $child_id )
			);
		}

		return $clean;
	}

	private static function compose_path( string $base_path, string $segment ): string {
		if ( '' === $segment ) {
			return $base_path;
		}

		if ( '' === $base_path ) {
			return $segment;
		}

		return $base_path . '.' . $segment;
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private static function nested_values_empty( array $values ): bool {
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				if ( ! empty( $value ) && ! self::nested_values_empty( $value ) ) {
					return false;
				}

				continue;
			}

			if ( is_bool( $value ) ) {
				if ( $value ) {
					return false;
				}

				continue;
			}

			if ( '' !== PageSchema::scalar_value( $value, '', true ) ) {
				return false;
			}
		}

		return true;
	}
}
