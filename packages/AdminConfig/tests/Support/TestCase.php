<?php
/**
 * Package-local PHPUnit test base.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Throwable;

abstract class TestCase extends BaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->reset_wordpress_globals();
	}

	protected function tearDown(): void {
		$this->reset_wordpress_globals();
		parent::tearDown();
	}

	private function reset_wordpress_globals(): void {
		$GLOBALS['lerm_admin_config_doing_it_wrong'] = array();
		$GLOBALS['lerm_admin_config_deprecated']     = array();
		$GLOBALS['lerm_admin_config_actions']        = array();
	}

	protected function assertStringContains( string $needle, string $haystack, string $message = '' ): void {
		self::assertStringContainsString( $needle, $haystack, $message );
	}

	/**
	 * @param class-string<Throwable> $exception_class
	 */
	protected function assertThrows( string $exception_class, callable $callback, string $message = '' ): void {
		try {
			$callback();
		} catch ( Throwable $throwable ) {
			self::assertInstanceOf(
				$exception_class,
				$throwable,
				'' !== $message ? $message : sprintf( 'Expected %s, got %s.', $exception_class, $throwable::class )
			);
			return;
		}

		self::fail(
			'' !== $message ? $message : sprintf( 'Expected exception %s was not thrown.', $exception_class )
		);
	}
}
