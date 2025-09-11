<?php // phpcs:disable WordPress.Files.FileName
/**
 * Singleton Trait
 *
 * This trait implements the Singleton design pattern, ensuring that a class has only one instance
 * and providing a global point of access to that instance.
 *
 * @package Lerm
 */

namespace Lerm\Inc\Traits;

trait Singleton {

	/**
	 * Static array to store instances of classes.
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Private constructor to prevent instantiation from outside the class.
	 *
	 * @param array $args Optional parameters for the constructor.
	 */
	final protected function __construct( $args = array() ) {
		// Trigger an action to indicate initialization of the singleton.
		do_action( 'lerm_singleton_init_' . get_called_class() );
	}

	/**
	 * Prevents the instance from being cloned.
	 */
	final protected function __clone() {}

	/**
	 * Prevents unserializing of the instance.
	 */
	final public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}
	/**
	 * Returns the single instance of the class.
	 *
	 * @param array $args Optional parameters for the constructor.
	 * @return static The single instance of the class.
	 */
	final public static function instance( $args = array() ) {
		if ( null === static::$instance ) {
			static::$instance = new static( $args );
		}
		return static::$instance;
	}
}
