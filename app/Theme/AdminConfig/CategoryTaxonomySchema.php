<?php // phpcs:disable WordPress.Files.FileName
/**
 * Category archive term-settings schema.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CategoryTaxonomySchema {

	/**
	 * Cached schema definition.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $definition = null;

	public static function schema_id(): string {
		return 'lerm-category-taxonomy';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		if ( null !== self::$definition ) {
			return self::$definition;
		}

		self::$definition = array(
			'id'        => self::schema_id(),
			'title'     => __( 'Archive Settings', 'lerm' ),
			'container' => array(
				'type'       => 'taxonomy',
				'title'      => __( 'Archive Settings', 'lerm' ),
				'taxonomy'   => array( 'category' ),
				'capability' => 'manage_categories',
			),
			'store'     => array(
				'type' => 'term_meta',
				'key'  => 'lerm_taxonomy_options',
			),
			'sections'  => array(
				'appearance' => array(
					'title'       => __( 'Archive Header', 'lerm' ),
					'description' => __( 'Control the category archive heading colors and background image.', 'lerm' ),
					'fields'      => array(
						array(
							'id'          => 'archive_color',
							'type'        => 'fieldset',
							'label'       => __( 'Header Colors', 'lerm' ),
							'description' => __( 'Background and text colors for the archive header card.', 'lerm' ),
							'fields'      => array(
								array(
									'id'      => 'bg_color',
									'type'    => 'color',
									'label'   => __( 'Background', 'lerm' ),
									'default' => '#ffffff',
								),
								array(
									'id'      => 'font_color',
									'type'    => 'color',
									'label'   => __( 'Text', 'lerm' ),
									'default' => '#5d6777',
								),
							),
							'default'     => array(
								'bg_color'   => '#ffffff',
								'font_color' => '#5d6777',
							),
						),
						array(
							'id'          => 'archive_header_image',
							'type'        => 'media',
							'label'       => __( 'Header Background Image', 'lerm' ),
							'description' => __( 'Optional image layered behind the archive header colors.', 'lerm' ),
							'default'     => array(),
						),
					),
				),
			),
		);

		return self::$definition;
	}

	public static function register( ?Runtime $runtime = null ): void {
		$runtime = $runtime ?? Runtime::instance();

		if ( $runtime->has( self::schema_id() ) ) {
			return;
		}

		$runtime->register( self::definition() );
	}
}
