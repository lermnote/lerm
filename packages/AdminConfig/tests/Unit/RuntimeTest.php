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

	protected function tearDown(): void {
		Runtime::reset_instance();
		parent::tearDown();
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
}
