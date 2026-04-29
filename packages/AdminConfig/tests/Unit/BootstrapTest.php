<?php
/**
 * Bootstrap behavior tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
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

	public function testOptionsPageEnqueuesBuiltScriptAssetWhenAvailable(): void {
		$definition  = array(
			'id'       => 'unit-build-asset',
			'store'    => array(
				'key' => 'unit_build_asset',
			),
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'      => 'headline',
							'type'    => 'text',
							'default' => '',
						),
					),
				),
			),
		);
		$field_types = new FieldTypeRegistry();
		$store       = new OptionStore( $definition, $field_types );
		$resolver    = new class() implements AssetResolver {
			public function url( string $filename ): string {
				return 'https://example.test/assets/' . ltrim( $filename, '/' );
			}

			public function version(): string {
				return 'unit-version';
			}
		};
		$page        = new OptionsPage( $definition, $store, $field_types, $resolver, false );

		$page->enqueue_support_assets( 'unit-build-asset' );

		$script = $GLOBALS['lerm_admin_config_enqueued_scripts']['lerm-admin-config-js-unit-build-asset'] ?? null;
		$asset  = require dirname( __DIR__, 2 ) . '/assets/build/admin-config.asset.php';

		$this->assertIsArray( $script );
		$this->assertSame( 'https://example.test/assets/build/admin-config.js', $script['src'] );
		$this->assertSame( (string) $asset['version'], $script['version'] );
		$this->assertContains( 'wp-theme-plugin-editor', $script['dependencies'] );
		$this->assertContains( 'wp-api-fetch', $script['dependencies'] );
		$this->assertSame(
			'https://example.test/wp-json/lerm-admin-config/v1/',
			$GLOBALS['lerm_admin_config_localized_scripts']['lerm-admin-config-js-unit-build-asset']['lermAdminConfig']['restUrl']
		);
	}
}
