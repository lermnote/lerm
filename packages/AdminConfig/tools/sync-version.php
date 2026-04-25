<?php
/**
 * Synchronize plugin version literals from VERSION.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

$root          = dirname( __DIR__ );
$version_file  = $root . '/VERSION';
$plugin_file   = $root . '/lerm-admin-config.php';
$check_only    = in_array( '--check', $argv, true );

$version = trim( (string) file_get_contents( $version_file ) );

if ( '' === $version ) {
	fwrite( STDERR, "VERSION must contain a version string.\n" );
	exit( 1 );
}

if ( 1 !== preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
	fwrite( STDERR, "Unsupported version format: {$version}\n" );
	exit( 1 );
}

$contents = (string) file_get_contents( $plugin_file );
$updated  = preg_replace( '/(\* Version:\s+)[^\r\n]+/', '${1}' . $version, $contents, 1, $header_count );
$updated  = preg_replace(
	"/(define\(\s*'LERM_ADMIN_CONFIG_VERSION'\s*,\s*')[^']+('\s*\)\s*;)/",
	'${1}' . $version . '${2}',
	(string) $updated,
	1,
	$constant_count
);

if ( 1 !== $header_count || 1 !== $constant_count ) {
	fwrite( STDERR, "Unable to locate plugin header and version constant.\n" );
	exit( 1 );
}

if ( $check_only ) {
	if ( $updated !== $contents ) {
		fwrite( STDERR, "lerm-admin-config.php is not synchronized with composer.json version {$version}.\n" );
		exit( 1 );
	}

	echo "Version literals are synchronized: {$version}\n";
	exit( 0 );
}

if ( false === file_put_contents( $plugin_file, $updated ) ) {
	fwrite( STDERR, "Unable to write {$plugin_file}.\n" );
	exit( 1 );
}

echo "Synchronized AdminConfig plugin version to {$version}.\n";
