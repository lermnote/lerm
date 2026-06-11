<?php
/**
 * Nested field sanitizer tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class NestedFieldSanitizersTest extends TestCase {

	public function testFieldsetValidationErrorsKeepNestedPaths(): void {
		$field_types = $this->field_types();
		$field_types->register_validator(
			'text',
			static function ( array $field, $value ) {
				unset( $field );

				if ( '' === (string) $value ) {
					return new \WP_Error( 'required', 'Value is required.' );
				}

				return $value;
			}
		);

		$store = new OptionStore(
			array(
				'id'       => 'nested_fieldset_validation',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'     => 'layout',
								'type'   => 'fieldset',
								'fields' => array(
									array(
										'id'   => 'headline',
										'type' => 'text',
									),
								),
							),
						),
					),
				),
			),
			$field_types
		);

		$this->assertFalse(
			$store->import_all(
				array(
					'layout' => array(
						'headline' => '',
					),
				)
			)
		);
		$this->assertSame(
			array(
				'layout.headline' => array( 'Value is required.' ),
			),
			$store->validation_errors()
		);
	}

	public function testGroupSanitizerDropsEmptyItems(): void {
		$store = new OptionStore(
			array(
				'id'    => 'nested_group_sanitize',
				'store' => array(
					'type' => 'option',
					'key'  => 'nested_group_sanitize',
				),
			),
			$this->field_types()
		);

		$this->assertSame(
			array(
				array(
					'title' => 'Primary card',
				),
			),
			$store->sanitize_field(
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
				array(
					array(
						'title' => '',
					),
					array(
						'title' => 'Primary card',
					),
				)
			)
		);
	}

	public function testAccordionValidationErrorsKeepPerPanelPaths(): void {
		$field_types = $this->field_types();
		$field_types->register_validator(
			'text',
			static function ( array $field, $value ) {
				unset( $field );

				if ( '' === (string) $value ) {
					return new \WP_Error( 'required', 'Value is required.' );
				}

				return $value;
			}
		);

		$store = new OptionStore(
			array(
				'id'       => 'nested_panel_validation',
				'sections' => array(
					'general' => array(
						'fields' => array(
							array(
								'id'    => 'launch_accordion',
								'type'  => 'accordion',
								'items' => array(
									array(
										'id'     => 'cta',
										'title'  => 'CTA',
										'fields' => array(
											array(
												'id'   => 'button_label',
												'type' => 'text',
											),
										),
									),
								),
							),
						),
					),
				),
			),
			$field_types
		);

		$this->assertFalse(
			$store->import_all(
				array(
					'launch_accordion' => array(
						'cta' => array(
							'button_label' => '',
						),
					),
				)
			)
		);
		$this->assertSame(
			array(
				'launch_accordion.cta.button_label' => array( 'Value is required.' ),
			),
			$store->validation_errors()
		);
	}

	private function field_types(): FieldTypeRegistry {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), StructuredFieldTypes::definitions(), AdvancedFieldTypes::definitions() ) as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		return $field_types;
	}
}
