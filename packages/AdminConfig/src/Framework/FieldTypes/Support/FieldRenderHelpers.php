<?php
/**
 * Shared rendering helpers for field type classes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldRenderHelpers {

	public static function name_attr( string $template ): string {
		return '' !== $template
			? ' data-name-template="' . esc_attr( $template ) . '"'
			: '';
	}

	public static function id_attr( string $template ): string {
		return '' !== $template
			? ' data-id-template="' . esc_attr( $template ) . '"'
			: '';
	}

	public static function sub_name( string $field_name, string $key ): string {
		return $field_name . '[' . $key . ']';
	}

	public static function sub_template( string $template, string $key ): string {
		return '' !== $template ? $template . '[' . $key . ']' : '';
	}

	public static function sub_id( string $input_id, string $key ): string {
		return $input_id . '__' . sanitize_html_class( str_replace( '_', '-', $key ) );
	}

	public static function sub_id_template( string $template, string $key ): string {
		return '' !== $template
			? $template . '__' . sanitize_html_class( str_replace( '_', '-', $key ) )
			: '';
	}
}
