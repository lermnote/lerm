<?php
/**
 * Bootstrap behavior tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\EmbeddedBootstrap;
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\PluginAssetResolver;
use Lerm\AdminConfig\WordPress\Runtime;

final class BootstrapTest extends TestCase {

	public function testPluginBootstrapFiresReadyHookInEmbeddedlessUnitContext(): void {
		$events = array();

		add_action(
			'lerm_admin_config_booted',
			static function ( Runtime $runtime, string $mode ) use ( &$events ): void {
				$events[] = array(
					'mode'    => $mode,
					'runtime' => $runtime,
				);
			},
			10,
			2
		);

		$runtime = PluginBootstrap::boot(
			dirname( __DIR__, 2 ) . '/lerm-admin-config.php',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					array(
						'id'        => 'unit-plugin-bootstrap',
						'container' => array(
							'type' => 'options_page',
						),
						'store'     => array(
							'type' => 'option',
							'key'  => 'unit_plugin_bootstrap',
						),
						'sections'  => array(
							'general' => array(
								'fields' => array(
									array(
										'id'      => 'enabled',
										'type'    => 'switcher',
										'default' => 1,
									),
								),
							),
						),
					)
				);
			}
		);

		self::assertCount( 1, $events );
		self::assertSame( 'plugin', $events[0]['mode'] );
		self::assertSame( $runtime, $events[0]['runtime'] );
		self::assertTrue( $runtime->has( 'unit-plugin-bootstrap' ) );
	}

	public function testEmbeddedBootstrapFiresReadyHookInUnitContext(): void {
		$events = array();

		add_action(
			'lerm_admin_config_booted',
			static function ( Runtime $runtime, string $mode ) use ( &$events ): void {
				$events[] = array(
					'mode'    => $mode,
					'runtime' => $runtime,
				);
			},
			10,
			2
		);

		$runtime = EmbeddedBootstrap::boot(
			'https://example.test/theme/packages/AdminConfig/assets',
			'LERM_VERSION',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					array(
						'id'        => 'unit-embedded-bootstrap',
						'container' => array(
							'type' => 'options_page',
						),
						'store'     => array(
							'type' => 'option',
							'key'  => 'unit_embedded_bootstrap',
						),
						'sections'  => array(
							'general' => array(
								'fields' => array(
									array(
										'id'      => 'enabled',
										'type'    => 'switcher',
										'default' => 1,
									),
								),
							),
						),
					)
				);
			}
		);

		self::assertCount( 1, $events );
		self::assertSame( 'embedded', $events[0]['mode'] );
		self::assertSame( $runtime, $events[0]['runtime'] );
		self::assertTrue( $runtime->has( 'unit-embedded-bootstrap' ) );
	}

	public function testPluginAndEmbeddedBootstrapsUseIsolatedRuntimeInstances(): void {
		$plugin_runtime = PluginBootstrap::boot(
			dirname( __DIR__, 2 ) . '/lerm-admin-config.php',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					array(
						'id'       => 'unit-plugin-isolated',
						'sections' => array(),
					)
				);
			}
		);

		$embedded_runtime = EmbeddedBootstrap::boot(
			'https://example.test/theme/packages/AdminConfig/assets',
			'LERM_VERSION',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					array(
						'id'       => 'unit-embedded-isolated',
						'sections' => array(),
					)
				);
			}
		);

		self::assertNotSame( $plugin_runtime, $embedded_runtime );
		self::assertTrue( $plugin_runtime->has( 'unit-plugin-isolated' ) );
		self::assertFalse( $plugin_runtime->has( 'unit-embedded-isolated' ) );
		self::assertTrue( $embedded_runtime->has( 'unit-embedded-isolated' ) );
		self::assertFalse( $embedded_runtime->has( 'unit-plugin-isolated' ) );
	}

	public function testPluginAssetResolverFallsBackToPackageAssetsWhenHostPluginHasNoAssets(): void {
		$resolver = new PluginAssetResolver(
			dirname( __DIR__, 2 ) . '/examples/schema-demo-plugin/schema-demo-plugin.php'
		);

		self::assertSame(
			'https://example.test/plugins/AdminConfig/assets/admin-config.js',
			$resolver->url( 'admin-config.js' )
		);
	}
}
