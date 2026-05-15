<?php
/**
 * Options page submission state tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ExtendedPrimitiveFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class OptionsPageSubmissionStateTest extends TestCase {

	public function testMissingSubmissionRulesComeFromFieldTypeMetadata(): void {
		$definition = array(
			'id'       => 'unit_missing_submission_state',
			'store'    => array(
				'type' => 'option',
				'key'  => 'unit_missing_submission_state',
			),
			'sections' => array(
				'general' => array(
					'fields' => array(
						array(
							'id'       => 'channels',
							'type'     => 'select',
							'multiple' => true,
							'choices'  => array(
								'news' => 'News',
								'blog' => 'Blog',
							),
						),
						array(
							'id'      => 'audiences',
							'type'    => 'checkbox_list',
							'choices' => array(
								'members' => 'Members',
								'guests'  => 'Guests',
							),
						),
						array(
							'id'      => 'flags',
							'type'    => 'checkbox',
							'choices' => array(
								'beta' => 'Beta',
							),
						),
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
							'id'      => 'tone',
							'type'    => 'select',
							'choices' => array(
								'calm' => 'Calm',
								'bold' => 'Bold',
							),
						),
						array(
							'id'                       => 'slug',
							'type'                     => 'text',
							'missing_submission_value' => 'untitled',
						),
						array(
							'id'   => 'headline',
							'type' => 'text',
						),
					),
				),
			),
		);
		$page       = $this->options_page( $definition );
		$merged     = $this->merge_section_submitted_values(
			$page,
			'general',
			array(
				'channels'  => array( 'news' ),
				'audiences' => array( 'members' ),
				'flags'     => array( 'beta' ),
				'cards'     => array(
					array(
						'title' => 'Existing card',
					),
				),
				'tone'      => 'calm',
				'slug'      => 'existing-slug',
				'headline'  => 'Saved headline',
			),
			array(
				'headline' => 'Draft headline',
			)
		);

		$this->assertSame( array(), $merged['channels'] );
		$this->assertSame( array(), $merged['audiences'] );
		$this->assertSame( array(), $merged['flags'] );
		$this->assertSame( array(), $merged['cards'] );
		$this->assertSame( 'calm', $merged['tone'] );
		$this->assertSame( 'untitled', $merged['slug'] );
		$this->assertSame( 'Draft headline', $merged['headline'] );
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function options_page( array $definition ): OptionsPage {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), ExtendedPrimitiveFieldTypes::definitions(), StructuredFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		$store    = new OptionStore( $definition, $field_types );
		$resolver = new class() implements AssetResolver {
			public function url( string $filename ): string {
				return 'https://example.test/assets/' . ltrim( $filename, '/' );
			}

			public function version(): string {
				return 'unit-version';
			}
		};

		return new OptionsPage( $definition, $store, $field_types, $resolver, false );
	}

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $submitted
	 * @return array<string, mixed>
	 */
	private function merge_section_submitted_values( OptionsPage $page, string $section_id, array $values, array $submitted ): array {
		$method = new \ReflectionMethod( $page, 'merge_section_submitted_values' );
		$method->setAccessible( true );

		$result = $method->invoke( $page, $section_id, $values, $submitted );

		return is_array( $result ) ? $result : array();
	}
}
