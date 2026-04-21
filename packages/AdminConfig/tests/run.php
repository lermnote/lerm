<?php
/**
 * Minimal package-local test runner.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/Support/TestCase.php';
require_once __DIR__ . '/Unit/DataSourceRegistryTest.php';
require_once __DIR__ . '/Unit/FieldModuleRegistryTest.php';
require_once __DIR__ . '/Unit/PageSchemaTest.php';
require_once __DIR__ . '/Unit/SchemaCompilerTest.php';
require_once __DIR__ . '/Smoke/ExamplesSmokeTest.php';

$tests = array(
	new \Lerm\AdminConfig\Tests\Unit\DataSourceRegistryTest(),
	new \Lerm\AdminConfig\Tests\Unit\FieldModuleRegistryTest(),
	new \Lerm\AdminConfig\Tests\Unit\PageSchemaTest(),
	new \Lerm\AdminConfig\Tests\Unit\SchemaCompilerTest(),
	new \Lerm\AdminConfig\Tests\Smoke\ExamplesSmokeTest(),
);

$failures = 0;

foreach ( $tests as $test ) {
	foreach ( $test->run() as $result ) {
		$status = 'passed' === $result['status'] ? 'PASS' : 'FAIL';
		echo sprintf( "[%s] %s\n", $status, $result['test'] );

		if ( 'failed' === $result['status'] ) {
			$failures += 1;
			echo '       ' . $result['error'] . "\n";
		}
	}
}

if ( $failures > 0 ) {
	echo sprintf( "\n%d test(s) failed.\n", $failures );
	exit( 1 );
}

echo "\nAll tests passed.\n";
