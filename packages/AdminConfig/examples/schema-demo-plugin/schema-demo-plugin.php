<?php
/**
 * Plugin Name: Admin Config Schema Demo
 * Description: Demonstrates options pages, comment/profile/taxonomy meta, network settings, and extension APIs with Lerm Admin Config.
 * Version: 0.1.0
 * Author: Lerm
 * License: GPL-2.0-or-later
 * Text Domain: lerm-admin-config-demo
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_candidates = array(
	__DIR__ . '/vendor/autoload.php',
	dirname( __DIR__, 2 ) . '/vendor/autoload.php',
	dirname( __DIR__, 4 ) . '/vendor/autoload.php',
);

$autoload = '';

foreach ( $autoload_candidates as $candidate ) {
	if ( file_exists( $candidate ) ) {
		$autoload = $candidate;
		break;
	}
}

if ( '' === $autoload ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p><strong>Admin Config Schema Demo:</strong> Composer autoload was not found.</p></div>';
		}
	);

	return;
}

require_once $autoload;

require_once __DIR__ . '/src/DemoExtensions.php';
require_once __DIR__ . '/src/SchemaDemoPlugin.php';

$runtime = \Lerm\AdminConfig\WordPress\PluginBootstrap::boot( __FILE__ );
\Lerm\AdminConfig\Examples\SchemaDemoPlugin::register( $runtime );

if ( is_admin() ) {
	$runtime->boot();
}
