<?php
/**
 * Extension API demo registrations for the schema demo plugin.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Examples;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Stores\OptionStore;
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

		$runtime->register_field_type(
			'slug_text',
			array(
				'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
					$field_id = (string) ( $field['id'] ?? '' );

					printf(
						'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s" spellcheck="false" autocapitalize="off" autocorrect="off" %5$s>',
						esc_attr( $field_id ),
						esc_attr( $field_name ),
						esc_attr( is_scalar( $value ) ? (string) $value : '' ),
						esc_attr( (string) ( $field['placeholder'] ?? 'spring-launch' ) ),
						! empty( $field['dependency_field'] ) ? 'data-lerm-controller="1"' : ''
					);
				},
				'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
					return sanitize_title( is_scalar( $value ) ? (string) $value : '' );
				},
				'client'   => array(
					'control' => 'slug_text',
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
}
