<?php
/**
 * REST controller for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest\Controllers;

use Lerm\AdminConfig\Client\SchemaSerializer;
use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Rest\Support\ContextResolver;
use Lerm\AdminConfig\Rest\Support\RequestPayload;
use Lerm\AdminConfig\Rest\Support\ResponseFactory;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Stores\MissingStoreContextException;
use Lerm\AdminConfig\WordPress\Runtime;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaController {

	public function __construct(
		private Runtime $runtime
	) {
	}

	public function schema( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;
		$values             = $store->all();

		return rest_ensure_response(
			array(
				'schema' => SchemaSerializer::legacy_client_config( $schema ),
				'values' => $values,
			)
		);
	}

	public function schema_document( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		return ResponseFactory::success(
			SchemaSerializer::document( $schema, $this->schema_actions( $schema, $request ) )
		);
	}

	public function schemas( \WP_REST_Request $request ): \WP_REST_Response {
		return ResponseFactory::success(
			array(
				'schemas' => $this->schema_summaries( $request ),
			)
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function schema_summaries( \WP_REST_Request $request ): array {
		$summaries = array();
		$context   = ContextResolver::from_request( $request );

		foreach ( $this->runtime->schemas() as $schema ) {
			if ( ! $this->runtime->current_user_can_schema( $schema, $context ) ) {
				continue;
			}

			$summaries[] = SchemaSerializer::summary(
				$schema,
				$this->actions_from_allowed( true )
			);
		}

		return $summaries;
	}

	public function values( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;
		$values             = $store->all();

		return ResponseFactory::success(
			SchemaSerializer::values( $schema, $values, $this->schema_actions( $schema, $request ) )
		);
	}

	public function save( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;

		$submitted = RequestPayload::values( $request, $store->storage_key() );

		if ( ! $store->import_all( $submitted ) ) {
			if ( $store->has_validation_errors() ) {
				$target = $this->first_validation_target( $schema, $store->validation_errors() );

				return ResponseFactory::error(
					'validation_error',
					esc_html__( 'Please review the highlighted fields and try again.', 'lerm-admin-config' ),
					422,
					array(
						'fieldErrors' => ValidationFlash::collapse_errors( $store->validation_errors() ),
						'errors'      => $store->validation_errors(),
						'tab'         => $target['tab'],
						'subsection'  => $target['subsection'],
						'target'      => $this->target_payload( $target ),
					)
				);
			}

			return ResponseFactory::error(
				'save_failed',
				esc_html__( 'Unable to save these settings right now.', 'lerm-admin-config' ),
				500
			);
		}

		return ResponseFactory::success(
			array_merge(
				array(
					'message' => esc_html__( 'Settings saved.', 'lerm-admin-config' ),
				),
				SchemaSerializer::values( $schema, $store->all(), $this->schema_actions( $schema, $request ) )
			)
		);
	}

	public function reset( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;

		$section    = $this->posted_section( $request, $schema );
		$subsection = RequestPayload::string( $request, 'subsection', RequestPayload::string( $request, 'lerm_settings_subsection' ) );
		$scope      = sanitize_key( RequestPayload::string( $request, 'reset_scope', RequestPayload::string( $request, 'scope', 'section' ) ) );

		if ( 'fetch_only' === $scope ) {
			return ResponseFactory::success(
				SchemaSerializer::values( $schema, $store->section_values( $section ), $this->schema_actions( $schema, $request ) )
			);
		}

		if ( 'all' === $scope ) {
			$success = $store->reset_all_sections();
			$values  = $store->all();
			$message = esc_html__( 'All sections have been reset to defaults.', 'lerm-admin-config' );
			$scope   = 'all';
		} elseif ( '' !== $subsection && $store->has_section_group( $section, $subsection ) ) {
			$success = $store->reset_section_group( $section, $subsection );
			$values  = $store->section_group_values( $section, $subsection );
			$message = esc_html__( 'The current page has been reset to defaults.', 'lerm-admin-config' );
			$scope   = 'subsection';
		} else {
			$success = $store->reset_section( $section );
			$values  = $store->section_values( $section );
			$message = esc_html__( 'This section has been reset to defaults.', 'lerm-admin-config' );
			$scope   = 'section';
		}

		if ( ! $success ) {
			return ResponseFactory::error(
				'reset_failed',
				esc_html__( 'Unable to reset the requested settings.', 'lerm-admin-config' ),
				500
			);
		}

		return ResponseFactory::success(
			array_merge(
				array(
					'message' => $message,
					'scope'   => $scope,
				),
				SchemaSerializer::values( $schema, $values, $this->schema_actions( $schema, $request ) )
			)
		);
	}

	public function export( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;
		$values             = $store->all();

		$json = wp_json_encode( $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			return ResponseFactory::error(
				'export_failed',
				esc_html__( 'Unable to export the current settings snapshot.', 'lerm-admin-config' ),
				500
			);
		}

		return ResponseFactory::success(
			array(
				'message' => esc_html__( 'Current settings snapshot generated.', 'lerm-admin-config' ),
				'json'    => $json,
			)
		);
	}

	public function import( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$resolved = $this->resolve_store( $request );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		[ $schema, $store ] = $resolved;

		$raw_body = $request->get_body();

		if ( strlen( $raw_body ) > MB_IN_BYTES ) {
			return ResponseFactory::error(
				'import_payload_too_large',
				esc_html__( 'The import payload exceeds the 1 MB limit.', 'lerm-admin-config' ),
				413
			);
		}

		$json = RequestPayload::string( $request, 'backup_json', RequestPayload::string( $request, 'json' ) );

		if ( '' === $json ) {
			return ResponseFactory::error(
				'missing_import_payload',
				esc_html__( 'Paste a JSON snapshot before importing.', 'lerm-admin-config' ),
				400
			);
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return ResponseFactory::error(
				'invalid_import_json',
				esc_html__( 'The backup JSON is invalid.', 'lerm-admin-config' ),
				400
			);
		}

		if ( ! $store->import_all( $decoded ) ) {
			if ( $store->has_validation_errors() ) {
				$target = $this->first_validation_target( $schema, $store->validation_errors() );

				return ResponseFactory::error(
					'validation_error',
					esc_html__( 'Please review the highlighted fields before importing again.', 'lerm-admin-config' ),
					422,
					array(
						'fieldErrors' => ValidationFlash::collapse_errors( $store->validation_errors() ),
						'errors'      => $store->validation_errors(),
						'tab'         => $target['tab'],
						'subsection'  => $target['subsection'],
						'target'      => $this->target_payload( $target ),
					)
				);
			}

			return ResponseFactory::error(
				'import_failed',
				esc_html__( 'Unable to import the provided settings JSON.', 'lerm-admin-config' ),
				500
			);
		}

		return ResponseFactory::success(
			array_merge(
				array(
					'message' => esc_html__( 'Settings imported successfully.', 'lerm-admin-config' ),
				),
				SchemaSerializer::values( $schema, $store->all(), $this->schema_actions( $schema, $request ) )
			)
		);
	}

	public function data_source( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$field_id = sanitize_key( RequestPayload::string( $request, 'field_id' ) );
		$field    = PageSchema::field( $schema->definition(), $field_id );

		if ( ! is_array( $field ) ) {
			return ResponseFactory::error(
				'field_not_found',
				esc_html__( 'The requested field was not found.', 'lerm-admin-config' ),
				404
			);
		}

		$source_id = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );

		if ( '' === $source_id || ! $this->runtime->has_data_source( $source_id ) ) {
			return ResponseFactory::error(
				'data_source_not_found',
				esc_html__( 'The requested data source is not registered.', 'lerm-admin-config' ),
				404
			);
		}

		$args = array(
			'search'    => RequestPayload::string( $request, 'search' ),
			'page'      => max( 1, absint( $request->get_param( 'page' ) ) ),
			'per_page'  => Runtime::sanitize_data_source_per_page( $request->get_param( 'per_page' ) ),
			'selected'  => $this->selected_values( $request ),
			'context'   => ContextResolver::from_request( $request ),
			'field'     => $field,
			'schema'    => $schema->definition(),
			'schema_id' => $schema->id(),
		);

		try {
			return ResponseFactory::success(
				$this->runtime->normalize_data_source_response(
					$this->runtime->resolve_data_source( $source_id, $args )
				)
			);
		} catch ( \Throwable $e ) {
			return ResponseFactory::error(
				'data_source_error',
				esc_html__( 'An error occurred while fetching data from the source.', 'lerm-admin-config' ),
				500
			);
		}
	}

	public function can_access_schema( \WP_REST_Request $request ): bool|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		if ( ! $this->runtime->current_user_can_schema( $schema, ContextResolver::from_request( $request ) ) ) {
			return ResponseFactory::error(
				'forbidden',
				esc_html__( 'You do not have permission to manage this schema.', 'lerm-admin-config' ),
				403
			);
		}

		return true;
	}

	private function schema_from_request( \WP_REST_Request $request ): CompiledSchema|\WP_Error {
		$schema_id = sanitize_key( RequestPayload::string( $request, 'id' ) );

		if ( '' === $schema_id || ! $this->runtime->has( $schema_id ) ) {
			return ResponseFactory::error(
				'schema_not_found',
				esc_html__( 'The requested schema was not found.', 'lerm-admin-config' ),
				404
			);
		}

		return $this->runtime->compiled( $schema_id );
	}

	/**
	 * @return array{read: bool, edit: bool, reset: bool, export: bool, import: bool, dataSource: bool}
	 */
	private function schema_actions( CompiledSchema $schema, \WP_REST_Request $request ): array {
		return $this->actions_from_allowed(
			$this->runtime->current_user_can_schema( $schema, ContextResolver::from_request( $request ) )
		);
	}

	/**
	 * @return array{read: bool, edit: bool, reset: bool, export: bool, import: bool, dataSource: bool}
	 */
	private function actions_from_allowed( bool $allowed ): array {
		return array(
			'read'       => $allowed,
			'edit'       => $allowed,
			'reset'      => $allowed,
			'export'     => $allowed,
			'import'     => $allowed,
			'dataSource' => $allowed,
		);
	}

	/**
	 * @param array{tab: string, subsection: string} $target
	 * @return array{section: string, group: string}
	 */
	private function target_payload( array $target ): array {
		return array(
			'section' => $target['tab'],
			'group'   => $target['subsection'],
		);
	}

	private function posted_section( \WP_REST_Request $request, CompiledSchema $schema ): string {
		$sections = PageSchema::sections( $schema->definition() );
		$section  = sanitize_key( RequestPayload::string( $request, 'section', RequestPayload::string( $request, 'lerm_settings_tab' ) ) );

		if ( isset( $sections[ $section ] ) ) {
			return $section;
		}

		return (string) array_key_first( $sections );
	}

	/**
	 * @param array<string, array<int, string>> $errors
	 * @return array{tab: string, subsection: string}
	 */
	private function first_validation_target( CompiledSchema $schema, array $errors ): array {
		$targets = $this->validation_targets( $schema );

		foreach ( array_keys( $errors ) as $field_path ) {
			$field_id = sanitize_key( (string) strtok( (string) $field_path, '.' ) );

			if ( '' === $field_id ) {
				continue;
			}

			if ( isset( $targets[ $field_id ] ) ) {
				return $targets[ $field_id ];
			}
		}

		return array(
			'tab'        => (string) array_key_first( PageSchema::sections( $schema->definition() ) ),
			'subsection' => '',
		);
	}

	/**
	 * @return array<string, array{tab: string, subsection: string}>
	 */
	private function validation_targets( CompiledSchema $schema ): array {
		$targets = array();

		foreach ( PageSchema::sections( $schema->definition() ) as $section_id => $section ) {
			foreach ( PageSchema::section_groups( $section ) as $group ) {
				foreach ( (array) ( $group['fields'] ?? array() ) as $field ) {
					if ( ! is_array( $field ) ) {
						continue;
					}

					$field_id = sanitize_key( (string) ( $field['id'] ?? '' ) );

					if ( '' === $field_id || isset( $targets[ $field_id ] ) ) {
						continue;
					}

					$targets[ $field_id ] = array(
						'tab'        => (string) $section_id,
						'subsection' => sanitize_key( (string) ( $group['id'] ?? '' ) ),
					);
				}
			}
		}

		return $targets;
	}

	/**
	 * @return array<int, string>
	 */
	private function selected_values( \WP_REST_Request $request ): array {
		$raw = $request->get_param( 'selected' );

		if ( null === $raw ) {
			$raw = $request->get_param( 'selected[]' );
		}

		$selected = array();

		foreach ( is_array( $raw ) ? $raw : array( $raw ) as $item ) {
			$item = is_scalar( $item ) ? trim( (string) $item ) : '';

			if ( '' !== $item ) {
				$selected[] = $item;
			}
		}

		return $selected;
	}

	/**
	 * @return array{0: CompiledSchema, 1: OptionStore}|\WP_Error
	 */
	private function resolve_store( \WP_REST_Request $request ): array|\WP_Error {
		$schema = $this->schema_from_request( $request );
		if ( is_wp_error( $schema ) ) {
			return $schema;
		}
		try {
			$store = $this->runtime->store(
				$schema->id(),
				ContextResolver::from_request( $request )
			);
		} catch ( MissingStoreContextException $e ) {
			return $this->missing_context_error( $e );
		}
		return array( $schema, $store );
	}

	private function missing_context_error( MissingStoreContextException $exception ): \WP_Error {
		return ResponseFactory::error(
			'missing_store_context',
			$exception->getMessage(),
			400
		);
	}
}
