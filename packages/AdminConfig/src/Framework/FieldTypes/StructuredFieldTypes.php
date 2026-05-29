<?php
/**
 * Structured admin field definitions.
 *
 * @package Lerm\AdminConfig
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
				$page->container_field_renderer()->render_fieldset( $field, $value, $field_name );
			},
			'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return NestedFieldSanitizer::sanitize_fieldset( $field, $value, $strict, $store );
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
			'render'             => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				$page->container_field_renderer()->render_group( $field, $value, $field_name );
			},
			'sanitize'           => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				return NestedFieldSanitizer::sanitize_group( $field, $value, $strict, $store );
			},
			'missing_submission' => static function (): array {
				return array(
					'apply' => true,
					'value' => array(),
				);
			},
			'client'             => array(
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
				unset( $page );

				self::render_media_control( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				unset( $page );

				self::render_media_control(
					$field,
					$value,
					$field_name,
					$input_id,
					self::name_attr( '' !== $name_template ? $name_template . '[id]' : '' ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				unset( $field, $strict, $store );

				return self::sanitize_media_value( $value );
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
				unset( $page );

				self::render_gallery_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				unset( $page );

				self::render_gallery_field(
					$field,
					$value,
					$field_name,
					$input_id,
					self::name_attr( '' !== $name_template ? $name_template . '[ids]' : '' ),
					self::id_attr( $id_template )
				);
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				unset( $field, $strict, $store );

				return self::sanitize_gallery_value( $value );
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
				self::render_nested_warning( __( 'Sorter fields cannot be nested inside a fieldset or group.', 'lerm-admin-config' ) );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): array {
				unset( $store );

				return self::sanitize_sorter_value( $field, $value, $strict );
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
				unset( $page );

				self::render_code_editor_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				unset( $page );

				self::render_code_editor_field( $field, $value, $field_name, $input_id, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
				unset( $field, $strict, $store );

				return self::sanitize_code_editor_value( $value );
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
				unset( $page );

				self::render_wp_editor_field( $field, $value, $field_name, (string) $field['id'] );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				unset( $page );

				self::render_wp_editor_field( $field, $value, $field_name, $input_id, false, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
				unset( $field, $strict, $store );

				return self::sanitize_wp_editor_value( $value );
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
		echo '<p class="description">' . esc_html__( 'Drag to reorder. Checked items stay enabled; unchecked items are hidden.', 'lerm-admin-config' ) . '</p>';
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

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 */
	private static function render_code_editor_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		printf(
			'<textarea id="%1$s" name="%2$s" class="large-text lerm-code-editor" rows="%3$s" placeholder="%4$s"%5$s%6$s>%7$s</textarea>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( (string) ( $field['rows'] ?? 10 ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			self::name_attr( $name_template ),
			self::id_attr( $id_template ),
			esc_textarea( PageSchema::scalar_value( $value ) )
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 */
	private static function render_wp_editor_field( array $field, $value, string $field_name, string $input_id, bool $rich_editor = true, string $name_template = '', string $id_template = '' ): void {
		if ( ! $rich_editor ) {
			printf(
				'<textarea id="%1$s" name="%2$s" class="large-text" rows="%3$s"%4$s%5$s>%6$s</textarea>',
				esc_attr( $input_id ),
				esc_attr( $field_name ),
				esc_attr( (string) ( $field['rows'] ?? 6 ) ),
				self::name_attr( $name_template ),
				self::id_attr( $id_template ),
				esc_textarea( PageSchema::scalar_value( $value ) )
			);
			return;
		}

		$editor_args = array_merge(
			array(
				'textarea_name' => $field_name,
				'textarea_rows' => 6,
			),
			(array) ( $field['editor_args'] ?? array() )
		);

		wp_editor(
			PageSchema::scalar_value( $value ),
			sanitize_html_class( 'lerm-' . $input_id ),
			$editor_args
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 */
	public static function render_media_control( array $field, $value, string $field_name, string $target, string $name_attr = '', string $id_attr = '' ): void {
		$attachment_id = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value );
		$image_url     = '';

		if ( $attachment_id > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $attachment_id, 'medium' );
		}

		if ( '' === $image_url && is_array( $value ) ) {
			$image_url = PageSchema::scalar_value( $value['thumbnail'] ?? $value['url'] ?? '' );
		}

		$button_text = (string) ( $field['button_text'] ?? __( 'Choose image', 'lerm-admin-config' ) );

		printf(
			'<div class="lerm-media-field" data-target="%1$s"><input type="hidden" name="%2$s[id]" value="%3$s"%8$s%9$s><div class="lerm-media-preview" %10$s>%4$s</div><div class="lerm-media-actions"><button type="button" class="button lerm-media-select">%5$s</button><button type="button" class="button button-secondary button-link-delete lerm-media-remove" %6$s>%7$s</button></div></div>',
			esc_attr( $target ),
			esc_attr( $field_name ),
			esc_attr( (string) $attachment_id ),
			$image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '',
			esc_html( $button_text ),
			$attachment_id > 0 ? '' : 'hidden',
			esc_html__( 'Remove', 'lerm-admin-config' ),
			$name_attr,
			$id_attr,
			$image_url ? '' : 'hidden'
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 */
	private static function render_gallery_field( array $field, $value, string $field_name, string $target, string $name_attr = '', string $id_attr = '' ): void {
		unset( $field );

		$ids = self::normalize_gallery_ids( $value );

		echo '<div class="lerm-gallery-field" data-target="' . esc_attr( $target ) . '">';
		echo '<input type="hidden" name="' . esc_attr( $field_name . '[ids]' ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '"' . $name_attr . $id_attr . '>';
		echo '<div class="lerm-gallery-preview" ' . ( empty( $ids ) ? 'hidden' : '' ) . '>';

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $attachment_id ) {
				$thumbnail = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

				if ( ! $thumbnail ) {
					continue;
				}

				echo '<img src="' . esc_url( $thumbnail ) . '" alt="">';
			}
		}

		echo '</div>';
		echo '<div class="lerm-media-actions">';
		echo '<button type="button" class="button lerm-gallery-select">' . esc_html__( 'Choose images', 'lerm-admin-config' ) . '</button>';
		echo '<button type="button" class="button button-secondary button-link-delete lerm-gallery-remove" ' . ( empty( $ids ) ? 'hidden' : '' ) . '>' . esc_html__( 'Clear gallery', 'lerm-admin-config' ) . '</button>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param mixed $value
	 * @return array<int, int>
	 */
	private static function normalize_gallery_ids( $value ): array {
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

		return array_values(
			array_filter(
				array_map( 'absint', $ids )
			)
		);
	}

	/**
	 * @param mixed $value
	 * @return array<string, mixed>
	 */
	private static function sanitize_media_value( $value ): array {
		$attachment_id = is_array( $value ) ? absint( $value['id'] ?? 0 ) : absint( $value );

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
	 * @param mixed $value
	 * @return array<int, int>
	 */
	private static function sanitize_gallery_value( $value ): array {
		return array_values( array_unique( self::normalize_gallery_ids( $value ) ) );
	}

	/**
	 * @param array<string, mixed> $field
	 * @param mixed                $value
	 * @return array<string, array<string, string>>
	 */
	private static function sanitize_sorter_value( array $field, $value, bool $strict ): array {
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
	 * @param mixed $value
	 */
	private static function sanitize_code_editor_value( $value ): string {
		return PageSchema::scalar_value( $value, '', true );
	}

	/**
	 * @param mixed $value
	 */
	private static function sanitize_wp_editor_value( $value ): string {
		return wp_kses_post( PageSchema::scalar_value( $value ) );
	}

	private static function render_nested_warning( string $message ): void {
		printf(
			'<p class="description" style="color:#b91c1c;font-style:italic">%s</p>',
			esc_html( $message )
		);
	}

	private static function name_attr( string $name_template ): string {
		return '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
	}

	private static function id_attr( string $id_template ): string {
		return '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
	}
}
