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
		callable $persist,
		string $validation_message,
		string $failure_message
	): void {
		$success = (bool) $persist( $store, $submitted );

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
}
