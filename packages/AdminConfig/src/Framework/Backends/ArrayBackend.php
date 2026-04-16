<?php // phpcs:disable WordPress.Files.FileName
/**
 * Ephemeral in-memory storage backend.
 *
 * Useful for rendering schema defaults in add/new forms before a persistent
 * object ID exists, such as taxonomy term creation screens.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ArrayBackend implements StorageBackend {

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(
		private string $backend_key,
		private array $data = array()
	) {
		$this->backend_key = sanitize_key( $this->backend_key );
	}

	public function read(): array {
		return $this->data;
	}

	public function write( array $data ): bool {
		$this->data = $data;
		return true;
	}

	public function key(): string {
		return '' !== $this->backend_key ? $this->backend_key : 'array_backend';
	}

	public function delete(): bool {
		$this->data = array();
		return true;
	}
}

