<?php // phpcs:disable WordPress.Files.FileName
/**
 * Registry for built-in and custom field types.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldTypeRegistry {

	/**
	 * Registered field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $types = array();

	public function __construct() {
		$this->register_defaults();
	}

	/**
	 * Register a field type.
	 *
	 * @param string               $type Field type name.
	 * @param array<string, mixed> $definition Optional callbacks and metadata.
	 */
	public function register( string $type, array $definition = array() ): void {
		$type = sanitize_key( $type );

		if ( '' === $type ) {
			return;
		}

		if ( isset( $this->types[ $type ] ) && $this->is_builtin( $type ) ) {
			// Built-in types can only be extended (callbacks merged in), not fully replaced.
			// Pass 'override_builtin' => true in $definition to intentionally replace a built-in.
			if ( empty( $definition['override_builtin'] ) ) {
				$definition = wp_parse_args( $definition, $this->types[ $type ] );
			}
		}

		$this->types[ $type ] = wp_parse_args(
			$definition,
			array(
				'render'   => null,
				'sanitize' => null,
				'builtin'  => false,
			)
		);
	}

	/**
	 * Whether the registry knows about a field type.
	 */
	public function has( string $type ): bool {
		return isset( $this->types[ sanitize_key( $type ) ] );
	}

	/**
	 * Return the render callback for a field type.
	 *
	 * @return callable|null
	 */
	public function render_callback( string $type ): ?callable {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['render'] ) || ! is_callable( $this->types[ $type ]['render'] ) ) {
			return null;
		}

		return $this->types[ $type ]['render'];
	}

	/**
	 * Return the sanitize callback for a field type.
	 *
	 * @return callable|null
	 */
	public function sanitize_callback( string $type ): ?callable {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['sanitize'] ) || ! is_callable( $this->types[ $type ]['sanitize'] ) ) {
			return null;
		}

		return $this->types[ $type ]['sanitize'];
	}

	/**
	 * Return all registered field types.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		return $this->types;
	}

	/**
	 * Whether a type is a framework built-in (registered by register_defaults).
	 */
	public function is_builtin( string $type ): bool {
		return ! empty( $this->types[ sanitize_key( $type ) ]['builtin'] );
	}

	/**
	 * Register the MVP built-in field set.
	 */
	private function register_defaults(): void {
		foreach ( array( 'backup_tools', 'button_set', 'checkbox_list', 'code_editor', 'color', 'fieldset', 'gallery', 'group', 'media', 'notice', 'number', 'radio', 'select', 'sorter', 'switcher', 'text', 'textarea', 'url', 'wp_editor' ) as $type ) {
			$this->types[ $type ] = array(
				'render'   => null,
				'sanitize' => null,
				'builtin'  => true, // prevents accidental full replacement
			);
		}
	}
}
