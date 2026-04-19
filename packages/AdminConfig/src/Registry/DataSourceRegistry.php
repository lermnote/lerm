<?php
/**
 * Registry for named admin-config data sources.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Registry;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DataSourceRegistry {

	/**
	 * @var array<string, callable>
	 */
	private array $sources = array();

	public function register( string $source_id, callable $resolver ): void {
		$source_id = sanitize_key( $source_id );

		if ( '' === $source_id ) {
			return;
		}

		$this->sources[ $source_id ] = $resolver;
	}

	public function has( string $source_id ): bool {
		return isset( $this->sources[ sanitize_key( $source_id ) ] );
	}

	public function get( string $source_id ): callable {
		$source_id = sanitize_key( $source_id );

		if ( ! isset( $this->sources[ $source_id ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Admin config data source "%s" is not registered.',
					$source_id
				)
			);
		}

		return $this->sources[ $source_id ];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return mixed
	 */
	public function resolve( string $source_id, array $args = array() ) {
		return call_user_func( $this->get( $source_id ), $args );
	}

	/**
	 * @return array<string, callable>
	 */
	public function all(): array {
		return $this->sources;
	}
}
