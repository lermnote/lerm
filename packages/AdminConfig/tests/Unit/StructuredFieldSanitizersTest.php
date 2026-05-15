<?php
/**
 * Structured field sanitizer tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class StructuredFieldSanitizersTest extends TestCase {

	public function testMediaSanitizerReturnsAttachmentPayload(): void {
		$store = $this->store();
		$field = array(
			'id'   => 'hero_image',
			'type' => 'media',
		);

		$this->assertSame(
			array(
				'id'        => 42,
				'url'       => 'https://example.test/uploads/full/42.jpg',
				'thumbnail' => 'https://example.test/uploads/thumbnail/42.jpg',
			),
			$store->sanitize_field( $field, array( 'id' => 42 ) )
		);
	}

	public function testGallerySanitizerDeduplicatesAndFiltersIds(): void {
		$store = $this->store();
		$field = array(
			'id'   => 'gallery_items',
			'type' => 'gallery',
		);

		$this->assertSame(
			array( 3, 5, 8 ),
			$store->sanitize_field( $field, array( 'ids' => '3,5,3,0,foo,8' ) )
		);
	}

	public function testSorterSanitizerNormalizesLegacyPayloadIntoEnabledDisabledMaps(): void {
		$store = $this->store();
		$field = array(
			'id'      => 'layout_sort',
			'type'    => 'sorter',
			'choices' => array(
				'header'  => 'Header',
				'sidebar' => 'Sidebar',
				'footer'  => 'Footer',
			),
		);

		$this->assertSame(
			array(
				'enabled'  => array(
					'header' => 'Header',
				),
				'disabled' => array(
					'footer' => 'Footer',
				),
			),
			$store->sanitize_field(
				$field,
				array(
					'enabled'  => array(
						'header' => 'Header',
					),
					'disabled' => array(
						'footer' => 'Footer',
					),
				)
			)
		);
	}

	public function testCodeEditorSanitizerTrimsScalarValues(): void {
		$store = $this->store();
		$field = array(
			'id'   => 'custom_css',
			'type' => 'code_editor',
		);

		$this->assertSame( 'body { color: red; }', $store->sanitize_field( $field, " \nbody { color: red; } \t" ) );
		$this->assertSame( '', $store->sanitize_field( $field, array( 'not-scalar' ) ) );
	}

	public function testWpEditorSanitizerNormalizesScalarHtml(): void {
		$store = $this->store();
		$field = array(
			'id'   => 'body_copy',
			'type' => 'wp_editor',
		);

		$this->assertSame( '<p>Hello</p>', $store->sanitize_field( $field, '<p>Hello</p>' ) );
		$this->assertSame( '', $store->sanitize_field( $field, array( 'not-scalar' ) ) );
	}

	private function store(): OptionStore {
		$field_types = new FieldTypeRegistry();

		foreach ( StructuredFieldTypes::definitions() as $type => $definition ) {
			$field_types->register( (string) $type, $definition );
		}

		return new OptionStore(
			array(
				'id'    => 'unit_structured_field_sanitizers',
				'store' => array(
					'type' => 'option',
					'key'  => 'unit_structured_field_sanitizers',
				),
			),
			$field_types
		);
	}
}
