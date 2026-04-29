<?php
/**
 * Bootstrap real-WordPress integration tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

$package_root = dirname( __DIR__, 2 );
$autoload     = $package_root . '/vendor/autoload.php';

if ( is_file( $autoload ) ) {
	require_once $autoload;
}

$wp_load_candidates = array();
$wp_load_env        = getenv( 'LERM_ADMIN_CONFIG_WP_LOAD' );

if ( is_string( $wp_load_env ) && '' !== $wp_load_env ) {
	$wp_load_candidates[] = $wp_load_env;
}

$search_dir = $package_root;

for ( $depth = 0; $depth < 8; $depth++ ) {
	$wp_load_candidates[] = $search_dir . '/wp-load.php';

	$parent_dir = dirname( $search_dir );

	if ( $parent_dir === $search_dir ) {
		break;
	}

	$search_dir = $parent_dir;
}

$wp_loaded = false;

foreach ( array_unique( $wp_load_candidates ) as $candidate ) {
	if ( ! is_file( $candidate ) ) {
		continue;
	}

	require_once $candidate;
	$wp_loaded = true;
	break;
}

if ( ! $wp_loaded ) {
	fwrite( STDERR, "Unable to locate wp-load.php for AdminConfig integration tests.\n" );
	exit( 1 );
}

if ( ! class_exists( 'WP_Screen' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
}

if ( ! function_exists( 'set_current_screen' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
}

if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

set_current_screen( 'dashboard' );

if ( 0 === get_current_user_id() ) {
	$admin_user = get_user_by( 'login', 'admin' );

	if ( $admin_user instanceof \WP_User ) {
		wp_set_current_user( (int) $admin_user->ID );
	}
}
