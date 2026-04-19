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
use Lerm\AdminConfig\Stores\StoreResolver;
use Lerm\AdminConfig\WordPress\Containers\CommentContainer;
use Lerm\AdminConfig\WordPress\Containers\MetaboxContainer;
use Lerm\AdminConfig\WordPress\Containers\NetworkOptionsPageContainer;
use Lerm\AdminConfig\WordPress\Containers\OptionsPageContainer;
use Lerm\AdminConfig\WordPress\Containers\ProfileContainer;
use Lerm\AdminConfig\WordPress\Containers\TaxonomyContainer;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Registry\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Stores\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Runtime {

	private static ?self $instance = null;

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
	}

	public static function instance( ?AssetResolver $asset_resolver = null ): self {
		if ( null === self::$instance ) {
			self::$instance = new self(
				null,
				new Framework( $asset_resolver )
			);
		}

		return self::$instance;
	}

	public function register( array $schema ): CompiledSchema {
		$this->framework->field_modules()->enable_for_definition( $schema );

		$compiled = $this->registry->register( $schema );

		if ( $this->boot_requested ) {
			$this->mount_schema( $compiled );
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
		$this->boot_requested = true;

		foreach ( $this->registry->all() as $compiled ) {
			$this->mount_schema( $compiled );
		}
	}

	public function store( string $schema_id, array $context = array() ): OptionStore {
		return $this->stores->store( $this->compiled( $schema_id ), $context );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all( string $schema_id, array $context = array() ): array {
		return $this->store( $schema_id, $context )->all();
	}

	/**
	 * @return mixed
	 */
	public function get( string $schema_id, string $field_id, string $tag = '', $default = '', array $context = array() ) {
		return $this->store( $schema_id, $context )->get( $field_id, $tag, $default );
	}

	private function mount_schema( CompiledSchema $compiled, bool $force = false ): void {
		if ( ! $force && isset( $this->mounted[ $compiled->id() ] ) ) {
			return;
		}

		$container_type = sanitize_key( (string) ( $compiled->container()['type'] ?? 'options_page' ) );

		if ( $this->containers->has( $container_type ) ) {
			$this->containers->get( $container_type )->mount( $compiled );
			$this->mounted[ $compiled->id() ] = $container_type;
			unset( $this->missing_container_notice[ $compiled->id() ] );
			return;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && empty( $this->missing_container_notice[ $compiled->id() ] ) ) {
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
}
