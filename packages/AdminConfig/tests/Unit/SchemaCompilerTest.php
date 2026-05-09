<?php
/**
 * Schema compiler tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Compiler\SchemaCompiler;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
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
						'title'       => 'General',
						'description' => 'General settings.',
						'fields'      => array(
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
							array(
								'id'          => 'layout',
								'type'        => 'select',
								'label'       => 'Layout',
								'choices'     => array(
									'compact' => 'Compact',
									'wide'    => 'Wide',
								),
								'default'     => 'compact',
								'placeholder' => 'Choose a layout',
							),
							array(
								'id'      => 'columns',
								'type'    => 'number',
								'label'   => 'Columns',
								'min'     => 1,
								'max'     => 4,
								'step'    => 1,
								'default' => 2,
							),
							array(
								'id'          => 'hero_image',
								'type'        => 'media',
								'label'       => 'Hero image',
								'library'     => 'image',
								'button_text' => 'Choose hero',
								'remove_text' => 'Remove hero',
								'default'     => array(),
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
		$this->assertSame( array( 'feature_enabled', 'accent_color', 'layout', 'columns', 'hero_image' ), $compiled->client_config()['sections']['general']['fields'] );
		$this->assertSame( 'General settings.', $compiled->client_config()['sections']['general']['description'] );
		$this->assertSame(
			array(
				'compact' => 'Compact',
				'wide'    => 'Wide',
			),
			$compiled->client_config()['fields']['layout']['choices']
		);
		$this->assertSame( 'Choose a layout', $compiled->client_config()['fields']['layout']['placeholder'] );
		$this->assertSame( 1, $compiled->client_config()['fields']['columns']['min'] );
		$this->assertSame( 4, $compiled->client_config()['fields']['columns']['max'] );
		$this->assertSame( 'image', $compiled->client_config()['fields']['hero_image']['library'] );
		$this->assertSame( 'Choose hero', $compiled->client_config()['fields']['hero_image']['button_text'] );
		$this->assertSame( 'Remove hero', $compiled->client_config()['fields']['hero_image']['remove_text'] );
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
								'id'   => 'no_dependency_key',
								'type' => 'text',
							),
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
		$this->assertArrayNotHasKey( 'dependency', $compiled->client_config()['fields']['no_dependency_key'] );
		$this->assertArrayNotHasKey( 'dependency', $compiled->client_config()['fields']['no_dependency_array'] );
		$this->assertArrayNotHasKey( 'dependency', $compiled->client_config()['fields']['empty_controller'] );
	}

	public function testCompilesNestedFieldMetadataForStructuredControls(): void {
		$compiled = ( new SchemaCompiler() )->compile(
			array(
				'id'       => 'structured_controls',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'      => 'badge',
								'type'    => 'fieldset',
								'fields'  => array(
									array(
										'id'      => 'label',
										'type'    => 'text',
										'default' => 'Featured',
									),
								),
								'default' => array(
									'label' => 'Featured',
								),
							),
							array(
								'id'      => 'links',
								'type'    => 'group',
								'fields'  => array(
									array(
										'id'   => 'url',
										'type' => 'url',
									),
								),
								'default' => array(),
							),
							array(
								'id'    => 'spacing',
								'type'  => 'spacing',
								'units' => array( 'px', 'rem' ),
								'top'   => true,
								'left'  => false,
							),
							array(
								'id'    => 'border',
								'type'  => 'border',
								'left'  => false,
								'style' => true,
								'color' => false,
							),
							array(
								'id'      => 'link_colors',
								'type'    => 'link_color',
								'active'  => true,
								'visited' => false,
							),
							array(
								'id'             => 'typography',
								'type'           => 'typography',
								'style'          => true,
								'letter_spacing' => true,
								'align'          => true,
								'units'          => array( 'px', 'rem' ),
							),
							array(
								'id'                    => 'background',
								'type'                  => 'background',
								'background_gradient'   => true,
								'background_origin'     => true,
								'background_blend_mode' => true,
								'background_image_button_text' => 'Choose background',
							),
							array(
								'id'      => 'palette',
								'type'    => 'palette',
								'choices' => array(
									'cool'    => array( '#0f172a', '#38bdf8' ),
									'invalid' => array( 'not-a-color' ),
								),
							),
							array(
								'id'      => 'image_style',
								'type'    => 'image_select',
								'choices' => array(
									'cover' => 'https://example.test/cover.png',
								),
							),
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'choices' => array(
									'dashicons-lightbulb' => 'Idea',
								),
							),
							array(
								'id'                => 'campaign',
								'type'              => 'ajax_select',
								'source'            => 'campaigns',
								'multiple'          => true,
								'allow_clear'       => false,
								'min_search_length' => 2,
								'per_page'          => 7,
								'search_label'      => 'Search campaigns',
							),
						),
					),
				),
			)
		);

		$fields = $compiled->client_config()['fields'];

		$this->assertSame( 'badge.label', $fields['badge']['fields'][0]['path'] );
		$this->assertSame( 'text', $fields['badge']['fields'][0]['client']['control'] ?? $fields['badge']['fields'][0]['type'] );
		$this->assertSame( 'links.*.url', $fields['links']['fields'][0]['path'] );
		$this->assertSame( array( 'px', 'rem' ), $fields['spacing']['units'] );
		$this->assertTrue( $fields['spacing']['top'] );
		$this->assertFalse( $fields['spacing']['left'] );
		$this->assertFalse( $fields['border']['left'] );
		$this->assertTrue( $fields['border']['style'] );
		$this->assertFalse( $fields['border']['color'] );
		$this->assertTrue( $fields['link_colors']['active'] );
		$this->assertFalse( $fields['link_colors']['visited'] );
		$this->assertTrue( $fields['typography']['style'] );
		$this->assertTrue( $fields['typography']['letter_spacing'] );
		$this->assertTrue( $fields['typography']['align'] );
		$this->assertSame( array( 'px', 'rem' ), $fields['typography']['units'] );
		$this->assertTrue( $fields['background']['background_gradient'] );
		$this->assertTrue( $fields['background']['background_origin'] );
		$this->assertTrue( $fields['background']['background_blend_mode'] );
		$this->assertSame( 'Choose background', $fields['background']['background_image_button_text'] );
		$this->assertSame( array( '#0f172a', '#38bdf8' ), $fields['palette']['choices']['cool'] );
		$this->assertArrayNotHasKey( 'invalid', $fields['palette']['choices'] );
		$this->assertSame( 'https://example.test/cover.png', $fields['image_style']['choices']['cover'] );
		$this->assertSame( 'Idea', $fields['icon']['choices']['dashicons-lightbulb'] );
		$this->assertSame( 'campaigns', $fields['campaign']['source'] );
		$this->assertTrue( $fields['campaign']['multiple'] );
		$this->assertFalse( $fields['campaign']['allow_clear'] );
		$this->assertSame( 2, $fields['campaign']['min_search_length'] );
		$this->assertSame( 7, $fields['campaign']['per_page'] );
		$this->assertSame( 'Search campaigns', $fields['campaign']['search_label'] );
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

	public function testMergesRegisteredFieldTypeClientMetadata(): void {
		$field_types = new FieldTypeRegistry();
		$field_types->register(
			'custom_text',
			array(
				'client' => array(
					'control'  => 'text',
					'settings' => array(
						'rows'       => 2,
						'spellcheck' => true,
					),
				),
			)
		);

		$compiled = ( new SchemaCompiler( $field_types ) )->compile(
			array(
				'id'       => 'field_type_client',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'   => 'registered_control',
								'type' => 'custom_text',
							),
							array(
								'id'     => 'field_override',
								'type'   => 'custom_text',
								'client' => array(
									'control'  => 'textarea',
									'settings' => array(
										'rows' => 4,
									),
								),
							),
						),
					),
				),
			)
		);

		$fields = $compiled->client_config()['fields'];

		$this->assertSame( 'text', $fields['registered_control']['client']['control'] );
		$this->assertSame( 2, $fields['registered_control']['client']['settings']['rows'] );
		$this->assertTrue( $fields['registered_control']['client']['settings']['spellcheck'] );
		$this->assertSame( 'textarea', $fields['field_override']['client']['control'] );
		$this->assertSame( 4, $fields['field_override']['client']['settings']['rows'] );
		$this->assertTrue( $fields['field_override']['client']['settings']['spellcheck'] );
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

	public function testStoreDefaultsInvalidTypeAndPreservesContextualProperties(): void {
		$compiled = ( new SchemaCompiler() )->compile(
			array(
				'id'    => 'context_store',
				'store' => array(
					'type'       => '',
					'key'        => array( 'not-scalar' ),
					'object_id'  => 123,
					'network_id' => 7,
					'autoload'   => false,
				),
			)
		);

		$this->assertSame(
			array(
				'type'       => 'option',
				'key'        => 'context_store',
				'object_id'  => 123,
				'network_id' => 7,
				'autoload'   => false,
			),
			$compiled->store()
		);
	}
}
