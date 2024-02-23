<?php // phpcs:disable WordPress.Files.FileName
/**
 * Trait Hooker class.
 *
 * @package lerm
 */

namespace Lerm\Inc;

trait Hooker {

	/**
	 * Hooks a function on to a specific action.
	 *
	 * @param string  $tag
	 * @param mixed   $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected static function action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_action( $tag, array( __CLASS__, $function_to_add ), $priority, $accepted_args );
	}

	/**
	 * Hooks functions array on to a specific action.
	 *
	 * @param array   $tags
	 * @param mixed   $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected static function actions( $tags, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$count = count( $tags );
		array_map(
			array( $this, 'action' ),
			(array) $tags,
			array_fill( 0, $count, $function_to_add ),
			array_fill( 0, $count, $priority ),
			array_fill( 0, $count, $accepted_args )
		);
	}

	/**
	 * Add filter to functions
	 *
	 * @param string  $tag
	 * @param mixed   $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected static function filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, array( __CLASS__, $function_to_add ), $priority, $accepted_args );
	}

	/**
	 * Add filter array to function
	 *
	 * @param array   $tags
	 * @param mixed   $function_to_add
	 * @param integer $priority
	 * @param integer $accepted_args
	 * @return void
	 */
	protected static function filters( $tags, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		foreach ( $tags as $tag ) {
			self::filter( $tag, $function_to_add, $priority, $accepted_args );
		}
	}
}
