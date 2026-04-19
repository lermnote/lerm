<?php
/**
 * Resolve compiled store definitions to concrete WordPress storage backends.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Stores;

use InvalidArgumentException;
use Lerm\AdminConfig\Compiler\CompiledSchema;
use Lerm\AdminConfig\Framework\Backends\CommentMetaBackend;
use Lerm\AdminConfig\Framework\Backends\OptionBackend;
use Lerm\AdminConfig\Framework\Backends\PostMetaBackend;
use Lerm\AdminConfig\Framework\Backends\SiteOptionBackend;
use Lerm\AdminConfig\Framework\Backends\TermMetaBackend;
use Lerm\AdminConfig\Framework\Backends\UserMetaBackend;
use Lerm\AdminConfig\Framework\Contracts\StorageBackend;
use Lerm\AdminConfig\Framework\Framework;
use Lerm\AdminConfig\Framework\Stores\OptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StoreResolver {

	/**
	 * @var array<string, callable>
	 */
	private array $factories = array();

	public function __construct(
		private Framework $framework
	) {
	}

	public function register_factory( string $type, callable $factory ): void {
		$type = sanitize_key( $type );

		if ( '' === $type ) {
			return;
		}

		$this->factories[ $type ] = $factory;
	}

	public function store( CompiledSchema $schema, array $context = array() ): OptionStore {
		return $this->framework->store(
			$schema->definition(),
			$this->resolve_backend( $schema, $context )
		);
	}

	public function resolve_backend( CompiledSchema $schema, array $context = array() ): ?StorageBackend {
		$store = $schema->store();
		$type  = sanitize_key( (string) ( $store['type'] ?? 'option' ) );
		$key   = sanitize_key( (string) ( $store['key'] ?? $schema->id() ) );

		if ( isset( $this->factories[ $type ] ) ) {
			$backend = call_user_func( $this->factories[ $type ], $schema, $context );

			if ( ! $backend instanceof StorageBackend ) {
				throw new InvalidArgumentException(
					sprintf(
						'Custom store factory for "%s" must return a StorageBackend instance.',
						$type
					)
				);
			}

			return $backend;
		}

		return match ( $type ) {
			'option' => new OptionBackend( $key ),
			'post_meta' => new PostMetaBackend( $this->resolve_object_id( $schema, $store, $context, array( 'post_id', 'object_id' ), $type ), $key ),
			'term_meta' => new TermMetaBackend( $this->resolve_object_id( $schema, $store, $context, array( 'term_id', 'object_id' ), $type ), $key ),
			'user_meta' => new UserMetaBackend( $this->resolve_object_id( $schema, $store, $context, array( 'user_id', 'object_id' ), $type ), $key ),
			'comment_meta' => new CommentMetaBackend( $this->resolve_object_id( $schema, $store, $context, array( 'comment_id', 'object_id' ), $type ), $key ),
			'site_option', 'network_option' => new SiteOptionBackend( $key ),
			default => throw new InvalidArgumentException(
				sprintf(
					'Unsupported admin-config store type "%s" for schema "%s".',
					$type,
					$schema->id()
				)
			),
		};
	}

	/**
	 * @param CompiledSchema       $schema
	 * @param array<string, mixed> $store
	 * @param array<string, mixed> $context
	 * @param array<int, string>   $keys
	 */
	private function resolve_object_id( CompiledSchema $schema, array $store, array $context, array $keys, string $type ): int {
		foreach ( $keys as $key ) {
			$value = $context[ $key ] ?? $store[ $key ] ?? null;

			if ( null === $value ) {
				continue;
			}

			$object_id = absint( $value );

			if ( $object_id > 0 ) {
				return $object_id;
			}
		}

		throw new MissingStoreContextException( $schema->id(), $type, $keys );
	}
}
