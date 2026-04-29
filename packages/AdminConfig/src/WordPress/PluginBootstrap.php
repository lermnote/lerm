<?php
/**
 * Plugin-install bootstrap for the admin config runtime.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Framework\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PluginBootstrap {

	public static function boot( string $plugin_file, ?callable $registrar = null ): Runtime {
		$runtime = new Runtime(
			null,
			new Framework(
				new PluginAssetResolver( $plugin_file )
			)
		);

		if ( ! is_callable( $registrar ) ) {
			return $runtime;
		}

		$boot_runtime = static function () use ( $runtime, $registrar ): void {
			call_user_func( $registrar, $runtime );

			if ( is_admin() ) {
				$runtime->boot();
			}

			do_action( 'lerm_admin_config_booted', $runtime, 'plugin' );
		};

		if ( function_exists( 'did_action' ) && 0 === did_action( 'init' ) ) {
			add_action( 'init', $boot_runtime, 0 );
		} else {
			$boot_runtime();
		}

		return $runtime;
	}
}
