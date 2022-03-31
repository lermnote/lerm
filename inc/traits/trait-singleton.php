<?php
/**
 * Trait singleton class
 *
 * @package lerm
 */

namespace Lerm\Inc\Traits;

trait Singleton {

	protected function __construct() {}

	final protected function __clone() {}

	final public static function instance( $params = array() ) {

		static $instence = array();

		$class = get_called_class();

		if ( ! isset( $instence[ $class ] ) ) {
			$instence[ $class ] = new $class( $params );
			do_action( 'lerm_singleton_init_' . $class );
		}
		return $instence[ $class ];
	}
}
