<?php
/**
 * Schema registry tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use InvalidArgumentException;
use Lerm\AdminConfig\Registry\SchemaRegistry;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class SchemaRegistryTest extends TestCase {

	public function testDuplicateSchemaIdsKeepTheFirstRegistrationAndEmitDebugNotice(): void {
		$registry = new SchemaRegistry();
		$first    = $registry->register(
			array(
				'id'        => 'demo_schema',
				'container' => array(
					'type' => 'options_page',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'demo_schema',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'title',
								'type'    => 'text',
								'default' => 'First',
							),
						),
					),
				),
			)
		);
		$second   = $registry->register(
			array(
				'id'        => 'demo_schema',
				'container' => array(
					'type' => 'options_page',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'demo_schema_overwrite',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'title',
								'type'    => 'text',
								'default' => 'Second',
							),
						),
					),
				),
			)
		);

		$this->assertSame( $first, $second );
		$this->assertSame( 'demo_schema', $registry->get( 'demo_schema' )->id() );
		$this->assertCount( 1, $GLOBALS['lerm_admin_config_doing_it_wrong'] ?? array() );
		$this->assertStringContains(
			'already registered',
			(string) ( $GLOBALS['lerm_admin_config_doing_it_wrong'][0]['message'] ?? '' )
		);
	}

	public function testGetThrowsForUnknownSchema(): void {
		$registry = new SchemaRegistry();

		$this->assertThrows(
			InvalidArgumentException::class,
			static function () use ( $registry ): void {
				$registry->get( 'missing_schema' );
			}
		);
	}
}
