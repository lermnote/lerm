<?php
/**
 * Multisite integration coverage for the schema demo network container.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Integration;

use Lerm\AdminConfig\Examples\SchemaDemoPlugin;
use Lerm\AdminConfig\WordPress\Runtime;

final class MultisiteSchemaIntegrationTest extends WpIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Multisite integration requires a multisite WordPress environment.' );
		}
	}

	public function testSchemaDemoRegistersAndPersistsNetworkSettings(): void {
		delete_site_option( 'acme_demo_network_settings' );

		$runtime = $this->runtime();

		SchemaDemoPlugin::register( $runtime );

		self::assertTrue( $runtime->has( 'acme-demo-network-settings' ) );

		$store = $runtime->store( 'acme-demo-network-settings' );

		self::assertTrue(
			$store->import_all(
				array(
					'shared_presets'    => 1,
					'template_endpoint' => 'https://example.com/network-library.json',
					'shared_library'    => array(
						'feed_slug'    => 'network-library',
						'landing_path' => '/network/library',
					),
				)
			)
		);

		$stored = get_site_option( 'acme_demo_network_settings', array() );

		self::assertIsArray( $stored );
		self::assertSame( 'https://example.com/network-library.json', $stored['template_endpoint'] ?? null );
		self::assertSame( 'network-library', $stored['shared_library']['feed_slug'] ?? null );
		self::assertSame( '/network/library', $stored['shared_library']['landing_path'] ?? null );
	}
}
