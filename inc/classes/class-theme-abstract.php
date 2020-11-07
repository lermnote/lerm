<?php

namespace Lerm\Inc;

abstract class Theme_Abstract {

	abstract protected function handle();
	abstract protected function  hooks();

	/**
	 * Add filter to functions
	 *
	 * @param string $tag
	 * @param mixed $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected function filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, [ $this, $function_to_add ], $priority, $accepted_args );
	}

	/**
	 * Add filter array to function
	 *
	 * @param array $tags
	 * @param mixed $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected function filters( $tags, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$count = count( $tags );
		array_map(
			[ $this, 'filter' ],
			(array) $tags,
			array_fill( 0, $count, $function_to_add ),
			array_fill( 0, $count, $priority ),
			array_fill( 0, $count, $accepted_args )
		);
	}
}
