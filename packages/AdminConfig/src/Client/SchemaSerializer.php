<?php
/**
 * Serialize compiled schemas into the public client protocol.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Client;

use Lerm\AdminConfig\Compiler\CompiledSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaSerializer {

	public const PROTOCOL_VERSION = 1;

	/**
	 * Controls that are exposed to clients but are not editable in the first
	 * block/editor surfaces yet.
	 *
	 * @var array<int, string>
	 */
	private const READ_ONLY_CONTROLS = array(
		'accordion',
		'ajax_select',
		'background',
		'backup_tools',
		'border',
		'code_editor',
		'content',
		'heading',
		'icon',
		'image_select',
		'link_color',
		'notice',
		'palette',
		'sorter',
		'subheading',
		'tabbed',
		'typography',
		'wp_editor',
	);

	/**
	 * @var array<int, string>
	 */
	private const FIELD_SCALAR_KEYS = array(
		'button_text',
		'data_source',
		'input_type',
		'library',
		'max',
		'min',
		'placeholder',
		'remove_text',
		'rows',
		'source',
		'step',
		'unit',
	);

	/**
	 * @var array<int, string>
	 */
	private const FIELD_BOOLEAN_KEYS = array(
		'all',
		'bottom',
		'height',
		'left',
		'right',
		'show_units',
		'top',
		'width',
	);

	/**
	 * @var array<int, string>
	 */
	private const FIELD_ARRAY_KEYS = array(
		'fields',
		'units',
	);

	/**
	 * @param array<string, bool> $actions
	 * @return array<string, mixed>
	 */
	public static function document( CompiledSchema $schema, array $actions = array() ): array {
		return array(
			'protocolVersion' => self::PROTOCOL_VERSION,
			'id'              => $schema->id(),
			'schemaId'        => $schema->id(),
			'title'           => self::schema_title( $schema ),
			'description'     => self::schema_description( $schema ),
			'container'       => self::container( $schema ),
			'store'           => self::store( $schema ),
			'actions'         => self::actions( $actions ),
			'defaults'        => self::without_server_only_keys( $schema->defaults() ),
			'sections'        => self::sections( $schema ),
			'fields'          => self::fields( $schema ),
			'dependencies'    => self::without_server_only_keys( $schema->dependency_graph() ),
			'optionName'      => (string) ( $schema->client_config()['optionName'] ?? $schema->id() ),
			'links'           => self::links( $schema ),
		);
	}

	/**
	 * @param array<string, bool> $actions
	 * @return array<string, mixed>
	 */
	public static function summary( CompiledSchema $schema, array $actions = array() ): array {
		return array(
			'id'          => $schema->id(),
			'title'       => self::schema_title( $schema ),
			'description' => self::schema_description( $schema ),
			'container'   => self::container( $schema ),
			'store'       => self::store( $schema ),
			'actions'     => self::actions( $actions ),
			'links'       => self::links( $schema ),
		);
	}

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, bool>  $actions
	 * @return array<string, mixed>
	 */
	public static function values( CompiledSchema $schema, array $values, array $actions = array() ): array {
		return array(
			'schemaId' => $schema->id(),
			'values'   => $values,
			'defaults' => self::without_server_only_keys( $schema->defaults() ),
			'actions'  => self::actions( $actions ),
		);
	}

	/**
	 * Return the pre-v1 client config shape used by the deprecated /schema alias.
	 *
	 * @return array<string, mixed>
	 */
	public static function legacy_client_config( CompiledSchema $schema ): array {
		return self::without_server_only_keys( $schema->client_config() );
	}

	/**
	 * @param array<string, bool> $actions
	 * @return array{read: bool, edit: bool, reset: bool, export: bool, import: bool, dataSource: bool}
	 */
	private static function actions( array $actions ): array {
		$defaults = array(
			'read'       => true,
			'edit'       => true,
			'reset'      => true,
			'export'     => true,
			'import'     => true,
			'dataSource' => true,
		);

		foreach ( $defaults as $action => $default ) {
			if ( array_key_exists( $action, $actions ) ) {
				$defaults[ $action ] = (bool) $actions[ $action ];
			}
		}

		return $defaults;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function container( CompiledSchema $schema ): array {
		$container = $schema->container();
		$type      = sanitize_key( (string) ( $container['type'] ?? 'options_page' ) );

		return array(
			'type'    => '' !== $type ? $type : 'options_page',
			'surface' => self::surface_for_container( $type ),
			'context' => array(
				'kind' => self::context_kind_for_container( $type ),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function store( CompiledSchema $schema ): array {
		$store = $schema->store();
		$type  = sanitize_key( (string) ( $store['type'] ?? 'option' ) );

		return array(
			'type'  => '' !== $type ? $type : 'option',
			'scope' => self::scope_for_store( $type ),
			'key'   => isset( $store['key'] ) && is_scalar( $store['key'] ) ? (string) $store['key'] : $schema->id(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function sections( CompiledSchema $schema ): array {
		return self::without_server_only_keys( (array) ( $schema->client_config()['sections'] ?? array() ) );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function fields( CompiledSchema $schema ): array {
		$locations = self::field_locations( $schema );
		$fields    = array();

		foreach ( $schema->field_metadata() as $field_id => $metadata ) {
			$field_id            = (string) $field_id;
			$fields[ $field_id ] = self::field_payload(
				$field_id,
				$metadata,
				$locations[ $field_id ] ?? array(
					'section' => '',
					'group'   => '',
				)
			);
		}

		return $fields;
	}

	/**
	 * @param array<string, mixed> $metadata
	 * @param array{section: string, group: string} $location
	 * @return array<string, mixed>
	 */
	private static function field_payload( string $field_id, array $metadata, array $location ): array {
		$type    = sanitize_key( (string) ( $metadata['type'] ?? 'text' ) );
		$client  = self::without_server_only_keys( isset( $metadata['client'] ) && is_array( $metadata['client'] ) ? $metadata['client'] : array() );
		$control = sanitize_key( (string) ( $client['control'] ?? $type ) );

		if ( '' === $control ) {
			$control = $type;
		}

		$field = array(
			'id'          => $field_id,
			'path'        => isset( $metadata['path'] ) && is_scalar( $metadata['path'] ) ? (string) $metadata['path'] : $field_id,
			'type'        => '' !== $type ? $type : 'text',
			'control'     => $control,
			'label'       => isset( $metadata['label'] ) && is_scalar( $metadata['label'] ) ? (string) $metadata['label'] : '',
			'description' => isset( $metadata['description'] ) && is_scalar( $metadata['description'] ) ? (string) $metadata['description'] : '',
			'default'     => $metadata['default'] ?? null,
			'section'     => $location['section'],
			'group'       => $location['group'],
			'choices'     => isset( $metadata['choices'] ) && is_array( $metadata['choices'] ) ? self::without_server_only_keys( $metadata['choices'] ) : array(),
			'dependency'  => isset( $metadata['dependency'] ) && is_array( $metadata['dependency'] ) ? self::without_server_only_keys( $metadata['dependency'] ) : null,
			'multiple'    => ! empty( $metadata['multiple'] ),
			'readOnly'    => self::field_is_read_only( $control, $client ),
			'supported'   => '' !== $control,
			'ui'          => isset( $metadata['ui'] ) && is_array( $metadata['ui'] ) ? self::without_server_only_keys( $metadata['ui'] ) : array(),
			'client'      => $client,
		);

		foreach ( self::FIELD_SCALAR_KEYS as $key ) {
			if ( isset( $metadata[ $key ] ) && is_scalar( $metadata[ $key ] ) ) {
				$field[ $key ] = $metadata[ $key ];
			}
		}

		foreach ( self::FIELD_BOOLEAN_KEYS as $key ) {
			if ( array_key_exists( $key, $metadata ) ) {
				$field[ $key ] = (bool) $metadata[ $key ];
			}
		}

		foreach ( self::FIELD_ARRAY_KEYS as $key ) {
			if ( ! isset( $metadata[ $key ] ) || ! is_array( $metadata[ $key ] ) ) {
				continue;
			}

			if ( 'fields' === $key ) {
				$field[ $key ] = self::nested_fields( $metadata[ $key ] );
				continue;
			}

			$field[ $key ] = self::without_server_only_keys( $metadata[ $key ] );
		}

		return $field;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @return array<int, array<string, mixed>>
	 */
	private static function nested_fields( array $fields ): array {
		$nested = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['id'] ) || ! is_scalar( $field['id'] ) ) {
				continue;
			}

			$nested[] = self::field_payload(
				(string) $field['id'],
				$field,
				array(
					'section' => '',
					'group'   => '',
				)
			);
		}

		return $nested;
	}

	/**
	 * @return array<string, array{section: string, group: string}>
	 */
	private static function field_locations( CompiledSchema $schema ): array {
		$locations = array();

		foreach ( self::sections( $schema ) as $section_id => $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			foreach ( (array) ( $section['fields'] ?? array() ) as $field_id ) {
				if ( is_scalar( $field_id ) ) {
					$locations[ (string) $field_id ] = array(
						'section' => (string) $section_id,
						'group'   => '',
					);
				}
			}

			foreach ( (array) ( $section['groups'] ?? array() ) as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}

				$group_id = isset( $group['id'] ) && is_scalar( $group['id'] ) ? (string) $group['id'] : '';

				foreach ( (array) ( $group['fields'] ?? array() ) as $field_id ) {
					if ( is_scalar( $field_id ) ) {
						$locations[ (string) $field_id ] = array(
							'section' => (string) $section_id,
							'group'   => $group_id,
						);
					}
				}
			}
		}

		return $locations;
	}

	/**
	 * @param array<string, mixed> $client
	 */
	private static function field_is_read_only( string $control, array $client ): bool {
		if ( array_key_exists( 'readOnly', $client ) ) {
			return (bool) $client['readOnly'];
		}

		if ( array_key_exists( 'read_only', $client ) ) {
			return (bool) $client['read_only'];
		}

		return in_array( $control, self::READ_ONLY_CONTROLS, true );
	}

	private static function schema_title( CompiledSchema $schema ): string {
		$definition = $schema->definition();
		$view       = is_array( $definition['view'] ?? null ) ? $definition['view'] : array();
		$menu       = is_array( $definition['menu'] ?? null ) ? $definition['menu'] : array();

		foreach ( array( $definition['title'] ?? null, $view['title'] ?? null, $menu['page_title'] ?? null, $menu['menu_title'] ?? null ) as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return trim( (string) $candidate );
			}
		}

		return $schema->id();
	}

	private static function schema_description( CompiledSchema $schema ): string {
		$definition = $schema->definition();
		$view       = is_array( $definition['view'] ?? null ) ? $definition['view'] : array();

		foreach ( array( $definition['description'] ?? null, $view['description'] ?? null ) as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return trim( (string) $candidate );
			}
		}

		return '';
	}

	private static function surface_for_container( string $type ): string {
		switch ( $type ) {
			case 'network_options_page':
				return 'network-admin';

			case 'metabox':
				return 'block-editor';

			case 'taxonomy':
			case 'profile':
			case 'comment':
			case 'options_page':
			default:
				return 'admin';
		}
	}

	private static function context_kind_for_container( string $type ): string {
		switch ( $type ) {
			case 'metabox':
				return 'post';

			case 'taxonomy':
				return 'term';

			case 'profile':
				return 'user';

			case 'comment':
				return 'comment';

			case 'network_options_page':
				return 'network';

			case 'options_page':
			default:
				return 'site';
		}
	}

	private static function scope_for_store( string $type ): string {
		switch ( $type ) {
			case 'site_option':
				return 'network';

			case 'post_meta':
			case 'term_meta':
			case 'user_meta':
			case 'comment_meta':
				return 'object';

			case 'array':
				return 'memory';

			case 'option':
			default:
				return 'site';
		}
	}

	/**
	 * @return array{self: string, values: string}
	 */
	private static function links( CompiledSchema $schema ): array {
		$base = 'lerm-admin-config/v1/schemas/' . rawurlencode( $schema->id() );

		return array(
			'self'   => rest_url( $base ),
			'values' => rest_url( $base . '/values' ),
		);
	}

	/**
	 * @param array<string|int, mixed> $payload
	 * @return array<string|int, mixed>
	 */
	private static function without_server_only_keys( array $payload ): array {
		$clean = array();

		foreach ( $payload as $key => $value ) {
			if ( 'capability' === $key ) {
				continue;
			}

			$clean[ $key ] = is_array( $value ) ? self::without_server_only_keys( $value ) : $value;
		}

		return $clean;
	}
}
