<?php
/**
 * Runtime diagnostics tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Stores\MissingStoreContextException;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Runtime;

final class RuntimeTest extends TestCase {

	public function testAjaxDataSourceFallbackChecksNonceBeforeSchemaLookup(): void {
		$runtime = new Runtime();
		unset( $runtime );

		$_REQUEST = array(
			'nonce' => 'valid',
		);

		$response = $this->dispatch_ajax_data_source();

		$this->assertSame(
			array(
				array(
					'action' => 'lerm_admin_config_data_source',
					'arg'    => 'nonce',
				),
			),
			$GLOBALS['lerm_admin_config_ajax_nonce_checks']
		);
		$this->assertFalse( $response['success'] );
		$this->assertSame( 404, $response['status'] );
	}

	public function testAjaxDataSourceFallbackDispatchesToRuntimeOwningRequestedSchema(): void {
		$empty_runtime = new Runtime();
		unset( $empty_runtime );

		$runtime = new Runtime();
		$runtime->register_data_source(
			'campaigns',
			static function ( array $args ): array {
				unset( $args );

				return array(
					'items' => array(
						array(
							'value' => 'alpha',
							'label' => 'Alpha',
						),
					),
					'more'  => false,
				);
			}
		);
		$runtime->register(
			array(
				'id'       => 'ajax_data_source_runtime',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'     => 'campaign',
								'type'   => 'ajax_select',
								'source' => 'campaigns',
							),
						),
					),
				),
			)
		);

		$_REQUEST = array(
			'nonce'     => 'valid',
			'schema_id' => 'ajax_data_source_runtime',
			'field_id'  => 'campaign',
		);

		$response = $this->dispatch_ajax_data_source();

		$this->assertTrue( $response['success'] );
		$this->assertSame(
			array(
				'items' => array(
					array(
						'value' => 'alpha',
						'label' => 'Alpha',
					),
				),
				'more'  => false,
			),
			$response['data']
		);
	}

	public function testAllFallsBackToDefaultsWhenMetaContextIsMissing(): void {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'entry_meta',
				'container' => array(
					'type' => 'metabox',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_entry_meta',
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

		$values = $runtime->all( 'entry_meta' );

		$this->assertSame(
			array(
				'badge_text' => 'Featured',
			),
			$values
		);
		$this->assertCount( 1, $GLOBALS['lerm_admin_config_doing_it_wrong'] ?? array() );
		$this->assertStringContains(
			'requires one of',
			(string) ( $GLOBALS['lerm_admin_config_doing_it_wrong'][0]['message'] ?? '' )
		);
	}

	public function testStoreStillThrowsWhenMetaContextIsMissing(): void {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'entry_meta',
				'container' => array(
					'type' => 'metabox',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_entry_meta',
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

		$this->assertThrows(
			MissingStoreContextException::class,
			static function () use ( $runtime ): void {
				$runtime->store( 'entry_meta' );
			}
		);
	}

	public function testBootReportsMissingContainerAdaptersInDebugMode(): void {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'missing_container_schema',
				'container' => array(
					'type' => 'custom_container',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'missing_container_schema',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'enabled',
								'type'    => 'switcher',
								'default' => 1,
							),
						),
					),
				),
			)
		);

		$runtime->boot();

		$this->assertCount( 1, $GLOBALS['lerm_admin_config_doing_it_wrong'] ?? array() );
		$this->assertStringContains(
			'custom_container',
			(string) ( $GLOBALS['lerm_admin_config_doing_it_wrong'][0]['message'] ?? '' )
		);
	}

	public function testBootReportsInvalidStoreConfigurationWithoutThrowing(): void {
		$runtime = new Runtime();
		$runtime->register(
			array(
				'id'        => 'invalid_store_schema',
				'container' => array(
					'type' => 'options_page',
				),
				'store'     => array(
					'type' => 'unsupported_store',
					'key'  => 'invalid_store_schema',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'enabled',
								'type'    => 'switcher',
								'default' => 1,
							),
						),
					),
				),
			)
		);

		$runtime->boot();

		$this->assertCount( 1, $GLOBALS['lerm_admin_config_doing_it_wrong'] ?? array() );
		$this->assertStringContains(
			'was not mounted',
			(string) ( $GLOBALS['lerm_admin_config_doing_it_wrong'][0]['message'] ?? '' )
		);
	}

	/**
	 * @return array{success: bool, data: mixed, status: int}
	 */
	private function dispatch_ajax_data_source(): array {
		try {
			do_action( 'wp_ajax_lerm_admin_config_data_source' );
		} catch ( \RuntimeException $exception ) {
			if ( 'wp_send_json' !== $exception->getMessage() ) {
				throw $exception;
			}
		}

		$response = $GLOBALS['lerm_admin_config_json_response'];

		$this->assertIsArray( $response );

		return $response;
	}
}
