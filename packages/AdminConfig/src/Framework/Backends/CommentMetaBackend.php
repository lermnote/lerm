<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress comment meta.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentMetaBackend implements StorageBackend {

	private int $comment_id;
	private string $meta_key;

	/**
	 * @param int    $comment_id The comment whose meta this backend reads/writes.
	 * @param string $meta_key   The single meta key used to store the payload.
	 */
	public function __construct( int $comment_id, string $meta_key ) {
		$this->comment_id = $comment_id;
		$this->meta_key   = sanitize_key( $meta_key );
	}

	public function read(): array {
		$data = get_comment_meta( $this->comment_id, $this->meta_key, true );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_comment_meta( $this->comment_id, $this->meta_key, $data );
		return false !== $result;
	}

	public function key(): string {
		return 'comment_' . $this->comment_id . '_' . $this->meta_key;
	}

	public function delete(): bool {
		return delete_comment_meta( $this->comment_id, $this->meta_key );
	}
}

