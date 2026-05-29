<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress site options.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Framework\Backends;

use Lerm\AdminConfig\Framework\Contracts\StorageBackend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteOptionBackend implements StorageBackend {

	private string $option_name;

	public function __construct( string $option_name ) {
		$this->option_name = sanitize_key( $option_name );
	}

	public function read(): array {
		$data = get_site_option( $this->option_name, array() );
		return is_array( $data ) ? $data : array();
	}

	public function write( array $data ): bool {
		$result = update_site_option( $this->option_name, $data );

		if ( false === $result ) {
			// Compare normalized JSON to avoid int/string type coercion
			// and key-reordering false negatives from the serialize
			// round-trip.
			$stored = get_site_option( $this->option_name );
			if ( ! is_array( $stored ) ) {
				return false;
			}
			$stored_json = wp_json_encode( $stored );
			$data_json   = wp_json_encode( $data );
			return is_string( $stored_json ) && $stored_json === $data_json;
		}

		return $result;
	}

	public function key(): string {
		return 'site_' . $this->option_name;
	}

	public function delete(): bool {
		return delete_site_option( $this->option_name );
	}
}
