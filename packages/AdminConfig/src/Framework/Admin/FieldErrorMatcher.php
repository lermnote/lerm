<?php
/**
 * Shared field error matching logic for admin page and container renderers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

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
		return implode( ' ', $this->field_error_messages( $field_errors, $field_path ) );
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
		$messages = array();

		foreach ( $field_errors as $path => $raw_messages ) {
			$is_match = (string) $path === $field_path;

			if ( ! $is_match && $include_descendants ) {
				$is_match = '' !== $field_path && str_starts_with( (string) $path, $field_path . '.' );
			}

			if ( ! $is_match ) {
				continue;
			}

			foreach ( is_array( $raw_messages ) ? $raw_messages : array( $raw_messages ) as $message ) {
				$message = is_scalar( $message ) ? trim( (string) $message ) : '';

				if ( '' === $message || in_array( $message, $messages, true ) ) {
					continue;
				}

				$messages[] = $message;
			}
		}

		return $messages;
	}

	/**
	 * Check whether a field path has any errors.
	 *
	 * @param array<string, mixed> $field_errors        Field error map.
	 * @param string               $field_path          Dotted field path.
	 * @param bool                 $include_descendants Whether to include child-path errors.
	 */
	private function field_has_errors( array $field_errors, string $field_path, bool $include_descendants = false ): bool {
		return ! empty( $this->field_error_messages( $field_errors, $field_path, $include_descendants ) );
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
