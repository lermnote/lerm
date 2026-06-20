<?php
/**
 * Registry for field modules and lazy module activation.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Registry;

use InvalidArgumentException;
use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldModuleRegistry {

	/**
	 * @var array<string, FieldModule>
	 */
	private array $modules = array();

	/**
	 * @var array<string, string>
	 */
	private array $field_type_map = array();

	/**
	 * @var array<string, bool>
	 */
	private array $enabled = array();

	public function __construct(
		private FieldTypeRegistry $field_types
	) {
	}

	public function register( FieldModule $module ): void {
		$module_id                   = sanitize_key( $module->id() );
		$this->modules[ $module_id ] = $module;

		foreach ( $module->field_types() as $field_type ) {
			$field_type = sanitize_key( $field_type );

			if ( '' === $field_type ) {
				continue;
			}

			$this->field_type_map[ $field_type ] = $module_id;
		}

		if ( $module->enabled_by_default() ) {
			$this->enable( $module_id );
		}
	}

	public function has( string $module_id ): bool {
		return isset( $this->modules[ sanitize_key( $module_id ) ] );
	}

	public function is_enabled( string $module_id ): bool {
		return ! empty( $this->enabled[ sanitize_key( $module_id ) ] );
	}

	public function enable( string $module_id ): void {
		$module_id = sanitize_key( $module_id );

		if ( isset( $this->enabled[ $module_id ] ) ) {
			return;
		}

		if ( ! isset( $this->modules[ $module_id ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Admin config field module "%s" is not registered.',
					$module_id
				)
			);
		}

		foreach ( $this->modules[ $module_id ]->definitions() as $type => $definition ) {
			$this->field_types->register(
				(string) $type,
				array_merge(
					$definition,
					array(
						'builtin' => true,
					)
				)
			);
		}

		$this->enabled[ $module_id ] = true;
	}

	/**
	 * Enable any registered field modules required by a schema definition.
	 *
	 * @param array<string, mixed> $definition Schema definition.
	 */
	public function enable_for_definition( array $definition ): void {
		$this->enable_for_field_types( $this->field_types_for_definition( $definition ) );
	}

	/**
	 * Return the field types referenced by a schema definition.
	 *
	 * This only inspects the schema array available at registration time. If
	 * field types are assembled later from external data, call
	 * `enable_for_field_types()` or `enable_all()` explicitly.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<int, string>
	 */
	public function field_types_for_definition( array $definition ): array {
		$types = array();

		foreach ( PageSchema::sections( $definition ) as $section ) {
			if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
				$this->collect_field_types( $section['fields'], $types );
			}

			foreach ( PageSchema::section_groups( $section ) as $group ) {
				$group_fields = $group['fields'] ?? array();

				if ( is_array( $group_fields ) ) {
					$this->collect_field_types( $group_fields, $types );
				}
			}
		}

		return array_keys( $types );
	}

	/**
	 * Return the registered module IDs required by a schema definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<int, string>
	 */
	public function modules_for_definition( array $definition ): array {
		return $this->modules_for_field_types( $this->field_types_for_definition( $definition ) );
	}

	/**
	 * Return the registered module ID for a field type, when known.
	 */
	public function module_for_field_type( string $field_type ): ?string {
		$field_type = sanitize_key( $field_type );

		if ( '' === $field_type ) {
			return null;
		}

		return $this->field_type_map[ $field_type ] ?? null;
	}

	/**
	 * Return the registered module IDs required by a field-type list.
	 *
	 * @param array<int, string> $field_types
	 * @return array<int, string>
	 */
	public function modules_for_field_types( array $field_types ): array {
		$modules = array();

		foreach ( $field_types as $field_type ) {
			$module_id = $this->module_for_field_type( (string) $field_type );

			if ( null === $module_id ) {
				continue;
			}

			$modules[ $module_id ] = $module_id;
		}

		return array_values( $modules );
	}

	/**
	 * Enable modules for a known list of field types.
	 *
	 * @param array<int, string> $field_types
	 */
	public function enable_for_field_types( array $field_types ): void {
		foreach ( $this->modules_for_field_types( $field_types ) as $module_id ) {
			$this->enable( $module_id );
		}
	}

	/**
	 * Enable every registered module.
	 */
	public function enable_all(): void {
		foreach ( array_keys( $this->modules ) as $module_id ) {
			$this->enable( $module_id );
		}
	}

	/**
	 * @return array<string, FieldModule>
	 */
	public function all(): array {
		return $this->modules;
	}

	/**
	 * @param array<int, mixed>    $fields
	 * @param array<string, bool> &$types
	 */
	private function collect_field_types( array $fields, array &$types ): void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );

			if ( '' !== $field_type ) {
				$types[ $field_type ] = true;
			}

			$children = $field['fields'] ?? array();

			if ( is_array( $children ) && ! empty( $children ) ) {
				$this->collect_field_types( $children, $types );
			}

			$items = is_array( $field['items'] ?? null ) ? $field['items'] : array();

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || ! is_array( $item['fields'] ?? null ) ) {
					continue;
				}

				$this->collect_field_types( $item['fields'], $types );
			}
		}
	}
}
