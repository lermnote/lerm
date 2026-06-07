<?php
/**
 * Smoke tests for the bundled examples.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Smoke;

use Lerm\AdminConfig\Examples\EmbeddedThemeDemo;
use Lerm\AdminConfig\Examples\SchemaDemoPlugin;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Runtime;

final class ExamplesSmokeTest extends TestCase {

	public function testExampleRegistrationsProduceExpectedSchemas(): void {
		$runtime = $this->runtime();

		require_once dirname( __DIR__, 2 ) . '/examples/schema-demo-plugin/src/DemoExtensions.php';
		require_once dirname( __DIR__, 2 ) . '/examples/schema-demo-plugin/src/SchemaDemoPlugin.php';
		require_once dirname( __DIR__, 2 ) . '/examples/embedded-theme-demo/src/EmbeddedThemeDemo.php';

		SchemaDemoPlugin::register( $runtime );
		EmbeddedThemeDemo::register( $runtime );

		$this->assertTrue( $runtime->has( 'acme-demo-settings' ) );
		$this->assertTrue( $runtime->has( 'acme-demo-post-metabox' ) );
		$this->assertTrue( $runtime->has( 'acme-demo-comment' ) );
		$this->assertTrue( $runtime->has( 'acme-demo-profile' ) );
		$this->assertTrue( $runtime->has( 'acme-demo-taxonomy' ) );
		$this->assertTrue( $runtime->has( 'acme-theme-style-kit' ) );
		$this->assertTrue( $runtime->has( 'acme-demo-block-editor-panel' ) );
		$this->assertTrue( $runtime->has( 'acme-theme-hero-metabox' ) );
		$this->assertTrue( $runtime->has_data_source( 'tone_presets' ) );
		$this->assertCount( 8, $runtime->schemas() );
	}
}
