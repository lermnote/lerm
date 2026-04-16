<?php
/**
 * Shared WordPress runtime for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Registry\ContainerRegistry;
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
	private StoreResolver $stores;
	private Framework $framework;

	/**
	 * @var array<string, bool>
	 */
	private array $booted = array();

	public function __construct( ?SchemaRegistry $registry = null, ?Framework $framework = null ) {
		$this->framework = $framework ?? new Framework();
		$this->registry  = $registry ?? new SchemaRegistry();
		$this->stores    = new StoreResolver( $this->framework );
		$this->containers = new ContainerRegistry();
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

		return $this->registry->register( $schema );
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

	public function register_field_module( FieldModule $module ): void {
		$this->framework->register_field_module( $module );
	}

	public function register_store_factory( string $type, callable $factory ): void {
		$this->stores->register_factory( $type, $factory );
	}

	public function boot(): void {
		foreach ( $this->registry->all() as $compiled ) {
			if ( isset( $this->booted[ $compiled->id() ] ) ) {
				continue;
			}

			$container_type = sanitize_key( (string) ( $compiled->container()['type'] ?? 'options_page' ) );

			if ( $this->containers->has( $container_type ) ) {
				$this->containers->get( $container_type )->mount( $compiled );
				$this->booted[ $compiled->id() ] = true;
				continue;
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
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

			$this->booted[ $compiled->id() ] = true;
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
}
