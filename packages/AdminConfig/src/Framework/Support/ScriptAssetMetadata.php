<?php
/**
 * Helpers for WordPress build asset metadata files.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ScriptAssetMetadata {

	/**
	 * @param array<int, string>       $dependencies Base script dependencies.
	 * @param callable(string): string $asset_path   Resolver for package-relative asset paths.
	 * @return array{file: string, dependencies: array<int, string>, version: string}
	 */
	public static function resolve( string $entry_name, string $fallback_file, array $dependencies, string $fallback_version, callable $asset_path ): array {
		$build_file  = 'build/' . $entry_name . '.js';
		$fallback    = array(
			'file'         => $fallback_file,
			'dependencies' => $dependencies,
			'version'      => $fallback_version,
		);
		$script_file = $asset_path( $build_file );
		$asset_file  = $asset_path( 'build/' . $entry_name . '.asset.php' );

		if ( ! is_readable( $script_file ) || ! is_readable( $asset_file ) ) {
			return $fallback;
		}

		$asset = include $asset_file;

		if ( ! is_array( $asset ) ) {
			return $fallback;
		}

		foreach ( (array) ( $asset['dependencies'] ?? array() ) as $dependency ) {
			if ( is_string( $dependency ) && '' !== $dependency ) {
				$dependencies[] = $dependency;
			}
		}

		return array(
			'file'         => $build_file,
			'dependencies' => array_values( array_unique( $dependencies ) ),
			'version'      => isset( $asset['version'] ) && is_scalar( $asset['version'] )
				? (string) $asset['version']
				: $fallback_version,
		);
	}
}
