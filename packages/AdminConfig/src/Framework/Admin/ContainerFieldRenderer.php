<?php
/**
 * Classic admin structured container field renderer.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Admin;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContainerFieldRenderer {

	private FieldTypeRegistry $field_types;
	private OptionsPage $page;

	/**
	 * @var array<string, mixed>
	 */
	private array $field_errors;
	private string $current_path;

	/**
	 * @param array<string, mixed> $field_errors
	 */
	public function __construct( FieldTypeRegistry $field_types, OptionsPage $page, array $field_errors = array(), string $current_path = '' ) {
		$this->field_types  = $field_types;
		$this->page         = $page;
		$this->field_errors = $field_errors;
		$this->current_path = $current_path;
	}

	/**
	 * Render fieldsets as a compact grid of nested controls.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_fieldset( array $field, $value, string $field_name ): void {
		$field_id = (string) $field['id'];
		$values   = is_array( $value ) ? $value : array();
		$fields   = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$path     = $this->resolve_render_path( $field_id );
		$invalid  = $this->field_has_errors( $path, true );
		$classes  = array_filter(
			array_map(
				'trim',
				explode( ' ', 'lerm-fieldset ' . (string) ( $field['wrapper_class'] ?? '' ) )
			)
		);

		if ( $invalid ) {
			$classes[] = 'is-invalid';
		}

		echo '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $path ) . '">';
		$this->render_child_fields( $fields, $values, $field_name, $field_id, $path );
		echo '</div>';
	}

	/**
	 * Render accordion field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_accordion( array $field, $value, string $field_name ): void {
		$field_id       = (string) $field['id'];
		$field_path     = $this->resolve_render_path( $field_id );
		$values         = is_array( $value ) ? $value : array();
		$items          = $this->panel_items( $field );
		$allow_multiple = ! empty( $field['allow_multiple'] );
		$open_first     = ! array_key_exists( 'open_first', $field ) || ! empty( $field['open_first'] );
		$invalid        = $this->field_has_errors( $field_path, true );

		echo '<div class="lerm-fieldset lerm-accordion-field' . ( $invalid ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-accordion data-allow-multiple="' . esc_attr( $allow_multiple ? '1' : '0' ) . '">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_title   = (string) $item['title'];
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $item_path, true );
			$is_open      = $item_invalid || ! empty( $item['open'] ) || ( $open_first && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$button_id    = $panel_id . '__button';

			echo '<section class="lerm-accordion__item' . ( $item_invalid ? ' is-invalid' : '' ) . ( $is_open ? ' is-open' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '">';
			echo '<button type="button" id="' . esc_attr( $button_id ) . '" class="lerm-accordion__trigger" data-lerm-accordion-trigger aria-expanded="' . esc_attr( $is_open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '">';
			echo '<span>' . esc_html( $item_title ) . '</span>';
			echo '<span class="lerm-accordion__chevron" aria-hidden="true"></span>';
			echo '</button>';
			echo '<div id="' . esc_attr( $panel_id ) . '" class="lerm-accordion__panel" data-lerm-accordion-panel aria-labelledby="' . esc_attr( $button_id ) . '"' . ( $is_open ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-accordion__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</div></section>';
		}

		echo '</div>';
	}

	/**
	 * Render tabbed field panels.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_tabbed( array $field, $value, string $field_name ): void {
		$field_id   = (string) $field['id'];
		$field_path = $this->resolve_render_path( $field_id );
		$values     = is_array( $value ) ? $value : array();
		$items      = $this->panel_items( $field );
		$active_tab = sanitize_key( (string) ( $field['default_tab'] ?? '' ) );

		if ( '' === $active_tab && ! empty( $items[0]['id'] ) ) {
			$active_tab = (string) $items[0]['id'];
		}

		foreach ( $items as $item ) {
			$item_id   = (string) ( $item['id'] ?? '' );
			$item_path = $this->compose_render_path( $field_path, $item_id );

			if ( '' !== $item_id && $this->field_has_errors( $item_path, true ) ) {
				$active_tab = $item_id;
				break;
			}
		}

		echo '<div class="lerm-fieldset lerm-tabbed-field' . ( $this->field_has_errors( $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '" data-lerm-tabbed data-default-tab="' . esc_attr( $active_tab ) . '">';
		echo '<div class="lerm-tabbed__nav" role="tablist">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_invalid = $this->field_has_errors( $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<button type="button" id="' . esc_attr( $trigger_id ) . '" class="lerm-tabbed__trigger' . ( $is_active ? ' is-active' : '' ) . ( $item_invalid ? ' is-invalid' : '' ) . '" data-lerm-tabbed-trigger data-lerm-tabbed-target="' . esc_attr( $item_id ) . '" role="tab" aria-selected="' . esc_attr( $is_active ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '" tabindex="' . esc_attr( $is_active ? '0' : '-1' ) . '">';
			echo esc_html( (string) $item['title'] );
			echo '</button>';
		}

		echo '</div><div class="lerm-tabbed__panels">';

		foreach ( $items as $index => $item ) {
			$item_id      = (string) $item['id'];
			$item_path    = $this->compose_render_path( $field_path, $item_id );
			$item_desc    = (string) ( $item['description'] ?? '' );
			$item_fields  = is_array( $item['fields'] ?? null ) ? $item['fields'] : array();
			$item_values  = is_array( $values[ $item_id ] ?? null ) ? $values[ $item_id ] : array();
			$item_invalid = $this->field_has_errors( $item_path, true );
			$is_active    = $item_id === $active_tab || ( '' === $active_tab && 0 === $index );
			$panel_id     = $field_id . '__' . $item_id;
			$trigger_id   = $panel_id . '__tab';

			echo '<section id="' . esc_attr( $panel_id ) . '" class="lerm-tabbed__panel' . ( $item_invalid ? ' is-invalid' : '' ) . '" data-item-id="' . esc_attr( $item_id ) . '" data-lerm-tabbed-panel="' . esc_attr( $item_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $trigger_id ) . '"' . ( $is_active ? '' : ' hidden' ) . '>';

			if ( '' !== $item_desc ) {
				echo '<p class="description lerm-tabbed__description">' . esc_html( $item_desc ) . '</p>';
			}

			$this->render_child_fields(
				$item_fields,
				$item_values,
				$field_name . '[' . $item_id . ']',
				$field_id . '__' . $item_id,
				$item_path
			);
			echo '</section>';
		}

		echo '</div></div>';
	}

	/**
	 * Render repeatable groups.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	public function render_group( array $field, $value, string $field_name ): void {
		$field_id    = (string) $field['id'];
		$field_path  = $this->resolve_render_path( $field_id );
		$items       = is_array( $value ) ? array_values( $value ) : array();
		$button_text = (string) ( $field['button_text'] ?? __( 'Add item', 'lerm-admin-config' ) );

		echo '<div class="lerm-group' . ( $this->field_has_errors( $field_path, true ) ? ' is-invalid' : '' ) . '" data-target="' . esc_attr( $field_id ) . '" data-field-path="' . esc_attr( $field_path ) . '">';
		echo '<div class="lerm-group__toolbar">';
		echo '<button type="button" class="button button-secondary" data-lerm-group-add>' . esc_html( $button_text ) . '</button>';
		echo '</div>';
		echo '<div class="lerm-group__empty" ' . ( ! empty( $items ) ? 'hidden' : '' ) . '>' . esc_html__( 'No items added yet.', 'lerm-admin-config' ) . '</div>';
		echo '<div class="lerm-group-list" data-lerm-group-list>';

		foreach ( $items as $index => $item ) {
			echo $this->group_item_markup( $field, $field_name, is_array( $item ) ? $item : array(), (string) $index, $field_path, $this->compose_render_path( $field_path, '__INDEX__' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
		echo '<script type="text/html" class="lerm-group-template">' . $this->group_item_markup( $field, $field_name, array(), '__INDEX__', $field_path, $this->compose_render_path( $field_path, '__INDEX__' ) ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Build one repeatable group item.
	 *
	 * @param array<string, mixed> $field Group definition.
	 * @param array<string, mixed> $item  Current item values.
	 */
	private function group_item_markup( array $field, string $field_name, array $item, string $index, string $field_path = '', string $path_template = '' ): string {
		$fields          = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$item_path       = $this->compose_render_path( $field_path, $index );
		$item_has_errors = $this->field_has_errors( $item_path, true );

		ob_start();
		?>
		<div class="lerm-group-item<?php echo esc_attr( $item_has_errors ? ' is-invalid' : '' ); ?>" data-lerm-group-item data-index="<?php echo esc_attr( $index ); ?>" data-field-path="<?php echo esc_attr( $item_path ); ?>" data-field-path-template="<?php echo esc_attr( $path_template ); ?>">
			<div class="lerm-group-item__header">
				<span class="lerm-sorter-handle" aria-hidden="true">&#8645;</span>
				<strong class="lerm-group-item__title">
				<?php
				// translators: %s is the item number in the group, starting from 1. For example: "Item 1", "Item 2", etc. Do not translate the number itself.
				echo esc_html( sprintf( __( 'Item %s', 'lerm-admin-config' ), is_numeric( $index ) ? (string) ( (int) $index + 1 ) : '#' ) );
				?>
				</strong>
				<button type="button" class="button button-secondary button-link-delete" data-lerm-group-remove><?php echo esc_html__( 'Remove', 'lerm-admin-config' ); ?></button>
			</div>
			<div class="lerm-group-item__body">
				<?php
				$this->render_child_fields(
					$fields,
					$item,
					$field_name . '[' . $index . ']',
					(string) $field['id'] . '__' . $index,
					$item_path,
					$this->compose_render_path( $path_template, '' ),
					'lerm-group-item__field'
				);
				?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a nested sub-field for fieldsets and repeaters.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Field value.
	 */
	private function render_nested_field( array $field, $value, string $field_name, string $input_id, string $name_template = '', string $id_template = '' ): void {
		$field_type    = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$name_attr     = '' !== $name_template ? ' data-name-template="' . esc_attr( $name_template ) . '"' : '';
		$id_attr       = '' !== $id_template ? ' data-id-template="' . esc_attr( $id_template ) . '"' : '';
		$custom_render = $this->field_types->nested_render_callback( $field_type );

		if ( is_callable( $custom_render ) ) {
			call_user_func( $custom_render, $field, $value, $field_name, $input_id, $this->page, $name_template, $id_template );
			return;
		}

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s"%6$s%7$s>',
			esc_attr( (string) ( $field['input_type'] ?? 'text' ) ),
			esc_attr( $input_id ),
			esc_attr( $field_name ),
			esc_attr( PageSchema::scalar_value( $value ) ),
			esc_attr( (string) ( $field['placeholder'] ?? '' ) ),
			$name_attr,
			$id_attr
		);
	}

	/**
	 * Render a flat set of child controls inside a structured container.
	 *
	 * @param array<int, array<string, mixed>> $fields Child field definitions.
	 * @param array<string, mixed>             $values Child field values.
	 */
	private function render_child_fields( array $fields, array $values, string $field_name, string $field_id, string $base_path = '', string $base_path_template = '', string $item_class = 'lerm-fieldset__item' ): void {
		$base_path          = '' !== $base_path ? $base_path : $this->current_path;
		$base_path_template = '' !== $base_path_template ? $base_path_template : $base_path;

		foreach ( $fields as $child ) {
			if ( ! is_array( $child ) || ! isset( $child['id'] ) ) {
				continue;
			}

			$child_id    = (string) $child['id'];
			$child_name  = $field_name . '[' . $child_id . ']';
			$child_value = $values[ $child_id ] ?? ( $child['default'] ?? '' );
			$child_path  = $this->compose_render_path( $base_path, $child_id );
			$error       = $this->field_error_message( $child_path );
			$has_errors  = $this->field_has_errors( $child_path, true );
			$classes     = trim( $item_class . ( $has_errors ? ' is-invalid' : '' ) );

			echo '<div class="' . esc_attr( $classes ) . '" data-subfield-id="' . esc_attr( $child_id ) . '" data-field-type="' . esc_attr( sanitize_key( (string) ( $child['type'] ?? 'text' ) ) ) . '" data-field-path="' . esc_attr( $child_path ) . '"';

			if ( '' !== $base_path_template ) {
				echo ' data-field-path-template="' . esc_attr( $this->compose_render_path( $base_path_template, $child_id ) ) . '"';
			}

			echo '>';
			echo '<label class="lerm-fieldset__label" for="' . esc_attr( $field_id . '__' . $child_id ) . '">' . esc_html( (string) ( $child['label'] ?? $child_id ) ) . '</label>';
			$this->render_nested_field( $child, $child_value, $child_name, $field_id . '__' . $child_id );

			if ( ! empty( $child['description'] ) ) {
				echo '<p class="description">' . esc_html( (string) $child['description'] ) . '</p>';
			}

			if ( '' !== $error ) {
				printf( '<p class="lerm-field-error" data-lerm-field-error-message>%s</p>', esc_html( $error ) );
			}

			echo '</div>';
		}
	}

	private function field_error_message( string $field_id ): string {
		return implode( ' ', $this->field_error_messages( $field_id ) );
	}

	/**
	 * @return array<int, string>
	 */
	private function field_error_messages( string $field_path, bool $include_descendants = false ): array {
		$messages = array();

		foreach ( $this->field_errors as $error_path => $error_messages ) {
			$error_path = (string) $error_path;
			$is_match   = $error_path === $field_path;

			if ( ! $is_match && $include_descendants && '' !== $field_path ) {
				$is_match = str_starts_with( $error_path, $field_path . '.' );
			}

			if ( ! $is_match ) {
				continue;
			}

			foreach ( (array) $error_messages as $message ) {
				if ( is_scalar( $message ) && '' !== (string) $message ) {
					$messages[] = (string) $message;
				}
			}
		}

		return array_values( array_unique( $messages ) );
	}

	private function field_has_errors( string $field_path, bool $include_descendants = false ): bool {
		return ! empty( $this->field_error_messages( $field_path, $include_descendants ) );
	}

	private function resolve_render_path( string $field_id ): string {
		if ( '' === $field_id ) {
			return $this->current_path;
		}

		if ( '' === $this->current_path || $field_id === $this->current_path ) {
			return $field_id;
		}

		return $this->compose_render_path( $this->current_path, $field_id );
	}

	private function compose_render_path( string $base_path, string $segment ): string {
		if ( '' === $segment ) {
			return $base_path;
		}

		if ( '' === $base_path ) {
			return $segment;
		}

		return $base_path . '.' . $segment;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<int, array<string, mixed>>
	 */
	private function panel_items( array $field ): array {
		$items      = is_array( $field['items'] ?? null ) ? $field['items'] : array();
		$normalized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id    = isset( $item['id'] ) && is_scalar( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
			$item_title = isset( $item['title'] ) && is_scalar( $item['title'] ) ? (string) $item['title'] : '';
			$item_id    = '' !== $item_id ? $item_id : 'item_' . (string) ( (int) $index + 1 );

			$normalized[] = array(
				'id'          => $item_id,
				'title'       => '' !== $item_title ? $item_title : ucfirst( str_replace( '_', ' ', $item_id ) ),
				'description' => isset( $item['description'] ) && is_scalar( $item['description'] ) ? (string) $item['description'] : '',
				'fields'      => is_array( $item['fields'] ?? null ) ? $item['fields'] : array(),
				'open'        => ! empty( $item['open'] ),
			);
		}

		return $normalized;
	}
}
