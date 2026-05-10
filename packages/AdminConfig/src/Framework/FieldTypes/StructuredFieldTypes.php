<?php
/**
 * Structured admin field definitions.
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

final class StructuredFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'notice'      => self::notice_definition(),
			'fieldset'    => self::fieldset_definition(),
			'group'       => self::group_definition(),
			'media'       => self::media_definition(),
			'gallery'     => self::gallery_definition(),
			'sorter'      => self::sorter_definition(),
			'code_editor' => self::code_editor_definition(),
			'wp_editor'   => self::wp_editor_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function notice_definition(): array {
		return array(
			'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				unset( $value, $field_name, $page );

				self::render_notice_field( $field );
			},
			'sanitize' => static function () {
				return '';
			},
			'persist'  => false,
			'client'   => array(
				'control' => 'notice',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function fieldset_definition(): array {
		return array(
			'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_fieldset_field( $field, $value, $field_name );
			},
			'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return $store->sanitize_fieldset_field( $field, $value, $strict );
			},
			'client'   => array(
				'control' => 'fieldset',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_definition(): array {
		return array(
			'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_group_field( $field, $value, $field_name );
			},
			'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return $store->sanitize_group_field( $field, $value, $strict );
			},
			'client'   => array(
				'control' => 'group',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function media_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_media_field( $field, $value, $field_name );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				$name_attr = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template . '[id]' ) . '"' : '';
				$page->render_media_field( $field, $value, $field_name, $input_id, $name_attr, self::id_attr( $id_template ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return $store->sanitize_media_field( $value );
			},
			'client'        => array(
				'control' => 'media',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function gallery_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_gallery_field( $field, $value, $field_name );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				$name_attr = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template . '[ids]' ) . '"' : '';
				$page->render_gallery_field( $field, $value, $field_name, $input_id, $name_attr, self::id_attr( $id_template ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return $store->sanitize_gallery_field( $value );
			},
			'client'        => array(
				'control' => 'gallery',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function sorter_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				unset( $page );

				self::render_sorter_field( $field, $value, $field_name );
			},
			'render_nested' => static function (): void {
				self::render_nested_warning( __( 'Sorter fields cannot be nested inside a fieldset or group.', 'lerm' ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return $store->sanitize_sorter_field( $field, $value, $strict );
			},
			'client'        => array(
				'control' => 'sorter',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function code_editor_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_code_editor_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				$page->render_code_editor_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
				return $store->sanitize_code_editor_field( $value );
			},
			'client'        => array(
				'control' => 'code_editor',
				'nested'  => true,
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function wp_editor_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->render_wp_editor_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				$page->render_wp_editor_field( $field, $value, $field_name, $input_id, false, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
				return $store->sanitize_wp_editor_field( $value );
			},
			'client'        => array(
				'control' => 'wp_editor',
				'nested'  => true,
			),
		);
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private static function render_notice_field( array $field ): void {
		$html = isset( $field['html'] ) && is_scalar( $field['html'] ) ? (string) $field['html'] : '';

		if ( '' === trim( $html ) ) {
			return;
		}

		echo '<div class="lerm-settings-notice">';
		echo wp_kses(
			$html,
			array(
				'a'      => array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
					'class'  => true,
				),
				'br'     => array(),
				'code'   => array(),
				'em'     => array(),
				'p'      => array(
					'class' => true,
				),
				'span'   => array(
					'class' => true,
				),
				'strong' => array(),
			)
		);
		echo '</div>';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 */
	private static function render_sorter_field( array $field, $value, string $field_name ): void {
		$state      = self::sorter_state( $field, $value );
		$field_id   = (string) $field['id'];
		$order      = $state['order'];
		$enabled    = $state['enabled'];
		$label_text = (string) ( $field['label'] ?? '' );

		echo '<div class="lerm-sorter" data-target="' . esc_attr( $field_id ) . '">';
		echo '<p class="description">' . esc_html__( 'Drag to reorder. Checked items stay enabled; unchecked items are hidden.', 'lerm' ) . '</p>';
		echo '<ul class="lerm-sorter-list">';

		foreach ( $order as $key => $label ) {
			$is_enabled = in_array( $key, $enabled, true );
			echo '<li class="lerm-sorter-item">';
			echo '<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>';
			echo '<input type="hidden" name="' . esc_attr( $field_name . '[order][]' ) . '" value="' . esc_attr( $key ) . '">';
			echo '<label>';
			echo '<input type="checkbox" name="' . esc_attr( $field_name . '[enabled][]' ) . '" value="' . esc_attr( $key ) . '" ' . checked( $is_enabled, true, false ) . '>';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '</label>';
			echo '</li>';
		}

		echo '</ul>';
		echo '<span class="screen-reader-text">' . esc_html( $label_text ) . '</span>';
		echo '</div>';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 * @return array{order: array<string, string>, enabled: array<int, string>}
	 */
	private static function sorter_state( array $field, $value ): array {
		$choices = PageSchema::choices( $field );
		$order   = array();
		$enabled = array();

		if ( is_array( $value ) ) {
			if ( isset( $value['order'] ) && is_array( $value['order'] ) ) {
				$order_keys = array_map( 'strval', $value['order'] );
				$enabled    = isset( $value['enabled'] ) && is_array( $value['enabled'] )
					? array_map( 'strval', $value['enabled'] )
					: array();

				foreach ( $order_keys as $key ) {
					if ( isset( $choices[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
					}
				}
			} else {
				$enabled_values  = is_array( $value['enabled'] ?? null ) ? $value['enabled'] : array();
				$disabled_values = is_array( $value['disabled'] ?? null ) ? $value['disabled'] : array();

				foreach ( array_keys( $enabled_values ) as $key ) {
					if ( isset( $choices[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
						$enabled[]     = $key;
					}
				}

				foreach ( array_keys( $disabled_values ) as $key ) {
					if ( isset( $choices[ $key ] ) && ! isset( $order[ $key ] ) ) {
						$order[ $key ] = $choices[ $key ];
					}
				}
			}
		}

		foreach ( $choices as $key => $label ) {
			if ( ! isset( $order[ $key ] ) ) {
				$order[ $key ] = $label;
			}
		}

		return array(
			'order'   => $order,
			'enabled' => $enabled,
		);
	}

	private static function render_nested_warning( string $message ): void {
		printf(
			'<p class="description" style="color:#b91c1c;font-style:italic">%s</p>',
			esc_html( $message )
		);
	}

	private static function id_attr( string $id_template ): string {
		return '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
	}
}
