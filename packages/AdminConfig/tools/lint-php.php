<?php
/**
 * Recursive PHP syntax checker for the package.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

$root      = dirname( __DIR__ );
$directory = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
$iterator  = new RecursiveIteratorIterator( $directory );
$failures  = array();

foreach ( $iterator as $file ) {
	if ( ! $file instanceof SplFileInfo || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$path = $file->getPathname();

	if ( str_contains( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}

	$command = sprintf( '%s -l %s', escapeshellarg( PHP_BINARY ), escapeshellarg( $path ) );
	$output  = array();
	$code    = 0;

	exec( $command, $output, $code );

	if ( 0 !== $code ) {
		$failures[] = array(
			'file'   => $path,
			'output' => implode( PHP_EOL, $output ),
		);
		continue;
	}

	echo str_replace( $root . DIRECTORY_SEPARATOR, '', $path ) . PHP_EOL;
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, "PHP syntax errors found:\n" );

	foreach ( $failures as $failure ) {
		fwrite( STDERR, '- ' . $failure['file'] . PHP_EOL . $failure['output'] . PHP_EOL );
	}

	exit( 1 );
}

echo "\nPHP syntax check passed.\n";
