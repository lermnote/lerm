<?php
/**
 * wp-env fixture theme for the embedded-mode example.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LERM_VERSION' ) ) {
	define( 'LERM_VERSION', '0.1.0' );
}

/**
 * Locate the package plugin main file inside wp-env.
 */
function lerm_admin_config_fixture_plugin_file(): ?string {
	$matches = glob( WP_PLUGIN_DIR . '/*/lerm-admin-config.php' );

	if ( false === $matches || empty( $matches ) ) {
		return null;
	}

	return (string) reset( $matches );
}

$package_plugin = lerm_admin_config_fixture_plugin_file();

if ( ! is_string( $package_plugin ) ) {
	return;
}

$package_root = dirname( $package_plugin );
$autoload     = $package_root . '/vendor/autoload.php';
$example_file = $package_root . '/examples/embedded-theme-demo/src/EmbeddedThemeDemo.php';

if ( ! is_file( $autoload ) || ! is_file( $example_file ) ) {
	return;
}

require_once $autoload;
require_once $example_file;

add_action(
	'after_setup_theme',
	static function () use ( $package_plugin ): void {
		\Lerm\AdminConfig\WordPress\EmbeddedBootstrap::boot(
			trailingslashit( plugins_url( 'assets', $package_plugin ) ),
			'LERM_VERSION',
			static function ( \Lerm\AdminConfig\WordPress\Runtime $runtime ): void {
				\Lerm\AdminConfig\Examples\EmbeddedThemeDemo::register( $runtime );
			}
		);
	},
	20
);
