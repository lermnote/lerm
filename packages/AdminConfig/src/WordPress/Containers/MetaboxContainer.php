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
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		$this->hooks_registered = true;
	}

	public function register_meta_boxes( string $post_type ): void {
		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();
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

		$schema   = $this->schemas[ $schema_id ];
		$store    = $this->stores->store( $schema, array( 'post_id' => $post->ID ) );
		$renderer = new OptionsPage(
			$schema->definition(),
			$store,
			$this->framework->field_types(),
			$this->framework->asset_resolver(),
			false
		);
		$sections = PageSchema::sections( $schema->definition() );
		$section_id = (string) array_key_first( $sections );
		$section = '' !== $section_id ? ( $sections[ $section_id ] ?? null ) : null;
		$flash   = ValidationFlash::consume( 'metabox', $schema->id(), (string) $post->ID );
		$values  = ValidationFlash::render_values( $store->all(), $flash );
		$errors  = ValidationFlash::field_errors( $flash );
		$notice  = ValidationFlash::notice( $flash );

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

			$store        = $this->stores->store( $schema, array( 'post_id' => $post_id ) );
			$storage_key  = $store->storage_key();
			$submitted    = isset( $_POST[ $storage_key ] ) && is_array( $_POST[ $storage_key ] )
				? wp_unslash( $_POST[ $storage_key ] )
				: array();
			$sections     = PageSchema::sections( $schema->definition() );
			$section_id   = (string) array_key_first( $sections );

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
