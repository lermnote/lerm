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
use Lerm\AdminConfig\Framework\Registry\FieldTypeRegistry;
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
		$module_id               = sanitize_key( $module->id() );
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
		foreach ( $this->definition_field_types( $definition ) as $field_type ) {
			if ( isset( $this->field_type_map[ $field_type ] ) ) {
				$this->enable( $this->field_type_map[ $field_type ] );
			}
		}
	}

	/**
	 * @return array<string, FieldModule>
	 */
	public function all(): array {
		return $this->modules;
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array<int, string>
	 */
	private function definition_field_types( array $definition ): array {
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
		}
	}
}
