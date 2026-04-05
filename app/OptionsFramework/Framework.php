<?php // phpcs:disable WordPress.Files.FileName
/**
 * Core entry point for the options framework MVP.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework;

use Lerm\OptionsFramework\Admin\OptionsPage;
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
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function store( array $definition ): OptionStore {
		$page_id = $this->page_id( $definition );

		if ( ! isset( $this->stores[ $page_id ] ) ) {
			$this->stores[ $page_id ] = new OptionStore( $definition, $this->field_types );
		}

		return $this->stores[ $page_id ];
	}

	/**
	 * Mount an admin options page.
	 *
	 * @param array<string, mixed> $definition Page definition.
	 */
	public function mount_options_page( array $definition ): OptionsPage {
		$page_id = $this->page_id( $definition );

		if ( ! isset( $this->pages[ $page_id ] ) ) {
			$this->pages[ $page_id ] = new OptionsPage( $definition, $this->store( $definition ), $this->field_types );
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
