<?php // phpcs:disable WordPress.Files.FileName
/**
 * Contract for admin config storage backends.
 *
 * Implement this interface to add support for:
 *   - term meta   (TermMetaBackend)
 *   - user meta   (UserMetaBackend)
 *   - post/CPT meta (PostMetaBackend)
 *
 * The OptionStore receives a backend in its constructor.
 * The Framework factory decides which backend to inject based on
 * the 'storage' key in the page/meta-box definition:
 *
 *   'storage' => [ 'type' => 'option' ]                    // default
 *   'storage' => [ 'type' => 'term_meta',  'object_id' => $term_id ]
 *   'storage' => [ 'type' => 'user_meta',  'object_id' => $user_id ]
 *   'storage' => [ 'type' => 'post_meta',  'object_id' => $post_id ]
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface StorageBackend {

	/**
	 * Read the stored payload.
	 *
	 * Must return an array (empty array when nothing is stored yet).
	 *
	 * @return array<string, mixed>
	 */
	public function read(): array;

	/**
	 * Write a new payload.
	 *
	 * Must return true on success, false on DB failure.
	 * Returning true when the payload is identical to what is already
	 * stored ("no-op save") is allowed and expected.
	 *
	 * @param array<string, mixed> $data
	 */
	public function write( array $data ): bool;

	/**
	 * The unique storage key / option-name used by this backend.
	 * Used for cache busting and diagnostics.
	 */
	public function key(): string;

	/**
	 * Delete the stored payload entirely.
	 *
	 * Called on hard-reset or uninstall flows.
	 * Must return true on success, false on DB failure or when nothing existed.
	 */
	public function delete(): bool;
}

