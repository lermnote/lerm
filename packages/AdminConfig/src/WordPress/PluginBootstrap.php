<?php
/**
 * Plugin-install bootstrap for the admin config runtime.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PluginBootstrap {

	public static function boot( string $plugin_file ): Runtime {
		$runtime = Runtime::instance(
			new PluginAssetResolver( $plugin_file )
		);

		do_action( 'lerm_admin_config_booted', $runtime, 'plugin' );

		return $runtime;
	}
}
