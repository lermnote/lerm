<?php // phpcs:disable WordPress.Files.FileName
/**
 * Generic store for admin config pages.
 *
 * Storage is delegated to a StorageBackend implementation, making this class
 * reusable for option rows, term meta, user meta, and post/CPT meta without
 * any changes to sanitization, normalization, or section logic.
 *
 * Backward-compatible: when no backend is supplied the store falls back to the
 * OptionBackend (get_option / update_option), preserving existing behaviour.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Storage;

use Lerm\AdminConfig\Framework\Backends\OptionBackend;
use Lerm\AdminConfig\Framework\Contracts\FrameworkContract;
use Lerm\AdminConfig\Framework\Contracts\StorageBackend;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Support\PageSchema;

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

	private StorageBackend $backend;

	/**
	 * Optional reference to the Framework instance for lifecycle hooks.
	 */
	private ?FrameworkContract $framework;

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
	 * Field-level validation errors keyed by dotted field path.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $validation_errors = array();

	/**
	 * Whether validation/sanitize callbacks should be captured into the error bag.
	 */
	private bool $capture_validation_errors = false;

	/**
	 * Current field path stack used while sanitizing nested structures.
	 *
	 * @var array<int, string>
	 */
	private array $field_path_stack = array();

	/**
	 * @param array<string, mixed>  $definition  Page definition.
	 * @param FieldTypeRegistry     $field_types Field type registry.
	 * @param StorageBackend|null   $backend     Storage backend. Defaults to
	 *                                           OptionBackend using the option
	 *                                           name resolved from $definition.
	 */
	public function __construct( array $definition, FieldTypeRegistry $field_types, ?StorageBackend $backend = null, ?FrameworkContract $framework = null ) {
		$this->definition  = $definition;
		$this->field_types = $field_types;
		$this->backend     = $backend ?? new OptionBackend( $this->resolve_option_name() );
		$this->framework   = $framework;
	}

	/**
	 * Expose the backing storage key (option name, meta key, etc.) for
	 * external consumers that need it (e.g. the admin page form attribute).
	 */
	public function storage_key(): string {
		return $this->backend->key();
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function validation_errors(): array {
		return $this->validation_errors;
	}

	public function has_validation_errors(): bool {
		return ! empty( $this->validation_errors );
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
			$options[ $field_id ] = $this->sanitize_field_internal( $field, $options[ $field_id ] ?? ( $field['default'] ?? '' ), false );
		}

		$this->normalized_options = $options;

		return $this->normalized_options;
	}

	/**
	 * Get raw saved options from the storage backend.
	 *
	 * @return array<string, mixed>
	 */
	public function raw(): array {
		if ( null === $this->raw_options ) {
			$this->raw_options = $this->backend->read();
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

		$this->begin_validation_capture();
		$options = $this->raw();

		try {
			foreach ( PageSchema::section_fields( $section ) as $field ) {
				if ( ! $this->field_is_saved( $field ) ) {
					continue;
				}

				$field_id             = (string) $field['id'];
				$options[ $field_id ] = $this->sanitize_field_internal( $field, $submitted[ $field_id ] ?? null, true, $field_id );
			}

			if ( $this->has_validation_errors() ) {
				return false;
			}

			return $this->persist_options( $options );
		} finally {
			$this->end_validation_capture();
		}
	}

	/**
	 * Save the full settings payload across every section.
	 *
	 * @param array<string, mixed> $submitted Submitted values.
	 */
	public function save_all( array $submitted ): bool {
		return $this->import_all( $submitted );
	}

	/**
	 * Import a full settings payload.
	 *
	 * @param array<string, mixed> $submitted Submitted values.
	 */
	public function import_all( array $submitted ): bool {
		$this->begin_validation_capture();
		$options = $this->raw();

		try {
			foreach ( PageSchema::fields( $this->definition ) as $field ) {
				if ( ! $this->field_is_saved( $field ) ) {
					continue;
				}

				$field_id             = (string) $field['id'];
				$options[ $field_id ] = $this->sanitize_field_internal( $field, $submitted[ $field_id ] ?? null, true, $field_id );
			}

			if ( $this->has_validation_errors() ) {
				return false;
			}

			return $this->persist_options( $options );
		} finally {
			$this->end_validation_capture();
		}
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

		foreach ( PageSchema::section_fields( $section ) as $field ) {
			$field_id          = (string) $field['id'];
			$data[ $field_id ] = $values[ $field_id ] ?? ( $field['default'] ?? '' );
		}

		return $data;
	}

	/**
	 * Get normalized values for one explicit subsection group inside a section.
	 *
	 * @param string $section_id Section ID.
	 * @param string $group_id   Explicit subsection group ID.
	 * @return array<string, mixed>
	 */
	public function section_group_values( string $section_id, string $group_id ): array {
		$fields = $this->section_group_fields( $section_id, $group_id );

		if ( empty( $fields ) ) {
			return array();
		}

		$values = $this->all();
		$data   = array();

		foreach ( $fields as $field ) {
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

		$this->clear_validation_errors();
		$options = $this->raw();

		foreach ( PageSchema::section_fields( $section ) as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field_internal( $field, $field['default'] ?? '', false );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Reset one explicit subsection group inside a section to defaults.
	 *
	 * @param string $section_id Section ID.
	 * @param string $group_id   Explicit subsection group ID.
	 */
	public function reset_section_group( string $section_id, string $group_id ): bool {
		$fields = $this->section_group_fields( $section_id, $group_id );

		if ( empty( $fields ) ) {
			return false;
		}

		$this->clear_validation_errors();
		$options = $this->raw();

		foreach ( $fields as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field_internal( $field, $field['default'] ?? '', false );
		}

		return $this->persist_options( $options );
	}

	/**
	 * Whether the section declares a valid explicit subsection group.
	 *
	 * @param string $section_id Section ID.
	 * @param string $group_id   Group ID.
	 */
	public function has_section_group( string $section_id, string $group_id ): bool {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section || '' === $group_id ) {
			return false;
		}

		foreach ( PageSchema::section_groups( $section ) as $group ) {
			if ( (string) ( $group['id'] ?? '' ) === $group_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reset every field in the page to defaults.
	 */
	public function reset_all_sections(): bool {
		$this->clear_validation_errors();
		$options = $this->raw();

		foreach ( PageSchema::fields( $this->definition ) as $field ) {
			if ( ! $this->field_is_saved( $field ) ) {
				continue;
			}

			$field_id             = (string) $field['id'];
			$options[ $field_id ] = $this->sanitize_field_internal( $field, $field['default'] ?? '', false );
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
	private function sanitize_field_internal( array $field, $value, bool $strict, string $path = '' ) {
		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$default  = $field['default'] ?? '';
		$callback = $this->field_types->sanitize_callback( $type );
		$field_id = isset( $field['id'] ) && is_scalar( $field['id'] ) ? (string) $field['id'] : '';
		$path     = '' !== $path ? $path : $this->resolve_field_path( $field_id );

		$this->field_path_stack[] = $path;

		try {
			if ( is_callable( $callback ) ) {
				$value = call_user_func( $callback, $field, $value, $strict, $this );

				if ( is_wp_error( $value ) ) {
					$this->record_error( $field, $value );
					$value = $default;
				}
			} else {
				switch ( $type ) {
					case 'switcher':
						$value = ! empty( $value );
						break;

					case 'color':
						$color = sanitize_hex_color( $this->string_value( $value ) );
						$value = $color ? $color : $default;
						break;

					case 'url':
						$value = esc_url_raw( $this->string_value( $value, '', true ) );
						break;

					case 'button_set':
					case 'radio':
					case 'select':
						$value = $this->sanitize_select_field( $field, $value, $strict );
						break;

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

						$value = array_values( array_unique( $clean ) );
						break;

					case 'number':
						$value = $this->sanitize_number_field( $field, $value, $default );
						break;

					case 'textarea':
						$value = sanitize_textarea_field( $this->string_value( $value ) );
						break;

					case 'text':
					default:
						$value = sanitize_text_field( $this->string_value( $value ) );
						break;
				}
			}

			$value = $this->validate_field_value( $field, $value, $strict );

			return $this->serialize_field_value( $field, $value );
		} finally {
			array_pop( $this->field_path_stack );
		}
	}

	/**
	 * Public surface for sanitizing a single field value.
	 * Always enforces strict mode (choice whitelist validation).
	 * External consumers (custom renderers, import scripts) must use this.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return mixed
	 */
	public function sanitize_field( array $field, $value ) {
		return $this->sanitize_field_internal( $field, $value, true, (string) ( $field['id'] ?? '' ) );
	}

	/**
	 * Run an optional field-level validator after sanitization.
	 *
	 * Validators should return the validated value. Returning WP_Error records
	 * the message in the validation bag and aborts the current save/import flow.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Sanitized value.
	 * @return mixed
	 */
	private function validate_field_value( array $field, $value, bool $strict ) {
		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$callback = $this->field_types->validate_callback( $type );

		if ( ! is_callable( $callback ) ) {
			return $value;
		}

		$validated = call_user_func( $callback, $field, $value, $strict, $this );

		if ( is_wp_error( $validated ) ) {
			$this->record_error( $field, $validated );
			return $field['default'] ?? '';
		}

		return $validated;
	}

	/**
	 * Run an optional serializer before the value is written to storage.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Validated value.
	 * @return mixed
	 */
	private function serialize_field_value( array $field, $value ) {
		$type     = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$callback = $this->field_types->serialize_callback( $type );

		if ( ! is_callable( $callback ) ) {
			return $value;
		}

		return call_user_func( $callback, $field, $value, $this );
	}

	/**
	 * Persist raw options via the storage backend.
	 *
	 * Treats an identical payload as a success (no-op save).
	 * The StorageBackend implementations are responsible for distinguishing
	 * a genuine DB failure from a "no change" return value.
	 *
	 * @param array<string, mixed> $options New option payload.
	 */
	private function persist_options( array $options ): bool {
		$previous = $this->raw();

		// No-op: payload is identical, treat it as success and skip the DB write.
		if ( $previous === $options ) {
			$this->raw_options        = $options;
			$this->normalized_options = null;
			return true;
		}

		$page_id = $this->backend->key();

		if ( null !== $this->framework ) {
			$this->framework->fire( 'before_save', $page_id, $options );
		}

		$this->raw_options        = $options;
		$this->normalized_options = null;

		if ( $this->backend->write( $options ) ) {
			if ( null !== $this->framework ) {
				$this->framework->fire( 'after_save', $page_id, $options );
			}
			return true;
		}

		// Write failed, so roll back the in-memory cache.
		$this->raw_options        = $previous;
		$this->normalized_options = null;
		return false;
	}

	/**
	 * Sanitize gallery fields as ordered attachment IDs.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<int, int>
	 */
	public function sanitize_gallery_field( $value ): array {
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
	public function sanitize_fieldset_field( array $field, $value, bool $strict, string $base_path = '' ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$data   = is_array( $value ) ? $value : array();

		return $this->sanitize_nested_fields( $fields, $data, $strict, $this->container_path( $field, $base_path ) );
	}

	/**
	 * Sanitize repeatable group fields.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return array<int, array<string, mixed>>
	 */
	public function sanitize_group_field( array $field, $value, bool $strict, string $base_path = '' ): array {
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$items  = is_array( $value ) ? array_values( $value ) : array();
		$clean  = array();
		$path   = $this->container_path( $field, $base_path );

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized = $this->sanitize_nested_fields( $fields, $item, $strict, $this->compose_path( $path, (string) $index ) );

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
	public function sanitize_sorter_field( array $field, $value, bool $strict ): array {
		$choices = PageSchema::choices( $field );
		$default = is_array( $field['default'] ?? null ) ? $field['default'] : array(
			'enabled'  => array(),
			'disabled' => array(),
		);

		if ( ! is_array( $value ) ) {
			return $default;
		}

		$order   = array();
		$enabled = array();

		if ( array_key_exists( 'order', $value ) ) {
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
	 * @param mixed                $fallback Default value.
	 * @return int|float
	 */
	private function sanitize_number_field( array $field, $value, $fallback ) {
		$cast   = (string) ( $field['cast'] ?? 'int' );
		$number = is_numeric( $value ) ? (float) $value : (float) $fallback;
		$min    = isset( $field['min'] ) && is_numeric( $field['min'] ) ? (float) $field['min'] : null;
		$max    = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : null;

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
	private function sanitize_nested_fields( array $fields, array $submitted, bool $strict, string $base_path = '' ): array {
		$clean = array();

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id           = (string) $child['id'];
			$clean[ $child_id ] = $this->sanitize_field_internal(
				$child,
				$submitted[ $child_id ] ?? null,
				$strict,
				$this->compose_path( $base_path, $child_id )
			);
		}

		return $clean;
	}

	private function begin_validation_capture(): void {
		$this->clear_validation_errors();
		$this->capture_validation_errors = true;
		$this->field_path_stack          = array();
	}

	private function end_validation_capture(): void {
		$this->capture_validation_errors = false;
		$this->field_path_stack          = array();
	}

	private function clear_validation_errors(): void {
		$this->validation_errors = array();
	}

	private function resolve_field_path( string $field_id ): string {
		return $this->compose_path( $this->current_field_path(), $field_id );
	}

	private function current_field_path(): string {
		if ( empty( $this->field_path_stack ) ) {
			return '';
		}

		return (string) end( $this->field_path_stack );
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private function container_path( array $field, string $base_path = '' ): string {
		$container_path = '' !== $base_path ? $base_path : $this->current_field_path();
		$field_id       = isset( $field['id'] ) && is_scalar( $field['id'] ) ? (string) $field['id'] : '';

		if ( '' === $container_path ) {
			return $field_id;
		}

		if ( '' === $field_id || $container_path === $field_id || str_ends_with( $container_path, '.' . $field_id ) ) {
			return $container_path;
		}

		return $this->compose_path( $container_path, $field_id );
	}

	private function compose_path( string $base_path, string $segment ): string {
		if ( '' === $segment ) {
			return $base_path;
		}

		if ( '' === $base_path ) {
			return $segment;
		}

		return $base_path . '.' . $segment;
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private function record_error( array $field, \WP_Error $error ): void {
		if ( ! $this->capture_validation_errors ) {
			return;
		}

		$path = $this->current_field_path();

		if ( '' === $path && isset( $field['id'] ) && is_scalar( $field['id'] ) ) {
			$path = (string) $field['id'];
		}

		if ( '' === $path ) {
			return;
		}

		if ( ! isset( $this->validation_errors[ $path ] ) ) {
			$this->validation_errors[ $path ] = array();
		}

		foreach ( $error->get_error_messages() as $message ) {
			$message = trim( $message );

			if ( '' === $message || in_array( $message, $this->validation_errors[ $path ], true ) ) {
				continue;
			}

			$this->validation_errors[ $path ][] = $message;
		}
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

			if ( '' !== $this->string_value( $value, '', true ) ) {
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
		if ( array_key_exists( 'save', $field ) ) {
			return false !== $field['save'];
		}

		$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );

		return $this->field_types->persists_value( $type );
	}

	/**
	 * Collect fields that belong to one explicit subsection group.
	 *
	 * @param string $section_id Section ID.
	 * @param string $group_id   Group ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function section_group_fields( string $section_id, string $group_id ): array {
		$section = PageSchema::section( $this->definition, $section_id );

		if ( null === $section ) {
			return array();
		}

		foreach ( PageSchema::section_groups( $section ) as $group ) {
			if ( (string) ( $group['id'] ?? '' ) === $group_id ) {
				$fields = $group['fields'] ?? array();

				return is_array( $fields ) ? array_values( $fields ) : array();
			}
		}

		return array();
	}

	/**
	 * Safely normalize scalar-like values to strings.
	 *
	 * Avoids PHP "Array to string conversion" warnings when imported payloads or
	 * malformed requests send array/object values into scalar fields.
	 *
	 * @param mixed  $value Submitted or stored value.
	 * @param string $fallback Fallback value.
	 */
	private function string_value( $value, string $fallback = '', bool $trim = false ): string {
		return PageSchema::scalar_value( $value, $fallback, $trim );
	}

	/**
	 * Resolve the option name from the page definition.
	 * Used only when no explicit StorageBackend is provided to the constructor.
	 */
	private function resolve_option_name(): string {
		$option_name = isset( $this->definition['option_name'] )
			? sanitize_key( (string) $this->definition['option_name'] )
			: '';

		return '' !== $option_name ? $option_name : 'options_framework';
	}

	/**
	 * @param mixed $value Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize_media_field( $value ): array {
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
	}

	/**
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_code_editor_field( $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * @param mixed $value Submitted value.
	 */
	public function sanitize_wp_editor_field( $value ): string {
		return wp_kses_post( $this->string_value( $value ) );
	}
}
