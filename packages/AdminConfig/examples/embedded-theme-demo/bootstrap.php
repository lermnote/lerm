<?php
/**
 * Embedded-mode bootstrap example for themes.
 *
 * Drop this file into a theme and require it from functions.php.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_candidates = array(
	get_template_directory() . '/vendor/autoload.php',
	get_template_directory() . '/packages/AdminConfig/vendor/autoload.php',
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
			echo '<div class="notice notice-error"><p><strong>Admin Config Embedded Demo:</strong> Composer autoload was not found.</p></div>';
		}
	);

	return;
}

require_once $autoload;
require_once __DIR__ . '/src/EmbeddedThemeDemo.php';

add_action(
	'after_setup_theme',
	static function (): void {
		$runtime = \Lerm\AdminConfig\WordPress\EmbeddedBootstrap::boot(
			trailingslashit( get_template_directory_uri() ) . 'packages/AdminConfig/assets',
			'LERM_VERSION'
		);

		\Lerm\AdminConfig\Examples\EmbeddedThemeDemo::register( $runtime );

		if ( is_admin() ) {
			$runtime->boot();
		}
	},
	20
);
