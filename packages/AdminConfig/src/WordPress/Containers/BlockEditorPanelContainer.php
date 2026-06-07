<?php
/**
 * WordPress block editor panel container for admin-config schemas.
 *
 * Mounted schemas appear as side-panel controls in the Gutenberg editor
 * via the shared block-panel JavaScript bundle. Unlike MetaboxContainer,
 * this container only activates when the block editor is in use — it never
 * renders classic meta boxes.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Support\PackageAssets;
use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BlockEditorPanelContainer implements Container {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private bool $hooks_registered = false;

	public function __construct(
		private Framework $framework
	) {
	}

	public function type(): string {
		return 'block_editor_panel';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( $this->hooks_registered ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		$this->hooks_registered = true;
	}

	public function enqueue_block_editor_assets(): void {
		$editor_context = $this->current_editor_context();

		if ( empty( $editor_context['post_id'] ) || '' === $editor_context['post_type'] ) {
			return;
		}

		$schemas = $this->block_editor_schemas( $editor_context['post_type'], $editor_context['post_id'] );

		if ( empty( $schemas ) ) {
			return;
		}

		wp_enqueue_media();

		$script = $this->block_panel_script_asset();
		$handle = 'lerm-admin-config-block-panel';

		wp_enqueue_script(
			$handle,
			$this->asset_url( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
		);

		wp_enqueue_style(
			$handle,
			$this->asset_url( 'block-panel.css' ),
			array( 'wp-components' ),
			$this->asset_version()
		);

		$payload = wp_json_encode(
			array(
				'restUrl'   => rest_url( 'lerm-admin-config/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'schemas'   => $schemas,
			)
		);

		if ( false === $payload ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			'window.lermAdminConfigBlockPanelConfigs = window.lermAdminConfigBlockPanelConfigs || []; window.lermAdminConfigBlockPanelConfigs.push(' . $payload . ');',
			'before'
		);
	}

	/**
	 * @return array{post_id: int, post_type: string}
	 */
	private function current_editor_context(): array {
		$post_id   = 0;
		$post_type = '';

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( is_object( $screen ) ) {
			$screen_vars      = get_object_vars( $screen );
			$screen_post_type = $screen_vars['post_type'] ?? '';

			if ( is_scalar( $screen_post_type ) ) {
				$post_type = sanitize_key( (string) $screen_post_type );
			}
		}

		if ( isset( $_GET['post'] ) ) {
			$post_id = absint( wp_unslash( $_GET['post'] ) );
		}

		global $post;

		if ( 0 === $post_id && is_object( $post ) && isset( $post->ID ) ) {
			$post_id = absint( $post->ID );
		}

		if ( '' === $post_type && is_object( $post ) && isset( $post->post_type ) && is_scalar( $post->post_type ) ) {
			$post_type = sanitize_key( (string) $post->post_type );
		}

		if ( '' === $post_type && $post_id > 0 && function_exists( 'get_post_type' ) ) {
			$resolved_post_type = get_post_type( $post_id );

			if ( is_scalar( $resolved_post_type ) ) {
				$post_type = sanitize_key( (string) $resolved_post_type );
			}
		}

		return array(
			'post_id'   => $post_id,
			'post_type' => $post_type,
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function block_editor_schemas( string $post_type, int $post_id ): array {
		$schemas = array();

		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();

			if ( ! in_array( $post_type, $this->normalize_post_types( $container['post_types'] ?? array() ), true ) ) {
				continue;
			}

			$capability = (string) ( $container['capability'] ?? 'edit_post' );

			if ( ! current_user_can( $capability, $post_id ) ) {
				continue;
			}

			$schemas[] = array(
				'schemaId'      => $schema->id(),
				'title'         => (string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm-admin-config' ) ),
				'containerType' => 'block_editor_panel',
				'postType'      => $post_type,
				'context'       => array(
					'post_id' => $post_id,
				),
			);
		}

		return $schemas;
	}

	/**
	 * @return array{file: string, dependencies: array<int, string>, version: string}
	 */
	private function block_panel_script_asset(): array {
		return ScriptAssetMetadata::resolve(
			'block-panel',
			'build/block-panel.js',
			array(
				'wp-api-fetch',
				'wp-block-editor',
				'wp-components',
				'wp-edit-post',
				'wp-element',
				'wp-plugins',
			),
			$this->asset_version(),
			function ( string $asset ): string {
				return $this->asset_path( $asset );
			}
		);
	}

	private function asset_url( string $asset ): string {
		return $this->framework->asset_resolver()->url( $asset );
	}

	private function asset_path( string $asset ): string {
		$resolver = $this->framework->asset_resolver();

		if ( $resolver instanceof AssetPathResolver ) {
			return $resolver->path( $asset );
		}

		return PackageAssets::path( $asset );
	}

	private function asset_version(): string {
		return $this->framework->asset_resolver()->version();
	}

	/**
	 * @param mixed $post_types
	 * @return array<int, string>
	 */
	private function normalize_post_types( $post_types ): array {
		$normalized = array();

		foreach ( is_array( $post_types ) ? $post_types : array( $post_types ) as $post_type ) {
			if ( ! is_scalar( $post_type ) ) {
				continue;
			}

			$value = sanitize_key( (string) $post_type );

			if ( '' === $value ) {
				continue;
			}

			$normalized[] = $value;
		}

		return array_values( array_unique( $normalized ) );
	}
}
