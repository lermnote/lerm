<?php
/**
 * Package-local asset path helpers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PackageAssets {

	public static function directory(): string {
		return dirname( __DIR__, 3 ) . '/assets';
	}

	public static function path( string $filename ): string {
		return self::directory() . '/' . ltrim( $filename, '/\\' );
	}
}
