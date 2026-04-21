<?php
/**
 * Page schema helper tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class PageSchemaTest extends TestCase {

	public function testSectionGroupsNormalizeExplicitGroups(): void {
		$section = array(
			'groups' => array(
				array(
					'id'     => 'branding',
					'label'  => 'Branding',
					'fields' => array(
						array(
							'id'   => 'logo_text',
							'type' => 'text',
						),
					),
				),
				'Spacing',
			),
		);

		$groups = PageSchema::section_groups( $section );

		$this->assertCount( 2, $groups );
		$this->assertSame( 'branding', $groups[0]['id'] );
		$this->assertSame( 'spacing', $groups[1]['id'] );
	}

	public function testChoicesSupportCallableBuilders(): void {
		$field = array(
			'id'      => 'tone',
			'type'    => 'select',
			'choices' => static function (): array {
				return array(
					'calm' => 'Calm',
					'bold' => 'Bold',
				);
			},
		);

		$this->assertSame(
			array(
				'calm' => 'Calm',
				'bold' => 'Bold',
			),
			PageSchema::choices( $field )
		);
	}

	public function testDefaultsFlattenAcrossSections(): void {
		$definition = array(
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'      => 'feature_enabled',
							'type'    => 'switcher',
							'default' => 1,
						),
					),
				),
				'design'  => array(
					'fields' => array(
						array(
							'id'      => 'accent_color',
							'type'    => 'color',
							'default' => '#2271b1',
						),
					),
				),
			),
		);

		$this->assertSame(
			array(
				'feature_enabled' => 1,
				'accent_color'    => '#2271b1',
			),
			PageSchema::defaults( $definition )
		);
	}
}
