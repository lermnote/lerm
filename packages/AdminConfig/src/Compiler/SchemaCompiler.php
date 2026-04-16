<?php
/**
 * Compile runtime metadata from PHP schema definitions.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Compiler;

use Lerm\AdminConfig\Framework\Support\PageSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaCompiler {

	public function compile( array $schema ): CompiledSchema {
		$id               = $this->schema_id( $schema );
		$container        = $this->compile_container( $schema );
		$store            = $this->compile_store( $schema, $id );
		$definition       = $schema;
		$definition['id'] = $id;
		$definition['container'] = $container;
		$definition['store']     = $store;

		$field_metadata  = array();
		$dependency_graph = array();

		foreach ( PageSchema::fields( $definition ) as $field ) {
			if ( ! is_array( $field ) || empty( $field['id'] ) ) {
				continue;
			}

			$field_id                    = (string) $field['id'];
			$field_metadata[ $field_id ] = $this->compile_field_metadata( $field );

			if ( ! empty( $field_metadata[ $field_id ]['dependency'] ) ) {
				$dependency_graph[ $field_id ] = $field_metadata[ $field_id ]['dependency'];
			}
		}

		$defaults      = PageSchema::defaults( $definition );
		$client_config = array(
			'schemaId'     => $id,
			'container'    => $container,
			'store'        => $store,
			'defaults'     => $defaults,
			'fields'       => $field_metadata,
			'dependencies' => $dependency_graph,
			'optionName'   => (string) ( $definition['option_name'] ?? $store['key'] ?? $id ),
		);

		return new CompiledSchema(
			$id,
			$definition,
			$defaults,
			$dependency_graph,
			$field_metadata,
			$client_config,
			$container,
			$store
		);
	}

	/**
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	private function compile_container( array $schema ): array {
		$container = is_array( $schema['container'] ?? null ) ? $schema['container'] : array();
		$type      = sanitize_key( (string) ( $container['type'] ?? 'options_page' ) );

		if ( '' === $type ) {
			$type = 'options_page';
		}

		$compiled = array(
			'type' => $type,
		);

		foreach ( array( 'capability', 'parent_slug', 'post_types', 'taxonomy', 'context', 'priority' ) as $key ) {
			if ( array_key_exists( $key, $container ) ) {
				$compiled[ $key ] = $container[ $key ];
			}
		}

		return $compiled;
	}

	/**
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	private function compile_store( array $schema, string $id ): array {
		$store = is_array( $schema['store'] ?? null ) ? $schema['store'] : array();
		$type  = sanitize_key( (string) ( $store['type'] ?? 'option' ) );
		$key   = sanitize_key( (string) ( $store['key'] ?? $schema['option_name'] ?? $id ) );

		if ( '' === $type ) {
			$type = 'option';
		}

		if ( '' === $key ) {
			$key = $id;
		}

		$compiled = array(
			'type' => $type,
			'key'  => $key,
		);

		foreach ( array( 'object_id', 'network_id', 'autoload' ) as $prop ) {
			if ( array_key_exists( $prop, $store ) ) {
				$compiled[ $prop ] = $store[ $prop ];
			}
		}

		return $compiled;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	private function compile_field_metadata( array $field ): array {
		$metadata = array(
			'id'      => (string) $field['id'],
			'type'    => sanitize_key( (string) ( $field['type'] ?? 'text' ) ),
			'default' => $field['default'] ?? '',
			'label'   => $this->first_string( $field, array( 'label' ) ),
		);

		$description = $this->first_string( $field, array( 'description' ) );
		if ( '' !== $description ) {
			$metadata['description'] = $description;
		}

		if ( isset( $field['capability'] ) && is_scalar( $field['capability'] ) ) {
			$metadata['capability'] = (string) $field['capability'];
		}

		if ( isset( $field['ui'] ) && is_array( $field['ui'] ) ) {
			$metadata['ui'] = $field['ui'];
		}

		if ( isset( $field['client'] ) && is_array( $field['client'] ) ) {
			$metadata['client'] = $field['client'];
		}

		$dependency = $this->compile_dependency( $field );
		if ( ! empty( $dependency ) ) {
			$metadata['dependency'] = $dependency;
		}

		return $metadata;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	private function compile_dependency( array $field ): array {
		$dependency = $field['dependency'] ?? null;

		if ( ! is_array( $dependency ) || empty( $dependency[0] ) ) {
			return array();
		}

		$controller = sanitize_key( (string) $dependency[0] );
		$operator   = isset( $dependency[1] ) && is_scalar( $dependency[1] ) ? trim( (string) $dependency[1] ) : '==';
		$value      = $dependency[2] ?? true;

		if ( '' === $controller ) {
			return array();
		}

		return array(
			'field'    => $controller,
			'operator' => '' !== $operator ? $operator : '==',
			'value'    => $value,
		);
	}

	/**
	 * @param array<string, mixed> $schema
	 */
	private function schema_id( array $schema ): string {
		$id = isset( $schema['id'] ) && is_scalar( $schema['id'] ) ? sanitize_key( (string) $schema['id'] ) : '';

		if ( '' !== $id ) {
			return $id;
		}

		$option_name = isset( $schema['option_name'] ) && is_scalar( $schema['option_name'] ) ? sanitize_key( (string) $schema['option_name'] ) : '';

		return '' !== $option_name ? $option_name : 'admin-config-schema';
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<int, string>   $keys
	 */
	private function first_string( array $field, array $keys ): string {
		foreach ( $keys as $key ) {
			if ( isset( $field[ $key ] ) && is_scalar( $field[ $key ] ) ) {
				return (string) $field[ $key ];
			}
		}

		return '';
	}
}
