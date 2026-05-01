<?php
/**
 * Restore the wp-env fixture theme's default classic editor behavior.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

delete_option( 'lerm_admin_config_e2e_block_editor' );

if ( is_multisite() ) {
	delete_site_option( 'lerm_admin_config_e2e_block_editor' );
}
