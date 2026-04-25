<?php
/**
 * Schema compiler tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Compiler\SchemaCompiler;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class SchemaCompilerTest extends TestCase {

	public function testCompilesDefaultsDependenciesAndClientPayload(): void {
		$compiler = new SchemaCompiler();
		$compiled = $compiler->compile(
			array(
				'id'        => 'demo_settings',
				'container' => array(
					'type'       => 'options_page',
					'capability' => 'manage_options',
				),
				'store'     => array(
					'type' => 'option',
					'key'  => 'demo_settings',
				),
				'sections'  => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'feature_enabled',
								'type'    => 'switcher',
								'label'   => 'Enable feature',
								'default' => 1,
							),
							array(
								'id'          => 'accent_color',
								'type'        => 'color',
								'label'       => 'Accent color',
								'default'     => '#2271b1',
								'dependency'  => array( 'feature_enabled', '==', true ),
								'description' => 'Shown when the feature is enabled.',
							),
						),
					),
				),
			)
		);

		$this->assertSame( 'demo_settings', $compiled->id() );
		$this->assertSame( '#2271b1', $compiled->defaults()['accent_color'] );
		$this->assertArrayHasKey( 'accent_color', $compiled->dependency_graph() );
		$this->assertSame( 'feature_enabled', $compiled->dependency_graph()['accent_color']['field'] );
		$this->assertSame( 'demo_settings', $compiled->client_config()['optionName'] );
		$this->assertSame( 'option', $compiled->store()['type'] );
	}

	public function testIgnoresInvalidDependencyDeclarations(): void {
		$compiled = ( new SchemaCompiler() )->compile(
			array(
				'id'       => 'dependency_edges',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'         => 'no_dependency_array',
								'type'       => 'text',
								'dependency' => 'feature_enabled',
							),
							array(
								'id'         => 'empty_controller',
								'type'       => 'text',
								'dependency' => array( '', '==', true ),
							),
						),
					),
				),
			)
		);

		$this->assertSame( array(), $compiled->dependency_graph() );
		$this->assertArrayNotHasKey( 'dependency', $compiled->client_config()['fields']['no_dependency_array'] );
		$this->assertArrayNotHasKey( 'dependency', $compiled->client_config()['fields']['empty_controller'] );
	}

	public function testDependencyDefaultsEmptyOperatorToEquality(): void {
		$compiled = ( new SchemaCompiler() )->compile(
			array(
				'id'       => 'dependency_operator',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'feature_enabled',
								'type'    => 'switcher',
								'default' => false,
							),
							array(
								'id'         => 'dependent_field',
								'type'       => 'text',
								'dependency' => array( 'feature_enabled', '', true ),
							),
						),
					),
				),
			)
		);

		$this->assertSame(
			array(
				'field'    => 'feature_enabled',
				'operator' => '==',
				'value'    => true,
			),
			$compiled->dependency_graph()['dependent_field']
		);
	}

	public function testStoreKeyFallsBackToOptionNameThenSchemaId(): void {
		$compiler = new SchemaCompiler();

		$option_name_fallback = $compiler->compile(
			array(
				'id'          => 'fallback_schema',
				'option_name' => 'custom_options',
				'store'       => array(
					'type' => 'option',
					'key'  => '',
				),
			)
		);

		$schema_id_fallback = $compiler->compile(
			array(
				'id'          => 'fallback_schema',
				'option_name' => '',
				'store'       => array(
					'type' => 'option',
					'key'  => '',
				),
			)
		);

		$this->assertSame( 'custom_options', $option_name_fallback->store()['key'] );
		$this->assertSame( 'fallback_schema', $schema_id_fallback->store()['key'] );
	}
}
