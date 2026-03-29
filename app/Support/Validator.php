<?php // phpcs:disable WordPress.Files.FileName
declare( strict_types=1 );

namespace Lerm\Support;

/**
 * Validation helpers for controller and REST input.
 *
 * @package Lerm\Support
 */
final class Validator {

	/**
	 * Ensure a value is present.
	 */
	public static function required( mixed $value, string $field = '' ): ?string {
		if ( null === $value || '' === $value || array() === $value ) {
			$label = '' !== $field ? $field : __( 'This field', 'lerm' );

			/* translators: %s: field label */
			return sprintf( __( '%s cannot be empty.', 'lerm' ), $label );
		}

		return null;
	}

	/**
	 * Ensure a string meets a minimum length.
	 */
	public static function min_length( string $value, int $min ): ?string {
		if ( mb_strlen( $value ) < $min ) {
			/* translators: %d: minimum length */
			return sprintf( __( 'Must be at least %d characters long.', 'lerm' ), $min );
		}

		return null;
	}

	/**
	 * Ensure a string does not exceed a maximum length.
	 */
	public static function max_length( string $value, int $max ): ?string {
		if ( mb_strlen( $value ) > $max ) {
			/* translators: %d: maximum length */
			return sprintf( __( 'Must not exceed %d characters.', 'lerm' ), $max );
		}

		return null;
	}

	/**
	 * Match a value against a regular expression.
	 */
	public static function regex( string $value, string $pattern, string $message = '' ): ?string {
		if ( ! preg_match( $pattern, $value ) ) {
			return '' !== $message ? $message : __( 'Invalid format.', 'lerm' );
		}

		return null;
	}

	/**
	 * Ensure a numeric value is not below the minimum.
	 */
	public static function min( int|float $value, int|float $min ): ?string {
		if ( $value < $min ) {
			/* translators: %s: minimum value */
			return sprintf( __( 'Must be greater than or equal to %s.', 'lerm' ), $min );
		}

		return null;
	}

	/**
	 * Ensure a numeric value is not above the maximum.
	 */
	public static function max( int|float $value, int|float $max ): ?string {
		if ( $value > $max ) {
			/* translators: %s: maximum value */
			return sprintf( __( 'Must be less than or equal to %s.', 'lerm' ), $max );
		}

		return null;
	}

	/**
	 * Ensure a value is a positive integer.
	 */
	public static function positive_int( mixed $value ): ?string {
		if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
			return __( 'Must be a positive integer.', 'lerm' );
		}

		return null;
	}

	/**
	 * Ensure a value is a valid email address.
	 */
	public static function email( string $value ): ?string {
		if ( ! is_email( $value ) ) {
			return __( 'Please enter a valid email address.', 'lerm' );
		}

		return null;
	}

	/**
	 * Ensure a value is a valid HTTP or HTTPS URL.
	 */
	public static function url( string $value ): ?string {
		$cleaned = esc_url_raw( $value, array( 'http', 'https' ) );

		if ( '' === $cleaned || ! filter_var( $cleaned, FILTER_VALIDATE_URL ) ) {
			return __( 'Please enter a valid URL.', 'lerm' );
		}

		return null;
	}

	/**
	 * Ensure a value is in the allowed list.
	 */
	public static function in( mixed $value, array $allowed, bool $strict = true ): ?string {
		// phpcs:disable WordPress.PHP.StrictInArray
		if ( ! in_array( $value, $allowed, $strict ) ) {
			$list = implode( ', ', array_map( 'strval', $allowed ) );

			/* translators: %s: allowed values */
			return sprintf( __( 'Must be one of the following values: %s', 'lerm' ), $list );
		}
		// phpcs:enable WordPress.PHP.StrictInArray

		return null;
	}

	/**
	 * Run multiple validation rules and return the first error.
	 */
	public static function chain( callable ...$rules ): ?string {
		foreach ( $rules as $rule ) {
			$result = $rule();
			if ( null !== $result ) {
				return $result;
			}
		}

		return null;
	}
}
