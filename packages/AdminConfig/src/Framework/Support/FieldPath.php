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
	 */
	public static function starts_with( string $path, string $prefix ): bool {
		if ( '' === $path || '' === $prefix ) {
			return false;
		}

		return str_starts_with( $path, $prefix . '.' );
	}

	/**
	 * Whether $path ends with a dot separator followed by $segment.
	 *
	 * Matches "foo.bar" for segment "bar", but not "foobar".
	 * Returns false when either argument is empty.
	 */
	public static function ends_with_segment( string $path, string $segment ): bool {
		if ( '' === $path || '' === $segment ) {
			return false;
		}

		return str_ends_with( $path, '.' . $segment );
	}
}
