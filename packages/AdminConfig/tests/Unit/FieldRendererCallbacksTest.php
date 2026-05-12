<?php
/**
 * Field renderer callback tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\FieldTypes\StructuredFieldTypes;
use Lerm\AdminConfig\Framework\FieldTypes\ToolFieldTypes;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Tests\Support\TestCase;

final class FieldRendererCallbacksTest extends TestCase {

	public function testStructuredNoticeRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'    => 'intro_notice',
			'type'  => 'notice',
			'label' => '',
			'html'  => '<p class="notice-copy"><strong>Heads up</strong></p>',
		);

		$output = $this->render_field( $page, $field, array() );

		$this->assertStringContainsString( 'lerm-settings-notice', $output );
		$this->assertStringContainsString( '<strong>Heads up</strong>', $output );
	}

	public function testToolBackupRendererRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'           => 'backup',
			'type'         => 'backup_tools',
			'label'        => 'Backup',
			'export_label' => 'Export config',
			'import_label' => 'Import config',
		);

		$output = $this->render_field( $page, $field, array() );

		$this->assertStringContainsString( 'Export config', $output );
		$this->assertStringContainsString( 'data-lerm-backup-export', $output );
		$this->assertStringContainsString( 'data-lerm-backup-import', $output );
	}

	public function testStructuredSorterRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'      => 'layout_sort',
			'type'    => 'sorter',
			'label'   => 'Layout sort',
			'choices' => array(
				'header'  => 'Header',
				'sidebar' => 'Sidebar',
				'footer'  => 'Footer',
			),
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'layout_sort' => array(
					'order'   => array( 'sidebar', 'header' ),
					'enabled' => array( 'header' ),
				),
			)
		);

		$this->assertStringContainsString( 'name="options_framework[layout_sort][order][]" value="sidebar"', $output );
		$this->assertStringContainsString( 'name="options_framework[layout_sort][enabled][]" value="header"  checked="checked"', $output );
		$this->assertStringContainsString( '<span>Footer</span>', $output );
	}

	public function testStructuredMediaRendererRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'          => 'hero_image',
			'type'        => 'media',
			'label'       => 'Hero image',
			'button_text' => 'Choose hero',
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'hero_image' => array(
					'id' => 42,
				),
			)
		);

		$this->assertStringContainsString( 'class="lerm-media-field"', $output );
		$this->assertStringContainsString( 'name="options_framework[hero_image][id]" value="42"', $output );
		$this->assertStringContainsString( 'src="https://example.test/uploads/medium/42.jpg"', $output );
		$this->assertStringContainsString( 'class="button lerm-media-select">Choose hero</button>', $output );
	}

	public function testStructuredGalleryRendererRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'    => 'gallery_items',
			'type'  => 'gallery',
			'label' => 'Gallery items',
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'gallery_items' => array(
					'ids' => '3,5',
				),
			)
		);

		$this->assertStringContainsString( 'class="lerm-gallery-field"', $output );
		$this->assertStringContainsString( 'name="options_framework[gallery_items][ids]" value="3,5"', $output );
		$this->assertStringContainsString( 'src="https://example.test/uploads/thumbnail/3.jpg"', $output );
		$this->assertStringContainsString( 'src="https://example.test/uploads/thumbnail/5.jpg"', $output );
	}

	public function testStructuredCodeEditorRendererRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'          => 'custom_css',
			'type'        => 'code_editor',
			'label'       => 'Custom CSS',
			'rows'        => 4,
			'placeholder' => '<style>',
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'custom_css' => '<script>alert(1)</script>',
			)
		);

		$this->assertStringContainsString( 'id="custom_css"', $output );
		$this->assertStringContainsString( 'name="options_framework[custom_css]"', $output );
		$this->assertStringContainsString( 'class="large-text lerm-code-editor"', $output );
		$this->assertStringContainsString( 'rows="4"', $output );
		$this->assertStringContainsString( 'placeholder="&lt;style&gt;"', $output );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $output );
	}

	public function testStructuredWpEditorRendererRendersFromFieldTypeCallback(): void {
		$page  = $this->options_page();
		$field = array(
			'id'    => 'body_copy',
			'type'  => 'wp_editor',
			'label' => 'Body copy',
		);

		$output = $this->render_field(
			$page,
			$field,
			array(
				'body_copy' => '<p>Hello</p>',
			)
		);

		$this->assertStringContainsString( 'id="lerm-body_copy"', $output );
		$this->assertStringContainsString( 'name="options_framework[body_copy]"', $output );
		$this->assertStringContainsString( 'rows="6"', $output );
		$this->assertStringContainsString( '&lt;p&gt;Hello&lt;/p&gt;', $output );
	}

	private function options_page(): OptionsPage {
		$field_types = new FieldTypeRegistry();

		foreach ( array_merge( StructuredFieldTypes::definitions(), ToolFieldTypes::definitions() ) as $type => $field_type_definition ) {
			$field_types->register( (string) $type, $field_type_definition );
		}

		$definition = array(
			'id'    => 'unit_field_renderer_callbacks',
			'store' => array(
				'type' => 'option',
				'key'  => 'unit_field_renderer_callbacks',
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
	 * @param array<string, mixed> $field
	 * @param array<string, mixed> $values
	 */
	private function render_field( OptionsPage $page, array $field, array $values ): string {
		ob_start();

		try {
			$page->render_field( $field, $values, 'general' );

			return (string) ob_get_clean();
		} catch ( \Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}
}
