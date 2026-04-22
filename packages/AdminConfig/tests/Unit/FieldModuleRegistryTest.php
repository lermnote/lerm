<?php
/**
 * Field module registry tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Modules\AdvancedFieldsModule;
use Lerm\AdminConfig\Modules\CoreFieldsModule;
use Lerm\AdminConfig\Modules\StructuredFieldsModule;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class FieldModuleRegistryTest extends TestCase {

	public function testResolvesModulesForNestedDefinitionFieldTypes(): void {
		$field_types = new FieldTypeRegistry();
		$registry    = new FieldModuleRegistry( $field_types );

		$registry->register( new CoreFieldsModule() );
		$registry->register( new AdvancedFieldsModule() );
		$registry->register( new StructuredFieldsModule() );

		$schema = array(
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'     => 'hero_panels',
							'type'   => 'accordion',
							'items'  => array(),
							'fields' => array(
								array(
									'id'     => 'cards',
									'type'   => 'group',
									'fields' => array(
										array(
											'id'   => 'title',
											'type' => 'text',
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$this->assertSame(
			array( 'accordion', 'group', 'text' ),
			$registry->field_types_for_definition( $schema )
		);
		$this->assertSame(
			array( 'advanced', 'structured', 'core' ),
			$registry->modules_for_definition( $schema )
		);
	}

	public function testCanEnableKnownFieldTypesExplicitly(): void {
		$field_types = new FieldTypeRegistry();
		$registry    = new FieldModuleRegistry( $field_types );

		$registry->register( new CoreFieldsModule() );
		$registry->register( new AdvancedFieldsModule() );

		$this->assertFalse( $registry->is_enabled( 'advanced' ) );
		$registry->enable_for_field_types( array( 'typography', 'icon' ) );
		$this->assertTrue( $registry->is_enabled( 'advanced' ) );
		$this->assertTrue( $field_types->has( 'typography' ) );
	}
}
