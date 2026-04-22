<?php
/**
 * Legacy PHPUnit shim.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

$package_root = dirname( __DIR__ );
$autoload     = $package_root . '/vendor/autoload.php';
$phpunit      = $package_root . '/vendor/phpunit/phpunit/phpunit';
$config       = $package_root . '/phpunit.xml.dist';
$extra_args   = array_slice( $_SERVER['argv'] ?? array(), 1 );

if ( ! file_exists( $autoload ) || ! file_exists( $phpunit ) ) {
	fwrite( STDERR, "PHPUnit is not installed. Run `composer install` in packages/AdminConfig first.\n" );
	exit( 1 );
}

require_once $autoload;

$command = sprintf(
	'"%s" "%s" --configuration "%s"%s',
	PHP_BINARY,
	$phpunit,
	$config,
	empty( $extra_args )
		? ''
		: ' ' . implode(
			' ',
			array_map(
				static fn ( $arg ): string => escapeshellarg( (string) $arg ),
				$extra_args
			)
		)
);

passthru( $command, $exit_code );
exit( $exit_code );
