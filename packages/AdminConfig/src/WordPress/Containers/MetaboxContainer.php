<?php
/**
 * WordPress metabox container backed by admin-config schema and stores.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaboxContainer implements Container {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private bool $hooks_registered = false;

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'metabox';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( $this->hooks_registered ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
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

		$script = $this->block_panel_script_asset();
		$handle = 'lerm-admin-config-block-panel';

		wp_enqueue_script(
			$handle,
			$this->asset_url( $script['file'] ),
			$script['dependencies'],
			$script['version'],
			true
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

	public function register_meta_boxes( string $post_type ): void {
		foreach ( $this->schemas as $schema ) {
			$container  = $schema->container();
			$post_types = $this->normalize_post_types( $container['post_types'] ?? array() );

			if ( ! in_array( $post_type, $post_types, true ) ) {
				continue;
			}

			add_meta_box(
				$this->meta_box_id( $schema ),
				(string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm' ) ),
				array( $this, 'render_meta_box' ),
				$post_type,
				(string) ( $container['context'] ?? 'advanced' ),
				(string) ( $container['priority'] ?? 'default' ),
				array(
					'schema_id' => $schema->id(),
				)
			);
		}
	}

	public function render_meta_box( \WP_Post $post, array $callback_args ): void {
		$schema_id = isset( $callback_args['args']['schema_id'] ) ? sanitize_key( (string) $callback_args['args']['schema_id'] ) : '';

		if ( '' === $schema_id || ! isset( $this->schemas[ $schema_id ] ) ) {
			return;
		}

		$schema     = $this->schemas[ $schema_id ];
		$store      = $this->stores->store( $schema, array( 'post_id' => $post->ID ) );
		$renderer   = new OptionsPage(
			$schema->definition(),
			$store,
			$this->framework->field_types(),
			$this->framework->asset_resolver(),
			false,
			$this->framework->field_modules()
		);
		$sections   = PageSchema::sections( $schema->definition() );
		$section_id = (string) array_key_first( $sections );
		$section    = '' !== $section_id ? ( $sections[ $section_id ] ?? null ) : null;
		$flash      = ValidationFlash::consume( 'metabox', $schema->id(), (string) $post->ID );
		$values     = ValidationFlash::render_values( $store->all(), $flash );
		$errors     = ValidationFlash::field_errors( $flash );
		$notice     = ValidationFlash::notice( $flash );

		if ( ! is_array( $section ) ) {
			return;
		}

		wp_nonce_field( $this->nonce_action( $schema ), $this->nonce_name( $schema ) );

		echo '<div class="lerm-metabox lerm-metabox--stack">';
		if ( null !== $notice ) {
			printf(
				'<div class="notice %1$s inline"><p>%2$s</p></div>',
				esc_attr( $notice['class'] ),
				esc_html( $notice['message'] )
			);
		}
		$description = isset( $section['description'] ) && is_scalar( $section['description'] ) ? (string) $section['description'] : '';
		if ( '' !== $description ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
		$renderer->render_fields(
			PageSchema::section_fields( $section ),
			$values,
			$section_id,
			false,
			'stack',
			$errors
		);
		echo '</div>';
	}

	public function save_post( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$container  = $schema->container();
			$post_types = $this->normalize_post_types( $container['post_types'] ?? array() );

			if ( ! in_array( $post->post_type, $post_types, true ) ) {
				continue;
			}

			$nonce_name = $this->nonce_name( $schema );
			$nonce      = isset( $_POST[ $nonce_name ] ) && is_scalar( $_POST[ $nonce_name ] )
				? (string) wp_unslash( $_POST[ $nonce_name ] )
				: '';

			if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonce_action( $schema ) ) ) {
				continue;
			}

			$capability = (string) ( $container['capability'] ?? 'edit_post' );

			if ( ! current_user_can( $capability, $post_id ) ) {
				continue;
			}

			$store       = $this->stores->store( $schema, array( 'post_id' => $post_id ) );
			$storage_key = $store->storage_key();
			$submitted   = isset( $_POST[ $storage_key ] ) && is_array( $_POST[ $storage_key ] )
				? wp_unslash( $_POST[ $storage_key ] )
				: array();
			$sections    = PageSchema::sections( $schema->definition() );
			$section_id  = (string) array_key_first( $sections );

			if ( '' === $section_id ) {
				continue;
			}

			$success = $store->save_section( $section_id, $submitted );

			if ( $store->has_validation_errors() ) {
				ValidationFlash::store(
					'metabox',
					$schema->id(),
					(string) $post_id,
					array(
						'class'     => 'notice-error',
						'message'   => __( 'Please review the highlighted metabox fields before saving again.', 'lerm' ),
						'errors'    => $store->validation_errors(),
						'submitted' => $submitted,
					)
				);
				continue;
			}

			if ( ! $success ) {
				ValidationFlash::store(
					'metabox',
					$schema->id(),
					(string) $post_id,
					array(
						'class'   => 'notice-warning',
						'message' => __( 'Unable to save these metabox settings right now.', 'lerm' ),
					)
				);
				continue;
			}

			ValidationFlash::clear( 'metabox', $schema->id(), (string) $post_id );
		}
	}

	private function meta_box_id( CompiledSchema $schema ): string {
		return 'lerm-admin-config-metabox-' . $schema->id();
	}

	private function nonce_name( CompiledSchema $schema ): string {
		return 'lerm_admin_config_metabox_nonce_' . $schema->id();
	}

	private function nonce_action( CompiledSchema $schema ): string {
		return 'lerm_admin_config_metabox_' . $schema->id();
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
				'title'         => (string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Settings', 'lerm' ) ),
				'containerType' => 'metabox',
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
		$dependencies = array(
			'wp-api-fetch',
			'wp-components',
			'wp-edit-post',
			'wp-element',
			'wp-plugins',
		);
		$fallback     = array(
			'file'         => 'build/block-panel.js',
			'dependencies' => $dependencies,
			'version'      => $this->asset_version(),
		);
		$asset_dir    = dirname( __DIR__, 3 ) . '/assets';
		$script_file  = $asset_dir . '/build/block-panel.js';
		$asset_file   = $asset_dir . '/build/block-panel.asset.php';

		if ( ! is_readable( $script_file ) || ! is_readable( $asset_file ) ) {
			return $fallback;
		}

		$asset = include $asset_file;

		if ( ! is_array( $asset ) ) {
			return $fallback;
		}

		foreach ( (array) ( $asset['dependencies'] ?? array() ) as $dependency ) {
			if ( is_string( $dependency ) && '' !== $dependency ) {
				$dependencies[] = $dependency;
			}
		}

		return array(
			'file'         => 'build/block-panel.js',
			'dependencies' => array_values( array_unique( $dependencies ) ),
			'version'      => isset( $asset['version'] ) && is_scalar( $asset['version'] )
				? (string) $asset['version']
				: $fallback['version'],
		);
	}

	private function asset_url( string $asset ): string {
		return $this->framework->asset_resolver()->url( $asset );
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
