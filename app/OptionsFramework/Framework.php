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

	public function __construct() {
		$this->field_types = new FieldTypeRegistry();
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
	public function store( array $definition, ?StorageBackend $backend = null ): OptionStore {
		$page_id     = $this->page_id( $definition );
		$cache_key   = null !== $backend ? $page_id . '_' . $backend->key() : $page_id;

		if ( ! isset( $this->stores[ $cache_key ] ) ) {
			$this->stores[ $cache_key ] = new OptionStore( $definition, $this->field_types, $backend );
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
		$page_id = $this->page_id( $definition );

		if ( ! isset( $this->pages[ $page_id ] ) ) {
			$this->pages[ $page_id ] = new OptionsPage( $definition, $this->store( $definition, $backend ), $this->field_types );
		}

		return $this->pages[ $page_id ];
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
