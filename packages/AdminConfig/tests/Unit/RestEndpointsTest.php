<?php
/**
 * REST endpoint tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Rest\Controllers\SchemaController;
use Lerm\AdminConfig\Rest\RestEndpoints;
use Lerm\AdminConfig\Rest\Support\ContextResolver;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Runtime;

final class RestEndpointsTest extends TestCase {

	public function testRegistersSchemaRestRoutes(): void {
		$runtime = new Runtime();
		unset( $runtime );

		do_action( 'rest_api_init' );

		$routes = array_map(
			static fn( array $route ): string => $route['namespace'] . $route['route'],
			$GLOBALS['lerm_admin_config_rest_routes'] ?? array()
		);

		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/values', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/save', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/reset', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/import', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/export', $routes );
		$this->assertContains( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/data-source', $routes );
	}

	public function testSchemaEndpointReturnsClientConfigAndValues(): void {
		$runtime = $this->runtime_with_schema();

		$GLOBALS['lerm_admin_config_options']['rest_test_settings'] = array(
			'site_title' => 'Stored title',
		);

		$response = ( new SchemaController( $runtime ) )->schema( $this->request( array( 'id' => 'rest_test' ) ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->get_data();

		$this->assertSame( 'rest_test', $data['schema']['schemaId'] );
		$this->assertSame( 'Stored title', $data['values']['site_title'] );
	}

	public function testSchemaEndpointDoesNotExposeServerCapabilitiesToClient(): void {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'private_rest_test',
				'container' => array(
					'type'       => 'options_page',
					'capability' => 'manage_private_schema',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'private_rest_test_settings',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'         => 'secret_title',
								'type'       => 'text',
								'default'    => '',
								'capability' => 'manage_private_field',
							),
						),
					),
				),
			)
		);

		$response = ( new SchemaController( $runtime ) )->schema( $this->request( array( 'id' => 'private_rest_test' ) ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );

		$schema = $response->get_data()['schema'];

		$this->assertArrayNotHasKey( 'capability', $schema['container'] );
		$this->assertArrayNotHasKey( 'capability', $schema['fields']['secret_title'] );
	}

	public function testSaveEndpointPersistsJsonValues(): void {
		$runtime = $this->runtime_with_schema();

		$request = $this->request(
			array( 'id' => 'rest_test' ),
			array(
				'values' => array(
					'site_title' => ' Updated title ',
				),
			)
		);

		$response = ( new SchemaController( $runtime ) )->save( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Updated title', $response->get_data()['data']['values']['site_title'] );
		$this->assertSame(
			array(
				'site_title'    => 'Updated title',
				'items_per_row' => 4,
				'campaign'      => '',
			),
			$GLOBALS['lerm_admin_config_options']['rest_test_settings']
		);
	}

	public function testRegisteredRestRoutesDispatchToRuntimeOwningRequestedSchema(): void {
		$empty_runtime = new Runtime();
		unset( $empty_runtime );

		$runtime = $this->runtime_with_schema();

		do_action( 'rest_api_init' );

		$route   = $this->registered_route( 'lerm-admin-config/v1/schema/(?P<id>[a-z0-9_-]+)/save' );
		$request = $this->request(
			array( 'id' => 'rest_test' ),
			array(
				'values' => array(
					'site_title' => ' Routed title ',
				),
			)
		);

		self::assertTrue( call_user_func( $route['permission_callback'], $request ) );

		$response = call_user_func( $route['callback'], $request );

		unset( $runtime );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Routed title', $response->get_data()['data']['values']['site_title'] );
	}

	public function testRegisteredRuntimePoolDropsUnreferencedRuntimesBetweenTests(): void {
		$runtime = $this->runtime_with_schema();

		$this->assertSame( $runtime, RestEndpoints::runtime_for_schema( 'rest_test' ) );

		unset( $runtime );
		gc_collect_cycles();

		$this->assertNull( RestEndpoints::runtime_for_schema( 'rest_test' ) );
	}

	public function testRegisteredRestDispatchCoversResetImportExportAndDataSourceAcrossRuntimePool(): void {
		$empty_runtime = new Runtime();
		unset( $empty_runtime );

		$runtime = $this->runtime_with_schema();
		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ): array {
				unset( $args );

				return array(
					'items' => array(
						'alpha' => 'Alpha',
					),
					'more'  => false,
				);
			}
		);

		$GLOBALS['lerm_admin_config_options']['rest_test_settings'] = array(
			'site_title'    => 'Stored title',
			'items_per_row' => 2,
			'campaign'      => '',
		);

		$reset = RestEndpoints::reset(
			$this->request(
				array(
					'id'                => 'rest_test',
					'lerm_settings_tab' => 'general',
					'reset_scope'       => 'section',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $reset );
		$this->assertSame( 'Default title', $reset->get_data()['data']['values']['site_title'] );

		$import = RestEndpoints::import(
			$this->request(
				array(
					'id'          => 'rest_test',
					'backup_json' => '{"site_title":"Imported through route","items_per_row":5,"campaign":"alpha"}',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $import );
		$this->assertSame( 'Imported through route', $import->get_data()['data']['values']['site_title'] );

		$export = RestEndpoints::export( $this->request( array( 'id' => 'rest_test' ) ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $export );
		$this->assertStringContainsString( '"site_title": "Imported through route"', $export->get_data()['data']['json'] );

		$data_source = RestEndpoints::data_source(
			$this->request(
				array(
					'id'       => 'rest_test',
					'field_id' => 'campaign',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $data_source );
		$this->assertSame(
			array(
				array(
					'value' => 'alpha',
					'label' => 'Alpha',
				),
			),
			$data_source->get_data()['data']['items']
		);
	}

	public function testSaveValidationErrorsUseStableRestErrorShape(): void {
		$runtime = $this->runtime_with_schema();
		$runtime->register_validator(
			'text',
			static function ( array $field, $value ) {
				if ( 'site_title' === (string) ( $field['id'] ?? '' ) && strlen( (string) $value ) < 3 ) {
					return new \WP_Error( 'site_title_too_short', 'Site title is too short.' );
				}

				return $value;
			}
		);

		$response = ( new SchemaController( $runtime ) )->save(
			$this->request(
				array( 'id' => 'rest_test' ),
				array(
					'values' => array(
						'site_title' => 'No',
					),
				)
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'validation_error', $response->get_error_code() );

		$data = $response->get_error_data();

		$this->assertSame( 422, $data['status'] );
		$this->assertFalse( $data['success'] );
		$this->assertArrayNotHasKey( 'fieldErrors', $data );
		$this->assertSame( 'Site title is too short.', $data['data']['fieldErrors']['site_title'] );
		$this->assertSame( array( 'Site title is too short.' ), $data['data']['errors']['site_title'] );
		$this->assertSame( 'general', $data['data']['tab'] );
		$this->assertSame( 'general', $data['data']['subsection'] );
		$this->assertSame( 'Please review the highlighted fields and try again.', $data['data']['message'] );
	}

	public function testContextResolverUsesExplicitContextArrayBeforeTopLevelParams(): void {
		$request = $this->request(
			array(
				'context' => array(
					'post_id' => 123,
				),
				'post_id' => 456,
			)
		);

		$this->assertSame(
			array(
				'post_id' => 123,
			),
			ContextResolver::from_request( $request )
		);
	}

	public function testContextResolverOnlyReadsKnownTopLevelContextKeys(): void {
		$request = $this->request(
			array(
				'id'      => 'rest_test',
				'post_id' => 456,
				'values'  => array(
					'term_id' => 789,
				),
			)
		);

		$this->assertSame(
			array(
				'post_id' => 456,
			),
			ContextResolver::from_request( $request )
		);
	}

	public function testImportEndpointRejectsInvalidJsonWithStableErrorShape(): void {
		$response = ( new SchemaController( $this->runtime_with_schema() ) )->import(
			$this->request(
				array(
					'id'          => 'rest_test',
					'backup_json' => '{not-json',
				)
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'invalid_import_json', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFalse( $response->get_error_data()['success'] );
		$this->assertSame( 'The backup JSON is invalid.', $response->get_error_data()['data']['message'] );
	}

	public function testMissingMetaContextReturnsStableRestErrorShape(): void {
		$runtime = $this->runtime_with_meta_schema();

		$response = ( new SchemaController( $runtime ) )->save(
			$this->request(
				array( 'id' => 'rest_meta' ),
				array(
					'values' => array(
						'badge_text' => 'Launch',
					),
				)
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'missing_store_context', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertFalse( $response->get_error_data()['success'] );
		$this->assertStringContainsString( 'requires one of', $response->get_error_data()['data']['message'] );
	}

	public function testSchemaEndpointReturnsMissingContextForObjectBackedStore(): void {
		$response = ( new SchemaController( $this->runtime_with_meta_schema() ) )->schema(
			$this->request( array( 'id' => 'rest_meta' ) )
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'missing_store_context', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
	}

	public function testValuesEndpointReturnsMissingContextForObjectBackedStore(): void {
		$response = ( new SchemaController( $this->runtime_with_meta_schema() ) )->values(
			$this->request( array( 'id' => 'rest_meta' ) )
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'missing_store_context', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
	}

	public function testPermissionCallbackReturnsForbiddenRestErrorShape(): void {
		$GLOBALS['lerm_admin_config_current_user_can'] = array(
			'manage_options' => false,
		);

		$response = ( new SchemaController( $this->runtime_with_schema() ) )->can_access_schema(
			$this->request( array( 'id' => 'rest_test' ) )
		);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'forbidden', $response->get_error_code() );
		$this->assertSame( 403, $response->get_error_data()['status'] );
		$this->assertFalse( $response->get_error_data()['success'] );
		$this->assertSame( 'You do not have permission to manage this schema.', $response->get_error_data()['data']['message'] );
	}

	public function testResetEndpointResetsSectionValues(): void {
		$runtime = $this->runtime_with_schema();

		$GLOBALS['lerm_admin_config_options']['rest_test_settings'] = array(
			'site_title'    => 'Stored title',
			'items_per_row' => 2,
		);

		$response = ( new SchemaController( $runtime ) )->reset(
			$this->request(
				array(
					'id'                => 'rest_test',
					'lerm_settings_tab' => 'general',
					'reset_scope'       => 'section',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame(
			array(
				'site_title'    => 'Default title',
				'items_per_row' => 4,
				'campaign'      => '',
			),
			$response->get_data()['data']['values']
		);
		$this->assertSame(
			array(
				'site_title'    => 'Default title',
				'items_per_row' => 4,
				'campaign'      => '',
			),
			$GLOBALS['lerm_admin_config_options']['rest_test_settings']
		);
	}

	public function testImportAndExportUseAjaxCompatibleResponseShape(): void {
		$runtime    = $this->runtime_with_schema();
		$controller = new SchemaController( $runtime );

		$import = $controller->import(
			$this->request(
				array(
					'id'          => 'rest_test',
					'backup_json' => '{"site_title":"Imported title","items_per_row":3}',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $import );
		$this->assertTrue( $import->get_data()['success'] );
		$this->assertSame( 'Imported title', $import->get_data()['data']['values']['site_title'] );

		$export = $controller->export( $this->request( array( 'id' => 'rest_test' ) ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $export );
		$this->assertTrue( $export->get_data()['success'] );
		$this->assertStringContainsString( '"site_title": "Imported title"', $export->get_data()['data']['json'] );
	}

	public function testDataSourceEndpointNormalizesItems(): void {
		$runtime = $this->runtime_with_schema();
		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ): array {
				return array(
					'items' => array(
						'alpha' => 'Alpha',
						array(
							'value' => 'beta',
							'label' => 'Beta',
						),
					),
					'more'  => true,
				);
			}
		);

		$response = ( new SchemaController( $runtime ) )->data_source(
			$this->request(
				array(
					'id'       => 'rest_test',
					'field_id' => 'campaign',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame(
			array(
				array(
					'value' => 'alpha',
					'label' => 'Alpha',
				),
				array(
					'value' => 'beta',
					'label' => 'Beta',
				),
			),
			$response->get_data()['data']['items']
		);
		$this->assertTrue( $response->get_data()['data']['more'] );
	}

	public function testDataSourceEndpointClampsPerPage(): void {
		$runtime   = $this->runtime_with_schema();
		$seen_args = array();

		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ) use ( &$seen_args ): array {
				$seen_args = $args;

				return array();
			}
		);

		$response = ( new SchemaController( $runtime ) )->data_source(
			$this->request(
				array(
					'id'       => 'rest_test',
					'field_id' => 'campaign',
					'per_page' => 9999,
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( Runtime::MAX_DATA_SOURCE_PER_PAGE, $seen_args['per_page'] );
	}

	public function testDataSourceEndpointUsesDefaultPerPage(): void {
		$runtime   = $this->runtime_with_schema();
		$seen_args = array();

		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ) use ( &$seen_args ): array {
				$seen_args = $args;

				return array();
			}
		);

		$response = ( new SchemaController( $runtime ) )->data_source(
			$this->request(
				array(
					'id'       => 'rest_test',
					'field_id' => 'campaign',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 20, $seen_args['per_page'] );
	}

	public function testLegacyAjaxDataSourceClampsPerPage(): void {
		$runtime   = $this->runtime_with_schema();
		$seen_args = array();

		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ) use ( &$seen_args ): array {
				$seen_args = $args;

				return array();
			}
		);

		$_REQUEST = array(
			'schema_id' => 'rest_test',
			'field_id'  => 'campaign',
			'per_page'  => '9999',
			'nonce'     => 'nonce',
		);

		$this->assertThrows(
			\RuntimeException::class,
			static function (): void {
				Runtime::handle_ajax_data_source();
			}
		);

		$this->assertSame( Runtime::MAX_DATA_SOURCE_PER_PAGE, $seen_args['per_page'] );
		$this->assertTrue( $GLOBALS['lerm_admin_config_json_response']['success'] );
	}

	public function testMissingSchemaReturnsRestError(): void {
		$response = ( new SchemaController( new Runtime() ) )->schema( $this->request( array( 'id' => 'missing' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'schema_not_found', $response->get_error_code() );
		$this->assertSame( 404, $response->get_error_data()['status'] );
	}

	private function runtime_with_schema(): Runtime {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'       => 'rest_test',
				'store'    => array(
					'type' => 'option',
					'key'  => 'rest_test_settings',
				),
				'menu'     => array(
					'capability' => 'manage_options',
				),
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'site_title',
								'type'    => 'text',
								'default' => 'Default title',
							),
							array(
								'id'      => 'items_per_row',
								'type'    => 'number',
								'default' => 4,
								'min'     => 1,
								'max'     => 6,
							),
							array(
								'id'      => 'campaign',
								'type'    => 'ajax_select',
								'source'  => 'campaigns',
								'default' => '',
							),
						),
					),
				),
			)
		);

		return $runtime;
	}

	private function runtime_with_meta_schema(): Runtime {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'rest_meta',
				'container' => array(
					'type' => 'metabox',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_rest_meta',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'badge_text',
								'type'    => 'text',
								'default' => 'Featured',
							),
						),
					),
				),
			)
		);

		return $runtime;
	}

	/**
	 * @param array<string, mixed> $params Request params.
	 * @param array<string, mixed> $json_params JSON body params.
	 */
	private function request( array $params, array $json_params = array() ): \WP_REST_Request {
		$request = new \WP_REST_Request();

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		if ( array() !== $json_params ) {
			if ( method_exists( $request, 'set_json_params' ) ) {
				call_user_func( array( $request, 'set_json_params' ), $json_params );
			} else {
				foreach ( $json_params as $key => $value ) {
					$request->set_param( $key, $value );
				}
			}
		}

		return $request;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function registered_route( string $route ): array {
		foreach ( $GLOBALS['lerm_admin_config_rest_routes'] ?? array() as $registered ) {
			if ( $route === $registered['namespace'] . $registered['route'] ) {
				return $registered['args'];
			}
		}

		self::fail( sprintf( 'REST route %s was not registered.', $route ) );
	}
}
