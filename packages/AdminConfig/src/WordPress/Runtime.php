<?php
/**
 * Shared WordPress runtime for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\Container;
use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Registry\ContainerRegistry;
use Lerm\AdminConfig\Registry\DataSourceRegistry;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;
use Lerm\AdminConfig\Registry\SchemaRegistry;
use Lerm\AdminConfig\Rest\RestEndpoints;
use Lerm\AdminConfig\Framework\Support\PageSchema;
use Lerm\AdminConfig\Stores\MissingStoreContextException;
use Lerm\AdminConfig\Stores\StoreResolver;
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

	public function __construct( ?SchemaRegistry $registry = null, ?Framework $framework = null ) {
		$this->framework    = $framework ?? new Framework();
		$this->registry     = $registry ?? new SchemaRegistry();
		$this->stores       = new StoreResolver( $this->framework );
		$this->containers   = new ContainerRegistry();
		$this->data_sources = new DataSourceRegistry();
		$this->containers->register( new OptionsPageContainer( $this->framework, $this->stores ) );
		$this->containers->register( new NetworkOptionsPageContainer( $this->framework, $this->stores ) );
		$this->containers->register( new MetaboxContainer( $this->framework, $this->stores ) );
		$this->containers->register( new CommentContainer( $this->framework, $this->stores ) );
		$this->containers->register( new ProfileContainer( $this->framework, $this->stores ) );
		$this->containers->register( new TaxonomyContainer( $this->framework, $this->stores ) );
		if ( LegacyAjax::enabled() ) {
			add_action( 'wp_ajax_lerm_admin_config_data_source', array( self::class, 'handle_ajax_data_source' ) );
		}
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
	public function resolve_data_source( string $source_id, array $args = array() ) {
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

	public static function handle_ajax_data_source(): void {
		LegacyAjax::deprecate( __METHOD__, 'lerm-admin-config/v1 REST data-source endpoint' );

		check_ajax_referer( 'lerm_admin_config_data_source', 'nonce' );

		$schema_id = isset( $_REQUEST['schema_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['schema_id'] ) ) : '';
		$runtime   = RestEndpoints::runtime_for_schema( $schema_id );

		if ( null === $runtime ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested schema was not found.', 'lerm' ),
				),
				404
			);
		}

		$runtime->handle_ajax_data_source_for_schema( $runtime->compiled( $schema_id ) );
	}

	private function handle_ajax_data_source_for_schema( CompiledSchema $compiled ): void {
		$context = $this->request_context();

		if ( ! $this->current_user_can_schema( $compiled, $context ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to query this data source.', 'lerm' ),
				),
				403
			);
		}

		$field_id = isset( $_REQUEST['field_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['field_id'] ) ) : '';
		$field    = PageSchema::field( $compiled->definition(), $field_id );

		if ( ! is_array( $field ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested field was not found.', 'lerm' ),
				),
				404
			);
		}

		$source_id = sanitize_key( (string) ( $field['source'] ?? $field['data_source'] ?? '' ) );

		if ( '' === $source_id || ! $this->has_data_source( $source_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested data source is not registered.', 'lerm' ),
				),
				404
			);
		}

		$selected = array();

		if ( isset( $_REQUEST['selected'] ) ) {
			$raw_selected = wp_unslash( $_REQUEST['selected'] );

			foreach ( is_array( $raw_selected ) ? $raw_selected : array( $raw_selected ) as $item ) {
				$item = is_scalar( $item ) ? trim( (string) $item ) : '';

				if ( '' !== $item ) {
					$selected[] = $item;
				}
			}
		}

		$args = array(
			'search'    => isset( $_REQUEST['search'] ) && is_scalar( $_REQUEST['search'] ) ? trim( (string) wp_unslash( $_REQUEST['search'] ) ) : '',
			'page'      => isset( $_REQUEST['page'] ) ? max( 1, absint( $_REQUEST['page'] ) ) : 1,
			'per_page'  => self::sanitize_data_source_per_page( $_REQUEST['per_page'] ?? null ),
			'selected'  => $selected,
			'context'   => $context,
			'field'     => $field,
			'schema'    => $compiled->definition(),
			'schema_id' => $compiled->id(),
		);

		wp_send_json_success(
			$this->normalize_data_source_response( $this->resolve_data_source( $source_id, $args ) )
		);
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
	 * @return mixed
	 */
	public function get( string $schema_id, string $field_id, string $tag = '', $fallback = '', array $context = array() ) {
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
	public function normalize_data_source_response( $resolved ): array {
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
	 * @return array<string, int>
	 */
	private function request_context(): array {
		$context     = array();
		$context_map = array(
			'post_id'    => 'post_id',
			'term_id'    => 'term_id',
			'user_id'    => 'user_id',
			'comment_id' => 'comment_id',
			'network_id' => 'network_id',
		);

		$raw_context = isset( $_REQUEST['context'] ) && is_array( $_REQUEST['context'] )
			? wp_unslash( $_REQUEST['context'] )
			: array();

		foreach ( $context_map as $request_key => $target_key ) {
			$value = isset( $raw_context[ $request_key ] ) ? absint( $raw_context[ $request_key ] ) : 0;

			if ( $value > 0 ) {
				$context[ $target_key ] = $value;
			}
		}

		return $context;
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
