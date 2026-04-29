<?php
/**
 * Plugin-mode asset resolver for the admin config runtime.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Version;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PluginAssetResolver implements AssetResolver {

	private string $asset_plugin_file;
	private string $version_constant;

	public function __construct( string $plugin_file, string $version_constant = 'LERM_ADMIN_CONFIG_VERSION' ) {
		$this->asset_plugin_file = $this->resolve_asset_plugin_file( $plugin_file );
		$this->version_constant  = $version_constant;
	}

	public function url( string $filename ): string {
		return trailingslashit( plugin_dir_url( $this->asset_plugin_file ) . 'assets' ) . ltrim( $filename, '/' );
	}

	public function version(): string {
		return Version::from_constant( $this->version_constant );
	}

	private function resolve_asset_plugin_file( string $plugin_file ): string {
		if ( $this->has_admin_assets( $plugin_file ) ) {
			return $plugin_file;
		}

		$package_plugin_file = dirname( __DIR__, 2 ) . '/lerm-admin-config.php';

		if ( $this->has_admin_assets( $package_plugin_file ) ) {
			return $package_plugin_file;
		}

		return $plugin_file;
	}

	private function has_admin_assets( string $plugin_file ): bool {
		$asset_dir = dirname( $plugin_file ) . '/assets';

		return is_file( $asset_dir . '/admin-config.css' ) && is_file( $asset_dir . '/admin-config.js' );
	}
}
