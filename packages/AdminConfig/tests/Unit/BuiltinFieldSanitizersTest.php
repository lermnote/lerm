<?php
/**
 * Built-in field sanitizer tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class BuiltinFieldSanitizersTest extends TestCase {

	public function testSelectSanitizerUsesFieldDefinitionForMultipleChoices(): void {
		$store = $this->store();
		$field = array(
			'id'       => 'columns',
			'type'     => 'select',
			'multiple' => true,
			'cast'     => 'int',
			'choices'  => array(
				'1' => 'One',
				'2' => 'Two',
				'3' => 'Three',
			),
			'default'  => array(),
		);

		$this->assertSame(
			array( 2, 3 ),
			$store->sanitize_field( $field, array( '2', '3', '9', '2', 'foo' ) )
		);
	}

	public function testNumberSanitizerUsesFieldDefinitionForClampAndFloatSupport(): void {
		$store = $this->store();

		$this->assertSame(
			4,
			$store->sanitize_field(
				array(
					'id'      => 'count',
					'type'    => 'number',
					'cast'    => 'int',
					'min'     => 1,
					'max'     => 4,
					'default' => 2,
				),
				'5.6'
			)
		);

		$this->assertSame(
			1.75,
			$store->sanitize_field(
				array(
					'id'      => 'ratio',
					'type'    => 'number',
					'cast'    => 'float',
					'min'     => 0.5,
					'max'     => 2.5,
					'default' => 1.0,
				),
				'1.75'
			)
		);
	}

	private function store(): OptionStore {
		$field_types = new FieldTypeRegistry();

		foreach ( BuiltinFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		return new OptionStore(
			array(
				'id'    => 'unit_builtin_field_sanitizers',
				'store' => array(
					'type' => 'option',
					'key'  => 'unit_builtin_field_sanitizers',
				),
			),
			$field_types
		);
	}
}
