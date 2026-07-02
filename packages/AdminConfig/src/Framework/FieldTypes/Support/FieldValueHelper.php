<?php
/**
 * Shared scalar casting and field value sanitization utilities.
 *
 * Extracts duplicated helpers from BuiltinFieldTypes,
 * ExtendedPrimitiveFieldTypes and AsyncFieldTypes into a single
 * canonical location.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldValueHelper {

	/**
	 * Cast a string value to int or float based on the cast parameter.
	 *
	 * @return string|int|float
	 */
	public static function cast_scalar_value( string $value, string $cast ) {
		if ( 'int' === $cast ) {
			return (int) $value;
		}

		if ( 'float' === $cast ) {
			return (float) $value;
		}

		return $value;
	}

	/**
	 * Sanitize a numeric value with optional min/max clamping.
	 *
	 * @param mixed $value
	 * @return int|float
	 */
	public static function sanitize_numeric_value( array $field, $value ) {
		$default = $field['default'] ?? 0;
		$cast    = (string) ( $field['cast'] ?? 'int' );
		$number  = is_numeric( $value ) ? (float) $value : ( is_numeric( $default ) ? (float) $default : 0.0 );
		$min     = isset( $field['min'] ) && is_numeric( $field['min'] ) ? (float) $field['min'] : null;
		$max     = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : null;

		if ( null !== $min && $number < $min ) {
			$number = $min;
		}

		if ( null !== $max && $number > $max ) {
			$number = $max;
		}

		return 'float' === $cast ? $number : (int) round( $number );
	}

	/**
	 * Sanitize a checkbox-list or multi-choice submission against allowed choices.
	 *
	 * @param mixed $value
	 * @return array<int, string>
	 */
	public static function sanitize_checkbox_list_values( array $field, $value, bool $strict ): array {
		$choices = $strict ? PageSchema::choices( $field ) : array();
		$values  = is_array( $value ) ? $value : array();
		$clean   = array();

		foreach ( $values as $item ) {
			$item = is_scalar( $item ) ? (string) $item : '';

			if ( '' === $item ) {
				continue;
			}

			if ( ! $strict || array_key_exists( $item, $choices ) ) {
				$clean[] = $item;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
