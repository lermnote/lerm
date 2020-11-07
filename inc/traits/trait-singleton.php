<?php
/**
 * Trait singleton class
 *
 * @package lerm
 */

namespace Lerm\Inc\Traits;

trait Singleton {

	protected function __construct(){}

	final protected function __clone(){}

	final public static function get_instance() {

		static $instence = [];

		$called_class = get_called_class();

		if ( ! isset( $instence[ $called_class ] ) ) {
			$instence[ $called_class ] = new $called_class();
 			do_action( sprintf( 'lerm_theme_singleton_init_%s', $called_class ) );
		}
		return $instence[ $called_class ];
	}
}
