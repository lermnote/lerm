<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress user meta.
 *
 * Usage:
 *   $backend = new UserMetaBackend( $user_id, 'my_user_settings' );
 *   $store   = new OptionStore( $definition, $field_types, $backend );
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UserMetaBackend implements StorageBackend {

	private int $user_id;
	private string $meta_key;

	/**
	 * @param int    $user_id  The user whose meta this backend reads/writes.
	 * @param string $meta_key The single meta key used to store the payload.
	 */
	public function __construct( int $user_id, string $meta_key ) {
		$this->user_id  = $user_id;
		$this->meta_key = sanitize_key( $meta_key );
	}

	public function read(): array {
		$data = get_user_meta( $this->user_id, $this->meta_key, true );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_user_meta( $this->user_id, $this->meta_key, $data );
		return false !== $result;
	}

	public function key(): string {
		return 'user_' . $this->user_id . '_' . $this->meta_key;
	}

	public function delete(): bool {
		return delete_user_meta( $this->user_id, $this->meta_key );
	}
}
