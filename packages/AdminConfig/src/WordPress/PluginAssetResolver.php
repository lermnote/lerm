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

	private string $plugin_file;
	private string $version_constant;

	public function __construct( string $plugin_file, string $version_constant = 'LERM_ADMIN_CONFIG_VERSION' ) {
		$this->plugin_file      = $plugin_file;
		$this->version_constant = $version_constant;
	}

	public function url( string $filename ): string {
		return trailingslashit( plugin_dir_url( $this->plugin_file ) . 'assets' ) . ltrim( $filename, '/' );
	}

	public function version(): string {
		return Version::from_constant( $this->version_constant );
	}
}
