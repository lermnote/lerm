<?php
/**
 * Integration coverage for plugin and embedded bootstraps.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Integration;

use Lerm\AdminConfig\WordPress\EmbeddedBootstrap;
use Lerm\AdminConfig\WordPress\PluginBootstrap;
use Lerm\AdminConfig\WordPress\Runtime;

final class BootstrapIntegrationTest extends WpIntegrationTestCase {

	public function testPluginBootstrapFiresReadyHookAndRegistersSchemas(): void {
		$events   = array();
		$listener = static function ( Runtime $runtime, string $mode ) use ( &$events ): void {
			$events[] = array(
				'mode'    => $mode,
				'runtime' => $runtime,
			);
		};

		add_action( 'lerm_admin_config_booted', $listener, 10, 2 );

		$runtime = PluginBootstrap::boot(
			dirname( __DIR__, 2 ) . '/lerm-admin-config.php',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					self::make_store_schema(
						'integration-plugin-bootstrap',
						'option',
						'lerm_integration_plugin_bootstrap'
					)
				);
			}
		);

		remove_action( 'lerm_admin_config_booted', $listener, 10 );

		self::assertCount( 1, $events );
		self::assertSame( 'plugin', $events[0]['mode'] );
		self::assertSame( $runtime, $events[0]['runtime'] );
		self::assertTrue( $runtime->has( 'integration-plugin-bootstrap' ) );
	}

	public function testEmbeddedBootstrapFiresReadyHookAndRegistersSchemas(): void {
		$events   = array();
		$listener = static function ( Runtime $runtime, string $mode ) use ( &$events ): void {
			$events[] = array(
				'mode'    => $mode,
				'runtime' => $runtime,
			);
		};

		add_action( 'lerm_admin_config_booted', $listener, 10, 2 );

		$runtime = EmbeddedBootstrap::boot(
			trailingslashit( plugins_url( 'assets', dirname( __DIR__, 2 ) . '/lerm-admin-config.php' ) ),
			'LERM_VERSION',
			static function ( Runtime $runtime ): void {
				$runtime->register(
					self::make_store_schema(
						'integration-embedded-bootstrap',
						'option',
						'lerm_integration_embedded_bootstrap'
					)
				);
			}
		);

		remove_action( 'lerm_admin_config_booted', $listener, 10 );

		self::assertCount( 1, $events );
		self::assertSame( 'embedded', $events[0]['mode'] );
		self::assertSame( $runtime, $events[0]['runtime'] );
		self::assertTrue( $runtime->has( 'integration-embedded-bootstrap' ) );
	}
}
