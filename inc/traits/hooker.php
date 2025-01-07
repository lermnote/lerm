<?php // phpcs:disable WordPress.Files.FileName
declare(strict_types=1);
/**
 * Trait Hooker class.
 *
 * @package lerm
 */

namespace Lerm\Inc\Traits;

trait Hooker {

	/**
	 * Hooks a function on to a specific action.
	 *
	 * @param string   $tag              The action hook name.
	 * @param callable $function_to_add  The callback function (static or object method).
	 * @param int      $priority         Hook priority.
	 * @param int      $accepted_args    Number of accepted arguments.
	 * @return void
	 */
	protected static function action( string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): void {
		add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * Hooks multiple functions onto specific actions.
	 *
	 * @param array    $tags             Array of action hook names.
	 * @param callable $function_to_add  The callback function (static or object method).
	 * @param int      $priority         Hook priority.
	 * @param int      $accepted_args    Number of accepted arguments.
	 * @return void
	 */
	protected static function actions( array $tags, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): void {
		foreach ( $tags as $tag ) {
			self::action( $tag, $function_to_add, $priority, $accepted_args );
		}
	}

	/**
	 * Adds a function to a specific filter hook.
	 *
	 * @param string   $tag              The filter hook name.
	 * @param callable $function_to_add  The callback function (static or object method).
	 * @param int      $priority         Hook priority.
	 * @param int      $accepted_args    Number of accepted arguments.
	 * @return void
	 */
	protected static function filter( string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): void {
		add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * Adds a function to multiple filter hooks.
	 *
	 * @param array    $tags             Array of filter hook names.
	 * @param callable $function_to_add  The callback function (static or object method).
	 * @param int      $priority         Hook priority.
	 * @param int      $accepted_args    Number of accepted arguments.
	 * @return void
	 */
	protected static function filters( array $tags, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ): void {
		foreach ( $tags as $tag ) {
			self::filter( $tag, $function_to_add, $priority, $accepted_args );
		}
	}
}
