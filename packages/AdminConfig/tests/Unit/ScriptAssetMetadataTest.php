<?php
/**
 * Tests for script asset metadata resolution.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class ScriptAssetMetadataTest extends TestCase {

	public function test_returns_fallback_when_build_files_are_missing(): void {
		$asset = ScriptAssetMetadata::resolve(
			'admin-config',
			'admin-config.js',
			array( 'wp-api-fetch' ),
			'1.2.3',
			static fn( string $file ): string => sys_get_temp_dir() . '/missing-admin-config-assets/' . $file
		);

		self::assertSame(
			array(
				'file'         => 'admin-config.js',
				'dependencies' => array( 'wp-api-fetch' ),
				'version'      => '1.2.3',
			),
			$asset
		);
	}

	public function test_resolves_build_metadata_with_deduplicated_dependencies(): void {
		$directory = sys_get_temp_dir() . '/lerm-admin-config-script-assets-' . str_replace( '.', '-', uniqid( '', true ) );

		mkdir( $directory . '/build', 0777, true );
		file_put_contents( $directory . '/build/block-panel.js', 'console.log("ok");' );
		file_put_contents(
			$directory . '/build/block-panel.asset.php',
			'<?php return array("dependencies" => array("wp-element", "wp-components", "wp-element"), "version" => "abc123");'
		);

		try {
			$asset = ScriptAssetMetadata::resolve(
				'block-panel',
				'build/block-panel.js',
				array( 'wp-api-fetch', 'wp-element' ),
				'1.2.3',
				static fn( string $file ): string => $directory . '/' . ltrim( $file, '/\\' )
			);

			self::assertSame( 'build/block-panel.js', $asset['file'] );
			self::assertSame( array( 'wp-api-fetch', 'wp-element', 'wp-components' ), $asset['dependencies'] );
			self::assertSame( 'abc123', $asset['version'] );
		} finally {
			unlink( $directory . '/build/block-panel.asset.php' );
			unlink( $directory . '/build/block-panel.js' );
			rmdir( $directory . '/build' );
			rmdir( $directory );
		}
	}
}
