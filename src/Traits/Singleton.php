<?php // phpcs:disable WordPress.Files.FileName
/**
 * Singleton Trait
 *
 * This trait implements the Singleton design pattern, ensuring that a class has only one instance
 * and providing a global point of access to that instance.
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
	 * @param mixed ...$args Optional constructor args.
	 */
	protected function __construct( ...$args ) {
		// Intentionally empty — concrete class should handle its own initialization.
	}

	/**
	 * Prevent cloning.
	 */
	final private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 */
	final public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Return the singleton instance (creates it on first call).
	 *
	 * @param mixed ...$args Optional arguments forwarded to the constructor (used only on first instantiation).
	 * @return static
	 */
	final public static function instance( ...$args ): static {
		if ( null === static::$instance ) {
			static::$instance = new static( ...$args );

			// Fire an initialization action so concrete classes do not need to rely on a trait constructor call.
			if ( function_exists( 'do_action' ) ) {
				do_action( 'lerm_singleton_init_' . static::class, $args );
			}
		}

		// Help static analyzers: at this point $instance cannot be null.
		assert( null !== static::$instance );
		/** @var static $instance */
		$instance = static::$instance;

		return $instance;
	}
}
