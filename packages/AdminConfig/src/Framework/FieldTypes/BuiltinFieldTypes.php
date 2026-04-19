<?php
/**
 * Built-in field type definitions for the admin-config framework.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BuiltinFieldTypes {

	/**
	 * Return the built-in field type definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'text'          => self::text_definition(),
			'url'           => self::url_definition(),
			'textarea'      => self::textarea_definition(),
			'number'        => self::number_definition(),
			'color'         => self::color_definition(),
			'switcher'      => self::switcher_definition(),
			'button_set'    => self::choice_definition( 'button_set' ),
			'radio'         => self::choice_definition( 'radio' ),
			'select'        => self::select_definition(),
			'checkbox_list' => self::checkbox_list_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function text_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_text_input(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					'text',
					! empty( $field['dependency_field'] ) ? ' data-lerm-controller="1"' : ''
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_text_input(
					$field,
					$value,
					$field_name,
					$input_id,
					'text',
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return sanitize_text_field( PageSchema::scalar_value( $value ) );
			},
			'client'        => array(
				'control' => 'text',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function url_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_text_input(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					'url',
					! empty( $field['dependency_field'] ) ? ' data-lerm-controller="1"' : ''
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_text_input(
					$field,
					$value,
					$field_name,
					$input_id,
					'url',
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return esc_url_raw( PageSchema::scalar_value( $value, '', true ) );
			},
			'client'        => array(
				'control' => 'url',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function textarea_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_textarea(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					! empty( $field['dependency_field'] ) ? ' data-lerm-controller="1"' : ''
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_textarea(
					$field,
					$value,
					$field_name,
					$input_id,
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return sanitize_textarea_field( PageSchema::scalar_value( $value ) );
			},
			'client'        => array(
				'control' => 'textarea',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function number_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_number_input(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					! empty( $field['dependency_field'] ) ? ' data-lerm-controller="1"' : ''
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_number_input(
					$field,
					$value,
					$field_name,
					$input_id,
					self::name_attr( $name_template ) . self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_number( $field, $value );
			},
			'client'        => array(
				'control' => 'number',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function color_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field">',
					esc_attr( (string) $field['id'] ),
					esc_attr( $field_name ),
					esc_attr( PageSchema::scalar_value( $value ) )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field"%4$s%5$s>',
					esc_attr( $input_id ),
					esc_attr( $field_name ),
					esc_attr( PageSchema::scalar_value( $value ) ),
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				$default = is_scalar( $field['default'] ?? null ) ? (string) $field['default'] : '';
				$color   = sanitize_hex_color( PageSchema::scalar_value( $value ) );

				return $color ? $color : $default;
			},
			'client'        => array(
				'control' => 'color',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function switcher_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_switcher(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					' data-lerm-controller="1"'
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_switcher(
					$field,
					$value,
					$field_name,
					$input_id,
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return ! empty( $value );
			},
			'client'        => array(
				'control' => 'switcher',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function choice_definition( string $type ): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ) use ( $type ): void {
				self::render_choice_group( $field, $value, $field_name, true, '', '', $type );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ) use ( $type ): void {
				self::render_choice_group( $field, $value, $field_name, false, self::name_attr( $name_template ), self::id_attr( $id_template ), $type );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_select_like( $field, $value, $strict );
			},
			'client'        => array(
				'control' => $type,
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function select_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_select( $field, $value, $field_name, (string) $field['id'], true );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_select( $field, $value, $field_name, $input_id, false, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_select_like( $field, $value, $strict );
			},
			'client'        => array(
				'control' => 'select',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function checkbox_list_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_checkbox_list( $field, $value, $field_name, true );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_checkbox_list( $field, $value, $field_name, false, $name_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_checkbox_list( $field, $value, $strict );
			},
			'client'        => array(
				'control' => 'checkbox_list',
				'nested'  => true,
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_text_input( array $field, $value, string $field_name, string $input_id, string $fallback_type, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s%8$s>',
			esc_attr( self::input_type( $field, $fallback_type ) ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( PageSchema::scalar_value( $value ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$extra_attrs,
			$name_attr,
			$id_attr
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_textarea( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		printf(
			'<textarea id="%1$s" name="%2$s" class="large-text" rows="%3$s" placeholder="%4$s"%5$s%6$s%7$s>%8$s</textarea>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( (string) ( $field['rows'] ?? 4 ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$extra_attrs,
			$name_attr,
			$id_attr,
			esc_textarea( PageSchema::scalar_value( $value ) )
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_number_input( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '' ): void {
		echo '<span class="lerm-number-input">';
		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text lerm-number-input__control" min="%4$s" max="%5$s" step="%6$s"%7$s>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( PageSchema::scalar_value( $value ) ),
			esc_attr( (string) ( $field['min'] ?? '' ) ),
			esc_attr( (string) ( $field['max'] ?? '' ) ),
			esc_attr( (string) ( $field['step'] ?? 1 ) ),
			$extra_attrs
		);
		printf(
			'<span class="lerm-number-input__actions"><button type="button" class="lerm-number-input__button" data-lerm-number-step="up" aria-label="%1$s"><span aria-hidden="true">&#9650;</span></button><button type="button" class="lerm-number-input__button" data-lerm-number-step="down" aria-label="%2$s"><span aria-hidden="true">&#9660;</span></button></span>',
			esc_attr__( 'Increase value', 'lerm' ),
			esc_attr__( 'Decrease value', 'lerm' )
		);
		echo '</span>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_switcher( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		printf(
			'<input type="hidden" name="%1$s" value="0"%4$s><label class="lerm-switch"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s%4$s%5$s%6$s><span class="lerm-switch__track" data-on="%7$s" data-off="%8$s" aria-hidden="true"></span><span class="screen-reader-text">%9$s</span></label>',
			esc_attr( $field_name ),
			esc_attr( $input_id ),
			checked( ! empty( $value ), true, false ),
			$name_attr,
			$id_attr,
			$extra_attrs,
			esc_attr__( 'on', 'lerm' ),
			esc_attr__( 'off', 'lerm' ),
			esc_html__( 'Enabled', 'lerm' )
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_choice_group( array $field, $value, string $field_name, bool $is_root, string $name_attr = '', string $id_attr = '', string $type = 'radio' ): void {
		$choices = PageSchema::choices( $field );
		$current = is_scalar( $value ) ? (string) $value : '';
		$class   = 'button_set' === $type ? 'lerm-button-set' : 'lerm-radio-list';

		if ( $is_root ) {
			echo '<fieldset class="' . esc_attr( $class ) . '"><legend class="screen-reader-text">' . esc_html( (string) ( $field['label'] ?? '' ) ) . '</legend>';
		} else {
			echo '<fieldset class="' . esc_attr( $class ) . '">';
		}

		foreach ( $choices as $choice_value => $choice_label ) {
			printf(
				'<label><input type="radio" name="%1$s" value="%2$s" %3$s%4$s%5$s> <span>%6$s</span></label>',
				esc_attr( $field_name ),
				esc_attr( $choice_value ),
				checked( $current, (string) $choice_value, false ),
				$name_attr,
				$is_root ? ' data-lerm-controller="1"' : $id_attr,
				esc_html( $choice_label )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_select( array $field, $value, string $field_name, string $input_id, bool $is_root, string $name_template = '', string $id_template = '' ): void {
		$choices          = PageSchema::choices( $field );
		$multiple         = ! empty( $field['multiple'] );
		$current          = $multiple && is_array( $value ) ? array_map( 'strval', $value ) : array();
		$current_value    = $multiple ? '' : PageSchema::scalar_value( $value );
		$select_name_attr = $multiple && '' !== $name_template
			? ' data-name-template="' . esc_attr( $name_template . '[]' ) . '"'
			: self::name_attr( $name_template );

		printf(
			'<select id="%1$s" name="%2$s" class="regular-text"%3$s%4$s%5$s%6$s>',
			esc_attr( $input_id ),
			esc_attr( $multiple ? $field_name . '[]' : $field_name ),
			$multiple ? ' multiple="multiple"' : '',
			$multiple ? ' size="' . esc_attr( (string) min( max( count( $choices ), 4 ), 10 ) ) . '"' : '',
			$select_name_attr,
			$is_root ? ' data-lerm-controller="1"' : self::id_attr( $id_template )
		);

		foreach ( $choices as $choice_value => $choice_label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $choice_value ),
				$multiple
					? selected( in_array( (string) $choice_value, $current, true ), true, false )
					: selected( $current_value, (string) $choice_value, false ),
				esc_html( $choice_label )
			);
		}

		echo '</select>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_checkbox_list( array $field, $value, string $field_name, bool $is_root, string $name_template = '' ): void {
		$choices   = PageSchema::choices( $field );
		$current   = is_array( $value ) ? array_map( 'strval', $value ) : array();
		$name_attr = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template . '[]' ) . '"' : '';

		if ( $is_root ) {
			echo '<fieldset class="lerm-checkbox-list"><legend class="screen-reader-text">' . esc_html( (string) ( $field['label'] ?? '' ) ) . '</legend>';
		} else {
			echo '<fieldset class="lerm-checkbox-list">';
		}

		foreach ( $choices as $choice_value => $choice_label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s%4$s> <span>%5$s</span></label>',
				esc_attr( $field_name ),
				esc_attr( $choice_value ),
				checked( in_array( (string) $choice_value, $current, true ), true, false ),
				$name_attr,
				esc_html( $choice_label )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private static function sanitize_select_like( array $field, $value, bool $strict ) {
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

				$clean[] = self::cast_scalar_value( $item, $cast );
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

		return self::cast_scalar_value( $choice, $cast );
	}

	/**
	 * @param mixed $value
	 * @return array<int, string>
	 */
	private static function sanitize_checkbox_list( array $field, $value, bool $strict ): array {
		$choices = $strict ? PageSchema::choices( $field ) : array();
		$values  = is_array( $value ) ? $value : array();
		$clean   = array();

		foreach ( $values as $item ) {
			$item = is_scalar( $item ) ? (string) $item : '';

			if ( '' === $item ) {
				continue;
			}

			if ( ! $strict || array_key_exists( $item, $choices ) ) {
				$clean[] = $item;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * @param mixed $value
	 * @return int|float
	 */
	private static function sanitize_number( array $field, $value ) {
		$default = $field['default'] ?? 0;
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

		return 'float' === $cast ? $number : (int) round( $number );
	}

	/**
	 * @return string|int|float
	 */
	private static function cast_scalar_value( string $value, string $cast ) {
		if ( 'int' === $cast ) {
			return (int) $value;
		}

		if ( 'float' === $cast ) {
			return (float) $value;
		}

		return $value;
	}

	private static function input_type( array $field, string $fallback ): string {
		if ( ! empty( $field['input_type'] ) && is_scalar( $field['input_type'] ) ) {
			return (string) $field['input_type'];
		}

		return $fallback;
	}

	private static function name_attr( string $name_template ): string {
		return '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
	}

	private static function id_attr( string $id_template ): string {
		return '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
	}
}
