<?php // phpcs:disable WordPress.Files.FileName
/**
 * Registry for built-in and custom field types.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\FieldTypes;

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

		$current = $this->types[ $type ] ?? array();
		$replace = ! empty( $definition['replace'] );

		if ( ! empty( $current ) && ! $replace ) {
			// Existing field types are extended by default so public extension APIs
			// can add validators, serializers, or client metadata incrementally.
			// Pass `replace => true` to fully replace a custom type definition, or
			// `override_builtin => true` to intentionally replace a built-in type.
			if ( $this->is_builtin( $type ) && empty( $definition['override_builtin'] ) ) {
				$definition = wp_parse_args( $definition, $current );
			} elseif ( empty( $definition['override_builtin'] ) ) {
				$definition = wp_parse_args( $definition, $current );
			}
		}

		$this->types[ $type ] = wp_parse_args(
			$definition,
			array(
				'render'        => null,
				'render_nested' => null,
				'sanitize'      => null,
				'validate'      => null,
				'serialize'     => null,
				'client'        => array(),
				'persist'       => true,
				'builtin'       => false,
			)
		);
	}

	/**
	 * Register or extend a field validator.
	 *
	 * @param callable $validator Signature: (array $field, mixed $value, bool $strict, OptionStore $store): mixed
	 */
	public function register_validator( string $type, callable $validator ): void {
		$this->register(
			$type,
			array(
				'validate' => $validator,
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
	 * Return the nested render callback for a field type.
	 *
	 * @return callable|null
	 */
	public function nested_render_callback( string $type ): ?callable {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['render_nested'] ) || ! is_callable( $this->types[ $type ]['render_nested'] ) ) {
			return null;
		}

		return $this->types[ $type ]['render_nested'];
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
	 * Return the validate callback for a field type.
	 *
	 * @return callable|null
	 */
	public function validate_callback( string $type ): ?callable {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['validate'] ) || ! is_callable( $this->types[ $type ]['validate'] ) ) {
			return null;
		}

		return $this->types[ $type ]['validate'];
	}

	/**
	 * Return the serialize callback for a field type.
	 *
	 * @return callable|null
	 */
	public function serialize_callback( string $type ): ?callable {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['serialize'] ) || ! is_callable( $this->types[ $type ]['serialize'] ) ) {
			return null;
		}

		return $this->types[ $type ]['serialize'];
	}

	/**
	 * Return the client-side config payload for a field type.
	 *
	 * @return array<string, mixed>
	 */
	public function client_config( string $type ): array {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ]['client'] ) || ! is_array( $this->types[ $type ]['client'] ) ) {
			return array();
		}

		return $this->types[ $type ]['client'];
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
	 * Whether values of a field type should be persisted by default.
	 */
	public function persists_value( string $type ): bool {
		$type = sanitize_key( $type );

		if ( ! isset( $this->types[ $type ] ) ) {
			return true;
		}

		return ! array_key_exists( 'persist', $this->types[ $type ] ) || false !== $this->types[ $type ]['persist'];
	}

	/**
	 * Whether a type is a framework built-in (registered by register_defaults).
	 */
	public function is_builtin( string $type ): bool {
		return ! empty( $this->types[ sanitize_key( $type ) ]['builtin'] );
	}
}

