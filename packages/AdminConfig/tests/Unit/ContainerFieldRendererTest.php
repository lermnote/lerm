<?php
/**
 * Container field renderer tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\AdvancedFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\BuiltinFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class ContainerFieldRendererTest extends TestCase {

	public function testFieldsetRendererPreservesNestedPathsAndErrors(): void {
		$page  = $this->options_page();
		$field = array(
			'id'            => 'layout',
			'type'          => 'fieldset',
			'label'         => 'Layout',
			'wrapper_class' => 'wide-panel',
			'fields'        => array(
				array(
					'id'          => 'headline',
					'type'        => 'text',
					'label'       => 'Headline',
					'description' => 'Shown above the content.',
				),
			),
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'layout' => array(
					'headline' => 'Draft headline',
				),
			),
			array(
				'layout.headline' => array( 'Headline is required.' ),
			)
		);

		$this->assertStringContainsString( 'class="lerm-fieldset wide-panel is-invalid"', $output );
		$this->assertStringContainsString( 'data-field-path="layout"', $output );
		$this->assertStringContainsString( 'name="options_framework[layout][headline]" value="Draft headline"', $output );
		$this->assertStringContainsString( 'Shown above the content.', $output );
		$this->assertStringContainsString( 'Headline is required.', $output );
	}

	public function testGroupRendererPreservesRepeatableItemMarkupAndTemplates(): void {
		$page  = $this->options_page();
		$field = array(
			'id'          => 'cards',
			'type'        => 'group',
			'label'       => 'Cards',
			'button_text' => 'Add card',
			'fields'      => array(
				array(
					'id'    => 'title',
					'type'  => 'text',
					'label' => 'Title',
				),
			),
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'cards' => array(
					array(
						'title' => 'First card',
					),
				),
			)
		);

		$this->assertStringContainsString( 'class="lerm-group"', $output );
		$this->assertStringContainsString( 'data-field-path="cards"', $output );
		$this->assertStringContainsString( 'data-lerm-group-add>Add card</button>', $output );
		$this->assertStringContainsString( 'name="options_framework[cards][0][title]" value="First card"', $output );
		$this->assertStringContainsString( 'data-index="__INDEX__"', $output );
		$this->assertStringContainsString( 'data-field-path-template="cards.__INDEX__.title"', $output );
	}

	public function testAccordionRendererOpensInvalidPanel(): void {
		$page  = $this->options_page();
		$field = $this->panel_field( 'accordion' );

		$output = $this->render_field(
			$page,
			$field,
			array(
				'panels' => array(
					'seo' => array(
						'title' => '',
					),
				),
			),
			array(
				'panels.seo.title' => array( 'Title is required.' ),
			)
		);

		$this->assertStringContainsString( 'class="lerm-fieldset lerm-accordion-field is-invalid"', $output );
		$this->assertStringContainsString( 'data-item-id="seo"', $output );
		$this->assertStringContainsString( 'aria-controls="panels__seo"', $output );
		$this->assertStringContainsString( 'class="lerm-accordion__item is-invalid is-open"', $output );
		$this->assertStringContainsString( 'Title is required.', $output );
	}

	public function testTabbedRendererActivatesInvalidPanel(): void {
		$page  = $this->options_page();
		$field = $this->panel_field( 'tabbed' );

		$output = $this->render_field(
			$page,
			$field,
			array(
				'panels' => array(
					'seo' => array(
						'title' => '',
					),
				),
			),
			array(
				'panels.seo.title' => array( 'Title is required.' ),
			)
		);

		$this->assertStringContainsString( 'class="lerm-fieldset lerm-tabbed-field is-invalid"', $output );
		$this->assertStringContainsString( 'data-default-tab="seo"', $output );
		$this->assertStringContainsString( 'class="lerm-tabbed__trigger is-active is-invalid"', $output );
		$this->assertStringContainsString( 'id="panels__seo"', $output );
		$this->assertStringContainsString( 'Title is required.', $output );
	}

	private function options_page(): OptionsPage {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( BuiltinFieldTypes::definitions(), StructuredFieldTypes::definitions(), AdvancedFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		$definition = array(
			'id'    => 'unit_container_field_renderer',
			'store' => array(
				'type' => 'option',
				'key'  => 'unit_container_field_renderer',
			),
		);
		$store      = new OptionStore( $definition, $field_types );
		$resolver   = new class() implements AssetResolver {
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
	 * @return array<string, mixed>
	 */
	private function panel_field( string $type ): array {
		return array(
			'id'    => 'panels',
			'type'  => $type,
			'label' => 'Panels',
			'items' => array(
				array(
					'id'     => 'general',
					'title'  => 'General',
					'fields' => array(
						array(
							'id'    => 'summary',
							'type'  => 'text',
							'label' => 'Summary',
						),
					),
				),
				array(
					'id'     => 'seo',
					'title'  => 'SEO',
					'fields' => array(
						array(
							'id'    => 'title',
							'type'  => 'text',
							'label' => 'Title',
						),
					),
				),
			),
		);
	}

	/**
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $field_errors
	 */
	private function render_field( OptionsPage $page, array $field, array $values, array $field_errors = array() ): string {
		ob_start();

		try {
			$page->render_field( $field, $values, 'general', 'table', $field_errors );

			return (string) ob_get_clean();
		} catch ( \Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}
}
