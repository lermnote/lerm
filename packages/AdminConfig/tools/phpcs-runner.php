<?php
/**
 * Run PHPCS for the extracted package, preferring package-local binaries and
 * falling back to the workspace root vendor when needed.
 */

declare( strict_types=1 );

$package_root = dirname( __DIR__ );
$php_binary   = PHP_BINARY;
$stub_file    = __DIR__ . '/wp-tool-stubs.php';
$config_file  = $package_root . '/phpcs.xml.dist';
$extra_args   = array_slice( $_SERVER['argv'] ?? array(), 1 );

if ( ! is_file( $config_file ) ) {
	fwrite( STDERR, sprintf( "PHPCS config file not found: %s\n", $config_file ) );
	exit( 1 );
}

$candidates = array(
	$package_root . '/vendor/bin/phpcs',
	$package_root . '/../../vendor/bin/phpcs',
);

$phpcs = null;

foreach ( $candidates as $candidate ) {
	if ( is_file( $candidate ) ) {
		$phpcs = $candidate;
		break;
	}
}

if ( null === $phpcs ) {
	fwrite( STDERR, "PHPCS binary not found. Install squizlabs/php_codesniffer and WPCS to enable package-local linting.\n" );
	exit( 1 );
}

chdir( $package_root );

$command = sprintf(
	'%s -d auto_prepend_file=%s %s --standard=%s%s',
	escapeshellarg( $php_binary ),
	escapeshellarg( $stub_file ),
	escapeshellarg( $phpcs ),
	escapeshellarg( './phpcs.xml.dist' ),
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
