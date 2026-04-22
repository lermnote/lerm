<?php // phpcs:disable WordPress.Files.FileName
/**
 * Default asset resolver implementation, resolving relative to the framework's
 * own directory, reading the host's version constant when available.
 *
 * Replace with a custom implementation to host assets outside the framework
 * directory or to use a different versioning strategy:
 *
 *   $framework = new Framework( new MyPluginAssetResolver() );
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Resolvers;

use Lerm\AdminConfig\Framework\Contracts\AssetResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DefaultAssetResolver implements AssetResolver {

	private string $assets_url;
	private string $version_constant;

	/**
	 * @param string $assets_url       Public URL of the framework assets directory (trailing slash optional).
	 * @param string $version_constant Name of the PHP constant holding the version string.
	 *                                 Falls back to '1.0.0' when not defined.
	 */
	public function __construct( string $assets_url, string $version_constant = 'LERM_VERSION' ) {
		$this->assets_url       = trailingslashit( $assets_url );
		$this->version_constant = $version_constant;
	}

	public function url( string $filename ): string {
		return $this->assets_url . ltrim( $filename, '/' );
	}

	public function version(): string {
		return defined( $this->version_constant ) ? (string) constant( $this->version_constant ) : '1.0.0';
	}
}
