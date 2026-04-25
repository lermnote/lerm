<?php
/**
 * REST controller for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Rest\Controllers;

use Lerm\AdminConfig\Client\SchemaClientConfig;
use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Rest\Support\ContextResolver;
use Lerm\AdminConfig\Rest\Support\RequestPayload;
use Lerm\AdminConfig\Rest\Support\ResponseFactory;
use Lerm\AdminConfig\Stores\MissingStoreContextException;
use Lerm\AdminConfig\WordPress\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaController {

	public function __construct(
		private Runtime $runtime
	) {
	}

	public function schema( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$context = ContextResolver::from_request( $request );

		return rest_ensure_response(
			array(
				'schema' => SchemaClientConfig::from_compiled( $schema ),
				'values' => $this->runtime->all( $schema->id(), $context ),
			)
		);
	}

	public function values( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$context = ContextResolver::from_request( $request );

		return ResponseFactory::success(
			array(
				'values' => $this->runtime->all( $schema->id(), $context ),
			)
		);
	}

	public function save( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		try {
			$store = $this->runtime->store( $schema->id(), ContextResolver::from_request( $request ) );
		} catch ( MissingStoreContextException $exception ) {
			return $this->missing_context_error( $exception );
		}

		$submitted = RequestPayload::values( $request, $store->storage_key() );

		if ( ! $store->save_all( $submitted ) ) {
			if ( $store->has_validation_errors() ) {
				$target = $this->first_validation_target( $schema, $store->validation_errors() );

				return ResponseFactory::error(
					'validation_error',
					esc_html__( 'Please review the highlighted fields and try again.', 'lerm' ),
					422,
					array(
						'fieldErrors' => $this->collapse_field_errors( $store->validation_errors() ),
						'errors'      => $store->validation_errors(),
						'tab'         => $target['tab'],
						'subsection'  => $target['subsection'],
					)
				);
			}

			return ResponseFactory::error(
				'save_failed',
				esc_html__( 'Unable to save these settings right now.', 'lerm' ),
				500
			);
		}

		return ResponseFactory::success(
			array(
				'message' => esc_html__( 'Settings saved.', 'lerm' ),
				'values'  => $store->all(),
			)
		);
	}

	public function reset( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		try {
			$store = $this->runtime->store( $schema->id(), ContextResolver::from_request( $request ) );
		} catch ( MissingStoreContextException $exception ) {
			return $this->missing_context_error( $exception );
		}

		$section    = $this->posted_section( $request, $schema );
		$subsection = RequestPayload::string( $request, 'subsection', RequestPayload::string( $request, 'lerm_settings_subsection' ) );
		$scope      = sanitize_key( RequestPayload::string( $request, 'reset_scope', RequestPayload::string( $request, 'scope', 'section' ) ) );

		if ( 'fetch_only' === $scope ) {
			return ResponseFactory::success(
				array(
					'values' => $store->section_values( $section ),
				)
			);
		}

		if ( 'all' === $scope ) {
			$success = $store->reset_all_sections();
			$values  = $store->all();
			$message = esc_html__( 'All sections have been reset to defaults.', 'lerm' );
			$scope   = 'all';
		} elseif ( '' !== $subsection && $store->has_section_group( $section, $subsection ) ) {
			$success = $store->reset_section_group( $section, $subsection );
			$values  = $store->section_group_values( $section, $subsection );
			$message = esc_html__( 'The current page has been reset to defaults.', 'lerm' );
			$scope   = 'subsection';
		} else {
			$success = $store->reset_section( $section );
			$values  = $store->section_values( $section );
			$message = esc_html__( 'This section has been reset to defaults.', 'lerm' );
			$scope   = 'section';
		}

		if ( ! $success ) {
			return ResponseFactory::error(
				'reset_failed',
				esc_html__( 'Unable to reset the requested settings.', 'lerm' ),
				500
			);
		}

		return ResponseFactory::success(
			array(
				'message' => $message,
				'scope'   => $scope,
				'values'  => $values,
			)
		);
	}

	public function export( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		try {
			$values = $this->runtime->store( $schema->id(), ContextResolver::from_request( $request ) )->all();
		} catch ( MissingStoreContextException $exception ) {
			return $this->missing_context_error( $exception );
		}

		$json = wp_json_encode( $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			return ResponseFactory::error(
				'export_failed',
				esc_html__( 'Unable to export the current settings snapshot.', 'lerm' ),
				500
			);
		}

		return ResponseFactory::success(
			array(
				'message' => esc_html__( 'Current settings snapshot generated.', 'lerm' ),
				'json'    => $json,
			)
		);
	}

	public function import( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		try {
			$store = $this->runtime->store( $schema->id(), ContextResolver::from_request( $request ) );
		} catch ( MissingStoreContextException $exception ) {
			return $this->missing_context_error( $exception );
		}

		$json = RequestPayload::string( $request, 'backup_json', RequestPayload::string( $request, 'json' ) );

		if ( '' === $json ) {
			return ResponseFactory::error(
				'missing_import_payload',
				esc_html__( 'Paste a JSON snapshot before importing.', 'lerm' ),
				400
			);
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return ResponseFactory::error(
				'invalid_import_json',
				esc_html__( 'The backup JSON is invalid.', 'lerm' ),
				400
			);
		}

		if ( ! $store->import_all( $decoded ) ) {
			if ( $store->has_validation_errors() ) {
				$target = $this->first_validation_target( $schema, $store->validation_errors() );

				return ResponseFactory::error(
					'validation_error',
					esc_html__( 'Please review the highlighted fields before importing again.', 'lerm' ),
					422,
					array(
						'fieldErrors' => $this->collapse_field_errors( $store->validation_errors() ),
						'errors'      => $store->validation_errors(),
						'tab'         => $target['tab'],
						'subsection'  => $target['subsection'],
					)
				);
			}

			return ResponseFactory::error(
				'import_failed',
				esc_html__( 'Unable to import the provided settings JSON.', 'lerm' ),
				500
			);
		}

		return ResponseFactory::success(
			array(
				'message' => esc_html__( 'Settings imported successfully.', 'lerm' ),
				'values'  => $store->all(),
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
				esc_html__( 'The requested field was not found.', 'lerm' ),
				404
			);
		}

		$source_id = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );

		if ( '' === $source_id || ! $this->runtime->has_data_source( $source_id ) ) {
			return ResponseFactory::error(
				'data_source_not_found',
				esc_html__( 'The requested data source is not registered.', 'lerm' ),
				404
			);
		}

		$args = array(
			'search'    => RequestPayload::string( $request, 'search' ),
			'page'      => max( 1, absint( $request->get_param( 'page' ) ) ),
			'per_page'  => max( 1, absint( $request->get_param( 'per_page' ) ) ),
			'selected'  => $this->selected_values( $request ),
			'context'   => ContextResolver::from_request( $request ),
			'field'     => $field,
			'schema'    => $schema->definition(),
			'schema_id' => $schema->id(),
		);

		return ResponseFactory::success(
			$this->runtime->normalize_data_source_response(
				$this->runtime->resolve_data_source( $source_id, $args )
			)
		);
	}

	public function can_access_schema( \WP_REST_Request $request ): true|\WP_Error {
		$schema = $this->schema_from_request( $request );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		if ( ! $this->runtime->current_user_can_schema( $schema, ContextResolver::from_request( $request ) ) ) {
			return ResponseFactory::error(
				'forbidden',
				esc_html__( 'You do not have permission to manage this schema.', 'lerm' ),
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
				esc_html__( 'The requested schema was not found.', 'lerm' ),
				404
			);
		}

		return $this->runtime->compiled( $schema_id );
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
		foreach ( array_keys( $errors ) as $field_path ) {
			$field_id = sanitize_key( (string) strtok( (string) $field_path, '.' ) );

			if ( '' === $field_id ) {
				continue;
			}

			foreach ( PageSchema::sections( $schema->definition() ) as $section_id => $section ) {
				foreach ( PageSchema::section_groups( $section ) as $group ) {
					foreach ( (array) ( $group['fields'] ?? array() ) as $field ) {
						if ( is_array( $field ) && (string) ( $field['id'] ?? '' ) === $field_id ) {
							return array(
								'tab'        => (string) $section_id,
								'subsection' => sanitize_key( (string) ( $group['id'] ?? '' ) ),
							);
						}
					}
				}
			}
		}

		return array(
			'tab'        => (string) array_key_first( PageSchema::sections( $schema->definition() ) ),
			'subsection' => '',
		);
	}

	/**
	 * @param array<string, array<int, string>> $errors
	 * @return array<string, string>
	 */
	private function collapse_field_errors( array $errors ): array {
		$collapsed = array();

		foreach ( $errors as $path => $messages ) {
			if ( ! is_array( $messages ) || empty( $messages ) ) {
				continue;
			}

			$field_id = sanitize_key( (string) strtok( (string) $path, '.' ) );
			$message  = trim( implode( ' ', array_filter( array_map( 'strval', $messages ) ) ) );

			if ( '' !== $field_id && '' !== $message && ! isset( $collapsed[ $field_id ] ) ) {
				$collapsed[ $field_id ] = $message;
			}
		}

		return $collapsed;
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

	private function missing_context_error( MissingStoreContextException $exception ): \WP_Error {
		return ResponseFactory::error(
			'missing_store_context',
			$exception->getMessage(),
			400
		);
	}
}
