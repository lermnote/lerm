<?php
/**
 * Enable the block editor in the wp-env fixture theme for focused editor-panel tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

update_option( 'lerm_admin_config_e2e_block_editor', '1' );

if ( is_multisite() ) {
	update_site_option( 'lerm_admin_config_e2e_block_editor', '1' );
}
