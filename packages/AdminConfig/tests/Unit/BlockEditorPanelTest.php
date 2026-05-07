<?php
/**
 * Block editor panel bootstrap tests.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Tests\Unit;

use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Tests\Support\TestCase;
use Lerm\AdminConfig\WordPress\Containers\MetaboxContainer;
use Lerm\AdminConfig\WordPress\Runtime;

final class BlockEditorPanelTest extends TestCase {

	public function testMetaboxRuntimeRegistersBlockEditorAssetHook(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();

		$this->assertArrayHasKey( 'enqueue_block_editor_assets', $GLOBALS['lerm_admin_config_actions'] );
	}

	public function testMetaboxRuntimeDoesNotRegisterClassicMetaboxesInBlockEditor(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();
		$GLOBALS['lerm_admin_config_use_block_editor_for_post_type'] = array(
			'post' => true,
		);

		$container = $runtime->containers()['metabox'];

		$this->assertInstanceOf( MetaboxContainer::class, $container );
		$container->register_meta_boxes( 'post' );

		$this->assertSame( array(), $GLOBALS['lerm_admin_config_meta_boxes'] );
	}

	public function testMetaboxRuntimeStillRegistersClassicMetaboxesOutsideBlockEditor(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();
		$GLOBALS['lerm_admin_config_use_block_editor_for_post_type'] = array(
			'post' => false,
		);

		$container = $runtime->containers()['metabox'];

		$this->assertInstanceOf( MetaboxContainer::class, $container );
		$container->register_meta_boxes( 'post' );

		$this->assertCount( 1, $GLOBALS['lerm_admin_config_meta_boxes'] );
		$this->assertSame( 'lerm-admin-config-metabox-unit-post-metabox', $GLOBALS['lerm_admin_config_meta_boxes'][0]['id'] );
		$this->assertSame( 'post', $GLOBALS['lerm_admin_config_meta_boxes'][0]['screen'] );
	}

	public function testMetaboxRegistrationAcceptsNonPostAddMetaBoxesContexts(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();

		$container = $runtime->containers()['metabox'];

		$this->assertInstanceOf( MetaboxContainer::class, $container );
		$container->register_meta_boxes( 'comment', (object) array( 'comment_ID' => 123 ) );

		$this->assertSame( array(), $GLOBALS['lerm_admin_config_meta_boxes'] );
	}

	public function testMetaboxRuntimeEnqueuesBlockPanelWithPostContext(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();
		$GLOBALS['lerm_admin_config_current_screen'] = (object) array(
			'post_type' => 'post',
		);
		$GLOBALS['post']                             = (object) array(
			'ID'        => 123,
			'post_type' => 'post',
		);

		$container = $runtime->containers()['metabox'];

		$this->assertInstanceOf( MetaboxContainer::class, $container );
		$container->enqueue_block_editor_assets();

		$script           = $GLOBALS['lerm_admin_config_enqueued_scripts']['lerm-admin-config-block-panel'] ?? null;
		$inline           = $GLOBALS['lerm_admin_config_inline_scripts']['lerm-admin-config-block-panel'][0] ?? null;
		$asset_file       = dirname( __DIR__, 2 ) . '/assets/build/block-panel.asset.php';
		$expected_version = 'unit-version';

		if ( is_readable( $asset_file ) ) {
			$asset            = require $asset_file;
			$expected_version = (string) $asset['version'];
		}

		$this->assertIsArray( $script );
		$this->assertSame( 'https://example.test/assets/build/block-panel.js', $script['src'] );
		$this->assertSame( $expected_version, $script['version'] );
		$this->assertContains( 'wp-api-fetch', $script['dependencies'] );
		$this->assertContains( 'wp-edit-post', $script['dependencies'] );
		$this->assertContains( 'wp-element', $script['dependencies'] );
		$this->assertIsArray( $inline );
		$this->assertSame( 'before', $inline['position'] );

		$payload = $this->extract_inline_payload( (string) $inline['data'] );

		$this->assertSame( 'https://example.test/wp-json/lerm-admin-config/v1/', $payload['restUrl'] );
		$this->assertSame( 'nonce-wp_rest', $payload['restNonce'] );
		$this->assertSame( 'unit-post-metabox', $payload['schemas'][0]['schemaId'] );
		$this->assertSame( 123, $payload['schemas'][0]['context']['post_id'] );
		$this->assertSame( 'post', $payload['schemas'][0]['postType'] );
	}

	public function testMetaboxRuntimeSkipsBlockPanelForUnmatchedPostType(): void {
		$runtime = $this->runtime_with_metabox_schema();

		$runtime->boot();
		$GLOBALS['lerm_admin_config_current_screen'] = (object) array(
			'post_type' => 'page',
		);
		$GLOBALS['post']                             = (object) array(
			'ID'        => 123,
			'post_type' => 'page',
		);

		$container = $runtime->containers()['metabox'];

		$this->assertInstanceOf( MetaboxContainer::class, $container );
		$container->enqueue_block_editor_assets();

		$this->assertArrayNotHasKey( 'lerm-admin-config-block-panel', $GLOBALS['lerm_admin_config_enqueued_scripts'] );
	}

	private function runtime_with_metabox_schema(): Runtime {
		$runtime = new Runtime(
			null,
			new Framework(
				new class() implements AssetResolver {
					public function url( string $filename ): string {
						return 'https://example.test/assets/' . ltrim( $filename, '/' );
					}

					public function version(): string {
						return 'unit-version';
					}
				}
			)
		);

		$runtime->register(
			array(
				'id'        => 'unit-post-metabox',
				'title'     => 'Unit Post Metabox',
				'container' => array(
					'type'       => 'metabox',
					'title'      => 'Unit Post Metabox',
					'post_types' => array( 'post' ),
					'capability' => 'edit_post',
				),
				'store'     => array(
					'type' => 'post_meta',
					'key'  => '_unit_post_metabox',
				),
				'sections'  => array(
					'display' => array(
						'fields' => array(
							array(
								'id'      => 'headline',
								'type'    => 'text',
								'default' => '',
							),
						),
					),
				),
			)
		);

		return $runtime;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function extract_inline_payload( string $script ): array {
		$matched = preg_match( '/push\((.*)\);$/', $script, $matches );

		$this->assertSame( 1, $matched );

		$payload = json_decode( $matches[1], true );

		$this->assertIsArray( $payload );

		return $payload;
	}
}
