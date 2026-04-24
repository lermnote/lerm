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
 * Activate a fixture plugin, forcing network activation when requested.
 */
function lerm_admin_config_activate_plugin( string $plugin_file, bool $network_wide ): void {
	$plugin_basename = plugin_basename( $plugin_file );

	if ( $network_wide && function_exists( 'is_plugin_active_for_network' ) && ! is_plugin_active_for_network( $plugin_basename ) ) {
		if ( is_plugin_active( $plugin_basename ) ) {
			deactivate_plugins( $plugin_basename, true, false );
		}
	}

	$result = activate_plugin( $plugin_basename, '', $network_wide, true );

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException(
			'Admin Config wp-env setup could not activate plugin ' . $plugin_basename . ': ' . $result->get_error_message()
		);
	}
}

/**
 * Ensure the deterministic admin fixture user exists.
 */
function lerm_admin_config_ensure_admin_user(): int {
	$user = get_user_by( 'login', 'admin' );

	if ( ! ( $user instanceof WP_User ) ) {
		$user_id = wp_create_user( 'admin', 'password', 'admin@example.com' );

		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		$user = get_user_by( 'id', (int) $user_id );
	}

	if ( ! ( $user instanceof WP_User ) ) {
		return 0;
	}

	wp_set_password( 'password', (int) $user->ID );
	$user->set_role( 'administrator' );

	if ( is_multisite() && function_exists( 'add_user_to_blog' ) ) {
		add_user_to_blog( get_current_blog_id(), (int) $user->ID, 'administrator' );
	}

	clean_user_cache( (int) $user->ID );

	return (int) $user->ID;
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

$package_plugin = lerm_admin_config_find_plugin_file( 'lerm-admin-config.php' );
$demo_plugin    = lerm_admin_config_find_plugin_file( 'schema-demo-plugin.php' );
$network_wide   = is_multisite();
$admin_user_id  = lerm_admin_config_ensure_admin_user();

if ( is_string( $package_plugin ) ) {
	lerm_admin_config_activate_plugin( $package_plugin, $network_wide );
}

if ( is_string( $demo_plugin ) ) {
	lerm_admin_config_activate_plugin( $demo_plugin, $network_wide );
}

if ( $network_wide && 0 !== $admin_user_id && function_exists( 'grant_super_admin' ) ) {
	grant_super_admin( $admin_user_id );
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
