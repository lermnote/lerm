<?php
/**
 * Shared helpers for native WordPress container save flows.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\WordPress\Support;

use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Framework\Storage\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContainerSaveSupport {

	public static function nonce_name( string $scope, CompiledSchema $schema ): string {
		return 'lerm_admin_config_' . $scope . '_nonce_' . $schema->id();
	}

	public static function nonce_action( string $scope, CompiledSchema $schema ): string {
		return 'lerm_admin_config_' . $scope . '_' . $schema->id();
	}

	public static function posted_nonce( string $nonce_name ): string {
		return isset( $_POST[ $nonce_name ] ) && is_scalar( $_POST[ $nonce_name ] )
			? (string) wp_unslash( $_POST[ $nonce_name ] )
			: '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function submitted_values( OptionStore $store ): array {
		$storage_key = $store->storage_key();

		return isset( $_POST[ $storage_key ] ) && is_array( $_POST[ $storage_key ] )
			? wp_unslash( $_POST[ $storage_key ] )
			: array();
	}

	/**
	 * @param array<string, mixed>                               $submitted
	 * @param callable(OptionStore, array<string, mixed>): bool $persist
	 */
	public static function persist(
		string $scope,
		string $schema_id,
		string $resource_key,
		OptionStore $store,
		array $submitted,
		?callable $persist = null,
		string $validation_message = '',
		string $failure_message = ''
	): void {
		$persist ??= static fn ( OptionStore $s, array $p ): bool => $s->import_all( $p );
		$success   = (bool) $persist( $store, $submitted );

		if ( $store->has_validation_errors() ) {
			ValidationFlash::store(
				$scope,
				$schema_id,
				$resource_key,
				array(
					'class'     => 'notice-error',
					'message'   => $validation_message,
					'errors'    => $store->validation_errors(),
					'submitted' => $submitted,
				)
			);
			return;
		}

		if ( ! $success ) {
			ValidationFlash::store(
				$scope,
				$schema_id,
				$resource_key,
				array(
					'class'   => 'notice-warning',
					'message' => $failure_message,
				)
			);
			return;
		}

		ValidationFlash::clear( $scope, $schema_id, $resource_key );
	}

	/**
	 * Normalize a mixed list of identifiers into a unique array of sanitized keys.
	 *
	 * Accepts a string, an array of strings, or a mixed array — mirrors the
	 * same pattern used for post_types, taxonomies, and other container targets.
	 *
	 * @param mixed $items Raw identifier(s) from the schema definition.
	 * @return array<int, string>
	 */
	public static function normalize_string_list( $items ): array {
		$normalized = array();

		foreach ( is_array( $items ) ? $items : array( $items ) as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}

			$value = sanitize_key( (string) $item );

			if ( '' === $value ) {
				continue;
			}

			$normalized[] = $value;
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Read the capability declared in the schema container, falling back
	 * to the caller-supplied default when no explicit capability is set.
	 */
	public static function capability_for_schema( CompiledSchema $schema, string $fallback ): string {
		$container = $schema->container();

		if ( ! empty( $container['capability'] ) && is_scalar( $container['capability'] ) ) {
			return (string) $container['capability'];
		}

		return $fallback;
	}
}
