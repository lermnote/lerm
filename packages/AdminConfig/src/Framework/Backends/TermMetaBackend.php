<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress term meta.
 *
 * Usage:
 *   $backend = new TermMetaBackend( $term_id, 'my_term_settings' );
 *   $store   = new OptionStore( $definition, $field_types, $backend );
 *
 * All fields are stored as a single serialised array under one meta key,
 * matching the behaviour of the OptionBackend so that OptionStore can work
 * transparently with either backend.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TermMetaBackend implements StorageBackend {

	private int $term_id;
	private string $meta_key;

	/**
	 * @param int    $term_id  The term whose meta this backend reads/writes.
	 * @param string $meta_key The single meta key used to store the payload.
	 */
	public function __construct( int $term_id, string $meta_key ) {
		$this->term_id  = $term_id;
		$this->meta_key = sanitize_key( $meta_key );
	}

	public function read(): array {
		$data = get_term_meta( $this->term_id, $this->meta_key, true );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_term_meta( $this->term_id, $this->meta_key, $data );

		// update_term_meta returns the meta ID (int) on insert,
		// true on update, false on failure. Normalise to bool.
		return false !== $result;
	}

	public function key(): string {
		return 'term_' . $this->term_id . '_' . $this->meta_key;
	}

	public function delete(): bool {
		return delete_term_meta( $this->term_id, $this->meta_key );
	}
}
