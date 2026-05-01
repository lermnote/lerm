<?php
/**
 * Plugin Name: Lerm Admin Config
 * Plugin URI:  https://lerm.net
 * Description: Schema-driven WordPress admin configuration infrastructure for options, metadata, and profile surfaces.
 * Version:     0.2.1
 * Author:      Lerm
 * License:     GPL-2.0-or-later
 * Text Domain: lerm-admin-config
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LERM_ADMIN_CONFIG_VERSION' ) ) {
	define( 'LERM_ADMIN_CONFIG_VERSION', '0.2.1' );
}

$autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
	add_action(
		'admin_notices',
		static function () use ( $autoload ) {
			printf(
				'<div class="notice notice-error"><p><strong>Lerm Admin Config:</strong> %s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: autoload file path */
						__( 'Composer autoload not found: %s. Please install the plugin build with vendor files.', 'lerm-admin-config' ),
						$autoload
					)
				)
			);
		}
	);

	return;
}

require_once $autoload;

\Lerm\AdminConfig\WordPress\PluginBootstrap::boot( __FILE__ );
