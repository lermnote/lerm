<?php
/**
 * Unit tests for the BlockEditorPanelContainer class.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Containers\BlockEditorPanelContainer;

final class BlockEditorPanelContainerTest extends TestCase {

	public function testTypeReturnsBlockEditorPanel(): void {
		$container = $this->createContainer();

		$this->assertSame( 'block_editor_panel', $container->type() );
	}

	public function testMountDoesNotRegisterHookTwice(): void {
		$container = $this->createContainer();

		// Clear any existing hooks.
		unset( $GLOBALS['lerm_admin_config_actions'] );

		$schema_a = $this->createSchema( 'schema_a', 'Panel A' );
		$schema_b = $this->createSchema( 'schema_b', 'Panel B' );

		$container->mount( $schema_a );
		$container->mount( $schema_b );

		$this->assertArrayHasKey( 'enqueue_block_editor_assets', $GLOBALS['lerm_admin_config_actions'] );

		$callbacks = array_filter(
			$GLOBALS['lerm_admin_config_actions']['enqueue_block_editor_assets'] ?? array(),
			static fn ( $entry ) => is_array( $entry )
		);
		$this->assertCount( 1, $callbacks, 'Hook should only be registered once.' );
	}

	public function testEnqueueSkipsWhenNoEditorContext(): void {
		$container = $this->createContainer();
		$container->mount( $this->createSchema( 'test', 'Test Panel' ) );

		// No screen or post global set — should skip.
		unset( $GLOBALS['lerm_admin_config_enqueued_scripts'] );
		$container->enqueue_block_editor_assets();

		$this->assertArrayNotHasKey(
			'lerm-admin-config-block-panel',
			$GLOBALS['lerm_admin_config_enqueued_scripts'] ?? array(),
			'Should not enqueue without editor context.'
		);
	}

	public function testEnqueueSkipsForUnmatchedPostType(): void {
		$container = $this->createContainer();
		$container->mount( $this->createSchema( 'test', 'Test Panel' ) );

		$GLOBALS['lerm_admin_config_current_screen'] = (object) array( 'post_type' => 'page' );
		$GLOBALS['post']                              = (object) array( 'ID' => 1, 'post_type' => 'page' );
		unset( $GLOBALS['lerm_admin_config_enqueued_scripts'] );

		$container->enqueue_block_editor_assets();

		$this->assertArrayNotHasKey(
			'lerm-admin-config-block-panel',
			$GLOBALS['lerm_admin_config_enqueued_scripts'] ?? array(),
			'Should not enqueue for unmatched post type "page".'
		);
	}

	public function testEnqueueForMatchingPostType(): void {
		$container = $this->createContainer();
		$container->mount( $this->createSchema( 'test', 'Test Panel' ) );

		$GLOBALS['lerm_admin_config_current_screen'] = (object) array( 'post_type' => 'post' );
		$GLOBALS['post']                              = (object) array( 'ID' => 42, 'post_type' => 'post' );
		unset( $GLOBALS['lerm_admin_config_enqueued_scripts'] );

		$container->enqueue_block_editor_assets();

		$script = ($GLOBALS['lerm_admin_config_enqueued_scripts'] ?? array())['lerm-admin-config-block-panel'] ?? null;
		$this->assertIsArray( $script, 'Should enqueue block panel script for matching post type.' );
	}

	public function testEnqueueAddsWpSetScriptTranslations(): void {
		$container = $this->createContainer();
		$container->mount( $this->createSchema( 'test', 'Test Panel' ) );

		$GLOBALS['lerm_admin_config_current_screen']  = (object) array( 'post_type' => 'post' );
		$GLOBALS['post']                               = (object) array( 'ID' => 1, 'post_type' => 'post' );
		unset( $GLOBALS['lerm_admin_config_script_translations'] );

		$container->enqueue_block_editor_assets();

		$translations = ($GLOBALS['lerm_admin_config_script_translations'] ?? array())['lerm-admin-config-block-panel'] ?? null;
		$this->assertIsArray( $translations, 'Should register script translations.' );
		$this->assertSame( 'lerm-admin-config', $translations['domain'] );
	}

	/**
	 * @return \Lerm\AdminConfig\Compiler\CompiledSchema
	 */
	private function createSchema( string $id, string $title ): object {
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

		$compiler = new \Lerm\AdminConfig\Compiler\SchemaCompiler( $framework->field_types() );

		return $compiler->compile(
			array(
				'id'        => $id,
				'title'     => $title,
				'container' => array(
					'type'       => 'block_editor_panel',
					'title'      => $title,
					'post_types' => array( 'post' ),
					'capability' => 'edit_post',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_' . $id . '_settings',
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
