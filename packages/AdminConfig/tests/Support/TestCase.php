<?php
/**
 * Tiny package-local test case.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Support;

use RuntimeException;
use Throwable;

abstract class TestCase {

	/**
	 * @return array<int, array<string, string>>
	 */
	public function run(): array {
		$results = array();

		foreach ( get_class_methods( $this ) as $method ) {
			if ( ! str_starts_with( $method, 'test' ) ) {
				continue;
			}

			try {
				$this->setUp();
				$this->{$method}();
				$results[] = array(
					'status' => 'passed',
					'test'   => static::class . '::' . $method,
				);
			} catch ( Throwable $throwable ) {
				$results[] = array(
					'status' => 'failed',
					'test'   => static::class . '::' . $method,
					'error'  => $throwable->getMessage(),
				);
			} finally {
				$this->tearDown();
			}
		}

		return $results;
	}

	protected function setUp(): void {
	}

	protected function tearDown(): void {
	}

	protected function assertTrue( bool $condition, string $message = 'Expected condition to be true.' ): void {
		if ( ! $condition ) {
			throw new RuntimeException( $message );
		}
	}

	protected function assertFalse( bool $condition, string $message = 'Expected condition to be false.' ): void {
		$this->assertTrue( ! $condition, $message );
	}

	/**
	 * @param mixed $expected
	 * @param mixed $actual
	 */
	protected function assertSame( $expected, $actual, string $message = '' ): void {
		if ( $expected === $actual ) {
			return;
		}

		throw new RuntimeException(
			'' !== $message
				? $message
				: sprintf( 'Expected %s, got %s.', var_export( $expected, true ), var_export( $actual, true ) )
		);
	}

	/**
	 * @param mixed $needle
	 * @param array<int|string, mixed> $haystack
	 */
	protected function assertContains( $needle, array $haystack, string $message = '' ): void {
		$this->assertTrue(
			in_array( $needle, $haystack, true ),
			'' !== $message ? $message : sprintf( 'Expected array to contain %s.', var_export( $needle, true ) )
		);
	}

	/**
	 * @param mixed $value
	 */
	protected function assertCount( int $expected_count, $value, string $message = '' ): void {
		$count = is_countable( $value ) ? count( $value ) : -1;
		$this->assertSame(
			$expected_count,
			$count,
			'' !== $message ? $message : sprintf( 'Expected count %d, got %d.', $expected_count, $count )
		);
	}

	/**
	 * @param mixed $value
	 */
	protected function assertArrayHasKey( string $key, $value, string $message = '' ): void {
		$this->assertTrue(
			is_array( $value ) && array_key_exists( $key, $value ),
			'' !== $message ? $message : sprintf( 'Expected array key "%s" to exist.', $key )
		);
	}

	/**
	 * @param class-string<Throwable> $exception_class
	 */
	protected function assertThrows( string $exception_class, callable $callback, string $message = '' ): void {
		try {
			$callback();
		} catch ( Throwable $throwable ) {
			$this->assertTrue(
				$throwable instanceof $exception_class,
				'' !== $message ? $message : sprintf( 'Expected %s, got %s.', $exception_class, $throwable::class )
			);
			return;
		}

		throw new RuntimeException(
			'' !== $message ? $message : sprintf( 'Expected exception %s was not thrown.', $exception_class )
		);
	}
}
