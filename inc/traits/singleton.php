<?php
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
	 * @var array
	 */
	private static $instance = array();

	/**
	 * Private constructor to prevent instantiation from outside the class.
	 *
	 * @param array $args Optional parameters for the constructor.
	 */
	protected function __construct( $args = array() ) {}

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
	 * @return mixed The single instance of the class.
	 */
	final public static function instance( $args = array() ) {

		// Get the name of the calling class.
		$class = get_called_class();

		// Check if an instance of the class already exists.
		if ( ! isset( self::$instance[ $class ] ) ) {
			// Create a new instance of the class if it doesn't exist.
			self::$instance[ $class ] = new static( $args );
			// Trigger an action to indicate initialization of the singleton.
			do_action( 'lerm_singleton_init_' . $class );
		}

		// Return the instance of the class.
		return self::$instance[ $class ];
	}
}
