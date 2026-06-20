<?php
/**
 * Shared block editor panel enqueue logic for admin-config containers.
 *
 * The consuming class MUST implement BlockEditorPanelContext, providing:
 *   - schemas(): array<string, CompiledSchema>
 *   - framework(): Framework
 *   - container_type_for_block_panel(): string
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Support\PackageAssets;
use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-require-implements BlockEditorPanelContext
 */
trait HasBlockEditorPanel {

	/**
	 * Enqueue block editor panel assets.
	 *
	 * Hook this as: add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	 */
	public function enqueue_block_editor_assets(): void {
		$ctx = $this->resolve_editor_context();

		if ( empty( $ctx['post_id'] ) || '' === $ctx['post_type'] ) {
			return;
		}

		$panel_schemas = $this->collect_panel_schemas( $ctx['post_type'], $ctx['post_id'] );

		if ( empty( $panel_schemas ) ) {
			return;
		}

		wp_enqueue_media();

		$script = $this->resolve_panel_script_asset();
		$handle = 'lerm-admin-config-block-panel';

		wp_enqueue_script(
			$handle,
			$this->panel_asset_url( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
		);

		wp_enqueue_style(
			$handle,
			$this->panel_asset_url( 'block-panel.css' ),
			array( 'wp-components' ),
			$this->panel_asset_version()
		);

		wp_set_script_translations(
			$handle,
			'lerm-admin-config',
			dirname( PackageAssets::directory() ) . '/languages/'
		);

		$payload = wp_json_encode(
			array(
				'restUrl'   => rest_url( 'lerm-admin-config/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'schemas'   => $panel_schemas,
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
	private function resolve_editor_context(): array {
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
	private function collect_panel_schemas( string $post_type, int $post_id ): array {
		$schemas = array();

		foreach ( $this->schemas() as $schema ) {
			$container = $schema->container();

			if ( ! in_array( $post_type, ContainerSaveSupport::normalize_string_list( $container['post_types'] ?? array() ), true ) ) {
				continue;
			}

			$capability = (string) ( $container['capability'] ?? 'edit_post' );

			if ( ! current_user_can( $capability, $post_id ) ) {
				continue;
			}

			$schemas[] = array(
				'schemaId'      => $schema->id(),
				'title'         => (string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm-admin-config' ) ),
				'containerType' => $this->container_type_for_block_panel(),
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
	private function resolve_panel_script_asset(): array {
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
			$this->panel_asset_version(),
			function ( string $asset ): string {
				return $this->panel_asset_path( $asset );
			}
		);
	}

	private function panel_asset_url( string $asset ): string {
		return $this->framework()->asset_resolver()->url( $asset );
	}

	private function panel_asset_path( string $asset ): string {
		$resolver = $this->framework()->asset_resolver();

		if ( $resolver instanceof AssetPathResolver ) {
			return $resolver->path( $asset );
		}

		return PackageAssets::path( $asset );
	}

	private function panel_asset_version(): string {
		return $this->framework()->asset_resolver()->version();
	}
}
