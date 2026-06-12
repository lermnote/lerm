<?php
/**
 * Shared field error matching logic for admin page and container renderers.
 *
 * Delegates core lookup to {@see FieldErrorLookup} while keeping the
 * convenience wrappers that keep call sites clean.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\Support\FieldErrorLookup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait FieldErrorMatcher {

	/**
	 * Get the concatenated error message for a field path.
	 *
	 * @param array<string, mixed> $field_errors Field error map.
	 * @param string               $field_path   Dotted field path.
	 */
	private function field_error_message( array $field_errors, string $field_path ): string {
		return implode( ' ', FieldErrorLookup::messages( $field_errors, $field_path ) );
	}

	/**
	 * Collect all unique error messages for a field path.
	 *
	 * @param array<string, mixed> $field_errors        Field error map.
	 * @param string               $field_path          Dotted field path.
	 * @param bool                 $include_descendants Whether to include child-path errors.
	 * @return array<int, string>
	 */
	private function field_error_messages( array $field_errors, string $field_path, bool $include_descendants = false ): array {
		return FieldErrorLookup::messages( $field_errors, $field_path, $include_descendants );
	}

	/**
	 * Check whether a field path has any errors.
	 *
	 * @param array<string, mixed> $field_errors        Field error map.
	 * @param string               $field_path          Dotted field path.
	 * @param bool                 $include_descendants Whether to include child-path errors.
	 */
	private function field_has_errors( array $field_errors, string $field_path, bool $include_descendants = false ): bool {
		return FieldErrorLookup::has_errors( $field_errors, $field_path, $include_descendants );
	}

	/**
	 * Render the description and error notes below a field control.
	 *
	 * @param string $description Field description text.
	 * @param string $field_error Concatenated error message.
	 */
	private function render_field_notes( string $description, string $field_error ): void {
		if ( $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}

		if ( '' !== $field_error ) {
			printf( '<p class="lerm-field-error" data-lerm-field-error-message>%s</p>', esc_html( $field_error ) );
		}
	}
}
