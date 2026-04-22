<?php // phpcs:disable WordPress.Files.FileName
/**
 * Helpers for working with options page schema definitions.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

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
	 * Fields with duplicate IDs across sections are skipped after the first
	 * occurrence; a _doing_it_wrong() notice is emitted in debug mode so
	 * theme/plugin authors catch the problem early.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields( array $definition ): array {
		$fields = array();
		$seen   = array();

		foreach ( self::sections( $definition ) as $section_id => $section ) {
			foreach ( self::section_fields( $section ) as $field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
					continue;
				}

				$field_id = (string) $field['id'];

				if ( isset( $seen[ $field_id ] ) ) {
					if ( defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false ) {
						_doing_it_wrong(
							__METHOD__,
							sprintf(
								/* translators: 1: field ID, 2: first section, 3: duplicate section */
								'Admin Config: field ID "%1$s" is declared in both section "%2$s" and "%3$s". The second declaration is ignored.',
								esc_html( $field_id ),
								esc_html( $seen[ $field_id ] ),
								esc_html( (string) $section_id )
							),
							'1.0.0'
						);
					}

					continue;
				}

				$seen[ $field_id ] = (string) $section_id;
				$fields[]          = $field;
			}
		}

		return $fields;
	}

	/**
	 * Return all fields declared inside a section.
	 *
	 * Sections with explicit subsection groups read fields only from
	 * `groups[*].fields`. Sections without subsection groups continue to use
	 * the top-level `fields` list.
	 *
	 * @param array<string, mixed> $section Section definition.
	 * @return array<int, array<string, mixed>>
	 */
	public static function section_fields( array $section ): array {
		$groups = self::declared_section_groups( $section );

		if ( empty( $groups ) ) {
			return self::normalize_fields_list( $section['fields'] ?? array() );
		}

		$fields = array();
		$seen   = array();

		foreach ( $groups as $group ) {
			$group_fields = self::normalize_fields_list( $group['fields'] ?? array() );

			foreach ( $group_fields as $field ) {
				if ( ! isset( $field['id'] ) ) {
					continue;
				}

				$field_id = (string) $field['id'];

				if ( isset( $seen[ $field_id ] ) ) {
					continue;
				}

				$seen[ $field_id ] = true;
				$fields[]          = $field;
			}
		}

		return $fields;
	}

	/**
	 * Return normalized subsection groups for a section.
	 *
	 * @param array<string, mixed> $section Section definition.
	 * @return array<int, array<string, mixed>>
	 */
	public static function section_groups( array $section ): array {
		$groups = self::declared_section_groups( $section );

		if ( empty( $groups ) ) {
			return array(
				array(
					'id'     => 'general',
					'label'  => (string) __( 'General', 'lerm' ),
					'fields' => self::section_fields( $section ),
				),
			);
		}

		return array_values( $groups );
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

		$normalized = array();

		foreach ( $choices as $key => $label ) {
			if ( ! is_scalar( $key ) || ! is_scalar( $label ) ) {
				continue;
			}

			$normalized[ (string) $key ] = (string) $label;
		}

		return $normalized;
	}

	/**
	 * Safely normalize any scalar-like value to a string.
	 *
	 * Canonical implementation shared by OptionStore and OptionsPage.
	 * Both classes previously had their own copy under different names
	 * (string_value / scalar_string); both now delegate here.
	 *
	 * @param mixed  $value   Source value (may be array, null, scalar, etc.).
	 * @param string $fallback Fallback when $value is not scalar.
	 * @param bool   $trim    Whether to trim the result.
	 */
	public static function scalar_value( $value, string $fallback = '', bool $trim = false ): string {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$string = (string) $value;

		return $trim ? trim( $string ) : $string;
	}

	/**
	 * Normalize a field list to valid field definitions only.
	 *
	 * @param mixed $fields Source field list.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_fields_list( $fields ): array {
		static $reported_invalid_field_definition = false;

		if ( ! is_array( $fields ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['id'] ) ) {
				$normalized[] = $field;
				continue;
			}

			if ( ( defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false ) && ! $reported_invalid_field_definition ) {
				_doing_it_wrong(
					__METHOD__,
					'Admin Config field definitions must be arrays with a non-empty "id". Invalid entries are ignored.',
					'0.2.0'
				);
				$reported_invalid_field_definition = true;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize explicit subsection group definitions.
	 *
	 * @param array<string, mixed> $section Section definition.
	 * @return array<string, array<string, mixed>>
	 */
	private static function declared_section_groups( array $section ): array {
		$declared = $section['groups'] ?? array();

		if ( ! is_array( $declared ) ) {
			return array();
		}

		$groups = array();

		foreach ( $declared as $index => $group ) {
			$group_id     = '';
			$group_label  = '';
			$group_fields = array();

			if ( is_scalar( $group ) ) {
				$group_label = trim( (string) $group );
			} elseif ( is_array( $group ) ) {
				$group_id     = isset( $group['id'] ) && is_scalar( $group['id'] ) ? sanitize_title( (string) $group['id'] ) : '';
				$group_label  = trim( (string) ( $group['label'] ?? '' ) );
				$group_fields = self::normalize_fields_list( $group['fields'] ?? array() );
			}

			if ( '' === $group_label && '' === $group_id ) {
				continue;
			}

			$group_id = '' !== $group_id ? $group_id : sanitize_title( $group_label );
			$group_id = '' !== $group_id ? $group_id : 'group-' . (string) ( (int) $index + 1 );

			while ( isset( $groups[ $group_id ] ) ) {
				$group_id .= '-2';
			}

			$groups[ $group_id ] = array(
				'id'     => $group_id,
				'label'  => '' !== $group_label ? $group_label : (string) __( 'General', 'lerm' ),
				'fields' => $group_fields,
			);
		}

		return $groups;
	}
}
