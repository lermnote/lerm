<?php
/**
 * Re-enable legacy admin-ajax.php fallback after wp-env REST-only rehearsals.
 *
 * @package Lerm\AdminConfig
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

delete_option( 'lerm_admin_config_e2e_rest_only' );

if ( is_multisite() ) {
	delete_site_option( 'lerm_admin_config_e2e_rest_only' );
}
