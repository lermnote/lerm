<?php
/**
 * Advanced field definitions.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\FieldTypes\Support\NestedFieldSanitizer;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdvancedFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'typography' => self::typography_definition(),
			'icon'       => self::icon_definition(),
			'accordion'  => self::accordion_definition(),
			'tabbed'     => self::tabbed_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function typography_definition(): array {
		return array(
			'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->container_field_renderer()->render_fieldset( self::typography_field( $field ), $value, $field_name );
			},
			'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return NestedFieldSanitizer::sanitize_fieldset( self::typography_field( $field ), $value, $strict, $store );
			},
			'client'   => array(
				'control' => 'typography',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function icon_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_icon_field(
					$field,
					$value,
					$field_name,
					(string) $field['id'],
					$page->dependency_controller_attribute( $field )
				);
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_icon_field(
					$field,
					$value,
					$field_name,
					$input_id,
					'',
					self::name_attr( $name_template ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
				return self::sanitize_icon_value( $field, $value, $strict );
			},
			'client'        => array(
				'control' => 'icon',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function accordion_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->container_field_renderer()->render_accordion( $field, $value, $field_name );
			},
			'render_nested' => static function (): void {
				self::render_nested_warning( __( 'Accordion fields cannot be nested inside a fieldset or group.', 'lerm' ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return self::sanitize_panel_value( $field, $value, $strict, $store );
			},
			'client'        => array(
				'control' => 'accordion',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function tabbed_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->container_field_renderer()->render_tabbed( $field, $value, $field_name );
			},
			'render_nested' => static function (): void {
				self::render_nested_warning( __( 'Tabbed fields cannot be nested inside a fieldset or group.', 'lerm' ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return self::sanitize_panel_value( $field, $value, $strict, $store );
			},
			'client'        => array(
				'control' => 'tabbed',
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_icon_field( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '', string $name_attr = '', string $id_attr = '' ): void {
		$choices        = self::icon_choices( $field );
		$current        = self::sanitize_icon_value( $field, $value, false );
		$searchable     = ! array_key_exists( 'searchable', $field ) || ! empty( $field['searchable'] );
		$search_label   = (string) ( $field['search_label'] ?? __( 'Filter icons', 'lerm' ) );
		$empty_label    = (string) ( $field['empty_label'] ?? __( 'No icon selected', 'lerm' ) );
		$selected_label = $choices[ $current ] ?? ( '' !== $current ? $current : $empty_label );

		echo '<div class="lerm-icon-field" data-target="' . esc_attr( $input_id ) . '" data-empty-label="' . esc_attr( $empty_label ) . '">';
		echo '<div class="lerm-icon-field__toolbar">';

		if ( $searchable ) {
			printf(
				'<input type="search" class="regular-text lerm-icon-field__search" placeholder="%1$s" aria-label="%1$s">',
				esc_attr( $search_label )
			);
		}

		echo '<div class="lerm-icon-field__current" data-lerm-icon-current>';
		echo '<span class="lerm-icon-field__current-preview">';
		if ( '' !== $current ) {
			echo '<span class="dashicons ' . esc_attr( $current ) . '" aria-hidden="true"></span>';
		}
		echo '</span>';
		echo '<span class="lerm-icon-field__current-label" data-lerm-icon-current-label>' . esc_html( $selected_label ) . '</span>';
		echo '</div>';
		echo '</div>';

		echo '<fieldset class="lerm-icon-field__grid">';
		foreach ( $choices as $icon_class => $label ) {
			$choice_id = $input_id . '__' . sanitize_html_class( $icon_class );
			printf(
				'<label class="lerm-icon-field__item" data-icon-label="%1$s"><input type="radio" id="%2$s" name="%3$s" value="%4$s" %5$s%6$s%7$s%8$s><span class="lerm-icon-field__glyph"><span class="dashicons %4$s" aria-hidden="true"></span></span><span class="lerm-icon-field__label">%9$s</span></label>',
				esc_attr( strtolower( (string) $label . ' ' . $icon_class ) ),
				esc_attr( $choice_id ),
				esc_attr( $field_name ),
				esc_attr( $icon_class ),
				checked( $current, $icon_class, false ),
				$extra_attrs,
				$name_attr,
				$id_attr,
				esc_html( $label )
			);
		}
		echo '</fieldset></div>';
	}

	/**
	 * @param mixed $value
	 */
	private static function sanitize_icon_value( array $field, $value, bool $strict ): string {
		$choice  = self::sanitize_icon_class( PageSchema::scalar_value( $value, '', true ) );
		$choices = self::icon_choices( $field );
		$default = self::sanitize_icon_class( PageSchema::scalar_value( $field['default'] ?? '', '', true ) );

		if ( $strict && ! array_key_exists( $choice, $choices ) ) {
			return array_key_exists( $default, $choices ) ? $default : '';
		}

		if ( '' === $choice && array_key_exists( $default, $choices ) ) {
			return $default;
		}

		return $choice;
	}

	/**
	 * @param mixed $value
	 * @return array<string, array<string, mixed>>
	 */
	private static function sanitize_panel_value( array $field, $value, bool $strict, OptionStore $store ): array {
		$items  = self::panel_items( $field );
		$values = is_array( $value ) ? $value : array();
		$clean  = array();

		foreach ( $items as $item ) {
			$item_id           = (string) $item['id'];
			$item_fields       = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_value        = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$clean[ $item_id ] = NestedFieldSanitizer::sanitize_fieldset(
				array(
					'id'     => $item_id,
					'fields' => $item_fields,
				),
				$item_value,
				$strict,
				$store
			);
		}

		return $clean;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	private static function typography_field( array $field ): array {
		$config   = $field;
		$existing = is_scalar( $config['wrapper_class'] ?? null ) ? trim( (string) $config['wrapper_class'] ) : '';

		$config['fields']        = self::typography_controls( $field );
		$config['wrapper_class'] = trim( 'lerm-typography-field ' . $existing );

		if ( ! is_array( $config['default'] ?? null ) ) {
			$config['default'] = self::fieldset_defaults( $config['fields'] );
		}

		return $config;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	private static function typography_controls( array $field ): array {
		$units    = self::typography_units( $field );
		$defaults = is_array( $field['default'] ?? null ) ? $field['default'] : array();
		$fields   = array();

		if ( self::flag( $field, 'family', true ) ) {
			$fields[] = array(
				'id'          => 'font-family',
				'type'        => 'text',
				'label'       => __( 'Family', 'lerm' ),
				'placeholder' => (string) ( $field['family_placeholder'] ?? 'Inter, system-ui, sans-serif' ),
				'default'     => (string) ( $defaults['font-family'] ?? '' ),
			);
		}

		if ( self::flag( $field, 'weight', true ) ) {
			$fields[] = array(
				'id'      => 'font-weight',
				'type'    => 'select',
				'label'   => __( 'Weight', 'lerm' ),
				'choices' => self::font_weight_choices(),
				'default' => (string) ( $defaults['font-weight'] ?? '400' ),
			);
		}

		if ( self::flag( $field, 'style', false ) ) {
			$fields[] = array(
				'id'      => 'font-style',
				'type'    => 'button_set',
				'label'   => __( 'Style', 'lerm' ),
				'choices' => array(
					'normal' => __( 'Normal', 'lerm' ),
					'italic' => __( 'Italic', 'lerm' ),
				),
				'default' => (string) ( $defaults['font-style'] ?? 'normal' ),
			);
		}

		if ( self::flag( $field, 'size', true ) ) {
			$fields[] = array(
				'id'          => 'font-size',
				'type'        => 'text',
				'label'       => __( 'Size', 'lerm' ),
				'placeholder' => (string) ( $field['size_placeholder'] ?? '1' ),
				'default'     => (string) ( $defaults['font-size'] ?? '' ),
			);
		}

		if ( self::flag( $field, 'unit', true ) ) {
			$fields[] = array(
				'id'      => 'unit',
				'type'    => 'select',
				'label'   => __( 'Unit', 'lerm' ),
				'choices' => array_combine( $units, $units ),
				'default' => (string) ( $defaults['unit'] ?? ( $units[0] ?? 'px' ) ),
			);
		}

		if ( self::flag( $field, 'line_height', true ) ) {
			$fields[] = array(
				'id'          => 'line-height',
				'type'        => 'text',
				'label'       => __( 'Line height', 'lerm' ),
				'placeholder' => (string) ( $field['line_height_placeholder'] ?? '1.5' ),
				'default'     => (string) ( $defaults['line-height'] ?? '' ),
			);
		}

		if ( self::flag( $field, 'letter_spacing', false ) ) {
			$fields[] = array(
				'id'          => 'letter-spacing',
				'type'        => 'text',
				'label'       => __( 'Letter spacing', 'lerm' ),
				'placeholder' => (string) ( $field['letter_spacing_placeholder'] ?? '0' ),
				'default'     => (string) ( $defaults['letter-spacing'] ?? '' ),
			);
		}

		if ( self::flag( $field, 'transform', false ) ) {
			$fields[] = array(
				'id'      => 'text-transform',
				'type'    => 'select',
				'label'   => __( 'Transform', 'lerm' ),
				'choices' => array(
					'none'       => __( 'None', 'lerm' ),
					'uppercase'  => __( 'Uppercase', 'lerm' ),
					'lowercase'  => __( 'Lowercase', 'lerm' ),
					'capitalize' => __( 'Capitalize', 'lerm' ),
				),
				'default' => (string) ( $defaults['text-transform'] ?? 'none' ),
			);
		}

		if ( self::flag( $field, 'align', false ) ) {
			$fields[] = array(
				'id'      => 'text-align',
				'type'    => 'button_set',
				'label'   => __( 'Align', 'lerm' ),
				'choices' => array(
					'left'    => __( 'Left', 'lerm' ),
					'center'  => __( 'Center', 'lerm' ),
					'right'   => __( 'Right', 'lerm' ),
					'justify' => __( 'Justify', 'lerm' ),
				),
				'default' => (string) ( $defaults['text-align'] ?? 'left' ),
			);
		}

		if ( self::flag( $field, 'color', true ) ) {
			$fields[] = array(
				'id'      => 'color',
				'type'    => 'color',
				'label'   => __( 'Color', 'lerm' ),
				'default' => (string) ( $defaults['color'] ?? '' ),
			);
		}

		return $fields;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, string>
	 */
	private static function icon_choices( array $field ): array {
		$choices = PageSchema::choices( $field );

		if ( ! empty( $choices ) ) {
			return $choices;
		}

		return array(
			'dashicons-admin-site-alt3'  => __( 'Site', 'lerm' ),
			'dashicons-admin-appearance' => __( 'Appearance', 'lerm' ),
			'dashicons-admin-generic'    => __( 'Settings', 'lerm' ),
			'dashicons-admin-users'      => __( 'Users', 'lerm' ),
			'dashicons-admin-comments'   => __( 'Comments', 'lerm' ),
			'dashicons-admin-links'      => __( 'Links', 'lerm' ),
			'dashicons-format-image'     => __( 'Image', 'lerm' ),
			'dashicons-format-gallery'   => __( 'Gallery', 'lerm' ),
			'dashicons-format-audio'     => __( 'Audio', 'lerm' ),
			'dashicons-format-video'     => __( 'Video', 'lerm' ),
			'dashicons-star-filled'      => __( 'Star', 'lerm' ),
			'dashicons-heart'            => __( 'Heart', 'lerm' ),
			'dashicons-lightbulb'        => __( 'Idea', 'lerm' ),
			'dashicons-yes-alt'          => __( 'Check', 'lerm' ),
			'dashicons-warning'          => __( 'Warning', 'lerm' ),
			'dashicons-megaphone'        => __( 'Announcement', 'lerm' ),
			'dashicons-chart-bar'        => __( 'Chart', 'lerm' ),
			'dashicons-palmtree'         => __( 'Lifestyle', 'lerm' ),
			'dashicons-store'            => __( 'Store', 'lerm' ),
			'dashicons-portfolio'        => __( 'Portfolio', 'lerm' ),
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	private static function panel_items( array $field ): array {
		$items      = is_array( $field['items'] ?? null ) ? $field['items'] : array();
		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id    = isset( $item['id'] ) && is_scalar( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
			$item_title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? (string) $item['title'] : '';
			$item_id    = '' !== $item_id ? $item_id : 'item_' . (string) ( (int) $index + 1 );

			$normalized[] = array(
				'id'          => $item_id,
				'title'       => '' !== $item_title ? $item_title : ucfirst( str_replace( '_', ' ', $item_id ) ),
				'description' => isset( $item['description'] ) && is_scalar( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => is_array( $item['fields'] ?? null ) ? $item['fields'] : array(),
				'open'        => ! empty( $item['open'] ),
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @return array<string, mixed>
	 */
	private static function fieldset_defaults( array $fields ): array {
		$defaults = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['id'] ) ) {
				continue;
			}

			$defaults[ (string) $field['id'] ] = $field['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * @return array<int, string>
	 */
	private static function font_weight_choices(): array {
		return array(
			'300' => '300',
			'400' => '400',
			'500' => '500',
			'600' => '600',
			'700' => '700',
			'800' => '800',
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<int, string>
	 */
	private static function typography_units( array $field ): array {
		$units = $field['units'] ?? array( 'px', 'rem', 'em' );

		if ( ! is_array( $units ) || empty( $units ) ) {
			return array( 'px' );
		}

		$normalized = array_values(
			array_filter(
				array_map(
					static function ( $unit ): string {
						return is_scalar( $unit ) ? trim( (string) $unit ) : '';
					},
					$units
				)
			)
		);

		return ! empty( $normalized ) ? $normalized : array( 'px' );
	}

	private static function sanitize_icon_class( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		$parts = preg_split( '/\s+/', $value );
		$parts = is_array( $parts ) ? $parts : array( $value );
		$parts = array_values(
			array_filter(
				array_map(
					static function ( string $part ): string {
						return sanitize_html_class( $part );
					},
					$parts
				)
			)
		);

		return $parts[0] ?? '';
	}

	private static function flag( array $field, string $key, bool $fallback ): bool {
		return array_key_exists( $key, $field ) ? ! empty( $field[ $key ] ) : $fallback;
	}

	private static function render_nested_warning( string $message ): void {
		printf(
			'<p class="description" style="color:#b91c1c;font-style:italic">%s</p>',
			esc_html( $message )
		);
	}

	private static function name_attr( string $template ): string {
		return '' !== $template ? ' data-name-template="' . esc_attr( $template ) . '"' : '';
	}

	private static function id_attr( string $template ): string {
		return '' !== $template ? ' data-id-template="' . esc_attr( $template ) . '"' : '';
	}
}
