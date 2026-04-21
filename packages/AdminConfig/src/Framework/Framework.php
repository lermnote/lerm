<?php // phpcs:disable WordPress.Files.FileName
/**
 * Core entry point for the admin config runtime.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework;

use Lerm\AdminConfig\Contracts\FieldModule;
use Lerm\AdminConfig\Framework\Admin\OptionsPage;
use Lerm\AdminConfig\Framework\Contracts\StorageBackend;
use Lerm\AdminConfig\Framework\FieldTypes\FieldTypeRegistry;
use Lerm\AdminConfig\Framework\Contracts\AssetResolver;
use Lerm\AdminConfig\Framework\Resolvers\DefaultAssetResolver;
use Lerm\AdminConfig\Framework\Storage\OptionStore;
use Lerm\AdminConfig\Modules\AsyncFieldsModule;
use Lerm\AdminConfig\Modules\AdvancedFieldsModule;
use Lerm\AdminConfig\Modules\CoreFieldsModule;
use Lerm\AdminConfig\Modules\DesignFieldsModule;
use Lerm\AdminConfig\Modules\ExtendedFieldsModule;
use Lerm\AdminConfig\Modules\StructuredFieldsModule;
use Lerm\AdminConfig\Modules\ToolsFieldsModule;
use Lerm\AdminConfig\Registry\FieldModuleRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Framework {

	/**
	 * Deprecated shared framework instance retained for backward compatibility.
	 */
	private static ?self $instance = null;

	private FieldTypeRegistry $field_types;

	private FieldModuleRegistry $field_modules;

	/**
	 * Cached option stores keyed by page ID.
	 *
	 * @var array<string, OptionStore>
	 */
	private array $stores = array();

	/**
	 * Cached admin pages keyed by page ID.
	 *
	 * @var array<string, OptionsPage>
	 */
	private array $pages = array();

	private AssetResolver $asset_resolver;

	public function __construct( ?AssetResolver $resolver = null ) {
		$this->field_types    = new FieldTypeRegistry();
		$this->field_modules  = new FieldModuleRegistry( $this->field_types );
		$this->register_default_field_modules();
		$this->asset_resolver = $resolver ?? new DefaultAssetResolver(
			// When embedded in a theme/package tree, default to the bundled
			// AdminConfig assets. Plugin bootstrap injects its own resolver.
			trailingslashit( get_template_directory_uri() . '/packages/AdminConfig/assets' )
		);
	}

	/**
	 * Get the deprecated shared framework singleton.
	 *
	 * Prefer `new Framework()` or injecting a Framework into `Runtime` so tests
	 * and multi-runtime setups do not accidentally share mutable state.
	 */
	public static function instance(): self {
		_deprecated_function( __METHOD__, '0.2.0', 'new ' . __CLASS__ . '()' );

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Get the field type registry.
	 */
	public function field_types(): FieldTypeRegistry {
		return $this->field_types;
	}

	public function field_modules(): FieldModuleRegistry {
		return $this->field_modules;
	}

	/**
	 * Register or extend a field type definition.
	 *
	 * @param array<string, mixed> $definition
	 */
	public function register_field_type( string $type, array $definition = array() ): void {
		$this->field_types->register( $type, $definition );
	}

	public function register_validator( string $type, callable $validator ): void {
		$this->field_types->register_validator( $type, $validator );
	}

	public function register_field_module( FieldModule $module ): void {
		$this->field_modules->register( $module );
	}

	public function asset_resolver(): AssetResolver {
		return $this->asset_resolver;
	}

	/**
	 * Get or create an option store for a page definition.
	 *
	 * Pass a StorageBackend to use term_meta, user_meta, or post_meta instead
	 * of the default get_option / update_option backend:
	 *
	 *   $framework->store( $def, new TermMetaBackend( $term_id, 'my_key' ) );
	 *   $framework->store( $def, new UserMetaBackend( $user_id, 'my_key' ) );
	 *   $framework->store( $def, new PostMetaBackend( $post_id, 'my_key' ) );
	 *
	 * @param array<string, mixed>  $definition Page definition.
	 * @param StorageBackend|null   $backend    Optional custom backend.
	 */
	/**
	 * Fire a framework lifecycle hook.
	 *
	 * Used internally by the OptionStore after a successful write.
	 * External code can hook 'lerm_admin_config_before_save' and
	 * 'lerm_admin_config_after_save' to observe saves.
	 *
	 * @param string               $hook    Short hook name ('before_save' or 'after_save').
	 * @param string               $page_id The page / store identifier.
	 * @param array<string, mixed> $data    Data being saved.
	 */
	public function fire( string $hook, string $page_id, array $data ): void {
		do_action( 'lerm_admin_config_' . $hook, $page_id, $data, $this );
	}

	public function store( array $definition, ?StorageBackend $backend = null ): OptionStore {
		$this->prepare_definition( $definition );
		$cache_key = $this->cache_key( $definition, $backend );

		if ( ! isset( $this->stores[ $cache_key ] ) ) {
			$this->stores[ $cache_key ] = new OptionStore( $definition, $this->field_types, $backend, $this );
		}

		return $this->stores[ $cache_key ];
	}

	/**
	 * Mount an admin options page.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @param StorageBackend|null  $backend    Optional custom backend.
	 */
	public function mount_options_page( array $definition, ?StorageBackend $backend = null ): OptionsPage {
		$this->prepare_definition( $definition );
		$cache_key = $this->cache_key( $definition, $backend );

		if ( ! isset( $this->pages[ $cache_key ] ) ) {
			$this->pages[ $cache_key ] = new OptionsPage( $definition, $this->store( $definition, $backend ), $this->field_types, $this->asset_resolver, true, $this->field_modules );
		}

		return $this->pages[ $cache_key ];
	}

	/**
	 * Resolve the framework cache key for a mounted definition/backend pair.
	 *
	 * The page ID alone is not sufficient once the same schema can be reused
	 * against multiple backends (option row, term meta, user meta, post meta).
	 *
	 * @param array<string, mixed> $definition Page definition.
	 * @param StorageBackend|null  $backend    Optional custom backend.
	 */
	private function cache_key( array $definition, ?StorageBackend $backend = null ): string {
		$page_id = $this->page_id( $definition );

		return null !== $backend ? $page_id . '_' . $backend->key() : $page_id;
	}

	/**
	 * Resolve the unique page ID for caching.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 */
	private function page_id( array $definition ): string {
		$page_id = isset( $definition['id'] ) ? sanitize_key( (string) $definition['id'] ) : '';

		if ( '' !== $page_id ) {
			return $page_id;
		}

		$option_name = isset( $definition['option_name'] ) ? sanitize_key( (string) $definition['option_name'] ) : '';

		return '' !== $option_name ? $option_name : 'admin-config-page';
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	private function prepare_definition( array $definition ): void {
		$this->field_modules->enable_for_definition( $definition );
	}

	private function register_default_field_modules(): void {
		foreach ( array(
			new CoreFieldsModule(),
			new ExtendedFieldsModule(),
			new AsyncFieldsModule(),
			new DesignFieldsModule(),
			new AdvancedFieldsModule(),
			new StructuredFieldsModule(),
			new ToolsFieldsModule(),
		) as $module ) {
			$this->register_field_module( $module );
		}
	}
}

