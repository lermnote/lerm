<?php // phpcs:disable WordPress.Files.FileName
/**
 * Theme-owned schema registration for the extracted admin config runtime.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\Theme\AdminConfig;

use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeOptionsSchema {

	/**
	 * Cached schema definition.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $definition = null;

	/**
	 * Return the theme schema ID.
	 */
	public static function schema_id(): string {
		return (string) self::definition()['id'];
	}

	/**
	 * Return the normalized theme schema definition.
	 *
	 * @return array<string, mixed>
	 */
	public static function definition(): array {
		if ( null !== self::$definition ) {
			return self::$definition;
		}

		$definition              = ThemeOptionsDefinition::definition();
		$definition['container'] = wp_parse_args(
			is_array( $definition['container'] ?? null ) ? $definition['container'] : array(),
			array(
				'type' => 'options_page',
			)
		);
		$definition['store']     = wp_parse_args(
			is_array( $definition['store'] ?? null ) ? $definition['store'] : array(),
			array(
				'type' => 'option',
				'key'  => (string) ( $definition['option_name'] ?? ThemeOptionsDefinition::OPTION_NAME ),
			)
		);

		self::$definition = $definition;

		return self::$definition;
	}

	/**
	 * Register the schema with the shared runtime.
	 */
	public static function register( Runtime $runtime ): void {
		if ( $runtime->has( self::schema_id() ) ) {
			return;
		}

		$runtime->register( self::definition() );
	}

	/**
	 * Return sidebar choices including custom registered sidebars.
	 *
	 * @return array<string, string>
	 */
	public static function sidebar_choices(): array {
		return ThemeOptionsDefinition::sidebar_choices();
	}
}
