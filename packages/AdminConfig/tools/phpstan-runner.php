<?php
/**
 * Run PHPStan for the extracted package when the binary is available.
 */

declare( strict_types=1 );

$package_root = dirname( __DIR__ );
$php_binary   = PHP_BINARY;
$stub_file    = __DIR__ . '/wp-tool-stubs.php';
$config_file  = $package_root . '/phpstan.neon.dist';
$extra_args   = array_slice( $_SERVER['argv'] ?? array(), 1 );

$candidates = array(
	$package_root . '/vendor/bin/phpstan',
	$package_root . '/../../vendor/bin/phpstan',
);

$phpstan = null;

foreach ( $candidates as $candidate ) {
	if ( is_file( $candidate ) ) {
		$phpstan = $candidate;
		break;
	}
}

if ( null === $phpstan ) {
	fwrite( STDERR, "PHPStan binary not found in package-local or workspace vendor directories.\n" );
	fwrite( STDERR, "Install phpstan/phpstan to enable package-local static analysis.\n" );
	exit( 1 );
}

$command = sprintf(
	'"%s" -d auto_prepend_file="%s" "%s" analyse --configuration="%s"%s',
	$php_binary,
	$stub_file,
	$phpstan,
	$config_file,
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
