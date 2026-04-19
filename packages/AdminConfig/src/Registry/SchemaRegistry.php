<?php
/**
 * Runtime registry for compiled admin-config schemas.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Registry;

use InvalidArgumentException;
use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Compiler\SchemaCompiler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaRegistry {

	private SchemaCompiler $compiler;

	/**
	 * @var array<string, CompiledSchema>
	 */
	private array $schemas = array();

	public function __construct( ?SchemaCompiler $compiler = null ) {
		$this->compiler = $compiler ?? new SchemaCompiler();
	}

	public function register( array $schema ): CompiledSchema {
		$compiled = $this->compiler->compile( $schema );

		if ( isset( $this->schemas[ $compiled->id() ] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						'Admin config schema "%s" is already registered. The first registration is kept and the duplicate is ignored.',
						$compiled->id()
					),
					'0.2.0'
				);
			}

			return $this->schemas[ $compiled->id() ];
		}

		$this->schemas[ $compiled->id() ] = $compiled;

		return $compiled;
	}

	public function has( string $schema_id ): bool {
		return isset( $this->schemas[ sanitize_key( $schema_id ) ] );
	}

	public function get( string $schema_id ): CompiledSchema {
		$schema_id = sanitize_key( $schema_id );

		if ( ! isset( $this->schemas[ $schema_id ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Admin config schema "%s" is not registered.',
					$schema_id
				)
			);
		}

		return $this->schemas[ $schema_id ];
	}

	/**
	 * @return array<string, CompiledSchema>
	 */
	public function all(): array {
		return $this->schemas;
	}
}
