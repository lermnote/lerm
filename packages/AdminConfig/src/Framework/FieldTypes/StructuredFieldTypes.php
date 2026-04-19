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
				$page->render_notice_field( $field );
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
				$page->render_sorter_field( $field, $value );
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
