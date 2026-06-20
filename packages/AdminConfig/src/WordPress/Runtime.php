<?php
/**
 * Shared WordPress runtime for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Compiler\SchemaCompiler;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Registry\ContainerRegistry;
use Lerm\AdminConfig\Registry\DataSourceRegistry;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;
use Lerm\AdminConfig\Registry\SchemaRegistry;
use Lerm\AdminConfig\Rest\RestEndpoints;
use Lerm\AdminConfig\Stores\MissingStoreContextException;
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\WordPress\Containers\BlockEditorPanelContainer;
use Lerm\AdminConfig\WordPress\Containers\CommentContainer;
use Lerm\AdminConfig\WordPress\Containers\MetaboxContainer;
use Lerm\AdminConfig\WordPress\Containers\NetworkOptionsPageContainer;
use Lerm\AdminConfig\WordPress\Containers\OptionsPageContainer;
use Lerm\AdminConfig\WordPress\Containers\ProfileContainer;
use Lerm\AdminConfig\WordPress\Containers\TaxonomyContainer;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Storage\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Runtime {

	public const MAX_DATA_SOURCE_PER_PAGE = 100;

	private SchemaRegistry $registry;
	private ContainerRegistry $containers;
	private DataSourceRegistry $data_sources;
	private StoreResolver $stores;
	private Framework $framework;

	/**
	 * @var array<string, string>
	 */
	private array $mounted = array();

	/**
	 * @var array<string, bool>
	 */
	private array $missing_container_notice = array();

	/**
	 * @var array<string, bool>
	 */
	private array $mount_issue_notice = array();

	private bool $boot_requested = false;

	public function __construct( Framework $framework, ?SchemaRegistry $registry = null ) {
		$this->framework    = $framework;
		$this->registry     = $registry ?? new SchemaRegistry( new SchemaCompiler( $this->framework->field_types() ) );
		$this->stores       = new StoreResolver( $this->framework );
		$this->containers   = new ContainerRegistry();
		$this->data_sources = new DataSourceRegistry();
		$this->containers->register( new OptionsPageContainer( $this->framework, $this->stores ) );
		$this->containers->register( new NetworkOptionsPageContainer( $this->framework, $this->stores ) );
		$this->containers->register( new MetaboxContainer( $this->framework, $this->stores ) );
		$this->containers->register( new BlockEditorPanelContainer( $this->framework ) );
		$this->containers->register( new CommentContainer( $this->framework, $this->stores ) );
		$this->containers->register( new ProfileContainer( $this->framework, $this->stores ) );
		$this->containers->register( new TaxonomyContainer( $this->framework, $this->stores ) );
		( new RestEndpoints( $this ) )->register();
	}

	public function register( array $schema ): CompiledSchema {
		$this->framework->field_modules()->enable_for_definition( $schema );

		$compiled = $this->registry->register( $schema );

		if ( $this->boot_requested ) {
			$this->mount_schema( $compiled );
		}

		return $compiled;
	}

	/**
	 * @param array<int, array<string, mixed>> $schemas
	 * @return array<int, CompiledSchema>
	 */
	public function register_many( array $schemas ): array {
		$compiled = array();

		foreach ( $schemas as $schema ) {
			$compiled[] = $this->register( $schema );
		}

		return $compiled;
	}

	public function has( string $schema_id ): bool {
		return $this->registry->has( $schema_id );
	}

	public function compiled( string $schema_id ): CompiledSchema {
		return $this->registry->get( $schema_id );
	}

	/**
	 * @return array<string, CompiledSchema>
	 */
	public function schemas(): array {
		return $this->registry->all();
	}

	public function field_types(): FieldTypeRegistry {
		return $this->framework->field_types();
	}

	public function field_modules(): FieldModuleRegistry {
		return $this->framework->field_modules();
	}

	public function framework(): Framework {
		return $this->framework;
	}

	public function data_sources(): DataSourceRegistry {
		return $this->data_sources;
	}

	/**
	 * @return array<string, Container>
	 */
	public function containers(): array {
		return $this->containers->all();
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	public function register_field_type( string $type, array $definition = array() ): void {
		$this->framework->register_field_type( $type, $definition );
	}

	public function register_validator( string $type, callable $validator ): void {
		$this->framework->register_validator( $type, $validator );
	}

	public function register_field_module( FieldModule $module ): void {
		$this->framework->register_field_module( $module );
	}

	public function register_container( Container $container ): void {
		$type = sanitize_key( $container->type() );

		if ( '' === $type ) {
			return;
		}

		$this->containers->register( $container );

		foreach ( $this->registry->all() as $compiled ) {
			$container_type = sanitize_key( (string) ( $compiled->container()['type'] ?? 'options_page' ) );

			if ( $container_type !== $type ) {
				continue;
			}

			$this->mount_schema( $compiled );
		}
	}

	public function register_store_factory( string $type, callable $factory ): void {
		$this->stores->register_factory( $type, $factory );
	}

	public function register_data_source( string $source_id, callable $resolver ): void {
		$this->data_sources->register( $source_id, $resolver );
	}

	public function has_data_source( string $source_id ): bool {
		return $this->data_sources->has( $source_id );
	}

	/**
	 * @param array<string, mixed> $args
	 * @return mixed
	 */
	public function resolve_data_source( string $source_id, array $args = array() ): mixed {
		return $this->data_sources->resolve( $source_id, $args );
	}

	public function boot(): void {
		if ( $this->boot_requested ) {
			return;
		}

		$this->boot_requested = true;

		foreach ( $this->registry->all() as $compiled ) {
			$this->mount_schema( $compiled );
		}
	}

	public function is_booted(): bool {
		return $this->boot_requested;
	}

	/**
	 * @param mixed $value Raw page size.
	 */
	public static function sanitize_data_source_per_page( $value, int $fallback = 20 ): int {
		$per_page = absint( $value );

		if ( 0 === $per_page ) {
			$per_page = $fallback;
		}

		return min( self::MAX_DATA_SOURCE_PER_PAGE, max( 1, $per_page ) );
	}

	public function store( string $schema_id, array $context = array() ): OptionStore {
		return $this->stores->store( $this->compiled( $schema_id ), $context );
	}

	/**
	 * Return compiled defaults without touching the storage layer.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults( string $schema_id ): array {
		return $this->compiled( $schema_id )->defaults();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all( string $schema_id, array $context = array() ): array {
		try {
			return $this->store( $schema_id, $context )->all();
		} catch ( MissingStoreContextException $exception ) {
			$this->report_missing_store_context( __METHOD__, $exception );
			return $this->defaults( $schema_id );
		}
	}

	/**
	 * @param mixed $fallback
	 * @return mixed
	 */
	public function get( string $schema_id, string $field_id, string $tag = '', $fallback = '', array $context = array() ): mixed {
		try {
			return $this->store( $schema_id, $context )->get( $field_id, $tag, $fallback );
		} catch ( MissingStoreContextException $exception ) {
			$this->report_missing_store_context( __METHOD__, $exception );
			return $this->default_value( $schema_id, $field_id, $tag, $fallback );
		}
	}

	private function mount_schema( CompiledSchema $compiled, bool $force = false ): void {
		if ( ! $force && isset( $this->mounted[ $compiled->id() ] ) ) {
			return;
		}

		$container_type = sanitize_key( (string) ( $compiled->container()['type'] ?? 'options_page' ) );

		if ( $this->containers->has( $container_type ) ) {
			try {
				$this->containers->get( $container_type )->mount( $compiled );
				$this->mounted[ $compiled->id() ] = $container_type;
				unset( $this->missing_container_notice[ $compiled->id() ] );
				unset( $this->mount_issue_notice[ $compiled->id() ] );
			} catch ( \InvalidArgumentException $exception ) {
				$this->report_mount_issue( $compiled->id(), $exception->getMessage() );
			}

			return;
		}

		if ( $this->wp_debug_enabled() && empty( $this->missing_container_notice[ $compiled->id() ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					'Admin config container "%s" is not mounted yet for schema "%s".',
					$container_type,
					$compiled->id()
				),
				'0.1.0'
			);
		}

		$this->missing_container_notice[ $compiled->id() ] = true;
	}

	/**
	 * @param mixed $fallback
	 * @return mixed
	 */
	private function default_value( string $schema_id, string $field_id, string $tag = '', $fallback = '' ) {
		$defaults = $this->defaults( $schema_id );

		if ( ! array_key_exists( $field_id, $defaults ) ) {
			return $fallback;
		}

		$value = $defaults[ $field_id ];

		if ( is_array( $value ) && '' !== $tag ) {
			return $value[ $tag ] ?? $fallback;
		}

		return $value;
	}

	private function report_missing_store_context( string $method, MissingStoreContextException $exception ): void {
		if ( $this->wp_debug_enabled() ) {
			_doing_it_wrong( $method, $exception->getMessage(), '0.2.0' );
		}
	}

	private function report_mount_issue( string $schema_id, string $message ): void {
		if ( ! $this->wp_debug_enabled() || ! empty( $this->mount_issue_notice[ $schema_id ] ) ) {
			return;
		}

		_doing_it_wrong(
			__METHOD__,
			sprintf(
				'Admin config schema "%1$s" was not mounted: %2$s',
				$schema_id,
				$message
			),
			'0.2.0'
		);

		$this->mount_issue_notice[ $schema_id ] = true;
	}

	private function wp_debug_enabled(): bool {
		return defined( 'WP_DEBUG' ) ? (bool) constant( 'WP_DEBUG' ) : false;
	}

	/**
	 * @param mixed $resolved
	 * @return array{items: array<int, array{value: string, label: string}>, more: bool}
	 */
	public function normalize_data_source_response( mixed $resolved ): array {
		$items = array();
		$more  = false;

		if ( is_array( $resolved ) && isset( $resolved['items'] ) && is_array( $resolved['items'] ) ) {
			$more     = ! empty( $resolved['more'] );
			$resolved = $resolved['items'];
		}

		if ( ! is_array( $resolved ) ) {
			return array(
				'items' => array(),
				'more'  => $more,
			);
		}

		foreach ( $resolved as $key => $item ) {
			if ( is_array( $item ) ) {
				$value = isset( $item['value'] ) && is_scalar( $item['value'] ) ? (string) $item['value'] : '';
				$label = isset( $item['label'] ) && is_scalar( $item['label'] ) ? (string) $item['label'] : $value;

				if ( '' !== $value && '' !== $label ) {
					$items[] = array(
						'value' => $value,
						'label' => $label,
					);
				}

				continue;
			}

			if ( is_scalar( $key ) && is_scalar( $item ) ) {
				$items[] = array(
					'value' => (string) $key,
					'label' => (string) $item,
				);
				continue;
			}

			if ( is_scalar( $item ) ) {
				$items[] = array(
					'value' => (string) $item,
					'label' => (string) $item,
				);
			}
		}

		return array(
			'items' => $items,
			'more'  => $more,
		);
	}

	/**
	 * @param array<string, int> $context
	 */
	public function current_user_can_schema( CompiledSchema $schema, array $context = array() ): bool {
		$container  = $schema->container();
		$definition = $schema->definition();
		$type       = sanitize_key( (string) ( $container['type'] ?? 'options_page' ) );
		$menu       = is_array( $definition['menu'] ?? null ) ? $definition['menu'] : array();

		switch ( $type ) {
			case 'network_options_page':
				return current_user_can( (string) ( $menu['capability'] ?? $container['capability'] ?? 'manage_network_options' ) );

			case 'metabox':
			case 'block_editor_panel':
				if ( ! empty( $context['post_id'] ) && ! empty( $container['capability'] ) ) {
					return current_user_can( (string) $container['capability'], $context['post_id'] );
				}

				return current_user_can( 'edit_posts' );

			case 'taxonomy':
				if ( ! empty( $context['term_id'] ) && ! empty( $container['capability'] ) ) {
					return current_user_can( (string) $container['capability'], $context['term_id'] );
				}

				return current_user_can( 'manage_categories' );

			case 'profile':
				if ( ! empty( $context['user_id'] ) && ! empty( $container['capability'] ) ) {
					return current_user_can( (string) $container['capability'], $context['user_id'] );
				}

				return current_user_can( 'edit_users' );

			case 'comment':
				if ( ! empty( $context['comment_id'] ) && ! empty( $container['capability'] ) ) {
					return current_user_can( (string) $container['capability'], $context['comment_id'] );
				}

				return current_user_can( 'moderate_comments' );

			case 'options_page':
			default:
				return current_user_can( (string) ( $menu['capability'] ?? $container['capability'] ?? 'manage_options' ) );
		}
	}
}
