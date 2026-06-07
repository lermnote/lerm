<?php
/**
 * Unit tests for the HasBlockEditorPanel trait.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Compiler\SchemaCompiler;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Containers\BlockEditorPanelContainer;
use Lerm\AdminConfig\WordPress\Runtime;

final class HasBlockEditorPanelTest extends TestCase {

	public function testTraitProvidesEnqueueBlockEditorAssetsMethod(): void {
		$container = $this->createContainer();

		$this->assertTrue( method_exists( $container, 'enqueue_block_editor_assets' ) );
	}

	public function testBlockEditorPanelContainerTypeReturnsCorrectValue(): void {
		$container = $this->createContainer();

		$this->assertSame( 'block_editor_panel', $container->type() );
	}

	public function testMountRegistersEnqueueBlockEditorAssetsHook(): void {
		$runtime  = $this->runtime();
		$compiled = $runtime->register(
			array(
				'id'        => 'trait_panel_test',
				'container' => array(
					'type'       => 'block_editor_panel',
					'title'      => 'Trait Panel',
					'post_types' => array( 'post' ),
					'capability' => 'edit_post',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_trait_panel_test',
				),
				'sections'  => array(
					'main' => array(
						'fields' => array(
							array(
								'id'      => 'field_one',
								'type'    => 'text',
								'default' => '',
							),
						),
					),
				),
			)
		);

		$runtime->boot();

		$this->assertArrayHasKey( 'enqueue_block_editor_assets', $GLOBALS['lerm_admin_config_actions'] );
	}

	public function testMountRegistersHookOnlyOnceForMultipleSchemas(): void {
		$runtime = $this->runtime();
		$schema  = array(
			'container' => array(
				'type'       => 'block_editor_panel',
				'post_types' => array( 'post' ),
				'capability' => 'edit_post',
			),
			'store'     => array(
				'type' => 'post_meta',
				'key'  => '_multi_test',
			),
			'sections'  => array(
				'main' => array(
					'fields' => array(
						array(
							'id'      => 'f1',
							'type'    => 'text',
							'default' => '',
						),
					),
				),
			),
		);

		$runtime->register(
			array_merge(
				$schema,
				array(
					'id'    => 'panel_one',
					'title' => 'Panel One',
				)
			)
		);
		$runtime->register(
			array_merge(
				$schema,
				array(
					'id'    => 'panel_two',
					'title' => 'Panel Two',
				)
			)
		);
		$runtime->boot();

		$hook_count = 0;
		foreach ( $GLOBALS['lerm_admin_config_actions']['enqueue_block_editor_assets'] ?? array() as $entry ) {
			if ( is_array( $entry ) && isset( $entry['callback'] ) ) {
				++$hook_count;
			}
		}

		$this->assertSame( 1, $hook_count, 'Multiple schemas should register the hook only once.' );
	}

	public function testContainerTypeForBlockPanelIsCalledDuringEnqueue(): void {
		$runtime = $this->runtime();
		$runtime->register(
			array(
				'id'        => 'ct_panel',
				'container' => array(
					'type'       => 'block_editor_panel',
					'title'      => 'CT Panel',
					'post_types' => array( 'post' ),
					'capability' => 'edit_post',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_ct_panel',
				),
				'sections'  => array(
					'main' => array(
						'fields' => array(
							array(
								'id'      => 'f1',
								'type'    => 'text',
								'default' => '',
							),
						),
					),
				),
			)
		);

		$runtime->boot();

		$GLOBALS['lerm_admin_config_current_screen'] = (object) array( 'post_type' => 'post' );
		$GLOBALS['post']                             = (object) array(
			'ID'        => 1,
			'post_type' => 'post',
		);

		$container = $runtime->containers()['block_editor_panel'];
		$this->assertInstanceOf( BlockEditorPanelContainer::class, $container );

		// Should not throw — verifies the trait wiring is correct.
		$container->enqueue_block_editor_assets();

		$script = $GLOBALS['lerm_admin_config_enqueued_scripts']['lerm-admin-config-block-panel'] ?? null;
		$this->assertIsArray( $script, 'Block panel script should be enqueued.' );
		$this->assertTrue( $GLOBALS['lerm_admin_config_media_enqueued'] ?? false, 'Media should be enqueued.' );
	}

	private function createContainer(): BlockEditorPanelContainer {
		$framework = new Framework(
			new class() implements AssetResolver {
				public function url( string $filename ): string {
					return 'https://example.test/assets/' . ltrim( $filename, '/' );
				}

				public function version(): string {
					return 'unit-version';
				}
			}
		);

		return new BlockEditorPanelContainer( $framework );
	}
}
