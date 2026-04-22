<?php
/**
 * Prepare wp-env with the package plugin, schema demo plugin, and embedded theme.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

/**
 * Locate a plugin main file by basename.
 */
function lerm_admin_config_find_plugin_file( string $basename ): ?string {
	$matches = glob( WP_PLUGIN_DIR . '/*/' . $basename );

	if ( false === $matches || empty( $matches ) ) {
		return null;
	}

	return (string) reset( $matches );
}

$package_plugin = lerm_admin_config_find_plugin_file( 'lerm-admin-config.php' );
$demo_plugin    = lerm_admin_config_find_plugin_file( 'schema-demo-plugin.php' );

if ( is_string( $package_plugin ) ) {
	activate_plugin( plugin_basename( $package_plugin ) );
}

if ( is_string( $demo_plugin ) ) {
	activate_plugin( plugin_basename( $demo_plugin ) );
}

switch_theme( 'admin-config-embedded' );

if ( ! term_exists( 'Admin Config Smoke', 'category' ) ) {
	wp_insert_term( 'Admin Config Smoke', 'category' );
}

$existing_page = get_page_by_path( 'admin-config-smoke' );

if ( ! $existing_page instanceof WP_Post ) {
	wp_insert_post(
		array(
			'post_title'   => 'Admin Config Smoke',
			'post_name'    => 'admin-config-smoke',
			'post_content' => 'Smoke fixture page for Admin Config end-to-end tests.',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}
