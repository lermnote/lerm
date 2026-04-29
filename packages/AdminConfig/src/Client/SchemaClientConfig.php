<?php
/**
 * Build client-safe schema payloads.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Client;

use Lerm\AdminConfig\Compiler\CompiledSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaClientConfig {

	/**
	 * @return array<string, mixed>
	 */
	public static function from_compiled( CompiledSchema $schema ): array {
		return self::without_server_only_keys( $schema->client_config() );
	}

	/**
	 * @param array<string|int, mixed> $payload
	 * @return array<string|int, mixed>
	 */
	private static function without_server_only_keys( array $payload ): array {
		$clean = array();

		foreach ( $payload as $key => $value ) {
			if ( 'capability' === $key ) {
				continue;
			}

			$clean[ $key ] = is_array( $value ) ? self::without_server_only_keys( $value ) : $value;
		}

		return $clean;
	}
}
