<?php
/**
 * Registry for mountable admin-config containers.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Registry;

use InvalidArgumentException;
use Lerm\AdminConfig\Contracts\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContainerRegistry {

	/**
	 * @var array<string, Container>
	 */
	private array $containers = array();

	public function register( Container $container ): void {
		$this->containers[ $container->type() ] = $container;
	}

	public function has( string $type ): bool {
		return isset( $this->containers[ sanitize_key( $type ) ] );
	}

	public function get( string $type ): Container {
		$type = sanitize_key( $type );

		if ( ! isset( $this->containers[ $type ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Admin config container "%s" is not registered.',
					$type
				)
			);
		}

		return $this->containers[ $type ];
	}

	/**
	 * @return array<string, Container>
	 */
	public function all(): array {
		return $this->containers;
	}
}
