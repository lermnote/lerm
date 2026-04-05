<?php // phpcs:disable WordPress.Files.FileName
/**
 * Helpers for working with options page schema definitions.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageSchema {

	/**
	 * Return the sections array from a page definition.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections( array $definition ): array {
		$sections = $definition['sections'] ?? array();

		return is_array( $sections ) ? $sections : array();
	}

	/**
	 * Return default values for all fields.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @return array<string, mixed>
	 */
	public static function defaults( array $definition ): array {
		$defaults = array();

		foreach ( self::fields( $definition ) as $field ) {
			$defaults[ (string) $field['id'] ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * Flatten all fields into a numeric list.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields( array $definition ): array {
		$fields = array();

		foreach ( self::sections( $definition ) as $section ) {
			$section_fields = $section['fields'] ?? array();

			if ( ! is_array( $section_fields ) ) {
				continue;
			}

			foreach ( $section_fields as $field ) {
				if ( is_array( $field ) && isset( $field['id'] ) ) {
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Return a single section by ID.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @param string               $section_id Section ID.
	 * @return array<string, mixed>|null
	 */
	public static function section( array $definition, string $section_id ): ?array {
		$sections = self::sections( $definition );

		return $sections[ $section_id ] ?? null;
	}

	/**
	 * Return a single field by ID.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @param string               $field_id Field ID.
	 * @return array<string, mixed>|null
	 */
	public static function field( array $definition, string $field_id ): ?array {
		foreach ( self::fields( $definition ) as $field ) {
			if ( (string) $field['id'] === $field_id ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Resolve choices for a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, string>
	 */
	public static function choices( array $field ): array {
		$choices = $field['choices'] ?? array();

		if ( is_callable( $choices ) ) {
			$choices = call_user_func( $choices );
		}

		if ( ! is_array( $choices ) ) {
			return array();
		}

		return array_map( 'strval', $choices );
	}
}
