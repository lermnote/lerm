<?php
/**
 * Prepare wp-env with the package plugin, schema demo plugin, and embedded theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

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

/**
 * Ensure a fixture post or page exists and return its ID.
 */
function lerm_admin_config_ensure_post( string $slug, string $title, string $post_type, string $content ): int {
	$existing = get_page_by_path( $slug, 'OBJECT', $post_type );

	if ( $existing instanceof WP_Post ) {
		wp_update_post(
			array(
				'ID'           => $existing->ID,
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		return (int) $existing->ID;
	}

	return (int) wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => $post_type,
		)
	);
}

/**
 * Ensure a fixture comment exists on the given post.
 */
function lerm_admin_config_ensure_comment( int $post_id, string $content ): int {
	$matches = get_comments(
		array(
			'post_id' => $post_id,
			'search'  => $content,
			'number'  => 1,
			'status'  => 'all',
		)
	);

	if ( ! empty( $matches ) && $matches[0] instanceof WP_Comment ) {
		return (int) $matches[0]->comment_ID;
	}

	return (int) wp_insert_comment(
		array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => 'Admin Config Smoke',
			'comment_author_email' => 'smoke@example.com',
			'comment_content'      => $content,
			'comment_approved'     => 1,
		)
	);
}

/**
 * Ensure a fixture term exists and return its term ID.
 */
function lerm_admin_config_ensure_term( string $name, string $taxonomy ): int {
	$existing = term_exists( $name, $taxonomy );

	if ( is_array( $existing ) ) {
		return (int) $existing['term_id'];
	}

	if ( is_scalar( $existing ) ) {
		return (int) $existing;
	}

	$created = wp_insert_term( $name, $taxonomy );

	return is_array( $created ) ? (int) $created['term_id'] : 0;
}

/**
 * Ensure the E2E admin account exists and has deterministic credentials.
 */
function lerm_admin_config_ensure_admin_user( string $login, string $password ): ?WP_User {
	$user = get_user_by( 'login', $login );

	if ( ! $user instanceof WP_User ) {
		$user_id = wp_create_user( $login, $password, $login . '@example.com' );

		if ( is_wp_error( $user_id ) ) {
			return null;
		}

		$user = get_user_by( 'id', (int) $user_id );
	}

	if ( ! $user instanceof WP_User ) {
		return null;
	}

	wp_set_password( $password, (int) $user->ID );
	$user = get_user_by( 'id', (int) $user->ID );

	if ( $user instanceof WP_User ) {
		$user->set_role( 'administrator' );
	}

	return $user instanceof WP_User ? $user : null;
}

$package_plugin = lerm_admin_config_find_plugin_file( 'lerm-admin-config.php' );
$demo_plugin    = lerm_admin_config_find_plugin_file( 'schema-demo-plugin.php' );
$network_wide   = is_multisite();
$admin_password = getenv( 'LERM_ADMIN_CONFIG_ADMIN_PASS' );
$admin_password = is_string( $admin_password ) && '' !== $admin_password ? $admin_password : 'password';
$admin_user     = lerm_admin_config_ensure_admin_user( 'admin', $admin_password );

if ( is_string( $package_plugin ) ) {
	activate_plugin( plugin_basename( $package_plugin ), '', $network_wide, true );
}

if ( is_string( $demo_plugin ) ) {
	activate_plugin( plugin_basename( $demo_plugin ), '', $network_wide, true );
}

if ( $admin_user instanceof WP_User ) {
	if ( $network_wide && function_exists( 'grant_super_admin' ) ) {
		grant_super_admin( (int) $admin_user->ID );
	}
}

switch_theme( 'admin-config-embedded' );

$term_id    = lerm_admin_config_ensure_term( 'Admin Config Smoke', 'category' );
$page_id    = lerm_admin_config_ensure_post(
	'admin-config-smoke',
	'Admin Config Smoke',
	'page',
	'Smoke fixture page for Admin Config end-to-end tests.'
);
$post_id    = lerm_admin_config_ensure_post(
	'admin-config-smoke-post',
	'Admin Config Smoke Post',
	'post',
	'Smoke fixture post for Admin Config classic-editor metabox tests.'
);
$comment_id = lerm_admin_config_ensure_comment( $post_id, 'Admin Config Smoke Comment' );

update_option(
	'lerm_admin_config_e2e_fixtures',
	array(
		'page_id'           => $page_id,
		'post_id'           => $post_id,
		'comment_id'        => $comment_id,
		'category_term_id'  => $term_id,
		'page_slug'         => 'admin-config-smoke',
		'post_slug'         => 'admin-config-smoke-post',
		'category_name'     => 'Admin Config Smoke',
		'comment_signature' => 'Admin Config Smoke Comment',
	),
	false
);
