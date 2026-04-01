<?php // phpcs:disable WordPress.Files.FileName
/**
 * Repository for theme options.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Options;

use Lerm\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeOptionsRepository {

	use Singleton;

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
	 * Get all options merged with schema defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null !== $this->normalized_options ) {
			return $this->normalized_options;
		}

		$options = wp_parse_args( $this->raw(), ThemeOptionsSchema::defaults() );

		foreach ( ThemeOptionsSchema::fields() as $field ) {
			$field_id             = $field['id'];
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
			$options           = get_option( ThemeOptionsSchema::OPTION_NAME, array() );
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
	 * Save a single schema section.
	 *
	 * @param string               $section_id Section ID.
	 * @param array<string, mixed> $submitted Submitted values.
	 * @return bool
	 */
	public function save_section( string $section_id, array $submitted ): bool {
		$section = ThemeOptionsSchema::section( $section_id );

		if ( null === $section ) {
			return false;
		}

		$options = $this->raw();

		foreach ( $section['fields'] as $field ) {
			$field_id             = $field['id'];
			$options[ $field_id ] = $this->sanitize_field( $field, $submitted[ $field_id ] ?? null, true );
		}

		$this->raw_options        = $options;
		$this->normalized_options = null;

		return update_option( ThemeOptionsSchema::OPTION_NAME, $options );
	}

	/**
	 * Sanitize a field value by schema type.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Submitted value.
	 * @return mixed
	 */
	private function sanitize_field( array $field, $value, bool $strict = true ) {
		$type    = $field['type'] ?? 'text';
		$default = $field['default'] ?? '';

		switch ( $type ) {
			case 'switcher':
				return ! empty( $value );

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

			case 'select':
				$choice = is_scalar( $value ) ? (string) $value : '';

				if ( $strict ) {
					$choices = ThemeOptionsSchema::choices( $field );

					if ( ! array_key_exists( $choice, $choices ) ) {
						return $default;
					}
				}

				if ( ( $field['cast'] ?? '' ) === 'int' ) {
					return (int) $choice;
				}

				return $choice;

			case 'checkbox_list':
				$choices = $strict ? ThemeOptionsSchema::choices( $field ) : array();
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

			case 'number':
				$number = is_numeric( $value ) ? (int) $value : (int) $default;
				$min    = isset( $field['min'] ) ? (int) $field['min'] : null;
				$max    = isset( $field['max'] ) ? (int) $field['max'] : null;

				if ( null !== $min && $number < $min ) {
					$number = $min;
				}

				if ( null !== $max && $number > $max ) {
					$number = $max;
				}

				return $number;

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}
}
