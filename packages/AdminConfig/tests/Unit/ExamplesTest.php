<?php
/**
 * Example-registration behavior tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Examples\EmbeddedThemeDemo;
use Lerm\AdminConfig\Examples\SchemaDemoPlugin;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Runtime;

final class ExamplesTest extends TestCase {

	protected function tearDown(): void {
		Runtime::reset_instance();
		parent::tearDown();
	}

	public function testSchemaDemoPluginRegistersNetworkSchemaOnlyOnMultisite(): void {
		require_once dirname( __DIR__, 2 ) . '/examples/schema-demo-plugin/src/DemoExtensions.php';
		require_once dirname( __DIR__, 2 ) . '/examples/schema-demo-plugin/src/SchemaDemoPlugin.php';

		$runtime = new Runtime();
		SchemaDemoPlugin::register( $runtime );

		self::assertFalse( $runtime->has( 'acme-demo-network-settings' ) );

		Runtime::reset_instance();
		$GLOBALS['lerm_admin_config_is_multisite'] = true;

		$runtime = new Runtime();
		SchemaDemoPlugin::register( $runtime );

		self::assertTrue( $runtime->has( 'acme-demo-network-settings' ) );
	}

	public function testEmbeddedThemeDemoRegistersExpectedSchemas(): void {
		require_once dirname( __DIR__, 2 ) . '/examples/embedded-theme-demo/src/EmbeddedThemeDemo.php';

		$runtime = new Runtime();
		EmbeddedThemeDemo::register( $runtime );

		self::assertTrue( $runtime->has( 'acme-theme-style-kit' ) );
		self::assertTrue( $runtime->has( 'acme-theme-hero-metabox' ) );
	}
}
