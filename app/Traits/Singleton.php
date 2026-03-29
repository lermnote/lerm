<?php // phpcs:disable WordPress.Files.FileName
/**
 * Singleton trait.
 *
 * This trait implements the Singleton pattern, ensuring that a class has only
 * one instance and exposing a global access point for it.
 *
 * @package Lerm
 */

namespace Lerm\Traits;

trait Singleton {

	/**
	 * Singleton instance.
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Protected constructor to allow concrete classes to implement initialization.
	 *
	 * Concrete classes may declare their own constructor signature.
	 *
	 * @param mixed ...$args Optional constructor arguments.
	 */
	protected function __construct( ...$args ) {
		// Intentionally empty; concrete classes should handle their own initialization.
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 */
	final public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Return the singleton instance, creating it on first use.
	 *
	 * @param mixed ...$args Optional arguments forwarded to the constructor.
	 * @return static
	 */
	final public static function instance( ...$args ): static {
		if ( null === static::$instance ) {
			static::$instance = new static( ...$args );

			// Fire an initialization action so concrete classes do not rely on trait constructors.
			if ( function_exists( 'do_action' ) ) {
				do_action( 'lerm_singleton_init_' . static::class, $args );
			}
		}

		// Help static analyzers: at this point the instance cannot be null.
		assert( null !== static::$instance );
		/** @var static $instance */
		$instance = static::$instance;

		return $instance;
	}
}
