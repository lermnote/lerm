<?php
/**
 * Native design-oriented composite field controls.
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

final class DesignFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'background' => self::background_definition(),
			'border'     => self::border_definition(),
			'dimensions' => self::dimensions_definition(),
			'link_color' => self::link_color_definition(),
			'spacing'    => self::spacing_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function dimensions_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_dimensions_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_dimensions_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_dimensions_value( $field, $value );
			},
			'client'        => array(
				'control' => 'dimensions',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function spacing_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_spacing_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_spacing_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_spacing_value( $field, $value );
			},
			'client'        => array(
				'control' => 'spacing',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function border_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_border_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_border_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_border_value( $field, $value );
			},
			'client'        => array(
				'control' => 'border',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function link_color_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_link_color_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_link_color_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_link_color_value( $field, $value );
			},
			'client'        => array(
				'control' => 'link_color',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function background_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				unset( $page );

				self::render_background_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				unset( $page );

				self::render_background_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_background_value( $field, $value );
			},
			'client'        => array(
				'control' => 'background',
				'nested'  => true,
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_dimensions_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_dimensions( $field );
		$values   = wp_parse_args( $data, $defaults );
		$units    = self::units( $field );

		echo '<div class="lerm-composite"><div class="lerm-composite__grid lerm-composite__grid--compact">';
		if ( self::flag( $field, 'width', true ) ) {
			self::render_number_item( $field_name, $input_id, 'width', __( 'Width', 'lerm-admin-config' ), $values, $name_template, $id_template );
		}
		if ( self::flag( $field, 'height', true ) ) {
			self::render_number_item( $field_name, $input_id, 'height', __( 'Height', 'lerm-admin-config' ), $values, $name_template, $id_template );
		}
		if ( self::show_unit_select( $field, $units ) ) {
			self::render_select_item( $field_name, $input_id, 'unit', __( 'Unit', 'lerm-admin-config' ), array_combine( $units, $units ), (string) ( $values['unit'] ?? $defaults['unit'] ), $name_template, $id_template );
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_spacing_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_spacing( $field );
		$values   = wp_parse_args( $data, $defaults );
		$units    = self::units( $field );

		echo '<div class="lerm-composite"><div class="lerm-composite__grid lerm-composite__grid--compact">';
		if ( self::flag( $field, 'all', false ) ) {
			self::render_number_item( $field_name, $input_id, 'all', __( 'All', 'lerm-admin-config' ), $values, $name_template, $id_template );
		} else {
			foreach ( array(
				'top'    => __( 'Top', 'lerm-admin-config' ),
				'right'  => __( 'Right', 'lerm-admin-config' ),
				'bottom' => __( 'Bottom', 'lerm-admin-config' ),
				'left'   => __( 'Left', 'lerm-admin-config' ),
			) as $key => $label ) {
				if ( self::flag( $field, $key, true ) ) {
					self::render_number_item( $field_name, $input_id, $key, $label, $values, $name_template, $id_template );
				}
			}
		}
		if ( self::show_unit_select( $field, $units ) ) {
			self::render_select_item( $field_name, $input_id, 'unit', __( 'Unit', 'lerm-admin-config' ), array_combine( $units, $units ), (string) ( $values['unit'] ?? $defaults['unit'] ), $name_template, $id_template );
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_border_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_border( $field );
		$values   = wp_parse_args( $data, $defaults );

		echo '<div class="lerm-composite"><div class="lerm-composite__grid lerm-composite__grid--compact">';
		if ( self::flag( $field, 'all', false ) ) {
			self::render_number_item( $field_name, $input_id, 'all', __( 'All', 'lerm-admin-config' ), $values, $name_template, $id_template, (string) ( $field['unit'] ?? 'px' ) );
		} else {
			foreach ( array(
				'top'    => __( 'Top', 'lerm-admin-config' ),
				'right'  => __( 'Right', 'lerm-admin-config' ),
				'bottom' => __( 'Bottom', 'lerm-admin-config' ),
				'left'   => __( 'Left', 'lerm-admin-config' ),
			) as $key => $label ) {
				if ( self::flag( $field, $key, true ) ) {
					self::render_number_item( $field_name, $input_id, $key, $label, $values, $name_template, $id_template, (string) ( $field['unit'] ?? 'px' ) );
				}
			}
		}
		if ( self::flag( $field, 'style', true ) ) {
			self::render_select_item( $field_name, $input_id, 'style', __( 'Style', 'lerm-admin-config' ), self::border_styles(), (string) ( $values['style'] ?? 'solid' ), $name_template, $id_template );
		}
		if ( self::flag( $field, 'color', true ) ) {
			self::render_color_item( $field_name, $input_id, 'color', __( 'Color', 'lerm-admin-config' ), PageSchema::scalar_value( $values['color'] ?? '' ), $name_template, $id_template );
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_link_color_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_link_colors( $field );
		$values   = wp_parse_args( $data, $defaults );

		echo '<div class="lerm-composite"><div class="lerm-composite__grid lerm-composite__grid--compact">';
		foreach ( array(
			'color'   => __( 'Normal', 'lerm-admin-config' ),
			'hover'   => __( 'Hover', 'lerm-admin-config' ),
			'active'  => __( 'Active', 'lerm-admin-config' ),
			'visited' => __( 'Visited', 'lerm-admin-config' ),
			'focus'   => __( 'Focus', 'lerm-admin-config' ),
		) as $key => $label ) {
			if ( self::flag( $field, $key, 'color' === $key || 'hover' === $key ) ) {
				self::render_color_item( $field_name, $input_id, $key, $label, PageSchema::scalar_value( $values[ $key ] ?? '' ), $name_template, $id_template );
			}
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function render_background_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_background( $field );
		$values   = wp_parse_args( $data, $defaults );

		echo '<div class="lerm-composite"><div class="lerm-composite__grid">';
		if ( self::flag( $field, 'background_color', true ) ) {
			self::render_color_item( $field_name, $input_id, 'background-color', __( 'Color', 'lerm-admin-config' ), PageSchema::scalar_value( $values['background-color'] ?? '' ), $name_template, $id_template );
		}
		if ( self::flag( $field, 'background_gradient', false ) && self::flag( $field, 'background_gradient_color', true ) ) {
			self::render_color_item( $field_name, $input_id, 'background-gradient-color', __( 'Gradient To', 'lerm-admin-config' ), PageSchema::scalar_value( $values['background-gradient-color'] ?? '' ), $name_template, $id_template );
		}
		if ( self::flag( $field, 'background_gradient', false ) && self::flag( $field, 'background_gradient_direction', true ) ) {
			self::render_select_item( $field_name, $input_id, 'background-gradient-direction', __( 'Gradient Direction', 'lerm-admin-config' ), self::background_gradient_direction_choices(), PageSchema::scalar_value( $values['background-gradient-direction'] ?? '' ), $name_template, $id_template );
		}
		if ( self::flag( $field, 'background_image', true ) ) {
			echo '<div class="lerm-composite__item lerm-composite__item--full"><span class="lerm-composite__label">' . esc_html__( 'Image', 'lerm-admin-config' ) . '</span>';
			StructuredFieldTypes::render_media_control(
				array(
					'id'          => 'background-image',
					'button_text' => (string) ( $field['background_image_button_text'] ?? __( 'Choose image', 'lerm-admin-config' ) ),
				),
				$values['background-image'] ?? array(),
				self::sub_name( $field_name, 'background-image' ),
				self::sub_id( $input_id, 'background-image' ),
				self::name_attr( self::sub_template( $name_template, 'background-image' ) ),
				self::id_attr( self::sub_id_template( $id_template, 'background-image' ) )
			);
			echo '</div>';
		}
		foreach ( array(
			'background-position'   => array(
				'flag'    => 'background_position',
				'label'   => __( 'Position', 'lerm-admin-config' ),
				'choices' => self::background_position_choices(),
			),
			'background-repeat'     => array(
				'flag'    => 'background_repeat',
				'label'   => __( 'Repeat', 'lerm-admin-config' ),
				'choices' => self::background_repeat_choices(),
			),
			'background-attachment' => array(
				'flag'    => 'background_attachment',
				'label'   => __( 'Attachment', 'lerm-admin-config' ),
				'choices' => self::background_attachment_choices(),
			),
			'background-size'       => array(
				'flag'    => 'background_size',
				'label'   => __( 'Size', 'lerm-admin-config' ),
				'choices' => self::background_size_choices(),
			),
			'background-origin'     => array(
				'flag'    => 'background_origin',
				'label'   => __( 'Origin', 'lerm-admin-config' ),
				'choices' => self::background_origin_choices(),
			),
			'background-clip'       => array(
				'flag'    => 'background_clip',
				'label'   => __( 'Clip', 'lerm-admin-config' ),
				'choices' => self::background_clip_choices(),
			),
			'background-blend-mode' => array(
				'flag'    => 'background_blend_mode',
				'label'   => __( 'Blend Mode', 'lerm-admin-config' ),
				'choices' => self::background_blend_mode_choices(),
			),
		) as $key => $config ) {
			$default_enabled = in_array( $key, array( 'background-position', 'background-repeat', 'background-attachment', 'background-size' ), true );
			if ( self::flag( $field, (string) $config['flag'], $default_enabled ) ) {
				self::render_select_item( $field_name, $input_id, $key, (string) $config['label'], (array) $config['choices'], PageSchema::scalar_value( $values[ $key ] ?? '' ), $name_template, $id_template );
			}
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 * @return array<string, string>
	 */
	private static function sanitize_dimensions_value( array $field, $value ): array {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_dimensions( $field );

		return array(
			'width'  => self::numeric_fragment( $data['width'] ?? $defaults['width'] ),
			'height' => self::numeric_fragment( $data['height'] ?? $defaults['height'] ),
			'unit'   => self::sanitize_unit( $field, $data['unit'] ?? $defaults['unit'] ),
		);
	}

	/**
	 * @param mixed $value
	 * @return array<string, string>
	 */
	private static function sanitize_spacing_value( array $field, $value ): array {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_spacing( $field );
		$result   = array( 'unit' => self::sanitize_unit( $field, $data['unit'] ?? $defaults['unit'] ) );

		if ( self::flag( $field, 'all', false ) ) {
			$result['all'] = self::numeric_fragment( $data['all'] ?? $defaults['all'] );
			return $result;
		}

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $key ) {
			if ( self::flag( $field, $key, true ) ) {
				$result[ $key ] = self::numeric_fragment( $data[ $key ] ?? $defaults[ $key ] );
			}
		}

		return $result;
	}

	/**
	 * @param mixed $value
	 * @return array<string, string>
	 */
	private static function sanitize_border_value( array $field, $value ): array {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_border( $field );
		$result   = array(
			'style' => self::sanitize_border_style( $data['style'] ?? $defaults['style'] ),
			'color' => self::sanitize_color( $data['color'] ?? $defaults['color'] ),
		);

		if ( self::flag( $field, 'all', false ) ) {
			$result['all'] = self::numeric_fragment( $data['all'] ?? $defaults['all'] );
			return $result;
		}

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $key ) {
			if ( self::flag( $field, $key, true ) ) {
				$result[ $key ] = self::numeric_fragment( $data[ $key ] ?? $defaults[ $key ] );
			}
		}

		return $result;
	}

	/**
	 * @param mixed $value
	 * @return array<string, string>
	 */
	private static function sanitize_link_color_value( array $field, $value ): array {
		$data     = is_array( $value ) ? $value : array();
		$defaults = self::default_link_colors( $field );
		$result   = array();

		foreach ( array( 'color', 'hover', 'active', 'visited', 'focus' ) as $key ) {
			if ( self::flag( $field, $key, 'color' === $key || 'hover' === $key ) ) {
				$result[ $key ] = self::sanitize_color( $data[ $key ] ?? $defaults[ $key ] );
			}
		}

		return $result;
	}

	/**
	 * @param mixed $value
	 * @return array<string, mixed>
	 */
	private static function sanitize_background_value( array $field, $value ): array {
		$data = is_array( $value ) ? $value : array();

		return array(
			'background-color'              => self::sanitize_color( $data['background-color'] ?? '' ),
			'background-gradient-color'     => self::sanitize_color( $data['background-gradient-color'] ?? '' ),
			'background-gradient-direction' => self::sanitize_choice( self::background_gradient_direction_choices(), $data['background-gradient-direction'] ?? '' ),
			'background-image'              => self::sanitize_media_like( $data['background-image'] ?? array() ),
			'background-position'           => self::sanitize_choice( self::background_position_choices(), $data['background-position'] ?? '' ),
			'background-repeat'             => self::sanitize_choice( self::background_repeat_choices(), $data['background-repeat'] ?? '' ),
			'background-attachment'         => self::sanitize_choice( self::background_attachment_choices(), $data['background-attachment'] ?? '' ),
			'background-size'               => self::sanitize_choice( self::background_size_choices(), $data['background-size'] ?? '' ),
			'background-origin'             => self::sanitize_choice( self::background_origin_choices(), $data['background-origin'] ?? '' ),
			'background-clip'               => self::sanitize_choice( self::background_clip_choices(), $data['background-clip'] ?? '' ),
			'background-blend-mode'         => self::sanitize_choice( self::background_blend_mode_choices(), $data['background-blend-mode'] ?? '' ),
		);
	}

	private static function render_number_item( string $field_name, string $input_id, string $key, string $label, array $values, string $name_template, string $id_template, string $unit = '' ): void {
		echo '<label class="lerm-composite__item"><span class="lerm-composite__label">' . esc_html( $label ) . '</span><span class="lerm-composite__input">';
		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text"%4$s%5$s step="any">%6$s',
			esc_attr( self::sub_id( $input_id, $key ) ),
			esc_attr( self::sub_name( $field_name, $key ) ),
			esc_attr( self::numeric_fragment( $values[ $key ] ?? '' ) ),
			self::name_attr( self::sub_template( $name_template, $key ) ),
			self::id_attr( self::sub_id_template( $id_template, $key ) ),
			'' !== $unit ? '<span class="lerm-composite__unit">' . esc_html( $unit ) . '</span>' : ''
		);
		echo '</span></label>';
	}

	private static function render_color_item( string $field_name, string $input_id, string $key, string $label, string $value, string $name_template, string $id_template ): void {
		echo '<label class="lerm-composite__item"><span class="lerm-composite__label">' . esc_html( $label ) . '</span>';
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text lerm-color-field"%4$s%5$s>',
			esc_attr( self::sub_id( $input_id, $key ) ),
			esc_attr( self::sub_name( $field_name, $key ) ),
			esc_attr( $value ),
			self::name_attr( self::sub_template( $name_template, $key ) ),
			self::id_attr( self::sub_id_template( $id_template, $key ) )
		);
		echo '</label>';
	}

	private static function render_select_item( string $field_name, string $input_id, string $key, string $label, array $choices, string $value, string $name_template, string $id_template ): void {
		echo '<label class="lerm-composite__item"><span class="lerm-composite__label">' . esc_html( $label ) . '</span>';
		printf(
			'<select id="%1$s" name="%2$s" class="regular-text"%3$s%4$s>',
			esc_attr( self::sub_id( $input_id, $key ) ),
			esc_attr( self::sub_name( $field_name, $key ) ),
			self::name_attr( self::sub_template( $name_template, $key ) ),
			self::id_attr( self::sub_id_template( $id_template, $key ) )
		);
		foreach ( $choices as $choice_value => $choice_label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $choice_value ), selected( $value, (string) $choice_value, false ), esc_html( $choice_label ) );
		}
		echo '</select></label>';
	}

	private static function default_dimensions( array $field ): array {
		$units = self::units( $field );
		$base  = array(
			'width'  => '',
			'height' => '',
			'unit'   => $units[0] ?? 'px',
		);
		return is_array( $field['default'] ?? null ) ? wp_parse_args( $field['default'], $base ) : $base;
	}

	private static function default_spacing( array $field ): array {
		$units = self::units( $field );
		$base  = array(
			'top'    => '',
			'right'  => '',
			'bottom' => '',
			'left'   => '',
			'all'    => '',
			'unit'   => $units[0] ?? 'px',
		);
		return is_array( $field['default'] ?? null ) ? wp_parse_args( $field['default'], $base ) : $base;
	}

	private static function default_border( array $field ): array {
		$base = array(
			'top'    => '',
			'right'  => '',
			'bottom' => '',
			'left'   => '',
			'all'    => '',
			'style'  => 'solid',
			'color'  => '',
		);
		return is_array( $field['default'] ?? null ) ? wp_parse_args( $field['default'], $base ) : $base;
	}

	private static function default_link_colors( array $field ): array {
		$base = array(
			'color'   => '',
			'hover'   => '',
			'active'  => '',
			'visited' => '',
			'focus'   => '',
		);
		return is_array( $field['default'] ?? null ) ? wp_parse_args( $field['default'], $base ) : $base;
	}

	private static function default_background( array $field ): array {
		$base = array(
			'background-color'              => '',
			'background-gradient-color'     => '',
			'background-gradient-direction' => '',
			'background-image'              => array(),
			'background-position'           => '',
			'background-repeat'             => '',
			'background-attachment'         => '',
			'background-size'               => '',
			'background-origin'             => '',
			'background-clip'               => '',
			'background-blend-mode'         => '',
		);
		return is_array( $field['default'] ?? null ) ? wp_parse_args( $field['default'], $base ) : $base;
	}

	private static function units( array $field ): array {
		$unit_setting = $field['unit'] ?? true;
		if ( is_scalar( $unit_setting ) && ! is_bool( $unit_setting ) && '' !== (string) $unit_setting ) {
			return array( (string) $unit_setting );
		}
		$units = $field['units'] ?? array( 'px', '%', 'em' );
		if ( ! is_array( $units ) || empty( $units ) ) {
			return array( 'px' );
		}
		return array_values( array_filter( array_map( static fn( $unit ) => is_scalar( $unit ) ? trim( (string) $unit ) : '', $units ) ) );
	}

	private static function show_unit_select( array $field, array $units ): bool {
		return self::flag( $field, 'unit', true ) && self::flag( $field, 'show_units', true ) && count( $units ) > 1;
	}

	private static function sanitize_unit( array $field, $value ): string {
		$units = self::units( $field );
		$unit  = is_scalar( $value ) ? trim( (string) $value ) : '';
		return in_array( $unit, $units, true ) ? $unit : ( $units[0] ?? 'px' );
	}

	private static function sanitize_border_style( $value ): string {
		$style = is_scalar( $value ) ? (string) $value : '';
		return array_key_exists( $style, self::border_styles() ) ? $style : 'solid';
	}

	private static function sanitize_color( $value ): string {
		$color = sanitize_hex_color( PageSchema::scalar_value( $value ) );
		return $color ? $color : '';
	}

	private static function sanitize_choice( array $choices, $value ): string {
		$choice = is_scalar( $value ) ? (string) $value : '';
		return array_key_exists( $choice, $choices ) ? $choice : '';
	}

	private static function sanitize_media_like( $value ): array {
		if ( is_array( $value ) ) {
			$attachment_id = absint( $value['id'] ?? 0 );
			if ( $attachment_id > 0 ) {
				$url = (string) wp_get_attachment_url( $attachment_id );
				if ( '' !== $url ) {
					return array_filter(
						array(
							'id'        => $attachment_id,
							'url'       => $url,
							'thumbnail' => (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
						)
					);
				}
			}

			$url = esc_url_raw( PageSchema::scalar_value( $value['url'] ?? '', '', true ) );

			if ( '' !== $url ) {
				return array(
					'id'        => 0,
					'url'       => $url,
					'thumbnail' => esc_url_raw( PageSchema::scalar_value( $value['thumbnail'] ?? $url, '', true ) ),
				);
			}

			return array();
		}
		return array();
	}

	private static function flag( array $field, string $key, bool $fallback ): bool {
		return array_key_exists( $key, $field ) ? ! empty( $field[ $key ] ) : $fallback;
	}

	private static function numeric_fragment( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$string = trim( (string) $value );
		if ( '' === $string || ! is_numeric( $string ) ) {
			return '';
		}
		$number = (float) $string;
		return floor( $number ) === $number ? (string) (int) $number : rtrim( rtrim( sprintf( '%.4F', $number ), '0' ), '.' );
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

	private static function name_attr( string $template ): string {
		return '' !== $template ? ' data-name-template="' . esc_attr( $template ) . '"' : '';
	}

	private static function id_attr( string $template ): string {
		return '' !== $template ? ' data-id-template="' . esc_attr( $template ) . '"' : '';
	}

	private static function border_styles(): array {
		return array(
			'solid'  => __( 'Solid', 'lerm-admin-config' ),
			'dashed' => __( 'Dashed', 'lerm-admin-config' ),
			'dotted' => __( 'Dotted', 'lerm-admin-config' ),
			'double' => __( 'Double', 'lerm-admin-config' ),
			'inset'  => __( 'Inset', 'lerm-admin-config' ),
			'outset' => __( 'Outset', 'lerm-admin-config' ),
			'groove' => __( 'Groove', 'lerm-admin-config' ),
			'ridge'  => __( 'Ridge', 'lerm-admin-config' ),
			'none'   => __( 'None', 'lerm-admin-config' ),
		);
	}

	private static function background_position_choices(): array {
		return array(
			''              => __( 'Default', 'lerm-admin-config' ),
			'left top'      => __( 'Left Top', 'lerm-admin-config' ),
			'left center'   => __( 'Left Center', 'lerm-admin-config' ),
			'left bottom'   => __( 'Left Bottom', 'lerm-admin-config' ),
			'center top'    => __( 'Center Top', 'lerm-admin-config' ),
			'center center' => __( 'Center Center', 'lerm-admin-config' ),
			'center bottom' => __( 'Center Bottom', 'lerm-admin-config' ),
			'right top'     => __( 'Right Top', 'lerm-admin-config' ),
			'right center'  => __( 'Right Center', 'lerm-admin-config' ),
			'right bottom'  => __( 'Right Bottom', 'lerm-admin-config' ),
		);
	}

	private static function background_repeat_choices(): array {
		return array(
			''          => __( 'Default', 'lerm-admin-config' ),
			'repeat'    => __( 'Repeat', 'lerm-admin-config' ),
			'no-repeat' => __( 'No Repeat', 'lerm-admin-config' ),
			'repeat-x'  => __( 'Repeat Horizontally', 'lerm-admin-config' ),
			'repeat-y'  => __( 'Repeat Vertically', 'lerm-admin-config' ),
		);
	}

	private static function background_attachment_choices(): array {
		return array(
			''       => __( 'Default', 'lerm-admin-config' ),
			'scroll' => __( 'Scroll', 'lerm-admin-config' ),
			'fixed'  => __( 'Fixed', 'lerm-admin-config' ),
		);
	}

	private static function background_size_choices(): array {
		return array(
			''        => __( 'Default', 'lerm-admin-config' ),
			'cover'   => __( 'Cover', 'lerm-admin-config' ),
			'contain' => __( 'Contain', 'lerm-admin-config' ),
			'auto'    => __( 'Auto', 'lerm-admin-config' ),
		);
	}

	private static function background_origin_choices(): array {
		return array(
			''            => __( 'Default', 'lerm-admin-config' ),
			'padding-box' => __( 'Padding Box', 'lerm-admin-config' ),
			'border-box'  => __( 'Border Box', 'lerm-admin-config' ),
			'content-box' => __( 'Content Box', 'lerm-admin-config' ),
		);
	}

	private static function background_clip_choices(): array {
		return array(
			''            => __( 'Default', 'lerm-admin-config' ),
			'border-box'  => __( 'Border Box', 'lerm-admin-config' ),
			'padding-box' => __( 'Padding Box', 'lerm-admin-config' ),
			'content-box' => __( 'Content Box', 'lerm-admin-config' ),
		);
	}

	private static function background_blend_mode_choices(): array {
		return array(
			''         => __( 'Default', 'lerm-admin-config' ),
			'normal'   => __( 'Normal', 'lerm-admin-config' ),
			'multiply' => __( 'Multiply', 'lerm-admin-config' ),
			'screen'   => __( 'Screen', 'lerm-admin-config' ),
			'overlay'  => __( 'Overlay', 'lerm-admin-config' ),
			'darken'   => __( 'Darken', 'lerm-admin-config' ),
			'lighten'  => __( 'Lighten', 'lerm-admin-config' ),
		);
	}

	private static function background_gradient_direction_choices(): array {
		return array(
			''          => __( 'Default', 'lerm-admin-config' ),
			'to bottom' => __( 'Top to Bottom', 'lerm-admin-config' ),
			'to right'  => __( 'Left to Right', 'lerm-admin-config' ),
			'135deg'    => __( 'Top Left to Bottom Right', 'lerm-admin-config' ),
			'-135deg'   => __( 'Top Right to Bottom Left', 'lerm-admin-config' ),
		);
	}
}
