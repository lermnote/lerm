<?php
/**
 * Legacy admin-ajax.php transport controls.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyAjax {

	private const SINCE_VERSION   = '0.2.0';
	private const REMOVAL_VERSION = '0.3.0';

	public static function enabled(): bool {
		$enabled = defined( 'LERM_ADMIN_CONFIG_ENABLE_LEGACY_AJAX' )
			? (bool) constant( 'LERM_ADMIN_CONFIG_ENABLE_LEGACY_AJAX' )
			: false;

		if ( function_exists( 'apply_filters' ) ) {
			$enabled = (bool) apply_filters( 'lerm_admin_config_legacy_ajax_enabled', $enabled );
		}

		return $enabled;
	}

	public static function deprecate( string $function_name, string $replacement ): void {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( $function_name, self::SINCE_VERSION, $replacement );
		}
	}

	public static function removal_version(): string {
		return self::REMOVAL_VERSION;
	}
}
