<?php
/**
 * Dotted field path helper for schema-aware AdminConfig classes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldPath {

	/**
	 * Join a base path with a path segment using dot notation.
	 *
	 * Returns the non-empty argument when the other is empty, otherwise
	 * joins with a single dot separator. This is the canonical path join
	 * for schema field paths used across sanitizers, store, and renderers.
	 */
	public static function join( string $base_path, string $segment ): string {
		if ( '' === $segment ) {
			return $base_path;
		}

		if ( '' === $base_path ) {
			return $segment;
		}

		return $base_path . '.' . $segment;
	}

	/**
	 * Whether $path starts with $prefix followed by a dot separator.
	 *
	 * Matches "foo.bar" for prefix "foo", but not "foobar".
	 * Returns false when either argument is empty.
	 *
	 * Replaces str_starts_with( $path, $prefix . '.' ) (PHP 8.0+) so that
	 * the call-sites remain compatible with PHP 7.x if the minimum
	 * requirement is ever lowered.
	 */
	public static function starts_with( string $path, string $prefix ): bool {
		if ( '' === $path || '' === $prefix ) {
			return false;
		}

		$needle = $prefix . '.';

		return substr( $path, 0, strlen( $needle ) ) === $needle;
	}

	/**
	 * Whether $path ends with a dot separator followed by $segment.
	 *
	 * Matches "foo.bar" for segment "bar", but not "foobar".
	 * Returns false when either argument is empty.
	 *
	 * Replaces str_ends_with( $path, '.' . $segment ) (PHP 8.0+) so that
	 * the call-sites remain compatible with PHP 7.x if the minimum
	 * requirement is ever lowered.
	 */
	public static function ends_with_segment( string $path, string $segment ): bool {
		if ( '' === $path || '' === $segment ) {
			return false;
		}

		$needle = '.' . $segment;
		$offset = strlen( $path ) - strlen( $needle );

		return $offset > 0 && substr( $path, $offset ) === $needle;
	}
}
