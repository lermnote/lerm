<?php
/**
 * REST endpoint tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Rest\Controllers\SchemaController;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Runtime;

final class RestEndpointsTest extends TestCase {

	protected function tearDown(): void {
		Runtime::reset_instance();
		parent::tearDown();
	}

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
}
