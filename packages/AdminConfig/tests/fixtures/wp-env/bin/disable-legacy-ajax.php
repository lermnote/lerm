<?php
/**
 * Disable legacy admin-ajax.php fallback for wp-env REST-only rehearsals.
 *
 * @package Lerm\AdminConfig
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

update_option( 'lerm_admin_config_e2e_rest_only', '1' );

if ( is_multisite() ) {
	update_site_option( 'lerm_admin_config_e2e_rest_only', '1' );
}
