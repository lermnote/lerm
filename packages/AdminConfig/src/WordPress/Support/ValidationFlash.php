<?php
/**
 * Shared validation flash helpers for native WordPress admin containers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Support\PageSchema;

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
	 * Merge flashed submission data back into saved values for re-rendering.
	 *
	 * Some controls intentionally submit no key when emptied (for example
	 * multi-selects, checkbox lists, or an emptied group). A plain
	 * `wp_parse_args()` merge would resurrect the last saved value after a
	 * validation failure, so we replay those omissions as their empty state
	 * instead when a FieldTypeRegistry and schema definition are available.
	 *
	 * @param array<string, mixed>      $values
	 * @param array<string, mixed>|null $flash
	 * @param array<string, mixed>|null $definition   Schema definition for field lookup.
	 * @param FieldTypeRegistry|null    $field_types   Registry for missing_submission callbacks.
	 * @return array<string, mixed>
	 */
	public static function render_values( array $values, ?array $flash, ?array $definition = null, ?FieldTypeRegistry $field_types = null ): array {
		$submitted = is_array( $flash['submitted'] ?? null ) ? $flash['submitted'] : array();

		if ( null === $definition || null === $field_types ) {
			return wp_parse_args( $submitted, $values );
		}

		$fields = self::collect_definition_fields( $definition );
		$merged = $values;

		foreach ( $fields as $field_id => $field ) {
			if ( array_key_exists( $field_id, $submitted ) ) {
				$merged[ $field_id ] = $submitted[ $field_id ];
				continue;
			}

			$missing = self::missing_submission_render_value( $field, $field_types );

			if ( $missing['apply'] ) {
				$merged[ $field_id ] = $missing['value'];
			}
		}

		// Any submitted keys that are not known fields (e.g. dynamic groups) still win.
		return wp_parse_args( $submitted, $merged );
	}

	/**
	 * Collect all top-level field definitions from a schema, including
	 * fields nested inside section groups.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, array<string, mixed>>
	 */
	private static function collect_definition_fields( array $definition ): array {
		$fields = array();

		foreach ( PageSchema::sections( $definition ) as $section ) {
			foreach ( PageSchema::section_fields( $section ) as $field ) {
				if ( is_array( $field ) && isset( $field['id'] ) ) {
					$fields[ (string) $field['id'] ] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Resolve the empty-state value for a field that was absent from the submission.
	 *
	 * Mirrors the logic in OptionsPage::missing_submission_render_value so
	 * containers (metabox, taxonomy, profile, comment) get the same behavior.
	 *
	 * @param array<string, mixed> $field
	 * @param FieldTypeRegistry    $field_types
	 * @return array{apply: bool, value: mixed}
	 */
	private static function missing_submission_render_value( array $field, FieldTypeRegistry $field_types ): array {
		if ( array_key_exists( 'missing_submission_value', $field ) ) {
			return array(
				'apply' => true,
				'value' => $field['missing_submission_value'],
			);
		}

		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$callback = $field_types->missing_submission_callback( $type );

		if ( is_callable( $callback ) ) {
			$missing = call_user_func( $callback, $field );

			if ( is_array( $missing ) ) {
				return array(
					'apply' => ! empty( $missing['apply'] ),
					'value' => $missing['value'] ?? null,
				);
			}
		}

		return array(
			'apply' => false,
			'value' => null,
		);
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
