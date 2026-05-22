<?php
/**
 * WordPress comment container backed by admin-config schema and stores.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Containers;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Backends\ArrayBackend;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\ContainerSaveSupport;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentContainer implements Container {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	private bool $hooks_registered       = false;
	private bool $assets_hook_registered = false;

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'comment';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		if ( ! $this->hooks_registered ) {
			add_action( 'add_meta_boxes_comment', array( $this, 'register_meta_boxes' ) );
			add_action( 'edit_comment', array( $this, 'save_comment' ) );
			$this->hooks_registered = true;
		}

		if ( ! $this->assets_hook_registered ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			$this->assets_hook_registered = true;
		}
	}

	public function register_meta_boxes( ?\WP_Comment $comment = null ): void {
		foreach ( $this->schemas as $schema ) {
			$container = $schema->container();

			add_meta_box(
				$this->meta_box_id( $schema ),
				(string) ( $container['title'] ?? $schema->definition()['title'] ?? __( 'Comment Settings', 'lerm' ) ),
				array( $this, 'render_meta_box' ),
				'comment',
				(string) ( $container['context'] ?? 'normal' ),
				(string) ( $container['priority'] ?? 'default' ),
				array(
					'schema_id' => $schema->id(),
				)
			);
		}
	}

	public function enqueue_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'comment' !== $screen->id ) {
			return;
		}

		$schema = $this->first_schema();

		if ( ! $schema ) {
			return;
		}

		$this->renderer( $schema )->enqueue_support_assets( 'comment-' . $schema->id() );
	}

	public function render_meta_box( \WP_Comment $comment, array $callback_args ): void {
		$schema_id = isset( $callback_args['args']['schema_id'] ) ? sanitize_key( (string) $callback_args['args']['schema_id'] ) : '';

		if ( '' === $schema_id || ! isset( $this->schemas[ $schema_id ] ) ) {
			return;
		}

		$schema      = $this->schemas[ $schema_id ];
		$store       = $this->stores->store( $schema, array( 'comment_id' => $comment->comment_ID ) );
		$renderer    = $this->renderer( $schema, $store );
		$sections    = PageSchema::sections( $schema->definition() );
		$flash       = ValidationFlash::consume( 'comment', $schema->id(), (string) $comment->comment_ID );
		$values      = ValidationFlash::render_values( $store->all(), $flash );
		$errors      = ValidationFlash::field_errors( $flash );
		$notice      = ValidationFlash::notice( $flash );
		$show_titles = count( $sections ) > 1;

		echo '<div class="lerm-comment-metabox lerm-metabox--stack">';

		if ( null !== $notice ) {
			printf(
				'<div class="notice %1$s inline"><p>%2$s</p></div>',
				esc_attr( $notice['class'] ),
				esc_html( $notice['message'] )
			);
		}

		foreach ( $sections as $section_id => $section ) {
			$title       = isset( $section['title'] ) && is_scalar( $section['title'] ) ? (string) $section['title'] : '';
			$description = isset( $section['description'] ) && is_scalar( $section['description'] ) ? (string) $section['description'] : '';

			if ( $show_titles && '' !== $title ) {
				printf( '<h3>%s</h3>', esc_html( $title ) );
			}

			if ( '' !== $description ) {
				printf( '<p class="description">%s</p>', esc_html( $description ) );
			}

			$renderer->render_fields(
				PageSchema::section_fields( $section ),
				$values,
				(string) $section_id,
				false,
				'stack',
				$errors
			);
		}

		wp_nonce_field( $this->nonce_action( $schema ), $this->nonce_name( $schema ) );
		echo '</div>';
	}

	public function save_comment( int $comment_id ): void {
		$comment = get_comment( $comment_id );

		if ( ! $comment instanceof \WP_Comment ) {
			return;
		}

		foreach ( $this->schemas as $schema ) {
			$nonce = ContainerSaveSupport::posted_nonce( $this->nonce_name( $schema ) );

			if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonce_action( $schema ) ) ) {
				continue;
			}

			if ( ! current_user_can( $this->capability_for_schema( $schema ), $comment_id ) ) {
				continue;
			}

			$store     = $this->stores->store( $schema, array( 'comment_id' => $comment_id ) );
			$submitted = ContainerSaveSupport::submitted_values( $store );

			ContainerSaveSupport::persist(
				'comment',
				$schema->id(),
				(string) $comment_id,
				$store,
				$submitted,
				static fn ( OptionStore $resolved_store, array $payload ): bool => $resolved_store->import_all( $payload ),
				__( 'Please review the highlighted comment fields before saving again.', 'lerm' ),
				__( 'Unable to save these comment settings right now.', 'lerm' )
			);
		}
	}

	private function renderer( CompiledSchema $schema, ?OptionStore $store = null ): OptionsPage {
		$resolved_store = $store ?? $this->framework->store(
			$schema->definition(),
			new ArrayBackend( 'comment_defaults_' . $schema->id() )
		);

		return new OptionsPage(
			$schema->definition(),
			$resolved_store,
			$this->framework->field_types(),
			$this->framework->asset_resolver(),
			false,
			$this->framework->field_modules()
		);
	}

	private function capability_for_schema( CompiledSchema $schema ): string {
		$container = $schema->container();

		if ( ! empty( $container['capability'] ) && is_scalar( $container['capability'] ) ) {
			return (string) $container['capability'];
		}

		return 'edit_comment';
	}

	private function meta_box_id( CompiledSchema $schema ): string {
		return 'lerm-admin-config-comment-' . $schema->id();
	}

	private function nonce_name( CompiledSchema $schema ): string {
		return 'lerm_admin_config_comment_nonce_' . $schema->id();
	}

	private function nonce_action( CompiledSchema $schema ): string {
		return 'lerm_admin_config_comment_' . $schema->id();
	}

	private function first_schema(): ?CompiledSchema {
		foreach ( $this->schemas as $schema ) {
			return $schema;
		}

		return null;
	}
}
