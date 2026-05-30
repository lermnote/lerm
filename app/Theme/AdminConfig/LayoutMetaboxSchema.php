<?php // phpcs:disable WordPress.Files.FileName
/**
 * Post/page layout metabox schema registration.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LayoutMetaboxSchema {

	/**
	 * Cached metabox definition.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $definition = null;

	public static function schema_id(): string {
		return 'lerm-layout-metabox';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		if ( null !== self::$definition ) {
			return self::$definition;
		}

		$theme_options = get_option( ThemeOptionsDefinition::OPTION_NAME, array() );
		$theme_options = is_array( $theme_options ) ? $theme_options : array();
		$global_layout = (string) ( $theme_options['global_layout'] ?? 'layout-2c-r' );
		$image_path    = trailingslashit( get_template_directory_uri() ) . 'assets/img/';

		self::$definition = array(
			'id'        => self::schema_id(),
			'title'     => __( 'Post Options', 'lerm' ),
			'container' => array(
				'type'       => 'metabox',
				'title'      => __( 'Post Options', 'lerm' ),
				'post_types' => array( 'post', 'page' ),
				'context'    => 'side',
				'priority'   => 'default',
				'capability' => 'edit_post',
			),
			'store'     => array(
				'type' => 'post_meta',
				'key'  => '_lerm_metabox_options',
			),
			'sections'  => array(
				'general' => array(
					'title'  => __( 'Layout', 'lerm' ),
					'fields' => array(
						array(
							'id'          => 'page_layout',
							'type'        => 'image_select',
							'label'       => __( 'Layout', 'lerm' ),
							'description' => __( 'Choose the content/sidebar layout for this entry.', 'lerm' ),
							'choices'     => array(
								'layout-1c'        => $image_path . '1c.png',
								'layout-1c-narrow' => $image_path . '1c-narrow.png',
								'layout-2c-l'      => $image_path . '2c-l.png',
								'layout-2c-r'      => $image_path . '2c-r.png',
							),
							'default'     => $global_layout,
						),
						array(
							'id'          => 'sidebar_select',
							'type'        => 'select',
							'label'       => __( 'Sidebar', 'lerm' ),
							'description' => __( 'Choose a sidebar for this entry.', 'lerm' ),
							'choices'     => static fn(): array => ThemeOptionsSchema::sidebar_choices(),
							'default'     => 'home-sidebar',
						),
					),
				),
			),
		);

		return self::$definition;
	}

	public static function register( Runtime $runtime ): void {
		if ( $runtime->has( self::schema_id() ) ) {
			return;
		}

		$runtime->register( self::definition() );
	}
}
