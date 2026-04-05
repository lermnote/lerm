<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic option-backed store for options framework pages.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Stores;

use Lerm\OptionsFramework\Registry\FieldTypeRegistry;
use Lerm\OptionsFramework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionStore {

	/**
	 * Page definition for the store.
	 *
	 * @var array<string, mixed>
	 */
	private array $definition;

	private FieldTypeRegistry $field_types;

	/**
	 * Cached raw options.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $raw_options = null;

	/**
	 * Cached normalized options.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $normalized_options = null;

	/**
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function __construct( array $definition, FieldTypeRegistry $field_types ) {
		$this->definition  = $definition;
		$this->field_types = $field_types;
	}

	/**
	 * Get all options merged with schema defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null !== $this->normalized_options ) {
			return $this->normalized_options;
		}

		$options = wp_parse_args( $this->raw(), PageSchema::defaults( $this->definition ) );

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $options[ $field_id ] ?? ( $field['default'] ?? '' ), false );
		}

		$this->normalized_options = $options;

		return $this->normalized_options;
	}

	/**
	 * Get raw saved options.
	 *
	 * @return array<string, mixed>
	 */
	public function raw(): array {
		if ( null === $this->raw_options ) {
			$options           = get_option( $this->option_name(), array() );
			$this->raw_options = is_array( $options ) ? $options : array();
		}

		return $this->raw_options;
	}

	/**
	 * Get a single option value.
	 *
	 * @param string $id Option ID.
	 * @param string $tag Optional nested tag key.
	 * @param mixed  $default_value Fallback value.
	 * @return mixed
	 */
	public function get( string $id, string $tag = '', $default_value = '' ) {
		$options = $this->all();

		if ( ! array_key_exists( $id, $options ) ) {
			return $default_value;
		}

		$value = $options[ $id ];

		if ( is_array( $value ) && '' !== $tag ) {
			return $value[ $tag ] ?? $default_value;
		}

		return $value;
	}

	/**
	 * Save a single section.
	 *
	 * @param string               $section_id Section ID.
	 * @param array<string, mixed> $submitted Submitted values.
	 */
	public function save_section( string $section_id, array $submitted ): bool {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return false;
		}

		$options = $this->raw();

		foreach ( $section['fields'] as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $submitted[ $field_id ] ?? null, true );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Import a full settings payload.
	 *
	 * @param array<string, mixed> $submitted Submitted values.
	 */
	public function import_all( array $submitted ): bool {
		$options = $this->raw();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $submitted[ $field_id ] ?? null, true );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Get normalized values for a single section.
	 *
	 * @param string $section_id Section ID.
	 * @return array<string, mixed>
	 */
	public function section_values( string $section_id ): array {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return array();
		}

		$values = $this->all();
		$data   = array();

		foreach ( $section['fields'] as $field ) {
			$field_id          = (string) $field['id'];
			$data[ $field_id ] = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		}

		return $data;
	}

	/**
	 * Reset a single section to defaults.
	 */
	public function reset_section( string $section_id ): bool {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return false;
		}

		$options = $this->raw();

		foreach ( $section['fields'] as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $field['default'] ?? '', false );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Reset every field in the page to defaults.
	 */
	public function reset_all_sections(): bool {
		$options = $this->raw();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $field['default'] ?? '', false );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Sanitize a value according to field type.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return mixed
	 */
	public function sanitize_field( array $field, $value, bool $strict = true ) {
		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$default  = $field['default'] ?? '';
		$callback = $this->field_types->sanitize_callback( $type );

		if ( is_callable( $callback ) ) {
			return call_user_func( $callback, $field, $value, $strict, $this );
		}

		switch ( $type ) {
			case 'switcher':
				return ! empty( $value );

			case 'fieldset':
				return $this->sanitize_fieldset_field( $field, $value, $strict );

			case 'group':
				return $this->sanitize_group_field( $field, $value, $strict );

			case 'media':
				$attachment_id = 0;

				if ( is_array( $value ) ) {
					$attachment_id = absint( $value['id'] ?? 0 );
				} else {
					$attachment_id = absint( $value );
				}

				if ( $attachment_id <= 0 ) {
					return array();
				}

				$attachment_url = wp_get_attachment_url( $attachment_id );
				if ( ! $attachment_url ) {
					return array();
				}

				$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

				return array_filter(
					array(
						'id'        => $attachment_id,
						'url'       => $attachment_url,
						'thumbnail' => $thumbnail_url ? $thumbnail_url : '',
					)
				);

			case 'color':
				$color = sanitize_hex_color( (string) $value );
				return $color ? $color : $default;

			case 'url':
				return esc_url_raw( trim( (string) $value ) );

			case 'wp_editor':
				return wp_kses_post( (string) $value );

			case 'code_editor':
				return is_scalar( $value ) ? trim( (string) $value ) : '';

			case 'button_set':
			case 'radio':
			case 'select':
				return $this->sanitize_select_field( $field, $value, $strict );

			case 'gallery':
				return $this->sanitize_gallery_field( $value );

			case 'checkbox_list':
				$choices = $strict ? PageSchema::choices( $field ) : array();
				$values  = is_array( $value ) ? $value : array();
				$clean   = array();

				foreach ( $values as $item ) {
					$item = is_scalar( $item ) ? (string) $item : '';

					if ( '' === $item ) {
						continue;
					}

					if ( ! $strict ) {
						$clean[] = $item;
						continue;
					}

					if ( array_key_exists( $item, $choices ) ) {
						$clean[] = $item;
					}
				}

				return array_values( array_unique( $clean ) );

			case 'sorter':
				return $this->sanitize_sorter_field( $field, $value, $strict );

			case 'number':
				return $this->sanitize_number_field( $field, $value, $default );

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Persist raw options while treating "no change" as success.
	 *
	 * @param array<string, mixed> $options New option payload.
	 */
	private function persist_options( array $options ): bool {
		$previous = $this->raw();

		$this->raw_options        = $options;
		$this->normalized_options = null;

		if ( $previous === $options ) {
			return true;
		}

		return update_option( $this->option_name(), $options );
	}

	/**
	 * Sanitize gallery fields as ordered attachment IDs.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<int, int>
	 */
	private function sanitize_gallery_field( $value ): array {
		$ids = array();

		if ( is_array( $value ) ) {
			if ( isset( $value['ids'] ) && is_scalar( $value['ids'] ) ) {
				$ids = explode( ',', (string) $value['ids'] );
			} else {
				$ids = $value;
			}
		} elseif ( is_scalar( $value ) ) {
			$ids = explode( ',', (string) $value );
		}

		$clean = array();

		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( $id > 0 ) {
				$clean[] = $id;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitize fieldsets into keyed arrays of sanitized child values.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return array<string, mixed>
	 */
	private function sanitize_fieldset_field( array $field, $value, bool $strict ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$data   = is_array( $value ) ? $value : array();

		return $this->sanitize_nested_fields( $fields, $data, $strict );
	}

	/**
	 * Sanitize repeatable group fields.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_group_field( array $field, $value, bool $strict ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$items  = is_array( $value ) ? array_values( $value ) : array();
		$clean  = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized = $this->sanitize_nested_fields( $fields, $item, $strict );

			if ( $this->nested_values_empty( $sanitized ) ) {
				continue;
			}

			$clean[] = $sanitized;
		}

		return $clean;
	}

	/**
	 * Sanitize sorter fields to the enabled/disabled legacy structure.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted or stored value.
	 * @return array<string, array<string, string>>
	 */
	private function sanitize_sorter_field( array $field, $value, bool $strict ): array {
		$choices = PageSchema::choices( $field );
		$default = is_array( $field['default'] ?? null ) ? $field['default'] : array( 'enabled' => array(), 'disabled' => array() );

		if ( ! is_array( $value ) ) {
			return $default;
		}

		$order   = array();
		$enabled = array();

		if ( array_key_exists( 'order', $value ) || array_key_exists( 'enabled', $value ) ) {
			$order   = is_array( $value['order'] ?? null ) ? $value['order'] : array();
			$enabled = is_array( $value['enabled'] ?? null ) ? $value['enabled'] : array();
		} else {
			$enabled  = array_keys( is_array( $value['enabled'] ?? null ) ? $value['enabled'] : array() );
			$disabled = array_keys( is_array( $value['disabled'] ?? null ) ? $value['disabled'] : array() );
			$order    = array_merge( $enabled, $disabled );
		}

		$ordered_keys = array();

		foreach ( $order as $key ) {
			$key = is_scalar( $key ) ? (string) $key : '';

			if ( '' === $key || isset( $ordered_keys[ $key ] ) ) {
				continue;
			}

			if ( $strict && ! array_key_exists( $key, $choices ) ) {
				continue;
			}

			$ordered_keys[ $key ] = $key;
		}

		if ( ! $strict ) {
			foreach ( array_keys( $choices ) as $key ) {
				if ( ! isset( $ordered_keys[ $key ] ) ) {
					$ordered_keys[ $key ] = $key;
				}
			}
		}

		if ( empty( $ordered_keys ) ) {
			return $default;
		}

		$enabled_lookup = array();

		foreach ( $enabled as $key ) {
			$key = is_scalar( $key ) ? (string) $key : '';

			if ( '' === $key ) {
				continue;
			}

			if ( $strict && ! array_key_exists( $key, $choices ) ) {
				continue;
			}

			$enabled_lookup[ $key ] = true;
		}

		$result = array(
			'enabled'  => array(),
			'disabled' => array(),
		);

		foreach ( $ordered_keys as $key ) {
			$label = $choices[ $key ] ?? (string) $key;

			if ( isset( $enabled_lookup[ $key ] ) ) {
				$result['enabled'][ $key ] = $label;
				continue;
			}

			$result['disabled'][ $key ] = $label;
		}

		return $result;
	}

	/**
	 * Sanitize select-like fields, including multi-select payloads.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return mixed
	 */
	private function sanitize_select_field( array $field, $value, bool $strict ) {
		$default  = $field['default'] ?? '';
		$cast     = (string) ( $field['cast'] ?? '' );
		$multiple = ! empty( $field['multiple'] );

		if ( $multiple ) {
			$values  = is_array( $value ) ? $value : array();
			$choices = $strict ? PageSchema::choices( $field ) : array();
			$clean   = array();

			foreach ( $values as $item ) {
				$item = is_scalar( $item ) ? (string) $item : '';

				if ( '' === $item ) {
					continue;
				}

				if ( $strict && ! array_key_exists( $item, $choices ) ) {
					continue;
				}

				$clean[] = $this->cast_scalar_value( $item, $cast );
			}

			return array_values( array_unique( $clean, SORT_REGULAR ) );
		}

		$choice = is_scalar( $value ) ? (string) $value : '';

		if ( $strict ) {
			$choices = PageSchema::choices( $field );

			if ( ! array_key_exists( $choice, $choices ) ) {
				return $default;
			}
		}

		return $this->cast_scalar_value( $choice, $cast );
	}

	/**
	 * Sanitize numeric fields while preserving optional float support.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @param mixed                $default Default value.
	 * @return int|float
	 */
	private function sanitize_number_field( array $field, $value, $default ) {
		$cast    = (string) ( $field['cast'] ?? 'int' );
		$number  = is_numeric( $value ) ? (float) $value : (float) $default;
		$min     = isset( $field['min'] ) && is_numeric( $field['min'] ) ? (float) $field['min'] : null;
		$max     = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : null;

		if ( null !== $min && $number < $min ) {
			$number = $min;
		}

		if ( null !== $max && $number > $max ) {
			$number = $max;
		}

		if ( 'float' === $cast ) {
			return $number;
		}

		return (int) round( $number );
	}

	/**
	 * Sanitize nested child field definitions.
	 *
	 * @param array<int, array<string, mixed>> $fields Child fields.
	 * @param array<string, mixed>             $submitted Submitted child values.
	 * @return array<string, mixed>
	 */
	private function sanitize_nested_fields( array $fields, array $submitted, bool $strict ): array {
		$clean = array();

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id            = (string) $child['id'];
			$clean[ $child_id ] = $this->sanitize_field( $child, $submitted[ $child_id ] ?? null, $strict );
		}

		return $clean;
	}

	/**
	 * Determine whether a nested group item only contains empty values.
	 *
	 * @param array<string, mixed> $values Nested values.
	 */
	private function nested_values_empty( array $values ): bool {
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				if ( ! empty( $value ) && ! $this->nested_values_empty( $value ) ) {
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

			if ( '' !== trim( (string) $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Cast scalar values according to field preferences.
	 *
	 * @param string $value Scalar value.
	 * @return string|int|float
	 */
	private function cast_scalar_value( string $value, string $cast ) {
		if ( 'int' === $cast ) {
			return (int) $value;
		}

		if ( 'float' === $cast ) {
			return (float) $value;
		}

		return $value;
	}

	/**
	 * Whether a field should be written to the option payload.
	 *
	 * @param array<string, mixed> $field Field definition.
	 */
	private function field_is_saved( array $field ): bool {
		return ! array_key_exists( 'save', $field ) || false !== $field['save'];
	}

	/**
	 * Resolve the stored option name.
	 */
	private function option_name(): string {
		$option_name = isset( $this->definition['option_name'] ) ? sanitize_key( (string) $this->definition['option_name'] ) : '';

		return '' !== $option_name ? $option_name : 'options_framework';
	}
}
