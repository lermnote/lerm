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
		return $schema->client_config();
	}
}
