<?php
/**
 * Async field definitions.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AsyncFieldTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'ajax_select' => self::ajax_select_definition(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function ajax_select_definition(): array {
		return array(
			'render'        => static function ( array $field, $value, string $field_name, OptionsPage $page ): void {
				self::render_ajax_select( $field, $value, $field_name, (string) ( $field['id'] ?? '' ), $page );
			},
			'render_nested' => static function ( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
				self::render_ajax_select( $field, $value, $field_name, $input_id, $page, $name_template, $id_template );
			},
			'sanitize'      => static function ( array $field, $value, bool $strict, OptionStore $store ) {
				return self::sanitize_ajax_select_value( $field, $value );
			},
			'client'        => array(
				'control' => 'ajax_select',
				'nested'  => true,
				'async'   => true,
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	private static function render_ajax_select( array $field, $value, string $field_name, string $input_id, OptionsPage $page, string $name_template = '', string $id_template = '' ): void {
		$multiple            = ! empty( $field['multiple'] );
		$source_id           = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );
		$placeholder         = (string) ( $field['placeholder'] ?? __( 'Search...', 'lerm' ) );
		$search_label        = (string) ( $field['search_label'] ?? __( 'Search options', 'lerm' ) );
		$min_search_length   = max( 0, (int) ( $field['min_search_length'] ?? 0 ) );
		$per_page            = max( 1, (int) ( $field['per_page'] ?? 20 ) );
		$allow_clear         = ! array_key_exists( 'allow_clear', $field ) || ! empty( $field['allow_clear'] );
		$selected_values     = self::normalize_selected_values( $field, $value );
		$status_id           = $input_id . '__status';
		$results_id          = $input_id . '__results';
		$input_name_template = '' !== $name_template ? ( $multiple ? $name_template . '[]' : $name_template ) : '';
		$name_attr           = '' !== $input_name_template
			? ' data-name-template="' . esc_attr( $input_name_template ) . '"'
			: '';
		$id_attr             = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
		$search_id_attr      = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template . '__search' ) . '"' : '';
		$status_id_attr      = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template . '__status' ) . '"' : '';
		$results_id_attr     = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template . '__results' ) . '"' : '';

		echo '<div class="lerm-ajax-select"';
		echo ' data-target="' . esc_attr( $input_id ) . '"';
		echo ' data-schema-id="' . esc_attr( $page->schema_id() ) . '"';
		echo ' data-source="' . esc_attr( $source_id ) . '"';
		echo ' data-multiple="' . esc_attr( $multiple ? '1' : '0' ) . '"';
		echo ' data-placeholder="' . esc_attr( $placeholder ) . '"';
		echo ' data-search-label="' . esc_attr( $search_label ) . '"';
		echo ' data-min-search-length="' . esc_attr( (string) $min_search_length ) . '"';
		echo ' data-per-page="' . esc_attr( (string) $per_page ) . '"';
		echo ' data-allow-clear="' . esc_attr( $allow_clear ? '1' : '0' ) . '"';
		if ( '' !== $input_name_template ) {
			echo ' data-input-name-template="' . esc_attr( $input_name_template ) . '"';
		}
		echo '>';

		echo '<div class="lerm-ajax-select__selected" data-lerm-ajax-select-selected></div>';
		echo '<div class="lerm-ajax-select__controls">';
		printf(
			'<input type="search" id="%1$s__search" class="regular-text lerm-ajax-select__search" placeholder="%2$s" aria-label="%3$s" aria-haspopup="listbox" aria-controls="%4$s" aria-describedby="%5$s" autocomplete="off" spellcheck="false"%6$s>',
			esc_attr( $input_id ),
			esc_attr( $placeholder ),
			esc_attr( $search_label ),
			esc_attr( $results_id ),
			esc_attr( $status_id ),
			$search_id_attr
		);
		echo '</div>';
		printf(
			'<div id="%1$s" class="lerm-ajax-select__status" data-lerm-ajax-select-status role="status" aria-live="polite"%2$s>',
			esc_attr( $status_id ),
			$status_id_attr
		);
		echo esc_html(
			$min_search_length > 0
				? sprintf(
					/* translators: %d: minimum character count */
					__( 'Type %d or more characters to search.', 'lerm' ),
					$min_search_length
				)
				: __( 'Start typing to search.', 'lerm' )
		);
		echo '</div>';
		echo '<div class="lerm-ajax-select__dropdown" data-lerm-ajax-select-dropdown hidden>';
		printf(
			'<ul id="%1$s" class="lerm-ajax-select__results" data-lerm-ajax-select-results role="listbox"%2$s></ul>',
			esc_attr( $results_id ),
			$results_id_attr
		);
		echo '</div>';
		echo '<div class="lerm-ajax-select__values" data-lerm-ajax-select-values>';

		if ( $multiple ) {
			if ( empty( $selected_values ) ) {
				printf(
					'<input type="hidden" name="%1$s[]" value="" data-lerm-ajax-select-input="empty"%2$s>',
					esc_attr( $field_name ),
					$name_attr
				);
			} else {
				foreach ( $selected_values as $selected_value ) {
					printf(
						'<input type="hidden" name="%1$s[]" value="%2$s" data-lerm-ajax-select-input="1"%3$s>',
						esc_attr( $field_name ),
						esc_attr( $selected_value ),
						$name_attr
					);
				}
			}
		} else {
			printf(
				'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" data-lerm-ajax-select-input="1"%4$s%5$s>',
				esc_attr( $input_id ),
				esc_attr( $field_name ),
				esc_attr( $selected_values[0] ?? '' ),
				$name_attr,
				$id_attr
			);
		}

		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 * @return array<int, string|int|float>
	 */
	private static function normalize_selected_values( array $field, $value ): array {
		$multiple = ! empty( $field['multiple'] );
		$cast     = (string) ( $field['cast'] ?? '' );

		if ( $multiple ) {
			$values = is_array( $value ) ? $value : array();
			$clean  = array();

			foreach ( $values as $item ) {
				$item = PageSchema::scalar_value( $item, '', true );

				if ( '' === $item ) {
					continue;
				}

				$clean[] = self::cast_scalar_value( $item, $cast );
			}

			return array_values( array_unique( $clean, SORT_REGULAR ) );
		}

		$item = PageSchema::scalar_value( $value, '', true );

		return '' === $item ? array() : array( self::cast_scalar_value( $item, $cast ) );
	}

	/**
	 * @param mixed $value
	 * @return string|int|float|array<int, string|int|float>
	 */
	private static function sanitize_ajax_select_value( array $field, $value ) {
		$multiple = ! empty( $field['multiple'] );
		$cast     = (string) ( $field['cast'] ?? '' );

		if ( $multiple ) {
			$values = is_array( $value ) ? $value : array();
			$clean  = array();

			foreach ( $values as $item ) {
				$item = PageSchema::scalar_value( $item, '', true );

				if ( '' === $item ) {
					continue;
				}

				$clean[] = self::cast_scalar_value( $item, $cast );
			}

			return array_values( array_unique( $clean, SORT_REGULAR ) );
		}

		return self::cast_scalar_value( PageSchema::scalar_value( $value, '', true ), $cast );
	}

	/**
	 * @return string|int|float
	 */
	private static function cast_scalar_value( string $value, string $cast ) {
		if ( 'int' === $cast ) {
			return (int) $value;
		}

		if ( 'float' === $cast ) {
			return (float) $value;
		}

		return $value;
	}
}
