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
		$GLOBALS['lerm_admin_config_doing_it_wrong']    = array();
		$GLOBALS['lerm_admin_config_actions']           = array();
		$GLOBALS['lerm_admin_config_filters']           = array();
		$GLOBALS['lerm_admin_config_is_admin']          = false;
		$GLOBALS['lerm_admin_config_is_multisite']      = false;
		$GLOBALS['lerm_admin_config_options']           = array();
		$GLOBALS['lerm_admin_config_rest_routes']       = array();
		$GLOBALS['lerm_admin_config_deprecated']        = array();
		$GLOBALS['lerm_admin_config_current_user_can']  = true;
		$GLOBALS['lerm_admin_config_ajax_nonce_checks'] = array();
		$GLOBALS['lerm_admin_config_json_response']     = null;
		$GLOBALS['lerm_admin_config_enqueued_styles']   = array();
		$GLOBALS['lerm_admin_config_enqueued_scripts']  = array();
		$GLOBALS['lerm_admin_config_localized_scripts'] = array();

		$_REQUEST = array();
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
