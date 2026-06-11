<?php
/**
 * Theme-owned AdminConfig consumer contract tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Tests\Support\TestCase;

final class ThemeConsumerContractTest extends TestCase {

	private const BOOTSTRAP_CLASS          = 'Lerm\\Theme\\AdminConfig\\Bootstrap';
	private const THEME_OPTIONS_SCHEMA     = 'Lerm\\Theme\\AdminConfig\\ThemeOptionsSchema';
	private const LAYOUT_METABOX_SCHEMA    = 'Lerm\\Theme\\AdminConfig\\LayoutMetaboxSchema';
	private const CATEGORY_TAXONOMY_SCHEMA = 'Lerm\\Theme\\AdminConfig\\CategoryTaxonomySchema';
	private const PROFILE_SETTINGS_SCHEMA  = 'Lerm\\Theme\\AdminConfig\\ProfileSettingsSchema';
	private const THEME_OPTIONS_DEFINITION = 'Lerm\\Theme\\AdminConfig\\ThemeOptionsDefinition';

	public function testThemeAdminConfigRegistersExpectedSchemas(): void {
		$this->skip_if_theme_consumer_is_unavailable();

		$runtime                  = $this->runtime();
		$theme_options_schema     = $this->schema_id( self::THEME_OPTIONS_SCHEMA );
		$layout_metabox_schema    = $this->schema_id( self::LAYOUT_METABOX_SCHEMA );
		$category_taxonomy_schema = $this->schema_id( self::CATEGORY_TAXONOMY_SCHEMA );
		$profile_settings_schema  = $this->schema_id( self::PROFILE_SETTINGS_SCHEMA );

		$this->call_static( self::BOOTSTRAP_CLASS, 'register', $runtime );

		self::assertTrue( $runtime->has( $theme_options_schema ) );
		self::assertTrue( $runtime->has( $layout_metabox_schema ) );
		self::assertTrue( $runtime->has( $category_taxonomy_schema ) );
		self::assertTrue( $runtime->has( $profile_settings_schema ) );
		self::assertTrue( $runtime->field_types()->has( 'avatar_media_id' ) );

		self::assertSame(
			array(
				'type' => 'option',
				'key'  => 'lerm_theme_options',
			),
			$runtime->compiled( $theme_options_schema )->store()
		);
		self::assertSame( 'lerm_profile_meta', $runtime->compiled( $profile_settings_schema )->store()['type'] );

		$runtime->boot();

		self::assertArrayHasKey( 'admin_post_lerm_admin_config_save_lerm-theme-settings', $GLOBALS['lerm_admin_config_actions'] );
	}

	public function testThemeProfileStorePersistsDistributedUserMeta(): void {
		$this->skip_if_theme_consumer_is_unavailable();

		$runtime                 = $this->runtime();
		$profile_settings_schema = $this->schema_id( self::PROFILE_SETTINGS_SCHEMA );

		$this->call_static( self::PROFILE_SETTINGS_SCHEMA, 'register', $runtime );

		$store = $runtime->store(
			$profile_settings_schema,
			array(
				'user_id' => 42,
			)
		);

		self::assertTrue(
			$store->import_all(
				array(
					'avatar_id' => '15',
					'gender'    => 'female',
					'address'   => '  Mars Base  ',
				)
			)
		);

		self::assertSame( 15, $GLOBALS['lerm_admin_config_user_meta'][42]['avatar_id'] );
		self::assertSame( 'female', $GLOBALS['lerm_admin_config_user_meta'][42]['gender'] );
		self::assertSame( 'Mars Base', $GLOBALS['lerm_admin_config_user_meta'][42]['address'] );
	}

	public function testThemeOptionsSectionOrderMatchesConfigFiles(): void {
		$this->skip_if_theme_consumer_is_unavailable();

		$definition_class = self::THEME_OPTIONS_DEFINITION;

		if ( ! class_exists( $definition_class ) ) {
			self::markTestSkipped( 'Theme options definition class is not available in this checkout.' );
		}

		/** @var class-string $definition_class */
		$reflection       = new \ReflectionClass( $definition_class );
		$ordered_sections = $reflection->getConstant( 'SECTION_ORDER' );
		$section_files    = glob( dirname( __DIR__, 4 ) . '/app/Theme/AdminConfig/config/sections/*.php' );

		self::assertIsArray( $ordered_sections );
		self::assertIsArray( $section_files );

		$section_file_ids = array_map(
			static fn ( string $path ): string => basename( $path, '.php' ),
			$section_files
		);

		sort( $section_file_ids );

		$ordered_section_ids = array_values( array_map( 'strval', $ordered_sections ) );
		$sorted_order        = $ordered_section_ids;
		sort( $sorted_order );

		self::assertSame( $section_file_ids, $sorted_order );
		self::assertSame( $ordered_section_ids, array_keys( $this->call_static( self::THEME_OPTIONS_DEFINITION, 'sections' ) ) );
	}

	private function skip_if_theme_consumer_is_unavailable(): void {
		if ( ! class_exists( self::BOOTSTRAP_CLASS ) ) {
			self::markTestSkipped( 'Theme AdminConfig consumer classes are not available in this checkout.' );
		}
	}

	private function schema_id( string $schema_class ): string {
		return (string) $this->call_static( $schema_class, 'schema_id' );
	}

	/**
	 * @return mixed
	 */
	private function call_static( string $class_name, string $method, ...$args ) {
		return call_user_func_array( array( $class_name, $method ), $args );
	}
}
