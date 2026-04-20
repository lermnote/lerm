<?php
/**
 * Exception thrown when a meta-backed store is resolved without an object context.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Stores;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MissingStoreContextException extends InvalidArgumentException {

	private string $schema_id;

	private string $store_type;

	/**
	 * @param array<int, string> $keys
	 */
	public function __construct( string $schema_id, string $store_type, array $keys ) {
		$this->schema_id  = $schema_id;
		$this->store_type = $store_type;

		parent::__construct(
			sprintf(
				'Store type "%1$s" for schema "%2$s" requires one of [%3$s] in the schema store config or runtime context. Meta-backed reads should pass context to Runtime::all()/get() or use Runtime::defaults() for compiled fallback values.',
				$store_type,
				$schema_id,
				implode( ', ', array_map( 'strval', $keys ) )
			)
		);
	}

	public function schema_id(): string {
		return $this->schema_id;
	}

	public function store_type(): string {
		return $this->store_type;
	}
}
