<?php
/**
 * Extension API demo registrations for the schema demo plugin.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Examples;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DemoExtensions {

	public static function register( Runtime $runtime ): void {
		if ( ! $runtime->has_data_source( 'tone_presets' ) ) {
			$runtime->register_data_source(
				'tone_presets',
				static function ( array $args = array() ): array {
					$include_experimental = ! empty( $args['experimental'] );
					$choices              = array(
						'calm'  => __( 'Calm', 'lerm-admin-config-demo' ),
						'bold'  => __( 'Bold', 'lerm-admin-config-demo' ),
						'clean' => __( 'Clean', 'lerm-admin-config-demo' ),
					);

					if ( $include_experimental ) {
						$choices['vivid'] = __( 'Vivid', 'lerm-admin-config-demo' );
					}

					return $choices;
				}
			);
		}

		if ( ! $runtime->has_data_source( 'campaign_library' ) ) {
			$runtime->register_data_source(
				'campaign_library',
				static function ( array $args = array() ): array {
					$catalog  = DemoExtensions::campaign_library_items();
					$search   = strtolower( trim( (string) ( $args['search'] ?? '' ) ) );
					$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
					$per_page = max( 1, (int) ( $args['per_page'] ?? 5 ) );
					$selected = is_array( $args['selected'] ?? null ) ? array_values( array_filter( array_map( 'strval', $args['selected'] ) ) ) : array();
					$filtered = array_values(
						array_filter(
							$catalog,
							static function ( array $item ) use ( $search ): bool {
								if ( '' === $search ) {
									return true;
								}

								$haystack = strtolower( trim( (string) ( $item['label'] ?? '' ) . ' ' . (string) ( $item['value'] ?? '' ) ) );

								return str_contains( $haystack, $search );
							}
						)
					);

					if ( ! empty( $selected ) && 1 === $page ) {
						usort(
							$filtered,
							static function ( array $left, array $right ) use ( $selected ): int {
								$left_index  = array_search( (string) ( $left['value'] ?? '' ), $selected, true );
								$right_index = array_search( (string) ( $right['value'] ?? '' ), $selected, true );

								if ( false !== $left_index && false !== $right_index ) {
									return $left_index <=> $right_index;
								}

								if ( false !== $left_index ) {
									return -1;
								}

								if ( false !== $right_index ) {
									return 1;
								}

								return strcasecmp( (string) ( $left['label'] ?? '' ), (string) ( $right['label'] ?? '' ) );
							}
						);
					}

					$offset = ( $page - 1 ) * $per_page;

					return array(
						'items' => array_slice( $filtered, $offset, $per_page ),
						'more'  => count( $filtered ) > $offset + $per_page,
					);
				}
			);
		}

		$runtime->register_field_type(
			'slug_text',
			array(
				'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
					$attrs = ! empty( $field['dependency_field'] ) ? ' data-lerm-controller="1"' : '';

					self::render_slug_text_input(
						$field,
						$value,
						$field_name,
						(string) ( $field['id'] ?? '' ),
						$attrs
					);
				},
				'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
					$attrs = '';

					if ( ! empty( $field['dependency_field'] ) ) {
						$attrs .= ' data-lerm-controller="1"';
					}

					if ( '' !== $name_template ) {
						$attrs .= ' data-name-template="' . esc_attr( $name_template ) . '"';
					}

					if ( '' !== $id_template ) {
						$attrs .= ' data-id-template="' . esc_attr( $id_template ) . '"';
					}

					self::render_slug_text_input(
						$field,
						$value,
						$field_name,
						$input_id,
						$attrs
					);
				},
				'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
					return sanitize_title( is_scalar( $value ) ? (string) $value : '' );
				},
				'client'        => array(
					'control' => 'slug_text',
					'nested'  => true,
				),
			)
		);

		$runtime->register_validator(
			'slug_text',
			static function ( array $field, $value, bool $strict, OptionStore $store ) {
				$slug = is_scalar( $value ) ? (string) $value : '';

				if ( '' === $slug ) {
					return new \WP_Error(
						'lerm_admin_config_demo_slug_required',
						__( 'Please enter a slug value.', 'lerm-admin-config-demo' )
					);
				}

				if ( strlen( $slug ) < 3 || strlen( $slug ) > 32 ) {
					return new \WP_Error(
						'lerm_admin_config_demo_slug_length',
						__( 'Slug values must be between 3 and 32 characters.', 'lerm-admin-config-demo' )
					);
				}

				return $slug;
			}
		);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function campaign_library_items(): array {
		return array(
			array(
				'value' => 'spring-launch',
				'label' => __( 'Spring Launch', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'creator-series',
				'label' => __( 'Creator Series', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'audio-week',
				'label' => __( 'Audio Week', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'design-sprint',
				'label' => __( 'Design Sprint', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'community-notes',
				'label' => __( 'Community Notes', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'pro-tools',
				'label' => __( 'Pro Tools', 'lerm-admin-config-demo' ),
			),
			array(
				'value' => 'studio-preview',
				'label' => __( 'Studio Preview', 'lerm-admin-config-demo' ),
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_slug_text_input( array $field, $value, string $field_name, string $input_id, string $extra_attrs = '' ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s" spellcheck="false" autocapitalize="off" autocorrect="off"%5$s>',
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( is_scalar( $value ) ? (string) $value : '' ),
			esc_attr( (string) ( $field['placeholder'] ?? 'spring-launch' ) ),
			$extra_attrs
		);
	}
}
