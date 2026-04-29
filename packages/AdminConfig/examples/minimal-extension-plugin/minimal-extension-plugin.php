<?php
/**
 * Plugin Name: Admin Config Minimal Extension Example
 * Description: Minimal custom field, validator, and data-source example for Lerm Admin Config.
 * Version: 0.1.0
 * Author: Lerm
 * License: GPL-2.0-or-later
 * Text Domain: lerm-admin-config-minimal-extension
 */

declare( strict_types=1 );

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_candidates = array(
	__DIR__ . '/vendor/autoload.php',
	dirname( __DIR__, 2 ) . '/vendor/autoload.php',
	dirname( __DIR__, 4 ) . '/vendor/autoload.php',
);

$autoload = '';

foreach ( $autoload_candidates as $candidate ) {
	if ( file_exists( $candidate ) ) {
		$autoload = $candidate;
		break;
	}
}

if ( '' === $autoload ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p><strong>Admin Config Minimal Extension Example:</strong> Composer autoload was not found.</p></div>';
		}
	);

	return;
}

require_once $autoload;

PluginBootstrap::boot(
	__FILE__,
	static function ( Runtime $runtime ): void {
		if ( ! $runtime->has_data_source( 'badge_tones' ) ) {
			$runtime->register_data_source(
				'badge_tones',
				static fn (): array => array(
					'neutral' => __( 'Neutral', 'lerm-admin-config-minimal-extension' ),
					'bold'    => __( 'Bold', 'lerm-admin-config-minimal-extension' ),
					'calm'    => __( 'Calm', 'lerm-admin-config-minimal-extension' ),
				)
			);
		}

		$runtime->register_field_type(
			'badge_text',
			array(
				'render'   => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
					printf(
						'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s">',
						esc_attr( (string) ( $field['id'] ?? '' ) ),
						esc_attr( $field_name ),
						esc_attr( is_scalar( $value ) ? (string) $value : '' ),
						esc_attr( (string) ( $field['placeholder'] ?? 'Feature badge' ) )
					);
				},
				'sanitize' => static function ( array $field, $value, bool $strict, OptionStore $store ): string {
					return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
				},
				'client'   => array(
					'control' => 'badge_text',
				),
			)
		);

		$runtime->register_validator(
			'badge_text',
			static function ( array $field, $value, bool $strict, OptionStore $store ) {
				$label = trim( is_scalar( $value ) ? (string) $value : '' );

				if ( '' === $label ) {
					return new WP_Error(
						'lerm_admin_config_badge_required',
						__( 'Please enter a badge label.', 'lerm-admin-config-minimal-extension' )
					);
				}

				if ( strlen( $label ) < 3 ) {
					return new WP_Error(
						'lerm_admin_config_badge_too_short',
						__( 'Badge labels must be at least 3 characters long.', 'lerm-admin-config-minimal-extension' )
					);
				}

				return $label;
			}
		);

		$runtime->register(
			array(
				'id'        => 'acme-minimal-extension',
				'title'     => __( 'Minimal Extension Demo', 'lerm-admin-config-minimal-extension' ),
				'container' => array(
					'type' => 'options_page',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'acme_minimal_extension',
				),
				'menu'      => array(
					'parent_slug' => 'options-general.php',
					'page_title'  => __( 'Minimal Extension Demo', 'lerm-admin-config-minimal-extension' ),
					'menu_title'  => __( 'Minimal Extension Demo', 'lerm-admin-config-minimal-extension' ),
					'capability'  => 'manage_options',
				),
				'sections'  => array(
					'general' => array(
						'title'       => __( 'General', 'lerm-admin-config-minimal-extension' ),
						'description' => __( 'Smallest runnable example for extending the public runtime API.', 'lerm-admin-config-minimal-extension' ),
						'fields'      => array(
							array(
								'id'          => 'badge_label',
								'type'        => 'badge_text',
								'label'       => __( 'Badge label', 'lerm-admin-config-minimal-extension' ),
								'description' => __( 'Custom field type with a dedicated validator.', 'lerm-admin-config-minimal-extension' ),
								'default'     => __( 'Feature', 'lerm-admin-config-minimal-extension' ),
								'placeholder' => 'Feature',
							),
							array(
								'id'          => 'badge_tone',
								'type'        => 'select',
								'label'       => __( 'Badge tone', 'lerm-admin-config-minimal-extension' ),
								'description' => __( 'Choices resolved from a named runtime data source.', 'lerm-admin-config-minimal-extension' ),
								'choices'     => $runtime->resolve_data_source( 'badge_tones' ),
								'default'     => 'neutral',
							),
						),
					),
				),
			)
		);
	}
);
