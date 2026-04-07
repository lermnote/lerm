<?php // phpcs:disable WordPress.Files.FileName
/**
 * Core entry point for the options framework MVP.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework;

use Lerm\OptionsFramework\Admin\OptionsPage;
use Lerm\OptionsFramework\Contracts\StorageBackend;
use Lerm\OptionsFramework\Registry\FieldTypeRegistry;
use Lerm\OptionsFramework\Contracts\AssetResolver;
use Lerm\OptionsFramework\Resolvers\DefaultAssetResolver;
use Lerm\OptionsFramework\Stores\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Framework {

	/**
	 * Shared framework instance.
	 */
	private static ?self $instance = null;

	private FieldTypeRegistry $field_types;

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
		$this->asset_resolver = $resolver ?? new DefaultAssetResolver(
			// Derive the assets URL from this file's location so the framework
			// is portable — no dependency on LERM_URI or any host constant.
			trailingslashit( get_template_directory_uri() . '/app/OptionsFramework/assets' )
		);
	}

	/**
	 * Get the shared framework instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the field type registry.
	 */
	public function field_types(): FieldTypeRegistry {
		return $this->field_types;
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
	 * External code can hook 'lerm_options_framework_before_save' and
	 * 'lerm_options_framework_after_save' to observe saves.
	 *
	 * @param string               $hook    Short hook name ('before_save' or 'after_save').
	 * @param string               $page_id The page / store identifier.
	 * @param array<string, mixed> $data    Data being saved.
	 */
	public function fire( string $hook, string $page_id, array $data ): void {
		do_action( 'lerm_options_framework_' . $hook, $page_id, $data, $this );
	}

	public function store( array $definition, ?StorageBackend $backend = null ): OptionStore {
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
		$cache_key = $this->cache_key( $definition, $backend );

		if ( ! isset( $this->pages[ $cache_key ] ) ) {
			$this->pages[ $cache_key ] = new OptionsPage( $definition, $this->store( $definition, $backend ), $this->field_types, $this->asset_resolver );
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

		return '' !== $option_name ? $option_name : 'options-framework-page';
	}
}
