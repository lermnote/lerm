<?php
/**
 * Package version metadata.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Version {

	public const PACKAGE = '0.1.0';

	public static function from_constant( string $constant_name ): string {
		return defined( $constant_name ) ? (string) constant( $constant_name ) : self::PACKAGE;
	}
}
