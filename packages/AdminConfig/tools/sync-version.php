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
$version_class = $root . '/src/Version.php';
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

$plugin_contents = (string) file_get_contents( $plugin_file );
$plugin_updated  = preg_replace( '/(\* Version:\s+)[^\r\n]+/', '${1}' . $version, $plugin_contents, 1, $header_count );
$plugin_updated  = preg_replace(
	"/(define\(\s*'LERM_ADMIN_CONFIG_VERSION'\s*,\s*')[^']+('\s*\)\s*;)/",
	'${1}' . $version . '${2}',
	(string) $plugin_updated,
	1,
	$constant_count
);

$class_contents = (string) file_get_contents( $version_class );
$class_updated  = preg_replace(
	"/(public const PACKAGE = ')[^']+(';\s*)/",
	'${1}' . $version . '${2}',
	$class_contents,
	1,
	$class_count
);

if ( 1 !== $header_count || 1 !== $constant_count ) {
	fwrite( STDERR, "Unable to locate plugin header and version constant.\n" );
	exit( 1 );
}

if ( 1 !== $class_count ) {
	fwrite( STDERR, "Unable to locate Version::PACKAGE.\n" );
	exit( 1 );
}

if ( $check_only ) {
	if ( $plugin_updated !== $plugin_contents || $class_updated !== $class_contents ) {
		fwrite( STDERR, "AdminConfig version literals are not synchronized with VERSION {$version}.\n" );
		exit( 1 );
	}

	echo "Version literals are synchronized: {$version}\n";
	exit( 0 );
}

if ( false === file_put_contents( $plugin_file, $plugin_updated ) ) {
	fwrite( STDERR, "Unable to write {$plugin_file}.\n" );
	exit( 1 );
}

if ( false === file_put_contents( $version_class, $class_updated ) ) {
	fwrite( STDERR, "Unable to write {$version_class}.\n" );
	exit( 1 );
}

echo "Synchronized AdminConfig plugin version to {$version}.\n";
