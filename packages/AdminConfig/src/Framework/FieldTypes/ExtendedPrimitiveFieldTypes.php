<?php
/**
 * Extended native fields for controls and presentation.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExtendedPrimitiveFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'checkbox'     => self::checkbox_definition(),
			'content'      => self::presentation_definition( 'content' ),
			'date'         => self::date_definition(),
			'heading'      => self::presentation_definition( 'heading' ),
			'image_select' => self::image_select_definition(),
			'palette'      => self::palette_definition(),
			'slider'       => self::slider_definition(),
			'spinner'      => self::spinner_definition(),
			'subheading'   => self::presentation_definition( 'subheading' ),
			'upload'       => self::upload_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function checkbox_definition(): array {
		return array(
			'render'             => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				if ( self::checkbox_uses_choices( $field ) ) {
					self::render_checkbox_group( $field, $value, $field_name, true, $page->dependency_controller_attribute( $field ) );
					return;
				}

				self::render_checkbox_input(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested'      => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				if ( self::checkbox_uses_choices( $field ) ) {
					self::render_checkbox_group( $field, $value, $field_name, false, self::name_attr( self::multiple_name_template( $name_template ) ) );
					return;
				}

				self::render_checkbox_input(
					$field,
					$value,
					$field_name,
					$input_id,
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'           => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				if ( self::checkbox_uses_choices( $field ) ) {
					return self::sanitize_checkbox_choices( $field, $value, $strict );
				}

				return ! empty( $value );
			},
			'missing_submission' => static function ( array $field ): array {
				if ( ! self::checkbox_uses_choices( $field ) ) {
					return array(
						'apply' => false,
						'value' => null,
					);
				}

				return array(
					'apply' => true,
					'value' => array(),
				);
			},
			'client'             => array(
				'control' => 'checkbox',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function upload_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_upload_field(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_upload_field(
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
				return esc_url_raw( PageSchema::scalar_value( $value, '', true ) );
			},
			'client'        => array(
				'control' => 'upload',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function date_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_date_field(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_date_field( $field, $value, $field_name, $input_id, '', $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				if ( ! empty( $field['from_to'] ) ) {
					$data = is_array( $value ) ? $value : array();

					return array(
						'from' => sanitize_text_field( PageSchema::scalar_value( $data['from'] ?? '' ) ),
						'to'   => sanitize_text_field( PageSchema::scalar_value( $data['to'] ?? '' ) ),
					);
				}

				return sanitize_text_field( PageSchema::scalar_value( $value ) );
			},
			'client'        => array(
				'control' => 'date',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function slider_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_slider_field(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_slider_field(
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
				return self::sanitize_numeric_scalar( $field, $value );
			},
			'client'        => array(
				'control' => 'slider',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function spinner_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_spinner_field(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_spinner_field( $field, $value, $field_name, $input_id, self::name_attr( $name_template ) . self::id_attr( $id_template ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_numeric_scalar( $field, $value );
			},
			'client'        => array(
				'control' => 'spinner',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function image_select_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_image_select_field( $field, $value, $field_name, true );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_image_select_field( $field, $value, $field_name, false, self::name_attr( $name_template ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_select_value( $field, $value, $strict );
			},
			'client'        => array(
				'control' => 'image_select',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function palette_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_palette_field( $field, $value, $field_name, true );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_palette_field( $field, $value, $field_name, false, self::name_attr( $name_template ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				$options = self::palette_options( $field );
				$choice  = is_scalar( $value ) ? (string) $value : '';

				if ( ! array_key_exists( $choice, $options ) ) {
					$default = is_scalar( $field['default'] ?? null ) ? (string) $field['default'] : '';
					return array_key_exists( $default, $options ) ? $default : '';
				}

				return $choice;
			},
			'client'        => array(
				'control' => 'palette',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function presentation_definition( string $variant ): array {
		return array(
			'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ) use ( $variant ): void {
				$content = trim( (string) ( $field['content'] ?? '' ) );
				$title   = trim( (string) ( $field['label'] ?? '' ) );

				if ( '' === $content && '' !== $title ) {
					if ( 'heading' === $variant ) {
						$content = '<h3>' . esc_html( $title ) . '</h3>';
					} elseif ( 'subheading' === $variant ) {
						$content = '<h4>' . esc_html( $title ) . '</h4>';
					} else {
						$content = '<p>' . esc_html( $title ) . '</p>';
					}
				}

				if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
					return;
				}

				echo '<div class="lerm-presentation lerm-presentation--' . esc_attr( $variant ) . '">' . wp_kses_post( $content ) . '</div>';
			},
			'persist'  => false,
			'sanitize' => static function () {
				return '';
			},
			'client'   => array(
				'control' => $variant,
			),
		);
	}

	private static function checkbox_uses_choices( array $field ): bool {
		return ! empty( PageSchema::choices( $field ) );
	}

	/**
	 * @param mixed $value
	 */
	private static function render_checkbox_input( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		$text = isset( $field['text'] ) && is_scalar( $field['text'] ) ? (string) $field['text'] : '';

		printf(
			'<input type="hidden" name="%1$s" value="0"%5$s><label class="lerm-checkbox"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s%4$s%5$s%6$s>%7$s</label>',
			esc_attr( $field_name ),
			esc_attr( $input_id ),
			checked( ! empty( $value ), true, false ),
			$extra_attrs,
			$name_attr,
			$id_attr,
			'' !== $text ? '<span>' . esc_html( $text ) . '</span>' : ''
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_checkbox_group( array $field, $value, string $field_name, bool $is_root, string $name_attr = '' ): void {
		$choices = PageSchema::choices( $field );
		$current = is_array( $value ) ? array_map( 'strval', $value ) : array();

		echo '<fieldset class="lerm-checkbox-list">';
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
	 */
	private static function render_upload_field( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		$url         = esc_url( PageSchema::scalar_value( $value ) );
		$library     = is_scalar( $field['library'] ?? null ) ? (string) $field['library'] : '';
		$button_text = (string) ( $field['button_text'] ?? __( 'Choose file', 'lerm' ) );
		$remove_text = (string) ( $field['remove_text'] ?? __( 'Remove', 'lerm' ) );
		$placeholder = (string) ( $field['placeholder'] ?? '' );

		printf(
			'<div class="lerm-upload-field" data-library="%1$s"><div class="lerm-upload-field__controls"><input type="url" id="%2$s" name="%3$s" value="%4$s" class="regular-text lerm-upload-field__input" placeholder="%5$s"%6$s%7$s%8$s><button type="button" class="button lerm-upload-field__select">%9$s</button><button type="button" class="button button-secondary button-link-delete lerm-upload-field__remove" %10$s>%11$s</button></div><div class="lerm-upload-field__preview" %10$s>%12$s</div></div>',
			esc_attr( $library ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( $url ),
			esc_attr( $placeholder ),
			$extra_attrs,
			$name_attr,
			$id_attr,
			esc_html( $button_text ),
			'' !== $url ? '' : 'hidden',
			esc_html( $remove_text ),
			self::upload_preview_markup( $url )
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_date_field( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_template = '', string $id_template = '' ): void {
		if ( ! empty( $field['from_to'] ) ) {
			$data = is_array( $value ) ? $value : array();

			echo '<div class="lerm-date-range">';
			self::render_date_input( self::sub_name( $field_name, 'from' ), self::sub_id( $input_id, 'from' ), PageSchema::scalar_value( $data['from'] ?? '' ), (string) ( $field['text_from'] ?? __( 'From', 'lerm' ) ), self::name_attr( self::sub_template( $name_template, 'from' ) ), self::id_attr( self::sub_id_template( $id_template, 'from' ) ), $extra_attrs );
			self::render_date_input( self::sub_name( $field_name, 'to' ), self::sub_id( $input_id, 'to' ), PageSchema::scalar_value( $data['to'] ?? '' ), (string) ( $field['text_to'] ?? __( 'To', 'lerm' ) ), self::name_attr( self::sub_template( $name_template, 'to' ) ), self::id_attr( self::sub_id_template( $id_template, 'to' ) ) );
			echo '</div>';
			return;
		}

		self::render_date_input( $field_name, $input_id, PageSchema::scalar_value( $value ), '', self::name_attr( $name_template ), self::id_attr( $id_template ), $extra_attrs );
	}

	private static function render_date_input( string $field_name, string $input_id, string $value, string $label = '', string $name_attr = '', string $id_attr = '', string $extra_attrs = '' ): void {
		$input = sprintf(
			'<input type="date" id="%1$s" name="%2$s" value="%3$s" class="regular-text"%4$s%5$s%6$s>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( $value ),
			$extra_attrs,
			$name_attr,
			$id_attr
		);

		if ( '' === $label ) {
			echo $input;
			return;
		}

		echo '<label class="lerm-date-range__field"><span>' . esc_html( $label ) . '</span>' . $input . '</label>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_slider_field( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		$current = self::numeric_display_value( $field, $value );
		$unit    = is_scalar( $field['unit'] ?? null ) ? (string) $field['unit'] : '';

		echo '<div class="lerm-range-input">';
		printf(
			'<input type="range" value="%1$s" min="%2$s" max="%3$s" step="%4$s" class="lerm-range-input__range"%5$s>',
			esc_attr( $current ),
			esc_attr( (string) ( $field['min'] ?? 0 ) ),
			esc_attr( (string) ( $field['max'] ?? 100 ) ),
			esc_attr( (string) ( $field['step'] ?? 1 ) ),
			$extra_attrs
		);
		printf(
			'<div class="lerm-range-input__number-wrap"><input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="small-text lerm-range-input__number"%7$s%8$s%9$s>%10$s</div>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( $current ),
			esc_attr( (string) ( $field['min'] ?? 0 ) ),
			esc_attr( (string) ( $field['max'] ?? 100 ) ),
			esc_attr( (string) ( $field['step'] ?? 1 ) ),
			$extra_attrs,
			$name_attr,
			$id_attr,
			'' !== $unit ? '<span class="lerm-range-input__unit">' . esc_html( $unit ) . '</span>' : ''
		);
		echo '</div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_spinner_field( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '' ): void {
		$current = self::numeric_display_value( $field, $value );

		echo '<span class="lerm-number-input">';
		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text lerm-number-input__control" min="%4$s" max="%5$s" step="%6$s"%7$s>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( $current ),
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
	private static function render_image_select_field( array $field, $value, string $field_name, bool $is_root, string $name_attr = '' ): void {
		$choices = PageSchema::choices( $field );
		$current = is_scalar( $value ) ? (string) $value : (string) ( $field['default'] ?? '' );

		echo '<fieldset class="lerm-image-select">';
		foreach ( $choices as $choice_value => $image_url ) {
			printf(
				'<label class="lerm-image-select__item"><input type="radio" name="%1$s" value="%2$s" %3$s%4$s><span class="lerm-image-select__frame"><img src="%5$s" alt="%6$s"></span></label>',
				esc_attr( $field_name ),
				esc_attr( $choice_value ),
				checked( $current, (string) $choice_value, false ),
				$is_root ? '' : $name_attr,
				esc_url( $image_url ),
				esc_attr( $choice_value )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_palette_field( array $field, $value, string $field_name, bool $is_root, string $name_attr = '' ): void {
		$choices = self::palette_options( $field );
		$current = is_scalar( $value ) ? (string) $value : '';

		echo '<fieldset class="lerm-palette">';
		foreach ( $choices as $choice_value => $colors ) {
			echo '<label class="lerm-palette__item">';
			printf(
				'<input type="radio" name="%1$s" value="%2$s" %3$s%4$s>',
				esc_attr( $field_name ),
				esc_attr( $choice_value ),
				checked( $current, (string) $choice_value, false ),
				$is_root ? '' : $name_attr
			);
			echo '<span class="lerm-palette__swatches">';
			foreach ( $colors as $color ) {
				echo '<span style="background-color:' . esc_attr( $color ) . ';"></span>';
			}
			echo '</span></label>';
		}
		echo '</fieldset>';
	}

	/**
	 * @param mixed $value
	 * @return array<int, string>
	 */
	private static function sanitize_checkbox_choices( array $field, $value, bool $strict ): array {
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
	private static function sanitize_numeric_scalar( array $field, $value ) {
		$default = $field['default'] ?? 0;
		$cast    = (string) ( $field['cast'] ?? 'int' );
		$number  = is_numeric( $value ) ? (float) $value : ( is_numeric( $default ) ? (float) $default : 0.0 );
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
	 * @param mixed $value
	 */
	private static function sanitize_select_value( array $field, $value, bool $strict ) {
		$default = is_scalar( $field['default'] ?? null ) ? (string) $field['default'] : '';
		$choice  = is_scalar( $value ) ? (string) $value : '';

		if ( $strict ) {
			$choices = PageSchema::choices( $field );

			if ( ! array_key_exists( $choice, $choices ) ) {
				return $default;
			}
		}

		return sanitize_key( $choice );
	}

	private static function palette_options( array $field ): array {
		$options = $field['choices'] ?? array();

		if ( is_callable( $options ) ) {
			$options = call_user_func( $options );
		}

		if ( ! is_array( $options ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $options as $key => $colors ) {
			if ( ! is_scalar( $key ) || ! is_array( $colors ) ) {
				continue;
			}

			$swatches = array();
			foreach ( $colors as $color ) {
				$sanitized = sanitize_hex_color( PageSchema::scalar_value( $color ) );
				if ( $sanitized ) {
					$swatches[] = $sanitized;
				}
			}

			if ( ! empty( $swatches ) ) {
				$normalized[ (string) $key ] = $swatches;
			}
		}

		return $normalized;
	}

	private static function upload_preview_markup( string $url ): string {
		if ( '' === $url ) {
			return '';
		}

		if ( (bool) preg_match( '/\.(?:avif|gif|jpe?g|png|svg|webp)(?:\?.*)?$/i', $url ) ) {
			return '<img src="' . esc_url( $url ) . '" alt="">';
		}

		$path  = wp_parse_url( $url, PHP_URL_PATH );
		$label = wp_basename( is_string( $path ) && '' !== $path ? $path : $url );

		return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>';
	}

	private static function numeric_display_value( array $field, $value ): string {
		if ( is_scalar( $value ) && '' !== (string) $value ) {
			return (string) $value;
		}

		return is_scalar( $field['default'] ?? null ) ? (string) $field['default'] : '0';
	}

	private static function sub_name( string $field_name, string $key ): string {
		return $field_name . '[' . $key . ']';
	}

	private static function sub_template( string $template, string $key ): string {
		return '' !== $template ? $template . '[' . $key . ']' : '';
	}

	private static function sub_id( string $input_id, string $key ): string {
		return $input_id . '__' . sanitize_html_class( str_replace( '_', '-', $key ) );
	}

	private static function sub_id_template( string $template, string $key ): string {
		return '' !== $template ? $template . '__' . sanitize_html_class( str_replace( '_', '-', $key ) ) : '';
	}

	private static function multiple_name_template( string $template ): string {
		return '' !== $template ? $template . '[]' : '';
	}

	private static function name_attr( string $template ): string {
		return '' !== $template ? ' data-name-template="' . esc_attr( $template ) . '"' : '';
	}

	private static function id_attr( string $template ): string {
		return '' !== $template ? ' data-id-template="' . esc_attr( $template ) . '"' : '';
	}
}
