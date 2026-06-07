<?php
/**
 * Shared block editor panel enqueue logic for admin-config containers.
 *
 * Consuming classes MUST declare:
 *   private array $schemas     — compiled schemas keyed by schema ID
 *   private Framework $framework — for asset resolution
 *
 * And MUST implement:
 *   private function containerTypeForBlockPanel(): string
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Framework\Contracts\AssetPathResolver;
use Lerm\AdminConfig\Framework\Support\PackageAssets;
use Lerm\AdminConfig\Framework\Support\ScriptAssetMetadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait HasBlockEditorPanel {

	/**
	 * Enqueue block editor panel assets.
	 *
	 * Hook this as: add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	 */
	public function enqueue_block_editor_assets(): void {
		$ctx = $this->resolveEditorContext();

		if ( empty( $ctx['post_id'] ) || '' === $ctx['post_type'] ) {
			return;
		}

		$panel_schemas = $this->collectPanelSchemas( $ctx['post_type'], $ctx['post_id'] );

		if ( empty( $panel_schemas ) ) {
			return;
		}

		wp_enqueue_media();

		$script = $this->resolvePanelScriptAsset();
		$handle = 'lerm-admin-config-block-panel';

		wp_enqueue_script(
			$handle,
			$this->panelAssetUrl( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
		);

		wp_enqueue_style(
			$handle,
			$this->panelAssetUrl( 'block-panel.css' ),
			array( 'wp-components' ),
			$this->panelAssetVersion()
		);

		wp_set_script_translations(
			$handle,
			'lerm-admin-config',
			dirname( __DIR__, 3 ) . '/languages/'
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
	private function resolveEditorContext(): array {
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
	private function collectPanelSchemas( string $post_type, int $post_id ): array {
		$schemas = array();

		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();

			if ( ! in_array( $post_type, $this->normalizePostTypes( $container['post_types'] ?? array() ), true ) ) {
				continue;
			}

			$capability = (string) ( $container['capability'] ?? 'edit_post' );

			if ( ! current_user_can( $capability, $post_id ) ) {
				continue;
			}

			$schemas[] = array(
				'schemaId'      => $schema->id(),
				'title'         => (string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm-admin-config' ) ),
				'containerType' => $this->containerTypeForBlockPanel(),
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
	private function resolvePanelScriptAsset(): array {
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
			$this->panelAssetVersion(),
			function ( string $asset ): string {
				return $this->panelAssetPath( $asset );
			}
		);
	}

	private function panelAssetUrl( string $asset ): string {
		/** @psalm-suppress UndefinedThisPropertyFetch */
		return $this->framework->asset_resolver()->url( $asset );
	}

	private function panelAssetPath( string $asset ): string {
		/** @psalm-suppress UndefinedThisPropertyFetch */
		$resolver = $this->framework->asset_resolver();

		if ( $resolver instanceof AssetPathResolver ) {
			return $resolver->path( $asset );
		}

		return PackageAssets::path( $asset );
	}

	private function panelAssetVersion(): string {
		/** @psalm-suppress UndefinedThisPropertyFetch */
		return $this->framework->asset_resolver()->version();
	}

	/**
	 * @param mixed $post_types
	 * @return array<int, string>
	 */
	private function normalizePostTypes( $post_types ): array {
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
