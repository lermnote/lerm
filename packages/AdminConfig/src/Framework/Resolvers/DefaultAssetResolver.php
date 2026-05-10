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

use Lerm\AdminConfig\Version;
use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Support\PackageAssets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DefaultAssetResolver implements AssetPathResolver {

	private string $assets_url;
	private string $assets_path;
	private string $version_constant;

	/**
	 * @param string $assets_url       Public URL of the framework assets directory (trailing slash optional).
	 * @param string $version_constant Name of the PHP constant holding the version string.
	 *                                 Falls back to the package version when not defined.
	 * @param string $assets_path      Filesystem path of the framework assets directory.
	 */
	public function __construct( string $assets_url, string $version_constant = 'LERM_VERSION', string $assets_path = '' ) {
		$this->assets_url       = trailingslashit( $assets_url );
		$this->assets_path      = '' !== $assets_path ? rtrim( $assets_path, '/\\' ) : PackageAssets::directory();
		$this->version_constant = $version_constant;
	}

	public function url( string $filename ): string {
		return $this->assets_url . ltrim( $filename, '/' );
	}

	public function version(): string {
		return Version::from_constant( $this->version_constant );
	}

	public function path( string $filename ): string {
		return $this->assets_path . '/' . ltrim( $filename, '/\\' );
	}
}
