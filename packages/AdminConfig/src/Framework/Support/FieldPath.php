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
}
