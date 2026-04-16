<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress post meta.
 *
 * Suitable for CPT meta boxes rendered through the admin config runtime.
 *
 * Usage:
 *   $backend = new PostMetaBackend( $post_id, 'my_cpt_settings' );
 *   $store   = new OptionStore( $definition, $field_types, $backend );
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostMetaBackend implements StorageBackend {

	private int $post_id;
	private string $meta_key;

	/**
	 * @param int    $post_id  The post whose meta this backend reads/writes.
	 * @param string $meta_key The single meta key used to store the payload.
	 */
	public function __construct( int $post_id, string $meta_key ) {
		$this->post_id  = $post_id;
		$this->meta_key = sanitize_key( $meta_key );
	}

	public function read(): array {
		$data = get_post_meta( $this->post_id, $this->meta_key, true );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_post_meta( $this->post_id, $this->meta_key, $data );
		return false !== $result;
	}

	public function key(): string {
		return 'post_' . $this->post_id . '_' . $this->meta_key;
	}

	public function delete(): bool {
		return delete_post_meta( $this->post_id, $this->meta_key );
	}
}

