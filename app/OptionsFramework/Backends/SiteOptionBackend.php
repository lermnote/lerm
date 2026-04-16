<?php // phpcs:disable WordPress.Files.FileName
/**
 * Storage backend backed by WordPress site options.
 *
 * @package Lerm
 */

declare( strict_types=1 );

namespace Lerm\OptionsFramework\Backends;

use Lerm\OptionsFramework\Contracts\StorageBackend;

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
			$stored = get_site_option( $this->option_name );
			return is_array( $stored ) && $stored === $data;
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
