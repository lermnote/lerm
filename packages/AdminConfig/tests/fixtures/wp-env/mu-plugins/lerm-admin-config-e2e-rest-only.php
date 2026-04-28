<?php
/**
 * wp-env-only REST transport rehearsal gate.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'lerm_admin_config_legacy_ajax_enabled',
	static function ( bool $enabled ): bool {
		$rest_only = '1' === (string) get_option( 'lerm_admin_config_e2e_rest_only', '0' );

		if ( is_multisite() ) {
			$rest_only = $rest_only || '1' === (string) get_site_option( 'lerm_admin_config_e2e_rest_only', '0' );
		}

		return $rest_only ? false : $enabled;
	}
);
