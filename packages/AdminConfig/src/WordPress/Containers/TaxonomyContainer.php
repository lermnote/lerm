<?php
/**
 * WordPress taxonomy container backed by admin-config schema and stores.
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
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\WordPress\Support\ValidationFlash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyContainer implements Container {

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	/**
	 * @var array<string, bool>
	 */
	private array $taxonomy_hooks_registered = array();

	private bool $assets_hook_registered = false;

	public function __construct(
		private Framework $framework,
		private StoreResolver $stores
	) {
	}

	public function type(): string {
		return 'taxonomy';
	}

	public function mount( CompiledSchema $schema ): void {
		$this->schemas[ $schema->id() ] = $schema;

		foreach ( $this->taxonomies_for_schema( $schema ) as $taxonomy ) {
			if ( isset( $this->taxonomy_hooks_registered[ $taxonomy ] ) ) {
				continue;
			}

			add_action(
				$taxonomy . '_add_form_fields',
				function ( string $taxonomy_name ) use ( $taxonomy ): void {
					$resolved_taxonomy = '' !== $taxonomy_name ? $taxonomy_name : $taxonomy;
					$this->render_add_form_fields( $resolved_taxonomy );
				}
			);
			add_action(
				$taxonomy . '_edit_form_fields',
				function ( \WP_Term $term, string $taxonomy_name ) use ( $taxonomy ): void {
					$resolved_taxonomy = '' !== $taxonomy_name ? $taxonomy_name : $taxonomy;
					$this->render_edit_form_fields( $term, $resolved_taxonomy );
				},
				10,
				2
			);
			add_action(
				'created_' . $taxonomy,
				function ( int $term_id ) use ( $taxonomy ): void {
					$this->save_term( $term_id, $taxonomy );
				},
				10,
				1
			);
			add_action(
				'edited_' . $taxonomy,
				function ( int $term_id ) use ( $taxonomy ): void {
					$this->save_term( $term_id, $taxonomy );
				},
				10,
				1
			);

			$this->taxonomy_hooks_registered[ $taxonomy ] = true;
		}

		if ( $this->assets_hook_registered ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$this->assets_hook_registered = true;
	}

	public function enqueue_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || empty( $screen->taxonomy ) ) {
			return;
		}

		$taxonomy = sanitize_key( (string) $screen->taxonomy );
		$schema   = $this->first_schema_for_taxonomy( $taxonomy );

		if ( ! $schema ) {
			return;
		}

		$this->renderer( $schema, null )->enqueue_support_assets( 'taxonomy-' . $schema->id() );
	}

	public function render_add_form_fields( string $taxonomy ): void {
		foreach ( $this->schemas_for_taxonomy( $taxonomy ) as $schema ) {
			$store    = $this->framework->store(
				$schema->definition(),
				new ArrayBackend( $this->store_key( $schema ) )
			);
			$renderer = $this->renderer( $schema, $store );
			$sections = PageSchema::sections( $schema->definition() );
			$flash    = ValidationFlash::consume( 'taxonomy', $schema->id(), $this->add_flash_resource( $taxonomy ) );
			$values   = ValidationFlash::render_values( $store->all(), $flash );
			$errors   = ValidationFlash::field_errors( $flash );
			$notice   = ValidationFlash::notice( $flash );

			if ( null !== $notice ) {
				printf(
					'<div class="form-field term-admin-config-notice"><div class="notice %1$s inline"><p>%2$s</p></div></div>',
					esc_attr( $notice['class'] ),
					esc_html( $notice['message'] )
				);
			}

			foreach ( $sections as $section_id => $section ) {
				$title = isset( $section['title'] ) && is_scalar( $section['title'] ) ? (string) $section['title'] : '';
				if ( '' !== $title ) {
					printf( '<div class="form-field"><h2>%s</h2></div>', esc_html( $title ) );
				}

				foreach ( PageSchema::section_fields( $section ) as $field ) {
					echo '<div class="form-field term-admin-config-field">';
					$renderer->render_field( $field, $values, (string) $section_id, 'stack', $errors );
					echo '</div>';
				}
			}

			wp_nonce_field( $this->nonce_action( $schema ), $this->nonce_name( $schema ) );
		}
	}

	public function render_edit_form_fields( \WP_Term $term, string $taxonomy ): void {
		foreach ( $this->schemas_for_taxonomy( $taxonomy ) as $schema ) {
			$store    = $this->stores->store( $schema, array( 'term_id' => $term->term_id ) );
			$renderer = $this->renderer( $schema, $store );
			$sections = PageSchema::sections( $schema->definition() );
			$flash    = ValidationFlash::consume( 'taxonomy', $schema->id(), $this->edit_flash_resource( $term->term_id ) );
			$values   = ValidationFlash::render_values( $store->all(), $flash );
			$errors   = ValidationFlash::field_errors( $flash );
			$notice   = ValidationFlash::notice( $flash );

			if ( null !== $notice ) {
				printf(
					'<tr class="form-field term-admin-config-notice"><td colspan="2"><div class="notice %1$s inline"><p>%2$s</p></div></td></tr>',
					esc_attr( $notice['class'] ),
					esc_html( $notice['message'] )
				);
			}

			foreach ( $sections as $section_id => $section ) {
				$title = isset( $section['title'] ) && is_scalar( $section['title'] ) ? (string) $section['title'] : '';
				if ( '' !== $title ) {
					printf(
						'<tr class="form-field term-admin-config-group"><th scope="row" colspan="2"><h2>%s</h2></th></tr>',
						esc_html( $title )
					);
				}

				$renderer->render_fields(
					PageSchema::section_fields( $section ),
					$values,
					(string) $section_id,
					false,
					'table',
					$errors
				);
			}

			printf(
				'<tr class="form-field term-admin-config-nonce"><td colspan="2">%s</td></tr>',
				wp_nonce_field( $this->nonce_action( $schema ), $this->nonce_name( $schema ), true, false )
			);
		}
	}

	public function save_term( int $term_id, string $taxonomy ): void {
		foreach ( $this->schemas_for_taxonomy( $taxonomy ) as $schema ) {
			$nonce_name = $this->nonce_name( $schema );
			$nonce      = isset( $_POST[ $nonce_name ] ) && is_scalar( $_POST[ $nonce_name ] )
				? (string) wp_unslash( $_POST[ $nonce_name ] )
				: '';

			if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonce_action( $schema ) ) ) {
				continue;
			}

			if ( ! current_user_can( $this->capability_for_schema( $schema, $taxonomy ) ) ) {
				continue;
			}

			$store       = $this->stores->store( $schema, array( 'term_id' => $term_id ) );
			$storage_key = $store->storage_key();
			$submitted   = isset( $_POST[ $storage_key ] ) && is_array( $_POST[ $storage_key ] )
				? wp_unslash( $_POST[ $storage_key ] )
				: array();
			$resource    = $this->current_flash_resource( $taxonomy, $term_id );
			$success     = $store->import_all( $submitted );

			if ( $store->has_validation_errors() ) {
				ValidationFlash::store(
					'taxonomy',
					$schema->id(),
					$resource,
					array(
						'class'     => 'notice-error',
						'message'   => __( 'Please review the highlighted term fields before saving again.', 'lerm' ),
						'errors'    => $store->validation_errors(),
						'submitted' => $submitted,
					)
				);
				continue;
			}

			if ( ! $success ) {
				ValidationFlash::store(
					'taxonomy',
					$schema->id(),
					$resource,
					array(
						'class'   => 'notice-warning',
						'message' => __( 'Unable to save these term settings right now.', 'lerm' ),
					)
				);
				continue;
			}

			ValidationFlash::clear( 'taxonomy', $schema->id(), $resource );
		}
	}

	private function renderer( CompiledSchema $schema, ?\Lerm\AdminConfig\Framework\Storage\OptionStore $store ): OptionsPage {
		$resolved_store = $store ?? $this->framework->store(
			$schema->definition(),
			new ArrayBackend( 'taxonomy_defaults_' . $schema->id() )
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

	/**
	 * @return array<int, CompiledSchema>
	 */
	private function schemas_for_taxonomy( string $taxonomy ): array {
		$matched = array();

		foreach ( $this->schemas as $schema ) {
			if ( in_array( $taxonomy, $this->taxonomies_for_schema( $schema ), true ) ) {
				$matched[] = $schema;
			}
		}

		return $matched;
	}

	private function first_schema_for_taxonomy( string $taxonomy ): ?CompiledSchema {
		foreach ( $this->schemas_for_taxonomy( $taxonomy ) as $schema ) {
			return $schema;
		}

		return null;
	}

	/**
	 * @return array<int, string>
	 */
	private function taxonomies_for_schema( CompiledSchema $schema ): array {
		$container  = $schema->container();
		$taxonomies = $container['taxonomy'] ?? $container['taxonomies'] ?? array();
		$normalized = array();

		foreach ( is_array( $taxonomies ) ? $taxonomies : array( $taxonomies ) as $taxonomy ) {
			if ( ! is_scalar( $taxonomy ) ) {
				continue;
			}

			$value = sanitize_key( (string) $taxonomy );

			if ( '' === $value ) {
				continue;
			}

			$normalized[] = $value;
		}

		return array_values( array_unique( $normalized ) );
	}

	private function nonce_name( CompiledSchema $schema ): string {
		return 'lerm_admin_config_taxonomy_nonce_' . $schema->id();
	}

	private function nonce_action( CompiledSchema $schema ): string {
		return 'lerm_admin_config_taxonomy_' . $schema->id();
	}

	private function capability_for_schema( CompiledSchema $schema, string $taxonomy ): string {
		$container = $schema->container();

		if ( ! empty( $container['capability'] ) && is_scalar( $container['capability'] ) ) {
			return (string) $container['capability'];
		}

		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( $taxonomy_object && ! empty( $taxonomy_object->cap->manage_terms ) ) {
			return (string) $taxonomy_object->cap->manage_terms;
		}

		return 'manage_categories';
	}

	private function store_key( CompiledSchema $schema ): string {
		return sanitize_key( (string) ( $schema->store()['key'] ?? $schema->id() ) );
	}

	private function add_flash_resource( string $taxonomy ): string {
		return 'add:' . sanitize_key( $taxonomy );
	}

	private function edit_flash_resource( int $term_id ): string {
		return 'edit:' . $term_id;
	}

	private function current_flash_resource( string $taxonomy, int $term_id ): string {
		$posted_term_id = isset( $_POST['tag_ID'] ) ? absint( wp_unslash( $_POST['tag_ID'] ) ) : 0;

		if ( $posted_term_id > 0 && $posted_term_id === $term_id ) {
			return $this->edit_flash_resource( $term_id );
		}

		return $this->add_flash_resource( $taxonomy );
	}
}
